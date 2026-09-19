<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'order_number',
        'invoice',
        'user_id',
        'product_id',
        'price',
        'amount',
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
        'status',
        'download_token',
        'notes',
    ];

    protected $casts = [
        'payment_meta' => 'array',
        'domain_quota' => 'integer',
        'payment_confirms_needed' => 'integer',
        'payment_timeout' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->order_number)) {
                $order->order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(6));
            }
            if (empty($order->invoice)) {
                $order->invoice = 'INV-' . strtoupper(Str::random(6)) . '-' . date('ymd');
            }
            if (empty($order->amount) && !empty($order->price)) {
                $order->amount = $order->price;
            }
            if (empty($order->price) && !empty($order->amount)) {
                $order->price = $order->amount;
            }
            if (empty($order->domain_quota)) {
                $order->domain_quota = 3;
            }
            if (empty($order->payment_method)) {
                $order->payment_method = 'Instant Gateway';
            }
            if (empty($order->status)) {
                $order->status = self::STATUS_PENDING;
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function domains()
    {
        return $this->hasMany(Domain::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }
}
