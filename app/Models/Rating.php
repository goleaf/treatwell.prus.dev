<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rating extends Model
{
    use HasFactory;

    protected $fillable = [
        'venue_id', 'weighted_average', 'count',
        'cleanliness_avg', 'cleanliness_count',
        'staff_avg', 'staff_count',
        'atmosphere_avg', 'atmosphere_count',
        'display_average', 'reviewer_name', 'reviewer_email',
        'rating', 'comment', 'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'weighted_average' => 'decimal:2',
            'cleanliness_avg' => 'decimal:2',
            'staff_avg' => 'decimal:2',
            'atmosphere_avg' => 'decimal:2',
            'display_average' => 'decimal:2',
            'is_verified' => 'boolean',
        ];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }
}
