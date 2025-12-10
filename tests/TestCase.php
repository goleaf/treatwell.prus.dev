<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use PHPUnit\Framework\ExpectationFailedException;

abstract class TestCase extends BaseTestCase
{
    public function assertDatabaseHas($table, array $data = [], $connection = null, string $message = '')
    {
        $legacyMessage = null;

        if (is_string($connection) && $connection !== '' && ! array_key_exists($connection, config('database.connections', []))) {
            $legacyMessage = $connection;
            $connection = null;
        }

        $messages = array_filter([$legacyMessage, $message], static fn (?string $value) => $value !== null && $value !== '');

        try {
            return parent::assertDatabaseHas($table, $data, $connection);
        } catch (ExpectationFailedException $exception) {
            if ($legacyMessage === null && $message === '') {
                throw $exception;
            }

            $combinedMessage = trim(implode(PHP_EOL, array_merge($messages, [$exception->getMessage()])));

            throw new ExpectationFailedException($combinedMessage, $exception->getComparisonFailure());
        }
    }
}
