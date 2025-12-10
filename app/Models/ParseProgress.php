<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParseProgress extends Model
{
    /** @use HasFactory<\Database\Factories\ParseProgressFactory> */
    use HasFactory;

    protected $table = 'parse_progress';

    protected $fillable = [
        'city_slug',
        'status',
        'started_at',
        'completed_at',
        'venues_found',
        'treatments_found',
        'api_calls_made',
        'error_message',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'venues_found' => 'integer',
            'treatments_found' => 'integer',
            'api_calls_made' => 'integer',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the city associated with this progress record.
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_slug', 'slug');
    }

    /**
     * Scope to get records by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get pending records.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get processing records.
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope to get completed records.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get failed records.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Mark the progress as started.
     */
    public function markAsStarted(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark the progress as completed.
     */
    public function markAsCompleted(array $stats = []): void
    {
        $updateData = [
            'status' => 'completed',
            'completed_at' => now(),
        ];

        if (isset($stats['venues_found'])) {
            $updateData['venues_found'] = $stats['venues_found'];
        }

        if (isset($stats['treatments_found'])) {
            $updateData['treatments_found'] = $stats['treatments_found'];
        }

        if (isset($stats['api_calls_made'])) {
            $updateData['api_calls_made'] = $stats['api_calls_made'];
        }

        if (isset($stats['metadata'])) {
            $updateData['metadata'] = $stats['metadata'];
        }

        $this->update($updateData);
    }

    /**
     * Mark the progress as failed.
     */
    public function markAsFailed(string $errorMessage, array $stats = []): void
    {
        $updateData = [
            'status' => 'failed',
            'completed_at' => now(),
            'error_message' => $errorMessage,
        ];

        if (isset($stats['venues_found'])) {
            $updateData['venues_found'] = $stats['venues_found'];
        }

        if (isset($stats['treatments_found'])) {
            $updateData['treatments_found'] = $stats['treatments_found'];
        }

        if (isset($stats['api_calls_made'])) {
            $updateData['api_calls_made'] = $stats['api_calls_made'];
        }

        if (isset($stats['metadata'])) {
            $updateData['metadata'] = $stats['metadata'];
        }

        $this->update($updateData);
    }

    /**
     * Get the duration of processing in seconds.
     */
    public function getProcessingDuration(): ?int
    {
        if (! $this->started_at) {
            return null;
        }

        $endTime = $this->completed_at ?? now();

        return $this->started_at->diffInSeconds($endTime);
    }

    /**
     * Check if the progress is in a terminal state.
     */
    public function isTerminal(): bool
    {
        return in_array($this->status, ['completed', 'failed']);
    }

    /**
     * Check if the progress is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'processing';
    }
}
