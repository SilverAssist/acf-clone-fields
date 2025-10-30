# Silver Assist ACF Clone Fields - AI Agent Instructions

## 🎯 Project Overview

This is a **WordPress plugin** called "Silver Assist ACF Clone Fields" - a sophisticated ACF field cloning system with granular selection capabilities, real-time conflict detection, and professional-grade architecture following SilverAssist ecosystem standards.

**Plugin Location**: `wp-content/plugins/silver-assist-acf-clone-fields/`  
**Tech Stack**: WordPress 5.0+, **PHP 8.2+ (Required)**, ACF Pro, SilverAssist Packages  
**PHP Features**: PSR-4 Autoloading, LoadableInterface Pattern, Modern PHP 8.2 Features  
**License**: PolyForm Noncommercial License 1.0.0  
**Last Updated**: January 2025 (SilverAssist Standards Applied)

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
- **Singleton Pattern**: Service classes use singleton with `get_instance()` method
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
    
    public static function get_instance(): YourService {
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
- Register in `includes/Core/Plugin.php::init()` method
- Follow LoadableInterface pattern for consistent initialization
- Set priority: Core (10), Services (20), Admin (30), Assets (40)

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
├── Utils/                          # Utility classes
│   ├── Helpers.php                # Global helper functions
│   └── Logger.php                 # Logging functionality
└── Assets/                         # Frontend asset management
    └── AssetLoader.php            # CSS/JS loading

# Frontend assets
assets/
├── css/admin.css                  # Admin interface styles
├── js/admin.js                    # Admin interface functionality
└── images/                        # Plugin icons and images
```

**No compilation needed** - CSS and JS files are used as-is for simplicity and maintainability.

### **File Structure**
```
silver-assist-acf-clone-fields/
├── silver-assist-acf-clone-fields.php (main plugin file)
├── composer.json (dependencies & autoloading)
├── includes/ (PSR-4 classes)
├── assets/
│   ├── css/admin.css (admin interface styles)
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

### **PSR-4 Implementation Rules**
1. **Namespace Root**: `SilverAssist\ACFCloneFields\` maps to `includes/`
2. **Class Files**: PascalCase matching class names exactly
3. **Directory Structure**: PascalCase directories (e.g., `Core/`, `Admin/`)
4. **File Naming**: Must match class names exactly (e.g., `Plugin.php`)

### **SilverAssist Package Integration**

#### **wp-github-updater Integration**
- Enables native WordPress updates from GitHub repository
- Configured in main plugin file header with `GitHub Plugin URI`
- Automatic update checks and notifications

#### **wp-settings-hub Integration**
- Centralizes all SilverAssist plugins in unified admin menu
- Provides consistent settings interface across plugins
- Shared branding and navigation patterns

### **Component Loading Pattern**
```php
// All components implement LoadableInterface
class ComponentName implements LoadableInterface {
    public function init(): void {
        // Component initialization
    }
    
    public function get_priority(): int {
        return 20; // Loading priority
    }
    
    public function should_load(): bool {
        return is_admin(); // Conditional loading
    }
}
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
- **Get Source Posts**: Dynamic loading of posts for selection
- **Preview Fields**: Real-time field preview for source post
- **Clone Fields**: Execute cloning operation with progress feedback
- **Validation**: Server-side validation of clone operations

## 🧪 Testing Strategy

### **Test Coverage Requirements**
- **Unit Tests**: All core classes (Plugin, FieldCloner, FieldDetector)
- **Integration Tests**: ACF integration, WordPress hooks
- **Admin Tests**: Meta box functionality, settings page
- **AJAX Tests**: All AJAX endpoints with mocked requests

### **Quality Assurance**
```bash
# Code quality pipeline
composer phpcbf          # Auto-fix standards
composer phpcs           # Check standards
composer phpstan         # Static analysis level 8
composer test            # Run PHPUnit tests
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
                __('Clone Custom Fields', SILVER_ACF_CLONE_TEXT_DOMAIN),
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
        wp_die(__('Insufficient permissions.', SILVER_ACF_CLONE_TEXT_DOMAIN));
    }
    
    // Sanitize input
    $source_id = absint($_POST['source_id']);
    $target_id = absint($_POST['target_id']);
    $field_keys = array_map('sanitize_key', $_POST['field_keys']);
    
    // Execute cloning
    $result = $this->field_cloner->cloneFields($source_id, $target_id, $field_keys);
    
    wp_send_json_success(['message' => __('Fields cloned successfully!', SILVER_ACF_CLONE_TEXT_DOMAIN)]);
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
__('Clone Custom Fields', SILVER_ACF_CLONE_TEXT_DOMAIN)
_e('Select source post:', SILVER_ACF_CLONE_TEXT_DOMAIN)
_n('%s field cloned', '%s fields cloned', $count, SILVER_ACF_CLONE_TEXT_DOMAIN)
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
composer quality  # Run all quality checks
```

### **Testing Commands**
```bash
composer test                    # Run all tests
composer test:coverage          # Generate coverage report
phpunit tests/Unit/PluginTest.php  # Run specific test
```

### **Quality Assurance**
```bash
composer phpcbf     # Auto-fix coding standards
composer phpcs      # Check coding standards  
composer phpstan    # Static analysis
```

This plugin extends the WellSpring theme's ACF functionality while maintaining the same high standards of code quality, security, and performance.