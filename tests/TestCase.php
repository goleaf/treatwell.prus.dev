<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create the application.
     */
    public function createApplication(): Application
    {
        $basePath = Application::inferBasePath();
        $envPath = $basePath.'/.env';

        if (! file_exists($envPath)) {
            file_put_contents($envPath, 'APP_ENV=testing'.PHP_EOL);
        }

        $app = require $basePath.'/bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
