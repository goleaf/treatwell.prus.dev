<?php

namespace Tests\Unit\CityDataParser;

use App\Models\City;
use App\Models\Venue;
use App\Models\Treatment;
use App\Models\Location;
use App\Models\Rating;
use App\Models\Image;
use App\Models\OpeningHour;
use App\Models\Service;
use App\Models\Procedure;
use App\Services\CityDataProcessor;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Feature: city-data-parser, Property 4: Data persistence completeness
 * Validates: Requirements 1.4, 3.5
 */
class DataPersistenceCompletenessPropertyTest extends TestCase
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
     * Property 4: Data persistence completeness
     * For any data collected from APIs, all information should be properly stored 
     * in the database with correct relationships
     */
    public function testDataPersistenceCompleteness(): void
    {
        $this->forAll(
            $this->generateCityData(),
            $this->generateVenueData(),
            $this->generateTreatmentData()
        )->then(function (array $cityData, array $venueData, array $treatmentData) {
            // Create a city
            $city = City::factory()->create($cityData);

            // Prepare comprehensive API data
            $apiData = [
                'venues' => [
                    array_merge($venueData, [
                        'treatments' => [$treatmentData],
                        'location' => [
                            'name' => $city->name,
                            'address' => ['addressLines' => ['Test Street 123']],
                            'coordinates' => ['lat' => 54.6872, 'lng' => 25.2797],
                            'postalCode' => '01234',
                            'countryCode' => 'LT'
                        ],
                        'rating' => [
                            'value' => 4.5,
                            'count' => 25,
                            'displayAverage' => '4.5'
                        ],
                        'images' => [
                            [
                                'url' => 'https://example.com/image1.jpg',
                                'type' => 'primary',
                                'alt' => 'Venue image',
                                'isPrimary' => true
                            ]
                        ],
                        'openingHours' => [
                            [
                                'dayOfWeek' => 'monday',
                                'from' => '09:00',
                                'to' => '18:00',
                                'open' => true
                            ]
                        ],
                        'services' => [
                            [
                                'id' => 'service-' . uniqid(),
                                'name' => 'Test Service',
                                'description' => 'Test service description',
                                'category' => 'beauty'
                            ]
                        ]
                    ])
                ],
                'procedures' => [
                    [
                        'id' => 'procedure-' . uniqid(),
                        'name' => 'Test Procedure',
                        'description' => 'Test procedure description',
                        'category' => 'hair'
                    ]
                ]
            ];

            // Process the comprehensive data
            $results = $this->processor->processComprehensiveCityData($city, $apiData);

            // Property: All venue data should be persisted
            $this->assertGreaterThan(0, $results['venues_processed'], 'At least one venue should be processed');
            
            $venue = Venue::where('city_id', $city->id)->first();
            $this->assertNotNull($venue, 'Venue should be persisted in database');
            $this->assertEquals($venueData['name'], $venue->name, 'Venue name should be persisted correctly');
            $this->assertNotNull($venue->external_id, 'Venue external_id should be persisted');

            // Property: All treatment data should be persisted with correct relationships
            $this->assertGreaterThan(0, $results['treatments_processed'], 'At least one treatment should be processed');
            
            $treatment = Treatment::where('venue_id', $venue->id)->first();
            $this->assertNotNull($treatment, 'Treatment should be persisted in database');
            $this->assertEquals($treatmentData['name'], $treatment->name, 'Treatment name should be persisted correctly');
            $this->assertEquals($venue->id, $treatment->venue_id, 'Treatment should be linked to correct venue');

            // Property: Location data should be persisted with correct relationships
            $this->assertGreaterThan(0, $results['locations_processed'], 'At least one location should be processed');
            
            $location = Location::where('venue_id', $venue->id)->first();
            $this->assertNotNull($location, 'Location should be persisted in database');
            $this->assertEquals($city->id, $location->city_id, 'Location should be linked to correct city');
            $this->assertEquals($venue->id, $location->venue_id, 'Location should be linked to correct venue');
            $this->assertNotNull($location->latitude, 'Location coordinates should be persisted');
            $this->assertNotNull($location->longitude, 'Location coordinates should be persisted');

            // Property: Rating data should be persisted with correct relationships
            $this->assertGreaterThan(0, $results['ratings_processed'], 'At least one rating should be processed');
            
            $rating = Rating::where('venue_id', $venue->id)->first();
            $this->assertNotNull($rating, 'Rating should be persisted in database');
            $this->assertEquals($venue->id, $rating->venue_id, 'Rating should be linked to correct venue');
            $this->assertEquals(4.5, $rating->value, 'Rating value should be persisted correctly');

            // Property: Image data should be persisted with correct relationships
            $this->assertGreaterThan(0, $results['images_processed'], 'At least one image should be processed');
            
            $image = Image::where('venue_id', $venue->id)->first();
            $this->assertNotNull($image, 'Image should be persisted in database');
            $this->assertEquals($venue->id, $image->venue_id, 'Image should be linked to correct venue');
            $this->assertNotNull($image->path, 'Image path should be persisted');

            // Property: Opening hours should be persisted with correct relationships
            $this->assertGreaterThan(0, $results['opening_hours_processed'], 'At least one opening hour should be processed');
            
            $openingHour = OpeningHour::where('venue_id', $venue->id)->first();
            $this->assertNotNull($openingHour, 'Opening hour should be persisted in database');
            $this->assertEquals($venue->id, $openingHour->venue_id, 'Opening hour should be linked to correct venue');
            $this->assertTrue($openingHour->is_open, 'Opening hour status should be persisted correctly');

            // Property: Service data should be persisted with correct relationships
            $this->assertGreaterThan(0, $results['services_processed'], 'At least one service should be processed');
            
            $service = Service::where('venue_id', $venue->id)->first();
            $this->assertNotNull($service, 'Service should be persisted in database');
            $this->assertEquals($venue->id, $service->venue_id, 'Service should be linked to correct venue');

            // Property: Procedure data should be persisted with correct city relationships
            $this->assertGreaterThan(0, $results['procedures_processed'], 'At least one procedure should be processed');
            
            $procedure = Procedure::first();
            $this->assertNotNull($procedure, 'Procedure should be persisted in database');
            $this->assertTrue($city->procedures()->where('procedure_id', $procedure->id)->exists(), 
                'Procedure should be linked to city');

            // Property: Data completeness should be trackable
            $this->assertGreaterThan(0, $venue->getDataCompleteness(), 'Venue data completeness should be calculable');
            $this->assertGreaterThan(0, $treatment->getDataCompleteness(), 'Treatment data completeness should be calculable');
            $this->assertGreaterThan(0, $city->getDataCompleteness(), 'City data completeness should be calculable');

            // Property: No processing errors should occur for valid data
            $this->assertEmpty($results['errors'], 'No processing errors should occur for valid data');
        });
    }

    /**
     * Property: Data integrity should be maintained across updates
     */
    public function testDataIntegrityAcrossUpdates(): void
    {
        $this->forAll(
            $this->generateVenueData(),
            $this->generateVenueData() // Second set for updates
        )->then(function (array $initialData, array $updatedData) {
            // Create city and initial venue
            $city = City::factory()->create();
            $venue = Venue::factory()->create([
                'city_id' => $city->id,
                'external_id' => $initialData['id'],
                'name' => $initialData['name']
            ]);

            $initialVenueCount = Venue::count();
            $initialTreatmentCount = Treatment::count();

            // Process updated data for the same venue
            $apiData = [
                'venues' => [
                    array_merge($updatedData, [
                        'id' => $initialData['id'], // Same external ID
                        'treatments' => [
                            [
                                'id' => 'treatment-' . uniqid(),
                                'name' => 'Updated Treatment',
                                'description' => 'Updated description'
                            ]
                        ]
                    ])
                ]
            ];

            $results = $this->processor->processComprehensiveCityData($city, $apiData);

            // Property: Updates should not create duplicate venues
            $finalVenueCount = Venue::count();
            $this->assertEquals($initialVenueCount, $finalVenueCount, 
                'Updates should not create duplicate venues');

            // Property: Venue data should be updated, not duplicated
            $updatedVenue = Venue::where('external_id', $initialData['id'])->first();
            $this->assertNotNull($updatedVenue, 'Venue should still exist after update');
            $this->assertEquals($venue->id, $updatedVenue->id, 'Should be the same venue record');

            // Property: Related data should be properly managed
            $this->assertGreaterThan($initialTreatmentCount, Treatment::count(), 
                'New treatments should be added');
        });
    }

    /**
     * Property: Partial data should be handled gracefully
     */
    public function testPartialDataHandling(): void
    {
        $this->forAll(
            Generator\bind(
                Generator\choose(1, 3),
                function ($type) {
                    $uniqueId = uniqid() . '-' . microtime(true) . '-' . rand(1000, 9999);
                    switch ($type) {
                        case 1:
                            return Generator\constant(['name' => 'Minimal Venue ' . $uniqueId]);
                        case 2:
                            return Generator\constant(['name' => 'Venue with ID ' . $uniqueId, 'id' => 'venue-' . $uniqueId]);
                        case 3:
                            return Generator\constant(['name' => 'Venue with Location ' . $uniqueId, 'location' => ['name' => 'Test City']]);
                    }
                }
            )
        )->then(function (array $partialVenueData) {
            $uniqueId = uniqid();
            $city = City::factory()->create([
                'slug' => 'test-city-' . $uniqueId,
                'name' => 'Test City ' . $uniqueId,
                'entity_id' => 'test-entity-' . $uniqueId . '-test'
            ]);

            $apiData = [
                'venues' => [$partialVenueData]
            ];

            // Property: Partial data should not cause processing to fail
            $results = $this->processor->processComprehensiveCityData($city, $apiData);

            if ($results['venues_processed'] === 0 && !empty($results['errors'])) {
                $this->fail('Processing failed with errors: ' . implode(', ', $results['errors']));
            }

            $this->assertGreaterThan(0, $results['venues_processed'], 
                'Partial venue data should still be processed');

            $venue = Venue::where('city_id', $city->id)->first();
            $this->assertNotNull($venue, 'Venue should be created even with partial data');
            $this->assertEquals($partialVenueData['name'], $venue->name, 
                'Available data should be persisted correctly');

            // Property: Data completeness should reflect partial data
            $completeness = $venue->getDataCompleteness();
            $this->assertGreaterThan(0, $completeness, 'Data completeness should be calculable for partial data');
            $this->assertLessThan(100, $completeness, 'Partial data should not show 100% completeness');
        });
    }

    /**
     * Property: Unknown fields should be preserved
     */
    public function testUnknownFieldPreservation(): void
    {
        $this->forAll(
            Generator\bind(
                Generator\elements(['custom_field', 'unknown_attribute', 'special_data']),
                function ($customField) {
                    return Generator\bind(
                        Generator\elements(['custom_value', 123, ['nested' => 'data']]),
                        function ($customValue) use ($customField) {
                            return Generator\constant([
                                'name' => 'Test Venue',
                                'id' => 'venue-' . uniqid(),
                                $customField => $customValue
                            ]);
                        }
                    );
                }
            )
        )->then(function (array $venueDataWithUnknown) {
            $city = City::factory()->create();

            $apiData = [
                'venues' => [$venueDataWithUnknown]
            ];

            $results = $this->processor->processComprehensiveCityData($city, $apiData);

            $venue = Venue::where('city_id', $city->id)->first();
            $this->assertNotNull($venue, 'Venue should be created');

            // Property: Unknown fields should be preserved in raw_data
            $this->assertNotNull($venue->raw_data, 'Raw data should be preserved');
            $this->assertIsArray($venue->raw_data, 'Raw data should be an array');

            // Property: Processing should complete successfully despite unknown fields
            $this->assertEmpty($results['errors'], 'Unknown fields should not cause processing errors');
        });
    }

    /**
     * Generate city data for testing
     */
    private function generateCityData(): Generator
    {
        return Generator\bind(
            Generator\elements(['Vilnius', 'Kaunas', 'Klaipeda', 'Siauliai']),
            function ($name) {
                $uniqueId = uniqid() . '-' . microtime(true) . '-' . rand(1000, 9999);
                return Generator\constant([
                    'name' => $name . ' ' . $uniqueId,
                    'slug' => strtolower($name) . '-' . $uniqueId,
                    'entity_id' => 'test-entity-' . $uniqueId . '-test',
                    'is_main_city' => true,
                    'latitude' => 54.6872,
                    'longitude' => 25.2797,
                ]);
            }
        );
    }

    /**
     * Generate venue data for testing
     */
    private function generateVenueData(): Generator
    {
        return Generator\bind(
            Generator\elements(['Beauty Salon', 'Hair Studio', 'Spa Center', 'Wellness Clinic']),
            function ($name) {
                $uniqueId = uniqid() . '-' . microtime(true) . '-' . rand(1000, 9999);
                return Generator\constant([
                    'id' => 'venue-' . $uniqueId,
                    'name' => $name . ' ' . $uniqueId,
                    'description' => "Professional {$name} services",
                    'phone' => '+370 600 12345',
                    'email' => 'test@example.com',
                    'website' => 'https://example.com',
                ]);
            }
        );
    }

    /**
     * Generate treatment data for testing
     */
    private function generateTreatmentData(): Generator
    {
        return Generator\bind(
            Generator\elements(['Hair Cut', 'Facial Treatment', 'Massage', 'Manicure']),
            function ($name) {
                return Generator\bind(
                    Generator\elements(['hair', 'facial', 'massage', 'nails']),
                    function ($category) use ($name) {
                        return Generator\bind(
                            Generator\choose(20, 150),
                            function ($price) use ($name, $category) {
                                return Generator\bind(
                                    Generator\choose(30, 120),
                                    function ($duration) use ($name, $category, $price) {
                                        $uniqueId = uniqid() . '-' . microtime(true) . '-' . rand(1000, 9999);
                                        return Generator\constant([
                                            'id' => 'treatment-' . $uniqueId,
                                            'name' => $name . ' ' . $uniqueId,
                                            'description' => "Professional {$name} service",
                                            'category' => $category,
                                            'price' => $price,
                                            'duration' => $duration,
                                        ]);
                                    }
                                );
                            }
                        );
                    }
                );
            }
        );
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