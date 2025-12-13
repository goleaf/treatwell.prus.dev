# Database Migration Spec

## Overview
Template for creating and managing database migrations in Laravel 12.

## Migration Planning
- [ ] Identify table/column changes needed
- [ ] Plan foreign key relationships
- [ ] Consider indexing requirements
- [ ] Plan rollback strategy

## Implementation Steps
- [ ] Create migration: `php artisan make:migration create_table_name --create=table_name`
- [ ] Or modify existing: `php artisan make:migration add_column_to_table --table=table_name`
- [ ] Define up() method with all column attributes
- [ ] Define down() method for rollback
- [ ] Add proper indexes and foreign keys

## Important Laravel 12 Notes
- When modifying columns, include ALL previous attributes or they'll be dropped
- Use proper column types and constraints
- Add foreign key constraints with proper cascading

## Model Updates
- [ ] Update model with new fillable fields
- [ ] Add/update relationships
- [ ] Update casts() method if needed
- [ ] Create/update factory for new fields

## Testing
- [ ] Test migration runs successfully
- [ ] Test rollback works correctly
- [ ] Update model tests for new fields
- [ ] Test relationships work properly

## Example Migration Structure
```php
public function up(): void
{
    Schema::create('table_name', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->timestamps();
        
        $table->index(['user_id', 'created_at']);
    });
}
```

## References
- #[[file:AGENTS.md]] - Laravel Boost guidelines
- Use `search-docs` tool for migration documentation