<?php

namespace Tests\Unit\CityDataParser;

use App\Models\City;
use App\Models\Venue;
use App\Services\CityDataProcessor;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Feature: city-data-parser, Property 3: Graceful error handling
 * Validates: Requirements 1.3, 3.4
 */
class GracefulErrorHandlingPropertyTest extends TestCase
{
    use DatabaseTransactions, TestTrait;

    private CityDataProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->processor = $this->app->make(CityDataProcessor::class);
        
        // Initialize Eris
        $this->erisSetUp();
    }

    /**
     * Property 3: Graceful error handling
     * For any API failure or rate limiting scenario, the parser should continue 
     * processing other cities and log appropriate error information
     */
    public function testGracefulErrorHandling(): void
    {
        $this->forAll(
            $this->generateMixedQualityData()
        )->then(function (array $mixedData) {
            $uniqueId = uniqid();
            $city = City::factory()->create([
                'slug' => 'test-city-' . $uniqueId,
                'name' => 'Test City ' . $uniqueId,
                'entity_id' => 'entity-' . $uniqueId
            ]);

            // Process mixed quality data (some valid, some invalid)
            $results = $this->processor->processComprehensiveCityData($city, $mixedData);

            // Property: Processing should complete without throwing exceptions
            $this->assertIsArray($results, 'Processing should return results array');
            $this->assertArrayHasKey('errors', $results, 'Results should include errors array');
            $this->assertArrayHasKey('warnings', $results, 'Results should include warnings array');

            // Property: Valid data should be processed despite invalid data presence
            if ($this->hasValidVenues($mixedData)) {
                $this->assertGreaterThan(0, $results['venues_processed'], 
                    'Valid venues should be processed despite presence of invalid data');
            }

            // Property: Invalid data should generate warnings/errors but not stop processing
            if ($this->hasInvalidVenues($mixedData)) {
                $this->assertGreaterThan(0, count($results['errors']) + count($results['warnings']), 
                    'Invalid data should generate errors or warnings');
            }

            // Property: Database should remain in consistent state
            $venuesInDb = Venue::where('city_id', $city->id)->count();
            $this->assertEquals($results['venues_processed'], $venuesInDb, 
                'Database venue count should match processed count');
        });
    }

    /**
     * Property: Empty or malformed data should be handled gracefully
     */
    public function testEmptyDataHandling(): void
    {
        $this->forAll(
            Generator\elements([
                [], // Empty data
                ['venues' => []], // Empty venues array
                ['venues' => null], // Null venues
                ['invalid_key' => 'invalid_value'], // Invalid structure
                ['venues' => [null, [], ['invalid' => 'data']]], // Mixed invalid venues
            ])
        )->then(function (array $emptyOrInvalidData) {
            $uniqueId = uniqid();
            $city = City::factory()->create([
                'slug' => 'test-city-' . $uniqueId,
                'name' => 'Test City ' . $uniqueId,
                'entity_id' => 'entity-' . $uniqueId
            ]);

            // Property: Empty/invalid data should not cause exceptions
            $results = $this->processor->processComprehensiveCityData($city, $emptyOrInvalidData);

            $this->assertIsArray($results, 'Processing should return results array for empty data');
            $this->assertEquals(0, $results['venues_processed'], 'No venues should be processed from empty data');
            
            // Property: Warnings should be generated for invalid data structure
            if (!empty($emptyOrInvalidData) && isset($emptyOrInvalidData['venues']) && $emptyOrInvalidData['venues'] !== []) {
                $this->assertGreaterThan(0, count($results['warnings']), 
                    'Warnings should be generated for invalid data structure');
            }
        });
    }

    /**
     * Property: Partial failures should not prevent other data from being processed
     */
    public function testPartialFailureResilience(): void
    {
        $this->forAll(
            Generator\choose(2, 5) // Number of venues to test
        )->then(function (int $venueCount) {
            $uniqueId = uniqid();
            $city = City::factory()->create([
                'slug' => 'test-city-' . $uniqueId,
                'name' => 'Test City ' . $uniqueId,
                'entity_id' => 'entity-' . $uniqueId
            ]);

            // Create mixed data: some valid venues, some with issues
            $venues = [];
            $expectedValidVenues = 0;
            
            for ($i = 0; $i < $venueCount; $i++) {
                if ($i % 2 === 0) {
                    // Valid venue
                    $venues[] = [
                        'id' => 'venue-' . uniqid(),
                        'name' => 'Valid Venue ' . $i,
                        'description' => 'Valid description',
                        'phone' => '+370 600 12345'
                    ];
                    $expectedValidVenues++;
                } else {
                    // Invalid venue (missing required name)
                    $venues[] = [
                        'id' => 'venue-' . uniqid(),
                        'description' => 'Missing name',
                        'invalid_field' => 'invalid_value'
                    ];
                }
            }

            $apiData = ['venues' => $venues];
            $results = $this->processor->processComprehensiveCityData($city, $apiData);

            // Property: Valid venues should be processed despite invalid ones
            $this->assertEquals($expectedValidVenues, $results['venues_processed'], 
                'Valid venues should be processed despite presence of invalid venues');

            // Property: Errors/warnings should be logged for invalid venues
            $this->assertGreaterThan(0, count($results['errors']) + count($results['warnings']), 
                'Errors or warnings should be generated for invalid venues');

            // Property: Database should contain only valid venues
            $venuesInDb = Venue::where('city_id', $city->id)->count();
            $this->assertEquals($expectedValidVenues, $venuesInDb, 
                'Database should contain only successfully processed venues');
        });
    }

    /**
     * Property: Data validation errors should be logged appropriately
     */
    public function testDataValidationErrorLogging(): void
    {
        $this->forAll(
            Generator\elements([
                // Venue with invalid nested data
                [
                    'name' => 'Venue with Invalid Data',
                    'treatments' => [
                        ['name' => 'Valid Treatment'],
                        ['invalid' => 'no name'], // Invalid treatment
                        null, // Null treatment
                    ],
                    'images' => [
                        ['url' => 'https://example.com/image.jpg'],
                        ['invalid' => 'no url'], // Invalid image
                    ],
                    'openingHours' => [
                        ['dayOfWeek' => 'monday', 'from' => '09:00', 'to' => '17:00'],
                        ['invalid' => 'no day'], // Invalid opening hour
                    ]
                ]
            ])
        )->then(function (array $venueWithInvalidNested) {
            $uniqueId = uniqid();
            $city = City::factory()->create([
                'slug' => 'test-city-' . $uniqueId,
                'name' => 'Test City ' . $uniqueId,
                'entity_id' => 'entity-' . $uniqueId
            ]);

            $apiData = ['venues' => [$venueWithInvalidNested]];
            $results = $this->processor->processComprehensiveCityData($city, $apiData);

            // Property: Venue should be processed despite invalid nested data
            $this->assertEquals(1, $results['venues_processed'], 
                'Venue should be processed despite invalid nested data');

            // Property: Valid nested data should be processed
            $this->assertGreaterThan(0, $results['treatments_processed'], 
                'Valid treatments should be processed');

            // Property: Warnings should be generated for invalid nested data
            $this->assertGreaterThan(0, count($results['warnings']), 
                'Warnings should be generated for invalid nested data');

            // Property: Processing should continue with valid data
            $venue = Venue::where('city_id', $city->id)->first();
            $this->assertNotNull($venue, 'Venue should be created');
            $this->assertEquals($venueWithInvalidNested['name'], $venue->name, 
                'Venue name should be correctly stored');
        });
    }

    /**
     * Property: System should handle unexpected data types gracefully
     */
    public function testUnexpectedDataTypeHandling(): void
    {
        $this->forAll(
            Generator\elements([
                // String instead of array
                ['venues' => 'not an array'],
                // Number instead of array
                ['venues' => 123],
                // Boolean instead of array
                ['venues' => true],
                // Nested unexpected types
                ['venues' => [
                    ['name' => 'Valid Venue'],
                    'string instead of object',
                    123,
                    true,
                    ['name' => ['nested' => 'array instead of string']]
                ]]
            ])
        )->then(function (array $unexpectedTypeData) {
            $uniqueId = uniqid();
            $city = City::factory()->create([
                'slug' => 'test-city-' . $uniqueId,
                'name' => 'Test City ' . $uniqueId,
                'entity_id' => 'entity-' . $uniqueId
            ]);

            // Property: Unexpected data types should not cause exceptions
            $results = $this->processor->processComprehensiveCityData($city, $unexpectedTypeData);

            $this->assertIsArray($results, 'Processing should return results array for unexpected data types');
            
            // Property: Warnings should be generated for unexpected data types
            $this->assertGreaterThan(0, count($results['warnings']), 
                'Warnings should be generated for unexpected data types');

            // Property: System should continue processing valid data if any exists
            if (isset($unexpectedTypeData['venues']) && is_array($unexpectedTypeData['venues'])) {
                $validVenues = array_filter($unexpectedTypeData['venues'], function($venue) {
                    return is_array($venue) && !empty($venue['name']) && is_string($venue['name']);
                });
                
                $this->assertEquals(count($validVenues), $results['venues_processed'], 
                    'Only valid venues should be processed from mixed data types');
            }
        });
    }

    /**
     * Generate mixed quality data for testing
     */
    private function generateMixedQualityData(): Generator
    {
        return Generator\bind(
            Generator\choose(1, 4), // Number of venues
            function ($venueCount) {
                return Generator\bind(
                    Generator\choose(0, $venueCount), // Number of valid venues
                    function ($validCount) use ($venueCount) {
                        $venues = [];
                        
                        // Add valid venues
                        for ($i = 0; $i < $validCount; $i++) {
                            $venues[] = [
                                'id' => 'venue-' . uniqid(),
                                'name' => 'Valid Venue ' . $i,
                                'description' => 'Valid description',
                                'phone' => '+370 600 12345'
                            ];
                        }
                        
                        // Add invalid venues
                        for ($i = $validCount; $i < $venueCount; $i++) {
                            $venues[] = [
                                'id' => 'venue-' . uniqid(),
                                // Missing name - invalid
                                'description' => 'Invalid venue without name',
                                'invalid_field' => 'some value'
                            ];
                        }
                        
                        return Generator\constant(['venues' => $venues]);
                    }
                );
            }
        );
    }

    /**
     * Check if data contains valid venues
     */
    private function hasValidVenues(array $data): bool
    {
        if (!isset($data['venues']) || !is_array($data['venues'])) {
            return false;
        }

        foreach ($data['venues'] as $venue) {
            if (is_array($venue) && !empty($venue['name'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if data contains invalid venues
     */
    private function hasInvalidVenues(array $data): bool
    {
        if (!isset($data['venues']) || !is_array($data['venues'])) {
            return true; // Invalid structure
        }

        foreach ($data['venues'] as $venue) {
            if (!is_array($venue) || empty($venue['name'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Configure Eris to run sufficient iterations
     */
    protected function erisSetUp(): void
    {
        $this->iterations = 100;
        $this->seed = rand(0, 2**31 - 1);
        $this->randRange = \Eris\Random\purePhpMtRand();
    }
}