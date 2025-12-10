<?php

namespace Tests\Unit;

use App\Http\Resources\CityResource;
use App\Http\Resources\CountryResource;
use App\Http\Resources\ProcedureResource;
use App\Http\Resources\RatingResource;
use App\Http\Resources\ServiceResource;
use App\Http\Resources\TreatmentResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\VenueResource;
use App\Models\City;
use App\Models\Country;
use App\Models\Procedure;
use App\Models\Rating;
use App\Models\Service;
use App\Models\Treatment;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_country_resource_transforms_data_correctly(): void
    {
        $country = Country::factory()->create();

        $resource = new CountryResource($country);
        $array = $resource->toArray(request());

        $this->assertEquals($country->id, $array['id']);
        $this->assertEquals($country->name, $array['name']);
        $this->assertEquals($country->code, $array['code']);
        $this->assertEquals($country->slug, $array['slug']);
        $this->assertEquals($country->normalised_name, $array['normalised_name']);
        $this->assertEquals($country->active, $array['active']);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
    }

    public function test_city_resource_transforms_data_correctly(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);

        $resource = new CityResource($city);
        $array = $resource->toArray(request());

        $this->assertEquals($city->id, $array['id']);
        $this->assertEquals($city->country_id, $array['country_id']);
        $this->assertEquals($city->name, $array['name']);
        $this->assertEquals($city->slug, $array['slug']);
        $this->assertEquals($city->latitude, $array['latitude']);
        $this->assertEquals($city->longitude, $array['longitude']);
        $this->assertEquals($city->timezone, $array['timezone']);
        $this->assertEquals($city->is_active, $array['is_active']);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
    }

    public function test_city_resource_includes_relationships_when_loaded(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        $city->load('country');

        $resource = new CityResource($city);
        $array = $resource->toArray(request());

        $this->assertArrayHasKey('country', $array);
        $this->assertEquals($country->id, $array['country']['id']);
        $this->assertEquals($country->name, $array['country']['name']);
    }

    public function test_treatment_resource_transforms_data_correctly(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        $venue = Venue::factory()->create(['city_id' => $city->id]);
        $treatment = Treatment::factory()->create(['venue_id' => $venue->id]);

        $resource = new TreatmentResource($treatment);
        $array = $resource->toArray(request());

        $this->assertEquals($treatment->id, $array['id']);
        $this->assertEquals($treatment->venue_id, $array['venue_id']);
        $this->assertEquals($treatment->name, $array['name']);
        $this->assertEquals($treatment->description, $array['description']);
        $this->assertEquals($treatment->duration, $array['duration']);
        $this->assertEquals($treatment->price, $array['price']);
        $this->assertEquals($treatment->currency, $array['currency']);
        $this->assertEquals($treatment->category, $array['category']);
        $this->assertEquals($treatment->is_active, $array['is_active']);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
    }

    public function test_venue_resource_transforms_data_correctly(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        $venue = Venue::factory()->create(['city_id' => $city->id]);

        $resource = new VenueResource($venue);
        $array = $resource->toArray(request());

        $this->assertEquals($venue->id, $array['id']);
        $this->assertEquals($venue->name, $array['name']);
        $this->assertEquals($venue->slug, $array['slug']);
        $this->assertEquals($venue->description, $array['description']);
        $this->assertEquals($venue->address, $array['address']);
        $this->assertEquals($venue->phone, $array['phone']);
        $this->assertEquals($venue->email, $array['email']);
        $this->assertEquals($venue->website, $array['website']);
        $this->assertEquals($venue->is_active, $array['is_active']);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
    }

    public function test_rating_resource_transforms_data_correctly(): void
    {
        $rating = Rating::factory()->create([
            'weighted_average' => 4.5,
            'count' => 10,
            'cleanliness_avg' => 4.2,
            'staff_avg' => 4.8,
            'atmosphere_avg' => 4.3,
            'is_verified' => true,
        ]);

        $resource = new RatingResource($rating);
        $array = $resource->toArray(request());

        $this->assertEquals($rating->id, $array['id']);
        $this->assertEquals(4.5, $array['weighted_average']);
        $this->assertEquals(10, $array['count']);
        $this->assertEquals(4.2, $array['cleanliness_avg']);
        $this->assertEquals(4.8, $array['staff_avg']);
        $this->assertEquals(4.3, $array['atmosphere_avg']);
        $this->assertTrue($array['is_verified']);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
    }

    public function test_service_resource_transforms_data_correctly(): void
    {
        $service = Service::factory()->create();

        $resource = new ServiceResource($service);
        $array = $resource->toArray(request());

        $this->assertEquals($service->id, $array['id']);
        $this->assertEquals($service->name, $array['name']);
        $this->assertEquals($service->slug, $array['slug']);
        $this->assertEquals($service->description, $array['description']);
        $this->assertEquals($service->category, $array['category']);
        $this->assertEquals($service->price, $array['price']);
        $this->assertEquals($service->min_price, $array['min_price']);
        $this->assertEquals($service->max_price, $array['max_price']);
        $this->assertEquals($service->is_active, $array['is_active']);
        $this->assertEquals($service->is_featured, $array['is_featured']);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
    }

    public function test_procedure_resource_transforms_data_correctly(): void
    {
        $procedure = Procedure::factory()->create();

        $resource = new ProcedureResource($procedure);
        $array = $resource->toArray(request());

        $this->assertEquals($procedure->id, $array['id']);
        $this->assertEquals($procedure->name, $array['name']);
        $this->assertEquals($procedure->slug, $array['slug']);
        $this->assertEquals($procedure->description, $array['description']);
        $this->assertEquals($procedure->category, $array['category']);
        $this->assertEquals($procedure->is_active, $array['is_active']);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
    }

    public function test_user_resource_excludes_sensitive_data(): void
    {
        $user = User::factory()->create();

        $resource = new UserResource($user);
        $array = $resource->toArray(request());

        $this->assertEquals($user->id, $array['id']);
        $this->assertEquals($user->name, $array['name']);
        $this->assertEquals($user->email, $array['email']);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);

        // Ensure sensitive data is excluded
        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
    }

    public function test_resources_handle_null_values_correctly(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create([
            'country_id' => $country->id,
            'latitude' => null,
            'longitude' => null,
        ]);

        $resource = new CityResource($city);
        $array = $resource->toArray(request());

        $this->assertNull($array['latitude']);
        $this->assertNull($array['longitude']);
    }

    public function test_resources_handle_boolean_values_correctly(): void
    {
        $country = Country::factory()->create(['active' => false]);

        $resource = new CountryResource($country);
        $array = $resource->toArray(request());

        $this->assertFalse($array['active']);
        $this->assertIsBool($array['active']);
    }

    public function test_resources_format_timestamps_correctly(): void
    {
        $country = Country::factory()->create();

        $resource = new CountryResource($country);
        $array = $resource->toArray(request());

        $this->assertNotNull($array['created_at']);
        $this->assertNotNull($array['updated_at']);
        // Timestamps are Carbon objects in API Resources, they get serialized to strings in JSON
        $this->assertInstanceOf(\Carbon\Carbon::class, $array['created_at']);
        $this->assertInstanceOf(\Carbon\Carbon::class, $array['updated_at']);
    }
}
