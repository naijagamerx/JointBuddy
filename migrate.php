<?php
require_once __DIR__ . '/includes/database.php';

try {
    $db = (new Database())->getConnection();
    
    echo "Running migrations...\n";
    
    // 1. Password reset tokens table
    $db->exec("CREATE TABLE IF NOT EXISTS password_reset_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(64) NOT NULL UNIQUE,
        expires_at DATETIME NOT NULL,
        used_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    echo "- Created/verified password_reset_tokens table\n";
    
    // 2. Email templates table
    $db->exec("CREATE TABLE IF NOT EXISTS email_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(50) NOT NULL UNIQUE,
        name VARCHAR(100) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        html_content TEXT NOT NULL,
        placeholders TEXT,
        active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "- Created/verified email_templates table\n";
    
    // 3. User sessions table
    $db->exec("CREATE TABLE IF NOT EXISTS user_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        session_id VARCHAR(128) NOT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        device_type VARCHAR(50),
        location VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        is_current TINYINT(1) DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    echo "- Created/verified user_sessions table\n";
    
    // 4. User security logs table
    $db->exec("CREATE TABLE IF NOT EXISTS user_security_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        action VARCHAR(50) NOT NULL,
        description TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    echo "- Created/verified user_security_logs table\n";
    
    // 5. Add missing columns to users table
    $columns = [
        'email_verified' => "TINYINT(1) DEFAULT 0",
        'email_verification_token' => "VARCHAR(64) NULL",
        'email_verification_sent_at' => "DATETIME NULL",
        'must_change_password' => "TINYINT(1) DEFAULT 0",
        'date_of_birth' => "DATE NULL",
        'gender' => "ENUM('male', 'female', 'other', 'prefer-not-to-say') NULL",
        'email_notifications' => "TINYINT(1) DEFAULT 1",
        'sms_notifications' => "TINYINT(1) DEFAULT 0",
        'marketing_communications' => "TINYINT(1) DEFAULT 1",
        'data_processing_consent' => "TINYINT(1) DEFAULT 1",
        'analytics_consent' => "TINYINT(1) DEFAULT 1",
        'consent_updated_at' => "DATETIME NULL",
        'password_changed_at' => "DATETIME NULL",
        'phone' => "VARCHAR(20) NULL"
    ];
    
    foreach ($columns as $col => $def) {
        $stmt = $db->query("SHOW COLUMNS FROM users LIKE '$col'");
        if ($stmt->rowCount() === 0) {
            $db->exec("ALTER TABLE users ADD COLUMN $col $def");
            echo "- Added column $col to users table\n";
        }
    }
    
    echo "Migrations COMPLETED successfully!\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
