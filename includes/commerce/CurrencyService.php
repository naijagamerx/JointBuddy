<?php

class CurrencyService
{
    private $db;
    private $sessionKey = 'user_currency';

    public function __construct($db)
    {
        $this->db = $db;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function getCurrentCurrency()
    {
        if (isset($_SESSION[$this->sessionKey])) {
            return $_SESSION[$this->sessionKey];
        }
        return $this->getDefaultCurrencyCode();
    }

    public function setCurrency($code)
    {
        // Verify if currency exists and is active
        $stmt = $this->db->prepare("SELECT code FROM currencies WHERE code = ? AND is_active = 1");
        $stmt->execute([$code]);
        if ($stmt->fetch()) {
            $_SESSION[$this->sessionKey] = $code;
            return true;
        }
        return false;
    }

    public function getDefaultCurrencyCode()
    {
        $stmt = $this->db->query("SELECT code FROM currencies WHERE is_default = 1 LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['code'] : 'ZAR';
    }

    public function getAllCurrencies()
    {
        $stmt = $this->db->query("SELECT c.*, (SELECT er2.rate FROM exchange_rates er2 WHERE er2.currency_code = c.code ORDER BY er2.updated_at DESC LIMIT 1) as rate FROM currencies c ORDER BY c.is_default DESC, c.code ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function convertPrice($amountInDefault, $targetCurrencyCode = null)
    {
        if ($targetCurrencyCode === null) {
            $targetCurrencyCode = $this->getCurrentCurrency();
        }

        // Get default currency
        $defaultCode = $this->getDefaultCurrencyCode();

        // If target is default, return as is
        if ($targetCurrencyCode === $defaultCode) {
            return $amountInDefault;
        }

        // Get rate
        $stmt = $this->db->prepare("SELECT rate FROM exchange_rates WHERE currency_code = ?");
        $stmt->execute([$targetCurrencyCode]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return $amountInDefault * $row['rate'];
        }

        return $amountInDefault; // Fallback
    }

    public function formatPrice($amount, $currencyCode = null)
    {
        if ($currencyCode === null) {
            $currencyCode = $this->getCurrentCurrency();
        }

        $converted = $this->convertPrice($amount, $currencyCode);
        
        $stmt = $this->db->prepare("SELECT symbol FROM currencies WHERE code = ?");
        $stmt->execute([$currencyCode]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $symbol = $row ? $row['symbol'] : $currencyCode . ' ';

        return $symbol . number_format($converted, 2);
    }
    
    public function updateRate($code, $rate)
    {
        $stmt = $this->db->prepare("INSERT INTO exchange_rates (currency_code, rate, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE rate = ?, updated_at = NOW()");
        return $stmt->execute([$code, $rate, $rate]);
    }
}
