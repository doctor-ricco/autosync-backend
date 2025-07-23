<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favorite extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'user_identifier',
        'vehicle_id',
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
     * Get the user that owns the favorite.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the vehicle that is favorited.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Scope a query to only include favorites for a specific user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include favorites for a specific vehicle.
     */
    public function scopeByVehicle($query, $vehicleId)
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    /**
     * Check if a user has favorited a specific vehicle.
     */
    public static function isFavorited(int $userId, int $vehicleId): bool
    {
        return static::where('user_id', $userId)
            ->where('vehicle_id', $vehicleId)
            ->exists();
    }

    /**
     * Add a vehicle to user's favorites.
     */
    public static function addToFavorites(int $userId, int $vehicleId): bool
    {
        if (static::isFavorited($userId, $vehicleId)) {
            return false; // Already favorited
        }

        return static::create([
            'user_id' => $userId,
            'vehicle_id' => $vehicleId,
        ]) !== null;
    }

    /**
     * Remove a vehicle from user's favorites.
     */
    public static function removeFromFavorites(int $userId, int $vehicleId): bool
    {
        return static::where('user_id', $userId)
            ->where('vehicle_id', $vehicleId)
            ->delete() > 0;
    }

    /**
     * Toggle favorite status for a vehicle.
     */
    public static function toggleFavorite(int $userId, int $vehicleId): bool
    {
        if (static::isFavorited($userId, $vehicleId)) {
            return static::removeFromFavorites($userId, $vehicleId);
        } else {
            return static::addToFavorites($userId, $vehicleId);
        }
    }

    /**
     * Get the number of favorites for a vehicle.
     */
    public static function getVehicleFavoritesCount(int $vehicleId): int
    {
        return static::where('vehicle_id', $vehicleId)->count();
    }

    /**
     * Get the number of favorites for a user.
     */
    public static function getUserFavoritesCount(int $userId): int
    {
        return static::where('user_id', $userId)->count();
    }

    /**
     * Get all favorite vehicles for a user.
     */
    public static function getUserFavorites(int $userId)
    {
        return static::where('user_id', $userId)
            ->with('vehicle.images')
            ->orderBy('created_at', 'desc')
            ->get()
            ->pluck('vehicle');
    }

    /**
     * Get all users who favorited a specific vehicle.
     */
    public static function getVehicleFavoritedBy(int $vehicleId)
    {
        return static::where('vehicle_id', $vehicleId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->pluck('user');
    }

    /**
     * Get the favorite date in a formatted string.
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->created_at->format('d/m/Y H:i');
    }

    /**
     * Get the time since the favorite was added.
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }
}
