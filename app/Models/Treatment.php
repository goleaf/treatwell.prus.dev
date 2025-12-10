<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Treatment extends Model
{
    /** @use HasFactory<\Database\Factories\TreatmentFactory> */
    use CrudTrait, HasFactory, SoftDeletes;

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

    /**
     * Scope to get only active treatments.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter treatments by category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to filter treatments by price range.
     */
    public function scopeByPriceRange($query, $minPrice, $maxPrice)
    {
        return $query->where(function ($q) use ($minPrice, $maxPrice) {
            $q->whereBetween('price', [$minPrice, $maxPrice])
                ->orWhereBetween('min_price', [$minPrice, $maxPrice])
                ->orWhereBetween('max_price', [$minPrice, $maxPrice]);
        });
    }

    /**
     * Get formatted price display.
     */
    public function getFormattedPrice(): string
    {
        if ($this->min_price && $this->max_price && $this->min_price !== $this->max_price) {
            return "€{$this->min_price} - €{$this->max_price}";
        }

        if ($this->price) {
            return "€{$this->price}";
        }

        return 'Price on request';
    }

    /**
     * Get formatted duration display.
     */
    public function getFormattedDuration(): string
    {
        if ($this->min_duration && $this->max_duration && $this->min_duration !== $this->max_duration) {
            return "{$this->min_duration} - {$this->max_duration} min";
        }

        if ($this->duration) {
            return "{$this->duration} min";
        }

        return 'Duration varies';
    }

    /**
     * Check if treatment has price range.
     */
    public function hasPriceRange(): bool
    {
        return $this->min_price && $this->max_price && $this->min_price !== $this->max_price;
    }

    /**
     * Check if treatment has duration range.
     */
    public function hasDurationRange(): bool
    {
        return $this->min_duration && $this->max_duration && $this->min_duration !== $this->max_duration;
    }
}
