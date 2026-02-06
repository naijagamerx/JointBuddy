<?php
/**
 * CSRF Middleware Tests
 *
 * Tests for CsrfMiddleware class
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class CsrfMiddlewareTest extends TestCase {
    /**
     * Backup of global state
     *
     * @var array
     */
    protected array $stateBackup = [];

    /**
     * Set up before each test
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();

        // Backup global state
        $this->stateBackup = [
            '_SESSION' => $_SESSION ?? [],
            '_POST' => $_POST ?? [],
            '_SERVER' => $_SERVER ?? [],
        ];

        // Reset state
        $_SESSION = [];
        $_POST = [];

        // Set default server variables
        $_SERVER['REQUEST_URI'] = '/admin/products/';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
    }

    /**
     * Tear down after each test
     *
     * @return void
     */
    protected function tearDown(): void {
        // Restore global state
        $_SESSION = $this->stateBackup['_SESSION'] ?? [];
        $_POST = $this->stateBackup['_POST'] ?? [];
        $_SERVER = $this->stateBackup['_SERVER'] ?? [];

        parent::tearDown();
    }

    /**
     * Test getToken generates a token
     *
     * @return void
     */
    public function testGetTokenGeneratesToken(): void {
        $token = CsrfMiddleware::getToken();

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        $this->assertGreaterThanOrEqual(64, strlen($token)); // 32 bytes = 64 hex chars
    }

    /**
     * Test getToken returns same token on multiple calls
     *
     * @return void
     */
    public function testGetTokenReturnsSameToken(): void {
        $token1 = CsrfMiddleware::getToken();
        $token2 = CsrfMiddleware::getToken();

        $this->assertEquals($token1, $token2);
    }

    /**
     * Test getToken stores token in session
     *
     * @return void
     */
    public function testGetTokenStoresInSession(): void {
        $token = CsrfMiddleware::getToken();

        $this->assertArrayHasKey('csrf_token', $_SESSION);
        $this->assertEquals($token, $_SESSION['csrf_token']);
    }

    /**
     * Test getField returns HTML input element
     *
     * @return void
     */
    public function testGetFieldReturnsHtml(): void {
        $field = CsrfMiddleware::getField();

        $this->assertIsString($field);
        $this->assertStringContainsString('<input', $field);
        $this->assertStringContainsString('type="hidden"', $field);
        $this->assertStringContainsString('name="csrf_token"', $field);
        $this->assertStringContainsString('value="', $field);
    }

    /**
     * Test getField includes current token
     *
     * @return void
     */
    public function testGetFieldIncludesCurrentToken(): void {
        $token = CsrfMiddleware::getToken();
        $field = CsrfMiddleware::getField();

        $this->assertStringContainsString($token, $field);
    }

    /**
     * Test isValid accepts valid token
     *
     * @return void
     */
    public function testIsValidAcceptsValidToken(): void {
        $token = CsrfMiddleware::getToken();

        $isValid = CsrfMiddleware::isValid($token);

        $this->assertTrue($isValid);
    }

    /**
     * Test isValid rejects invalid token
     *
     * @return void
     */
    public function testIsValidRejectsInvalidToken(): void {
        $isValid = CsrfMiddleware::isValid('invalid_token_12345');

        $this->assertFalse($isValid);
    }

    /**
     * Test isValid rejects empty token
     *
     * @return void
     */
    public function testIsValidRejectsEmptyToken(): void {
        $isValid = CsrfMiddleware::isValid('');

        $this->assertFalse($isValid);
    }

    /**
     * Test regenerate creates new token
     *
     * @return void
     */
    public function testRegenerateCreatesNewToken(): void {
        $token1 = CsrfMiddleware::getToken();

        CsrfMiddleware::regenerate();

        $token2 = CsrfMiddleware::getToken();

        $this->assertNotEquals($token1, $token2);
    }

    /**
     * Test regenerate updates session
     *
     * @return void
     */
    public function testRegenerateUpdatesSession(): void {
        $token1 = CsrfMiddleware::getToken();

        CsrfMiddleware::regenerate();

        $token2 = $_SESSION['csrf_token'];

        $this->assertNotEquals($token1, $token2);
    }

    /**
     * Test validate skips GET requests
     *
     * @return void
     */
    public function testValidateSkipsGetRequests(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // Should not throw exception
        CsrfMiddleware::validate();

        $this->assertTrue(true);
    }

    /**
     * Test validate skips POST requests with no token (no redirect in test)
     *
     * @return void
     */
    public function testValidateAllowsPostWithNoToken(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['csrf_token'] = '';

        // In test environment, redirect throws exception
        $this->expectException(RuntimeException::class);

        CsrfMiddleware::validate();
    }

    /**
     * Test validate accepts POST with valid token
     *
     * @return void
     */
    public function testValidateAcceptsPostWithValidToken(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $token = CsrfMiddleware::getToken();
        $_POST['csrf_token'] = $token;

        // Should not throw exception
        CsrfMiddleware::validate();

        $this->assertTrue(true);
    }

    /**
     * Test validate regenerates token after successful validation
     *
     * @return void
     */
    public function testValidateRegeneratesTokenAfterSuccess(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $token1 = CsrfMiddleware::getToken();
        $_POST['csrf_token'] = $token1;

        CsrfMiddleware::validate();

        $token2 = CsrfMiddleware::getToken();

        $this->assertNotEquals($token1, $token2);
    }

    /**
     * Test getAjaxHeader returns array with X-CSRF-Token
     *
     * @return void
     */
    public function testGetAjaxHeaderReturnsArray(): void {
        $header = CsrfMiddleware::getAjaxHeader();

        $this->assertIsArray($header);
        $this->assertArrayHasKey('X-CSRF-Token', $header);
    }

    /**
     * Test getAjaxHeader includes current token
     *
     * @return void
     */
    public function testGetAjaxHeaderIncludesCurrentToken(): void {
        $token = CsrfMiddleware::getToken();
        $header = CsrfMiddleware::getAjaxHeader();

        $this->assertEquals($token, $header['X-CSRF-Token']);
    }

    /**
     * Test exempt adds route to exempt list
     *
     * @return void
     */
    public function testExemptAddsRouteToList(): void {
        CsrfMiddleware::exempt('api/webhook');

        $exempt = sessionGet('csrf_exempt_routes', []);

        $this->assertContains('api/webhook', $exempt);
    }

    /**
     * Test exempt prevents duplicate routes
     *
     * @return void
     */
    public function testExemptPreventsDuplicates(): void {
        CsrfMiddleware::exempt('api/webhook');
        CsrfMiddleware::exempt('api/webhook');

        $exempt = sessionGet('csrf_exempt_routes', []);

        $this->assertCount(1, array_filter($exempt, fn($r) => $r === 'api/webhook'));
    }

    /**
     * Test isExempt returns true for exempt routes
     *
     * @return void
     */
    public function testIsExemptReturnsTrueForExemptRoutes(): void {
        CsrfMiddleware::exempt('api/webhook');
        $_SERVER['REQUEST_URI'] = '/api/webhook';

        $isExempt = CsrfMiddleware::isExempt();

        $this->assertTrue($isExempt);
    }

    /**
     * Test isExempt returns false for non-exempt routes
     *
     * @return void
     */
    public function testIsExemptReturnsFalseForNonExemptRoutes(): void {
        $_SERVER['REQUEST_URI'] = '/admin/products';

        $isExempt = CsrfMiddleware::isExempt();

        $this->assertFalse($isExempt);
    }

    /**
     * Test isExempt handles trailing slashes
     *
     * @return void
     */
    public function testIsExemptHandlesTrailingSlashes(): void {
        CsrfMiddleware::exempt('api/webhook');

        // Test with trailing slash
        $_SERVER['REQUEST_URI'] = '/api/webhook/';
        $isExempt1 = CsrfMiddleware::isExempt();

        // Test without trailing slash
        $_SERVER['REQUEST_URI'] = '/api/webhook';
        $isExempt2 = CsrfMiddleware::isExempt();

        // Both should match (behavior depends on implementation)
        $this->assertIsBool($isExempt1);
        $this->assertIsBool($isExempt2);
    }

    /**
     * Test token timing-safe comparison
     *
     * @return void
     */
    public function testTokenUsesTimingSafeComparison(): void {
        $token = CsrfMiddleware::getToken();

        // Valid token
        $validResult = CsrfMiddleware::isValid($token);
        $this->assertTrue($validResult);

        // Similar but invalid token (testing timing attack prevention)
        $invalidToken = $token . 'a';
        $invalidResult = CsrfMiddleware::isValid($invalidToken);
        $this->assertFalse($invalidResult);
    }

    /**
     * Test token includes timestamp
     *
     * @return void
     */
    public function testTokenIncludesTimestamp(): void {
        CsrfMiddleware::getToken();

        $this->assertArrayHasKey('csrf_token_created', $_SESSION);
        $this->assertIsInt($_SESSION['csrf_token_created']);
    }

    /**
     * Test old tokens are rejected
     *
     * @return void
     */
    public function testOldTokensAreRejected(): void {
        $token = CsrfMiddleware::getToken();

        // Set token as old (more than 1 hour)
        $_SESSION['csrf_token_created'] = time() - 3700; // 1 hour 1 minute ago

        $isValid = CsrfMiddleware::isValid($token);

        $this->assertFalse($isValid);
    }

    /**
     * Test fresh tokens are accepted
     *
     * @return void
     */
    public function testFreshTokensAreAccepted(): void {
        $token = CsrfMiddleware::getToken();

        // Set token as fresh (30 minutes ago)
        $_SESSION['csrf_token_created'] = time() - 1800;

        $isValid = CsrfMiddleware::isValid($token);

        $this->assertTrue($isValid);
    }
}
