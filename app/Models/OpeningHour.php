<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpeningHour extends Model
{
    protected $fillable = [
        'venue_id',
        'day_of_week',
        'opening_time',
        'closing_time',
        'is_open',
    ];

    protected $casts = [
        'is_open' => 'boolean',
    ];

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }
}
