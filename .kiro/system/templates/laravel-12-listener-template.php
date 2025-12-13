<?php

namespace App\Listeners;

use App\Events\{{EventName}};
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class {{ListenerName}} implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the queued listener may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the queued listener can run.
     */
    public int $timeout = 60;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        // Listener initialization
    }

    /**
     * Handle the event.
     */
    public function handle({{EventName}} $event): void
    {
        try {
            Log::info('Processing event', [
                'event' => get_class($event),
                'user_id' => $event->userId,
                'listener' => self::class
            ]);

            // Handle the event logic here
            
            Log::info('Event processed successfully', [
                'event' => get_class($event),
                'user_id' => $event->userId,
                'listener' => self::class
            ]);
        } catch (\Exception $e) {
            Log::error('Event processing failed', [
                'event' => get_class($event),
                'user_id' => $event->userId,
                'listener' => self::class,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed({{EventName}} $event, \Throwable $exception): void
    {
        Log::error('Event listener failed permanently', [
            'event' => get_class($event),
            'user_id' => $event->userId,
            'listener' => self::class,
            'error' => $exception->getMessage()
        ]);
    }
}