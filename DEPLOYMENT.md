# DEPLOYMENT INSTRUCTIONS - Products Delete Fix

## Date: 2025-12-11
## Issue: Admin products delete button non-functional

---

## Pre-Deployment Checklist

### 1. Backup Current Files
**Before making any changes, backup these files:**

```bash
# Create backup directory
mkdir -p /path/to/backup/$(date +%Y%m%d_%H%M%S)
cd /path/to/backup/$(date +%Y%m%d_%H%M%S)

# Backup files
cp /path/to/your/site/includes/admin_routes.php ./
cp /path/to/your/site/admin/products/index.php ./
```

### 2. Backup Database (Recommended)
```bash
# Create database backup
mysqldump -u [username] -p[password] cannabuddy > backup_cannabuddy_$(date +%Y%m%d_%H%M%S).sql
```

---

## Deployment Steps

### Step 1: Upload Modified Files

**File 1: `/includes/admin_routes.php`**
- **Location on server:** `/path/to/your/site/includes/admin_routes.php`
- **Action:** Upload new version
- **Lines changed:** 1024-1028
- **Size:** ~2KB

**File 2: `/admin/products/index.php`**
- **Location on server:** `/path/to/your/site/admin/products/index.php`
- **Action:** Upload new version (for success/error message display)
- **Lines changed:** Added ~30 lines after line 35
- **Size:** ~1KB

### Step 2: Set File Permissions
```bash
chmod 644 /path/to/your/site/includes/admin_routes.php
chmod 644 /path/to/your/site/admin/products/index.php
```

### Step 3: Clear PHP OPcache (if enabled)
```bash
# If using OPcache, restart PHP-FPM or Apache
sudo systemctl restart php-fpm
# OR
sudo systemctl restart apache2
```

---

## Verification Steps

### 1. Test Delete Functionality
1. Log into admin panel: `https://yourdomain.com/admin/`
2. Navigate to **Products** section
3. Find any product
4. Click the **Delete** button (red trash icon)
5. Confirm deletion in the popup dialog
6. **Expected Result:**
   - Product should be removed from the table
   - Success message should appear: "Product deleted successfully"
   - Page should reload showing updated product count

### 2. Verify Database Changes
```sql
-- Connect to MySQL
mysql -u [username] -p[password] cannabuddy

-- Check if product was actually deleted
SELECT COUNT(*) FROM products;

-- Verify the specific product is gone
SELECT * FROM products WHERE slug = '[deleted-product-slug]';
```

### 3. Check Error Logs
```bash
# Check PHP error logs
tail -f /path/to/your/site/logs/php_errors_admin.log

# Check web server error logs
tail -f /var/log/apache2/error.log
# OR
tail -f /var/log/nginx/error.log
```

---

## What Was Fixed

### Before:
- Delete button showed confirmation dialog
- After clicking "OK", page redirected to products list
- **Product was NOT deleted** (mock implementation)
- No user feedback

### After:
- Delete button shows confirmation dialog
- After clicking "OK", product is **actually deleted** from database
- Success/error message displayed
- Page reloads with updated product list
- Proper error handling for edge cases

---

## Troubleshooting

### Issue: Delete button still not working
**Solution:**
1. Check file upload was successful
2. Verify file permissions (644)
3. Clear OPcache if enabled
4. Check error logs for PHP errors

### Issue: "Product not found" message
**Solution:**
1. Check if product slug is correct in URL
2. Verify database has the product record
3. Check for special characters in product name/slug

### Issue: Database constraint errors
**Solution:**
1. Check if there are foreign key constraints on the products table
2. If errors persist, check related tables (order_items, etc.)
3. May need to manually clean up related records

### Issue: Success message not showing
**Solution:**
1. Make sure you uploaded the updated `/admin/products/index.php`
2. Check if session is working properly
3. Clear browser cache and test again

---

## Rollback Procedure

If something goes wrong:

### 1. Restore Files from Backup
```bash
# Restore original files
cp /path/to/backup/[timestamp]/admin_routes.php /path/to/your/site/includes/
cp /path/to/backup/[timestamp]/index.php /path/to/your/site/admin/products/

# Set permissions
chmod 644 /path/to/your/site/includes/admin_routes.php
chmod 644 /path/to/your/site/admin/products/index.php
```

### 2. Restore Database (if needed)
```bash
# Drop current database and restore from backup
mysql -u [username] -p[password] -e "DROP DATABASE cannabuddy;"
mysql -u [username] -p[password] < backup_cannabuddy_[timestamp].sql
```

---

## Post-Deployment Monitoring

### Monitor These:
1. **Error Logs** - Check daily for any delete-related errors
2. **Product Count** - Verify products are actually being deleted
3. **User Feedback** - Check if admin users report issues
4. **Database Integrity** - Ensure no orphaned records

### Success Indicators:
- ✅ Products are deleted successfully
- ✅ Success messages appear
- ✅ No errors in logs
- ✅ Product count decreases after deletion

---

## Support Contact
If you encounter issues during deployment, document:
1. Exact error messages
2. Steps taken
3. Log file contents
4. Current environment (PHP version, MySQL version, web server)

---

## Estimated Deployment Time
- **File Upload:** 2 minutes
- **Verification:** 5 minutes
- **Total:** ~10 minutes

---

## Risk Level: LOW
- Changes are minimal and targeted
- Only affects the delete functionality
- No changes to database schema
- Includes proper error handling and rollback