<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Treatment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'venue_id',
        'procedure_id',
        'name',
        'description',
        'price',
        'duration',
        'is_available',
        'min_price',
        'max_price',
        'min_duration',
        'max_duration',
        'category_id',
        'category_name',
        'options',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'price' => 'float',
        'duration' => 'integer',
        'is_available' => 'boolean',
        'min_price' => 'float',
        'max_price' => 'float',
        'min_duration' => 'integer',
        'max_duration' => 'integer',
        'options' => 'array',
    ];

    /**
     * Get the venue that owns the treatment.
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * Get the procedure that owns the treatment.
     */
    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }

    /**
     * Format the price with currency symbol.
     */
    public function getFormattedPriceAttribute(): string
    {
        return '€'.number_format($this->price, 2);
    }

    /**
     * Format the duration in hours and minutes.
     */
    public function getFormattedDurationAttribute(): string
    {
        $hours = floor($this->duration / 60);
        $minutes = $this->duration % 60;

        $result = '';
        if ($hours > 0) {
            $result .= $hours.'h ';
        }

        if ($minutes > 0 || $hours == 0) {
            $result .= $minutes.'min';
        }

        return trim($result);
    }

    /**
     * Scope a query to only include available treatments.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }
}
