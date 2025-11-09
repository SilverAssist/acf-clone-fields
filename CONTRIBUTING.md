# Contributing to Silver Assist ACF Clone Fields

Thank you for your interest in contributing! This document provides guidelines and technical information for developers.

## 🛠️ Development Setup

### Prerequisites

- **WordPress**: 5.0 or higher
- **PHP**: 8.2 or higher  
- **ACF Plugin**: Advanced Custom Fields (free) **OR** Advanced Custom Fields Pro
  - **ACF Free**: For testing basic field types (text, image, select, etc.)
  - **ACF Pro**: For testing advanced field types (repeater, group, flexible content)
- **Composer**: For dependency management
- **Git**: For version control

**Note**: The plugin automatically detects which ACF version is active and adjusts available field types accordingly.

### Local Environment Setup

```bash
# Clone repository
git clone https://github.com/silverassist/acf-clone-fields.git
cd acf-clone-fields

# Install dependencies
composer install

# Install development dependencies
composer install --dev
```

### Required Environment

Set up a local WordPress development environment:
- **Local by Flywheel** (Recommended)
- **XAMPP** / **MAMP**
- **Docker** (wp-env, LocalWP, etc.)

## 📋 Code Quality Standards

### Coding Standards

- **PSR-4**: Strict adherence to PSR-4 autoloading
- **WordPress Coding Standards (WPCS)**: Follow official WordPress guidelines
- **PHP 8.2+**: Use modern PHP features and type hints
- **PHPStan Level 8**: Static analysis for type safety
- **PHPUnit Testing**: Comprehensive unit test coverage

### Running Quality Checks

Before committing, **always** run the complete quality check:

```bash
# Complete quality pipeline (MANDATORY before commits)
./scripts/run-quality-checks.sh

# Individual checks
vendor/bin/phpcbf                    # Auto-fix code standards
vendor/bin/phpcs                     # Check WPCS compliance  
php -d memory_limit=512M vendor/bin/phpstan analyse --no-progress  # Static analysis
vendor/bin/phpunit --testsuite=unit  # Run unit tests
```

### Quality Requirements

All contributions must pass:
- ✅ **PHPCS**: Zero errors, minimal warnings
- ✅ **PHPStan Level 8**: No type safety issues
- ✅ **PHPUnit**: All tests passing
- ✅ **Documentation**: English-only, properly formatted

## 🧪 Testing

### WordPress Test Suite Integration

**CRITICAL**: This plugin uses **WordPress Test Suite** with real WordPress environment, NOT mocks.

#### Test Base Class: WP_UnitTestCase

All tests extend `TestCase` which directly extends `WP_UnitTestCase`:

```php
namespace SilverAssist\ACFCloneFields\Tests\Utils;

/**
 * Base test case using WordPress Test Suite
 *
 * All tests extend this class to have access to WordPress functions,
 * factory methods, and proper database transaction rollback.
 */
abstract class TestCase extends \WP_UnitTestCase {
    // Use Activator::create_tables() for database schema setup
}
```

**Note**: WordPress Test Suite must be installed for tests to run. See testing documentation for setup instructions.

#### WordPress Factory Pattern (MANDATORY)

**CRITICAL**: Use `static::factory()` (NOT `$this->factory`) - deprecated since WordPress 6.1+

```php
// ✅ CORRECT: static::factory() pattern
public function setUp(): void {
    parent::setUp();
    
    // Create admin user
    $this->admin_user_id = static::factory()->user->create([
        'role' => 'administrator',
    ]);
    \wp_set_current_user($this->admin_user_id);
    
    // Create test posts
    $this->post_id = static::factory()->post->create([
        'post_title'   => 'Test Post',
        'post_status'  => 'publish',
        'post_type'    => 'post',
    ]);
}

// ❌ INCORRECT: Old $this->factory pattern (deprecated)
$user_id = $this->factory->user->create(...);  // DO NOT USE
```

#### Database Schema Changes (CRITICAL)

**IMPORTANT**: `CREATE TABLE` statements trigger implicit MySQL `COMMIT` which breaks WordPress Test Suite's transaction-based rollback system.

**The Problem:**
- WordPress Test Suite wraps each test in a MySQL transaction (`START TRANSACTION`)
- After each test, it rolls back the transaction (`ROLLBACK`) to restore clean state
- `CREATE TABLE` triggers an **implicit COMMIT**, breaking this rollback mechanism
- This causes tables to persist incorrectly or disappear unexpectedly between tests

**The Solution:**

```php
// ✅ CORRECT: Use wpSetUpBeforeClass() for schema changes
class YourTest extends TestCase {
    /**
     * Create shared fixtures before class
     *
     * Runs ONCE before any tests. Use for CREATE TABLE statements.
     *
     * @param WP_UnitTest_Factory $factory Factory instance.
     */
    public static function wpSetUpBeforeClass( $factory ): void {
        global $wpdb;
        
        // CREATE TABLE happens outside transaction system
        // Use Activator or direct CREATE TABLE here
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
        
        // Initialize services
        $this->service = YourService::instance();
    }
    
    protected function clean_table_data(): void {
        global $wpdb;
        $table_name = $wpdb->prefix . 'your_table';
        
        // TRUNCATE doesn't trigger COMMIT
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query( "TRUNCATE TABLE $table_name" );
    }
}

// ❌ INCORRECT: Creating tables in setUp()
public function setUp(): void {
    parent::setUp();
    
    // This breaks transaction rollback!
    $wpdb->query("CREATE TABLE IF NOT EXISTS ...");
}
```

**MySQL Statements That Trigger Implicit COMMIT:**
- `CREATE TABLE` / `DROP TABLE`
- `CREATE DATABASE` / `DROP DATABASE`
- `ALTER TABLE`
- `RENAME TABLE`
- `TRUNCATE TABLE` (in some contexts, but safe in WordPress Test Suite)

**Reference**: [MySQL Implicit Commit Documentation](https://dev.mysql.com/doc/refman/5.7/en/implicit-commit.html) | [WordPress Testing Handbook - Database](https://make.wordpress.org/core/handbook/testing/automated-testing/writing-phpunit-tests/#database)

#### Available Factory Methods

```php
static::factory()->post->create([...]);      // Create posts
static::factory()->user->create([...]);      // Create users
static::factory()->comment->create([...]);   // Create comments
static::factory()->term->create([...]);      // Create terms
static::factory()->category->create([...]);  // Create categories
```

#### Real WordPress Functions in Tests

With WP_UnitTestCase, you have access to ALL WordPress functions:

```php
// Options API
\update_option('key', 'value');
$value = \get_option('key');
\delete_option('key');

// User functions
\wp_set_current_user($user_id);
$can_edit = \current_user_can('edit_posts');

// Post functions
\wp_delete_post($post_id, true);
$post = \get_post($post_id);

// Hooks
\has_action('hook_name', $callback);
\has_filter('filter_name', $callback);
\add_action('hook_name', $callback);
\do_action('hook_name', $args);
```

### Running Tests

```bash
# Run all tests with WordPress Test Suite
vendor/bin/phpunit

# Run specific test file
vendor/bin/phpunit tests/Unit/Core/PluginTest.php

# With readable output
vendor/bin/phpunit --testdox

# Coverage report (requires xdebug)
vendor/bin/phpunit --coverage-html coverage/
vendor/bin/phpunit --coverage-text
```

### Test Structure

```
tests/
├── bootstrap.php          # Auto-detects WordPress availability
├── Unit/                  # Unit tests (isolated components)
│   ├── Core/
│   │   ├── ActivatorTest.php
│   │   └── PluginTest.php
│   ├── Services/
│   │   ├── FieldClonerTest.php
│   │   └── FieldDetectorTest.php
│   ├── Admin/
│   │   ├── AjaxTest.php
│   │   ├── BackupManagerTest.php
│   │   ├── LoaderTest.php
│   │   ├── MetaBoxTest.php
│   │   └── SettingsTest.php
│   ├── BackupSystemTest.php
│   ├── HelpersTest.php
│   └── LoggerTest.php
├── Integration/           # Integration tests (WordPress integration)
│   ├── AdminComponentsTest.php
│   └── CloneOptionsTest.php
└── Utils/                 # Testing utilities
    ├── TestCase.php       # Base test class (extends WP_UnitTestCase)
    └── ACFTestHelpers.php # ACF-specific helpers
```

**Test Counts**: 211 total tests (184 unit + 27 integration)

**Coverage Areas**:
- Core: Plugin initialization, activation, component loading
- Services: Field detection, cloning, backup/restore operations
- Admin: Meta box UI, settings, AJAX handlers, backup management
- Integration: End-to-end cloning workflows with real WordPress environment

### Writing New Tests

**Complete Pattern Example:**

```php
<?php
namespace SilverAssist\ACFCloneFields\Tests\Unit\Services;

use SilverAssist\ACFCloneFields\Tests\Utils\TestCase;
use SilverAssist\ACFCloneFields\Services\YourService;

class YourServiceTest extends TestCase {
    private YourService $service;
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
        
        // Initialize service
        $this->service = YourService::instance();
    }
    
    public function tearDown(): void {
        // Clean up test data
        \wp_delete_post($this->test_post_id, true);
        \wp_delete_user($this->test_user_id);
        
        parent::tearDown();
    }
    
    public function test_service_functionality(): void {
        // Use real WordPress functions
        $result = $this->service->do_something($this->test_post_id);
        
        $this->assertTrue($result);
        $this->assertIsArray(\get_post_meta($this->test_post_id));
    }
    
    public function test_singleton_pattern(): void {
        $instance1 = YourService::instance();
        $instance2 = YourService::instance();
        
        $this->assertSame($instance1, $instance2);
    }
}
```

**Key Testing Principles:**

1. **Always use `static::factory()`** - Never use deprecated `$this->factory`
2. **Clean up in tearDown()** - Delete created posts, users, options
3. **Use real WordPress functions** - Prefix with `\` for global namespace
4. **Test singletons** - Verify instance() returns same object
5. **Test LoadableInterface** - Verify init(), get_priority(), should_load()

See `tests/README.md` for detailed testing documentation.

## 🏗️ Architecture

### PSR-4 Namespace Structure

Namespace: `SilverAssist\ACFCloneFields\` → `includes/`

```
includes/
├── Core/
│   ├── Plugin.php          # Main plugin controller
│   ├── Activator.php       # Plugin activation/deactivation
│   └── Interfaces/
│       └── LoadableInterface.php
├── Services/
│   ├── Loader.php          # Services component loader
│   ├── FieldDetector.php   # ACF field analysis
│   └── FieldCloner.php     # Field cloning operations
├── Admin/
│   ├── Loader.php          # Admin component loader
│   ├── MetaBox.php         # Sidebar meta box interface
│   ├── Settings.php        # WordPress Settings API integration
│   └── Ajax.php            # AJAX request handlers
└── Utils/
    ├── Helpers.php         # Utility functions
    └── Logger.php          # PSR-3 compliant logging
```

### LoadableInterface Pattern

All components implement `LoadableInterface` for priority-based loading:

```php
interface LoadableInterface {
    public function init(): void;
    public function get_priority(): int;
    public function should_load(): bool;
}
```

**Loading Priorities:**
- `10`: Core components
- `20`: Services
- `30`: Admin interfaces
- `40`: Assets

### Component Creation Pattern

```php
<?php
defined('ABSPATH') || exit;

namespace SilverAssist\ACFCloneFields\ComponentName;

use SilverAssist\ACFCloneFields\Core\Interfaces\LoadableInterface;

class YourComponent implements LoadableInterface {
    private static ?YourComponent $instance = null;
    
    public static function instance(): YourComponent {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function init(): void {
        // Component initialization
        $this->register_hooks();
    }
    
    public function get_priority(): int {
        return 20; // Services priority
    }
    
    public function should_load(): bool {
        return is_admin(); // Load only in admin
    }
    
    private function register_hooks(): void {
        // Register WordPress hooks
    }
}
```

## 🔌 SilverAssist Package Integration

### GitHub Updater

Enables automatic updates from GitHub releases:

```php
$config = new \SilverAssist\WpGithubUpdater\UpdaterConfig(
    __FILE__,
    'SilverAssist/acf-clone-fields',
    [
        'plugin_name' => 'Silver Assist - ACF Clone Fields',
        'requires_wordpress' => '5.0',
        'requires_php' => '8.2',
    ]
);
```

### Settings Hub

Centralizes plugin settings in unified admin menu:

```php
$hub = \SilverAssist\SettingsHub\SettingsHub::get_instance();
$hub->register_plugin(
    'acf-clone-fields',
    'ACF Clone Fields',
    [$this, 'render_settings_page'],
    ['description' => 'Field cloning configuration']
);
```

## 🔒 Security Guidelines

### Input Validation

```php
// Sanitize user input
$post_id = absint($_POST['post_id']);
$field_keys = array_map('sanitize_key', $_POST['field_keys']);

// Validate nonces
check_ajax_referer('silver_acf_clone_nonce', 'nonce');

// Check capabilities
if (!current_user_can('edit_posts')) {
    wp_die(__('Insufficient permissions.', 'silver-assist-acf-clone-fields'));
}
```

### Output Escaping

```php
// Escape for HTML output
echo esc_html($field_name);
echo esc_attr($field_value);
echo esc_url($image_url);

// Escape for JavaScript
wp_localize_script('silver-acf-clone-admin', 'silverAcfClone', [
    'ajaxUrl' => esc_url(admin_url('admin-ajax.php')),
]);
```

## 📝 Documentation Standards

### Language Requirement

**CRITICAL**: All documentation, code comments, commit messages, and technical writing **MUST be in English**.

- ✅ Code comments: English only
- ✅ Commit messages: English only
- ✅ Documentation files: English only
- ✅ PHPDoc blocks: English only
- ✅ README files: English only
- ✅ Error messages: English only (user-facing messages use WordPress i18n)

### PHPDoc Blocks

```php
/**
 * Clone selected ACF fields from source post to target post.
 *
 * @param int   $source_id  Source post ID to clone fields from.
 * @param int   $target_id  Target post ID to clone fields to.
 * @param array $field_keys Array of ACF field keys to clone.
 *
 * @return bool True on success, false on failure.
 */
public function clone_fields(int $source_id, int $target_id, array $field_keys): bool {
    // Implementation
}
```

## 🔄 CI/CD Workflows

### Workflow Overview

| Workflow | WordPress Tests | Duration | Purpose |
|----------|----------------|----------|---------|
| `ci.yml` | ✅ Yes | ~8-10 min | Full integration testing on PRs |
| `release.yml` | ✅ Yes | ~10-12 min | Validation before release |
| `dependency-updates.yml` | ❌ No | ~2-3 min | Composer package validation |

See `docs/WORKFLOWS.md` for complete workflow documentation.

### Local Quality Checks

```bash
# Run all quality checks (matches CI)
./scripts/run-quality-checks.sh all

# Quick checks (skip WordPress setup)
./scripts/run-quality-checks.sh --skip-wp-setup phpcs phpstan
```

## 🚀 Release Process

### Version Numbering

Follow **Semantic Versioning** (major.minor.patch):
- **Major**: Breaking changes
- **Minor**: New features (backward compatible)
- **Patch**: Bug fixes

### Creating a Release

```bash
# Update version in files
./scripts/update-version.sh 1.2.0

# Build release package
./scripts/build-release.sh 1.2.0

# Create git tag
git tag -a v1.2.0 -m "Release version 1.2.0"
git push origin v1.2.0
```

See `docs/RELEASE_PROCESS.md` for complete release documentation.

## 🎯 Extending the Plugin

### Custom Field Type Support

Add support for custom ACF field types:

```php
add_filter('silver_acf_clone_process_field_type', function($value, $field, $type) {
    if ($type === 'my_custom_type') {
        return $this->process_custom_field($value, $field);
    }
    return $value;
}, 10, 3);
```

### Additional Post Type Support

Enable cloning for custom post types:

```php
add_filter('silver_acf_clone_enabled_post_types', function($types) {
    $types[] = 'my_custom_post_type';
    return $types;
});
```

## 📊 AJAX API Reference

The plugin provides secure AJAX endpoints for field cloning operations.

See `docs/AJAX_API_REFERENCE.md` for complete API documentation.

## 🐛 Debugging

### Enable Debug Mode

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Plugin-specific debug
define('SILVER_ACF_CLONE_DEBUG', true);
```

### Common Issues

**"Cannot redeclare function" errors**
- Bootstrap auto-detection handles this
- Don't define WordPress functions/constants manually

**Tests fail with database errors**
- Verify MySQL is running
- Check WordPress test suite installation
- Re-run: `bash scripts/install-wp-tests.sh wordpress_test root '' localhost latest true`

**PHPStan memory errors**
- Increase memory: `php -d memory_limit=512M vendor/bin/phpstan analyse`

## 📞 Support

- **Issues**: [GitHub Issues](https://github.com/silverassist/acf-clone-fields/issues)
- **Documentation**: See `docs/` directory
- **Workflows**: See `docs/WORKFLOWS.md`
- **Release Process**: See `docs/RELEASE_PROCESS.md`

## 📄 License

This plugin is licensed under the [PolyForm Noncommercial License 1.0.0](https://polyformproject.org/licenses/noncommercial/1.0.0/).

---

Made with ❤️ by Silver Assist
