<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'price',
        'contents',
        'description',
        'active',
    ];


    protected $casts = [
        'contents' => 'array',
    ];


}
