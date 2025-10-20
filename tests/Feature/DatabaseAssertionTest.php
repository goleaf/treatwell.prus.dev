<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\ExpectationFailedException;
use Tests\TestCase;

class DatabaseAssertionTest extends TestCase
{
    public function test_assert_database_has_handles_legacy_message_parameter(): void
    {
        Schema::dropIfExists('assert_database_has_legacy_message');

        Schema::create('assert_database_has_legacy_message', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });

        try {
            $this->expectException(ExpectationFailedException::class);
            $this->expectExceptionMessage('legacy assertion message');

            $this->assertDatabaseHas('assert_database_has_legacy_message', ['name' => 'missing'], 'legacy assertion message');
        } finally {
            Schema::dropIfExists('assert_database_has_legacy_message');
        }
    }
}
