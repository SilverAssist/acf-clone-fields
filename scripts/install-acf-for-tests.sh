#!/bin/bash
###############################################################################
# Install ACF Plugin for Testing
#
# Downloads and installs Advanced Custom Fields (free version) from the
# WordPress.org plugin repository for use in integration tests.
#
# Usage: ./scripts/install-acf-for-tests.sh
###############################################################################

set -e

# Configuration
ACF_VERSION=${ACF_VERSION:-"latest"}
WP_PLUGINS_DIR="${WP_PLUGINS_DIR:-/tmp/wordpress-tests/wp-content/plugins}"
ACF_PLUGIN_DIR="${WP_PLUGINS_DIR}/advanced-custom-fields"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}[ACF Test Setup]${NC} Installing ACF for integration tests..."

# Create plugins directory if it doesn't exist
if [ ! -d "$WP_PLUGINS_DIR" ]; then
    echo -e "${YELLOW}[INFO]${NC} Creating WordPress plugins directory..."
    mkdir -p "$WP_PLUGINS_DIR"
fi

# Remove existing ACF installation
if [ -d "$ACF_PLUGIN_DIR" ]; then
    echo -e "${YELLOW}[INFO]${NC} Removing existing ACF installation..."
    rm -rf "$ACF_PLUGIN_DIR"
fi

# Download ACF from WordPress.org
echo -e "${YELLOW}[INFO]${NC} Downloading ACF ${ACF_VERSION}..."
cd "$WP_PLUGINS_DIR"


# Use official ACF source for latest version
if [ "$ACF_VERSION" = "latest" ]; then
    DOWNLOAD_URL="https://www.advancedcustomfields.com/latest/"
else
    # If a specific version is needed, fallback to WordPress.org (or error)
    DOWNLOAD_URL="https://downloads.wordpress.org/plugin/advanced-custom-fields.${ACF_VERSION}.zip"
fi

# Download with curl or wget
if command -v curl &> /dev/null; then
    curl -L -o acf.zip "$DOWNLOAD_URL"
elif command -v wget &> /dev/null; then
    wget -O acf.zip "$DOWNLOAD_URL"
else
    echo -e "${RED}[ERROR]${NC} Neither curl nor wget found. Please install one of them."
    exit 1
fi

# Extract ACF
echo -e "${YELLOW}[INFO]${NC} Extracting ACF plugin..."
unzip -q acf.zip
rm acf.zip

# Verify installation
if [ -f "$ACF_PLUGIN_DIR/acf.php" ]; then
    ACF_INSTALLED_VERSION=$(grep -oP "Version:\s*\K[\d.]+" "$ACF_PLUGIN_DIR/acf.php" || echo "unknown")
    echo -e "${GREEN}[SUCCESS]${NC} ACF ${ACF_INSTALLED_VERSION} installed successfully!"
    echo -e "${GREEN}[SUCCESS]${NC} Installation path: ${ACF_PLUGIN_DIR}"
else
    echo -e "${RED}[ERROR]${NC} ACF installation failed. acf.php not found."
    exit 1
fi

# Create symlink in WP_TESTS_DIR if different from standard location
WP_TESTS_DIR="${WP_TESTS_DIR:-/tmp/wordpress-tests-lib}"
WP_TESTS_PLUGINS="${WP_TESTS_DIR}/../wordpress/wp-content/plugins"

if [ "$WP_TESTS_PLUGINS" != "$WP_PLUGINS_DIR" ] && [ -d "$WP_TESTS_DIR" ]; then
    echo -e "${YELLOW}[INFO]${NC} Creating symlink for WordPress test suite..."
    mkdir -p "$WP_TESTS_PLUGINS"
    
    if [ -L "$WP_TESTS_PLUGINS/advanced-custom-fields" ]; then
        rm "$WP_TESTS_PLUGINS/advanced-custom-fields"
    fi
    
    ln -s "$ACF_PLUGIN_DIR" "$WP_TESTS_PLUGINS/advanced-custom-fields"
    echo -e "${GREEN}[SUCCESS]${NC} Symlink created: ${WP_TESTS_PLUGINS}/advanced-custom-fields"
fi

echo -e "${GREEN}[COMPLETE]${NC} ACF is ready for integration tests!"
echo ""
echo "You can now run tests that require ACF:"
echo "  vendor/bin/phpunit tests/Integration/CloneOptionsTest.php"
