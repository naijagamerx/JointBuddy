# Product Images Fix Plan

## Issue Analysis

### Admin Page (Working)
- **Query**: Uses `p.images as product_images` (comma-separated list)
- **Image Processing**:
  - Splits by comma to get first image
  - Removes `/CannaBuddy.shop/` prefix
  - Uses `url()` helper for correct path

### User Pages (Broken)
- **Query**: Uses `p.image_1` which may be NULL/empty
- **Image Display**: Uses `assetUrl('images/' . $item['image_1'])` - wrong path

---

## Fix Required

### Step 1: Update Query
Change from:
```sql
SELECT oi.*, p.name as product_name, p.slug as product_slug, p.image_1
```

To:
```sql
SELECT oi.*, p.name as product_name, p.slug as product_slug, p.images as product_images
```

### Step 2: Add Image Processing Helper Function
```php
function getProductImageUrl($productImages) {
    if (empty($productImages)) {
        return '';
    }
    $imageParts = explode(',', $productImages);
    $firstImage = trim($imageParts[0]);
    if (empty($firstImage)) {
        return '';
    }
    // Remove /CannaBuddy.shop/ prefix and generate URL
    $imagePath = ltrim(str_replace('/CannaBuddy.shop/', '', $firstImage), '/');
    return url($imagePath);
}
```

### Step 3: Update Image Display
Change from:
```php
<img src="<?= assetUrl('images/' . $item['image_1']) ?>" ...>
```

To:
```php
<?php
$imageUrl = getProductImageUrl($item['product_images']);
if (!empty($imageUrl)):
?>
    <img src="<?= htmlspecialchars($imageUrl) ?>" ...>
<?php else: ?>
    <i class="fas fa-cannabis text-green-600 text-xl"></i>
<?php endif; ?>
```

---

## Files to Fix

| File | Query Change | Image Display |
|------|--------------|---------------|
| `user/orders/index.php` | Line 86: `p.image_1` → `p.images as product_images` | Lines 249-260 |
| `user/orders/view.php` | Line 82: `p.image_1` → `p.images as product_images` | Lines 341-350 |
| `user/orders/track.php` | Line 82: `p.image_1` → `p.images as product_images` | Lines 365-371 |

---

## Implementation

## COMPLETED: 2025-12-26

All three pages fixed:

### user/orders/index.php
- Query: `p.image_1` → `p.images as product_images`
- Added `getProductImageUrl()` helper function
- Updated image display (lines 260-279)

### user/orders/view.php
- Query: `p.image_1` → `p.images as product_images`
- Added `getProductImageUrl()` helper function
- Updated image display (lines 271-278)

### user/orders/track.php
- Query: `p.image_1` → `p.images as product_images`
- Added `getProductImageUrl()` helper function
- Updated image display (lines 380-387)

All PHP syntax verified - no errors detected.

*Plan created: 2025-12-26*
*Status: COMPLETED*
