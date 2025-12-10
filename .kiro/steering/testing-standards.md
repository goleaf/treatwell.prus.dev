---
inclusion: fileMatch
fileMatchPattern: '*Test.php'
---

# Testing Standards

## PHPUnit Test Structure
All tests must use PHPUnit (convert any Pest tests). Use proper test organization:

```php
class FeatureTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_feature_works_correctly(): void
    {
        // Arrange
        $user = User::factory()->create();
        
        // Act
        $response = $this->actingAs($user)->get('/endpoint');
        
        // Assert
        $response->assertStatus(200);
    }
}
```

## Test Coverage Requirements
- Test all happy paths (successful operations)
- Test failure paths (validation errors, unauthorized access)
- Test edge cases and boundary conditions
- Use model factories instead of manual model creation

## Running Tests
- Run specific test: `php artisan test --filter=test_method_name`
- Run test file: `php artisan test tests/Feature/ExampleTest.php`
- Always run affected tests after changes

## Factory Usage
Check existing factories for custom states before manually setting up models:
```php
// Good - use factory states
$user = User::factory()->verified()->create();

// Avoid - manual setup when factory exists
$user = User::factory()->create(['email_verified_at' => now()]);
```

## Faker Conventions
Follow existing patterns for `$this->faker` vs `fake()`:
```php
// Check existing tests to see which pattern is used
$name = $this->faker->name();
// or
$name = fake()->name();
```