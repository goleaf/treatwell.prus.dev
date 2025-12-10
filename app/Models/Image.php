<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Image extends Model
{
    use CrudTrait;

    protected $fillable = [
        'imageable_type', 'imageable_id', 'external_id',
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
}
