#!/bin/bash
set -e

echo "========================================="
echo "WordPress Installation Build Script"
echo "========================================="

# Check if WordPress core directories already exist
if [ -d "wp-includes" ] && [ -d "wp-admin" ] && [ -d "wp-content" ]; then
    echo "✓ WordPress core directories already exist. Skipping download."
    exit 0
fi

echo "→ Downloading WordPress..."
# Download latest WordPress - try curl first, then wget
if command -v curl &> /dev/null; then
    if curl -sL -o /tmp/wordpress.tar.gz https://wordpress.org/latest.tar.gz; then
        echo "✓ Downloaded WordPress with curl"
    else
        echo "✗ curl failed, trying wget..."
        if command -v wget &> /dev/null; then
            if wget -q -O /tmp/wordpress.tar.gz https://wordpress.org/latest.tar.gz; then
                echo "✓ Downloaded WordPress with wget"
            else
                echo "✗ Failed to download WordPress with both curl and wget"
                exit 1
            fi
        else
            echo "✗ Failed to download WordPress - curl failed and wget not available"
            exit 1
        fi
    fi
elif command -v wget &> /dev/null; then
    if wget -q -O /tmp/wordpress.tar.gz https://wordpress.org/latest.tar.gz; then
        echo "✓ Downloaded WordPress with wget"
    else
        echo "✗ Failed to download WordPress with wget"
        exit 1
    fi
else
    echo "✗ Neither curl nor wget is available"
    exit 1
fi

echo "→ Extracting WordPress..."
# Extract to temp directory
tar -xzf /tmp/wordpress.tar.gz -C /tmp/

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

echo "→ Cleaning up..."
rm -rf /tmp/wordpress /tmp/wordpress.tar.gz

echo "========================================="
echo "✓ WordPress installation complete!"
echo "========================================="
