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
        Schema::table('domains', function (Blueprint $table) {
            if (!Schema::hasColumn('domains', 'hits')) {
                $table->integer('hits')->default(0)->after('ip_address');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            if (Schema::hasColumn('domains', 'hits')) {
                $table->dropColumn('hits');
            }
        });
    }
};
