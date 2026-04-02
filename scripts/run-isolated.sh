#!/bin/sh
# Script to run commands in an isolated copy of the codebase
# Usage: ./scripts/run-isolated.sh <command> [args...]
# Example: ./scripts/run-isolated.sh composer install
# Example: ./scripts/run-isolated.sh ./vendor/bin/phpunit

set -e

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Get the project root directory (parent of scripts/)
PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"

# Create temporary directory for isolated testing
TMP_DIR=$(mktemp -d -t plugin-test.XXXXXX)

echo "${YELLOW}==> Creating isolated test environment in: ${TMP_DIR}${NC}"

# Cleanup function
cleanup() {
    echo "${YELLOW}==> Cleaning up isolated environment${NC}"
    rm -rf "$TMP_DIR"
}

# Register cleanup on exit
trap cleanup EXIT INT TERM

# Copy source code to temporary directory (exclude vendor, node_modules, build artifacts)
echo "${YELLOW}==> Copying source code to isolated environment${NC}"
rsync -a \
    --exclude='vendor/' \
    --exclude='node_modules/' \
    --exclude='build/' \
    --exclude='.git/' \
    --exclude='.phpunit.cache/' \
    --exclude='coverage/' \
    --exclude='composer.lock' \
    --exclude='.idea/' \
    --exclude='*.zip' \
    "$PROJECT_ROOT/" "$TMP_DIR/"

# Change to temporary directory
cd "$TMP_DIR"

echo "${YELLOW}==> Installing dependencies in isolated environment${NC}"
# Run composer install in isolated environment
composer install --quiet --no-interaction --prefer-dist

# Execute the command passed as arguments
echo "${GREEN}==> Running command in isolated environment: $@${NC}"
"$@"

# Capture exit code
EXIT_CODE=$?

if [ $EXIT_CODE -eq 0 ]; then
    echo "${GREEN}==> Command completed successfully${NC}"
else
    echo "${RED}==> Command failed with exit code: $EXIT_CODE${NC}"
fi

exit $EXIT_CODE