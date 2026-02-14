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

2. **Build script (`build.sh`) runs** and:
   - Downloads latest WordPress from wordpress.org
   - Extracts WordPress to a temporary location
   - Copies `wp-includes/`, `wp-admin/`, and `wp-content/` to the app directory
   - Keeps the custom `wp-config.php` from the repository
   - Cleans up temporary files

3. **Application starts** with:
   - WordPress core directories in place
   - Custom configuration from repository
   - Environment variables from App Platform

### 3. Environment Variables

The following environment variables MUST be configured in DigitalOcean App Platform:

**Required (Database)**:
- `DB_NAME` - Database name
- `DB_USER` - Database user
- `DB_PASSWORD` - Database password (encrypted)
- `DB_HOST` - Database host with port (e.g., `host:3306`)
- `DB_PREFIX` - Table prefix (default: `wp_`)
- `WP_DEBUG` - Debug mode (true/false)

**Required (Security)**:
- `AUTH_KEY`, `SECURE_AUTH_KEY`, `LOGGED_IN_KEY`, `NONCE_KEY`
- `AUTH_SALT`, `SECURE_AUTH_SALT`, `LOGGED_IN_SALT`, `NONCE_SALT`

Generate security keys at: https://api.wordpress.org/secret-key/1.1/salt/

## Troubleshooting

### Build Fails

If the build script fails:
1. Check the Build Logs in DigitalOcean App Platform
2. Verify internet connectivity is available during build
3. Check if wordpress.org is accessible

### Missing Directories Error

If you see: `Failed to open stream: No such file or directory in /workspace/wp-includes/version.php`

**Causes**:
- Build script didn't run or failed
- Environment variables not configured
- Deploy failed

**Solutions**:
1. Check Build Logs for errors
2. Verify `.do/app.yaml` exists and specifies `build_command: bash build.sh`
3. Verify `build.sh` is executable (`chmod +x build.sh`)
4. Force rebuild: Actions → Force Rebuild and Deploy

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
