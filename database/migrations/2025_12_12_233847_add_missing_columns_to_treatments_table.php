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
        Schema::table('treatments', function (Blueprint $table) {
            if (! Schema::hasColumn('treatments', 'external_id')) {
                $table->string('external_id')->nullable()->after('venue_id');
            }
            if (! Schema::hasColumn('treatments', 'min_price')) {
                $table->decimal('min_price', 8, 2)->nullable()->after('price');
            }
            if (! Schema::hasColumn('treatments', 'max_price')) {
                $table->decimal('max_price', 8, 2)->nullable()->after('min_price');
            }
            if (! Schema::hasColumn('treatments', 'min_duration')) {
                $table->integer('min_duration')->nullable()->after('duration');
            }
            if (! Schema::hasColumn('treatments', 'max_duration')) {
                $table->integer('max_duration')->nullable()->after('min_duration');
            }
            if (! Schema::hasColumn('treatments', 'category_id')) {
                $table->string('category_id')->nullable()->after('category');
            }
            if (! Schema::hasColumn('treatments', 'category_name')) {
                $table->string('category_name')->nullable()->after('category_id');
            }
            if (! Schema::hasColumn('treatments', 'options')) {
                $table->json('options')->nullable()->after('category_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('treatments', function (Blueprint $table) {
            $table->dropColumn([
                'external_id',
                'min_price',
                'max_price',
                'min_duration',
                'max_duration',
                'category_id',
                'category_name',
                'options',
            ]);
        });
    }
};
