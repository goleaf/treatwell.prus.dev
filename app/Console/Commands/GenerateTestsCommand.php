<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ReflectionClass;

class GenerateTestsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:tests {--force : Overwrite existing tests}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate tests for all PHP files in the app directory';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $appPath = app_path();
        $testPath = base_path('tests');
        $force = $this->option('force');

        $this->info('Scanning app directory for PHP files...');

        $files = $this->getPhpFiles($appPath);
        $generated = 0;
        $skipped = 0;

        foreach ($files as $file) {
            $classInfo = $this->getClassInfo($file);

            if ($classInfo === null) {
                continue;
            }

            $testFilePath = $this->getTestPath($classInfo, $testPath);

            if (File::exists($testFilePath) && ! $force) {
                $this->line("Skipping: {$classInfo['name']} (test already exists)");
                $skipped++;

                continue;
            }

            $this->generateTest($classInfo, $testFilePath);
            $generated++;
        }

        $this->info("Generated {$generated} test files. Skipped {$skipped} existing tests.");

        return Command::SUCCESS;
    }

    /**
     * Get all PHP files recursively from a directory.
     */
    protected function getPhpFiles(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Get class information from a PHP file.
     *
     * @return array<string, mixed>|null
     */
    protected function getClassInfo(string $filePath): ?array
    {
        try {
            $content = File::get($filePath);

            // Extract namespace
            if (! preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatch)) {
                return null;
            }
            $namespace = $namespaceMatch[1];

            // Extract class name
            if (! preg_match('/class\s+(\w+)/', $content, $classMatch)) {
                return null;
            }
            $className = $classMatch[1];

            $fullClassName = $namespace.'\\'.$className;

            // Skip if class doesn't exist or can't be loaded
            if (! class_exists($fullClassName) && ! trait_exists($fullClassName) && ! interface_exists($fullClassName)) {
                return null;
            }

            $reflection = new ReflectionClass($fullClassName);

            // Determine test type based on class location and type
            $testType = $this->determineTestType($filePath, $reflection);
            $testNamespace = $this->getTestNamespace($namespace, $testType);

            return [
                'name' => $className,
                'namespace' => $namespace,
                'fullName' => $fullClassName,
                'testType' => $testType,
                'testNamespace' => $testNamespace,
                'reflection' => $reflection,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Determine the test type based on file path and class.
     */
    protected function determineTestType(string $filePath, ReflectionClass $reflection): string
    {
        if (str_contains($filePath, 'Models')) {
            return 'Unit';
        }

        if (str_contains($filePath, 'Http/Controllers')) {
            if (str_contains($filePath, 'Api')) {
                return 'Feature';
            }

            return 'Unit';
        }

        if (str_contains($filePath, 'Console/Commands')) {
            return 'Unit';
        }

        if (str_contains($filePath, 'Services')) {
            return 'Unit';
        }

        if (str_contains($filePath, 'Repositories')) {
            return 'Unit';
        }

        if (str_contains($filePath, 'Http/Requests')) {
            return 'Unit';
        }

        if (str_contains($filePath, 'Http/Middleware')) {
            return 'Unit';
        }

        if (str_contains($filePath, 'Providers')) {
            return 'Unit';
        }

        if (str_contains($filePath, 'Filament')) {
            return 'Unit';
        }

        return 'Unit';
    }

    /**
     * Get test namespace from class namespace.
     */
    protected function getTestNamespace(string $namespace, string $testType): string
    {
        $namespace = str_replace('App\\', 'Tests\\'.$testType.'\\', $namespace);

        return $namespace;
    }

    /**
     * Get test file path.
     */
    protected function getTestPath(array $classInfo, string $baseTestPath): string
    {
        $relativePath = str_replace('App\\', '', $classInfo['namespace']);
        $relativePath = str_replace('\\', '/', $relativePath);

        $testDir = $baseTestPath.'/'.$classInfo['testType'].'/'.$relativePath;
        $testFile = $testDir.'/'.$classInfo['name'].'Test.php';

        return $testFile;
    }

    /**
     * Generate test file content.
     */
    protected function generateTest(array $classInfo, string $testFilePath): void
    {
        $testDir = dirname($testFilePath);

        // Check if path exists as a file instead of directory
        if (File::exists($testDir) && ! File::isDirectory($testDir)) {
            $this->warn("Cannot create directory: {$testDir} (file exists with same name)");

            return;
        }

        // Create directory recursively if it doesn't exist
        if (! File::isDirectory($testDir)) {
            File::makeDirectory($testDir, 0755, true);
        }

        $methods = $this->getPublicMethods($classInfo['reflection']);
        $testContent = $this->buildTestContent($classInfo, $methods);

        File::put($testFilePath, $testContent);
        $this->info("Generated: {$classInfo['name']}Test.php");
    }

    /**
     * Get public methods from a class.
     *
     * @return array<string>
     */
    protected function getPublicMethods(ReflectionClass $reflection): array
    {
        $methods = [];

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            // Skip magic methods and inherited methods from parent classes
            if (! $method->isConstructor()
                && ! str_starts_with($method->getName(), '__')
                && $method->getDeclaringClass()->getName() === $reflection->getName()) {
                $methods[] = $method->getName();
            }
        }

        return $methods;
    }

    /**
     * Build test file content.
     */
    protected function buildTestContent(array $classInfo, array $methods): string
    {
        $className = $classInfo['name'];
        $fullClassName = $classInfo['fullName'];
        $testNamespace = $classInfo['testNamespace'];
        $testType = $classInfo['testType'];
        $reflection = $classInfo['reflection'];

        $uses = ['use Tests\TestCase;'];

        // Add RefreshDatabase for models
        if (str_contains($fullClassName, 'Models')) {
            $uses[] = 'use Illuminate\Foundation\Testing\RefreshDatabase;';
        }

        $uses[] = "use {$fullClassName};";

        $usesString = implode("\n", array_unique($uses));

        $traits = '';
        if (str_contains($fullClassName, 'Models')) {
            $traits = "    use RefreshDatabase;\n\n";
        }

        $testMethods = '';
        foreach ($methods as $method) {
            $testMethodName = 'test_'.$this->camelToSnake($method);
            $testMethods .= "    public function {$testMethodName}(): void\n";
            $testMethods .= "    {\n";
            $testMethods .= "        \$this->markTestIncomplete('Test for {$method} needs implementation');\n";
            $testMethods .= "    }\n\n";
        }

        // If no methods, add a basic test
        if (empty($testMethods)) {
            $testMethods = "    public function test_basic(): void\n";
            $testMethods .= "    {\n";
            $testMethods .= "        \$this->assertTrue(true);\n";
            $testMethods .= "    }\n";
        }

        return <<<PHP
<?php

namespace {$testNamespace};

{$usesString}

class {$className}Test extends TestCase
{
{$traits}{$testMethods}}
PHP;
    }

    /**
     * Convert camelCase to snake_case.
     */
    protected function camelToSnake(string $string): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $string));
    }
}
