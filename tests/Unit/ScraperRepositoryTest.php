<?php

namespace Tests\Unit;

use App\Repositories\ScraperRepository;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class ScraperRepositoryTest extends TestCase
{
    protected $repository;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ScraperRepository();
        
        // Mock the Log facade
        Log::shouldReceive('error')->byDefault();
        Log::shouldReceive('info')->byDefault();
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    
    /**
     * Test that processResponse returns null when response is null.
     * 
     * @return void
     */
    public function test_process_response_returns_null_when_response_is_null()
    {
        Log::shouldReceive('error')
            ->once()
            ->with("No response received from URL: https://test.com");
            
        $result = $this->repository->processResponse(null, 'https://test.com');
        
        $this->assertNull($result, 'Process response should return null for null responses');
    }
    
    /**
     * Test that processResponse returns null when response is not successful.
     * 
     * @return void
     */
    public function test_process_response_returns_null_when_response_not_successful()
    {
        $response = Mockery::mock(Response::class);
        $response->shouldReceive('successful')->once()->andReturn(false);
        $response->shouldReceive('status')->once()->andReturn(500);
        
        Log::shouldReceive('error')
            ->once()
            ->with("API request failed with status: 500 for URL: https://test.com");
            
        $result = $this->repository->processResponse($response, 'https://test.com');
        
        $this->assertNull($result, 'Process response should return null for unsuccessful responses');
    }
    
    /**
     * Test that processResponse returns null when the results key is missing.
     * 
     * @return void
     */
    public function test_process_response_returns_null_when_missing_results()
    {
        $response = Mockery::mock(Response::class);
        $response->shouldReceive('successful')->once()->andReturn(true);
        $response->shouldReceive('json')->once()->andReturn(['data' => 'value']);
        
        Log::shouldReceive('error')
            ->once()
            ->with("API response missing 'results' key for URL: https://test.com");
            
        $result = $this->repository->processResponse($response, 'https://test.com');
        
        $this->assertNull($result, 'Process response should return null when results key is missing');
    }
    
    /**
     * Test that processResponse returns the data when the response is valid.
     * 
     * @return void
     */
    public function test_process_response_returns_data_when_valid()
    {
        $data = ['results' => [['id' => 1]]];
        
        $response = Mockery::mock(Response::class);
        $response->shouldReceive('successful')->once()->andReturn(true);
        $response->shouldReceive('json')->once()->andReturn($data);
        
        $result = $this->repository->processResponse($response, 'https://test.com');
        
        $this->assertEquals($data, $result, 'Process response should return valid data when response is successful');
    }
    
    /**
     * Test that extractVenueData correctly filters venue data from results.
     * 
     * @return void
     */
    public function test_extract_venue_data_filters_correctly()
    {
        $results = [
            ['type' => 'venue', 'data' => ['id' => 1, 'name' => 'Venue 1']],
            ['type' => 'other', 'data' => ['id' => 2]],
            ['type' => 'venue', 'data' => ['id' => 3, 'name' => 'Venue 3']],
            ['type' => 'venue'], // Missing data key
        ];
        
        $venueData = $this->repository->extractVenueData($results);
        
        $this->assertCount(2, $venueData, 'Extract venue data should return correct number of venues');
        $this->assertEquals(1, $venueData[0]['id'], 'First venue should have ID 1');
        $this->assertEquals(3, $venueData[1]['id'], 'Second venue should have ID 3');
    }
    
    /**
     * Test that logPaginationInfo correctly logs pagination information.
     * 
     * @return void
     */
    public function test_log_pagination_info_logs_correctly()
    {
        $data = [
            'pagination' => [
                'page' => 2,
                'totalPages' => 5,
                'from' => 11,
                'totalElements' => 50
            ],
            'results' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
        ];
        
        Log::shouldReceive('info')
            ->once()
            ->with("Pagination: Page 2 of 5, showing 11-20 of 50 venues for URL: https://test.com");
            
        $this->repository->logPaginationInfo($data, 'https://test.com');
    }
    
    /**
     * Test that logPaginationInfo handles missing pagination data gracefully.
     * 
     * @return void
     */
    public function test_log_pagination_info_handles_missing_pagination()
    {
        $data = ['results' => []];
        
        // No logging should happen
        Log::shouldReceive('info')->never();
            
        $this->repository->logPaginationInfo($data, 'https://test.com');
    }
} 