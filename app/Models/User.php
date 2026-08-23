<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'active_session_id', 'active_installation_id'])]
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

    public function isAdminArea(): bool
    {
        return $this->role === self::ROLE_ADMIN_AREA;
    }

    public function isTeknisi(): bool
    {
        return $this->role === self::ROLE_TEKNISI;
    }

    public function canManageUsers(): bool
    {
        return in_array($this->role, [self::ROLE_DEVELOPER, self::ROLE_SUPERADMIN], true);
    }

    public function canDeleteInvoices(): bool
    {
        return in_array($this->role, [self::ROLE_DEVELOPER, self::ROLE_SUPERADMIN], true);
    }

    public function canDeleteCustomers(): bool
    {
        return in_array($this->role, [self::ROLE_DEVELOPER, self::ROLE_SUPERADMIN], true);
    }

    public function canGenerateInvoices(): bool
    {
        return ! $this->isAdminArea() && ! $this->isTeknisi();
    }

    public function canAccessNetwork(): bool
    {
        return ! $this->isAdminArea() && ! $this->isTeknisi();
    }

    public function canAccessCpes(): bool
    {
        return in_array($this->role, [self::ROLE_DEVELOPER, self::ROLE_SUPERADMIN, self::ROLE_TEKNISI], true);
    }

    public function canAccessAdministration(): bool
    {
        return ! $this->isAdminArea() && ! $this->isTeknisi();
    }

    public function canAccessBilling(): bool
    {
        return ! $this->isTeknisi();
    }

    public function canAccessGudang(): bool
    {
        return ! $this->isAdminArea() && ! $this->isTeknisi();
    }

    public function canAccessPackages(): bool
    {
        return ! $this->isTeknisi();
    }

    public function canAccessAreas(): bool
    {
        return ! $this->isTeknisi();
    }

    public function canAccessSettings(): bool
    {
        return $this->role === self::ROLE_DEVELOPER;
    }

    public function areas(): BelongsToMany
    {
        return $this->belongsToMany(Area::class, 'user_area');
    }

    /**
     * @return array<int, int>
     */
    public function areaIds(): array
    {
        return $this->areas()->pluck('areas.id')->all();
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
            self::ROLE_ADMIN_AREA => 'primary',
            self::ROLE_TEKNISI => 'info',
            default => 'default',
        };
    }

    public static function roles(): array
    {
        return [
            self::ROLE_DEVELOPER => 'Developer',
            self::ROLE_SUPERADMIN => 'Super Admin',
            self::ROLE_ADMIN_AREA => 'Admin Area',
            self::ROLE_TEKNISI => 'Teknisi',
        ];
    }

    public const ROLE_DEVELOPER = 'developer';

    public const ROLE_SUPERADMIN = 'superadmin';

    public const ROLE_ADMIN_AREA = 'admin_area';

    public const ROLE_TEKNISI = 'teknisi';
}
