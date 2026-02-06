# Order Tracking Page Enhancement Plan

## Current State Analysis

**File**: `user/orders/track.php`

**Current Flow:**
1. If no order ID/number provided → shows search form + recent 5 orders
2. If order found → shows full tracking details
3. If order not found → shows error + search form

**Issues:**
- Only shows 5 recent orders (line 186)
- No "View All Orders" link to see complete order history
- User has to remember order number to search

---

## Proposed Enhancement

### Goal
Enable users to track ANY of their orders easily, either by:
1. Searching by order number
2. Browsing and selecting from their order history

### New Page Structure

#### Section 1: Order Search (Always Visible)
- Prominent search box
- Clear placeholder with example format
- Error message when order not found

#### Section 2: Current Order Tracking (When order selected)
- Show tracking timeline
- Show order items with images
- Show order summary

#### Section 3: All Orders List (Always Visible below search)
- Show ALL user orders (not just 5)
- Include pagination or "Load More"
- Each order shows: order number, date, status, amount
- "Track" button for each order
- Highlight currently tracked order

---

## Database Queries Needed

### Query 1: Get All User Orders (with pagination)
```sql
SELECT id, order_number, status, payment_status, created_at, total_amount
FROM orders
WHERE user_id = ?
ORDER BY created_at DESC
LIMIT ? OFFSET ?
```

### Query 2: Get Single Order Details (existing - already implemented)
```sql
SELECT * FROM orders WHERE id = ? AND user_id = ?
SELECT * FROM order_items WHERE order_id = ?
SELECT * FROM order_status_history WHERE order_id = ?
```

---

## UI Changes

### Search Section
```
┌─────────────────────────────────────┐
│  Track Your Order                   │
│  ┌─────────────────────────────┐    │
│  │ Enter order number        │    │
│  │ e.g., ORD-123456          │    │
│  └─────────────────────────────┘    │
│  [Track Order]                      │
└─────────────────────────────────────┘
```

### Orders List Section
```
┌─────────────────────────────────────┐
│  Your Orders (15 total)             │
│                                     │
│  ┌─── Order #123456 ───┐ [Track]   │
│  │ Dec 25, 2024 | R150.00          │
│  │ On The Way                     │
│  └───────────────────────┘         │
│                                     │
│  ┌─── Order #123455 ───┐ [Track]   │
│  │ Dec 24, 2024 | R89.99           │
│  │ Delivered                      │
│  └───────────────────────┘         │
│                                     │
│  [View All Orders →]               │
└─────────────────────────────────────┘
```

### Tracking Section (when order selected)
```
┌─────────────────────────────────────┐
│  ← Back to Orders                   │
│                                     │
│  ┌─────────────────────────────┐    │
│  │ Order #123456               │    │
│  │ [Status Badge]              │    │
│  └─────────────────────────────┘    │
│                                     │
│  [Vertical Timeline]                │
│                                     │
│  ┌─────────────────────────────┐    │
│  │ Order Items (3)             │    │
│  │ [Img] Product 1    R50.00   │    │
│  │ [Img] Product 2    R50.00   │    │
│  │ [Img] Product 3    R50.00   │    │
│  │ Total: R150.00              │    │
│  └─────────────────────────────┘    │
└─────────────────────────────────────┘
```

---

## Implementation Steps

### Step 1: Update Order List Query
- Change LIMIT 5 to higher number (e.g., 20)
- Add pagination support

### Step 2: Add "View All Orders" Link
- Link to `user/orders/` for complete list
- Or implement pagination on this page

### Step 3: Highlight Active Order
- If `$_GET['id']` matches an order in the list, highlight it
- Add visual indicator (border, background color)

### Step 4: Improve Search UX
- Add autocomplete from user's orders
- Show order status in search results

---

## Files to Modify

| File | Changes |
|------|---------|
| `user/orders/track.php` | 1. Fetch all orders (not just 5) |
| | 2. Add order list even when tracking an order |
| | 3. Highlight active order in list |
| | 4. Improve search functionality |

---

## URL Structure

```
/user/orders/track/              → Show search + all orders
/user/orders/track/?id=8         → Show search + all orders + tracking for order #8
/user/orders/track/?order_number= → Search by order number
```

---

## Back Button Flow

When viewing order details:
- "← Back to Orders" should go to `user/orders/track/` (show all orders list)
- User can then click on any order to track it

---

## Testing Scenarios

1. [ ] User with no orders → show empty state
2. [ ] User with 1-5 orders → show all orders
3. [ ] User with 20+ orders → show first 20 with pagination
4. [ ] Search with valid order number → show tracking
5. [ ] Search with invalid order number → show error + orders list
6. [ ] Click "Track" on order → show tracking for that order
7. [ ] Active order highlighted in list

---

*Plan created: 2025-12-26*
*Status: Ready for implementation*
