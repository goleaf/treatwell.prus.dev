<?php

namespace Tests\Unit;

use App\Http\Requests\ImageRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ImageRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_image_request_validation_rules(): void
    {
        $request = new ImageRequest();
        $rules = $request->rules();

        // Test that required fields are present
        $this->assertArrayHasKey('imageable_type', $rules);
        $this->assertArrayHasKey('imageable_id', $rules);
        $this->assertArrayHasKey('path', $rules);

        // Test that required validation is applied
        $this->assertContains('required', $rules['imageable_type']);
        $this->assertContains('required', $rules['imageable_id']);
        $this->assertContains('required', $rules['path']);
    }

    public function test_image_request_validation_passes_with_valid_data(): void
    {
        $request = new ImageRequest();
        $rules = $request->rules();

        $validData = [
            'imageable_type' => 'App\\Models\\Venue',
            'imageable_id' => 1,
            'path' => '/images/test.jpg',
            'is_primary' => true,
            'alt_text' => 'Test image',
        ];

        $validator = Validator::make($validData, $rules);
        $this->assertTrue($validator->passes());
    }

    public function test_image_request_validation_fails_with_invalid_data(): void
    {
        $request = new ImageRequest();
        $rules = $request->rules();

        $invalidData = [
            'imageable_type' => '', // Required field empty
            'imageable_id' => 'not-a-number', // Should be integer
            'path' => '', // Required field empty
        ];

        $validator = Validator::make($invalidData, $rules);
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('imageable_type'));
        $this->assertTrue($validator->errors()->has('imageable_id'));
        $this->assertTrue($validator->errors()->has('path'));
    }

    public function test_image_request_custom_messages(): void
    {
        $request = new ImageRequest();
        $messages = $request->messages();

        $this->assertArrayHasKey('imageable_type.required', $messages);
        $this->assertArrayHasKey('imageable_id.required', $messages);
        $this->assertArrayHasKey('path.required', $messages);
    }

    public function test_image_request_custom_attributes(): void
    {
        $request = new ImageRequest();
        $attributes = $request->attributes();

        $this->assertArrayHasKey('imageable_type', $attributes);
        $this->assertArrayHasKey('imageable_id', $attributes);
        $this->assertArrayHasKey('alt_text', $attributes);
    }
}
