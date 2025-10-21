<?php

namespace Tests\Unit;

use App\Console\Commands\ScrapeAllCities;
use App\Console\Commands\ScrapeTreatwellAll;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use ReflectionClass;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class ScraperCommandsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.sqlite.database' => database_path('testing.sqlite')]);
        config(['database.default' => 'sqlite']);

        if (! file_exists(database_path('testing.sqlite'))) {
            touch(database_path('testing.sqlite'));
        }

        $this->artisan('migrate:fresh');
    }

    /**
     * Mock the Treatwell API response with test data.
     *
     * @return void
     */
    protected function mockApiResponse()
    {
        Http::fake([
            'https://www.treatwell.lt/api/v1/page/browse*' => Http::response([
                'pagination' => [
                    'page' => 1,
                    'totalPages' => 2,
                    'from' => 1,
                    'totalElements' => 30,
                ],
                'results' => [
                    [
                        'type' => 'venue',
                        'data' => [
                            'id' => '123456',
                            'name' => 'Test Salon',
                            'description' => 'A test salon description',
                            'type' => [
                                'id' => '1',
                                'name' => 'Spa',
                                'normalisedName' => 'spa',
                            ],
                            'uri' => [
                                'desktopUri' => '/salon/test-salon',
                                'mobileUri' => '/salon/test-salon',
                                'appUri' => '/salon/test-salon',
                            ],
                            'newVenue' => false,
                            'location' => [
                                'tree' => [
                                    'id' => 'vilnius-lt',
                                    'name' => 'Vilnius',
                                    'normalisedName' => 'vilnius-lt',
                                    'point' => [
                                        'lat' => 54.68716,
                                        'lon' => 25.27965,
                                    ],
                                    'type' => 'city',
                                    'radius' => [
                                        'distance' => 15,
                                        'distanceUnit' => 'km',
                                    ],
                                ],
                                'address' => [
                                    'postalCode' => 'LT-01234',
                                    'addressLines' => [
                                        'Gedimino pr. 1',
                                        'Vilnius',
                                    ],
                                ],
                                'point' => [
                                    'lat' => 54.68716,
                                    'lon' => 25.27965,
                                ],
                                'map' => [
                                    'zoom' => 15,
                                ],
                            ],
                            'rating' => [
                                'weightedAverage' => 4.8,
                                'count' => 50,
                                'displayAverage' => '4.8',
                                'dimensions' => [
                                    [
                                        'name' => 'Švara',
                                        'average' => 4.9,
                                        'count' => 48,
                                    ],
                                    [
                                        'name' => 'Personalas',
                                        'average' => 4.7,
                                        'count' => 50,
                                    ],
                                    [
                                        'name' => 'Atmosfera',
                                        'average' => 4.8,
                                        'count' => 45,
                                    ],
                                ],
                            ],
                            'openingHours' => [
                                [
                                    'dayOfWeek' => 'Monday',
                                    'from' => '09:00',
                                    'to' => '20:00',
                                    'open' => true,
                                ],
                                [
                                    'dayOfWeek' => 'Sunday',
                                    'open' => false,
                                ],
                            ],
                            'images' => [
                                [
                                    'id' => 'img123',
                                    'uris' => [
                                        '360x240' => 'https://cdn1.treatwell.net/images/view/v2.iimg123.w360.h240.xE2CA18A7/',
                                        '720x480' => 'https://cdn1.treatwell.net/images/view/v2.iimg123.w720.h480.xE2CA18A7/',
                                        '1080x720' => 'https://cdn1.treatwell.net/images/view/v2.iimg123.w1080.h720.xE2CA18A7/',
                                        '1280x800' => 'https://cdn1.treatwell.net/images/view/v2.iimg123.w1280.h800.xE2CA18A7/',
                                    ],
                                ],
                            ],
                            'primaryImage' => [
                                'id' => 'img123',
                                'uris' => [
                                    '360x240' => 'https://cdn1.treatwell.net/images/view/v2.iimg123.w360.h240.xE2CA18A7/',
                                    '720x480' => 'https://cdn1.treatwell.net/images/view/v2.iimg123.w720.h480.xE2CA18A7/',
                                    '1080x720' => 'https://cdn1.treatwell.net/images/view/v2.iimg123.w1080.h720.xE2CA18A7/',
                                    '1280x800' => 'https://cdn1.treatwell.net/images/view/v2.iimg123.w1280.h800.xE2CA18A7/',
                                ],
                            ],
                            'menuHighlights' => [
                                [
                                    'type' => 'treatment',
                                    'data' => [
                                        'id' => 't123',
                                        'name' => 'Swedish Massage',
                                        'priceRange' => [
                                            'minSalePriceAmount' => 45,
                                            'maxSalePriceAmount' => 60,
                                        ],
                                        'durationRange' => [
                                            'minDurationMinutes' => 60,
                                            'maxDurationMinutes' => 90,
                                        ],
                                        'primaryTreatmentCategoryId' => '1',
                                        'optionGroups' => [],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'filters' => [
                    'location' => [
                        'options' => [
                            ['normalisedName' => 'vilnius-lt', 'name' => 'Vilnius'],
                            ['normalisedName' => 'kaunas-lt', 'name' => 'Kaunas'],
                        ],
                    ],
                ],
                'locationBreadcrumbs' => [
                    [
                        'entityId' => 'vilnius-lt',
                        'uri' => [
                            'desktopUri' => '/salonai/kur-vilnius-lt/',
                        ],
                    ],
                ],
            ], 200),
        ]);
    }

    /**
     * Test that the scrape:all-cities command executes successfully.
     *
     * @return void
     */
    public function test_scrape_all_cities_command()
    {
        $this->mockApiResponse();

        $command = new class extends ScrapeAllCities
        {
            /**
             * @var array<int, string>
             */
            public array $calledCommands = [];

            public function call($command, array $arguments = [])
            {
                $this->calledCommands[] = $command;

                return 0;
            }
        };
        $command->setLaravel($this->app);

        $exitCode = $command->run(new ArrayInput([]), new BufferedOutput);

        $this->assertSame(0, $exitCode, 'The scrape:all-cities command should exit with code 0');

        $this->assertContains('scrape:treatwell-all', $command->calledCommands, 'The nested scrape command should be triggered for each city run.');
    }

    /**
     * Test that the scrape:treatwell-all command executes successfully and saves venue data.
     *
     * @return void
     */
    public function test_scrape_treatwell_all_command()
    {
        $this->mockApiResponse();

        // Create a country to avoid foreign key constraint errors
        $country = \App\Models\Country::factory()->create([
            'code' => 'LT',
            'name' => 'Lithuania',
        ]);

        $command = new ScrapeTreatwellAll;
        $command->setLaravel($this->app);

        $exitCode = $command->run(new ArrayInput([]), new BufferedOutput);

        $this->assertSame(0, $exitCode, 'The scrape:treatwell-all command should exit with code 0');

        // Verify data was saved
        $this->assertDatabaseHas('venues', [
            'external_id' => '123456',
            'name' => 'Test Salon',
        ], 'sqlite');

        $this->assertDatabaseHas('cities', [
            'entity_id' => 'vilnius-lt',
            'name' => 'Vilnius',
        ], 'sqlite');

        $venue = \App\Models\Venue::where('external_id', '123456')->first();

        $this->assertDatabaseHas('treatments', [
            'venue_id' => $venue->id,
            'name' => 'Swedish Massage',
        ], 'sqlite');

        $this->assertDatabaseHas('images', [
            'venue_id' => $venue->id,
            'external_id' => 'img123',
            'is_primary' => 1,
        ], 'sqlite');

        $this->assertDatabaseHas('opening_hours', [
            'venue_id' => $venue->id,
            'day_of_week' => 'Monday',
            'opening_time' => '09:00',
            'closing_time' => '20:00',
            'is_open' => 1,
        ], 'sqlite');

        $this->assertDatabaseHas('opening_hours', [
            'venue_id' => $venue->id,
            'day_of_week' => 'Sunday',
            'is_open' => 0,
        ], 'sqlite');

        $this->assertDatabaseHas('ratings', [
            'venue_id' => $venue->id,
            'weighted_average' => 4.8,
            'count' => 50,
        ], 'sqlite');
    }

    public function test_scrape_treatwell_all_visits_all_cities_without_throttling_in_testing_environment()
    {
        $this->mockApiResponse();

        $command = new class extends ScrapeTreatwellAll
        {
            public bool $sleptBetweenCities = false;

            protected function sleepBetweenCities(): void
            {
                $this->sleptBetweenCities = true;
            }
        };

        $command->setLaravel($this->app);

        $exitCode = $command->run(new ArrayInput([]), new BufferedOutput);

        $this->assertSame(0, $exitCode, 'The scrape:treatwell-all command should exit with code 0');

        $this->assertFalse($command->sleptBetweenCities, 'The command should not throttle between cities in the testing environment.');

        $reflection = new ReflectionClass(ScrapeTreatwellAll::class);
        $property = $reflection->getProperty('cities');
        $property->setAccessible(true);
        $cities = $property->getValue($command);

        foreach ($cities as $city) {
            Http::assertSent(function (Request $request) use ($city) {
                if (! str_starts_with($request->url(), 'https://www.treatwell.lt/api/v1/page/browse')) {
                    return false;
                }

                return ($request->data()['currentBrowseUri'] ?? null) === "/salonai/kur-{$city}/";
            });
        }
    }
}
