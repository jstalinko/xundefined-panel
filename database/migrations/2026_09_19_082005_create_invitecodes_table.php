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
        if (!Schema::hasTable('invitecodes')) {
            Schema::create('invitecodes', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->dateTime('expired_at')->nullable();
                $table->dateTime('used_at')->nullable();
                $table->boolean('used')->default(false);
                $table->unsignedBigInteger('used_by_user_id')->nullable();
                $table->string('generate_via')->default('admin');
                $table->json('products_id')->nullable();
                $table->timestamps();

                $table->foreign('used_by_user_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitecodes');
    }
};
