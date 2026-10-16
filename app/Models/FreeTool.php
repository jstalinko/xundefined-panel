<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FreeTool extends Model
{
    use HasFactory;

    public const TYPE_FILE = 'file';
    public const TYPE_URL = 'url';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'url',
        'file_path',
        'file_name',
        'file_size',
        'is_active',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (FreeTool $tool) {
            if (empty($tool->slug)) {
                $baseSlug = Str::slug($tool->name) ?: 'free-tool';
                $slug = $baseSlug;
                $counter = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter++;
                }
                $tool->slug = $slug;
            }
            if (empty($tool->type)) {
                $tool->type = self::TYPE_URL;
            }
        });
    }

    public function isFile(): bool
    {
        return $this->type === self::TYPE_FILE;
    }

    public function isUrl(): bool
    {
        return $this->type === self::TYPE_URL;
    }

    public function getFileSizeHumanAttribute(): ?string
    {
        if (!$this->file_size) {
            return null;
        }

        if ($this->file_size >= 1048576) {
            return round($this->file_size / 1048576, 2) . ' MB';
        }

        if ($this->file_size >= 1024) {
            return round($this->file_size / 1024, 2) . ' KB';
        }

        return $this->file_size . ' B';
    }
}
