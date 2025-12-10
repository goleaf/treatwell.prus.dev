<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Procedure extends Model
{
    /** @use HasFactory<\Database\Factories\ProcedureFactory> */
    use CrudTrait, HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'category',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function venues(): BelongsToMany
    {
        return $this->belongsToMany(Venue::class);
    }

    public function cities(): BelongsToMany
    {
        return $this->belongsToMany(City::class);
    }
}
