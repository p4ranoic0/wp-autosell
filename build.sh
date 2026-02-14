#!/bin/bash
set -e  # Exit on error
set -u  # Exit on undefined variable
set -o pipefail  # Exit on pipe failure

echo "==================================="
echo "WordPress Installation Build Script"
echo "==================================="
echo "Build started at: $(date)"
echo "Working directory: $(pwd)"
echo "==================================="

# Verify PHP and required extensions
echo "→ Checking PHP version and extensions..."
php -v
echo ""
echo "Checking for required extensions:"
php -m | grep -E "(mbstring|mysqli|curl|gd|xml|zip|openssl)" || echo "⚠️  Warning: Some required extensions may be missing"
echo ""

# Check if WordPress core directories already exist
if [ -d "wp-includes" ] && [ -d "wp-admin" ] && [ -d "wp-content" ]; then
    echo "✓ WordPress core directories already exist. Skipping download."
    
    # Verify critical files
    if [ -f "wp-includes/version.php" ]; then
        echo "✓ Verification: wp-includes/version.php exists"
    else
        echo "⚠ Warning: wp-includes/version.php not found, will re-download"
        rm -rf wp-includes wp-admin wp-content
    fi
    
    if [ -d "wp-includes" ]; then
        echo "✓ WordPress installation is complete and verified"
        exit 0
    fi
fi

echo "→ Downloading WordPress..."
echo "  Source: https://wordpress.org/latest.tar.gz"

# Clean up any previous attempts
rm -f /tmp/wordpress.tar.gz
rm -rf /tmp/wordpress

# Download latest WordPress - try curl first, then wget
DOWNLOAD_SUCCESS=false

if command -v curl &> /dev/null; then
    echo "  Using: curl"
    if curl -fsSL -o /tmp/wordpress.tar.gz https://wordpress.org/latest.tar.gz; then
        echo "✓ Downloaded WordPress with curl"
        DOWNLOAD_SUCCESS=true
    else
        echo "✗ curl failed (exit code: $?), trying wget..."
    fi
fi

if [ "$DOWNLOAD_SUCCESS" = false ] && command -v wget &> /dev/null; then
    echo "  Using: wget"
    if wget -q -O /tmp/wordpress.tar.gz https://wordpress.org/latest.tar.gz; then
        echo "✓ Downloaded WordPress with wget"
        DOWNLOAD_SUCCESS=true
    else
        echo "✗ wget failed (exit code: $?)"
    fi
fi

if [ "$DOWNLOAD_SUCCESS" = false ]; then
    echo "✗ ERROR: Failed to download WordPress"
    echo "  Attempted methods: curl, wget"
    echo "  Please check internet connectivity and try again"
    exit 1
fi

# Verify download
if [ ! -f /tmp/wordpress.tar.gz ]; then
    echo "✗ ERROR: Download file not found at /tmp/wordpress.tar.gz"
    exit 1
fi

# Check file size (WordPress is typically 15-25 MB compressed)
# Minimum size of 1 MB helps detect failed/incomplete downloads
MIN_EXPECTED_SIZE=1000000
FILE_SIZE=$(wc -c < /tmp/wordpress.tar.gz 2>/dev/null || echo "0")
echo "  Downloaded file size: $FILE_SIZE bytes"

if [ "$FILE_SIZE" -lt "$MIN_EXPECTED_SIZE" ]; then
    echo "✗ ERROR: Downloaded file is too small ($FILE_SIZE bytes), likely corrupt"
    echo "  Expected at least $MIN_EXPECTED_SIZE bytes"
    exit 1
fi

echo "✓ Download verified successfully"

echo "→ Extracting WordPress..."
# Extract to temp directory
if ! tar -xzf /tmp/wordpress.tar.gz -C /tmp/; then
    echo "✗ ERROR: Failed to extract WordPress archive"
    echo "  Archive may be corrupted"
    exit 1
fi

# Verify extraction
if [ ! -d "/tmp/wordpress" ]; then
    echo "✗ ERROR: WordPress directory not found after extraction"
    exit 1
fi

if [ ! -d "/tmp/wordpress/wp-includes" ] || [ ! -d "/tmp/wordpress/wp-admin" ]; then
    echo "✗ ERROR: WordPress core directories missing after extraction"
    exit 1
fi

echo "✓ Extraction verified successfully"

echo "→ Installing WordPress core directories..."
# Copy only the missing directories (wp-includes, wp-admin, wp-content)
# We keep the existing root files (wp-config.php, etc.) from the repository
if [ ! -d "wp-includes" ]; then
    cp -r /tmp/wordpress/wp-includes ./
    echo "✓ Copied wp-includes/"
fi

if [ ! -d "wp-admin" ]; then
    cp -r /tmp/wordpress/wp-admin ./
    echo "✓ Copied wp-admin/"
fi

# Handle wp-content specially - merge with any existing custom content
if [ ! -d "wp-content" ]; then
    cp -r /tmp/wordpress/wp-content ./
    echo "✓ Copied wp-content/"
else
    echo "→ wp-content/ exists, merging with WordPress defaults..."
    # Copy WordPress default directories if they don't exist
    [ ! -d "wp-content/themes" ] && cp -r /tmp/wordpress/wp-content/themes ./wp-content/
    [ ! -d "wp-content/plugins" ] && cp -r /tmp/wordpress/wp-content/plugins ./wp-content/
    [ ! -d "wp-content/languages" ] && mkdir -p ./wp-content/languages
    [ ! -d "wp-content/upgrade" ] && mkdir -p ./wp-content/upgrade
    [ ! -d "wp-content/uploads" ] && mkdir -p ./wp-content/uploads
    # Copy index.php if it doesn't exist
    [ ! -f "wp-content/index.php" ] && cp /tmp/wordpress/wp-content/index.php ./wp-content/
    echo "✓ Merged wp-content/"
fi

echo "→ Cleaning up temporary files..."
rm -rf /tmp/wordpress /tmp/wordpress.tar.gz
echo "✓ Cleanup complete"

echo "==================================="
echo "→ Verifying installation..."

# Critical verification checks
VERIFICATION_FAILED=false

if [ ! -d "wp-includes" ]; then
    echo "✗ VERIFICATION FAILED: wp-includes/ directory not found"
    VERIFICATION_FAILED=true
else
    echo "✓ wp-includes/ directory exists"
fi

if [ ! -d "wp-admin" ]; then
    echo "✗ VERIFICATION FAILED: wp-admin/ directory not found"
    VERIFICATION_FAILED=true
else
    echo "✓ wp-admin/ directory exists"
fi

if [ ! -d "wp-content" ]; then
    echo "✗ VERIFICATION FAILED: wp-content/ directory not found"
    VERIFICATION_FAILED=true
else
    echo "✓ wp-content/ directory exists"
fi

if [ ! -f "wp-includes/version.php" ]; then
    echo "✗ VERIFICATION FAILED: wp-includes/version.php not found"
    VERIFICATION_FAILED=true
else
    echo "✓ wp-includes/version.php exists"
    # Extract and display WordPress version
    WP_VERSION=$(grep "wp_version = " wp-includes/version.php | cut -d"'" -f2 || echo "unknown")
    echo "  WordPress version: $WP_VERSION"
fi

if [ ! -f "wp-admin/index.php" ]; then
    echo "✗ VERIFICATION FAILED: wp-admin/index.php not found"
    VERIFICATION_FAILED=true
else
    echo "✓ wp-admin/index.php exists"
fi

if [ "$VERIFICATION_FAILED" = true ]; then
    echo "==================================="
    echo "✗ INSTALLATION FAILED"
    echo "==================================="
    exit 1
fi

echo "==================================="
echo "✓ WordPress installation complete!"
echo "✓ All verification checks passed"
echo "Build completed at: $(date)"
echo "==================================="