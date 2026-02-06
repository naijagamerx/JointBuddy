# BMW Product Image Investigation & Fix

## Issue Summary
- **Product**: BMW Bic Lighter Sleeves (slug: `bmw-bic-lighter-sleeves`)
- **Problem**: Images show fallback image on single product page, but work on homepage
- **Additional Issue**: 3-in-1 container product missing entire gallery section
- **Admin Panel**: BMW product image now broken there too

## Investigation Steps

### 1. Database Comparison
Query the products table to compare:
- BMW product vs a working product
- Check `image`, `images` (JSON), `image_path` fields
- Look for special characters, spaces, or encoding issues

### 2. Code Analysis
- Single product page template: `shop/product/index.php` or similar
- Homepage template: How it renders product images
- Admin edit product: `admin/products/edit.php`

### 3. File System Check
- Verify actual image files exist in `assets/images/`
- Check filename consistency

## Progress

### Investigation Results (2025-01-18)

#### Database Analysis
```
BMW Product (id: 26):
- images: EMPTY
- image_1: assets/images/products/prod_693dc8f4b4cf7.png (NO leading slash)

3-in-1 Product (id: 25):
- images: /CannaBuddy.shop/assets/images/products/... (HARDCODED - FORBIDDEN!)
- image_1: EMPTY
```

#### Root Causes Identified

1. **Admin Panel (`admin/products/index.php`)**:
   - Uses raw image paths WITHOUT `url()` helper
   - For BMW: `assets/images/...` → relative path from current page → 404
   - For others: `/CannaBuddy.shop/assets/...` → absolute path → works

2. **Helper Functions (`includes/product_helpers.php`)**:
   - `getProductMainImage()` and `getProductImages()` work CORRECTLY
   - They properly use `url()` helper for path generation
   - Test confirms: `http://localhost/CannaBuddy.shop/assets/images/products/prod_693dc8f4b4cf7.png`

3. **Single Product Page**:
   - Uses helper functions which generate correct URLs
   - Should be working - may be browser cache issue

### Files to Fix

1. `admin/products/index.php` - Add URL helper processing for images
2. Database: Normalize image paths (remove `/CannaBuddy.shop/` prefix)

## Implementation

### Changes Made (2025-01-18)

#### 1. Fixed Admin Products List Page (`admin/products/index.php`)
**Problem**: Used raw image paths without `url()` helper
**Fix**: Added URL processing similar to homepage logic
```php
// Before: $productImage = trim($imageUrls[0]);
// After:
$dbPath = trim($imageUrls[0]);
$dbPath = preg_replace('#^https?://[^/]+/[^/]+/#', '/', $dbPath);
$dbPath = preg_replace('#^/CannaBuddy\.shop/#', '/', $dbPath);
$dbPath = ltrim($dbPath, '/');
$productImage = url($dbPath);
```

#### 2. Fixed Admin Product Edit Page (`admin/products/edit/index.php`)
**Problem**: JavaScript used raw URLs causing 404s for relative paths
**Fix**: Added `normalizeImageUrl()` JavaScript function
```javascript
function normalizeImageUrl(rawUrl) {
    if (!rawUrl) return '';
    let url = rawUrl.replace(/^https?:\/\/[^\/]+\/[^\/]+\//i, '/');
    url = url.replace(/^\/CannaBuddy\.shop\//i, '/');
    url = url.replace(/^\//, '');
    const baseUrl = window.location.origin + window.location.pathname.replace(/\/admin\/products\/edit\/.*/, '');
    return baseUrl + '/' + url;
}
```

#### 3. Database Migration (`test_delete/fix_image_paths.php`)
**Fixed**:
- Removed hardcoded `/CannaBuddy.shop/` prefix from 5 products
- Migrated BMW product from `image_1` to `images` field for consistency

**Results**:
```
BMW Product (id: 26): images = /assets/images/products/prod_693dc8f4b4cf7.png
3-in-1 Product (id: 25): images = /assets/images/products/prod_693dcceed506b.png,...
```

#### 4. Fixed Helper Functions (`includes/product_helpers.php`)
**Bug**: Line 313 regex `preg_replace('#^/[^/]+/#', '/', $imagePath)` was stripping `/assets/` from clean paths
**Fix**: Changed to specific `/CannaBuddy.shop/` removal only
```php
// Before: $imagePath = preg_replace('#^/[^/]+/#', '/', $imagePath);
// After:  $imagePath = preg_replace('#^/CannaBuddy\.shop/#', '/', $imagePath);
```

**Fixed in both**:
- `getProductMainImage()` (lines 313, 328)
- `getProductImages()` (lines 355, 376)

## Verification

All image URLs now generate correctly:
```
BMW:   http://localhost/CannaBuddy.shop/assets/images/products/prod_693dc8f4b4cf7.png
3-in-1: http://localhost/CannaBuddy.shop/assets/images/products/prod_693dcceed506b.png
```

## Files Modified
1. `admin/products/index.php` - Added URL helper processing
2. `admin/products/edit/index.php` - Added JavaScript URL normalization
3. `includes/product_helpers.php` - Fixed regex bug in helper functions
4. Database: Migrated 6 products to clean format

## Cleanup
- Delete: `test_delete/check.php`
- Delete: `test_delete/fix_image_paths.php`
- Delete: `test_delete/fix_image_paths.php` after verification