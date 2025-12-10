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
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->morphs('imageable');
            $table->string('external_id')->nullable();
            $table->string('path')->nullable();
            $table->string('uri_small')->nullable();
            $table->string('uri_medium')->nullable();
            $table->string('uri_large')->nullable();
            $table->string('uri_xlarge')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->string('alt_text')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
