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
        $schedule->command('venues:sync --api')
            ->everyFifteenMinutes()
            ->withoutOverlapping();

        $defaultJsonPath = config('treatwell.default_json_path');
        if ($defaultJsonPath) {
            $schedule->command('venues:sync --json='.$defaultJsonPath)
                ->hourly()
                ->withoutOverlapping();
        }
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
        \App\Console\Commands\SyncTreatwellVenuesCommand::class,
    ];
}
