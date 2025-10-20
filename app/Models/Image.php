<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Image extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'venue_id',
        'external_id',
        'uri_small',
        'uri_medium',
        'uri_large',
        'uri_xlarge',
        'is_primary'
    ];
    
    protected $casts = [
        'is_primary' => 'boolean'
    ];
    
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }
}
