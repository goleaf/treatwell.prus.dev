<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

trait HandlesApiErrors
{
    /**
     * Execute a database operation within a transaction.
     */
    protected function executeInTransaction(callable $operation): mixed
    {
        try {
            return DB::transaction($operation);
        } catch (QueryException $e) {
            Log::error('Database query error in API operation', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'sql' => $e->getSql() ?? 'N/A',
            ]);

            return $this->handleDatabaseError($e);
        } catch (Throwable $e) {
            Log::error('Unexpected error in API operation', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle database-specific errors with user-friendly messages.
     */
    protected function handleDatabaseError(QueryException $e): JsonResponse
    {
        $errorCode = $e->errorInfo[1] ?? $e->getCode();
        $message = 'A database error occurred.';

        // Handle common database constraint violations
        switch ($errorCode) {
            case 1062: // MySQL duplicate entry
            case 23000: // Integrity constraint violation
                if (str_contains($e->getMessage(), 'Duplicate entry')) {
                    $message = 'A record with this information already exists.';
                } else {
                    $message = 'This operation violates a database constraint.';
                }
                break;

            case 1451: // MySQL foreign key constraint fails on delete
                $message = 'Cannot delete this record because it is referenced by other data.';
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
                }
        }

        return response()->json([
            'success' => false,
            'message' => $message,
        ], 422);
    }

    /**
     * Validate that a model can be deleted.
     */
    protected function validateDeletion(Model $model): ?JsonResponse
    {
        // Check if model has related records that would prevent deletion
        $relationships = $this->getCriticalRelationships($model);

        foreach ($relationships as $relationship) {
            if ($model->$relationship()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete this record because it has associated {$relationship}.",
                ], 422);
            }
        }

        return null;
    }

    /**
     * Get critical relationships that prevent deletion.
     * Override this method in controllers to specify model-specific relationships.
     */
    protected function getCriticalRelationships(Model $model): array
    {
        return [];
    }

    /**
     * Validate that related models exist and are accessible.
     */
    protected function validateRelatedModels(array $data, array $relationships): ?JsonResponse
    {
        foreach ($relationships as $field => $modelClass) {
            if (isset($data[$field])) {
                $relatedModel = $modelClass::find($data[$field]);

                if (! $relatedModel) {
                    return response()->json([
                        'success' => false,
                        'message' => "The selected {$field} is invalid.",
                        'errors' => [
                            $field => ["The selected {$field} does not exist."],
                        ],
                    ], 422);
                }

                // Check if the related model is soft deleted
                if (method_exists($relatedModel, 'trashed') && $relatedModel->trashed()) {
                    return response()->json([
                        'success' => false,
                        'message' => "The selected {$field} is no longer available.",
                        'errors' => [
                            $field => ["The selected {$field} has been deleted."],
                        ],
                    ], 422);
                }
            }
        }

        return null;
    }

    /**
     * Handle bulk operation errors.
     */
    protected function handleBulkOperationErrors(array $results): JsonResponse
    {
        $successful = collect($results)->where('success', true)->count();
        $failed = collect($results)->where('success', false);
        $total = count($results);

        if ($failed->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => "All {$total} operations completed successfully.",
                'results' => $results,
            ]);
        }

        if ($successful === 0) {
            return response()->json([
                'success' => false,
                'message' => 'All operations failed.',
                'results' => $results,
            ], 422);
        }

        return response()->json([
            'success' => false,
            'message' => "{$successful} of {$total} operations completed successfully.",
            'results' => $results,
        ], 207); // Multi-Status
    }

    /**
     * Check for concurrent modifications using updated_at timestamp.
     */
    protected function checkConcurrentModification(Model $model, ?string $lastModified): ?JsonResponse
    {
        if ($lastModified && $model->updated_at->toISOString() !== $lastModified) {
            return response()->json([
                'success' => false,
                'message' => 'This record has been modified by another user. Please refresh and try again.',
                'current_version' => $model->updated_at->toISOString(),
            ], 409); // Conflict
        }

        return null;
    }

    /**
     * Log API operation for debugging.
     */
    protected function logApiOperation(string $operation, Model $model, array $data = []): void
    {
        Log::info("API {$operation} operation", [
            'model' => get_class($model),
            'id' => $model->getKey(),
            'data' => $data,
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
        ]);
    }
}
