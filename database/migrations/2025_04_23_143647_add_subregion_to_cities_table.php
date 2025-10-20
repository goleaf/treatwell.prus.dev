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
        // First check if normalised_name exists
        if (Schema::hasColumn('cities', 'normalised_name')) {
            Schema::table('cities', function (Blueprint $table) {
                $table->string('subregion')->nullable()->after('normalised_name');
                $table->boolean('is_main_city')->default(false)->after('type');
                $table->unsignedBigInteger('main_city_id')->nullable()->after('is_main_city');

                $table->foreign('main_city_id')->references('id')->on('cities');
            });
        } else {
            // If normalised_name doesn't exist, just add it at the end
            Schema::table('cities', function (Blueprint $table) {
                $table->string('subregion')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropForeign(['main_city_id']);
            $table->dropColumn(['subregion', 'is_main_city', 'main_city_id']);
        });
    }
};
