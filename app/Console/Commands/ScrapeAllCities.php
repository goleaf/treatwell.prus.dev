<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ScrapeAllCities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:all-cities {--limit-pages= : Limit pages per city}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape Treatwell data for all Lithuanian cities';

    /**
     * List of Lithuanian cities to scrape
     */
    protected $cities = [
        'vilnius-lt',
        'kaunas-lt',
        'klaipeda-lt',
        'siauliai-lt',
        'panevezys-lt',
        'alytus-lt',
        'marijampole-lt',
        'mazeikiai-lt',
        'jonava-lt',
        'utena-lt',
        'kedainiai-lt',
        'telsiai-lt',
        'visaginas-lt',
        'taurage-lt',
        'ukmerge-lt',
        'plunge-lt',
        'kretinga-lt',
        'palanga-lt',
        'radviliškis-lt',
        'druskininkai-lt',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limitPages = $this->option('limit-pages');

        $this->info('Starting scraping for all Lithuanian cities...');

        // Try to get more cities from the Treatwell API response
        try {
            $response = Http::get('https://www.treatwell.lt/api/v1/page/browse', [
                'page' => '1',
                'currentBrowseUri' => '/salonai/kur-lietuva/',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Check if there are location breadcrumbs or other city references
                if (isset($data['locationBreadcrumbs'])) {
                    $this->info('Found location breadcrumbs in the API response');

                    foreach ($data['locationBreadcrumbs'] as $breadcrumb) {
                        if (isset($breadcrumb['entityId']) && isset($breadcrumb['uri']['desktopUri'])) {
                            $uri = $breadcrumb['uri']['desktopUri'];
                            if (preg_match('/\/salonai\/kur-([^\/]+)\/$/', $uri, $matches)) {
                                $citySlug = $matches[1];
                                if (! in_array($citySlug, $this->cities)) {
                                    $this->cities[] = $citySlug;
                                    $this->info("Added city from breadcrumbs: $citySlug");
                                }
                            }
                        }
                    }
                }

                // Look for cities in filters section
                if (isset($data['filters']) && isset($data['filters']['location']) && isset($data['filters']['location']['options'])) {
                    $this->info('Found location filters in the API response');

                    foreach ($data['filters']['location']['options'] as $option) {
                        if (isset($option['normalisedName']) && ! in_array($option['normalisedName'], $this->cities)) {
                            $this->cities[] = $option['normalisedName'];
                            $this->info("Added city from filters: {$option['normalisedName']}");
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->warn('Could not fetch additional cities from the API: '.$e->getMessage());
        }

        $this->info('Will process '.count($this->cities).' cities');
        $this->newLine();

        // Call the scrape:treatwell-all command which will process all cities
        config(['scraping.cities' => $this->cities]);

        $exitCode = $this->call('scrape:treatwell-all');

        app('config')->offsetUnset('scraping.cities');

        if ($exitCode !== 0) {
            $this->warn('There was an issue running the scrape:treatwell-all command');
        }

        $this->info('============================');
        $this->info('All cities have been scraped!');

        return 0;
    }
}
