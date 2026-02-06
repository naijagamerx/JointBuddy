# Returns System Implementation Plan

## Overview
Build a complete product returns system with 14-day eligibility window, return request forms, and admin controls.

## Database Schema

### Table: `returns`
```sql
CREATE TABLE returns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_id INT NOT NULL,
    return_number VARCHAR(50) UNIQUE NOT NULL,
    reason_type ENUM('damaged', 'not_working', 'not_as_described', 'changed_mind', 'other') NOT NULL,
    reason_details TEXT,
    product_not_used TINYINT(1) DEFAULT 0,
    status ENUM('pending', 'approved', 'rejected', 'received', 'refunded', 'cancelled') DEFAULT 'pending',
    courier_method ENUM('drop_off', 'courier') NULL,
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_order_id (order_id),
    INDEX idx_status (status)
);
```

### Table: `return_items`
```sql
CREATE TABLE return_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    return_id INT NOT NULL,
    order_item_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_return_id (return_id)
);
```

### Settings Added
- `return_eligibility_days` (default: 14)
- `return_policy_text` (textarea)
- `damaged_delivery_window` (default: 30)
- `allow_courier_service` (boolean)
- `allow_drop_off` (boolean)

---

## User Pages

### 1. `user/returns/index.php` - Returns Dashboard
**Shows:**
- All eligible products (14-day window from delivered orders)
- "Log Return" button next to each eligible product
- Tabs: Eligible Returns | Pending Returns | Completed Returns

### 2. `user/returns/request.php` - Return Form
**Form Fields:**
- Return Reason (radio buttons): damaged, not_working, not_as_described, changed_mind, other
- Detailed Reason (textarea)
- Product selection with quantities
- Acknowledgment checkboxes

### 3. `user/returns/courier.php` - Courier Selection
**Options:**
- Drop-off at store location
- Courier collection (if enabled in admin)

### 4. `user/returns/confirmation.php` - Return Details
**Shows:**
- Return status and progress timeline
- Items to return
- Return reason
- Actions: Cancel return, Select return method

---

## Admin Pages

### 1. `admin/returns/index.php` - Returns List
- Filter by status, date range
- Search by return number, order number, customer
- View return details

### 2. `admin/returns/view.php` - Return Details
- Customer info, order items, return reason
- Update status: Approve, Reject, Mark Received, Process Refund
- Add admin notes

### 3. `admin/settings/returns.php` - Returns Settings
- Set eligibility days (standard & damaged delivery)
- Enable/disable courier options
- Edit return policy text

---

## Implementation Status: COMPLETED

All files created and verified:

### User Pages
- [x] `user/returns/index.php` - Returns dashboard with tabs
- [x] `user/returns/request.php` - Return request form
- [x] `user/returns/courier.php` - Courier selection
- [x] `user/returns/confirmation.php` - Return details

### Admin Pages
- [x] `admin/returns/index.php` - Returns list
- [x] `admin/returns/view.php` - Return details + actions
- [x] `admin/settings/returns.php` - Settings page

### Database
- [x] `returns` table created
- [x] `return_items` table created
- [x] Settings added to `settings` table

### Other
- [x] Sidebar link updated with orange theme

*Plan created: 2025-12-26*
*Status: COMPLETED*
