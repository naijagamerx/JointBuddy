# Plan: Optional Email & Phone for Manual Order Creation

## Problem Statement
Manual order creation (POS-style) is too restrictive:
- Currently requires email (but walk-in customers may not have one)
- Currently requires phone (but may not always be available)
- Creates unnecessary friction for cash/in-person sales

## Use Cases
1. **Walk-in cash customer**: No email, no phone needed, just name and items
2. **Phone order**: Have name + phone, no email
3. **Email order**: Have name + email, no phone
4. **Complete info**: Have name + email + phone
5. **Minimal info**: Only first name + items

## Important: Order ≠ User Account
- Creating an order does NOT create a user account
- Order is linked to existing user ONLY if found by searchCustomer()
- Otherwise `user_id` = NULL, just customer info stored in order

## Field Requirements (NEW)

| Field | Required? | Notes |
|-------|-----------|-------|
| First Name | ✅ YES | Minimum identification for invoice |
| Last Name | ❌ NO | Optional |
| Email | ❌ NO | Optional, no validation if empty |
| Phone | ❌ NO | Optional |
| Shipping Address | ❓ Depends | Required if shipping physical items |

## Changes Required

### 1. Database Migration
```sql
-- Make customer_email nullable (currently NOT NULL)
ALTER TABLE orders
MODIFY COLUMN customer_email VARCHAR(255) NULL;

-- Phone is already NULL in database, no change needed
-- customer_name will store "First Name" or "First Last" if provided
```

### 2. Form Validation Changes

**File: `admin/orders/create/process.php`**

REMOVE these validations:
```php
// ❌ Remove lines 100-109
if (empty($data['customer_email'])) {
    $_SESSION['error'] = 'Email is required.';
    redirect('/admin/orders/create/');
}

if (!filter_var($data['customer_email'], FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Please enter a valid email address.';
    redirect('/admin/orders/create/');
}
```

ADD conditional validation (only validate if provided):
```php
// ✅ Add conditional email validation
if (!empty($data['customer_email']) && !filter_var($data['customer_email'], FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Please enter a valid email address or leave blank.';
    redirect('/admin/orders/create/');
}
```

UPDATE name validation:
```php
// ✅ First name required, last name optional
if (empty($data['customer_first_name'])) {
    $_SESSION['error'] = 'First name is required.';
    redirect('/admin/orders/create/');
}
// ❌ Remove last name validation
```

UPDATE customer_name construction:
```php
// ✅ Handle optional last name
$customerName = trim($data['customer_first_name'] . ' ' . ($data['customer_last_name'] ?? ''));
```

### 3. Form UI Changes

**File: `admin/orders/create/index.php`**

Line 119: Remove `required` from email input
```html
<!-- Before -->
<input type="email" id="email" name="email" required

<!-- After -->
<input type="email" id="email" name="email"
```

Line 112: Remove `required` from phone input (if present)
```html
<!-- Before -->
<input type="tel" id="phone" name="phone" required

<!-- After -->
<input type="tel" id="phone" name="phone"
```

Line 134: Make last name optional
```html
<!-- Before -->
<input type="text" id="last_name" name="last_name" required

<!-- After -->
<input type="text" id="last_name" name="last_name"
```

UPDATE labels to show optional:
```html
<label class="block text-sm font-medium text-gray-700 mb-1">
    Email Address <span class="text-gray-400 font-normal">(optional)</span>
</label>

<label class="block text-sm font-medium text-gray-700 mb-1">
    Phone Number <span class="text-gray-400 font-normal">(optional)</span>
</label>

<label class="block text-sm font-medium text-gray-700 mb-1">
    Last Name <span class="text-gray-400 font-normal">(optional)</span>
</label>
```

### 4. Display Changes (Admin Views)

**Files affected:**
- `admin/orders/view/index.php`
- `admin/orders/view/templates/*.php`
- `admin/qr-codes/scans.php`

Update email display:
```php
// Instead of:
echo htmlspecialchars($order['customer_email']);

// Use:
echo !empty($order['customer_email'])
    ? htmlspecialchars($order['customer_email'])
    : '<span class="text-gray-400">Not provided</span>';
```

Update phone display:
```php
// Instead of:
echo htmlspecialchars($order['customer_phone']);

// Use:
echo !empty($order['customer_phone'])
    ? htmlspecialchars($order['customer_phone'])
    : '<span class="text-gray-400">Not provided</span>';
```

### 5. OrderService Changes

**File: `includes/order_service.php`**

No changes needed! The `searchCustomer()` function already handles this:
- Searches by email OR phone
- Returns NULL if not found
- `user_id` becomes NULL in order (which is fine)

## Validation Summary

| Scenario | First Name | Last Name | Email | Phone | Result |
|----------|------------|-----------|-------|-------|--------|
| Walk-in cash | ✅ Required | Optional | Optional | Optional | ✅ Creates order |
| Phone order | ✅ Required | Optional | Optional | ✅ Provided | ✅ Creates order |
| Email order | ✅ Required | Optional | ✅ Valid | Optional | ✅ Creates order |
| Full info | ✅ Required | ✅ Provided | ✅ Valid | ✅ Provided | ✅ Creates order |
| No name | ❌ Missing | Optional | Optional | Optional | ❌ Error: "First name required" |

## Benefits

1. ✅ **Flexibility**: Create orders with minimal customer info
2. ✅ **POS-style**: Walk-in cash sales handled easily
3. ✅ **No breaking changes**: Existing orders untouched
4. ✅ **No user creation**: Orders independent of user accounts
5. ✅ **Invoice generation**: Still works with just first name

## Testing Checklist

- [ ] Walk-in customer: Only first name provided
- [ ] Phone order: First name + phone (no email)
- [ ] Email order: First name + email (no phone)
- [ ] Full info: All fields provided
- [ ] Invalid email: Shows error if invalid format
- [ ] Empty email: Accepted (no error)
- [ ] Empty phone: Accepted (no error)
- [ ] Invoice displays correctly with minimal info
- [ ] Existing orders still display correctly
