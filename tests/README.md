# CannaBuddy PHPUnit Test Suite

This directory contains comprehensive PHPUnit tests for the CannaBuddy e-commerce system.

## Test Structure

```
tests/
├── phpunit.xml           # PHPUnit configuration
├── bootstrap.php         # Test bootstrap file
├── base/
│   ├── TestCase.php      # Base test class with common setup
│   └── MockPDO.php       # PDO mock factory
├── SessionHelperTest.php # Session management tests
├── ServicesTest.php      # Service container tests
├── AuthMiddlewareTest.php # Authentication middleware tests
├── CsrfMiddlewareTest.php # CSRF protection tests
├── ValidatorTest.php     # Input validation tests
└── UrlHelperTest.php     # URL helper tests
```

## Running Tests

### Run All Tests
```bash
vendor/bin/phpunit tests/
```

### Run Specific Test File
```bash
vendor/bin/phpunit tests/SessionHelperTest.php
```

### Run with Coverage Report
```bash
vendor/bin/phpunit --coverage-html coverage tests/
```

### Run Specific Test Method
```bash
vendor/bin/phpunit --filter testEnsureSessionStarted tests/SessionHelperTest.php
```

### Run with Verbose Output
```bash
vendor/bin/phpunit --verbose tests/
```

## Test Coverage

Current test coverage targets:

| Component | Target Coverage | Status |
|-----------|----------------|--------|
| SessionHelper | 100% | ✅ |
| Services | 95%+ | ✅ |
| AuthMiddleware | 90%+ | ✅ |
| CsrfMiddleware | 95%+ | ✅ |
| Validator | 95%+ | ✅ |
| UrlHelper | 90%+ | ✅ |

## Writing New Tests

### Test Class Template

```php
<?php
declare(strict_types=1);

namespace CannaBuddy\Tests;

use PHPUnit\Framework\TestCase;

class YourTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        // Your setup code
    }

    protected function tearDown(): void {
        // Your cleanup code
        parent::tearDown();
    }

    public function testSomething(): void {
        $this->assertTrue(true);
    }
}
```

### Best Practices

1. **Use strict types**: Always include `declare(strict_types=1);`
2. **Follow naming conventions**: Test methods must start with `test`
3. **Use type hints**: All method parameters and return types should be typed
4. **Mock external dependencies**: Use mocks for PDO, auth classes, etc.
5. **Test both success and failure cases**: Cover happy path and edge cases
6. **Use descriptive names**: Test names should explain what is being tested
7. **Clean up after tests**: Use tearDown to reset global state
8. **Test in isolation**: Each test should be independent

### Session Testing

For tests that require session functionality:

```php
protected function setUp(): void {
    parent::setUp();
    @session_start();
}

protected function tearDown(): void {
    $_SESSION = [];
    parent::tearDown();
}
```

### Mocking PDO

Use the provided MockPDOFactory:

```php
use CannaBuddy\Tests\base\MockPDOFactory;

$pdo = MockPDOFactory::create(
    $fetchResult,     // Result from fetch()
    $fetchAllResult,  // Result from fetchAll()
    $execResult,      // Result from exec()
    $lastInsertId     // Result from lastInsertId()
);
```

### Testing Exception Cases

Use expectException:

```php
public function testThrowsException(): void {
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('expected message');

    Validator::string(''); // Should throw
}
```

## Environment Variables

Tests use these environment variables (set in phpunit.xml):

- `APP_ENV=testing`
- `CB_DB_HOST=localhost`
- `CB_DB_NAME=cannabuddy_test`
- `CB_DB_USER=root`
- `CB_DB_PASS=root`

## Troubleshooting

### Tests Fail to Run

1. Ensure PHPUnit is installed: `composer install`
2. Check PHP version: `php -v` (requires PHP 8.0+)
3. Verify file permissions: tests must be readable

### Session Errors

If you get "Headers already sent" errors:
- Check for whitespace before `<?php` tags
- Ensure bootstrap.php starts session correctly

### Database Connection Errors

Tests mock database connections, so real DB errors indicate:
- Configuration issue in phpunit.xml
- Database class not being mocked properly

### "Class not found" Errors

1. Run `composer dump-autoload`
2. Check namespace declarations
3. Verify file paths in bootstrap.php

## Continuous Integration

These tests can be integrated with CI/CD:

```yaml
# Example GitHub Actions workflow
- name: Run PHPUnit tests
  run: vendor/bin/phpunit tests/

- name: Generate coverage report
  run: vendor/bin/phpunit --coverage-clover coverage.xml tests/
```

## Contributing

When adding new features:

1. Write tests first (TDD approach)
2. Ensure all tests pass before committing
3. Maintain or improve code coverage
4. Update this README if adding new test suites

## Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Testing Best Practices](https://phpunit.de/manual/current/en/test-doubles.html)
- [Project CLAUDE.md](../CLAUDE.md) - Project coding standards
