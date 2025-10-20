<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SitemapUrl extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'original_url',
        'path',
        'browse_uri',
        'treatment_slug',
        'treatment_name',
        'offer_type_slug',
        'offer_type_name',
        'location_slug',
        'location_name',
        'is_processed',
        'is_valid',
        'venues_found',
        'api_requests',
        'pages_processed',
        'last_processed_at',
        'downloaded_at',
        'api_response',
        'error_message',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_processed' => 'boolean',
        'is_valid' => 'boolean',
        'venues_found' => 'integer',
        'api_requests' => 'integer',
        'pages_processed' => 'integer',
        'last_processed_at' => 'datetime',
        'downloaded_at' => 'datetime',
    ];
    
    /**
     * Scope a query to only include unprocessed URLs.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnprocessed($query)
    {
        return $query->where('is_processed', false)
                    ->where('is_valid', true);
    }
    
    /**
     * Scope a query to only include valid URLs.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeValid($query)
    {
        return $query->where('is_valid', true);
    }
    
    /**
     * Scope a query to only include URLs for a specific treatment.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $treatmentSlug
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForTreatment($query, $treatmentSlug)
    {
        return $query->where('treatment_slug', $treatmentSlug);
    }
    
    /**
     * Scope a query to only include URLs for a specific location.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $locationSlug
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForLocation($query, $locationSlug)
    {
        return $query->where('location_slug', $locationSlug);
    }
    
    /**
     * Get the API endpoint URL for this sitemap URL.
     *
     * @param  int  $page
     * @return string
     */
    public function getApiEndpoint($page = 0)
    {
        $baseUrl = 'https://www.treatwell.lt/api/v1/page/browse';
        return "{$baseUrl}?page={$page}&currentBrowseUri=" . urlencode($this->browse_uri);
    }
    
    /**
     * Mark this URL as processed.
     *
     * @param  int  $venuesFound
     * @param  int  $apiRequests
     * @param  int  $pagesProcessed
     * @return bool
     */
    public function markAsProcessed($venuesFound = 0, $apiRequests = 0, $pagesProcessed = 0)
    {
        $this->is_processed = true;
        $this->venues_found = $venuesFound;
        $this->api_requests = $apiRequests;
        $this->pages_processed = $pagesProcessed;
        $this->last_processed_at = now();
        
        return $this->save();
    }
    
    /**
     * Mark this URL as invalid.
     *
     * @param  string  $errorMessage
     * @return bool
     */
    public function markAsInvalid($errorMessage = null)
    {
        $this->is_valid = false;
        $this->error_message = $errorMessage;
        
        return $this->save();
    }
}
