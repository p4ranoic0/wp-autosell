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

# Check PHP version and extensions
echo "→ Checking PHP version..."
php -v
echo ""

echo "→ Checking loaded PHP extensions..."
php -m
echo ""

echo "→ Checking for mbstring specifically..."
if php -m | grep -qw "mbstring"; then
    echo "✓ mbstring is available"
else
    echo "⚠️  WARNING: mbstring extension NOT found!"
    echo "Attempting to continue anyway..."
fi

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

echo "→ Installing WordPress core files and directories..."

# CRITICAL: Always update root-level WordPress core PHP files to match
# the downloaded version. This prevents version mismatch errors like
# "Call to undefined function wp_is_valid_utf8()" which happen when
# wp-settings.php is from an older version than wp-includes/.
echo "→ Updating WordPress core root files..."
WP_CORE_ROOT_FILES=(
    "wp-settings.php"
    "wp-load.php"
    "wp-blog-header.php"
    "wp-login.php"
    "wp-activate.php"
    "wp-comments-post.php"
    "wp-cron.php"
    "wp-links-opml.php"
    "wp-mail.php"
    "wp-signup.php"
    "wp-trackback.php"
    "xmlrpc.php"
    "index.php"
    "wp-config-sample.php"
)

for core_file in "${WP_CORE_ROOT_FILES[@]}"; do
    if [ -f "/tmp/wordpress/$core_file" ]; then
        cp -f "/tmp/wordpress/$core_file" "./$core_file"
        echo "  ✓ Updated $core_file"
    fi
done
echo "✓ Core root files updated to match downloaded WordPress version"

# Copy core directories (always fresh to avoid partial updates)
echo "→ Installing WordPress core directories..."
# Remove old directories to ensure clean copy
rm -rf wp-includes wp-admin

cp -r /tmp/wordpress/wp-includes ./
echo "✓ Copied wp-includes/"

cp -r /tmp/wordpress/wp-admin ./
echo "✓ Copied wp-admin/"

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
    # Ensure uploads directory always exists
    mkdir -p ./wp-content/uploads
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

# Final verification
if [ ! -f "wp-includes/version.php" ]; then
    echo "✗ ERROR: wp-includes/version.php not found after installation"
    exit 1
fi

echo "✓ Build verification successful"
echo ""
echo "Installed extensions:"
php -m

echo ""
echo "==================================="
echo "Build Summary:"
echo "==================================="
echo "→ WordPress Core Files:"
ls -lh wp-includes/version.php wp-admin/index.php wp-settings.php 2>/dev/null | awk '{print "  "$9" ("$5")"}'
echo ""
echo "→ Directory Structure:"
du -sh wp-includes wp-admin wp-content 2>/dev/null || echo "  Unable to calculate sizes"
echo ""
echo "→ PHP Configuration:"
echo "  Memory Limit: $(php -r 'echo ini_get("memory_limit");')"
echo "  Max Execution Time: $(php -r 'echo ini_get("max_execution_time");')s"
echo "  Upload Max: $(php -r 'echo ini_get("upload_max_filesize");')"
echo ""
echo "==================================="
echo "✓ Build Phase Complete"
echo "→ Next: Application will start with startup diagnostics"
echo "→ Access /health-check.php for deployment verification"
echo "→ Access /phpinfo.php for detailed PHP diagnostics"
echo "==================================="