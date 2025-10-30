#!/bin/bash
###############################################################################
# PHPCS Error Fixer for Silver Assist ACF Clone Fields
#
# This script fixes common PHPCS errors automatically
#
# @package  silver-assist-acf-clone-fields
# @author   Silver Assist
# @version  1.0.0
###############################################################################

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_header() {
    echo ""
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

cd "$PROJECT_ROOT"

print_header "🔧 Fixing PHPCS Errors Automatically"

print_info "Step 1: Replace text domain constants with string literals"
find includes/ -name "*.php" -exec sed -i '' 's/SILVER_ACF_CLONE_TEXT_DOMAIN/'\''silver-assist-acf-clone-fields'\''/g' {} \;
sed -i '' 's/SILVER_ACF_CLONE_TEXT_DOMAIN/'\''silver-assist-acf-clone-fields'\''/g' silver-assist-acf-clone-fields.php

print_info "Step 2: Fix Yoda conditions (basic patterns)"
# This is a complex fix that requires careful analysis of each case
# For now, we'll create a list of files that need manual review

print_info "Step 3: Fix inline comments punctuation"
find includes/ -name "*.php" -exec sed -i '' 's|// \(.*[^.!?]\)$|// \1.|g' {} \;

print_info "Step 4: Fix hook names with proper prefix"
find includes/ -name "*.php" -exec sed -i '' 's/acf_clone_fields_/silver_assist_acf_clone_fields_/g' {} \;

print_info "Step 5: Fix date() function calls to use gmdate()"
find includes/ -name "*.php" -exec sed -i '' 's/date(/gmdate(/g' {} \;

print_success "Automatic fixes completed!"

print_header "📝 Files that need manual review for Yoda conditions:"

echo "The following files have Yoda condition errors that need manual review:"
echo ""

# List files with Yoda condition errors
vendor/bin/phpcs --standard=phpcs.xml --report=summary 2>/dev/null | grep "WordPress.PHP.YodaConditions.NotYoda" || true

print_header "🧪 Running PHPCS after automatic fixes"
vendor/bin/phpcs --standard=phpcs.xml --report=summary

echo ""
print_info "Manual fixes still needed:"
echo "1. Convert remaining conditions to Yoda format (variable === value)"
echo "2. Add translators comments for placeholder strings"
echo "3. Fix remaining text domain issues"
echo "4. Review and fix any remaining style issues"