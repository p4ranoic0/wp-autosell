<?php
/**
 * PHP Configuration and Diagnostics Page
 * Access at: /phpinfo.php
 * 
 * This file helps diagnose configuration issues
 * DELETE THIS FILE after debugging for security
 */

// Only allow access from localhost or specific IPs in production
// Uncomment and customize for security:
// $allowed_ips = ['127.0.0.1', '::1', 'YOUR_IP_HERE'];
// if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips)) {
//     http_response_code(403);
//     die('Access denied');
// }

?>
<!DOCTYPE html>
<html>
<head>
    <title>WordPress Diagnostics</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h2 { color: #0073aa; }
        .section { margin: 20px 0; padding: 15px; background: #f0f0f0; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        table { border-collapse: collapse; width: 100%; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background-color: #0073aa; color: white; }
    </style>
</head>
<body>
    <h1>WordPress Environment Diagnostics</h1>
    
    <div class="section">
        <h2>Server Information</h2>
        <table>
            <tr><th>Item</th><th>Value</th></tr>
            <tr><td>PHP Version</td><td><?php echo PHP_VERSION; ?></td></tr>
            <tr><td>Memory Limit</td><td><?php echo ini_get('memory_limit'); ?></td></tr>
            <tr><td>Max Execution Time</td><td><?php echo ini_get('max_execution_time'); ?>s</td></tr>
            <tr><td>Upload Max Filesize</td><td><?php echo ini_get('upload_max_filesize'); ?></td></tr>
            <tr><td>Post Max Size</td><td><?php echo ini_get('post_max_size'); ?></td></tr>
            <tr><td>Memory Used</td><td><?php echo round(memory_get_usage() / 1024 / 1024, 2); ?> MB</td></tr>
        </table>
    </div>
    
    <div class="section">
        <h2>Required PHP Extensions</h2>
        <?php
        $required_extensions = ['mbstring', 'mysqli', 'curl', 'gd', 'xml', 'zip', 'openssl', 'json'];
        echo '<table><tr><th>Extension</th><th>Status</th></tr>';
        foreach ($required_extensions as $ext) {
            $loaded = extension_loaded($ext);
            $status = $loaded ? '<span class="success">✓ Loaded</span>' : '<span class="error">✗ Missing</span>';
            echo "<tr><td>$ext</td><td>$status</td></tr>";
        }
        echo '</table>';
        ?>
    </div>
    
    <div class="section">
        <h2>Database Connection Test</h2>
        <?php
        $db_host = getenv('DB_HOST') ?: '127.0.0.1';
        $db_name = getenv('DB_NAME') ?: 'wordpress';
        $db_user = getenv('DB_USER') ?: 'root';
        $db_password = getenv('DB_PASSWORD') ?: '';
        
        $has_db_host = (getenv('DB_HOST') !== false && getenv('DB_HOST') !== '');
        $has_db_name = (getenv('DB_NAME') !== false && getenv('DB_NAME') !== '');
        $has_db_user = (getenv('DB_USER') !== false && getenv('DB_USER') !== '');
        $has_db_pass = (getenv('DB_PASSWORD') !== false && getenv('DB_PASSWORD') !== '');

        echo "<p><strong>DB_HOST env var:</strong> " . ($has_db_host ? '<span class="success">SET</span> (' . htmlspecialchars($db_host) . ')' : '<span class="error">NOT SET</span> (defaulting to 127.0.0.1)') . "</p>";
        echo "<p><strong>DB_NAME env var:</strong> " . ($has_db_name ? '<span class="success">SET</span>' : '<span class="error">NOT SET</span> (defaulting to wordpress)') . "</p>";
        echo "<p><strong>DB_USER env var:</strong> " . ($has_db_user ? '<span class="success">SET</span>' : '<span class="error">NOT SET</span> (defaulting to root)') . "</p>";
        echo "<p><strong>DB_PASSWORD env var:</strong> " . ($has_db_pass ? '<span class="success">SET</span>' : '<span class="error">NOT SET</span>') . "</p>";
        
        if (!$has_db_host || !$has_db_name || !$has_db_user) {
            echo '<p class="error">✗ Database env variables NOT configured. Set DB_HOST, DB_USER, DB_PASSWORD, DB_NAME in DigitalOcean App Platform Settings > Environment Variables</p>';
        } else {
            // First check if host is reachable with a short timeout
            $host_parts = explode(':', $db_host);
            $connect_host = $host_parts[0];
            $connect_port = isset($host_parts[1]) ? (int)$host_parts[1] : 3306;
            
            echo "<p>Testing TCP connection to $connect_host:$connect_port (3s timeout)...</p>";
            $socket = @fsockopen($connect_host, $connect_port, $errno, $errstr, 3);
            
            if ($socket === false) {
                echo '<p class="error">✗ Cannot reach database host: ' . htmlspecialchars($errstr) . ' (errno: ' . $errno . ')</p>';
                echo '<p class="warning">⚠ Check: 1) DB_HOST value is correct, 2) Database is running, 3) App is in DB trusted sources</p>';
            } else {
                fclose($socket);
                echo '<p class="success">✓ Host is reachable. Testing MySQL authentication...</p>';
                
                try {
                    $mysqli = mysqli_init();
                    $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
                    
                    // Check if SSL is required (DigitalOcean Managed MySQL)
                    $db_ssl_env = getenv('DB_SSL');
                    $use_ssl = $db_ssl_env && in_array(strtolower(trim($db_ssl_env)), ['true', 'required'], true);
                    $flags = $use_ssl ? MYSQLI_CLIENT_SSL : 0;
                    
                    if ($use_ssl) {
                        $mysqli->ssl_set(NULL, NULL, NULL, NULL, NULL);
                        echo '<p class="success">Using SSL connection (DB_SSL=' . htmlspecialchars($db_ssl_env) . ')</p>';
                    }
                    @$mysqli->real_connect($connect_host, $db_user, $db_password, $db_name, $connect_port, NULL, $flags);
                    if ($mysqli->connect_error) {
                        echo '<p class="error">✗ Database Connection Failed: ' . htmlspecialchars($mysqli->connect_error) . '</p>';
                    } else {
                        echo '<p class="success">✓ Database Connection Successful</p>';
                        echo '<p>MySQL Version: ' . $mysqli->server_info . '</p>';
                        $mysqli->close();
                    }
                } catch (Exception $e) {
                    echo '<p class="error">✗ Database Connection Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
            }
        }
        ?>
    </div>
    
    <div class="section">
        <h2>WordPress Files Check</h2>
        <?php
        $required_files = [
            'wp-config.php' => 'Configuration file',
            'wp-settings.php' => 'WordPress settings',
            'wp-includes/version.php' => 'WordPress version',
            'wp-admin/index.php' => 'Admin panel',
            'wp-content' => 'Content directory'
        ];
        
        echo '<table><tr><th>File/Directory</th><th>Description</th><th>Status</th></tr>';
        foreach ($required_files as $file => $desc) {
            $exists = file_exists(__DIR__ . '/' . $file);
            $status = $exists ? '<span class="success">✓ Exists</span>' : '<span class="error">✗ Missing</span>';
            echo "<tr><td>$file</td><td>$desc</td><td>$status</td></tr>";
        }
        echo '</table>';
        ?>
    </div>
    
    <div class="section">
        <h2>Environment Variables</h2>
        <?php
        $env_vars = ['DB_NAME', 'DB_USER', 'DB_HOST', 'DB_SSL', 'AUTH_KEY', 'WP_DEBUG'];
        echo '<table><tr><th>Variable</th><th>Status</th></tr>';
        foreach ($env_vars as $var) {
            $value = getenv($var);
            $status = $value ? '<span class="success">✓ Set</span>' : '<span class="warning">⚠ Not Set</span>';
            echo "<tr><td>$var</td><td>$status</td></tr>";
        }
        echo '</table>';
        ?>
    </div>
    
    <div class="section">
        <h2>Memory Status</h2>
        <?php
        $memory_limit = ini_get('memory_limit');
        $memory_limit_bytes = 0;
        if (preg_match('/^(\d+)(.)$/', $memory_limit, $matches)) {
            $memory_limit_bytes = $matches[1];
            if ($matches[2] == 'M') {
                $memory_limit_bytes *= 1024 * 1024;
            } elseif ($matches[2] == 'K') {
                $memory_limit_bytes *= 1024;
            } elseif ($matches[2] == 'G') {
                $memory_limit_bytes *= 1024 * 1024 * 1024;
            }
        }
        
        $memory_used = memory_get_usage(true);
        $memory_percent = ($memory_limit_bytes > 0) ? round(($memory_used / $memory_limit_bytes) * 100, 2) : 0;
        
        echo "<p><strong>Memory Limit:</strong> $memory_limit</p>";
        echo "<p><strong>Memory Used:</strong> " . round($memory_used / 1024 / 1024, 2) . " MB</p>";
        echo "<p><strong>Memory Usage:</strong> $memory_percent%</p>";
        ?>
    </div>
    
    <div class="section">
        <h2>⚠️ Security Warning</h2>
        <p style="color: red;"><strong>DELETE THIS FILE (phpinfo.php) after debugging!</strong></p>
        <p>This file exposes sensitive information about your server configuration.</p>
    </div>
    
    <div class="section">
        <h2>Full PHP Info</h2>
        <p><a href="?fullinfo=1">Click here to view full phpinfo()</a></p>
        <?php
        if (isset($_GET['fullinfo'])) {
            phpinfo();
        }
        ?>
    </div>
</body>
</html>
