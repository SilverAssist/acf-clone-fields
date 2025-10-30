# Silver Assist - ACF Clone Fields

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![License](https://img.shields.io/badge/license-PolyForm%20Noncommercial-red.svg)
![PHP](https://img.shields.io/badge/php-8.2%2B-purple.svg)
![WordPress](https://img.shields.io/badge/wordpress-5.0%2B-blue.svg)
![ACF Pro](https://img.shields.io/badge/acf_pro-required-orange.svg)

Advanced ACF field cloning system that allows selective copying of custom fields between posts of the same type. Features granular field selection, sidebar interface, and intelligent repeater field cloning.

## Features

### Core Functionality
- **Granular Field Selection**: Choose specific individual fields to clone rather than copying entire field groups
- **3-Step Modal Interface**: User-friendly modal with guided process (Source → Fields → Execute)
- **Real-time Field Analysis**: Live display of field counts, values, and conflict detection
- **Post Type Configuration**: Enable/disable cloning for specific post types via Settings Hub
- **Same Post Type Cloning**: Clone fields between posts of the same post type only

### User Experience
- **Smart Field Organization**: Fields grouped by ACF field groups for easy navigation
- **Visual Conflict Detection**: Clear warnings when fields will overwrite existing data
- **Field Value Indicators**: See which source fields have content before cloning
- **Bulk Selection Tools**: Select/deselect entire field groups with one click
- **Success Feedback**: Detailed results showing exactly which fields were cloned

### Integration & Management
- **Settings Hub Integration**: Centralized configuration under Silver Assist → Settings Hub
- **Meta Box Interface**: Clean sidebar integration in post edit screens
- **Overwrite Control**: Choose whether to overwrite existing field values
- **Backup System Ready**: UI prepared for future backup functionality (TODO)
- **GitHub Updater Support**: Automated updates via GitHub releases

## Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 8.2 or higher  
- **ACF Pro**: Advanced Custom Fields Pro plugin
- **SilverAssist Packages**: 
  - `silverassist/wp-github-updater` (automatic updates)
  - `silverassist/wp-settings-hub` (settings management)

## 📦 Installation & Setup

### Installation Methods

**WordPress Admin Dashboard** *(Recommended)*
1. Download the latest `silver-assist-acf-clone-fields.zip` from [GitHub Releases](https://github.com/silverassist/acf-clone-fields/releases)
2. Go to **Plugins → Add New → Upload Plugin**
3. Choose the ZIP file and click **Install Now**
4. Click **Activate Plugin**

**Manual FTP Upload**
1. Download and extract the ZIP file to get the `silver-assist-acf-clone-fields` folder
2. Upload the folder to `/wp-content/plugins/` via FTP
3. Activate from **WordPress Admin → Plugins**

**WP-CLI** *(Advanced)*
```bash
wp plugin install silver-assist-acf-clone-fields.zip --activate
```

### Dependencies Installation

The plugin includes all required SilverAssist composer packages. No additional installation steps needed - everything works out of the box after plugin activation.

## Configuration

### Post Type Settings

1. Navigate to **Silver Assist → Settings Hub** in your WordPress admin
2. Click on **ACF Clone Fields** in the plugins list
3. Select which post types should support field cloning
4. Configure default cloning options (backup settings, field behavior)
5. Save settings

### GitHub Updater

The plugin includes automatic update functionality via GitHub releases. No additional configuration is needed - updates will appear in your WordPress admin when new versions are released.

## Usage

### Step-by-Step Cloning Process

#### Step 1: Access the Clone Interface

1. **Edit Any Post**: Open any post for editing that belongs to an enabled post type
2. **Locate Meta Box**: Find "Clone Custom Fields" meta box in the editor sidebar (right panel)
3. **Click Clone Button**: Click "Clone Fields from Another Post" to open the modal

#### Step 2: Select Source Post (Modal Opens)

1. **Source Post Selection**: The modal displays a list of available posts from the same post type
2. **Post Statistics**: Each post shows field statistics (total fields, fields with values, etc.)
3. **Choose Source**: Select the post you want to copy fields from
4. **Click Continue**: Proceed to field selection

#### Step 3: Select Fields to Clone

1. **Field Groups Display**: ACF field groups are shown as organized sections
2. **Individual Field Selection**: Check/uncheck individual fields within each group
3. **Field Information Display**:
   - Field type (text, image, repeater, etc.)
   - Whether the field has a value in the source post
   - Warning indicators for fields that will overwrite existing data
4. **Selection Summary**: Real-time count shows selected fields and potential conflicts
5. **Select All/None**: Quick selection buttons for entire groups

#### Step 4: Configure Options & Execute

1. **Clone Options**:
   - ✅ **Create Backup**: Creates backup before cloning (recommended)
   - ✅ **Overwrite Existing**: Replaces existing field values (enabled by default)
   - **Preserve Empty**: Option to skip empty fields
2. **Review Summary**: Final count of fields to be cloned
3. **Execute Clone**: Click "Clone Selected Fields" to start the process
4. **Success Confirmation**: Modal shows results with cloned field count

### Field Selection Features

- **Smart Grouping**: Fields organized by ACF field groups for easy navigation
- **Visual Indicators**: 
  - 🟢 Fields with values in source
  - ⚠️ Fields that will overwrite existing data
  - 📁 Group and repeater field types
- **Real-time Feedback**: Live count of selected fields and potential overwrites
- **Bulk Selection**: Select/deselect entire field groups with one click

## Technical Architecture

### PSR-4 Autoloading

The plugin follows PSR-4 standards with namespace `SilverAssist\ACFCloneFields`:

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

### SilverAssist Package Integration

The plugin integrates with SilverAssist ecosystem packages:

#### GitHub Updater Integration

```php
// Automatic updates from GitHub releases
$config = new \SilverAssist\WpGithubUpdater\UpdaterConfig(
    __FILE__,
    'SilverAssist/acf-clone-fields',
    [
        'plugin_name' => 'Silver Assist - ACF Clone Fields',
        'requires_wordpress' => '5.0',
        'requires_php' => '8.2',
        'asset_pattern' => 'acf-clone-fields-v{version}.zip',
        'text_domain' => 'silver-assist-acf-clone-fields',
    ]
);
new \SilverAssist\WpGithubUpdater\Updater($config);
```

#### Settings Hub Integration

```php
// Centralized admin menu under "Silver Assist"
$hub = \SilverAssist\SettingsHub\SettingsHub::get_instance();
$hub->register_plugin(
    'acf-clone-fields',
    'ACF Clone Fields',
    [$this, 'render_settings_page'],
    [
        'description' => 'Advanced ACF field cloning with granular selection',
        'version' => '1.0.0',
    ]
);
```

### Component Loading System

Uses `LoadableInterface` for modular component architecture:

```php
interface LoadableInterface {
    public function init(): void;
    public function get_priority(): int;
    public function should_load(): bool;
}
```

### AJAX Endpoints

The plugin registers secure AJAX endpoints:

- `acf_clone_get_source_posts` - Retrieve available source posts
- `acf_clone_get_source_fields` - Get field data from source post
- `acf_clone_execute_clone` - Execute the cloning operation
- `acf_clone_validate_selection` - Validate field selection before cloning

## Field Type Support

### Supported ACF Field Types

- **Text/Textarea**: Basic text content
- **Number/Email/URL**: Formatted input fields  
- **Select/Radio/Checkbox**: Choice-based fields
- **Image/Gallery**: Media attachments with proper URL handling
- **Date/Time**: Temporal data with format preservation
- **Repeater**: Complex repeater fields with sub-field processing
- **Group**: Grouped field sets with nested structure
- **Post Object/Relationship**: Post relationships with ID mapping
- **User**: User field references
- **Taxonomy**: Term relationships

### Field Processing Features

- **Type-Specific Handling**: Each field type has specialized processing logic
- **Attachment Management**: Proper handling of media files and galleries
- **Relationship Preservation**: Maintains post/user/term relationships
- **Nested Structure Support**: Complete support for repeater and group sub-fields
- **Data Validation**: Comprehensive validation before and after cloning

## Security & Performance

### Security Measures

- **Nonce Verification**: All AJAX requests verified with WordPress nonces
- **User Capability Checks**: Proper permission validation for edit operations
- **Data Sanitization**: All input data properly sanitized
- **SQL Injection Prevention**: Uses WordPress database abstraction layer
- **XSS Protection**: All output properly escaped

### Performance Optimizations

- **Caching**: Intelligent caching of field group queries
- **Lazy Loading**: Components loaded only when needed
- **Database Optimization**: Minimal database queries with batch operations
- **Asset Loading**: CSS/JS loaded only on relevant admin pages
- **Memory Management**: Efficient memory usage for large field sets

### Error Handling

- **Comprehensive Logging**: PSR-3 compliant logging system
- **User Feedback**: Clear error messages and success notifications
- **Rollback Capability**: Automatic rollback on failed operations
- **Debug Mode**: Enhanced debugging information in development

## Development

### Local Development Setup

```bash
# Clone repository
git clone https://github.com/silverassist/acf-clone-fields.git
cd acf-clone-fields

# Install dependencies
composer install

# Install development dependencies
composer install --dev

# Run code quality checks
composer run-script phpcs
composer run-script phpstan
```

### Coding Standards

- **PSR-4**: Strict adherence to PSR-4 autoloading
- **WordPress Coding Standards**: Follows official WordPress guidelines
- **PHP 8.2+**: Modern PHP features and type hints
- **PHPStan Level 8**: Static analysis for type safety
- **PHPUnit Testing**: Comprehensive unit test coverage

### Extending the Plugin

#### Custom Field Type Support

Add support for custom ACF field types:

```php
// Add filter to extend field type processing
add_filter('silver_acf_clone_process_field_type', function($value, $field, $type) {
    if ($type === 'my_custom_type') {
        return $this->process_custom_field($value, $field);
    }
    return $value;
}, 10, 3);
```

#### Additional Post Type Support

Enable cloning for custom post types:

```php
// Add post type to enabled types
add_filter('silver_acf_clone_enabled_post_types', function($types) {
    $types[] = 'my_custom_post_type';
    return $types;
});
```

## Troubleshooting

### Common Issues

#### "No Source Posts Available"

**Cause**: No posts of the same type with ACF fields found

**Solution**: 

1. Verify ACF fields exist on other posts of the same type
2. Check post type is enabled in **Silver Assist → Settings Hub → ACF Clone Fields**
3. Ensure user has proper edit permissions for the current post type
4. Confirm ACF Pro is active and field groups are assigned to the post type

#### "Clone Operation Failed - Fields Not Copying"

**Cause**: AJAX communication issues or field processing errors

**Solution**:

1. Check browser console for JavaScript errors during the clone process
2. Verify all selected fields completed the clone operation in the modal result
3. Check WordPress debug.log for detailed PHP errors
4. Ensure ACF Pro is active and updated to latest version
5. Verify sufficient memory limit for large field sets (especially repeaters)

#### "Modal Not Opening"

**Cause**: JavaScript conflicts or missing dependencies

**Solution**:

1. Check browser console for JavaScript errors when clicking the clone button
2. Verify jQuery is loaded on the post edit page
3. Test for theme/plugin conflicts by deactivating other plugins temporarily
4. Clear browser cache and reload the page
5. Ensure the post type is enabled in plugin settings

#### "Fields Not Overwriting Existing Values"

**Cause**: Clone options configuration or field processing issues

**Solution**:

1. Verify "Overwrite Existing" option is enabled in the modal (Step 4)
2. Check that the source post actually has values in the selected fields
3. Confirm field types are supported for cloning operations
4. Review field conflict warnings in the modal for guidance

### Debug Mode

Enable debug mode for detailed logging:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Plugin-specific debug
define('SILVER_ACF_CLONE_DEBUG', true);
```

### Performance Issues

For large datasets:

```php
// Increase memory limit
ini_set('memory_limit', '512M');

// Increase execution time
ini_set('max_execution_time', 300);
```

## License

This plugin is licensed under the [PolyForm Noncommercial License 1.0.0](https://polyformproject.org/licenses/noncommercial/1.0.0/).

**Key Points:**
- ✅ Free for personal, educational, and noncommercial use
- ❌ Commercial use requires a separate license

---

Made with ❤️ by Silver Assist