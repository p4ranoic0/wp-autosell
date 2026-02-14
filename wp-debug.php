<?php
/**
 * WordPress Load Debugger
 * Attempts to load WordPress step by step to identify where the critical error occurs
 * Access at: /wp-debug.php
 * DELETE THIS FILE after debugging!
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

header('Content-Type: text/html; charset=utf-8');
echo "<h1>WordPress Load Debugger</h1><pre>\n";

// Step 1: Basic PHP
echo "✓ Step 1: PHP is running (v" . PHP_VERSION . ")\n";
echo "  Memory limit: " . ini_get('memory_limit') . "\n";
echo "  Max execution time: " . ini_get('max_execution_time') . "s\n\n";

// Step 2: Check wp-config.php can be parsed
echo "→ Step 2: Loading wp-config.php constants...\n";
try {
    // We'll manually parse wp-config to check for issues
    if (!file_exists(__DIR__ . '/wp-config.php')) {
        echo "✗ wp-config.php NOT FOUND\n";
        exit;
    }
    echo "  ✓ wp-config.php exists\n";
} catch (Throwable $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit;
}

// Step 3: Test DB connection the WordPress way
echo "\n→ Step 3: Testing DB connection (WordPress-style)...\n";
$db_host_raw = getenv('DB_HOST') ?: '127.0.0.1';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASSWORD') ?: '';
$db_name = getenv('DB_NAME') ?: 'wordpress';
$db_ssl = getenv('DB_SSL');

echo "  DB_HOST: $db_host_raw\n";
echo "  DB_NAME: $db_name\n";
echo "  DB_USER: $db_user\n";
echo "  DB_SSL: " . ($db_ssl ?: 'not set') . "\n";

// Parse host:port like WordPress does
$host = $db_host_raw;
$port = null;
$socket = null;

if (strpos($host, ':') !== false) {
    list($host, $port_or_socket) = explode(':', $host, 2);
    if (is_numeric($port_or_socket)) {
        $port = (int) $port_or_socket;
    } else {
        $socket = $port_or_socket;
    }
}

echo "  Parsed host: $host\n";
echo "  Parsed port: " . ($port ?: 'default') . "\n";
echo "  Parsed socket: " . ($socket ?: 'none') . "\n";

$mysqli = mysqli_init();
$mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);

$flags = 0;
$use_ssl = $db_ssl && in_array(strtolower(trim($db_ssl)), ['true', 'required'], true);
if ($use_ssl) {
    $mysqli->ssl_set(NULL, NULL, NULL, NULL, NULL);
    $flags = MYSQLI_CLIENT_SSL;
    echo "  Using SSL: YES\n";
}

$connected = @$mysqli->real_connect($host, $db_user, $db_pass, $db_name, $port, $socket, $flags);

if (!$connected || $mysqli->connect_error) {
    echo "  ✗ DB Connection FAILED: " . $mysqli->connect_error . "\n";
    echo "  Error code: " . $mysqli->connect_errno . "\n";
} else {
    echo "  ✓ DB Connection OK (MySQL " . $mysqli->server_info . ")\n";
    
    // Check if WordPress tables exist
    $result = $mysqli->query("SHOW TABLES");
    $count = $result ? $result->num_rows : 0;
    echo "  Tables in database: $count\n";
    if ($count == 0) {
        echo "  ⚠ Database is empty - WordPress needs to install tables\n";
    }
    $mysqli->close();
}

// Step 4: Try to load WordPress
echo "\n→ Step 4: Loading WordPress (wp-settings.php)...\n";
echo "  This is where the critical error likely happens.\n";
echo "  ABSPATH will be: " . __DIR__ . "/\n\n";

flush();

// Set up error handler to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
        echo "\n\n";
        echo "╔══════════════════════════════════════════════╗\n";
        echo "║  FATAL ERROR DETECTED                        ║\n";
        echo "╚══════════════════════════════════════════════╝\n";
        echo "Type: " . $error['type'] . "\n";
        echo "Message: " . $error['message'] . "\n";
        echo "File: " . $error['file'] . "\n";
        echo "Line: " . $error['line'] . "\n";
    }
});

// Now try to load WordPress
try {
    define('WP_USE_THEMES', false);
    define('ABSPATH', __DIR__ . '/');
    
    // Load wp-config constants manually (skip the require wp-settings at the end)
    // Instead, we load wp-settings.php directly which wp-config.php would do
    echo "  Loading wp-load.php...\n";
    flush();
    
    require(__DIR__ . '/wp-load.php');
    
    echo "  ✓ WordPress loaded successfully!\n";
    echo "  WordPress version: " . (defined('WP_VERSION') ? WP_VERSION : 'unknown') . "\n";
    
    global $wpdb;
    if (isset($wpdb) && $wpdb->ready) {
        echo "  ✓ Database connection via wpdb is ready\n";
    } else {
        echo "  ✗ wpdb is not ready\n";
        if (isset($wpdb->last_error)) {
            echo "  Error: " . $wpdb->last_error . "\n";
        }
    }
    
} catch (Throwable $e) {
    echo "\n✗ EXCEPTION during WordPress load:\n";
    echo "  Type: " . get_class($e) . "\n";
    echo "  Message: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . "\n";
    echo "  Line: " . $e->getLine() . "\n";
    echo "  Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n</pre>";
