# Order Pages Enhancement Plan

## Tasks Overview
1. Add product images to orders listing page
2. Convert order tracking timeline to vertical layout
3. Add "Track Your Order" link to sidebar

---

## Task 1: Add Product Images to Orders Listing

### File: `user/orders/index.php`

**Current Issue:**
- Line 86: Query only fetches `p.slug` but not `p.image_1`
- Lines 245-252: Shows emoji placeholder instead of real images

**Changes Required:**
1. Update query to fetch `p.image_1`:
```sql
SELECT oi.*, p.name as product_name, p.slug as product_slug, p.image_1
FROM order_items oi
LEFT JOIN products p ON oi.product_id = p.id
WHERE oi.order_id = ?
```

2. Update product image display (lines 245-252):
```php
<div class="w-12 h-12 bg-gradient-to-br from-green-50 to-green-100 rounded-lg border-2 border-white flex items-center justify-center overflow-hidden">
    <?php if (!empty($item['image_1'])): ?>
        <img src="<?= assetUrl('images/' . $item['image_1']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" class="w-full h-full object-cover">
    <?php else: ?>
        <span class="text-lg">📦</span>
    <?php endif; ?>
</div>
```

---

## Task 2: Convert Tracking Timeline to Vertical Layout

### File: `user/orders/view.php`

**Current Issue:**
- Lines 205-234: Horizontal timeline layout with `flex md:flex-row`
- Timeline overflows when screen is small

**Changes Required:**
- Convert to vertical timeline with connecting line
- Use `flex flex-col` for all screen sizes
- Add proper spacing and vertical connector line

**New Vertical Timeline Design:**
```html
<div class="relative pl-8">
    <!-- Vertical Line -->
    <div class="absolute left-3 top-0 bottom-0 w-0.5 bg-gray-200"></div>

    <?php foreach ($steps as $key => $step): ?>
        <!-- Step Item -->
        <div class="relative mb-6 last:mb-0">
            <!-- Step Circle -->
            <div class="absolute left-[-20px] w-6 h-6 rounded-full..."></div>
            <!-- Content -->
            <div class="...">
                <h3><?= $step['label'] ?></h3>
                <p><?= $step['description'] ?></p>
            </div>
        </div>
    <?php endforeach; ?>
</div>
```

---

## Task 3: Add "Track Your Order" to Sidebar

### File: `user/components/sidebar.php`

**Current Structure (lines 22-32):**
```php
<div class="ml-6 space-y-1">
    <a href="<?= userUrl('/invoices/') ?>">Invoices</a>
    <a href="<?= userUrl('/returns/') ?>">Returns</a>
    <a href="<?= userUrl('/reviews/') ?>">Product Reviews</a>
</div>
```

**Add after line 31:**
```php
<a href="<?= userUrl('/orders/track/') ?>" class="block px-3 py-1 text-sm <?= $currentPage === 'track-order' ? 'text-green-600 bg-green-50 border border-green-200 rounded-md' : 'text-gray-600 hover:text-green-600' ?>">
    <i class="fas fa-shipping-fast mr-1"></i>Track Your Order
</a>
```

---

## Implementation Order
1. Update orders/index.php - fetch and show product images
2. Update view.php - convert timeline to vertical layout
3. Update sidebar.php - add Track Your Order link

---

*Plan created: 2025-12-26*
