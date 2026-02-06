# Checkout & User Experience Overhaul - Implementation Plan

## Executive Summary

This plan outlines the complete overhaul of the checkout experience, transforming it from a static form-based system into a dynamic, database-driven, user-friendly flow that adapts based on login status. The implementation includes address management, coupon system, gift messaging, and a redesigned order summary.

---

## Phase 1: Database Schema & Backend Services

### 1.1 Database Tables to Create/Modify

#### A. Create `user_addresses` table
```sql
CREATE TABLE user_addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    label VARCHAR(100) DEFAULT 'Home',
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255) DEFAULT NULL,
    city VARCHAR(100) NOT NULL,
    province VARCHAR(100) DEFAULT NULL,
    postal_code VARCHAR(20) NOT NULL,
    country VARCHAR(100) DEFAULT 'South Africa',
    address_type ENUM('residential','business') DEFAULT 'residential',
    default_for_shipping TINYINT(1) DEFAULT 0,
    delivery_instructions TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_default (user_id, default_for_shipping)
);
```

#### B. Create `coupons` table
```sql
CREATE TABLE coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    description VARCHAR(255) NOT NULL,
    discount_type ENUM('percent','fixed','free_shipping') NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL,
    min_order_amount DECIMAL(10,2) DEFAULT 0,
    max_discount_amount DECIMAL(10,2) DEFAULT NULL,
    starts_at DATETIME DEFAULT NULL,
    expires_at DATETIME DEFAULT NULL,
    usage_limit INT DEFAULT NULL,
    usage_per_user INT DEFAULT NULL,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at DATESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### C. Create `coupon_usages` table
```sql
CREATE TABLE coupon_usages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coupon_id INT NOT NULL,
    user_id INT DEFAULT NULL,
    order_id INT DEFAULT NULL,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    INDEX idx_coupon_user (coupon_id, user_id)
);
```

#### D. Modify `orders` table
```sql
ALTER TABLE orders
    ADD COLUMN coupon_code VARCHAR(50) DEFAULT NULL,
    ADD COLUMN coupon_discount DECIMAL(10,2) DEFAULT 0,
    ADD COLUMN gift_message TEXT NULL;
```

### 1.2 Backend Service Files to Create

#### A. `includes/user_addresses_service.php`
Create service with functions:
- `getUserAddresses(PDO $db, int $userId): array` - Fetch all user addresses
- `getDefaultAddress(PDO $db, int $userId): ?array` - Get default shipping address
- `saveUserAddress(PDO $db, int $userId, array $data): int` - Add new address
- `updateUserAddress(PDO $db, int $userId, int $addressId, array $data): bool` - Update address
- `deleteUserAddress(PDO $db, int $userId, int $addressId): bool` - Delete address
- `setDefaultAddress(PDO $db, int $userId, int $addressId): bool` - Set as default
- `validateAddressData(array $data): array` - Validate address fields

**Rules:**
- Only one default address per user
- All operations scoped by user_id
- Ensure data sanitization

#### B. `includes/coupons_service.php`
Create service with functions:
- `getAvailableCoupons(PDO $db, int $userId = null): array` - Fetch active coupons
- `getCouponByCode(PDO $db, string $code): ?array` - Get coupon by code
- `validateCoupon(PDO $db, array $coupon, float $subtotal, int $userId = null): array` - Validate coupon
- `calculateDiscount(array $coupon, float $subtotal, float $shipping = 0): float` - Calculate discount amount
- `applyCoupon(PDO $db, int $couponId, int $userId, int $orderId): bool` - Record coupon usage
- `getUserCouponUsage(PDO $db, int $couponId, int $userId): int` - Count user's coupon usage

**Validation Rules:**
- Check active status
- Verify date ranges (starts_at, expires_at)
- Check usage limits (total and per user)
- Verify minimum order amount
- Check free shipping eligibility

#### C. `includes/delivery_methods_service.php`
Create service with functions:
- `getActiveDeliveryMethods(PDO $db): array` - Fetch from delivery_methods table
- `getDeliveryMethodById(PDO $db, int $id): ?array` - Get specific method

---

## Phase 2: Address Book Page (`user/address-book/`)

### 2.1 Current State
- All hardcoded/static addresses
- No database integration
- Buttons don't function
- No backend storage

### 2.2 Implementation Tasks

#### A. Backend Integration
1. **Fetch user addresses** on page load using `getUserAddresses()`
2. **Handle POST actions:**
   - `address_action=add` - Save new address
   - `address_action=edit` - Update existing address
   - `address_action=delete` - Delete address
   - `address_action=set_default` - Set default address

#### B. UI Updates
1. **Replace hardcoded addresses** with dynamic rendering from DB
2. **Implement "Add New Address" modal:**
   - Form fields: first_name, last_name, phone, address_line1, address_line2, city, province, postal_code, country
   - Address label (Home, Office, etc.)
   - Address type (Residential/Business)
   - Delivery instructions
   - "Set as default" checkbox

3. **Add Edit/Delete buttons functionality:**
   - Edit: Pre-fill modal with address data
   - Delete: Confirmation dialog, then delete
   - Set as Default: Updates default flag

4. **Update stats cards:**
   - Total addresses count from DB
   - Default address count (should always be 1)
   - Delivery zones (unique cities/counties)

#### C. Validation
- Required fields: first_name, last_name, phone, address_line1, city, postal_code
- Phone number format validation
- Postal code format validation

---

## Phase 3: Coupons & Offers Page (`user/coupons-offers/`)

### 3.1 Current State
- All hardcoded/static coupons
- No database integration
- Apply buttons don't function
- No backend validation

### 3.2 Implementation Tasks

#### A. Seed Test Coupons
Insert sample coupons into database:
```sql
INSERT INTO coupons (code, description, discount_type, discount_value, min_order_amount, expires_at, active) VALUES
('WELCOME10', '10% off your first order', 'percent', 10.00, 0, '2025-12-31 23:59:59', 1),
('FREESHIP', 'Free shipping on orders over R200', 'free_shipping', 0, 200.00, '2025-11-30 23:59:59', 1),
('CANNABIS15', '15% off premium strains', 'percent', 15.00, 0, '2025-12-15 23:59:59', 1),
('NEWBIE20', '20% off edibles', 'percent', 20.00, 0, '2025-12-10 23:59:59', 1);
```

#### B. Backend Integration
1. **Fetch available coupons** on page load using `getAvailableCoupons()`
2. **Handle Apply button:**
   - Set session variable: `$_SESSION['selected_coupon'] = $code`
   - Show success message
   - Option: redirect to checkout automatically or let user continue browsing

3. **Show "Recently Used" coupons:**
   - Query `coupon_usages` table joined with `coupons`
   - Show code, used date, and discount amount

#### C. UI Updates
1. **Replace hardcoded coupons** with dynamic DB-driven rendering
2. **Show coupon details:**
   - Code
   - Description
   - Discount type and value
   - Expiry date
   - Minimum order amount (if applicable)
   - Usage limit status

3. **Update "Apply" buttons:**
   - On click: AJAX POST to save coupon to session
   - Show loading state
   - Update button to "Applied" state with checkmark

---

## Phase 4: Checkout Page Overhaul (`checkout/`)

### 4.1 Remove Hardcoded Fallbacks

#### A. Delivery Methods
**Current code (lines 56-62):**
```php
// Fallback delivery methods if none in DB
if (empty($deliveryMethods)) {
    $deliveryMethods = [
        ['id' => 1, 'name' => 'Standard Shipping', 'description' => '5-7 business days', 'cost' => 50.00],
        ['id' => 2, 'name' => 'Express Shipping', 'description' => '2-3 business days', 'cost' => 99.00],
        ['id' => 3, 'name' => 'Overnight Delivery', 'description' => 'Next business day', 'cost' => 149.00]
    ];
}
```

**Action:** Remove this entire fallback block. Always require delivery methods from database.

#### B. Payment Methods
**Current code (lines 94-101):**
```php
// Fallback payment methods
if (empty($paymentMethods)) {
    $paymentMethods = [
        ['id' => 1, 'name' => 'Credit/Debit Card', 'type' => 'payfast', 'description' => 'Pay securely with PayFast'],
        ['id' => 2, 'name' => 'Bank Transfer (EFT)', 'type' => 'bank_transfer', 'description' => 'Manual bank transfer'],
        ['id' => 3, 'name' => 'Cash on Delivery', 'type' => 'cash_on_delivery', 'description' => 'Pay when you receive']
    ];
}
```

**Action:** Remove this fallback. If no payment methods in DB, show error message.

### 4.2 Logged-In User Experience

#### A. Hide Contact Information Forms
**Current:** Always shows contact info forms (lines 275-323)
**New behavior:** If `$isLoggedIn`, show summary instead

**Implementation:**
```php
<?php if (!$isLoggedIn): ?>
    <!-- Existing contact form -->
<?php else: ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
            <span class="w-8 h-8 rounded-full bg-green-100 text-green-600 flex items-center justify-center mr-3 text-sm font-bold">1</span>
            Contact Information
        </h2>
        <div class="flex items-center justify-between">
            <div>
                <p class="font-medium text-gray-900"><?= htmlspecialchars($currentUser['name']) ?></p>
                <p class="text-sm text-gray-600"><?= htmlspecialchars($currentUser['email']) ?></p>
                <a href="<?= userUrl('personal-details/') ?>" class="text-green-600 text-sm hover:text-green-700">Edit Details</a>
            </div>
        </div>
    </div>
<?php endif; ?>
```

#### B. Show Saved Address with "Change" Option
**Location:** Replace shipping address form (lines 326-373)

**Implementation:**
1. Fetch default address using `getDefaultAddress()`
2. If address exists, show formatted summary:
```
Residential
41 Manor Park
26 Pongola Avenue
Randpark Ridge, Randburg, 2169
Garry Collins 0846114757
```

3. Add "Change" link that opens modal with all user addresses
4. Hidden fields to send address data in form POST
5. If no address exists, show full address form with option to save

**Modal for selecting address:**
- List all user addresses
- Show each address formatted
- Radio button selection
- "Use this address" button
- "Add new address" link

#### C. Delivery Method Selection
**Current:** Radio button list (lines 376-423)
**Enhancement:** Add "Change" option for logged-in users

**Implementation:**
1. Show selected delivery method prominently
2. Show "3 More Delivery Options" link
3. Clicking opens modal with all delivery methods from DB
4. Display format: "Today, 1PM - 7PM, Same Day Delivery, R 95"
5. Use `estimated_delivery_time` field from database

### 4.3 Coupon System Integration

#### A. Add Coupon Input Section
**Location:** In right column (order summary), before totals

**Implementation:**
```html
<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 mb-2">Have a coupon?</label>
    <div class="flex gap-2">
        <input type="text" name="coupon_code" id="coupon_code"
               value="<?= htmlspecialchars($_SESSION['selected_coupon'] ?? '') ?>"
               placeholder="Enter coupon code"
               class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
        <button type="button" onclick="applyCoupon()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300">
            Apply
        </button>
    </div>
    <div id="coupon-message" class="mt-2 text-sm"></div>
</div>
```

#### B. Handle Coupon on Page Load
```php
// Check for coupon in session or GET param
$appliedCoupon = null;
if (isset($_SESSION['selected_coupon'])) {
    $couponCode = $_SESSION['selected_coupon'];
    $coupon = getCouponByCode($db, $couponCode);
    if ($coupon && $coupon['active']) {
        $appliedCoupon = $coupon;
    }
}
```

#### C. Apply Coupon Discount to Totals
**Modify order creation logic (lines 117-127):**

```php
// Calculate discount
$couponDiscount = 0;
$couponCode = $_POST['coupon_code'] ?? null;
if ($couponCode) {
    $coupon = getCouponByCode($db, $couponCode);
    if ($coupon) {
        $validation = validateCoupon($db, $coupon, $subtotal, $isLoggedIn ? $_SESSION['user_id'] : null);
        if ($validation['valid']) {
            $couponDiscount = calculateDiscount($coupon, $subtotal, $shippingCost);
        }
    }
}

$totalAmount = $subtotal + $shippingCost - $couponDiscount;
```

#### D. Store Coupon in Order
**Add to order creation (line 138):**
```php
INSERT INTO orders (
    ...
    coupon_code,
    coupon_discount,
    ...
) VALUES (
    ...
    $couponCode,
    $couponDiscount,
    ...
);
```

#### E. Show Discount in Order Summary
```html
<div class="flex justify-between text-gray-600">
    <span>Subtotal (<?= $itemCount ?> items)</span>
    <span class="font-medium text-gray-900">R <?= number_format($subtotal, 2) ?></span>
</div>
<?php if ($appliedCoupon): ?>
    <div class="flex justify-between text-gray-600">
        <span>Coupon (<?= htmlspecialchars($appliedCoupon['code']) ?>)</span>
        <span class="font-medium text-green-600">-R <?= number_format($couponDiscount, 2) ?></span>
    </div>
<?php endif; ?>
```

### 4.4 Gift Messaging Feature

#### A. Add Gift Option Section
**Location:** At bottom of left column, before "Place Order" button

**Implementation:**
```html
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
        <i class="fas fa-gift text-green-600 text-xl mr-3"></i>
        Gift Option
    </h2>
    <label class="flex items-center cursor-pointer">
        <input type="checkbox" name="is_gift" id="is_gift" value="1"
               onchange="toggleGiftMessage()"
               class="text-green-600 focus:ring-green-500 rounded">
        <span class="ml-2 text-gray-700 font-medium">Is this a gift?</span>
    </label>

    <div id="gift-message-container" class="mt-4 hidden">
        <label class="block text-sm font-medium text-gray-700 mb-2">Gift Message</label>
        <textarea name="gift_message" id="gift_message" rows="4"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                  placeholder="Hi [Recipient Name],&#10;&#10;Enjoy your gift!&#10;&#10;From [Your Name]"><?php
            $senderName = $isLoggedIn ? $currentUser['name'] : '';
            echo "Hi [Recipient Name],\n\nEnjoy your gift!\n\nFrom " . htmlspecialchars($senderName);
        ?></textarea>
        <p class="text-xs text-gray-500 mt-1">You can customize this message</p>
    </div>
</div>

<script>
function toggleGiftMessage() {
    const checkbox = document.getElementById('is_gift');
    const container = document.getElementById('gift-message-container');
    if (checkbox.checked) {
        container.classList.remove('hidden');
    } else {
        container.classList.add('hidden');
    }
}
</script>
```

#### B. Store Gift Message in Order
**Modify order creation (line 167):**
```php
$giftMessage = ($_POST['is_gift'] ?? false) ? ($_POST['gift_message'] ?? '') : null;

// Then in INSERT:
$giftMessage,
```

### 4.5 Order Summary - 3x3 Image Grid

#### A. Current Implementation
**Lines 473-509:** List-style with image, name, qty, price

#### B. New Implementation - Image Grid Only

**Replace entire order summary cart items section:**
```html
<!-- Cart Items - Image Grid -->
<style>
    .order-summary-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
        max-height: 280px;
        overflow-y: auto;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }
    .order-summary-grid::-webkit-scrollbar {
        display: none;
    }
    .grid-item {
        position: relative;
        aspect-ratio: 1;
        border-radius: 8px;
        overflow: hidden;
        background: #f3f4f6;
    }
    .grid-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .qty-badge {
        position: absolute;
        bottom: 4px;
        right: 4px;
        background: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 2px 6px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
    }
</style>

<div class="mb-6">
    <?php if (count($cart) <= 9): ?>
        <div class="order-summary-grid">
            <?php foreach ($cart as $item): ?>
                <div class="grid-item">
                    <?php if (!empty($item['image'])): ?>
                        <img src="<?= htmlspecialchars($item['image']) ?>"
                             alt="<?= htmlspecialchars($item['name']) ?>"
                             loading="lazy"
                             onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'w-full h-full bg-gradient-to-br from-green-50 to-green-100 flex items-center justify-center\'><i class=\'fas fa-leaf text-green-400 text-2xl\'></i></div>';">
                    <?php else: ?>
                        <div class="w-full h-full bg-gradient-to-br from-green-50 to-green-100 flex items-center justify-center">
                            <i class="fas fa-leaf text-green-400 text-2xl"></i>
                        </div>
                    <?php endif; ?>
                    <?php if ($item['qty'] > 1): ?>
                        <div class="qty-badge">x<?= $item['qty'] ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <!-- More than 9 items: show 9 + count -->
        <div class="order-summary-grid">
            <?php
            $displayCount = 0;
            foreach ($cart as $item):
                if ($displayCount >= 9) break;
            ?>
                <div class="grid-item">
                    <?php if (!empty($item['image'])): ?>
                        <img src="<?= htmlspecialchars($item['image']) ?>" alt="" loading="lazy">
                    <?php else: ?>
                        <div class="w-full h-full bg-gradient-to-br from-green-50 to-green-100 flex items-center justify-center">
                            <i class="fas fa-leaf text-green-400 text-2xl"></i>
                        </div>
                    <?php endif; ?>
                    <?php if ($item['qty'] > 1): ?>
                        <div class="qty-badge">x<?= $item['qty'] ?></div>
                    <?php endif; ?>
                </div>
            <?php
                $displayCount++;
                endforeach;
            ?>
        </div>
        <div class="text-center mt-3 text-sm text-gray-600">
            +<?= count($cart) - 9 ?> more items
        </div>
    <?php endif; ?>
</div>
```

---

## Phase 5: JavaScript Enhancements

### 5.1 Apply Coupon AJAX
```javascript
function applyCoupon() {
    const code = document.getElementById('coupon_code').value.trim();
    const messageDiv = document.getElementById('coupon-message');

    if (!code) {
        messageDiv.innerHTML = '<span class="text-red-500">Please enter a coupon code</span>';
        return;
    }

    fetch('/user/coupons/apply/', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({coupon_code: code})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            messageDiv.innerHTML = '<span class="text-green-600">✓ ' + data.message + '</span>';
            location.reload(); // Reload to update totals
        } else {
            messageDiv.innerHTML = '<span class="text-red-500">✗ ' + data.message + '</span>';
        }
    });
}
```

### 5.2 Address Selection Modal
```javascript
function openAddressModal() {
    // Fetch user's addresses via AJAX
    // Display in modal
    // On selection, update hidden fields and display summary
}

function selectAddress(addressId, addressData) {
    // Update hidden form fields
    document.querySelector('input[name="address_id"]').value = addressId;
    document.querySelector('input[name="first_name"]').value = addressData.first_name;
    document.querySelector('input[name="last_name"]').value = addressData.last_name;
    // etc.

    // Update display summary
    updateAddressDisplay(addressData);

    // Close modal
    document.getElementById('addressModal').classList.add('hidden');
}
```

### 5.3 Delivery Method Selection Modal
```javascript
function openDeliveryModal() {
    // Fetch delivery methods from DB
    // Display in modal with pricing and estimated delivery
    // On selection, update hidden field and display summary
}
```

---

## Phase 6: Testing & Validation

### 6.1 Database Testing
1. **Verify tables created correctly**
2. **Test foreign key constraints**
3. **Test indexes on user_addresses and coupon_usages**

### 6.2 Address Book Testing
1. Add new address
2. Edit existing address
3. Delete address
4. Set default address
5. Verify only one default per user
6. Test validation (required fields)
7. Test on checkout page (address pre-filled)

### 6.3 Coupon System Testing
1. Apply valid coupon
2. Apply expired coupon
3. Apply coupon below minimum order
4. Apply coupon exceeding usage limit
5. Test percent discount calculation
6. Test fixed discount calculation
7. Test free shipping discount
8. Test max discount cap
9. Verify coupon stored in order

### 6.4 Checkout Flow Testing

#### Logged-in User:
1. Contact info hidden, only summary shown
2. Default address displayed correctly
3. "Change" address opens modal
4. Delivery method displayed with "Change" option
5. Payment method from database (not hardcoded)
6. Coupon applied successfully
7. Gift message functionality
8. Order summary shows 3x3 image grid
9. Order created with all data

#### Guest User:
1. Contact info form displayed
2. Shipping address form displayed
3. No address change options
4. All other features same as logged-in

### 6.5 Cross-Browser Testing
- Chrome
- Firefox
- Safari
- Edge
- Mobile browsers (touch scrolling, responsive grid)

### 6.6 Performance Testing
- Image lazy loading on order summary
- Modal loading times
- Coupon validation speed
- Address loading speed

---

## Implementation Timeline

### Week 1: Database & Services
- Day 1-2: Create database tables
- Day 3-4: Create user_addresses_service.php
- Day 5-7: Create coupons_service.php and delivery_methods_service.php

### Week 2: Address Book
- Day 1-3: Backend integration
- Day 4-5: UI updates and modals
- Day 6-7: Testing and bug fixes

### Week 3: Coupons
- Day 1-2: Seed test data
- Day 3-4: Backend integration
- Day 5-7: UI updates and testing

### Week 4: Checkout Overhaul
- Day 1-2: Remove hardcoded fallbacks
- Day 3-4: Logged-in user experience
- Day 5-6: Coupon integration
- Day 7: Gift messaging

### Week 5: Order Summary & Polish
- Day 1-3: 3x3 image grid
- Day 4-5: JavaScript enhancements
- Day 6-7: Cross-browser testing

---

## Files to Create

1. `includes/user_addresses_service.php` - ~200 lines
2. `includes/coupons_service.php` - ~250 lines
3. `includes/delivery_methods_service.php` - ~50 lines
4. `test_delete/migrate_user_addresses_table.php` - Migration script
5. `test_delete/migrate_coupons_tables.php` - Migration script
6. `test_delete/migrate_orders_gift_coupon.php` - Migration script

## Files to Modify

1. `user/address-book/index.php` - Complete overhaul
2. `user/coupons-offers/index.php` - Complete overhaul
3. `checkout/index.php` - Major changes (30-40% of file)
4. `includes/database.php` - Add AdminAuth, UserAuth methods if needed

## Success Criteria

- [ ] Users can save multiple addresses
- [ ] Checkout shows saved address for logged-in users
- [ ] Users can apply coupons from coupons-offers page
- [ ] Coupon discounts apply to order total
- [ ] Logged-in users see minimal checkout form
- [ ] Guest users see full checkout form
- [ ] Order summary shows only product images in 3x3 grid
- [ ] Gift messaging works and stores with order
- [ ] All delivery methods pulled from database
- [ ] All payment methods pulled from database
- [ ] No hardcoded fallbacks in checkout
- [ ] Mobile responsive across all devices
- [ ] Passes all validation tests

---

## Notes

- **No hardcoded URLs**: Always use `url()`, `userUrl()`, `adminUrl()` helpers
- **No "CannaBuddy" in UI**: Project name is confidential
- **Test files go to test_delete**: All test scripts must be in test_delete folder
- **Responsive design**: Ensure all new features work on mobile
- **Accessibility**: Alt text for images, proper contrast ratios
- **Performance**: Lazy loading for images, minimal database queries
- **Security**: Sanitize all user inputs, validate on server-side
