<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Stand extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'address',
        'city',
        'postal_code',
        'phone',
        'email',
        'website',
        'logo_url',
        'latitude',
        'longitude',
        'business_hours',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'business_hours' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Get the vehicles for this stand.
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Get the users (employees) for this stand.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the inquiries for this stand.
     */
    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    /**
     * Get the sales for this stand.
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Scope a query to only include active stands.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include stands in a specific city.
     */
    public function scopeInCity($query, $city)
    {
        return $query->where('city', $city);
    }

    /**
     * Get the total number of vehicles in this stand.
     */
    public function getTotalVehiclesAttribute(): int
    {
        return $this->vehicles()->count();
    }

    /**
     * Get the number of available vehicles in this stand.
     */
    public function getAvailableVehiclesAttribute(): int
    {
        return $this->vehicles()->where('status', 'available')->count();
    }

    /**
     * Get the total sales value for this stand.
     */
    public function getTotalSalesValueAttribute(): float
    {
        return $this->sales()->sum('sale_price');
    }

    /**
     * Get the total commission value for this stand.
     */
    public function getTotalCommissionValueAttribute(): float
    {
        return $this->sales()->sum('commission_amount');
    }

    /**
     * Get the most popular vehicles in this stand.
     */
    public function getPopularVehiclesAttribute()
    {
        return $this->vehicles()
            ->orderBy('views_count', 'desc')
            ->limit(5)
            ->get();
    }

    /**
     * Get the top sellers in this stand.
     */
    public function getTopSellersAttribute()
    {
        return $this->users()
            ->where('role', 'seller')
            ->withCount(['sales as total_sales' => function ($query) {
                $query->whereNotNull('sale_price');
            }])
            ->orderBy('total_sales', 'desc')
            ->limit(5)
            ->get();
    }

    /**
     * Generate a unique slug for the stand.
     */
    public static function generateSlug(string $name): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Get the full address of the stand.
     */
    public function getFullAddressAttribute(): string
    {
        return "{$this->address}, {$this->postal_code} {$this->city}";
    }

    /**
     * Get the coordinates as an array.
     */
    public function getCoordinatesAttribute(): array
    {
        return [
            'lat' => $this->latitude,
            'lng' => $this->longitude,
        ];
    }

    /**
     * Check if the stand has coordinates.
     */
    public function hasCoordinates(): bool
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }

    /**
     * Get the business hours for a specific day.
     */
    public function getBusinessHoursForDay(string $day): ?array
    {
        return $this->business_hours[$day] ?? null;
    }

    /**
     * Check if the stand is open on a specific day and time.
     */
    public function isOpen(string $day = null, string $time = null): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (!$day) {
            $day = strtolower(now()->format('l'));
        }

        if (!$time) {
            $time = now()->format('H:i');
        }

        $hours = $this->getBusinessHoursForDay($day);
        
        if (!$hours || !isset($hours['open']) || !isset($hours['close'])) {
            return false;
        }

        return $time >= $hours['open'] && $time <= $hours['close'];
    }
}
