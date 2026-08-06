<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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

    public function isDeveloper(): bool
    {
        return $this->role === self::ROLE_DEVELOPER;
    }

    public function isSuperadmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function canManageUsers(): bool
    {
        return in_array($this->role, [self::ROLE_DEVELOPER, self::ROLE_SUPERADMIN], true);
    }

    public function canAccessSettings(): bool
    {
        return $this->role === self::ROLE_DEVELOPER;
    }

    public function routeNotificationForFcm($notification): array
    {
        return DeviceToken::forUser(DeviceToken::TYPE_ADMIN, $this->id)
            ->pluck('token')
            ->all();
    }

    public function roleLabel(): string
    {
        return self::roles()[$this->role] ?? ucfirst($this->role);
    }

    public function roleColor(): string
    {
        return match ($this->role) {
            self::ROLE_DEVELOPER => 'danger',
            self::ROLE_SUPERADMIN => 'warning',
            default => 'default',
        };
    }

    public static function roles(): array
    {
        return [
            self::ROLE_DEVELOPER => 'Developer',
            self::ROLE_SUPERADMIN => 'Super Admin',
            self::ROLE_ADMIN => 'Admin',
        ];
    }

    public const ROLE_DEVELOPER = 'developer';

    public const ROLE_SUPERADMIN = 'superadmin';

    public const ROLE_ADMIN = 'admin';
}
