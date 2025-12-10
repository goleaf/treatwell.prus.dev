<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('opening_hours')) {
            Schema::table('opening_hours', function (Blueprint $table) {
                if (! Schema::hasColumn('opening_hours', 'day')) {
                    $table->unsignedTinyInteger('day')->nullable()->after('venue_id');
                }

                if (! Schema::hasColumn('opening_hours', 'open_time')) {
                    $table->time('open_time')->nullable()->after('day');
                }

                if (! Schema::hasColumn('opening_hours', 'close_time')) {
                    $table->time('close_time')->nullable()->after('open_time');
                }

                if (! Schema::hasColumn('opening_hours', 'is_closed')) {
                    $table->boolean('is_closed')->default(false)->after('close_time');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('opening_hours')) {
            Schema::table('opening_hours', function (Blueprint $table) {
                if (Schema::hasColumn('opening_hours', 'is_closed')) {
                    $table->dropColumn('is_closed');
                }

                if (Schema::hasColumn('opening_hours', 'close_time')) {
                    $table->dropColumn('close_time');
                }

                if (Schema::hasColumn('opening_hours', 'open_time')) {
                    $table->dropColumn('open_time');
                }

                if (Schema::hasColumn('opening_hours', 'day')) {
                    $table->dropColumn('day');
                }
            });
        }
    }
};
