<?php
/**
 * Gift Voucher Service
 * Handles voucher validation, redemption, and balance management
 */

if (!function_exists('createVoucher')) {
    /**
     * Create a new gift voucher
     */
    function createVoucher($db, $data) {
        try {
            $sql = "INSERT INTO gift_vouchers (code, amount, description, created_by, created_for, expires_at, max_uses, notes)
                    VALUES (:code, :amount, :description, :created_by, :created_for, :expires_at, :max_uses, :notes)";
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                ':code' => strtoupper($data['code']),
                ':amount' => $data['amount'],
                ':description' => $data['description'] ?? null,
                ':created_by' => $data['created_by'],
                ':created_for' => $data['created_for'] ?? null,
                ':expires_at' => $data['expires_at'] ?? null,
                ':max_uses' => $data['max_uses'] ?? 1,
                ':notes' => $data['notes'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log("Error creating voucher: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('getVoucherByCode')) {
    /**
     * Get voucher by code
     */
    function getVoucherByCode($db, $code) {
        try {
            $sql = "SELECT * FROM gift_vouchers WHERE code = :code AND is_active = 1";
            $stmt = $db->prepare($sql);
            $stmt->execute([':code' => strtoupper($code)]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting voucher: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('validateVoucher')) {
    /**
     * Validate if voucher can be redeemed
     */
    function validateVoucher($db, $voucher, $userId) {
        $errors = [];

        // Check if voucher exists
        if (!$voucher) {
            return ['valid' => false, 'message' => 'Invalid voucher code'];
        }

        // Check if expired
        if ($voucher['expires_at'] && strtotime($voucher['expires_at']) < time()) {
            return ['valid' => false, 'message' => 'This voucher has expired'];
        }

        // Check if max uses reached
        if ($voucher['max_uses'] && $voucher['current_uses'] >= $voucher['max_uses']) {
            return ['valid' => false, 'message' => 'This voucher has already been used'];
        }

        // Check if voucher is for specific user
        if ($voucher['created_for'] && $voucher['created_for'] != $userId) {
            return ['valid' => false, 'message' => 'This voucher is not valid for your account'];
        }

        // Check if user already redeemed this voucher
        $sql = "SELECT COUNT(*) as count FROM gift_voucher_redemptions
                WHERE voucher_id = :voucher_id AND user_id = :user_id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':voucher_id' => $voucher['id'], ':user_id' => $userId]);
        $alreadyRedeemed = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;

        if ($alreadyRedeemed) {
            return ['valid' => false, 'message' => 'You have already redeemed this voucher'];
        }

        return ['valid' => true, 'voucher' => $voucher];
    }
}

if (!function_exists('redeemVoucher')) {
    /**
     * Redeem a voucher for a user
     */
    function redeemVoucher($db, $voucher, $userId) {
        try {
            $db->beginTransaction();

            // Get or create user balance record
            $sql = "SELECT * FROM user_voucher_balance WHERE user_id = :user_id FOR UPDATE";
            $stmt = $db->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            $balance = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$balance) {
                $sql = "INSERT INTO user_voucher_balance (user_id, balance, total_earned)
                        VALUES (:user_id, :balance_amount, :total_earned_amount)";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    ':user_id' => $userId,
                    ':balance_amount' => $voucher['amount'],
                    ':total_earned_amount' => $voucher['amount']
                ]);
                $newBalance = $voucher['amount'];
            } else {
                $newBalance = $balance['balance'] + $voucher['amount'];
                $sql = "UPDATE user_voucher_balance
                        SET balance = balance + :balance_amount,
                            total_earned = total_earned + :total_earned_amount
                        WHERE user_id = :user_id";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    ':balance_amount' => $voucher['amount'],
                    ':total_earned_amount' => $voucher['amount'],
                    ':user_id' => $userId
                ]);
            }

            // Record redemption
            $ipAddress = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
            $sql = "INSERT INTO gift_voucher_redemptions (voucher_id, user_id, amount, balance_after, ip_address)
                    VALUES (:voucher_id, :user_id, :amount, :balance_after, :ip_address)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':voucher_id' => $voucher['id'],
                ':user_id' => $userId,
                ':amount' => $voucher['amount'],
                ':balance_after' => $newBalance,
                ':ip_address' => $ipAddress
            ]);

            // Update voucher use count
            $sql = "UPDATE gift_vouchers SET current_uses = current_uses + 1 WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute([':id' => $voucher['id']]);

            $db->commit();

            return [
                'success' => true,
                'message' => 'Voucher redeemed successfully! R' . number_format($voucher['amount'], 2) . ' has been added to your account.',
                'new_balance' => $newBalance
            ];
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Error redeeming voucher: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error redeeming voucher. Please try again.'];
        }
    }
}

if (!function_exists('getUserVoucherBalance')) {
    /**
     * Get user's voucher balance
     */
    function getUserVoucherBalance($db, $userId) {
        try {
            $sql = "SELECT * FROM user_voucher_balance WHERE user_id = :user_id";
            $stmt = $db->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting user balance: " . $e->getMessage());
            return ['balance' => 0, 'total_earned' => 0, 'total_spent' => 0];
        }
    }
}

if (!function_exists('getUserRedemptions')) {
    /**
     * Get user's voucher redemptions
     */
    function getUserRedemptions($db, $userId, $limit = 10) {
        try {
            $sql = "SELECT gvr.*, gv.code, gv.description
                    FROM gift_voucher_redemptions gvr
                    JOIN gift_vouchers gv ON gvr.voucher_id = gv.id
                    WHERE gvr.user_id = :user_id
                    ORDER BY gvr.redeemed_at DESC
                    LIMIT :limit";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting user redemptions: " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('getAllVouchers')) {
    /**
     * Get all vouchers (for admin)
     */
    function getAllVouchers($db, $limit = 50, $offset = 0) {
        try {
            $sql = "SELECT gv.*,
                           (SELECT COUNT(*) FROM gift_voucher_redemptions WHERE voucher_id = gv.id) as redemption_count,
                           (SELECT SUM(amount) FROM gift_voucher_redemptions WHERE voucher_id = gv.id) as total_redeemed
                    FROM gift_vouchers gv
                    ORDER BY gv.created_at DESC
                    LIMIT :limit OFFSET :offset";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting vouchers: " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('getVoucherById')) {
    /**
     * Get voucher by ID
     */
    function getVoucherById($db, $id) {
        try {
            $sql = "SELECT * FROM gift_vouchers WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting voucher: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('updateVoucher')) {
    /**
     * Update voucher
     */
    function updateVoucher($db, $id, $data) {
        try {
            $sql = "UPDATE gift_vouchers
                    SET code = :code,
                        amount = :amount,
                        description = :description,
                        expires_at = :expires_at,
                        is_active = :is_active,
                        max_uses = :max_uses,
                        notes = :notes
                    WHERE id = :id";
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                ':code' => strtoupper($data['code']),
                ':amount' => $data['amount'],
                ':description' => $data['description'],
                ':expires_at' => $data['expires_at'],
                ':is_active' => $data['is_active'] ?? 1,
                ':max_uses' => $data['max_uses'] ?? 1,
                ':notes' => $data['notes'],
                ':id' => $id
            ]);
        } catch (PDOException $e) {
            error_log("Error updating voucher: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('deleteVoucher')) {
    /**
     * Delete voucher (soft delete by setting is_active = 0)
     */
    function deleteVoucher($db, $id) {
        try {
            $sql = "UPDATE gift_vouchers SET is_active = 0 WHERE id = :id";
            $stmt = $db->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Error deleting voucher: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('useVoucherBalance')) {
    /**
     * Deduct from user's voucher balance when used in order
     */
    function useVoucherBalance($db, $userId, $amount, $orderId) {
        try {
            $db->beginTransaction();

            // Get current balance
            $sql = "SELECT * FROM user_voucher_balance WHERE user_id = :user_id FOR UPDATE";
            $stmt = $db->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            $balance = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$balance || $balance['balance'] < $amount) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Insufficient voucher balance'];
            }

            // Deduct from balance
            $newBalance = $balance['balance'] - $amount;
            $sql = "UPDATE user_voucher_balance
                    SET balance = :new_balance,
                        total_spent = total_spent + :amount
                    WHERE user_id = :user_id";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':new_balance' => $newBalance,
                ':amount' => $amount,
                ':user_id' => $userId
            ]);

            // Record as usage
            $sql = "INSERT INTO gift_voucher_redemptions (voucher_id, user_id, amount, balance_after, order_id)
                    VALUES (NULL, :user_id, -:amount, :balance_after, :order_id)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':amount' => $amount,
                ':balance_after' => $newBalance,
                ':order_id' => $orderId
            ]);

            $db->commit();

            return ['success' => true, 'new_balance' => $newBalance];
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Error using voucher balance: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error processing voucher payment'];
        }
    }
}
