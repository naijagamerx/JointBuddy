# CannaBuddy.shop - Codebase Map

## Overview
**Project**: CannaBuddy.shop E-commerce Platform  
**Type**: Standalone PHP E-commerce System (No Framework)  
**Architecture**: Custom routing, file-based system  

## Technology Stack
- **Backend**: PHP 8.3.1
- **Database**: MySQL 5.7.24
- **Frontend**: Tailwind CSS 2.2.19 (CDN), Alpine.js (CDN)
- **No Build Tools**: Direct deployment to shared hosting

## Core Architecture

### Request Flow
```
User Request → index.php → route.php → Admin auth check → Route file → Render with header/footer
```

### Key Files
| File | Purpose |
|------|---------|
| `index.php` | Main entry point, handles POST requests, renders pages |
| `route.php` | File-based routing, parses REQUEST_URI |
| `includes/database.php` | Database class (PDO), AdminAuth, UserAuth classes |
| `includes/url_helper.php` | URL generation for all deployments |

## Directory Structure

### Core Directories
- **`includes/`** - Core functionality (database, auth, helpers, commerce, seo)
- **`admin/`** - Admin panel (dashboard, products, orders, users, slider, etc.)
- **`user/`** - User accounts (login, register, dashboard, profile, orders, etc.)
- **`shop/`** - Product listings and shop pages
- **`assets/`** - Static assets (images, icons)
- **`templates/`** - Reusable HTML templates
- **`cart/`** - Shopping cart functionality
- **`checkout/`** - Checkout process

### Other Directories
- **`about/`, `contact/`** - Public pages
- **`register/`, `thank-you/`** - User flow pages
- **`product/`** - Product detail pages
- **`migrations/`** - Database migrations
- **`logs/`** - Application logs
- **`PRD/`** - Product Requirements Documentation

## Authentication System
- **AdminAuth**: Session-based admin authentication with bcrypt, attempt limiting, IP logging
- **UserAuth**: Customer registration/login with session management

## URL Helper System
**CRITICAL**: Always use helper functions for URLs
```php
url('/path/')        // Full URL
rurl('/path/')       // Relative URL
adminUrl('path/')    // Admin URLs
userUrl('path/')     // User URLs
productUrl($slug)    // Product pages
assetUrl('file.js')  // Asset files
```

## Database
- **Connection**: mysql:host=localhost;dbname=cannabuddy;charset=utf8mb4
- **Tables**: products, orders, order_items, users, admin_users, categories, homepage_slider

## Development Notes
- **No localStorage**: Server-side sessions only
- **No client-side cart**: MySQL-based cart only
- **No hardcoded URLs**: Use url() helper functions
- **Test files**: All moved to `test_delete/` folder

## Stats
- Total directories: 69
- PHP files: 98
- Files in test_delete: 124

## Important Files
- `index.php` (77KB) - Main application file
- `route.php` - Routing logic
- `admin_sidebar_components.php` - Admin interface components
- `composer.json` - PHP dependencies
- `.htaccess` - Apache configuration

Generated: 2025-12-12
