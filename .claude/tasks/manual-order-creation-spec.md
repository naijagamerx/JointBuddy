# Manual Order Creation - Feature Specification

## Overview
A new admin feature to manually create orders for customers who didn't purchase through the public website (e.g., cash payments, phone orders, in-person sales). The system will allow admins to enter customer details, select products, and generate invoices using the existing invoice templates.

## Business Context
Currently, orders can only be created through the public website checkout process. However, the business needs to handle:
- Cash payments at physical locations
- Phone orders where customers don't use the website
- In-person sales at events or markets
- Walk-in customers at physical stores

## Functional Requirements

### 1. Manual Order Creation Interface
**Location**: `/admin/orders/create/`

**Form Sections**:

#### A. Customer Information
- **Customer Search**: Search existing customers by email or phone
- **Or Manual Entry**:
  - First Name *
  - Last Name *
  - Email * (required for invoicing)
  - Phone (optional)

#### B. Shipping Address
- Street Address *
- City *
  - State/Province *
- Postal Code *
- Notes/Directions (optional)

#### C. Billing Address
- Same as Shipping (checkbox)
- Or manual entry:
  - Street Address *
  - City *
  - State/Province *
  - Postal Code *

#### D. Product Selection
- **Search/Add Products**:
  - Product search by name or SKU
  - Display product name, SKU, price, stock availability
  - Quantity input
  - Add to order button
- **Order Items Table**:
  - Product name
  - SKU
  - Quantity (editable)
  - Unit price
  - Line total
  - Remove button

#### E. Order Options
- **Delivery Method**: Dropdown from delivery_methods table
- **Payment Method**:
  - Cash (default for manual orders)
  - EFT
  - Card Payment (In-Store)
  - Other
- **Payment Status**:
  - Paid (default for cash/card)
  - Pending (default for EFT)
  - Partial Payment
- **Order Notes**: Text area for internal notes

#### F. Pricing Summary
- Subtotal (auto-calculated)
- Shipping Cost (from delivery method)
- Discount Amount (manual entry)
- Total Amount (auto-calculated)

### 2. Order Submission
- **Validation**: Required fields must be filled
- **Order Number Generation**: Auto-generate ORD-YYYY-XXXXXXXX format
- **Database Insert**: Save to `orders` and `order_items` tables
- **Redirect**: To order view page with success message

### 3. Invoice Generation
- Use existing invoice templates at `/admin/orders/view/print.php`
- Templates available:
  - `default.php` - Standard invoice
  - `print2.php` - Alternative design
  - `print3.php` - Alternative design
- Invoice accessible from order view page

### 4. Order Management
- Manual orders appear in the same orders list as website orders
- Can be identified by `order_source` field (if added)
- All existing order management features apply:
  - Status updates
  - Payment status changes
  - Invoice generation
  - Shipping updates
  - Notes addition

## Non-Functional Requirements

### Performance
- Page load time: < 2 seconds
- Product search: < 500ms response time
- Order creation: < 1 second

### Security
- Admin authentication required
- CSRF protection on form submission
- Input validation and sanitization
- SQL injection protection (parameterized queries)

### Data Integrity
- Foreign key relationships maintained
- Order items must link to valid products
- Customer email format validation
- Phone number format validation

### Usability
- Responsive design (mobile-friendly)
- Auto-calculation of totals
- Real-time stock availability display
- Product search with autocomplete

## Database Schema Changes

### Optional: Add `order_source` field to `orders` table
```sql
ALTER TABLE orders
ADD COLUMN order_source ENUM('website', 'admin', 'pos', 'api') DEFAULT 'website',
ADD INDEX idx_order_source (order_source);
```

This field would help distinguish:
- `website` = Orders from public checkout
- `admin` = Manual orders created by admin
- `pos` = Point of Sale orders (future)
- `api` = Orders from API (future)

## User Stories

### US1: Admin Creates Cash Order
**As an** admin user
**I want to** create an order for a customer paying cash
**So that** I can generate an invoice for them immediately

**Acceptance Criteria**:
- I can search for existing customer or enter new details
- I can select products and quantities
- I can specify "Cash" as payment method
- I can mark as "Paid"
- Order is saved and invoice can be generated

### US2: Admin Creates Phone Order
**As an** admin user
**I want to** create an order from a phone call
**So that** the customer gets an invoice emailed to them

**Acceptance Criteria**:
- I can capture customer details over the phone
- I can select products from catalog
- I can specify "EFT" as payment method
- I can mark as "Pending" payment
- Order is saved with pending payment status

### US3: Admin Reuses Customer Details
**As an** admin user
**I want to** search for existing customers
**So that** I don't have to re-enter their information

**Acceptance Criteria**:
- I can search by email or phone
- Customer details auto-fill when selected
- Previous addresses are available to select

## Edge Cases & Validation

### Stock Validation
- Warning if ordered quantity exceeds available stock
- Allow over-order (admin discretion)
- Display stock levels in product search

### Customer Linking
- If customer email matches existing user, link to user account
- If no match, create as guest order (user_id = NULL)

### Duplicate Prevention
- Check for duplicate orders (same customer, same products, same time window)
- Warning prompt if potential duplicate detected

### Address Handling
- Validate required address fields
- Handle JSON format for storage
- Support international address formats

## Success Metrics
- Orders created per day (manual vs website)
- Average time to create manual order
- Reduction in manual invoice creation
- Customer satisfaction with phone order process

## Future Enhancements
- Point of Sale (POS) interface
- Barcode scanner integration
- Quick order templates for frequent orders
- Customer payment history display
- Partial payment handling
- Deposit/order flow
- Quote-to-order conversion
