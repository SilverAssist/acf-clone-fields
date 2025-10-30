# Release Process

This document outlines the complete process for creating and publishing new releases of Silver Assist ACF Clone Fields.

## Creating a New Release

### 1. Update Version Numbers
```bash
./scripts/update-version.sh 1.2.0
```

### 2. Update Changelog
- Add new section for the version
- Document all changes under appropriate categories
- Move items from [Unreleased] to the new version

### 3. Quality Checks
```bash
./scripts/run-quality-checks.sh
```

### 4. Commit and Tag
```bash
git add .
git commit -m "chore: bump version to 1.2.0"
git tag v1.2.0
git push origin main --tags
```

### 5. Automated Release
- GitHub Actions will automatically create the release
- Build and package the plugin
- Generate checksums and upload assets
- Update GitHub releases page

## Version Categories

- **Major** (1.0.0 → 2.0.0): Breaking changes, new features
- **Minor** (1.0.0 → 1.1.0): New features, enhancements (backward compatible)  
- **Patch** (1.0.0 → 1.0.1): Bug fixes, security updates (backward compatible)

## Change Categories

- **Added**: New features and functionality
- **Changed**: Changes in existing functionality
- **Deprecated**: Soon-to-be removed features
- **Removed**: Removed features
- **Fixed**: Bug fixes
- **Security**: Security improvements and vulnerability fixes

## Pre-Release Checklist

- [ ] All tests passing
- [ ] Documentation updated
- [ ] Changelog updated
- [ ] Version numbers updated
- [ ] Quality checks passed
- [ ] Breaking changes documented
- [ ] Migration guide prepared (if needed)

## Post-Release Tasks

- [ ] Verify GitHub release created
- [ ] Test automatic updates
- [ ] Update project documentation
- [ ] Announce release (if significant)
- [ ] Monitor for issues