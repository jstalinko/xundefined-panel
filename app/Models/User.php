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

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'invite_key',
    ];

    /**
     * Role definitions:
     * 0 = Banned
     * 1 = Admin
     * 2 = Member
     * 3 = Inactive
     */
    public const ROLE_BANNED = 0;
    public const ROLE_ADMIN = 1;
    public const ROLE_MEMBER = 2;
    public const ROLE_INACTIVE = 3;

    public function isAdmin(): bool
    {
        return (int) $this->role === self::ROLE_ADMIN;
    }

    public function isMember(): bool
    {
        return (int) $this->role === self::ROLE_MEMBER;
    }

    public function isBanned(): bool
    {
        return (int) $this->role === self::ROLE_BANNED;
    }

    public function isInactive(): bool
    {
        return (int) $this->role === self::ROLE_INACTIVE;
    }

    public function getRoleNameAttribute(): string
    {
        return match ((int) $this->role) {
            self::ROLE_ADMIN => 'System Admin',
            self::ROLE_MEMBER => 'Cyber Operative',
            self::ROLE_BANNED => 'Terminated',
            self::ROLE_INACTIVE => 'Pending Clearance',
            default => 'Unknown Unit',
        };
    }

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
        ];
    }
}
