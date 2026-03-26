# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## MANDATORY SKILL DISCOVERY PROTOCOL

**CRITICAL**: Before ANY task, you MUST check for relevant skills. This is not optional.

### Mandatory Checklist (Every Response):
1. List available skills mentally
2. Ask: "Does ANY skill match this request?"
3. If yes → Use the Skill tool to read and run the skill
4. Announce which skill you're using
5. Follow the skill exactly

**1% chance = 100% mandatory usage.**

### Available Skills:
- **Skill-Power** (MANDATORY workflow - always check first)
- **php-guardian** (MANDATORY for ANY PHP file edit - prevents HTTP 500 errors)
- **CodebaseErrorDetector** (PHP error analysis with whoops/phpunit/phinx)
- **ProjectMemoryManager** (Logs tasks to project_memory.md)
- **CodebaseContextMapper** (Generates codebase_map.md for context)
- **Plugin_Maker** (Plugin development)
- **Skill_Knowledge** (Skill management best practices)
- **Codebase-Cleaner** (Moves test/debug files to test_delete)
- **Mysql** (MySQL commands for MAMP Windows)
- **StitchUI** (UI generation via Google Stitch MCP)
- **Speckitty** (Software specifications generation)

### Skill Triggers:
- **PHP file edit** → php-guardian (MANDATORY)
- **Database** → Mysql skill
- **Errors/Fix** → CodebaseErrorDetector
- **Test/Debug files** → Codebase-Cleaner
- **Project memory** → ProjectMemoryManager
- **Codebase mapping** → CodebaseContextMapper
- **Plugin creation** → Plugin_Maker

# Plan and Review
Before you begin, write detailed implementation plan in a file name .claude/tasks/TASK_NAME.MD

This plan should include:

A clear, detailed breakdown of the implementation steps

The reasoning behind your approach.

A list of specific tasks.

Once Plan is ready , please carefully review it , do not proceed with implementation until you have approved the plan is good and perfect to fit into the current codebase  

This Md Task file you have created will always provides guidance to you TRAE when working with code in the codebase

## While Implementing  

As you work, keep the plan updated {task md file} . after you complete a task, append a detailed descriptions of the changes you've made to the plan. this ensures that the progress and next steps are clear and can be easily handed over to other engineers if needed

always verify and clarify your information or code  and make sure its meet the project requirements, and also follow the project rules , 
verify your implementation follows all project standards and architectural principles 

-very important all test files must go to test_delete folder , no test must be on the root folder , all test, debug , task md files , check files , test files , debug files , fix files all must go to test_delete , the root folder cannot contain test files 

when you write a test comment "delete" so you remembver to delete the test md file ,

to analyze database ALWAYS use direct SQL queries via MySQL command line - it's safer and more reliable:

```bash
C:\MAMP\bin\mysql\bin\mysql -u root -proot cannabuddy -e "SHOW TABLES;"
C:\MAMP\bin\mysql\bin\mysql -u root -proot cannabuddy -e "DESCRIBE products;"
C:\MAMP\bin\mysql\bin\mysql -u root -proot cannabuddy -e "SELECT id, email, role FROM users;"
```

dont hardcode CannaBuddy.shop into any link , this is prohbited dont ever add CannaBuddy.shop to the link we use relative or url helper or maybe base url no hardcode 

## Initial Analysis

Before beginning work, use multi-agent to analyze the codebase. Create implementation plans in `.claude/tasks/TASK_NAME.md`.

**Parallel Tool Calls**: ALWAYS use parallel tool calls when possible. If you need to call multiple tools with no dependencies between them, make all independent calls in a single message. This maximizes speed and efficiency. Only call tools sequentially when one call's result is needed as input for another.

## Common Development Commands

```bash
# PHP Syntax Checking
php -l index.php
php -l route.php
php -l includes/database.php

# Database Setup
php setup_database.php

# Testing
php test_delete/test_database.php      # Database connection
php test_delete/test_admin_flow.php    # Admin authentication
bash test_delete/test_system.sh        # Full system test

# Debug Routing
# Add ?debug_routing=1 to any URL
```

## Architecture Overview

**CannaBuddy** is a standalone PHP e-commerce system with NO framework dependencies. Custom routing, no .htaccess required.

### Request Flow
```
User Request → index.php → route.php → Admin auth check → Route file → Render with header/footer
```

### Core Files

| File | Purpose |
|------|---------|
| `index.php` | Main entry point, handles POST requests, renders pages |
| `route.php` | File-based routing, parses REQUEST_URI |
| `includes/database.php` | Database class (PDO), AdminAuth, UserAuth classes |
| `includes/url_helper.php` | **CRITICAL** - URL generation for all deployments |

### URL Helper System

**CRITICAL**: Always use helper functions for URLs - auto-detects base path for any deployment.

```php
url('/path/')        // Full URL: http://localhost:8080/CannaBuddy.shop/path/
rurl('/path/')       // Relative: /CannaBuddy.shop/path/
adminUrl('path/')    // Admin URLs
userUrl('path/')     // User URLs
productUrl($slug)    // Product pages
assetUrl('file.js')  // Asset files
```

**NEVER** hardcode URLs like `/CannaBuddy.shop/` or `/admin/`.

### Directory Structure

```
/                     # Web root
├── index.php         # Entry point
├── route.php         # Routing
├── includes/         # Core: database.php, url_helper.php, header.php, footer.php
├── admin/            # Admin panel: dashboard, products, orders, users, slider
├── user/             # User accounts: login, register, dashboard, profile
├── shop/             # Product listings
└── assets/           # images, css, js
```

### Database

- **Connection**: `mysql:host=localhost;dbname=cannabuddy;charset=utf8mb4`
- **Credentials**: root/root (MAMP), admin@cannabuddy.co.za/admin123 (admin panel)
- **Tables**: products, orders, order_items, users, admin_users, categories, homepage_slider

### Authentication

- **AdminAuth**: Session-based, bcrypt, attempt limiting, IP logging, account locking
- **UserAuth**: Customer registration/login, session management

### Frontend

- Tailwind CSS 2.2.19 (CDN)
- Alpine.js (CDN)
- No build tools (Hostinger shared hosting)

## Critical Restrictions

1. **NEVER mention "3D printing"** - Business secret
2. **NO localStorage** - Server-side sessions only
3. **NO client-side cart** - MySQL-based cart only
4. **NO hardcoded URLs** - Use url() helper functions
5. **NO build tools** - CDN only

## Adding Routes

1. Create directory matching URL path (e.g., `/contact/` → `contact/index.php`)
2. Use `url()` helper for all links
3. Add to `includes/header.php` if public
4. Test with `?debug_routing=1`

## Key Documentation

- `agent.md` - Detailed technical implementation
- `cannabuddy_development_brief.md` - Original requirements
- `.claude/tasks/` - Implementation plans and progress

## Environment

- PHP 8.3.1, MySQL 5.7.24
- MAMP (dev) → Hostinger (prod)
- test is done direct with http://localhost/CannaBuddy.shop

Please dont write Cannabuddy in any of the page , yes the project name is cannabuddy but thatdoesnt mean its should be on the page because right now we think of changing the name so we need to start makiing it easy ,  no Cannabuddy in any new page or code

---

## FTP Deployment (Production Uploads)

### FTP Credentials
```
FTP IP (hostname):    ftp://141.136.39.117
FTP username:         u397530364.jointbuddy.co.za
FTP port:             21
FTP password:         (stored separately - not in version control)
```

### IMPORTANT: Path Structure

**The FTP server's root directory is `/public_html`**

When uploading files, the path works like this:
- **Local file**: `C:\MAMP\htdocs\CannaBuddy.shop\admin\settings\index.php`
- **FTP path**: `ftp://141.136.39.117/admin/settings/index.php` (NOT `/public_html/admin/...`)

**DO NOT** include `/public_html/` in the FTP URL - the server starts there already!

### Upload Methods

#### Method 1: Using curl (Recommended for Automation)

```bash
# Upload a single file
curl -T "local/path/file.php" -u "username:password" \
  --ftp-create-dirs \
  ftp://141.136.39.117/remote/path/file.php

# Upload multiple files
curl -T "test_delete/file1.php" -u "username:password" ftp://141.136.39.117/test_delete/file1.php
curl -T "test_delete/file2.php" -u "username:password" ftp://141.136.39.117/test_delete/file2.php
curl -T "admin/settings/index.php" -u "username:password" --ftp-create-dirs ftp://141.136.39.117/admin/settings/index.php
```

#### Method 2: Verify Upload

```bash
# Check if file exists on server
curl -I ftp://141.136.39.117/path/to/file.php -u "username:password" 2>&1 | grep "Content-Length"
```

#### Method 3: Windows FTP Client (Interactive)

```bash
# Create a script file (e.g., ftp_commands.txt):
u397530364.jointbuddy.co.za
[password]
pwd
cd admin
cd settings
lcd C:\MAMP\htdocs\CannaBuddy.shop\admin\settings
put index.php
quit

# Run it:
ftp -s:ftp_commands.txt 141.136.39.117
```

### Common Upload Paths

| Local Path | FTP Upload Path |
|------------|-----------------|
| `admin/settings/index.php` | `ftp://.../admin/settings/index.php` |
| `test_delete/debug_script.php` | `ftp://.../test_delete/debug_script.php` |
| `includes/database.php` | `ftp://.../includes/database.php` |
| `assets/images/logo.png` | `ftp://.../assets/images/logo.png` |

### Production Debugging Tools

When debugging production issues, these tools are available:

1. **Setup Error Logging**: Upload and run `test_delete/setup_error_logging.php`
2. **Run Diagnostics**: Upload and run `test_delete/diagnose_production.php`
3. **View Error Logs**: Check `/logs/php_errors.log` on server

### After Upload Checklist

1. **Verify files exist** using curl -I or Hostinger File Manager
2. **Check file permissions** (755 for directories, 644 for files)
3. **Test the page** in browser immediately
4. **Run php-guardian skill** to check for syntax errors before uploading

### IMPORTANT NOTES

- **Always** test locally before uploading
- **Never** commit FTP credentials to version control
- **Always** use `--ftp-create-dirs` flag when creating new directories
- **Delete** debug/diagnostic scripts after fixing issues
- **Remember**: FTP root = `/public_html`, so paths are relative to that
