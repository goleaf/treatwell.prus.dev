<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            if (! Schema::hasColumn('locations', 'postal_code')) {
                $table->string('postal_code')->nullable();
            }
            if (! Schema::hasColumn('locations', 'address_line1')) {
                $table->string('address_line1')->nullable();
            }
            if (! Schema::hasColumn('locations', 'address_line2')) {
                $table->string('address_line2')->nullable();
            }
            if (! Schema::hasColumn('locations', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable();
            }
            if (! Schema::hasColumn('locations', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable();
            }
            if (! Schema::hasColumn('locations', 'map_zoom')) {
                $table->integer('map_zoom')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            //
        });
    }
};
