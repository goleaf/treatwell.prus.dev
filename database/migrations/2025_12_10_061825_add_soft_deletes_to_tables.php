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
        // Add soft deletes to users table
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Add soft deletes to services table
        Schema::table('services', function (Blueprint $table) {
            if (! Schema::hasColumn('services', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Add soft deletes to images table
        Schema::table('images', function (Blueprint $table) {
            if (! Schema::hasColumn('images', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Add soft deletes to ratings table
        Schema::table('ratings', function (Blueprint $table) {
            if (! Schema::hasColumn('ratings', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('images', function (Blueprint $table) {
            if (Schema::hasColumn('images', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('ratings', function (Blueprint $table) {
            if (Schema::hasColumn('ratings', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
