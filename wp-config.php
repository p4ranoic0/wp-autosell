<?php
/**
 * The base configuration for WordPress
 *
 * This file is configured to read credentials from environment variables
 * for secure deployment on DigitalOcean App Platform or similar PaaS.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 * @package WordPress
 */

// ** Database settings - Read from environment variables ** //
/** The name of the database for WordPress */
define( 'DB_NAME', getenv('DB_NAME') ?: 'wordpress' );

/** Database username */
define( 'DB_USER', getenv('DB_USER') ?: 'root' );

/** Database password */
define( 'DB_PASSWORD', getenv('DB_PASSWORD') ?: '' );

/** Database hostname - supports host:port format */
define( 'DB_HOST', getenv('DB_HOST') ?: '127.0.0.1' );

/**
 * IMPORTANT: In production, you MUST set all DB_* environment variables.
 * The default values above are only for local development.
 */

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/** MySQL SSL/TLS Configuration for DigitalOcean Managed Database */
$db_ssl = getenv('DB_SSL');
if ( $db_ssl && ( strtolower( trim( $db_ssl ) ) === 'true' || strtoupper( trim( $db_ssl ) ) === 'REQUIRED' ) ) {
	define( 'MYSQL_CLIENT_FLAGS', MYSQLI_CLIENT_SSL );
}

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * CRITICAL: In production, you MUST set unique values for all 8 keys via environment variables.
 * Using the default placeholders compromises security and makes your site vulnerable to attacks.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         getenv('AUTH_KEY') ?: 'put your unique phrase here' );
define( 'SECURE_AUTH_KEY',  getenv('SECURE_AUTH_KEY') ?: 'put your unique phrase here' );
define( 'LOGGED_IN_KEY',    getenv('LOGGED_IN_KEY') ?: 'put your unique phrase here' );
define( 'NONCE_KEY',        getenv('NONCE_KEY') ?: 'put your unique phrase here' );
define( 'AUTH_SALT',        getenv('AUTH_SALT') ?: 'put your unique phrase here' );
define( 'SECURE_AUTH_SALT', getenv('SECURE_AUTH_SALT') ?: 'put your unique phrase here' );
define( 'LOGGED_IN_SALT',   getenv('LOGGED_IN_SALT') ?: 'put your unique phrase here' );
define( 'NONCE_SALT',       getenv('NONCE_SALT') ?: 'put your unique phrase here' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = getenv('DB_PREFIX') ?: 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', (getenv('WP_DEBUG') === 'true') );

/* Add any custom values between this line and the "stop editing" line. */

/**
 * HTTPS handling for App Platform / reverse proxy
 * This ensures WordPress properly handles HTTPS when behind a proxy
 */
if ( ! empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ) {
	$_SERVER['HTTPS'] = 'on';
}

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
