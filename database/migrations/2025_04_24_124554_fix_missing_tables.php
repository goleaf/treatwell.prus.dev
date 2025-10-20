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
        // Ensure we don't have any lingering reference to venues_old
        try {
            $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table'");
            foreach ($tables as $table) {
                if ($table->name === 'venues_old') {
                    Schema::dropIfExists('venues_old');
                }
            }
        } catch (\Exception $e) {
            // Ignore any errors
        }
        
        // Create procedure_venue pivot table if it doesn't exist
        if (!Schema::hasTable('procedure_venue')) {
            Schema::create('procedure_venue', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('procedure_id');
                $table->unsignedBigInteger('venue_id');
                $table->timestamps();
                
                // Add unique constraint
                $table->unique(['procedure_id', 'venue_id']);
            });
        }
        
        // Create procedures table if it doesn't exist
        if (!Schema::hasTable('procedures')) {
            Schema::create('procedures', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }
        
        // Create ratings table if it doesn't exist
        if (!Schema::hasTable('ratings')) {
            Schema::create('ratings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('venue_id');
                $table->decimal('average', 3, 2)->nullable();
                $table->integer('count')->nullable();
                $table->json('dimensions')->nullable();
                $table->timestamps();
                
                $table->foreign('venue_id')->references('id')->on('venues')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not implementing down migration as this is a fix
    }
};
