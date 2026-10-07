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
        Schema::create('blast_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->nullable();
            $table->text('message');
            $table->string('parse_mode')->default('Markdown');
            $table->string('target_audience')->default('all');
            $table->string('button_text')->nullable();
            $table->string('button_url')->nullable();
            $table->string('image_url')->nullable();
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('successful_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->json('recipient_details')->nullable();
            $table->string('status')->default('completed');
            $table->boolean('is_test')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blast_messages');
    }
};
