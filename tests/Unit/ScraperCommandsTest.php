<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ScraperCommandsTest extends TestCase
{
    use RefreshDatabase;

    protected string $dynamicCitySlug = 'naujas-miestas-lt';

    /**
     * Mock the Treatwell API response with test data.
     */
    protected function mockApiResponse(): void
    {
        Http::fake(function (Request $request) {
            $query = $request->data();
            $currentBrowseUri = $query['currentBrowseUri'] ?? '';
            $page = (int) ($query['page'] ?? 1);

            if ($currentBrowseUri === '/salonai/kur-lietuva/') {
                return Http::response([
                    'pagination' => [
                        'page' => 1,
                        'totalPages' => 1,
                        'from' => 1,
                        'totalElements' => 2,
                    ],
                    'results' => [],
                    'filters' => [
                        'location' => [
                            'options' => [
                                ['normalisedName' => 'vilnius-lt', 'name' => 'Vilnius'],
                                ['normalisedName' => $this->dynamicCitySlug, 'name' => 'Naujas miestas'],
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
                ], 200);
            }

            $citySlug = trim(str_replace('/salonai/kur-', '', $currentBrowseUri), '/');

            return Http::response($this->cityResponse($citySlug, $page), 200);
        });
    }

    /**
     * Build a fake response for a specific city page.
     */
    protected function cityResponse(string $citySlug, int $page): array
    {
        $cityName = ucwords(str_replace('-', ' ', preg_replace('/-lt$/', '', $citySlug)));

        return [
            'pagination' => [
                'page' => $page,
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
                        'slug' => 'test-salon',
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
                                'id' => $citySlug,
                                'name' => $cityName,
                                'normalisedName' => $citySlug,
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
                                    $cityName,
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
        ];
    }

    /**
     * Test that the scrape:all-cities command executes successfully.
     *
     * @return void
     */
    public function test_scrape_all_cities_command()
    {
        $this->mockApiResponse();

        $this->artisan('scrape:all-cities')->assertExitCode(0);

        $recordedUris = Http::recorded()
            ->map(function ($interaction) {
                [$request] = $interaction;

                return $request->data()['currentBrowseUri'] ?? null;
            })
            ->filter();

        $this->assertContains(
            '/salonai/kur-'.$this->dynamicCitySlug.'/',
            $recordedUris,
            'The dynamic city request should be sent to the Treatwell API.'
        );

        $this->assertDatabaseHas('cities', [
            'entity_id' => $this->dynamicCitySlug,
        ]);
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

        config(['scraping.cities' => ['vilnius-lt', $this->dynamicCitySlug]]);

        $this->artisan('scrape:treatwell-all')->assertExitCode(0);
        // Verify data was saved
        $this->assertDatabaseHas('venues', [
            'external_id' => '123456',
            'name' => 'Test Salon',
        ]);

        $this->assertDatabaseHas('cities', [
            'entity_id' => 'vilnius-lt',
            'name' => 'Vilnius',
        ]);

        $venue = \App\Models\Venue::where('external_id', '123456')->first();

        $this->assertDatabaseHas('treatments', [
            'venue_id' => $venue->id,
            'name' => 'Swedish Massage',
            'min_price' => 45,
            'max_price' => 60,
        ]);

        $this->assertDatabaseHas('images', [
            'venue_id' => $venue->id,
            'external_id' => 'img123',
            'is_primary' => 1,
        ]);

        $this->assertDatabaseHas('opening_hours', [
            'venue_id' => $venue->id,
            'day_of_week' => 'Monday',
            'opening_time' => '09:00',
            'closing_time' => '20:00',
            'is_open' => 1,
        ]);

        $this->assertDatabaseHas('opening_hours', [
            'venue_id' => $venue->id,
            'day_of_week' => 'Sunday',
            'is_open' => 0,
        ]);

        $this->assertDatabaseHas('ratings', [
            'venue_id' => $venue->id,
            'weighted_average' => 4.8,
            'count' => 50,
        ]);
    }
}
