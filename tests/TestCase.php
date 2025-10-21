<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use PHPUnit\Framework\ExpectationFailedException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Assert that a given where condition exists in the database.
     *
     * @param  iterable<\Illuminate\Database\Eloquent\Model>|\Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>|string  $table
     * @param  array<string, mixed>  $data
     */
    public function assertDatabaseHas($table, array $data = [], $connection = null, string $message = ''): static
    {
        $messages = [];

        $configuredConnections = config('database.connections', []);
        $configuredConnections = is_array($configuredConnections) ? $configuredConnections : [];

        if (is_string($connection) && ! array_key_exists($connection, $configuredConnections)) {
            $messages[] = $connection;
            $connection = null;
        }

        if ($message !== '') {
            $messages[] = $message;
        }

        try {
            return parent::assertDatabaseHas($table, $data, $connection);
        } catch (ExpectationFailedException $exception) {
            if ($messages !== []) {
                $combinedMessage = implode(PHP_EOL.PHP_EOL, [...$messages, $exception->getMessage()]);

                throw new ExpectationFailedException($combinedMessage, $exception->getComparisonFailure(), $exception);
            }

            throw $exception;
        }
    }
}
