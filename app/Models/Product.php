<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'name',
        'pid',
        'price',
        'contents',
        'description',
        'active',
        'published',
    ];

    protected $casts = [
        'contents' => 'array',
        'active' => 'boolean',
        'published' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            if (empty($product->pid)) {
                $product->pid = 'PID-' . strtoupper(Str::random(10));
            }
        });
    }
}

