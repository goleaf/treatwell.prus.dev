<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if the table exists first
        if (Schema::hasTable('venues')) {
            // Check if source column exists
            if (Schema::hasColumn('venues', 'source')) {
                // Update the default value of the source column
                Schema::table('venues', function (Blueprint $table) {
                    $table->string('source')->default('treatwell_api')->change();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not implementing a proper down migration as it would be complex
        // and not needed for this one-time fix
    }
};
