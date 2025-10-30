#!/bin/bash
###############################################################################
# Quick Yoda Conditions Fixer
# Fixes the most common Yoda condition patterns
###############################################################################

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

cd "$PROJECT_ROOT"

echo "🔧 Fixing Yoda Conditions..."

# Fix common Yoda condition patterns
find includes/ -name "*.php" -exec sed -i '' 's/\$[a-zA-Z_][a-zA-Z0-9_]* === null/null === \$&/g' {} \;
find includes/ -name "*.php" -exec sed -i '' 's/\$[a-zA-Z_][a-zA-Z0-9_]* !== null/null !== \$&/g' {} \;
find includes/ -name "*.php" -exec sed -i '' 's/\$[a-zA-Z_][a-zA-Z0-9_]* === false/false === \$&/g' {} \;
find includes/ -name "*.php" -exec sed -i '' 's/\$[a-zA-Z_][a-zA-Z0-9_]* !== false/false !== \$&/g' {} \;
find includes/ -name "*.php" -exec sed -i '' 's/\$[a-zA-Z_][a-zA-Z0-9_]* === true/true === \$&/g' {} \;
find includes/ -name "*.php" -exec sed -i '' 's/\$[a-zA-Z_][a-zA-Z0-9_]* !== true/true !== \$&/g' {} \;

# Fix string comparisons - this is more complex, let's do specific ones
find includes/ -name "*.php" -exec sed -i '' "s/\$[a-zA-Z_][a-zA-Z0-9_]* === 'active'/'active' === \$&/g" {} \;
find includes/ -name "*.php" -exec sed -i '' "s/\$[a-zA-Z_][a-zA-Z0-9_]* === 'POST'/'POST' === \$&/g" {} \;
find includes/ -name "*.php" -exec sed -i '' "s/\$[a-zA-Z_][a-zA-Z0-9_]* === 'GET'/'GET' === \$&/g" {} \;

echo "✅ Basic Yoda conditions fixed"

# Run PHPCS to see progress
echo ""
echo "📊 Checking progress..."
vendor/bin/phpcs --standard=phpcs.xml --report=summary

echo ""
echo "ℹ️  Note: Some complex Yoda conditions may still need manual fixing"