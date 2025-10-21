<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Image;
use App\Models\Location;
use App\Models\OpeningHour;
use App\Models\Rating;
use App\Models\Treatment;
use App\Models\Venue;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VenuesSyncCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTables();
        $this->resetData();
        Storage::disk('local')->delete('test-sync.json');
    }

    protected function tearDown(): void
    {
        Storage::disk('local')->delete('test-sync.json');
        parent::tearDown();
    }

    public function test_it_imports_venues_from_json_file(): void
    {
        $payload = [
            'results' => [
                [
                    'type' => 'venue',
                    'data' => [
                        'id' => 'json-1',
                        'name' => 'JSON Test Venue',
                        'description' => '<p>Great venue.</p>',
                        'type' => ['id' => 10, 'name' => 'Spa', 'normalisedName' => 'spa'],
                        'uri' => [
                            'desktopUri' => 'https://example.test/venues/json-test-venue/',
                        ],
                        'location' => [
                            'tree' => [
                                'id' => 101,
                                'name' => 'Vilnius',
                                'countryCode' => 'LT',
                                'normalisedName' => 'vilnius-lt',
                                'point' => ['lat' => 54.6872, 'lon' => 25.2797],
                            ],
                            'address' => [
                                'postalCode' => '01234',
                                'addressLines' => ['Gedimino pr. 1'],
                            ],
                        ],
                        'treatments' => [
                            [
                                'id' => 'treatment-1',
                                'name' => 'Massage',
                                'price' => 49.99,
                                'duration' => 60,
                            ],
                        ],
                        'openingHours' => [
                            ['dayOfWeek' => 'monday', 'from' => '09:00', 'to' => '18:00', 'open' => true],
                        ],
                    ],
                ],
            ],
        ];

        Storage::disk('local')->put('test-sync.json', json_encode($payload));

        $this->artisan('venues:sync --json=storage/app/test-sync.json')
            ->expectsOutputToContain('JSON import completed')
            ->assertExitCode(0);

        $this->assertDatabaseHas('venues', ['external_id' => 'json-1', 'name' => 'JSON Test Venue']);
        $this->assertDatabaseHas('cities', ['slug' => 'vilnius-lt']);
        $this->assertSame(1, Venue::count());
        $this->assertSame(1, Treatment::count());
        $this->assertSame(1, OpeningHour::count());
    }

    public function test_it_imports_from_api_with_pagination(): void
    {
        $response = [
            'pagination' => [
                'page' => 0,
                'totalPages' => 1,
                'totalElements' => 1,
            ],
            'results' => [
                [
                    'type' => 'venue',
                    'data' => [
                        'id' => 'api-1',
                        'name' => 'API Test Venue',
                        'type' => ['id' => 20, 'name' => 'Salon', 'normalisedName' => 'salon'],
                        'uri' => [
                            'desktopUri' => 'https://example.test/venues/api-test-venue/',
                        ],
                        'location' => [
                            'tree' => [
                                'id' => 201,
                                'name' => 'Kaunas',
                                'countryCode' => 'LT',
                                'normalisedName' => 'kaunas-lt',
                                'point' => ['lat' => 54.8985, 'lon' => 23.9036],
                            ],
                            'address' => [
                                'postalCode' => '50001',
                                'addressLines' => ['Laisvės al. 1'],
                            ],
                        ],
                        'rating' => [
                            'weightedAverage' => 4.8,
                            'count' => 125,
                        ],
                        'menuHighlights' => [
                            [
                                'type' => 'treatment',
                                'data' => [
                                    'id' => 'api-treatment',
                                    'name' => 'Haircut',
                                    'priceRange' => [
                                        'minSalePriceAmount' => '15.00',
                                        'maxSalePriceAmount' => '25.00',
                                    ],
                                    'durationRange' => [
                                        'minDurationMinutes' => 30,
                                        'maxDurationMinutes' => 60,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        Http::fake([
            'https://www.treatwell.lt/api/v1/page/browse*' => Http::response($response, 200),
        ]);

        $this->artisan('venues:sync --api --cities=kaunas-lt --max-pages=1 --page-size=5')
            ->expectsOutputToContain('API import completed')
            ->assertExitCode(0);

        $this->assertDatabaseHas('venues', ['external_id' => 'api-1', 'name' => 'API Test Venue']);
        $this->assertDatabaseHas('ratings', ['weighted_average' => 4.8]);
        $this->assertSame(1, Venue::where('external_id', 'api-1')->first()->treatments()->count());
    }

    private function resetData(): void
    {
        foreach (['city_treatment', 'city_procedure', 'city_venue', 'procedure_venue'] as $pivot) {
            if (Schema::hasTable($pivot)) {
                DB::table($pivot)->delete();
            }
        }

        OpeningHour::query()->delete();
        Image::query()->delete();
        Treatment::query()->delete();
        Rating::query()->delete();
        Location::query()->delete();
        Venue::query()->delete();
        City::query()->delete();
        DB::table('countries')->delete();
    }

    private function createTables(): void
    {
        if (! Schema::hasTable('countries')) {
            Schema::create('countries', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code', 3)->unique();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('cities')) {
            Schema::create('cities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('country_id')->nullable();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('normalised_name')->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->boolean('is_main_city')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('venues')) {
            Schema::create('venues', function (Blueprint $table) {
                $table->id();
                $table->string('external_id')->nullable()->index();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('type_id')->nullable();
                $table->string('type_name')->nullable();
                $table->string('normalised_name')->nullable();
                $table->string('desktop_uri')->nullable();
                $table->string('mobile_uri')->nullable();
                $table->string('app_uri')->nullable();
                $table->boolean('is_new_venue')->default(false);
                $table->json('raw_data')->nullable();
                $table->string('url')->nullable();
                $table->string('source')->nullable();
                $table->string('address')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->string('website')->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->unsignedBigInteger('location_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('locations')) {
            Schema::create('locations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('venue_id')->nullable();
                $table->foreignId('city_id')->nullable();
                $table->string('address_line1')->nullable();
                $table->string('address_line2')->nullable();
                $table->string('postal_code')->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->integer('map_zoom')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('images')) {
            Schema::create('images', function (Blueprint $table) {
                $table->id();
                $table->foreignId('venue_id');
                $table->string('external_id')->nullable();
                $table->string('uri_small')->nullable();
                $table->string('uri_medium')->nullable();
                $table->string('uri_large')->nullable();
                $table->string('uri_xlarge')->nullable();
                $table->boolean('is_primary')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('opening_hours')) {
            Schema::create('opening_hours', function (Blueprint $table) {
                $table->id();
                $table->foreignId('venue_id');
                $table->string('day_of_week');
                $table->time('opening_time')->nullable();
                $table->time('closing_time')->nullable();
                $table->boolean('is_open')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ratings')) {
            Schema::create('ratings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('venue_id');
                $table->decimal('weighted_average', 3, 2)->nullable();
                $table->integer('count')->default(0);
                $table->decimal('cleanliness_avg', 3, 2)->nullable();
                $table->integer('cleanliness_count')->default(0);
                $table->decimal('staff_avg', 3, 2)->nullable();
                $table->integer('staff_count')->default(0);
                $table->decimal('atmosphere_avg', 3, 2)->nullable();
                $table->integer('atmosphere_count')->default(0);
                $table->string('display_average')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('procedures')) {
            Schema::create('procedures', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('treatments')) {
            Schema::create('treatments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('venue_id');
                $table->foreignId('procedure_id')->nullable();
                $table->string('external_id')->nullable();
                $table->string('name');
                $table->string('slug')->nullable();
                $table->decimal('min_price', 10, 2)->nullable();
                $table->decimal('max_price', 10, 2)->nullable();
                $table->integer('min_duration')->nullable();
                $table->integer('max_duration')->nullable();
                $table->string('category_id')->nullable();
                $table->string('category_name')->nullable();
                $table->json('options')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('procedure_venue')) {
            Schema::create('procedure_venue', function (Blueprint $table) {
                $table->id();
                $table->foreignId('procedure_id');
                $table->foreignId('venue_id');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('city_venue')) {
            Schema::create('city_venue', function (Blueprint $table) {
                $table->id();
                $table->foreignId('city_id');
                $table->foreignId('venue_id');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('city_procedure')) {
            Schema::create('city_procedure', function (Blueprint $table) {
                $table->id();
                $table->foreignId('city_id');
                $table->foreignId('procedure_id');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('city_treatment')) {
            Schema::create('city_treatment', function (Blueprint $table) {
                $table->id();
                $table->foreignId('city_id');
                $table->foreignId('treatment_id');
                $table->timestamps();
            });
        }
    }
}
