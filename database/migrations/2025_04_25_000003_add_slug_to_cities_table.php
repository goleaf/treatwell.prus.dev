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
        if (Schema::hasTable('cities')) {
            if (! Schema::hasColumn('cities', 'slug')) {
                Schema::table('cities', function (Blueprint $table) {
                    $table->string('slug')->nullable()->after('name');
                });
            }
        } else {
            Schema::create('cities', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('cities') && Schema::hasColumn('cities', 'slug')) {
            Schema::table('cities', function (Blueprint $table) {
                $table->dropColumn('slug');
            });
        }
    }
};
