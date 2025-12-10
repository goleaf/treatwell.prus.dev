<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    /** @use HasFactory<\Database\Factories\CountryFactory> */
    use CrudTrait, HasFactory;

    protected $fillable = [
        'name',
        'code',
        'slug',
        'normalised_name',
        'active',
    ];

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }
}
