<?php

namespace Tests\Unit\Http\Controllers\Concerns;

use App\Http\Controllers\Concerns\HandlesWebErrors;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class HandlesWebErrorsTest extends TestCase
{
    protected $testController;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test controller that uses the trait
        $this->testController = new class
        {
            use HandlesWebErrors;

            public function test_execute_web_transaction(callable $operation, string $operationType = 'operation')
            {
                return $this->executeWebTransaction($operation, $operationType);
            }

            public function test_handle_web_database_error(QueryException $e, string $entityType = 'record')
            {
                return $this->handleWebDatabaseError($e, $entityType);
            }

            public function test_log_web_operation(string $operation, $model, array $data = [])
            {
                return $this->logWebOperation($operation, $model, $data);
            }

            public function test_validate_web_deletion($model, array $relationships = [])
            {
                return $this->validateWebDeletion($model, $relationships);
            }

            public function test_get_database_error_message(QueryException $e, string $entityType = 'record')
            {
                return $this->getDatabaseErrorMessage($e, $entityType);
            }
        };
    }

    /**
     * Test executeWebTransaction executes operation successfully.
     */
    public function test_execute_web_transaction_executes_successfully(): void
    {
        $result = $this->testController->testExecuteWebTransaction(function () {
            return 'success';
        });

        $this->assertEquals('success', $result);
    }

    /**
     * Test executeWebTransaction logs database errors.
     */
    public function test_execute_web_transaction_logs_database_errors(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Database query error in web test operation', \Mockery::type('array'));

        $this->expectException(QueryException::class);

        $this->testController->testExecuteWebTransaction(function () {
            // Simulate a database error
            throw new QueryException('test', 'SELECT * FROM invalid_table', [], new \Exception('Table not found'));
        }, 'test operation');
    }

    /**
     * Test handleWebDatabaseError returns redirect with error.
     */
    public function test_handle_web_database_error_returns_redirect(): void
    {
        $exception = new QueryException('test', 'SELECT * FROM test', [], new \Exception('Duplicate entry'));
        $exception->errorInfo = [null, 1062, 'Duplicate entry'];

        $result = $this->testController->testHandleWebDatabaseError($exception, 'venue');

        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    /**
     * Test logWebOperation logs with proper context.
     */
    public function test_log_web_operation_logs_with_context(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Web test operation', \Mockery::on(function ($context) {
                return isset($context['ip']) &&
                       isset($context['model']) &&
                       $context['id'] === null; // Since we're passing a string
            }));

        $this->testController->testLogWebOperation('test', 'TestModel', ['test' => 'data']);
    }

    /**
     * Test validateWebDeletion returns null when no relationships exist.
     */
    public function test_validate_web_deletion_returns_null_when_no_relationships(): void
    {
        $venue = new \stdClass;

        $result = $this->testController->testValidateWebDeletion($venue, []);

        $this->assertNull($result);
    }

    /**
     * Test getDatabaseErrorMessage returns appropriate messages.
     */
    public function test_get_database_error_message_returns_appropriate_messages(): void
    {
        // Test duplicate entry error
        $exception = new QueryException('test', 'SELECT * FROM test', [], new \Exception('Duplicate entry'));
        $exception->errorInfo = [null, 1062, 'Duplicate entry'];

        $message = $this->testController->testGetDatabaseErrorMessage($exception, 'venue');
        $this->assertEquals('A venue with this information already exists.', $message);

        // Test foreign key constraint error
        $exception = new QueryException('test', 'DELETE FROM test', [], new \Exception('Foreign key constraint'));
        $exception->errorInfo = [null, 1451, 'Foreign key constraint'];

        $message = $this->testController->testGetDatabaseErrorMessage($exception, 'venue');
        $this->assertEquals('Cannot delete this venue because it is referenced by other data.', $message);

        // Test missing reference error
        $exception = new QueryException('test', 'INSERT INTO test', [], new \Exception('Foreign key constraint'));
        $exception->errorInfo = [null, 1452, 'Foreign key constraint'];

        $message = $this->testController->testGetDatabaseErrorMessage($exception, 'venue');
        $this->assertEquals('The referenced record does not exist.', $message);

        // Test null constraint error
        $exception = new QueryException('test', 'INSERT INTO test', [], new \Exception('Column cannot be null'));
        $exception->errorInfo = [null, 1048, 'Column cannot be null'];

        $message = $this->testController->testGetDatabaseErrorMessage($exception, 'venue');
        $this->assertEquals('Required information is missing.', $message);

        // Test default error in production
        app()->instance('env', 'production');
        $exception = new QueryException('test', 'SELECT * FROM test', [], new \Exception('Unknown error'));
        $exception->errorInfo = [null, 9999, 'Unknown error'];

        $message = $this->testController->testGetDatabaseErrorMessage($exception, 'venue');
        $this->assertEquals('A database error occurred. Please try again.', $message);
    }

    /**
     * Test getDatabaseErrorMessage returns detailed message in local environment.
     */
    public function test_get_database_error_message_returns_detailed_message_in_local(): void
    {
        app()->instance('env', 'local');

        $exception = new QueryException('test', 'SELECT * FROM test', [], new \Exception('Detailed error message'));
        $exception->errorInfo = [null, 9999, 'Unknown error'];

        $message = $this->testController->testGetDatabaseErrorMessage($exception, 'venue');
        $this->assertStringContainsString('Detailed error message', $message);
    }
}
