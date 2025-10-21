<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    private static bool $envEnsured = false;

    public function createApplication()
    {
        $basePath = dirname(__DIR__);
        $envPath = $basePath.'/.env';

        if (! self::$envEnsured && ! file_exists($envPath)) {
            $examplePath = $basePath.'/.env.example';
            $contents = '';

            if (file_exists($examplePath)) {
                $contents = (string) file_get_contents($examplePath);
            }

            if (trim($contents) === '') {
                $contents = "APP_ENV=testing\n";
            }

            file_put_contents($envPath, $contents);
            self::$envEnsured = true;

            register_shutdown_function(static function () use ($envPath) {
                if (file_exists($envPath)) {
                    @unlink($envPath);
                }
            });
        }

        $app = require $basePath.'/bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
