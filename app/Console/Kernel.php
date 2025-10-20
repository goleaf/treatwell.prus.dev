<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * These schedules are used to run console commands.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('venues:fetch --fetch-api --fetch-sitemap')->weekly()->sundays()->at('01:00');

        // Run data validation weekly
        $schedule->command('venues:validate --report=json --fix')->weekly()->mondays()->at('03:00');

        // Export venue data to JSON monthly for backup
        $schedule->command('venues:export-json --format=single --pretty')->monthly()->at('05:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\FetchVenuesCommand::class,
        \App\Console\Commands\ProcessJsonFilesCommand::class,
        \App\Console\Commands\ProcessAllJsonFilesCommand::class,
        \App\Console\Commands\ParseVenueUrlCommand::class,
        \App\Console\Commands\SimpleSaveVenueCommand::class,
        \App\Console\Commands\SaveSingleVenueCommand::class,
        \App\Console\Commands\ScrapeAllCities::class,
        \App\Console\Commands\UpdateCityRelationships::class,
        \App\Console\Commands\ScrapeTreatwellAll::class,
        // Newly added commands
        \App\Console\Commands\ExportVenuesToJsonCommand::class,
        \App\Console\Commands\ConvertXmlToJsonCommand::class,
        \App\Console\Commands\ValidateVenuesCommand::class,
    ];
}
