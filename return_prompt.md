# Return System Documentation - Complete Overview

## System Overview
This document provides comprehensive documentation of the current return management system in CannaBuddy.shop, covering both admin and user-facing return functionality.

## Current Return System Structure

### Admin Return Pages

#### 1. `/admin/returns/index.php`
**Purpose**: Main admin dashboard for managing all customer return requests

**Key Features**:
- **Status Overview Cards**: Shows counts for Pending, Approved, Received, and Refunded returns
- **Search & Filter**: Search by return number, order number, customer name/email + filter by status
- **Returns Table**: Lists all returns with customer info, order number, reason, status, date
- **Quick Actions**: View details link for each return
- **Settings Link**: Direct link to return settings page

**Database Queries**:
```sql
-- Get returns with customer and order info
SELECT r.*, o.order_number, u.name as customer_name, u.email as customer_email
FROM returns r
JOIN orders o ON r.order_id = o.id
JOIN users u ON r.user_id = u.id
WHERE 1=1 [optional filters]

-- Get status counts
SELECT status, COUNT(*) as count FROM returns GROUP BY status
```

**Status Flow**: pending → approved → received → refunded (with rejected/cancelled as terminal states)

---

#### 2. `/admin/returns/view.php`
**Purpose**: Detailed view and management of individual return requests

**Key Features**:
- **Return Information**: Customer details, order number, reason, product condition
- **Items Table**: Lists products being returned with quantities and prices
- **Return Method**: Shows courier collection vs drop-off preference
- **Status Management**: Admin can update status with notes
- **Customer Info**: Contact details and shipping address
- **Timeline**: Shows return submission and status updates
- **Quick Actions**: View original order, email customer

**Status Update Logic**:
- Pending → Approved/Rejected
- Approved → Received
- Received → Refunded
- Cannot update if already refunded or cancelled

**Database Operations**:
```sql
-- Get return details
SELECT r.*, o.order_number, o.shipping_address, o.shipping_city, o.shipping_postal_code, o.shipping_phone,
       u.name as customer_name, u.email as customer_email, u.phone as customer_phone
FROM returns r
JOIN orders o ON r.order_id = o.id
JOIN users u ON r.user_id = u.id
WHERE r.id = ?

-- Get return items
SELECT ri.*, oi.unit_price, p.name as product_name, p.images as product_images
FROM return_items ri
JOIN order_items oi ON ri.order_item_id = oi.id
LEFT JOIN products p ON oi.product_id = p.id
WHERE ri.return_id = ?

-- Update status
UPDATE returns SET status = ?, admin_notes = ?, updated_at = NOW() WHERE id = ?
```

---

#### 3. `/admin/settings/returns.php`
**Purpose**: Configure return policy and processing settings

**Key Features**:
- **Eligibility Windows**: Standard return window (14 days) + damaged delivery window (30 days)
- **Return Methods**: Enable/disable drop-off and courier collection
- **Policy Text**: Customizable return policy displayed to customers
- **Live Preview**: Shows how policy appears to customers

**Settings Stored in Database**:
- `return_eligibility_days` (returns category)
- `damaged_delivery_window` (returns category)
- `allow_courier_service` (returns category)
- `allow_drop_off` (returns category)
- `return_policy_text` (returns category)

---

### User Return Pages

#### 1. `/user/returns/index.php`
**Purpose**: User dashboard for viewing returns and eligible orders

**Key Features**:
- **Return History**: Shows all user's return requests with status tabs (All, Pending, Approved, Completed)
- **Eligible Orders**: Lists delivered orders within 14-day window that don't have active returns
- **Return Policy**: Displays policy information
- **Status Cards**: Each return shows status badge, reason, refund amount, order number
- **Quick Actions**: View return details, log new return

**Eligibility Logic**:
```sql
-- Find eligible orders
SELECT o.*, 
       DATEDIFF(NOW(), o.updated_at) as days_since_delivery,
       CASE 
           WHEN o.status = 'delivered' AND DATEDIFF(NOW(), o.updated_at) <= 14 THEN 1
           ELSE 0
       END as eligible_for_return,
       (SELECT COUNT(*) FROM returns r WHERE r.order_id = o.id AND r.status != 'cancelled') as has_active_return
FROM orders o
WHERE o.user_id = ? AND o.status IN ('delivered', 'shipped')
```

---

#### 2. `/user/returns/eligibility.php`
**Purpose**: Check if specific order item is eligible for return

**Key Features**:
- **Eligibility Check**: Validates 14-day window and no active returns
- **Product Display**: Shows product image, name, SKU, pricing
- **Time Remaining**: Shows days remaining for return
- **Accessories**: Lists product accessories to be returned
- **Return Policy**: Displays relevant policy points
- **Continue to Form**: Links to request.php if eligible

**Validation Steps**:
1. Check order exists and belongs to user
2. Verify order status is 'delivered'
3. Check delivery date is within 14 days
4. Ensure no active return exists for this order
5. Verify item exists in the order

---

#### 3. `/user/returns/request.php`
**Purpose**: Form to submit a new return request

**Key Features**:
- **Product Details**: Shows item being returned with image and pricing
- **Reason Selection**: Multiple choice for return reason
- **Condition**: Checkbox for product unused/unopened
- **Details**: Text area for additional information
- **Return Method**: Radio buttons for courier vs drop-off (based on admin settings)
- **Accessories**: Lists items that must be returned with product

**Return Reasons**:
- damaged: "Product delivered in bad/damaged condition"
- not_working: "Product doesn't work"
- not_as_described: "Product not as described"
- changed_mind: "Changed my mind"
- other: "Other"

**Database Insert**:
```sql
-- Create return
INSERT INTO returns (user_id, order_id, return_number, reason_type, reason_details, product_not_used, courier_method, total_amount, status, created_at)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())

-- Create return items
INSERT INTO return_items (return_id, order_item_id, quantity, price, reason_type)
VALUES (?, ?, ?, ?, ?)
```

---

#### 4. `/user/returns/view.php`
**Purpose**: Detailed view of a single return request

**Key Features**:
- **Return Header**: Return number, status badge, request date
- **Items List**: Products being returned with images, quantities, prices
- **Return Information**: Reason, condition, method
- **Order Information**: Order number, shipping address
- **Timeline**: Visual timeline of return process
- **Help Section**: Contact support + cancel return (if pending)

**Status Labels**:
- pending: "Pending Review"
- approved: "Approved"
- received: "Item Received"
- refunded: "Refunded"
- rejected: "Rejected"
- cancelled: "Cancelled"

---

#### 5. `/user/returns/confirmation.php`
**Purpose**: Confirmation page after submitting return request

**Features**:
- Shows return number
- Confirms submission
- Links to view return details
- Shows next steps

---

#### 6. `/user/returns/courier.php`
**Purpose**: Handles courier collection scheduling for returns

**Features**:
- Validates return exists and belongs to user
- Schedules courier pickup
- Updates return status
- Shows confirmation

---

## Database Schema

### Returns Table
```sql
CREATE TABLE returns (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    order_id INT NOT NULL,
    return_number VARCHAR(50) UNIQUE,
    reason_type ENUM('damaged', 'not_working', 'not_as_described', 'changed_mind', 'other'),
    reason_details TEXT,
    product_not_used BOOLEAN DEFAULT FALSE,
    courier_method ENUM('courier', 'drop_off'),
    total_amount DECIMAL(10,2),
    status ENUM('pending', 'approved', 'received', 'refunded', 'rejected', 'cancelled') DEFAULT 'pending',
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (order_id) REFERENCES orders(id)
);
```

### Return Items Table
```sql
CREATE TABLE return_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    return_id INT NOT NULL,
    order_item_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    reason_type VARCHAR(50),
    FOREIGN KEY (return_id) REFERENCES returns(id) ON DELETE CASCADE,
    FOREIGN KEY (order_item_id) REFERENCES order_items(id)
);
```

### Settings Table (Returns Category)
```sql
INSERT INTO settings (setting_key, setting_value, category) VALUES
('return_eligibility_days', '14', 'returns'),
('damaged_delivery_window', '30', 'returns'),
('allow_courier_service', '1', 'returns'),
('allow_drop_off', '1', 'returns'),
('return_policy_text', 'Your return policy text here', 'returns');
```

---

## User Flow

### Customer Return Process
1. **Browse Returns**: User visits `/user/returns/`
2. **Check Eligibility**: System shows eligible orders within 14 days
3. **Select Item**: User clicks "Log Return" on eligible item
4. **Verify Eligibility**: System validates at `/user/returns/eligibility.php`
5. **Submit Request**: User fills out form at `/user/returns/request.php`
6. **Choose Method**: Select courier or drop-off (if enabled)
7. **Confirmation**: View confirmation page
8. **Track Status**: Monitor return status in dashboard
9. **Receive Refund**: Once processed and refunded

### Admin Management Process
1. **Monitor Returns**: View all returns in `/admin/returns/`
2. **Filter & Search**: Use filters to find specific returns
3. **Review Details**: Click to view return details at `/admin/returns/view.php`
4. **Update Status**: Approve, reject, mark as received, process refund
5. **Add Notes**: Document decisions and communications
6. **Configure Settings**: Adjust policy at `/admin/settings/returns.php`

---

## Status Flow Diagram

```
User submits → pending → approved → received → refunded
                ↓         ↓
              rejected  cancelled
```

**Terminal States**: refunded, rejected, cancelled
**Active States**: pending, approved, received

---

## Key Business Rules

1. **Eligibility Window**: 14 days from delivery date
2. **Damaged Products**: May have extended 30-day window
3. **Active Returns**: Only one active return per order allowed
4. **Product Condition**: Must specify if unused/unopened
5. **Return Methods**: Configurable by admin (courier/drop-off)
6. **Refund Processing**: 5-7 business days after receipt
7. **Status Updates**: Require admin notes for tracking
8. **Cancellation**: Only allowed while status is "pending"

---

## Integration Points

- **Orders**: Links to order items and order details
- **Products**: Retrieves product images, names, prices
- **Users**: Customer contact and shipping information
- **Settings**: Return policy configuration
- **Email**: Potential integration for notifications (not implemented)

---

## Files to Delete

### User Return Pages (6 files):
1. `/user/returns/index.php`
2. `/user/returns/eligibility.php`
3. `/user/returns/request.php`
4. `/user/returns/view.php`
5. `/user/returns/confirmation.php`
6. `/user/returns/courier.php`

### Admin Return Pages (3 files):
1. `/admin/returns/index.php`
2. `/admin/returns/view.php`
3. `/admin/settings/returns.php`

### Total: 9 files to be deleted

---

## Notes for Reimplementation

When rebuilding this system, consider:
- Better error handling and validation
- Enhanced email notifications
- Bulk status updates
- Return shipping label generation
- Refund integration with payment processors
- Return tracking timeline improvements
- Mobile-responsive design enhancements
- Return reason analytics
- Automated return approval for certain conditions
- Integration with inventory management for received returns