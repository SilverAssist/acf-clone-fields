---
description: Internationalization (i18n) guidelines for Silver Assist ACF Clone Fields plugin
applyTo: "languages/**/*.{pot,po,mo}"
---

# Internationalization Instructions - Silver Assist ACF Clone Fields

## 🌍 Overview

This plugin follows WordPress internationalization best practices to support multiple languages. All user-facing strings must be properly wrapped with translation functions and included in the POT template file.

**Text Domain**: `silver-assist-acf-clone-fields`  
**Translation Files Location**: `languages/`  
**POT Template**: `languages/silver-assist-acf-clone-fields.pot`

---

## 📝 Translation Functions Reference

### Basic Translation Functions

```php
// Simple translation
__('String to translate', 'silver-assist-acf-clone-fields')

// Echo translated string
_e('String to translate', 'silver-assist-acf-clone-fields')

// Translation with context (when same English word has different meanings)
_x('Post', 'noun', 'silver-assist-acf-clone-fields')
_x('Post', 'verb', 'silver-assist-acf-clone-fields')

// Plural forms
_n(
    '%d field cloned',      // Singular
    '%d fields cloned',     // Plural
    $count,                 // Number
    'silver-assist-acf-clone-fields'
)

// Echo plural forms
_n_noop(
    '%d field',
    '%d fields',
    'silver-assist-acf-clone-fields'
)

// Contextual plural forms
_nx(
    '%d field',             // Singular
    '%d fields',            // Plural
    $count,                 // Number
    'noun',                 // Context
    'silver-assist-acf-clone-fields'
)
```

### Escaping and Translation Combined

```php
// Translate and escape HTML
esc_html__('String', 'silver-assist-acf-clone-fields')
esc_html_e('String', 'silver-assist-acf-clone-fields')

// Translate and escape attributes
esc_attr__('String', 'silver-assist-acf-clone-fields')
esc_attr_e('String', 'silver-assist-acf-clone-fields')

// Translate and escape for JavaScript
esc_js__('String', 'silver-assist-acf-clone-fields')
```

---

## 🔧 Generating POT Translation Template

### Using WP-CLI (Recommended)

**Primary Command:**
```bash
wp i18n make-pot . languages/silver-assist-acf-clone-fields.pot \
  --domain=silver-assist-acf-clone-fields \
  --exclude=vendor,node_modules,tests,build
```

**With Additional Options:**
```bash
wp i18n make-pot . languages/silver-assist-acf-clone-fields.pot \
  --domain=silver-assist-acf-clone-fields \
  --exclude=vendor,node_modules,tests,build \
  --headers='{"Report-Msgid-Bugs-To":"https://github.com/SilverAssist/acf-clone-fields/issues"}'
```

**Verification Command:**
```bash
# Check if POT file was generated correctly
ls -lh languages/silver-assist-acf-clone-fields.pot

# Count translatable strings
grep -c "^msgid" languages/silver-assist-acf-clone-fields.pot
```

### Excluded Directories

The following directories are excluded from translation scanning:
- `vendor/` - Composer dependencies
- `node_modules/` - npm dependencies
- `tests/` - PHPUnit test files
- `build/` - Build artifacts and release files

**Rationale**: These directories contain:
- Third-party code with their own translations
- Development dependencies not shipped with the plugin
- Test code not visible to end users
- Temporary build files

---

## 📋 POT File Header Standards

### Required Headers

The POT file must maintain consistent metadata:

```pot
# Copyright (C) 2025 Silver Assist
# This file is distributed under the PolyForm-Noncommercial-1.0.0.
msgid ""
msgstr ""
"Project-Id-Version: ACF Clone Fields 1.1.1\n"
"Report-Msgid-Bugs-To: https://github.com/SilverAssist/acf-clone-fields/issues\n"
"Last-Translator: SilverAssist <support@silverassist.com>\n"
"Language-Team: SilverAssist <support@silverassist.com>\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
"POT-Creation-Date: 2025-11-09T06:05:18+00:00\n"
"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\n"
"X-Generator: WP-CLI 2.12.0\n"
"X-Domain: silver-assist-acf-clone-fields\n"
```

### Header Field Explanations

| Field | Value | Purpose |
|-------|-------|---------|
| **Project-Id-Version** | `ACF Clone Fields {version}` | Plugin name and current version |
| **Report-Msgid-Bugs-To** | `https://github.com/SilverAssist/acf-clone-fields/issues` | Where to report translation bugs |
| **Last-Translator** | `SilverAssist <support@silverassist.com>` | Maintain consistency across translations |
| **Language-Team** | `SilverAssist <support@silverassist.com>` | Translation team contact |
| **MIME-Version** | `1.0` | MIME standard version |
| **Content-Type** | `text/plain; charset=UTF-8` | UTF-8 encoding (required) |
| **Content-Transfer-Encoding** | `8bit` | Transfer encoding |
| **X-Domain** | `silver-assist-acf-clone-fields` | WordPress text domain |

### Important Notes

- **Last-Translator**: Always use `SilverAssist <support@silverassist.com>` for consistency
- **Language-Team**: Always use `SilverAssist <support@silverassist.com>` for consistency
- **Report-Msgid-Bugs-To**: Always use `https://github.com/SilverAssist/acf-clone-fields/issues`
- **POT-Creation-Date**: Auto-generated by WP-CLI (do not modify manually)
- **Project-Id-Version**: Update version number when releasing new versions

---

## 🎯 Translation Best Practices

### 1. Always Use Text Domain

**❌ Incorrect:**
```php
echo __('Clone Fields');  // Missing text domain
echo _e('Settings');      // Missing text domain
```

**✅ Correct:**
```php
echo __('Clone Fields', 'silver-assist-acf-clone-fields');
_e('Settings', 'silver-assist-acf-clone-fields');
```

### 2. Use Placeholders for Dynamic Content

**❌ Incorrect:**
```php
echo __("You have " . $count . " fields", 'silver-assist-acf-clone-fields');
```

**✅ Correct:**
```php
// translators: %d: number of fields
echo sprintf(
    __('You have %d fields', 'silver-assist-acf-clone-fields'),
    $count
);
```

### 3. Use Plural Forms

**❌ Incorrect:**
```php
echo sprintf(__('%d field(s)', 'silver-assist-acf-clone-fields'), $count);
```

**✅ Correct:**
```php
// translators: %d: number of fields
echo sprintf(
    _n('%d field', '%d fields', $count, 'silver-assist-acf-clone-fields'),
    $count
);
```

### 4. Add Translator Comments

**✅ Best Practice:**
```php
// translators: %1$d: number of fields, %2$s: post type name
sprintf(
    __('Cloned %1$d fields from %2$s', 'silver-assist-acf-clone-fields'),
    $count,
    $post_type
);
```

### 5. Use Context for Ambiguous Strings

**✅ Correct:**
```php
_x('Post', 'noun - a blog post', 'silver-assist-acf-clone-fields');
_x('Post', 'verb - to publish', 'silver-assist-acf-clone-fields');
```

### 6. Avoid Concatenation

**❌ Incorrect:**
```php
echo __('Total:', 'silver-assist-acf-clone-fields') . ' ' . $count;
```

**✅ Correct:**
```php
// translators: %d: total count
printf(__('Total: %d', 'silver-assist-acf-clone-fields'), $count);
```

---

## 🔄 Workflow for Updates

### When Adding New Translatable Strings

1. **Add translation function** to your PHP code:
   ```php
   __('New translatable string', 'silver-assist-acf-clone-fields')
   ```

2. **Regenerate POT file**:
   ```bash
   wp i18n make-pot . languages/silver-assist-acf-clone-fields.pot \
     --domain=silver-assist-acf-clone-fields \
     --exclude=vendor,node_modules,tests,build
   ```

3. **Verify new strings** were added:
   ```bash
   grep "New translatable string" languages/silver-assist-acf-clone-fields.pot
   ```

4. **Commit POT file** to repository:
   ```bash
   git add languages/silver-assist-acf-clone-fields.pot
   git commit -m "i18n: Update POT file with new translatable strings"
   ```

### When Releasing New Version

1. **Update version** in POT header (automatic with WP-CLI)
2. **Regenerate POT** with updated version
3. **Notify translators** via GitHub issues if major changes
4. **Include POT** in release package

---

## 📦 File Structure

```
languages/
├── silver-assist-acf-clone-fields.pot   # Template file (source)
├── silver-assist-acf-clone-fields-es_ES.po   # Spanish translation (if exists)
├── silver-assist-acf-clone-fields-es_ES.mo   # Compiled Spanish (if exists)
├── silver-assist-acf-clone-fields-fr_FR.po   # French translation (if exists)
└── silver-assist-acf-clone-fields-fr_FR.mo   # Compiled French (if exists)
```

**File Types:**
- `.pot` - Template (source, English strings)
- `.po` - Translation source (human-readable)
- `.mo` - Compiled translation (machine-readable, used by WordPress)

---

## 🌐 Adding New Language Translations

### For Community Translators

1. **Get POT file** from repository:
   ```bash
   wget https://raw.githubusercontent.com/SilverAssist/acf-clone-fields/main/languages/silver-assist-acf-clone-fields.pot
   ```

2. **Create PO file** for your language:
   ```bash
   # Example for Spanish (es_ES)
   cp silver-assist-acf-clone-fields.pot silver-assist-acf-clone-fields-es_ES.po
   ```

3. **Translate strings** using translation tool:
   - [Poedit](https://poedit.net/) (GUI, recommended)
   - [GlotPress](https://translate.wordpress.org/) (WordPress.org)
   - Text editor (advanced users)

4. **Compile MO file**:
   ```bash
   msgfmt silver-assist-acf-clone-fields-es_ES.po -o silver-assist-acf-clone-fields-es_ES.mo
   ```

5. **Submit translation**:
   - Create pull request on GitHub
   - Submit to WordPress.org translation platform
   - Email to support@silverassist.com

---

## 🔍 Validation and Testing

### Check Translation Coverage

```bash
# Count total translatable strings
grep -c "^msgid" languages/silver-assist-acf-clone-fields.pot

# Check for untranslated strings in PO file
msgcmp --use-untranslated languages/silver-assist-acf-clone-fields-es_ES.po \
       languages/silver-assist-acf-clone-fields.pot
```

### Validate POT File Format

```bash
# Check for syntax errors
msgfmt --check languages/silver-assist-acf-clone-fields.pot

# Validate POT structure
wp i18n make-pot . /tmp/test.pot \
  --domain=silver-assist-acf-clone-fields \
  --exclude=vendor,node_modules,tests,build

# Compare with existing POT
diff languages/silver-assist-acf-clone-fields.pot /tmp/test.pot
```

### Test Translations in WordPress

1. **Install translation files** in `languages/` directory
2. **Set WordPress language** in Settings → General
3. **Clear cache** (if using caching plugin)
4. **Navigate to plugin** and verify translated strings appear
5. **Test all user flows** to ensure complete coverage

---

## 📚 Resources

### Documentation
- [WordPress I18n Handbook](https://developer.wordpress.org/plugins/internationalization/)
- [WP-CLI i18n Commands](https://developer.wordpress.org/cli/commands/i18n/)
- [GNU gettext Manual](https://www.gnu.org/software/gettext/manual/)

### Tools
- [Poedit](https://poedit.net/) - Translation editor
- [LocoTranslate](https://wordpress.org/plugins/loco-translate/) - WordPress plugin
- [WP-CLI](https://wp-cli.org/) - Command-line tool

### Silver Assist Resources
- **Report Translation Bugs**: https://github.com/SilverAssist/acf-clone-fields/issues
- **Translation Team Contact**: support@silverassist.com
- **Plugin Repository**: https://github.com/SilverAssist/acf-clone-fields

---

## ⚠️ Important Notes

### DO NOT Translate

The following should **never** be translated:
- Function names
- Class names
- Hook names
- Database table/column names
- Field keys/IDs
- File paths
- URLs (except in documentation)
- Code comments (translate inline documentation separately)

### Character Encoding

- **Always use UTF-8** encoding for all translation files
- **Never use** BOM (Byte Order Mark)
- **Test special characters**: ñ, ü, é, ç, 中文, العربية, etc.

### WordPress.org Integration

If submitting to WordPress.org plugin directory:
- POT file must be in `languages/` directory
- Text domain must match plugin slug
- Domain must be loaded in main plugin file
- Follow WordPress.org translation guidelines

---

## 🔄 CI/CD Integration

### Automated POT Generation

The POT file can be automatically regenerated in CI/CD workflows:

```yaml
# .github/workflows/i18n.yml (example)
name: Update Translations

on:
  push:
    branches: [main]
    paths:
      - 'includes/**/*.php'
      - 'silver-assist-acf-clone-fields.php'

jobs:
  update-pot:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup WP-CLI
        run: |
          curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
          chmod +x wp-cli.phar
          sudo mv wp-cli.phar /usr/local/bin/wp
      
      - name: Generate POT
        run: |
          wp i18n make-pot . languages/silver-assist-acf-clone-fields.pot \
            --domain=silver-assist-acf-clone-fields \
            --exclude=vendor,node_modules,tests,build
      
      - name: Commit Changes
        run: |
          git config user.name "GitHub Actions"
          git config user.email "actions@github.com"
          git add languages/silver-assist-acf-clone-fields.pot
          git commit -m "i18n: Auto-update POT file" || exit 0
          git push
```

---

**Last Updated**: November 9, 2025  
**Version**: 1.1.1  
**Maintained by**: Miguel Colmenares
