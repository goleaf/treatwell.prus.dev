<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds external_id column to locations table with index for performance.
     */
    public function up(): void
    {
        if (Schema::hasTable('locations')) {
            Schema::table('locations', function (Blueprint $table) {
                if (! Schema::hasColumn('locations', 'external_id')) {
                    $table->string('external_id')->nullable()->after('id')->index();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * Removes external_id column and its index from locations table.
     */
    public function down(): void
    {
        if (Schema::hasTable('locations')) {
            Schema::table('locations', function (Blueprint $table) {
                if (Schema::hasColumn('locations', 'external_id')) {
                    $table->dropIndex(['external_id']);
                    $table->dropColumn('external_id');
                }
            });
        }
    }
};
