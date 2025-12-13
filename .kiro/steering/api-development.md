---
inclusion: fileMatch
fileMatchPattern: '*Controller.php,*Resource.php,*Request.php'
---

# API Development Standards

## API Resource Usage
Always use Eloquent API Resources for consistent API responses:

```php
// In Controller
public function index(): JsonResponse
{
    $items = Item::with('user')->paginate();
    return ItemResource::collection($items);
}

public function show(Item $item): ItemResource
{
    return new ItemResource($item->load('user'));
}
```

## Form Request Validation
Create dedicated Form Request classes for all validation:

```php
// CreateItemRequest.php
public function rules(): array
{
    return [
        'name' => ['required', 'string', 'max:255'],
        'description' => ['nullable', 'string'],
        'user_id' => ['required', 'exists:users,id'],
    ];
}

public function messages(): array
{
    return [
        'name.required' => 'The item name is required.',
        'user_id.exists' => 'The selected user is invalid.',
    ];
}
```

## API Versioning
Follow existing API versioning patterns in the application:
- Routes in `routes/api.php` with version prefixes
- Consistent response structure across versions
- Proper deprecation handling

## Error Handling
Use consistent error responses:
```php
// In Controller
try {
    // Operation
} catch (ModelNotFoundException $e) {
    return response()->json(['error' => 'Resource not found'], 404);
}
```

## Authentication & Authorization
- Use Laravel Sanctum for API authentication
- Implement proper authorization with policies or gates
- Check existing auth patterns in the application