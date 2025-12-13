<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class {{CommandName}} extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = '{{command:name}}
                            {argument : Description of the argument}
                            {--option= : Description of the option}
                            {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $this->info('Starting {{command:name}} command...');

            // Get arguments and options
            $argument = $this->argument('argument');
            $option = $this->option('option');
            $force = $this->option('force');

            // Confirmation for destructive operations
            if (!$force && !$this->confirm('Are you sure you want to proceed?')) {
                $this->info('Operation cancelled.');
                return self::FAILURE;
            }

            // Progress bar for long operations
            $items = collect(range(1, 100)); // Replace with actual items
            $bar = $this->output->createProgressBar($items->count());
            $bar->start();

            foreach ($items as $item) {
                // Process item
                sleep(0.1); // Simulate work
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();

            Log::info('Command executed successfully', [
                'command' => self::class,
                'argument' => $argument,
                'option' => $option
            ]);

            $this->info('Command completed successfully!');
            return self::SUCCESS;

        } catch (\Exception $e) {
            Log::error('Command failed', [
                'command' => self::class,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->error('Command failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}