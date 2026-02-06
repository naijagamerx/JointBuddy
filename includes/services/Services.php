<?php
/**
 * Service Container - Single source for application services
 *
 * Implements singleton pattern for database and auth instances
 * Prevents duplicate connections and ensures consistent state
 *
 * @package CannaBuddy
 */

class Services {
    private static ?PDO $db = null;
    private static ?AdminAuth $adminAuth = null;
    private static ?UserAuth $userAuth = null;
    private static ?Database $database = null;
    private static ?CurrencyService $currencyService = null;
    private static bool $initialized = false;

    /**
     * Initialize all services
     * Should be called once in bootstrap
     *
     * @return void
     * @throws Exception If initialization fails
     */
    public static function initialize(): void {
        if (self::$initialized) {
            return;
        }

        try {
            self::$database = new Database();
            self::$db = self::$database->getConnection();
            self::$adminAuth = new AdminAuth(self::$db);
            self::$userAuth = new UserAuth(self::$db);
            self::$currencyService = new CurrencyService(self::$db);
            self::$initialized = true;
        } catch (Exception $e) {
            error_log("Services initialization failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get database connection (PDO instance)
     *
     * @return PDO Database connection
     */
    public static function db(): PDO {
        self::initialize();
        return self::$db;
    }

    /**
     * Get admin authentication instance
     *
     * @return AdminAuth Admin authentication handler
     */
    public static function adminAuth(): AdminAuth {
        self::initialize();
        return self::$adminAuth;
    }

    /**
     * Get user authentication instance
     *
     * @return UserAuth User authentication handler
     */
    public static function userAuth(): UserAuth {
        self::initialize();
        return self::$userAuth;
    }

    /**
     * Get database instance
     *
     * @return Database Database wrapper class
     */
    public static function database(): Database {
        self::initialize();
        return self::$database;
    }

    /**
     * Get currency service instance
     *
     * @return CurrencyService Currency management service
     */
    public static function currencyService(): CurrencyService {
        self::initialize();
        return self::$currencyService;
    }

    /**
     * Reset all services (for testing only)
     *
     * @return void
     */
    public static function reset(): void {
        self::$db = null;
        self::$adminAuth = null;
        self::$userAuth = null;
        self::$database = null;
        self::$currencyService = null;
        self::$initialized = false;
    }

    /**
     * Check if services are initialized
     *
     * @return bool True if services have been initialized
     */
    public static function isInitialized(): bool {
        return self::$initialized;
    }
}
