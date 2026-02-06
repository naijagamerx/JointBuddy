<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Use bootstrap to load all dependencies including AdminAuth
require_once __DIR__ . '/../../includes/bootstrap.php';

// Get database and auth from Services
$db = Services::db();
$adminAuth = Services::adminAuth();

header('Content-Type: application/json');

if (!$adminAuth || !$adminAuth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_FILES['images'])) {
    echo json_encode(['success' => false, 'message' => 'No images uploaded']);
    exit;
}

$uploadedUrls = [];
$errors = [];
$targetDir = __DIR__ . '/../../assets/images/products/';

// Create directory if not exists
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

// Normalize info
$files = $_FILES['images'];
$count = is_array($files['name']) ? count($files['name']) : 1;

for ($i = 0; $i < $count; $i++) {
    $fileName = is_array($files['name']) ? $files['name'][$i] : $files['name'];
    $fileTmp = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
    $fileError = is_array($files['error']) ? $files['error'][$i] : $files['error'];
    $fileSize = is_array($files['size']) ? $files['size'][$i] : $files['size'];
    
    if ($fileError !== UPLOAD_ERR_OK) {
        $errors[] = "Error uploading $fileName";
        continue;
    }
    
    // Validate Extension (WEBP support added)
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    
    if (!in_array($ext, $allowed)) {
        $errors[] = "Invalid file type for $fileName. Allowed: " . implode(', ', $allowed);
        continue;
    }
    
    // Generate unique name
    $newFileName = uniqid('prod_') . '.' . $ext;
    $targetFile = $targetDir . $newFileName;
    
    if (move_uploaded_file($fileTmp, $targetFile)) {
        // Use assetUrl() to generate correct path for any deployment
        $uploadedUrls[] = assetUrl('images/products/' . $newFileName);
    } else {
        $errors[] = "Failed to save $fileName";
    }
}

if (!empty($uploadedUrls)) {
    echo json_encode([
        'success' => true, 
        'urls' => $uploadedUrls, 
        'message' => count($uploadedUrls) . ' images uploaded successfully.' . (empty($errors) ? '' : ' Errors: ' . implode(', ', $errors))
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'No files uploaded. ' . implode(', ', $errors)]);
}
