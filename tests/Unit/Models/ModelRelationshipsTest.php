<?php

namespace Tests\Unit\Models;

use App\Models\City;
use App\Models\Country;
use App\Models\Image;
use App\Models\Location;
use App\Models\OpeningHour;
use App\Models\Procedure;
use App\Models\Rating;
use App\Models\Treatment;
use App\Models\Venue;
use Tests\TestCase;

class ModelRelationshipsTest extends TestCase
{
    /**
     * Test country model relationships.
     *
     * @return void
     */
    public function test_country_relationships(): void
    {
        $country = new Country();
        
        $this->assertContains('name', $country->getFillable());
        $this->assertContains('code', $country->getFillable());
        $this->assertContains('normalised_name', $country->getFillable());
        $this->assertContains('active', $country->getFillable());
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $country->cities());
    }
    
    /**
     * Test city model relationships.
     *
     * @return void
     */
    public function test_city_relationships(): void
    {
        $city = new City();
        
        $this->assertContains('country_id', $city->getFillable());
        $this->assertContains('name', $city->getFillable());
        $this->assertContains('normalised_name', $city->getFillable());
        $this->assertContains('entity_id', $city->getFillable());
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $city->country());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $city->locations());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $city->mainCity());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $city->subregions());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $city->venues());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $city->procedures());
    }
    
    /**
     * Test venue model relationships.
     *
     * @return void
     */
    public function test_venue_relationships(): void
    {
        $venue = new Venue();
        
        $this->assertContains('name', $venue->getFillable());
        $this->assertContains('description', $venue->getFillable());
        $this->assertContains('type_name', $venue->getFillable());
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $venue->location());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $venue->rating());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $venue->images());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $venue->openingHours());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $venue->treatments());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $venue->procedures());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $venue->cities());
    }
    
    /**
     * Test location model relationships.
     *
     * @return void
     */
    public function test_location_relationships(): void
    {
        $location = new Location();
        
        $this->assertContains('venue_id', $location->getFillable());
        $this->assertContains('city_id', $location->getFillable());
        $this->assertContains('latitude', $location->getFillable());
        $this->assertContains('longitude', $location->getFillable());
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $location->venue());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $location->city());
    }
    
    /**
     * Test treatment model relationships.
     *
     * @return void
     */
    public function test_treatment_relationships(): void
    {
        $treatment = new Treatment();
        
        $this->assertContains('venue_id', $treatment->getFillable());
        $this->assertContains('name', $treatment->getFillable());
        $this->assertContains('min_price', $treatment->getFillable());
        $this->assertContains('max_price', $treatment->getFillable());
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $treatment->venue());
    }
    
    /**
     * Test image model relationships.
     *
     * @return void
     */
    public function test_image_relationships(): void
    {
        $image = new Image();
        
        $this->assertContains('venue_id', $image->getFillable());
        $this->assertContains('external_id', $image->getFillable());
        $this->assertContains('is_primary', $image->getFillable());
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $image->venue());
    }
    
    /**
     * Test rating model relationships.
     *
     * @return void
     */
    public function test_rating_relationships(): void
    {
        $rating = new Rating();
        
        $this->assertContains('venue_id', $rating->getFillable());
        $this->assertContains('weighted_average', $rating->getFillable());
        $this->assertContains('count', $rating->getFillable());
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $rating->venue());
    }
    
    /**
     * Test opening hour model relationships.
     *
     * @return void
     */
    public function test_opening_hour_relationships(): void
    {
        $openingHour = new OpeningHour();
        
        $this->assertContains('venue_id', $openingHour->getFillable());
        $this->assertContains('day_of_week', $openingHour->getFillable());
        $this->assertContains('opening_time', $openingHour->getFillable());
        $this->assertContains('closing_time', $openingHour->getFillable());
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $openingHour->venue());
    }
    
    /**
     * Test procedure model relationships.
     *
     * @return void
     */
    public function test_procedure_relationships(): void
    {
        $procedure = new Procedure();
        
        $this->assertContains('name', $procedure->getFillable());
        $this->assertContains('slug', $procedure->getFillable());
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $procedure->venues());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $procedure->cities());
    }
} 