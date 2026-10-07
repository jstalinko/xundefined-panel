<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlastMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'parse_mode',
        'target_audience',
        'button_text',
        'button_url',
        'image_url',
        'total_recipients',
        'successful_count',
        'failed_count',
        'recipient_details',
        'status',
        'is_test',
    ];

    protected function casts(): array
    {
        return [
            'recipient_details' => 'array',
            'is_test' => 'boolean',
            'total_recipients' => 'integer',
            'successful_count' => 'integer',
            'failed_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getSuccessRateAttribute(): float
    {
        if ($this->total_recipients <= 0) {
            return 0.0;
        }

        return round(($this->successful_count / $this->total_recipients) * 100, 1);
    }

    public function getTargetAudienceLabelAttribute(): string
    {
        return match ($this->target_audience) {
            'all' => 'All Telegram Subscribers',
            'members' => 'Active Members',
            'admins' => 'System Admins',
            'orders' => 'Users with Orders',
            'custom' => 'Custom Chat IDs',
            'test' => 'Test Broadcast',
            default => ucfirst($this->target_audience),
        };
    }
}
