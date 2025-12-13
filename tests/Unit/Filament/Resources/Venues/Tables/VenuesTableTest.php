<?php

namespace Tests\Unit\Filament\Resources\Venues\Tables;

use Tests\TestCase;

class VenuesTableTest extends TestCase
{
    public function test_configure_exists(): void
    {
        $this->assertTrue(method_exists(\App\Filament\Resources\Venues\Tables\VenuesTable::class, 'configure'));
    }
}
