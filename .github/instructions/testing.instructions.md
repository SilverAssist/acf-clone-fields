---
description: Testing guidelines for Silver Assist ACF Clone Fields plugin using WordPress Test Suite
applyTo: "tests/**/*.php"
---

# Testing Instructions - Silver Assist ACF Clone Fields

## 🎯 Testing Philosophy

This plugin uses **WordPress Test Suite** (`WP_UnitTestCase`) with real WordPress environment - **NOT mocks**.

- ✅ Real WordPress functions available in tests
- ✅ Factory methods for creating test data
- ✅ Automatic database transaction rollback after each test
- ✅ No need for WordPress mocks or stubs

## 📋 Test Structure

All tests are organized in `tests/` directory:

```
tests/
├── Unit/                  # Unit tests (184 tests)
│   ├── Admin/            # Admin components (81 tests)
│   ├── Core/             # Core components (20 tests)
│   ├── Services/         # Service layer (35 tests)
│   └── *.php             # Utility tests (48 tests)
├── Integration/          # Integration tests (27 tests)
└── Utils/                # Testing utilities
    ├── TestCase.php      # Base test class (extends WP_UnitTestCase)
    └── ACFTestHelpers.php
```

**Total**: 211 tests (184 unit + 27 integration), 445+ assertions

## 🔧 WordPress Factory Pattern (MANDATORY)

**CRITICAL**: Always use `static::factory()` - **NEVER** use `$this->factory` (deprecated since WordPress 6.1+)

### ✅ CORRECT Pattern

```php
public function setUp(): void {
    parent::setUp();
    
    // Create admin user
    $this->admin_user_id = static::factory()->user->create([
        'role' => 'administrator',
    ]);
    \wp_set_current_user($this->admin_user_id);
    
    // Create test post
    $this->post_id = static::factory()->post->create([
        'post_title'   => 'Test Post',
        'post_status'  => 'publish',
        'post_type'    => 'post',
    ]);
}
```

### ❌ INCORRECT Pattern (Deprecated)

```php
// DO NOT USE - deprecated pattern
$user_id = $this->factory->user->create([...]);
$post_id = $this->factory->post->create([...]);
```

### Available Factory Methods

- `static::factory()->post->create([...])` - Create posts
- `static::factory()->user->create([...])` - Create users
- `static::factory()->comment->create([...])` - Create comments
- `static::factory()->term->create([...])` - Create terms
- `static::factory()->category->create([...])` - Create categories

## 🗄️ Database Schema Management

**CRITICAL**: Always use `Activator::create_tables()` for database setup - single source of truth.

### ✅ CORRECT: Schema Creation

```php
use SilverAssist\ACFCloneFields\Core\Activator;

class DatabaseTest extends TestCase {
    /**
     * Create tables ONCE before class (runs outside transactions)
     */
    public static function wpSetUpBeforeClass($factory): void {
        Activator::create_tables();
    }
    
    public function setUp(): void {
        parent::setUp();
        
        // Clean data with TRUNCATE (safe - doesn't trigger COMMIT)
        global $wpdb;
        $table = $wpdb->prefix . 'acf_field_backups';
        $wpdb->query("TRUNCATE TABLE $table");
    }
}
```

### ❌ INCORRECT: Custom Schema Methods

```php
// DO NOT USE - creates schema inconsistencies
protected function create_backup_table(): void {
    global $wpdb;
    $wpdb->query("CREATE TABLE IF NOT EXISTS ...");
}
```

### Why Use Activator::create_tables()?

1. **Single source of truth** - Schema consistency across tests and production
2. **Automatic updates** - Schema changes propagate to all tests
3. **No schema drift** - Prevents duplicate/inconsistent table definitions

## ⚠️ MySQL Transactions and CREATE TABLE (CRITICAL)

**Problem**: `CREATE TABLE` triggers implicit MySQL `COMMIT` which breaks WordPress Test Suite's rollback system.

### How WordPress Test Suite Works

1. Wraps each test in MySQL transaction (`START TRANSACTION`)
2. Runs test code
3. Rolls back transaction (`ROLLBACK`) to clean state
4. **BUT**: `CREATE TABLE` triggers implicit `COMMIT`, breaking rollback

### Solution: Use wpSetUpBeforeClass()

```php
class MyTest extends TestCase {
    /**
     * Setup BEFORE CLASS - runs ONCE for all tests
     * Use this for CREATE TABLE statements
     */
    public static function wpSetUpBeforeClass($factory): void {
        // ✅ Safe - runs once outside transaction system
        Activator::create_tables();
    }
    
    /**
     * Setup BEFORE EACH test
     * DO NOT create tables here
     */
    public function setUp(): void {
        parent::setUp();
        
        // ✅ Safe - TRUNCATE doesn't trigger COMMIT in WordPress Test Suite
        $this->clean_table_data();
    }
    
    protected function clean_table_data(): void {
        global $wpdb;
        $table = $wpdb->prefix . 'acf_field_backups';
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query("TRUNCATE TABLE $table");
    }
}
```

### MySQL Statements That Trigger Implicit COMMIT

**Use wpSetUpBeforeClass() for these:**
- `CREATE TABLE` / `DROP TABLE`
- `ALTER TABLE`
- `CREATE DATABASE` / `DROP DATABASE`
- `RENAME TABLE`

**Safe for setUp() / tearDown():**
- `TRUNCATE TABLE` (safe in WordPress Test Suite context)
- `INSERT` / `UPDATE` / `DELETE`
- `SELECT` queries

**Reference**: [MySQL Implicit Commit Docs](https://dev.mysql.com/doc/refman/5.7/en/implicit-commit.html)

## 🧪 Writing New Tests

### Basic Test Template

```php
<?php
namespace SilverAssist\ACFCloneFields\Tests\Unit;

use SilverAssist\ACFCloneFields\Tests\Utils\TestCase;
use SilverAssist\ACFCloneFields\YourNamespace\YourClass;

class YourClassTest extends TestCase {
    private YourClass $instance;
    private int $test_user_id;
    private int $test_post_id;
    
    public function setUp(): void {
        parent::setUp();
        
        // Create test fixtures using static::factory()
        $this->test_user_id = static::factory()->user->create([
            'role' => 'administrator',
        ]);
        \wp_set_current_user($this->test_user_id);
        
        $this->test_post_id = static::factory()->post->create([
            'post_title'  => 'Test Post',
            'post_status' => 'publish',
        ]);
        
        // Initialize class under test
        $this->instance = YourClass::instance();
    }
    
    public function test_singleton_pattern(): void {
        $instance1 = YourClass::instance();
        $instance2 = YourClass::instance();
        
        $this->assertSame($instance1, $instance2);
    }
    
    public function test_your_feature(): void {
        // Use real WordPress functions (prefix with \)
        $post = \get_post($this->test_post_id);
        
        $this->assertInstanceOf(\WP_Post::class, $post);
        $this->assertEquals('Test Post', $post->post_title);
    }
    
    public function tearDown(): void {
        // Clean up test data
        \wp_delete_post($this->test_post_id, true);
        \wp_delete_user($this->test_user_id);
        
        parent::tearDown();
    }
}
```

### Test Naming Conventions

- Test files: `*Test.php` (e.g., `FieldClonerTest.php`)
- Test methods: `test_*` prefix (e.g., `test_clone_fields_success`)
- Use descriptive names: `test_restore_backup_with_invalid_id`

### Test Organization

1. **Arrange**: Set up test data and conditions
2. **Act**: Execute the code being tested
3. **Assert**: Verify the results

## 🔍 Common Testing Patterns

### Testing LoadableInterface Components

```php
public function test_implements_loadable_interface(): void {
    $this->assertInstanceOf(LoadableInterface::class, $this->instance);
}

public function test_get_priority_returns_expected_value(): void {
    $this->assertEquals(20, $this->instance->get_priority());
}

public function test_should_load_returns_boolean(): void {
    $this->assertIsBool($this->instance->should_load());
}

public function test_init_registers_hooks(): void {
    $this->instance->init();
    
    // Check hook exists (works even if registered earlier)
    $this->assertNotFalse(
        has_action('hook_name', [$this->instance, 'callback']),
        'Hook should be registered'
    );
}
```

### Testing with WordPress Options

```php
public function test_saves_options_correctly(): void {
    $this->instance->save_setting('key', 'value');
    
    $result = \get_option('plugin_prefix_key');
    $this->assertEquals('value', $result);
}
```

### Testing AJAX Endpoints

```php
public function test_ajax_endpoint_requires_authentication(): void {
    \wp_set_current_user(0); // Logout
    
    $this->expectException(\WPAjaxDieContinueException::class);
    $this->instance->handle_ajax_request();
}

public function test_ajax_endpoint_validates_nonce(): void {
    $_POST['nonce'] = 'invalid_nonce';
    
    $this->expectException(\WPAjaxDieContinueException::class);
    $this->instance->handle_ajax_request();
}
```

## 🐛 Troubleshooting Common Issues

### Issue: Tests Pass Individually But Fail Together

**Cause**: Singleton instances persist across tests

**Solution**: Reset singleton state with Reflection

```php
public function setUp(): void {
    parent::setUp();
    
    $instance = MyClass::instance();
    $reflection = new \ReflectionClass($instance);
    $property = $reflection->getProperty('initialized');
    $property->setAccessible(true);
    $property->setValue($instance, false);
}
```

**Alternative**: Test hook existence instead of registration

```php
// ✅ Works even if hook registered earlier
public function test_hooks_registered(): void {
    $instance = MyClass::instance();
    $instance->init();
    
    $this->assertNotFalse(
        has_action('hook_name', [$instance, 'callback']),
        'Hook should be registered'
    );
}

// ❌ Fails if hook already registered
public function test_hooks_registered_bad(): void {
    $result = $instance->init();
    $this->assertTrue($result); // May fail due to singleton state
}
```

### Issue: CREATE TABLE Breaks Test Isolation

**Cause**: `CREATE TABLE` triggers MySQL implicit COMMIT

**Solution**: Always use `wpSetUpBeforeClass()`

```php
// ✅ CORRECT
public static function wpSetUpBeforeClass($factory): void {
    Activator::create_tables();
}

// ❌ INCORRECT - breaks transaction rollback
public function setUp(): void {
    global $wpdb;
    $wpdb->query("CREATE TABLE IF NOT EXISTS ...");
}
```

### Issue: Factory Method Undefined

**Cause**: Using deprecated `$this->factory` instead of `static::factory()`

**Solution**: Always use `static::factory()`

```php
// ✅ CORRECT
$user_id = static::factory()->user->create([...]);

// ❌ INCORRECT
$user_id = $this->factory->user->create([...]);
```

## 📊 Test Coverage Guidelines

### Current Coverage Targets

- **Overall Goal**: 60%+ line coverage
- **Critical Components**: 70%+ coverage
  - Core\Plugin
  - Core\Activator
  - Services\FieldCloner
  - Services\FieldDetector
- **Admin Components**: 50%+ coverage
- **Utilities**: 60%+ coverage

### Coverage Exclusions

- AJAX endpoints (require browser environment)
- Admin UI rendering (requires WordPress admin context)
- JavaScript interactions (use QUnit for JS testing)

## 🚀 Running Tests

### Quick Reference

```bash
# All tests
vendor/bin/phpunit

# Unit tests only (faster)
vendor/bin/phpunit --testsuite=unit

# Integration tests only
vendor/bin/phpunit --testsuite=integration

# Specific test file
vendor/bin/phpunit tests/Unit/Services/FieldClonerTest.php

# Specific test method
vendor/bin/phpunit --filter test_clone_fields_success

# With readable output
vendor/bin/phpunit --testdox

# With coverage (requires Xdebug or PCOV)
vendor/bin/phpunit --coverage-html coverage/
```

## 📚 Additional References

- [WordPress Test Suite Handbook](https://make.wordpress.org/core/handbook/testing/automated-testing/writing-phpunit-tests/)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [MySQL Implicit Commit Reference](https://dev.mysql.com/doc/refman/5.7/en/implicit-commit.html)
- [WordPress Factory Pattern](https://make.wordpress.org/core/handbook/testing/automated-testing/writing-phpunit-tests/#factory)

## 🎯 Key Takeaways for AI Agents

1. **Always use `static::factory()`** - Never `$this->factory` (deprecated)
2. **Use `Activator::create_tables()`** - Single source of truth for schema
3. **Use `wpSetUpBeforeClass()` for CREATE TABLE** - Avoids transaction issues
4. **Prefix WordPress functions with `\`** - Global namespace in tests
5. **Test hook existence, not registration** - Handles singleton state better
6. **Clean up in tearDown()** - Delete created posts, users, options
7. **Extend from `TestCase`** - Automatically extends `WP_UnitTestCase`
8. **Use descriptive test names** - `test_restore_backup_with_invalid_id`
9. **Follow AAA pattern** - Arrange, Act, Assert
10. **Check coverage regularly** - Target 60%+ overall, 70%+ for critical components
