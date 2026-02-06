<?php
/**
 * Email Service
 * Handles sending transactional emails
 */

class EmailService {
    private $db;
    private $settings = [];
    private $error = null;

    public function __construct($database = null) {
        $this->db = $database;
        $this->loadSettings();
    }

    /**
     * Load email settings from database
     */
    private function loadSettings() {
        if (!$this->db) {
            try {
                $database = new Database();
                $this->db = $database->getConnection();
            } catch (Exception $e) {
                error_log("EmailService: Could not connect to database - " . $e->getMessage());
                return;
            }
        }

        try {
            $stmt = $this->db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'email_%'");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($results as $row) {
                $this->settings[$row['setting_key']] = $row['setting_value'];
            }

            // Set defaults if not found
            $defaults = [
                'email_method' => 'mail',
                'from_email' => 'noreply@example.com',
                'from_name' => 'Online Store',
                'smtp_host' => '',
                'smtp_port' => '587',
                'smtp_username' => '',
                'smtp_password' => '',
                'smtp_encryption' => 'tls',
            ];

            foreach ($defaults as $key => $value) {
                if (!isset($this->settings[$key])) {
                    $this->settings[$key] = $value;
                }
            }
        } catch (Exception $e) {
            error_log("EmailService: Error loading settings - " . $e->getMessage());
        }
    }

    /**
     * Send an email
     *
     * @param string $to Recipient email
     * @param string $toName Recipient name
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @param string $altBody Plain text alternative
     * @return bool Success status
     */
    public function send($to, $toName, $subject, $body, $altBody = '') {
        if (empty($to)) {
            $this->error = "No recipient email specified";
            return false;
        }

        $fromEmail = $this->settings['from_email'] ?? 'noreply@example.com';
        $fromName = $this->settings['from_name'] ?? 'Online Store';

        // Build email headers
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            "From: $fromName <$fromEmail>",
            "Reply-To: $fromEmail",
            "X-Mailer: PHP/" . phpversion()
        ];

        // Add CC and BCC if needed
        $headers[] = 'X-Priority: 1';

        // Sanitize inputs
        $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $toName = mb_encode_mimeheader($toName, 'UTF-8', 'Q');

        // Try different sending methods based on settings
        $method = $this->settings['email_method'] ?? 'mail';

        if ($method === 'smtp' && !empty($this->settings['smtp_host'])) {
            return $this->sendSMTP($to, $toName, $subject, $body, $headers);
        }

        // Fall back to PHP mail()
        return $this->sendMail($to, $subject, $body, implode("\r\n", $headers));
    }

    /**
     * Send via PHP mail() function
     */
    private function sendMail($to, $subject, $body, $headers) {
        $result = mail($to, $subject, $body, $headers);

        if (!$result) {
            $this->error = "mail() function failed";
            error_log("EmailService: mail() failed to - $to");
        }

        return $result;
    }

    /**
     * Send via SMTP
     */
    private function sendSMTP($to, $toName, $subject, $body, $headers) {
        // Simple SMTP implementation
        $host = $this->settings['smtp_host'];
        $port = intval($this->settings['smtp_port'] ?? 587);
        $username = $this->settings['smtp_username'];
        $password = $this->settings['smtp_password'];
        $encryption = $this->settings['smtp_encryption'] ?? 'tls';

        // For now, fall back to mail() if SMTP configuration is incomplete
        if (empty($host) || empty($username)) {
            error_log("EmailService: SMTP settings incomplete, using mail()");
            return $this->sendMail($to, $subject, $body, implode("\r\n", $headers));
        }

        // Connect to SMTP server
        $socket = @fsockopen(
            ($encryption === 'ssl' ? 'ssl://' : '') . $host,
            $port,
            $errno,
            $errstr,
            30
        );

        if (!$socket) {
            $this->error = "SMTP connection failed: $errstr ($errno)";
            error_log("EmailService: SMTP connection failed - $errstr");
            // Fall back to mail()
            return $this->sendMail($to, $subject, $body, implode("\r\n", $headers));
        }

        // Read greeting
        $response = fgets($socket, 515);
        if (substr($response, 0, 3) !== '220') {
            fclose($socket);
            $this->error = "SMTP greeting failed: " . substr($response, 0, 3);
            return $this->sendMail($to, $subject, $body, implode("\r\n", $headers));
        }

        // EHLO
        fputs($socket, "EHLO " . gethostname() . "\r\n");
        $this->readSMTPResponse($socket);

        // STARTTLS if using TLS
        if ($encryption === 'tls') {
            fputs($socket, "STARTTLS\r\n");
            $this->readSMTPResponse($socket);
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

            // Re-EHLO after STARTTLS
            fputs($socket, "EHLO " . gethostname() . "\r\n");
            $this->readSMTPResponse($socket);
        }

        // AUTH LOGIN
        fputs($socket, "AUTH LOGIN\r\n");
        $this->readSMTPResponse($socket);

        fputs($socket, base64_encode($username) . "\r\n");
        $this->readSMTPResponse($socket);

        fputs($socket, base64_encode($password) . "\r\n");
        $authResponse = $this->readSMTPResponse($socket);

        if (substr($authResponse, 0, 3) !== '235') {
            fclose($socket);
            $this->error = "SMTP authentication failed";
            return $this->sendMail($to, $subject, $body, implode("\r\n", $headers));
        }

        // MAIL FROM
        $fromEmail = $this->settings['from_email'] ?? 'noreply@example.com';
        fputs($socket, "MAIL FROM:<$fromEmail>\r\n");
        $this->readSMTPResponse($socket);

        // RCPT TO
        fputs($socket, "RCPT TO:<$to>\r\n");
        $this->readSMTPResponse($socket);

        // DATA
        fputs($socket, "DATA\r\n");
        $this->readSMTPResponse($socket);

        // Build message
        $message = "To: $toName <$to>\r\n";
        $message .= implode("\r\n", $headers) . "\r\n";
        $message .= "Subject: $subject\r\n";
        $message .= "\r\n";
        $message .= $body . "\r\n";
        $message .= ".\r\n";

        fputs($socket, $message);
        $this->readSMTPResponse($socket);

        // QUIT
        fputs($socket, "QUIT\r\n");
        fclose($socket);

        return true;
    }

    /**
     * Send a test email to verify configuration
     */
    public function sendTestEmail($to) {
        $subject = "Store SMTP Test Email";
        $body = "
            <div style='font-family: sans-serif; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
                <h2 style='color: #22c55e;'>SMTP Test Successful!</h2>
                <p>If you are reading this, your SMTP settings for <strong>" . ($_SERVER['HTTP_HOST'] ?? 'your store') . "</strong> are configured correctly.</p>
                <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                <p style='color: #666; font-size: 12px;'>Sent at: " . date('Y-m-d H:i:s') . "</p>
            </div>";
        $altBody = "SMTP Test Successful! Your email settings are configured correctly.";
        
        return $this->send($to, 'Test Recipient', $subject, $body, $altBody);
    }

    /**
     * Read SMTP response
     */
    private function readSMTPResponse($socket) {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        return $response;
    }

    /**
     * Get last error
     */
    public function getError() {
        return $this->error;
    }

    /**
     * Send order confirmation email
     */
    public function sendOrderConfirmation($order, $orderItems, $customerEmail, $customerName) {
        $orderNumber = $order['order_number'] ?? 'ORD-' . date('Y') . '-' . str_pad($order['id'], 6, '0', STR_PAD_LEFT);
        $subject = "Order Confirmed - $orderNumber";

        // Get email template
        $template = $this->getTemplate('order_confirmation');

        // Get payment method details if applicable
        $paymentMethod = $order['payment_method'] ?? '';
        $paymentDetails = '';
        $paymentInfoHtml = '';

        if ($this->db && in_array($paymentMethod, ['bank_transfer', 'crypto', 'manual_custom'], true)) {
            try {
                $details = $this->getPaymentDetails($paymentMethod);
                if ($details && !empty($details['fields'])) {
                    $paymentInfoHtml = '
            <div style="background: #eff6ff; border: 1px solid #3b82f6; border-radius: 8px; padding: 20px; margin: 20px 0;">
                <h3 style="color: #1e40af; margin: 0 0 15px 0; font-size: 16px;">
                    <i style="margin-right: 8px;">&#128179;</i>Payment Information
                </h3>
                <p style="color: #3b82f6; margin: 0 0 10px 0; font-size: 14px;">
                    Please make your payment using the details below:
                </p>
                <table style="width: 100%; border-collapse: collapse;">';
                    foreach ($details['fields'] as $field) {
                        $paymentInfoHtml .= '
                    <tr>
                        <td style="padding: 8px 0; color: #1e3a8a; font-weight: bold; font-size: 13px;">' . htmlspecialchars($field['field_name']) . '</td>
                        <td style="padding: 8px 0; color: #1e3a8a; font-size: 13px; font-family: monospace;">' . htmlspecialchars($field['field_value']) . '</td>
                    </tr>';
                    }
                    $paymentInfoHtml .= '
                </table>
                <p style="color: #1e40af; margin: 15px 0 0 0; font-size: 13px; font-weight: bold;">
                    <i style="margin-right: 5px;">&#128203;</i>Please use <strong>' . htmlspecialchars($orderNumber) . '</strong> as your payment reference
                </p>
            </div>';
                }
            } catch (Exception $e) {
                // Payment details not available
            }
        }

        // Build items HTML
        $itemsHtml = '';
        foreach ($orderItems as $item) {
            $itemsHtml .= '<tr>';
            $itemsHtml .= '<td style="padding: 12px; border-bottom: 1px solid #eee;">' . htmlspecialchars($item['product_name'] ?? 'Product') . '</td>';
            $itemsHtml .= '<td style="padding: 12px; border-bottom: 1px solid #eee; text-align: center;">' . $item['quantity'] . '</td>';
            $itemsHtml .= '<td style="padding: 12px; border-bottom: 1px solid #eee; text-align: right;">R ' . number_format($item['unit_price'] ?? 0, 2) . '</td>';
            $itemsHtml .= '<td style="padding: 12px; border-bottom: 1px solid #eee; text-align: right;">R ' . number_format($item['total_price'] ?? 0, 2) . '</td>';
            $itemsHtml .= '</tr>';
        }

        // Replace placeholders
        $body = str_replace([
            '{{customer_name}}',
            '{{order_number}}',
            '{{order_date}}',
            '{{order_total}}',
            '{{order_items}}',
            '{{track_order_url}}',
            '{{payment_method}}',
            '{{payment_info}}'
        ], [
            htmlspecialchars($customerName),
            htmlspecialchars($orderNumber),
            date('F j, Y', strtotime($order['created_at'])),
            'R ' . number_format($order['total_amount'], 2),
            $itemsHtml,
            '',
            $this->getPaymentMethodName($paymentMethod),
            $paymentInfoHtml
        ], $template);

        // Use default template if no custom template exists
        if (empty(trim($body)) || strpos($body, '{{') !== false) {
            $body = $this->getDefaultOrderTemplate($orderNumber, $order, $orderItems, $customerName, $paymentMethod, $paymentInfoHtml);
        }

        return $this->send($customerEmail, $customerName, $subject, $body);
    }

    /**
     * Get human-readable payment method name
     */
    private function getPaymentMethodName($method) {
        $names = [
            'bank_transfer' => 'Bank Transfer',
            'crypto' => 'Cryptocurrency',
            'payfast' => 'PayFast',
            'manual_custom' => 'Manual Payment',
            'cod' => 'Cash on Delivery'
        ];
        return $names[$method] ?? ucfirst(str_replace('_', ' ', $method));
    }

    /**
     * Get payment details for manual payment methods
     */
    private function getPaymentDetails($method) {
        if (!$this->db) return null;

        try {
            $stmt = $this->db->prepare("
                SELECT pm.id, pmf.field_name, pmf.field_value
                FROM payment_methods pm
                LEFT JOIN payment_method_fields pmf ON pm.id = pmf.payment_method_id
                WHERE pm.type = ? AND pm.active = 1
            ");
            $stmt->execute([$method]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($results)) return null;

            $fields = [];
            foreach ($results as $row) {
                if (!empty($row['field_name'])) {
                    $fields[] = [
                        'field_name' => $row['field_name'],
                        'field_value' => $row['field_value']
                    ];
                }
            }

            return ['fields' => $fields];
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get email template from database
     */
    private function getTemplate($type) {
        if (!$this->db) return '';

        try {
            $stmt = $this->db->prepare("SELECT html_content FROM email_templates WHERE type = ? AND active = 1");
            $stmt->execute([$type]);
            $template = $stmt->fetch(PDO::FETCH_ASSOC);
            return $template ? $template['html_content'] : '';
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Default order confirmation email template
     */
    private function getDefaultOrderTemplate($orderNumber, $order, $orderItems, $customerName, $paymentMethod = '', $paymentInfoHtml = '') {
        $itemsHtml = '';
        foreach ($orderItems as $item) {
            $itemsHtml .= '<tr>';
            $itemsHtml .= '<td style="padding: 12px; border-bottom: 1px solid #eee;">' . htmlspecialchars($item['product_name'] ?? 'Product') . '</td>';
            $itemsHtml .= '<td style="padding: 12px; border-bottom: 1px solid #eee; text-align: center;">' . $item['quantity'] . '</td>';
            $itemsHtml .= '<td style="padding: 12px; border-bottom: 1px solid #eee; text-align: right;">R ' . number_format($item['unit_price'] ?? 0, 2) . '</td>';
            $itemsHtml .= '<td style="padding: 12px; border-bottom: 1px solid #eee; text-align: right;">R ' . number_format($item['total_price'] ?? 0, 2) . '</td>';
            $itemsHtml .= '</tr>';
        }

        $paymentMethodName = $this->getPaymentMethodName($paymentMethod);
        $paymentSection = '';
        if (!empty($paymentInfoHtml)) {
            $paymentSection = $paymentInfoHtml;
        } elseif ($paymentMethod && $paymentMethod !== 'payfast') {
            $paymentSection = '
            <div style="background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 20px; margin: 20px 0;">
                <h3 style="color: #92400e; margin: 0 0 10px 0; font-size: 16px;">
                    <i style="margin-right: 8px;">&#128179;</i>Payment Method: ' . htmlspecialchars($paymentMethodName) . '
                </h3>
                <p style="color: #b45309; margin: 0; font-size: 14px;">
                    Please complete your payment to process your order.
                </p>
            </div>';
        }

        // Calculate shipping display
        $shippingDisplay = $order['shipping_amount'] > 0 ? 'R ' . number_format($order['shipping_amount'], 2) : 'FREE';

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
            <h1 style="color: white; margin: 0; font-size: 28px;">Thank You for Your Order!</h1>
            <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0 0;">We've received your order</p>
        </div>

        <div style="background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <p style="color: #333; font-size: 16px; margin-bottom: 20px;">Hi {$customerName},</p>

            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <p style="margin: 0 0 10px 0; color: #666; font-size: 14px;">Order Number</p>
                <p style="margin: 0; color: #22c55e; font-size: 24px; font-weight: bold;">{$orderNumber}</p>
            </div>

            {$paymentSection}

            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                <thead>
                    <tr style="background: #f8f9fa;">
                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #eee;">Product</th>
                        <th style="padding: 12px; text-align: center; border-bottom: 2px solid #eee;">Qty</th>
                        <th style="padding: 12px; text-align: right; border-bottom: 2px solid #eee;">Price</th>
                        <th style="padding: 12px; text-align: right; border-bottom: 2px solid #eee;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    {$itemsHtml}
                </tbody>
            </table>

            <div style="border-top: 2px solid #eee; padding-top: 15px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="color: #666;">Subtotal</span>
                    <span style="color: #333;">R {$order['subtotal']}</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="color: #666;">Shipping</span>
                    <span style="color: #333;">{$shippingDisplay}</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 18px; font-weight: bold; margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee;">
                    <span style="color: #333;">Total</span>
                    <span style="color: #22c55e;">R {$order['total_amount']}</span>
                </div>
            </div>

            <div style="background: #e8f5e9; padding: 20px; border-radius: 8px; margin-top: 25px; text-align: center;">
                <p style="color: #2e7d32; margin: 0 0 10px 0; font-weight: bold;">
                    <i style="margin-right: 8px;">&#10003;</i> Confirmation email sent!
                </p>
                <p style="color: #555; margin: 0; font-size: 14px;">We've sent this confirmation to your email address.</p>
            </div>

            <div style="margin-top: 30px; text-align: center;">
                <a href="#" style="display: inline-block; background: #22c55e; color: white; padding: 14px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Track Your Order</a>
            </div>

            <p style="color: #999; font-size: 12px; text-align: center; margin-top: 30px;">
                If you have any questions, please contact our support team.
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
