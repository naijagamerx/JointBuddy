## Analysis of the Issues

### Issue 1: Product Details Not Showing
**Root Cause**: The query in [admin/returns/view.php](c:\MAMP\htdocs\CannaBuddy.shop\admin\returns\view.php#L129-L137) joins `return_items` with `order_items` and `products` to get product details. If `order_items` doesn't have `product_id` or if the product is deleted, the LEFT JOIN might return NULL for `product_images` and `product_id`.

**SQL Query**:
```sql
SELECT ri.*, oi.product_name, oi.unit_price, p.images as product_images, p.id as product_id
FROM return_items ri
JOIN order_items oi ON ri.order_item_id = oi.id
LEFT JOIN products p ON oi.product_id = p.id
WHERE ri.return_id = ?
```

### Issue 2: Shipping Address Fields Missing
**Root Cause**: The `orders` table stores `shipping_address` as JSON (confirmed in [admin/orders/view/index.php](c:\MAMP\htdocs\CannaBuddy.shop\admin\orders\view\index.php#L117)), not as separate columns. The query in [admin/returns/view.php](c:\MAMP\htdocs\CannaBuddy.shop\admin\returns\view.php#L113-L122) tries to select `shipping_address, shipping_city, shipping_postal_code, shipping_phone` as separate columns, but these columns don't exist.

### Issue 3: htmlspecialchars() Deprecated Error (index.php:220)
**Root Cause**: One of the variables passed to `htmlspecialchars()` is NULL. This is a PHP 8.1+ deprecation warning.

## Implementation Plan

### Step 1: Fix Shipping Address Query
- Modify the query to fetch `shipping_address` as JSON
- JSON decode the address to extract city, postal code, and phone
- Update the display code to handle the JSON structure

### Step 2: Verify Return Items Query
- Add fallback for missing product images
- Ensure proper handling of NULL values in the query results

### Step 3: Fix htmlspecialchars() Errors
- Run the test script to identify which variable is NULL
- Add null coalescing operators `??` or proper null checks

## Files to Modify
1. **admin/returns/view.php** - Fix SQL query and JSON decoding for shipping address
2. **index.php** - Fix htmlspecialchars() null value issues (line 220)
