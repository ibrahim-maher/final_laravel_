<?php
// app/Modules/Commission/Models/CommissionPayout.php

namespace App\Modules\Commission\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class CommissionPayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'commission_id',
        'recipient_id',
        'recipient_type',
        'amount',
        'payout_method',
        'payout_date',
        'processed_date',
        'status',
        'transaction_id',
        'reference_number',
        'metadata',
        'notes',
        'created_by',
        'processed_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payout_date' => 'datetime',
        'processed_date' => 'datetime',
        'metadata' => 'array'
    ];

    // Constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_PROCESSED = 'processed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    const METHOD_BANK_TRANSFER = 'bank_transfer';
    const METHOD_DIGITAL_WALLET = 'digital_wallet';
    const METHOD_CASH = 'cash';
    const METHOD_CHECK = 'check';

    const RECIPIENT_DRIVER = 'driver';
    const RECIPIENT_COMPANY = 'company';
    const RECIPIENT_PARTNER = 'partner';
    const RECIPIENT_REFERRER = 'referrer';

    // Relationships
    public function commission()
    {
        return $this->belongsTo(Commission::class);
    }

    // Accessors
    public function getFormattedAmountAttribute()
    {
        return '$' . number_format($this->amount, 2);
    }

    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_PROCESSING => 'blue',
            self::STATUS_PROCESSED => 'green',
            self::STATUS_FAILED => 'red',
            self::STATUS_CANCELLED => 'gray',
            default => 'gray'
        };
    }

    public function getStatusLabelAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getPayoutMethodLabelAttribute()
    {
        return match ($this->payout_method) {
            self::METHOD_BANK_TRANSFER => 'Bank Transfer',
            self::METHOD_DIGITAL_WALLET => 'Digital Wallet',
            self::METHOD_CASH => 'Cash',
            self::METHOD_CHECK => 'Check',
            default => ucfirst(str_replace('_', ' ', $this->payout_method))
        };
    }

    public function getRecipientTypeLabelAttribute()
    {
        return ucfirst($this->recipient_type);
    }

    // Boot method to handle model events
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payout) {
            // Generate unique reference number if not provided
            if (empty($payout->reference_number)) {
                $payout->reference_number = 'PAY-' . strtoupper(Str::random(8)) . '-' . now()->format('Ymd');

                // Ensure uniqueness
                $counter = 1;
                $originalRef = $payout->reference_number;
                while (static::where('reference_number', $payout->reference_number)->exists()) {
                    $payout->reference_number = $originalRef . '-' . $counter;
                    $counter++;
                }
            }

            // Set payout_date if not provided
            if (empty($payout->payout_date)) {
                $payout->payout_date = now();
            }
        });

        static::updating(function ($payout) {
            // Set processed_date when status changes to processed
            if ($payout->isDirty('status') && $payout->status === self::STATUS_PROCESSED) {
                $payout->processed_date = now();
            }
        });
    }

    // Static methods
    public static function getStatuses()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_PROCESSED => 'Processed',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_CANCELLED => 'Cancelled'
        ];
    }

    public static function getPayoutMethods()
    {
        return [
            self::METHOD_BANK_TRANSFER => 'Bank Transfer',
            self::METHOD_DIGITAL_WALLET => 'Digital Wallet',
            self::METHOD_CASH => 'Cash',
            self::METHOD_CHECK => 'Check'
        ];
    }

    public static function getRecipientTypes()
    {
        return [
            self::RECIPIENT_DRIVER => 'Driver',
            self::RECIPIENT_COMPANY => 'Company',
            self::RECIPIENT_PARTNER => 'Partner',
            self::RECIPIENT_REFERRER => 'Referrer'
        ];
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', self::STATUS_PROCESSED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeByRecipient($query, $recipientType)
    {
        return $query->where('recipient_type', $recipientType);
    }

    public function scopeByPayoutMethod($query, $method)
    {
        return $query->where('payout_method', $method);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('payout_date', [$startDate, $endDate]);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('reference_number', 'like', "%{$search}%")
                ->orWhere('transaction_id', 'like', "%{$search}%")
                ->orWhere('recipient_id', 'like', "%{$search}%")
                ->orWhere('notes', 'like', "%{$search}%");
        });
    }

    // Helper methods
    public function markAsProcessed($transactionId = null, $processedBy = null)
    {
        $this->update([
            'status' => self::STATUS_PROCESSED,
            'processed_date' => now(),
            'transaction_id' => $transactionId,
            'processed_by' => $processedBy ?? auth()->id() ?? 'system'
        ]);
    }

    public function markAsFailed($reason = null)
    {
        $metadata = $this->metadata ?? [];
        $metadata['failure_reason'] = $reason;

        $this->update([
            'status' => self::STATUS_FAILED,
            'metadata' => $metadata
        ]);
    }

    public function markAsCancelled($reason = null, $cancelledBy = null)
    {
        $metadata = $this->metadata ?? [];
        $metadata['cancellation_reason'] = $reason;
        $metadata['cancelled_by'] = $cancelledBy ?? auth()->id() ?? 'system';
        $metadata['cancelled_at'] = now()->toISOString();

        $this->update([
            'status' => self::STATUS_CANCELLED,
            'metadata' => $metadata
        ]);
    }

    public function canBeProcessed()
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_FAILED]);
    }

    public function canBeCancelled()
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    public function isCompleted()
    {
        return in_array($this->status, [self::STATUS_PROCESSED, self::STATUS_CANCELLED]);
    }

    // Calculate processing time
    public function getProcessingTimeAttribute()
    {
        if ($this->processed_date && $this->payout_date) {
            return $this->payout_date->diffForHumans($this->processed_date);
        }
        return null;
    }

    // Get days since payout
    public function getDaysSincePayoutAttribute()
    {
        return $this->payout_date->diffInDays(now());
    }
}
