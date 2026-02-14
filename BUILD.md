# Build Process Documentation

## Overview

This repository uses a custom build process to install WordPress during deployment. This approach keeps the repository lightweight by not committing WordPress core directories.

## How It Works

### 1. Repository Structure

The repository contains:
- WordPress root PHP files (`index.php`, `wp-config.php`, `wp-settings.php`, etc.)
- Custom configuration (`wp-config.php` with environment variable support)
- Build script (`build.sh`)
- DigitalOcean App Platform configuration (`.do/app.yaml`)

**What's NOT in the repository:**
- `wp-includes/` - WordPress core includes
- `wp-admin/` - WordPress admin panel
- `wp-content/` - WordPress content directory (themes, plugins, uploads)

### 2. Build Process

When deployed to DigitalOcean App Platform:

1. **DigitalOcean reads `.do/app.yaml`** which specifies:
   - Build command: `bash build.sh`
   - Environment variables needed
   - PHP environment configuration
   - PHP version and required extensions

2. **PHP extensions are configured** via multiple files for compatibility:
   - `composer.json` - Declares required PHP extensions for buildpack
   - `.user.ini` - PHP configuration loaded at runtime
   - `php.ini` - Additional PHP settings
   - `.do/app.yaml` - Environment variables for PHP_VERSION and PHP_EXTENSIONS

3. **Build script (`build.sh`) runs** and:
   - Verifies PHP version and installed extensions
   - Downloads latest WordPress from wordpress.org
   - Extracts WordPress to a temporary location
   - Copies `wp-includes/`, `wp-admin/`, and `wp-content/` to the app directory
   - Keeps the custom `wp-config.php` from the repository
   - Cleans up temporary files

4. **Application starts** with:
   - WordPress core directories in place
   - Custom configuration from repository
   - Environment variables from App Platform
   - All required PHP extensions loaded

### 3. PHP Requirements

**Required PHP Version**: 8.1+

**Required PHP Extensions**:
- `mbstring` - Multi-byte string functions (required by WordPress core)
- `mysqli` - MySQL database connectivity
- `curl` - HTTP requests
- `gd` - Image processing
- `xml` - XML parsing
- `zip` - Archive handling
- `openssl` - Secure connections

These extensions are automatically configured via `composer.json` and loaded by the PHP buildpack.

### 4. Environment Variables

The following environment variables MUST be configured in DigitalOcean App Platform:

**Required (Database)**:
- `DB_NAME` - Database name
- `DB_USER` - Database user
- `DB_PASSWORD` - Database password (encrypted)
- `DB_HOST` - Database host with port (e.g., `host:25060`)
- `DB_PREFIX` - Table prefix (default: `wp_`)
- `DB_SSL` - Set to `REQUIRED` or `true` for DigitalOcean Managed MySQL
- `WP_DEBUG` - Debug mode (true/false)

**Required (Security)**:
- `AUTH_KEY`, `SECURE_AUTH_KEY`, `LOGGED_IN_KEY`, `NONCE_KEY`
- `AUTH_SALT`, `SECURE_AUTH_SALT`, `LOGGED_IN_SALT`, `NONCE_SALT`

Generate security keys at: https://api.wordpress.org/secret-key/1.1/salt/

## Troubleshooting

### Build Fails

If the build script fails:
1. **Check the Build Logs in DigitalOcean App Platform**
   - Go to your app → **Activity** → Click on the latest deployment → **Build Logs**
   - Look for detailed error messages from the build script
2. Verify internet connectivity is available during build
3. Check if wordpress.org is accessible

**New in v2.0**: The build script now provides comprehensive logging:
- ✓ Download progress and method used (curl/wget)
- ✓ File size verification
- ✓ Extraction verification
- ✓ Post-installation verification with WordPress version
- ✗ Clear error messages when something fails

### Missing Directories Error

If you see: `Failed to open stream: No such file or directory in /workspace/wp-includes/version.php`

**Causes**:
- Build script didn't run or failed
- Environment variables not configured (especially DB_NAME, DB_USER, DB_PASSWORD, DB_HOST, DB_SSL)
- Deploy failed

**Solutions**:
1. **Check Build Logs** - The enhanced build script now shows exactly where it fails:
   - Download failure? Check internet connectivity
   - Extraction failure? Check if download was complete
   - Verification failure? See which files are missing
2. Verify `.do/app.yaml` exists and specifies `build_command: bash build.sh`
3. Verify `build.sh` is executable (`chmod +x build.sh`)
4. **Verify all environment variables are set** in App Platform (Settings → Environment Variables)
5. Force rebuild: Actions → **Force Rebuild and Deploy**

**Important**: The DB_SSL variable is now required for DigitalOcean Managed MySQL. Set it to `REQUIRED` in your environment variables.

## Local Development

To set up WordPress locally for development:

```bash
# Run the build script
bash build.sh

# Copy .env.example to .env and configure
cp .env.example .env
# Edit .env with your local database credentials

# Start PHP development server
php -S localhost:8000
```

## Adding Custom Themes and Plugins

The build script intelligently merges custom content with WordPress defaults:

### Adding a Custom Theme

1. Create your theme directory in `wp-content/themes/your-theme/`
2. Commit it to the repository
3. On deployment:
   - Build script downloads WordPress
   - Copies default themes to `wp-content/themes/`
   - Your custom theme remains intact and is preserved

### Adding a Custom Plugin

1. Add your plugin to `wp-content/plugins/your-plugin/`
2. Commit it to the repository
3. On deployment:
   - Build script downloads WordPress
   - Copies default plugins to `wp-content/plugins/`
   - Your custom plugin is preserved

### What Gets Ignored

The `.gitignore` is configured to ignore:
- WordPress core directories (`wp-includes/`, `wp-admin/`)
- Default WordPress themes (twenty* themes)
- Default plugins (Akismet, Hello Dolly)
- User uploads and dynamic content

But allows:
- Custom themes (anything not matching `twenty*`)
- Custom plugins (anything not default)

## Why This Approach?

**Benefits**:
1. **Lightweight repository** - No need to commit 50MB+ of WordPress core files
2. **Always up-to-date** - Gets latest WordPress on each deploy
3. **Clean version control** - Only custom code is tracked
4. **Easy updates** - Redeploy to get WordPress updates

**Trade-offs**:
1. Requires internet access during build
2. Slightly longer initial deploy time
3. Need to ensure build script executes successfully

## Security Considerations

- All sensitive data (passwords, keys) stored as environment variables
- Environment variables encrypted in App Platform
- WordPress core downloaded from official wordpress.org
- No credentials committed to repository
