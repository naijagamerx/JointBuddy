# CannaBuddy Project Status

**Date**: 2026-01-31
**Current Phase**: Production Ready - Security Fixes & Image Path Corrections Applied
**Version**: 1.0.0-dev
**Architecture**: Standalone PHP E-Commerce Platform (NO Frameworks)

---

## 📊 Project Overview

**CannaBuddy** is a fully-featured standalone PHP e-commerce platform for cannabis accessories. Built without any frameworks, it features a custom routing system, MySQL-based cart, comprehensive admin panel, and Takealot-style user dashboard.

### Technical Stack
| Component | Technology |
|-----------|------------|
| Backend | Native PHP 8.3.1 (no frameworks) |
| Database | MySQL 5.7.24 |
| Frontend | Tailwind CSS 2.2.19 (CDN) + Alpine.js (CDN) |
| Icons | Font Awesome (CDN) |
| Routing | Custom file-based routing system |
| Authentication | Session-based with bcrypt |
| Development | MAMP (localhost/CannaBuddy.shop) |
| Production | Hostinger shared hosting |

---

## ✅ Recently Completed (Updated 2026-01-16)

### Critical Security Fixes (January 2026)
- **Review System Security** (2026-01-16): Complete security overhaul
  - Added CSRF token validation to review submission
  - Implemented rate limiting (1 review per product per 24 hours)
  - Masked user email addresses in admin panel (GDPR/POPIA compliance)
  - Added CSRF protection to admin review moderation
  - Implemented user review deletion with CSRF protection
  - Fixed redirect() helper usage across user review pages
- **DEBUG_MODE Configuration**: Added `config.php` with production-safe defaults (`DEBUG_MODE = false`)
- **Image Path Fixes**: Fixed product image loading in production by implementing `url()` helper conversion
  - `shop/index.php` - Product grid images
  - `includes/product_helpers.php` - `getProductMainImage()` and `getProductImages()` functions
  - `index.php` - Home page product sections
- **Margin Fixes**: Added `max-w-7xl` (1280px) to all main containers for consistent site-wide margins
  - Home page, Shop, Cart, Checkout, Product pages
  - Header and Footer
- **Admin Sidebar Scrolling**: Fixed sidebar navigation scrolling issue with hidden scrollbar
- **Installer Tool**: Created `installer.php` for easy database configuration and file cleanup

### Previous Major Achievements (2025)
- **System Consolidation**: Removed CodeIgniter 4; 100% Standalone PHP
- **Login Standardization**: User `user/login/index.php`, Admin `admin/login/index.php`
- **Dashboard Redesign**: Complete Takealot-style design with grid menu system
- **Product Edit Page**: Fully functional image preview and management system
- **Product Reviews System**: Complete with backend, frontend, and admin moderation
- **URL Helper Refactoring**: 28% complete (128 of 459 URLs)

---

## 🏗️ Architecture Overview

### Directory Structure
```
CannaBuddy.shop/
├── index.php              # Main entry point (~1755 lines)
├── route.php              # File-based routing logic
├── config.php             # DEBUG_MODE configuration
├── installer.php          # Installation/config tool
├── includes/              # Core files
│   ├── database.php       # PDO + AdminAuth + UserAuth + OrderManager
│   ├── url_helper.php     # URL generation (CRITICAL - use always!)
│   ├── product_helpers.php # Product helper functions
│   ├── header.php         # Common header
│   ├── footer.php         # Common footer
│   ├── admin_routes.php   # Admin routing
│   ├── order_service.php  # Order management
│   ├── email_service.php  # Email functionality
│   └── commerce/          # Currency service, etc.
├── admin/                 # Admin panel (20+ sections)
├── user/                  # Customer area (25+ sections)
├── shop/                  # Product listings
├── product/               # Individual product pages
├── cart/                  # Shopping cart (MySQL-based)
├── checkout/              # Checkout process
├── assets/                # Static files
├── test_delete/           # ALL test files (CRITICAL RULE)
├── migrations/            # Database migrations
├── templates/             # Reusable templates
└── logs/                  # Application logs
```

### Request Flow
```
User Request → index.php → route.php → Admin auth check → Route file → Render with header/footer
```

---

## 🗄️ Database Schema

### Core Tables
| Table | Purpose |
|-------|---------|
| `admin_users` | Admin accounts with login tracking |
| `users` | Customer accounts with security logs |
| `password_reset_tokens` | Password reset functionality |
| `user_security_logs` | User security event tracking |
| `admin_login_logs` | Admin login history |
| `products` | Product catalog (with `images` field) |
| `categories` | Product categories |
| `product_reviews` | Customer product reviews |
| `orders` | Order records with JSON addresses |
| `order_items` | Order line items |
| `order_status_history` | Status change tracking |
| `order_notes` | Admin order notes |
| `wishlists` | User wishlist functionality |
| `homepage_slider` | Hero slider images |
| `homepage_hero_sections` | Configurable hero sections |
| `settings` | System-wide settings |
| `delivery_methods` | Shipping options |
| `payment_methods` | Payment options |
| `coupons` | Discount coupons |
| `vouchers` | Voucher codes |
| `reward_points` | Customer reward points |
| `reward_points_transactions` | Points transaction history |
| `email_log` | Email tracking |
| `newsletter_subscribers` | Newsletter subscriptions |

---

## 🔐 Authentication & Security

### AdminAuth Class Features
- Session-based authentication
- Bcrypt password hashing
- Login attempt limiting (5 attempts = 30 min lockout)
- IP address logging
- Account locking mechanism
- Session regeneration (prevents session fixation)
- Login history tracking

### UserAuth Class Features
- Customer registration and login
- Email validation
- Password reset tokens
- Temporary password generation
- Security event logging
- Account locking (5 attempts, 30 min lockout)

### Security Features
✅ SQL Injection Prevention - PDO prepared statements
✅ Password Security - Bcrypt hashing
✅ Session Security - Proper session management
✅ CSRF Protection - Session tokens
✅ XSS Prevention - htmlspecialchars escaping
✅ Login Attempt Limiting - Account lockout
✅ IP Logging - All login attempts tracked

---

## 🎨 Implemented Features

### Frontend Pages
✅ Homepage with slider, categories, featured products
✅ Shop page with product listings, filtering, sorting
✅ Product detail pages with reviews, image gallery
✅ Shopping cart (MySQL-based, NO localStorage)
✅ Checkout process
✅ User registration/login
✅ User dashboard (Takealot-style grid menu)
✅ Order history and tracking
✅ Product reviews system
✅ Wishlist functionality
✅ Newsletter subscription
✅ Contact page
✅ Legal pages (Terms, Privacy, Refund Policy)

### Admin Panel
✅ Admin login with security features
✅ Dashboard with stats
✅ Product management (CRUD + image management)
✅ Order management (view, update status, add notes)
✅ Customer management
✅ Category management
✅ Homepage slider management
✅ Hero section management (2 configurable sections)
✅ Coupon/voucher system
✅ Payment methods configuration
✅ Delivery methods configuration
✅ QR code generation for products
✅ Returns management
✅ SEO tools
✅ System settings
✅ Analytics
✅ Newsletter management
✅ Manual order creation

### Additional Features
✅ Multi-currency support (CurrencyService)
✅ Reward points system
✅ Order status history tracking
✅ Email service (SMTP support)
✅ Invoice generation with dynamic company info
✅ Address book management
✅ Product variations support
✅ Stock management
✅ Product inquiry system
✅ Cache clearing tools

---

## 🔧 URL Routing System

### URL Helper Functions (CRITICAL - Always Use!)

| Function | Purpose | Example |
|----------|---------|---------|
| `url($path)` | Full URL | `url('/admin/')` → full URL |
| `rurl($path)` | Relative URL | `rurl('/admin/')` → relative path |
| `adminUrl($path)` | Admin section | Auto-detects base path |
| `userUrl($path)` | User section | Auto-detects base path |
| `shopUrl($path)` | Shop section | Auto-detects base path |
| `productUrl($slug)` | Product pages | Auto-detects base path |
| `assetUrl($path)` | Asset files | Auto-detects base path |

### Routing
- File-based routing system (`route.php`)
- NO .htaccess required
- Works with any deployment (localhost, subdirectory, root)
- Debug mode: `?debug_routing=1`

---

## 🚧 Known Issues & Technical Debt

### High Priority
- [ ] Complete PayFast payment gateway integration
- [x] ~~Fix review system security vulnerabilities~~ (COMPLETED 2026-01-16)
- [ ] Complete URL refactoring (72% remaining - 331 of 459 URLs)

### Medium Priority
- [ ] Code review and optimization
- [ ] Unit test coverage (currently 0%)
- [ ] Consistent error handling across pages
- [ ] Performance profiling

### Low Priority
- [ ] Documentation updates
- [ ] Legacy code cleanup

---

## 📋 Critical Rules for Developers

1. **NEVER hardcode URLs** - Use `url()`, `adminUrl()`, `userUrl()`, `productUrl()`, `assetUrl()`
2. **ALL test files MUST go to test_delete/** - Never in root folder
3. **NO localStorage** - Server-side sessions only
4. **NO client-side cart** - MySQL-based cart only
5. **NO build tools** - CDN only for frontend
6. **NO "3D printing" mentions** - Business secret
7. **ALWAYS use prepared statements** - SQL injection prevention
8. **ALWAYS use htmlspecialchars** - XSS prevention
9. **Set DEBUG_MODE = false** - In production
10. **Use bcrypt for passwords** - Never md5/sha1

---

## 🚀 Next Steps / Roadmap

### Immediate (Before Production)
1. Deploy to Hostinger
2. Set production database credentials via `installer.php`
3. Delete development files using `installer.php`
4. Verify all functionality on production
5. Delete `installer.php` when done

### High Priority
- Complete PayFast payment gateway integration
- Fix review system security
- Complete URL refactoring

### Medium Priority
- Enhance hero section options (animations, scheduling)
- Advanced product search and filtering
- Order management improvements
- Customer dashboard enhancements
- SEO optimization tools

---

## 📊 Progress Summary

| Metric | Status |
|--------|--------|
| **Core Features** | ✅ 95% Complete |
| **Admin Panel** | ✅ 100% Complete |
| **User Dashboard** | ✅ 100% Complete |
| **Security** | ✅ 100% Complete |
| **URL Refactoring** | 🔄 28% Complete (128/459) |
| **Payment Gateway** | ❌ Not Started (Stub only) |
| **Production Ready** | ✅ Yes (with manual orders) |

---

## 📝 Deployment Checklist

### Pre-Deployment
- [ ] Set `DEBUG_MODE = false` in `config.php` ✅
- [ ] Test all pages locally
- [ ] Backup database
- [ ] Prepare production database

### Post-Deployment
- [ ] Update database credentials via `installer.php`
- [ ] Delete development files via `installer.php`
- [ ] Test all functionality
- [ ] Test payment flow
- [ ] Delete `installer.php`

---

**Last Updated**: January 16, 2026
**Status**: Ready for Production Deployment
**Next Milestone**: PayFast Payment Gateway Integration
