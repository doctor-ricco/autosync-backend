<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, HasApiTokens;

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
        'phone',
        'avatar_url',
        'stand_id',
        'commission_rate',
        'is_active',
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
        'created_at',
        'updated_at',
        'deleted_at',
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
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'commission_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the stand that the user belongs to.
     */
    public function stand(): BelongsTo
    {
        return $this->belongsTo(Stand::class);
    }

    /**
     * Get the sales made by this user (if seller).
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'seller_id');
    }

    /**
     * Get the inquiries assigned to this user.
     */
    public function assignedInquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class, 'assigned_to');
    }

    /**
     * Get the favorites for this user.
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * Get the audit logs created by this user.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include users with a specific role.
     */
    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope a query to only include sellers.
     */
    public function scopeSellers($query)
    {
        return $query->where('role', 'seller');
    }

    /**
     * Scope a query to only include managers.
     */
    public function scopeManagers($query)
    {
        return $query->where('role', 'manager');
    }

    /**
     * Scope a query to only include admins.
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    /**
     * Scope a query to only include users from a specific stand.
     */
    public function scopeByStand($query, $standId)
    {
        return $query->where('stand_id', $standId);
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if the user is a manager.
     */
    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    /**
     * Check if the user is a seller.
     */
    public function isSeller(): bool
    {
        return $this->role === 'seller';
    }

    /**
     * Check if the user is a viewer.
     */
    public function isViewer(): bool
    {
        return $this->role === 'viewer';
    }

    /**
     * Check if the user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if the user has any of the specified roles.
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    /**
     * Check if the user can perform a specific action.
     */
    public function canPerform(string $action): bool
    {
        return match($action) {
            'manage_users' => $this->isAdmin() || $this->isManager(),
            'manage_stands' => $this->isAdmin(),
            'manage_vehicles' => $this->isAdmin() || $this->isManager() || $this->isSeller(),
            'view_reports' => $this->isAdmin() || $this->isManager(),
            'manage_sales' => $this->isAdmin() || $this->isManager() || $this->isSeller(),
            'view_analytics' => $this->isAdmin() || $this->isManager(),
            default => false,
        };
    }

    /**
     * Get the total sales value for this user.
     */
    public function getTotalSalesValueAttribute(): float
    {
        return $this->sales()->sum('sale_price');
    }

    /**
     * Get the total commission earned by this user.
     */
    public function getTotalCommissionAttribute(): float
    {
        return $this->sales()->sum('commission_amount');
    }

    /**
     * Get the total number of sales made by this user.
     */
    public function getTotalSalesCountAttribute(): int
    {
        return $this->sales()->count();
    }

    /**
     * Get the average sale value for this user.
     */
    public function getAverageSaleValueAttribute(): float
    {
        $totalSales = $this->sales()->count();
        return $totalSales > 0 ? $this->total_sales_value / $totalSales : 0;
    }

    /**
     * Get the total number of inquiries assigned to this user.
     */
    public function getAssignedInquiriesCountAttribute(): int
    {
        return $this->assignedInquiries()->count();
    }

    /**
     * Get the number of pending inquiries assigned to this user.
     */
    public function getPendingInquiriesCountAttribute(): int
    {
        return $this->assignedInquiries()->where('status', 'new')->count();
    }

    /**
     * Get the number of converted inquiries assigned to this user.
     */
    public function getConvertedInquiriesCountAttribute(): int
    {
        return $this->assignedInquiries()->where('status', 'converted')->count();
    }

    /**
     * Get the conversion rate for this user.
     */
    public function getConversionRateAttribute(): float
    {
        $totalInquiries = $this->assignedInquiries()->count();
        return $totalInquiries > 0 ? ($this->converted_inquiries_count / $totalInquiries) * 100 : 0;
    }

    /**
     * Get the role label.
     */
    public function getRoleLabelAttribute(): string
    {
        return match($this->role) {
            'admin' => 'Administrador',
            'manager' => 'Gerente',
            'seller' => 'Vendedor',
            'viewer' => 'Visualizador',
            default => 'Desconhecido',
        };
    }

    /**
     * Get the full name of the user.
     */
    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    /**
     * Get the user's initials.
     */
    public function getInitialsAttribute(): string
    {
        $names = explode(' ', $this->name);
        $initials = '';
        
        foreach ($names as $name) {
            if (!empty($name)) {
                $initials .= strtoupper(substr($name, 0, 1));
            }
        }
        
        return substr($initials, 0, 2);
    }

    /**
     * Get the avatar URL or generate initials.
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->attributes['avatar_url'] ?? null) {
            return $this->attributes['avatar_url'];
        }

        // Generate a placeholder avatar with initials
        return "https://ui-avatars.com/api/?name=" . urlencode($this->name) . "&color=7C3AED&background=EBF4FF";
    }

    /**
     * Check if the user has an avatar.
     */
    public function hasAvatar(): bool
    {
        return !empty($this->avatar_url);
    }

    /**
     * Check if the user belongs to a stand.
     */
    public function belongsToStand(): bool
    {
        return !is_null($this->stand_id);
    }

    /**
     * Get the stand name for this user.
     */
    public function getStandNameAttribute(): ?string
    {
        return $this->stand?->name;
    }

    /**
     * Check if the user is currently online (active within last 15 minutes).
     */
    public function isOnline(): bool
    {
        if (!$this->last_login_at) {
            return false;
        }

        return $this->last_login_at->diffInMinutes(now()) < 15;
    }

    /**
     * Update the last login timestamp.
     */
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * Get the commission rate as a percentage.
     */
    public function getCommissionRatePercentageAttribute(): float
    {
        return $this->commission_rate;
    }

    /**
     * Calculate commission for a sale amount.
     */
    public function calculateCommission(float $saleAmount): float
    {
        return $saleAmount * ($this->commission_rate / 100);
    }

    /**
     * Get the user's performance rating based on sales and conversions.
     */
    public function getPerformanceRatingAttribute(): float
    {
        $salesScore = min($this->total_sales_count * 10, 50); // Max 50 points for sales
        $conversionScore = min($this->conversion_rate * 0.5, 30); // Max 30 points for conversion rate
        $averageSaleScore = min($this->average_sale_value / 1000, 20); // Max 20 points for average sale

        return $salesScore + $conversionScore + $averageSaleScore;
    }

    /**
     * Get the performance level based on rating.
     */
    public function getPerformanceLevelAttribute(): string
    {
        $rating = $this->performance_rating;

        return match(true) {
            $rating >= 80 => 'Excelente',
            $rating >= 60 => 'Bom',
            $rating >= 40 => 'Regular',
            $rating >= 20 => 'Baixo',
            default => 'Muito Baixo',
        };
    }
}
