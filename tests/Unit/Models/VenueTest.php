<?php

namespace Tests\Unit\Models;

use App\Models\Image;
use App\Models\Location;
use App\Models\OpeningHour;
use App\Models\Rating;
use App\Models\Treatment;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenueTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test venue has required fillable attributes.
     */
    public function test_venue_has_fillable_attributes(): void
    {
        $venue = new Venue;

        $this->assertContains('external_id', $venue->getFillable());
        $this->assertContains('name', $venue->getFillable());
        $this->assertContains('description', $venue->getFillable());
        $this->assertContains('type_name', $venue->getFillable());
        $this->assertContains('desktop_uri', $venue->getFillable());
    }

    /**
     * Test venue has proper casts.
     */
    public function test_venue_has_proper_casts(): void
    {
        $venue = new Venue;
        $casts = $venue->getCasts();

        $this->assertArrayHasKey('raw_data', $casts);
        $this->assertEquals('array', $casts['raw_data']);
        $this->assertArrayHasKey('is_new_venue', $casts);
        $this->assertEquals('boolean', $casts['is_new_venue']);
    }

    /**
     * Test venue belongs to location.
     */
    public function test_venue_belongs_to_location(): void
    {
        $venue = new Venue;

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $venue->location());
    }

    /**
     * Test venue belongs to rating.
     */
    public function test_venue_belongs_to_rating(): void
    {
        $venue = new Venue;

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $venue->rating());
    }

    /**
     * Test venue has many images.
     */
    public function test_venue_has_many_images(): void
    {
        $venue = new Venue;

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphMany::class, $venue->images());
    }

    /**
     * Test venue has many opening hours.
     */
    public function test_venue_has_many_opening_hours(): void
    {
        $venue = new Venue;

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $venue->openingHours());
    }

    /**
     * Test venue has many treatments.
     */
    public function test_venue_has_many_treatments(): void
    {
        $venue = new Venue;

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $venue->treatments());
    }

    /**
     * Test venue belongs to city.
     */
    public function test_venue_belongs_to_city(): void
    {
        $venue = new Venue;

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $venue->city());
    }

    /**
     * Test venue relationships work with actual data.
     */
    public function test_venue_relationships_with_data(): void
    {
        // Create a venue with related models
        $venue = Venue::factory()->create(['name' => 'Test Venue']);

        // Create location
        Location::factory()->create(['venue_id' => $venue->id]);

        // Create rating
        Rating::factory()->create(['venue_id' => $venue->id]);

        // Create images (polymorphic relationship)
        Image::factory()->count(3)->create([
            'imageable_type' => Venue::class,
            'imageable_id' => $venue->id,
        ]);

        // Create opening hours
        OpeningHour::factory()->count(7)->create(['venue_id' => $venue->id]);

        // Create treatments
        Treatment::factory()->count(5)->create(['venue_id' => $venue->id]);

        // Load relationships
        $venue->load('locations', 'ratings', 'images', 'openingHours', 'treatments');

        // Test relationships
        $this->assertInstanceOf(Collection::class, $venue->locations);
        $this->assertInstanceOf(Collection::class, $venue->ratings);
        $this->assertInstanceOf(Collection::class, $venue->images);
        $this->assertCount(3, $venue->images);
        $this->assertInstanceOf(Collection::class, $venue->openingHours);
        $this->assertCount(7, $venue->openingHours);
        $this->assertInstanceOf(Collection::class, $venue->treatments);
        $this->assertCount(5, $venue->treatments);
    }

    public function test_location(): void
    {
        $this->markTestIncomplete('Test for location needs implementation');
    }

    public function test_rating(): void
    {
        $this->markTestIncomplete('Test for rating needs implementation');
    }

    public function test_images(): void
    {
        $this->markTestIncomplete('Test for images needs implementation');
    }

    public function test_opening_hours(): void
    {
        $this->markTestIncomplete('Test for openingHours needs implementation');
    }

    public function test_treatments(): void
    {
        $this->markTestIncomplete('Test for treatments needs implementation');
    }

    public function test_procedures(): void
    {
        $this->markTestIncomplete('Test for procedures needs implementation');
    }

    public function test_cities(): void
    {
        $this->markTestIncomplete('Test for cities needs implementation');
    }

    public function test_get_primary_image_url(): void
    {
        $this->markTestIncomplete('Test for getPrimaryImageUrl needs implementation');
    }

    public function test_get_average_rating(): void
    {
        $this->markTestIncomplete('Test for getAverageRating needs implementation');
    }

    public function test_get_city_name(): void
    {
        $this->markTestIncomplete('Test for getCityName needs implementation');
    }

    public function test_scope_by_city(): void
    {
        $this->markTestIncomplete('Test for scopeByCity needs implementation');
    }

    public function test_scope_by_procedure(): void
    {
        $this->markTestIncomplete('Test for scopeByProcedure needs implementation');
    }
}
