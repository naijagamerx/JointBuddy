# Email System - Design Document

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                        ADMIN PANEL                          │
├──────────────┬────────────────────┬─────────────────────────┤
│  SMTP Config │  Email Templates   │  Notification Settings  │
│  (FIX ME)    │  (AUDIT & ENHANCE) │  (EXISTING)             │
└──────┬───────┴──────────┬─────────┴────────────┬────────────┘
       │                  │                      │
       └──────────────────┼──────────────────────┘
                          │
       ┌──────────────────▼──────────────────────┐
       │         EmailService Class              │
       │  ┌──────────────┐  ┌───────────────┐   │
       │  │ SMTP Sender  │  │ mail() Fallback│   │
       │  └──────────────┘  └───────────────┘   │
       │  ┌──────────────┐  ┌───────────────┐   │
       │  │ Template     │  │ Placeholder   │   │
       │  │ Loader       │  │ Replacement   │   │
       │  └──────────────┘  └───────────────┘   │
       └──────────────────┬──────────────────────┘
                          │
       ┌──────────────────▼──────────────────────┐
       │              DATABASE                   │
       │  ┌──────────────┐  ┌───────────────┐   │
       │  │ settings     │  │ email_templates│   │
       │  │ (SMTP config)│  │ (HTML content) │   │
       │  └──────────────┘  └───────────────┘   │
       │  ┌──────────────┐  ┌───────────────┐   │
       │  │ email_logs   │  │ (NEW TABLE)    │   │
       │  │ (track sends)│  │                │   │
       │  └──────────────┘  └───────────────┘   │
       └──────────────────────────────────────────┘
```

## Database Schema

### Current Tables (Verified)

**settings table** (existing):
```sql
- id (PK)
- setting_key (varchar) -- 'smtp_host', 'smtp_port', etc.
- setting_value (text)
- created_at, updated_at
```

**email_templates table** (existing):
```sql
- id (PK)
- type (varchar 50) -- 'order_confirmation', 'password_reset', 'welcome'
- name (varchar 100) -- Display name
- subject (varchar 255)
- html_content (text)
- text_content (text) -- Plain text version
- active (tinyint)
- created_at, updated_at
```

### Proposed New Tables

**email_logs table** (NEW):
```sql
CREATE TABLE email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_type VARCHAR(50) NOT NULL,
    recipient_email VARCHAR(255) NOT NULL,
    recipient_name VARCHAR(255),
    subject VARCHAR(255),
    status ENUM('queued', 'sent', 'failed', 'bounced') DEFAULT 'queued',
    error_message TEXT,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_template (template_type),
    INDEX idx_recipient (recipient_email),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
);
```

## Settings Key Mapping

### Current (BROKEN) vs Fixed

| Setting | Database Key | Form Field | Current Status |
|---------|----------|------------|------------|
| SMTP Host | smtp_host | smtp_host | ✅ Saved, ❌ Not loaded |
| SMTP Port | smtp_port | smtp_port | ✅ Saved, ❌ Not loaded |
| SMTP Username | smtp_username | smtp_username | ✅ Saved, ❌ Not loaded |
| SMTP Password | smtp_password | smtp_password | ✅ Saved, ❌ Not loaded |
| Encryption | smtp_encryption | smtp_encryption | ✅ Saved, ❌ Not loaded |
| From Email | from_email | from_email | ✅ Saved, ❌ Not loaded |
| From Name | from_name | from_name | ✅ Saved, ❌ Not loaded |

### Fix Required
Change line 38 in `admin/settings/email.php`:
```php
// BROKEN: Only loads keys starting with 'email_'
$stmt = $db->query("SELECT * FROM settings WHERE setting_key LIKE 'email_%'");

// FIXED: Load all email-related settings
$stmt = $db->query("
    SELECT * FROM settings
    WHERE setting_key LIKE 'email_%'
       OR setting_key LIKE 'smtp_%'
       OR setting_key LIKE 'from_%'
");
```

## Email Template Types

### Existing Templates
| Type | Purpose | Trigger Location |
|------|---------|------------------|
| order_confirmation | Order placed confirmation | checkout/index.php:440 |
| password_reset | Password reset link | user/forgot-password/index.php:40 |
| welcome | New user welcome | ❓ Find trigger |

### Required New Templates
| Type | Purpose | Trigger Location |
|------|---------|------------------|
| payment_received | Payment confirmed | After payment gateway callback |
| order_status_pending | Order received | checkout completion |
| order_status_processing | Order being prepared | Admin status change |
| order_status_shipped | Order dispatched | Admin status change |
| order_status_delivered | Order completed | Admin status change |
| order_status_cancelled | Order cancelled | Admin status change |
| account_verification | Verify email address | User registration |
| coupon_received | Gift coupon/voucher | Coupon assignment |

## Placeholder Variables

### Common Variables (All Templates)
```
{{site_name}}          - Store name from settings
{{site_url}}           - Base URL
{{year}}               - Current year
{{current_date}}       - Full date
```

### Order-Related Templates
```
{{order_number}}       - Order reference
{{order_date}}         - Order creation date
{{order_total}}        - Total amount
{{order_subtotal}}     - Subtotal
{{order_shipping}}     - Shipping cost
{{order_discount}}     - Discount amount
{{order_items}}        - Items HTML table
{{payment_method}}     - Payment method name
{{payment_info}}       - Payment instructions
{{customer_name}}      - Customer full name
{{customer_email}}     - Customer email
{{shipping_address}}   - Full shipping address
{{billing_address}}    - Full billing address
{{track_order_url}}    - Order tracking link
```

### User-Related Templates
```
{{first_name}}         - User first name
{{last_name}}          - User last name
{{email}}              - User email
{{reset_url}}          - Password reset link
{{verification_url}}   - Email verification link
{{login_url}}          - Login page URL
```

## EmailService Class Design

### Current Methods
```php
class EmailService {
    private $db;
    private $settings = [];
    private $error = null;

    public function __construct($database = null);
    private function loadSettings();              // ✅ Works
    public function send($to, $toName, $subject, $body, $altBody = '');
    private function sendMail($to, $subject, $body, $headers);
    private function sendSMTP($to, $toName, $subject, $body, $headers);
    public function sendTestEmail($to);
    public function sendOrderConfirmation($order, $orderItems, $customerEmail, $customerName);
    private function getTemplate($type);          // ✅ Works
    private function getDefaultOrderTemplate(...);
    public function getError();
}
```

### Proposed New Methods
```php
// Template management
public function getAllTemplates();
public function updateTemplate($id, $data);
public function previewTemplate($type, $variables = []);

// New sending methods
public function sendWelcomeEmail($user);
public function sendPasswordReset($email, $name, $token);
public function sendOrderStatusUpdate($order, $status);
public function sendPaymentReceived($order, $paymentDetails);
public function sendAccountVerification($email, $name, $token);

// Reliability
private function logEmail($templateType, $recipient, $subject, $status);
public function queueEmail($templateType, $recipient, $variables);
public function processEmailQueue($limit = 10);
}
```

## Security Considerations

### SMTP Password Encryption
```php
// Save: Encrypt before storing
$encrypted = openssl_encrypt($password, 'AES-256-CBC', ENCRYPTION_KEY, 0, $iv);
$db->save('smtp_password', base64_encode($encrypted . '::' . $iv));

// Load: Decrypt when using
$parts = explode('::', base64_decode($encrypted));
$password = openssl_decrypt($parts[0], 'AES-256-CBC', ENCRYPTION_KEY, 0, $parts[1]);
```

### Input Validation
- Sanitize all template content (strip dangerous tags)
- Validate email addresses
- Limit subject line length
- Check for valid placeholders

## UI/UX Design

### Email Settings Page Layout
```
┌──────────────────────────────────────────────┐
│ Email Settings                     [Save]    │
├──────────────────────────────────────────────┤
│ SMTP Configuration                             │
│ ┌──────────────┬────────────────────────────┐│
│ │ Host         │ [smtp.hostinger.com    ]   ││
│ │ Port         │ [587                   ]   ││
│ │ Username     │ [support@...           ]   ││
│ │ Password     │ [••••••••••••••••••••]   ││
│ │ Encryption   │ [TLS ▼]                    ││
│ └──────────────┴────────────────────────────┘│
├──────────────────────────────────────────────┤
│ Sender Information                             │
│ ┌──────────────┬────────────────────────────┐│
│ │ From Email   │ [noreply@...           ]   ││
│ │ From Name    │ [Store Name            ]   ││
│ └──────────────┴────────────────────────────┘│
├──────────────────────────────────────────────┤
│ Test Configuration                             │
│ [Email: _________________] [Send Test Email]   │
└──────────────────────────────────────────────┘
```

### Email Templates Page Layout
```
┌──────────────────────────────────────────────┐
│ Email Templates                    [? Help]  │
├──────────────────────────────────────────────┤
│ [All ▼] [Active ▼]        [Search: ______]   │
├──────────────────────────────────────────────┤
│ ┌──────────────────────────────────────────────┐ │
│ │ Order Confirmation               [Active 🟢]   │ │
│ │ Key: order_confirmation                    │ │
│ │ Subject: Order Confirmed - {{order_number}}  │ │
│ │ [Preview] [Edit] [Test]             │ │
│ └──────────────────────────────────────────────┘ │
│ ┌──────────────────────────────────────────────┐ │
│ │ Password Reset               [Active 🟢]   │ │
│ │ Key: password_reset                    │ │
│ │ [Preview] [Edit] [Test]              │ │
│ └──────────────────────────────────────────────┘│
└──────────────────────────────────────────────┘
```

## Error Handling Strategy

### SMTP Errors
| Error | Action | User Message |
|-------|--------|------------|
| Connection refused | Log, queue retry | "Email queued, will retry" |
| Authentication fail | Log, alert admin | "Email config error" |
| Invalid recipient | Log, mark failed | "Invalid email address" |
| Rate limited | Log, queue retry | "Email delayed" |

### Template Errors
| Error | Action | User Message |
|-------|--------|------------|
| Template not found | Use default | (silent) |
| Missing placeholder | Leave as-is | (silent) |
| Invalid HTML | Clean with tidy | "Template cleaned" |
| Database error | Log, skip email | "Email temporarily unavailable" |

## Testing Strategy

### Unit Tests (via test_delete/)
1. `test_email_service.php` - Test all EmailService methods
2. `test_smtp_connection.php` - Verify SMTP connectivity
3. `test_templates.php` - Verify all templates load correctly

### Integration Tests
1. Place order → Verify confirmation email
2. Register user → Verify welcome email
3. Request password reset → Verify reset email
4. Update order status → Verify status email

### Manual Testing Checklist
- [ ] SMTP settings save and persist
- [ ] Test email sends successfully
- [ ] All template placeholders work
- [ ] Email looks correct in Gmail/Outlook
- [ ] Mobile responsive
- [ ] Plain text version readable
