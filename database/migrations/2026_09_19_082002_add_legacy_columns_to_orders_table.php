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
            if (!Schema::hasColumn('orders', 'invoice')) {
                $table->string('invoice', 64)->nullable()->unique()->after('order_number');
            }
            if (!Schema::hasColumn('orders', 'price')) {
                $table->decimal('price', 10, 2)->nullable()->after('amount');
            }
            if (!Schema::hasColumn('orders', 'domain_quota')) {
                $table->integer('domain_quota')->default(3)->after('price');
            }
            if (!Schema::hasColumn('orders', 'payment_method')) {
                $table->string('payment_method', 64)->default('Balance')->after('domain_quota');
            }
            if (!Schema::hasColumn('orders', 'txn_id')) {
                $table->string('txn_id')->nullable()->index()->after('payment_method');
            }
            if (!Schema::hasColumn('orders', 'payment_address')) {
                $table->string('payment_address')->nullable()->after('txn_id');
            }
            if (!Schema::hasColumn('orders', 'payment_dest_tag')) {
                $table->string('payment_dest_tag')->nullable()->after('payment_address');
            }
            if (!Schema::hasColumn('orders', 'payment_currency')) {
                $table->string('payment_currency')->nullable()->after('payment_dest_tag');
            }
            if (!Schema::hasColumn('orders', 'payment_amount')) {
                $table->string('payment_amount')->nullable()->after('payment_currency');
            }
            if (!Schema::hasColumn('orders', 'payment_confirms_needed')) {
                $table->integer('payment_confirms_needed')->nullable()->after('payment_amount');
            }
            if (!Schema::hasColumn('orders', 'payment_timeout')) {
                $table->integer('payment_timeout')->nullable()->after('payment_confirms_needed');
            }
            if (!Schema::hasColumn('orders', 'payment_status_url')) {
                $table->text('payment_status_url')->nullable()->after('payment_timeout');
            }
            if (!Schema::hasColumn('orders', 'payment_qrcode_url')) {
                $table->text('payment_qrcode_url')->nullable()->after('payment_status_url');
            }
            if (!Schema::hasColumn('orders', 'payment_meta')) {
                $table->json('payment_meta')->nullable()->after('payment_qrcode_url');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $columnsToDrop = [];
            $cols = [
                'invoice',
                'price',
                'domain_quota',
                'payment_method',
                'txn_id',
                'payment_address',
                'payment_dest_tag',
                'payment_currency',
                'payment_amount',
                'payment_confirms_needed',
                'payment_timeout',
                'payment_status_url',
                'payment_qrcode_url',
                'payment_meta',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $columnsToDrop[] = $col;
                }
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
