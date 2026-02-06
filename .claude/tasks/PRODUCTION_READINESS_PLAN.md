# 🚀 CannaBuddy.shop Production Readiness Plan

## Executive Summary

**Timeline:** 2-3 weeks  
**Priority:** Critical fixes first, then hardening, then validation  
**Current Score:** 5/10 → **Target:** 9/10

---

## Phase 1: CRITICAL FIXES (Week 1)

### Task 1.1: Remove All Debug Code & Test Files
**Priority:** CRITICAL  
**Time:** 2 hours

| Action | File | Details |
|--------|------|---------|
| Delete test folder | test_delete/ | Contains debug/test files |
| Remove debug routing | index.php, route.php | Remove ?debug_routing=1 and ?debug=1 parameters |
| Clean debug output | checkout/index.php | Remove conditional debug echo statements |
| Remove test data | database.php | Any sample/demo data in code |

Commands:
```
rm -rf test_delete/
grep -r "debug_routing\|debug=1\|DEBUG_MODE" --include="*.php" .
```

---

### Task 1.2: Create .gitignore
**Priority:** CRITICAL  
**Time:** 30 minutes

File: .gitignore
```
# Development
/test_delete/
.phpunit.result.cache
.php-cs-fixer.cache

# Logs
logs/
*.log
error_log
debug.log

# Configuration
.env
.env.*
config.local.php

# Vendor
/vendor/

# IDE
.idea/
.vscode/
*.swp
*.swo

# OS
.DS_Store
Thumbs.db

# Uploads
/uploads/products/*
!/uploads/products/.gitkeep
```

---

### Task 1.3: Configure SSL/TLS (HTTPS)
**Priority:** CRITICAL  
**Time:** 2-4 hours

#### 1.3.1: Install SSL Certificate
```
# Option A: Let's Encrypt (free)
certbot --apache -d cannawabuddy.shop -d www.cannabuddy.shop

# Option B: Hostinger Auto-SSL
# Enable in hosting panel → SSL/TLS → Auto-SSL
```

#### 1.3.2: Force HTTPS in URL Helper
File: includes/url_helper.php

```php
function url($path = "") {
    $baseUrl = rtrim(getBaseUrl(), "/");
    $protocol = isHttpsEnabled() ? "https://" : "http://";
    return $protocol . $baseUrl . "/" . ltrim($path, "/");
}

function isHttpsEnabled() {
    return (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") 
        || $_SERVER["SERVER_PORT"] == 443;
}
```

#### 1.3.3: Add HSTS Header
File: includes/security_headers.php
```php
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
```

---

### Task 1.4: Secure Configuration Management
**Priority:** CRITICAL  
**Time:** 2 hours

File: .env
```
APP_ENV=production
DEBUG_MODE=false
DB_HOST=localhost
DB_NAME=cannabuddy
DB_USER=root
DB_PASS=your_secure_password_here
SESSION_SECURE=true
SESSION_HTTPONLY=true
```

File: config.php (update)
```php
<?php
// Load environment variables from .env
$envFile = __DIR__ . "/.env";
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), "#") === 0) continue;
        if (strpos($line, "=") !== false) {
            list($name, $value) = explode("=", $line, 2);
            $_ENV[trim($name)] = trim($value);
        }
    }
}

define("DEBUG_MODE", $_ENV["DEBUG_MODE"] ?? false);
define("APP_ENV", $_ENV["APP_ENV"] ?? "production");

if (APP_ENV === "production") {
    error_reporting(0);
    ini_set("display_errors", "0");
}
```

---

## Phase 2: SECURITY HARDENING (Week 1-2)

### Task 2.1: Implement Rate Limiting
**Priority:** HIGH  
**Time:** 4 hours

File: includes/rate_limiter.php (create new)
```php
<?php
class RateLimiter {
    private $attempts = [];
    
    public function check($key, $maxAttempts = 5, $decayMinutes = 30) {
        $cacheKey = "rate_limit:" . $key;
        $attempts = $this->getAttempts($cacheKey);
        
        if ($attempts >= $maxAttempts) {
            return false;
        }
        
        $this->incrementAttempt($cacheKey, $decayMinutes);
        return true;
    }
    
    private function getAttempts($key) {
        $file = "logs/rate_" . md5($key) . ".dat";
        if (!file_exists($file)) return 0;
        $data = unserialize(file_get_contents($file));
        if ($data["expires"] < time()) {
            unlink($file);
            return 0;
        }
        return $data["attempts"];
    }
    
    private function incrementAttempt($key, $decayMinutes) {
        $file = "logs/rate_" . md5($key) . ".dat";
        $attempts = $this->getAttempts($key) + 1;
        file_put_contents($file, serialize([
            "attempts" => $attempts,
            "expires" => time() + ($decayMinutes * 60)
        ]));
    }
}

// Usage:
$limiter = new RateLimiter();
if (!$limiter->check("login:" . ($_SERVER["REMOTE_ADDR"] ?? "unknown"), 5, 30)) {
    http_response_code(429);
    die("Too many login attempts. Please try again in 30 minutes.");
}
```

Apply rate limiting to:
- admin/login/index.php - 5 attempts / 30 min
- user/login/index.php - 10 attempts / 30 min
- checkout/index.php - 20 attempts / 10 min

---

### Task 2.2: Enhanced Security Headers
**Priority:** HIGH  
**Time:** 2 hours

File: includes/security_headers.php (update existing)
```php
<?php
function setSecurityHeaders() {
    header("X-XSS-Protection: 1; mode=block");
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: DENY");
    
    $csp = "default-src 'self'; " .
           "script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdn.jsdelivr.net; " .
           "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
           "img-src 'self' data: https:; " .
           "font-src 'self' https://fonts.gstatic.com; " .
           "connect-src 'self'; " .
           "frame-ancestors 'none';";
    header("Content-Security-Policy: $csp");
    
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
    
    if (isHttpsEnabled()) {
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
    }
}

setSecurityHeaders();
```

---

### Task 2.3: Enhanced Audit Logging
**Priority:** MEDIUM  
**Time:** 3 hours

File: includes/audit_logger.php (create new)
```php
<?php
class AuditLogger {
    private $logFile = "logs/audit.log";
    
    public function log($action, $userId, $details = []) {
        $entry = [
            "timestamp" => date("Y-m-d H:i:s"),
            "action" => $action,
            "user_id" => $userId,
            "ip" => $_SERVER["REMOTE_ADDR"] ?? "unknown",
            "user_agent" => $_SERVER["HTTP_USER_AGENT"] ?? "unknown",
            "details" => $details
        ];
        
        file_put_contents($this->logFile, json_encode($entry) . "\n", FILE_APPEND | LOCK_EX);
    }
}

// Usage:
$logger = new AuditLogger();
$logger->log("admin_login", $adminId, ["success" => true]);
$logger->log("order_created", $userId, ["order_id" => $orderId, "total" => $total]);
$logger->log("product_updated", $adminId, ["product_id" => $productId]);
```

---

## Phase 3: PERFORMANCE OPTIMIZATION (Week 2)

### Task 3.1: Implement Caching System
**Priority:** HIGH  
**Time:** 6-8 hours

File: includes/cache.php (create new)
```php
<?php
class Cache {
    private $cacheDir = "cache/";
    private $ttl = 3600;
    
    public function __construct() {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    public function get($key) {
        $file = $this->cacheDir . md5($key) . ".cache";
        if (!file_exists($file)) return null;
        
        $data = unserialize(file_get_contents($file));
        if ($data["expires"] < time()) {
            unlink($file);
            return null;
        }
        return $data["value"];
    }
    
    public function set($key, $value, $ttl = null) {
        $file = $this->cacheDir . md5($key) . ".cache";
        file_put_contents($file, serialize([
            "value" => $value,
            "expires" => time() + ($ttl ?? $this->ttl)
        ]), LOCK_EX);
    }
    
    public function delete($key) {
        $file = $this->cacheDir . md5($key) . ".cache";
        if (file_exists($file)) unlink($file);
    }
    
    public function clear() {
        $files = glob($this->cacheDir . "*.cache");
        foreach ($files as $file) unlink($file);
    }
}
```

Usage example:
```php
$cache = new Cache();
$categories = $cache->get("all_categories");
if (!$categories)
---

## Phase 4: INFRASTRUCTURE (Week 2-3)

### Task 4.1: Automated Database Backups
**Priority:** HIGH  
**Time:** 4 hours

File: backup.php (create new)
```php
<?php
// Automated backup script - run via cron
$host = "localhost";
$db = "cannabuddy";
$user = "root";
$pass = "root";
$backupDir = __DIR__ . "/../backups/";

if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);

$filename = $backupDir . "cannabuddy_" . date("Y-m-d_H-i-s") . ".sql.gz";
$command = "mysqldump -h $host -u $user -p'$pass' $db | gzip > $filename";

exec($command . " 2>&1", $output, $return);

// Keep only last 30 days
$files = glob($backupDir . "*.sql.gz");
foreach ($files as $file) {
    if (filemtime($file) < time() - 30 * 86400) {
        unlink($file);
    }
}

echo "Backup completed: " . $filename . "\n";
```

Cron Setup:
```
# Run daily at 2 AM
0 2 * * * /usr/bin/php /path/to/backup.php >> /var/log/backup.log 2>&1
```

---

### Task 4.2: Monitoring & Alerting
**Priority:** MEDIUM  
**Time:** 4 hours

File: includes/monitor.php (create new)
```php
<?php
class Monitor {
    private $logDir = "logs/";
    
    public function trackPerformance($name, $startTime) {
        $duration = microtime(true) - $startTime;
        if ($duration > 2.0) {
            $this->log("slow_page", [
                "page" => $name,
                "duration" => round($duration, 3),
                "url" => $_SERVER["REQUEST_URI"] ?? "unknown"
            ]);
        }
    }
    
    public function log($type, $data) {
        if (!is_dir($this->logDir)) mkdir($this->logDir, 0755, true);
        $file = $this->logDir . $type . ".log";
        $entry = [
            "timestamp" => date("Y-m-d H:i:s"),
            "data" => $data,
            "ip" => $_SERVER["REMOTE_ADDR"] ?? "unknown"
        ];
        file_put_contents($file, json_encode($entry) . "\n", FILE_APPEND | LOCK_EX);
    }
}
```

---

### Task 4.3: Uptime Monitoring Setup
**Priority:** LOW  
**Time:** 1 hour

Option 1: UptimeRobot (free tier - 5 monitors)
- https://uptimerobot.com - Sign up and add:
  - https://cannabuddy.shop
  - https://cannabuddy.shop/admin/
  - https://cannabuddy.shop/cart/

Option 2: Cron-based health check
```
*/5 * * * * curl -s -o /dev/null -w "%{http_code}" https://cannabuddy.shop/ | grep -q "200" || mail -s "Site Down" admin@email.com
```

---

## Phase 5: TESTING & VALIDATION (Week 3)

### Task 5.1: Load Testing
**Priority:** HIGH  
**Time:** 2 hours

Using k6 load test script: load-test.js
```javascript
import http from "k6/http";
import { check, sleep } from "k6";

export let options = {
  stages: [
    { duration: "2m", target: 100 },
    { duration: "5m", target: 100 },
    { duration: "2m", target: 0 },
  ],
};

export default function() {
  let res = http.get("https://cannabuddy.shop/");
  check(res, { "status is 200": (r) => r.status === 200 });
  sleep(1);
}
```

Run test:
```
k6 run load-test.js
```

---

### Task 5.2: Security Audit
**Priority:** HIGH  
**Time:** 2 hours

Automated Scanning:
```bash
composer require --dev phpunit/phpunit
./vendor/bin/phpunit

composer require --dev phpstan/phpstan
./vendor/bin/phpstan analyze
```

Manual Security Review:
- Test all forms for XSS vulnerabilities
- Test admin authentication bypass attempts
- Test file upload with malicious files
- Test SQL injection on all input fields
- Verify CSRF protection on all forms

---

### Task 5.3: Final Pre-Launch Checklist
**Priority:** HIGH  
**Time:** 1 hour

## Pre-Launch Checklist

### Security
- [ ] SSL certificate installed and working
- [ ] HTTPS forced on all pages
- [ ] Security headers implemented
- [ ] Rate limiting enabled
- [ ] Debug code removed
- [ ] .gitignore created

### Performance
- [ ] Caching enabled
- [ ] Database indexes added
- [ ] Load testing passed (100 concurrent users)

### Infrastructure
- [ ] Automated backups configured
- [ ] Uptime monitoring configured
- [ ] Error logging working
- [ ] Logs rotation configured

### Code Quality
- [ ] No test files in production
- [ ] No debug output visible
- [ ] Error reporting disabled
- [ ] All external dependencies loaded securely

### Functionality
- [ ] All payment methods tested
- [ ] Email notifications working
- [ ] Admin functions verified
- [ ] User registration/login tested
- [ ] Cart/checkout flow tested

---

## Implementation Summary

| Phase | Task | Priority | Time | Status |
|-------|------|----------|------|--------|
| 1.1 | Remove debug code & test files | CRITICAL | 2h | Pending |
| 1.2 | Create .gitignore | CRITICAL | 30m | Pending |
| 1.3 | Configure SSL/TLS | CRITICAL | 4h | Pending |
| 1.4 | Secure configuration | CRITICAL | 2h | Pending |
| 2.1 | Rate limiting | HIGH | 4h | Pending |
| 2.2 | Security headers | HIGH | 2h | Pending |
| 2.3 | Audit logging | MEDIUM | 3h | Pending |
| 3.1 | Caching system | HIGH | 8h | Pending |
| 3.2 | Database optimization | HIGH | 4h | Pending |
| 3.3 | Session optimization | MEDIUM | 2h | Pending |
| 4.1 | Automated backups | HIGH | 4h | Pending |
| 4.2 | Monitoring | MEDIUM | 4h | Pending |
| 4.3 | Uptime monitoring | LOW | 1h | Pending |
| 5.1 | Load testing | HIGH | 2h | Pending |
| 5.2 | Security audit | HIGH | 2h | Pending |
| 5.3 | Pre-launch checklist | HIGH | 1h | Pending |

**Total Estimated Time: ~43-47 hours (2-3 weeks)**

---

## Approval Required

Before proceeding with implementation, confirm:

1. Start Phase 1 immediately? (Critical fixes)
2. SSL certificate available? (Need domain pointed and SSL cert)
3. Server access for cron/backup setup? (Hostinger panel access)
4. Any specific performance requirements? (e.g., target concurrent users)

Once approved, begin Phase 1 and provide progress updates as each task completes.
