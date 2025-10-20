<?php

namespace Tests\Unit\Models;

use App\Models\Treatment;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TreatmentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test treatment has required fillable attributes.
     *
     * @return void
     */
    public function test_treatment_has_fillable_attributes(): void
    {
        $treatment = new Treatment();
        
        $this->assertContains('venue_id', $treatment->getFillable());
        $this->assertContains('external_id', $treatment->getFillable());
        $this->assertContains('name', $treatment->getFillable());
        $this->assertContains('min_price', $treatment->getFillable());
        $this->assertContains('max_price', $treatment->getFillable());
        $this->assertContains('min_duration', $treatment->getFillable());
        $this->assertContains('max_duration', $treatment->getFillable());
        $this->assertContains('category_id', $treatment->getFillable());
        $this->assertContains('category_name', $treatment->getFillable());
    }

    /**
     * Test treatment belongs to a venue.
     *
     * @return void
     */
    public function test_treatment_belongs_to_venue(): void
    {
        $treatment = new Treatment();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $treatment->venue());
    }

    /**
     * Test creating a treatment with a venue.
     *
     * @return void
     */
    public function test_creating_treatment_with_venue(): void
    {
        // Create a venue
        $venue = Venue::factory()->create([
            'name' => 'Test Salon'
        ]);
        
        // Create a treatment associated with this venue
        $treatment = Treatment::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Swedish Massage',
            'min_price' => 50,
            'max_price' => 80,
            'category_name' => 'Massage'
        ]);
        
        // Retrieve the treatment from the database
        $dbTreatment = Treatment::find($treatment->id);
        
        // Test relationship
        $this->assertInstanceOf(Venue::class, $dbTreatment->venue);
        $this->assertEquals('Test Salon', $dbTreatment->venue->name);
        
        // Test treatment attributes
        $this->assertEquals('Swedish Massage', $dbTreatment->name);
        $this->assertEquals(50, $dbTreatment->min_price);
        $this->assertEquals(80, $dbTreatment->max_price);
        $this->assertEquals('Massage', $dbTreatment->category_name);
    }
} 