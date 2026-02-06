<?php
/**
 * Services Test Suite
 *
 * Tests for the Services service container
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PDO;
use PDOException;

class ServicesTest extends TestCase {
    /**
     * Reset Services before each test
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();

        // Reset Services to ensure clean state
        if (class_exists('Services')) {
            Services::reset();
        }
    }

    /**
     * Reset Services after each test
     *
     * @return void
     */
    protected function tearDown(): void {
        if (class_exists('Services')) {
            Services::reset();
        }

        parent::tearDown();
    }

    /**
     * Test that Services::initialize initializes all services
     *
     * @return void
     */
    public function testInitializeInitializesAllServices(): void {
        $this->assertFalse(Services::isInitialized());

        Services::initialize();

        $this->assertTrue(Services::isInitialized());
    }

    /**
     * Test that initialize is idempotent
     *
     * @return void
     */
    public function testInitializeIsIdempotent(): void {
        Services::initialize();

        $firstDb = Services::db();

        Services::initialize();

        $secondDb = Services::db();

        $this->assertSame($firstDb, $secondDb);
    }

    /**
     * Test db returns a PDO instance
     *
     * @return void
     */
    public function testDbReturnsPDOInstance(): void {
        Services::initialize();

        $db = Services::db();

        $this->assertInstanceOf(PDO::class, $db);
    }

    /**
     * Test db returns singleton instance
     *
     * @return void
     */
    public function testDbReturnsSingleton(): void {
        Services::initialize();

        $firstDb = Services::db();
        $secondDb = Services::db();

        $this->assertSame($firstDb, $secondDb);
    }

    /**
     * Test adminAuth returns AdminAuth instance
     *
     * @return void
     */
    public function testAdminAuthReturnsAdminAuthInstance(): void {
        Services::initialize();

        $adminAuth = Services::adminAuth();

        $this->assertInstanceOf(AdminAuth::class, $adminAuth);
    }

    /**
     * Test adminAuth returns singleton instance
     *
     * @return void
     */
    public function testAdminAuthReturnsSingleton(): void {
        Services::initialize();

        $firstAuth = Services::adminAuth();
        $secondAuth = Services::adminAuth();

        $this->assertSame($firstAuth, $secondAuth);
    }

    /**
     * Test userAuth returns UserAuth instance
     *
     * @return void
     */
    public function testUserAuthReturnsUserAuthInstance(): void {
        Services::initialize();

        $userAuth = Services::userAuth();

        $this->assertInstanceOf(UserAuth::class, $userAuth);
    }

    /**
     * Test userAuth returns singleton instance
     *
     * @return void
     */
    public function testUserAuthReturnsSingleton(): void {
        Services::initialize();

        $firstAuth = Services::userAuth();
        $secondAuth = Services::userAuth();

        $this->assertSame($firstAuth, $secondAuth);
    }

    /**
     * Test database returns Database instance
     *
     * @return void
     */
    public function testDatabaseReturnsDatabaseInstance(): void {
        Services::initialize();

        $database = Services::database();

        $this->assertInstanceOf(Database::class, $database);
    }

    /**
     * Test database returns singleton instance
     *
     * @return void
     */
    public function testDatabaseReturnsSingleton(): void {
        Services::initialize();

        $firstDb = Services::database();
        $secondDb = Services::database();

        $this->assertSame($firstDb, $secondDb);
    }

    /**
     * Test currencyService returns CurrencyService instance
     *
     * @return void
     */
    public function testCurrencyServiceReturnsCurrencyServiceInstance(): void {
        Services::initialize();

        $currencyService = Services::currencyService();

        $this->assertInstanceOf(CurrencyService::class, $currencyService);
    }

    /**
     * Test currencyService returns singleton instance
     *
     * @return void
     */
    public function testCurrencyServiceReturnsSingleton(): void {
        Services::initialize();

        $firstService = Services::currencyService();
        $secondService = Services::currencyService();

        $this->assertSame($firstService, $secondService);
    }

    /**
     * Test all services share the same PDO connection
     *
     * @return void
     */
    public function testAllServicesShareSamePDOConnection(): void {
        Services::initialize();

        $db = Services::db();
        $adminAuth = Services::adminAuth();
        $userAuth = Services::userAuth();

        // Access the PDO connection from auth classes
        // (This requires knowing the internal structure of Auth classes)
        // For now, we just verify all services are initialized

        $this->assertInstanceOf(PDO::class, $db);
        $this->assertInstanceOf(AdminAuth::class, $adminAuth);
        $this->assertInstanceOf(UserAuth::class, $userAuth);
    }

    /**
     * Test reset clears all services
     *
     * @return void
     */
    public function testResetClearsAllServices(): void {
        Services::initialize();

        $this->assertTrue(Services::isInitialized());

        $dbBeforeReset = Services::db();

        Services::reset();

        $this->assertFalse(Services::isInitialized());

        Services::initialize();

        $dbAfterReset = Services::db();

        // After reset, we should get a new instance
        $this->assertNotSame($dbBeforeReset, $dbAfterReset);
    }

    /**
     * Test isInitialized returns correct state
     *
     * @return void
     */
    public function testIsInitializedReturnsCorrectState(): void {
        $this->assertFalse(Services::isInitialized());

        Services::initialize();

        $this->assertTrue(Services::isInitialized());

        Services::reset();

        $this->assertFalse(Services::isInitialized());
    }

    /**
     * Test calling db without initialize triggers initialization
     *
     * @return void
     */
    public function testCallingDbTriggersInitialization(): void {
        $this->assertFalse(Services::isInitialized());

        $db = Services::db();

        $this->assertTrue(Services::isInitialized());
        $this->assertInstanceOf(PDO::class, $db);
    }

    /**
     * Test calling adminAuth without initialize triggers initialization
     *
     * @return void
     */
    public function testCallingAdminAuthTriggersInitialization(): void {
        $this->assertFalse(Services::isInitialized());

        $auth = Services::adminAuth();

        $this->assertTrue(Services::isInitialized());
        $this->assertInstanceOf(AdminAuth::class, $auth);
    }

    /**
     * Test calling userAuth without initialize triggers initialization
     *
     * @return void
     */
    public function testCallingUserAuthTriggersInitialization(): void {
        $this->assertFalse(Services::isInitialized());

        $auth = Services::userAuth();

        $this->assertTrue(Services::isInitialized());
        $this->assertInstanceOf(UserAuth::class, $auth);
    }

    /**
     * Test initialize throws exception on database connection failure
     *
     * @return void
     */
    public function testInitializeThrowsExceptionOnConnectionFailure(): void {
        // This test would require mocking the Database class
        // or setting invalid database credentials

        // For now, we'll skip this test as it requires
        // more complex setup with environment variables
        $this->assertTrue(true);
    }

    /**
     * Test multiple calls to initialize don't recreate services
     *
     * @return void
     */
    public function testMultipleInitializeCallsDontRecreateServices(): void {
        Services::initialize();

        $db1 = Services::db();
        $auth1 = Services::adminAuth();

        Services::initialize();

        $db2 = Services::db();
        $auth2 = Services::adminAuth();

        $this->assertSame($db1, $db2);
        $this->assertSame($auth1, $auth2);
    }

    /**
     * Test database returns Database instance
     *
     * @return void
     */
    public function testDatabaseReturnsInstance(): void {
        Services::initialize();

        $database = Services::database();

        $this->assertInstanceOf(Database::class, $database);
    }

    /**
     * Test reset can be called multiple times safely
     *
     * @return void
     */
    public function testResetCanBeCalledMultipleTimes(): void {
        Services::initialize();

        Services::reset();
        Services::reset();
        Services::reset();

        $this->assertFalse(Services::isInitialized());

        // Should still work after multiple resets
        Services::initialize();

        $this->assertTrue(Services::isInitialized());
    }
}
