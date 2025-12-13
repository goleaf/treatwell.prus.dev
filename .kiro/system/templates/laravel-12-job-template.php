<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class {{JobName}} implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly string $data,
        public readonly int $userId
    ) {
        // Constructor logic here
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Job logic here
            Log::info('Job started', [
                'job' => self::class,
                'user_id' => $this->userId,
                'data' => $this->data
            ]);
            
            // Process the job
            
            Log::info('Job completed successfully', [
                'job' => self::class,
                'user_id' => $this->userId
            ]);
        } catch (\Exception $e) {
            Log::error('Job failed', [
                'job' => self::class,
                'user_id' => $this->userId,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Job failed permanently', [
            'job' => self::class,
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
        
        // Send notification or cleanup
    }
}