<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'stand_id',
        'reference',
        'brand',
        'model',
        'year',
        'mileage',
        'fuel_type',
        'transmission',
        'engine_size',
        'power_hp',
        'doors',
        'seats',
        'color',
        'price',
        'original_price',
        'discount_percentage',
        'description',
        'features',
        'status',
        'is_featured',
        'is_new',
        'views_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'features' => 'array',
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'engine_size' => 'decimal:1',
        'is_featured' => 'boolean',
        'is_new' => 'boolean',
        'views_count' => 'integer',
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
     * Get the stand that owns the vehicle.
     */
    public function stand(): BelongsTo
    {
        return $this->belongsTo(Stand::class);
    }

    /**
     * Get the images for the vehicle.
     */
    public function images(): HasMany
    {
        return $this->hasMany(VehicleImage::class);
    }

    /**
     * Get the primary image for the vehicle.
     */
    public function primaryImage(): BelongsTo
    {
        return $this->belongsTo(VehicleImage::class, 'id', 'vehicle_id')
            ->where('is_primary', true);
    }

    /**
     * Get the favorites for the vehicle.
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * Get the inquiries for the vehicle.
     */
    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    /**
     * Get the sale for the vehicle (if sold).
     */
    public function sale(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Get the views for the vehicle.
     */
    public function views(): HasMany
    {
        return $this->hasMany(VehicleView::class);
    }

    /**
     * Scope a query to only include available vehicles.
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    /**
     * Scope a query to only include featured vehicles.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to only include new vehicles.
     */
    public function scopeNew($query)
    {
        return $query->where('is_new', true);
    }

    /**
     * Scope a query to only include vehicles by brand.
     */
    public function scopeByBrand($query, $brand)
    {
        return $query->where('brand', $brand);
    }

    /**
     * Scope a query to only include vehicles by fuel type.
     */
    public function scopeByFuelType($query, $fuelType)
    {
        return $query->where('fuel_type', $fuelType);
    }

    /**
     * Scope a query to only include vehicles by transmission.
     */
    public function scopeByTransmission($query, $transmission)
    {
        return $query->where('transmission', $transmission);
    }

    /**
     * Scope a query to only include vehicles within a price range.
     */
    public function scopePriceRange($query, $minPrice, $maxPrice)
    {
        return $query->whereBetween('price', [$minPrice, $maxPrice]);
    }

    /**
     * Scope a query to only include vehicles by year range.
     */
    public function scopeYearRange($query, $minYear, $maxYear)
    {
        return $query->whereBetween('year', [$minYear, $maxYear]);
    }

    /**
     * Scope a query to only include vehicles by mileage range.
     */
    public function scopeMileageRange($query, $minMileage, $maxMileage)
    {
        return $query->whereBetween('mileage', [$minMileage, $maxMileage]);
    }

    /**
     * Get the full name of the vehicle.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->brand} {$this->model} {$this->year}";
    }

    /**
     * Get the short name of the vehicle.
     */
    public function getShortNameAttribute(): string
    {
        return "{$this->brand} {$this->model}";
    }

    /**
     * Get the current price with discount applied.
     */
    public function getCurrentPriceAttribute(): float
    {
        if ($this->discount_percentage > 0) {
            return $this->price * (1 - $this->discount_percentage / 100);
        }
        
        return $this->price;
    }

    /**
     * Get the discount amount.
     */
    public function getDiscountAmountAttribute(): float
    {
        return $this->price - $this->current_price;
    }

    /**
     * Check if the vehicle has a discount.
     */
    public function hasDiscount(): bool
    {
        return $this->discount_percentage > 0;
    }

    /**
     * Check if the vehicle is sold.
     */
    public function isSold(): bool
    {
        return $this->status === 'sold';
    }

    /**
     * Check if the vehicle is available.
     */
    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    /**
     * Check if the vehicle is reserved.
     */
    public function isReserved(): bool
    {
        return $this->status === 'reserved';
    }

    /**
     * Check if the vehicle is in maintenance.
     */
    public function isInMaintenance(): bool
    {
        return $this->status === 'maintenance';
    }

    /**
     * Get the primary image URL.
     */
    public function getPrimaryImageUrlAttribute(): ?string
    {
        $primaryImage = $this->images()->where('is_primary', true)->first();
        return $primaryImage ? $primaryImage->url : null;
    }

    /**
     * Get all image URLs for the vehicle.
     */
    public function getAllImageUrlsAttribute(): array
    {
        return $this->images()->orderBy('order_index')->pluck('url')->toArray();
    }

    /**
     * Get the number of images for the vehicle.
     */
    public function getImagesCountAttribute(): int
    {
        return $this->images()->count();
    }

    /**
     * Get the number of favorites for the vehicle.
     */
    public function getFavoritesCountAttribute(): int
    {
        return $this->favorites()->count();
    }

    /**
     * Get the number of inquiries for the vehicle.
     */
    public function getInquiriesCountAttribute(): int
    {
        return $this->inquiries()->count();
    }

    /**
     * Increment the views count for the vehicle.
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Get the age of the vehicle in years.
     */
    public function getAgeAttribute(): int
    {
        return now()->year - $this->year;
    }

    /**
     * Get the mileage in a formatted string.
     */
    public function getFormattedMileageAttribute(): string
    {
        return number_format($this->mileage, 0, ',', '.') . ' km';
    }

    /**
     * Get the price in a formatted string.
     */
    public function getFormattedPriceAttribute(): string
    {
        return '€' . number_format($this->price, 2, ',', '.');
    }

    /**
     * Get the current price in a formatted string.
     */
    public function getFormattedCurrentPriceAttribute(): string
    {
        return '€' . number_format($this->current_price, 2, ',', '.');
    }

    /**
     * Get the discount amount in a formatted string.
     */
    public function getFormattedDiscountAmountAttribute(): string
    {
        return '€' . number_format($this->discount_amount, 2, ',', '.');
    }

    /**
     * Get the engine size in a formatted string.
     */
    public function getFormattedEngineSizeAttribute(): string
    {
        return $this->engine_size ? $this->engine_size . 'L' : 'N/A';
    }

    /**
     * Get the power in a formatted string.
     */
    public function getFormattedPowerAttribute(): string
    {
        return $this->power_hp ? $this->power_hp . ' cv' : 'N/A';
    }

    /**
     * Check if the vehicle has features.
     */
    public function hasFeatures(): bool
    {
        return !empty($this->features);
    }

    /**
     * Get the features as a list.
     */
    public function getFeaturesListAttribute(): array
    {
        return $this->features ?? [];
    }

    /**
     * Check if the vehicle has a specific feature.
     */
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    /**
     * Generate a unique reference for the vehicle.
     */
    public static function generateReference(): string
    {
        do {
            $reference = 'VH' . strtoupper(Str::random(6));
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'available' => 'Disponível',
            'sold' => 'Vendido',
            'reserved' => 'Reservado',
            'maintenance' => 'Em Manutenção',
            default => 'Desconhecido',
        };
    }

    /**
     * Get the fuel type label.
     */
    public function getFuelTypeLabelAttribute(): string
    {
        return match($this->fuel_type) {
            'gasoline' => 'Gasolina',
            'diesel' => 'Diesel',
            'hybrid' => 'Híbrido',
            'electric' => 'Elétrico',
            'lpg' => 'GPL',
            default => 'Desconhecido',
        };
    }

    /**
     * Get the transmission label.
     */
    public function getTransmissionLabelAttribute(): string
    {
        return match($this->transmission) {
            'manual' => 'Manual',
            'automatic' => 'Automático',
            'semi_automatic' => 'Semi-Automático',
            default => 'Desconhecido',
        };
    }
}
