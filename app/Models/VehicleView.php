<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleView extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'vehicle_id',
        'user_id',
        'ip_address',
        'user_agent',
        'viewed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'viewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the vehicle that was viewed.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the user who viewed the vehicle (if authenticated).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include views for a specific vehicle.
     */
    public function scopeByVehicle($query, $vehicleId)
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    /**
     * Scope a query to only include views by a specific user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include views from a specific IP address.
     */
    public function scopeByIpAddress($query, $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Scope a query to only include views from today.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('viewed_at', today());
    }

    /**
     * Scope a query to only include views from this week.
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('viewed_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    /**
     * Scope a query to only include views from this month.
     */
    public function scopeThisMonth($query)
    {
        return $query->whereBetween('viewed_at', [now()->startOfMonth(), now()->endOfMonth()]);
    }

    /**
     * Scope a query to only include views from this year.
     */
    public function scopeThisYear($query)
    {
        return $query->whereYear('viewed_at', now()->year);
    }

    /**
     * Scope a query to only include views within a date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('viewed_at', [$startDate, $endDate]);
    }

    /**
     * Check if the view was made by an authenticated user.
     */
    public function isAuthenticatedUser(): bool
    {
        return !is_null($this->user_id);
    }

    /**
     * Check if the view was made by an anonymous user.
     */
    public function isAnonymousUser(): bool
    {
        return is_null($this->user_id);
    }

    /**
     * Get the view date in a formatted string.
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->viewed_at->format('d/m/Y H:i:s');
    }

    /**
     * Get the time since the view was made.
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->viewed_at->diffForHumans();
    }

    /**
     * Get the vehicle name that was viewed.
     */
    public function getVehicleNameAttribute(): string
    {
        return $this->vehicle?->full_name ?? 'Veículo não encontrado';
    }

    /**
     * Get the user name who viewed the vehicle.
     */
    public function getUserNameAttribute(): ?string
    {
        return $this->user?->name;
    }

    /**
     * Get the browser information from user agent.
     */
    public function getBrowserAttribute(): string
    {
        if (!$this->user_agent) {
            return 'Desconhecido';
        }

        // Simple browser detection
        $userAgent = strtolower($this->user_agent);
        
        if (str_contains($userAgent, 'chrome')) {
            return 'Chrome';
        } elseif (str_contains($userAgent, 'firefox')) {
            return 'Firefox';
        } elseif (str_contains($userAgent, 'safari')) {
            return 'Safari';
        } elseif (str_contains($userAgent, 'edge')) {
            return 'Edge';
        } elseif (str_contains($userAgent, 'opera')) {
            return 'Opera';
        } else {
            return 'Outro';
        }
    }

    /**
     * Get the device type from user agent.
     */
    public function getDeviceTypeAttribute(): string
    {
        if (!$this->user_agent) {
            return 'Desconhecido';
        }

        $userAgent = strtolower($this->user_agent);
        
        if (str_contains($userAgent, 'mobile')) {
            return 'Mobile';
        } elseif (str_contains($userAgent, 'tablet')) {
            return 'Tablet';
        } else {
            return 'Desktop';
        }
    }

    /**
     * Get the operating system from user agent.
     */
    public function getOperatingSystemAttribute(): string
    {
        if (!$this->user_agent) {
            return 'Desconhecido';
        }

        $userAgent = strtolower($this->user_agent);
        
        if (str_contains($userAgent, 'windows')) {
            return 'Windows';
        } elseif (str_contains($userAgent, 'mac')) {
            return 'macOS';
        } elseif (str_contains($userAgent, 'linux')) {
            return 'Linux';
        } elseif (str_contains($userAgent, 'android')) {
            return 'Android';
        } elseif (str_contains($userAgent, 'ios')) {
            return 'iOS';
        } else {
            return 'Outro';
        }
    }

    /**
     * Record a new vehicle view.
     */
    public static function recordView(int $vehicleId, ?int $userId = null, ?string $ipAddress = null, ?string $userAgent = null): bool
    {
        // Update the vehicle's views count
        $vehicle = Vehicle::find($vehicleId);
        if ($vehicle) {
            $vehicle->incrementViews();
        }

        // Record the view
        return static::create([
            'vehicle_id' => $vehicleId,
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'viewed_at' => now(),
        ]) !== null;
    }

    /**
     * Get the total views for a vehicle.
     */
    public static function getVehicleViewsCount(int $vehicleId): int
    {
        return static::where('vehicle_id', $vehicleId)->count();
    }

    /**
     * Get the total views for a vehicle in a specific period.
     */
    public static function getVehicleViewsCountInPeriod(int $vehicleId, $startDate, $endDate): int
    {
        return static::where('vehicle_id', $vehicleId)
            ->dateRange($startDate, $endDate)
            ->count();
    }

    /**
     * Get the most viewed vehicles.
     */
    public static function getMostViewedVehicles($limit = 10, $startDate = null, $endDate = null)
    {
        $query = static::query();
        
        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }
        
        return $query->with('vehicle')
            ->select('vehicle_id')
            ->selectRaw('COUNT(*) as views_count')
            ->groupBy('vehicle_id')
            ->orderBy('views_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get the unique visitors for a vehicle.
     */
    public static function getUniqueVisitorsForVehicle(int $vehicleId): int
    {
        return static::where('vehicle_id', $vehicleId)
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');
    }

    /**
     * Get the unique IP addresses for a vehicle.
     */
    public static function getUniqueIPsForVehicle(int $vehicleId): int
    {
        return static::where('vehicle_id', $vehicleId)
            ->whereNotNull('ip_address')
            ->distinct('ip_address')
            ->count('ip_address');
    }

    /**
     * Get the average views per vehicle.
     */
    public static function getAverageViewsPerVehicle(): float
    {
        $totalViews = static::count();
        $uniqueVehicles = static::distinct('vehicle_id')->count('vehicle_id');
        
        return $uniqueVehicles > 0 ? $totalViews / $uniqueVehicles : 0;
    }

    /**
     * Get the views trend for a vehicle over time.
     */
    public static function getVehicleViewsTrend(int $vehicleId, $days = 30)
    {
        $startDate = now()->subDays($days);
        
        return static::where('vehicle_id', $vehicleId)
            ->where('viewed_at', '>=', $startDate)
            ->selectRaw('DATE(viewed_at) as date')
            ->selectRaw('COUNT(*) as views_count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * Get the views by device type for a vehicle.
     */
    public static function getVehicleViewsByDeviceType(int $vehicleId)
    {
        return static::where('vehicle_id', $vehicleId)
            ->selectRaw('CASE 
                WHEN user_agent LIKE "%mobile%" THEN "Mobile"
                WHEN user_agent LIKE "%tablet%" THEN "Tablet"
                ELSE "Desktop"
            END as device_type')
            ->selectRaw('COUNT(*) as views_count')
            ->groupBy('device_type')
            ->orderBy('views_count', 'desc')
            ->get();
    }

    /**
     * Get the views by browser for a vehicle.
     */
    public static function getVehicleViewsByBrowser(int $vehicleId)
    {
        return static::where('vehicle_id', $vehicleId)
            ->selectRaw('CASE 
                WHEN user_agent LIKE "%chrome%" THEN "Chrome"
                WHEN user_agent LIKE "%firefox%" THEN "Firefox"
                WHEN user_agent LIKE "%safari%" THEN "Safari"
                WHEN user_agent LIKE "%edge%" THEN "Edge"
                WHEN user_agent LIKE "%opera%" THEN "Opera"
                ELSE "Outro"
            END as browser')
            ->selectRaw('COUNT(*) as views_count')
            ->groupBy('browser')
            ->orderBy('views_count', 'desc')
            ->get();
    }
}
