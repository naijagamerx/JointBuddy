<?php
// Database configuration for CannaBuddy
class Database {
    private $host = 'localhost';
    private $db_name = 'cannabuddy';
    private $username = 'root';
    private $password = 'root'; // Default MAMP MySQL password
    private $charset = 'utf8mb4';
    private $pdo;
    
    public function __construct() {
        $this->loadFromEnvironment();
        $this->connect();
    }
    
    private function loadFromEnvironment() {
        // First, try to load from config.php (production)
        $configFile = dirname(__DIR__) . '/config.php';
        if (file_exists($configFile)) {
            include $configFile;
            // config.php should define: $db_host, $db_name, $db_user, $db_pass
            if (isset($db_host)) $this->host = $db_host;
            if (isset($db_name)) $this->db_name = $db_name;
            if (isset($db_user)) $this->username = $db_user;
            if (isset($db_pass)) $this->password = $db_pass;
            return; // Don't override with environment vars
        }

        // Fall back to environment variables
        $host = getenv('CB_DB_HOST');
        if ($host !== false && $host !== '') {
            $this->host = $host;
        }

        $dbName = getenv('CB_DB_NAME');
        if ($dbName !== false && $dbName !== '') {
            $this->db_name = $dbName;
        }

        $username = getenv('CB_DB_USER');
        if ($username !== false && $username !== '') {
            $this->username = $username;
        }

        $password = getenv('CB_DB_PASS');
        if ($password !== false) {
            $this->password = $password;
        }

        $charset = getenv('CB_DB_CHARSET');
        if ($charset !== false && $charset !== '') {
            $this->charset = $charset;
        }
    }
    
    private function connect() {
        $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        try {
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    public function testConnection() {
        try {
            // Use prepared statement for consistency with secure coding practices
            $stmt = $this->pdo->prepare("SELECT 'Connection successful' as message");
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            // Log actual error for debugging, return safe message to caller
            error_log("Database connection test failed: " . $e->getMessage());
            return ['error' => 'Database connection failed'];
        }
    }
}

// Authentication class
class AdminAuth {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function login($username, $password, $ip_address = null) {
        error_log("DEBUG: AdminAuth::login called for user: " . $username);
        
        $stmt = $this->db->prepare("SELECT * FROM admin_users WHERE (username = ? OR email = ?) AND is_active = 1");
        $stmt->execute([$username, $username]);
        $admin = $stmt->fetch();
        
        if (!$admin) {
            $this->logLogin(null, false, 'User not found', $ip_address);
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
        
        // Check if account is locked
        if ($admin['locked_until'] && strtotime($admin['locked_until']) > time()) {
            $this->logLogin($admin['id'], false, 'Account locked', $ip_address);
            return ['success' => false, 'message' => 'Account is temporarily locked'];
        }
        
        // Verify password
        if (!password_verify($password, $admin['password'])) {
            $this->incrementLoginAttempts($admin['id']);
            $this->logLogin($admin['id'], false, 'Invalid password', $ip_address);
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
        
        // Successful login
        $this->updateLastLogin($admin['id']);
        $this->resetLoginAttempts($admin['id']);
        $this->logLogin($admin['id'], true, null, $ip_address);

        // Set session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Security: Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_role'] = $admin['role'];
        $_SESSION['admin_logged_in'] = true;
        
        // Security: Set session fingerprint
        $this->setSessionFingerprint();
        
        error_log("DEBUG: Session data after login:");
        error_log("DEBUG: admin_id: " . ($_SESSION['admin_id'] ?? 'NOT SET'));
        error_log("DEBUG: admin_logged_in: " . ($_SESSION['admin_logged_in'] ?? 'NOT SET'));
        error_log("DEBUG: admin_fingerprint: " . ($_SESSION['admin_fingerprint'] ?? 'NOT SET'));

        return ['success' => true, 'admin' => $admin];
    }

    /**
     * Set a unique fingerprint for the current session
     */
    private function setSessionFingerprint() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $fingerprint = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));
        $_SESSION['admin_fingerprint'] = $fingerprint;
        $_SESSION['last_activity'] = time();
    }

    /**
     * Verify the current session fingerprint matches the stored one
     */
    public function verifySessionFingerprint() {
        error_log("DEBUG: verifySessionFingerprint called");
        error_log("DEBUG: isLoggedIn check: " . ($this->isLoggedIn() ? 'true' : 'false'));
        
        if (!$this->isLoggedIn()) {
            error_log("DEBUG: isLoggedIn returned false, exiting verifySessionFingerprint");
            return false;
        }

        // Check for inactivity timeout (2 hours)
        $timeout = 2 * 60 * 60;
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
            $this->logout();
            return false;
        }
        $_SESSION['last_activity'] = time();

        $current_fingerprint = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));
        $stored_fingerprint = $_SESSION['admin_fingerprint'] ?? '';
        
        error_log("DEBUG: current_fingerprint: " . $current_fingerprint);
        error_log("DEBUG: stored_fingerprint: " . $stored_fingerprint);
        error_log("DEBUG: IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

        if ($current_fingerprint !== $stored_fingerprint) {
            error_log("Session fingerprint mismatch for admin ID: " . ($_SESSION['admin_id'] ?? 'unknown'));
            $this->logout();
            return false;
        }

        return true;
    }

    /**
     * Verify 2FA code (Skeleton)
     */
    public function verify2FA($adminId, $code) {
        if ($code === '123456') { // Mock logic
            $_SESSION['admin_2fa_verified'] = true;
            return true;
        }
        return false;
    }
    
    public function logout() {
        ensureSessionStarted();
        
        $adminId = $_SESSION['admin_id'] ?? null;
        if ($adminId) {
            // Optional: Log logout event if logSecurityEvent exists for admin
        }
        unset($_SESSION['admin_id']);
        unset($_SESSION['admin_username']);
        unset($_SESSION['admin_role']);
        unset($_SESSION['admin_logged_in']);
        session_destroy();
        return true;
    }
    
    public function isLoggedIn() {
        ensureSessionStarted();
        $result = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
        error_log("DEBUG: isLoggedIn() called, result: " . ($result ? 'true' : 'false'));
        error_log("DEBUG: admin_logged_in = " . ($_SESSION['admin_logged_in'] ?? 'NOT SET'));
        return $result;
    }
    
    public function getCurrentAdmin() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        $stmt = $this->db->prepare("SELECT * FROM admin_users WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        return $stmt->fetch();
    }
    
    public function getAdminId() {
        return $_SESSION['admin_id'] ?? null;
    }

    public function getAdminById($adminId) {
        $stmt = $this->db->prepare("SELECT * FROM admin_users WHERE id = ?");
        $stmt->execute([$adminId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function logLogin($admin_id, $success, $failure_reason, $ip_address) {
        if ($admin_id) {
            $stmt = $this->db->prepare("INSERT INTO admin_login_logs (admin_id, ip_address, success, failure_reason) VALUES (?, ?, ?, ?)");
            $stmt->execute([$admin_id, $ip_address, $success ? 1 : 0, $failure_reason]);
        }
    }
    
    private function incrementLoginAttempts($admin_id) {
        $stmt = $this->db->prepare("UPDATE admin_users SET login_attempts = login_attempts + 1, locked_until = DATE_ADD(NOW(), INTERVAL 30 MINUTE) WHERE login_attempts >= 5 AND id = ?");
        $stmt->execute([$admin_id]);
    }
    
    private function resetLoginAttempts($admin_id) {
        $stmt = $this->db->prepare("UPDATE admin_users SET login_attempts = 0, locked_until = NULL WHERE id = ?");
        $stmt->execute([$admin_id]);
    }
    
    private function updateLastLogin($admin_id) {
        $stmt = $this->db->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$admin_id]);
    }
}

// User registration class
class UserAuth {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function register($data) {
        $firstName = $data['first_name'];
        $lastName = $data['last_name'];
        $email = $data['email'];
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $stmt = $this->db->prepare("INSERT INTO users (first_name, last_name, email, password, phone, is_active, email_verified, created_at) VALUES (?, ?, ?, ?, ?, 1, 0, NOW())");
        $result = $stmt->execute([$firstName, $lastName, $email, $password, $data['phone'] ?? null]);
        
        if ($result) {
            $userId = $this->db->lastInsertId();
            $this->logSecurityEvent($userId, 'register', 'New user registered');
            return $userId;
        }
        return false;
    }
    
    public function login($email, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid credentials'];
        }

        // Check if account is locked
        if ($this->isAccountLocked($user)) {
            return ['success' => false, 'message' => 'Account temporarily locked. Please try again later.'];
        }

        // Verify password
        if (!password_verify($password, $user['password'])) {
            $this->incrementLoginAttempts($user['id']);
            return ['success' => false, 'message' => 'Invalid credentials'];
        }

        // Successful login - reset attempts and update last login
        $this->resetLoginAttempts($user['id']);

        // Update last login
        $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);

        // Set session
        ensureSessionStarted();

        // Security: Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_logged_in'] = true;

        $this->logSecurityEvent($user['id'], 'login', 'User logged in successfully');
        session_write_close();

        return ['success' => true, 'user' => $user];
    }
    
    public function logout() {
        ensureSessionStarted();
        
        if (isset($_SESSION['user_id'])) {
            $this->logSecurityEvent($_SESSION['user_id'], 'logout', 'User logged out');
        }
        
        unset($_SESSION['user_id']);
        unset($_SESSION['user_email']);
        unset($_SESSION['user_name']);
        unset($_SESSION['user_logged_in']);
        session_destroy();
        return true;
    }

    /**
     * Generate and store a password reset token
     */
    public function createPasswordResetToken($email) {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) return false;
        
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $stmt = $this->db->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
        return $stmt->execute([$user['id'], $token, $expires]) ? $token : false;
    }

    /**
     * Validate a password reset token
     */
    public function validatePasswordResetToken($token) {
        $stmt = $this->db->prepare("
            SELECT prt.*, u.email, u.first_name, u.last_name 
            FROM password_reset_tokens prt
            JOIN users u ON prt.user_id = u.id
            WHERE prt.token = ? AND prt.used_at IS NULL AND prt.expires_at > NOW()
        ");
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Reset password using token
     */
    public function resetPassword($token, $newPassword) {
        $tokenData = $this->validatePasswordResetToken($token);
        if (!$tokenData) return false;
        
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        try {
            $this->db->beginTransaction();
            
            // Update password
            $stmt = $this->db->prepare("UPDATE users SET password = ?, updated_at = NOW(), password_changed_at = NOW(), must_change_password = 0 WHERE id = ?");
            $stmt->execute([$hashedPassword, $tokenData['user_id']]);
            
            // Mark token as used
            $stmt = $this->db->prepare("UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ?");
            $stmt->execute([$tokenData['id']]);
            
            // Log security event
            $this->logSecurityEvent($tokenData['user_id'], 'password_reset', 'Password reset successful using token');
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Password reset error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate temporary password and set change flag
     */
    public function generateTemporaryPassword($userId) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
        $tempPass = substr(str_shuffle($chars), 0, 12);
        $hashedPassword = password_hash($tempPass, PASSWORD_DEFAULT);
        
        $stmt = $this->db->prepare("UPDATE users SET password = ?, must_change_password = 1, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$hashedPassword, $userId]) ? $tempPass : false;
    }

    /**
     * Log a security event
     */
    public function logSecurityEvent($userId, $action, $description = '') {
        try {
            $stmt = $this->db->prepare("INSERT INTO user_security_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
            return $stmt->execute([$userId, $action, $description, $_SERVER['REMOTE_ADDR'] ?? null]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check if user account is currently locked
     *
     * @param array $user User record from database
     * @return bool True if account is locked, false otherwise
     */
    public function isAccountLocked($user) {
        if (!$user || empty($user['locked_until'])) {
            return false;
        }

        $lockedUntil = strtotime($user['locked_until']);
        $now = time();

        // If lock period has expired, clear it
        if ($lockedUntil <= $now) {
            $this->resetLoginAttempts($user['id']);
            return false;
        }

        return true;
    }

    /**
     * Increment login attempts after failed login
     * Locks account after 5 failed attempts for 30 minutes
     *
     * @param int $userId User ID
     * @return void
     */
    private function incrementLoginAttempts($userId) {
        $stmt = $this->db->prepare("
            UPDATE users
            SET login_attempts = login_attempts + 1,
                locked_until = CASE
                    WHEN login_attempts >= 4 THEN DATE_ADD(NOW(), INTERVAL 30 MINUTE)
                    ELSE NULL
                END
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
    }

    /**
     * Reset login attempts after successful login
     *
     * @param int $userId User ID
     * @return void
     */
    private function resetLoginAttempts($userId) {
        $stmt = $this->db->prepare("UPDATE users SET login_attempts = 0, locked_until = NULL WHERE id = ?");
        $stmt->execute([$userId]);
    }

    public function isLoggedIn() {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params(0, '/');
            session_start();
        }
        return isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
    }
}

// Order Management Functions
class OrderManager {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Update order status with automation
     */
    public function updateOrderStatus($orderId, $newStatus, $adminId = null, $note = '') {
        // Get current order
        $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        if (!$order) {
            return ['success' => false, 'message' => 'Order not found'];
        }

        $oldStatus = $order['status'];
        $customerEmail = $order['customer_email'];

        // Update order status
        $stmt = $this->db->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newStatus, $orderId]);

        // Add status history record
        $stmt = $this->db->prepare("
            INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, note)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$orderId, $oldStatus, $newStatus, $adminId, $note]);

        $result = ['success' => true, 'message' => 'Status updated'];

        // Automation: When status = delivered
        if ($newStatus === 'delivered') {
            // Auto-mark payment as paid
            $stmt = $this->db->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = ?");
            $stmt->execute([$orderId]);

            // Add 1 reward point to customer
            $pointsResult = $this->addRewardPoints($orderId, 1, 'earn', 'Points earned from delivered order #' . $order['order_number']);
            $result['reward_points'] = $pointsResult;
        }

        return $result;
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus($orderId, $paymentStatus) {
        $stmt = $this->db->prepare("UPDATE orders SET payment_status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$paymentStatus, $orderId]);
        return ['success' => true];
    }

    /**
     * Get order status history
     */
    public function getOrderStatusHistory($orderId) {
        $stmt = $this->db->prepare("
            SELECT h.*, a.username as admin_name
            FROM order_status_history h
            LEFT JOIN admin_users a ON h.changed_by = a.id
            WHERE h.order_id = ?
            ORDER BY h.created_at ASC
        ");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }

    /**
     * Add admin note to order
     */
    public function addOrderNote($orderId, $adminId, $note) {
        $stmt = $this->db->prepare("
            INSERT INTO order_notes (order_id, admin_id, note)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$orderId, $adminId, $note]);
        return ['success' => true, 'note_id' => $this->db->lastInsertId()];
    }

    /**
     * Get order notes
     */
    public function getOrderNotes($orderId) {
        $stmt = $this->db->prepare("
            SELECT n.*, a.username as admin_name
            FROM order_notes n
            JOIN admin_users a ON n.admin_id = a.id
            WHERE n.order_id = ?
            ORDER BY n.created_at DESC
        ");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }

    /**
     * Get customer reward points
     */
    public function getCustomerRewardPoints($userId) {
        $stmt = $this->db->prepare("SELECT * FROM reward_points WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    /**
     * Add reward points to customer
     */
    public function addRewardPoints($orderId, $points, $type = 'earn', $description = '') {
        // Get order to find user
        $stmt = $this->db->prepare("SELECT user_id, order_number FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        if (!$order) {
            return ['success' => false, 'message' => 'Order not found'];
        }

        $userId = $order['user_id'];

        // Skip reward points for orders without user account (walk-in customers)
        if ($userId === null) {
            return [
                'success' => true,
                'message' => 'No user account - reward points skipped',
                'new_balance' => null,
                'eligible_for_gift' => false,
                'points_to_next_gift' => null
            ];
        }

        // Get or create reward points record
        $stmt = $this->db->prepare("SELECT * FROM reward_points WHERE user_id = ?");
        $stmt->execute([$userId]);
        $rewardPoints = $stmt->fetch();

        if (!$rewardPoints) {
            // Create new record
            $stmt = $this->db->prepare("INSERT INTO reward_points (user_id, points, total_earned) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $points, $points]);
            $newBalance = $points;
        } else {
            // Update existing
            $newBalance = $rewardPoints['points'] + $points;
            $newTotalEarned = $rewardPoints['total_earned'] + $points;
            $stmt = $this->db->prepare("UPDATE reward_points SET points = ?, total_earned = ?, updated_at = NOW() WHERE user_id = ?");
            $stmt->execute([$newBalance, $newTotalEarned, $userId]);
        }

        // Log transaction
        $stmt = $this->db->prepare("
            INSERT INTO reward_points_transactions (user_id, order_id, points_change, points_balance, transaction_type, description)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $orderId, $points, $newBalance, $type, $description]);

        // Check if eligible for free gift (every 5 points)
        $eligibleForGift = ($newBalance >= 5) && ($newBalance % 5 === 0 || $newBalance >= 5);

        return [
            'success' => true,
            'new_balance' => $newBalance,
            'eligible_for_gift' => $eligibleForGift,
            'points_to_next_gift' => 5 - ($newBalance % 5)
        ];
    }

    /**
     * Get email log for order (by recipient email)
     */
    public function getEmailLogForOrder($email) {
        $stmt = $this->db->prepare("
            SELECT * FROM email_log
            WHERE recipient_email = ?
            ORDER BY created_at DESC
            LIMIT 20
        ");
        $stmt->execute([$email]);
        return $stmt->fetchAll();
    }

    /**
     * Log email sent
     */
    public function logEmail($recipientEmail, $subject, $emailType, $status = 'sent', $errorMessage = null) {
        $stmt = $this->db->prepare("
            INSERT INTO email_log (recipient_email, subject, email_type, status, error_message, sent_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$recipientEmail, $subject, $emailType, $status, $errorMessage]);
        return $this->db->lastInsertId();
    }
}
?>
