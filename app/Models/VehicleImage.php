<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleImage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'vehicle_id',
        'cloudinary_id',
        'url',
        'alt_text',
        'is_primary',
        'order_index',
        'file_size',
        'width',
        'height',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_primary' => 'boolean',
        'order_index' => 'integer',
        'file_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * Get the vehicle that owns the image.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Scope a query to only include primary images.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope a query to only include secondary images.
     */
    public function scopeSecondary($query)
    {
        return $query->where('is_primary', false);
    }

    /**
     * Scope a query to order images by their order index.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order_index');
    }

    /**
     * Get the file size in a formatted string.
     */
    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->file_size) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * Get the dimensions as a string.
     */
    public function getDimensionsAttribute(): string
    {
        if (!$this->width || !$this->height) {
            return 'N/A';
        }

        return "{$this->width} x {$this->height}";
    }

    /**
     * Check if the image is a primary image.
     */
    public function isPrimary(): bool
    {
        return $this->is_primary;
    }

    /**
     * Check if the image is a secondary image.
     */
    public function isSecondary(): bool
    {
        return !$this->is_primary;
    }

    /**
     * Get the image URL with fallback.
     */
    public function getImageUrlAttribute(): string
    {
        return $this->url ?: '/images/placeholder.jpg';
    }

    /**
     * Get the thumbnail URL (assuming a thumbnail service or naming convention).
     */
    public function getThumbnailUrlAttribute(): string
    {
        // Assuming thumbnails are stored with a specific naming convention
        // You can modify this based on your image storage strategy
        if (str_contains($this->url, 'http')) {
            // For external URLs, you might want to use a thumbnail service
            return $this->url;
        }

        $pathInfo = pathinfo($this->url);
        $thumbnailPath = $pathInfo['dirname'] . '/thumbnails/' . $pathInfo['basename'];
        
        return file_exists(public_path($thumbnailPath)) ? $thumbnailPath : $this->url;
    }

    /**
     * Get the medium size URL.
     */
    public function getMediumUrlAttribute(): string
    {
        // Similar to thumbnail but for medium size
        if (str_contains($this->url, 'http')) {
            return $this->url;
        }

        $pathInfo = pathinfo($this->url);
        $mediumPath = $pathInfo['dirname'] . '/medium/' . $pathInfo['basename'];
        
        return file_exists(public_path($mediumPath)) ? $mediumPath : $this->url;
    }

    /**
     * Get the large size URL.
     */
    public function getLargeUrlAttribute(): string
    {
        // Similar to thumbnail but for large size
        if (str_contains($this->url, 'http')) {
            return $this->url;
        }

        $pathInfo = pathinfo($this->url);
        $largePath = $pathInfo['dirname'] . '/large/' . $pathInfo['basename'];
        
        return file_exists(public_path($largePath)) ? $largePath : $this->url;
    }

    /**
     * Check if the image has dimensions.
     */
    public function hasDimensions(): bool
    {
        return !is_null($this->width) && !is_null($this->height);
    }

    /**
     * Check if the image has file size information.
     */
    public function hasFileSize(): bool
    {
        return !is_null($this->file_size);
    }

    /**
     * Get the aspect ratio of the image.
     */
    public function getAspectRatioAttribute(): ?float
    {
        if (!$this->hasDimensions()) {
            return null;
        }

        return $this->width / $this->height;
    }

    /**
     * Check if the image is landscape.
     */
    public function isLandscape(): bool
    {
        return $this->aspect_ratio > 1;
    }

    /**
     * Check if the image is portrait.
     */
    public function isPortrait(): bool
    {
        return $this->aspect_ratio < 1;
    }

    /**
     * Check if the image is square.
     */
    public function isSquare(): bool
    {
        return $this->aspect_ratio == 1;
    }

    /**
     * Get the alt text or generate a default one.
     */
    public function getAltTextAttribute(): string
    {
        // Se temos alt_text definido, usar
        if (!empty($this->attributes['alt_text'])) {
            return $this->attributes['alt_text'];
        }

        // Se temos vehicle carregado, gerar alt text
        if ($this->relationLoaded('vehicle') && $this->vehicle) {
            return "{$this->vehicle->brand} {$this->vehicle->model} {$this->vehicle->year} - Imagem " . ($this->order_index + 1);
        }

        return 'Imagem do veículo';
    }

    /**
     * Set this image as the primary image for the vehicle.
     */
    public function setAsPrimary(): void
    {
        // Remove primary status from other images of the same vehicle
        $this->vehicle->images()
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        // Set this image as primary
        $this->update(['is_primary' => true]);
    }

    /**
     * Move the image to a new position in the order.
     */
    public function moveToPosition(int $newPosition): void
    {
        $vehicle = $this->vehicle;
        $images = $vehicle->images()->where('id', '!=', $this->id)->ordered()->get();
        
        $images->splice($newPosition, 0, [$this]);
        
        foreach ($images as $index => $image) {
            $image->update(['order_index' => $index]);
        }
    }

    /**
     * Get the next image in the sequence.
     */
    public function getNextImageAttribute(): ?self
    {
        return $this->vehicle->images()
            ->where('order_index', '>', $this->order_index)
            ->ordered()
            ->first();
    }

    /**
     * Get the previous image in the sequence.
     */
    public function getPreviousImageAttribute(): ?self
    {
        return $this->vehicle->images()
            ->where('order_index', '<', $this->order_index)
            ->ordered()
            ->latest('order_index')
            ->first();
    }
}
