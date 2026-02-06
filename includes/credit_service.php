<?php
/**
 * Credit & Refunds Service
 * Handles user credit balance, reward points, and transaction history
 */

if (!function_exists('getUserCreditSummary')) {
    /**
     * Get user's credit summary (voucher balance, reward points)
     */
    function getUserCreditSummary($db, $userId) {
        $summary = [
            'store_credit' => 0,
            'store_credit_earned' => 0,
            'store_credit_spent' => 0,
            'reward_points' => 0,
            'reward_points_earned' => 0,
            'reward_points_redeemed' => 0
        ];

        // Get voucher balance (store credit)
        try {
            $sql = "SELECT * FROM user_voucher_balance WHERE user_id = :user_id";
            $stmt = $db->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            $voucherBalance = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($voucherBalance) {
                $summary['store_credit'] = floatval($voucherBalance['balance']);
                $summary['store_credit_earned'] = floatval($voucherBalance['total_earned']);
                $summary['store_credit_spent'] = floatval($voucherBalance['total_spent']);
            }
        } catch (PDOException $e) {
            error_log("Error getting voucher balance: " . $e->getMessage());
        }

        // Get reward points
        try {
            $sql = "SELECT * FROM reward_points WHERE user_id = :user_id";
            $stmt = $db->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            $rewardPoints = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($rewardPoints) {
                $summary['reward_points'] = intval($rewardPoints['points']);
                $summary['reward_points_earned'] = intval($rewardPoints['total_earned']);
                $summary['reward_points_redeemed'] = intval($rewardPoints['total_redeemed'] ?? 0);
            }
        } catch (PDOException $e) {
            error_log("Error getting reward points: " . $e->getMessage());
        }

        return $summary;
    }
}

if (!function_exists('getUserCreditTransactions')) {
    /**
     * Get user's credit transaction history
     */
    function getUserCreditTransactions($db, $userId, $limit = 10) {
        $transactions = [];

        // Get voucher redemptions
        try {
            $sql = "SELECT gvr.id,
                           gvr.amount,
                           gvr.balance_after,
                           gvr.redeemed_at,
                           gvr.order_id,
                           gv.code as voucher_code,
                           gv.description,
                           'voucher' as transaction_type,
                           'credit' as direction
                    FROM gift_voucher_redemptions gvr
                    LEFT JOIN gift_vouchers gv ON gvr.voucher_id = gv.id
                    WHERE gvr.user_id = :user_id
                    ORDER BY gvr.redeemed_at DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            $voucherRedemptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($voucherRedemptions as $redemption) {
                $transactions[] = [
                    'id' => $redemption['id'],
                    'type' => 'voucher_redemption',
                    'title' => 'Gift Voucher Redeemed',
                    'description' => $redemption['description'] ?? 'Voucher: ' . $redemption['voucher_code'],
                    'code' => $redemption['voucher_code'],
                    'amount' => floatval($redemption['amount']),
                    'balance_after' => floatval($redemption['balance_after']),
                    'order_id' => $redemption['order_id'],
                    'date' => $redemption['redeemed_at'],
                    'status' => 'completed',
                    'icon' => 'fa-gift',
                    'color' => 'green'
                ];
            }
        } catch (PDOException $e) {
            error_log("Error getting voucher redemptions: " . $e->getMessage());
        }

        // Get reward point transactions
        try {
            $sql = "SELECT rpt.id,
                           rpt.points_change,
                           rpt.points_balance,
                           rpt.transaction_type,
                           rpt.description,
                           rpt.created_at,
                           rpt.order_id,
                           'reward' as transaction_type
                    FROM reward_points_transactions rpt
                    WHERE rpt.user_id = :user_id
                    ORDER BY rpt.created_at DESC
                    LIMIT :limit";
            $stmt = $db->prepare($sql);
            $stmt->execute([':user_id' => $userId, ':limit' => $limit]);
            $rewardTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rewardTransactions as $tx) {
                $isEarn = $tx['transaction_type'] === 'earn';
                $transactions[] = [
                    'id' => $tx['id'],
                    'type' => 'reward_points',
                    'title' => $isEarn ? 'Points Earned' : 'Points Redeemed',
                    'description' => $tx['description'] ?? ($isEarn ? 'Points earned from order' : 'Points redeemed'),
                    'amount' => abs(intval($tx['points_change'])),
                    'balance_after' => intval($tx['points_balance']),
                    'order_id' => $tx['order_id'],
                    'date' => $tx['created_at'],
                    'status' => 'completed',
                    'icon' => $isEarn ? 'fa-coins' : 'fa-gift',
                    'color' => $isEarn ? 'purple' : 'orange'
                ];
            }
        } catch (PDOException $e) {
            error_log("Error getting reward transactions: " . $e->getMessage());
        }

        // Sort all transactions by date
        usort($transactions, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return array_slice($transactions, 0, $limit);
    }
}

if (!function_exists('getRecentRefunds')) {
    /**
     * Get recent refunds for user (from orders)
     */
    function getRecentRefunds($db, $userId, $limit = 5) {
        $refunds = [];

        try {
            $sql = "SELECT o.id,
                           o.order_number,
                           o.total_amount,
                           o.status,
                           o.payment_status,
                           o.created_at,
                           o.updated_at
                    FROM orders o
                    WHERE o.user_id = :user_id
                    AND o.status IN ('rejected', 'refunded')
                    ORDER BY o.updated_at DESC
                    LIMIT :limit";
            $stmt = $db->prepare($sql);
            $stmt->execute([':user_id' => $userId, ':limit' => $limit]);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($orders as $order) {
                $refunds[] = [
                    'order_number' => $order['order_number'],
                    'amount' => floatval($order['total_amount']),
                    'status' => $order['status'] === 'refunded' ? 'completed' : 'pending',
                    'date' => $order['updated_at']
                ];
            }
        } catch (PDOException $e) {
            error_log("Error getting refunds: " . $e->getMessage());
        }

        return $refunds;
    }
}

if (!function_exists('getPendingRefundAmount')) {
    /**
     * Calculate pending refund amount for user
     */
    function getPendingRefundAmount($db, $userId) {
        $total = 0;

        try {
            $sql = "SELECT COALESCE(SUM(total_amount), 0) as total
                    FROM orders
                    WHERE user_id = :user_id
                    AND status = 'rejected'
                    AND payment_status = 'pending'";
            $stmt = $db->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $total = floatval($result['total']);
        } catch (PDOException $e) {
            error_log("Error getting pending refunds: " . $e->getMessage());
        }

        return $total;
    }
}

if (!function_exists('getCompletedRefundAmount')) {
    /**
     * Calculate completed refund amount for user
     */
    function getCompletedRefundAmount($db, $userId) {
        $total = 0;

        try {
            $sql = "SELECT COALESCE(SUM(total_amount), 0) as total
                    FROM orders
                    WHERE user_id = :user_id
                    AND status = 'refunded'";
            $stmt = $db->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $total = floatval($result['total']);
        } catch (PDOException $e) {
            error_log("Error getting completed refunds: " . $e->getMessage());
        }

        return $total;
    }
}
