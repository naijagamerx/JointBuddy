<?php
/**
 * User Addresses Service
 * Provides functions for managing user addresses
 */

/**
 * Get all addresses for a user
 *
 * @param PDO $db Database connection
 * @param int $userId User ID
 * @return array Array of addresses
 */
function getUserAddresses(PDO $db, int $userId): array
{
    try {
        $stmt = $db->prepare("
            SELECT *,
                   CONCAT(first_name, ' ', last_name) as name
            FROM user_addresses
            WHERE user_id = ?
            ORDER BY default_for_shipping DESC, created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching user addresses: " . $e->getMessage());
        return [];
    }
}

/**
 * Get the default shipping address for a user
 *
 * @param PDO $db Database connection
 * @param int $userId User ID
 * @return array|null Default address or null if not found
 */
function getDefaultAddress(PDO $db, int $userId): ?array
{
    try {
        $stmt = $db->prepare("
            SELECT *,
                   CONCAT(first_name, ' ', last_name) as name
            FROM user_addresses
            WHERE user_id = ? AND default_for_shipping = 1
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $address = $stmt->fetch(PDO::FETCH_ASSOC);
        return $address ?: null;
    } catch (Exception $e) {
        error_log("Error fetching default address: " . $e->getMessage());
        return null;
    }
}

/**
 * Save a new user address
 *
 * @param PDO $db Database connection
 * @param int $userId User ID
 * @param array $data Address data
 * @return int|false New address ID or false on failure
 */
function saveUserAddress(PDO $db, int $userId, array $data)
{
    try {
        // Validate address data
        $validation = validateAddressData($data);
        if (!$validation['valid']) {
            throw new InvalidArgumentException($validation['message']);
        }

        // If this is set as default, unset other defaults first
        if (!empty($data['default_for_shipping'])) {
            unsetDefaultAddresses($db, $userId);
        }

        $stmt = $db->prepare("
            INSERT INTO user_addresses (
                user_id, label, first_name, last_name, phone,
                address_line1, address_line2, city, province,
                postal_code, country, address_type,
                default_for_shipping, delivery_instructions
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $result = $stmt->execute([
            $userId,
            $data['label'] ?? 'Home',
            $data['first_name'],
            $data['last_name'],
            $data['phone'],
            $data['address_line1'],
            $data['address_line2'] ?? null,
            $data['city'],
            $data['province'] ?? null,
            $data['postal_code'],
            $data['country'] ?? 'South Africa',
            $data['address_type'] ?? 'residential',
            !empty($data['default_for_shipping']) ? 1 : 0,
            $data['delivery_instructions'] ?? null
        ]);

        if ($result) {
            return $db->lastInsertId();
        }
        return false;
    } catch (Exception $e) {
        error_log("Error saving user address: " . $e->getMessage());
        return false;
    }
}

/**
 * Update an existing user address
 *
 * @param PDO $db Database connection
 * @param int $userId User ID
 * @param int $addressId Address ID
 * @param array $data Address data
 * @return bool True on success, false on failure
 */
function updateUserAddress(PDO $db, int $userId, int $addressId, array $data): bool
{
    try {
        // Verify address belongs to user
        if (!addressBelongsToUser($db, $addressId, $userId)) {
            throw new InvalidArgumentException("Address does not belong to user");
        }

        // Validate address data
        $validation = validateAddressData($data);
        if (!$validation['valid']) {
            throw new InvalidArgumentException($validation['message']);
        }

        // If this is set as default, unset other defaults first
        if (!empty($data['default_for_shipping'])) {
            unsetDefaultAddresses($db, $userId);
        }

        $stmt = $db->prepare("
            UPDATE user_addresses SET
                label = ?, first_name = ?, last_name = ?, phone = ?,
                address_line1 = ?, address_line2 = ?, city = ?, province = ?,
                postal_code = ?, country = ?, address_type = ?,
                default_for_shipping = ?, delivery_instructions = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND user_id = ?
        ");

        return $stmt->execute([
            $data['label'] ?? 'Home',
            $data['first_name'],
            $data['last_name'],
            $data['phone'],
            $data['address_line1'],
            $data['address_line2'] ?? null,
            $data['city'],
            $data['province'] ?? null,
            $data['postal_code'],
            $data['country'] ?? 'South Africa',
            $data['address_type'] ?? 'residential',
            !empty($data['default_for_shipping']) ? 1 : 0,
            $data['delivery_instructions'] ?? null,
            $addressId,
            $userId
        ]);
    } catch (Exception $e) {
        error_log("Error updating user address: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a user address
 *
 * @param PDO $db Database connection
 * @param int $userId User ID
 * @param int $addressId Address ID
 * @return bool True on success, false on failure
 */
function deleteUserAddress(PDO $db, int $userId, int $addressId): bool
{
    try {
        // Verify address belongs to user
        if (!addressBelongsToUser($db, $addressId, $userId)) {
            throw new InvalidArgumentException("Address does not belong to user");
        }

        $stmt = $db->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
        return $stmt->execute([$addressId, $userId]);
    } catch (Exception $e) {
        error_log("Error deleting user address: " . $e->getMessage());
        return false;
    }
}

/**
 * Set an address as the default shipping address
 *
 * @param PDO $db Database connection
 * @param int $userId User ID
 * @param int $addressId Address ID
 * @return bool True on success, false on failure
 */
function setDefaultAddress(PDO $db, int $userId, int $addressId): bool
{
    try {
        // Verify address belongs to user
        if (!addressBelongsToUser($db, $addressId, $userId)) {
            throw new InvalidArgumentException("Address does not belong to user");
        }

        // Unset all defaults for this user
        unsetDefaultAddresses($db, $userId);

        // Set this address as default
        $stmt = $db->prepare("
            UPDATE user_addresses
            SET default_for_shipping = 1, updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND user_id = ?
        ");

        return $stmt->execute([$addressId, $userId]);
    } catch (Exception $e) {
        error_log("Error setting default address: " . $e->getMessage());
        return false;
    }
}

/**
 * Validate address data
 *
 * @param array $data Address data to validate
 * @return array Validation result with 'valid' boolean and 'message' string
 */
function validateAddressData(array $data): array
{
    $required = ['first_name', 'last_name', 'phone', 'address_line1', 'city', 'postal_code'];

    foreach ($required as $field) {
        if (empty($data[$field])) {
            return [
                'valid' => false,
                'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required'
            ];
        }
    }

    // Validate phone number (basic check)
    if (!preg_match('/^[0-9+\-\s\(\)]{7,20}$/', $data['phone'])) {
        return [
            'valid' => false,
            'message' => 'Please enter a valid phone number'
        ];
    }

    // Validate postal code (basic check)
    if (!preg_match('/^[0-9A-Za-z\s\-]{3,10}$/', $data['postal_code'])) {
        return [
            'valid' => false,
            'message' => 'Please enter a valid postal code'
        ];
    }

    return ['valid' => true, 'message' => 'Valid'];
}

/**
 * Check if an address belongs to a user
 *
 * @param PDO $db Database connection
 * @param int $addressId Address ID
 * @param int $userId User ID
 * @return bool True if address belongs to user
 */
function addressBelongsToUser(PDO $db, int $addressId, int $userId): bool
{
    try {
        $stmt = $db->prepare("SELECT id FROM user_addresses WHERE id = ? AND user_id = ?");
        $stmt->execute([$addressId, $userId]);
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        error_log("Error checking address ownership: " . $e->getMessage());
        return false;
    }
}

/**
 * Unset all default addresses for a user
 *
 * @param PDO $db Database connection
 * @param int $userId User ID
 * @return void
 */
function unsetDefaultAddresses(PDO $db, int $userId): void
{
    try {
        $stmt = $db->prepare("
            UPDATE user_addresses
            SET default_for_shipping = 0
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
    } catch (Exception $e) {
        error_log("Error unsetting default addresses: " . $e->getMessage());
    }
}

/**
 * Format address for display
 *
 * @param array $address Address data
 * @return string Formatted address string
 */
function formatAddress(array $address): string
{
    $formatted = "";

    // Address type
    $formatted .= ucfirst($address['address_type'] ?? 'residential') . "\n";

    // Address lines
    $formatted .= $address['address_line1'] . "\n";
    if (!empty($address['address_line2'])) {
        $formatted .= $address['address_line2'] . "\n";
    }

    // City, province, postal code
    $cityLine = $address['city'];
    if (!empty($address['province'])) {
        $cityLine .= ", " . $address['province'];
    }
    if (!empty($address['postal_code'])) {
        $cityLine .= ", " . $address['postal_code'];
    }
    $formatted .= $cityLine . "\n";

    // Name and phone
    $formatted .= trim($address['first_name'] . ' ' . $address['last_name']) . "\n";
    $formatted .= $address['phone'];

    return $formatted;
}

/**
 * Get address statistics for a user
 *
 * @param PDO $db Database connection
 * @param int $userId User ID
 * @return array Statistics
 */
function getAddressStats(PDO $db, int $userId): array
{
    try {
        // Total addresses
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM user_addresses WHERE user_id = ?");
        $stmt->execute([$userId]);
        $totalAddresses = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Default address
        $defaultAddress = getDefaultAddress($db, $userId);

        // Unique cities (delivery zones)
        $stmt = $db->prepare("SELECT DISTINCT city FROM user_addresses WHERE user_id = ?");
        $stmt->execute([$userId]);
        $cities = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $deliveryZones = count($cities);

        return [
            'total_addresses' => $totalAddresses,
            'default_address' => $defaultAddress,
            'delivery_zones' => $deliveryZones
        ];
    } catch (Exception $e) {
        error_log("Error getting address stats: " . $e->getMessage());
        return [
            'total_addresses' => 0,
            'default_address' => null,
            'delivery_zones' => 0
        ];
    }
}
