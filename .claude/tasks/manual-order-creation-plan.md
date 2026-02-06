# Manual Order Creation - Implementation Plan

## Executive Summary
This plan outlines the implementation of a manual order creation feature for the admin dashboard. The feature will allow admins to create orders for customers who didn't purchase through the website (cash payments, phone orders, in-person sales) and generate invoices using existing templates.

## Current System Analysis

### Existing Order Flow
```
Public Website → Cart → Checkout → Order Created → Invoice Generated
```

### Existing Invoice Generation
```
/admin/orders/view/print.php?id={order_id}
├── Fetches order from database
├── Fetches order items with product details
├── Loads store settings
├── Determines template (default/print2/print3)
└── Renders invoice HTML
```

### Database Schema (Relevant Tables)

#### `orders` table
- Contains all order data (customer, shipping, billing, pricing)
- No changes required - manual orders will use same structure
- Optional: Add `order_source` field to track origin

#### `order_items` table
- Contains line items (product_id, quantity, price)
- No changes required

#### `products` table
- Product catalog with pricing and stock
- Already has all necessary fields

#### `users` table
- Customer accounts
- Manual orders can link to existing users or be guest orders

### Existing Files to Leverage
- `/admin/orders/view/print.php` - Invoice generation (reuse as-is)
- `/admin/orders/view/templates/*.php` - Invoice templates (reuse as-is)
- `/includes/database.php` - Database class (reuse as-is)
- `/includes/url_helper.php` - URL generation (reuse as-is)
- `/admin_sidebar_components.php` - UI components (reuse as-is)

## Technical Approach

### Architecture Decision: Reuse Existing Tables
**Decision**: Manual orders will use the same `orders` and `order_items` tables as website orders.

**Rationale**:
- No data duplication
- Existing invoice generation works immediately
- Unified order management
- No migration needed

**Trade-offs**:
- Optional: Add `order_source` field to distinguish origin
- All orders treated equally in management interface

### New Files to Create
| File | Purpose |
|------|---------|
| `/admin/orders/create/index.php` | Manual order creation page |
| `/admin/orders/create/process.php` | Order creation handler |
| `/includes/order_service.php` | Order management service class |

### Files to Modify
| File | Changes |
|------|---------|
| `admin_sidebar_components.php` | Add "Create Order" link |
| `/admin/orders/index.php` | Optional: Filter by order source |

## Implementation Strategy

### Phase 1: Foundation (Day 1)
1. Create database migration (optional `order_source` field)
2. Create `OrderService` class
3. Create order creation page structure

### Phase 2: Order Creation Form (Day 1-2)
1. Customer information section
2. Address form (shipping/billing)
3. Product search and selection
4. Order items table with calculations

### Phase 3: Processing (Day 2)
1. Form validation
2. Order creation handler
3. Order items insertion
4. Redirect to order view

### Phase 4: Integration (Day 2-3)
1. Add sidebar navigation link
2. Test invoice generation
3. Test with various scenarios

### Phase 5: Polish (Day 3)
1. Error handling
2. Success/error messaging
3. Responsive design
4. Accessibility improvements

## Key Technical Decisions

### 1. Order Number Generation
**Approach**: Same format as website orders
- Format: `ORD-YYYY-XXXXXXXX`
- Generated in PHP using `uniqid()` and `md5()`
- Ensures uniqueness across all orders

### 2. Customer Search vs. Manual Entry
**Approach**: Hybrid
- Search existing customers by email/phone first
- If found, auto-fill details
- If not found, show manual entry form
- Link to existing user if email matches

### 3. Stock Handling
**Approach**: Warning only
- Display current stock level
- Show warning if order quantity exceeds stock
- Allow admin to proceed (admin discretion)
- Don't prevent order creation

### 4. Address Storage
**Approach**: JSON format (consistent with existing)
- Store as JSON in `shipping_address` and `billing_address` fields
- Reuse existing `formatAddressCompact()` helper
- Compatible with invoice templates

### 5. Payment Status Default
**Approach**: Smart defaults based on payment method
- Cash → "Paid" (customer pays immediately)
- EFT → "Pending" (payment to be confirmed)
- Card (In-Store) → "Paid"
- Can be overridden by admin

## Data Flow Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                     Admin Dashboard                             │
│                  /admin/orders/create/                          │
└─────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────┐
│                        User Input                               │
│  ┌───────────────┐  ┌───────────────┐  ┌───────────────┐      │
│  │   Customer    │  │   Products    │  │   Options     │      │
│  │   Details     │  │   Selection   │  │   (Payment,   │      │
│  │               │  │               │  │   Shipping)   │      │
│  └───────────────┘  └───────────────┘  └───────────────┘      │
└─────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────┐
│                   Form Validation                                │
│  • Required fields check                                        │
│  • Email format validation                                       │
│  • Phone format validation                                       │
│  • Stock availability warning                                    │
└─────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────┐
│              Order Creation (process.php)                         │
│  1. Generate order number                                       │
│  2. Check for existing customer                                  │
│  3. Insert into `orders` table                                   │
│  4. Insert items into `order_items` table                        │
│  5. Set order status to 'pending'                                │
└─────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────┐
│                   Success Redirect                               │
│              /admin/orders/view/{id}                            │
└─────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────┐
│              Generate Invoice (Existing)                          │
│          /admin/orders/view/print.php?id={id}                   │
└─────────────────────────────────────────────────────────────────┘
```

## Integration Points

### With Existing Systems

| System | Integration Method |
|--------|-------------------|
| Product Catalog | Direct database query |
| Customer/Users | Optional lookup by email |
| Delivery Methods | Dropdown from delivery_methods table |
| Invoice Generation | Reuse existing print.php |
| Order Management | Appears in same orders list |

### No Integration Required
- Cart system (bypassed)
- Checkout process (bypassed)
- Payment gateways (manual tracking only)
- Stock management (warning only, no enforcement)

## Security Considerations

### Authentication
- Admin login required (existing AdminAuth class)
- Session-based authentication

### Authorization
- Only admin users can create manual orders
- No customer access to this feature

### CSRF Protection
- Include CSRF token in form
- Validate on form submission

### Input Validation
- All user input sanitized
- Email format validation
- Phone number format validation
- Numeric fields validated

### SQL Injection Prevention
- Use parameterized queries (PDO prepared statements)
- No string concatenation in SQL

## Testing Strategy

### Unit Testing
- OrderService class methods
- Order number generation uniqueness
- Price calculations

### Integration Testing
- Form submission to database
- Order items insertion
- Invoice generation from manual order

### Manual Testing Scenarios
1. Create order for new customer (cash payment)
2. Create order for existing customer
3. Create order with multiple products
4. Create order with custom shipping address
5. Verify invoice generation
6. Test stock warning display
7. Test form validation (required fields)

## Rollout Plan

### Development
- Feature flag not required (admin-only feature)
- Can be deployed immediately after testing

### Training
- Demo for admin users
- Documentation for support team

### Monitoring
- Track manual order creation rate
- Monitor for errors or issues
- Gather user feedback

## Success Criteria
- [ ] Admin can create manual orders
- [ ] Orders appear in orders list
- [ ] Invoices generate correctly
- [ ] Form validation works
- [ ] Stock warnings display
- [ ] Customer search functional
- [ ] Mobile-responsive design

## Timeline Estimate
- **Phase 1**: 2-3 hours
- **Phase 2**: 4-6 hours
- **Phase 3**: 3-4 hours
- **Phase 4**: 2-3 hours
- **Phase 5**: 2-3 hours
- **Total**: 13-19 hours (2-3 development days)
