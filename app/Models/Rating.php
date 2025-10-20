<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rating extends Model
{
    use HasFactory;

    protected $fillable = [
        'venue_id',
        'weighted_average',
        'count',
        'cleanliness_avg',
        'cleanliness_count',
        'staff_avg',
        'staff_count',
        'atmosphere_avg',
        'atmosphere_count',
        'display_average',
    ];

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }
}
