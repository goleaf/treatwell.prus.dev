<?php

namespace Tests\Unit\CityDataParser;

use App\Models\City;
use App\Models\ParseProgress;
use App\Services\ProgressTracker;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: city-data-parser, Property 10: Resumability
 * Validates: Requirements 5.1
 */
class ResumabilityPropertyTest extends TestCase
{
    use RefreshDatabase, TestTrait;

    private ProgressTracker $progressTracker;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->progressTracker = $this->app->make(ProgressTracker::class);
        
        // Initialize Eris
        $this->erisSetUp();
    }

    /**
     * Property 10: Resumability
     * For any interrupted parsing session, restarting the parser should continue 
     * from the last successfully processed city
     */
    public function testResumabilityAfterInterruption(): void
    {
        $this->forAll(
            Generator\choose(2, 4), // number of cities to process
            Generator\choose(1, 2)  // number of cities to complete before interruption
        )->then(function (int $totalCities, int $completedCities) {
            // Clean up any existing data
            City::query()->delete();
            ParseProgress::query()->delete();
            
            // Ensure completedCities doesn't exceed totalCities
            $completedCities = min($completedCities, $totalCities - 1);
            
            // Create cities for processing with unique identifiers
            $testId = uniqid();
            $cities = [];
            for ($i = 0; $i < $totalCities; $i++) {
                $slug = "resume-city-{$testId}-{$i}";
                $cities[] = City::factory()->create([
                    'slug' => $slug,
                    'entity_id' => "entity-{$testId}-{$i}",
                    'name' => "Resume City {$i}",
                    'is_main_city' => true,
                ]);
            }

            // Simulate partial processing - mark some cities as completed
            for ($i = 0; $i < $completedCities; $i++) {
                $citySlug = $cities[$i]->slug;
                
                // Start processing
                $progress = $this->progressTracker->markCityAsStarted($citySlug);
                
                // Complete processing with some stats
                $this->progressTracker->markCityAsCompleted($citySlug, [
                    'venues_found' => rand(1, 50),
                    'treatments_found' => rand(10, 200),
                    'api_calls_made' => rand(5, 25),
                ]);
            }

            // Simulate interruption - mark one city as processing (stuck)
            if ($completedCities < $totalCities) {
                $stuckCitySlug = $cities[$completedCities]->slug;
                $stuckProgress = $this->progressTracker->markCityAsStarted($stuckCitySlug);
                
                // Simulate that this city has been processing for too long (stuck)
                $stuckProgress->update([
                    'started_at' => now()->subHours(3), // 3 hours ago
                ]);
            }

            // Property: Get cities for resume should identify remaining cities
            $citiesToResume = $this->progressTracker->getCitiesForResume();
            
            // Should include all unprocessed cities plus the stuck one
            $expectedResumeCount = $totalCities - $completedCities;
            $this->assertEquals(
                $expectedResumeCount, 
                $citiesToResume->count(),
                "Should identify {$expectedResumeCount} cities to resume processing (total: {$totalCities}, completed: {$completedCities})"
            );

            // Property: Last processed city should be correctly identified
            $lastProcessedCity = $this->progressTracker->getLastProcessedCity();
            if ($completedCities > 0) {
                // Should be one of the completed cities
                $completedCitySlugs = collect($cities)->take($completedCities)->pluck('slug')->toArray();
                $this->assertContains(
                    $lastProcessedCity,
                    $completedCitySlugs,
                    'Should identify one of the completed cities as last processed'
                );
            } else {
                $this->assertNull($lastProcessedCity, 'Should return null when no cities completed');
            }

            // Property: Stuck processing cities should be reset to pending
            if ($completedCities < $totalCities) {
                $stuckCitySlug = $cities[$completedCities]->slug;
                $stuckProgress = ParseProgress::where('city_slug', $stuckCitySlug)->first();
                $this->assertEquals(
                    'pending',
                    $stuckProgress->status,
                    'Stuck processing city should be reset to pending status'
                );
            }

            // Property: Completed cities should remain completed
            $completedProgress = ParseProgress::completed()->count();
            $this->assertEquals(
                $completedCities,
                $completedProgress,
                'Completed cities should remain in completed status'
            );
        });
    }

    /**
     * Property: Resume should handle empty progress gracefully
     */
    public function testResumeWithNoProgress(): void
    {
        $this->forAll(
            Generator\choose(1, 3)
        )->then(function (int $cityCount) {
            // Clean up any existing data
            City::query()->delete();
            ParseProgress::query()->delete();
            
            // Create cities but no progress records
            $testId = uniqid();
            for ($i = 0; $i < $cityCount; $i++) {
                City::factory()->create([
                    'slug' => "no-progress-city-{$testId}-{$i}",
                    'entity_id' => "entity-no-progress-{$testId}-{$i}",
                    'name' => "No Progress City {$i}",
                    'is_main_city' => true,
                ]);
            }

            // Property: Should identify all cities for processing
            $citiesToResume = $this->progressTracker->getCitiesForResume();
            $this->assertEquals(
                $cityCount,
                $citiesToResume->count(),
                'Should identify all cities when no progress exists'
            );

            // Property: No last processed city
            $lastProcessedCity = $this->progressTracker->getLastProcessedCity();
            $this->assertNull($lastProcessedCity, 'Should return null when no progress exists');
        });
    }

    /**
     * Property: Resume should handle all cities completed
     */
    public function testResumeWithAllCitiesCompleted(): void
    {
        $this->forAll(
            Generator\choose(1, 3)
        )->then(function (int $cityCount) {
            // Clean up any existing data
            City::query()->delete();
            ParseProgress::query()->delete();
            
            // Create cities and mark all as completed
            $testId = uniqid();
            $cities = [];
            for ($i = 0; $i < $cityCount; $i++) {
                $slug = "completed-city-{$testId}-{$i}";
                $cities[] = City::factory()->create([
                    'slug' => $slug,
                    'entity_id' => "entity-completed-{$testId}-{$i}",
                    'name' => "Completed City {$i}",
                    'is_main_city' => true,
                ]);

                // Mark as completed
                $this->progressTracker->markCityAsStarted($slug);
                $this->progressTracker->markCityAsCompleted($slug, [
                    'venues_found' => rand(1, 20),
                    'treatments_found' => rand(5, 100),
                    'api_calls_made' => rand(2, 15),
                ]);
            }

            // Property: No cities should need resuming
            $citiesToResume = $this->progressTracker->getCitiesForResume();
            $this->assertEquals(
                0,
                $citiesToResume->count(),
                'Should identify no cities to resume when all are completed'
            );

            // Property: Last processed city should be the most recently completed
            $lastProcessedCity = $this->progressTracker->getLastProcessedCity();
            $this->assertNotNull($lastProcessedCity, 'Should identify a last processed city');
            $this->assertContains(
                $lastProcessedCity,
                collect($cities)->pluck('slug')->toArray(),
                'Last processed city should be one of the created cities'
            );
        });
    }

    /**
     * Property: Resume should handle failed cities correctly
     */
    public function testResumeWithFailedCities(): void
    {
        $this->forAll(
            Generator\choose(2, 3), // total cities
            Generator\choose(1, 2)  // failed cities
        )->then(function (int $totalCities, int $failedCitiesCount) {
            // Clean up any existing data
            City::query()->delete();
            ParseProgress::query()->delete();
            
            $failedCitiesCount = min($failedCitiesCount, $totalCities);
            
            // Create cities
            $testId = uniqid();
            $cities = [];
            for ($i = 0; $i < $totalCities; $i++) {
                $slug = "failed-test-city-{$testId}-{$i}";
                $cities[] = City::factory()->create([
                    'slug' => $slug,
                    'entity_id' => "entity-failed-{$testId}-{$i}",
                    'name' => "Failed Test City {$i}",
                    'is_main_city' => true,
                ]);
            }

            // Mark some cities as failed
            for ($i = 0; $i < $failedCitiesCount; $i++) {
                $citySlug = $cities[$i]->slug;
                $this->progressTracker->markCityAsStarted($citySlug);
                $this->progressTracker->markCityAsFailed($citySlug, 'Test error message', [
                    'venues_found' => 0,
                    'treatments_found' => 0,
                    'api_calls_made' => rand(1, 5),
                ]);
            }

            // Property: Failed cities should be included in resume list
            $citiesToResume = $this->progressTracker->getCitiesForResume();
            $this->assertEquals(
                $totalCities, // All cities should be resumable (failed + unprocessed)
                $citiesToResume->count(),
                'Should include failed cities in resume list'
            );

            // Property: Failed cities should be identifiable
            $failedCitiesResult = $this->progressTracker->getFailedCities();
            $this->assertEquals(
                $failedCitiesCount,
                $failedCitiesResult->count(),
                'Should correctly identify failed cities'
            );
        });
    }

    /**
     * Property: Progress tracking should be consistent across resume operations
     */
    public function testProgressConsistencyAcrossResume(): void
    {
        $this->forAll(
            Generator\choose(3, 4)
        )->then(function (int $cityCount) {
            // Clean up any existing data
            City::query()->delete();
            ParseProgress::query()->delete();
            
            // Create cities
            $testId = uniqid();
            $cities = [];
            for ($i = 0; $i < $cityCount; $i++) {
                $slug = "consistency-city-{$testId}-{$i}";
                $cities[] = City::factory()->create([
                    'slug' => $slug,
                    'entity_id' => "entity-consistency-{$testId}-{$i}",
                    'name' => "Consistency City {$i}",
                    'is_main_city' => true,
                ]);
            }

            // Process cities in batches to simulate resume
            $batchSize = 2;
            $processedCount = 0;

            for ($batch = 0; $batch < ceil($cityCount / $batchSize); $batch++) {
                $batchStart = $batch * $batchSize;
                $batchEnd = min($batchStart + $batchSize, $cityCount);

                // Process cities in this batch
                for ($i = $batchStart; $i < $batchEnd; $i++) {
                    $citySlug = $cities[$i]->slug;
                    $this->progressTracker->markCityAsStarted($citySlug);
                    $this->progressTracker->markCityAsCompleted($citySlug, [
                        'venues_found' => rand(1, 30),
                        'treatments_found' => rand(5, 150),
                        'api_calls_made' => rand(3, 20),
                    ]);
                    $processedCount++;
                }

                // Check progress consistency after each batch
                $summary = $this->progressTracker->getOverallProgress();
                
                // Property: Completed count should match processed count
                $this->assertEquals(
                    $processedCount,
                    $summary->getCompletedCities(),
                    "Completed cities count should be consistent after batch {$batch}"
                );

                // Property: Pending count should be correct
                $expectedPending = $cityCount - $processedCount;
                $this->assertEquals(
                    $expectedPending,
                    $summary->getPendingCities(),
                    "Pending cities count should be consistent after batch {$batch}"
                );

                // Property: Total should remain constant
                $this->assertEquals(
                    $cityCount,
                    $summary->getTotalCities(),
                    "Total cities count should remain constant"
                );
            }
        });
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