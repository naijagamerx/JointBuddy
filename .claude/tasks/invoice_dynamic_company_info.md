# Invoice Page - Dynamic Company Information from Settings

## Task Overview
Update the invoice pages (`admin/orders/view/index.php` and `admin/orders/view/print.php`) to pull company information dynamically from the admin settings page instead of using hardcoded values and incorrect setting keys.

## Analysis Summary

### Current Issues Identified

#### 1. Wrong Setting Keys in Both Files

**Admin Settings Page** (`admin/settings/index.php`) stores data as:
- `store_name` - Company name
- `store_email` - Email address
- `store_phone` - Phone number
- `store_address` - Store address (textarea, supports multiline)

**Orders View Page** (`admin/orders/view/index.php` lines 127-133) uses WRONG keys:
```php
$stmt = $db->query("SELECT setting_key, setting_value FROM settings");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$siteName = $settings['site_name'] ?? 'Store';           // ❌ WRONG - should be store_name
$companyAddress = $settings['address'] ?? '';             // ❌ WRONG - should be store_address
$companyPhone = $settings['phone'] ?? '';                 // ❌ WRONG - should be store_phone
// ❌ MISSING - store_email
```

**Print Invoice Page** (`admin/orders/view/print.php` lines 52-57) uses WRONG keys:
```php
$stmt = $db->query("SELECT setting_key, setting_value FROM settings");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$siteName = $settings['site_name'] ?? 'Store';           // ❌ WRONG - should be store_name
$companyAddress = $settings['address'] ?? '';             // ❌ WRONG - should be store_address
$companyPhone = $settings['phone'] ?? '';                 // ❌ WRONG - should be store_phone
$companyEmail = $settings['contact_email'] ?? '';         // ❌ WRONG - should be store_email
```

#### 2. Address Formatting Issues

**Current behavior** (line 442 in view/index.php):
```php
<p class="text-sm text-gray-600 mt-1"><?php echo nl2br(htmlspecialchars($companyAddress)); ?></p>
```
- Uses `nl2br()` which converts newlines to `<br>` tags
- User wants **3 separate lines** for address display

**User requirement**:
- Address should display as **3 lines** (not just nl2br)
- Email should appear **before** phone number

#### 3. Missing Email in View Page

**Current structure** (lines 440-444):
```php
<h2>Company Name</h2>
<p>Address</p>
<p>Phone</p>
```

**Required structure**:
```php
<h2>Company Name</h2>
<p>Address Line 1</p>     <!-- or full address with proper formatting -->
<p>City, State, ZIP</p>   <!-- as separate lines -->
<p>Email</p>              <!-- NEW - before phone -->
<p>Phone</p>
```

---

## Implementation Plan

### Phase 1: Fix Setting Keys in Orders View Page

**File**: `admin/orders/view/index.php`

**Location**: Lines 127-133

**Current Code**:
```php
// Get store settings for company info
$stmt = $db->query("SELECT setting_key, setting_value FROM settings");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$siteName = $settings['site_name'] ?? 'Store';
$companyAddress = $settings['address'] ?? '';
$companyPhone = $settings['phone'] ?? '';
```

**New Code**:
```php
// Get store settings for company info
$stmt = $db->query("SELECT setting_key, setting_value FROM settings");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$storeName = $settings['store_name'] ?? 'Store';
$storeAddress = $settings['store_address'] ?? '';
$storeEmail = $settings['store_email'] ?? '';
$storePhone = $settings['store_phone'] ?? '';
```

### Phase 2: Fix Invoice Header HTML in View Page

**File**: `admin/orders/view/index.php`

**Location**: Lines 436-468 (Invoice Header section)

**Current Output**:
```html
<div class="mb-4 md:mb-0">
    <h2 class="text-xl font-bold text-gray-900">Company Name</h2>
    <p class="text-sm text-gray-600 mt-1">Address (single line with nl2br)</p>
    <p class="text-sm text-gray-600 mt-1"><i class="fas fa-phone mr-1"></i>Phone</p>
</div>
```

**New Output Structure**:
```html
<div class="mb-4 md:mb-0">
    <h2 class="text-xl font-bold text-gray-900">Store Name (dynamic)</h2>
    <!-- Address as 3 separate lines if available -->
    <p class="text-sm text-gray-600 mt-1">Address Line 1</p>
    <p class="text-sm text-gray-600">Address Line 2 (if exists)</p>
    <p class="text-sm text-gray-600">City, State, ZIP</p>
    <!-- Email BEFORE phone -->
    <p class="text-sm text-gray-600 mt-1"><i class="fas fa-envelope mr-1"></i>Email</p>
    <p class="text-sm text-gray-600"><i class="fas fa-phone mr-1"></i>Phone</p>
</div>
```

**Address Parsing Logic**:
The `store_address` field is a textarea that can contain multiline input. We need to:
1. Split the address by newlines
2. Display each line as a separate `<p>` tag
3. Ensure we have max 3 lines (or handle whatever is stored)
4. Add email and phone after address

### Phase 3: Fix Setting Keys in Print Invoice Page

**File**: `admin/orders/view/print.php`

**Location**: Lines 52-57

**Current Code**:
```php
$stmt = $db->query("SELECT setting_key, setting_value FROM settings");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$siteName = $settings['site_name'] ?? 'Store';
$companyAddress = $settings['address'] ?? '';
$companyPhone = $settings['phone'] ?? '';
$companyEmail = $settings['contact_email'] ?? '';
```

**New Code**:
```php
$stmt = $db->query("SELECT setting_key, setting_value FROM settings");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$storeName = $settings['store_name'] ?? 'Store';
$storeAddress = $settings['store_address'] ?? '';
$storeEmail = $settings['store_email'] ?? '';
$storePhone = $settings['store_phone'] ?? '';
```

### Phase 4: Update Print Invoice HTML

**File**: `admin/orders/view/print.php`

**Location**: Lines 422-431 (company-details section)

**Current Code**:
```php
<div class="company-details">
    <h1><?php echo htmlspecialchars($siteName); ?></h1>
    <p><?php echo nl2br(htmlspecialchars($companyAddress)); ?></p>
    <?php if (!empty($companyPhone) || !empty($companyEmail)): ?>
        <p>
            <?php if (!empty($companyPhone)): ?><i class="fas fa-phone"></i> <?php echo htmlspecialchars($companyPhone); ?><?php endif; ?>
            <?php if (!empty($companyPhone) && !empty($companyEmail)): ?> | <?php endif; ?>
            <?php if (!empty($companyEmail)): ?><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($companyEmail); ?><?php endif; ?>
        </p>
    <?php endif; ?>
</div>
```

**New Code**:
```php
<div class="company-details">
    <h1><?php echo htmlspecialchars($storeName); ?></h1>
    <?php
    // Parse address into separate lines
    $addressLines = array_filter(array_map('trim', explode("\n", $storeAddress)));
    foreach ($addressLines as $line): ?>
        <p><?php echo htmlspecialchars($line); ?></p>
    <?php endforeach; ?>
    <?php if (!empty($storeEmail)): ?>
        <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($storeEmail); ?></p>
    <?php endif; ?>
    <?php if (!empty($storePhone)): ?>
        <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($storePhone); ?></p>
    <?php endif; ?>
</div>
```

### Phase 5: Update Print Invoice Footer

**File**: `admin/orders/view/print.php`

**Location**: Lines 548-553 (doc-footer section)

**Current Code**:
```php
<div class="doc-footer">
    <div class="contact">
        <?php if (!empty($companyPhone)): ?><span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($companyPhone); ?></span><?php endif; ?>
        <?php if (!empty($companyEmail)): ?><span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($companyEmail); ?></span><?php endif; ?>
    </div>
    <p>Thank you for your order! | Generated on <?php echo date('F j, Y \a\t g:i A'); ?></p>
</div>
```

**New Code**:
```php
<div class="doc-footer">
    <div class="contact">
        <?php if (!empty($storeEmail)): ?><span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($storeEmail); ?></span><?php endif; ?>
        <?php if (!empty($storePhone)): ?><span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($storePhone); ?></span><?php endif; ?>
    </div>
    <p>Thank you for your order! | Generated on <?php echo date('F j, Y \a\t g:i A'); ?></p>
</div>
```

---

## Detailed Code Changes

### File 1: admin/orders/view/index.php

#### Change 1: Update settings query (lines 127-133)

**BEFORE:**
```php
// Get store settings for company info
$stmt = $db->query("SELECT setting_key, setting_value FROM settings");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$siteName = $settings['site_name'] ?? 'Store';
$companyAddress = $settings['address'] ?? '';
$companyPhone = $settings['phone'] ?? '';
```

**AFTER:**
```php
// Get store settings for company info
$stmt = $db->query("SELECT setting_key, setting_value FROM settings");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$storeName = $settings['store_name'] ?? 'Store';
$storeAddress = $settings['store_address'] ?? '';
$storeEmail = $settings['store_email'] ?? '';
$storePhone = $settings['store_phone'] ?? '';
```

#### Change 2: Update invoice header HTML (lines 436-444)

**BEFORE:**
```php
<!-- Company Info (Left) -->
<div class="mb-4 md:mb-0">
    <h2 class="text-xl font-bold text-gray-900">' . htmlspecialchars($siteName) . '</h2>
    <p class="text-sm text-gray-600 mt-1">' . nl2br(htmlspecialchars($companyAddress)) . '</p>
    ' . (!empty($companyPhone) ? '<p class="text-sm text-gray-600 mt-1"><i class="fas fa-phone mr-1"></i>' . htmlspecialchars($companyPhone) . '</p>' : '') . '
</div>
```

**AFTER:**
```php
<!-- Company Info (Left) -->
<div class="mb-4 md:mb-0">
    <h2 class="text-xl font-bold text-gray-900">' . htmlspecialchars($storeName) . '</h2>';
    // Parse address into separate lines
    $addressLines = array_filter(array_map('trim', explode("\n", $storeAddress)));
    foreach ($addressLines as $addrLine) {
        $content .= '<p class="text-sm text-gray-600 mt-1">' . htmlspecialchars($addrLine) . '</p>';
    }
    $content .= (!empty($storeEmail) ? '<p class="text-sm text-gray-600 mt-1"><i class="fas fa-envelope mr-1"></i>' . htmlspecialchars($storeEmail) . '</p>' : '') . '
    ' . (!empty($storePhone) ? '<p class="text-sm text-gray-600"><i class="fas fa-phone mr-1"></i>' . htmlspecialchars($storePhone) . '</p>' : '') . '
</div>
```

### File 2: admin/orders/view/print.php

#### Change 1: Update settings query (lines 52-57)

**BEFORE:**
```php
$stmt = $db->query("SELECT setting_key, setting_value FROM settings");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$siteName = $settings['site_name'] ?? 'Store';
$companyAddress = $settings['address'] ?? '';
$companyPhone = $settings['phone'] ?? '';
$companyEmail = $settings['contact_email'] ?? '';
```

**AFTER:**
```php
$stmt = $db->query("SELECT setting_key, setting_value FROM settings");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$storeName = $settings['store_name'] ?? 'Store';
$storeAddress = $settings['store_address'] ?? '';
$storeEmail = $settings['store_email'] ?? '';
$storePhone = $settings['store_phone'] ?? '';
```

#### Change 2: Update company-details header (lines 422-431)

**BEFORE:**
```php
<div class="company-details">
    <h1><?php echo htmlspecialchars($siteName); ?></h1>
    <p><?php echo nl2br(htmlspecialchars($companyAddress)); ?></p>
    <?php if (!empty($companyPhone) || !empty($companyEmail)): ?>
        <p>
            <?php if (!empty($companyPhone)): ?><i class="fas fa-phone"></i> <?php echo htmlspecialchars($companyPhone); ?><?php endif; ?>
            <?php if (!empty($companyPhone) && !empty($companyEmail)): ?> | <?php endif; ?>
            <?php if (!empty($companyEmail)): ?><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($companyEmail); ?><?php endif; ?>
        </p>
    <?php endif; ?>
</div>
```

**AFTER:**
```php
<div class="company-details">
    <h1><?php echo htmlspecialchars($storeName); ?></h1>
    <?php
    $addressLines = array_filter(array_map('trim', explode("\n", $storeAddress)));
    foreach ($addressLines as $line): ?>
        <p><?php echo htmlspecialchars($line); ?></p>
    <?php endforeach; ?>
    <?php if (!empty($storeEmail)): ?>
        <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($storeEmail); ?></p>
    <?php endif; ?>
    <?php if (!empty($storePhone)): ?>
        <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($storePhone); ?></p>
    <?php endif; ?>
</div>
```

#### Change 3: Update footer contact info (lines 549-551)

**BEFORE:**
```php
<div class="contact">
    <?php if (!empty($companyPhone)): ?><span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($companyPhone); ?></span><?php endif; ?>
    <?php if (!empty($companyEmail)): ?><span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($companyEmail); ?></span><?php endif; ?>
</div>
```

**AFTER:**
```php
<div class="contact">
    <?php if (!empty($storeEmail)): ?><span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($storeEmail); ?></span><?php endif; ?>
    <?php if (!empty($storePhone)): ?><span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($storePhone); ?></span><?php endif; ?>
</div>
```

---

## Success Criteria

1. ✅ Company name pulls from `store_name` setting (not hardcoded)
2. ✅ Address displays as separate lines (parsed from `store_address`)
3. ✅ Email appears before phone number
4. ✅ All fields pull from correct setting keys:
   - `store_name` (not `site_name`)
   - `store_address` (not `address`)
   - `store_email` (not `contact_email`)
   - `store_phone` (not `phone`)
5. ✅ No hardcoded values like "CannaBuddy" or "123 Cannabis Street"
6. ✅ Changes apply to both view page and print invoice

## Testing Plan

1. **Verify Settings Data**: Check admin settings page has correct data
2. **Test View Page**: Load order view page, verify company info displays correctly
3. **Test Print Page**: Load print invoice, verify company info displays correctly
4. **Test Empty Settings**: Test with empty settings to verify fallbacks work
5. **Test Multiline Address**: Test address with multiple lines

## Files to Modify

1. `admin/orders/view/index.php` - Orders view page
2. `admin/orders/view/print.php` - Print invoice page

## Files to Reference

1. `admin/settings/index.php` - To verify correct setting keys

## Notes

- Address is stored as textarea in settings, can contain newlines
- Use `explode("\n", ...)` to split address into lines
- Use `array_filter()` to remove empty lines
- Email must appear BEFORE phone in output
- No hardcoded company values anywhere

---

## Implementation Complete

### Date Completed: 2025-12-26

### Changes Made

#### File 1: `admin/orders/view/index.php`
**Changes:**
1. Updated settings query (lines 127-133):
   - Changed `$siteName` → `$storeName` (uses `store_name` setting)
   - Changed `$companyAddress` → `$storeAddress` (uses `store_address` setting)
   - Added `$storeEmail` (uses `store_email` setting)
   - Changed `$companyPhone` → `$storePhone` (uses `store_phone` setting)

2. Updated invoice header HTML (lines 440-450):
   - Replaced `nl2br()` with parsed address lines using `explode("\n", ...)`
   - Added email display BEFORE phone number
   - Each address line displays as separate `<p>` tag

#### File 2: `admin/orders/view/print.php`
**Changes:**
1. Updated settings query (lines 52-57):
   - Changed `$siteName` → `$storeName` (uses `store_name` setting)
   - Changed `$companyAddress` → `$storeAddress` (uses `store_address` setting)
   - Changed `$companyEmail` → `$storeEmail` (uses `store_email` setting)
   - Changed `$companyPhone` → `$storePhone` (uses `store_phone` setting)

2. Updated company-details header (lines 422-435):
   - Replaced `nl2br()` with parsed address lines
   - Email displays BEFORE phone
   - Each address line displays as separate `<p>` tag

3. Updated footer contact info (lines 551-557):
   - Email displays BEFORE phone
   - Uses correct variable names

### Results

✅ **All hardcoded values removed**
- No more "CannaBuddy" hardcoded
- No more "123 Cannabis Street" hardcoded
- No more "+1 (555) 123-4567" hardcoded

✅ **Correct setting keys used**
- `store_name` (not `site_name`)
- `store_address` (not `address`)
- `store_email` (not `contact_email`)
- `store_phone` (not `phone`)

✅ **Address formatting corrected**
- Address displays as separate lines (parsed from textarea)
- Email appears BEFORE phone number
- Clean, professional layout

✅ **PHP syntax validated**
- `admin/orders/view/index.php` - No errors
- `admin/orders/view/print.php` - No errors

### Testing Recommendations

1. Visit admin settings page (`/admin/settings/`) and ensure all fields are filled:
   - Store Name
   - Store Email
   - Store Phone
   - Store Address (enter as multiple lines for testing)

2. Test order view page:
   - Navigate to `/admin/orders/view/{id}`
   - Verify company info displays correctly
   - Check address shows as separate lines
   - Verify email appears before phone

3. Test print invoice:
   - Click "Print Invoice" button
   - Verify company info displays correctly in print view
   - Check footer contact info order

### Example Output Structure

With settings:
```
Store Name: My E-Commerce Store
Store Address:
  123 Main Street
  Suburbville
  Gauteng, 1234
Store Email: info@mystore.com
Store Phone: +27 12 345 6789
```

Invoice displays as:
```
My E-Commerce Store
123 Main Street
Suburbville
Gauteng, 1234
info@mystore.com
+27 12 345 6789
```
