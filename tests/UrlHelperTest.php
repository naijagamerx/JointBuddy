<?php
/**
 * URL Helper Tests
 *
 * Tests for url_helper.php functions
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class UrlHelperTest extends TestCase {
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
            '_SERVER' => $_SERVER ?? [],
            'GLOBALS' => $GLOBALS ?? [],
        ];

        // Set default server variables
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['DOCUMENT_ROOT'] = BASE_PATH;
        $_SERVER['SCRIPT_NAME'] = '/CannaBuddy.shop/index.php';

        // Clear cached URL values
        if (isset($GLOBALS['cannabuddy_base_url'])) {
            $GLOBALS['cannabuddy_base_url'] = null;
        }
        if (isset($GLOBALS['cannabuddy_base_path'])) {
            $GLOBALS['cannabuddy_base_path'] = null;
        }
    }

    /**
     * Tear down after each test
     *
     * @return void
     */
    protected function tearDown(): void {
        // Restore global state
        $_SERVER = $this->stateBackup['_SERVER'] ?? [];

        // Clear cached URL values
        if (isset($GLOBALS['cannabuddy_base_url'])) {
            $GLOBALS['cannabuddy_base_url'] = null;
        }
        if (isset($GLOBALS['cannabuddy_base_path'])) {
            $GLOBALS['cannabuddy_base_path'] = null;
        }

        parent::tearDown();
    }

    /**
     * Test url generates full URL with HTTP
     *
     * @return void
     */
    public function testUrlGeneratesFullUrlWithHttp(): void {
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['DOCUMENT_ROOT'] = BASE_PATH;
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $result = url('admin/login/');

        $this->assertEquals('http://localhost/admin/login/', $result);
    }

    /**
     * Test url generates full URL with HTTPS
     *
     * @return void
     */
    public function testUrlGeneratesFullUrlWithHttps(): void {
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['HTTP_HOST'] = 'localhost';

        $result = url('admin/login/');

        $this->assertEquals('https://localhost/admin/login/', $result);
    }

    /**
     * Test url handles subdirectory deployment
     *
     * @return void
     */
    public function testUrlHandlesSubdirectoryDeployment(): void {
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['DOCUMENT_ROOT'] = dirname(BASE_PATH);
        $_SERVER['SCRIPT_NAME'] = '/CannaBuddy.shop/index.php';

        $result = url('admin/login/');

        $this->assertEquals('http://localhost/CannaBuddy.shop/admin/login/', $result);
    }

    /**
     * Test url trims leading slashes from path
     *
     * @return void
     */
    public function testUrlTrimsLeadingSlashesFromPath(): void {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['DOCUMENT_ROOT'] = BASE_PATH;
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $result1 = url('admin/login/');
        $result2 = url('/admin/login/');
        $result3 = url('//admin/login/');

        $this->assertEquals('http://localhost/admin/login/', $result1);
        $this->assertEquals('http://localhost/admin/login/', $result2);
        $this->assertEquals('http://localhost/admin/login/', $result3);
    }

    /**
     * Test url with empty path returns base URL
     *
     * @return void
     */
    public function testUrlWithEmptyPathReturnsBaseUrl(): void {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['DOCUMENT_ROOT'] = BASE_PATH;
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $result = url('');

        $this->assertEquals('http://localhost/', $result);
    }

    /**
     * Test url caches base URL
     *
     * @return void
     */
    public function testUrlCachesBaseUrl(): void {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['DOCUMENT_ROOT'] = BASE_PATH;
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $result1 = url('test/');
        $result2 = url('test/');

        $this->assertEquals($result1, $result2);
    }

    /**
     * Test rurl generates relative URL
     *
     * @return void
     */
    public function testRurlGeneratesRelativeUrl(): void {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['DOCUMENT_ROOT'] = BASE_PATH;
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $result = rurl('admin/login/');

        $this->assertEquals('/admin/login/', $result);
    }

    /**
     * Test rurl includes base path for subdirectory
     *
     * @return void
     */
    public function testRurlIncludesBasePathForSubdirectory(): void {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['DOCUMENT_ROOT'] = dirname(BASE_PATH);
        $_SERVER['SCRIPT_NAME'] = '/CannaBuddy.shop/index.php';

        $result = rurl('admin/login/');

        $this->assertEquals('/CannaBuddy.shop/admin/login/', $result);
    }

    /**
     * Test adminUrl generates admin URL
     *
     * @return void
     */
    public function testAdminUrlGeneratesAdminUrl(): void {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['DOCUMENT_ROOT'] = BASE_PATH;
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $result = adminUrl('dashboard/');

        $this->assertEquals('http://localhost/admin/dashboard/', $result);
    }

    /**
     * Test adminUrl with subdirectory deployment
     *
     * @return void
     */
    public function testAdminUrlWithSubdirectoryDeployment(): void {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['DOCUMENT_ROOT'] = dirname(BASE_PATH);
        $_SERVER['SCRIPT_NAME'] = '/CannaBuddy.shop/index.php';

        $result = adminUrl('dashboard/');

        $this->assertEquals('http://localhost/CannaBuddy.shop/admin/dashboard/', $result);
    }

    /**
     * Test userUrl generates user URL
     *
     * @return void
     */
    public function testUserUrlGeneratesUserUrl(): void {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['DOCUMENT_ROOT'] = BASE_PATH;
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $result = userUrl('dashboard/');

        $this->assertEquals('http://localhost/user/dashboard/', $result);
    }

    /**
     * Test shopUrl generates shop URL
     *
     * @return void
     */
    public function testShopUrlGeneratesShopUrl(): void {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['DOCUMENT_ROOT'] = BASE_PATH;
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $result = shopUrl('products/');

        $this->assertEquals('http://localhost/shop/products/', $result);
    }

    /**
     * Test productUrl generates product URL
     *
     * @return void
     */
    public function testProductUrlGeneratesProductUrl(): void {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['DOCUMENT_ROOT'] = BASE_PATH;
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $result = productUrl('test-product');

        $this->assertEquals('http://localhost/product/test-product', $result);
    }

    /**
     * Test assetUrl generates asset URL
     *
     * @return void
     */
    public function testAssetUrlGeneratesAssetUrl(): void {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['DOCUMENT_ROOT'] = BASE_PATH;
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $result = assetUrl('css/style.css');

        $this->assertEquals('http://localhost/assets/css/style.css', $result);
    }

    /**
     * Test assetPath generates relative asset path
     *
     * @return void
     */
    public function testAssetPathGeneratesRelativeAssetPath(): void {
        $result = assetPath('images/logo.png');

        $this->assertEquals('/assets/images/logo.png', $result);
    }

    /**
     * Test safeHtml escapes HTML entities
     *
     * @return void
     */
    public function testSafeHtmlEscapesHtmlEntities(): void {
        $result = safe_html('<script>alert("xss")</script>');

        $this->assertStringContainsString('&lt;', $result);
        $this->assertStringNotContainsString('<script>', $result);
    }

    /**
     * Test safeHtml handles null values
     *
     * @return void
     */
    public function testSafeHtmlHandlesNullValues(): void {
        $result = safe_html(null);

        $this->assertEquals('', $result);
    }

    /**
     * Test safeHtml handles empty strings
     *
     * @return void
     */
    public function testSafeHtmlHandlesEmptyStrings(): void {
        $result = safe_html('');

        $this->assertEquals('', $result);
    }

    /**
     * Test validateRedirect allows valid relative paths
     *
     * @return void
     */
    public function testValidateRedirectAllowsValidRelativePaths(): void {
        $result = validateRedirect('/user/dashboard');

        $this->assertTrue($result);
    }

    /**
     * Test validateRedirect allows empty redirect
     *
     * @return void
     */
    public function testValidateRedirectAllowsEmptyRedirect(): void {
        $result = validateRedirect('');

        $this->assertTrue($result);
    }

    /**
     * Test validateRedirect blocks protocol-relative URLs
     *
     * @return void
     */
    public function testValidateRedirectBlocksProtocolRelativeUrls(): void {
        $result = validateRedirect('//evil.com');

        $this->assertFalse($result);
    }

    /**
     * Test validateRedirect blocks external URLs
     *
     * @return void
     */
    public function testValidateRedirectBlocksExternalUrls(): void {
        $result = validateRedirect('https://evil.com');

        $this->assertFalse($result);
    }

    /**
     * Test validateRedirect blocks paths not starting with slash
     *
     * @return void
     */
    public function testValidateRedirectBlocksPathsWithoutSlash(): void {
        $result = validateRedirect('evil.com/path');

        $this->assertFalse($result);
    }

    /**
     * Test validateRedirect blocks paths not in whitelist
     *
     * @return void
     */
    public function testValidateRedirectBlocksNonWhitelistedPaths(): void {
        $result = validateRedirect('/external/path');

        $this->assertFalse($result);
    }

    /**
     * Test csrfToken generates token
     *
     * @return void
     */
    public function testCsrfTokenGeneratesToken(): void {
        @session_start();

        $token = csrf_token();

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        $this->assertGreaterThanOrEqual(64, strlen($token));
    }

    /**
     * Test csrfToken returns same token on multiple calls
     *
     * @return void
     */
    public function testCsrfTokenReturnsSameToken(): void {
        @session_start();

        $token1 = csrf_token();
        $token2 = csrf_token();

        $this->assertEquals($token1, $token2);
    }

    /**
     * Test csrfToken stores token in session
     *
     * @return void
     */
    public function testCsrfTokenStoresInSession(): void {
        @session_start();

        $token = csrf_token();

        $this->assertArrayHasKey('csrf_token', $_SESSION);
        $this->assertEquals($token, $_SESSION['csrf_token']);
    }

    /**
     * Test csrfToken stores creation time
     *
     * @return void
     */
    public function testCsrfTokenStoresCreationTime(): void {
        @session_start();

        csrf_token();

        $this->assertArrayHasKey('csrf_token_created', $_SESSION);
        $this->assertIsInt($_SESSION['csrf_token_created']);
    }

    /**
     * Test csrfField generates HTML input
     *
     * @return void
     */
    public function testCsrfFieldGeneratesHtmlInput(): void {
        @session_start();

        $field = csrf_field();

        $this->assertIsString($field);
        $this->assertStringContainsString('<input', $field);
        $this->assertStringContainsString('type="hidden"', $field);
        $this->assertStringContainsString('name="csrf_token"', $field);
        $this->assertStringContainsString('value="', $field);
    }

    /**
     * Test verifyCsrfToken accepts valid token
     *
     * @return void
     */
    public function testVerifyCsrfTokenAcceptsValidToken(): void {
        @session_start();

        $token = csrf_token();

        $result = verifyCsrfToken($token);

        $this->assertTrue($result);
    }

    /**
     * Test verifyCsrfToken rejects invalid token
     *
     * @return void
     */
    public function testVerifyCsrfTokenRejectsInvalidToken(): void {
        @session_start();
        csrf_token();

        $result = verifyCsrfToken('invalid_token');

        $this->assertFalse($result);
    }

    /**
     * Test verifyCsrfToken uses POST token by default
     *
     * @return void
     */
    public function testVerifyCsrfTokenUsesPostTokenByDefault(): void {
        @session_start();

        $token = csrf_token();
        $_POST['csrf_token'] = $token;

        $result = verifyCsrfToken();

        $this->assertTrue($result);
    }

    /**
     * Test verifyCsrfToken rejects expired tokens
     *
     * @return void
     */
    public function testVerifyCsrfTokenRejectsExpiredTokens(): void {
        @session_start();

        $token = csrf_token();
        $_SESSION['csrf_token_created'] = time() - 3700; // Over 1 hour ago

        $result = verifyCsrfToken($token);

        $this->assertFalse($result);
    }

    /**
     * Test verifyCsrfToken clears expired tokens
     *
     * @return void
     */
    public function testVerifyCsrfTokenClearsExpiredTokens(): void {
        @session_start();

        csrf_token();
        $_SESSION['csrf_token_created'] = time() - 3700;

        verifyCsrfToken($_SESSION['csrf_token']);

        $this->assertArrayNotHasKey('csrf_token', $_SESSION);
        $this->assertArrayNotHasKey('csrf_token_created', $_SESSION);
    }

    /**
     * Test csrfRegenerate creates new token
     *
     * @return void
     */
    public function testCsrfRegenerateCreatesNewToken(): void {
        @session_start();

        $token1 = csrf_token();

        csrf_regenerate();

        $token2 = csrf_token();

        $this->assertNotEquals($token1, $token2);
    }

    /**
     * Test csrfRegenerate updates creation time
     *
     * @return void
     */
    public function testCsrfRegenerateUpdatesCreationTime(): void {
        @session_start();

        csrf_token();
        $time1 = $_SESSION['csrf_token_created'];

        sleep(1); // Ensure time difference

        csrf_regenerate();
        $time2 = $_SESSION['csrf_token_created'];

        $this->assertGreaterThan($time1, $time2);
    }

    /**
     * Test getBaseUrl handles custom port
     *
     * @return void
     */
    public function testGetBaseUrlHandlesCustomPort(): void {
        $_SERVER['HTTP_HOST'] = 'localhost:8080';
        $_SERVER['DOCUMENT_ROOT'] = BASE_PATH;
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $result = url('test/');

        $this->assertEquals('http://localhost:8080/test/', $result);
    }

    /**
     * Test url with production domain
     *
     * @return void
     */
    public function testUrlWithProductionDomain(): void {
        $_SERVER['HTTP_HOST'] = 'cannakingdom.ky';
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['DOCUMENT_ROOT'] = BASE_PATH;
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $result = url('admin/');

        $this->assertEquals('https://cannakingdom.ky/admin/', $result);
    }
}
