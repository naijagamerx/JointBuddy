# CannaBuddy Agent Documentation

**Complete Guide for AI Agents Working on CannaBuddy E-commerce Platform**

---

## 📋 Table of Contents

1. [Project Overview](#project-overview)
2. [Architecture & System Design](#architecture--system-design)
3. [Database Schema](#database-schema)
4. [Authentication & Security](#authentication--security)
5. [Build & Test Commands](#build--test-commands)
6. [Testing Instructions](#testing-instructions)
7. [Code Style Guidelines](#code-style-guidelines)
8. [URL Helper System](#url-helper-system)
9. [Development Workflow](#development-workflow)
10. [Security Considerations](#security-considerations)
11. [Common Tasks](#common-tasks)
12. [Deployment Guide](#deployment-guide)
13. [Troubleshooting](#troubleshooting)

---

## 📖 Project Overview

### Business Context
- **Name**: CannaBuddy (also called JointBuddy)
- **Business**: E-commerce for premium cannabis accessories
- **Tagline**: "Pocket-sized peace of mind. Made in Mzansi."
- **Secret**: Products are 3D-printed (PLA+PETG) - **NEVER mention to customers**

### Technical Stack
- **Backend**: Native PHP 8.3 (NO frameworks)
- **Database**: MySQL 5.7+
- **Routing**: Custom file-based routing (`route.php`)
- **Frontend**: Tailwind CSS 2.2.19 (CDN) + Alpine.js
- **Hosting**: Shared hosting (Hostinger) - No build tools
- **Payments**: PayFast integration

### System Status
- **Primary System**: Standalone PHP (root directory) - ✅ PRODUCTION
- **Deprecated**: CodeIgniter 4 (`app/` directory) - archived to `_archive/`

---

## 🏗️ Architecture & System Design

### Request Flow
```
User Request → index.php → route.php → Admin auth check → Route file → Render with header/footer
```

### Core Files

| File | Purpose | Lines |
|------|---------|-------|
| `index.php` | Main entry point, handles POST requests, renders pages | ~1600 |
| `route.php` | File-based routing, parses REQUEST_URI | ~50 |
| `includes/database.php` | Database class (PDO), AdminAuth, UserAuth classes | ~260 |
| `includes/url_helper.php` | **CRITICAL** - URL generation for all deployments | ~170 |
| `includes/header.php` | Common header with navigation | - |
| `includes/footer.php` | Common footer with scripts | - |

### Directory Structure
```
CannaBuddy.shop/
├── index.php                    # Entry point
├── route.php                    # Routing logic
├── router.php                   # PHP built-in server router
├── includes/                    # Core files
│   ├── database.php            # PDO + Auth classes
│   ├── url_helper.php          # URL generation
│   ├── header.php              # Site header
│   ├── footer.php              # Site footer
│   └── ...
├── admin/                       # Admin panel (20+ pages)
│   ├── login/
│   ├── products/
│   ├── orders/
│   ├── users/
│   ├── slider/
│   ├── settings/
│   └── analytics/
├── user/                        # User system (25+ subdirs)
│   ├── login/
│   ├── dashboard/              # 5 versions (cleanup needed)
│   ├── orders/
│   ├── profile/
│   ├── address-book/
│   └── ...
├── shop/                        # Product listings
├── product/                     # Individual products
├── assets/                      # Static files
│   ├── css/
│   ├── js/
│   └── images/
└── test_delete/                 # ALL test files MUST go here

```

### Important Notes
- **NO .htaccess required** - Uses file-based routing
- **NO framework dependencies** - Pure PHP
- **CDN only** - No build tools or bundlers
- **Test files MUST go to test_delete/** - Never in root

---

## 🗄️ Database Schema

### Database Configuration
```php
// Connection details (includes/database.php)
Host: localhost
Database: cannabuddy
Username: root
Password: root (MAMP default)
Charset: utf8mb4
```

### Core Tables

#### 1. **admin_users**
```sql
- id (INT, PK, AUTO_INCREMENT)
- username (VARCHAR)
- email (VARCHAR)
- password (VARCHAR) - bcrypt hashed
- role (VARCHAR)
- is_active (TINYINT)
- login_attempts (INT)
- locked_until (DATETIME)
- last_login (DATETIME)
- created_at (TIMESTAMP)
```

#### 2. **users** (Customers)
```sql
- id (INT, PK, AUTO_INCREMENT)
- email (VARCHAR)
- password (VARCHAR) - bcrypt hashed
- first_name (VARCHAR)
- last_name (VARCHAR)
- phone (VARCHAR)
- is_active (TINYINT)
- last_login (DATETIME)
- created_at (TIMESTAMP)
```

#### 3. **products**
```sql
- id (INT, PK, AUTO_INCREMENT)
- name (VARCHAR)
- slug (VARCHAR) - URL-friendly
- description (TEXT)
- price (DECIMAL)
- color (VARCHAR)
- image (VARCHAR)
- stock_quantity (INT)
- featured (TINYINT)
- category_id (INT)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

#### 4. **orders**
```sql
- id (INT, PK, AUTO_INCREMENT)
- user_id (INT)
- total_amount (DECIMAL)
- status (VARCHAR)
- payment_status (VARCHAR)
- payment_method (VARCHAR)
- shipping_address (TEXT)
- created_at (TIMESTAMP)
```

#### 5. **order_items**
```sql
- id (INT, PK, AUTO_INCREMENT)
- order_id (INT)
- product_id (INT)
- quantity (INT)
- price (DECIMAL)
```

#### 6. **categories**
```sql
- id (INT, PK, AUTO_INCREMENT)
- name (VARCHAR)
- slug (VARCHAR)
- description (TEXT)
```

#### 7. **homepage_slider**
```sql
- id (INT, PK, AUTO_INCREMENT)
- image (VARCHAR)
- title (VARCHAR)
- subtitle (VARCHAR)
- button_text (VARCHAR)
- button_link (VARCHAR)
- display_order (INT)
- is_active (TINYINT)
```

### Database Setup Commands

```bash
# Setup database and tables
php setup_database.php

# Or use test file
php test_delete/setup_database.php

# View database structure
php test_delete/DATABASE_ANALYSIS_REPORT.md
```

---

## 🔐 Authentication & Security

### AdminAuth Class (includes/database.php)

**Features:**
- Session-based authentication
- Bcrypt password hashing
- Login attempt limiting (locks after 5 attempts for 30 minutes)
- IP address logging
- Account locking mechanism
- Login history tracking

**Key Methods:**
```php
$adminAuth->login($username, $password, $ip_address);
$adminAuth->logout();
$adminAuth->isLoggedIn();
$adminAuth->getCurrentAdmin();
```

**Session Variables:**
```php
$_SESSION['admin_id']
$_SESSION['admin_username']
$_SESSION['admin_role']
$_SESSION['admin_logged_in']
```

### UserAuth Class (includes/database.php)

**Features:**
- Customer registration and login
- Session management
- Email uniqueness validation
- Bcrypt password hashing

**Key Methods:**
```php
$userAuth->register($data);
$userAuth->login($email, $password);
$userAuth->logout();
$userAuth->isLoggedIn();
```

**Session Variables:**
```php
$_SESSION['user_id']
$_SESSION['user_email']
$_SESSION['user_name']
$_SESSION['user_logged_in']
```

### Security Implementation

✅ **SQL Injection Prevention**: PDO with prepared statements
✅ **Password Security**: Bcrypt hashing (PASSWORD_DEFAULT)
✅ **Session Security**: Proper session initialization/cleanup
✅ **CSRF Protection**: Session tokens for forms
✅ **XSS Prevention**: Input sanitization and output escaping
✅ **Login Attempt Limiting**: Account lockout after failed attempts
✅ **IP Logging**: All login attempts tracked

---

## 🛠️ Build & Test Commands

### PHP Syntax Checking
```bash
# Check main files
php -l index.php
php -l route.php
php -l includes/database.php

# Check admin files
php -l admin/login/index.php
php -l admin/products/index.php

# Check user files
php -l user/login/index.php
```

### Database Operations
```bash
# Setup database
php setup_database.php

# Or via test file
php test_delete/setup_database.php

# Test database connection
php test_delete/test_database.php

# Verify database structure
php test_delete/check_database.php
```

### System Testing
```bash
# Full system test
bash test_delete/test_system.sh

# Admin authentication test
php test_delete/test_admin_flow.php

# Database test
php test_delete/test_db_connection.php

# Route testing
php test_delete/test_routing.php

# URL helper test
php test_delete/url_debug.php
```

### MySQL Operations (via bash scripts)
```bash
# Basic MySQL operations
bash test_delete/mysql_operations.sh

# Advanced MySQL operations
bash test_delete/advanced_mysql_operations.sh
```

### Debugging
```bash
# Debug routing - add to any URL
?debug_routing=1

# Example:
http://localhost/CannaBuddy.shop/admin/?debug_routing=1
```

---

## 🧪 Testing Instructions

### Test File Organization
**CRITICAL**: ALL test files MUST be in `test_delete/` directory. Never leave test files in root or other directories.

### Available Test Files

#### Database Tests
- `test_delete/test_database.php` - Database connection test
- `test_delete/check_database.php` - Verify database structure
- `test_delete/test_db_connection.php` - Connection diagnostics
- `test_delete/setup_database.php` - Create and populate database

#### Authentication Tests
- `test_delete/test_admin_flow.php` - Complete admin login flow
- `test_delete/test_admin_system.php` - Admin system tests
- `test_delete/test_new_admin.php` - Admin creation tests
- `test_delete/test_admin_users.php` - Admin user management

#### Routing Tests
- `test_delete/test_routing.php` - Routing functionality
- `test_delete/route_checker.php` - Verify all routes
- `test_delete/debug_route.php` - Debug route parsing
- `test_delete/test_main_routing.php` - Main routing test

#### System Tests
- `test_delete/test_system.sh` - Full system verification
- `test_delete/system_diagnosis.php` - Complete system diagnosis
- `test_delete/final_verification.php` - Final system check

#### URL Helper Tests
- `test_delete/url_debug.php` - URL generation test
- `test_delete/quick_url_test.php` - Quick URL test
- `test_image_url.php` - Image URL test

### Test Execution Order

1. **Setup Database**
   ```bash
   php test_delete/setup_database.php
   ```

2. **Test Database Connection**
   ```bash
   php test_delete/test_database.php
   ```

3. **Test Admin Flow**
   ```bash
   php test_delete/test_admin_flow.php
   ```

4. **Run Full System Test**
   ```bash
   bash test_delete/test_system.sh
   ```

5. **Manual Testing**
   - Visit: http://localhost/CannaBuddy.shop/admin/login/
   - Login: admin / admin123
   - Verify dashboard loads

### Writing New Tests

**Template for test files:**
```php
<?php
/**
 * Test: [Test Name]
 * Description: [What this test does]
 */

echo "Testing: [Feature Name]\n";

// Include required files
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/url_helper.php';

try {
    // Test code here
    echo "✅ Test passed\n";
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
}
?>
```

**Remember:**
- Add "delete" comment in test file for cleanup reminder
- Move to test_delete/ when complete
- Update this documentation

---

## 📝 Code Style Guidelines

### PHP Standards

1. **File Encoding**: UTF-8
2. **Line Endings**: Unix (LF)
3. **Indentation**: 4 spaces (no tabs)
4. **Class Naming**: PascalCase (e.g., `AdminAuth`)
5. **Method Naming**: camelCase (e.g., `getCurrentAdmin()`)
6. **Variable Naming**: snake_case (e.g., `$user_id`)
7. **Constants**: UPPER_CASE (e.g., `BASE_PATH`)

### Code Formatting

```php
<?php
// File header comment
/**
 * File Description
 * Author: CannaBuddy Team
 */

// Use strict typing when possible
declare(strict_types=1);

// Class definition
class ExampleClass {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function exampleMethod($param): bool {
        if ($param === null) {
            return false;
        }
        return true;
    }
}
?>
```

### SQL Standards

```sql
-- Use UPPERCASE for SQL keywords
SELECT id, name, email
FROM users
WHERE is_active = 1
ORDER BY created_at DESC;

-- Always use prepared statements in PHP
$stmt = $this->db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
```

### HTML/JavaScript Standards

```php
// Use url() helper for ALL URLs
<a href="<?php echo url('/admin/products/'); ?>">Products</a>

// Don't hardcode URLs
❌ <a href="/CannaBuddy.shop/admin/">
✅ <a href="<?php echo adminUrl(''); ?>">

// Alpine.js for interactivity
<div x-data="{ open: false }">
    <button @click="open = !open">Toggle</button>
    <div x-show="open">Content</div>
</div>
```

### CSS Standards

```php
// Use Tailwind CSS classes (no custom CSS files)
<div class="bg-green-500 text-white p-4 rounded-lg">

// Inline styles only for dynamic values
<div style="background-color: <?php echo $color; ?>">

// Admin panel uses glassmorphism
<div class="backdrop-blur-lg bg-white/30">
```

---

## 🔗 URL Helper System

**CRITICAL**: Always use URL helper functions. Never hardcode URLs.

### Available Helper Functions

| Function | Purpose | Example |
|----------|---------|---------|
| `url($path)` | Full URL | `url('/admin/')` → `http://localhost/CannaBuddy.shop/admin/` |
| `rurl($path)` | Relative URL | `rurl('/admin/')` → `/CannaBuddy.shop/admin/` |
| `adminUrl($path)` | Admin section | `adminUrl('products/')` → `http://localhost/CannaBuddy.shop/admin/products/` |
| `userUrl($path)` | User section | `userUrl('dashboard/')` → `http://localhost/CannaBuddy.shop/user/dashboard/` |
| `shopUrl($path)` | Shop section | `shopUrl('/')` → `http://localhost/CannaBuddy.shop/shop/` |
| `productUrl($slug)` | Product page | `productUrl('purple-case')` → `http://localhost/CannaBuddy.shop/product/purple-case/` |
| `assetUrl($path)` | Asset files | `assetUrl('css/style.css')` → `http://localhost/CannaBuddy.shop/assets/css/style.css` |
| `redirect($path)` | Redirect | `redirect('/admin/')` |

### How to Use URL Helpers

**Step 1: Include the helper**
```php
<?php
require_once __DIR__ . '/../includes/url_helper.php';
?>
```

**Step 2: Use in links**
```php
<a href="<?php echo adminUrl('products/'); ?>" class="btn">
    Manage Products
</a>

<form action="<?php echo url('/cart/add/'); ?>" method="POST">
    <input type="text" name="product_id" value="123">
    <button type="submit">Add to Cart</button>
</form>
```

**Step 3: Use in JavaScript**
```php
<script>
function addToCart(productId) {
    fetch('<?php echo url("/cart/add/"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'product_id=' + productId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Added to cart!');
        }
    });
}
</script>
```

### Why URL Helpers?

✅ **Auto-detects base path** - Works on localhost, subdomains, different hosts
✅ **No hardcoding** - Portable across environments
✅ **Consistent** - All URLs generated the same way
✅ **Deployment-safe** - Works on shared hosting without config changes

### Examples of What NOT to Do

```php
❌ BAD - Hardcoded URLs
<a href="/CannaBuddy.shop/admin/">Admin</a>
<a href="http://localhost:8080/admin/">Admin</a>

✅ GOOD - Using helpers
<a href="<?php echo adminUrl(''); ?>">Admin</a>
```

---

## 🔄 Development Workflow

### Before Starting Work

1. **Analyze the codebase**
   ```bash
   # Use mgrep for code search
   mgrep "AdminAuth"

   # Use auggie-mcp for semantic analysis
   auggie-mcp analyze the authentication system
   ```

2. **Create implementation plan**
   - Create `.claude/tasks/TASK_NAME.md`
   - Include detailed steps
   - Review and approve plan

3. **Check existing tests**
   ```bash
   php test_delete/test_system.sh
   ```

### During Development

1. **Follow URL helper rules**
   - Always use url(), adminUrl(), userUrl()
   - Never hardcode URLs

2. **Keep tests organized**
   - All tests in test_delete/
   - Mark with "delete" comment for cleanup

3. **Update task file**
   - Document progress
   - Add completed steps

### After Completion

1. **Run tests**
   ```bash
   bash test_delete/test_system.sh
   php test_delete/test_admin_flow.php
   ```

2. **Clean up**
   - Move tests to test_delete/
   - Remove unused files

3. **Update documentation**
   - Update agent.md
   - Update task file

### Adding New Routes

**Example: Add /contact/ route**

1. Create directory structure
   ```
   contact/
   └── index.php
   ```

2. Create index.php
   ```php
   <?php
   require_once __DIR__ . '/../includes/url_helper.php';
   require_once __DIR__ . '/../includes/header.php';
   ?>
   <div class="container mx-auto p-4">
       <h1>Contact Us</h1>
       <!-- Content -->
   </div>
   <?php
   require_once __DIR__ . '/../includes/footer.php';
   ?>
   ```

3. Add navigation (if public)
   - Update includes/header.php

4. Test with debug
   - Visit: http://localhost/CannaBuddy.shop/contact/?debug_routing=1

---

## 🔒 Security Considerations

### Critical Security Rules

1. **SQL Injection Prevention**
   ```php
   ✅ ALWAYS use prepared statements
   $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
   $stmt->execute([$userId]);

   ❌ NEVER concatenate user input into SQL
   $query = "SELECT * FROM users WHERE id = " . $userId; // BAD!
   ```

2. **Password Security**
   ```php
   ✅ ALWAYS use bcrypt
   $hash = password_hash($password, PASSWORD_DEFAULT);

   ✅ Verify with password_verify
   if (password_verify($input, $stored)) { ... }

   ❌ NEVER use md5, sha1, or plain text
   ```

3. **Session Security**
   ```php
   ✅ Always start session properly
   if (session_status() === PHP_SESSION_NONE) {
       session_start();
   }

   ✅ Clear sessions on logout
   session_destroy();
   unset($_SESSION['admin_id']);
   ```

4. **XSS Prevention**
   ```php
   ✅ Always escape output
   echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

   ✅ Use htmlspecialchars in templates
   <div><?php echo htmlspecialchars($data); ?></div>
   ```

5. **File Upload Security**
   ```php
   ✅ Validate file types
   $allowed = ['jpg', 'jpeg', 'png', 'webp'];
   $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
   if (!in_array($ext, $allowed)) { die('Invalid file type'); }

   ✅ Check file size
   if ($_FILES['image']['size'] > 5000000) { die('File too large'); }

   ✅ Sanitize filename
   $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
   ```

6. **CSRF Protection**
   ```php
   ✅ Generate CSRF token
   $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

   ✅ Validate token on POST
   if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
       die('CSRF token mismatch');
   }

   ✅ Include token in forms
   <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
   ```

### Admin Security Features

1. **Login Attempt Limiting**
   - Max 5 attempts
   - 30-minute lockout
   - IP address logging

2. **Account Locking**
   - Automatic lockout after failed attempts
   - Locked_until timestamp
   - Admin must unlock manually

3. **Login Logging**
   - All login attempts logged
   - IP address tracking
   - Success/failure reasons

### User Security Features

1. **Email Validation**
   - Unique email addresses
   - Format validation

2. **Password Requirements**
   - Minimum 8 characters (enforced in registration)
   - Bcrypt hashing

3. **Session Management**
   - Secure session configuration
   - Proper logout cleanup

---

## 📚 Common Tasks

### Adding a New Admin Page

1. Create directory: `admin/new-page/`
2. Create `admin/new-page/index.php`
3. Include auth check
4. Use adminUrl() for links

**Template:**
```php
<?php
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/url_helper.php';

$database = new Database();
$db = $database->getConnection();
$adminAuth = new AdminAuth($db);

// Check if admin is logged in
if (!$adminAuth->isLoggedIn()) {
    redirect(adminUrl('login/'));
}

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="container mx-auto p-4">
    <h1>New Page</h1>
    <!-- Content -->
</div>
<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
```

### Adding a New User Page

1. Create directory: `user/new-page/`
2. Create `user/new-page/index.php`
3. Include auth check
4. Use userUrl() for links

**Template:**
```php
<?php
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/url_helper.php';

$database = new Database();
$db = $database->getConnection();
$userAuth = new UserAuth($db);

// Check if user is logged in
if (!$userAuth->isLoggedIn()) {
    redirect(userUrl('login/'));
}

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="container mx-auto p-4">
    <h1>User Page</h1>
    <!-- Content -->
</div>
<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
```

### Adding a New Product

```php
<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/url_helper.php';

$database = new Database();
$db = $database->getConnection();
$adminAuth = new AdminAuth($db);

if (!$adminAuth->isLoggedIn()) {
    redirect(adminUrl('login/'));
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $price = $_POST['price'] ?? '';
    $description = $_POST['description'] ?? '';

    $stmt = $db->prepare("INSERT INTO products (name, price, description) VALUES (?, ?, ?)");
    if ($stmt->execute([$name, $price, $description])) {
        redirect(adminUrl('products/'));
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="container mx-auto p-4">
    <h1>Add Product</h1>
    <form method="POST">
        <input type="text" name="name" placeholder="Product Name" required>
        <input type="number" name="price" placeholder="Price" step="0.01" required>
        <textarea name="description" placeholder="Description"></textarea>
        <button type="submit" class="bg-green-500 text-white px-4 py-2">Add</button>
    </form>
</div>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>
```

### Database Query Examples

**Select all products:**
```php
$stmt = $db->prepare("SELECT * FROM products WHERE featured = 1 ORDER BY created_at DESC");
$stmt->execute();
$products = $stmt->fetchAll();
```

**Select single product:**
```php
$stmt = $db->prepare("SELECT * FROM products WHERE slug = ?");
$stmt->execute([$slug]);
$product = $stmt->fetch();
```

**Insert new record:**
```php
$stmt = $db->prepare("INSERT INTO users (email, password, first_name) VALUES (?, ?, ?)");
$result = $stmt->execute([$email, $hash, $firstName]);
```

**Update record:**
```php
$stmt = $db->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");
$stmt->execute([$newQuantity, $productId]);
```

**Delete record:**
```php
$stmt = $db->prepare("DELETE FROM products WHERE id = ?");
$stmt->execute([$productId]);
```

---

## 🚀 Deployment Guide

### Environment Requirements

- **PHP**: 8.3+ (tested on 8.3.1)
- **MySQL**: 5.7+ (tested on 5.7.24)
- **Web Server**: Apache/Nginx (shared hosting compatible)
- **Extensions**: PDO, PDO_MySQL

### Deployment Steps

1. **Upload Files**
   - Upload all files to web root
   - No special permissions needed
   - No .htaccess required

2. **Setup Database**
   ```bash
   php setup_database.php
   ```

3. **Configure Database (if needed)**
   - Edit includes/database.php
   - Or set environment variables:
     - CB_DB_HOST
     - CB_DB_NAME
     - CB_DB_USER
     - CB_DB_PASS

4. **Test Deployment**
   - Visit your domain
   - Test admin login: /admin/login/
   - Test user registration: /register/

### Environment-Specific URLs

The URL helper automatically handles:
- **localhost**: http://localhost/CannaBuddy.shop/
- **Subdomain**: http://cannakingdom.ky/
- **Subdirectory**: http://domain.com/subdir/

### Hostinger Shared Hosting

1. Upload files via File Manager or FTP
2. Create MySQL database
3. Import database (use phpMyAdmin)
4. Update database credentials if needed
5. Test all functionality

**No special configuration needed!**

---

## 🔧 Troubleshooting

### Database Connection Issues

**Problem**: "Connection refused"
```bash
# Test connection
php test_delete/test_database.php

# Check credentials in includes/database.php
# Verify MySQL is running (MAMP)
```

**Problem**: "Table doesn't exist"
```bash
# Run setup
php test_delete/setup_database.php

# Check table creation
php test_delete/check_database.php
```

### Routing Issues

**Problem**: 404 errors on routes
```bash
# Debug routing
# Add ?debug_routing=1 to any URL
# Example: http://localhost/CannaBuddy.shop/admin/?debug_routing=1
```

**Problem**: Wrong URLs generated
```php
// Check url_helper.php is included
require_once __DIR__ . '/../includes/url_helper.php';

// Test URL generation
php test_delete/url_debug.php
```

### Authentication Issues

**Problem**: Can't login as admin
```bash
# Check admin user exists
php test_delete/test_admin_flow.php

# Verify credentials
# Default: admin / admin123
```

**Problem**: Session not persisting
```php
// Check session_start() is called
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
```

### PHP Syntax Errors

**Problem**: Parse errors
```bash
# Check syntax
php -l index.php
php -l route.php
php -l includes/database.php

# Check specific file
php -l admin/products/index.php
```

### Test File Cleanup

**Problem**: Tests in wrong location
```bash
# Find all test files
find . -name "*test*.php" -not -path "./test_delete/*"

# Move to test_delete/
# All test files MUST be in test_delete/
```

---

## 📊 Testing Infrastructure

### Test Coverage

✅ **Database Tests**
- Connection testing
- Table structure validation
- Data integrity checks

✅ **Authentication Tests**
- Admin login flow
- User registration
- Session management
- Password verification

✅ **Routing Tests**
- URL parsing
- Route resolution
- 404 handling

✅ **Security Tests**
- SQL injection prevention
- XSS prevention
- CSRF token validation

### Running Complete Test Suite

```bash
# 1. Setup database
php test_delete/setup_database.php

# 2. Run all tests
bash test_delete/test_system.sh

# 3. Manual verification
# - Visit admin login
# - Visit user registration
# - Test product browsing
```

### Creating Custom Tests

**Test Template:**
```php
<?php
/**
 * Test: [Test Name]
 * Purpose: [What this validates]
 * Priority: [High/Medium/Low]
 */

// Include dependencies
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../../includes/url_helper.php';

echo "Running test: [Test Name]\n";

try {
    // Test implementation
    $result = true; // or actual test logic

    if ($result) {
        echo "✅ PASSED: [Test Name]\n";
        exit(0);
    } else {
        echo "❌ FAILED: [Test Name]\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

/*
TODO: Delete this test file when project is complete
*/
?>
```

---

## 📞 Support & Resources

### Documentation Files

- `CLAUDE.md` - Project instructions for Claude
- `README.md` - Project overview
- `agent.md` - This file
- `CLAUDE.md` - Development guidelines

### Test Reports

- `test_delete/DATABASE_ANALYSIS_REPORT.md` - Database documentation
- `test_delete/ADMIN_FIX_COMPLETE.md` - Admin system fixes
- `test_delete/ROUTING_FIX_COMPLETE.md` - Routing documentation

### Quick Reference

**Default Credentials:**
- Admin: admin / admin123
- Email: admin@cannabuddy.co.za

**Key URLs:**
- Homepage: /
- Admin: /admin/
- Shop: /shop/
- User Login: /user/login/

**Debug Mode:**
- Add `?debug_routing=1` to any URL

---

## 🎯 Best Practices Summary

### DO ✅

- Use URL helper functions for all links
- Put ALL tests in test_delete/
- Use prepared statements for all SQL
- Hash passwords with bcrypt
- Escape output with htmlspecialchars
- Follow PSR-12 coding standards
- Include auth checks on admin pages
- Use session_start() properly
- Document complex functions
- Test before deploying

### DON'T ❌

- Never hardcode URLs (like /CannaBuddy.shop/)
- Never leave test files in root directory
- Never use string concatenation in SQL
- Never store passwords in plain text
- Never trust user input
- Never skip auth checks on admin pages
- Never use md5/sha1 for passwords
- Never use eval() or exec()
- Never expose database credentials
- Never skip error handling

---

## 📝 Changelog

**2025-12-14** - Comprehensive agent.md update
- Added complete testing documentation
- Added security considerations
- Added code style guidelines
- Added URL helper documentation
- Added common tasks examples
- Added troubleshooting guide

**2025-12-11** - Initial agent.md creation
- Basic project overview
- Authentication documentation
- Database schema

---

**Last Updated**: December 14, 2025
**Version**: 2.0.0
**Maintained by**: CannaBuddy Development Team

---

## 🔗 Quick Links

- [Project README](README.md)
- [Development Brief](test_delete/cannabuddy_development_brief.md)
- [Database Analysis](test_delete/DATABASE_ANALYSIS_REPORT.md)
- [System Tests](test_delete/test_system.sh)
- [Claude Instructions](CLAUDE.md)
