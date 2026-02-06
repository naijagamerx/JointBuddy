<?php
// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

// Require authentication (admin only)
AuthMiddleware::requireAdmin();

// Get database connection from services
$db = Services::db();

if ($db) {
    $defaultCurrencies = [
        ['ZAR', 'South African Rand', 'R'],
        ['USD', 'US Dollar', '$'],
        ['EUR', 'Euro', '€'],
        ['GBP', 'British Pound', '£'],
        ['CAD', 'Canadian Dollar', 'C$'],
        ['AUD', 'Australian Dollar', 'A$'],
        ['NGN', 'Nigerian Naira', '₦'],
        ['KES', 'Kenyan Shilling', 'KSh'],
        ['BWP', 'Botswana Pula', 'P'],
        ['NAD', 'Namibian Dollar', '$']
    ];

    foreach ($defaultCurrencies as $cur) {
        $stmt = $db->prepare("INSERT INTO currencies (code, name, symbol, is_active) VALUES (?, ?, ?, 1) ON DUPLICATE KEY UPDATE name = VALUES(name), symbol = VALUES(symbol)");
        $stmt->execute([$cur[0], $cur[1], $cur[2]]);
        $stmt = $db->prepare("INSERT IGNORE INTO exchange_rates (currency_code, rate, updated_at) VALUES (?, 1.0000, NOW())");
        $stmt->execute([$cur[0]]);
    }
}

$service = new CurrencyService($db);
$message = '';
$messageType = 'success';

// Check if editing
$editingCurrency = null;
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $editCode = $_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM currencies WHERE code = ?");
    $stmt->execute([$editCode]);
    $editingCurrency = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$editingCurrency) {
        $message = "Currency not found.";
        $messageType = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['add_currency'])) {
            $code = strtoupper(trim($_POST['code']));
            $name = trim($_POST['name']);
            $symbol = trim($_POST['symbol']);
            $iconPath = null;

            // Handle icon upload
            if (isset($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../assets/currency-icons/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $fileExt = strtolower(pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION));
                $allowedExts = ['svg', 'png', 'jpg', 'jpeg', 'gif'];

                if (in_array($fileExt, $allowedExts)) {
                    $iconFileName = $code . '_' . time() . '.' . $fileExt;
                    $iconFullPath = $uploadDir . $iconFileName;

                    if (move_uploaded_file($_FILES['icon']['tmp_name'], $iconFullPath)) {
                        $iconPath = 'assets/currency-icons/' . $iconFileName;
                    }
                }
            }

            if ($code && $name && $symbol) {
                if ($iconPath) {
                    $stmt = $db->prepare("INSERT INTO currencies (code, name, symbol, icon, is_active) VALUES (?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE name = ?, symbol = ?, icon = ?");
                    $stmt->execute([$code, $name, $symbol, $iconPath, $name, $symbol, $iconPath]);
                } else {
                    $stmt = $db->prepare("INSERT INTO currencies (code, name, symbol, is_active) VALUES (?, ?, ?, 1) ON DUPLICATE KEY UPDATE name = ?, symbol = ?");
                    $stmt->execute([$code, $name, $symbol, $name, $symbol]);
                }

                // Set initial rate if not exists
                $stmt = $db->prepare("INSERT IGNORE INTO exchange_rates (currency_code, rate, updated_at) VALUES (?, 1.0000, NOW())");
                $stmt->execute([$code]);

                $message = $editingCurrency ? "Currency $code updated successfully." : "Currency $code saved successfully.";
                $editingCurrency = null; // Clear editing state after successful update
            } else {
                $message = "All fields are required.";
                $messageType = 'error';
            }
        }

        if (isset($_POST['update_rate'])) {
            $code = $_POST['code'];
            $rate = (float)$_POST['rate'];
            if ($rate > 0) {
                $service->updateRate($code, $rate);
                $message = "Exchange rate for $code updated.";
            } else {
                $message = "Rate must be positive.";
                $messageType = 'error';
            }
        }

        if (isset($_POST['set_default'])) {
            $code = $_POST['code'];
            $db->exec("UPDATE currencies SET is_default = 0");
            $stmt = $db->prepare("UPDATE currencies SET is_default = 1 WHERE code = ?");
            $stmt->execute([$code]);
            $message = "Default currency set to $code.";
        }

        if (isset($_POST['toggle_active'])) {
            $code = $_POST['code'];
            $isActive = (int)$_POST['is_active'];
            $stmt = $db->prepare("UPDATE currencies SET is_active = ? WHERE code = ?");
            $stmt->execute([$isActive, $code]);
            $message = "Currency $code status updated.";
        }

        if (isset($_POST['delete_currency'])) {
            $code = $_POST['code'];
            // Prevent deleting default currency
            $stmt = $db->prepare("SELECT is_default FROM currencies WHERE code = ?");
            $stmt->execute([$code]);
            $currency = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($currency && !$currency['is_default']) {
                // Delete the currency
                $stmt = $db->prepare("DELETE FROM currencies WHERE code = ?");
                $stmt->execute([$code]);
                $message = "Currency $code deleted successfully.";
            } else {
                $message = "Cannot delete the default currency.";
                $messageType = 'error';
            }
        }

    } catch (Exception $e) {
        $message = AppError::handleDatabaseError($e, 'Error processing request');
        $messageType = 'error';
    }
}

$currencies = $service->getAllCurrencies();

$content = '<div class="max-w-7xl mx-auto">';
if ($message) { $content .= adminAlert($message, $messageType); }

$content .= '<div class="mb-6 flex justify-between items-center">';
$content .= '<h2 class="text-2xl font-bold text-gray-900">Currency Management</h2>';
$content .= $editingCurrency ? '<a href="' . adminUrl('/settings/currency/') . '" class="text-sm text-gray-600 hover:text-gray-800"><i class="fas fa-arrow-left mr-1"></i>Back to Add New</a>' : '';
$content .= '</div>';

// Add / Edit Currency Form
$content .= '<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">';
$content .= '<h3 class="text-lg font-semibold text-gray-900 mb-4">' . ($editingCurrency ? 'Edit Currency: ' . htmlspecialchars($editingCurrency['code']) : 'Add / Edit Currency') . '</h3>';

if ($editingCurrency) {
    if (!empty($editingCurrency['icon'])) {
        $fileExt = strtolower(pathinfo($editingCurrency['icon'], PATHINFO_EXTENSION));
        if ($fileExt === 'svg') {
            $iconPath = __DIR__ . '/../../' . $editingCurrency['icon'];
            if (file_exists($iconPath)) {
                $svgContent = file_get_contents($iconPath);
                $svgContent = str_replace('<svg', '<svg class="w-16 h-16 inline-block mr-4"', $svgContent);
                $content .= '<div class="mb-4 p-4 bg-gray-50 rounded-lg border">';
                $content .= '<p class="text-sm text-gray-600 mb-2">Current Icon:</p>';
                $content .= $svgContent;
                $content .= '<p class="text-xs text-gray-500 inline-block">Upload a new icon below to replace this one</p>';
                $content .= '</div>';
            }
        } else {
            $content .= '<div class="mb-4 p-4 bg-gray-50 rounded-lg border">';
            $content .= '<p class="text-sm text-gray-600 mb-2">Current Icon:</p>';
            $content .= '<img src="' . assetUrl(htmlspecialchars($editingCurrency['icon'])) . '" alt="' . htmlspecialchars($editingCurrency['code']) . '" class="w-16 h-16 inline-block mr-4">';
            $content .= '<p class="text-xs text-gray-500 inline-block">Upload a new icon below to replace this one</p>';
            $content .= '</div>';
        }
    }
}

$content .= '<form method="POST" enctype="multipart/form-data" class="space-y-4">
    ' . csrf_field() . '
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">';
$content .= '<div>';
$content .= '<label class="block text-sm font-medium text-gray-700 mb-1">Currency Code *</label>';
$content .= '<input type="text" name="code" value="' . htmlspecialchars($editingCurrency['code'] ?? '') . '" placeholder="e.g., ZAR, USD" class="w-full border rounded px-3 py-2" required maxlength="3" ' . ($editingCurrency ? 'readonly' : '') . '>';
if ($editingCurrency) {
    $content .= '<p class="text-xs text-gray-500 mt-1">Currency code cannot be changed</p>';
}
$content .= '</div>';
$content .= '<div>';
$content .= '<label class="block text-sm font-medium text-gray-700 mb-1">Currency Name *</label>';
$content .= '<input type="text" name="name" value="' . htmlspecialchars($editingCurrency['name'] ?? '') . '" placeholder="e.g., South African Rand" class="w-full border rounded px-3 py-2" required>';
$content .= '</div>';
$content .= '<div>';
$content .= '<label class="block text-sm font-medium text-gray-700 mb-1">Symbol *</label>';
$content .= '<input type="text" name="symbol" value="' . htmlspecialchars($editingCurrency['symbol'] ?? '') . '" placeholder="e.g., R, $, €" class="w-full border rounded px-3 py-2" required>';
$content .= '</div>';
$content .= '</div>';
$content .= '<div>';
$content .= '<label class="block text-sm font-medium text-gray-700 mb-1">Currency Icon (SVG, PNG, JPG, GIF)</label>';
$content .= '<input type="file" name="icon" accept=".svg,.png,.jpg,.jpeg,.gif" class="w-full border rounded px-3 py-2">';
$content .= '<p class="text-xs text-gray-500 mt-1">Upload a flag icon or currency symbol. Max size: 2MB</p>';
$content .= '</div>';
$content .= '<button type="submit" name="add_currency" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">' . ($editingCurrency ? 'Update Currency' : 'Save Currency') . '</button>';
$content .= '</form>';
$content .= '</div>';

// Currencies Table
$rows = [];
foreach ($currencies as $c) {
    $defaultBadge = $c['is_default'] ? '<span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded">Default</span>' : '';

    $iconDisplay = '';
    if (!empty($c['icon'])) {
        $fileExt = strtolower(pathinfo($c['icon'], PATHINFO_EXTENSION));
        if ($fileExt === 'svg') {
            $iconPath = __DIR__ . '/../../' . $c['icon'];
            if (file_exists($iconPath)) {
                $svgContent = file_get_contents($iconPath);
                $svgContent = str_replace('<svg', '<svg class="w-8 h-8 inline-block mr-2"', $svgContent);
                $iconDisplay = $svgContent;
            }
        } else {
            $iconDisplay = '<img src="' . assetUrl(htmlspecialchars($c['icon'])) . '" alt="' . htmlspecialchars($c['code']) . '" class="w-8 h-8 inline-block mr-2">';
        }
    }

    // Edit button
    $editBtn = '<a href="' . adminUrl('/settings/currency/?edit=' . urlencode($c['code'])) . '" class="text-blue-600 hover:text-blue-800 text-sm">
                    <i class="fas fa-edit mr-1"></i>Edit
                </a>';

    // Delete button (only show if not default currency)
    $deleteBtn = !$c['is_default'] ? '<form method="POST" class="inline ml-2" onsubmit="return confirm(\'Are you sure you want to delete ' . htmlspecialchars($c['code']) . '?\')">
        ' . csrf_field() . '
        <input type="hidden" name="code" value="'.$c['code'].'">
        <button type="submit" name="delete_currency" class="text-red-600 hover:text-red-800 text-sm">
            <i class="fas fa-trash mr-1"></i>Delete
        </button>
    </form>' : '';

    $statusBtn = '<form method="POST" class="inline">
        ' . csrf_field() . '
        <input type="hidden" name="code" value="'.$c['code'].'">
        <input type="hidden" name="is_active" value="'.($c['is_active'] ? 0 : 1).'">
        <button type="submit" name="toggle_active" class="text-sm '.($c['is_active'] ? 'text-green-600' : 'text-red-600').'">
            '.($c['is_active'] ? 'Active' : 'Inactive').'
        </button>
    </form>';

    $setDefBtn = !$c['is_default'] ? '<form method="POST" class="inline ml-2">
        ' . csrf_field() . '
        <input type="hidden" name="code" value="'.$c['code'].'">
        <button type="submit" name="set_default" class="text-xs bg-gray-200 hover:bg-gray-300 px-2 py-1 rounded text-gray-700">Set Default</button>
    </form>' : '';

    $rateForm = '<form method="POST" class="flex items-center space-x-2">
        ' . csrf_field() . '
        <input type="hidden" name="code" value="'.$c['code'].'">
        <input type="number" step="0.0001" name="rate" value="'.($c['rate'] ?? 1.0000).'" class="w-24 border rounded px-2 py-1 text-sm">
        <button type="submit" name="update_rate" class="text-blue-600 hover:text-blue-800"><i class="fas fa-save"></i></button>
    </form>';

    $rows[] = [
        '<div class="flex items-center">' . $iconDisplay . '<strong>' . htmlspecialchars($c['code']) . '</strong> ' . $defaultBadge . $setDefBtn . '</div>',
        htmlspecialchars($c['name']),
        htmlspecialchars($c['symbol']),
        $rateForm,
        $editBtn . $deleteBtn . '<br>' . $statusBtn
    ];
}

$content .= '<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">';
$content .= adminTable(['Currency', 'Name', 'Symbol', 'Exchange Rate (vs Default)', 'Actions'], $rows);
$content .= '</div>';

$content .= '</div>';

echo adminSidebarWrapper('Currency', $content, 'currency');
