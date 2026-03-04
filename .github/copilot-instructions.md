# Silver Assist ACF Clone Fields — Project Context

ACF field cloning system with granular per-field selection, real-time conflict detection, and a 3-step modal interface (source selection → field selection → execution). Works with ACF free (basic fields) and ACF Pro (repeater, group, flexible content).

## Plugin Details

| Property | Value |
|----------|-------|
| **Namespace** | `SilverAssist\ACFCloneFields\` → `includes/` |
| **Text Domain** | `silver-assist-acf-clone-fields` |
| **Version** | 1.1.0 |
| **PHP** | 8.2+ |
| **WordPress** | 5.0+ |
| **ACF** | Free and Pro |
| **WP Prefix** | `silver_acf_clone_` / `SILVER_ACF_CLONE_` |
| **License** | PolyForm Noncommercial 1.0.0 |

## Architecture

```
includes/
├── Core/
│   ├── Interfaces/LoadableInterface.php
│   └── Plugin.php                  # Bootstrap, loads components via Loader classes
├── Services/
│   ├── FieldDetector.php          # Detects ACF fields/groups/sub-fields for a post
│   └── FieldCloner.php            # Clones fields between posts, validates compatibility
├── Admin/
│   ├── MetaBox.php                # Sidebar meta box on enabled post types
│   ├── Settings.php               # Settings via wp-settings-hub (post types, behavior, logging)
│   └── Ajax.php                   # AJAX endpoints for source posts, field preview, clone execution
└── Utils/
    ├── Helpers.php
    └── Logger.php

assets/   # No build step — used as-is
├── css/silver-acf-clone-fields.css
└── js/admin.js                    # 3-step modal interface
```

### Key Classes

- **FieldDetector** — `getAvailableFields(postId)`, `getFieldGroups(postType)`, `getRepeaterSubFields(fieldConfig)`.
- **FieldCloner** — `cloneFields(sourceId, targetId, fieldKeys)`, `cloneRepeaterField(...)`, `validateFieldCompatibility(...)`. Creates backups in `{prefix}acf_field_backups` table.
- **MetaBox** — Registers sidebar meta box on enabled post types. Renders source selector, field checkboxes, clone button.
- **Ajax** — Four endpoints: `acf_clone_get_source_posts`, `acf_clone_get_source_fields`, `acf_clone_execute_clone`, `acf_clone_validate_selection`. All require nonce + `edit_posts` capability.
- **Settings** — Integrated via wp-settings-hub. Configures enabled post types, default clone behavior, confirmation messages, logging.

### Component Loading

Components register via `Loader` classes (`Services\Loader`, `Admin\Loader`) discovered by `Plugin::load_components()`. Priorities: Core (10), Services (20), Admin (30), Assets (40).

### Package Integration

- **wp-github-updater** — Native WP updates from GitHub. Configured via `UpdaterConfig` class (not plugin headers). Uses `Update URI` header.
- **wp-settings-hub** — Unified admin menu for SilverAssist plugins. Uses `SettingsHub::get_instance()`.

## ACF Compatibility Notes

- **ACF Free**: Basic field types (text, textarea, image, select, etc.)
- **ACF Pro**: Repeater, group, flexible content — cloning handles sub-field hierarchies
- Field type compatibility is validated before cloning via `validateFieldCompatibility()`

## Plugin-Specific Test Utilities

- `tests/Utils/ACFTestHelpers.php` — ACF-specific test helpers (create mock field groups, register fields)
- Backup table (`{prefix}acf_field_backups`) requires `CREATE TABLE` — use `wpSetUpBeforeClass()` to avoid implicit MySQL COMMIT breaking test rollbacks; use `TRUNCATE` in `setUp()` to clean data

## Quick Reference

| Task | Command / Location |
|------|--------------------|
| Quality checks | `./scripts/run-quality-checks.sh` |
| Build release | `./scripts/build-release.sh <version>` |
| Main plugin file | `silver-assist-acf-clone-fields.php` |
| Bootstrap | `includes/Core/Plugin.php` |
| AJAX API docs | `docs/AJAX_API_REFERENCE.md` |
| CI workflows | `ci.yml`, `release.yml`, `dependency-updates.yml` |
| Test suite | `tests/` (Unit + Integration, WordPress Test Suite) |