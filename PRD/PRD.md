# Product Requirements Document (PRD)
## CannaBuddy.shop E-Commerce Platform

**Version**: 3.0 (Comprehensive Update)
**Date**: 2025-12-08
**Status**: 🟡 STABLE - OPTIMIZATION PHASE
**Document Owner**: Development Team

---

## 📋 EXECUTIVE SUMMARY

### Project Overview
**CannaBuddy** (branded as "JointBuddy") is a premium e-commerce platform specializing in cannabis accessories, specifically the JointBuddy protective case for BIC lighters and joints. The business positions itself as eco-friendly, South African-made physical accessories.

### Critical Finding
**RESOLVED**: The CodeIgniter 4 system has been deprecated and archived. The project now runs on a **Single Standalone PHP System**.

### Business Model
- **Target Market**: Cannabis consumers in South Africa
- **Product Focus**: Premium protective cases and accessories
- **Key Selling Points**: Eco-friendly, locally-made, premium quality
- **Revenue Model**: Direct-to-consumer online sales

### Critical Business Rule
Products are 3D-printed using PLA+PETG materials, but **this must NEVER be mentioned to customers**. The website presents products as premium physical accessories only.

---

## 🎯 PROJECT OBJECTIVES

### Primary Goals
1. **Launch E-Commerce Platform**: Create a fully functional online store
2. **Product Showcase**: Display JointBuddy and related accessories
3. **Admin Management**: Comprehensive admin panel for content management
4. **User Experience**: Seamless shopping experience for customers
5. **Payment Integration**: Secure PayFast payment processing
6. **Mobile Responsive**: Optimized for all device sizes
7. **🔴 CRITICAL**: Consolidate dual-system architecture to single system

### Success Metrics
- [x] Homepage with product showcase
- [x] Admin panel with full CRUD operations
- [x] User authentication and account management
- [x] Product catalog with 12+ items
- [x] Shopping cart and checkout flow
- [🔴 CRITICAL] Order processing and fulfillment system
- [🔴 CRITICAL] Email notifications
- [🔴 CRITICAL] Inventory management
- [x] Single-system consolidation

---

## 👥 USER PERSONAS

### 1. Store Owner/Administrator
**Needs**: Manage products, orders, users, content, homepage slider
**Goals**: Efficiently run the online business
**Pain Points**: Manual inventory tracking, customer management
**Access**: `/admin/login/` - admin@cannabuddy.co.za / admin123

### 2. Customer (Cannabis Consumer)
**Needs**: Browse products, place orders, track purchases, manage account
**Goals**: Convenient, secure online shopping
**Pain Points**: Lack of quality storage solutions, discreet shopping
**Access**: `/user/login/` - Register or login with existing account

### 3. Visitor (Potential Customer)
**Needs**: Learn about products, view pricing, see featured items
**Goals**: Evaluate products before purchase
**Pain Points**: Unclear product benefits, shipping info
**Access**: Homepage, product pages, about page

---

## 📦 PRODUCT CATALOG

### Current Product Range (12 Items)

#### Featured Products (Recommended For You - 6 items)
1. **JointBuddy Protective Case** - R189.00
   - Primary product: Protective case for lighters and joints
   - Colors: Purple, Green, Black
   - Status: ✅ Active

2. **3D Print Vape Holder** - R159.00
   - Convenient vape storage solution

3. **Cannabis Grinder Dispenser** - R225.00
   - 2-in-1 grinder and storage

4. **Rolling Tray Organizer** - R139.00
   - Keep rolling supplies organized

5. **Stash Box Pro** - R299.00
   - Premium storage with compartments

6. **Hemp Wick Dispenser** - R89.00
   - Easy hemp wick storage and access

#### Sale Products (Deals For You - 6 items)
1. **Budget Rolling Kit** - ~~R199.00~~ → **R149.00** (25% off)
2. **Plastic Grinder (2pc)** - ~~R129.00~~ → **R99.00** (23% off)
3. **Basic Stash Tube** - ~~R89.00~~ → **R69.00** (22% off)
4. **Mini Torch Lighter** - ~~R179.00~~ → **R139.00** (22% off)
5. **Pre-Roll Storage Tubes (5pk)** - ~~R149.00~~ → **R119.00** (20% off)
6. **Smoking Tray Set** - ~~R249.00~~ → **R199.00** (20% off)

### Product Attributes
- **Images**: 3 images per product (image_1, image_2, image_3)
- **Stock Management**: Track inventory levels
- **Pricing**: Regular price + sale price (optional)
- **Flags**: Featured (for homepage), On Sale (for deals section)
- **Colors**: JSON array for color variants
- **Categories**: Support for future categorization

---

## 🏗️ TECHNICAL ARCHITECTURE

### Current State: Dual System Architecture ⚠️

#### System A: Standalone PHP (RECOMMENDED AS PRIMARY)
**Location**: Root directory
**Status**: Fully implemented, more complete feature set

**Components**:
- **Entry Point**: `index.php` (1032 lines)
- **Routing**: Custom file-based routing (`route.php`)
- **Database Layer**: PDO with prepared statements (`includes/database.php`)
- **Authentication**: Custom AdminAuth & UserAuth classes
- **Admin Panel**: 20+ pages with full CRUD
- **User System**: 25+ subdirectories with extensive features

**File Structure**:
```
admin/
├── analytics.php
├── delivery-methods/
├── login/ (Enhanced UI)
├── orders/ (Full CRUD)
├── payment-methods/
├── products/ (Full CRUD + variations)
├── seo/
├── settings/ (Appearance, currency, email, notifications)
├── slider/ (Homepage management)
└── users/ (User management)

user/
├── address-book/
├── dashboard/ (5 versions - needs cleanup)
├── login/ (4 versions - needs cleanup)
├── orders/ (Complete with tracking)
├── personal-details/
├── reviews/
└── [20+ other subdirectories]

includes/
├── database.php (PDO + Auth)
├── header.php
├── footer.php
└── [other components]
```

**Advantages**:
- ✅ More complete admin system (20+ pages vs 1 CI4 controller)
- ✅ More developed user system (25+ subdirectories)
- ✅ File-based routing works with shared hosting
- ✅ Documented as "primary" in CLAUDE.md
- ✅ Admin UI is more polished (glassmorphism effects)
- ✅ PDO is more modern than MySQLi

#### System B: CodeIgniter 4 (RECOMMENDED FOR DEPRECATION)
**Location**: `app/` directory
**Status**: Fully implemented but less complete

**Components**:
- **Framework**: CodeIgniter 4
- **Controllers**: Admin, User, Home, Shop, Product, Cart, Checkout, PayFast
- **Models**: ProductModel, OrderModel, OrderItemModel, UserModel
- **Database Layer**: MySQLi driver (`app/Config/Database.php`)
- **Authentication**: JWT tokens + CodeIgniter sessions

**File Structure**:
```
app/
├── Controllers/
│   ├── Admin.php
│   ├── User.php
│   ├── Home.php
│   ├── Shop.php
│   ├── Product.php
│   ├── Cart.php
│   ├── Checkout.php
│   └── PayFast.php
├── Models/
│   ├── ProductModel.php
│   ├── OrderModel.php
│   ├── OrderItemModel.php
│   └── UserModel.php
├── Views/
└── Config/
    ├── Database.php
    └── Routes.php
```

**Issues**:
- ❌ Less complete feature set
- ❌ MySQLi is older than PDO
- ❌ JWT auth adds complexity
- ❌ CI4 routing may not work with shared hosting

### Technology Stack
- **Backend**: PHP 8.3.1
- **Database**: MySQL 5.7.24
- **Frontend**: Tailwind CSS 2.2.19 (CDN) + Alpine.js (CDN)
- **Payment**: PayFast integration
- **Hosting**: MAMP (development) → Hostinger (production)
- **No Build Tools**: Direct deployment to shared hosting

---

## 📊 FEATURE SPECIFICATIONS

### Homepage
**Status**: ✅ COMPLETE

#### Features
- [x] 500px hero slider with 4 slides (managed via admin)
- [x] "Recommended For You" section (6 featured products)
- [x] "Deals For You" section (6 sale products with discounts)
- [x] 4-image banner section
- [x] Responsive design (mobile, tablet, desktop)
- [x] Admin slider management (`/admin/slider/`)

#### Admin Management
- **URL**: `/admin/slider/`
- **Features**: Add, edit, delete slides
- **Images**: Upload and manage slider images
- **Content**: Edit slide text and links

### Admin Panel
**Status**: ✅ 90% COMPLETE

#### Fully Implemented Features
- [x] **Analytics**: Dashboard statistics
- [x] **Delivery Methods**: Shipping options
- [x] **Login System**: Enhanced UI with glassmorphism
- [x] **Orders Management**: Full CRUD operations
- [x] **Payment Methods**: Payment configuration
- [x] **Products**: Full CRUD + variations
- [x] **SEO**: Search engine optimization settings
- [x] **Settings**:
  - [x] Appearance settings
  - [x] Currency settings
  - [x] Email settings
  - [x] Notification settings
- [x] **Slider Management**: Homepage content
- [x] **Users**: User management interface

#### Incomplete Features
- [❓] **Reviews**: Basic file exists (`admin/products/reviews.php`), needs enhancement

#### Admin Access
- **URL**: `/admin/login/`
- **Credentials**: admin@cannabuddy.co.za / admin123
- **Status**: ✅ Functional

### User System
**Status**: ✅ 80% COMPLETE

#### Fully Implemented Features
- [x] **Login/Registration**: User authentication
- [x] **Dashboard**: User account overview (5 versions - needs consolidation)
- [x] **Orders**: Complete with tracking timeline
- [x] **Profile Management**: User profile editing

#### Incomplete Features
- [❓] **Address Book**: Status Unknown/Incomplete
- [❓] **Personal Details**: Status Unknown/Incomplete
- [❓] **My Reviews**: Status Unknown/Incomplete

#### User Access
- **URL**: `/user/login/`
- **Status**: ✅ Functional (multiple versions exist)

### E-Commerce Features
**Status**: ✅ 85% COMPLETE

#### Implemented
- [x] **Product Catalog**: Database-driven product listing
- [x] **Product Filtering**: Filter by category, price, etc.
- [x] **Product Search**: Search products by name/description
- [x] **Shopping Cart**: Server-side cart (no localStorage)
- [x] **Checkout Process**: Complete order flow
- [x] **Order Management**: Order tracking and status
- [x] **PayFast Integration**: Payment gateway integrated

#### Incomplete
- [❓] **Order Fulfillment**: Complete fulfillment workflow
- [❓] **Email Notifications**: Order confirmations, shipping notifications
- [❓] **Inventory Management**: Stock tracking and alerts

---

## 🔐 SECURITY REQUIREMENTS

### Authentication & Authorization
- **Admin Authentication**: AdminAuth class with session management
- **User Authentication**: UserAuth class with session management
- **Password Hashing**: Bcrypt for all passwords
- **Session Management**: PHP native sessions
- **Login Attempt Limiting**: Account lockout after failed attempts
- **CSRF Protection**: Session tokens for form submissions

### Data Protection
- **SQL Injection Prevention**: PDO with prepared statements
- **XSS Prevention**: Input sanitization and output escaping
- **File Upload Security**: Validate and sanitize uploaded files
- **Session Security**: Secure session configuration

### Compliance
- **PCI DSS**: Not applicable (PayFast handles card data)
- **POPIA**: South African data protection compliance
- **GDPR**: Not applicable (no EU customers)

---

## 🚫 CRITICAL RESTRICTIONS

### Business Rules (NEVER Violate)
1. ❌ **NEVER mention "3D printing"** - It's the business secret
2. ❌ **NO 3D models or viewers** - Real product photos only
3. ❌ **NO localStorage** - Use server-side sessions only
4. ❌ **NO client-side cart** - Must be server-side with MySQL
5. ❌ **NO build tools** - Hostinger shared hosting limitation

### Technical Requirements
1. ✅ **Use PDO with prepared statements** (implemented in includes/database.php)
2. ✅ **Server-side sessions** for cart and state management
3. ✅ **Mobile-first responsive design**
4. ✅ **SEO optimization** with meta tags and Open Graph

---

## 🔴 CRITICAL ISSUES & RESOLUTION

### Issue 1: Dual System Chaos
**Severity**: ✅ RESOLVED
**Problem**: Two complete, separate systems running in parallel
**Status**: Consolidated to Standalone PHP. CI4 archived.

**Recommended Action**:
1. Choose Standalone PHP as production system
2. Deprecate CodeIgniter 4 system
3. Port any unique CI4 features to Standalone
4. Delete `app/` directory

### Issue 2: Code Duplication
**Severity**: 🔴 HIGH
**Problem**: Multiple versions of same files
- `user/dashboard/` has 5 versions
- `user/login/` has 4 versions
**Impact**: Confusion, bugs, wasted space
**Resolution**: Consolidate to single version per feature

### Issue 3: Authentication Confusion
**Severity**: ✅ RESOLVED
**Problem**: Two different auth systems (Standalone vs CI4)
**Status**: Standardized on Standalone AdminAuth/UserAuth.

### Issue 4: Database Driver Mismatch
**Severity**: 🟡 MEDIUM
**Problem**: PDO (standalone) vs MySQLi (CI4)
**Impact**: Code inconsistency, potential bugs
**Resolution**: Standardize to PDO (keep standalone system)

### Issue 5: Documentation Inconsistencies
**Severity**: 🟡 MEDIUM
**Problem**: Multiple documents with conflicting information
**Impact**: Developer confusion, deployment errors
**Resolution**: Update all documentation to reflect single system

---

## 📋 DATABASE SCHEMA

### Database: `cannabuddy`
**Version**: MySQL 5.7.24
**Status**: ✅ Operational

#### Tables

##### products
- id (Primary Key)
- name
- slug
- description
- price
- sale_price (nullable)
- featured (boolean)
- on_sale (boolean)
- stock
- colors (JSON)
- image_1
- image_2
- image_3
- created_at
- updated_at

##### orders
- id (Primary Key)
- user_id (Foreign Key)
- order_number
- total_amount
- status
- payfast_transaction_id (nullable)
- shipping_address
- billing_address
- created_at
- updated_at

##### order_items
- id (Primary Key)
- order_id (Foreign Key)
- product_id (Foreign Key)
- quantity
- price
- created_at

##### users
- id (Primary Key)
- email (unique)
- password
- first_name
- last_name
- phone
- is_active (boolean)
- created_at
- updated_at

##### admin_users
- id (Primary Key)
- username (unique)
- email (unique)
- password
- role
- is_active (boolean)
- login_attempts
- locked_until (nullable)
- last_login (nullable)
- created_at
- updated_at

##### homepage_slider
- id (Primary Key)
- title
- subtitle
- image
- button_text
- button_link
- sort_order
- is_active (boolean)
- created_at
- updated_at

##### categories (future use)
- id (Primary Key)
- name
- slug
- description
- created_at

---

## 🚀 DEVELOPMENT ROADMAP

### Phase 1: Consolidation (Week 1)
**Timeline**: 2025-12-08 to 2025-12-14
**Priority**: 🔴 CRITICAL

#### Tasks
1. **Decide Production System** (Dec 8-10)
   - [ ] Feature audit complete
   - [ ] Decision made (recommend Standalone PHP)
   - [ ] Team alignment achieved

2. **Plan Consolidation** (Dec 8-12)
   - [ ] Feature comparison documented
   - [ ] Migration plan created
   - [ ] Team briefed

3. **Begin Consolidation** (Dec 10-14)
   - [ ] Audit CI4 features
   - [ ] Port unique features to Standalone
   - [ ] Test ported features

### Phase 2: Cleanup (Week 2)
**Timeline**: 2025-12-15 to 2025-12-21
**Priority**: 🔴 HIGH

#### Tasks
1. **Clean Up Duplicate Files**
   - [ ] Consolidate dashboard files (5 versions → 1)
   - [ ] Consolidate login files (4 versions → 1)
   - [ ] Remove unused files

2. **Standardize Authentication**
   - [ ] Choose auth system (recommend AdminAuth/UserAuth)
   - [ ] Remove other auth system
   - [ ] Test all login flows

3. **Complete Incomplete Features**
   - [ ] Address Book
   - [ ] Personal Details
   - [ ] My Reviews
   - [ ] Admin Reviews enhancement

### Phase 3: Testing & Deployment (Week 3)
**Timeline**: 2025-12-22 to 2025-12-28
**Priority**: 🔴 HIGH

#### Tasks
1. **Comprehensive Testing**
   - [ ] Functional testing
   - [ ] Security testing
   - [ ] Performance testing
   - [ ] Compatibility testing

2. **Documentation Update**
   - [ ] Update all documentation
   - [ ] Single source of truth
   - [ ] Developer onboarding guide

3. **Deployment**
   - [ ] Deploy to staging
   - [ ] Final testing
   - [ ] Deploy to production
   - [ ] Monitor for issues

---

## 💡 RECOMMENDATIONS

### 1. Choose Standalone PHP as Production System
**Rationale**:
- More complete feature set (20+ admin pages vs 1 CI4 controller)
- More developed user system (25+ subdirectories)
- File-based routing works with shared hosting
- Documented as "primary" in CLAUDE.md
- Admin UI is more polished
- PDO is more modern than MySQLi

### 2. Deprecate CodeIgniter 4 System
**Action Items**:
1. Review CI4-specific features
2. Port any unique features to Standalone
3. Delete `app/` directory
4. Remove CI4 dependencies from composer

### 3. Clean Up Standalone System
**Action Items**:
1. Remove duplicate dashboard files (keep 1, remove 4)
2. Remove duplicate login files (keep 1, remove 3)
3. Standardize coding patterns
4. Update authentication to single model

### 4. Complete Incomplete Features
**Priority Order**:
1. Address Book
2. Personal Details
3. My Reviews
4. Admin Reviews enhancement

### 5. Enhance Payment System
**Tasks**:
1. Complete PayFast integration
2. Add webhook handling
3. Implement email notifications
4. Test complete checkout flow

---

## 📊 SUCCESS CRITERIA

### Technical Success Criteria
- [ ] Single system operational (no dual architecture)
- [ ] All features working in chosen system
- [ ] Zero duplicate files
- [ ] Single authentication system
- [ ] Single database layer
- [ ] All tests passing
- [ ] Security audit passed
- [ ] Performance benchmarks met

### Business Success Criteria
- [ ] Homepage fully functional with slider and product sections
- [ ] Admin panel with complete CRUD operations
- [ ] User system with account management
- [ ] E-commerce flow (cart, checkout, payment)
- [ ] PayFast payment processing
- [ ] Mobile responsive design
- [ ] SEO optimized

### Quality Criteria
- [ ] Code review passed
- [ ] Documentation complete and consistent
- [ ] Developer onboarding guide created
- [ ] Deployment procedure documented
- [ ] Monitoring and alerting in place

---

## 🧪 TESTING STRATEGY

### Functional Testing
- [ ] Homepage (slider, featured products, deals)
- [ ] Admin panel (all 20+ pages)
- [ ] User system (all 25+ subdirectories)
- [ ] Shop system
- [ ] Cart and checkout
- [ ] PayFast integration

### Security Testing
- [ ] Authentication flows
- [ ] Authorization (admin vs user access)
- [ ] SQL injection prevention
- [ ] XSS prevention
- [ ] CSRF protection
- [ ] Session security

### Performance Testing
- [ ] Page load times
- [ ] Database query optimization
- [ ] Image optimization
- [ ] CDN usage verification

### Compatibility Testing
- [ ] Desktop browsers (Chrome, Firefox, Safari, Edge)
- [ ] Mobile devices (iOS, Android)
- [ ] PHP 8.3.1 compatibility
- [ ] MySQL 5.7.24 compatibility

---

## 📚 DOCUMENTATION REQUIREMENTS

### Required Documents
1. **Technical Documentation**
   - [ ] Architecture overview (single system)
   - [ ] API documentation
   - [ ] Database schema
   - [ ] Authentication flow

2. **User Documentation**
   - [ ] Admin user guide
   - [ ] Customer user guide
   - [ ] FAQ

3. **Developer Documentation**
   - [ ] Setup instructions
   - [ ] Coding standards
   - [ ] Deployment guide
   - [ ] Troubleshooting guide

4. **Project Documentation**
   - [ ] This PRD (updated)
   - [ ] Project status document
   - [ ] Current tasks document
   - [ ] Change log

### Documentation Standards
- Use Markdown format
- Include code examples
- Add screenshots for UI components
- Keep up to date with changes
- Single source of truth per topic

---

## 🧱 Technical Debt & Discovery Findings

**Source**: `discovery.md` (2025-12-13) – comprehensive scan of the standalone PHP system  
**Purpose**: Capture technical debt as standalone work items that can be scheduled and tracked over time.

### Recommended Execution Order

- TD-01 → TD-02 → TD-03 → TD-04 → TD-10 → TD-05 → TD-06 → TD-07 → TD-08 → TD-09

---

### TD-01 – Environment-Aware Error & Logging Configuration

- **Problem**: Error reporting and display settings are hard-coded in multiple entry points, forcing development-style error visibility in all environments.
- **Code References**: 
  - `index.php:1–7`
  - `includes/error_handler.php:31–36`
  - `user/dashboard/index.php:2–7`
  - `includes/admin_error_catcher.php:12–18`
- **Priority**: High
- **Estimated Effort / Complexity**: Medium (1–2 days, 1 developer)
- **Dependencies / Prerequisites**:
  - None; this task should precede TD-02 and TD-07.
- **Acceptance Criteria**:
  - A central configuration or environment mechanism controls `error_reporting` and `display_errors`.
  - In production mode, PHP errors are logged but not displayed to users.
  - All ad-hoc `error_reporting` / `ini_set('display_errors', 1)` calls are removed or gated behind the central environment check.
  - Manual verification confirms production-like configuration does not leak stack traces or file paths.

---

### TD-02 – Consolidate Error Handling into a Single Global Handler

- **Problem**: Multiple overlapping error-handling implementations (global handler, enhanced handler, admin-specific handler, page-level handlers) create inconsistent behavior and duplicated logic.
- **Code References**:
  - `includes/error_handler.php:1–37, 57–135`
  - `includes/enhanced_error_handler.php:90–185, 250–306`
  - `includes/admin_error_catcher.php:1–32`
  - `admin/index.php:69–106`
  - `user/dashboard/index.php:11–61`
- **Priority**: High
- **Estimated Effort / Complexity**: High (2–4 days, requires careful regression testing)
- **Dependencies / Prerequisites**:
  - TD-01 (environment-aware error configuration).
- **Acceptance Criteria**:
  - One canonical error handler class is defined and used across front-end, admin, and user dashboard.
  - All other handlers are either removed or clearly marked as deprecated and unused in the runtime path.
  - Error type mapping and generic error page rendering are implemented once and reused.
  - Intentional behavior difference between development and production modes is documented and verified.

---

### TD-03 – Secure Database Configuration & Secrets Management

- **Problem**: Database credentials are hard-coded in the `Database` class, and some test scripts embed or echo sensitive connection details.
- **Code References**:
  - `includes/database.php:4–8`
  - `test_delete/test_database.php:1–42`
- **Priority**: High (security-critical)
- **Estimated Effort / Complexity**: Medium (1–2 days, requires deployment coordination)
- **Dependencies / Prerequisites**:
  - None; this task can run in parallel with TD-01/TD-02, but rollout should be coordinated with ops/deployment.
- **Acceptance Criteria**:
  - Database credentials are loaded from environment variables or a config file excluded from version control.
  - Local development still works with sensible defaults, but production requires explicit configuration.
  - Test scripts do not echo raw credentials or sensitive connection strings.
  - Deployment documentation is updated to describe how to configure DB credentials per environment.

---

### TD-04 – Implement CSRF Protection for Sensitive POST Actions

- **Problem**: Key forms (user auth, currency switch) use POST but do not enforce CSRF tokens, despite CSRF being a documented requirement.
- **Code References**:
  - `index.php:39–75` (user registration and login handling)
  - `includes/header.php:225–240` (currency switch form)
- **Priority**: High
- **Estimated Effort / Complexity**: Medium (2–3 days, needs careful integration across forms)
- **Dependencies / Prerequisites**:
  - TD-01 (for consistent handling of CSRF-related errors).
- **Acceptance Criteria**:
  - A CSRF token mechanism (session-based) is implemented and documented.
  - All state-changing forms (login, register, currency switch, other POST actions) include and validate CSRF tokens.
  - Invalid or missing tokens result in safe failures with appropriate user messaging and logging.
  - Security testing confirms CSRF attempts are rejected for protected actions.

---

### TD-05 – Clarify and Clean Up Legacy CodeIgniter 4 Artifacts

- **Problem**: Documentation and test utilities still reference the deprecated CodeIgniter 4 system and an `app/` directory that no longer exists.
- **Code References**:
  - `PRD/project_status.md:86–135`
  - `PRD/PRD_COMPREHENSIVE.md:167–195`
  - `test_delete/README.md:1–34`
  - `test_delete/check_setup.php:1–36`
  - `test_delete/direct_test.php:30–72`
- **Priority**: Medium
- **Estimated Effort / Complexity**: Medium (2–3 days, mostly documentation and directory hygiene)
- **Dependencies / Prerequisites**:
  - TD-10 (ensure CI4-related files are not part of production deploys).
- **Acceptance Criteria**:
  - All active documentation clearly states that the standalone PHP system is the only production system.
  - CI4-related docs and scripts are either moved into an `_archive/ci4/` area or explicitly marked as legacy.
  - No references remain suggesting CI4 is the primary system.
  - New developers can understand the current architecture without encountering conflicting instructions.

---

### TD-06 – Normalize User and Admin Architecture (Routing & Session Handling)

- **Problem**: User and admin sections each implement their own session checks, DB connections, and in some cases custom error logic, leading to duplication and inconsistent behavior.
- **Code References**:
  - `user/dashboard/index.php:63–115, 136–255`
  - `user/*/index.php` (other user sections)
  - `admin/index.php`
- **Priority**: Medium
- **Estimated Effort / Complexity**: High (multi-file refactor, 3–5 days)
- **Dependencies / Prerequisites**:
  - TD-01 and TD-02 (shared bootstrap and error handling).
- **Acceptance Criteria**:
  - A shared user bootstrap (router or front controller) handles auth checks and shared setup for user sections.
  - Admin pages share a single bootstrap path for session checks, error handling, and layout composition.
  - No user or admin page directly opens new DB connections or sets up sessions in an ad-hoc way.
  - Regression testing confirms existing user and admin flows still function correctly.

---

### TD-07 – Optimize Database Connection & Settings Retrieval

- **Problem**: Layout components create new `Database` instances and query settings independently, even when a connection already exists.
- **Code References**:
  - `admin_sidebar_components.php:55–66`
  - `includes/header.php:31–40, 111–121`
  - `index.php:18–24`
- **Priority**: Medium
- **Estimated Effort / Complexity**: Medium (1–3 days)
- **Dependencies / Prerequisites**:
  - TD-01 (for consistent error handling around DB failures).
- **Acceptance Criteria**:
  - Admin and front-end layouts reuse an existing `$db` connection or a shared data-access layer.
  - Settings (e.g., site name, logo, branding) are loaded once per request and reused by header and admin layout.
-  - Performance tests show reduced redundant queries and/or connection overhead for typical page loads.

---

### TD-08 – Align Documentation with Current Error Handling and Architecture

- **Problem**: Some documentation (e.g., error handling solution documents and architecture summaries) describe patterns or systems that do not match the current runtime behavior.
- **Code References**:
  - `test_delete/ERROR_HANDLING_SOLUTION.md:44–89, 226–262`
  - `README.md:1–22`
  - `PRD/ANALYSIS_SUMMARY.md`
  - `PRD/comprehensive_analysis.md`
- **Priority**: Medium
- **Estimated Effort / Complexity**: Medium (2–3 days)
- **Dependencies / Prerequisites**:
  - TD-02 (so docs can reflect the chosen canonical error handler).
- **Acceptance Criteria**:
  - Error handling documentation matches the implemented global handler behavior.
  - Architecture diagrams and descriptions clearly describe a single-system standalone PHP architecture.
  - Documentation requirements in this PRD (architecture overview, authentication flow) are satisfied using updated content.

---

### TD-09 – Establish Automated Testing for Standalone System

- **Problem**: Existing testing documentation and tooling focus on CI4; the standalone PHP system relies mainly on manual test scripts.
- **Code References**:
  - `test_delete/app_support/tests_README.md:1–36, 78–109`
  - `test_delete/test_database.php:1–42`
  - `test_delete/system_test.php:1–49, 91–118`
  - `test_delete/test_components.php:1–69`
- **Priority**: High (quality and process)
- **Estimated Effort / Complexity**: High (4–7 days to get a minimal but useful suite)
- **Dependencies / Prerequisites**:
  - TD-03 (DB configuration abstraction).
  - TD-01/TD-02 (stable and predictable bootstrap behavior).
 - **Acceptance Criteria**:
   - A PHPUnit test suite exists for core standalone components (Database, AdminAuth, UserAuth, routing).
   - Tests can be run locally via `vendor/bin/phpunit -c phpunit.xml`.
   - Coverage reports are generated into `tests/coverage/` with at least minimal coverage on database and auth flows.
   - New changes to critical components (DB config, auth, routing, currency, checkout) include corresponding tests.
  - Critical flows (admin login, user login, product listing, basic cart/checkout) have automated regression coverage.
  - CI or at least a documented local command can run the test suite and report results.
  - Testing documentation is updated to focus on the standalone system, with CI4-specific instructions moved to legacy docs.

---

### TD-10 – Control Deployment of Test Utilities and Backup Files

- **Problem**: Test utilities and backup files (including admin index backups and CI-focused test scripts) live alongside production code and may be deployed inadvertently.
- **Code References**:
  - `admin/index.php.backup_broken:1–25`
  - `test_delete/*` (entire directory)
- **Priority**: Medium
- **Estimated Effort / Complexity**: Low–Medium (1–2 days)
- **Dependencies / Prerequisites**:
  - None; can be executed early to reduce risk during other refactors.
- **Acceptance Criteria**:
  - Deployment process explicitly excludes `test_delete/` and other non-production support files.
  - Backup or experimental PHP files are moved out of request-accessible directories or removed if obsolete.
  - Documentation notes which directories are safe for local testing only.

---

## 🎯 BUSINESS IMPACT

### Current State
- Dual system architecture creates confusion
- Maintenance burden is 2x
- Security risk with two auth systems
- Deployment complexity high

### Post-Consolidation State
- Single system = clear direction
- Maintenance burden reduced by 50%
- Security surface reduced
- Deployment simplified
- Developer productivity increased

### Measurable Outcomes
- [ ] 50% reduction in maintenance time
- [ ] 100% reduction in dual-system confusion
- [ ] Zero security vulnerabilities from dual auth
- [ ] 75% faster feature development
- [ ] 90% reduction in deployment issues

---

## 📞 PROJECT CONTACTS

### Team Structure
- **Product Owner**: Business Owner
- **Tech Lead**: Development Team Lead
- **Senior Developer**: Architecture and critical features
- **Developer**: Feature development and testing
- **QA Engineer**: Testing and quality assurance
- **Technical Writer**: Documentation

### Communication
- **Daily Standups**: 9:00 AM SAST (15 min)
- **Weekly Reviews**: Friday 4:00 PM SAST (1 hour)
- **Milestone Reviews**: End of each phase

---

## 📈 METRICS & KPIs

### Technical Metrics
- [ ] Code coverage: 80%+
- [ ] Page load time: < 3 seconds
- [ ] Database query time: < 500ms
- [ ] Zero critical security vulnerabilities
- [ ] 99.9% uptime

### Business Metrics
- [ ] Order conversion rate: Track post-launch
- [ ] User registration rate: Track post-launch
- [ ] Admin efficiency: Time to manage products/orders
- [ ] Customer satisfaction: Post-purchase surveys

### Project Metrics
- [ ] On-time delivery: All phases by deadlines
- [ ] Budget adherence: Track development costs
- [ ] Quality metrics: Bugs per feature
- [ ] Team velocity: Features per sprint

---

## 🏁 CONCLUSION

### Current State
The CannaBuddy e-commerce platform is a **well-featured but architecturally chaotic** implementation with two complete, separate systems. Both systems are functional but create severe maintenance, security, and deployment challenges.

### Required Action
**Immediate consolidation to a single system is non-negotiable.** The recommended approach is to choose the Standalone PHP system as production, deprecate the CodeIgniter 4 system, and complete the incomplete features.

### Expected Outcome
Post-consolidation, the platform will be a streamlined, maintainable, secure e-commerce system ready for production launch and future growth.

### Timeline
With focused effort, consolidation can be completed in 3 weeks (by 2025-12-28), followed by production deployment.

---

**Document Version**: 3.1
**Last Updated**: 2025-12-13
**Change Summary**: Added "Technical Debt & Discovery Findings" section based on discovery.md (2025-12-13)
**Next Review**: After Phase 1 completion (2025-12-14)
**Approved By**: Development Team Lead
