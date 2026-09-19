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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('category')->default('Website Script');
            $table->string('version')->default('v1.0.0');
            $table->decimal('price', 10, 2)->default(0.00);
            $table->string('demo_url')->nullable();
            $table->string('download_url')->nullable();
            $table->string('documentation_url')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('stock')->default(-1); // -1 = unlimited
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
