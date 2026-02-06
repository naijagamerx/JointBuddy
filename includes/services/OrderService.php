<?php

class OrderService {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getDeliveryMethods() {
        try {
            // Check if table exists first to avoid fatal errors if migration didn't run
             $stmt = $this->db->query("SHOW TABLES LIKE 'delivery_methods'");
             if ($stmt->rowCount() > 0) {
                 $stmt = $this->db->query("SELECT * FROM delivery_methods WHERE active = 1 ORDER BY sort_order ASC, name ASC");
                 return $stmt->fetchAll(PDO::FETCH_ASSOC);
             }
             return [];
        } catch (Exception $e) {
            error_log("Error getting delivery methods: " . $e->getMessage());
            return [];
        }
    }

    public function getPaymentMethods() {
        try {
             $stmt = $this->db->query("SHOW TABLES LIKE 'payment_methods'");
             if ($stmt->rowCount() > 0) {
                // We select default_status if it exists, otherwise default to 'pending'
                $cols = "id, name, active";
                // Check columns to be safe
                $colStmt = $this->db->query("SHOW COLUMNS FROM payment_methods LIKE 'default_status'");
                if ($colStmt->rowCount() > 0) {
                    $cols .= ", default_status";
                }
                
                $stmt = $this->db->query("SELECT $cols FROM payment_methods WHERE active = 1 ORDER BY name ASC");
                $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Polyfill default_status if logic depends on it but column missing
                foreach ($methods as &$method) {
                    if (!isset($method['default_status'])) {
                        $method['default_status'] = 'pending';
                    }
                }
                return $methods;
             }
             return [];
        } catch (Exception $e) {
            error_log("Error getting payment methods: " . $e->getMessage());
            return [];
        }
    }
}
