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
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'invite_key')) {
                $table->renameColumn('invite_key', 'account_key');
            } elseif (!Schema::hasColumn('users', 'account_key')) {
                $table->string('account_key', 32)->nullable()->unique()->after('role');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'account_key')) {
                $table->renameColumn('account_key', 'invite_key');
            }
        });
    }
};
