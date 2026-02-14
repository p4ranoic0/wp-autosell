#!/bin/bash
# Startup Diagnostics Script for WordPress Deployment
# This script runs at application startup to verify the environment

echo "=============================================="
echo "WordPress Startup Diagnostics"
echo "=============================================="
echo "Timestamp: $(date)"
echo "Hostname: $(hostname)"
echo "Working Directory: $(pwd)"
echo "=============================================="

# Function to check and report
check_item() {
    local name="$1"
    local command="$2"
    
    echo -n "→ Checking $name... "
    if eval "$command" > /dev/null 2>&1; then
        echo "✓ OK"
        return 0
    else
        echo "✗ FAILED"
        return 1
    fi
}

# Track overall status
FAILURES=0

# 1. Check PHP is available
echo ""
echo "=== PHP Environment ==="
if command -v php > /dev/null 2>&1; then
    echo "✓ PHP is available"
    php -v | head -n 1
else
    echo "✗ CRITICAL: PHP not found in PATH"
    ((FAILURES++))
fi

# 2. Check PHP extensions
echo ""
echo "=== PHP Extensions ==="
REQUIRED_EXTS="mysqli curl gd xml zip mbstring json openssl"
for ext in $REQUIRED_EXTS; do
    if php -m 2>/dev/null | grep -qw "$ext"; then
        echo "✓ $ext"
    else
        echo "✗ MISSING: $ext"
        ((FAILURES++))
    fi
done

# 3. Check WordPress files
echo ""
echo "=== WordPress Files ==="
REQUIRED_FILES=(
    "wp-config.php"
    "wp-settings.php"
    "index.php"
    "wp-includes/version.php"
    "wp-admin/index.php"
)

for file in "${REQUIRED_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "✓ $file"
    else
        echo "✗ MISSING: $file"
        ((FAILURES++))
    fi
done

REQUIRED_DIRS=(
    "wp-includes"
    "wp-admin"
    "wp-content"
    "wp-content/themes"
    "wp-content/plugins"
)

for dir in "${REQUIRED_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        echo "✓ $dir/"
    else
        echo "✗ MISSING: $dir/"
        ((FAILURES++))
    fi
done

# 4. Check environment variables
echo ""
echo "=== Environment Variables ==="
ENV_VARS=(
    "DB_NAME"
    "DB_USER"
    "DB_PASSWORD"
    "DB_HOST"
    "AUTH_KEY"
    "SECURE_AUTH_KEY"
    "LOGGED_IN_KEY"
    "NONCE_KEY"
)

for var in "${ENV_VARS[@]}"; do
    if [ -n "${!var}" ]; then
        echo "✓ $var is set"
    else
        echo "✗ NOT SET: $var"
        if [[ "$var" == DB_* ]] || [[ "$var" == *_KEY ]]; then
            ((FAILURES++))
        fi
    fi
done

# 5. Test database connection
echo ""
echo "=== Database Connection ==="
if [ -n "$DB_HOST" ] && [ -n "$DB_USER" ] && [ -n "$DB_NAME" ]; then
    echo "Attempting to connect to: $DB_HOST"
    echo "Database: $DB_NAME"
    echo "User: $DB_USER"
    
    # Try to connect using PHP with a timeout
    CONNECTION_TEST=$(timeout 10 php -r "
        \$host = getenv('DB_HOST');
        \$user = getenv('DB_USER');
        \$pass = getenv('DB_PASSWORD');
        \$db = getenv('DB_NAME');
        
        // Parse host:port
        \$parts = explode(':', \$host);
        \$connect_host = \$parts[0];
        \$connect_port = isset(\$parts[1]) ? (int)\$parts[1] : 3306;
        
        // First test TCP reachability with 3s timeout
        \$socket = @fsockopen(\$connect_host, \$connect_port, \$errno, \$errstr, 3);
        if (\$socket === false) {
            echo 'ERROR: Cannot reach DB host ' . \$connect_host . ':' . \$connect_port . ' - ' . \$errstr . ' (errno: ' . \$errno . ')';
            exit(1);
        }
        fclose(\$socket);
        
        try {
            \$mysqli = mysqli_init();
            \$mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
            
            // Check if SSL is required (DigitalOcean Managed MySQL)
            \$db_ssl = getenv('DB_SSL');
            \$use_ssl = \$db_ssl && in_array(strtolower(trim(\$db_ssl)), ['true', 'required'], true);
            \$flags = \$use_ssl ? MYSQLI_CLIENT_SSL : 0;
            if (\$use_ssl) { \$mysqli->ssl_set(NULL,NULL,NULL,NULL,NULL); }
            @\$mysqli->real_connect(\$connect_host, \$user, \$pass, \$db, \$connect_port, NULL, \$flags);
            if (\$mysqli->connect_error) {
                echo 'ERROR: ' . \$mysqli->connect_error;
                exit(1);
            }
            echo 'SUCCESS: Connected to MySQL ' . \$mysqli->server_info;
            \$mysqli->close();
            exit(0);
        } catch (Exception \$e) {
            echo 'ERROR: ' . \$e->getMessage();
            exit(1);
        }
    " 2>&1)
    
    if echo "$CONNECTION_TEST" | grep -q "SUCCESS"; then
        echo "✓ $CONNECTION_TEST"
    else
        echo "✗ $CONNECTION_TEST"
        ((FAILURES++))
    fi
else
    echo "✗ Missing database configuration variables"
    ((FAILURES++))
fi

# 6. Check file permissions
echo ""
echo "=== File Permissions ==="
check_writable() {
    local dir="$1"
    if [ -d "$dir" ]; then
        if [ -w "$dir" ]; then
            echo "✓ $dir is writable"
        else
            echo "⚠ $dir is NOT writable (may cause issues)"
        fi
    fi
}

check_writable "wp-content"
check_writable "wp-content/uploads"
check_writable "wp-content/themes"
check_writable "wp-content/plugins"

# 7. Memory and resource check
echo ""
echo "=== System Resources ==="
echo "PHP Memory Limit: $(php -r 'echo ini_get("memory_limit");')"
echo "PHP Max Execution Time: $(php -r 'echo ini_get("max_execution_time");')s"
echo "PHP Upload Max: $(php -r 'echo ini_get("upload_max_filesize");')"
echo "PHP Post Max: $(php -r 'echo ini_get("post_max_size");')"

# 8. WordPress version check
echo ""
echo "=== WordPress Version ==="
if [ -f "wp-includes/version.php" ]; then
    WP_VERSION=$(grep "wp_version = " wp-includes/version.php | cut -d"'" -f2 2>/dev/null || echo "unknown")
    echo "✓ WordPress version: $WP_VERSION"
else
    echo "✗ Cannot determine WordPress version"
    ((FAILURES++))
fi

# Summary
echo ""
echo "=============================================="
if [ $FAILURES -eq 0 ]; then
    echo "✓✓✓ ALL CHECKS PASSED ✓✓✓"
    echo "WordPress should be ready to start!"
    echo "=============================================="
    exit 0
else
    echo "✗✗✗ $FAILURES CRITICAL ISSUES FOUND ✗✗✗"
    echo "WordPress may not work correctly!"
    echo "=============================================="
    echo ""
    echo "TROUBLESHOOTING STEPS:"
    echo "1. Review the errors above"
    echo "2. Check Runtime Logs in DigitalOcean dashboard"
    echo "3. Verify environment variables in App Settings"
    echo "4. Access /health-check.php for detailed diagnostics"
    echo "5. Access /phpinfo.php to see full PHP configuration"
    echo "=============================================="
    exit 0  # Don't fail startup, just warn
fi
