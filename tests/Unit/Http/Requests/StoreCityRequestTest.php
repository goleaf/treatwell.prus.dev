<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\Api\StoreCityRequest;
use App\Models\City;
use App\Models\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreCityRequestTest extends TestCase
{
    use RefreshDatabase;

    protected Country $country;

    protected function setUp(): void
    {
        parent::setUp();
        $this->country = Country::factory()->create();
    }

    public function test_store_city_request_validation_rules(): void
    {
        $request = new StoreCityRequest;
        $rules = $request->rules();

        // Test that required fields are present
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('slug', $rules);
        $this->assertArrayHasKey('country_id', $rules);

        // Test that required validation is applied
        $this->assertContains('required', $rules['name']);
        $this->assertContains('required', $rules['slug']);
        $this->assertContains('required', $rules['country_id']);

        // Test that unique validation is applied to slug
        $this->assertContains('unique:cities,slug', $rules['slug']);

        // Test that exists validation is applied to country_id
        $this->assertContains('exists:countries,id', $rules['country_id']);
    }

    public function test_store_city_request_validation_passes_with_valid_data(): void
    {
        $request = new StoreCityRequest;
        $rules = $request->rules();

        $validData = [
            'name' => 'Test City',
            'slug' => 'test-city',
            'normalised_name' => 'test city',
            'country_id' => $this->country->id,
            'is_main_city' => true,
            'latitude' => 51.5074,
            'longitude' => -0.1278,
            'type' => 'city',
            'radius_distance' => 10.5,
            'radius_unit' => 'km',
        ];

        $validator = Validator::make($validData, $rules);
        $this->assertTrue($validator->passes());
    }

    public function test_store_city_request_validation_fails_with_missing_required_fields(): void
    {
        $request = new StoreCityRequest;
        $rules = $request->rules();

        $invalidData = [
            'normalised_name' => 'test city',
            'is_main_city' => true,
        ];

        $validator = Validator::make($invalidData, $rules);
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('name'));
        $this->assertTrue($validator->errors()->has('slug'));
        $this->assertTrue($validator->errors()->has('country_id'));
    }

    public function test_store_city_request_validation_fails_with_invalid_country_id(): void
    {
        $request = new StoreCityRequest;
        $rules = $request->rules();

        $invalidData = [
            'name' => 'Test City',
            'slug' => 'test-city',
            'country_id' => 999999, // Non-existent country
        ];

        $validator = Validator::make($invalidData, $rules);
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('country_id'));
    }

    public function test_store_city_request_validation_fails_with_duplicate_slug(): void
    {
        // Create an existing city
        City::factory()->create([
            'slug' => 'existing-city',
            'country_id' => $this->country->id,
        ]);

        $request = new StoreCityRequest;
        $rules = $request->rules();

        $invalidData = [
            'name' => 'Test City',
            'slug' => 'existing-city', // Duplicate slug
            'country_id' => $this->country->id,
        ];

        $validator = Validator::make($invalidData, $rules);
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('slug'));
    }

    public function test_store_city_request_validation_fails_with_invalid_coordinates(): void
    {
        $request = new StoreCityRequest;
        $rules = $request->rules();

        $invalidData = [
            'name' => 'Test City',
            'slug' => 'test-city',
            'country_id' => $this->country->id,
            'latitude' => 91, // Invalid latitude (> 90)
            'longitude' => 181, // Invalid longitude (> 180)
        ];

        $validator = Validator::make($invalidData, $rules);
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('latitude'));
        $this->assertTrue($validator->errors()->has('longitude'));
    }

    public function test_store_city_request_validation_fails_with_invalid_data_types(): void
    {
        $request = new StoreCityRequest;
        $rules = $request->rules();

        $invalidData = [
            'name' => 123, // Should be string
            'slug' => 456, // Should be string
            'country_id' => 'not-a-number', // Should be integer
            'is_main_city' => 'not-a-boolean', // Should be boolean
            'latitude' => 'not-a-number', // Should be numeric
            'longitude' => 'not-a-number', // Should be numeric
            'radius_distance' => 'not-a-number', // Should be numeric
        ];

        $validator = Validator::make($invalidData, $rules);
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('name'));
        $this->assertTrue($validator->errors()->has('slug'));
        $this->assertTrue($validator->errors()->has('country_id'));
        $this->assertTrue($validator->errors()->has('is_main_city'));
        $this->assertTrue($validator->errors()->has('latitude'));
        $this->assertTrue($validator->errors()->has('longitude'));
        $this->assertTrue($validator->errors()->has('radius_distance'));
    }

    public function test_store_city_request_validation_fails_with_negative_radius_distance(): void
    {
        $request = new StoreCityRequest;
        $rules = $request->rules();

        $invalidData = [
            'name' => 'Test City',
            'slug' => 'test-city',
            'country_id' => $this->country->id,
            'radius_distance' => -10, // Negative value
        ];

        $validator = Validator::make($invalidData, $rules);
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('radius_distance'));
    }

    public function test_store_city_request_validation_passes_with_optional_fields_null(): void
    {
        $request = new StoreCityRequest;
        $rules = $request->rules();

        $validData = [
            'name' => 'Test City',
            'slug' => 'test-city',
            'country_id' => $this->country->id,
            'normalised_name' => null,
            'entity_id' => null,
            'subregion' => null,
            'latitude' => null,
            'longitude' => null,
            'main_city_id' => null,
            'type' => null,
            'radius_distance' => null,
            'radius_unit' => null,
        ];

        $validator = Validator::make($validData, $rules);
        $this->assertTrue($validator->passes());
    }

    public function test_store_city_request_authorization_returns_true(): void
    {
        $request = new StoreCityRequest;
        $this->assertTrue($request->authorize());
    }
}
