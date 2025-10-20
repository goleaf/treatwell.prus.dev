<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\TreatmentController;
use App\Models\Treatment;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class TreatmentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $treatmentController;

    protected function setUp(): void
    {
        parent::setUp();
        $this->treatmentController = new TreatmentController;
    }

    /**
     * Test that categories method returns unique treatment categories.
     *
     * @return void
     */
    public function test_categories_returns_unique_treatment_categories()
    {
        $venue = Venue::factory()->create();

        // Create treatments with same category
        Treatment::factory()->create([
            'venue_id' => $venue->id,
            'category_name' => 'Massage',
        ]);

        Treatment::factory()->create([
            'venue_id' => $venue->id,
            'category_name' => 'Massage',
        ]);

        // Create treatment with different category
        Treatment::factory()->create([
            'venue_id' => $venue->id,
            'category_name' => 'Haircut',
        ]);

        $response = $this->treatmentController->categories();
        $data = $response->getData(true);

        // We should have only 2 unique categories despite having 3 treatments
        $this->assertCount(2, $data, 'Should return exactly 2 unique categories');
        $this->assertContains('Massage', $data, 'Response should contain Massage category');
        $this->assertContains('Haircut', $data, 'Response should contain Haircut category');
    }

    /**
     * Test that venuesByTreatment correctly filters venues by treatment category.
     *
     * @return void
     */
    public function test_venues_by_treatment_filters_by_category()
    {
        $venue1 = Venue::factory()->create(['name' => 'Massage Salon']);
        $venue2 = Venue::factory()->create(['name' => 'Hair Salon']);

        Treatment::factory()->create([
            'venue_id' => $venue1->id,
            'category_name' => 'Massage',
            'name' => 'Swedish Massage',
        ]);

        Treatment::factory()->create([
            'venue_id' => $venue2->id,
            'category_name' => 'Haircut',
            'name' => 'Men\'s Haircut',
        ]);

        $request = new Request(['category' => 'Massage']);
        $response = $this->treatmentController->venuesByTreatment($request);
        $data = $response->getData(true)['data'];

        $this->assertCount(1, $data, 'Should return exactly 1 venue when filtered by Massage category');
        $this->assertEquals('Massage Salon', $data[0]['name'], 'The returned venue should be Massage Salon');
    }

    /**
     * Test that venuesByTreatment correctly filters venues by treatment name.
     *
     * @return void
     */
    public function test_venues_by_treatment_filters_by_name()
    {
        $venue1 = Venue::factory()->create(['name' => 'Spa 1']);
        $venue2 = Venue::factory()->create(['name' => 'Spa 2']);

        Treatment::factory()->create([
            'venue_id' => $venue1->id,
            'name' => 'Deep Tissue Massage',
        ]);

        Treatment::factory()->create([
            'venue_id' => $venue2->id,
            'name' => 'Swedish Massage',
        ]);

        $request = new Request(['name' => 'Deep']);
        $response = $this->treatmentController->venuesByTreatment($request);
        $data = $response->getData(true)['data'];

        $this->assertCount(1, $data, 'Should return exactly 1 venue when filtered by "Deep" in treatment name');
        $this->assertEquals('Spa 1', $data[0]['name'], 'The returned venue should be Spa 1');
    }

    /**
     * Test that venuesByPriceRange correctly filters venues by price range.
     *
     * @return void
     */
    public function test_venues_by_price_range_filters_correctly()
    {
        $venue1 = Venue::factory()->create(['name' => 'Budget Salon']);
        $venue2 = Venue::factory()->create(['name' => 'Luxury Salon']);

        Treatment::factory()->create([
            'venue_id' => $venue1->id,
            'min_price' => 30,
            'max_price' => 50,
        ]);

        Treatment::factory()->create([
            'venue_id' => $venue2->id,
            'min_price' => 100,
            'max_price' => 200,
        ]);

        // Test min price filter
        $request = new Request(['min_price' => 80]);
        $response = $this->treatmentController->venuesByPriceRange($request);
        $data = $response->getData(true)['data'];

        $this->assertCount(1, $data, 'Should return exactly 1 venue when min_price is 80');
        $this->assertEquals('Luxury Salon', $data[0]['name'], 'The returned venue should be Luxury Salon');

        // Test max price filter
        $request = new Request(['max_price' => 60]);
        $response = $this->treatmentController->venuesByPriceRange($request);
        $data = $response->getData(true)['data'];

        $this->assertCount(1, $data, 'Should return exactly 1 venue when max_price is 60');
        $this->assertEquals('Budget Salon', $data[0]['name'], 'The returned venue should be Budget Salon');
    }

    /**
     * Test that priceStats returns correct min, max and average prices.
     *
     * @return void
     */
    public function test_price_stats_returns_correct_values()
    {
        $venue = Venue::factory()->create();

        Treatment::factory()->create([
            'venue_id' => $venue->id,
            'min_price' => 10,
            'max_price' => 50,
        ]);

        Treatment::factory()->create([
            'venue_id' => $venue->id,
            'min_price' => 30,
            'max_price' => 100,
        ]);

        $response = $this->treatmentController->priceStats();
        $data = $response->getData(true);

        $this->assertEquals(10, $data['min_price'], 'Min price should be 10');
        $this->assertEquals(100, $data['max_price'], 'Max price should be 100');
        $this->assertEquals(20, $data['avg_price'], 'Average price should be 20');
    }
}
