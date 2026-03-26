# Email System Fix & Enhancement Specification

## Project Overview

**Task**: Fix SMTP settings persistence bug and audit/implement complete email template system
**Priority**: High
**Impact**: Critical - Email functionality is core to e-commerce operations

## Current State Analysis

### The Bug (Root Cause Identified)
- **Location**: `admin/settings/email.php`
- **Issue**: Settings save correctly to database but disappear from UI after refresh
- **Root Cause**:
  - Form submission saves keys: `smtp_host`, `smtp_port`, `smtp_username`, etc. (no prefix)
  - Form loading query: `WHERE setting_key LIKE 'email_%'`
  - Result: SMTP settings saved but never retrieved

### Existing Database Schema
**email_templates table**:
| Field | Type | Notes |
|-------|------|-------|
| id | int(11) | PK |
| type | varchar(50) | Template identifier |
| name | varchar(100) | Display name |
| subject | varchar(255) | Email subject |
| html_content | text | HTML body |
| text_content | text | Plain text alternative |
| active | tinyint(1) | Enable/disable |
| created_at | timestamp | Creation time |
| updated_at | timestamp | Last modified |

### Current Email Templates (3 exists)
1. **order_confirmation** - Order Confirmation
2. **password_reset** - Password Reset
3. **welcome** - Welcome Email

### Current Settings in Database
- `email_frequency` = immediate
- `email_verification` = 1
- `from_email` = support@apextfunding.com
- `from_name` = JointBuddy
- `smtp_encryption` = ssl
- `smtp_host` = smtp.hostinger.com
- `smtp_password` = [hidden]
- `smtp_port` = 587
- `smtp_username` = support@apextfunding.com

### Where Emails Are Triggered
1. **Order Confirmation** - `checkout/index.php` line 440
2. **Password Reset** - `user/forgot-password/index.php` line 40
3. **Welcome Email** - Currently triggered where?

## Requirements

### R1: Fix SMTP Settings Persistence
**Priority**: P0 (Critical)
**Acceptance Criteria**:
- [ ] SMTP settings save to database and persist after page refresh
- [ ] All form fields (host, port, username, password, encryption) display saved values
- [ ] From email and from name display saved values
- [ ] Notification checkboxes display saved state
- [ ] Form validation prevents empty required fields
- [ ] Success/error messages display correctly

### R2: Audit & Complete Email Templates
**Priority**: P1 (High)
**Acceptance Criteria**:
- [ ] Inventory of all email sending locations in codebase
- [ ] Verify all templates exist for each email type
- [ ] Ensure templates use consistent branding (no "CannaBuddy" hardcoded)
- [ ] All templates support placeholder variables
- [ ] Templates are editable via admin panel

### R3: Missing Email Templates Implementation
**Priority**: P1 (High)
**Acceptance Criteria**:
- [ ] Create `payment_received` template
- [ ] Create `order_status_update` template (pending, processing, shipped, delivered, cancelled)
- [ ] Create `delivery_scheduled` template
- [ ] Create `account_verification` template
- [ ] Create `coupon_received` template
- [ ] Create `abandoned_cart` template (optional)

### R4: Email Template Management Enhancement
**Priority**: P2 (Medium)
**Acceptance Criteria**:
- [ ] Add email template preview functionality
- [ ] Add "Send Test Email" for each template
- [ ] Show list of available placeholders per template
- [ ] Validate template HTML before save
- [ ] Add template categories/filters

### R5: Email Sending Reliability
**Priority**: P1 (High)
**Acceptance Criteria**:
- [ ] Log all sent emails with status
- [ ] Queue failed emails for retry
- [ ] Add rate limiting to prevent spam
- [ ] Handle SMTP connection failures gracefully
- [ ] Send async where possible (don't block user actions)

### R6: Configuration Security
**Priority**: P1 (High)
**Acceptance Criteria**:
- [ ] SMTP password encrypted in database
- [ ] Password field masked in UI
- [ ] Test email feature validates permissions
- [ ] No credentials in error logs

## Out of Scope
- Third-party email service integration (SendGrid, Mailgun)
- Email analytics/statistics dashboard
- Customer email preferences management
- A/B testing for email templates

## Technical Constraints
- PHP 8.3 (no framework)
- MySQL 5.7
- Tailwind CSS for UI
- Must use existing `url_helper.php` for URLs
- Must work on Hostinger shared hosting
- No external dependencies beyond existing

## Success Criteria
1. SMTP settings persist correctly and are visually confirmed after save
2. All email types have corresponding templates in database
3. Order confirmation email sends successfully with template
4. Password reset email sends successfully with template
5. Welcome email sends on registration
6. Admin can edit all templates without code changes

## Files to Modify/Create

### Core Files
- `admin/settings/email.php` - Fix persistence bug
- `admin/settings/email-templates/index.php` - Enhance template management
- `includes/email_service.php` - Add missing template types, improve reliability

### New Files (if needed)
- `test_delete/verify_email_system.php` - Diagnostic script
- Database migration for new templates

### Email Triggers to Verify
- `checkout/index.php` - Order confirmation
- `user/forgot-password/index.php` - Password reset
- `user/register/index.php` - Welcome email (if exists)
- `admin/orders/update.php` - Status updates (if exists)

## Risks & Mitigation

| Risk | Impact | Mitigation |
|------|--------|------------|
| Breaking existing email sending | High | Test each email type after changes |
| SMTP credentials exposure | High | Encrypt password, mask in UI |
| Template HTML corruption | Medium | Validate before save, backup originals |
| Email delivery failures | Medium | Add logging, retry mechanism |

## Timeline Estimate
- R1 (Fix persistence): 2-3 hours
- R2+R3 (Templates): 4-6 hours
- R4 (Enhancements): 3-4 hours
- R5 (Reliability): 4-6 hours
- R6 (Security): 2 hours
- Testing: 3-4 hours
- **Total**: 18-25 hours
