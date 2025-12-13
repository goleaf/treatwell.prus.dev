<?php

namespace Tests\Unit\Models;

use App\Models\City;
use App\Models\Country;
use App\Models\Image;
use App\Models\Location;
use App\Models\OpeningHour;
use App\Models\Rating;
use App\Models\Treatment;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationshipTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test Country has many Cities relationship.
     */
    public function test_country_has_many_cities(): void
    {
        $country = Country::factory()->create();
        $cities = City::factory()->count(3)->create(['country_id' => $country->id]);

        $this->assertInstanceOf(Collection::class, $country->cities);
        $this->assertCount(3, $country->cities);
        $this->assertTrue($country->cities->contains($cities->first()));
    }

    /**
     * Test City belongs to Country relationship.
     */
    public function test_city_belongs_to_country(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);

        $this->assertInstanceOf(Country::class, $city->country);
        $this->assertEquals($country->id, $city->country->id);
        $this->assertEquals($country->name, $city->country->name);
    }

    /**
     * Test City has many Locations relationship.
     */
    public function test_city_has_many_locations(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        $locations = Location::factory()->count(2)->create(['city_id' => $city->id]);

        $this->assertInstanceOf(Collection::class, $city->locations);
        $this->assertCount(2, $city->locations);
        $this->assertTrue($city->locations->contains($locations->first()));
    }

    /**
     * Test Location belongs to City relationship.
     */
    public function test_location_belongs_to_city(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        $location = Location::factory()->create(['city_id' => $city->id]);

        $this->assertInstanceOf(City::class, $location->city);
        $this->assertEquals($city->id, $location->city->id);
        $this->assertEquals($city->name, $location->city->name);
    }

    /**
     * Test Venue has many Treatments relationship.
     */
    public function test_venue_has_many_treatments(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        $venue = Venue::factory()->create(['city_id' => $city->id]);
        $treatments = Treatment::factory()->count(3)->create(['venue_id' => $venue->id]);

        $this->assertInstanceOf(Collection::class, $venue->treatments);
        $this->assertCount(3, $venue->treatments);
        $this->assertTrue($venue->treatments->contains($treatments->first()));
    }

    /**
     * Test Treatment belongs to Venue relationship.
     */
    public function test_treatment_belongs_to_venue(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        $venue = Venue::factory()->create(['city_id' => $city->id]);
        $treatment = Treatment::factory()->create(['venue_id' => $venue->id]);

        $this->assertInstanceOf(Venue::class, $treatment->venue);
        $this->assertEquals($venue->id, $treatment->venue->id);
        $this->assertEquals($venue->name, $treatment->venue->name);
    }

    /**
     * Test Venue has many Images relationship (polymorphic).
     */
    public function test_venue_has_many_images(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        $venue = Venue::factory()->create(['city_id' => $city->id]);

        $images = Image::factory()->count(2)->create([
            'venue_id' => $venue->id,
        ]);

        $this->assertInstanceOf(Collection::class, $venue->images);
        $this->assertCount(2, $venue->images);
        $this->assertTrue($venue->images->contains($images->first()));
    }

    /**
     * Test Image belongs to imageable (polymorphic).
     */
    public function test_image_belongs_to_imageable(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        $venue = Venue::factory()->create(['city_id' => $city->id]);

        $image = Image::factory()->create([
            'imageable_type' => Venue::class,
            'imageable_id' => $venue->id,
        ]);

        $this->assertInstanceOf(Venue::class, $image->imageable);
        $this->assertEquals($venue->id, $image->imageable->id);
        $this->assertEquals($venue->name, $image->imageable->name);
    }

    /**
     * Test Venue has one Rating relationship.
     */
    public function test_venue_has_one_rating(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        $venue = Venue::factory()->create(['city_id' => $city->id]);
        $rating = Rating::factory()->create(['venue_id' => $venue->id]);

        $this->assertInstanceOf(Rating::class, $venue->ratingDetails);
        $this->assertEquals($rating->id, $venue->ratingDetails->id);
        $this->assertEquals($rating->weighted_average, $venue->ratingDetails->weighted_average);
    }

    /**
     * Test Rating belongs to Venue relationship.
     */
    public function test_rating_belongs_to_venue(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        $venue = Venue::factory()->create(['city_id' => $city->id]);
        $rating = Rating::factory()->create(['venue_id' => $venue->id]);

        $this->assertInstanceOf(Venue::class, $rating->venue);
        $this->assertEquals($venue->id, $rating->venue->id);
        $this->assertEquals($venue->name, $rating->venue->name);
    }

    /**
     * Test Venue has many Opening Hours relationship.
     */
    public function test_venue_has_many_opening_hours(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        $venue = Venue::factory()->create(['city_id' => $city->id]);
        $openingHours = OpeningHour::factory()->count(7)->create(['venue_id' => $venue->id]);

        $this->assertInstanceOf(Collection::class, $venue->openingHours);
        $this->assertCount(7, $venue->openingHours);
        $this->assertTrue($venue->openingHours->contains($openingHours->first()));
    }

    /**
     * Test Opening Hour belongs to Venue relationship.
     */
    public function test_opening_hour_belongs_to_venue(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        $venue = Venue::factory()->create(['city_id' => $city->id]);
        $openingHour = OpeningHour::factory()->create(['venue_id' => $venue->id]);

        $this->assertInstanceOf(Venue::class, $openingHour->venue);
        $this->assertEquals($venue->id, $openingHour->venue->id);
        $this->assertEquals($venue->name, $openingHour->venue->name);
    }

    /**
     * Test eager loading relationships works correctly.
     */
    public function test_eager_loading_relationships(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        $venue = Venue::factory()->create(['city_id' => $city->id]);

        Treatment::factory()->count(2)->create(['venue_id' => $venue->id]);
        Image::factory()->count(2)->create([
            'venue_id' => $venue->id,
        ]);
        Rating::factory()->create(['venue_id' => $venue->id]);

        // Test eager loading multiple relationships
        $venueWithRelations = Venue::with(['treatments', 'images', 'ratingDetails'])
            ->find($venue->id);

        $this->assertTrue($venueWithRelations->relationLoaded('treatments'));
        $this->assertTrue($venueWithRelations->relationLoaded('images'));
        $this->assertTrue($venueWithRelations->relationLoaded('ratingDetails'));

        $this->assertCount(2, $venueWithRelations->treatments);
        $this->assertCount(2, $venueWithRelations->images);
        $this->assertNotNull($venueWithRelations->ratingDetails);
    }

    /**
     * Test nested eager loading works correctly.
     */
    public function test_nested_eager_loading(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        $venue = Venue::factory()->create(['city_id' => $city->id]);
        $location = Location::factory()->create([
            'city_id' => $city->id,
            'venue_id' => $venue->id,
        ]);

        // Test nested eager loading
        $venueWithNestedRelations = Venue::with(['location.city.country'])
            ->find($venue->id);

        $this->assertTrue($venueWithNestedRelations->relationLoaded('location'));
        $this->assertTrue($venueWithNestedRelations->location->relationLoaded('city'));
        $this->assertTrue($venueWithNestedRelations->location->city->relationLoaded('country'));

        $this->assertEquals($country->name, $venueWithNestedRelations->location->city->country->name);
    }

    /**
     * Test relationship queries work correctly.
     */
    public function test_relationship_queries(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        $venue = Venue::factory()->create(['city_id' => $city->id]);

        Treatment::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Massage',
            'category_name' => 'Wellness',
        ]);
        Treatment::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Facial',
            'category_name' => 'Beauty',
        ]);

        // Test querying through relationships
        $venuesWithMassage = Venue::whereHas('treatments', function ($query) {
            $query->where('name', 'like', '%Massage%');
        })->get();

        $this->assertCount(1, $venuesWithMassage);
        $this->assertEquals($venue->id, $venuesWithMassage->first()->id);

        // Test querying with relationship counts
        $venuesWithTreatmentCount = Venue::withCount('treatments')->get();
        $this->assertEquals(2, $venuesWithTreatmentCount->first()->treatments_count);
    }

    /**
     * Test cascade deletion works correctly.
     */
    public function test_cascade_deletion(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        $venue = Venue::factory()->create(['city_id' => $city->id]);

        $treatment = Treatment::factory()->create(['venue_id' => $venue->id]);
        $image = Image::factory()->create([
            'imageable_type' => Venue::class,
            'imageable_id' => $venue->id,
            'venue_id' => $venue->id, // Explicitly set venue_id for foreign key constraint test
        ]);
        $rating = Rating::factory()->create(['venue_id' => $venue->id]);

        // Test soft delete first
        $venue->delete();

        // Check that venue is soft deleted (still exists but with deleted_at timestamp)
        $this->assertSoftDeleted('venues', ['id' => $venue->id]);

        // Test force delete to trigger cascade constraints
        $venue->forceDelete();

        // Check that venue is actually deleted from database
        $this->assertDatabaseMissing('venues', ['id' => $venue->id]);

        // With cascade delete configured, related records should be deleted
        $this->assertDatabaseMissing('treatments', ['id' => $treatment->id]);
        $this->assertDatabaseMissing('ratings', ['id' => $rating->id]);

        // Images should have venue_id set to null (SET NULL constraint)
        // Refresh the image model to get the updated data
        $image->refresh();
        $this->assertNull($image->venue_id, 'Image venue_id should be set to null when venue is deleted');
    }
}
