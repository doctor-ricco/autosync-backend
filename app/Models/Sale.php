<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'vehicle_id',
        'seller_id',
        'stand_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'sale_price',
        'commission_percentage',
        'commission_amount',
        'sale_date',
        'payment_method',
        'financing_details',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sale_price' => 'decimal:2',
        'commission_percentage' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'sale_date' => 'date',
        'financing_details' => 'array',
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
     * Get the vehicle that was sold.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the seller who made the sale.
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * Get the stand where the sale was made.
     */
    public function stand(): BelongsTo
    {
        return $this->belongsTo(Stand::class);
    }

    /**
     * Scope a query to only include sales by a specific seller.
     */
    public function scopeBySeller($query, $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }

    /**
     * Scope a query to only include sales from a specific stand.
     */
    public function scopeByStand($query, $standId)
    {
        return $query->where('stand_id', $standId);
    }

    /**
     * Scope a query to only include sales with a specific payment method.
     */
    public function scopeByPaymentMethod($query, $paymentMethod)
    {
        return $query->where('payment_method', $paymentMethod);
    }

    /**
     * Scope a query to only include sales from today.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('sale_date', today());
    }

    /**
     * Scope a query to only include sales from this week.
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('sale_date', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    /**
     * Scope a query to only include sales from this month.
     */
    public function scopeThisMonth($query)
    {
        return $query->whereBetween('sale_date', [now()->startOfMonth(), now()->endOfMonth()]);
    }

    /**
     * Scope a query to only include sales from this year.
     */
    public function scopeThisYear($query)
    {
        return $query->whereYear('sale_date', now()->year);
    }

    /**
     * Scope a query to only include sales within a date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('sale_date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include sales above a certain value.
     */
    public function scopeAboveValue($query, $value)
    {
        return $query->where('sale_price', '>', $value);
    }

    /**
     * Scope a query to only include sales below a certain value.
     */
    public function scopeBelowValue($query, $value)
    {
        return $query->where('sale_price', '<', $value);
    }

    /**
     * Check if the sale was made with cash payment.
     */
    public function isCashPayment(): bool
    {
        return $this->payment_method === 'cash';
    }

    /**
     * Check if the sale was made with financing.
     */
    public function isFinancingPayment(): bool
    {
        return $this->payment_method === 'financing';
    }

    /**
     * Check if the sale was made with lease.
     */
    public function isLeasePayment(): bool
    {
        return $this->payment_method === 'lease';
    }

    /**
     * Check if the sale was made with trade-in.
     */
    public function isTradeInPayment(): bool
    {
        return $this->payment_method === 'trade_in';
    }

    /**
     * Get the payment method label.
     */
    public function getPaymentMethodLabelAttribute(): string
    {
        return match($this->payment_method) {
            'cash' => 'Dinheiro',
            'financing' => 'Financiamento',
            'lease' => 'Leasing',
            'trade_in' => 'Troca',
            default => 'Desconhecido',
        };
    }

    /**
     * Get the sale price in a formatted string.
     */
    public function getFormattedSalePriceAttribute(): string
    {
        return '€' . number_format($this->sale_price, 2, ',', '.');
    }

    /**
     * Get the commission amount in a formatted string.
     */
    public function getFormattedCommissionAmountAttribute(): string
    {
        return '€' . number_format($this->commission_amount, 2, ',', '.');
    }

    /**
     * Get the commission percentage in a formatted string.
     */
    public function getFormattedCommissionPercentageAttribute(): string
    {
        return number_format($this->commission_percentage, 2, ',', '.') . '%';
    }

    /**
     * Get the sale date in a formatted string.
     */
    public function getFormattedSaleDateAttribute(): string
    {
        return $this->sale_date->format('d/m/Y');
    }

    /**
     * Get the vehicle name that was sold.
     */
    public function getVehicleNameAttribute(): string
    {
        return $this->vehicle?->full_name ?? 'Veículo não encontrado';
    }

    /**
     * Get the seller name.
     */
    public function getSellerNameAttribute(): string
    {
        return $this->seller?->name ?? 'Vendedor não encontrado';
    }

    /**
     * Get the stand name where the sale was made.
     */
    public function getStandNameAttribute(): string
    {
        return $this->stand?->name ?? 'Stand não encontrado';
    }

    /**
     * Get the customer's full contact information.
     */
    public function getCustomerContactAttribute(): string
    {
        $contact = $this->customer_email;
        
        if ($this->customer_phone) {
            $contact .= ' | ' . $this->customer_phone;
        }
        
        return $contact;
    }

    /**
     * Calculate the profit margin (assuming we have the original cost).
     */
    public function getProfitMarginAttribute(): float
    {
        // This would need the original vehicle cost to calculate properly
        // For now, we'll use a simplified calculation
        $originalPrice = $this->vehicle?->original_price ?? $this->sale_price;
        return $originalPrice > 0 ? (($this->sale_price - $originalPrice) / $originalPrice) * 100 : 0;
    }

    /**
     * Get the profit margin in a formatted string.
     */
    public function getFormattedProfitMarginAttribute(): string
    {
        return number_format($this->profit_margin, 2, ',', '.') . '%';
    }

    /**
     * Get the days since the sale was made.
     */
    public function getDaysSinceSaleAttribute(): int
    {
        return $this->sale_date->diffInDays(now());
    }

    /**
     * Check if the sale was made today.
     */
    public function isToday(): bool
    {
        return $this->sale_date->isToday();
    }

    /**
     * Check if the sale was made this week.
     */
    public function isThisWeek(): bool
    {
        return $this->sale_date->isThisWeek();
    }

    /**
     * Check if the sale was made this month.
     */
    public function isThisMonth(): bool
    {
        return $this->sale_date->isThisMonth();
    }

    /**
     * Check if the sale was made this year.
     */
    public function isThisYear(): bool
    {
        return $this->sale_date->isThisYear();
    }

    /**
     * Get the financing details as a formatted string.
     */
    public function getFormattedFinancingDetailsAttribute(): string
    {
        if (!$this->financing_details || !$this->isFinancingPayment()) {
            return 'N/A';
        }

        $details = $this->financing_details;
        $formatted = [];

        if (isset($details['bank'])) {
            $formatted[] = "Banco: {$details['bank']}";
        }

        if (isset($details['term'])) {
            $formatted[] = "Prazo: {$details['term']} meses";
        }

        if (isset($details['interest_rate'])) {
            $formatted[] = "Taxa: {$details['interest_rate']}%";
        }

        return implode(', ', $formatted);
    }

    /**
     * Get the sale summary for display.
     */
    public function getSummaryAttribute(): string
    {
        return "Venda de {$this->vehicle_name} por {$this->formatted_sale_price} - {$this->seller_name}";
    }

    /**
     * Calculate commission based on sale price and seller's commission rate.
     */
    public function calculateCommission(): float
    {
        return $this->sale_price * ($this->commission_percentage / 100);
    }

    /**
     * Update commission amount based on current commission percentage.
     */
    public function updateCommissionAmount(): bool
    {
        $this->commission_amount = $this->calculateCommission();
        return $this->save();
    }

    /**
     * Get the total revenue for a specific period.
     */
    public static function getTotalRevenue($startDate = null, $endDate = null): float
    {
        $query = static::query();
        
        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }
        
        return $query->sum('sale_price');
    }

    /**
     * Get the total commission for a specific period.
     */
    public static function getTotalCommission($startDate = null, $endDate = null): float
    {
        $query = static::query();
        
        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }
        
        return $query->sum('commission_amount');
    }

    /**
     * Get the average sale value for a specific period.
     */
    public static function getAverageSaleValue($startDate = null, $endDate = null): float
    {
        $query = static::query();
        
        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }
        
        $count = $query->count();
        return $count > 0 ? $query->sum('sale_price') / $count : 0;
    }

    /**
     * Get the top sellers for a specific period.
     */
    public static function getTopSellers($startDate = null, $endDate = null, $limit = 10)
    {
        $query = static::query();
        
        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }
        
        return $query->with('seller')
            ->select('seller_id')
            ->selectRaw('COUNT(*) as total_sales')
            ->selectRaw('SUM(sale_price) as total_revenue')
            ->selectRaw('SUM(commission_amount) as total_commission')
            ->groupBy('seller_id')
            ->orderBy('total_revenue', 'desc')
            ->limit($limit)
            ->get();
    }
}
