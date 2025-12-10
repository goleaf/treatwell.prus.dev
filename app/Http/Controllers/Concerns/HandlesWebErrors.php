<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

trait HandlesWebErrors
{
    /**
     * Execute a database operation within a transaction for web controllers.
     */
    protected function executeWebTransaction(callable $operation, string $operationType = 'operation'): mixed
    {
        try {
            return DB::transaction($operation);
        } catch (QueryException $e) {
            Log::error("Database query error in web {$operationType}", [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'sql' => $e->getSql() ?? 'N/A',
                'user_id' => auth()->id(),
                'ip' => request()->ip(),
            ]);

            throw $e;
        } catch (Throwable $e) {
            Log::error("Unexpected error in web {$operationType}", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => auth()->id(),
                'ip' => request()->ip(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle database-specific errors with user-friendly messages for web.
     */
    protected function handleWebDatabaseError(QueryException $e, string $entityType = 'record'): RedirectResponse
    {
        $errorCode = $e->errorInfo[1] ?? $e->getCode();
        $message = 'A database error occurred.';

        // Handle common database constraint violations
        switch ($errorCode) {
            case 1062: // MySQL duplicate entry
            case 23000: // Integrity constraint violation
                if (str_contains($e->getMessage(), 'Duplicate entry')) {
                    $message = "A {$entityType} with this information already exists.";
                } else {
                    $message = 'This operation violates a database constraint.';
                }
                break;

            case 1451: // MySQL foreign key constraint fails on delete
                $message = "Cannot delete this {$entityType} because it is referenced by other data.";
                break;

            case 1452: // MySQL foreign key constraint fails on insert/update
                $message = 'The referenced record does not exist.';
                break;

            case 1048: // MySQL column cannot be null
                $message = 'Required information is missing.';
                break;

            default:
                if (app()->environment('local', 'testing')) {
                    $message = $e->getMessage();
                } else {
                    $message = 'A database error occurred. Please try again.';
                }
        }

        return redirect()
            ->back()
            ->withInput()
            ->withErrors(['error' => $message]);
    }

    /**
     * Handle unexpected errors for web controllers.
     */
    protected function handleWebUnexpectedError(Throwable $e, string $operationType = 'operation'): RedirectResponse
    {
        Log::error("Unexpected error in web {$operationType}", [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
        ]);

        return redirect()
            ->back()
            ->withInput()
            ->withErrors(['error' => 'An unexpected error occurred. Please try again.']);
    }

    /**
     * Log web operation for debugging.
     */
    protected function logWebOperation(string $operation, $model, array $data = []): void
    {
        Log::info("Web {$operation} operation", [
            'model' => is_object($model) ? get_class($model) : $model,
            'id' => is_object($model) ? $model->getKey() : null,
            'data' => $data,
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
        ]);
    }

    /**
     * Validate that a model can be deleted by checking relationships.
     */
    protected function validateWebDeletion($model, array $relationships = []): ?RedirectResponse
    {
        foreach ($relationships as $relationship) {
            if ($model->$relationship()->exists()) {
                $relationshipName = str_replace('_', ' ', $relationship);
                return redirect()
                    ->back()
                    ->withErrors(['error' => "Cannot delete this record because it has associated {$relationshipName}. Please remove them first."]);
            }
        }

        return null;
    }

    /**
     * Get user-friendly database error messages.
     */
    protected function getDatabaseErrorMessage(QueryException $e, string $entityType = 'record'): string
    {
        $errorCode = $e->errorInfo[1] ?? $e->getCode();

        return match ($errorCode) {
            1062, 23000 => str_contains($e->getMessage(), 'Duplicate entry') 
                ? "A {$entityType} with this information already exists." 
                : 'This operation violates a database constraint.',
            1451 => "Cannot delete this {$entityType} because it is referenced by other data.",
            1452 => 'The referenced record does not exist.',
            1048 => 'Required information is missing.',
            default => app()->environment('local', 'testing') 
                ? $e->getMessage() 
                : 'A database error occurred. Please try again.',
        };
    }
}