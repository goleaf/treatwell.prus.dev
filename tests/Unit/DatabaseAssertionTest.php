<?php

namespace Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\ExpectationFailedException;
use Tests\TestCase;

class DatabaseAssertionTest extends TestCase
{
    public function test_assert_database_has_allows_legacy_message_string(): void
    {
        $databasePath = database_path('database.sqlite');

        if (! file_exists($databasePath)) {
            touch($databasePath);
        }

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', $databasePath);

        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('email');
        });

        $legacyMessage = 'Legacy message text';

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage($legacyMessage);

        try {
            $this->assertDatabaseHas('users', ['email' => 'missing@example.com'], $legacyMessage);
        } finally {
            Schema::dropIfExists('users');
        }
    }
}
