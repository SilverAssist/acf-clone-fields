# CI/CD Workflows

## Overview

This document describes the CI/CD workflow architecture for Silver Assist ACF Clone Fields plugin.

**Version**: 1.2.0  
**Strategy**: WordPress Integration Testing

## Quick Reference

| Workflow | WordPress | Duration | Purpose |
|----------|-----------|----------|---------|
| `ci.yml` | ✅ Yes | ~8-10 min | Full integration testing |
| `release.yml` | ✅ Yes | ~10-12 min | Exhaustive pre-release validation |
| `dependency-updates.yml` | ❌ No | ~2-3 min | Fast Composer validation |

## Workflow Files

### 1. quality-checks.yml (Reusable Workflow)

**Purpose**: Centralized quality validation across PHP versions

**Parameters**:
- `php-version`: PHP version (default: `8.2`)
- `skip-wp-setup`: Skip WordPress (default: `false`)
- `upload-coverage`: Upload coverage (default: `false`)

**Executes**:
- Composer validate
- PHPCS (WordPress Coding Standards)
- PHPStan Level 8
- PHP Syntax Check
- PHPUnit Tests
- WordPress Test Suite (if enabled)

### 2. ci.yml (Continuous Integration)

**Trigger**: Push to `main`/`develop`, Pull Requests

**Jobs**:
- `quality-checks-82/83/84`: Quality checks for PHP 8.2, 8.3, 8.4
- `security-scan`: Security audit
- `compatibility`: WordPress 6.4, 6.5, 6.6, latest
- `build-test`: Build verification
- `notify`: Results notification

**Why WordPress**: Detects integration issues before merge

### 3. release.yml (Release Process)

**Trigger**: Git tags `v*`, Manual dispatch

**Jobs**:
- `validate-release`: Structure, version, CHANGELOG validation
- `test-release`: Quality checks matrix (PHP 8.2, 8.3, 8.4)
- `build-release`: Build ZIP package
- `notify-success/failure`: Notifications

**Why WordPress**: Final validation before public release

### 4. dependency-updates.yml (Dependency Management)

**Trigger**: Weekly (Mondays 9AM Mexico City), Dependabot PRs

**Jobs**:
- `check-composer-updates`: Check outdated packages
- `security-audit`: CVE detection
- `auto-merge-dependabot`: Auto-merge safe updates

**Why no WordPress**: Composer packages don't require WordPress validation

## Scripts

### run-quality-checks.sh

Centralized script for quality validation.

**Usage**:
```bash
# Quick checks (no WordPress)
./scripts/run-quality-checks.sh --skip-wp-setup phpcs phpstan

# Full checks (with WordPress)
./scripts/run-quality-checks.sh all

# Individual check
./scripts/run-quality-checks.sh phpstan
```

**Options**:
- `--skip-wp-setup`: Skip WordPress installation
- `--php-version`: PHP version (default: 8.3)
- `--wp-version`: WordPress version (default: latest)
- `--db-*`: Database configuration

**Checks**:
- `composer-validate`: Validate composer.json
- `phpcs`: Code standards
- `phpstan`: Static analysis
- `phpunit`: Unit tests
- `syntax`: PHP syntax
- `all`: All checks

## Testing Strategy

### WordPress Integration

**ci.yml**: Full WordPress integration
- Setup MySQL database
- Install WordPress Test Suite
- Run tests with real WordPress

**release.yml**: Full WordPress integration
- Matrix testing (PHP 8.2, 8.3, 8.4)
- MySQL setup
- WordPress Test Suite

**dependency-updates.yml**: No WordPress
- Mocks only
- Fast execution
- Code validation only

### Bootstrap Auto-Detection

`tests/bootstrap.php` automatically detects WordPress:

```php
$_tests_dir = getenv('WP_TESTS_DIR');
$wp_tests_available = $_tests_dir && file_exists($_tests_dir . '/includes/functions.php');

if ($wp_tests_available) {
    // Load WordPress Test Suite
} else {
    // Load mocks
}
```

## Development

### Local Development

```bash
# Quick checks (development)
./scripts/run-quality-checks.sh --skip-wp-setup phpcs phpstan

# Full checks (pre-commit)
./scripts/run-quality-checks.sh all

# PHPUnit with WordPress
WP_TESTS_DIR=/tmp/wordpress-tests-lib vendor/bin/phpunit
```

### Pull Request Flow

1. Push to branch
2. CI runs quality-checks.yml (3 parallel jobs)
3. Each job: MySQL + WordPress + quality checks
4. Security scan + compatibility tests
5. Results in GitHub Actions

### Release Flow

1. Tag `v1.x.x` or manual dispatch
2. Validate release structure
3. Test release (PHP 8.2, 8.3, 8.4 matrix)
4. Build ZIP package
5. Publish to GitHub Releases

## Future Enhancements

### Phase 2: ACF Integration Testing
- Install ACF Free/Pro in tests
- Test field groups
- Test complex field types
- Test field cloning

### Phase 3: E2E Testing
- Playwright/Cypress tests
- UI testing
- Visual regression

## Maintenance

### Add Quality Check

Edit `scripts/run-quality-checks.sh`:
```bash
run_new_check() {
    print_header "🆕 Running New Check"
    vendor/bin/new-tool
    print_success "New check passed"
}
```

### Add PHP Version

Edit `.github/workflows/ci.yml`:
```yaml
quality-checks-85:
  uses: ./.github/workflows/quality-checks.yml
  with:
    php-version: '8.5'
    skip-wp-setup: false
```

## Changelog

### v1.2.0 (2025-01-07)
- Changed ci.yml to use WordPress integration
- Added MySQL setup to release.yml
- Removed WordPress from dependency-updates.yml
- Updated documentation

### v1.1.0 (2025-01-07)
- Created quality-checks.yml reusable workflow
- Fixed run-quality-checks.sh paths
- Added WP_TESTS_DIR support

### v1.0.0 (2025-01-06)
- Initial workflow setup
