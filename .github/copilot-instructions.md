# Silver Assist ACF Clone Fields - AI Agent Instructions

## 🎯 Project Overview

This is a **WordPress plugin** called "Silver Assist ACF Clone Fields" - a sophisticated ACF field cloning system with granular selection capabilities, real-time conflict detection, and professional-grade architecture following SilverAssist ecosystem standards.

**IMPORTANT**: All documentation, code comments, commit messages, and technical writing **MUST be in English**.

**Plugin Location**: `wp-content/plugins/silver-assist-acf-clone-fields/`  
**Tech Stack**: WordPress 5.0+, **PHP 8.2+ (Required)**, ACF (free or Pro), SilverAssist Packages  
**ACF Compatibility**: Works with **ACF free** (basic fields) and **ACF Pro** (advanced fields like repeater, group, flexible content)  
**PHP Features**: PSR-4 Autoloading, LoadableInterface Pattern, Modern PHP 8.2 Features  
**License**: PolyForm Noncommercial License 1.0.0  
**Version**: 1.1.0  
**Last Updated**: January 2025

## 📝 Documentation Standards

**CRITICAL RULE**: All documentation MUST be written in English.

- ✅ **Code comments**: English only
- ✅ **Commit messages**: English only
- ✅ **Documentation files**: English only
- ✅ **PHPDoc blocks**: English only
- ✅ **README files**: English only
- ✅ **Error messages**: English only (user-facing messages use WordPress i18n)

**Documentation Location**: 
- `README.md` - User-facing documentation (installation, usage, troubleshooting)
- `CONTRIBUTING.md` - Developer documentation (setup, standards, workflows, extending)
- `docs/` - Technical documentation (workflows, API reference, release process)
- `tests/README.md` - Testing documentation (running tests, writing tests)
- Keep only essential, relevant documentation
- Remove temporary analysis documents
- Focus on user needs and developer workflows

**Current Documentation**:
- `README.md` - User guide (installation, features, usage, troubleshooting)
- `CONTRIBUTING.md` - Developer guide (setup, coding standards, testing, extending)
- `docs/WORKFLOWS.md` - CI/CD workflow architecture
- `docs/AJAX_API_REFERENCE.md` - AJAX endpoints reference
- `docs/RELEASE_PROCESS.md` - Release process guide
- `tests/README.md` - Testing guide (running tests, coverage, troubleshooting)

## 🚀 Essential Commands & Quick Start

**All commands run from plugin directory: `wp-content/plugins/silver-assist-acf-clone-fields/`**

```bash
# MANDATORY: Complete quality checks before commits
./scripts/run-quality-checks.sh

# Individual quality checks
vendor/bin/phpcbf                    # Auto-fix code standards
vendor/bin/phpcs                     # Check WPCS compliance  
php -d memory_limit=512M vendor/bin/phpstan analyse --no-progress  # Level 8 static analysis

# Build production release
./scripts/build-release.sh

# Component-specific analysis
php -d memory_limit=512M vendor/bin/phpstan analyse includes/Services/ --no-progress
```

## 📋 GitHub CLI Usage (MANDATORY)

**CRITICAL**: Always use `PAGER=cat` or pipe to `cat` when using `gh` CLI to avoid interactive pager issues:

```bash
# ✅ CORRECT: GitHub CLI commands (use one of these patterns)
PAGER=cat gh run list --limit 10
gh run list --limit 10 | cat
PAGER=cat gh workflow list
gh pr list | cat
PAGER=cat gh repo view --json description

# ❌ INCORRECT: Never use gh without pager control
gh run list --limit 10              # May hang waiting for pager input
gh workflow list                     # Interactive pager causes issues

# Common GitHub CLI operations
PAGER=cat gh run list --limit 5      # Check recent workflow runs
PAGER=cat gh workflow list           # List all workflows
PAGER=cat gh release list --limit 3  # Check releases
gh pr status | cat                   # PR status without pager
```

## 🏗️ Core Architecture (SilverAssist Standards)

**Design Patterns**:
- **PSR-4 Namespace**: `SilverAssist\ACFCloneFields\` → `includes/` (PascalCase files/dirs)
- **LoadableInterface**: All components implement priority-based loading system
- **Singleton Pattern**: Service classes use singleton with `instance()` method
- **SilverAssist Packages**: Integration with `wp-github-updater` and `wp-settings-hub`
- **WordPress Standards**: Full WPCS compliance with custom PHPCS ruleset

**File Creation Rules**:
- PHP classes: `PascalCase.php` following PSR-4 structure
- Always implement `LoadableInterface` for new components
- Start files with `defined('ABSPATH') || exit;`
- Use SilverAssist package integrations for updates and settings

**CRITICAL**: These standards apply to Silver Assist ACF Clone Fields plugin specifically.

## 🤖 AI Agent Workflow

### **Before Coding**
1. Navigate to plugin: `cd wp-content/plugins/silver-assist-acf-clone-fields`
2. Understand component: Check `includes/Core/Plugin.php` for architecture
3. Review services: All business logic in `includes/Services/` directory

### **Code Creation Pattern**
```php
// 1. Always start PHP files with
<?php
defined('ABSPATH') || exit;

// 2. Use proper namespace (PSR-4)
namespace SilverAssist\ACFCloneFields\ComponentName;

// 3. Import LoadableInterface for components
use SilverAssist\ACFCloneFields\Core\Interfaces\LoadableInterface;

// 4. Implement singleton pattern for services
class YourService {
    private static ?YourService $instance = null;
    
    public static function instance(): YourService {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
```

### **Quality Check (MANDATORY)**
```bash
./scripts/run-quality-checks.sh     # Complete quality pipeline
vendor/bin/phpcbf                   # Fix formatting
vendor/bin/phpcs                    # Validate standards
php -d memory_limit=512M vendor/bin/phpstan analyse includes/YourComponent/ --no-progress
```

### **Component Integration**
- Add new components to appropriate namespace
- Register in `includes/Core/Plugin.php::load_components()` method
- Follow LoadableInterface pattern for consistent initialization
- Set priority: Core (10), Services (20), Admin (30), Assets (40)
- Components are loaded via Loader classes (`Services\Loader`, `Admin\Loader`)

## 🔧 Plugin Architecture

**IMPORTANT**: This plugin uses a service-oriented architecture with clear separation of concerns.

```bash
# Core structure
includes/
├── Core/                           # Core plugin functionality
│   ├── Interfaces/LoadableInterface.php  # Component contract
│   └── Plugin.php                  # Main plugin bootstrap
├── Services/                       # Business logic services
│   ├── FieldDetector.php          # ACF field detection service
│   └── FieldCloner.php            # Field cloning operations
├── Admin/                          # WordPress admin integration
│   ├── MetaBox.php                # Post edit screen integration
│   ├── Settings.php               # Plugin settings management
│   └── Ajax.php                   # AJAX request handling
└── Utils/                          # Utility classes
    ├── Helpers.php                # Global helper functions
    └── Logger.php                 # Logging functionality

# Frontend assets (no compilation needed)
assets/
├── css/silver-acf-clone-fields.css # Admin interface styles
└── js/admin.js                    # Admin interface functionality
```

**No compilation needed** - CSS and JS files are used as-is for simplicity and maintainability.

### **File Structure**
```
silver-assist-acf-clone-fields/
├── silver-assist-acf-clone-fields.php (main plugin file)
├── composer.json (dependencies & autoloading)
├── includes/ (PSR-4 classes)
├── assets/
│   ├── css/silver-acf-clone-fields.css (admin interface styles)
│   └── js/admin.js (frontend interactions)
├── languages/ (i18n files)
├── tests/ (PHPUnit tests)
└── .github/copilot-instructions.md (this file)
```

## 🛠️ Development Standards

### **Code Quality Requirements**
- **PHP 8.2+ Features**: Use modern PHP features (enums, readonly properties, union types)
- **Singleton Pattern**: Main classes use `instance()` method
- **LoadableInterface**: All components implement priority-based loading
- **WordPress Standards**: Follow WPCS, escape outputs, sanitize inputs
- **Security First**: Nonces for forms, capability checks, data validation

### **WordPress Naming Conventions (CRITICAL)**

**Plugin Prefix**: `silver_acf_clone_`

#### **Global Variables**
ALL global variables MUST be prefixed with `silver_acf_clone_` to comply with WordPress.NamingConventions.PrefixAllGlobals:

```php
// ✅ CORRECT: Prefixed global variables
$silver_acf_clone_autoload_path      = SILVER_ACF_CLONE_PATH . 'vendor/autoload.php';
$silver_acf_clone_real_autoload_path = realpath( $silver_acf_clone_autoload_path );
$silver_acf_clone_plugin_real_path   = realpath( SILVER_ACF_CLONE_PATH );

// ❌ INCORRECT: Non-prefixed global variables (WPCS violation)
$autoload_path      = SILVER_ACF_CLONE_PATH . 'vendor/autoload.php';
$real_autoload_path = realpath( $autoload_path );
$plugin_real_path   = realpath( SILVER_ACF_CLONE_PATH );
```

#### **Inline Comments**
ALL inline comments MUST end with proper punctuation (`.`, `!`, or `?`) to comply with Squiz.Commenting.InlineComment:

```php
// ✅ CORRECT: Comment ends with period.
// Validate: both paths resolve, autoloader is inside plugin directory.

// ❌ INCORRECT: Comment missing punctuation (WPCS violation)
// Validate: both paths resolve, autoloader is inside plugin directory
```

#### **Naming Convention Rules Summary**
1. **Global variables**: Prefix with `silver_acf_clone_`
2. **Functions**: Prefix with `silver_acf_clone_`
3. **Constants**: Prefix with `SILVER_ACF_CLONE_`
4. **Classes**: Use namespaced classes (no prefix needed due to PSR-4)
5. **Inline comments**: Always end with `.`, `!`, or `?`
6. **PHPDoc blocks**: Follow WordPress documentation standards

**Reference**: These rules prevent conflicts with other plugins/themes and ensure WPCS compliance.

### **PSR-4 Implementation Rules**
1. **Namespace Root**: `SilverAssist\ACFCloneFields\` maps to `includes/`
2. **Class Files**: PascalCase matching class names exactly
3. **Directory Structure**: PascalCase directories (e.g., `Core/`, `Admin/`)
4. **File Naming**: Must match class names exactly (e.g., `Plugin.php`)

### **SilverAssist Package Integration**

#### **wp-github-updater Integration**
- Enables native WordPress updates from GitHub repository
- Configured programmatically via `UpdaterConfig` class (NOT via plugin headers)
- Automatic update checks and notifications
- Uses official WordPress `Update URI` header to prevent update conflicts

#### **wp-settings-hub Integration**
- Centralizes all SilverAssist plugins in unified admin menu
- Provides consistent settings interface across plugins
- Shared branding and navigation patterns
- Uses `SettingsHub::get_instance()` (note different pattern from plugin singletons)

### **Component Loading Pattern**
```php
// All components implement LoadableInterface with priority-based loading
class ComponentName implements LoadableInterface {
    public function init(): void {
        // Component initialization - called by Plugin::load_components()
    }
    
    public function get_priority(): int {
        return 20; // Loading priority (10=Core, 20=Services, 30=Admin, 40=Assets)
    }
    
    public function should_load(): bool {
        return is_admin(); // Conditional loading based on context
    }
}

// Components are loaded via Loader classes (Admin\Loader, Services\Loader)
// which are automatically discovered and initialized by Plugin::load_components()
```

## 🎨 User Interface Specifications

### **Meta Box in Sidebar**
- **Location**: Post edit screen sidebar (high priority)
- **Visibility**: Only on enabled post types
- **Components**:
  - Source post selector (dropdown)
  - Available fields preview
  - Field selection checkboxes (hierarchical)
  - Clone button with confirmation
  - Status messages

### **Admin Settings Page**
- **Integration**: Uses wp-settings-hub for unified interface
- **Settings**:
  - Enabled post types (multiselect)
  - Default clone behavior
  - Confirmation messages
  - Logging options

### **Field Selection Interface**
- **Hierarchical Display**: Groups → Fields → Sub-fields
- **Smart Grouping**: Group repeaters, show individual fields
- **Preview Mode**: Show field types and current values
- **Batch Selection**: Select all fields in a group

## 🔧 Core Functionality Implementation

### **Field Detection Logic**
```php
// FieldDetector identifies available ACF fields
class FieldDetector {
    public function getAvailableFields(int $postId): array;
    public function getFieldGroups(string $postType): array;
    public function getRepeaterSubFields(array $fieldConfig): array;
}
```

### **Field Cloning Logic**
```php
// FieldCloner handles the actual field copying
class FieldCloner {
    public function cloneFields(int $sourceId, int $targetId, array $fieldKeys): bool;
    public function cloneRepeaterField(int $sourceId, int $targetId, string $fieldKey): bool;
    public function validateFieldCompatibility(array $sourceField, array $targetField): bool;
}
```

### **AJAX Integration**
- **Get Source Posts**: `acf_clone_get_source_posts` - Dynamic loading of posts for selection
- **Preview Fields**: `acf_clone_get_source_fields` - Real-time field preview for source post
- **Clone Fields**: `acf_clone_execute_clone` - Execute cloning operation with progress feedback
- **Validation**: `acf_clone_validate_selection` - Server-side validation of clone operations

### **Modal Interface Architecture**
The plugin uses a 3-step modal interface implemented via `assets/js/admin.js`:
1. **Source Selection**: Choose post to clone from (same post type only)
2. **Field Selection**: Granular field selection with conflict detection
3. **Execution**: Clone operation with real-time progress feedback

**Critical**: All AJAX endpoints require proper nonce verification (`wp_ajax_*` actions) and `edit_posts` capability checks.

## 🧪 Testing Strategy

### **WordPress Test Suite Integration**

**CRITICAL**: This plugin uses **WordPress Test Suite** with real WordPress environment, NOT mocks.

#### **Test Base Class: WP_UnitTestCase**
All tests extend `TestCase` which conditionally extends `WP_UnitTestCase` when WordPress is available:

```php
namespace SilverAssist\ACFCloneFields\Tests\Utils;

// Conditional base class - extends WP_UnitTestCase when available
if (class_exists('WP_UnitTestCase')) {
    abstract class TestCase extends \WP_UnitTestCase {
        // Real WordPress environment
    }
} else {
    abstract class TestCase extends \PHPUnit\Framework\TestCase {
        // Fallback for static analysis
    }
}
```

#### **WordPress Factory Pattern (MANDATORY)**

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

#### **Factory Methods Available**
```php
static::factory()->post->create([...]);      // Create posts
static::factory()->user->create([...]);      // Create users
static::factory()->comment->create([...]);   // Create comments
static::factory()->term->create([...]);      // Create terms
static::factory()->category->create([...]);  // Create categories
```

#### **Real WordPress Functions in Tests**

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

#### **Test Organization**

```
tests/
├── Unit/                          # Unit tests (isolated components)
│   ├── Core/
│   │   └── PluginTest.php        # Plugin class tests
│   ├── Services/
│   │   ├── FieldClonerTest.php   # FieldCloner tests
│   │   └── FieldDetectorTest.php # FieldDetector tests
│   ├── BackupSystemTest.php      # Backup functionality
│   ├── HelpersTest.php           # Utility helpers
│   └── LoggerTest.php            # Logger functionality
├── Integration/                   # Integration tests (WordPress integration)
│   ├── AdminComponentsTest.php   # Admin layer integration
│   └── CloneOptionsTest.php      # End-to-end clone operations
└── Utils/
    ├── TestCase.php              # Base test class
    └── ACFTestHelpers.php        # ACF-specific test utilities
```

#### **Database Schema Changes in Tests (CRITICAL)**

**IMPORTANT**: `CREATE TABLE` statements trigger implicit MySQL `COMMIT` which breaks WordPress Test Suite's transaction-based rollback system.

**The Problem:**
- WordPress Test Suite wraps each test in a MySQL transaction (`START TRANSACTION`)
- After each test, it rolls back the transaction (`ROLLBACK`) to restore clean state
- **`CREATE TABLE` triggers an implicit COMMIT**, breaking this rollback mechanism
- Result: Tables persist incorrectly or disappear unexpectedly between tests

**The Solution - Use wpSetUpBeforeClass():**

```php
// ✅ CORRECT: Schema changes in wpSetUpBeforeClass()
class BackupSystemTest extends TestCase {
    /**
     * Create shared fixtures before class
     *
     * Runs ONCE before any tests in the class.
     * Use this for CREATE TABLE statements.
     *
     * @param WP_UnitTest_Factory $factory Factory instance.
     */
    public static function wpSetUpBeforeClass( $factory ): void {
        global $wpdb;
        
        // CREATE TABLE happens outside transaction system
        // Safe to use here - runs once before all tests
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
        $this->service = FieldCloner::instance();
    }
    
    protected function clean_table_data(): void {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acf_field_backups';
        
        // TRUNCATE doesn't trigger COMMIT - safe for test isolation
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
- `CREATE TABLE` / `DROP TABLE` (use wpSetUpBeforeClass)
- `CREATE DATABASE` / `DROP DATABASE`
- `ALTER TABLE` (use wpSetUpBeforeClass)
- `RENAME TABLE`

**Safe for setUp() / tearDown():**
- `TRUNCATE TABLE` (safe in WordPress Test Suite context)
- `INSERT` / `UPDATE` / `DELETE` (regular DML)
- `SELECT` queries

**Reference**: 
- [MySQL Implicit Commit](https://dev.mysql.com/doc/refman/5.7/en/implicit-commit.html)
- [WordPress Testing Handbook - Database](https://make.wordpress.org/core/handbook/testing/automated-testing/writing-phpunit-tests/#database)

#### **Writing New Tests - Pattern**

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
}
```

### **Test Coverage Requirements**
- **Unit Tests**: All core classes (Plugin, FieldCloner, FieldDetector)
- **Integration Tests**: ACF integration, WordPress hooks
- **Admin Tests**: Meta box functionality, settings page
- **AJAX Tests**: All AJAX endpoints with real WordPress environment

### **Quality Assurance**
```bash
# Code quality pipeline
composer phpcbf          # Auto-fix standards
composer phpcs           # Check standards
composer phpstan         # Static analysis level 8
composer test            # Run all tests (phpcs + phpstan + phpunit)

# Run specific tests
vendor/bin/phpunit tests/Unit/Core/PluginTest.php
vendor/bin/phpunit --testdox                    # Human-readable output
vendor/bin/phpunit --coverage-text              # Coverage report (requires xdebug)
```

## 🔐 Security Implementation

### **Data Validation**
- **Nonce Verification**: All forms and AJAX requests
- **Capability Checks**: `edit_posts` capability required
- **Input Sanitization**: All user inputs sanitized
- **Output Escaping**: All outputs properly escaped

### **ACF Integration Security**
- **Field Validation**: Verify field exists before cloning
- **Type Checking**: Ensure field type compatibility
- **Permission Checks**: Verify user can edit target post
- **Data Integrity**: Validate cloned data before saving

## 📝 Coding Patterns & Examples

### **Main Plugin Class**
```php
class Plugin implements LoadableInterface {
    private static ?Plugin $instance = null;
    
    public static function instance(): Plugin {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function init(): void {
        $this->load_components();
        $this->init_hooks();
    }
}
```

### **Meta Box Implementation**
```php
class MetaBox implements LoadableInterface {
    public function init(): void {
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('wp_ajax_clone_acf_fields', [$this, 'handle_clone_request']);
    }
    
    public function add_meta_box(): void {
        $enabled_post_types = $this->get_enabled_post_types();
        foreach ($enabled_post_types as $post_type) {
            add_meta_box(
                'silver-acf-clone-fields',
                __('Clone Custom Fields', 'silver-assist-acf-clone-fields'),
                [$this, 'render_meta_box'],
                $post_type,
                'side',
                'high'
            );
        }
    }
}
```

### **AJAX Handler Pattern**
```php
public function handle_clone_request(): void {
    // Verify nonce
    check_ajax_referer('silver_acf_clone_nonce', 'nonce');
    
    // Check capabilities
    if (!current_user_can('edit_posts')) {
        wp_die(__('Insufficient permissions.', 'silver-assist-acf-clone-fields'));
    }
    
    // Sanitize input
    $source_id = absint($_POST['source_id']);
    $target_id = absint($_POST['target_id']);
    $field_keys = array_map('sanitize_key', $_POST['field_keys']);
    
    // Execute cloning
    $result = $this->field_cloner->cloneFields($source_id, $target_id, $field_keys);
    
    wp_send_json_success(['message' => __('Fields cloned successfully!', 'silver-assist-acf-clone-fields')]);
}
```

## 🌐 Internationalization

### **Text Domain**: `silver-assist-acf-clone-fields`
### **Translation Functions**
- Use `__()` for translatable strings
- Use `_e()` for echoing translated strings  
- Use `_n()` for plural forms
- Use `_x()` for contextual strings

### **String Examples**
```php
__('Clone Custom Fields', 'silver-assist-acf-clone-fields')
_e('Select source post:', 'silver-assist-acf-clone-fields')
_n('%s field cloned', '%s fields cloned', $count, 'silver-assist-acf-clone-fields')
```

## 📊 Performance Considerations

### **Caching Strategy**
- Cache field group configurations
- Cache post lists for source selection
- Use transients for expensive operations
- Implement cache invalidation on field changes

### **Database Optimization**
- Minimize database queries during cloning
- Use batch operations for repeater fields
- Implement progress tracking for large operations

## 🎯 User Experience Guidelines

### **Interface Principles**
- **Intuitive**: Clear field hierarchy and selection
- **Safe**: Confirmation dialogs for destructive operations
- **Responsive**: Real-time feedback during operations
- **Accessible**: Proper ARIA labels and keyboard navigation

### **Error Handling**
- **User-Friendly Messages**: Clear explanations of errors
- **Graceful Degradation**: Fallbacks for missing dependencies
- **Logging**: Detailed logs for debugging (when enabled)

## 🔄 Update & Maintenance

### **GitHub Integration**
- **Automatic Updates**: Via wp-github-updater package
- **Version Management**: Semantic versioning (major.minor.patch)
- **Release Notes**: Detailed changelog for each version

### **Backward Compatibility**
- **Settings Migration**: Handle settings schema changes
- **API Compatibility**: Maintain public method signatures
- **Database Updates**: Version-based database migrations

## 🎓 Development Workflow

### **Setup Commands**

```bash
cd wp-content/plugins/silver-assist-acf-clone-fields
composer install --no-interaction
composer test    # Run all quality checks (phpcs + phpstan + phpunit)
```

### **Testing Commands**

```bash
composer test                    # Run all tests (phpcs + phpstan + phpunit)
composer phpunit                 # Run PHPUnit tests only
phpunit tests/Unit/PluginTest.php  # Run specific test
vendor/bin/phpunit --coverage-html coverage/  # Generate coverage report
```

### **Quality Assurance**

```bash
composer phpcbf     # Auto-fix coding standards
composer phpcs      # Check coding standards  
composer phpstan    # Static analysis
```

### **CI/CD Error Handling Best Practices**

**CRITICAL**: Quality check scripts must return proper exit codes to fail CI workflows when errors occur.

#### **Problem Pattern (Bad)**
```bash
# ❌ INCORRECT: Command runs but doesn't fail workflow on errors
run_phpcs() {
    vendor/bin/phpcs --warning-severity=0
    echo "✅ PHPCS passed"  # Always prints, even if phpcs failed!
}
```

#### **Solution Pattern (Good)**
```bash
# ✅ CORRECT: Capture exit code and return appropriate value
run_phpcs() {
    if vendor/bin/phpcs --warning-severity=0; then
        echo "✅ PHPCS passed - No errors found"
        return 0
    else
        echo "❌ PHPCS failed - Code style errors found"
        return 1
    fi
}
```

#### **Quality Check Script Pattern**
All quality check functions in `scripts/run-quality-checks.sh` follow this pattern:

```bash
run_quality_check() {
    print_header "🔍 Running Quality Check"
    
    cd "$PROJECT_ROOT"
    
    # Run command and capture exit code
    if command_to_run --with-options; then
        print_success "Check passed"
        return 0
    else
        print_error "Check failed - errors found"
        return 1
    fi
}
```

**Benefits:**
- ✅ Workflows fail immediately when errors occur
- ✅ Clear error messages in CI logs
- ✅ Prevents merging code with quality issues
- ✅ Consistent behavior between local and CI environments

**Reference**: See `scripts/run-quality-checks.sh` for complete implementation.

## 🔄 CI/CD Workflows

**Complete documentation**: See `docs/WORKFLOWS.md`

### **Workflow Overview**

| Workflow | WordPress Tests | Duration | Purpose |
|----------|----------------|----------|---------|
| `ci.yml` | ✅ Yes | ~8-10 min | Full integration testing on PRs |
| `release.yml` | ✅ Yes | ~10-12 min | Exhaustive validation before release |
| `dependency-updates.yml` | ❌ No | ~2-3 min | Fast Composer package validation |

### **Key Scripts**

```bash
# Local quality checks
./scripts/run-quality-checks.sh all              # Full checks with WordPress
./scripts/run-quality-checks.sh --skip-wp-setup phpcs phpstan  # Quick checks

# Build release
./scripts/build-release.sh <version>             # Build production ZIP
```

### **Workflow Strategy**

**ci.yml** - Continuous Integration:
- Runs on every PR and push to main
- Full WordPress integration testing
- Matrix: PHP 8.2, 8.3, 8.4
- Security scan + compatibility tests

**release.yml** - Release Process:
- Triggered by git tags (`v*`) or manual dispatch
- Validates plugin structure and version
- Tests across PHP 8.2, 8.3, 8.4 with WordPress
- Builds and publishes ZIP to GitHub Releases

**dependency-updates.yml** - Dependency Management:
- Weekly automated checks (Mondays 9AM Mexico City)
- Detects outdated Composer packages
- Security audit with CVE detection
- Auto-merges safe updates (patch/minor)

### **Testing Philosophy**

**WordPress Integration**: `ci.yml` and `release.yml` use real WordPress Test Suite to:
- Detect integration issues early
- Validate plugin functionality with real WordPress
- Prepare for future ACF/ACF Pro integration tests
- Ensure professional quality standards

**Mock-Only Testing**: `dependency-updates.yml` skips WordPress because:
- Composer packages don't require WordPress validation
- Faster feedback for automated checks
- Enables quick auto-merge of safe updates

### **Bootstrap Auto-Detection**

The test bootstrap (`tests/bootstrap.php`) automatically detects WordPress availability:

```php
$_tests_dir = getenv('WP_TESTS_DIR');
$wp_tests_available = $_tests_dir && file_exists($_tests_dir . '/includes/functions.php');

if ($wp_tests_available) {
    // Load WordPress Test Suite
} else {
    // Load mocks
}
```

This allows tests to work both locally (with or without WordPress) and in CI/CD workflows.

This plugin extends the WellSpring theme's ACF functionality while maintaining the same high standards of code quality, security, and performance.