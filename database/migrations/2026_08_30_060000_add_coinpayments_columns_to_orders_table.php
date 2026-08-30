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
            $table->string('txn_id')->nullable()->index()->after('payment_method');
            $table->string('payment_address')->nullable()->after('txn_id');
            $table->string('payment_dest_tag')->nullable()->after('payment_address');
            $table->string('payment_currency')->nullable()->after('payment_dest_tag');
            $table->string('payment_amount')->nullable()->after('payment_currency');
            $table->integer('payment_confirms_needed')->nullable()->after('payment_amount');
            $table->integer('payment_timeout')->nullable()->after('payment_confirms_needed');
            $table->text('payment_status_url')->nullable()->after('payment_timeout');
            $table->text('payment_qrcode_url')->nullable()->after('payment_status_url');
            $table->json('payment_meta')->nullable()->after('payment_qrcode_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
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
            ]);
        });
    }
};
