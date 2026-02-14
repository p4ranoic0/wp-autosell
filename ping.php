<?php
/**
 * Simple health ping - NO database connection
 * Use this to verify PHP is working independently of the database.
 * Access at: /ping.php
 */
header('Content-Type: application/json');
echo json_encode([
    'status' => 'ok',
    'message' => 'PHP is running',
    'timestamp' => date('c'),
    'php_version' => PHP_VERSION,
    'memory_limit' => ini_get('memory_limit'),
    'memory_used_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
    'env_check' => [
        'DB_HOST' => getenv('DB_HOST') !== false && getenv('DB_HOST') !== '' ? 'SET' : 'NOT SET',
        'DB_NAME' => getenv('DB_NAME') !== false && getenv('DB_NAME') !== '' ? 'SET' : 'NOT SET',
        'DB_USER' => getenv('DB_USER') !== false && getenv('DB_USER') !== '' ? 'SET' : 'NOT SET',
        'DB_PASSWORD' => getenv('DB_PASSWORD') !== false && getenv('DB_PASSWORD') !== '' ? 'SET' : 'NOT SET',
        'AUTH_KEY' => getenv('AUTH_KEY') !== false && getenv('AUTH_KEY') !== '' ? 'SET' : 'NOT SET',
    ],
    'extensions' => [
        'mysqli' => extension_loaded('mysqli') ? 'loaded' : 'missing',
        'curl' => extension_loaded('curl') ? 'loaded' : 'missing',
        'mbstring' => extension_loaded('mbstring') ? 'loaded' : 'missing',
    ],
    'server' => [
        'hostname' => gethostname(),
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'N/A',
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
    ]
], JSON_PRETTY_PRINT);
