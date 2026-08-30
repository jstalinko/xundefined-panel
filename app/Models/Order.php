<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'invoice',
        'user_id',
        'product_id',
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
        'status',
    ];

    protected $casts = [
        'payment_meta' => 'array',
        'domain_quota' => 'integer',
        'price' => 'integer',
        'payment_confirms_needed' => 'integer',
        'payment_timeout' => 'integer',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
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
