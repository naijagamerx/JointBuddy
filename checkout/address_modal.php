<?php
session_start();
require_once __DIR__ . '/../includes/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];
$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->prepare("
        SELECT id, name, address, city, postal_code, country, phone, 
               default_for_shipping, label, address_type
        FROM user_addresses
        WHERE user_id = ?
        ORDER BY default_for_shipping DESC, created_at DESC
    ");
    $stmt->execute([$userId]);
    $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'addresses' => $addresses
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load addresses: ' . $e->getMessage()
    ]);
}
