<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inquiry extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'vehicle_id',
        'stand_id',
        'name',
        'email',
        'phone',
        'message',
        'inquiry_type',
        'status',
        'assigned_to',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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
     * Get the vehicle that the inquiry is about.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the stand that received the inquiry.
     */
    public function stand(): BelongsTo
    {
        return $this->belongsTo(Stand::class);
    }

    /**
     * Get the user assigned to handle this inquiry.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Scope a query to only include inquiries with a specific status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include inquiries of a specific type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('inquiry_type', $type);
    }

    /**
     * Scope a query to only include inquiries for a specific stand.
     */
    public function scopeByStand($query, $standId)
    {
        return $query->where('stand_id', $standId);
    }

    /**
     * Scope a query to only include inquiries assigned to a specific user.
     */
    public function scopeByAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Scope a query to only include unassigned inquiries.
     */
    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    /**
     * Scope a query to only include inquiries from today.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope a query to only include inquiries from this week.
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    /**
     * Scope a query to only include inquiries from this month.
     */
    public function scopeThisMonth($query)
    {
        return $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
    }

    /**
     * Check if the inquiry is new.
     */
    public function isNew(): bool
    {
        return $this->status === 'new';
    }

    /**
     * Check if the inquiry has been contacted.
     */
    public function isContacted(): bool
    {
        return $this->status === 'contacted';
    }

    /**
     * Check if the inquiry has been qualified.
     */
    public function isQualified(): bool
    {
        return $this->status === 'qualified';
    }

    /**
     * Check if the inquiry has been converted.
     */
    public function isConverted(): bool
    {
        return $this->status === 'converted';
    }

    /**
     * Check if the inquiry has been lost.
     */
    public function isLost(): bool
    {
        return $this->status === 'lost';
    }

    /**
     * Check if the inquiry is about a specific vehicle.
     */
    public function isAboutVehicle(): bool
    {
        return !is_null($this->vehicle_id);
    }

    /**
     * Check if the inquiry is general.
     */
    public function isGeneral(): bool
    {
        return $this->inquiry_type === 'general';
    }

    /**
     * Check if the inquiry is about vehicle information.
     */
    public function isVehicleInfo(): bool
    {
        return $this->inquiry_type === 'vehicle_info';
    }

    /**
     * Check if the inquiry is about a test drive.
     */
    public function isTestDrive(): bool
    {
        return $this->inquiry_type === 'test_drive';
    }

    /**
     * Check if the inquiry is about price negotiation.
     */
    public function isPriceNegotiation(): bool
    {
        return $this->inquiry_type === 'price_negotiation';
    }

    /**
     * Check if the inquiry is assigned to someone.
     */
    public function isAssigned(): bool
    {
        return !is_null($this->assigned_to);
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'new' => 'Novo',
            'contacted' => 'Contactado',
            'qualified' => 'Qualificado',
            'converted' => 'Convertido',
            'lost' => 'Perdido',
            default => 'Desconhecido',
        };
    }

    /**
     * Get the inquiry type label.
     */
    public function getInquiryTypeLabelAttribute(): string
    {
        return match($this->inquiry_type) {
            'general' => 'Geral',
            'vehicle_info' => 'Informação do Veículo',
            'test_drive' => 'Teste de Condução',
            'price_negotiation' => 'Negociação de Preço',
            default => 'Desconhecido',
        };
    }

    /**
     * Get the customer's full name.
     */
    public function getCustomerFullNameAttribute(): string
    {
        return $this->name;
    }

    /**
     * Get the customer's contact information.
     */
    public function getCustomerContactAttribute(): string
    {
        $contact = $this->email;
        
        if ($this->phone) {
            $contact .= ' | ' . $this->phone;
        }
        
        return $contact;
    }

    /**
     * Get the inquiry date in a formatted string.
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->created_at->format('d/m/Y H:i');
    }

    /**
     * Get the time since the inquiry was created.
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get the vehicle name if the inquiry is about a specific vehicle.
     */
    public function getVehicleNameAttribute(): ?string
    {
        return $this->vehicle?->full_name;
    }

    /**
     * Get the stand name.
     */
    public function getStandNameAttribute(): string
    {
        return $this->stand?->name ?? 'N/A';
    }

    /**
     * Get the assigned user name.
     */
    public function getAssignedUserNameAttribute(): ?string
    {
        return $this->assignedTo?->name;
    }

    /**
     * Update the status of the inquiry.
     */
    public function updateStatus(string $status): bool
    {
        return $this->update(['status' => $status]);
    }

    /**
     * Assign the inquiry to a user.
     */
    public function assignTo(int $userId): bool
    {
        return $this->update(['assigned_to' => $userId]);
    }

    /**
     * Unassign the inquiry.
     */
    public function unassign(): bool
    {
        return $this->update(['assigned_to' => null]);
    }

    /**
     * Add notes to the inquiry.
     */
    public function addNotes(string $notes): bool
    {
        $currentNotes = $this->notes ?: '';
        $newNotes = $currentNotes ? $currentNotes . "\n\n" . $notes : $notes;
        
        return $this->update(['notes' => $newNotes]);
    }

    /**
     * Get the priority level of the inquiry.
     */
    public function getPriorityAttribute(): string
    {
        $hoursSinceCreation = $this->created_at->diffInHours(now());
        
        return match(true) {
            $hoursSinceCreation <= 1 => 'Alta',
            $hoursSinceCreation <= 4 => 'Média',
            $hoursSinceCreation <= 24 => 'Baixa',
            default => 'Muito Baixa',
        };
    }

    /**
     * Check if the inquiry is urgent (high priority).
     */
    public function isUrgent(): bool
    {
        return $this->priority === 'Alta';
    }

    /**
     * Get the inquiry summary for display.
     */
    public function getSummaryAttribute(): string
    {
        $summary = "Inquérito de {$this->customer_full_name}";
        
        if ($this->isAboutVehicle()) {
            $summary .= " sobre {$this->vehicle_name}";
        }
        
        $summary .= " ({$this->inquiry_type_label})";
        
        return $summary;
    }

    /**
     * Get the number of days since the inquiry was created.
     */
    public function getDaysSinceCreationAttribute(): int
    {
        return $this->created_at->diffInDays(now());
    }

    /**
     * Check if the inquiry is overdue (more than 7 days old and not converted).
     */
    public function isOverdue(): bool
    {
        return $this->days_since_creation > 7 && !$this->isConverted();
    }
}
