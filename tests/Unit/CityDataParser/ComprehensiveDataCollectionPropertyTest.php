<?php

namespace Tests\Unit\CityDataParser;

use App\Services\CityDataProcessor;
use App\Services\ApiResponse;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: city-data-parser, Property 2: Comprehensive data collection
 * Validates: Requirements 1.2, 4.1, 4.2, 4.3, 4.4, 4.5
 */
class ComprehensiveDataCollectionPropertyTest extends TestCase
{
    use RefreshDatabase, TestTrait;

    private CityDataProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new CityDataProcessor();
        
        // Initialize Eris
        $this->erisSetUp();
    }

    /**
     * Property 2: Comprehensive data collection
     * For any city processed by the parser, all expected data types 
     * (venues, treatments, services, ratings, locations) should be collected and stored
     */
    public function testComprehensiveDataCollection(): void
    {
        $this->forAll(
            Generator\choose(1, 3),
            Generator\bool()
        )->then(function (int $venueCount, bool $hasResults) {
            // Generate API response based on parameters
            $venues = [];
            for ($i = 0; $i < $venueCount; $i++) {
                $venues[] = [
                    'id' => 'venue-' . $i,
                    'name' => 'Test Venue ' . $i,
                    'description' => 'Test venue description',
                    'url' => '/venue/venue-' . $i,
                    'phone' => '+370 600 1234' . $i,
                    'email' => 'test' . $i . '@venue.com',
                    'website' => 'https://venue' . $i . '.com'
                ];
            }
            
            if ($hasResults) {
                $apiResponse = [
                    'results' => array_map(function ($venue) {
                        return [
                            'type' => 'venue',
                            'data' => $venue
                        ];
                    }, $venues),
                    'pagination' => [
                        'currentPage' => 0,
                        'totalPages' => 1
                    ]
                ];
            } else {
                $apiResponse = [
                    'venues' => $venues
                ];
            }
            // Process the API response
            $transformedData = $this->processor->transformApiResponse($apiResponse);

            // Property: All expected data types should be present in the transformed data
            $expectedDataTypes = ['venues', 'treatments', 'locations', 'ratings', 'images', 'opening_hours'];
            
            foreach ($expectedDataTypes as $dataType) {
                $this->assertArrayHasKey($dataType, $transformedData, 
                    "Transformed data should contain '{$dataType}' array");
                $this->assertIsArray($transformedData[$dataType], 
                    "'{$dataType}' should be an array");
            }

            // Property: If venues exist in input, they should be processed
            if (isset($apiResponse['results'])) {
                $venueResults = array_filter($apiResponse['results'], fn($r) => $r['type'] === 'venue');
                if (!empty($venueResults)) {
                    $this->assertNotEmpty($transformedData['venues'], 
                        'Venues should be extracted when present in API response');
                    
                    // Property: Each venue should have required fields
                    foreach ($transformedData['venues'] as $venue) {
                        $this->assertArrayHasKey('name', $venue, 'Each venue should have a name');
                        $this->assertNotEmpty($venue['name'], 'Venue name should not be empty');
                    }
                }
            }

            // Property: Related data should be linked to venues
            if (!empty($transformedData['venues'])) {
                $venueCount = count($transformedData['venues']);
                
                // If locations exist, they should be related to venues
                if (!empty($transformedData['locations'])) {
                    $this->assertLessThanOrEqual($venueCount, count($transformedData['locations']),
                        'Location count should not exceed venue count');
                }
                
                // If ratings exist, they should be related to venues
                if (!empty($transformedData['ratings'])) {
                    $this->assertLessThanOrEqual($venueCount, count($transformedData['ratings']),
                        'Rating count should not exceed venue count');
                }
            }

            // Property: Metadata should be included
            $this->assertArrayHasKey('metadata', $transformedData, 'Metadata should be included');
            $this->assertArrayHasKey('processed_at', $transformedData['metadata'], 
                'Processing timestamp should be included');
            $this->assertArrayHasKey('source', $transformedData['metadata'], 
                'Data source should be specified');
        });
    }

    /**
     * Property: Venue data processing should extract all available fields
     */
    public function testVenueDataFieldExtraction(): void
    {
        $this->forAll(
            $this->generateVenueData()
        )->then(function (array $venueData) {
            $processedVenue = $this->processor->processVenueData($venueData);
            $venueArray = $processedVenue->toArray();

            // Property: Core venue fields should be extracted
            $coreFields = ['external_id', 'name', 'description', 'url', 'phone', 'email', 'website'];
            foreach ($coreFields as $field) {
                $this->assertArrayHasKey($field, $venueArray, "Venue should have '{$field}' field");
            }

            // Property: If name exists in input, it should be preserved
            if (isset($venueData['name'])) {
                $this->assertEquals($venueData['name'], $venueArray['name'], 
                    'Venue name should be preserved from input');
            }

            // Property: If location data exists, it should be processed
            if (isset($venueData['location'])) {
                $locationData = $processedVenue->getLocationData();
                $this->assertNotNull($locationData, 'Location data should be processed when present');
                
                $locationArray = $locationData->toArray();
                $this->assertArrayHasKey('name', $locationArray, 'Location should have a name');
            }

            // Property: If rating data exists, it should be processed
            if (isset($venueData['rating']) || isset($venueData['ratings'])) {
                $ratingData = $processedVenue->getRatingData();
                $this->assertNotNull($ratingData, 'Rating data should be processed when present');
                
                $ratingArray = $ratingData->toArray();
                $this->assertArrayHasKey('value', $ratingArray, 'Rating should have a value');
            }

            // Property: Unknown fields should be preserved
            $this->assertArrayHasKey('unknown_fields', $venueArray, 
                'Unknown fields should be preserved');
            $this->assertIsArray($venueArray['unknown_fields'], 
                'Unknown fields should be an array');
        });
    }

    /**
     * Property: Treatment data processing should handle various formats
     */
    public function testTreatmentDataProcessing(): void
    {
        $this->forAll(
            $this->generateTreatmentData()
        )->then(function (array $treatmentData) {
            $processedTreatment = $this->processor->processTreatmentData($treatmentData);
            $treatmentArray = $processedTreatment->toArray();

            // Property: Core treatment fields should be extracted
            $coreFields = ['external_id', 'name', 'description', 'category', 'duration', 'price'];
            foreach ($coreFields as $field) {
                $this->assertArrayHasKey($field, $treatmentArray, "Treatment should have '{$field}' field");
            }

            // Property: If name exists in input, it should be preserved
            if (isset($treatmentData['name'])) {
                $this->assertEquals($treatmentData['name'], $treatmentArray['name'], 
                    'Treatment name should be preserved from input');
            }

            // Property: Price should be numeric if present
            if ($treatmentArray['price'] !== null) {
                $this->assertIsNumeric($treatmentArray['price'], 
                    'Treatment price should be numeric when present');
                $this->assertGreaterThanOrEqual(0, $treatmentArray['price'], 
                    'Treatment price should not be negative');
            }

            // Property: Duration should be numeric if present
            if ($treatmentArray['duration'] !== null) {
                $this->assertIsNumeric($treatmentArray['duration'], 
                    'Treatment duration should be numeric when present');
                $this->assertGreaterThan(0, $treatmentArray['duration'], 
                    'Treatment duration should be positive');
            }
        });
    }

    /**
     * Property: Location data should preserve coordinate information
     */
    public function testLocationDataCoordinatePreservation(): void
    {
        $this->forAll(
            $this->generateLocationData()
        )->then(function (array $locationData) {
            $processedLocation = $this->processor->processLocationData($locationData);
            $locationArray = $processedLocation->toArray();

            // Property: Core location fields should be extracted
            $coreFields = ['external_id', 'name', 'address_line1', 'postal_code', 'latitude', 'longitude'];
            foreach ($coreFields as $field) {
                $this->assertArrayHasKey($field, $locationArray, "Location should have '{$field}' field");
            }

            // Property: Coordinates should be valid if present
            if ($locationArray['latitude'] !== null) {
                $this->assertIsNumeric($locationArray['latitude'], 'Latitude should be numeric');
                $this->assertGreaterThanOrEqual(-90, $locationArray['latitude'], 
                    'Latitude should be >= -90');
                $this->assertLessThanOrEqual(90, $locationArray['latitude'], 
                    'Latitude should be <= 90');
            }

            if ($locationArray['longitude'] !== null) {
                $this->assertIsNumeric($locationArray['longitude'], 'Longitude should be numeric');
                $this->assertGreaterThanOrEqual(-180, $locationArray['longitude'], 
                    'Longitude should be >= -180');
                $this->assertLessThanOrEqual(180, $locationArray['longitude'], 
                    'Longitude should be <= 180');
            }

            // Property: Address should be preserved if present
            if (isset($locationData['address'])) {
                $this->assertNotNull($locationArray['address_line1'], 
                    'Address should be preserved when present in input');
            }
        });
    }

    /**
     * Property: Data validation should catch invalid data
     */
    public function testDataValidationCatchesInvalidData(): void
    {
        $this->forAll(
            $this->generateInvalidData()
        )->then(function (array $invalidData) {
            $validationResult = $this->processor->validateDataIntegrity($invalidData);

            // Property: Invalid data should be detected
            if ($this->hasInvalidVenues($invalidData) || $this->hasInvalidTreatments($invalidData)) {
                $this->assertFalse($validationResult->isValid(), 
                    'Validation should fail for invalid data');
                $this->assertNotEmpty($validationResult->getErrors(), 
                    'Validation errors should be reported');
            }
        });
    }

    /**
     * Generate complete API response for testing
     */
    private function generateCompleteApiResponse(): Generator
    {
        return Generator\map(
            function ($venueCount, $hasResults) {
                $venues = [];
                for ($i = 0; $i < $venueCount; $i++) {
                    $venues[] = [
                        'id' => 'venue-' . $i,
                        'name' => 'Test Venue ' . $i,
                        'description' => 'Test venue description',
                        'url' => '/venue/venue-' . $i,
                        'phone' => '+370 600 1234' . $i,
                        'email' => 'test' . $i . '@venue.com',
                        'website' => 'https://venue' . $i . '.com'
                    ];
                }
                if ($hasResults) {
                    return [
                        'results' => array_map(function ($venue) {
                            return [
                                'type' => 'venue',
                                'data' => $venue
                            ];
                        }, $venues),
                        'pagination' => [
                            'currentPage' => 0,
                            'totalPages' => 1
                        ]
                    ];
                } else {
                    return [
                        'venues' => $venues
                    ];
                }
            },
            Generator\choose(1, 3),
            Generator\bool()
        );
    }

    /**
     * Generate venue data for testing
     */
    private function generateVenueData(): Generator
    {
        return Generator\map(
            function ($id, $name, $hasLocation, $hasRating, $hasImages, $hasOpeningHours) {
                $venue = [
                    'id' => $id,
                    'name' => $name,
                    'description' => 'Test venue description',
                    'url' => '/venue/' . $id,
                    'phone' => '+370 600 12345',
                    'email' => 'test@venue.com',
                    'website' => 'https://venue.com'
                ];

                if ($hasLocation) {
                    $venue['location'] = [
                        'id' => 'loc-' . $id,
                        'name' => 'Test City',
                        'address' => ['addressLines' => ['Test Street 123']],
                        'postalCode' => '12345',
                        'coordinates' => ['lat' => 54.6872, 'lng' => 25.2797]
                    ];
                }

                if ($hasRating) {
                    $venue['rating'] = [
                        'average' => 4.5,
                        'count' => 100
                    ];
                }

                if ($hasImages) {
                    $venue['images'] = [
                        [
                            'url' => 'https://example.com/image1.jpg',
                            'type' => 'primary'
                        ]
                    ];
                }

                if ($hasOpeningHours) {
                    $venue['openingHours'] = [
                        [
                            'dayOfWeek' => 'monday',
                            'from' => '09:00',
                            'to' => '18:00',
                            'open' => true
                        ]
                    ];
                }

                return $venue;
            },
            Generator\string()->withSize(Generator\choose(5, 10)),
            Generator\string()->withSize(Generator\choose(5, 20)),
            Generator\bool(),
            Generator\bool(),
            Generator\bool(),
            Generator\bool()
        );
    }

    /**
     * Generate treatment data for testing
     */
    private function generateTreatmentData(): Generator
    {
        return Generator\map(
            function ($id, $name, $price, $duration) {
                return [
                    'id' => $id,
                    'name' => $name,
                    'description' => 'Test treatment description',
                    'category' => 'hair',
                    'price' => $price,
                    'duration' => $duration
                ];
            },
            Generator\string()->withSize(Generator\choose(5, 10)),
            Generator\string()->withSize(Generator\choose(5, 20)),
            Generator\choose(10, 200),
            Generator\choose(15, 180)
        );
    }

    /**
     * Generate location data for testing
     */
    private function generateLocationData(): Generator
    {
        return Generator\map(
            function ($id, $name, $lat, $lng) {
                return [
                    'id' => $id,
                    'name' => $name,
                    'address' => ['addressLines' => ['Test Address 123']],
                    'postalCode' => '12345',
                    'coordinates' => ['lat' => $lat, 'lng' => $lng]
                ];
            },
            Generator\string()->withSize(Generator\choose(5, 10)),
            Generator\string()->withSize(Generator\choose(5, 20)),
            Generator\float()->between(-90, 90),
            Generator\float()->between(-180, 180)
        );
    }

    /**
     * Generate invalid data for testing validation
     */
    private function generateInvalidData(): Generator
    {
        return Generator\oneOf(
            // Invalid venues (missing name)
            Generator\constant([
                'venues' => [
                    ['external_id' => 'test-1', 'name' => ''], // empty name
                    ['external_id' => 'test-2'] // missing name
                ]
            ]),
            // Invalid treatments (missing name)
            Generator\constant([
                'treatments' => [
                    ['external_id' => 'treat-1', 'name' => ''], // empty name
                    ['external_id' => 'treat-2'] // missing name
                ]
            ]),
            // Inconsistent data (treatment references non-existent venue)
            Generator\constant([
                'venues' => [
                    ['external_id' => 'venue-1', 'name' => 'Test Venue']
                ],
                'treatments' => [
                    ['external_id' => 'treat-1', 'name' => 'Test Treatment', 'venue_id' => 'non-existent-venue']
                ]
            ])
        );
    }

    /**
     * Check if data has invalid venues
     */
    private function hasInvalidVenues(array $data): bool
    {
        if (!isset($data['venues'])) {
            return false;
        }

        foreach ($data['venues'] as $venue) {
            if (empty($venue['name']) || !isset($venue['name'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if data has invalid treatments
     */
    private function hasInvalidTreatments(array $data): bool
    {
        if (!isset($data['treatments'])) {
            return false;
        }

        foreach ($data['treatments'] as $treatment) {
            if (empty($treatment['name']) || !isset($treatment['name'])) {
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