<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('images', function (Blueprint $table) {
            if (! Schema::hasColumn('images', 'url')) {
                $table->string('url')->nullable()->after('external_id');
            }

            if (! Schema::hasColumn('images', 'type')) {
                $table->string('type')->nullable()->after('url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('images', function (Blueprint $table) {
            if (Schema::hasColumn('images', 'type')) {
                $table->dropColumn('type');
            }

            if (Schema::hasColumn('images', 'url')) {
                $table->dropColumn('url');
            }
        });
    }
};
