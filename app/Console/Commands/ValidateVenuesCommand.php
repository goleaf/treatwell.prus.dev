<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\Venue;
use App\Models\City;
use App\Models\Treatment;
use App\Models\Procedure;
use App\Models\Image;
use App\Models\Location;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ValidateVenuesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'venues:validate
                           {--report=console : Report output format (console, json, or csv)}
                           {--output=storage/app/reports : Directory to save reports}
                           {--batch-size=100 : Number of venues to process in each batch}
                           {--max=0 : Maximum number of venues to validate (0 = all)}
                           {--fix : Attempt to fix common issues automatically}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate venue data and generate reports on potential issues';

    /**
     * Validation results
     */
    protected $results = [
        'total_venues' => 0,
        'issues_found' => 0,
        'venues_with_issues' => 0,
        'fixed_issues' => 0,
        'validation_checks' => [],
        'issues' => []
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting venue data validation...');
        $startTime = microtime(true);

        // Get command options
        $reportFormat = $this->option('report');
        $outputDir = $this->option('output');
        $batchSize = (int)$this->option('batch-size');
        $maxVenues = (int)$this->option('max');
        $fix = $this->option('fix');

        // Validate report format
        if (!in_array($reportFormat, ['console', 'json', 'csv'])) {
            $this->error("Invalid report format: {$reportFormat}. Must be 'console', 'json', or 'csv'.");
            return 1;
        }

        // Ensure output directory exists if needed
        if ($reportFormat !== 'console' && !Storage::exists($outputDir)) {
            Storage::makeDirectory($outputDir);
            $this->info("Created output directory: {$outputDir}");
        }

        // Build query
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

        // Get total count for progress bar
        $totalVenues = $query->count();
        $this->results['total_venues'] = $totalVenues;
        
        if ($totalVenues === 0) {
            $this->error("No venues found in the database.");
            return 1;
        }

        // Apply max venues limit if specified
        if ($maxVenues > 0 && $totalVenues > $maxVenues) {
            $totalVenues = $maxVenues;
            $this->info("Limited to validating {$maxVenues} venues");
        }

        $this->info("Starting validation of {$totalVenues} venues");

        // Initialize progress bar
        $progressBar = $this->output->createProgressBar($totalVenues);
        $progressBar->start();

        // Define validation checks
        $this->setupValidationChecks();

        // Process venues in batches
        $processed = 0;
        
        $query->chunk($batchSize, function ($venues) use ($maxVenues, $fix, &$processed, $progressBar) {
            foreach ($venues as $venue) {
                // Check if we've reached the maximum
                if ($maxVenues > 0 && $processed >= $maxVenues) {
                    return false;
                }
                
                // Validate this venue
                $this->validateVenue($venue, $fix);
                
                $processed++;
                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->line('');

        // Generate report
        $this->generateReport($reportFormat, $outputDir, round(microtime(true) - $startTime, 2));
        
        return $this->results['issues_found'] > 0 ? 1 : 0;
    }

    /**
     * Set up validation checks to run
     *
     * @return void
     */
    protected function setupValidationChecks(): void
    {
        $this->results['validation_checks'] = [
            'missing_name' => [
                'description' => 'Venue is missing a name',
                'count' => 0,
                'fixable' => false
            ],
            'missing_location' => [
                'description' => 'Venue has no associated location',
                'count' => 0,
                'fixable' => false
            ],
            'missing_city' => [
                'description' => 'Venue has no associated city',
                'count' => 0,
                'fixable' => false
            ],
            'missing_slug' => [
                'description' => 'Venue has no slug',
                'count' => 0,
                'fixable' => true
            ],
            'missing_treatments' => [
                'description' => 'Venue has no treatments',
                'count' => 0,
                'fixable' => false
            ],
            'missing_images' => [
                'description' => 'Venue has no images',
                'count' => 0,
                'fixable' => false
            ],
            'invalid_coordinates' => [
                'description' => 'Venue has invalid coordinates',
                'count' => 0,
                'fixable' => true
            ],
            'missing_opening_hours' => [
                'description' => 'Venue has no opening hours',
                'count' => 0,
                'fixable' => false
            ],
            'broken_images' => [
                'description' => 'Venue has images with broken URLs',
                'count' => 0,
                'fixable' => true
            ],
            'missing_email' => [
                'description' => 'Venue has no email address',
                'count' => 0,
                'fixable' => false
            ],
            'missing_phone' => [
                'description' => 'Venue has no phone number',
                'count' => 0,
                'fixable' => false
            ],
            'inconsistent_city_references' => [
                'description' => 'Venue has inconsistent city references',
                'count' => 0,
                'fixable' => true
            ]
        ];
    }

    /**
     * Validate a single venue
     *
     * @param Venue $venue
     * @param bool $fix Whether to attempt fixes
     * @return void
     */
    protected function validateVenue(Venue $venue, bool $fix): void
    {
        $venueIssues = [];
        $venueFixed = [];
        
        // Check for missing name
        if (empty($venue->name)) {
            $this->recordIssue('missing_name', $venue);
            $venueIssues[] = 'Missing name';
        }
        
        // Check for missing slug
        if (empty($venue->slug)) {
            $this->recordIssue('missing_slug', $venue);
            $venueIssues[] = 'Missing slug';
            
            // Fix: Generate slug from name
            if ($fix && !empty($venue->name)) {
                $venue->slug = \Illuminate\Support\Str::slug($venue->name);
                $venue->save();
                $venueFixed[] = 'Generated slug';
                $this->results['fixed_issues']++;
            }
        }
        
        // Check for missing location
        if (!$venue->location) {
            $this->recordIssue('missing_location', $venue);
            $venueIssues[] = 'Missing location';
        }
        
        // Check for missing city relationships
        $hasCityReference = false;
        
        if ($venue->location && $venue->location->city) {
            $hasCityReference = true;
        }
        
        if ($venue->cities->isEmpty()) {
            if (!$hasCityReference) {
                $this->recordIssue('missing_city', $venue);
                $venueIssues[] = 'Missing city';
            }
        } else {
            // Check for inconsistent city references
            if ($hasCityReference && $venue->location->city_id) {
                $locationCityId = $venue->location->city_id;
                $relationshipCityIds = $venue->cities->pluck('id')->toArray();
                
                if (!in_array($locationCityId, $relationshipCityIds)) {
                    $this->recordIssue('inconsistent_city_references', $venue);
                    $venueIssues[] = 'Inconsistent city references';
                    
                    // Fix: Add location's city to the relationships
                    if ($fix) {
                        if (!$venue->cities()->where('city_id', $locationCityId)->exists()) {
                            $venue->cities()->attach($locationCityId);
                            $venueFixed[] = 'Added location city to relationships';
                            $this->results['fixed_issues']++;
                        }
                    }
                }
            }
        }
        
        // Check for missing treatments
        if ($venue->treatments->isEmpty()) {
            $this->recordIssue('missing_treatments', $venue);
            $venueIssues[] = 'Missing treatments';
        }
        
        // Check for missing images
        if ($venue->images->isEmpty()) {
            $this->recordIssue('missing_images', $venue);
            $venueIssues[] = 'Missing images';
        } else {
            // Check for images with broken URLs
            $brokenImages = false;
            foreach ($venue->images as $image) {
                if (empty($image->url) || !filter_var($image->url, FILTER_VALIDATE_URL)) {
                    $brokenImages = true;
                    
                    // Fix: Remove broken images
                    if ($fix) {
                        $image->delete();
                        $venueFixed[] = 'Removed broken image';
                        $this->results['fixed_issues']++;
                    }
                }
            }
            
            if ($brokenImages) {
                $this->recordIssue('broken_images', $venue);
                $venueIssues[] = 'Has broken images';
            }
        }
        
        // Check for invalid coordinates
        if ($venue->latitude === null || $venue->longitude === null ||
            $venue->latitude == 0 || $venue->longitude == 0) {
            
            // Only mark as issue if location exists but has invalid coords
            if ($venue->location && 
                ($venue->location->latitude !== null && $venue->location->longitude !== null) &&
                ($venue->location->latitude != 0 && $venue->location->longitude != 0)) {
                
                $this->recordIssue('invalid_coordinates', $venue);
                $venueIssues[] = 'Invalid coordinates';
                
                // Fix: Copy coordinates from location
                if ($fix) {
                    $venue->latitude = $venue->location->latitude;
                    $venue->longitude = $venue->location->longitude;
                    $venue->save();
                    $venueFixed[] = 'Copied coordinates from location';
                    $this->results['fixed_issues']++;
                }
            }
        }
        
        // Check for missing opening hours
        if ($venue->openingHours->isEmpty()) {
            $this->recordIssue('missing_opening_hours', $venue);
            $venueIssues[] = 'Missing opening hours';
        }
        
        // Check for missing email
        if (empty($venue->email)) {
            $this->recordIssue('missing_email', $venue);
            $venueIssues[] = 'Missing email';
        }
        
        // Check for missing phone
        if (empty($venue->phone)) {
            $this->recordIssue('missing_phone', $venue);
            $venueIssues[] = 'Missing phone';
        }
        
        // Record venue in issues list if it has any issues
        if (!empty($venueIssues)) {
            $this->results['venues_with_issues']++;
            
            $this->results['issues'][] = [
                'venue_id' => $venue->id,
                'external_id' => $venue->external_id,
                'name' => $venue->name,
                'issues' => $venueIssues,
                'fixed' => $venueFixed
            ];
        }
    }

    /**
     * Record an issue in the validation results
     *
     * @param string $type
     * @param Venue $venue
     * @return void
     */
    protected function recordIssue(string $type, Venue $venue): void
    {
        $this->results['validation_checks'][$type]['count']++;
        $this->results['issues_found']++;
    }

    /**
     * Generate validation report
     *
     * @param string $format
     * @param string $outputDir
     * @param float $executionTime
     * @return void
     */
    protected function generateReport(string $format, string $outputDir, float $executionTime): void
    {
        $timestamp = date('Y-m-d_His');
        
        switch ($format) {
            case 'json':
                $filename = "venue_validation_{$timestamp}.json";
                $filepath = $outputDir . '/' . $filename;
                
                $reportData = [
                    'summary' => [
                        'total_venues' => $this->results['total_venues'],
                        'venues_with_issues' => $this->results['venues_with_issues'],
                        'issues_found' => $this->results['issues_found'],
                        'fixed_issues' => $this->results['fixed_issues'],
                        'execution_time' => $executionTime
                    ],
                    'validation_checks' => $this->results['validation_checks'],
                    'issues' => $this->results['issues']
                ];
                
                Storage::put($filepath, json_encode($reportData, JSON_PRETTY_PRINT));
                $this->info("JSON report saved to {$filepath}");
                break;
                
            case 'csv':
                $summaryFilename = "venue_validation_summary_{$timestamp}.csv";
                $summaryFilepath = $outputDir . '/' . $summaryFilename;
                
                $issuesFilename = "venue_validation_issues_{$timestamp}.csv";
                $issuesFilepath = $outputDir . '/' . $issuesFilename;
                
                // Generate summary CSV
                $summaryData = "Check,Description,Count\n";
                foreach ($this->results['validation_checks'] as $key => $check) {
                    $summaryData .= "{$key},\"{$check['description']}\",{$check['count']}\n";
                }
                
                Storage::put($summaryFilepath, $summaryData);
                
                // Generate issues CSV
                $issuesData = "Venue ID,External ID,Name,Issues,Fixed\n";
                foreach ($this->results['issues'] as $issue) {
                    $issues = implode('; ', $issue['issues']);
                    $fixed = implode('; ', $issue['fixed'] ?? []);
                    $issuesData .= "{$issue['venue_id']},{$issue['external_id']},\"{$issue['name']}\",\"{$issues}\",\"{$fixed}\"\n";
                }
                
                Storage::put($issuesFilepath, $issuesData);
                
                $this->info("CSV summary report saved to {$summaryFilepath}");
                $this->info("CSV issues report saved to {$issuesFilepath}");
                break;
                
            case 'console':
            default:
                $this->displayConsoleReport($executionTime);
                break;
        }
    }

    /**
     * Display console report
     *
     * @param float $executionTime
     * @return void
     */
    protected function displayConsoleReport(float $executionTime): void
    {
        $this->info(str_repeat('=', 60));
        $this->info('Venue Validation Summary');
        $this->info(str_repeat('=', 60));
        $this->line("Total venues checked: {$this->results['total_venues']}");
        $this->line("Venues with issues: {$this->results['venues_with_issues']}");
        $this->line("Total issues found: {$this->results['issues_found']}");
        $this->line("Issues fixed: {$this->results['fixed_issues']}");
        $this->line("Execution time: {$executionTime} seconds");
        $this->info(str_repeat('-', 60));
        
        $this->info('Issues by Type:');
        $table = [];
        foreach ($this->results['validation_checks'] as $key => $check) {
            if ($check['count'] > 0) {
                $table[] = [$key, $check['description'], $check['count'], $check['fixable'] ? 'Yes' : 'No'];
            }
        }
        
        if (!empty($table)) {
            $this->table(['Issue Type', 'Description', 'Count', 'Fixable'], $table);
        } else {
            $this->info('No issues found!');
        }
        
        if ($this->results['venues_with_issues'] > 0) {
            if ($this->output->isVerbose()) {
                $this->info('Venues with Issues:');
                
                $venueTable = [];
                foreach ($this->results['issues'] as $issue) {
                    $issues = implode(', ', $issue['issues']);
                    $fixed = !empty($issue['fixed']) ? implode(', ', $issue['fixed']) : '-';
                    $venueTable[] = [$issue['venue_id'], $issue['name'], $issues, $fixed];
                }
                
                $this->table(['ID', 'Name', 'Issues', 'Fixed'], $venueTable);
            } else {
                $this->info('Run with -v to see detailed venue issues');
            }
        }
        
        $this->info(str_repeat('=', 60));
    }
} 