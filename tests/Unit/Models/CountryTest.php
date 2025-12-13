<?php

namespace Tests\Unit\Models;

use App\Models\City;
use App\Models\Country;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CountryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test country has required fillable attributes.
     */
    public function test_country_has_fillable_attributes(): void
    {
        $country = new Country;

        $this->assertContains('name', $country->getFillable());
        $this->assertContains('code', $country->getFillable());
        $this->assertContains('slug', $country->getFillable());
    }

    /**
     * Test country has many cities relationship.
     */
    public function test_country_has_many_cities(): void
    {
        $country = new Country;

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $country->cities());
    }

    /**
     * Test country-cities relationship with actual data.
     */
    public function test_country_cities_relationship(): void
    {
        // Create a country
        $country = Country::factory()->create(['name' => 'Lithuania']);

        // Create cities for this country
        City::factory()->count(3)->create([
            'country_id' => $country->id,
        ]);

        // Test the relationship
        $this->assertInstanceOf(Collection::class, $country->cities);
        $this->assertCount(3, $country->cities);
        $this->assertEquals($country->id, $country->cities->first()->country_id);
    }

    /**
     * Test creating a country and verifying database storage.
     */
    public function test_creating_country(): void
    {
        // Create country using the model
        $country = Country::factory()->create([
            'name' => 'Lithuania',
            'code' => 'LT',
            'slug' => 'lithuania',
        ]);

        // Verify it's in the database
        $this->assertDatabaseHas('countries', [
            'id' => $country->id,
            'name' => 'Lithuania',
            'code' => 'LT',
            'slug' => 'lithuania',
        ]);
    }

    public function test_cities(): void
    {
        $this->markTestIncomplete('Test for cities needs implementation');
    }
}
