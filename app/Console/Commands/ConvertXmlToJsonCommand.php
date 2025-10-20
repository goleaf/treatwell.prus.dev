<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ConvertXmlToJsonCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'venues:convert-xml
                            {--input=storage/app/xml : Directory containing XML files}
                            {--output=storage/app/json : Directory to save JSON files}
                            {--batch-size=20 : Number of files to process in a batch}
                            {--max-files=0 : Maximum number of files to process (0 = all)}
                            {--force : Process files even if JSON output already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert XML files from Treatwell API to JSON format';

    /**
     * Statistics tracking
     */
    private $stats = [
        'files_processed' => 0,
        'files_skipped' => 0,
        'files_failed' => 0,
        'bytes_read' => 0,
        'bytes_written' => 0
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting XML to JSON conversion...');
        $startTime = microtime(true);

        // Get command options
        $inputDir = $this->option('input');
        $outputDir = $this->option('output');
        $batchSize = (int)$this->option('batch-size');
        $maxFiles = (int)$this->option('max-files');
        $force = $this->option('force');

        // Ensure output directory exists
        if (!Storage::exists($outputDir)) {
            Storage::makeDirectory($outputDir);
            $this->info("Created output directory: {$outputDir}");
        }

        // Get all XML files in the input directory
        $xmlFiles = Storage::files($inputDir);
        $xmlFiles = array_filter($xmlFiles, function($file) {
            return Str::endsWith($file, '.xml');
        });

        $totalFiles = count($xmlFiles);
        
        if ($totalFiles === 0) {
            $this->error("No XML files found in {$inputDir}.");
            return 1;
        }

        $this->info("Found {$totalFiles} XML files to process.");

        // Apply max files limit if specified
        if ($maxFiles > 0 && $totalFiles > $maxFiles) {
            $xmlFiles = array_slice($xmlFiles, 0, $maxFiles);
            $this->info("Limited to processing {$maxFiles} files.");
        }

        // Process files in batches
        $fileCount = count($xmlFiles);
        $batches = ceil($fileCount / $batchSize);
        
        $this->info("Processing files in {$batches} batches of up to {$batchSize}...");
        
        for ($i = 0; $i < $batches; $i++) {
            $batchFiles = array_slice($xmlFiles, $i * $batchSize, $batchSize);
            $this->processBatch($batchFiles, $outputDir, $force);
            
            // Show progress
            $processed = min(($i + 1) * $batchSize, $fileCount);
            $this->info("Processed {$processed}/{$fileCount} files.");
        }

        // Display statistics
        $executionTime = microtime(true) - $startTime;
        $this->displayStatistics($executionTime);
        
        return 0;
    }

    /**
     * Process a batch of XML files.
     *
     * @param array $files
     * @param string $outputDir
     * @param bool $force
     * @return void
     */
    private function processBatch(array $files, string $outputDir, bool $force): void
    {
        $progressBar = $this->output->createProgressBar(count($files));
        $progressBar->start();
        
        foreach ($files as $file) {
            $this->processXmlFile($file, $outputDir, $force);
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->line('');
    }

    /**
     * Process a single XML file.
     *
     * @param string $filePath
     * @param string $outputDir
     * @param bool $force
     * @return void
     */
    private function processXmlFile(string $filePath, string $outputDir, bool $force): void
    {
        // Determine output file path
        $baseName = basename($filePath, '.xml');
        $jsonFilePath = $outputDir . '/' . $baseName . '.json';
        
        // Skip if output file already exists and force is not enabled
        if (!$force && Storage::exists($jsonFilePath)) {
            $this->stats['files_skipped']++;
            return;
        }
        
        try {
            // Read XML file content
            $xmlContent = Storage::get($filePath);
            $this->stats['bytes_read'] += strlen($xmlContent);
            
            // Fix XML issues - add root element if missing
            if (strpos($xmlContent, '<?xml') === false) {
                $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . $xmlContent;
            }
            
            // Remove whitespace and invalid characters
            $xmlContent = $this->cleanXmlContent($xmlContent);
            
            // Convert XML to JSON
            $json = $this->convertXmlToJson($xmlContent);
            
            if ($json === false) {
                $this->stats['files_failed']++;
                return;
            }
            
            // Post-process the JSON to match expected format
            $processedJson = $this->postProcessJson($json);
            
            // Save JSON to file
            Storage::put($jsonFilePath, $processedJson);
            $this->stats['bytes_written'] += strlen($processedJson);
            $this->stats['files_processed']++;
        } catch (\Exception $e) {
            $this->error("Error processing file {$filePath}: " . $e->getMessage());
            $this->stats['files_failed']++;
        }
    }

    /**
     * Clean XML content to fix common issues.
     *
     * @param string $xmlContent
     * @return string
     */
    private function cleanXmlContent(string $xmlContent): string
    {
        // Remove control characters
        $xmlContent = preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', '', $xmlContent);
        
        // Ensure there's a single root element
        if (strpos($xmlContent, '<root>') === false && 
            preg_match_all('/<\?xml|<[a-zA-Z]/', $xmlContent, $matches) && count($matches[0]) > 1) {
            $xmlContent = '<root>' . $xmlContent . '</root>';
        }
        
        return $xmlContent;
    }

    /**
     * Convert XML content to JSON.
     *
     * @param string $xmlContent
     * @return string|false
     */
    private function convertXmlToJson(string $xmlContent)
    {
        // Load XML
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlContent);
        
        if ($xml === false) {
            $errors = libxml_get_errors();
            $errorMsg = '';
            foreach ($errors as $error) {
                $errorMsg .= $error->message . ' at line ' . $error->line . "\n";
            }
            libxml_clear_errors();
            $this->error("XML parsing error: " . $errorMsg);
            return false;
        }
        
        // Convert to JSON
        $jsonOptions = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        $json = json_encode($xml, $jsonOptions);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error("JSON encoding error: " . json_last_error_msg());
            return false;
        }
        
        return $json;
    }

    /**
     * Post-process JSON to match expected format for import.
     *
     * @param string $json
     * @return string
     */
    private function postProcessJson(string $json): string
    {
        $data = json_decode($json, true);
        
        // If the data has a 'root' element, move everything up one level
        if (isset($data['root'])) {
            $data = $data['root'];
        }
        
        // Handle different XML formats
        $processed = [];
        
        // Check if we have a venue list structure
        if (isset($data['venue']) || isset($data['venues']['venue'])) {
            $venues = isset($data['venue']) ? $data['venue'] : $data['venues']['venue'];
            
            // If venues is not an array of venues but a single venue, wrap it in an array
            if (isset($venues['id']) || isset($venues['name'])) {
                $venues = [$venues];
            }
            
            $processed['results'] = [];
            
            foreach ($venues as $venue) {
                $processed['results'][] = [
                    'type' => 'venue',
                    'data' => $this->normalizeVenueData($venue)
                ];
            }
        } 
        // Check if we have already results format
        elseif (isset($data['results']) && is_array($data['results'])) {
            $processed = $data;
            
            // Process each result
            foreach ($processed['results'] as &$result) {
                if ($result['type'] === 'venue' && isset($result['data'])) {
                    $result['data'] = $this->normalizeVenueData($result['data']);
                }
            }
        }
        // Otherwise just use the data as is
        else {
            $processed = $data;
        }
        
        return json_encode($processed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Normalize venue data to match expected format.
     *
     * @param array $venue
     * @return array
     */
    private function normalizeVenueData(array $venue): array
    {
        // Normalize ID
        if (!isset($venue['id']) && isset($venue['@attributes']['id'])) {
            $venue['id'] = $venue['@attributes']['id'];
            unset($venue['@attributes']);
        }
        
        // Normalize location
        if (isset($venue['location'])) {
            if (isset($venue['location']['address']) && !is_array($venue['location']['address'])) {
                $venue['location']['address'] = [
                    'addressLines' => [$venue['location']['address']]
                ];
            }
            
            // Normalize coordinates
            if (isset($venue['location']['coordinates'])) {
                if (!isset($venue['location']['point'])) {
                    $venue['location']['point'] = [
                        'lat' => $venue['location']['coordinates']['latitude'] ?? $venue['location']['coordinates']['lat'] ?? 0,
                        'lon' => $venue['location']['coordinates']['longitude'] ?? $venue['location']['coordinates']['lon'] ?? $venue['location']['coordinates']['lng'] ?? 0
                    ];
                }
            }
        }
        
        // Normalize treatments
        if (isset($venue['treatments']) && !isset($venue['treatments'][0]) && isset($venue['treatments']['treatment'])) {
            $treatmentData = $venue['treatments']['treatment'];
            
            // Check if it's a single treatment or an array
            if (isset($treatmentData['id']) || isset($treatmentData['name'])) {
                $venue['treatments'] = [$treatmentData];
            } else {
                $venue['treatments'] = $treatmentData;
            }
        }
        
        // Normalize images
        if (isset($venue['images']) && !isset($venue['images'][0]) && isset($venue['images']['image'])) {
            $imageData = $venue['images']['image'];
            
            // Check if it's a single image or an array
            if (isset($imageData['id']) || isset($imageData['url']) || isset($imageData['uris'])) {
                $venue['images'] = [$imageData];
            } else {
                $venue['images'] = $imageData;
            }
        }
        
        // Normalize opening hours
        if (isset($venue['openingHours']) && !isset($venue['openingHours'][0]) && isset($venue['openingHours']['day'])) {
            $hourData = $venue['openingHours']['day'];
            
            // Check if it's a single day or an array
            if (isset($hourData['dayOfWeek'])) {
                $venue['openingHours'] = [$hourData];
            } else {
                $venue['openingHours'] = $hourData;
            }
        }
        
        return $venue;
    }

    /**
     * Display statistics after processing.
     *
     * @param float $executionTime
     * @return void
     */
    private function displayStatistics(float $executionTime): void
    {
        $this->info(str_repeat('=', 30));
        $this->info('Processing Statistics');
        $this->info(str_repeat('=', 30));
        
        $this->line("Files processed: {$this->stats['files_processed']}");
        $this->line("Files skipped: {$this->stats['files_skipped']}");
        $this->line("Files failed: {$this->stats['files_failed']}");
        $this->line("Total data read: " . $this->formatBytes($this->stats['bytes_read']));
        $this->line("Total data written: " . $this->formatBytes($this->stats['bytes_written']));
        $this->line("Execution time: " . round($executionTime, 2) . " seconds");
        $this->info(str_repeat('=', 30));
    }
    
    /**
     * Format bytes to human-readable format.
     *
     * @param int $bytes
     * @return string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
} 