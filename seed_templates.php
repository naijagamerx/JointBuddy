<?php
require_once __DIR__ . '/includes/database.php';

try {
    $db = (new Database())->getConnection();
    echo "Seeding email templates...\n";
    
    $templates = [
        [
            'type' => 'password_reset',
            'name' => 'Password Reset',
            'subject' => 'Reset Your CannaBuddy Password',
            'html_content' => '
                <div style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;">
                    <h2 style="color: #2c3e50; text-align: center;">Reset Your Password</h2>
                    <p>Hello {{first_name}},</p>
                    <p>We received a request to reset your password for your CannaBuddy account. Click the button below to set a new password:</p>
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="{{reset_url}}" style="background-color: #22c55e; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;">Reset Password</a>
                    </div>
                    <p>If you did not request a password reset, please ignore this email or contact support if you have concerns.</p>
                    <p>This link will expire in 24 hours.</p>
                    <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">
                    <p style="color: #666; font-size: 12px; text-align: center;">&copy; ' . date('Y') . ' CannaBuddy.shop. All rights reserved.</p>
                </div>'
        ],
        [
            'type' => 'welcome',
            'name' => 'Welcome Email',
            'subject' => 'Welcome to CannaBuddy!',
            'html_content' => '
                <div style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;">
                    <h2 style="color: #2c3e50; text-align: center;">Welcome to CannaBuddy!</h2>
                    <p>Hello {{first_name}},</p>
                    <p>Your account has been successfully created. We are excited to have you with us!</p>
                    <p>You can now log in to your account and start exploring our products.</p>
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="{{login_url}}" style="background-color: #22c55e; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;">Log In Now</a>
                    </div>
                    <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">
                    <p style="color: #666; font-size: 12px; text-align: center;">&copy; ' . date('Y') . ' CannaBuddy.shop. All rights reserved.</p>
                </div>'
        ]
    ];
    
    foreach ($templates as $template) {
        $stmt = $db->prepare("INSERT INTO email_templates (type, name, subject, html_content) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE name=VALUES(name), subject=VALUES(subject), html_content=VALUES(html_content)");
        $stmt->execute([$template['type'], $template['name'], $template['subject'], $template['html_content']]);
        echo "- Seeded {$template['type']} template\n";
    }
    
    echo "Seeding COMPLETED successfully!\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
