# Admin Product Edit Page Implementation Plan

## Task Overview
Create a comprehensive admin product edit page at `/admin/products/edit/` that allows editing all product details and managing product photos, maintaining consistency with the existing add product page.

## Analysis Summary

### Existing Add Product Page (`admin/products/add.php`)
The add product page includes these form sections:

1. **Basic Information**
   - name (required, text)
   - short_description (textarea)
   - description (textarea)
   - category (dropdown from categories table - field name: `category`)
   - sku (required, text)

2. **Pricing**
   - price (required, number)
   - sale_price (number, optional)
   - cost (number, optional)

3. **Inventory**
   - stock (required, number)
   - weight (number, optional)
   - dimensions (text, optional)

4. **Images**
   - Drag & drop upload interface
   - Multiple image upload via AJAX to `upload_image.php`
   - Stored as comma-separated URLs in `images` field

5. **Tags**
   - Text input, converted to JSON array
   - Supports comma-separated values

6. **Settings**
   - active (checkbox)
   - featured (checkbox)

7. **SEO**
   - meta_title (text)
   - meta_description (textarea)

8. **Custom Fields**
   - JSON field with dynamic UI
   - Add/remove field pairs (label, value)

9. **Product Policies**
   - Multi-line textarea

### View Product Page (`admin/products/view/index.php`)
Shows:
- All product data in read-only format
- Images grid display
- Stock status calculation
- Edit link pointing to `/products/edit/{slug}`

### Database Schema Confirmed (Based on Working Code)
From analyzing the actual working code (add.php, view.php, products/index.php):

**Products Table Fields:**
- `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
- `name` (VARCHAR(255), NOT NULL)
- `slug` (VARCHAR(255), UNIQUE, NOT NULL)
- `description` (TEXT)
- `short_description` (TEXT)
- `price` (DECIMAL, NOT NULL)
- `sale_price` (DECIMAL, nullable)
- `cost` (DECIMAL, nullable)
- `sku` (VARCHAR, UNIQUE)
- `stock` (INT, DEFAULT 0)
- `weight` (DECIMAL, nullable)
- `dimensions` (VARCHAR/TEXT, nullable)
- `category` (VARCHAR/TEXT, NOT NULL) - **Text field, not category_id**
- `tags` (JSON) - stored as JSON string
- `images` (TEXT) - comma-separated image URLs
- `active` (TINYINT/BOOLEAN) - **Not 'status'**
- `featured` (TINYINT/BOOLEAN)
- `meta_title` (VARCHAR, nullable)
- `meta_description` (TEXT, nullable)
- `custom_fields` (JSON) - stored as JSON string
- `product_policies` (TEXT)
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)

**Note**: The view.php attempts to use `category_id` but the actual database uses `category` (text). The edit page should use `category` to match add.php.

## Implementation Plan

### Phase 1: Database Schema Verification
**Task**: Verify the actual products table structure
**File**: Run database analysis
**Goal**: Confirm correct field names for category reference

### Phase 2: Create Edit Page Structure
**Task**: Create `/admin/products/edit/index.php`
**Components to implement**:

1. **URL Routing Setup**
   - Route pattern: `admin/products/edit/{slug}`
   - Accept product slug from URL
   - Validate product exists

2. **Database Operations**
   - Fetch product by slug on page load
   - Handle form submission (POST) for updates
   - Update product record with all fields
   - Maintain updated_at timestamp

3. **Form Structure** (mirror add product page)
   - Pre-populate all fields with existing product data
   - Use same form components: `adminFormInput()`, `adminFormTextarea()`, `adminFormSelect()`
   - Implement same sections:
     * Basic Information
     * Pricing
     * Inventory
     * Images (with pre-populated existing images)
     * Tags
     * Settings
     * SEO
     * Custom Fields
     * Product Policies

4. **Image Management**
   - Display existing images with remove buttons
   - Allow adding new images via drag & drop
   - Maintain comma-separated format in `images` field
   - Delete functionality for individual images

5. **Form Handling**
   - POST request processing
   - Validate required fields
   - Generate slug (should preserve existing or update if name changed)
   - Convert tags to JSON
   - Sanitize custom fields JSON
   - Update database record
   - Success/error messaging
   - Redirect to products list or view page

### Phase 3: UI Components to Implement

1. **Page Header**
   - Title: "Edit Product - {product_name}"
   - Back button to products list
   - View product button

2. **Form Sections** (using same styling as add page)
   - Consistent with add product layout
   - Pre-populated values from database
   - Existing images displayed with preview
   - Remove image functionality

3. **JavaScript Features**
   - Image upload handling (reuse from add page)
   - Custom fields management ()
   - Formreuse from add page validation

### Phase 4: Key Technical Considerations

1. **Slug Handling**
   - If name changes, regenerate slug
   - Check for slug conflicts
   - Update slug if changed

2. **Image Management**
   - Store existing image URLs
   - Allow removal of individual images
   - Upload new images via AJAX
   - Update comma-separated list

3. **Field Validation**
   - Required fields: name, sku, price, stock
   - Numeric validation: price, sale_price, cost, stock, weight
   - JSON validation for tags and custom fields

4. **Redirect Logic**
   - On success: redirect to `/admin/products/view/{slug}`
   - On error: stay on page with error message

### Phase 5: Code Structure

```
admin/products/edit/index.php
├── Authentication check
├── Get product slug from URL
├── Fetch product from database
├── Fetch categories for dropdown
├── Handle POST request (form submission)
│   ├── Validate input
│   ├── Process tags (JSON)
│   ├── Process custom fields (JSON)
│   ├── Update database
│   └── Redirect on success
├── Generate form HTML
│   ├── Pre-populated fields
│   ├── Existing images display
│   ├── Image upload interface
│   └── Custom fields UI
└── Render page with sidebar
```

### Phase 6: Files to Create/Modify

1. **Create**: `admin/products/edit/index.php`
   - Main edit page

2. **Verify**: Route handling in `route.php` or `includes/admin_routes.php`
   - Ensure `/admin/products/edit/{slug}` route is registered

3. **Check**: `admin/products/upload_image.php`
   - Verify works for both add and edit

## Success Criteria

1. ✅ Edit page accessible via `/admin/products/edit/{slug}`
2. ✅ All product fields pre-populated from database
3. ✅ Form submission updates product successfully
4. ✅ Images can be managed (add/remove)
5. ✅ Maintains consistency with add product page
6. ✅ Proper validation and error handling
7. ✅ Success/error messaging works
8. ✅ Redirects appropriately after save

## Testing Plan

1. **Load Test**
   - Access edit page with valid slug
   - Verify all fields populated correctly
   - Check images display properly

2. **Update Test**
   - Modify product details
   - Upload new images
   - Remove existing images
   - Save changes
   - Verify database updated

3. **Validation Test**
   - Submit with missing required fields
   - Verify error messages

4. **Navigation Test**
   - Back button works
   - View product button works
   - Success redirect works

## Estimated Implementation Steps

1. Verify database schema
2. Create edit page structure
3. Implement form pre-population
4. Add image management UI
5. Implement form submission logic
6. Test all functionality
7. Fix any issues found

## Notes

- Reuse as much code as possible from `add.php` for consistency
- Maintain same styling and UI patterns
- Use existing form helper functions
- Follow same validation logic as add page
- Ensure proper error handling
