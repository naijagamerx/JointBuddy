<?php

class QRCodeService {
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->uploadDir = __DIR__ . '/../../assets/qr-codes/';
        // Store relative path to allow dynamic domain changes
        $this->uploadUrl = 'qr-codes/'; 

        // Create upload directory if not exists
    }

    public function getAllQRCodes($filterType = null, $limit = 100) {
        try {
            $check = $this->db->query("SHOW TABLES LIKE 'qr_codes'");
            if ($check->rowCount() === 0) {
                return [];
            }

            $sql = "SELECT q.*, 
                    CASE 
                        WHEN q.qr_code_type = 'product' THEN p.name 
                        WHEN q.qr_code_type = 'invoice' THEN CONCAT('Order #', o.order_number)
                        WHEN q.qr_code_type = 'custom_link' THEN q.reference_id
                        ELSE NULL
                    END as reference_name
                    FROM qr_codes q
                    LEFT JOIN products p ON q.qr_code_type = 'product' AND q.reference_id = p.id
                    LEFT JOIN orders o ON q.qr_code_type = 'invoice' AND q.reference_id = o.id";
            
            $params = [];
            if ($filterType) {
                $sql .= " WHERE q.qr_code_type = ?";
                $params[] = $filterType;
            }
            
            $sql .= " ORDER BY q.created_at DESC LIMIT " . (int)$limit;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching QR codes: " . $e->getMessage());
            return [];
        }
    }

    public function getQRStatistics() {
        $stats = [
            'total_qr_codes' => 0,
            'product_qr_codes' => 0,
            'invoice_qr_codes' => 0,
            'custom_link_qr_codes' => 0,
            'scans_today' => 0
        ];

        try {
            $stmt = $this->db->query("SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN qr_code_type = 'product' THEN 1 ELSE 0 END) as products,
                SUM(CASE WHEN qr_code_type = 'invoice' THEN 1 ELSE 0 END) as invoices,
                SUM(CASE WHEN qr_code_type = 'custom_link' THEN 1 ELSE 0 END) as custom_links
                FROM qr_codes");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row) {
                $stats['total_qr_codes'] = $row['total'];
                $stats['product_qr_codes'] = $row['products'];
                $stats['invoice_qr_codes'] = $row['invoices'];
                $stats['custom_link_qr_codes'] = $row['custom_links'];
            }

            // efficient scan count for today if table exists
            $stmt = $this->db->query("SHOW TABLES LIKE 'qr_code_scans'");
            if ($stmt->rowCount() > 0) {
                $stmt = $this->db->query("SELECT COUNT(*) FROM qr_code_scans WHERE DATE(scanned_at) = CURDATE()");
                $stats['scans_today'] = $stmt->fetchColumn();
            }
        } catch (Exception $e) {
            error_log("Error fetching QR stats: " . $e->getMessage());
        }

        return $stats;
    }

    public function deleteQRCode($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM qr_codes WHERE id = ?");
            $stmt->execute([$id]);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function toggleQRCodeStatus($id) {
        try {
            $stmt = $this->db->prepare("UPDATE qr_codes SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$id]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getProductsForDropdown() {
        $stmt = $this->db->query("SELECT id, name FROM products WHERE active = 1 ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOrdersForDropdown() {
        $stmt = $this->db->query("SELECT id, order_number, customer_name FROM orders ORDER BY created_at DESC LIMIT 50");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
