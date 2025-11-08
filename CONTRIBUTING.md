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

### Running Tests

```bash
# Quick tests (with mocks)
vendor/bin/phpunit --testsuite=unit

# Full tests (with WordPress)
WP_TESTS_DIR=/tmp/wordpress-tests-lib vendor/bin/phpunit --testsuite=unit

# With readable output
vendor/bin/phpunit --testsuite=unit --testdox

# Coverage report
vendor/bin/phpunit --coverage-html coverage/
```

### Test Structure

```
tests/
├── bootstrap.php          # Auto-detects WordPress availability
├── Unit/                  # Unit tests
│   ├── BackupSystemTest.php
│   ├── FieldDetectorTest.php
│   └── LoggerTest.php
└── Utils/                 # Testing utilities
    ├── TestCase.php
    ├── ACFTestHelpers.php
    └── WordPressMocks.php
```

### Writing Tests

1. Create file in `tests/Unit/` with `Test.php` suffix
2. Extend from `SilverAssist\ACFCloneFields\Tests\Utils\TestCase`
3. Implement methods with `test_` prefix

Example:

```php
<?php
namespace SilverAssist\ACFCloneFields\Tests\Unit;

use SilverAssist\ACFCloneFields\Tests\Utils\TestCase;

class MyFeatureTest extends TestCase {
    public function test_my_feature(): void {
        $result = $this->some_function();
        $this->assertTrue($result);
    }
}
```

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
