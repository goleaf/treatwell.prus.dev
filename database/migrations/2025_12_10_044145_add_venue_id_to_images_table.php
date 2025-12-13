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
            if (! Schema::hasColumn('images', 'venue_id')) {
                $table->unsignedBigInteger('venue_id')->nullable()->after('id');
                $table->foreign('venue_id')->references('id')->on('venues')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('images', function (Blueprint $table) {
            if (Schema::hasColumn('images', 'venue_id')) {
                $table->dropForeign(['venue_id']);
                $table->dropColumn('venue_id');
            }
        });
    }
};
