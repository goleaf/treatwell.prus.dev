<?php

namespace App\Console\Commands;

use App\Models\City;
use App\Models\Procedure;
use App\Models\Venue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExportVenuesToJsonCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'venues:export-json
                           {--output=storage/app/json/exports : Directory to save exported JSON files}
                           {--batch-size=50 : Number of venues to process in each batch}
                           {--max=0 : Maximum number of venues to export (0 = all)}
                           {--city= : Filter venues by city slug}
                           {--procedure= : Filter venues by procedure slug}
                           {--format=individual : Export format (individual = one file per venue, single = all venues in one file)}
                           {--pretty : Whether to format JSON output with indentation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export venues from database to JSON files';

    /**
     * Statistics tracking
     */
    protected $stats = [
        'venues_exported' => 0,
        'files_created' => 0,
        'bytes_written' => 0,
        'batches_processed' => 0,
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting venue export to JSON...');
        $startTime = microtime(true);

        // Get command options
        $outputDir = $this->option('output');
        $batchSize = (int) $this->option('batch-size');
        $maxVenues = (int) $this->option('max');
        $citySlug = $this->option('city');
        $procedureSlug = $this->option('procedure');
        $format = $this->option('format');
        $pretty = $this->option('pretty');

        // Validate format
        if (! in_array($format, ['individual', 'single'])) {
            $this->error("Invalid format: {$format}. Must be 'individual' or 'single'.");

            return 1;
        }

        // Ensure output directory exists
        if (! Storage::exists($outputDir)) {
            Storage::makeDirectory($outputDir);
            $this->info("Created output directory: {$outputDir}");
        }

        // Build query with filters
        $query = Venue::with([
            'location',
            'location.city',
            'rating',
            'images',
            'openingHours',
            'treatments',
            'procedures',
            'cities',
        ]);

        // Apply city filter if specified
        if ($citySlug) {
            $city = City::where('slug', $citySlug)->first();
            if (! $city) {
                $this->error("City with slug '{$citySlug}' not found.");

                return 1;
            }

            $this->info("Filtering venues in city: {$city->name}");
            $query->byCity($city->id);
        }

        // Apply procedure filter if specified
        if ($procedureSlug) {
            $procedure = Procedure::where('slug', $procedureSlug)->first();
            if (! $procedure) {
                $this->error("Procedure with slug '{$procedureSlug}' not found.");

                return 1;
            }

            $this->info("Filtering venues with procedure: {$procedure->name}");
            $query->byProcedure($procedure->id);
        }

        // Get total count for progress bar
        $totalVenues = $query->count();

        if ($totalVenues === 0) {
            $this->error('No venues found matching the specified criteria.');

            return 1;
        }

        // Apply max venues limit if specified
        if ($maxVenues > 0 && $totalVenues > $maxVenues) {
            $totalVenues = $maxVenues;
            $this->info("Limited to exporting {$maxVenues} venues");
        }

        $this->info("Found {$totalVenues} venues to export");

        // Initialize progress bar
        $progressBar = $this->output->createProgressBar($totalVenues);
        $progressBar->start();

        // Handle single file format
        if ($format === 'single') {
            $filename = 'venues_export_'.date('Y-m-d_His').'.json';
            $filepath = $outputDir.'/'.$filename;

            // Start the JSON array
            Storage::put($filepath, "{\n\"venues\": [");
            $isFirstVenue = true;

            // Process venues in batches
            $processed = 0;

            $query->chunk($batchSize, function ($venues) use ($filepath, $maxVenues, $pretty, &$processed, &$isFirstVenue, $progressBar) {
                $jsonChunk = '';

                foreach ($venues as $venue) {
                    // Check if we've reached the maximum
                    if ($maxVenues > 0 && $processed >= $maxVenues) {
                        break;
                    }

                    // Generate JSON for this venue
                    $venueData = $this->formatVenueData($venue);
                    $venueJson = json_encode($venueData, $pretty ? JSON_PRETTY_PRINT : 0);

                    // Add comma separator if not the first venue
                    if (! $isFirstVenue) {
                        $jsonChunk .= ",\n";
                    } else {
                        $isFirstVenue = false;
                    }

                    $jsonChunk .= $venueJson;

                    // Update stats
                    $this->stats['venues_exported']++;
                    $this->stats['bytes_written'] += strlen($venueJson);

                    $processed++;
                    $progressBar->advance();
                }

                // Append to the file
                if (! empty($jsonChunk)) {
                    Storage::append($filepath, $jsonChunk);
                }

                $this->stats['batches_processed']++;
            });

            // Close the JSON array
            Storage::append($filepath, "\n]\n}");

            $this->stats['files_created'] = 1;
        } else {
            // Individual file format - process venues in batches
            $processed = 0;
            $batchNum = 0;

            $query->chunk($batchSize, function ($venues) use ($outputDir, $maxVenues, $pretty, &$processed, &$batchNum, $progressBar) {
                foreach ($venues as $venue) {
                    // Check if we've reached the maximum
                    if ($maxVenues > 0 && $processed >= $maxVenues) {
                        break;
                    }

                    // Generate JSON for this venue
                    $venueData = $this->formatVenueData($venue);
                    $venueJson = json_encode($venueData, $pretty ? JSON_PRETTY_PRINT : 0);

                    // Create filename using venue ID and slug
                    $filename = 'venue_'.$venue->id.'_'.Str::slug($venue->name).'.json';
                    $filepath = $outputDir.'/'.$filename;

                    // Save to file
                    Storage::put($filepath, $venueJson);

                    // Update stats
                    $this->stats['venues_exported']++;
                    $this->stats['files_created']++;
                    $this->stats['bytes_written'] += strlen($venueJson);

                    $processed++;
                    $progressBar->advance();
                }

                $batchNum++;
                $this->stats['batches_processed'] = $batchNum;
            });
        }

        $progressBar->finish();
        $this->line('');

        // Display execution summary
        $executionTime = round(microtime(true) - $startTime, 2);
        $this->displayStatistics($executionTime, $outputDir);

        return 0;
    }

    /**
     * Format venue data for export
     */
    protected function formatVenueData(Venue $venue): array
    {
        $venueData = [
            'id' => $venue->external_id,
            'name' => $venue->name,
            'slug' => $venue->slug,
            'description' => $venue->description,
            'url' => $venue->url,
            'address' => $venue->address,
            'phone' => $venue->phone,
            'email' => $venue->email,
            'website' => $venue->website,
            'latitude' => $venue->latitude,
            'longitude' => $venue->longitude,
        ];

        // Add location data if available
        if ($venue->location) {
            $venueData['location'] = [
                'name' => $venue->location->name,
                'address' => [
                    'postalCode' => $venue->location->postal_code,
                    'addressLines' => array_filter([
                        $venue->location->address_line1,
                        $venue->location->address_line2,
                    ]),
                ],
            ];

            if ($venue->location->latitude && $venue->location->longitude) {
                $venueData['location']['point'] = [
                    'lat' => (float) $venue->location->latitude,
                    'lon' => (float) $venue->location->longitude,
                ];
            }

            if ($venue->location->city) {
                $venueData['location']['tree'] = [
                    'id' => $venue->location->city->id,
                    'name' => $venue->location->city->name,
                    'normalisedName' => $venue->location->city->normalised_name ?? $venue->location->city->slug,
                    'type' => 'city',
                ];

                if ($venue->location->city->latitude && $venue->location->city->longitude) {
                    $venueData['location']['tree']['point'] = [
                        'lat' => (float) $venue->location->city->latitude,
                        'lon' => (float) $venue->location->city->longitude,
                    ];
                }
            }
        }

        // Add city data if available
        if ($venue->cities->isNotEmpty()) {
            $primaryCity = $venue->cities->first();
            $venueData['city'] = [
                'id' => $primaryCity->id,
                'name' => $primaryCity->name,
                'slug' => $primaryCity->slug,
            ];
        }

        // Add rating data if available
        if ($venue->rating) {
            $venueData['rating'] = [
                'average' => (float) $venue->rating->average,
                'weightedAverage' => (float) $venue->rating->value,
                'count' => (int) $venue->rating->count,
            ];
        }

        // Add treatments data if available
        if ($venue->treatments->isNotEmpty()) {
            $venueData['treatments'] = [];

            foreach ($venue->treatments as $treatment) {
                $treatmentData = [
                    'id' => $treatment->id,
                    'name' => $treatment->name,
                ];

                if ($treatment->price) {
                    $treatmentData['price'] = (float) $treatment->price;
                }

                if ($treatment->duration) {
                    $treatmentData['duration'] = (int) $treatment->duration;
                }

                if ($treatment->procedure) {
                    $treatmentData['procedure'] = [
                        'id' => $treatment->procedure->id,
                        'name' => $treatment->procedure->name,
                        'slug' => $treatment->procedure->slug,
                    ];
                }

                $venueData['treatments'][] = $treatmentData;
            }
        }

        // Add procedures data if available
        if ($venue->procedures->isNotEmpty()) {
            $venueData['procedures'] = [];

            foreach ($venue->procedures as $procedure) {
                $venueData['procedures'][] = [
                    'id' => $procedure->id,
                    'name' => $procedure->name,
                    'slug' => $procedure->slug,
                ];
            }
        }

        // Add images data if available
        if ($venue->images->isNotEmpty()) {
            $venueData['images'] = [];

            // Find primary image
            $primaryImage = $venue->images->where('is_primary', true)->first();
            if ($primaryImage) {
                $venueData['primaryImage'] = [
                    'id' => $primaryImage->id,
                    'url' => $primaryImage->url,
                ];
            }

            // Add all images
            foreach ($venue->images as $image) {
                $venueData['images'][] = [
                    'id' => $image->id,
                    'url' => $image->url,
                    'type' => $image->type,
                ];
            }
        }

        // Add opening hours data if available
        if ($venue->openingHours->isNotEmpty()) {
            $venueData['openingHours'] = [];

            $dayMap = [
                0 => 'sunday',
                1 => 'monday',
                2 => 'tuesday',
                3 => 'wednesday',
                4 => 'thursday',
                5 => 'friday',
                6 => 'saturday',
            ];

            foreach ($venue->openingHours as $hour) {
                $venueData['openingHours'][] = [
                    'dayOfWeek' => $dayMap[$hour->day] ?? 'unknown',
                    'open' => ! $hour->is_closed,
                    'from' => $hour->open_time,
                    'to' => $hour->close_time,
                ];
            }
        }

        return $venueData;
    }

    /**
     * Display execution statistics
     */
    protected function displayStatistics(float $executionTime, string $outputDir): void
    {
        $this->info(str_repeat('=', 40));
        $this->info('Export Summary');
        $this->info(str_repeat('=', 40));
        $this->line("Venues exported: {$this->stats['venues_exported']}");
        $this->line("Files created: {$this->stats['files_created']}");
        $this->line("Batches processed: {$this->stats['batches_processed']}");
        $this->line('Total data written: '.$this->formatBytes($this->stats['bytes_written']));
        $this->line("Execution time: {$executionTime} seconds");
        $this->line("Output directory: {$outputDir}");
        $this->info(str_repeat('=', 40));
    }

    /**
     * Format bytes to human-readable size
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }
}
