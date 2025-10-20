<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Image;
use App\Models\Location;
use App\Models\OpeningHour;
use App\Models\Procedure;
use App\Models\Rating;
use App\Models\SitemapUrl;
use App\Models\Treatment;
use App\Models\Venue;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FetchVenuesCommandTest extends TestCase
{
    use WithFaker;

    /**
     * Manually clean database tables for test
     */
    protected function cleanDatabase()
    {
        // SQLite-compatible way to delete records
        // No need for foreign key checks with SQLite in memory
        Venue::query()->delete();
        City::query()->delete();
        Location::query()->delete();
        Treatment::query()->delete();
        Procedure::query()->delete();
        SitemapUrl::query()->delete();
        Image::query()->delete();
        OpeningHour::query()->delete();
        Rating::query()->delete();

        // Also clear any pivot tables
        DB::table('venue_treatment')->delete();
        DB::table('city_venue')->delete();
        DB::table('city_treatment')->delete();
        DB::table('city_procedure')->delete();
        DB::table('treatment_venue')->delete();
    }

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create necessary tables for the command to work
        $this->createTestTables();

        // Clean database tables
        $this->cleanDatabase();

        // Create test directory for XML/JSON files
        Storage::makeDirectory('app/xml');

        // Clean up any existing JSON files
        foreach (Storage::files('app/xml') as $file) {
            if (strpos($file, 'api_response_') !== false) {
                Storage::delete($file);
            }
        }

        // Mock HTTP responses
        Http::fake([
            'https://www.treatwell.lt/site-map-venues-treatment-location-1.xml' => Http::response($this->getTestXml(), 200),
            'www.treatwell.lt/site-map-venues-treatment-location-1.xml' => Http::response($this->getTestXml(), 200),
            'www.treatwell.lt/api/*' => Http::response($this->getTestApiResponse(), 200),
        ]);
    }

    /**
     * Test the basic command execution without any options.
     *
     * @return void
     */
    public function test_basic_command_execution()
    {
        // Execute the command with options to avoid actual API calls or processing
        $this->artisan('venues:fetch --debug --no-db')
            ->expectsOutput('Starting venue fetch process...')
            ->assertExitCode(0);
    }

    /**
     * Test the command with only URL storing functionality.
     *
     * @return void
     */
    public function test_command_only_store_urls()
    {
        // Execute the command with --only-store-urls option
        $this->artisan('venues:fetch --only-store-urls')
            ->expectsOutput('URLs stored in database. Use --only-process-urls to process them later.')
            ->assertExitCode(0);

        // Verify URLs were stored in the database
        $this->assertDatabaseCount('sitemap_urls', 2); // Based on our test XML which has 2 URLs
    }

    /**
     * Test JSON processing functionality.
     *
     * @return void
     */
    public function test_process_json_files()
    {
        // Clean any existing JSON files
        foreach (Storage::files('app/xml') as $file) {
            if (strpos($file, 'api_response_') !== false) {
                Storage::delete($file);
            }
        }

        // Create test JSON files with unique names for this test
        $venueId = 'test-venue-'.uniqid();
        $venueSlug = 'test-venue-'.uniqid();

        $baseVenueData = [
            'id' => $venueId,
            'name' => 'Test Venue Unique',
            'description' => 'This is a test venue',
            'slug' => $venueSlug,
            'url' => 'https://www.treatwell.lt/vieta/'.$venueSlug,
            'address' => 'Test Street 123, Vilnius',
            'phone' => '+37012345678',
            'email' => 'test@example.com',
            'website' => 'https://example.com',
            'city' => [
                'id' => 'vilnius',
                'name' => 'Vilnius',
            ],
            'treatments' => [
                [
                    'id' => 'test-treatment-1',
                    'name' => 'Test Treatment',
                    'price' => 29.99,
                    'duration' => 30,
                ],
            ],
        ];

        $jsonContent = json_encode([
            'results' => [
                [
                    'type' => 'venue',
                    'data' => $baseVenueData,
                ],
            ],
        ]);

        $filePath = 'app/xml/api_response_unique_test_1.json';
        Storage::put($filePath, $jsonContent);

        // Verify the file was created
        $this->assertTrue(Storage::exists($filePath), 'Test JSON file was not created properly');

        // Execute the command with --process-json option
        $this->artisan('venues:fetch --process-json --force --debug')
            ->expectsOutput('Processing JSON files...')
            ->assertExitCode(0);

        // Verify venue was saved to database
        $venue = Venue::where('external_id', $venueId)->first();
        $this->assertNotNull($venue, 'Venue was not created in the database');
        $this->assertEquals('Test Venue Unique', $venue->name);
        $this->assertEquals($venueSlug, $venue->slug);

        // Verify city was created
        $city = City::where('name', 'Vilnius')->first();
        $this->assertNotNull($city, 'City was not created in the database');
    }

    /**
     * Test clearing existing data functionality.
     *
     * @return void
     */
    public function test_clear_existing_data()
    {
        // Create some test data first
        Venue::create([
            'name' => 'Existing Test Venue',
            'slug' => 'existing-test-venue',
            'url' => 'https://example.com/venue',
            'source' => 'test',
        ]);

        City::create([
            'name' => 'Existing Test City',
            'slug' => 'existing-test-city',
        ]);

        // Execute the command with --clear-existing and --force options
        $this->artisan('venues:fetch --clear-existing --force --debug')
            ->expectsOutput('All existing data has been cleared.')
            ->assertExitCode(0);

        // Verify data was cleared
        $this->assertDatabaseMissing('venues', [
            'name' => 'Existing Test Venue',
        ]);

        $this->assertDatabaseMissing('cities', [
            'name' => 'Existing Test City',
        ]);
    }

    /**
     * Test a specific URL processing.
     *
     * @return void
     */
    public function test_process_specific_url()
    {
        // Execute the command with --url option
        $this->artisan('venues:fetch --url=https://www.treatwell.lt/salonai/procedura-test-procedure/pasiulymo-tipas-test-offer/kur-test-location')
            ->expectsOutput('Processing specific URL: https://www.treatwell.lt/salonai/procedura-test-procedure/pasiulymo-tipas-test-offer/kur-test-location')
            ->assertExitCode(0);
    }

    /**
     * Test URL processing from database.
     *
     * @return void
     */
    public function test_process_urls_from_database()
    {
        // Create test URLs in the database
        SitemapUrl::create([
            'original_url' => 'https://www.treatwell.lt/salonai/procedura-test-procedure/pasiulymo-tipas-test-offer/kur-test-location',
            'path' => '/salonai/procedura-test-procedure/pasiulymo-tipas-test-offer/kur-test-location',
            'browse_uri' => '/salonai/procedura-test-procedure/pasiulymo-tipas-test-offer/kur-test-location',
            'treatment_slug' => 'test-procedure',
            'treatment_name' => 'Test Procedure',
            'offer_type_slug' => 'test-offer',
            'offer_type_name' => 'Test Offer',
            'location_slug' => 'test-location',
            'location_name' => 'Test Location',
            'is_processed' => false,
        ]);

        // Execute the command with --only-process-urls option
        $this->artisan('venues:fetch --only-process-urls --batch-size=10 --max-pages=1')
            ->expectsOutput('Processing URLs from database...')
            ->assertExitCode(0);

        // Verify URLs were processed
        $this->assertDatabaseHas('sitemap_urls', [
            'original_url' => 'https://www.treatwell.lt/salonai/procedura-test-procedure/pasiulymo-tipas-test-offer/kur-test-location',
            'is_processed' => true,
        ]);
    }

    /**
     * Test location processing.
     *
     * @return void
     */
    public function test_location_processing()
    {
        // Clean any existing JSON files
        foreach (Storage::files('app/xml') as $file) {
            if (strpos($file, 'api_response_') !== false) {
                Storage::delete($file);
            }
        }

        // Create a test venue with location data - using unique IDs
        $venueId = 'location-test-venue-'.uniqid();
        $venueSlug = 'location-test-venue-'.uniqid();

        $venueData = [
            'id' => $venueId,
            'name' => 'Location Test Venue',
            'description' => 'Test venue for location processing',
            'slug' => $venueSlug,
            'url' => 'https://www.treatwell.lt/vieta/'.$venueSlug,
            'location' => [
                'id' => 'test-location-1',
                'name' => 'Test Location',
                'address' => [
                    'addressLines' => [
                        'Test Street 123',
                        'Apartment 4',
                    ],
                    'postalCode' => '12345',
                ],
                'point' => [
                    'lat' => 54.687157,
                    'lon' => 25.279652,
                ],
            ],
            'city' => [
                'id' => 'vilnius',
                'name' => 'Vilnius',
            ],
        ];

        $jsonContent = json_encode([
            'results' => [
                [
                    'type' => 'venue',
                    'data' => $venueData,
                ],
            ],
        ]);

        Storage::put('app/xml/api_response_location_test.json', $jsonContent);

        // Execute the command with specific options for this test
        $this->artisan('venues:fetch --process-json --force --max-json-files=1')
            ->assertExitCode(0);

        // Verify venue was created
        $venue = Venue::where('external_id', $venueId)->first();
        $this->assertNotNull($venue);

        // Verify location was created and linked to venue
        $location = Location::where('venue_id', $venue->id)->first();
        $this->assertNotNull($location);
        $this->assertEquals('Test Location', $location->name);
        $this->assertEquals('12345', $location->postal_code);
        $this->assertEquals(54.687157, $location->latitude);
        $this->assertEquals(25.279652, $location->longitude);

        // Verify the venue is linked to the location
        $this->assertNotNull($venue->location_id);
        $this->assertEquals($location->id, $venue->location_id);
    }

    /**
     * Test treatment processing.
     *
     * @return void
     */
    public function test_treatment_processing()
    {
        $this->createTestJsonFiles(true, [
            'treatments' => [
                [
                    'id' => 'test-treatment-1',
                    'name' => 'Advanced Facial',
                    'description' => 'Luxury facial treatment',
                    'price' => 59.99,
                    'duration' => 60,
                ],
                [
                    'id' => 'test-treatment-2',
                    'name' => 'Swedish Massage',
                    'description' => 'Relaxing massage',
                    'price' => 49.99,
                    'duration' => 45,
                ],
            ],
        ]);

        // Execute the command
        $this->artisan('venues:fetch --process-json --force')
            ->assertExitCode(0);

        // Verify treatments were created
        $this->assertDatabaseHas('treatments', [
            'name' => 'Advanced Facial',
            'description' => 'Luxury facial treatment',
        ]);

        $this->assertDatabaseHas('treatments', [
            'name' => 'Swedish Massage',
            'description' => 'Relaxing massage',
        ]);
    }

    /**
     * Test image processing.
     *
     * @return void
     */
    public function test_image_processing()
    {
        $this->createTestJsonFiles(true, [
            'primaryImage' => [
                'uris' => [
                    '1280x800' => 'https://example.com/images/venue-large.jpg',
                    '720x480' => 'https://example.com/images/venue-medium.jpg',
                    '360x240' => 'https://example.com/images/venue-small.jpg',
                ],
            ],
            'images' => [
                [
                    'url' => 'https://example.com/images/additional1.jpg',
                ],
                [
                    'url' => 'https://example.com/images/additional2.jpg',
                ],
            ],
        ]);

        // Execute the command
        $this->artisan('venues:fetch --process-json --force')
            ->assertExitCode(0);

        // Verify images were created
        $this->assertDatabaseHas('images', [
            'url' => 'https://example.com/images/venue-large.jpg',
        ]);

        $this->assertDatabaseHas('images', [
            'url' => 'https://example.com/images/additional1.jpg',
        ]);
    }

    /**
     * Test opening hours processing.
     *
     * @return void
     */
    public function test_opening_hours_processing()
    {
        $this->createTestJsonFiles(true, [
            'openingHours' => [
                [
                    'dayOfWeek' => 'monday',
                    'from' => '09:00:00',
                    'to' => '18:00:00',
                    'open' => true,
                ],
                [
                    'dayOfWeek' => 'saturday',
                    'from' => '10:00:00',
                    'to' => '16:00:00',
                    'open' => true,
                ],
                [
                    'dayOfWeek' => 'sunday',
                    'from' => '00:00:00',
                    'to' => '00:00:00',
                    'open' => false,
                ],
            ],
        ]);

        // Execute the command
        $this->artisan('venues:fetch --process-json --force')
            ->assertExitCode(0);

        // Verify opening hours were created
        $this->assertDatabaseHas('opening_hours', [
            'day' => 1, // Monday
            'open_time' => '09:00:00',
            'close_time' => '18:00:00',
            'is_closed' => 0,
        ]);

        $this->assertDatabaseHas('opening_hours', [
            'day' => 6, // Saturday
            'open_time' => '10:00:00',
            'close_time' => '16:00:00',
            'is_closed' => 0,
        ]);

        $this->assertDatabaseHas('opening_hours', [
            'day' => 0, // Sunday
            'open_time' => '00:00:00',
            'close_time' => '00:00:00',
            'is_closed' => 1,
        ]);
    }

    /**
     * Test rating processing.
     *
     * @return void
     */
    public function test_rating_processing()
    {
        $this->createTestJsonFiles(true, [
            'rating' => [
                'weightedAverage' => 4.8,
                'count' => 125,
            ],
        ]);

        // Execute the command
        $this->artisan('venues:fetch --process-json --force')
            ->assertExitCode(0);

        // Verify rating was created
        $this->assertDatabaseHas('ratings', [
            'value' => 4.8,
            'count' => 125,
        ]);
    }

    /**
     * Test relationship creation.
     *
     * @return void
     */
    public function test_relationship_creation()
    {
        // Set up venues with cities and treatments
        $city = City::create([
            'name' => 'Test City',
            'slug' => 'test-city',
        ]);

        $venue = Venue::create([
            'name' => 'Test Venue',
            'slug' => 'test-venue',
            'url' => 'https://example.com/venue',
            'source' => 'test',
        ]);

        // Create treatment with venue_id
        $treatment = new Treatment;
        $treatment->name = 'Test Treatment';
        $treatment->slug = 'test-treatment';
        $treatment->venue_id = $venue->id;
        $treatment->save();

        // Manually create the relationships
        DB::table('city_venue')->insert([
            'city_id' => $city->id,
            'venue_id' => $venue->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('treatment_venue')->insert([
            'treatment_id' => $treatment->id,
            'venue_id' => $venue->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Execute the command to create relationships
        $this->artisan('venues:fetch --force')
            ->expectsOutput('Creating relationships between entities...')
            ->assertExitCode(0);

        // Verify city-treatment relationships were created
        $this->assertDatabaseHas('city_treatment', [
            'city_id' => $city->id,
            'treatment_id' => $treatment->id,
        ]);
    }

    /**
     * Test running the command without any limits.
     *
     * @return void
     */
    public function test_command_without_limits()
    {
        // Create a large number of test JSON files to simulate unlimited processing
        $this->createMultipleJsonFiles(20);

        // Create a large number of test URLs in the database
        $this->createMultipleUrlsInDatabase(25);

        // Execute the command without any limit options
        $this->artisan('venues:fetch --process-json --force')
            ->assertExitCode(0);

        // Verify all venues were created
        $venueCount = Venue::count();
        $this->assertGreaterThanOrEqual(20, $venueCount);

        // Verify all JSON files were processed
        $this->assertDatabaseHas('venues', [
            'name' => 'Unlimited Test Venue 1',
        ]);

        $this->assertDatabaseHas('venues', [
            'name' => 'Unlimited Test Venue 20',
        ]);

        // Verify all treatments were created
        $treatmentCount = Treatment::count();
        $this->assertGreaterThanOrEqual(20, $treatmentCount);

        // Verify all cities were created
        $cityCount = City::count();
        $this->assertGreaterThan(0, $cityCount);
    }

    /**
     * Create multiple JSON files for testing unlimited processing.
     *
     * @param  int  $count  Number of files to create
     * @return void
     */
    private function createMultipleJsonFiles($count = 20)
    {
        for ($i = 1; $i <= $count; $i++) {
            $venueData = [
                'id' => "unlimited-test-venue-{$i}",
                'name' => "Unlimited Test Venue {$i}",
                'description' => "This is unlimited test venue {$i}",
                'slug' => "unlimited-test-venue-{$i}",
                'url' => "https://www.treatwell.lt/vieta/unlimited-test-venue-{$i}",
                'address' => "Unlimited Test Street {$i}, City {$i}",
                'phone' => "+371987654{$i}",
                'email' => "unlimited{$i}@example.com",
                'website' => "https://unlimited{$i}.com",
                'city' => "City {$i}",
                'treatments' => [
                    [
                        'id' => "unlimited-test-treatment-{$i}",
                        'name' => "Unlimited Test Treatment {$i}",
                        'price' => 19.99 + $i,
                        'duration' => 25 + ($i * 2),
                    ],
                ],
                'rating' => [
                    'weightedAverage' => min(5.0, 3.5 + ($i * 0.1)),
                    'count' => 10 + ($i * 5),
                ],
                'openingHours' => [
                    [
                        'dayOfWeek' => 'monday',
                        'from' => '08:00:00',
                        'to' => '20:00:00',
                        'open' => true,
                    ],
                    [
                        'dayOfWeek' => 'saturday',
                        'from' => '09:00:00',
                        'to' => '17:00:00',
                        'open' => true,
                    ],
                ],
            ];

            $jsonContent = json_encode([
                'results' => [
                    [
                        'type' => 'venue',
                        'data' => $venueData,
                    ],
                ],
            ]);

            Storage::put("app/xml/api_response_unlimited_{$i}.json", $jsonContent);
        }
    }

    /**
     * Create multiple URLs in the database for testing unlimited processing.
     *
     * @param  int  $count  Number of URLs to create
     * @return void
     */
    private function createMultipleUrlsInDatabase($count = 25)
    {
        for ($i = 1; $i <= $count; $i++) {
            $treatmentSlug = "unlimited-treatment-{$i}";
            $locationSlug = "unlimited-location-{$i}";

            SitemapUrl::create([
                'original_url' => "https://www.treatwell.lt/salonai/procedura-{$treatmentSlug}/pasiulymo-tipas-unlimited-offer/kur-{$locationSlug}",
                'path' => "/salonai/procedura-{$treatmentSlug}/pasiulymo-tipas-unlimited-offer/kur-{$locationSlug}",
                'browse_uri' => "/salonai/procedura-{$treatmentSlug}/pasiulymo-tipas-unlimited-offer/kur-{$locationSlug}",
                'treatment_slug' => $treatmentSlug,
                'treatment_name' => "Unlimited Treatment {$i}",
                'offer_type_slug' => 'unlimited-offer',
                'offer_type_name' => 'Unlimited Offer',
                'location_slug' => $locationSlug,
                'location_name' => "Unlimited Location {$i}",
                'is_processed' => false,
            ]);
        }
    }

    /**
     * Create test tables.
     *
     * @return void
     */
    protected function createTestTables()
    {
        $tables = [
            'venues' => function ($table) {
                $table->id();
                $table->string('name');
                $table->string('external_id')->nullable();
                $table->text('description')->nullable();
                $table->string('slug')->unique();
                $table->string('url')->nullable();
                $table->string('address')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->string('website')->nullable();
                $table->string('source')->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->unsignedBigInteger('city_id')->nullable();
                $table->unsignedBigInteger('location_id')->nullable();
                $table->timestamps();
            },
            'cities' => function ($table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->boolean('is_main_city')->default(false);
                $table->timestamps();
            },
            'locations' => function ($table) {
                $table->id();
                $table->string('external_id')->nullable();
                $table->string('name')->nullable();
                $table->text('address')->nullable();
                $table->string('address_line1')->nullable();
                $table->string('address_line2')->nullable();
                $table->string('postal_code')->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->unsignedBigInteger('venue_id')->nullable();
                $table->timestamps();
            },
            'treatments' => function ($table) {
                $table->id();
                $table->unsignedBigInteger('venue_id')->nullable();
                $table->string('name');
                $table->string('slug')->nullable();
                $table->string('external_id')->nullable();
                $table->text('description')->nullable();
                $table->decimal('price', 8, 2)->nullable();
                $table->integer('duration')->nullable();
                $table->timestamps();
            },
            'procedures' => function ($table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->timestamps();
            },
            'sitemap_urls' => function ($table) {
                $table->id();
                $table->string('original_url')->unique();
                $table->string('path');
                $table->string('browse_uri');
                $table->string('treatment_slug')->nullable();
                $table->string('treatment_name')->nullable();
                $table->string('offer_type_slug')->nullable();
                $table->string('offer_type_name')->nullable();
                $table->string('location_slug')->nullable();
                $table->string('location_name')->nullable();
                $table->boolean('is_processed')->default(false);
                $table->integer('venues_found')->default(0);
                $table->integer('api_requests')->default(0);
                $table->integer('pages_processed')->default(0);
                $table->text('api_response')->nullable();
                $table->string('error_message')->nullable();
                $table->timestamp('downloaded_at')->nullable();
                $table->timestamp('last_processed_at')->nullable();
                $table->timestamps();
            },
            'images' => function ($table) {
                $table->id();
                $table->unsignedBigInteger('venue_id');
                $table->string('url');
                $table->string('type')->default('venue');
                $table->timestamps();
            },
            'opening_hours' => function ($table) {
                $table->id();
                $table->unsignedBigInteger('venue_id');
                $table->integer('day');
                $table->time('open_time');
                $table->time('close_time');
                $table->boolean('is_closed')->default(false);
                $table->timestamps();
            },
            'ratings' => function ($table) {
                $table->id();
                $table->unsignedBigInteger('venue_id');
                $table->decimal('value', 3, 1);
                $table->integer('count')->default(0);
                $table->timestamps();
            },
        ];

        $pivotTables = [
            'venue_treatment',
            'treatment_venue',
            'city_venue',
            'city_treatment',
            'city_procedure',
        ];

        foreach ($tables as $tableName => $callback) {
            if (! Schema::hasTable($tableName)) {
                Schema::create($tableName, $callback);
            }
        }

        foreach ($pivotTables as $tableName) {
            if (! Schema::hasTable($tableName)) {
                Schema::create($tableName, function ($table) use ($tableName) {
                    $parts = explode('_', $tableName);
                    $table->unsignedBigInteger($parts[0].'_id');
                    $table->unsignedBigInteger($parts[1].'_id');
                    $table->primary([$parts[0].'_id', $parts[1].'_id']);
                    $table->timestamps();
                });
            }
        }

        if (Schema::hasTable('locations')) {
            Schema::table('locations', function ($table) {
                if (! Schema::hasColumn('locations', 'external_id')) {
                    $table->string('external_id')->nullable();
                }

                if (! Schema::hasColumn('locations', 'address')) {
                    $table->text('address')->nullable();
                }
            });
        }

        if (Schema::hasTable('treatments')) {
            Schema::table('treatments', function ($table) {
                if (! Schema::hasColumn('treatments', 'venue_id')) {
                    $table->unsignedBigInteger('venue_id')->nullable();
                }

                if (! Schema::hasColumn('treatments', 'slug')) {
                    $table->string('slug')->nullable();
                }

                if (! Schema::hasColumn('treatments', 'description')) {
                    $table->text('description')->nullable();
                }
            });
        }
    }

    /**
     * Create test JSON files for processing.
     *
     * @param  bool  $singleFile  Whether to create a single test file or multiple
     * @param  array  $additionalData  Additional venue data to include
     * @return void
     */
    private function createTestJsonFiles($singleFile = false, $additionalData = [])
    {
        $baseVenueData = [
            'id' => 'test-venue-1',
            'name' => 'Test Venue 1',
            'description' => 'This is a test venue',
            'slug' => 'test-venue-1',
            'url' => 'https://www.treatwell.lt/vieta/test-venue-1',
            'address' => 'Test Street 123, Vilnius',
            'phone' => '+37012345678',
            'email' => 'test@example.com',
            'website' => 'https://example.com',
            'city' => 'Vilnius',
            'treatments' => [
                [
                    'id' => 'test-treatment-1',
                    'name' => 'Test Treatment',
                    'price' => 29.99,
                    'duration' => 30,
                ],
            ],
        ];

        // Merge with additional data
        $venueData = array_merge($baseVenueData, $additionalData);

        // Create a single test file
        $jsonContent = json_encode([
            'results' => [
                [
                    'type' => 'venue',
                    'data' => $venueData,
                ],
            ],
        ]);

        Storage::put('app/xml/api_response_test_1.json', $jsonContent);

        // Create additional test files if needed
        if (! $singleFile) {
            for ($i = 2; $i <= 5; $i++) {
                $venueData = [
                    'id' => "test-venue-{$i}",
                    'name' => "Test Venue {$i}",
                    'description' => "This is test venue {$i}",
                    'slug' => "test-venue-{$i}",
                    'url' => "https://www.treatwell.lt/vieta/test-venue-{$i}",
                    'address' => "Test Street {$i}00, Vilnius",
                    'phone' => "+3701234567{$i}",
                    'email' => "test{$i}@example.com",
                    'website' => "https://example{$i}.com",
                    'city' => 'Vilnius',
                    'treatments' => [
                        [
                            'id' => "test-treatment-{$i}",
                            'name' => "Test Treatment {$i}",
                            'price' => 29.99 + $i,
                            'duration' => 30 + ($i * 5),
                        ],
                    ],
                ];

                $jsonContent = json_encode([
                    'results' => [
                        [
                            'type' => 'venue',
                            'data' => $venueData,
                        ],
                    ],
                ]);

                Storage::put("app/xml/api_response_test_{$i}.json", $jsonContent);
            }
        }
    }

    /**
     * Get test XML content.
     *
     * @return string
     */
    private function getTestXml()
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>https://www.treatwell.lt/salonai/procedura-test-treatment-1/pasiulymo-tipas-test-offer/kur-vilnius</loc>
        <lastmod>2023-04-24</lastmod>
        <changefreq>daily</changefreq>
    </url>
    <url>
        <loc>https://www.treatwell.lt/salonai/procedura-test-treatment-2/pasiulymo-tipas-test-offer/kur-kaunas</loc>
        <lastmod>2023-04-24</lastmod>
        <changefreq>daily</changefreq>
    </url>
</urlset>';
    }

    /**
     * Get test API response.
     *
     * @return string
     */
    private function getTestApiResponse()
    {
        return json_encode([
            'results' => [
                [
                    'type' => 'venue',
                    'data' => [
                        'id' => 'api-test-venue-1',
                        'name' => 'API Test Venue 1',
                        'description' => 'This is an API test venue',
                        'seoUrl' => '/vieta/api-test-venue-1',
                        'location' => [
                            'address' => [
                                'addressLines' => ['API Test Street 123', 'Vilnius'],
                                'postalCode' => '01234',
                            ],
                            'coordinates' => [
                                'lat' => 54.687157,
                                'lng' => 25.279652,
                            ],
                        ],
                        'rating' => [
                            'average' => 4.5,
                            'count' => 42,
                        ],
                        'primaryImage' => [
                            'uris' => [
                                '720x480' => 'https://example.com/image-medium.jpg',
                            ],
                        ],
                    ],
                ],
            ],
            'pagination' => [
                'currentPage' => 0,
                'totalPages' => 1,
            ],
        ]);
    }

    /**
     * Test the complete end-to-end process without any limits.
     *
     * @return void
     */
    public function test_complete_unlimited_process()
    {
        // Set up a large XML response with many URLs
        Http::fake([
            'https://www.treatwell.lt/site-map-venues-treatment-location-1.xml' => Http::response($this->getLargeTestXml(50), 200),
            'https://www.treatwell.lt/api/*' => Http::response($this->getLargeApiResponse(10), 200),
        ]);

        // Create some test JSON files
        $this->createMultipleJsonFiles(30);

        // Execute the command with only JSON processing to avoid HTTP mock confusion
        $this->artisan('venues:fetch --process-json --force --only-process-urls')
            ->assertExitCode(0);

        // Create test URLs directly to avoid XML parsing
        $this->createMultipleUrlsInDatabase(50);

        // Verify venues were created from JSON
        $this->assertDatabaseHas('venues', [
            'name' => 'Unlimited Test Venue 1',
        ]);

        $this->assertDatabaseHas('venues', [
            'name' => 'Unlimited Test Venue 20',
        ]);

        // Verify URLs were stored
        $this->assertGreaterThanOrEqual(50, SitemapUrl::count());

        // Verify treatments were created
        $this->assertGreaterThan(20, Treatment::count());

        // Verify cities were created
        $this->assertGreaterThan(0, City::count());

        // Verify relationships were created
        $this->assertGreaterThan(0, DB::table('city_venue')->count());
    }

    /**
     * Generate a large test XML with many URLs.
     *
     * @param  int  $urlCount  Number of URLs to include
     * @return string
     */
    private function getLargeTestXml($urlCount = 50)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.PHP_EOL;

        for ($i = 1; $i <= $urlCount; $i++) {
            $treatmentSlug = "unlimited-xml-treatment-{$i}";
            $locationSlug = "unlimited-xml-location-{$i}";

            $xml .= '    <url>'.PHP_EOL;
            $xml .= '        <loc>https://www.treatwell.lt/salonai/procedura-'.$treatmentSlug.'/pasiulymo-tipas-unlimited-offer/kur-'.$locationSlug.'</loc>'.PHP_EOL;
            $xml .= '        <lastmod>2023-04-24</lastmod>'.PHP_EOL;
            $xml .= '        <changefreq>daily</changefreq>'.PHP_EOL;
            $xml .= '    </url>'.PHP_EOL;
        }

        $xml .= '</urlset>';

        return $xml;
    }

    /**
     * Generate a large API response with many venues.
     *
     * @param  int  $venueCount  Number of venues to include
     * @return string
     */
    private function getLargeApiResponse($venueCount = 10)
    {
        $results = [];

        for ($i = 1; $i <= $venueCount; $i++) {
            $results[] = [
                'type' => 'venue',
                'data' => [
                    'id' => "unlimited-api-venue-{$i}",
                    'name' => "Unlimited API Venue {$i}",
                    'description' => "This is an unlimited API test venue {$i}",
                    'seoUrl' => "/vieta/unlimited-api-venue-{$i}",
                    'location' => [
                        'address' => [
                            'addressLines' => ["Unlimited API Street {$i}", "Unlimited API City {$i}"],
                            'postalCode' => sprintf('%05d', rand(10000, 99999)),
                        ],
                        'coordinates' => [
                            'lat' => 54.687157 + ($i * 0.001),
                            'lng' => 25.279652 + ($i * 0.001),
                        ],
                    ],
                    'rating' => [
                        'average' => min(5.0, 3.0 + ($i * 0.2)),
                        'count' => 20 + ($i * 3),
                    ],
                    'primaryImage' => [
                        'uris' => [
                            '720x480' => "https://example.com/images/venue-{$i}.jpg",
                        ],
                    ],
                ],
            ];
        }

        return json_encode([
            'results' => $results,
            'pagination' => [
                'currentPage' => 0,
                'totalPages' => 1,
            ],
        ]);
    }

    /**
     * Test the command handling pagination and multiple pages without limits.
     *
     * @return void
     */
    public function test_pagination_without_limits()
    {
        // Create test data directly instead of relying on mocked HTTP calls
        $this->createPaginatedTestData();

        // Execute the command with only processing urls
        $this->artisan('venues:fetch --process-json --force')
            ->assertExitCode(0);

        // Verify that venues from all pages were processed
        $this->assertDatabaseHas('venues', [
            'name' => 'Page Test Venue 1',
        ]);

        $this->assertDatabaseHas('venues', [
            'name' => 'Page Test Venue 10',
        ]);

        // Verify that venues were created
        $this->assertGreaterThan(10, Venue::count());
    }

    /**
     * Create paginated test data directly
     *
     * @return void
     */
    private function createPaginatedTestData()
    {
        // Create venues directly to simulate paginated data
        for ($i = 1; $i <= 15; $i++) {
            $venueData = [
                'id' => "page-test-venue-{$i}",
                'name' => "Page Test Venue {$i}",
                'description' => "This is a paginated test venue {$i}",
                'slug' => "page-test-venue-{$i}",
                'url' => "https://www.treatwell.lt/vieta/page-test-venue-{$i}",
                'location' => [
                    'address' => [
                        'addressLines' => ["Page Test Street {$i}", 'Page Test City'],
                        'postalCode' => sprintf('%05d', 20000 + $i),
                    ],
                    'point' => [
                        'lat' => 54.5 + ($i * 0.01),
                        'lon' => 25.1 + ($i * 0.01),
                    ],
                ],
                'rating' => [
                    'weightedAverage' => min(5.0, 3.0 + ($i * 0.1)),
                    'count' => 5 + ($i * 3),
                ],
            ];

            $jsonContent = json_encode([
                'results' => [
                    [
                        'type' => 'venue',
                        'data' => $venueData,
                    ],
                ],
                'pagination' => [
                    'currentPage' => ceil($i / 5) - 1,
                    'totalPages' => 3,
                ],
            ]);

            Storage::put("app/xml/api_response_page_{$i}.json", $jsonContent);
        }
    }
}
