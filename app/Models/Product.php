<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'pid',
        'price',
        'contents',
        'description',
        'category',
        'status',
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
            if (empty($product->slug) && !empty($product->name)) {
                $product->slug = Str::slug($product->name);
            }
            if (!isset($product->active) && isset($product->status)) {
                $product->active = $product->status === 'active';
            }
            if (!isset($product->status) && isset($product->active)) {
                $product->status = $product->active ? 'active' : 'inactive';
            }
            if (!isset($product->published)) {
                $product->published = true;
            }
        });
    }

    /**
     * Get the latest release from contents array.
     */
    public function getLatestReleaseAttribute(): ?array
    {
        $contents = $this->contents;
        if (is_string($contents)) {
            $contents = json_decode($contents, true);
        }
        if (is_array($contents) && count($contents) > 0) {
            $first = $contents[0];
            if (!isset($first['md5checksum']) && isset($first['md5sum'])) {
                $first['md5checksum'] = $first['md5sum'];
            }
            if (!isset($first['md5sum']) && isset($first['md5checksum'])) {
                $first['md5sum'] = $first['md5checksum'];
            }
            return $first;
        }
        return null;
    }

    /**
     * Dynamic version accessor extracted from contents.
     */
    public function getVersionAttribute(): string
    {
        $latest = $this->latest_release;
        return $latest['version'] ?? '1.0.0';
    }

    /**
     * Dynamic download file accessor extracted from contents.
     */
    public function getDownloadFileAttribute(): ?string
    {
        $latest = $this->latest_release;
        return $latest['file'] ?? null;
    }

    /**
     * Dynamic md5 checksum accessor extracted from contents.
     */
    public function getMd5ChecksumAttribute(): ?string
    {
        $latest = $this->latest_release;
        return $latest['md5checksum'] ?? ($latest['md5sum'] ?? null);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function domains()
    {
        return $this->hasMany(Domain::class);
    }
}
