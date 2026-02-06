# QR Code Management System - Implementation Plan

## Project Overview

Implement a comprehensive QR code management system for admin users to:
1. Generate QR codes for individual products
2. Generate QR codes for invoices (with buyer info and purchase details)
3. Track QR code scans with timestamps and relevant data
4. Link scans to contact page with referral tracking

## Current System Analysis

### Database Schema
- **products table**: Contains product information (id, name, slug, price, colors, stock, etc.)
- **orders table**: Contains order information (id, order_number, customer_email, customer_name, total_amount, status, etc.)
- **order_items table**: Contains order line items (product_id, product_name, quantity, variation_name, etc.)
- **contact_messages table**: Stores contact form submissions

### Admin Panel Structure
- Location: `/admin/` directory
- Authentication: `AdminAuth` class with session-based auth
- UI Components: `adminSidebarWrapper()` for consistent layout
- URL Helper: Uses `adminUrl()` for generating admin URLs

### Contact Page
- Location: `/contact/index.php`
- Accepts: name, email, phone, subject, category, message
- Currently: Does not track QR code referrals

## Implementation Plan

---

## Phase 1: Database Schema

### 1.1 Create QR Codes Table

```sql
CREATE TABLE qr_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    qr_code_type ENUM('product', 'invoice') NOT NULL,
    reference_id INT NOT NULL COMMENT 'Product ID or Order ID based on type',
    qr_code_unique_id VARCHAR(64) NOT NULL UNIQUE COMMENT 'Unique ID for QR tracking URL',
    qr_code_label VARCHAR(255) DEFAULT NULL COMMENT 'Optional custom label for the QR code',
    qr_code_image_path VARCHAR(500) DEFAULT NULL COMMENT 'Path to generated QR code image',
    is_active TINYINT(1) DEFAULT 1,
    created_by INT NOT NULL COMMENT 'Admin user ID who created it',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_qr_unique (qr_code_unique_id),
    INDEX idx_type_reference (qr_code_type, reference_id),
    INDEX idx_created_by (created_by),
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 1.2 Create QR Scan Tracking Table

```sql
CREATE TABLE qr_scans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    qr_code_id INT NOT NULL,
    scanned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    scanned_from_ip VARCHAR(45) DEFAULT NULL,
    scanned_from_user_agent TEXT DEFAULT NULL,
    contact_form_submitted TINYINT(1) DEFAULT 0 COMMENT 'Did user submit contact form after scan?',
    contact_message_id INT DEFAULT NULL COMMENT 'Linked contact message if form submitted',
    INDEX idx_qr_code_id (qr_code_id),
    INDEX idx_scanned_at (scanned_at),
    FOREIGN KEY (qr_code_id) REFERENCES qr_codes(id) ON DELETE CASCADE,
    FOREIGN KEY (contact_message_id) REFERENCES contact_messages(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 1.3 Update Contact Messages Table

Add column to track QR code origin:

```sql
ALTER TABLE contact_messages
ADD COLUMN qr_scan_id INT DEFAULT NULL AFTER priority,
ADD INDEX idx_qr_scan_id (qr_scan_id),
ADD FOREIGN KEY (qr_scan_id) REFERENCES qr_scans(id) ON DELETE SET NULL;
```

---

## Phase 2: QR Code Generation Library

### 2.1 Install/Include QR Code Library

Since this is a standalone PHP system with no build tools, use a pure PHP QR code library.

**Option A**: Use `phpqrcode` library (single file, no composer required)
- Download: `https://github.com/t0k4rt/phpqrcode`
- Place in: `/includes/libraries/phpqrcode/`

**Option B**: Use endroid/qr-code via composer (if available)
- Not recommended due to no build tools policy

**Selected Approach**: Use `phpqrcode` library (single file inclusion)

### 2.2 Create QR Code Service Class

File: `/includes/qr_code_service.php`

```php
<?php
/**
 * QR Code Service
 * Handles QR code generation and tracking
 */

require_once __DIR__ . '/libraries/phpqrcode/qrlib.php';

class QRCodeService {
    private $db;
    private $uploadDir;
    private $uploadUrl;

    public function __construct($database) {
        $this->db = $database;
        $this->uploadDir = __DIR__ . '/../assets/qr-codes/';
        $this->uploadUrl = assetUrl('qr-codes/');

        // Create upload directory if not exists
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Generate QR code for a product
     */
    public function generateProductQRCode($productId, $label = null, $adminId) {
        // Verify product exists
        $stmt = $this->db->prepare("SELECT id, name, slug FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product) {
            return ['success' => false, 'message' => 'Product not found'];
        }

        // Generate unique QR code ID
        $uniqueId = $this->generateUniqueId();

        // Generate QR code filename
        $filename = 'product_' . $productId . '_' . $uniqueId . '.png';
        $filepath = $this->uploadDir . $filename;

        // Create QR code URL - points to contact page with QR tracking
        $qrUrl = url('contact/') . '?qr=' . $uniqueId;

        // Generate QR code image
        QRcode::png($qrUrl, $filepath, QR_ECLEVEL_M, 8, 2);

        // Save to database
        $stmt = $this->db->prepare("
            INSERT INTO qr_codes (qr_code_type, reference_id, qr_code_unique_id, qr_code_label, qr_code_image_path, created_by)
            VALUES ('product', ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $productId,
            $uniqueId,
            $label,
            $this->uploadUrl . $filename,
            $adminId
        ]);

        return [
            'success' => true,
            'qr_code_id' => $this->db->lastInsertId(),
            'unique_id' => $uniqueId,
            'image_path' => $this->uploadUrl . $filename,
            'local_path' => $filepath
        ];
    }

    /**
     * Generate QR code for an invoice/order
     */
    public function generateInvoiceQRCode($orderId, $label = null, $adminId) {
        // Verify order exists
        $stmt = $this->db->prepare("SELECT id, order_number, customer_name, customer_email FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        if (!$order) {
            return ['success' => false, 'message' => 'Order not found'];
        }

        // Generate unique QR code ID
        $uniqueId = $this->generateUniqueId();

        // Generate QR code filename
        $filename = 'invoice_' . $orderId . '_' . $uniqueId . '.png';
        $filepath = $this->uploadDir . $filename;

        // Create QR code URL - points to contact page with QR tracking
        $qrUrl = url('contact/') . '?qr=' . $uniqueId;

        // Generate QR code image
        QRcode::png($qrUrl, $filepath, QR_ECLEVEL_M, 8, 2);

        // Save to database
        $stmt = $this->db->prepare("
            INSERT INTO qr_codes (qr_code_type, reference_id, qr_code_unique_id, qr_code_label, qr_code_image_path, created_by)
            VALUES ('invoice', ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $orderId,
            $uniqueId,
            $label,
            $this->uploadUrl . $filename,
            $adminId
        ]);

        return [
            'success' => true,
            'qr_code_id' => $this->db->lastInsertId(),
            'unique_id' => $uniqueId,
            'image_path' => $this->uploadUrl . $filename,
            'local_path' => $filepath
        ];
    }

    /**
     * Track QR code scan
     */
    public function trackQRScan($uniqueId) {
        // Find QR code by unique ID
        $stmt = $this->db->prepare("
            SELECT qc.*, CASE qc.qr_code_type
                WHEN 'product' THEN p.name
                WHEN 'invoice' THEN CONCAT('Order #', o.order_number)
            END as reference_name
            FROM qr_codes qc
            LEFT JOIN products p ON qc.qr_code_type = 'product' AND qc.reference_id = p.id
            LEFT JOIN orders o ON qc.qr_code_type = 'invoice' AND qc.reference_id = o.id
            WHERE qc.qr_code_unique_id = ? AND qc.is_active = 1
        ");
        $stmt->execute([$uniqueId]);
        $qrCode = $stmt->fetch();

        if (!$qrCode) {
            return ['success' => false, 'message' => 'QR code not found or inactive'];
        }

        // Record the scan
        $stmt = $this->db->prepare("
            INSERT INTO qr_scans (qr_code_id, scanned_from_ip, scanned_from_user_agent)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $qrCode['id'],
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);

        $scanId = $this->db->lastInsertId();

        return [
            'success' => true,
            'qr_code' => $qrCode,
            'scan_id' => $scanId
        ];
    }

    /**
     * Get QR code details
     */
    public function getQRCodeByUniqueId($uniqueId) {
        $stmt = $this->db->prepare("
            SELECT qc.*,
                   CASE qc.qr_code_type
                       WHEN 'product' THEN p.name
                       WHEN 'invoice' THEN o.order_number
                   END as reference_name,
                   CASE qc.qr_code_type
                       WHEN 'product' THEN p.slug
                       WHEN 'invoice' THEN NULL
                   END as product_slug,
                   CASE qc.qr_code_type
                       WHEN 'product' THEN p.price
                       WHEN 'invoice' THEN NULL
                   END as product_price
            FROM qr_codes qc
            LEFT JOIN products p ON qc.qr_code_type = 'product' AND qc.reference_id = p.id
            LEFT JOIN orders o ON qc.qr_code_type = 'invoice' AND qc.reference_id = o.id
            WHERE qc.qr_code_unique_id = ?
        ");
        $stmt->execute([$uniqueId]);
        return $stmt->fetch();
    }

    /**
     * Get all QR codes for admin
     */
    public function getAllQRCodes($type = null, $limit = 50) {
        if ($type) {
            $stmt = $this->db->prepare("
                SELECT qc.*,
                       CASE qc.qr_code_type
                           WHEN 'product' THEN p.name
                           WHEN 'invoice' THEN CONCAT('Order #', o.order_number)
                       END as reference_name,
                       a.username as created_by_name,
                       (SELECT COUNT(*) FROM qr_scans WHERE qr_code_id = qc.id) as scan_count
                FROM qr_codes qc
                LEFT JOIN products p ON qc.qr_code_type = 'product' AND qc.reference_id = p.id
                LEFT JOIN orders o ON qc.qr_code_type = 'invoice' AND qc.reference_id = o.id
                LEFT JOIN admin_users a ON qc.created_by = a.id
                WHERE qc.qr_code_type = ?
                ORDER BY qc.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$type, $limit]);
        } else {
            $stmt = $this->db->prepare("
                SELECT qc.*,
                       CASE qc.qr_code_type
                           WHEN 'product' THEN p.name
                           WHEN 'invoice' THEN CONCAT('Order #', o.order_number)
                       END as reference_name,
                       a.username as created_by_name,
                       (SELECT COUNT(*) FROM qr_scans WHERE qr_code_id = qc.id) as scan_count
                FROM qr_codes qc
                LEFT JOIN products p ON qc.qr_code_type = 'product' AND qc.reference_id = p.id
                LEFT JOIN orders o ON qc.qr_code_type = 'invoice' AND qc.reference_id = o.id
                LEFT JOIN admin_users a ON qc.created_by = a.id
                ORDER BY qc.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
        }
        return $stmt->fetchAll();
    }

    /**
     * Get QR code scan history
     */
    public function getQRScanHistory($qrCodeId) {
        $stmt = $this->db->prepare("
            SELECT qs.*,
                   CASE WHEN qs.contact_message_id IS NOT NULL THEN 1 ELSE 0 END as has_contact
            FROM qr_scans qs
            WHERE qs.qr_code_id = ?
            ORDER BY qs.scanned_at DESC
        ");
        $stmt->execute([$qrCodeId]);
        return $stmt->fetchAll();
    }

    /**
     * Delete QR code
     */
    public function deleteQRCode($qrCodeId) {
        // Get QR code info
        $stmt = $this->db->prepare("SELECT * FROM qr_codes WHERE id = ?");
        $stmt->execute([$qrCodeId]);
        $qrCode = $stmt->fetch();

        if (!$qrCode) {
            return ['success' => false, 'message' => 'QR code not found'];
        }

        // Delete image file
        if ($qrCode['qr_code_image_path']) {
            $filepath = $this->uploadDir . basename($qrCode['qr_code_image_path']);
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }

        // Delete from database (cascade will handle scans)
        $stmt = $this->db->prepare("DELETE FROM qr_codes WHERE id = ?");
        $stmt->execute([$qrCodeId]);

        return ['success' => true];
    }

    /**
     * Toggle QR code active status
     */
    public function toggleQRCodeStatus($qrCodeId) {
        $stmt = $this->db->prepare("UPDATE qr_codes SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$qrCodeId]);
        return ['success' => true];
    }

    /**
     * Generate unique ID for QR code
     */
    private function generateUniqueId() {
        return bin2hex(random_bytes(16));
    }

    /**
     * Get QR code statistics
     */
    public function getQRStatistics() {
        $stats = [];

        // Total QR codes
        $stmt = $this->db->query("SELECT COUNT(*) FROM qr_codes");
        $stats['total_qr_codes'] = $stmt->fetchColumn();

        // Product QR codes
        $stmt = $this->db->query("SELECT COUNT(*) FROM qr_codes WHERE qr_code_type = 'product'");
        $stats['product_qr_codes'] = $stmt->fetchColumn();

        // Invoice QR codes
        $stmt = $this->db->query("SELECT COUNT(*) FROM qr_codes WHERE qr_code_type = 'invoice'");
        $stats['invoice_qr_codes'] = $stmt->fetchColumn();

        // Total scans
        $stmt = $this->db->query("SELECT COUNT(*) FROM qr_scans");
        $stats['total_scans'] = $stmt->fetchColumn();

        // Scans today
        $stmt = $this->db->query("SELECT COUNT(*) FROM qr_scans WHERE DATE(scanned_at) = CURDATE()");
        $stats['scans_today'] = $stmt->fetchColumn();

        // Contact form submissions from scans
        $stmt = $this->db->query("SELECT COUNT(*) FROM qr_scans WHERE contact_form_submitted = 1");
        $stats['contact_submissions'] = $stmt->fetchColumn();

        return $stats;
    }
}
```

---

## Phase 3: Admin QR Code Management Pages

### 3.1 Admin QR Codes List Page

File: `/admin/qr-codes/index.php`

Features:
- List all QR codes with type, reference, label, scan count, status
- Filter by type (product/invoice)
- Generate new QR code buttons
- Actions: View scans, Download, Delete, Toggle active

### 3.2 Generate QR Code Modal/Page

Features:
- Select type (Product/Invoice)
- For Product: Select product from dropdown
- For Invoice: Select order from dropdown
- Optional label
- Generate button
- Display generated QR code with download option

### 3.3 QR Code Scan History Page

File: `/admin/qr-codes/scans.php`

Features:
- Show scan history for specific QR code
- Display timestamp, IP, user agent
- Show if contact form was submitted
- Link to contact message if available

---

## Phase 4: Contact Page Integration

### 4.1 Modify Contact Page (`/contact/index.php`)

Add QR code detection at top of page:

```php
// Check for QR code scan
$qrScanData = null;
if (isset($_GET['qr'])) {
    require_once __DIR__ . '/../includes/qr_code_service.php';
    $qrService = new QRCodeService($db);
    $qrResult = $qrService->trackQRScan($_GET['qr']);
    if ($qrResult['success']) {
        $qrScanData = $qrResult;
        // Store scan ID in session for contact form submission
        $_SESSION['qr_scan_id'] = $qrResult['scan_id'];
        $_SESSION['qr_code_data'] = $qrResult['qr_code'];
    }
}
```

Display QR code info banner if scanned:

```php
if ($qrScanData): ?>
    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6 rounded-lg">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-qrcode text-blue-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-blue-700">
                    <?php if ($qrScanData['qr_code']['qr_code_type'] === 'product'): ?>
                        You scanned a QR code for: <strong><?= htmlspecialchars($qrScanData['qr_code']['reference_name']) ?></strong>
                    <?php else: ?>
                        You scanned a QR code for: <strong><?= htmlspecialchars($qrScanData['qr_code']['reference_name']) ?></strong>
                    <?php endif; ?>
                </p>
                <p class="text-xs text-blue-600 mt-1">Please fill out the form below and we'll help you right away.</p>
            </div>
        </div>
    </div>
<?php endif; ?>
```

### 4.2 Update Contact Form Submission

Link scan to contact message:

```php
// In form submission handler
$qrScanId = $_SESSION['qr_scan_id'] ?? null;

// Insert message
$stmt = $db->prepare("
    INSERT INTO contact_messages (name, email, phone, user_id, subject, category, message, priority, status, qr_scan_id)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'new', ?)
");
$stmt->execute([
    $name, $email, $phone, ($isLoggedIn ? $currentUser['id'] : null),
    $subject, $category, $message, $priority, $qrScanId
]);

// Update scan record
if ($qrScanId) {
    $stmt = $db->prepare("UPDATE qr_scans SET contact_form_submitted = 1, contact_message_id = ? WHERE id = ?");
    $stmt->execute([$db->lastInsertId(), $qrScanId]);
    unset($_SESSION['qr_scan_id'], $_SESSION['qr_code_data']);
}
```

---

## Phase 5: Product and Order Pages Integration

### 5.1 Add QR Code Button to Product Edit Page

File: `/admin/products/edit/index.php`

Add button/link to generate QR code for this product.

### 5.2 Add QR Code Button to Order View Page

File: `/admin/orders/view/index.php`

Add button/link to generate QR code for this invoice.

---

## Phase 6: Implementation Checklist

### Database Tasks
- [ ] Create `qr_codes` table
- [ ] Create `qr_scans` table
- [ ] Add `qr_scan_id` column to `contact_messages` table
- [ ] Test database structure with sample data

### Backend Tasks
- [ ] Download and include `phpqrcode` library
- [ ] Create `/includes/qr_code_service.php`
- [ ] Test QR code generation
- [ ] Test scan tracking functionality

### Admin Panel Tasks
- [ ] Create `/admin/qr-codes/index.php` (list page)
- [ ] Create QR code generation modal/form
- [ ] Create `/admin/qr-codes/scans.php` (scan history)
- [ ] Add QR code link to product edit page
- [ ] Add QR code link to order view page
- [ ] Add sidebar navigation link for QR Codes

### Contact Page Tasks
- [ ] Modify `/contact/index.php` to detect QR scans
- [ ] Display QR code info banner
- [ ] Link scan to contact message on submission

### Testing Tasks
- [ ] Test product QR code generation
- [ ] Test invoice QR code generation
- [ ] Test QR code scan tracking
- [ ] Test contact form with QR referral
- [ ] Test QR code download functionality
- [ ] Test QR code deletion
- [ ] Test QR code activation toggle

---

## File Structure (New Files)

```
/includes/
  ├── libraries/
  │   └── phpqrcode/
  │       ├── qrlib.php
  │       └── qrconst.php
  └── qr_code_service.php

/admin/
  └── qr-codes/
      ├── index.php          # QR codes list
      ├── generate.php       # Generate new QR code
      └── scans.php          # Scan history

/assets/
  └── qr-codes/              # Generated QR code images
```

---

## Security Considerations

1. **QR Code ID Validation**: Validate QR code unique IDs to prevent SQL injection
2. **Rate Limiting**: Consider rate limiting QR scan tracking to prevent abuse
3. **Image Upload Security**: QR codes are generated server-side, no user upload
4. **CSRF Protection**: Include CSRF tokens for all admin forms
5. **Admin Authorization**: Verify admin user is logged in for all QR operations

---

## Future Enhancements

1. **Batch QR Code Generation**: Generate multiple QR codes at once
2. **QR Code Templates**: Custom styling for QR codes (colors, logos)
3. **Analytics Dashboard**: Visual charts for scan statistics
4. **Export QR Codes**: Bulk download as ZIP
5. **Email QR Codes**: Send QR codes to customers via email
6. **Print Labels**: Generate printable label sheets with QR codes

---

## Notes

- All URLs use `url()` helper - no hardcoded paths
- QR code images stored in `/assets/qr-codes/`
- Contact page tracks QR scans via URL parameter `?qr=UNIQUE_ID`
- Admin auth required for all QR code management operations
- Session stores scan ID until contact form submission or session expires
