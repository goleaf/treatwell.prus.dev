<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingNotification extends Model
{
    /** @use HasFactory<\Database\Factories\BookingNotificationFactory> */
    use HasFactory;

    protected $fillable = [
        'booking_id', 'user_id', 'type', 'channel', 'recipient_email', 'recipient_phone',
        'subject', 'message', 'template_data', 'status', 'sent_at', 'delivered_at',
        'error_message', 'retry_count',
    ];

    protected function casts(): array
    {
        return [
            'template_data' => 'array',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    // Relationships
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // Business Logic Methods
    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1,
        ]);
    }

    public function canRetry(): bool
    {
        return $this->status === 'failed' && $this->retry_count < 3;
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'confirmation' => 'Booking Confirmation',
            'reminder' => 'Booking Reminder',
            'cancellation' => 'Booking Cancellation',
            'modification' => 'Booking Modification',
            'venue_update' => 'Venue Update',
            default => ucfirst($this->type)
        };
    }
}
