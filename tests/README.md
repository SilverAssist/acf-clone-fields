# Tests - Silver Assist ACF Clone Fields

This directory contains unit tests for the plugin.

## 📋 Structure

```
tests/
├── bootstrap.php          # PHPUnit bootstrap (auto-detects WordPress)
├── Unit/                  # Unit tests
│   ├── Admin/            # Admin component tests
│   │   ├── AjaxTest.php
│   │   ├── BackupManagerTest.php
│   │   ├── MetaBoxTest.php
│   │   └── SettingsTest.php
│   ├── Core/             # Core component tests
│   │   ├── ActivatorTest.php
│   │   └── PluginTest.php
│   ├── Services/         # Service tests
│   │   └── FieldClonerTest.php
│   ├── BackupSystemTest.php
│   ├── FieldDetectorTest.php
│   ├── HelpersTest.php
│   └── LoggerTest.php
├── Integration/          # Integration tests
│   ├── AdminComponentsTest.php
│   └── CloneOptionsTest.php
└── Utils/                # Testing utilities
    ├── TestCase.php
    └── ACFTestHelpers.php
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

**Current Coverage**: 22.77% lines (474/2082), 30.15% methods (60/199)  
**Expected After Session 2**: ~34-40% lines (pending CI verification)  
**Target**: 50%+ lines (industry standard)

### Completed Test Suites:

- ✅ **Core\Activator** (12 tests) - 61.54% lines
  - Database table creation
  - Table schema validation
  - WordPress integration
  
- ✅ **Core\Plugin** (8 tests) - 81.11% lines
  - Plugin initialization
  - Component loading
  - Hook registration

- ✅ **Admin\BackupManager** (22 tests) - NEW (Nov 8, 2025)
  - Meta box registration and rendering
  - AJAX handlers (restore, delete, cleanup)
  - Permission and security checks
  - Backup display with/without data

- ✅ **Admin\MetaBox** (13 tests) - NEW (Nov 8, 2025)
  - Meta box registration (enabled/disabled post types)
  - Meta box rendering with permissions
  - Asset enqueuing
  - Block editor compatibility

- ✅ **Admin\Settings** (23 tests) - NEW (Nov 8, 2025)
  - Settings registration and initialization
  - Default settings validation
  - Settings validation and sanitization
  - Render methods for all field types
  - Settings page output

- ✅ **Admin\Ajax** (10 tests) - 3.15% lines
  - AJAX endpoint security
  - Basic handler tests

- ✅ **Services\FieldCloner** (15 tests) - 31.91% lines
  - Field cloning operations
  - Backup creation
  - Data validation

- ✅ **Services\FieldDetector** (8 tests) - 25.15% lines
  - ACF field detection
  - Field groups and statistics

- ✅ **Backup System** (10 tests)
  - Backup creation and storage
  - Backup recovery and deletion
  - Retention policies

- ✅ **Logger** (7 tests)
  - Log levels (error, warning, info)
  - Singleton pattern

- ✅ **Helpers** (11 tests) - 45.13% lines
  - Utility functions
  - Data transformation

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
