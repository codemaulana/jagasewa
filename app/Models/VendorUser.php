<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class VendorUser extends Authenticatable implements FilamentUser, HasName
{
    use HasFactory, Notifiable;

    public const ROLE_OWNER = 'owner';

    public const ROLE_OPERATIONAL = 'operational';

    public const ROLE_LABELS = [
        self::ROLE_OWNER => 'Pemilik Toko (Akses Penuh)',
        self::ROLE_OPERATIONAL => 'Staf Operasional (Booking & Serah Terima)',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'vendor_id',
        'name',
        'email',
        'password',
        'role',
        'last_login_at',
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
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Get the vendor that owns the user.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Determine if the vendor user is an owner.
     */
    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    /**
     * Determine if the vendor user is operational staff.
     */
    public function isOperational(): bool
    {
        return in_array($this->role, [self::ROLE_OPERATIONAL, 'staff'], true);
    }

    /**
     * Determine if user has permission to see financial figures and revenue.
     */
    public function canViewFinancials(): bool
    {
        return $this->isOwner();
    }

    /**
     * Determine if user has permission to alter store settings and billing.
     */
    public function canManageSettings(): bool
    {
        return $this->isOwner();
    }

    /**
     * Determine whether the user can access the given Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'vendor') {
            return (bool) ($this->vendor?->is_active ?? false);
        }

        return false;
    }

    /**
     * Get the user's name for Filament displays.
     */
    public function getFilamentName(): string
    {
        return $this->name;
    }
}
