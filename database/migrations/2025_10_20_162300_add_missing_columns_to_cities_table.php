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
        if (Schema::hasTable('cities')) {
            Schema::table('cities', function (Blueprint $table) {
                if (!Schema::hasColumn('cities', 'country_id')) {
                    $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete()->after('id');
                }
                if (!Schema::hasColumn('cities', 'normalised_name')) {
                    $table->string('normalised_name')->nullable()->after('slug');
                }
                if (!Schema::hasColumn('cities', 'entity_id')) {
                    $table->string('entity_id')->nullable()->after('normalised_name');
                }
                if (!Schema::hasColumn('cities', 'subregion')) {
                    $table->string('subregion')->nullable()->after('entity_id');
                }
                if (!Schema::hasColumn('cities', 'latitude')) {
                    $table->decimal('latitude', 10, 7)->nullable()->after('subregion');
                }
                if (!Schema::hasColumn('cities', 'longitude')) {
                    $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
                }
                if (!Schema::hasColumn('cities', 'is_main_city')) {
                    $table->boolean('is_main_city')->default(false)->after('longitude');
                }
                if (!Schema::hasColumn('cities', 'main_city_id')) {
                    $table->foreignId('main_city_id')->nullable()->references('id')->on('cities')->nullOnDelete()->after('is_main_city');
                }
                if (!Schema::hasColumn('cities', 'type')) {
                    $table->string('type')->nullable()->after('main_city_id');
                }
                if (!Schema::hasColumn('cities', 'radius_distance')) {
                    $table->integer('radius_distance')->nullable()->after('type');
                }
                if (!Schema::hasColumn('cities', 'radius_unit')) {
                    $table->string('radius_unit')->nullable()->after('radius_distance');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('cities')) {
            Schema::table('cities', function (Blueprint $table) {
                foreach (['country_id', 'normalised_name', 'entity_id', 'subregion', 'latitude', 'longitude', 'is_main_city', 'main_city_id', 'type', 'radius_distance', 'radius_unit'] as $column) {
                    if (Schema::hasColumn('cities', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
