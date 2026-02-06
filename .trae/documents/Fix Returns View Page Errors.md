## Fix Plan for Returns View Page Issues

### Issue 1: `Call to undefined method AdminAuth::getAdminId()` (Line 96)

**Root Cause:**
- The `AdminAuth` class in `includes/database.php` does NOT have a `getAdminId()` method
- Line 96 in `admin/returns/view.php` calls `$adminAuth->getAdminId()` which doesn't exist

**Available AdminAuth methods:**
- `login($username, $password, $ip_address)`
- `logout()`
- `isLoggedIn()`
- `getCurrentAdmin()` - This returns the full admin array including id

**Fix:**
Replace `$adminAuth->getAdminId()` with `$adminAuth->getCurrentAdmin()['id']` OR add `getAdminId()` method to AdminAuth class

---

### Issue 2: "Cannot transition from 'approved' to 'approved'"

**Root Cause:**
- Return ID 4 is already in 'approved' status
- User expects to see "Approve/Reject/Cancel" buttons
- For 'approved' status, only these buttons are available:
  - "Mark as Received" (transitions to 'received')
  - "Cancel Return" (transitions to 'cancelled')
- Status transition rules:
  ```
  'pending'   => ['approved', 'rejected', 'cancelled']
  'approved'   => ['received', 'cancelled']
  'received'   => ['refunded']
  ```

**Fix:**
The error message is correct - it's a validation message. However, the UX issue is that users don't understand why buttons change.

**Improve UX:**
1. Add prominent current status badge at top of page
2. Show available actions clearly
3. Better explanation when status transition fails

---

### Issue 3: Page goes blank when clicking buttons

**Root Cause:**
- The `getAdminId()` fatal error causes the page to fail rendering
- The error handler should catch and display errors, but the page might still appear blank due to exception handling

**Fix:**
1. Fix the `getAdminId()` call (Issue 1)
2. Verify error handling works properly

---

### Changes Required

**File: `admin/returns/view.php`**
- Line 96: Replace `$adminAuth->getAdminId()` with `$adminAuth->getCurrentAdmin()['id']`

**File: `includes/database.php`** (Optional - alternative fix)
- Add `getAdminId()` method to AdminAuth class for convenience:
  ```php
  public function getAdminId() {
      return $_SESSION['admin_id'] ?? null;
  }
  ```

**File: `admin/returns/view.php`**
- Add current status badge prominently
- Improve error messages for invalid transitions