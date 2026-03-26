# JointBuddy E-Commerce Platform

**Version**: 3.2 (Standalone PHP System)
**Status**: Production Ready
**GitHub**: https://github.com/naijagamerx/JointBuddy

## 📖 Project Overview

JointBuddy (formerly CannaBuddy) is a premium e-commerce platform for smoking accessories. Built on a **Single Standalone PHP Architecture** with no framework dependencies.

> **Business Rule**: Products are premium accessories. No mention of manufacturing methods.

## 🛠️ Tech Stack

- **Backend**: PHP 8.3+
- **Database**: MySQL 5.7+
- **Frontend**: Tailwind CSS 2.2 (CDN), Alpine.js
- **Architecture**: Custom MVC with file-based routing
- **Hosting**: Shared Hosting compatible (Hostinger, etc.)

## 🌍 Supported Countries

- 🇿🇦 South Africa (9 Provinces)
- 🇳🇬 Nigeria (37 States)
- 🇺🇸 United States (50 States)
- 🇬🇧 United Kingdom (4 Nations)

## 📂 Directory Structure

```
/
├── index.php              # Main entry point
├── route.php              # File-based routing system
├── includes/              # Core files
│   ├── database.php       # Database & Auth classes
│   ├── url_helper.php     # URL generation
│   ├── email_service.php  # Email notifications
│   ├── location_data.php  # Country/Province/City data
│   └── middleware/        # Auth, CSRF middleware
├── admin/                 # Admin panel
│   ├── dashboard/
│   ├── products/
│   ├── orders/
│   └── users/
├── user/                  # User accounts
│   ├── login/
│   ├── register/
│   └── dashboard/
├── checkout/              # Checkout flow
├── shop/                  # Product listings
└── assets/                # CSS, JS, images
    └── js/
        └── checkout-location.js  # Location dropdowns
```

## 🚀 Installation

### 1. Clone the Repository
```bash
git clone https://github.com/naijagamerx/JointBuddy.git
cd JointBuddy
```

### 2. Database Setup
Create a MySQL database and import the schema:
```bash
php setup_database.php
```

Or manually configure in `config.php`:
```php
<?php
$db_host = 'localhost';
$db_name = 'jointbuddy';
$db_user = 'root';
$db_pass = 'your_password';
```

### 3. Local Development (MAMP)
1. Place project in `C:\MAMP\htdocs\JointBuddy`
2. Visit `http://localhost/JointBuddy/`

### 4. Production Deployment
1. Upload files via FTP to `public_html`
2. Create `config.php` with production credentials
3. Set file permissions (755 for dirs, 644 for files)

## 🔐 Default Admin Access

- **URL**: `/admin/login/`
- **Email**: `admin@cannabuddy.co.za`
- **Password**: `admin123` (change immediately)

## ✨ Key Features

### Core Functionality
- ✅ **Product Management**: Full CRUD with variations, images, categories
- ✅ **Order Management**: Order tracking, status updates, invoices
- ✅ **User Accounts**: Registration, address book, order history
- ✅ **Admin Panel**: Dashboard, analytics, content management
- ✅ **Payment Methods**: Bank transfer, crypto, cash on delivery, PayFast
- ✅ **Email Notifications**: Order confirmations, status updates
- ✅ **Location-Based Checkout**: Country → Province/State → City dropdowns

### Security Features
- 🔒 CSRF token protection on all forms
- 🔒 Session fingerprinting
- 🔒 Password hashing (bcrypt)
- 🔒 SQL injection prevention (PDO)
- 🔒 XSS protection
- 🔒 Rate limiting on login

## 🔄 Recent Updates

### v1.2.0 (2026-03-26)
- ✅ Fixed order placement error (qty/quantity key mismatch)
- ✅ Added location dropdowns (4 countries, provinces/states, cities)
- ✅ Improved error handling with Throwable catching
- ✅ Added missing getTemplate() method to email service

## 🧪 Testing

```bash
# Syntax check
php -l index.php

# Test database connection
php test_delete/test_database.php

# Test location data
php test_delete/test_location_dropdowns.php
```

## 📤 FTP Upload Commands

```bash
# Upload single file
curl -T "file.php" -u "user:pass" \
  --ftp-create-dirs \
  ftp://server.com/path/to/file.php

# Verify upload
curl -I ftp://server.com/path/to/file.php -u "user:pass"
```

## 🛠️ Development Guidelines

### Code Style
- Use PDO for all database operations
- Follow PSR-4 autoloading conventions
- Use `url()` helper for all URLs (never hardcode paths)
- Style with Tailwind CSS utility classes

### URL Helper System
```php
url('/path/')        // Full URL
adminUrl('path/')    // Admin URLs
userUrl('path/')     // User URLs
assetUrl('file.js')  // Asset files
```

### Adding New Routes
1. Create directory matching URL path (e.g., `/contact/` → `contact/index.php`)
2. Use `url()` helper for all links
3. Add to navigation if needed

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 License

Proprietary software. All rights reserved.

## 🆘 Support

For issues and questions, please open a GitHub issue.

---

**Built with ❤️ for the South African & Nigerian markets**
