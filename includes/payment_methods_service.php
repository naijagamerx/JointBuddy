<?php

function ensurePaymentMethodsSchema(PDO $db): void
{
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS payment_methods (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                type VARCHAR(50) NOT NULL,
                description TEXT NULL,
                config TEXT NULL,
                active TINYINT(1) DEFAULT 1,
                is_manual TINYINT(1) DEFAULT 0,
                manual_type VARCHAR(50) DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS payment_method_fields (
                id INT AUTO_INCREMENT PRIMARY KEY,
                payment_method_id INT NOT NULL,
                field_name VARCHAR(255) NOT NULL,
                field_value TEXT,
                sort_order INT DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_payment_method_id (payment_method_id),
                CONSTRAINT fk_payment_method_fields_method FOREIGN KEY (payment_method_id)
                    REFERENCES payment_methods(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $columns = [];
        $stmt = $db->query("DESCRIBE payment_methods");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[$row['Field']] = true;
        }

        if (!isset($columns['description'])) {
            $db->exec("ALTER TABLE payment_methods ADD COLUMN description TEXT NULL AFTER type");
        }
        if (!isset($columns['config'])) {
            $db->exec("ALTER TABLE payment_methods ADD COLUMN config TEXT NULL AFTER description");
        }
        if (!isset($columns['active'])) {
            $db->exec("ALTER TABLE payment_methods ADD COLUMN active TINYINT(1) DEFAULT 1 AFTER config");
        }
        if (!isset($columns['is_manual'])) {
            $db->exec("ALTER TABLE payment_methods ADD COLUMN is_manual TINYINT(1) DEFAULT 0 AFTER active");
        }
        if (!isset($columns['manual_type'])) {
            $db->exec("ALTER TABLE payment_methods ADD COLUMN manual_type VARCHAR(50) DEFAULT NULL AFTER is_manual");
        }
        if (!isset($columns['qr_code_path'])) {
            $db->exec("ALTER TABLE payment_methods ADD COLUMN qr_code_path VARCHAR(255) NULL AFTER manual_type");
        }
        if (!isset($columns['color'])) {
            $db->exec("ALTER TABLE payment_methods ADD COLUMN color VARCHAR(7) DEFAULT '#6B7280' AFTER qr_code_path");
        }
        if (!isset($columns['created_at'])) {
            $db->exec("ALTER TABLE payment_methods ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP AFTER color");
        }
        if (!isset($columns['updated_at'])) {
            $db->exec("ALTER TABLE payment_methods ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
        }
    } catch (Exception $e) {
        error_log('ensurePaymentMethodsSchema error: ' . $e->getMessage());
    }
}

function createManualPaymentMethod(PDO $db, array $data): int
{
    ensurePaymentMethodsSchema($db);

    $name = trim($data['name'] ?? '');
    $manualType = trim($data['manual_type'] ?? '');
    $description = trim($data['description'] ?? '');
    $active = !empty($data['active']) ? 1 : 0;
    $fields = $data['fields'] ?? [];
    $qrCodePath = trim($data['qr_code_path'] ?? '');
    $color = trim($data['color'] ?? '#6B7280');

    if ($name === '' || $manualType === '') {
        throw new InvalidArgumentException('Name and Type are required');
    }

    $type = 'manual_custom';
    if ($manualType === 'bank') {
        $type = 'bank_transfer';
    } elseif ($manualType === 'crypto') {
        $type = 'crypto';
    }

    $stmt = $db->prepare("SELECT id FROM payment_methods WHERE name = ?");
    $stmt->execute([$name]);
    if ($stmt->fetch()) {
        throw new RuntimeException('A payment method with this name already exists');
    }

    $stmt = $db->prepare("
        INSERT INTO payment_methods (name, type, description, config, active, is_manual, manual_type, qr_code_path, color, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, 1, ?, ?, ?, NOW(), NOW())
    ");

    $config = '';
    $stmt->execute([$name, $type, $description, $config, $active, $manualType, $qrCodePath, $color]);

    $methodId = (int)$db->lastInsertId();

    if (!empty($fields)) {
        $fieldStmt = $db->prepare("
            INSERT INTO payment_method_fields (payment_method_id, field_name, field_value, sort_order)
            VALUES (?, ?, ?, ?)
        ");

        $sort = 0;
        foreach ($fields as $field) {
            $fieldName = trim($field['name'] ?? '');
            $fieldValue = trim($field['value'] ?? '');
            if ($fieldName === '' && $fieldValue === '') {
                continue;
            }
            $fieldStmt->execute([$methodId, $fieldName, $fieldValue, $sort]);
            $sort++;
        }
    }

    return $methodId;
}

function getManualPaymentDetailsByType(PDO $db, string $type): ?array
{
    ensurePaymentMethodsSchema($db);

    $stmt = $db->prepare("
        SELECT * FROM payment_methods
        WHERE type = ? AND active = 1 AND is_manual = 1
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([$type]);
    $method = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$method) {
        return null;
    }

    $fieldStmt = $db->prepare("
        SELECT field_name, field_value, sort_order
        FROM payment_method_fields
        WHERE payment_method_id = ?
        ORDER BY sort_order ASC, id ASC
    ");
    $fieldStmt->execute([$method['id']]);
    $fields = $fieldStmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'method' => $method,
        'fields' => $fields,
    ];
}

