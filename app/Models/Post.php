<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'content',
        'category',
        'image',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Post $post) {
            if (empty($post->slug) && !empty($post->title)) {
                $post->slug = Str::slug($post->title);
            }
        });
    }
}
