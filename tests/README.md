# Tests - Silver Assist ACF Clone Fields

This directory contains unit tests for the plugin.

## 📋 Structure

```
tests/
├── bootstrap.php          # PHPUnit bootstrap (auto-detects WordPress)
├── Unit/                  # Unit tests
│   ├── BackupSystemTest.php
│   ├── FieldDetectorTest.php
│   └── LoggerTest.php
└── Utils/                 # Testing utilities
    ├── TestCase.php
    ├── ACFTestHelpers.php
    └── WordPressMocks.php
```

## 🚀 Running Tests

### With Mocks (without WordPress)

```bash
vendor/bin/phpunit --testsuite=unit
```

### With Real WordPress

1. **Install WordPress test environment:**

```bash
bash scripts/install-wp-tests.sh wordpress_test root '' localhost latest true
```

2. **Run tests with WordPress:**

```bash
WP_TESTS_DIR=/tmp/wordpress-tests-lib vendor/bin/phpunit --testsuite=unit
```

### With readable format

```bash
WP_TESTS_DIR=/tmp/wordpress-tests-lib vendor/bin/phpunit --testsuite=unit --testdox
```

## 🔧 Bootstrap Auto-Detection

The `bootstrap.php` file **automatically detects** if WordPress is available:

- ✅ **If `WP_TESTS_DIR` is configured**: Uses real WordPress
- ✅ **If WordPress is not available**: Uses mocks from `Utils/WordPressMocks.php`

This allows tests to work in both environments:
- **Local development**: With or without WordPress
- **CI/CD**: GitHub Actions configures WordPress automatically

## 📊 Test Coverage

Current tests cover:

- ✅ **Backup System** (10 tests)
  - Backup creation and storage
  - Backup recovery
  - Backup deletion
  - Retention policies
  - Data integrity

- ✅ **Field Detector** (7 tests)
  - ACF field detection
  - Field groups
  - Statistics
  - Cache

- ✅ **Logger** (7 tests)
  - Log levels (error, warning, info)
  - Log context
  - Singleton pattern

## 🔍 Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `WP_TESTS_DIR` | WordPress tests directory | `/tmp/wordpress-tests-lib` |
| `WP_CORE_DIR` | WordPress core directory | `/tmp/wordpress/` |

## ✅ CI/CD

Tests run automatically in GitHub Actions:

- **Quality Checks**: Tests with mocks (fast)
- **Compatibility**: Tests with WordPress 6.4, 6.5, 6.6, latest (complete)

Both approaches work thanks to bootstrap auto-detection.

## 📝 Adding New Tests

1. Create file in `tests/Unit/` with `Test.php` suffix
2. Extend from `TestCase`
3. Implement methods with `test_` prefix
4. Run: `vendor/bin/phpunit --testsuite=unit`

Example:

```php
<?php
namespace SilverAssist\ACFCloneFields\Tests\Unit;

use SilverAssist\ACFCloneFields\Tests\Utils\TestCase;

class MyFeatureTest extends TestCase {
    public function test_my_feature(): void {
        $this->assertTrue(true);
    }
}
```

## 🐛 Troubleshooting

**Error: "Cannot redeclare function"**
- Bootstrap handles this automatically
- Don't define constants before bootstrap

**Tests can't find WordPress**
- Verify: `echo $WP_TESTS_DIR`
- Re-install: `bash scripts/install-wp-tests.sh ...`

**Database connection failed**
- Verify MySQL is running
- Use `localhost` or `127.0.0.1` depending on your configuration
