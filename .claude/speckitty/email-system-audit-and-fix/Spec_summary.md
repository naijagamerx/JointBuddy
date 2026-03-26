# Email System Fix & Enhancement - Specification Summary

## Executive Summary

**Problem**: SMTP settings in admin panel save to database but disappear from UI after refresh.

**Root Cause**: Query mismatch - saves keys like `smtp_host` but loads `WHERE setting_key LIKE 'email_%'`.

**Impact**: Email functionality appears broken to admins, though emails may still send with saved settings.

**Solution**: Fix query to load all email-related settings. Then audit and complete the email template system.

---

## Quick Reference

### The Bug (One-Line Fix)
```php
// admin/settings/email.php line ~38
// BEFORE:
$stmt = $db->query("SELECT * FROM settings WHERE setting_key LIKE 'email_%'");

// AFTER:
$stmt = $db->query("
    SELECT * FROM settings
    WHERE setting_key LIKE 'email_%'
       OR setting_key LIKE 'smtp_%'
       OR setting_key IN ('from_email', 'from_name')
");
```

### Current Email Templates (3)
| Template | Status | Used By |
|----------|--------|---------|
| order_confirmation | ✅ Ready | checkout/index.php |
| password_reset | ✅ Ready | forgot-password/index.php |
| welcome | ✅ Ready | Need to find trigger |

### Missing Templates (Need Creation)
- payment_received
- order_status_processing
- order_status_shipped
- order_status_delivered
- order_status_cancelled
- account_verification
- coupon_received

---

## Architecture

```
Database Settings (9 keys):
├── smtp_host, smtp_port, smtp_username
├── smtp_password, smtp_encryption
├── from_email, from_name
├── email_frequency, email_verification

Database Templates (3+ needed):
├── order_confirmation, password_reset, welcome
├── [+payment_received, +status_updates, etc.]

Code Structure:
├── includes/email_service.php - Core service
├── admin/settings/email.php - SMTP config (BROKEN)
├── admin/settings/email-templates/ - Template editor
└── Triggers in checkout/, user/, admin/
```

---

## Implementation Plan

### Phase 1: URGENT FIX (30 minutes)
- Fix settings loading query in admin/settings/email.php
- Test persistence
- Verify all fields save/load correctly

### Phase 2: AUDIT (1-2 hours)
- Find all email triggers in codebase
- Verify templates exist for each
- Test current email sending

### Phase 3: ENHANCE (6-10 hours)
- Create missing templates
- Add template management features
- Enhance EmailService class
- Add email logging

### Phase 4: SECURE & TEST (2-3 hours)
- Encrypt SMTP password
- Add validation
- Comprehensive testing

---

## Key Files

| File | Purpose | Action |
|------|---------|--------|
| admin/settings/email.php | SMTP config page | **FIX QUERY** |
| includes/email_service.php | Email sending service | Enhance |
| admin/settings/email-templates/index.php | Template editor | Enhance |
| seed_templates.php | Template seeder | Add missing |

---

## Testing Checklist

- [ ] Save SMTP settings → refresh → fields persist
- [ ] Send test email → arrives in inbox
- [ ] Place order → confirmation email sent
- [ ] Reset password → reset email sent
- [ ] Edit template → changes apply to emails
- [ ] No "CannaBuddy" in any email

---

## Risk Mitigation

| Risk | Mitigation |
|------|------------|
| Breaking emails | Test each type after changes |
| Password exposure | Encrypt in database |
| Template corruption | Validate HTML before save |
| SMTP failures | Add logging, retry |

---

## Acceptance Criteria

1. **P0**: SMTP settings persist after page refresh
2. **P1**: All email types have working templates
3. **P1**: Order confirmation email sends correctly
4. **P1**: Password reset email sends correctly
5. **P2**: Admin can edit templates via UI
6. **P2**: Email sending is logged

---

## Notes

- **No hardcoded "CannaBuddy"** in any email
- **Use url_helper.php** for all links
- **Test on Hostinger** before marking complete
- **All test files** go to test_delete/

---

## Links to Full Documentation

- [Requirements](requirements.md) - Detailed requirements & acceptance criteria
- [Design](design.md) - Architecture, database schema, API design
- [Tasks](tasks.md) - Step-by-step implementation checklist

---

**Generated**: 2026-03-24
**Skill**: Speckitty v1.0
**Status**: Ready for implementation
