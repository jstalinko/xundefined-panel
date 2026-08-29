<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
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

}
