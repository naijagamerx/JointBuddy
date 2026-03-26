# Email System Implementation Tasks

## Phase 1: Fix SMTP Settings Persistence (CRITICAL - Do First)

### Task 1.1: Fix Settings Loading Query
**File**: `admin/settings/email.php`
**Line**: ~38
**Priority**: P0

```php
// CURRENT (BROKEN):
$stmt = $db->query("SELECT * FROM settings WHERE setting_key LIKE 'email_%'");

// FIXED:
$stmt = $db->query("
    SELECT * FROM settings
    WHERE setting_key LIKE 'email_%'
       OR setting_key LIKE 'smtp_%'
       OR setting_key IN ('from_email', 'from_name')
    ORDER BY setting_key ASC
");
```

**Acceptance**:
- [ ] After saving, all SMTP fields display saved values
- [ ] From email/name display saved values
- [ ] Checkboxes show correct checked state
- [ ] Page refresh preserves all values

### Task 1.2: Add Form Field Name Consistency Check
**File**: `admin/settings/email.php`
**Priority**: P0

Verify these form field names match database keys exactly:
- `smtp_host` → database: `smtp_host` ✅
- `smtp_port` → database: `smtp_port` ✅
- `smtp_username` → database: `smtp_username` ✅
- `smtp_password` → database: `smtp_password` ✅
- `smtp_encryption` → database: `smtp_encryption` ✅
- `from_email` → database: `from_email` ✅
- `from_name` → database: `from_name` ✅

**Acceptance**:
- [ ] All form inputs have correct `name` attributes
- [ ] No mismatches between form and database

### Task 1.3: Test Settings Persistence
**File**: Create `test_delete/test_email_settings.php`
**Priority**: P0

Create diagnostic script to verify:
1. Settings save correctly
2. Settings load correctly
3. All fields persist after refresh

**Acceptance**:
- [ ] Run test script, all checks pass
- [ ] Manual test: save settings, refresh, verify all fields

---

## Phase 2: Audit Current Email System

### Task 2.1: Inventory All Email Triggers
**Priority**: P1

Search codebase for email sending locations:
```bash
grep -r "EmailService\|->send\|mail(" --include="*.php" | grep -v vendor
```

**Acceptance**:
- [ ] List of all files that send emails
- [ ] Template type used for each
- [ ] Location documented in audit doc

**Known locations**:
- [x] `checkout/index.php:440` - sendOrderConfirmation
- [x] `user/forgot-password/index.php:40` - send (password_reset)
- [ ] `user/register/index.php` - Welcome email? (verify)
- [ ] `admin/orders/` - Status updates? (verify)

### Task 2.2: Verify Template Completeness
**Priority**: P1

Check each discovered email trigger has corresponding template:

| Trigger | Template Exists | Template Works |
|---------|-----------------|----------------|
| Order confirmation | ✅ Yes | Test |
| Password reset | ✅ Yes | Test |
| Welcome email | ✅ Yes | Find trigger |
| Payment received | ❌ No | Create |
| Status updates | ❌ No | Create |

**Acceptance**:
- [ ] All triggers verified
- [ ] Missing templates identified
- [ ] Template placeholders documented

### Task 2.3: Test Current Email Sending
**Priority**: P1

Test existing emails:
- [ ] Order confirmation sends successfully
- [ ] Password reset sends successfully
- [ ] Welcome email sends successfully (if trigger exists)
- [ ] All emails use correct templates
- [ ] No "CannaBuddy" branding in emails

---

## Phase 3: Create Missing Email Templates

### Task 3.1: Seed Payment Received Template
**File**: `seed_templates.php` (or run SQL)
**Priority**: P1

```sql
INSERT INTO email_templates (type, name, subject, html_content, active) VALUES
('payment_received', 'Payment Received', 'Payment Confirmed - {{order_number}}', '...HTML...', 1);
```

**Acceptance**:
- [ ] Template in database
- [ ] Uses consistent branding
- [ ] All order placeholders work

### Task 3.2: Seed Order Status Templates
**File**: Database SQL
**Priority**: P1

Create templates for:
- [ ] order_status_processing
- [ ] order_status_shipped
- [ ] order_status_delivered
- [ ] order_status_cancelled

Each should include:
- Order number
- Status change notification
- Current status explanation
- Next steps (if applicable)

### Task 3.3: Seed Account Verification Template
**File**: Database SQL
**Priority**: P1

```sql
INSERT INTO email_templates (type, name, subject, html_content, active) VALUES
('account_verification', 'Verify Your Email', 'Please verify your email address', '...HTML...', 1);
```

**Acceptance**:
- [ ] Template includes {{verification_url}} placeholder
- [ ] Clear CTA to verify email
- [ ] Expiration notice

### Task 3.4: Seed Coupon/Voucher Template
**File**: Database SQL
**Priority**: P2

Create template for sending coupons to customers.

---

## Phase 4: Enhance EmailService Class

### Task 4.1: Add Missing Send Methods
**File**: `includes/email_service.php`
**Priority**: P1

Add methods:
```php
public function sendWelcomeEmail($user) { ... }
public function sendPaymentReceived($order, $paymentDetails) { ... }
public function sendOrderStatusUpdate($order, $newStatus) { ... }
public function sendAccountVerification($email, $name, $token) { ... }
```

**Acceptance**:
- [ ] All methods use template system
- [ ] All methods support placeholders
- [ ] All methods log to email_logs (if created)

### Task 4.2: Add Template Management Methods
**File**: `includes/email_service.php`
**Priority**: P2

```php
public function getAllTemplates() { ... }
public function getTemplateByType($type) { ... }
public function previewTemplate($type, $variables = []) { ... }
```

**Acceptance**:
- [ ] Methods return proper data
- [ ] Preview replaces placeholders
- [ ] Error handling for missing templates

### Task 4.3: Add Email Logging
**File**: `includes/email_service.php`
**Priority**: P2

Create `email_logs` table and add logging:

```php
private function logEmail($templateType, $recipient, $subject, $status, $error = null) {
    // Insert to email_logs table
}
```

**Acceptance**:
- [ ] All emails logged
- [ ] Failed emails tracked with error
- [ ] Can query email history

---

## Phase 5: Enhance Admin Email Templates Page

### Task 5.1: Add Template Preview
**File**: `admin/settings/email-templates/index.php`
**Priority**: P2

Add "Preview" button that shows rendered template with sample data.

**Acceptance**:
- [ ] Preview modal opens
- [ ] Placeholders replaced with sample data
- [ ] HTML rendered correctly

### Task 5.2: Add Send Test Email per Template
**File**: `admin/settings/email-templates/index.php`
**Priority**: P2

Each template should have "Send Test" button with email input.

**Acceptance**:
- [ ] Test email sends with template
- [ ] Sample data used for placeholders
- [ ] Success/error message shown

### Task 5.3: Add Placeholder Documentation
**File**: `admin/settings/email-templates/index.php`
**Priority**: P2

Show available placeholders per template type:
- Order templates: {{order_number}}, {{customer_name}}, etc.
- User templates: {{first_name}}, {{reset_url}}, etc.

**Acceptance**:
- [ ] Placeholders listed in UI
- [ ] Description of each
- [ ] Copy-to-clipboard buttons

---

## Phase 6: Security & Reliability

### Task 6.1: Encrypt SMTP Password
**File**: `includes/email_service.php`, `admin/settings/email.php`
**Priority**: P1

Add encryption for smtp_password:
```php
// Before saving
$encrypted = encrypt_password($password);

// Before using
$password = decrypt_password($encrypted);
```

**Acceptance**:
- [ ] Password encrypted in database
- [ ] Password decrypted when sending email
- [ ] No plaintext password exposure

### Task 6.2: Add Input Validation
**File**: `admin/settings/email-templates/index.php`
**Priority**: P2

Validate:
- [ ] Template HTML is valid
- [ ] No dangerous tags (script, iframe)
- [ ] Subject line not too long
- [ ] Required fields present

### Task 6.3: Add Error Handling
**File**: `includes/email_service.php`
**Priority**: P2

Add try-catch around:
- [ ] SMTP connections
- [ ] Template loading
- [ ] Database queries

**Acceptance**:
- [ ] Graceful failures
- [ ] Errors logged
- [ ] User sees helpful messages

---

## Phase 7: Testing & Verification

### Task 7.1: Create Comprehensive Test Script
**File**: `test_delete/test_email_system.php`
**Priority**: P1


Test:
- [ ] Settings persistence
- [ ] All templates load
- [ ] SMTP connectivity
- [ ] Email sending

### Task 7.2: Manual Testing Checklist
**Priority**: P1

- [ ] Save SMTP settings → Refresh → Verify persistence
- [ ] Send test email → Verify receipt
- [ ] Place order → Verify confirmation email
- [ ] Request password reset → Verify email
- [ ] Edit template → Verify changes apply
- [ ] Check no "CannaBuddy" in any email

### Task 7.3: Code Review
**Priority**: P1

Review for:
- [ ] No hardcoded URLs
- [ ] No "CannaBuddy" strings
- [ ] Proper error handling
- [ ] SQL injection prevention
- [ ] XSS prevention

---

## Phase 8: Documentation

### Task 8.1: Update Admin Documentation
**File**: (Add to existing docs)
**Priority**: P2

Document:
- [ ] How to configure SMTP
- [ ] How to edit templates
- [ ] Available placeholders
- [ ] Troubleshooting guide

### Task 8.2: Code Comments
**Priority**: P2

Add comments to:
- [ ] EmailService class methods
- [ ] Complex template logic
- [ ] Placeholder replacement
- [ ] Error handling sections

---

## Summary

| Phase | Tasks | Est. Time | Priority |
|-------|-------|-----------|----------|
| 1 | Fix persistence | 2-3 hrs | P0 |
| 2 | Audit system | 2-3 hrs | P1 |
| 3 | Create templates | 4-6 hrs | P1 |
| 4 | Enhance service | 3-4 hrs | P1 |
| 5 | Enhance admin | 3-4 hrs | P2 |
| 6 | Security | 2-3 hrs | P1 |
| 7 | Testing | 3-4 hrs | P1 |
| 8 | Documentation | 1-2 hrs | P2 |
| **Total** | | **20-29 hrs** | |

## Immediate Next Steps

1. **Start with Task 1.1** - Fix the settings loading query (30 min fix)
2. **Task 1.3** - Verify fix works (15 min)
3. **Task 2.1** - Audit email triggers (1 hour)
4. Continue with template creation
