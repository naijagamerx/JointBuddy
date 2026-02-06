# CannaBuddy E-Commerce Platform

**Version**: 3.0 (Standalone PHP System)
**Status**: Active Development

## 📖 Project Overview
CannaBuddy (branded as **JointBuddy**) is a premium e-commerce platform for cannabis accessories, specifically protective cases. The project is built on a **Single Standalone PHP Architecture**.

> **CRITICAL BUSINESS RULE**: Products are 3D-printed, but this must **NEVER** be mentioned to customers. The website positions them as premium physical accessories.

## 🛠️ System Architecture
The project originally had a dual-system (PHP + CodeIgniter), but has been consolidated to a single system:

- **Core**: Native PHP 8.3 with PDO
- **Routing**: File-based router (`route.php`) + `index.php` entry point
- **Database**: MySQL 5.7+ (Tables: `products`, `orders`, `users`)
- **Frontend**: Tailwind CSS (CDN) + Alpine.js
- **Hosting**: Designed for Shared Hosting (Hostinger) - No build tools required.

## 📂 Directory Structure
- **`admin/`**: Admin panel (Products, Orders, Users, Dashboard)
- **`user/`**: Customer dashboard and account management
- **`shop/`**: Public product catalog
- **`includes/`**: Core classes (`database.php` with `AdminAuth`/`UserAuth`)
- **`_archive/`**: Deprecated code (including the old CodeIgniter system)

## 🚀 Getting Started

### 1. Database Setup
The system uses a MySQL database named `cannabuddy`.
- Import the schema from `setup_phase1.php` or check `check_schema.php`.
- Ensure `products` table has `cost` and `dimensions` columns (migration script `add_missing_columns.php` handles this).

### 2. Configuration
- Database credentials are in `includes/database.php`.
- Default Admin: `admin@cannabuddy.co.za` / `admin123`

### 3. Running Locally
Use MAMP or any PHP server:
```bash
php -S localhost:8080
```
Access at `http://localhost:8080/CannaBuddy.shop/`

## 🔐 Key Features
- **Authentication**: Custom Session-based Auth (Admin & User).
- **Admin Panel**: Full CRUD for Products (with Image Upload), Order Management, Analytics.
- **User Dashboard**: Sidebar navigation, Order History, Profile Management.
- **Routing**: Custom `route.php` handles clean URLs.

## ⚠️ Important Notes for Developers
- **Do NOT** use the `app/` directory (it is archived).
- **Do NOT** use `spark` or Composer dependencies from the old system.
- **Always** use `PDO` for database interactions.
- **Style** with Tailwind CSS utility classes directly in HTML.
