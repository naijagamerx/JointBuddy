<?php
// AJAX endpoint for fetching payment method details
require_once __DIR__ . '/../../includes/bootstrap.php';

// Require authentication (admin only)
AuthMiddleware::requireAdmin();

header('Content-Type: application/json');

$methodId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($methodId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid payment method ID'
    ]);
    exit;
}

$db = Services::db();

if (!$db) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit;
}

try {
    // Get payment method details
    $stmt = $db->prepare("SELECT * FROM payment_methods WHERE id = ?");
    $stmt->execute([$methodId]);
    $method = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$method) {
        echo json_encode([
            'success' => false,
            'message' => 'Payment method not found'
        ]);
        exit;
    }

    // Get custom fields - table may not exist
    $fields = [];
    try {
        $fieldStmt = $db->prepare("
            SELECT field_name, field_value
            FROM payment_method_fields
            WHERE payment_method_id = ?
            ORDER BY sort_order ASC, id ASC
        ");
        $fieldStmt->execute([$methodId]);
        $fields = $fieldStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Exception $e) {
        // Table may not exist, ignore
        $fields = [];
    }

    // Prepare response
    $response = [
        'success' => true,
        'payment_method' => [
            'id' => $method['id'],
            'name' => $method['name'],
            'type' => $method['type'],
            'manual_type' => $method['manual_type'] ?? null,
            'description' => $method['description'] ?? '',
            'active' => (int)($method['active'] ?? 0),
            'color' => $method['color'] ?? '#6B7280',
            'qr_code_path' => $method['qr_code_path'] ?? '',
            'asset_url' => !empty($method['qr_code_path']) ? url($method['qr_code_path']) : '',
        ],
        'fields' => $fields
    ];

    echo json_encode($response);

} catch (Exception $e) {
    error_log('Error fetching payment method details: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error loading payment method: ' . $e->getMessage()
    ]);
}
