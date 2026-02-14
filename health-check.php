<?php
/**
 * WordPress Deployment Health Check and Diagnostics
 * 
 * This endpoint provides detailed runtime diagnostics to help identify deployment issues.
 * Access at: /health-check.php
 * 
 * Returns JSON for programmatic access or HTML for browser viewing
 */

// Determine output format
$format = isset($_GET['format']) && $_GET['format'] === 'json' ? 'json' : 'html';

// Initialize results
$results = [
    'timestamp' => date('c'),
    'status' => 'unknown',
    'checks' => [],
    'errors' => [],
    'warnings' => []
];

/**
 * Helper function to add a check result
 */
function add_check($name, $status, $message = '', $details = null) {
    global $results;
    $results['checks'][$name] = [
        'status' => $status,  // 'ok', 'warning', 'error'
        'message' => $message,
        'details' => $details
    ];
    
    if ($status === 'error') {
        $results['errors'][] = "$name: $message";
    } elseif ($status === 'warning') {
        $results['warnings'][] = "$name: $message";
    }
}

// 1. Check PHP version
$php_version = PHP_VERSION;
$php_min_version = '7.4.0';
if (version_compare($php_version, $php_min_version, '>=')) {
    add_check('php_version', 'ok', "PHP $php_version", $php_version);
} else {
    add_check('php_version', 'error', "PHP version $php_version is below minimum $php_min_version", $php_version);
}

// 2. Check required PHP extensions
$required_extensions = ['mysqli', 'curl', 'gd', 'xml', 'zip', 'mbstring', 'json', 'openssl'];
$missing_extensions = [];
$loaded_extensions = [];

foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        $loaded_extensions[] = $ext;
    } else {
        $missing_extensions[] = $ext;
    }
}

if (empty($missing_extensions)) {
    add_check('php_extensions', 'ok', 'All required extensions loaded', $loaded_extensions);
} else {
    add_check('php_extensions', 'error', 'Missing extensions: ' . implode(', ', $missing_extensions), [
        'loaded' => $loaded_extensions,
        'missing' => $missing_extensions
    ]);
}

// 3. Check WordPress files
$required_files = [
    'wp-config.php' => 'WordPress configuration',
    'wp-settings.php' => 'WordPress settings',
    'index.php' => 'Main entry point',
    'wp-includes/version.php' => 'WordPress version info',
    'wp-admin/index.php' => 'Admin panel',
    'wp-blog-header.php' => 'Blog header'
];

$missing_files = [];
$found_files = [];

foreach ($required_files as $file => $description) {
    if (file_exists(__DIR__ . '/' . $file)) {
        $found_files[] = $file;
    } else {
        $missing_files[] = "$file ($description)";
    }
}

if (empty($missing_files)) {
    add_check('wordpress_files', 'ok', 'All WordPress core files present', $found_files);
} else {
    add_check('wordpress_files', 'error', 'Missing files: ' . implode(', ', $missing_files), [
        'found' => $found_files,
        'missing' => $missing_files
    ]);
}

// 4. Check WordPress directories
$required_dirs = [
    'wp-includes' => 'WordPress core includes',
    'wp-admin' => 'WordPress admin panel',
    'wp-content' => 'WordPress content',
    'wp-content/themes' => 'WordPress themes',
    'wp-content/plugins' => 'WordPress plugins'
];

$missing_dirs = [];
$found_dirs = [];

foreach ($required_dirs as $dir => $description) {
    if (is_dir(__DIR__ . '/' . $dir)) {
        $found_dirs[] = $dir;
    } else {
        $missing_dirs[] = "$dir ($description)";
    }
}

if (empty($missing_dirs)) {
    add_check('wordpress_directories', 'ok', 'All WordPress directories present', $found_dirs);
} else {
    add_check('wordpress_directories', 'error', 'Missing directories: ' . implode(', ', $missing_dirs), [
        'found' => $found_dirs,
        'missing' => $missing_dirs
    ]);
}

// 5. Check environment variables
$required_env_vars = [
    'DB_NAME' => 'Database name',
    'DB_USER' => 'Database user',
    'DB_PASSWORD' => 'Database password',
    'DB_HOST' => 'Database host',
    'AUTH_KEY' => 'WordPress authentication key',
    'SECURE_AUTH_KEY' => 'WordPress secure auth key',
    'LOGGED_IN_KEY' => 'WordPress logged in key',
    'NONCE_KEY' => 'WordPress nonce key'
];

$missing_env_vars = [];
$found_env_vars = [];

foreach ($required_env_vars as $var => $description) {
    $value = getenv($var);
    if ($value !== false && $value !== '') {
        $found_env_vars[] = $var;
    } else {
        $missing_env_vars[] = "$var ($description)";
    }
}

if (empty($missing_env_vars)) {
    add_check('environment_variables', 'ok', 'All required environment variables set', $found_env_vars);
} else {
    add_check('environment_variables', 'error', 'Missing environment variables: ' . implode(', ', $missing_env_vars), [
        'found' => $found_env_vars,
        'missing' => $missing_env_vars
    ]);
}

// 6. Test database connection
$db_host = getenv('DB_HOST');
$db_user = getenv('DB_USER');
$db_password = getenv('DB_PASSWORD');
$db_name = getenv('DB_NAME');

if ($db_host && $db_user && $db_name) {
    try {
        $mysqli = @new mysqli($db_host, $db_user, $db_password, $db_name);
        
        if ($mysqli->connect_error) {
            add_check('database_connection', 'error', 'Failed to connect: ' . $mysqli->connect_error, [
                'host' => $db_host,
                'database' => $db_name,
                'user' => $db_user,
                'error_code' => $mysqli->connect_errno,
                'error_message' => $mysqli->connect_error
            ]);
        } else {
            add_check('database_connection', 'ok', 'Successfully connected to database', [
                'host' => $db_host,
                'database' => $db_name,
                'user' => $db_user,
                'mysql_version' => $mysqli->server_info
            ]);
            
            // Check if WordPress tables exist
            $result = $mysqli->query("SHOW TABLES");
            $table_count = 0;
            $wp_tables = [];
            
            if ($result) {
                while ($row = $result->fetch_array()) {
                    $table_count++;
                    if (strpos($row[0], 'wp_') === 0) {
                        $wp_tables[] = $row[0];
                    }
                }
                
                if ($table_count === 0) {
                    add_check('wordpress_installation', 'warning', 'Database is empty - WordPress not yet installed', [
                        'tables' => 0,
                        'status' => 'needs_installation'
                    ]);
                } elseif (!empty($wp_tables)) {
                    add_check('wordpress_installation', 'ok', 'WordPress appears to be installed', [
                        'total_tables' => $table_count,
                        'wp_tables' => count($wp_tables),
                        'sample_tables' => array_slice($wp_tables, 0, 5)
                    ]);
                } else {
                    add_check('wordpress_installation', 'warning', 'Database has tables but no WordPress tables found', [
                        'total_tables' => $table_count,
                        'wp_tables' => 0
                    ]);
                }
            }
            
            $mysqli->close();
        }
    } catch (Exception $e) {
        add_check('database_connection', 'error', 'Exception: ' . $e->getMessage(), [
            'host' => $db_host,
            'database' => $db_name,
            'user' => $db_user,
            'exception' => get_class($e)
        ]);
    }
} else {
    add_check('database_connection', 'error', 'Database configuration incomplete', [
        'has_host' => !empty($db_host),
        'has_user' => !empty($db_user),
        'has_password' => !empty($db_password),
        'has_name' => !empty($db_name)
    ]);
}

// 7. Check file permissions
$writable_dirs = ['wp-content', 'wp-content/uploads', 'wp-content/themes', 'wp-content/plugins'];
$permission_issues = [];
$writable_ok = [];

foreach ($writable_dirs as $dir) {
    $full_path = __DIR__ . '/' . $dir;
    if (is_dir($full_path)) {
        if (is_writable($full_path)) {
            $writable_ok[] = $dir;
        } else {
            $permission_issues[] = "$dir (not writable)";
        }
    } else {
        $permission_issues[] = "$dir (does not exist)";
    }
}

if (empty($permission_issues)) {
    add_check('file_permissions', 'ok', 'All directories writable', $writable_ok);
} else {
    add_check('file_permissions', 'warning', 'Some directories not writable: ' . implode(', ', $permission_issues), [
        'writable' => $writable_ok,
        'issues' => $permission_issues
    ]);
}

// 8. Check memory configuration
$memory_limit = ini_get('memory_limit');
$memory_limit_bytes = 0;

if (preg_match('/^(\d+)([KMG])$/', $memory_limit, $matches)) {
    $memory_limit_bytes = $matches[1];
    switch ($matches[2]) {
        case 'G': $memory_limit_bytes *= 1024;
        case 'M': $memory_limit_bytes *= 1024;
        case 'K': $memory_limit_bytes *= 1024;
    }
}

$memory_used = memory_get_usage(true);
$memory_percent = $memory_limit_bytes > 0 ? round(($memory_used / $memory_limit_bytes) * 100, 2) : 0;

$memory_status = 'ok';
if ($memory_limit_bytes < 64 * 1024 * 1024) {
    $memory_status = 'warning';
}

add_check('memory_configuration', $memory_status, "Memory limit: $memory_limit", [
    'limit' => $memory_limit,
    'limit_bytes' => $memory_limit_bytes,
    'used_bytes' => $memory_used,
    'used_mb' => round($memory_used / 1024 / 1024, 2),
    'percent_used' => $memory_percent
]);

// 9. Get WordPress version
if (file_exists(__DIR__ . '/wp-includes/version.php')) {
    $version_content = file_get_contents(__DIR__ . '/wp-includes/version.php');
    if (preg_match('/\$wp_version = \'([^\']+)\'/i', $version_content, $matches)) {
        add_check('wordpress_version', 'ok', 'WordPress ' . $matches[1], $matches[1]);
    }
}

// Determine overall status
if (!empty($results['errors'])) {
    $results['status'] = 'error';
} elseif (!empty($results['warnings'])) {
    $results['status'] = 'warning';
} else {
    $results['status'] = 'ok';
}

// Output results
if ($format === 'json') {
    header('Content-Type: application/json');
    echo json_encode($results, JSON_PRETTY_PRINT);
} else {
    // HTML output
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>WordPress Health Check</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                margin: 0;
                padding: 20px;
                background: #f5f5f5;
            }
            .container {
                max-width: 1200px;
                margin: 0 auto;
                background: white;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                padding: 30px;
            }
            h1 {
                color: #23282d;
                border-bottom: 3px solid #0073aa;
                padding-bottom: 15px;
                margin-top: 0;
            }
            .status-badge {
                display: inline-block;
                padding: 8px 16px;
                border-radius: 4px;
                font-weight: bold;
                font-size: 14px;
                text-transform: uppercase;
                margin-bottom: 20px;
            }
            .status-ok { background: #46b450; color: white; }
            .status-warning { background: #ffb900; color: #23282d; }
            .status-error { background: #dc3232; color: white; }
            .check-item {
                margin: 15px 0;
                padding: 15px;
                border-left: 4px solid #ccc;
                background: #fafafa;
                border-radius: 4px;
            }
            .check-item.ok { border-left-color: #46b450; }
            .check-item.warning { border-left-color: #ffb900; }
            .check-item.error { border-left-color: #dc3232; }
            .check-title {
                font-weight: bold;
                font-size: 16px;
                margin-bottom: 5px;
            }
            .check-message {
                color: #555;
                margin-bottom: 8px;
            }
            .check-details {
                background: white;
                padding: 10px;
                border-radius: 4px;
                font-family: monospace;
                font-size: 12px;
                margin-top: 8px;
                overflow-x: auto;
            }
            .summary {
                background: #e5f5fa;
                padding: 20px;
                border-radius: 4px;
                margin: 20px 0;
            }
            .summary h2 {
                margin-top: 0;
                color: #0073aa;
            }
            .icon { margin-right: 8px; }
            .timestamp {
                color: #666;
                font-size: 14px;
                margin-bottom: 20px;
            }
            .actions {
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #ddd;
            }
            .btn {
                display: inline-block;
                padding: 10px 20px;
                background: #0073aa;
                color: white;
                text-decoration: none;
                border-radius: 4px;
                margin-right: 10px;
                margin-bottom: 10px;
            }
            .btn:hover { background: #005a87; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🏥 WordPress Health Check</h1>
            
            <div class="timestamp">
                <strong>Checked at:</strong> <?php echo $results['timestamp']; ?>
            </div>
            
            <div class="status-badge status-<?php echo $results['status']; ?>">
                <?php 
                if ($results['status'] === 'ok') echo '✓ ALL SYSTEMS OK';
                elseif ($results['status'] === 'warning') echo '⚠ WARNINGS DETECTED';
                else echo '✗ ERRORS DETECTED';
                ?>
            </div>
            
            <?php if (!empty($results['errors']) || !empty($results['warnings'])): ?>
            <div class="summary">
                <h2>Summary</h2>
                <?php if (!empty($results['errors'])): ?>
                    <p><strong style="color: #dc3232;">❌ <?php echo count($results['errors']); ?> Error(s):</strong></p>
                    <ul>
                        <?php foreach ($results['errors'] as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                
                <?php if (!empty($results['warnings'])): ?>
                    <p><strong style="color: #ffb900;">⚠️ <?php echo count($results['warnings']); ?> Warning(s):</strong></p>
                    <ul>
                        <?php foreach ($results['warnings'] as $warning): ?>
                            <li><?php echo htmlspecialchars($warning); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <h2>Detailed Checks</h2>
            
            <?php foreach ($results['checks'] as $check_name => $check): ?>
                <div class="check-item <?php echo $check['status']; ?>">
                    <div class="check-title">
                        <?php 
                        if ($check['status'] === 'ok') echo '<span class="icon">✓</span>';
                        elseif ($check['status'] === 'warning') echo '<span class="icon">⚠</span>';
                        else echo '<span class="icon">✗</span>';
                        
                        echo htmlspecialchars(ucwords(str_replace('_', ' ', $check_name)));
                        ?>
                    </div>
                    <?php if ($check['message']): ?>
                        <div class="check-message">
                            <?php echo htmlspecialchars($check['message']); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($check['details']): ?>
                        <div class="check-details">
                            <?php echo htmlspecialchars(json_encode($check['details'], JSON_PRETTY_PRINT)); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <div class="actions">
                <h2>Troubleshooting Actions</h2>
                <a href="/health-check.php?format=json" class="btn" target="_blank">View JSON Output</a>
                <a href="/phpinfo.php" class="btn" target="_blank">View PHP Info</a>
                <a href="/" class="btn">Go to WordPress</a>
            </div>
            
            <?php if ($results['status'] === 'error'): ?>
            <div class="summary" style="background: #fce4e4; margin-top: 20px;">
                <h2 style="color: #dc3232;">🔧 Next Steps</h2>
                <ol>
                    <li>Review the errors listed above</li>
                    <li>Check Runtime Logs in DigitalOcean dashboard for more details</li>
                    <li>Verify all environment variables are set in App Settings</li>
                    <li>Ensure database is accessible and credentials are correct</li>
                    <li>Confirm all WordPress files were downloaded during build</li>
                </ol>
            </div>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
}
?>
