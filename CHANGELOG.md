# Changelog

All notable changes to Silver Assist ACF Clone Fields will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 2025-01-07

### Added
- **ACF Free Compatibility**: Plugin now works with both ACF (free) and ACF Pro
  - Automatic detection of installed ACF version
  - `Helpers::is_acf_pro_active()`: Detect if ACF Pro is installed
  - `Helpers::get_supported_field_types()`: Return available field types based on ACF version
  - `Helpers::is_field_type_supported()`: Validate field type compatibility
  - Smart field filtering: Pro-only fields (repeater, group, flexible_content, clone) excluded when only ACF free is active
  - No user notifications needed - seamless experience regardless of ACF version

- **Complete Backup System Implementation**: Full backup functionality for field cloning operations
  - `create_backup()` method: Automatically backs up field data before cloning with database storage
  - `restore_backup()` method: Restore previous field values from backups with success/error reporting
  - `delete_backup()` method: Remove individual backups programmatically
  - `get_post_backups()` method: Retrieve all backups for a specific post
  - Automatic backup table creation (`wp_acf_field_backups`) with proper indexes
  - Backup metadata: Timestamp, user ID, field count, and field details
  
- **Backup Management Interface**: New admin panel for backup operations
  - Meta box in post edit sidebar showing available backups
  - One-click restore functionality with confirmation dialog
  - Individual backup deletion with safety confirmations
  - Manual cleanup trigger for old backups
  - User-friendly display of backup date, field count, and creator
  - Real-time AJAX operations for seamless user experience
  
- **Configurable Retention Policies**: Smart backup cleanup system
  - Retention period setting (default: 30 days, configurable 1-365 days)
  - Maximum backup count setting (default: 100, configurable 10-1000)
  - Automatic cleanup triggered after each new backup creation
  - Dual cleanup strategy: Age-based and count-based limits
  - Settings integration in admin panel with validation
  
- **Comprehensive Test Coverage**: Unit tests for backup functionality
  - Backup creation and storage tests
  - Backup retrieval and data integrity tests
  - Restore functionality tests
  - Deletion and cleanup tests
  - Multi-backup scenarios and edge cases
  - Settings validation tests

### Changed
- **Dependency Check Updated**: Now accepts either ACF (free) or ACF Pro
  - Removed requirement for ACF Pro exclusively
  - Updated error messages to reflect "Advanced Custom Fields (free or Pro)"
  - Added `silver_acf_clone_is_pro()` helper function for version detection
- **FieldDetector Enhanced**: Automatic field type filtering based on ACF version
  - Pro-only fields skipped when ACF Pro not active
  - Type-specific processing only runs for supported field types
  - Prevents errors when Pro fields exist but Pro plugin is not active
- Updated `FieldCloner::clone_fields()` to use new backup system when enabled
- Enhanced Settings page with dedicated Backup Settings section
- Admin component loader updated to initialize BackupManager
- Default settings now include backup retention policies
- Plugin description updated to reflect ACF free/Pro compatibility
- Version bumped to 1.1.0

### Documentation
- **README.md**: Updated to reflect ACF free/Pro compatibility
  - Requirements section clarified (ACF free OR Pro)
  - Added note about automatic field type detection
  - Feature list updated with compatibility badge
- **CONTRIBUTING.md**: Updated prerequisites for ACF free/Pro testing
- **Copilot Instructions**: Updated project overview with ACF compatibility details
  - Added ACF compatibility section
  - Version updated to 1.1.0

### Technical
- New database table: `{prefix}acf_field_backups` with optimized schema
- AJAX endpoints: `acf_clone_restore_backup`, `acf_clone_delete_backup`, `acf_clone_cleanup_backups`
- Field type detection constants: No longer requires ACF_PRO constant to function
- Backward compatible: Existing ACF Pro installations continue to work without changes

## [1.0.0] - 2024-01-15

### Added
- Initial release of Silver Assist ACF Clone Fields
- Core clone fields functionality for ACF field groups
- Complete GitHub Actions CI/CD pipeline
- Automated dependency management with Dependabot
- Multi-PHP version testing (8.2, 8.3, 8.4)
- Security scanning and vulnerability checks
- WordPress compatibility testing
- Automated release management
- Quality checks script for local development
- Version update automation script
- Release packaging and distribution
- WordPress plugin architecture with PSR-4 autoloading
- Integration with Silver Assist ecosystem packages:
  - `silverassist/wp-github-updater` for automatic updates
  - `silverassist/wp-settings-hub` for centralized settings management
- Comprehensive unit test suite with PHPUnit
- WordPress Coding Standards compliance (PHPCS)
- Static analysis with PHPStan Level 8
- PolyForm Noncommercial License 1.0.0
- Multi-language support (i18n ready)
- WordPress 6.0+ compatibility
- PHP 8.2+ requirement

### Features
- **Clone Field Groups**: Duplicate existing ACF field groups with customizable prefixes
- **Batch Operations**: Clone multiple field groups simultaneously
- **Settings Integration**: Centralized configuration through Silver Assist Settings Hub
- **Auto-Updates**: Seamless updates through GitHub releases
- **Developer Tools**: Comprehensive hooks and filters for customization

### Technical
- **Namespace**: `SilverAssist\ACFCloneFields`
- **Autoloading**: PSR-4 compliant with Composer
- **Testing**: 95%+ code coverage with PHPUnit
- **Quality**: WordPress VIP coding standards compliance
- **Security**: Input validation, output escaping, nonce verification
- **Performance**: Optimized database queries, caching integration

### Security
- Implemented automated security vulnerability scanning
- Added security best practices enforcement
- Enhanced input validation and output escaping

### Documentation
- Complete README with installation and usage instructions
- Inline code documentation (PHPDoc)
- Examples and code snippets
- Integration guides for Silver Assist ecosystem