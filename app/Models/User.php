<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_BANNED = 0;
    public const ROLE_ADMIN = 1;
    public const ROLE_MEMBER = 2;
    public const ROLE_INACTIVE = 3;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'telegram_id',
        'telegram_username',
        'balance',
        'role',
        'account_key',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'balance' => 'decimal:2',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin' || (is_numeric($this->role) && (int) $this->role === self::ROLE_ADMIN);
    }

    public function isMember(): bool
    {
        return $this->role === 'user' || (is_numeric($this->role) && (int) $this->role === self::ROLE_MEMBER);
    }

    public function isBanned(): bool
    {
        return $this->role === 'banned' || (is_numeric($this->role) && (int) $this->role === self::ROLE_BANNED);
    }

    public function isInactive(): bool
    {
        return $this->role === 'inactive' || (is_numeric($this->role) && (int) $this->role === self::ROLE_INACTIVE);
    }

    public function getRoleNameAttribute(): string
    {
        if ($this->isAdmin()) {
            return 'System Admin';
        }
        if ($this->isBanned()) {
            return 'Terminated';
        }
        if ($this->isInactive()) {
            return 'Pending Clearance';
        }
        return 'Cyber Operative';
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function domains()
    {
        return $this->hasMany(Domain::class);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class)->latest();
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class)->latest();
    }

    public function usedInviteCodes()
    {
        return $this->hasMany(Invitecode::class, 'used_by_user_id');
    }
}
