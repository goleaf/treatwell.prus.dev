# Test Coverage Agent

Automated agent that monitors source code changes and ensures test coverage.

## Features

1. **File Monitoring** - Watches all PHP files in `app/` directory
2. **Method Detection** - Identifies new/modified functions and methods
3. **Test Generation** - Creates test files and test methods for missing coverage
4. **Auto-Testing** - Runs tests automatically after changes
5. **Coverage Tracking** - Maintains cache of file states

## Usage

### Start the agent:
```bash
./run-test-agent.sh
```

Or directly:
```bash
php test-coverage-agent.php
```

### Stop the agent:
Press `Ctrl+C`

## How It Works

1. Monitors all PHP files in `app/` directory every 2 seconds
2. Detects file changes using MD5 hashing
3. Extracts public/protected/private methods from changed files
4. Checks if corresponding test file exists in `tests/Unit/`
5. Verifies each method has a test (using naming convention `test_method_name`)
6. Generates missing test files or appends missing test methods
7. Runs the tests and displays results

## Test Naming Convention

For a method `getUserData()`, the agent expects a test named:
```php
public function test_get_user_data(): void
```

## File Structure

- Source: `app/Models/User.php`
- Test: `tests/Unit/Models/UserTest.php`

## Cache

File hashes are stored in `storage/test-coverage-cache.json` to track changes.

## Example Output

```
Test Coverage Agent started...
Monitoring: /path/to/app

[14:30:45] File changed: User.php
Found 5 methods
✓ All methods have tests
Running tests...
PASS  Tests\Unit\Models\UserTest
Tests: 5 passed
```
