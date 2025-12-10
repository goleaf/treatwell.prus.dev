<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ratings')) {
            Schema::table('ratings', function (Blueprint $table) {
                if (! Schema::hasColumn('ratings', 'value')) {
                    $table->decimal('value', 4, 2)->nullable()->after('venue_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ratings')) {
            Schema::table('ratings', function (Blueprint $table) {
                if (Schema::hasColumn('ratings', 'value')) {
                    $table->dropColumn('value');
                }
            });
        }
    }
};
