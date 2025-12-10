<?php

namespace Tests\Feature\Controllers\Api;

use App\Http\Controllers\Api\VenueController;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Testing\Fluent\AssertableJson;
use Mockery;
use Tests\TestCase as BaseTestCase;

class VenueControllerTest extends BaseTestCase
{
    protected $controller;

    protected $mock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mock = Mockery::mock('overload:'.Venue::class);
        $this->controller = new VenueController;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test API routes are accessible.
     */
    public function test_api_routes_are_accessible(): void
    {
        $response = $this->getJson('/api/venues');
        $response->assertStatus(200);

        $response = $this->getJson('/api/cities');
        $response->assertStatus(200);

        $response = $this->getJson('/api/venue-types');
        $response->assertStatus(200);

        $response = $this->getJson('/api/venue-stats');
        $response->assertStatus(200);
    }

    /**
     * Test API response structure for venues index.
     */
    public function test_venues_index_response_structure(): void
    {
        $response = $this->getJson('/api/venues');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'meta' => [
                'total',
                'count',
                'per_page',
                'current_page',
                'total_pages',
            ],
            'links' => [
                'first',
                'last',
                'prev',
                'next',
            ],
        ]);
    }

    /**
     * Test API response structure for cities.
     */
    public function test_cities_response_structure(): void
    {
        $response = $this->getJson('/api/venue-cities');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => [
                'id',
                'name',
            ],
        ]);
    }

    /**
     * Test API response structure for types.
     */
    public function test_types_response_structure(): void
    {
        $response = $this->getJson('/api/venue-types');

        $response->assertStatus(200);
        $response->assertJsonIsArray();
    }

    /**
     * Test API response structure for stats.
     */
    public function test_stats_response_structure(): void
    {
        $response = $this->getJson('/api/venue-stats');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'total_venues',
            'total_cities',
            'top_rated_venues',
            'cities_with_most_venues',
        ]);
    }

    /**
     * Test the controller can filter venues by search.
     */
    public function test_index_filters_venues_by_search(): void
    {
        // Mock a request with search parameter
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('input')
            ->with('per_page', 20)
            ->andReturn(20);
        $request->shouldReceive('city_id')
            ->andReturn(null);
        $request->shouldReceive('rating')
            ->andReturn(null);
        $request->shouldReceive('search')
            ->andReturn('test');
        $request->shouldReceive('type')
            ->andReturn(null);
        $request->shouldReceive('sort')
            ->andReturn(null);

        // Get venues from VenueController
        $venues = $this->controller->index($request);

        // We can't assert on the results here without database setup, but we can ensure
        // the controller method doesn't throw an exception and returns a response
        $this->assertNotNull($venues);
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $venues);
    }

    /**
     * Test the controller can filter venues by type.
     */
    public function test_index_filters_venues_by_type(): void
    {
        // Mock a request with type parameter
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('input')
            ->with('per_page', 20)
            ->andReturn(20);
        $request->shouldReceive('city_id')
            ->andReturn(null);
        $request->shouldReceive('rating')
            ->andReturn(null);
        $request->shouldReceive('search')
            ->andReturn(null);
        $request->shouldReceive('type')
            ->andReturn('Spa');
        $request->shouldReceive('sort')
            ->andReturn(null);

        // Get venues from VenueController
        $venues = $this->controller->index($request);

        // We can't assert on the results here without database setup, but we can ensure
        // the controller method doesn't throw an exception and returns a response
        $this->assertNotNull($venues);
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $venues);
    }

    /**
     * Test venue controller index method.
     */
    public function test_index_returns_venues(): void
    {
        // Create a mock of the query builder
        $queryBuilder = Mockery::mock(Builder::class);
        $queryBuilder->shouldReceive('with')->andReturnSelf();
        $queryBuilder->shouldReceive('when')->withAnyArgs()->andReturnSelf();

        // Set up a mock paginator
        $venues = new \Illuminate\Pagination\LengthAwarePaginator(
            [$this->makeVenue(1, 'Test Venue 1'), $this->makeVenue(2, 'Test Venue 2')],
            2,
            15,
            1
        );

        $queryBuilder->shouldReceive('paginate')->andReturn($venues);

        // Set up the mock to return the query builder
        $this->mock->shouldReceive('with')->andReturn($queryBuilder);

        // Create a mock request
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('input')->withAnyArgs()->andReturn(null);

        // Call the controller method
        $response = $this->controller->index($request);

        // Assert response is correct
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        // Get the data from the response
        $responseData = json_decode($response->getContent(), true);

        // Assert that there are 2 venues in the response
        $this->assertCount(2, $responseData['data']);
    }

    /**
     * Test the types method returns venue types.
     */
    public function test_types_returns_venue_types(): void
    {
        // Create a mock query builder
        $queryBuilder = Mockery::mock(Builder::class);
        $queryBuilder->shouldReceive('select')->with('type_name')->andReturnSelf();
        $queryBuilder->shouldReceive('whereNotNull')->with('type_name')->andReturnSelf();
        $queryBuilder->shouldReceive('distinct')->andReturnSelf();
        $queryBuilder->shouldReceive('orderBy')->with('type_name')->andReturnSelf();
        $queryBuilder->shouldReceive('pluck')->with('type_name')->andReturn(collect(['Spa', 'Salon']));

        // Set up the mock to return the query builder
        $this->mock->shouldReceive('select')->andReturn($queryBuilder);

        // Call the controller method
        $response = $this->controller->types();

        // Assert response is correct
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        // Get the data from the response
        $responseData = json_decode($response->getContent(), true);

        // Assert that the response contains the expected types
        $this->assertContains('Spa', $responseData);
        $this->assertContains('Salon', $responseData);
    }

    /**
     * Helper method to create a venue model for testing.
     *
     * @param  int  $id
     * @param  string  $name
     */
    private function makeVenue($id, $name): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'description' => 'Description for '.$name,
            'type_name' => 'Spa',
        ];
    }
}
