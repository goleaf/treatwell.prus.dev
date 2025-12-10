<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceFactory> */
    use CrudTrait, HasFactory, SoftDeletes;

    protected $fillable = [
        'venue_id',
        'city_id',
        'name',
        'slug',
        'description',
        'category',
        'duration',
        'price',
        'min_price',
        'max_price',
        'is_active',
        'is_featured',
        'sort_order',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'min_price' => 'decimal:2',
            'max_price' => 'decimal:2',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    // Scope for active services
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope for featured services
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    // Get formatted price range
    public function getPriceRangeAttribute(): string
    {
        if ($this->min_price && $this->max_price && $this->min_price !== $this->max_price) {
            return "€{$this->min_price} - €{$this->max_price}";
        }

        return $this->price ? "€{$this->price}" : 'Price on request';
    }
}
