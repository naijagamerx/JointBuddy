# User Dashboard Fixes Implementation Plan

## Overview
Fix layout inconsistencies and remove hardcoded data across user dashboard pages (orders, invoices, reviews) and add order tracking functionality.

## Database Tables Analyzed

### `reviews` Table
- `id`, `customer_email`, `customer_name`, `product_id`, `product_name`
- `rating` (1-5), `title`, `comment`
- `is_approved` (0/1), `helpful_count`, `created_at`, `updated_at`

### `orders` Table
- `id`, `user_id`, `order_number`, `customer_email`, `customer_name`
- `status` (new, pending, approved, preparing, ready, on_the_way, delivered, rejected)
- `payment_status`, `payment_method`, `subtotal`, `shipping_amount`, `total_amount`
- `shipping_address` (JSON), `created_at`, `updated_at`

### `order_items` Table
- `id`, `order_id`, `product_id`, `product_name`, `product_sku`
- `quantity`, `unit_price`, `total_price`

---

## Task 1: Fix Order Details View Layout

### File: `user/orders/view.php`

**Issues Found:**
- Uses `../components/header.php` instead of `../../includes/header.php`
- Uses `../components/footer.php` instead of `../../includes/footer.php`
- Missing welcome back card
- Missing sidebar component

**Changes Required:**
1. Change header include to `../../includes/header.php`
2. Change footer include to `../../includes/footer.php`
3. Add welcome back card (like orders listing page)
4. Add sidebar component: `<?php include __DIR__ . '/../components/sidebar.php'; ?>`
5. Maintain 2-column grid layout (main content + sidebar)

**Layout Pattern to Follow:**
```php
<div class="container mx-auto px-4 py-6">
    <!-- Welcome Back Card -->
    <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow-md text-white p-4 mb-6">
        ...
    </div>

    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../components/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="lg:w-3/4">
            ...
        </div>
    </div>
</div>
```

---

## Task 2: Add Dynamic Invoice Data

### File: `user/invoices/index.php`

**Issues Found:**
- Hardcoded sample invoices (INV-2024-001, etc.)
- Static statistics (5 invoices, 3 this year, 2 last year)
- Non-functional buttons

**Changes Required:**
1. Fetch real orders for the logged-in user from `orders` table
2. Query format: `SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC`
3. Display order number, date, status, payment status, total
4. Add View/Download buttons linking to invoice print page
5. Calculate dynamic statistics
6. Add filter tabs (All, This Year, Last Year)

**Invoice Print URL Pattern:**
```
user/invoices/view.php?id={order_id}
```

**New File to Create: `user/invoices/view.php`**
- Reuse invoice template from `admin/orders/view/templates/default.php`
- Modify to work for user-facing context
- Add view/download functionality

---

## Task 3: Add Dynamic Review Data

### File: `user/reviews/index.php`

**Issues Found:**
- Hardcoded review statistics (12 total, 4.7 avg, 8 published, 4 pending)
- Hardcoded sample reviews (Premium OG Kush, CBD Gummies, etc.)
- Static star ratings and dates

**Changes Required:**
1. Fetch reviews for logged-in user:
   - `SELECT * FROM reviews WHERE customer_email = ? ORDER BY created_at DESC`
2. Calculate dynamic statistics:
   - Total reviews count
   - Average rating (AVG of rating column)
   - Published count (WHERE is_approved = 1)
   - Pending count (WHERE is_approved = 0)
3. Replace hardcoded review cards with loop over real data
4. Display product image placeholder (or from products table)
5. Show dynamic star rating
6. Use `created_at` for review dates

**Database Query Pattern:**
```php
// Get all reviews for user
$stmt = $db->prepare("SELECT * FROM reviews WHERE customer_email = ? ORDER BY created_at DESC");
$stmt->execute([$currentUser['email']]);
$reviews = $stmt->fetchAll();

// Get statistics
$stmt = $db->prepare("SELECT COUNT(*) as total, AVG(rating) as avg_rating, SUM(is_approved=1) as published FROM reviews WHERE customer_email = ?");
$stmt->execute([$currentUser['email']]);
$stats = $stmt->fetch();
```

---

## Task 4: Create Order Tracking Page

### New File: `user/orders/track.php`

**Requirements:**
- Display detailed order tracking information
- Show order status timeline (visual progress)
- Display order status history from `order_status_history` table
- Show estimated delivery date (if available)
- Display shipping carrier information (if available)

**Order Statuses:**
- new → pending → approved → preparing → ready → on_the_way → delivered

**Features:**
1. Order lookup by order ID or order number
2. Visual status timeline (reusing order tracking code from view.php)
3. Status history table with timestamps
4. Estimated delivery date display
5. Contact support option

**Database Queries:**
```php
// Get order status history
$stmt = $db->prepare("
    SELECT h.*, a.username as admin_name
    FROM order_status_history h
    LEFT JOIN admin_users a ON h.changed_by = a.id
    WHERE h.order_id = ?
    ORDER BY h.created_at ASC
");
```

**URL Pattern:** `/user/orders/track/`

**Optional Enhancement:** Add order lookup form for non-logged-in users (guest tracking)

---

## File Changes Summary

| File | Action | Description |
|------|--------|-------------|
| `user/orders/view.php` | Modify | Fix header/footer includes, add welcome card, add sidebar |
| `user/invoices/index.php` | Modify | Replace hardcoded data with dynamic orders data |
| `user/invoices/view.php` | Create | New file - invoice view/download page |
| `user/reviews/index.php` | Modify | Replace hardcoded data with dynamic reviews data |
| `user/orders/track.php` | Create | New file - order tracking page |

---

## URL Helper Functions to Use

- `userUrl('/orders/')` - Orders listing
- `userUrl('/orders/view.php?id=' . $orderId)` - Order details
- `userUrl('/invoices/')` - Invoices listing
- `userUrl('/invoices/view.php?id=' . $orderId)` - Invoice view
- `userUrl('/reviews/')` - Reviews listing
- `userUrl('/orders/track/')` - Order tracking

---

## Testing Checklist

- [ ] Order details page shows welcome card and sidebar
- [ ] Invoices page shows real orders with View/Download buttons
- [ ] Reviews page shows real user reviews with dynamic statistics
- [ ] Order tracking page displays status timeline and history
- [ ] All pages maintain consistent layout pattern
- [ ] No hardcoded company name (CannaBuddy) in new code
- [ ] All links use url helper functions
- [ ] Responsive layout works on mobile devices

---

## Implementation Order

1. Fix order details view layout (Task 1)
2. Create order tracking page (Task 4)
3. Add dynamic invoice data (Task 2)
4. Add dynamic review data (Task 3)

---

## COMPLETED: 2025-12-26

All tasks successfully implemented:

### Task 1: Fix Order Details View Layout ✅
- Fixed header/footer includes to use proper paths
- Added welcome back card for consistent layout
- Added sidebar component
- Added order status history section
- Updated tracking timeline with all statuses

### Task 2: Dynamic Invoice System ✅
- Invoices index now fetches real orders with dynamic statistics
- Created new invoice view/download page with print functionality
- View and Download buttons for each invoice
- Filter tabs: All, This Year, Last Year
- Summary showing total invoices and total spent

### Task 3: Dynamic Review Data ✅
- Fetches real reviews from database for logged-in user
- Dynamic statistics: total reviews, average rating, published, pending
- Filter tabs: All, Published, Pending
- Shows product images when available
- Dynamic star ratings from database values

### Task 4: Order Tracking Page ✅
- Created new order tracking page
- Order lookup by order ID
- Visual tracking timeline showing order progress
- Order status history with timestamps
- Recent orders quick links

---

*Plan created: 2025-12-26*
*Status: ALL TASKS COMPLETED*
