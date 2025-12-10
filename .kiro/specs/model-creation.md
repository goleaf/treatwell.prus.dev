# Model Creation Spec

## Overview
Template for creating new Eloquent models following Laravel 12 best practices.

## Planning
- [ ] Define model name and table name
- [ ] Plan relationships with other models
- [ ] Identify fillable fields and validation rules
- [ ] Consider soft deletes, timestamps, or other traits

## Model Creation
- [ ] Create model: `php artisan make:model ModelName -mfs`
  - `-m` creates migration
  - `-f` creates factory
  - `-s` creates seeder
- [ ] Or use individual commands as needed

## Model Implementation
- [ ] Use constructor property promotion for dependencies
- [ ] Define fillable fields
- [ ] Implement casts() method instead of $casts property
- [ ] Add relationship methods with proper return types
- [ ] Add any custom methods with return type hints

## Example Model Structure
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ModelName extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

## Factory and Seeder
- [ ] Create meaningful factory definitions
- [ ] Add factory states for common variations
- [ ] Create seeder if needed for development data

## Testing
- [ ] Create model test for relationships
- [ ] Test model methods and scopes
- [ ] Test factory creates valid models
- [ ] Run `php artisan test --filter=ModelNameTest`

## References
- #[[file:AGENTS.md]] - Laravel Boost guidelines
- Use `search-docs` tool for Eloquent documentation