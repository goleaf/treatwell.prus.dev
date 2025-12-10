<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Treatment extends Model
{
    use CrudTrait;

    protected $fillable = [
        'venue_id', 'external_id', 'name', 'slug', 'description',
        'duration', 'price', 'min_price', 'max_price',
        'min_duration', 'max_duration', 'category_id', 'category_name',
        'category', 'options', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'min_price' => 'decimal:2',
            'max_price' => 'decimal:2',
            'options' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }
}
