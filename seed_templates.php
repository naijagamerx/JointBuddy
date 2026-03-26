<?php
/**
 * Seed Email Templates
 * Creates all required email templates in the database
 */

require_once __DIR__ . '/includes/database.php';

try {
    $db = (new Database())->getConnection();
    echo "Seeding email templates...\n\n";

    $templates = [
        // Existing templates (updated)
        [
            'type' => 'password_reset',
            'name' => 'Password Reset',
            'subject' => 'Reset Your Password',
            'html_content' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
            <h1 style="color: white; margin: 0; font-size: 28px;">Password Reset Request</h1>
        </div>
        <div style="background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <p style="color: #333; font-size: 16px;">Hello {{first_name}},</p>
            <p style="color: #555; font-size: 14px; line-height: 1.6;">We received a request to reset your password. Click the button below to create a new password:</p>
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{reset_url}}" style="background-color: #22c55e; color: white; padding: 14px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">Reset Password</a>
            </div>
            <p style="color: #666; font-size: 13px;">If you did not request this reset, you can safely ignore this email. This link will expire in 24 hours.</p>
            <hr style="border: 0; border-top: 1px solid #eee; margin: 30px 0;">
            <p style="color: #999; font-size: 12px; text-align: center;">&copy; ' . date('Y') . ' All rights reserved.</p>
        </div>
    </div>
</body>
</html>'
        ],
        [
            'type' => 'welcome',
            'name' => 'Welcome Email',
            'subject' => 'Welcome to Our Store!',
            'html_content' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
            <h1 style="color: white; margin: 0; font-size: 28px;">Welcome, {{first_name}}!</h1>
            <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0 0;">Your account is ready</p>
        </div>
        <div style="background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <p style="color: #333; font-size: 16px;">Hello {{first_name}},</p>
            <p style="color: #555; font-size: 14px; line-height: 1.6;">Thank you for joining us! Your account has been successfully created and you can now start shopping.</p>
            <div style="background: #f0fdf4; border-left: 4px solid #22c55e; padding: 15px; margin: 20px 0;">
                <p style="color: #166534; margin: 0; font-size: 14px;"><strong>Your login email:</strong> {{email}}</p>
            </div>
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{login_url}}" style="background-color: #22c55e; color: white; padding: 14px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">Start Shopping</a>
            </div>
            <p style="color: #666; font-size: 13px;">If you have any questions, our support team is here to help.</p>
            <hr style="border: 0; border-top: 1px solid #eee; margin: 30px 0;">
            <p style="color: #999; font-size: 12px; text-align: center;">&copy; ' . date('Y') . ' All rights reserved.</p>
        </div>
    </div>
</body>
</html>'
        ],
        // NEW: Temporary Password
        [
            'type' => 'temporary_password',
            'name' => 'Temporary Password',
            'subject' => 'Your Temporary Password',
            'html_content' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Temporary Password</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
            <h1 style="color: white; margin: 0; font-size: 28px;">Temporary Password</h1>
        </div>
        <div style="background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <p style="color: #333; font-size: 16px;">Hello {{first_name}},</p>
            <p style="color: #555; font-size: 14px; line-height: 1.6;">An administrator has generated a temporary password for your account. Please use the password below to log in:</p>
            <div style="background: #fef3c7; border: 2px dashed #f59e0b; padding: 20px; margin: 20px 0; text-align: center;">
                <p style="color: #92400e; margin: 0 0 10px 0; font-size: 14px; font-weight: bold;">Your Temporary Password:</p>
                <p style="color: #1f2937; margin: 0; font-size: 24px; font-family: monospace; letter-spacing: 2px;">{{temp_password}}</p>
            </div>
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{login_url}}" style="background-color: #22c55e; color: white; padding: 14px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">Log In Now</a>
            </div>
            <p style="color: #dc2626; font-size: 13px; font-weight: bold;">Important: Please change your password immediately after logging in for security reasons.</p>
            <hr style="border: 0; border-top: 1px solid #eee; margin: 30px 0;">
            <p style="color: #999; font-size: 12px; text-align: center;">&copy; ' . date('Y') . ' All rights reserved.</p>
        </div>
    </div>
</body>
</html>'
        ],
        // NEW: Payment Received
        [
            'type' => 'payment_received',
            'name' => 'Payment Received',
            'subject' => 'Payment Confirmed - {{order_number}}',
            'html_content' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Received</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
            <h1 style="color: white; margin: 0; font-size: 28px;">Payment Received!</h1>
            <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0 0;">Thank you for your payment</p>
        </div>
        <div style="background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <p style="color: #333; font-size: 16px;">Hello {{customer_name}},</p>
            <p style="color: #555; font-size: 14px; line-height: 1.6;">We have received your payment for order <strong>{{order_number}}</strong>. Your order is now being processed.</p>
            <div style="background: #f0fdf4; border-left: 4px solid #22c55e; padding: 20px; margin: 20px 0;">
                <p style="color: #166534; margin: 5px 0; font-size: 14px;"><strong>Order Number:</strong> {{order_number}}</p>
                <p style="color: #166534; margin: 5px 0; font-size: 14px;"><strong>Payment Amount:</strong> {{payment_amount}}</p>
                <p style="color: #166534; margin: 5px 0; font-size: 14px;"><strong>Payment Date:</strong> {{payment_date}}</p>
            </div>
            <p style="color: #555; font-size: 14px;">You will receive another email when your order ships.</p>
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{track_order_url}}" style="background-color: #22c55e; color: white; padding: 14px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">Track Order</a>
            </div>
            <hr style="border: 0; border-top: 1px solid #eee; margin: 30px 0;">
            <p style="color: #999; font-size: 12px; text-align: center;">&copy; ' . date('Y') . ' All rights reserved.</p>
        </div>
    </div>
</body>
</html>'
        ],
        // NEW: Order Status Updates
        [
            'type' => 'order_status_processing',
            'name' => 'Order Processing',
            'subject' => 'Order {{order_number}} - Now Processing',
            'html_content' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Processing</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
            <h1 style="color: white; margin: 0; font-size: 28px;">Order Processing</h1>
        </div>
        <div style="background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <p style="color: #333; font-size: 16px;">Hello {{customer_name}},</p>
            <p style="color: #555; font-size: 14px; line-height: 1.6;">Good news! Your order <strong>{{order_number}}</strong> is now being processed. Our team is preparing your items for shipment.</p>
            <div style="background: #eff6ff; border-left: 4px solid #3b82f6; padding: 20px; margin: 20px 0;">
                <p style="color: #1e40af; margin: 5px 0; font-size: 14px;"><strong>Status:</strong> Processing</p>
                <p style="color: #1e40af; margin: 5px 0; font-size: 14px;"><strong>Order:</strong> {{order_number}}</p>
            </div>
            <p style="color: #555; font-size: 14px;">We will notify you once your order ships.</p>
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{track_order_url}}" style="background-color: #3b82f6; color: white; padding: 14px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">Track Order</a>
            </div>
            <hr style="border: 0; border-top: 1px solid #eee; margin: 30px 0;">
            <p style="color: #999; font-size: 12px; text-align: center;">&copy; ' . date('Y') . ' All rights reserved.</p>
        </div>
    </div>
</body>
</html>'
        ],
        [
            'type' => 'order_status_shipped',
            'name' => 'Order Shipped',
            'subject' => 'Order {{order_number}} - Shipped!',
            'html_content' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Shipped</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
            <h1 style="color: white; margin: 0; font-size: 28px;">Order Shipped!</h1>
            <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0 0;">Your order is on its way</p>
        </div>
        <div style="background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <p style="color: #333; font-size: 16px;">Hello {{customer_name}},</p>
            <p style="color: #555; font-size: 14px; line-height: 1.6;">Great news! Your order <strong>{{order_number}}</strong> has been shipped and is on its way to you.</p>
            <div style="background: #f3e8ff; border-left: 4px solid #8b5cf6; padding: 20px; margin: 20px 0;">
                <p style="color: #5b21b6; margin: 5px 0; font-size: 14px;"><strong>Status:</strong> Shipped</p>
                <p style="color: #5b21b6; margin: 5px 0; font-size: 14px;"><strong>Order:</strong> {{order_number}}</p>
            </div>
            <p style="color: #555; font-size: 14px;">You can track your delivery using the link below.</p>
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{track_order_url}}" style="background-color: #8b5cf6; color: white; padding: 14px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">Track Delivery</a>
            </div>
            <hr style="border: 0; border-top: 1px solid #eee; margin: 30px 0;">
            <p style="color: #999; font-size: 12px; text-align: center;">&copy; ' . date('Y') . ' All rights reserved.</p>
        </div>
    </div>
</body>
</html>'
        ],
        [
            'type' => 'order_status_delivered',
            'name' => 'Order Delivered',
            'subject' => 'Order {{order_number}} - Delivered!',
            'html_content' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Delivered</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
            <h1 style="color: white; margin: 0; font-size: 28px;">Order Delivered!</h1>
            <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0 0;">Your order has arrived</p>
        </div>
        <div style="background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <p style="color: #333; font-size: 16px;">Hello {{customer_name}},</p>
            <p style="color: #555; font-size: 14px; line-height: 1.6;">Your order <strong>{{order_number}}</strong> has been delivered! We hope you love your purchase.</p>
            <div style="background: #f0fdf4; border-left: 4px solid #22c55e; padding: 20px; margin: 20px 0;">
                <p style="color: #166534; margin: 5px 0; font-size: 14px;"><strong>Status:</strong> Delivered</p>
                <p style="color: #166534; margin: 5px 0; font-size: 14px;"><strong>Order:</strong> {{order_number}}</p>
            </div>
            <p style="color: #555; font-size: 14px;">If you have any issues with your order, please contact our support team.</p>
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{track_order_url}}" style="background-color: #22c55e; color: white; padding: 14px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">View Order</a>
            </div>
            <hr style="border: 0; border-top: 1px solid #eee; margin: 30px 0;">
            <p style="color: #999; font-size: 12px; text-align: center;">&copy; ' . date('Y') . ' All rights reserved.</p>
        </div>
    </div>
</body>
</html>'
        ],
        [
            'type' => 'order_status_cancelled',
            'name' => 'Order Cancelled',
            'subject' => 'Order {{order_number}} - Cancelled',
            'html_content' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Cancelled</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
            <h1 style="color: white; margin: 0; font-size: 28px;">Order Cancelled</h1>
        </div>
        <div style="background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <p style="color: #333; font-size: 16px;">Hello {{customer_name}},</p>
            <p style="color: #555; font-size: 14px; line-height: 1.6;">Your order <strong>{{order_number}}</strong> has been cancelled as requested.</p>
            <div style="background: #fef2f2; border-left: 4px solid #ef4444; padding: 20px; margin: 20px 0;">
                <p style="color: #991b1b; margin: 5px 0; font-size: 14px;"><strong>Status:</strong> Cancelled</p>
                <p style="color: #991b1b; margin: 5px 0; font-size: 14px;"><strong>Order:</strong> {{order_number}}</p>
            </div>
            <p style="color: #555; font-size: 14px;">If you did not request this cancellation or have any questions, please contact our support team immediately.</p>
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{track_order_url}}" style="background-color: #6b7280; color: white; padding: 14px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">View Order Details</a>
            </div>
            <hr style="border: 0; border-top: 1px solid #eee; margin: 30px 0;">
            <p style="color: #999; font-size: 12px; text-align: center;">&copy; ' . date('Y') . ' All rights reserved.</p>
        </div>
    </div>
</body>
</html>'
        ],
        // NEW: Account Verification
        [
            'type' => 'account_verification',
            'name' => 'Account Verification',
            'subject' => 'Please Verify Your Email Address',
            'html_content' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
            <h1 style="color: white; margin: 0; font-size: 28px;">Verify Your Email</h1>
        </div>
        <div style="background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <p style="color: #333; font-size: 16px;">Hello {{first_name}},</p>
            <p style="color: #555; font-size: 14px; line-height: 1.6;">Thank you for creating an account! Please verify your email address by clicking the button below:</p>
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{verification_url}}" style="background-color: #22c55e; color: white; padding: 14px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">Verify Email Address</a>
            </div>
            <p style="color: #666; font-size: 13px;">This verification link will expire in 24 hours. If you did not create this account, please ignore this email.</p>
            <p style="color: #555; font-size: 13px;">If the button does not work, copy and paste this link into your browser:</p>
            <p style="color: #3b82f6; font-size: 12px; word-break: break-all;">{{verification_url}}</p>
            <hr style="border: 0; border-top: 1px solid #eee; margin: 30px 0;">
            <p style="color: #999; font-size: 12px; text-align: center;">&copy; ' . date('Y') . ' All rights reserved.</p>
        </div>
    </div>
</body>
</html>'
        ]
    ];

    $inserted = 0;
    $updated = 0;

    foreach ($templates as $template) {
        $stmt = $db->prepare("
            INSERT INTO email_templates (type, name, subject, html_content, active, created_at, updated_at)
            VALUES (?, ?, ?, ?, 1, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                subject = VALUES(subject),
                html_content = VALUES(html_content),
                updated_at = NOW()
        ");

        try {
            $stmt->execute([
                $template['type'],
                $template['name'],
                $template['subject'],
                $template['html_content']
            ]);

            if ($stmt->rowCount() > 0) {
                $inserted++;
                echo "✅ Inserted: {$template['type']} - {$template['name']}\n";
            } else {
                $updated++;
                echo "🔄 Updated: {$template['type']} - {$template['name']}\n";
            }
        } catch (Exception $e) {
            echo "❌ Error with {$template['type']}: " . $e->getMessage() . "\n";
        }
    }

    echo "\n========================================\n";
    echo "Seeding COMPLETED!\n";
    echo "Inserted: $inserted templates\n";
    echo "Updated: $updated templates\n";
    echo "========================================\n";

    // Show all templates
    echo "\nAll email templates in database:\n";
    echo str_repeat("-", 50) . "\n";
    $stmt = $db->query("SELECT type, name, active FROM email_templates ORDER BY type");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("%-25s | %s\n", $row['type'], $row['active'] ? '✅ Active' : '❌ Inactive');
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
