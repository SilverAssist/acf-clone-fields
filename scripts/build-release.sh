#!/bin/bash

################################################################################
# Silver Assist ACF Clone Fields - Build Release Script
#
# Creates a production-ready plugin release package
#
# Usage: ./scripts/build-release.sh [version]
#
# @package ACFCloneFields
# @since 1.0.0
# @author Silver Assist
# @version 1.1.1
################################################################################

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Get project root
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN_SLUG="silver-assist-acf-clone-fields"

print_status "Silver Assist ACF Clone Fields - Release Builder"
print_status "Project root: ${PROJECT_ROOT}"

# Check if we're in the right directory
if [ ! -f "${PROJECT_ROOT}/silver-assist-acf-clone-fields.php" ]; then
    print_error "Main plugin file not found. Make sure you're running this from the project root."
    exit 1
fi

# Get version
if [ -n "$1" ]; then
    VERSION="$1"
else
    VERSION=$(grep -o "Version: [0-9]\+\.[0-9]\+\.[0-9]\+" "${PROJECT_ROOT}/silver-assist-acf-clone-fields.php" | cut -d' ' -f2)
fi

if [ -z "$VERSION" ]; then
    print_error "Could not detect version. Please provide version as argument."
    echo "Usage: $0 [version]"
    exit 1
fi

print_status "Building release for version: ${VERSION}"

# Create build directory
BUILD_DIR="${PROJECT_ROOT}/build"
RELEASE_DIR="${BUILD_DIR}/release"
PACKAGE_DIR="${RELEASE_DIR}/${PLUGIN_SLUG}"

print_status "Creating build directories..."
rm -rf "$BUILD_DIR"
mkdir -p "$PACKAGE_DIR"

# Copy main plugin files
print_status "Copying plugin files..."

# Main plugin file
cp "${PROJECT_ROOT}/silver-assist-acf-clone-fields.php" "$PACKAGE_DIR/"

# Source code (includes directory)
if [ -d "${PROJECT_ROOT}/includes" ]; then
    cp -r "${PROJECT_ROOT}/includes" "$PACKAGE_DIR/"
    print_status "  ✓ Source code (includes/) copied"
fi

# Languages
if [ -d "${PROJECT_ROOT}/languages" ]; then
    cp -r "${PROJECT_ROOT}/languages" "$PACKAGE_DIR/"
    print_status "  ✓ Language files copied"
fi

# Assets (CSS and JavaScript)
if [ -d "${PROJECT_ROOT}/assets" ]; then
    cp -r "${PROJECT_ROOT}/assets" "$PACKAGE_DIR/"
    print_status "  ✓ Assets (CSS/JS) copied"
fi

# Documentation
for doc_file in README.md CHANGELOG.md LICENSE; do
    if [ -f "${PROJECT_ROOT}/${doc_file}" ]; then
        cp "${PROJECT_ROOT}/${doc_file}" "$PACKAGE_DIR/"
        print_status "  ✓ ${doc_file} copied"
    fi
done

# Composer dependencies (production only)
print_status "Installing production dependencies..."
if [ -f "${PROJECT_ROOT}/composer.json" ]; then
    cd "${PROJECT_ROOT}"
    
    # Install production dependencies
    if command -v composer >/dev/null 2>&1; then
        print_status "  • Installing production dependencies with Composer..."
        
        # Save current composer.lock for restoration
        if [ -f "composer.lock" ]; then
            cp "composer.lock" "composer.lock.backup"
        fi
        
        # Install production-only dependencies
        if composer install --no-dev --optimize-autoloader --no-interaction; then
            if [ -d "${PROJECT_ROOT}/vendor" ]; then
                cp -r "${PROJECT_ROOT}/vendor" "$PACKAGE_DIR/"
                print_success "  ✓ Composer dependencies installed successfully"
            else
                print_error "Vendor directory not created after composer install"
                exit 1
            fi
            
            # Restore development dependencies for local development
            print_status "  • Restoring development dependencies..."
            if [ -f "composer.lock.backup" ]; then
                mv "composer.lock.backup" "composer.lock"
            fi
            composer install --optimize-autoloader --no-interaction >/dev/null 2>&1
        else
            print_error "Failed to install Composer dependencies"
            exit 1
        fi
    else
        print_error "Composer not found. This plugin requires Composer dependencies."
        print_error "Please install Composer: https://getcomposer.org/download/"
        exit 1
    fi
    
    # Copy composer files to package
    cp "${PROJECT_ROOT}/composer.json" "$PACKAGE_DIR/"
    
    cd "${PROJECT_ROOT}"
else
    print_error "composer.json not found"
    exit 1
fi

# Create readme.txt for WordPress.org
print_status "Generating WordPress.org readme.txt..."
cat > "${PACKAGE_DIR}/readme.txt" << EOF
=== Silver Assist - ACF Clone Fields ===
Contributors: silverassist
Tags: acf, advanced custom fields, clone, copy, fields
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 8.2
Stable tag: ${VERSION}
License: Polyform-Noncommercial-1.0.0
License URI: https://github.com/silverassist/silver-assist-acf-clone-fields/blob/main/LICENSE

Advanced ACF field cloning system that allows selective copying of custom fields between posts of the same type.

== Description ==

Silver Assist - ACF Clone Fields is a powerful WordPress plugin that extends Advanced Custom Fields (ACF) with intelligent field cloning capabilities. This plugin allows you to selectively copy custom field data between posts of the same type, streamlining content management workflows.

= Key Features =

* **Granular Field Selection**: Choose specific ACF fields to clone rather than copying everything
* **Same Post Type Only**: Ensures data integrity by restricting cloning to matching post types
* **Sidebar Interface**: Clean, intuitive sidebar widget for easy field management
* **Repeater Field Support**: Intelligent handling of ACF repeater fields with sub-field cloning
* **Flexible Field Groups**: Supports all ACF field types and custom field groups
* **Safe Operations**: Validates field compatibility before cloning to prevent data corruption
* **Modern Architecture**: Built with PHP 8.2+ features and PSR-4 autoloading
* **Performance Optimized**: Efficient field processing with minimal database queries

= Use Cases =

* Content duplication with selective field copying
* Template-based content creation workflows  
* Bulk content management and standardization
* Field data migration between similar posts
* Content variation creation from base templates

= How It Works =

1. Navigate to any post edit screen with ACF fields
2. Use the sidebar "Clone Fields" widget to select source post
3. Choose which specific fields to clone from the source
4. Apply changes to copy selected field data to current post

== Installation ==

1. Upload the plugin files to \`/wp-content/plugins/silver-assist-acf-clone-fields/\`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Ensure Advanced Custom Fields (ACF) plugin is installed and activated
2. Go to Settings > ACF Clone Fields to configure your options
3. Activate and configure at Settings > ACF Clone Fields
6. Use the sidebar "Clone Fields" widget to copy field data between posts

== Frequently Asked Questions ==

= Does this work with all ACF field types? =

Yes! The plugin supports all standard ACF field types including text, textarea, number, email, URL, password, image, gallery, select, checkbox, radio, date picker, and more.

= Can I clone repeater fields? =

Yes, the plugin includes intelligent repeater field handling that allows you to clone individual rows or entire repeater field sets with their sub-fields intact.

= Does it work with custom post types? =

Yes, the plugin works with any post type that has ACF fields attached. It automatically restricts cloning to posts of the same type to ensure data integrity.

= Is it safe to use? =

Yes, the plugin includes comprehensive validation, input sanitization, output escaping, and proper WordPress capability checks. It only clones compatible fields to prevent data corruption.

= What happens if ACF is not installed? =

The plugin gracefully handles ACF absence and will display appropriate notices. It requires ACF to be installed and activated to function.

= Can I clone fields between different post types? =

No, for data integrity and field compatibility, the plugin only allows cloning between posts of the same post type.

== Screenshots ==

1. Sidebar clone fields widget with source post selection
2. Field selection interface showing available ACF fields
3. Admin settings page for plugin configuration

== Changelog ==

= ${VERSION} =
* Initial release
* Granular ACF field selection and cloning
* Sidebar interface for field management
* Support for all ACF field types including repeaters
* Same post type restriction for data integrity
* Intelligent field validation and compatibility checks
* Modern PHP 8.2+ architecture with PSR-4 autoloading
* GitHub automatic updates integration
* Comprehensive admin settings interface
* WordPress Coding Standards compliance
* PHPStan Level 8 static analysis

== Upgrade Notice ==

= ${VERSION} =
Initial release of Silver Assist ACF Clone Fields plugin for advanced field cloning capabilities.

== Technical Details ==

= Requirements =
* WordPress 5.0+
* PHP 8.2+
* Advanced Custom Fields (ACF) plugin
* Composer (for development only)

= Architecture =
* PSR-4 autoloading with namespace structure
* Singleton pattern for core classes
* Modern PHP 8.2+ features (enums, readonly properties, etc.)
* WordPress Settings API integration
* ACF field type abstraction layer
* Comprehensive PHPDoc documentation

= Development =
* GitHub: https://github.com/silverassist/silver-assist-acf-clone-fields
* Issues: https://github.com/silverassist/silver-assist-acf-clone-fields/issues
* Author: Silver Assist Development Team (https://silverassist.com)
EOF

print_success "  ✓ WordPress.org readme.txt generated"

# Clean up development files
print_status "Cleaning up development files..."

# Remove development and build files
rm -rf "${PACKAGE_DIR}/.git"
rm -rf "${PACKAGE_DIR}/.github"
rm -rf "${PACKAGE_DIR}/scripts"
rm -rf "${PACKAGE_DIR}/tests"
rm -rf "${PACKAGE_DIR}/node_modules"
rm -f "${PACKAGE_DIR}/.gitignore"
rm -f "${PACKAGE_DIR}/.gitattributes"
rm -f "${PACKAGE_DIR}/composer.lock"
rm -f "${PACKAGE_DIR}/package.json"
rm -f "${PACKAGE_DIR}/package-lock.json"
rm -f "${PACKAGE_DIR}/phpunit.xml"
rm -f "${PACKAGE_DIR}/phpcs.xml"
rm -f "${PACKAGE_DIR}/phpstan.neon"
rm -f "${PACKAGE_DIR}/.travis.yml"
rm -f "${PACKAGE_DIR}/.circleci"

print_status "  ✓ Development files removed"

# Clean up vendor development files
if [ -d "${PACKAGE_DIR}/vendor" ]; then
    print_status "Cleaning vendor directory..."
    
    # Remove common development files
    find "${PACKAGE_DIR}/vendor" -name "*.md" -delete 2>/dev/null || true
    find "${PACKAGE_DIR}/vendor" -name "LICENSE*" -type f -delete 2>/dev/null || true
    find "${PACKAGE_DIR}/vendor" -name ".git*" -delete 2>/dev/null || true
    find "${PACKAGE_DIR}/vendor" -name "tests" -type d -exec rm -rf {} + 2>/dev/null || true
    find "${PACKAGE_DIR}/vendor" -name "test" -type d -exec rm -rf {} + 2>/dev/null || true
    find "${PACKAGE_DIR}/vendor" -name "docs" -type d -exec rm -rf {} + 2>/dev/null || true
    find "${PACKAGE_DIR}/vendor" -name "examples" -type d -exec rm -rf {} + 2>/dev/null || true
    
    # Clean up GitHub updater package - keep only essential PHP files
    UPDATER_DIR="${PACKAGE_DIR}/vendor/silverassist/wp-github-updater"
    if [ -d "$UPDATER_DIR" ]; then
        print_status "  • Cleaning GitHub updater package..."
        
        # Remove git directory
        rm -rf "$UPDATER_DIR/.git"
        rm -rf "$UPDATER_DIR/.github"
        
        # Remove development files
        rm -f "$UPDATER_DIR"/*.md 2>/dev/null || true
        rm -f "$UPDATER_DIR"/*.txt 2>/dev/null || true
        rm -f "$UPDATER_DIR"/*.xml 2>/dev/null || true
        rm -f "$UPDATER_DIR"/*.neon 2>/dev/null || true
        rm -f "$UPDATER_DIR"/.* 2>/dev/null || true
        
        # Remove examples and other non-essential directories
        rm -rf "$UPDATER_DIR/examples" 2>/dev/null || true
        rm -rf "$UPDATER_DIR/tests" 2>/dev/null || true
        rm -rf "$UPDATER_DIR/docs" 2>/dev/null || true
        
        # Keep only src/ directory and composer.json
        if [ -d "$UPDATER_DIR/src" ] && [ -f "$UPDATER_DIR/composer.json" ]; then
            print_success "    ✓ GitHub updater cleaned (kept only src/ and composer.json)"
        else
            print_warning "    ⚠ GitHub updater structure unexpected"
        fi
    fi
    
    # Clean up Settings Hub package - keep only essential PHP files
    HUB_DIR="${PACKAGE_DIR}/vendor/silverassist/wp-settings-hub"
    if [ -d "$HUB_DIR" ]; then
        print_status "  • Cleaning Settings Hub package..."
        
        # Remove git directory
        rm -rf "$HUB_DIR/.git"
        rm -rf "$HUB_DIR/.github"
        
        # Remove development files
        rm -f "$HUB_DIR"/*.md 2>/dev/null || true
        rm -f "$HUB_DIR"/*.txt 2>/dev/null || true
        rm -f "$HUB_DIR"/*.xml 2>/dev/null || true
        rm -f "$HUB_DIR"/*.neon 2>/dev/null || true
        rm -f "$HUB_DIR"/.* 2>/dev/null || true
        
        # Remove examples and other non-essential directories
        rm -rf "$HUB_DIR/examples" 2>/dev/null || true
        rm -rf "$HUB_DIR/tests" 2>/dev/null || true
        rm -rf "$HUB_DIR/docs" 2>/dev/null || true
        
        # Keep only src/ directory and composer.json
        if [ -d "$HUB_DIR/src" ] && [ -f "$HUB_DIR/composer.json" ]; then
            print_success "    ✓ Settings Hub cleaned (kept only src/ and composer.json)"
        else
            print_warning "    ⚠ Settings Hub structure unexpected"
        fi
    fi
    
    print_success "  ✓ Vendor directory cleaned"
fi

# Validate the package
print_status "Validating package..."

# Check if main plugin file exists
if [ ! -f "${PACKAGE_DIR}/silver-assist-acf-clone-fields.php" ]; then
    print_error "Main plugin file missing from package"
    exit 1
fi

# Check if version matches
PACKAGE_VERSION=$(grep -o "Version: [0-9]\+\.[0-9]\+\.[0-9]\+" "${PACKAGE_DIR}/silver-assist-acf-clone-fields.php" | cut -d' ' -f2)
if [ "$PACKAGE_VERSION" != "$VERSION" ]; then
    print_error "Version mismatch in package: expected $VERSION, found $PACKAGE_VERSION"
    exit 1
fi

# Check if Composer autoloader exists
if [ ! -f "${PACKAGE_DIR}/vendor/autoload.php" ]; then
    print_error "Composer autoloader missing from package"
    print_error "The plugin requires 'vendor/autoload.php' to function properly"
    exit 1
fi

# Check if GitHub updater package is included
if [ ! -d "${PACKAGE_DIR}/vendor/silverassist/wp-github-updater" ]; then
    print_warning "GitHub updater package not found in vendor directory"
    print_warning "Automatic updates may not work properly"
else
    print_success "  ✓ GitHub updater package included"
fi

# Check if Settings Hub package is included
if [ ! -d "${PACKAGE_DIR}/vendor/silverassist/wp-settings-hub" ]; then
    print_warning "Settings Hub package not found in vendor directory"
    print_warning "Centralized settings menu may not work properly"
else
    print_success "  ✓ Settings Hub package included"
fi

# Check if required directories exist
if [ ! -d "${PACKAGE_DIR}/includes" ]; then
    print_error "includes/ directory missing from package"
    exit 1
else
    print_status "  ✓ includes/ directory included"
fi

# Check if assets directory exists
if [ ! -d "${PACKAGE_DIR}/assets" ]; then
    print_warning "assets/ directory missing from package"
    print_warning "CSS and JavaScript files may not load properly"
else
    # Check for CSS and JS files
    if [ -f "${PACKAGE_DIR}/assets/css/admin-debug-logs.css" ] && [ -f "${PACKAGE_DIR}/assets/js/admin-debug-logs.js" ]; then
        print_success "  ✓ Assets/ directory included with CSS and JS files"
    else
        print_warning "  ⚠ Assets/ directory present but missing expected files"
    fi
fi

print_success "  ✓ Package validation passed"

# Create ZIP archive
print_status "Creating ZIP archive..."

cd "$RELEASE_DIR"
ZIP_FILE="${PLUGIN_SLUG}-v${VERSION}.zip"

if command -v zip >/dev/null 2>&1; then
    zip -r "$ZIP_FILE" "$PLUGIN_SLUG" >/dev/null 2>&1
    
    if [ -f "$ZIP_FILE" ]; then
        ZIP_SIZE=$(du -h "$ZIP_FILE" | cut -f1)
        print_success "  ✓ ZIP archive created: ${ZIP_FILE} (${ZIP_SIZE})"
        
        # Move ZIP to build root for easier access
        mv "$ZIP_FILE" "${BUILD_DIR}/"
        ZIP_PATH="${BUILD_DIR}/${ZIP_FILE}"
    else
        print_error "Failed to create ZIP archive"
        exit 1
    fi
else
    print_warning "ZIP command not found. Archive not created."
    ZIP_PATH="${RELEASE_DIR}/${PLUGIN_SLUG}"
fi

cd "${PROJECT_ROOT}"

# Clean up for local development (move CI/CD files)
if [ -z "$GITHUB_ACTIONS" ]; then
    print_status "Cleaning up for local development..."
    
    # Create CI folder for non-essential files
    CI_DIR="${BUILD_DIR}/ci-artifacts"
    mkdir -p "$CI_DIR"
    
    # Move release folder to CI artifacts (it's just an unpacked version)
    if [ -d "$RELEASE_DIR" ]; then
        mv "$RELEASE_DIR" "$CI_DIR/"
    fi
    
    print_success "  ✓ CI/CD artifacts moved to ci-artifacts/ folder"
    print_status "  📁 Main build folder now contains only: ${PLUGIN_SLUG}-v${VERSION}.zip"
fi

# Summary
echo ""
echo "=================================================="
echo "BUILD COMPLETED SUCCESSFULLY"
echo "=================================================="
echo "Version: $VERSION"
echo "Build directory: $BUILD_DIR"

if [ -z "$GITHUB_ACTIONS" ]; then
    echo ""
    print_success "📦 MAIN OUTPUT:"
    echo "  🎯 Ready to use: ${BUILD_DIR}/${PLUGIN_SLUG}-v${VERSION}.zip"
    echo ""
    print_status "📁 CI/CD ARTIFACTS (moved to ci-artifacts/):"
    echo "  📋 Release folder (unpacked version)"
else
    echo "Package directory: $PACKAGE_DIR"
    if [ -f "$ZIP_PATH" ]; then
        echo "ZIP archive: $ZIP_PATH"
    fi
fi
echo ""
print_success "🎉 Release package ready for distribution!"
echo ""

print_status "Next steps:"
if [ -z "$GITHUB_ACTIONS" ]; then
    echo "  1. Test the package: build/${PLUGIN_SLUG}-v${VERSION}.zip"
    echo "  2. Install in a clean WordPress installation"
    echo "  3. Create GitHub release and upload the ZIP file"
    echo "  4. Plugin will auto-update from GitHub releases"
else
    echo "  1. Test the package in a clean WordPress installation"
    echo "  2. Create GitHub release with the ZIP file"
    echo "  3. Users will receive automatic update notifications"
fi
echo ""

print_warning "Remember to test the package before distributing!"

# Final file listing
if [ -z "$GITHUB_ACTIONS" ]; then
    echo ""
    print_success "🎯 Ready to distribute: build/${PLUGIN_SLUG}-v${VERSION}.zip"
    
    if [ -f "${BUILD_DIR}/${PLUGIN_SLUG}-v${VERSION}.zip" ]; then
        ZIP_SIZE=$(du -h "${BUILD_DIR}/${PLUGIN_SLUG}-v${VERSION}.zip" | cut -f1)
        echo "   Size: ${ZIP_SIZE}"
    fi
else
    echo "Package contents:"
    find "${PACKAGE_DIR}" -type f | sed "s|${PACKAGE_DIR}/|  - |" | head -20
    if [ $(find "${PACKAGE_DIR}" -type f | wc -l) -gt 20 ]; then
        echo "  ... and $(($(find "${PACKAGE_DIR}" -type f | wc -l) - 20)) more files"
    fi
fi

exit 0
