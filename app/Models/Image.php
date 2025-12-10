<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Image extends Model
{
    /** @use HasFactory<\Database\Factories\ImageFactory> */
    use CrudTrait, HasFactory, SoftDeletes;

    protected $fillable = [
        'imageable_type', 'imageable_id', 'venue_id', 'external_id',
        'path', 'uri_small', 'uri_medium', 'uri_large', 'uri_xlarge',
        'is_primary', 'alt_text', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * Scope to get only primary images.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope to order images by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Get the best available image URL based on size preference.
     */
    public function getBestImageUrl(string $size = 'large'): string
    {
        return match ($size) {
            'small' => $this->uri_small ?? $this->uri_medium ?? $this->uri_large ?? $this->uri_xlarge ?? $this->path,
            'medium' => $this->uri_medium ?? $this->uri_large ?? $this->uri_small ?? $this->uri_xlarge ?? $this->path,
            'large' => $this->uri_large ?? $this->uri_xlarge ?? $this->uri_medium ?? $this->uri_small ?? $this->path,
            'xlarge' => $this->uri_xlarge ?? $this->uri_large ?? $this->uri_medium ?? $this->uri_small ?? $this->path,
            default => $this->uri_large ?? $this->uri_medium ?? $this->uri_small ?? $this->path,
        };
    }

    /**
     * Get responsive image srcset attribute.
     */
    public function getResponsiveSrcset(): string
    {
        $srcset = [];

        if ($this->uri_small) {
            $srcset[] = $this->uri_small.' 300w';
        }
        if ($this->uri_medium) {
            $srcset[] = $this->uri_medium.' 600w';
        }
        if ($this->uri_large) {
            $srcset[] = $this->uri_large.' 1200w';
        }
        if ($this->uri_xlarge) {
            $srcset[] = $this->uri_xlarge.' 1800w';
        }

        return implode(', ', $srcset);
    }

    /**
     * Get alt text or generate fallback.
     */
    public function getAltText(): string
    {
        if ($this->alt_text) {
            return $this->alt_text;
        }

        // Generate fallback alt text
        if ($this->venue) {
            return "Image of {$this->venue->name}";
        }

        return 'Image';
    }
}
