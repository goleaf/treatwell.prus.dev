<?php

namespace App\Console\Commands;

use App\Services\TreatwellSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SyncTreatwellVenuesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'venues:sync
        {--cities= : Comma separated list of Treatwell city slugs}
        {--procedures= : Comma separated list of procedure slugs}
        {--page-size=20 : Page size for API pagination}
        {--max-pages=0 : Maximum number of pages to fetch per combination (0 = all)}
        {--api : Only import from the Treatwell API}
        {--json= : Path to a JSON file or directory (comma separated for multiple)}
        {--replace : Clear existing venue data before syncing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronise Treatwell venue data from the API and/or stored JSON payloads.';

    public function __construct(private readonly TreatwellSyncService $syncService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $processApi = $this->option('api');
        $jsonOption = $this->option('json');
        $processJson = $jsonOption !== null && $jsonOption !== '';

        if (! $processApi && ! $processJson) {
            $processApi = true;
            $processJson = $jsonOption !== null;
        }

        if ($this->option('replace')) {
            $this->warn('Clearing existing venue related data...');
            $this->syncService->resetData();
            $this->info('Existing data cleared.');
        }

        if ($processApi) {
            $cities = $this->parseCsvOption('cities', config('treatwell.default_cities', []));
            $procedures = $this->parseCsvOption('procedures', []);
            $pageSize = (int) $this->option('page-size');
            $maxPages = (int) $this->option('max-pages');

            $this->line('');
            $this->info('Starting Treatwell API synchronisation...');
            $stats = $this->syncService->importFromApi($cities, $procedures, $pageSize, $maxPages > 0 ? $maxPages : null);
            $this->displayStats('API import completed', $stats);
        }

        if ($processJson) {
            $paths = $this->parseCsvPaths($jsonOption);

            if (empty($paths)) {
                $defaultPath = config('treatwell.default_json_path');
                if ($defaultPath) {
                    $paths[] = $defaultPath;
                }
            }

            $paths = array_filter($paths, fn ($path) => $path !== null);

            if (empty($paths)) {
                $this->warn('No JSON paths supplied or found. Skipping JSON import.');
            } else {
                $this->line('');
                $this->info('Processing stored JSON responses...');
                $stats = $this->syncService->importFromJsonFiles($paths);
                $this->displayStats('JSON import completed', $stats);
            }
        }

        if (! $processApi && ! $processJson) {
            $this->info('Nothing to import. Provide --api and/or --json options.');
        }

        return Command::SUCCESS;
    }

    /**
     * Parse a comma separated option into a cleaned array of slugs.
     *
     * @param  array<int, string>  $default
     * @return array<int, string>
     */
    private function parseCsvOption(string $option, array $default = []): array
    {
        $value = $this->option($option);

        if ($value === null || $value === '') {
            return $default;
        }

        $items = array_map('trim', explode(',', (string) $value));

        return array_values(array_filter($items, fn ($item) => $item !== ''));
    }

    /**
     * Parse JSON path inputs into absolute paths.
     *
     * @return array<int, string>
     */
    private function parseCsvPaths(?string $input): array
    {
        if ($input === null) {
            return [];
        }

        $paths = array_map('trim', explode(',', $input));

        return array_values(array_filter($paths, fn ($path) => $path !== ''));
    }

    /**
     * Display stats in a consistent format.
     *
     * @param  array<string, int>  $stats
     */
    private function displayStats(string $title, array $stats): void
    {
        $this->info($title.':');
        foreach ($stats as $key => $value) {
            $label = Str::title(str_replace('_', ' ', $key));
            $this->line(sprintf('  - %s: %s', $label, number_format($value)));
        }
    }
}
