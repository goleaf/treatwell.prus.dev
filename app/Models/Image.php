<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Image extends Model
{
    /** @use HasFactory<\Database\Factories\ImageFactory> */
    use CrudTrait, HasFactory;

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
}
