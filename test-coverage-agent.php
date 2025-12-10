#!/usr/bin/env php
<?php

/**
 * Test Coverage Agent
 * Monitors source files and ensures test coverage for changes
 */

require __DIR__.'/vendor/autoload.php';

class TestCoverageAgent
{
    private array $fileHashes = [];

    private string $appPath;

    private string $testPath;

    public function __construct()
    {
        $this->appPath = __DIR__.'/app';
        $this->testPath = __DIR__.'/tests';
        $this->loadFileHashes();
    }

    public function run(): void
    {
        echo "Test Coverage Agent started...\n";
        echo "Monitoring: {$this->appPath}\n\n";

        while (true) {
            $this->checkForChanges();
            sleep(2);
        }
    }

    private function checkForChanges(): void
    {
        $files = $this->getSourceFiles();

        foreach ($files as $file) {
            $currentHash = md5_file($file);
            $previousHash = $this->fileHashes[$file] ?? null;

            if ($currentHash !== $previousHash) {
                $this->handleFileChange($file);
                $this->fileHashes[$file] = $currentHash;
                $this->saveFileHashes();
            }
        }
    }

    private function handleFileChange(string $file): void
    {
        echo "\n[".date('H:i:s').'] File changed: '.basename($file)."\n";

        $methods = $this->extractMethods($file);
        echo 'Found '.count($methods)." methods\n";

        $testFile = $this->getTestFile($file);
        $testExists = file_exists($testFile);

        if (! $testExists) {
            echo "⚠ No test file found\n";
            $this->generateTestFile($file, $testFile, $methods);
        } else {
            $this->checkTestCoverage($file, $testFile, $methods);
        }

        $this->runTests($testFile);
    }

    private function extractMethods(string $file): array
    {
        $content = file_get_contents($file);
        $methods = [];

        preg_match_all('/(?:public|protected|private)\s+function\s+(\w+)\s*\(/', $content, $matches);

        foreach ($matches[1] as $method) {
            if (! in_array($method, ['__construct', '__destruct'])) {
                $methods[] = $method;
            }
        }

        return $methods;
    }

    private function getTestFile(string $sourceFile): string
    {
        $relativePath = str_replace($this->appPath, '', $sourceFile);
        $testPath = $this->testPath.'/Unit'.dirname($relativePath);
        $fileName = basename($sourceFile, '.php').'Test.php';

        return $testPath.'/'.$fileName;
    }

    private function checkTestCoverage(string $sourceFile, string $testFile, array $methods): void
    {
        $testContent = file_get_contents($testFile);
        $missingTests = [];

        foreach ($methods as $method) {
            $testMethodName = 'test_'.strtolower(preg_replace('/([A-Z])/', '_$1', $method));
            if (! str_contains($testContent, $testMethodName)) {
                $missingTests[] = $method;
            }
        }

        if (! empty($missingTests)) {
            echo '⚠ Missing tests for: '.implode(', ', $missingTests)."\n";
            $this->appendTests($testFile, $missingTests, $sourceFile);
        } else {
            echo "✓ All methods have tests\n";
        }
    }

    private function generateTestFile(string $sourceFile, string $testFile, array $methods): void
    {
        $className = basename($sourceFile, '.php');
        $namespace = $this->getNamespace($sourceFile);
        $testDir = dirname($testFile);

        if (! is_dir($testDir)) {
            mkdir($testDir, 0755, true);
        }

        $content = "<?php\n\nnamespace Tests\\Unit".str_replace('App', '', $namespace).";\n\n";
        $content .= "use {$namespace}\\{$className};\n";
        $content .= "use Tests\\TestCase;\n\n";
        $content .= "class {$className}Test extends TestCase\n{\n";

        foreach ($methods as $method) {
            $content .= $this->generateTestMethod($method);
        }

        $content .= "}\n";

        file_put_contents($testFile, $content);
        echo '✓ Generated test file: '.basename($testFile)."\n";
    }

    private function appendTests(string $testFile, array $methods, string $sourceFile): void
    {
        $content = file_get_contents($testFile);
        $newTests = '';

        foreach ($methods as $method) {
            $newTests .= $this->generateTestMethod($method);
        }

        $content = rtrim($content);
        $content = preg_replace('/}\s*$/', $newTests."}\n", $content);

        file_put_contents($testFile, $content);
        echo "✓ Added missing tests\n";
    }

    private function generateTestMethod(string $method): string
    {
        $testName = 'test_'.strtolower(preg_replace('/([A-Z])/', '_$1', $method));

        return "\n    public function {$testName}(): void\n    {\n        \$this->markTestIncomplete('Test for {$method} needs implementation');\n    }\n";
    }

    private function getNamespace(string $file): string
    {
        $content = file_get_contents($file);
        preg_match('/namespace\s+([^;]+);/', $content, $matches);

        return $matches[1] ?? 'App';
    }

    private function runTests(string $testFile): void
    {
        if (! file_exists($testFile)) {
            return;
        }

        echo "Running tests...\n";
        $output = shell_exec('cd '.__DIR__." && php artisan test {$testFile} 2>&1");
        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            if (str_contains($line, 'PASS') || str_contains($line, 'FAIL') || str_contains($line, 'Tests:')) {
                echo $line."\n";
            }
        }
    }

    private function getSourceFiles(): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->appPath)
        );

        $files = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function loadFileHashes(): void
    {
        $cacheFile = __DIR__.'/storage/test-coverage-cache.json';
        if (file_exists($cacheFile)) {
            $this->fileHashes = json_decode(file_get_contents($cacheFile), true) ?? [];
        }
    }

    private function saveFileHashes(): void
    {
        $cacheFile = __DIR__.'/storage/test-coverage-cache.json';
        file_put_contents($cacheFile, json_encode($this->fileHashes, JSON_PRETTY_PRINT));
    }
}

$agent = new TestCoverageAgent;
$agent->run();
