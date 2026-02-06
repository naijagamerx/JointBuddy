# Project Memory Log

## Task Statistics
- Total Tasks: 4
- Success Rate: 100%
- Most Active Files: admin/products/view/index.php, admin/tools/edit.php, admin/slider/index.php

---

## Task History

### T20251214-001: Hero Section Management
- **Date**: 2025-12-14
- **Task Description**: Implemented complete hero section management system for homepage
- **Outcome**: Success
- **Related Files**:
  - `admin/slider/index.php` - Added hero sections management UI and POST handling
  - `index.php` - Integrated hero sections display on homepage
- **Notes**:
  - Created `homepage_hero_sections` database table
  - Two hero sections configurable from admin panel
  - Background image upload, text content, button configuration
  - Active/Inactive toggle for each hero
  - Full admin interface with collapsible UI

### T20251215-001: Admin Product Edit Page
- **Date**: 2025-12-15
- **Task Description**: Created comprehensive admin product edit page at `/admin/products/edit/{slug}`
- **Outcome**: Success
- **Related Files**:
  - `admin/products/edit/index.php` (Created)
  - `index.php` (Modified - Added regex routing)
  - `admin/products/delete/index.php` (Created)
- **Notes**:
  - Full CRUD operations for products
  - Image management (add/remove)
  - Pre-populated form fields
  - Category field uses text, not category_id
  - Uses UPDATE query (not INSERT)
  - Maintains consistency with add product page

### T20251215-002: Admin View Page Redesign & Cache Tools
- **Date**: 2025-12-15
- **Task Description**: Redesigned product view page with banner-style images and created cache clearing tools
- **Outcome**: Success
- **Related Files**:
  - `admin/products/view/index.php` (Modified)
  - `admin/tools/index.php` (Created)
  - `admin_sidebar_components.php` (Modified)
- **Notes**:
  - Banner-style image gallery with grid layout
  - Full-width details section (single column)
  - Cache clearing tools (OPcache, Session, All)
  - System information display
  - No tabs - all content visible

### T20251215-003: Experimental Admin Pages
- **Date**: 2025-12-15
- **Task Description**: Created experimental edit and view pages with modern design
- **Outcome**: Success
- **Related Files**:
  - `admin/tools/edit.php` (Created)
  - `admin/tools/view.php` (Created)
  - `index.php` (Modified - Added routing)
- **Notes**:
  - Modern gradient designs
  - Product preview cards
  - Animated toggle switches
  - Full image management (upload, reorder, delete)
  - Fixed image preview URL issues
  - Removed URL transformations - use DB URLs directly
  - Product selectors for navigation

---

## Quick Reference

### Recent Tasks
1. Hero Section Management (2025-12-14)
2. Admin Product Edit Page (2025-12-15)
3. View Page Redesign & Cache Tools (2025-12-15)
4. Experimental Admin Pages (2025-12-15)

### Files Modified This Week
- `admin/slider/index.php`
- `admin/products/view/index.php`
- `admin/products/edit/index.php`
- `admin/tools/index.php`
- `admin/tools/edit.php`
- `admin/tools/view.php`
- `index.php` (routing)

### Common Task Types
- Feature Implementation
- UI/UX Improvements
- Database Integration
- Image Management
- Routing Configuration

---

## Technical Notes

### URL Helper Usage
- Always use `url()`, `rurl()`, `adminUrl()`, `userUrl()`, `productUrl()`, `assetUrl()`
- NEVER hardcode URLs like `/CannaBuddy.shop/`
- Images stored in DB as relative paths from document root

### Database Schema
- Products table uses `category` (text), NOT `category_id`
- Products table uses `active` (boolean), NOT `status`
- Images stored as comma-separated URLs
- Tags and custom_fields stored as JSON

### Routing Patterns
- Regex-based routing for dynamic URLs
- Pattern: `preg_match('#^admin/products/edit/(.+)$#', $route, $matches)`
- Route matching must happen before file-based routing

---

## Project Standards

### Code Quality
- PSR-12 compliant formatting
- PDO prepared statements for all queries
- htmlspecialchars() on all output
- Consistent naming conventions

### Testing
- PHP syntax validation: `php -l filename.php`
- Database testing with sample data
- Browser testing for UI components
- Cache clearing for troubleshooting

### File Organization
- Test files go to `test_delete/` folder
- Task documentation in `.claude/tasks/`
- No test files in root directory

---

## Future Considerations

### Known Issues
- None currently

### Pending Enhancements
- [ ] Hero preview on hover in admin
- [ ] Drag-and-drop reordering for heroes
- [ ] Multiple background images with carousel
- [ ] Hero scheduling (show/hide by date)

### Maintenance Schedule
- Monthly: Review hero section performance
- Quarterly: Update background images
- As Needed: Add new features

---

*Memory last updated: 2025-12-26*
