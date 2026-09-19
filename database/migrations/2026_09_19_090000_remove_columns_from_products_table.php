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
        Schema::table('products', function (Blueprint $table) {
            $columnsToDrop = [];
            foreach (['version', 'demo_url', 'documentation_url', 'stock', 'download_url'] as $col) {
                if (Schema::hasColumn('products', $col)) {
                    $columnsToDrop[] = $col;
                }
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('version')->nullable()->after('category');
            $table->string('demo_url')->nullable()->after('price');
            $table->string('download_url')->nullable()->after('demo_url');
            $table->string('documentation_url')->nullable()->after('download_url');
            $table->integer('stock')->default(-1)->after('published');
        });
    }
};
