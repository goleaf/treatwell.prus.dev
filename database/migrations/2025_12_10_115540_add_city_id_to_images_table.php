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
        Schema::table('images', function (Blueprint $table) {
            $table->foreignId('city_id')->nullable()->after('venue_id')->constrained()->onDelete('cascade');
            $table->index(['city_id', 'is_primary']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->dropIndex(['city_id', 'is_primary']);
            $table->dropForeign(['city_id']);
            $table->dropColumn('city_id');
        });
    }
};