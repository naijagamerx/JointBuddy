# Production Deployment Plan - CannaBuddy.shop

## Overview
Create a clean production folder containing only the necessary files and folders for deployment, excluding all test, development, and backup files.

## Files and Folders to INCLUDE in Production

### Root Level Files
- `index.php` - Main application entry point
- `config.php` - Global configuration (DEBUG_MODE already set to false)
- `production.htaccess` → rename to `.htaccess` (production Apache configuration)
- `composer.json` - Composer dependencies configuration
- `composer.lock` - Locked dependency versions
- `sitemap.xml` - SEO sitemap

### Core Directories (Include All Contents)

1. **`about/`** - About page
2. **`admin/`** - Admin panel with all subdirectories
3. **`assets/`** - All static assets (images, currency icons)
4. **`cart/`** - Shopping cart functionality
5. **`checkout/`** - Checkout process
6. **`contact/`** - Contact page
7. **`includes/`** - All core PHP classes, services, middleware
8. **`logs/`** - Create empty directory for error logs
9. **`migrations/`** - Database migration scripts
10. **`newsletter/`** - Newsletter subscription
11. **`product/`** - Product detail pages
12. **`register/`** - User registration
13. **`shop/`** - Shop listing page
14. **`templates/`** - All page templates
15. **`thank-you/`** - Order confirmation page
16. **`user/`** - User dashboard and all subdirectories
17. **`vendor/`** - Composer dependencies

## Files and Folders to EXCLUDE (Development/Test Files)

### Development/IDE Files
- `.agent/`
- `.claude/`
- `.playwright-mcp/`
- `.serena/`
- `.trae/`
- `.mcp.json`
- `.agent_memory.json`
- `.phpunit.result.cache`

### Test Files
- `test_delete/` - Entire directory
- `tests/` - PHPUnit test files

### Documentation Files
- `PRD/` - Project documentation
- `uiux design reference/` - Design reference
- All `*.md` files at root (CHANGELOG.md, CLAUDE.md, etc.)

### Debug/Utility Files
- `debug_id.php`
- `debug_images.php`
- `fix_images.php`
- `test_bmw_debug.php`
- `test_logout_route.php`
- `installer.php`
- `migrate.php`
- `seed_templates.php`
- `sync_production.php`
- `phpunit.phar`
- `phpunit.xml`
- `composer.phar`

### Backup Files
- `22production.7z`
- `index.php.backup`
- `index.php.virus_backup`
- `admin/index.php.backup_broken`
- `admin/products/index.php.virus_backup`
- `includes/admin_routes.php.backup`
- `product/index.php.virus_backup`

### Extra Files
- `nul` (empty file)
- `admin_sidebar_components.php` (duplicate)
- `.htaccess` (use production.htaccess instead)

## Production Configuration Notes

### Already Configured:
- `config.php` line 9: `define('DEBUG_MODE', false);` ✓

### Actions Required:
1. Use `production.htaccess` as `.htaccess` (not the development version)
2. Create empty `logs/` directory with proper permissions (755)

## Execution Steps

1. Create `production/` directory
2. Copy all listed files and directories
3. Exclude all test, development, and backup files
4. Rename `production.htaccess` → `.htaccess`
5. Create empty `logs/` directory
6. Verify no test files are included

## Estimated File Count
- Include: ~200+ files across 17 main directories
- Exclude: ~100+ test/development files