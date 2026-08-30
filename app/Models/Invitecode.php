<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Invitecode extends Model
{
    use HasFactory;

    protected $table = 'invitecodes';

    protected $fillable = [
        'code',
        'expired_at',
        'used_at',
        'used',
        'used_by_user_id',
        'generate_via',
        'products_id',
    ];

    protected $casts = [
        'expired_at' => 'datetime',
        'used_at' => 'datetime',
        'used' => 'boolean',
        'products_id' => 'array'
    ];

    /**
     * Check if invite code is valid for registration.
     */
    public function isValid(): bool
    {
        if ($this->used) {
            return false;
        }

        if ($this->expired_at && $this->expired_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if invite code has expired.
     */
    public function isExpired(): bool
    {
        return $this->expired_at && $this->expired_at->isPast();
    }

    /**
     * Get human-readable status badge.
     */
    public function getStatusLabelAttribute(): string
    {
        if ($this->used) {
            return 'CLAIMED';
        }

        if ($this->isExpired()) {
            return 'EXPIRED';
        }

        return 'ACTIVE';
    }

    /**
     * Relationship to user who claimed the invite code.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by_user_id');
    }

    /**
     * Mark code as used by given user.
     */
    public function markAsUsed(User $user): void
    {
        $this->update([
            'used' => true,
            'used_at' => now(),
            'used_by_user_id' => $user->id,
        ]);
    }

    /**
     * Generate unique random invite code (e.g. XU-8F3A-9B2C).
     */
    public static function generateCode(string $prefix = 'XU'): string
    {
        do {
            $part1 = strtoupper(Str::random(4));
            $part2 = strtoupper(Str::random(4));
            $code = "{$prefix}-{$part1}-{$part2}";
        } while (self::where('code', $code)->exists());

        return $code;
    }
}
