<?php
/**
 * QR Code Service
 * Handles QR code generation and tracking
 * Uses external API for QR code generation to avoid library dependencies
 */

class QRCodeService {
    private $db;
    private $uploadDir;
    private $uploadUrl;

    public function __construct($database) {
        $this->db = $database;
        $this->uploadDir = __DIR__ . '/../assets/qr-codes/';
        // Store only relative path (not full URL) to allow dynamic domain changes
        $this->uploadUrl = 'qr-codes/';

        // Create upload directory if not exists
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Generate QR code for a product
     */
    public function generateProductQRCode($productId, $adminId, $label = null) {
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

        // Generate QR code using API
        $qrGenerated = $this->generateQRCodeFromAPI($qrUrl, $filepath);

        if (!$qrGenerated) {
            return ['success' => false, 'message' => 'Failed to generate QR code image'];
        }

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
    public function generateInvoiceQRCode($orderId, $adminId, $label = null) {
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

        // Generate QR code using API
        $qrGenerated = $this->generateQRCodeFromAPI($qrUrl, $filepath);

        if (!$qrGenerated) {
            return ['success' => false, 'message' => 'Failed to generate QR code image'];
        }

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
     * Generate QR code for a custom link
     * Admin can manually enter a URL or select from predefined pages/products
     */
    public function generateCustomLinkQRCode($url, $adminId, $label = null) {
        // Validate URL
        if (empty($url)) {
            return ['success' => false, 'message' => 'URL is required'];
        }

        // Generate unique QR code ID
        $uniqueId = $this->generateUniqueId();

        // Generate QR code filename
        $filename = 'custom_' . $uniqueId . '.png';
        $filepath = $this->uploadDir . $filename;

        // Use the provided URL directly for the QR code
        $qrUrl = $url;

        // Generate QR code using API
        $qrGenerated = $this->generateQRCodeFromAPI($qrUrl, $filepath);

        if (!$qrGenerated) {
            return ['success' => false, 'message' => 'Failed to generate QR code image'];
        }

        // Save to database with 'custom_link' type and store URL in reference_id as string
        $stmt = $this->db->prepare("
            INSERT INTO qr_codes (qr_code_type, reference_id, qr_code_unique_id, qr_code_label, qr_code_image_path, created_by)
            VALUES ('custom_link', ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $url,
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
            'local_path' => $filepath,
            'url' => $url
        ];
    }

    /**
     * Generate QR code image from external API
     * Uses qrserver.com API - free and reliable
     */
    private function generateQRCodeFromAPI($data, $filepath, $size = 300) {
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/' .
                 '?size=' . $size . 'x' . $size .
                 '&data=' . urlencode($data) .
                 '&bgcolor=ffffff' .
                 '&color=000000' .
                 '&format=png' .
                 '&ecc=M';

        try {
            $imageData = @file_get_contents($qrUrl, false, stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'Mozilla/5.0'
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]));

            if ($imageData === false) {
                error_log("QR Code API request failed for: " . $data);
                return false;
            }

            // Verify it's a valid PNG
            if (substr($imageData, 0, 8) !== "\x89PNG\r\n\x1a\n") {
                error_log("QR Code API returned invalid PNG data");
                return false;
            }

            // Save to file
            $result = file_put_contents($filepath, $imageData);

            return $result !== false;
        } catch (Exception $e) {
            error_log("QR Code generation error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Track QR code scan
     */
    public function trackQRScan($uniqueId) {
        // Find QR code by unique ID
        $stmt = $this->db->prepare("
            SELECT qc.*,
                   CASE qc.qr_code_type
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

        // Get additional details based on type
        $details = null;
        if ($qrCode['qr_code_type'] === 'product') {
            $stmt = $this->db->prepare("SELECT name, slug, price FROM products WHERE id = ?");
            $stmt->execute([$qrCode['reference_id']]);
            $details = $stmt->fetch();
        }

        return [
            'success' => true,
            'qr_code' => $qrCode,
            'scan_id' => $scanId,
            'details' => $details
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
     * Get QR code by ID
     */
    public function getQRCodeById($qrCodeId) {
        $stmt = $this->db->prepare("
            SELECT qc.*,
                   CASE qc.qr_code_type
                       WHEN 'product' THEN p.name
                       WHEN 'invoice' THEN CONCAT('Order #', o.order_number)
                   END as reference_name,
                   CASE qc.qr_code_type
                       WHEN 'product' THEN p.slug
                       WHEN 'invoice' THEN NULL
                   END as product_slug,
                   a.username as created_by_name
            FROM qr_codes qc
            LEFT JOIN products p ON qc.qr_code_type = 'product' AND qc.reference_id = p.id
            LEFT JOIN orders o ON qc.qr_code_type = 'invoice' AND qc.reference_id = o.id
            LEFT JOIN admin_users a ON qc.created_by = a.id
            WHERE qc.id = ?
        ");
        $stmt->execute([$qrCodeId]);
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
                           WHEN 'custom_link' THEN qc.reference_id
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
                           WHEN 'custom_link' THEN qc.reference_id
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
                   CASE WHEN qs.contact_message_id IS NOT NULL THEN 1 ELSE 0 END as has_contact,
                   cm.name as contact_name,
                   cm.subject as contact_subject
            FROM qr_scans qs
            LEFT JOIN contact_messages cm ON qs.contact_message_id = cm.id
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
                @unlink($filepath);
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

        // Custom Link QR codes
        $stmt = $this->db->query("SELECT COUNT(*) FROM qr_codes WHERE qr_code_type = 'custom_link'");
        $stats['custom_link_qr_codes'] = $stmt->fetchColumn();

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

    /**
     * Get products for dropdown
     */
    public function getProductsForDropdown() {
        $stmt = $this->db->query("SELECT id, name, slug FROM products WHERE active = 1 ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    /**
     * Get orders for dropdown
     */
    public function getOrdersForDropdown() {
        $stmt = $this->db->query("SELECT id, order_number, customer_name FROM orders ORDER BY created_at DESC LIMIT 100");
        return $stmt->fetchAll();
    }
}
