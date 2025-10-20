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
        Schema::table('venues', function (Blueprint $table) {
            if (!Schema::hasColumn('venues', 'description')) {
                $table->text('description')->nullable();
            }
            
            if (!Schema::hasColumn('venues', 'external_id')) {
                $table->string('external_id')->nullable();
            }
            
            if (!Schema::hasColumn('venues', 'raw_data')) {
                $table->json('raw_data')->nullable();
            }
            
            if (!Schema::hasColumn('venues', 'type_id')) {
                $table->string('type_id')->nullable();
            }
            
            if (!Schema::hasColumn('venues', 'type_name')) {
                $table->string('type_name')->nullable();
            }
            
            if (!Schema::hasColumn('venues', 'normalised_name')) {
                $table->string('normalised_name')->nullable();
            }
            
            if (!Schema::hasColumn('venues', 'desktop_uri')) {
                $table->string('desktop_uri')->nullable();
            }
            
            if (!Schema::hasColumn('venues', 'mobile_uri')) {
                $table->string('mobile_uri')->nullable();
            }
            
            if (!Schema::hasColumn('venues', 'app_uri')) {
                $table->string('app_uri')->nullable();
            }
            
            if (!Schema::hasColumn('venues', 'is_new_venue')) {
                $table->boolean('is_new_venue')->default(false);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->dropColumn([
                'description',
                'external_id',
                'raw_data',
                'type_id',
                'type_name',
                'normalised_name',
                'desktop_uri',
                'mobile_uri',
                'app_uri',
                'is_new_venue'
            ]);
        });
    }
};
