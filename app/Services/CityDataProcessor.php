<?php

namespace App\Services;

use App\Models\City;
use App\Models\Venue;
use App\Models\Treatment;
use App\Models\Location;
use App\Models\Rating;
use App\Models\Image;
use App\Models\OpeningHour;
use App\Models\Procedure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CityDataProcessor
{
    private array $validationErrors = [];
    private array $processingStats = [
        'venues_processed' => 0,
        'treatments_processed' => 0,
        'locations_processed' => 0,
        'ratings_processed' => 0,
        'images_processed' => 0,
        'opening_hours_processed' => 0,
        'services_processed' => 0,
        'procedures_processed' => 0,
        'validation_errors' => 0,
        'unknown_fields_encountered' => 0,
    ];

    /**
     * Process venue data from API response
     */
    public function processVenueData(array $rawData): ProcessedVenue
    {
        try {
            // Validate required fields
            $validationResult = $this->validateVenueData($rawData);
            if (!$validationResult->isValid()) {
                throw new \InvalidArgumentException('Venue data validation failed: ' . implode(', ', $validationResult->getErrors()));
            }

            // Extract core venue information
            $venueData = [
                'external_id' => $this->extractField($rawData, ['id', 'venueId', 'businessId']),
                'name' => $this->extractField($rawData, ['name', 'venueName', 'businessName']),
                'description' => $this->extractField($rawData, ['description', 'venueDescription', 'about']),
                'url' => $this->extractField($rawData, ['url', 'venueUrl', 'seoUrl']),
                'phone' => $this->extractField($rawData, ['phone', 'phoneNumber', 'contactPhone']),
                'email' => $this->extractField($rawData, ['email', 'contactEmail']),
                'website' => $this->extractField($rawData, ['website', 'websiteUrl']),
            ];

            // Process location data if present
            $locationData = null;
            if (isset($rawData['location'])) {
                $locationData = $this->processLocationData($rawData['location']);
            }

            // Process rating data if present
            $ratingData = null;
            if (isset($rawData['rating']) || isset($rawData['ratings'])) {
                $ratingData = $this->processRatingData($rawData['rating'] ?? $rawData['ratings']);
            }

            // Process images if present
            $imagesData = [];
            if (isset($rawData['images']) && is_array($rawData['images'])) {
                foreach ($rawData['images'] as $imageData) {
                    $imagesData[] = $this->processImageData($imageData);
                }
            } elseif (isset($rawData['primaryImage'])) {
                $imagesData[] = $this->processImageData($rawData['primaryImage']);
            }

            // Process opening hours if present
            $openingHoursData = [];
            if (isset($rawData['openingHours']) && is_array($rawData['openingHours'])) {
                foreach ($rawData['openingHours'] as $hourData) {
                    $openingHoursData[] = $this->processOpeningHourData($hourData);
                }
            }

            // Handle unknown fields dynamically
            $unknownFields = $this->extractUnknownFields($rawData, [
                'id', 'venueId', 'businessId', 'name', 'venueName', 'businessName',
                'description', 'venueDescription', 'about', 'url', 'venueUrl', 'seoUrl',
                'phone', 'phoneNumber', 'contactPhone', 'email', 'contactEmail',
                'website', 'websiteUrl', 'location', 'rating', 'ratings', 'images',
                'primaryImage', 'openingHours'
            ]);

            $this->processingStats['venues_processed']++;

            return new ProcessedVenue(
                $venueData,
                $locationData,
                $ratingData,
                $imagesData,
                $openingHoursData,
                $unknownFields
            );

        } catch (\Exception $e) {
            $this->processingStats['validation_errors']++;
            Log::error('Venue data processing error', [
                'error' => $e->getMessage(),
                'raw_data' => $rawData
            ]);
            throw $e;
        }
    }

    /**
     * Process treatment data from API response
     */
    public function processTreatmentData(array $rawData): ProcessedTreatment
    {
        try {
            // Validate required fields
            $validationResult = $this->validateTreatmentData($rawData);
            if (!$validationResult->isValid()) {
                throw new \InvalidArgumentException('Treatment data validation failed: ' . implode(', ', $validationResult->getErrors()));
            }

            // Extract core treatment information
            $treatmentData = [
                'external_id' => $this->extractField($rawData, ['id', 'treatmentId', 'serviceId']),
                'name' => $this->extractField($rawData, ['name', 'treatmentName', 'serviceName']),
                'description' => $this->extractField($rawData, ['description', 'treatmentDescription']),
                'category' => $this->extractField($rawData, ['category', 'categoryName', 'type']),
                'duration' => $this->extractDuration($rawData),
                'price' => $this->extractPrice($rawData),
                'min_duration' => $this->extractField($rawData, ['minDuration', 'durationRange.minDurationMinutes']),
                'max_duration' => $this->extractField($rawData, ['maxDuration', 'durationRange.maxDurationMinutes']),
                'min_price' => $this->extractField($rawData, ['minPrice', 'priceRange.minSalePrice.salePriceAmount', 'priceRange.minSalePriceAmount']),
                'max_price' => $this->extractField($rawData, ['maxPrice', 'priceRange.maxSalePrice.salePriceAmount', 'priceRange.maxSalePriceAmount']),
            ];

            // Handle unknown fields
            $unknownFields = $this->extractUnknownFields($rawData, [
                'id', 'treatmentId', 'serviceId', 'name', 'treatmentName', 'serviceName',
                'description', 'treatmentDescription', 'category', 'categoryName', 'type',
                'duration', 'minDuration', 'maxDuration', 'durationRange', 'price',
                'minPrice', 'maxPrice', 'priceRange'
            ]);

            $this->processingStats['treatments_processed']++;

            return new ProcessedTreatment($treatmentData, $unknownFields);

        } catch (\Exception $e) {
            $this->processingStats['validation_errors']++;
            Log::error('Treatment data processing error', [
                'error' => $e->getMessage(),
                'raw_data' => $rawData
            ]);
            throw $e;
        }
    }

    /**
     * Process location data from API response
     */
    public function processLocationData(array $rawData): ProcessedLocation
    {
        try {
            $locationData = [
                'external_id' => $this->extractField($rawData, ['id', 'locationId']),
                'name' => $this->extractField($rawData, ['name', 'locationName', 'cityName']),
                'address_line1' => $this->extractAddressLine1($rawData),
                'address_line2' => $this->extractAddressLine2($rawData),
                'postal_code' => $this->extractField($rawData, ['postalCode', 'zipCode', 'address.postalCode']),
                'latitude' => $this->extractCoordinate($rawData, 'lat'),
                'longitude' => $this->extractCoordinate($rawData, 'lng'),
                'country_code' => $this->extractField($rawData, ['countryCode', 'address.countryCode']),
            ];

            // Handle unknown fields
            $unknownFields = $this->extractUnknownFields($rawData, [
                'id', 'locationId', 'name', 'locationName', 'cityName', 'address',
                'postalCode', 'zipCode', 'coordinates', 'point', 'countryCode'
            ]);

            $this->processingStats['locations_processed']++;

            return new ProcessedLocation($locationData, $unknownFields);

        } catch (\Exception $e) {
            $this->processingStats['validation_errors']++;
            Log::error('Location data processing error', [
                'error' => $e->getMessage(),
                'raw_data' => $rawData
            ]);
            throw $e;
        }
    }

    /**
     * Validate data integrity of processed data
     */
    public function validateDataIntegrity(array $data): ValidationResult
    {
        $errors = [];

        // Check for required venue fields
        if (isset($data['venues'])) {
            foreach ($data['venues'] as $index => $venue) {
                if (empty($venue['name'])) {
                    $errors[] = "Venue at index {$index} missing required 'name' field";
                }
                if (empty($venue['external_id'])) {
                    $errors[] = "Venue at index {$index} missing required 'external_id' field";
                }
            }
        }

        // Check for required treatment fields
        if (isset($data['treatments'])) {
            foreach ($data['treatments'] as $index => $treatment) {
                if (empty($treatment['name'])) {
                    $errors[] = "Treatment at index {$index} missing required 'name' field";
                }
            }
        }

        // Check for data consistency
        if (isset($data['venues']) && isset($data['treatments'])) {
            $venueIds = array_column($data['venues'], 'external_id');
            foreach ($data['treatments'] as $index => $treatment) {
                if (isset($treatment['venue_id']) && !in_array($treatment['venue_id'], $venueIds)) {
                    $errors[] = "Treatment at index {$index} references non-existent venue ID: {$treatment['venue_id']}";
                }
            }
        }

        return new ValidationResult(empty($errors), $errors);
    }

    /**
     * Transform API response into structured data
     */
    public function transformApiResponse(array $response): array
    {
        $transformedData = [
            'venues' => [],
            'treatments' => [],
            'locations' => [],
            'ratings' => [],
            'images' => [],
            'opening_hours' => [],
            'metadata' => [
                'processed_at' => now()->toISOString(),
                'source' => 'api_response',
                'unknown_fields_count' => 0,
            ]
        ];

        try {
            // Handle different response formats
            if (isset($response['results']) && is_array($response['results'])) {
                // Standard API response format
                foreach ($response['results'] as $result) {
                    if (isset($result['type']) && $result['type'] === 'venue' && isset($result['data'])) {
                        $processedVenue = $this->processVenueData($result['data']);
                        $transformedData['venues'][] = $processedVenue->toArray();
                        
                        // Add related data
                        if ($processedVenue->getLocationData()) {
                            $transformedData['locations'][] = $processedVenue->getLocationData()->toArray();
                        }
                        if ($processedVenue->getRatingData()) {
                            $transformedData['ratings'][] = $processedVenue->getRatingData()->toArray();
                        }
                        $transformedData['images'] = array_merge($transformedData['images'], 
                            array_map(fn($img) => $img->toArray(), $processedVenue->getImagesData()));
                        $transformedData['opening_hours'] = array_merge($transformedData['opening_hours'], 
                            array_map(fn($oh) => $oh->toArray(), $processedVenue->getOpeningHoursData()));
                    }
                }
            } elseif (isset($response['venues']) && is_array($response['venues'])) {
                // Alternative format with direct venues array
                foreach ($response['venues'] as $venueData) {
                    $processedVenue = $this->processVenueData($venueData);
                    $transformedData['venues'][] = $processedVenue->toArray();
                }
            } else {
                // Try to process as single venue
                if (isset($response['name']) || isset($response['id'])) {
                    $processedVenue = $this->processVenueData($response);
                    $transformedData['venues'][] = $processedVenue->toArray();
                }
            }

            // Update metadata
            $transformedData['metadata']['unknown_fields_count'] = $this->processingStats['unknown_fields_encountered'];
            $transformedData['metadata']['processing_stats'] = $this->processingStats;

            return $transformedData;

        } catch (\Exception $e) {
            Log::error('API response transformation error', [
                'error' => $e->getMessage(),
                'response_keys' => array_keys($response)
            ]);
            throw $e;
        }
    }

    /**
     * Extract field value from multiple possible paths
     */
    private function extractField(array $data, array $paths): mixed
    {
        foreach ($paths as $path) {
            $value = $this->getNestedValue($data, $path);
            if ($value !== null) {
                return $value;
            }
        }
        return null;
    }

    /**
     * Get nested value from array using dot notation
     */
    private function getNestedValue(array $data, string $path): mixed
    {
        $keys = explode('.', $path);
        $value = $data;

        foreach ($keys as $key) {
            if (!is_array($value) || !isset($value[$key])) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }

    /**
     * Extract duration from various formats
     */
    private function extractDuration(array $data): ?int
    {
        // Try different duration field formats
        $duration = $this->extractField($data, ['duration', 'durationMinutes', 'durationRange.minDurationMinutes']);
        
        if (is_numeric($duration)) {
            return (int) $duration;
        }

        return null;
    }

    /**
     * Extract price from various formats
     */
    private function extractPrice(array $data): ?float
    {
        // Try different price field formats
        $price = $this->extractField($data, [
            'price', 
            'priceAmount', 
            'priceRange.minSalePrice.salePriceAmount',
            'priceRange.minSalePriceAmount'
        ]);
        
        if (is_numeric($price)) {
            return (float) $price;
        }

        return null;
    }

    /**
     * Extract address line 1 from location data
     */
    private function extractAddressLine1(array $data): ?string
    {
        if (isset($data['address']['addressLines']) && is_array($data['address']['addressLines'])) {
            return $data['address']['addressLines'][0] ?? null;
        }
        
        return $this->extractField($data, ['address', 'addressLine1', 'street']);
    }

    /**
     * Extract address line 2 from location data
     */
    private function extractAddressLine2(array $data): ?string
    {
        if (isset($data['address']['addressLines']) && is_array($data['address']['addressLines'])) {
            return $data['address']['addressLines'][1] ?? null;
        }
        
        return $this->extractField($data, ['addressLine2', 'apartment', 'unit']);
    }

    /**
     * Extract coordinate (latitude or longitude)
     */
    private function extractCoordinate(array $data, string $type): ?float
    {
        // Try different coordinate formats
        $coordinate = null;
        
        if (isset($data['coordinates'])) {
            $coordinate = $data['coordinates'][$type] ?? null;
        } elseif (isset($data['point'])) {
            $coordinate = $data['point'][$type] ?? ($type === 'lng' ? $data['point']['lon'] : $data['point'][$type]);
        } else {
            $coordinate = $data[$type] ?? ($type === 'lng' ? ($data['longitude'] ?? null) : ($data['latitude'] ?? null));
        }

        return is_numeric($coordinate) ? (float) $coordinate : null;
    }

    /**
     * Extract unknown fields that weren't explicitly handled
     */
    private function extractUnknownFields(array $data, array $knownFields): array
    {
        $unknownFields = [];
        
        foreach ($data as $key => $value) {
            if (!in_array($key, $knownFields)) {
                $unknownFields[$key] = $value;
                $this->processingStats['unknown_fields_encountered']++;
            }
        }

        return $unknownFields;
    }

    /**
     * Process rating data
     */
    private function processRatingData(array $rawData): ProcessedRating
    {
        $ratingData = [
            'value' => $this->extractField($rawData, ['value', 'average', 'weightedAverage']),
            'count' => $this->extractField($rawData, ['count', 'reviewCount', 'totalReviews']),
            'display_average' => $this->extractField($rawData, ['displayAverage', 'formattedRating']),
        ];

        $unknownFields = $this->extractUnknownFields($rawData, ['value', 'average', 'weightedAverage', 'count', 'reviewCount', 'totalReviews', 'displayAverage', 'formattedRating']);
        
        $this->processingStats['ratings_processed']++;
        
        return new ProcessedRating($ratingData, $unknownFields);
    }

    /**
     * Process image data
     */
    private function processImageData(array $rawData): ProcessedImage
    {
        $imageData = [
            'url' => $this->extractImageUrl($rawData),
            'type' => $this->extractField($rawData, ['type', 'imageType']),
            'alt_text' => $this->extractField($rawData, ['alt', 'altText', 'description']),
            'is_primary' => $this->extractField($rawData, ['isPrimary', 'primary']) ?? false,
        ];

        $unknownFields = $this->extractUnknownFields($rawData, ['url', 'uris', 'type', 'imageType', 'alt', 'altText', 'description', 'isPrimary', 'primary']);
        
        $this->processingStats['images_processed']++;
        
        return new ProcessedImage($imageData, $unknownFields);
    }

    /**
     * Extract image URL from various formats
     */
    private function extractImageUrl(array $data): ?string
    {
        if (isset($data['url'])) {
            return $data['url'];
        }

        if (isset($data['uris']) && is_array($data['uris'])) {
            // Get the largest available resolution
            $resolutions = ['1280x800', '1080x720', '720x480', '360x240'];
            foreach ($resolutions as $resolution) {
                if (isset($data['uris'][$resolution])) {
                    return $data['uris'][$resolution];
                }
            }
            // Fallback to first available URI
            return reset($data['uris']);
        }

        return null;
    }

    /**
     * Process opening hour data
     */
    private function processOpeningHourData(array $rawData): ProcessedOpeningHour
    {
        $openingHourData = [
            'day_of_week' => $this->mapDayOfWeek($rawData['dayOfWeek'] ?? $rawData['day'] ?? null),
            'opening_time' => $this->extractField($rawData, ['from', 'openTime', 'start']),
            'closing_time' => $this->extractField($rawData, ['to', 'closeTime', 'end']),
            'is_closed' => !($rawData['open'] ?? true),
        ];

        $unknownFields = $this->extractUnknownFields($rawData, ['dayOfWeek', 'day', 'from', 'openTime', 'start', 'to', 'closeTime', 'end', 'open']);
        
        $this->processingStats['opening_hours_processed']++;
        
        return new ProcessedOpeningHour($openingHourData, $unknownFields);
    }

    /**
     * Map day of week to numeric value
     */
    private function mapDayOfWeek($day): int
    {
        if (is_numeric($day)) {
            return (int) $day;
        }

        $dayMap = [
            'sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3,
            'thursday' => 4, 'friday' => 5, 'saturday' => 6
        ];

        return $dayMap[strtolower($day)] ?? 0;
    }

    /**
     * Validate venue data
     */
    private function validateVenueData(array $data): ValidationResult
    {
        $errors = [];

        if (empty($this->extractField($data, ['name', 'venueName', 'businessName']))) {
            $errors[] = 'Venue name is required';
        }

        return new ValidationResult(empty($errors), $errors);
    }

    /**
     * Validate treatment data
     */
    private function validateTreatmentData(array $data): ValidationResult
    {
        $errors = [];

        if (empty($this->extractField($data, ['name', 'treatmentName', 'serviceName']))) {
            $errors[] = 'Treatment name is required';
        }

        return new ValidationResult(empty($errors), $errors);
    }

    /**
     * Get processing statistics
     */
    public function getProcessingStats(): array
    {
        return $this->processingStats;
    }

    /**
     * Reset processing statistics
     */
    public function resetProcessingStats(): void
    {
        $this->processingStats = [
            'venues_processed' => 0,
            'treatments_processed' => 0,
            'locations_processed' => 0,
            'ratings_processed' => 0,
            'images_processed' => 0,
            'opening_hours_processed' => 0,
            'services_processed' => 0,
            'procedures_processed' => 0,
            'validation_errors' => 0,
            'unknown_fields_encountered' => 0,
        ];
    }

    /**
     * Process comprehensive city data including all related entities
     */
    public function processComprehensiveCityData(City $city, array $apiData): array
    {
        DB::beginTransaction();
        
        try {
            $results = [
                'city' => $city->slug,
                'venues_processed' => 0,
                'treatments_processed' => 0,
                'services_processed' => 0,
                'locations_processed' => 0,
                'ratings_processed' => 0,
                'images_processed' => 0,
                'opening_hours_processed' => 0,
                'procedures_processed' => 0,
                'errors' => [],
                'warnings' => [],
            ];

            // Validate API data structure
            $validationResult = $this->validateApiDataStructure($apiData);
            if (!$validationResult->isValid()) {
                $results['warnings'] = array_merge($results['warnings'], $validationResult->getErrors());
            }

            // Process venues and their related data
            if (isset($apiData['venues']) && is_array($apiData['venues'])) {
                foreach ($apiData['venues'] as $venueIndex => $venueData) {
                    try {
                        // Validate venue data before processing
                        if (!$this->isValidVenueData($venueData)) {
                            $results['warnings'][] = "Venue at index {$venueIndex} has insufficient data, skipping";
                            continue;
                        }

                        $venue = $this->processAndStoreVenueDataSafely($city, $venueData);
                        if ($venue) {
                            $results['venues_processed']++;

                            // Process treatments for this venue with error handling
                            if (isset($venueData['treatments']) && is_array($venueData['treatments'])) {
                                foreach ($venueData['treatments'] as $treatmentIndex => $treatmentData) {
                                    try {
                                        if ($this->isValidTreatmentData($treatmentData)) {
                                            $this->processAndStoreTreatmentData($venue, $treatmentData);
                                            $results['treatments_processed']++;
                                        } else {
                                            $results['warnings'][] = "Treatment at index {$treatmentIndex} for venue {$venue->name} has insufficient data";
                                        }
                                    } catch (\Exception $e) {
                                        $results['errors'][] = "Treatment processing error for venue {$venue->name}: " . $e->getMessage();
                                        Log::warning('Treatment processing error', [
                                            'venue_id' => $venue->id,
                                            'treatment_data' => $treatmentData,
                                            'error' => $e->getMessage()
                                        ]);
                                    }
                                }
                            }

                            // Process services for this venue with error handling
                            if (isset($venueData['services']) && is_array($venueData['services'])) {
                                foreach ($venueData['services'] as $serviceIndex => $serviceData) {
                                    try {
                                        if ($this->isValidServiceData($serviceData)) {
                                            $this->processAndStoreServiceData($venue, $serviceData);
                                            $results['services_processed']++;
                                        } else {
                                            $results['warnings'][] = "Service at index {$serviceIndex} for venue {$venue->name} has insufficient data";
                                        }
                                    } catch (\Exception $e) {
                                        $results['errors'][] = "Service processing error for venue {$venue->name}: " . $e->getMessage();
                                        Log::warning('Service processing error', [
                                            'venue_id' => $venue->id,
                                            'service_data' => $serviceData,
                                            'error' => $e->getMessage()
                                        ]);
                                    }
                                }
                            }

                            // Process location data with error handling
                            if (isset($venueData['location'])) {
                                try {
                                    $this->processAndStoreLocationData($venue, $venueData['location']);
                                    $results['locations_processed']++;
                                } catch (\Exception $e) {
                                    $results['warnings'][] = "Location processing warning for venue {$venue->name}: " . $e->getMessage();
                                }
                            }

                            // Process rating data with error handling
                            if (isset($venueData['rating']) || isset($venueData['ratings'])) {
                                try {
                                    $this->processAndStoreRatingData($venue, $venueData['rating'] ?? $venueData['ratings']);
                                    $results['ratings_processed']++;
                                } catch (\Exception $e) {
                                    $results['warnings'][] = "Rating processing warning for venue {$venue->name}: " . $e->getMessage();
                                }
                            }

                            // Process images with error handling
                            if (isset($venueData['images']) && is_array($venueData['images'])) {
                                foreach ($venueData['images'] as $imageIndex => $imageData) {
                                    try {
                                        if ($this->isValidImageData($imageData)) {
                                            $this->processAndStoreImageData($venue, $imageData);
                                            $results['images_processed']++;
                                        } else {
                                            $results['warnings'][] = "Image at index {$imageIndex} for venue {$venue->name} has insufficient data";
                                        }
                                    } catch (\Exception $e) {
                                        $results['warnings'][] = "Image processing warning for venue {$venue->name}: " . $e->getMessage();
                                    }
                                }
                            }

                            // Process opening hours with error handling
                            if (isset($venueData['openingHours']) && is_array($venueData['openingHours'])) {
                                foreach ($venueData['openingHours'] as $hourIndex => $hourData) {
                                    try {
                                        if ($this->isValidOpeningHourData($hourData)) {
                                            $this->processAndStoreOpeningHourData($venue, $hourData);
                                            $results['opening_hours_processed']++;
                                        } else {
                                            $results['warnings'][] = "Opening hour at index {$hourIndex} for venue {$venue->name} has insufficient data";
                                        }
                                    } catch (\Exception $e) {
                                        $results['warnings'][] = "Opening hour processing warning for venue {$venue->name}: " . $e->getMessage();
                                    }
                                }
                            }
                        } else {
                            $results['warnings'][] = "Venue at index {$venueIndex} could not be processed safely";
                        }

                    } catch (\Exception $e) {
                        $results['errors'][] = "Venue processing error: " . $e->getMessage();
                        Log::error('Venue processing error', [
                            'city' => $city->slug,
                            'venue_data' => $venueData,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        // Continue processing other venues
                    }
                }
            }

            // Process city-level procedures with error handling
            if (isset($apiData['procedures']) && is_array($apiData['procedures'])) {
                foreach ($apiData['procedures'] as $procedureIndex => $procedureData) {
                    try {
                        if ($this->isValidProcedureData($procedureData)) {
                            $this->processAndStoreProcedureData($city, $procedureData);
                            $results['procedures_processed']++;
                        } else {
                            $results['warnings'][] = "Procedure at index {$procedureIndex} has insufficient data";
                        }
                    } catch (\Exception $e) {
                        $results['errors'][] = "Procedure processing error: " . $e->getMessage();
                        Log::warning('Procedure processing error', [
                            'city' => $city->slug,
                            'procedure_data' => $procedureData,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            DB::commit();
            return $results;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Comprehensive city data processing failed', [
                'city' => $city->slug,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Process and store venue data with relationships
     */
    private function processAndStoreVenueData(City $city, array $venueData): Venue
    {
        $processedVenue = $this->processVenueData($venueData);
        $venueAttributes = $processedVenue->toArray();
        
        // Prepare unique identifier for venue
        $uniqueFields = ['city_id' => $city->id];
        
        if (!empty($venueAttributes['external_id'])) {
            $uniqueFields['external_id'] = $venueAttributes['external_id'];
        } else {
            // If no external_id, use name and city as unique identifier
            $uniqueFields['name'] = $venueAttributes['name'];
        }
        
        // Find or create venue
        $venue = Venue::updateOrCreate(
            $uniqueFields,
            array_merge($venueAttributes, [
                'city_id' => $city->id,
                'slug' => $this->generateSlug($venueAttributes['name']),
                'raw_data' => $venueData, // Store original data for debugging
            ])
        );

        // Update venue with API data using the new method
        $venue->updateFromApiData($venueData);

        return $venue;
    }

    /**
     * Process and store treatment data
     */
    private function processAndStoreTreatmentData(Venue $venue, array $treatmentData): Treatment
    {
        $processedTreatment = $this->processTreatmentData($treatmentData);
        $treatmentAttributes = $processedTreatment->toArray();
        
        $treatment = Treatment::updateOrCreate(
            [
                'external_id' => $treatmentAttributes['external_id'],
                'venue_id' => $venue->id,
            ],
            array_merge($treatmentAttributes, [
                'venue_id' => $venue->id,
                'slug' => $this->generateSlug($treatmentAttributes['name']),
            ])
        );

        // Update treatment with API data using the new method
        $treatment->updateFromApiData($treatmentData);

        // Sync treatment with city through many-to-many relationship
        if ($venue->city_id && !$treatment->cities()->where('city_id', $venue->city_id)->exists()) {
            $treatment->cities()->attach($venue->city_id);
        }

        return $treatment;
    }

    /**
     * Process and store service data
     */
    private function processAndStoreServiceData(Venue $venue, array $serviceData): void
    {
        $processedService = $this->processServiceData($serviceData);
        $serviceAttributes = $processedService->toArray();
        
        // Remove external_id from attributes since Service model doesn't have it
        unset($serviceAttributes['external_id']);
        
        $service = \App\Models\Service::updateOrCreate(
            [
                'name' => $serviceAttributes['name'],
                'venue_id' => $venue->id,
            ],
            array_merge($serviceAttributes, [
                'venue_id' => $venue->id,
                'city_id' => $venue->city_id,
                'slug' => $this->generateSlug($serviceAttributes['name']),
            ])
        );

        $this->processingStats['services_processed']++;
    }

    /**
     * Process service data from API response
     */
    public function processServiceData(array $rawData): ProcessedService
    {
        try {
            $serviceData = [
                'external_id' => $this->extractField($rawData, ['id', 'serviceId']),
                'name' => $this->extractField($rawData, ['name', 'serviceName', 'title']),
                'description' => $this->extractField($rawData, ['description', 'serviceDescription']),
                'category' => $this->extractField($rawData, ['category', 'categoryName', 'type']),
                'is_active' => $this->extractField($rawData, ['isActive', 'active']) ?? true,
            ];

            $unknownFields = $this->extractUnknownFields($rawData, [
                'id', 'serviceId', 'name', 'serviceName', 'title',
                'description', 'serviceDescription', 'category', 'categoryName', 'type',
                'isActive', 'active'
            ]);

            return new ProcessedService($serviceData, $unknownFields);

        } catch (\Exception $e) {
            $this->processingStats['validation_errors']++;
            Log::error('Service data processing error', [
                'error' => $e->getMessage(),
                'raw_data' => $rawData
            ]);
            throw $e;
        }
    }

    /**
     * Process and store location data
     */
    private function processAndStoreLocationData(Venue $venue, array $locationData): void
    {
        $processedLocation = $this->processLocationData($locationData);
        $locationAttributes = $processedLocation->toArray();
        
        // Remove external_id since locations table doesn't have this column
        unset($locationAttributes['external_id']);
        
        Location::updateOrCreate(
            ['venue_id' => $venue->id],
            array_merge($locationAttributes, [
                'venue_id' => $venue->id,
                'city_id' => $venue->city_id,
            ])
        );
    }

    /**
     * Process and store rating data
     */
    private function processAndStoreRatingData(Venue $venue, array $ratingData): void
    {
        $processedRating = $this->processRatingData($ratingData);
        $ratingAttributes = $processedRating->toArray();
        
        Rating::updateOrCreate(
            ['venue_id' => $venue->id],
            array_merge($ratingAttributes, [
                'venue_id' => $venue->id,
                'city_id' => $venue->city_id,
            ])
        );
    }

    /**
     * Process and store image data
     */
    private function processAndStoreImageData(Venue $venue, array $imageData): void
    {
        $processedImage = $this->processImageData($imageData);
        $imageAttributes = $processedImage->toArray();
        
        Image::updateOrCreate(
            [
                'venue_id' => $venue->id,
                'url' => $imageAttributes['url'],
            ],
            array_merge($imageAttributes, [
                'venue_id' => $venue->id,
                'city_id' => $venue->city_id,
                'path' => $imageAttributes['url'], // Map URL to path field
            ])
        );
    }

    /**
     * Process and store opening hour data
     */
    private function processAndStoreOpeningHourData(Venue $venue, array $hourData): void
    {
        $processedHour = $this->processOpeningHourData($hourData);
        $hourAttributes = $processedHour->toArray();
        
        OpeningHour::updateOrCreate(
            [
                'venue_id' => $venue->id,
                'day_of_week' => $hourAttributes['day_of_week'],
            ],
            array_merge($hourAttributes, [
                'venue_id' => $venue->id,
                'city_id' => $venue->city_id,
                'is_open' => !$hourAttributes['is_closed'],
            ])
        );
    }

    /**
     * Process and store procedure data
     */
    private function processAndStoreProcedureData(City $city, array $procedureData): void
    {
        $processedProcedure = $this->processProcedureData($procedureData);
        $procedureAttributes = $processedProcedure->toArray();
        
        $procedure = Procedure::updateOrCreate(
            ['external_id' => $procedureAttributes['external_id']],
            array_merge($procedureAttributes, [
                'slug' => $this->generateSlug($procedureAttributes['name']),
            ])
        );

        // Attach to city if not already attached
        if (!$city->procedures()->where('procedure_id', $procedure->id)->exists()) {
            $city->procedures()->attach($procedure->id);
        }

        $this->processingStats['procedures_processed']++;
    }

    /**
     * Process procedure data from API response
     */
    public function processProcedureData(array $rawData): ProcessedProcedure
    {
        try {
            $procedureData = [
                'external_id' => $this->extractField($rawData, ['id', 'procedureId']),
                'name' => $this->extractField($rawData, ['name', 'procedureName', 'title']),
                'description' => $this->extractField($rawData, ['description', 'procedureDescription']),
                'category' => $this->extractField($rawData, ['category', 'categoryName', 'type']),
                'is_active' => $this->extractField($rawData, ['isActive', 'active']) ?? true,
            ];

            $unknownFields = $this->extractUnknownFields($rawData, [
                'id', 'procedureId', 'name', 'procedureName', 'title',
                'description', 'procedureDescription', 'category', 'categoryName', 'type',
                'isActive', 'active'
            ]);

            return new ProcessedProcedure($procedureData, $unknownFields);

        } catch (\Exception $e) {
            $this->processingStats['validation_errors']++;
            Log::error('Procedure data processing error', [
                'error' => $e->getMessage(),
                'raw_data' => $rawData
            ]);
            throw $e;
        }
    }

    /**
     * Generate slug from name
     */
    private function generateSlug($name): string
    {
        // Handle non-string names gracefully
        if (!is_string($name)) {
            if (is_array($name)) {
                $name = 'invalid-name-array';
            } else {
                $name = 'invalid-name-' . gettype($name);
            }
        }
        
        $baseSlug = Str::slug($name);
        
        // Add timestamp to make it more unique, especially for tests
        $slug = $baseSlug . '-' . time() . '-' . uniqid();
        
        return $slug;
    }

    /**
     * Manage relationships between entities
     */
    public function manageEntityRelationships(City $city, array $relationshipData): void
    {
        try {
            // Manage venue-treatment relationships
            if (isset($relationshipData['venue_treatments'])) {
                foreach ($relationshipData['venue_treatments'] as $venueId => $treatmentIds) {
                    $venue = Venue::where('external_id', $venueId)->where('city_id', $city->id)->first();
                    if ($venue) {
                        $treatments = Treatment::whereIn('external_id', $treatmentIds)->get();
                        $venue->treatments()->sync($treatments->pluck('id'));
                    }
                }
            }

            // Manage venue-procedure relationships
            if (isset($relationshipData['venue_procedures'])) {
                foreach ($relationshipData['venue_procedures'] as $venueId => $procedureIds) {
                    $venue = Venue::where('external_id', $venueId)->where('city_id', $city->id)->first();
                    if ($venue) {
                        $procedures = Procedure::whereIn('external_id', $procedureIds)->get();
                        $venue->procedures()->sync($procedures->pluck('id'));
                    }
                }
            }

            // Manage city-procedure relationships
            if (isset($relationshipData['city_procedures'])) {
                $procedures = Procedure::whereIn('external_id', $relationshipData['city_procedures'])->get();
                $city->procedures()->sync($procedures->pluck('id'));
            }

        } catch (\Exception $e) {
            Log::error('Entity relationship management error', [
                'city' => $city->slug,
                'error' => $e->getMessage(),
                'relationship_data' => $relationshipData
            ]);
            throw $e;
        }
    }

    /**
     * Validate API data structure
     */
    private function validateApiDataStructure(array $apiData): ValidationResult
    {
        $errors = [];

        if (empty($apiData)) {
            $errors[] = 'API data is empty';
            return new ValidationResult(false, $errors);
        }

        if (!isset($apiData['venues']) && !isset($apiData['procedures'])) {
            $errors[] = 'API data must contain either venues or procedures';
        }

        if (isset($apiData['venues']) && !is_array($apiData['venues'])) {
            $errors[] = 'Venues data must be an array';
        }

        if (isset($apiData['procedures']) && !is_array($apiData['procedures'])) {
            $errors[] = 'Procedures data must be an array';
        }

        return new ValidationResult(empty($errors), $errors);
    }

    /**
     * Check if venue data is valid for processing
     */
    private function isValidVenueData($venueData): bool
    {
        if (!is_array($venueData)) {
            return false;
        }
        
        // Minimum requirement: must have a name
        return !empty($venueData['name']) || !empty($venueData['venueName']) || !empty($venueData['businessName']);
    }

    /**
     * Check if treatment data is valid for processing
     */
    private function isValidTreatmentData($treatmentData): bool
    {
        if (!is_array($treatmentData)) {
            return false;
        }
        
        // Minimum requirement: must have a name
        return !empty($treatmentData['name']) || !empty($treatmentData['treatmentName']) || !empty($treatmentData['serviceName']);
    }

    /**
     * Check if service data is valid for processing
     */
    private function isValidServiceData($serviceData): bool
    {
        if (!is_array($serviceData)) {
            return false;
        }
        
        // Minimum requirement: must have a name
        return !empty($serviceData['name']) || !empty($serviceData['serviceName']) || !empty($serviceData['title']);
    }

    /**
     * Check if procedure data is valid for processing
     */
    private function isValidProcedureData($procedureData): bool
    {
        if (!is_array($procedureData)) {
            return false;
        }
        
        // Minimum requirement: must have a name
        return !empty($procedureData['name']) || !empty($procedureData['procedureName']) || !empty($procedureData['title']);
    }

    /**
     * Check if image data is valid for processing
     */
    private function isValidImageData($imageData): bool
    {
        if (!is_array($imageData)) {
            return false;
        }
        
        // Minimum requirement: must have a URL
        return !empty($imageData['url']) || !empty($imageData['uris']);
    }

    /**
     * Check if opening hour data is valid for processing
     */
    private function isValidOpeningHourData($hourData): bool
    {
        if (!is_array($hourData)) {
            return false;
        }
        
        // Minimum requirement: must have day information
        return !empty($hourData['dayOfWeek']) || !empty($hourData['day']);
    }

    /**
     * Process and store venue data safely with enhanced error handling
     */
    private function processAndStoreVenueDataSafely(City $city, array $venueData): ?Venue
    {
        try {
            $processedVenue = $this->processVenueData($venueData);
            $venueAttributes = $processedVenue->toArray();
            
            // Prepare unique identifier for venue
            $uniqueFields = ['city_id' => $city->id];
            
            if (!empty($venueAttributes['external_id'])) {
                $uniqueFields['external_id'] = $venueAttributes['external_id'];
            } else {
                // If no external_id, use name and city as unique identifier
                $uniqueFields['name'] = $venueAttributes['name'];
            }
            
            // Find or create venue
            $venue = Venue::updateOrCreate(
                $uniqueFields,
                array_merge($venueAttributes, [
                    'city_id' => $city->id,
                    'slug' => $this->generateSlug($venueAttributes['name']),
                    'raw_data' => $venueData, // Store original data for debugging
                ])
            );

            // Update venue with API data using the new method with error handling
            try {
                $venue->updateFromApiData($venueData);
            } catch (\Exception $e) {
                Log::warning('Venue API data update failed', [
                    'venue_id' => $venue->id,
                    'error' => $e->getMessage(),
                    'venue_data' => $venueData
                ]);
                // Continue without failing the entire process
            }

            return $venue;

        } catch (\Exception $e) {
            Log::error('Safe venue processing failed', [
                'city' => $city->slug,
                'venue_data' => $venueData,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
}

// Data Transfer Objects for processed data

class ProcessedVenue
{
    private array $data;
    private ?ProcessedLocation $locationData;
    private ?ProcessedRating $ratingData;
    private array $imagesData;
    private array $openingHoursData;
    private array $unknownFields;

    public function __construct(
        array $data,
        ?ProcessedLocation $locationData = null,
        ?ProcessedRating $ratingData = null,
        array $imagesData = [],
        array $openingHoursData = [],
        array $unknownFields = []
    ) {
        $this->data = $data;
        $this->locationData = $locationData;
        $this->ratingData = $ratingData;
        $this->imagesData = $imagesData;
        $this->openingHoursData = $openingHoursData;
        $this->unknownFields = $unknownFields;
    }

    public function toArray(): array
    {
        return array_merge($this->data, ['unknown_fields' => $this->unknownFields]);
    }

    public function getLocationData(): ?ProcessedLocation { return $this->locationData; }
    public function getRatingData(): ?ProcessedRating { return $this->ratingData; }
    public function getImagesData(): array { return $this->imagesData; }
    public function getOpeningHoursData(): array { return $this->openingHoursData; }
}

class ProcessedTreatment
{
    private array $data;
    private array $unknownFields;

    public function __construct(array $data, array $unknownFields = [])
    {
        $this->data = $data;
        $this->unknownFields = $unknownFields;
    }

    public function toArray(): array
    {
        return array_merge($this->data, ['unknown_fields' => $this->unknownFields]);
    }
}

class ProcessedLocation
{
    private array $data;
    private array $unknownFields;

    public function __construct(array $data, array $unknownFields = [])
    {
        $this->data = $data;
        $this->unknownFields = $unknownFields;
    }

    public function toArray(): array
    {
        return array_merge($this->data, ['unknown_fields' => $this->unknownFields]);
    }
}

class ProcessedRating
{
    private array $data;
    private array $unknownFields;

    public function __construct(array $data, array $unknownFields = [])
    {
        $this->data = $data;
        $this->unknownFields = $unknownFields;
    }

    public function toArray(): array
    {
        return array_merge($this->data, ['unknown_fields' => $this->unknownFields]);
    }
}

class ProcessedImage
{
    private array $data;
    private array $unknownFields;

    public function __construct(array $data, array $unknownFields = [])
    {
        $this->data = $data;
        $this->unknownFields = $unknownFields;
    }

    public function toArray(): array
    {
        return array_merge($this->data, ['unknown_fields' => $this->unknownFields]);
    }
}

class ProcessedOpeningHour
{
    private array $data;
    private array $unknownFields;

    public function __construct(array $data, array $unknownFields = [])
    {
        $this->data = $data;
        $this->unknownFields = $unknownFields;
    }

    public function toArray(): array
    {
        return array_merge($this->data, ['unknown_fields' => $this->unknownFields]);
    }
}

class ProcessedService
{
    private array $data;
    private array $unknownFields;

    public function __construct(array $data, array $unknownFields = [])
    {
        $this->data = $data;
        $this->unknownFields = $unknownFields;
    }

    public function toArray(): array
    {
        return array_merge($this->data, ['unknown_fields' => $this->unknownFields]);
    }
}

class ProcessedProcedure
{
    private array $data;
    private array $unknownFields;

    public function __construct(array $data, array $unknownFields = [])
    {
        $this->data = $data;
        $this->unknownFields = $unknownFields;
    }

    public function toArray(): array
    {
        return array_merge($this->data, ['unknown_fields' => $this->unknownFields]);
    }
}

class ValidationResult
{
    private bool $valid;
    private array $errors;

    public function __construct(bool $valid, array $errors = [])
    {
        $this->valid = $valid;
        $this->errors = $errors;
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}