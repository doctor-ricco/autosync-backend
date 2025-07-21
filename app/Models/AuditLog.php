<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'action',
        'table_name',
        'record_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'updated_at',
    ];

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include logs for a specific user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include logs for a specific action.
     */
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope a query to only include logs for a specific table.
     */
    public function scopeByTable($query, $tableName)
    {
        return $query->where('table_name', $tableName);
    }

    /**
     * Scope a query to only include logs for a specific record.
     */
    public function scopeByRecord($query, $tableName, $recordId)
    {
        return $query->where('table_name', $tableName)
            ->where('record_id', $recordId);
    }

    /**
     * Scope a query to only include logs from today.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope a query to only include logs from this week.
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    /**
     * Scope a query to only include logs from this month.
     */
    public function scopeThisMonth($query)
    {
        return $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
    }

    /**
     * Scope a query to only include logs from a specific IP address.
     */
    public function scopeByIpAddress($query, $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Check if the log is for a create action.
     */
    public function isCreate(): bool
    {
        return $this->action === 'create';
    }

    /**
     * Check if the log is for an update action.
     */
    public function isUpdate(): bool
    {
        return $this->action === 'update';
    }

    /**
     * Check if the log is for a delete action.
     */
    public function isDelete(): bool
    {
        return $this->action === 'delete';
    }

    /**
     * Check if the log is for a login action.
     */
    public function isLogin(): bool
    {
        return $this->action === 'login';
    }

    /**
     * Check if the log is for a logout action.
     */
    public function isLogout(): bool
    {
        return $this->action === 'logout';
    }

    /**
     * Check if the log is for a view action.
     */
    public function isView(): bool
    {
        return $this->action === 'view';
    }

    /**
     * Get the action label.
     */
    public function getActionLabelAttribute(): string
    {
        return match($this->action) {
            'create' => 'Criar',
            'update' => 'Atualizar',
            'delete' => 'Eliminar',
            'login' => 'Login',
            'logout' => 'Logout',
            'view' => 'Visualizar',
            'export' => 'Exportar',
            'import' => 'Importar',
            'download' => 'Download',
            'upload' => 'Upload',
            default => 'Desconhecido',
        };
    }

    /**
     * Get the table name label.
     */
    public function getTableNameLabelAttribute(): string
    {
        return match($this->table_name) {
            'users' => 'Utilizadores',
            'vehicles' => 'Veículos',
            'stands' => 'Stands',
            'sales' => 'Vendas',
            'inquiries' => 'Inquéritos',
            'favorites' => 'Favoritos',
            'vehicle_images' => 'Imagens de Veículos',
            'vehicle_views' => 'Visualizações de Veículos',
            'audit_logs' => 'Logs de Auditoria',
            default => ucfirst(str_replace('_', ' ', $this->table_name)),
        };
    }

    /**
     * Get the user name who performed the action.
     */
    public function getUserNameAttribute(): string
    {
        return $this->user?->name ?? 'Utilizador Anónimo';
    }

    /**
     * Get the log date in a formatted string.
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->created_at->format('d/m/Y H:i:s');
    }

    /**
     * Get the time since the log was created.
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get the browser information from user agent.
     */
    public function getBrowserAttribute(): string
    {
        if (!$this->user_agent) {
            return 'Desconhecido';
        }

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
     * Get the changes summary.
     */
    public function getChangesSummaryAttribute(): string
    {
        if ($this->isCreate()) {
            return "Criado novo registo";
        }

        if ($this->isDelete()) {
            return "Registo eliminado";
        }

        if ($this->isUpdate() && $this->old_values && $this->new_values) {
            $changes = [];
            foreach ($this->new_values as $field => $newValue) {
                $oldValue = $this->old_values[$field] ?? null;
                if ($oldValue !== $newValue) {
                    $changes[] = "{$field}: {$oldValue} → {$newValue}";
                }
            }
            return implode(', ', $changes);
        }

        return "Ação realizada";
    }

    /**
     * Get the number of fields that changed.
     */
    public function getChangedFieldsCountAttribute(): int
    {
        if (!$this->old_values || !$this->new_values) {
            return 0;
        }

        $count = 0;
        foreach ($this->new_values as $field => $newValue) {
            $oldValue = $this->old_values[$field] ?? null;
            if ($oldValue !== $newValue) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get the changed fields as an array.
     */
    public function getChangedFieldsAttribute(): array
    {
        if (!$this->old_values || !$this->new_values) {
            return [];
        }

        $changes = [];
        foreach ($this->new_values as $field => $newValue) {
            $oldValue = $this->old_values[$field] ?? null;
            if ($oldValue !== $newValue) {
                $changes[$field] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changes;
    }

    /**
     * Record a new audit log entry.
     */
    public static function recordAction(
        ?int $userId,
        string $action,
        string $tableName,
        ?int $recordId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): bool {
        return static::create([
            'user_id' => $userId,
            'action' => $action,
            'table_name' => $tableName,
            'record_id' => $recordId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]) !== null;
    }

    /**
     * Get the total actions performed by a user.
     */
    public static function getUserActionsCount(int $userId): int
    {
        return static::where('user_id', $userId)->count();
    }

    /**
     * Get the most active users.
     */
    public static function getMostActiveUsers($limit = 10, $startDate = null, $endDate = null)
    {
        $query = static::query();
        
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }
        
        return $query->with('user')
            ->select('user_id')
            ->selectRaw('COUNT(*) as actions_count')
            ->groupBy('user_id')
            ->orderBy('actions_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get the most common actions.
     */
    public static function getMostCommonActions($limit = 10, $startDate = null, $endDate = null)
    {
        $query = static::query();
        
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }
        
        return $query->select('action')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('action')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get the most affected tables.
     */
    public static function getMostAffectedTables($limit = 10, $startDate = null, $endDate = null)
    {
        $query = static::query();
        
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }
        
        return $query->select('table_name')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('table_name')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get the activity trend over time.
     */
    public static function getActivityTrend($days = 30)
    {
        $startDate = now()->subDays($days);
        
        return static::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw('COUNT(*) as actions_count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * Get the actions by hour of day.
     */
    public static function getActionsByHour($startDate = null, $endDate = null)
    {
        $query = static::query();
        
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }
        
        return $query->selectRaw('HOUR(created_at) as hour')
            ->selectRaw('COUNT(*) as actions_count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();
    }

    /**
     * Get the actions by day of week.
     */
    public static function getActionsByDayOfWeek($startDate = null, $endDate = null)
    {
        $query = static::query();
        
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }
        
        return $query->selectRaw('DAYOFWEEK(created_at) as day_of_week')
            ->selectRaw('COUNT(*) as actions_count')
            ->groupBy('day_of_week')
            ->orderBy('day_of_week')
            ->get();
    }
}
