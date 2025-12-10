<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\Api\StoreTreatmentRequest;
use App\Models\City;
use App\Models\Country;
use App\Models\Treatment;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreTreatmentRequestTest extends TestCase
{
    use RefreshDatabase;

    protected Venue $venue;

    protected function setUp(): void
    {
        parent::setUp();
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        $this->venue = Venue::factory()->create(['city_id' => $city->id]);
    }

    public function test_store_treatment_request_validation_rules(): void
    {
        $request = new StoreTreatmentRequest;
        $rules = $request->rules();

        // Test that required fields are present
        $this->assertArrayHasKey('venue_id', $rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('slug', $rules);

        // Test that required validation is applied
        $this->assertContains('required', $rules['venue_id']);
        $this->assertContains('required', $rules['name']);
        $this->assertContains('required', $rules['slug']);

        // Test that unique validation is applied to slug
        $this->assertContains('unique:treatments,slug', $rules['slug']);

        // Test that exists validation is applied to venue_id
        $this->assertContains('exists:venues,id', $rules['venue_id']);
    }

    public function test_store_treatment_request_validation_passes_with_valid_data(): void
    {
        $request = new StoreTreatmentRequest;
        $rules = $request->rules();

        $validData = [
            'venue_id' => $this->venue->id,
            'name' => 'Test Treatment',
            'slug' => 'test-treatment',
            'description' => 'A test treatment description',
            'duration' => 60,
            'price' => 100.50,
            'min_price' => 80.00,
            'max_price' => 120.00,
            'min_duration' => 45,
            'max_duration' => 90,
            'category_name' => 'Massage',
            'is_active' => true,
            'options' => ['option1', 'option2'],
        ];

        $validator = Validator::make($validData, $rules);
        $this->assertTrue($validator->passes());
    }

    public function test_store_treatment_request_validation_fails_with_missing_required_fields(): void
    {
        $request = new StoreTreatmentRequest;
        $rules = $request->rules();

        $invalidData = [
            'description' => 'A test treatment description',
            'is_active' => true,
        ];

        $validator = Validator::make($invalidData, $rules);
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('venue_id'));
        $this->assertTrue($validator->errors()->has('name'));
        $this->assertTrue($validator->errors()->has('slug'));
    }

    public function test_store_treatment_request_validation_fails_with_invalid_venue_id(): void
    {
        $request = new StoreTreatmentRequest;
        $rules = $request->rules();

        $invalidData = [
            'venue_id' => 999999, // Non-existent venue
            'name' => 'Test Treatment',
            'slug' => 'test-treatment',
        ];

        $validator = Validator::make($invalidData, $rules);
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('venue_id'));
    }

    public function test_store_treatment_request_validation_fails_with_duplicate_slug(): void
    {
        // Create an existing treatment
        Treatment::factory()->create([
            'slug' => 'existing-treatment',
            'venue_id' => $this->venue->id,
        ]);

        $request = new StoreTreatmentRequest;
        $rules = $request->rules();

        $invalidData = [
            'venue_id' => $this->venue->id,
            'name' => 'Test Treatment',
            'slug' => 'existing-treatment', // Duplicate slug
        ];

        $validator = Validator::make($invalidData, $rules);
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('slug'));
    }

    public function test_store_treatment_request_validation_fails_with_negative_prices(): void
    {
        $request = new StoreTreatmentRequest;
        $rules = $request->rules();

        $invalidData = [
            'venue_id' => $this->venue->id,
            'name' => 'Test Treatment',
            'slug' => 'test-treatment',
            'price' => -10.00, // Negative price
            'min_price' => -5.00, // Negative min price
            'max_price' => -15.00, // Negative max price
        ];

        $validator = Validator::make($invalidData, $rules);
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('price'));
        $this->assertTrue($validator->errors()->has('min_price'));
        $this->assertTrue($validator->errors()->has('max_price'));
    }

    public function test_store_treatment_request_validation_fails_with_invalid_price_range(): void
    {
        $request = new StoreTreatmentRequest;
        $rules = $request->rules();

        $invalidData = [
            'venue_id' => $this->venue->id,
            'name' => 'Test Treatment',
            'slug' => 'test-treatment',
            'min_price' => 100.00,
            'max_price' => 50.00, // Max price less than min price
        ];

        $validator = Validator::make($invalidData, $rules);
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('min_price'));
        $this->assertTrue($validator->errors()->has('max_price'));
    }

    public function test_store_treatment_request_validation_fails_with_invalid_duration_range(): void
    {
        $request = new StoreTreatmentRequest;
        $rules = $request->rules();

        $invalidData = [
            'venue_id' => $this->venue->id,
            'name' => 'Test Treatment',
            'slug' => 'test-treatment',
            'min_duration' => 90,
            'max_duration' => 60, // Max duration less than min duration
        ];

        $validator = Validator::make($invalidData, $rules);
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('min_duration'));
        $this->assertTrue($validator->errors()->has('max_duration'));
    }

    public function test_store_treatment_request_validation_fails_with_invalid_duration(): void
    {
        $request = new StoreTreatmentRequest;
        $rules = $request->rules();

        $invalidData = [
            'venue_id' => $this->venue->id,
            'name' => 'Test Treatment',
            'slug' => 'test-treatment',
            'duration' => 0, // Duration must be at least 1
            'min_duration' => 0, // Min duration must be at least 1
            'max_duration' => 0, // Max duration must be at least 1
        ];

        $validator = Validator::make($invalidData, $rules);
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('duration'));
        $this->assertTrue($validator->errors()->has('min_duration'));
        $this->assertTrue($validator->errors()->has('max_duration'));
    }

    public function test_store_treatment_request_validation_fails_with_invalid_data_types(): void
    {
        $request = new StoreTreatmentRequest;
        $rules = $request->rules();

        $invalidData = [
            'venue_id' => 'not-a-number', // Should be integer
            'name' => 123, // Should be string
            'slug' => 456, // Should be string
            'duration' => 'not-a-number', // Should be integer
            'price' => 'not-a-number', // Should be numeric
            'is_active' => 'not-a-boolean', // Should be boolean
            'options' => 'not-an-array', // Should be array
        ];

        $validator = Validator::make($invalidData, $rules);
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('venue_id'));
        $this->assertTrue($validator->errors()->has('name'));
        $this->assertTrue($validator->errors()->has('slug'));
        $this->assertTrue($validator->errors()->has('duration'));
        $this->assertTrue($validator->errors()->has('price'));
        $this->assertTrue($validator->errors()->has('is_active'));
        $this->assertTrue($validator->errors()->has('options'));
    }

    public function test_store_treatment_request_validation_passes_with_optional_fields_null(): void
    {
        $request = new StoreTreatmentRequest;
        $rules = $request->rules();

        $validData = [
            'venue_id' => $this->venue->id,
            'name' => 'Test Treatment',
            'slug' => 'test-treatment',
            'external_id' => null,
            'description' => null,
            'duration' => null,
            'price' => null,
            'min_price' => null,
            'max_price' => null,
            'min_duration' => null,
            'max_duration' => null,
            'category_id' => null,
            'category_name' => null,
            'category' => null,
            'options' => null,
        ];

        $validator = Validator::make($validData, $rules);
        $this->assertTrue($validator->passes());
    }

    public function test_store_treatment_request_validation_passes_with_valid_price_range(): void
    {
        $request = new StoreTreatmentRequest;
        $rules = $request->rules();

        $validData = [
            'venue_id' => $this->venue->id,
            'name' => 'Test Treatment',
            'slug' => 'test-treatment',
            'min_price' => 50.00,
            'max_price' => 100.00, // Max price greater than min price
        ];

        $validator = Validator::make($validData, $rules);
        $this->assertTrue($validator->passes());
    }

    public function test_store_treatment_request_validation_passes_with_valid_duration_range(): void
    {
        $request = new StoreTreatmentRequest;
        $rules = $request->rules();

        $validData = [
            'venue_id' => $this->venue->id,
            'name' => 'Test Treatment',
            'slug' => 'test-treatment',
            'min_duration' => 30,
            'max_duration' => 90, // Max duration greater than min duration
        ];

        $validator = Validator::make($validData, $rules);
        $this->assertTrue($validator->passes());
    }

    public function test_store_treatment_request_authorization_returns_true(): void
    {
        $request = new StoreTreatmentRequest;
        $this->assertTrue($request->authorize());
    }
}
