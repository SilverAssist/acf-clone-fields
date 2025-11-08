# TODO - Test Coverage Improvement Plan

**Current Coverage**: 22.77% lines (474/2082), 30.15% methods (60/199)  
**Target Coverage**: 50%+ lines (industry standard)  
**Last Updated**: November 8, 2025

---

## ✅ Completed

- [x] Create comprehensive Activator tests (0% → 61.54% lines)
- [x] Install ACF plugin in CI environment
- [x] Document WordPress Test Suite best practices
- [x] Document CI/CD error handling patterns
- [x] Fix quality check scripts to return proper exit codes

---

## 🎯 High Priority (0% Coverage Classes)

### 1. Admin\BackupManager (0% coverage)
**File**: `tests/Unit/Admin/BackupManagerTest.php` (doesn't exist)  
**Target Coverage**: 50%+ lines  
**Estimated Impact**: +3-5% overall coverage

**Methods to Test**:
- `create_backup()` - Create field backup before cloning
- `restore_backup()` - Restore fields from backup
- `list_backups()` - Get available backups for a post
- `delete_backup()` - Remove old backups
- `get_backup()` - Retrieve specific backup data
- `prune_old_backups()` - Clean up old backups by age

**Test Scenarios**:
- [ ] Create backup successfully
- [ ] Create backup stores field data correctly
- [ ] Restore backup overwrites current fields
- [ ] List backups returns correct count
- [ ] Delete backup removes from database
- [ ] Prune old backups respects retention period
- [ ] Handle missing backup table gracefully
- [ ] Validate backup data integrity

---

### 2. Admin\MetaBox (0% coverage)
**File**: `tests/Unit/Admin/MetaBoxTest.php` (doesn't exist)  
**Target Coverage**: 40%+ lines  
**Estimated Impact**: +4-6% overall coverage

**Methods to Test**:
- `add_meta_box()` - Register meta box on enabled post types
- `render_meta_box()` - Output meta box HTML
- `save_meta_box()` - Handle meta box save action
- `should_show_meta_box()` - Check if meta box should display
- `get_enabled_post_types()` - Get post types with meta box enabled

**Test Scenarios**:
- [ ] Meta box added to enabled post types only
- [ ] Meta box not added to disabled post types
- [ ] Render includes nonce field
- [ ] Render displays source post selector
- [ ] Save validates nonce
- [ ] Save checks user capabilities
- [ ] Save triggers clone operation
- [ ] Handle invalid post type gracefully

---

### 3. Admin\Settings (0% coverage)
**File**: `tests/Unit/Admin/SettingsTest.php` (doesn't exist)  
**Target Coverage**: 40%+ lines  
**Estimated Impact**: +5-7% overall coverage

**Methods to Test**:
- `register_settings()` - Register plugin settings
- `render_settings_page()` - Output settings page HTML
- `sanitize_settings()` - Validate and sanitize input
- `get_default_settings()` - Return default configuration
- `get_option()` - Retrieve setting value
- `update_option()` - Save setting value

**Test Scenarios**:
- [ ] Settings registered with correct parameters
- [ ] Default settings include required keys
- [ ] Sanitize removes invalid values
- [ ] Sanitize preserves valid values
- [ ] Get option returns correct value
- [ ] Update option saves to database
- [ ] Settings page includes all sections
- [ ] Settings page validates capabilities

---

## 📈 Medium Priority (Low Coverage Classes)

### 4. Admin\Ajax (3.15% lines coverage)
**File**: `tests/Unit/Admin/AjaxTest.php` (may exist, needs expansion)  
**Current Coverage**: 10/317 lines  
**Target Coverage**: 40%+ lines  
**Estimated Impact**: +4-6% overall coverage

**Methods to Test** (prioritize untested):
- `handle_get_source_posts()` - AJAX: Get available source posts
- `handle_get_source_fields()` - AJAX: Get fields from source post
- `handle_execute_clone()` - AJAX: Execute cloning operation
- `handle_validate_selection()` - AJAX: Validate field selection

**Test Scenarios**:
- [ ] Get source posts returns posts of same type
- [ ] Get source posts excludes current post
- [ ] Get source fields returns hierarchical structure
- [ ] Execute clone validates nonce
- [ ] Execute clone checks capabilities
- [ ] Execute clone calls FieldCloner service
- [ ] Validate selection detects conflicts
- [ ] All AJAX handlers return JSON

---

### 5. Services\FieldDetector (25.15% lines coverage)
**File**: `tests/Unit/Services/FieldDetectorTest.php` (exists, needs expansion)  
**Current Coverage**: 41/163 lines  
**Target Coverage**: 60%+ lines  
**Estimated Impact**: +5-8% overall coverage

**Methods to Test** (prioritize untested):
- `detect_field_types()` - Identify all field types in use
- `analyze_field_structure()` - Parse hierarchical field structure
- `get_repeater_sub_fields()` - Extract repeater sub-fields
- `get_group_sub_fields()` - Extract group sub-fields
- `detect_conflicts()` - Find field name conflicts

**Test Scenarios**:
- [ ] Detect simple field types (text, textarea, number)
- [ ] Detect complex field types (repeater, group, flexible)
- [ ] Analyze nested field structures
- [ ] Get repeater sub-fields recursively
- [ ] Detect field name conflicts between posts
- [ ] Handle missing ACF data gracefully
- [ ] Handle malformed field configurations

---

### 6. Services\FieldCloner (31.91% lines coverage)
**File**: `tests/Unit/Services/FieldClonerTest.php` (exists, needs expansion)  
**Current Coverage**: 165/517 lines  
**Target Coverage**: 60%+ lines  
**Estimated Impact**: +8-12% overall coverage

**Methods to Test** (prioritize untested):
- `clone_fields()` - Main cloning orchestration
- `clone_repeater_field()` - Clone repeater with all rows
- `clone_group_field()` - Clone group with sub-fields
- `clone_flexible_content()` - Clone flexible content layouts
- `validate_field_compatibility()` - Check source/target compatibility
- `prepare_field_value()` - Transform value for target post

**Test Scenarios**:
- [ ] Clone simple fields (text, number, select)
- [ ] Clone repeater fields with multiple rows
- [ ] Clone nested repeaters
- [ ] Clone group fields with sub-fields
- [ ] Clone flexible content with layouts
- [ ] Validate compatible field types
- [ ] Reject incompatible field types
- [ ] Handle file/image field references
- [ ] Handle relationship field post IDs
- [ ] Create backup before cloning
- [ ] Rollback on error

---

## 🔧 Low Priority (Already Good Coverage)

### 7. Utils\Helpers (45.13% lines coverage)
**Current Coverage**: 88/195 lines  
**Target**: Maintain or slightly improve  
**Action**: Add edge case tests for existing functions

### 8. Core\Plugin (81.11% lines coverage)
**Current Coverage**: 73/90 lines  
**Target**: Maintain current level  
**Action**: May not need additional tests

### 9. Services\Loader (95.00% lines coverage)
**Current Coverage**: 19/20 lines  
**Target**: Excellent - maintain  
**Action**: No action needed

---

## 📝 Testing Best Practices

### WordPress Test Suite
- Use `static::factory()` for creating test data (NOT `$this->factory`)
- Use `wpSetUpBeforeClass()` for CREATE TABLE statements
- Use `setUp()` for test data initialization
- Use TRUNCATE (not CREATE TABLE) in setUp()
- ACF is available via bootstrap.php

### Test Patterns
```php
// Good: Using static::factory()
$post_id = static::factory()->post->create([
    'post_title' => 'Test Post',
]);

// Good: Database schema in wpSetUpBeforeClass()
public static function wpSetUpBeforeClass($factory): void {
    Activator::create_tables();
}

// Good: Clean data in setUp()
public function setUp(): void {
    parent::setUp();
    global $wpdb;
    $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}acf_field_backups");
}
```

### Coverage Goals by Component Type
- **Core classes**: 70%+ (critical infrastructure)
- **Service classes**: 60%+ (business logic)
- **Admin classes**: 40%+ (UI interaction)
- **Utility classes**: 50%+ (helper functions)

---

## 🎯 Coverage Milestones

- [x] **Milestone 1**: 20%+ coverage (Currently: 22.77%) ✅
- [ ] **Milestone 2**: 30%+ coverage (Next target)
- [ ] **Milestone 3**: 40%+ coverage
- [ ] **Milestone 4**: 50%+ coverage (Industry standard)

---

## 📊 Progress Tracking

**Session 1 (Nov 8, 2025)**:
- Created ActivatorTest: 0% → 61.54% lines
- Installed ACF in CI environment
- Fixed quality check error handling
- Overall coverage: 19.84% → 22.77% (+2.93%)

**Next Session**:
- Target: BackupManager (0% → 50%)
- Expected impact: +3-5% overall coverage
- Goal: Reach 25-28% coverage

---

## 🚀 Quick Commands

```bash
# Run all tests
composer test

# Run specific test file
vendor/bin/phpunit tests/Unit/Admin/BackupManagerTest.php

# Run with coverage report
vendor/bin/phpunit --coverage-html coverage/

# Run quality checks before commit
./scripts/run-quality-checks.sh
```
