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
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'license_key')) {
                try {
                    $table->dropUnique(['license_key']);
                } catch (\Throwable $e) {
                }
                $table->dropColumn('license_key');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'license_key')) {
                $table->string('license_key')->nullable()->unique();
            }
        });
    }
};
