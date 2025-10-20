<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('locations') && !Schema::hasColumn('locations', 'map_zoom')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->integer('map_zoom')->nullable()->after('longitude');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('locations') && Schema::hasColumn('locations', 'map_zoom')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->dropColumn('map_zoom');
            });
        }
    }
};
