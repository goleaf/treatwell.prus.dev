<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    use CrudTrait;

    protected $fillable = [
        'name',
        'code',
        'slug',
    ];

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }
}
