# Tests - Silver Assist ACF Clone Fields

This directory contains comprehensive tests using **WordPress Test Suite** for real WordPress integration.

## 📋 Structure

```
tests/
├── bootstrap.php          # PHPUnit bootstrap with WordPress Test Suite
├── Unit/                  # Unit tests (184 tests)
│   ├── Admin/            # Admin component tests
│   │   ├── AjaxTest.php           # 16 tests - AJAX endpoints
│   │   ├── BackupManagerTest.php  # 22 tests - Backup management
│   │   ├── LoaderTest.php         # 7 tests - Admin loader
│   │   ├── MetaBoxTest.php        # 13 tests - Meta box functionality
│   │   └── SettingsTest.php       # 23 tests - Settings management
│   ├── Core/             # Core component tests
│   │   ├── ActivatorTest.php      # 12 tests - Plugin activation
│   │   └── PluginTest.php         # 8 tests - Plugin initialization
│   ├── Services/         # Service tests
│   │   ├── FieldClonerTest.php    # 20 tests - Field cloning
│   │   └── FieldDetectorTest.php  # 15 tests - Field detection
│   ├── BackupSystemTest.php       # 10 tests - Backup system
│   ├── HelpersTest.php            # 23 tests - Utility functions
│   └── LoggerTest.php             # 15 tests - Logging
├── Integration/          # Integration tests (27 tests)
│   ├── AdminComponentsTest.php    # 13 tests - Admin integration
│   └── CloneOptionsTest.php       # 14 tests - Clone options
└── Utils/                # Testing utilities
    ├── TestCase.php               # Base test class (extends WP_UnitTestCase)
    └── ACFTestHelpers.php         # ACF testing helpers
```

## 🚀 Running Tests

### Prerequisites

**Install WordPress Test Suite** (required):

```bash
bash scripts/install-wp-tests.sh wordpress_test root '' localhost latest true
```

This creates:
- WordPress test environment in `/tmp/wordpress/`
- Test database `wordpress_test`
- Test library in `/tmp/wordpress-tests-lib/`

### Run All Tests

```bash
# All tests (unit + integration)
vendor/bin/phpunit

# Unit tests only (faster)
vendor/bin/phpunit --testsuite=unit

# Integration tests only
vendor/bin/phpunit --testsuite=integration

# With readable format
vendor/bin/phpunit --testdox
```

### Run Specific Tests

```bash
# Single test file
vendor/bin/phpunit tests/Unit/Services/FieldClonerTest.php

# Single test method
vendor/bin/phpunit --filter test_clone_fields_success

# Specific test suite
vendor/bin/phpunit tests/Unit/Admin/
```

### Run with Coverage

```bash
# Requires Xdebug or PCOV
vendor/bin/phpunit --coverage-html coverage/
vendor/bin/phpunit --coverage-text
```

## 🧪 Test Environment

All tests use **WordPress Test Suite** (`WP_UnitTestCase`):

- ✅ **Real WordPress functions**: `wp_insert_post()`, `update_option()`, etc.
- ✅ **Factory methods**: `static::factory()->post->create()`
- ✅ **Database transactions**: Auto-rollback after each test
- ✅ **No mocks needed**: Direct WordPress integration

### WordPress Factory Pattern (MANDATORY)

**CRITICAL**: Use `static::factory()` (NOT `$this->factory`) - deprecated since WordPress 6.1+

```php
// ✅ CORRECT: static::factory() pattern
public function setUp(): void {
    parent::setUp();
    
    // Create test user
    $user_id = static::factory()->user->create([
        'role' => 'administrator',
    ]);
    \wp_set_current_user($user_id);
    
    // Create test post
    $post_id = static::factory()->post->create([
        'post_title'  => 'Test Post',
        'post_status' => 'publish',
    ]);
}

// ❌ INCORRECT: Old $this->factory pattern (deprecated)
$user_id = $this->factory->user->create(...);  // DO NOT USE
```

### Database Schema in Tests

**IMPORTANT**: Use `Activator::create_tables()` for database setup.

```php
use SilverAssist\ACFCloneFields\Core\Activator;

public function setUp(): void {
    parent::setUp();
    
    // ✅ CORRECT: Use canonical schema from Activator
    Activator::create_tables();
    
    // ❌ INCORRECT: Don't create custom schemas
    // $this->create_backup_table();  // REMOVED - use Activator
}
```

**Why?**
- Ensures schema consistency between production and tests
- Single source of truth for table structure
- Automatic updates when schema changes

### MySQL Transactions and CREATE TABLE

**CRITICAL**: `CREATE TABLE` triggers implicit MySQL `COMMIT` which breaks WordPress Test Suite's rollback system.

**The Problem:**
- WordPress Test Suite wraps each test in a transaction (`START TRANSACTION`)
- After test, rolls back transaction (`ROLLBACK`) to clean state
- **`CREATE TABLE` triggers implicit COMMIT**, breaking rollback
- Result: Tables persist or disappear unexpectedly between tests

**The Solution - Use wpSetUpBeforeClass():**

```php
class MyTest extends TestCase {
    /**
     * Create shared fixtures before class
     *
     * Runs ONCE before any tests. Use for CREATE TABLE.
     *
     * @param WP_UnitTest_Factory $factory Factory instance.
     */
    public static function wpSetUpBeforeClass($factory): void {
        // ✅ CORRECT: CREATE TABLE in wpSetUpBeforeClass
        Activator::create_tables();
    }
    
    /**
     * Setup test environment
     *
     * Runs BEFORE EACH test. DO NOT create tables here.
     */
    public function setUp(): void {
        parent::setUp();
        
        // ✅ TRUNCATE is safe - doesn't trigger COMMIT
        $this->clean_table_data();
    }
    
    protected function clean_table_data(): void {
        global $wpdb;
        $table = $wpdb->prefix . 'acf_field_backups';
        
        // TRUNCATE doesn't trigger COMMIT
        $wpdb->query("TRUNCATE TABLE $table");
    }
}
```

**MySQL Statements That Trigger Implicit COMMIT:**
- `CREATE TABLE` / `DROP TABLE` → use `wpSetUpBeforeClass()`
- `ALTER TABLE` → use `wpSetUpBeforeClass()`
- `CREATE DATABASE` / `DROP DATABASE`
- `RENAME TABLE`

**Safe for setUp() / tearDown():**
- `TRUNCATE TABLE` (safe in WordPress Test Suite)
- `INSERT` / `UPDATE` / `DELETE`
- `SELECT` queries

## 📊 Test Coverage

**Current Status** (as of Nov 8, 2025):

- **Total Tests**: 211 tests (184 unit + 27 integration)
- **Total Assertions**: 445+
- **Coverage**: ~45-50% lines (estimated, pending CI verification)
- **Target**: 60%+ lines

### Coverage by Component:

| Component | Coverage | Tests | Status |
|-----------|----------|-------|--------|
| **Core\Plugin** | 81.11% | 8 | ✅ Excellent |
| **Core\Activator** | 61.54% | 12 | ✅ Good |
| **Admin\Settings** | ~60% | 23 | ✅ Good |
| **Admin\MetaBox** | ~55% | 13 | ✅ Good |
| **Admin\BackupManager** | ~50% | 22 | ✅ Good |
| **Utils\Helpers** | 53.33% | 23 | ✅ Good |
| **Services\FieldCloner** | 31.91% | 20 | ⚠️ Improving |
| **Services\FieldDetector** | 25.15% | 15 | ⚠️ Improving |
| **Admin\Loader** | 15.38% | 7 | ⚠️ Low |
| **Admin\Ajax** | 3.15% | 16 | ❌ Low (AJAX excluded) |

### Recent Improvements:

- ✅ **Nov 8, 2025**: Added FieldDetectorTest (15 tests)
- ✅ **Nov 8, 2025**: Added LoaderTest (7 tests)  
- ✅ **Nov 8, 2025**: Enhanced HelpersTest (+10 tests)
- ✅ **Nov 8, 2025**: Enhanced FieldClonerTest (+3 tests)
- ✅ **Nov 8, 2025**: Consolidated backup table creation

### Test Suite Details:

#### ✅ Core Components (20 tests)
- **Core\Activator** (12 tests): Database setup, requirements validation
- **Core\Plugin** (8 tests): Initialization, component loading

#### ✅ Admin Components (81 tests)
- **Admin\Ajax** (16 tests): AJAX endpoints, security
- **Admin\BackupManager** (22 tests): Backup UI, restoration
- **Admin\Loader** (7 tests): Component loading, initialization
- **Admin\MetaBox** (13 tests): Meta box registration, rendering
- **Admin\Settings** (23 tests): Settings management, validation

#### ✅ Services (35 tests)
- **Services\FieldCloner** (20 tests): Field cloning, backups
- **Services\FieldDetector** (15 tests): Field detection, statistics

#### ✅ Utilities (48 tests)
- **BackupSystem** (10 tests): Backup operations
- **Helpers** (23 tests): Utility functions
- **Logger** (15 tests): Logging system

#### ✅ Integration (27 tests)
- **AdminComponents** (13 tests): Admin layer integration
- **CloneOptions** (14 tests): End-to-end clone operations

## 🔍 Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `WP_TESTS_DIR` | WordPress tests library | `/tmp/wordpress-tests-lib` |
| `WP_CORE_DIR` | WordPress core directory | `/tmp/wordpress/` |

## ✅ CI/CD Integration

Tests run automatically in GitHub Actions workflows:

### **ci.yml** - Continuous Integration
- Runs on every PR and push to main
- Full WordPress integration testing
- Matrix: PHP 8.2, 8.3, 8.4
- ~8-10 minutes duration

### **release.yml** - Release Validation  
- Triggered by git tags (`v*`)
- Exhaustive testing across PHP versions
- Validates plugin structure
- ~10-12 minutes duration

### **dependency-updates.yml** - Dependency Management
- Weekly automated checks (Mondays 9AM Mexico City)
- Fast validation without WordPress (~2-3 minutes)
- Security audit with CVE detection

All workflows use WordPress Test Suite for comprehensive validation.

## 📝 Adding New Tests

### Basic Unit Test

1. Create file in `tests/Unit/` with `Test.php` suffix
2. Extend from `TestCase` (which extends `WP_UnitTestCase`)
3. Use WordPress factory methods with `static::factory()`
4. Implement test methods with `test_` prefix

Example:

```php
<?php
namespace SilverAssist\ACFCloneFields\Tests\Unit;

use SilverAssist\ACFCloneFields\Tests\Utils\TestCase;

class MyFeatureTest extends TestCase {
    private int $test_user_id;
    private int $test_post_id;
    
    public function setUp(): void {
        parent::setUp();
        
        // ✅ Use static::factory() pattern (NOT $this->factory)
        $this->test_user_id = static::factory()->user->create([
            'role' => 'administrator',
        ]);
        \wp_set_current_user($this->test_user_id);
        
        $this->test_post_id = static::factory()->post->create([
            'post_title'  => 'Test Post',
            'post_status' => 'publish',
        ]);
    }
    
    public function test_my_feature(): void {
        // Use real WordPress functions
        $post = \get_post($this->test_post_id);
        
        $this->assertInstanceOf(\WP_Post::class, $post);
        $this->assertEquals('Test Post', $post->post_title);
    }
    
    public function tearDown(): void {
        // Clean up
        \wp_delete_post($this->test_post_id, true);
        \wp_delete_user($this->test_user_id);
        
        parent::tearDown();
    }
}
```

### Test with Database Tables

```php
<?php
use SilverAssist\ACFCloneFields\Core\Activator;

class DatabaseTest extends TestCase {
    /**
     * Create tables ONCE before class
     */
    public static function wpSetUpBeforeClass($factory): void {
        // ✅ CREATE TABLE in wpSetUpBeforeClass
        Activator::create_tables();
    }
    
    public function setUp(): void {
        parent::setUp();
        
        // ✅ TRUNCATE to clean data (doesn't trigger COMMIT)
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}acf_field_backups");
    }
    
    public function test_database_operation(): void {
        global $wpdb;
        $table = $wpdb->prefix . 'acf_field_backups';
        
        // Test database operations
        $wpdb->insert($table, [
            'backup_id'   => 'test123',
            'post_id'     => 1,
            'user_id'     => 1,
            'backup_data' => serialize(['data']),
            'field_count' => 1,
            'created_at'  => current_time('mysql'),
        ]);
        
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        $this->assertEquals(1, $count);
    }
}
```

## 🐛 Troubleshooting

### WordPress Test Suite Not Found

**Error**: "Cannot find WordPress test suite"

**Solution**:

```bash
# Install WordPress test environment
bash scripts/install-wp-tests.sh wordpress_test root '' localhost latest true

# Verify installation
ls /tmp/wordpress-tests-lib/
ls /tmp/wordpress/
```

### Database Connection Issues

**Error**: "Database connection failed"

**Solutions**:

```bash
# Check MySQL is running
mysql -u root -p -e "SELECT 1;"

# Recreate test database
mysql -u root -p -e "DROP DATABASE IF EXISTS wordpress_test; CREATE DATABASE wordpress_test;"

# Use correct host (try both)
bash scripts/install-wp-tests.sh wordpress_test root '' localhost latest true
bash scripts/install-wp-tests.sh wordpress_test root '' 127.0.0.1 latest true
```

### Tests Interfere With Each Other

**Problem**: Tests pass individually but fail when run together

**Cause**: Singleton instances persist across tests

**Solutions**:

```php
// Option 1: Reset singleton flag with Reflection
public function setUp(): void {
    parent::setUp();
    
    $metabox = MetaBox::instance();
    $reflection = new \ReflectionClass($metabox);
    $property = $reflection->getProperty('initialized');
    $property->setAccessible(true);
    $property->setValue($metabox, false);
}

// Option 2: Test that hook exists (not that it was just registered)
public function test_hooks_registered(): void {
    $instance = MyClass::instance();
    $instance->init();
    
    // ✅ Check hook exists (works even if registered earlier)
    $this->assertNotFalse(
        has_action('hook_name', [$instance, 'callback']),
        'Hook should be registered'
    );
}
```

### CREATE TABLE Breaks Test Isolation

**Problem**: Tables persist or disappear between tests

**Cause**: `CREATE TABLE` triggers MySQL implicit COMMIT

**Solution**: Always use `wpSetUpBeforeClass()` for schema changes

```php
// ✅ CORRECT
public static function wpSetUpBeforeClass($factory): void {
    Activator::create_tables();  // Runs once, outside transactions
}

// ❌ INCORRECT
public function setUp(): void {
    $this->create_tables();  // Triggers COMMIT, breaks rollback
}
```

### Factory Undefined Method Error

**Error**: "Call to undefined method factory()"

**Cause**: Using deprecated `$this->factory` instead of `static::factory()`

**Solution**:

```php
// ✅ CORRECT (WordPress 6.1+)
$user_id = static::factory()->user->create([...]);

// ❌ INCORRECT (deprecated)
$user_id = $this->factory->user->create([...]);
```

## 📚 Additional Resources

- [WordPress Test Suite Handbook](https://make.wordpress.org/core/handbook/testing/automated-testing/writing-phpunit-tests/)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Plugin Testing Best Practices](https://make.wordpress.org/core/handbook/testing/automated-testing/)
- [MySQL Implicit Commit Reference](https://dev.mysql.com/doc/refman/5.7/en/implicit-commit.html)

## 🎯 Coverage Goals

**Next Steps to Reach 60% Coverage:**

1. ✅ ~~Add FieldDetectorTest (15 tests)~~ - Completed Nov 8, 2025
2. ✅ ~~Add LoaderTest (7 tests)~~ - Completed Nov 8, 2025  
3. ✅ ~~Enhance HelpersTest (+10 tests)~~ - Completed Nov 8, 2025
4. ✅ ~~Enhance FieldClonerTest (+3 tests)~~ - Completed Nov 8, 2025
5. ⏳ Add more FieldCloner tests (target 50%+ coverage)
6. ⏳ Add more FieldDetector tests (target 50%+ coverage)
7. ⏳ Increase Helpers coverage to 70%+

**Progress**: From ~23% → ~45-50% (estimated) ✅ On track to 60%!
