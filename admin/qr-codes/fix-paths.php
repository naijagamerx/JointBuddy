<?php
// Fix QR code paths in database - convert localhost URLs to relative paths
require_once __DIR__ . '/../includes/bootstrap.php';

AuthMiddleware::requireAdmin();

$db = Services::db();

echo "<h1>Fixing QR Code Paths</h1>";

try {
    // Get all QR codes
    $stmt = $db->query("SELECT id, qr_code_image_path FROM qr_codes");
    $qrCodes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $fixed = 0;
    $alreadyOk = 0;

    foreach ($qrCodes as $qr) {
        $path = $qr['qr_code_image_path'];
        $id = $qr['id'];

        // Check if it's a localhost URL
        if (strpos($path, 'http://localhost') !== false || strpos($path, 'https://localhost') !== false) {
            // Extract just the filename
            $filename = basename($path);
            $newPath = 'qr-codes/' . $filename;

            // Update database
            $update = $db->prepare("UPDATE qr_codes SET qr_code_image_path = ? WHERE id = ?");
            $update->execute([$newPath, $id]);

            echo "<p>Fixed ID $id: $path -> $newPath</p>";
            $fixed++;
        } else if (strpos($path, 'http') === 0) {
            // Other full URL - extract filename
            $filename = basename($path);
            $newPath = 'qr-codes/' . $filename;

            $update = $db->prepare("UPDATE qr_codes SET qr_code_image_path = ? WHERE id = ?");
            $update->execute([$newPath, $id]);

            echo "<p>Fixed ID $id: $path -> $newPath</p>";
            $fixed++;
        } else {
            $alreadyOk++;
        }
    }

    echo "<h2>Done!</h2>";
    echo "<p>Fixed: $fixed</p>";
    echo "<p>Already OK: $alreadyOk</p>";
    echo "<p><a href='" . adminUrl('/qr-codes/') . "'>Back to QR Codes</a></p>";

} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
