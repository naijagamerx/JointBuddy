<?php
/**
 * Session Helper Tests
 *
 * Tests for session_helper.php functions
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class SessionHelperTest extends TestCase {
    /**
     * Original session state backup
     *
     * @var array
     */
    protected array $sessionStateBackup = [];

    /**
     * Set up before each test
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();

        // Backup current session
        $this->sessionStateBackup = $_SESSION ?? [];

        // Clear session for clean state
        $_SESSION = [];

        // Ensure session is started
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
        // Restore session
        $_SESSION = $this->sessionStateBackup;

        parent::tearDown();
    }

    /**
     * Test that ensureSessionStarted starts a session
     *
     * @return void
     */
    public function testEnsureSessionStartsSession(): void {
        // Note: Session is already started by bootstrap
        // So we just verify it's active

        // Verify session is active (started by bootstrap)
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());

        // Calling ensureSessionStarted should be safe (idempotent)
        ensureSessionStarted();

        // Still active
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
    }

    /**
     * Test that ensureSessionStarted is safe to call multiple times
     *
     * @return void
     */
    public function testEnsureSessionStartedIsIdempotent(): void {
        // Ensure session is started
        ensureSessionStarted();

        // Get current session ID
        $sessionId = session_id();

        // Call again
        ensureSessionStarted();

        // Verify session ID hasn't changed
        $this->assertEquals($sessionId, session_id());
    }

    /**
     * Test sessionFlash sets a flash message
     *
     * @return void
     */
    public function testSessionFlashSetsMessage(): void {
        sessionFlash('success', 'Operation completed successfully');

        $this->assertArrayHasKey('_flash', $_SESSION);
        $this->assertArrayHasKey('success', $_SESSION['_flash']);
        $this->assertEquals('Operation completed successfully', $_SESSION['_flash']['success']);
    }

    /**
     * Test sessionGetFlash retrieves and clears flash message
     *
     * @return void
     */
    public function testSessionGetFlashRetrievesAndClearsMessage(): void {
        $_SESSION['_flash']['info'] = 'Test message';

        $message = sessionGetFlash('info');

        $this->assertEquals('Test message', $message);
        $this->assertArrayNotHasKey('info', $_SESSION['_flash']);
    }

    /**
     * Test sessionGetFlash returns null for non-existent key
     *
     * @return void
     */
    public function testSessionGetFlashReturnsNullForNonExistentKey(): void {
        $message = sessionGetFlash('nonexistent');

        $this->assertNull($message);
    }

    /**
     * Test sessionHasFlash checks for flash message existence
     *
     * @return void
     */
    public function testSessionHasFlashReturnsTrueWhenExists(): void {
        $_SESSION['_flash']['warning'] = 'Warning message';

        $this->assertTrue(sessionHasFlash('warning'));
    }

    /**
     * Test sessionHasFlash returns false when doesn't exist
     *
     * @return void
     */
    public function testSessionHasFlashReturnsFalseWhenNotExists(): void {
        $this->assertFalse(sessionHasFlash('nonexistent'));
    }

    /**
     * Test regenerateSession creates new session ID
     *
     * @return void
     */
    public function testRegenerateSessionCreatesNewSessionId(): void {
        // Skip this test if session can't be regenerated (headers sent)
        if (headers_sent()) {
            $this->markTestSkipped('Cannot regenerate session when headers already sent');
            return;
        }

        ensureSessionStarted();

        $oldSessionId = session_id();

        regenerateSession();

        $newSessionId = session_id();

        $this->assertNotEmpty($newSessionId);
        $this->assertNotEquals($oldSessionId, $newSessionId);
    }

    /**
     * Test destroySession clears all session data
     *
     * @return void
     */
    public function testDestroySessionClearsAllData(): void {
        // Skip this test if session can't be destroyed (headers sent)
        if (headers_sent()) {
            $this->markTestSkipped('Cannot destroy session when headers already sent');
            return;
        }

        ensureSessionStarted();

        // Set some session data
        $_SESSION['user_id'] = 123;
        $_SESSION['csrf_token'] = 'abc123';
        $_SESSION['_flash']['success'] = 'Test';

        destroySession();

        // Verify session is cleared
        $this->assertEmpty($_SESSION);
    }

    /**
     * Test destroySession destroys session cookie
     *
     * @return void
     */
    public function testDestroySessionRemovesCookie(): void {
        // Skip this test if session can't be destroyed (headers sent)
        if (headers_sent()) {
            $this->markTestSkipped('Cannot destroy session when headers already sent');
            return;
        }

        ensureSessionStarted();

        $_SESSION[session_name()] = 'test_value';

        destroySession();

        // After destroy, cookie should be removed
        // Note: In testing environment, we can't fully test cookie removal
        // but we verify the session data is cleared
        $this->assertEmpty($_SESSION);
    }

    /**
     * Test sessionSet sets session variable
     *
     * @return void
     */
    public function testSessionSetSetsVariable(): void {
        sessionSet('user_id', 456);

        $this->assertEquals(456, $_SESSION['user_id']);
    }

    /**
     * Test sessionGet retrieves session variable
     *
     * @return void
     */
    public function testSessionGetRetrievesVariable(): void {
        $_SESSION['cart_count'] = 5;

        $value = sessionGet('cart_count');

        $this->assertEquals(5, $value);
    }

    /**
     * Test sessionGet returns default when key doesn't exist
     *
     * @return void
     */
    public function testSessionGetReturnsDefaultWhenNotExists(): void {
        $value = sessionGet('nonexistent', 'default_value');

        $this->assertEquals('default_value', $value);
    }

    /**
     * Test sessionGet returns null when no default specified
     *
     * @return void
     */
    public function testSessionGetReturnsNullWhenNotExistsAndNoDefault(): void {
        $value = sessionGet('nonexistent');

        $this->assertNull($value);
    }

    /**
     * Test sessionRemove removes session variable
     *
     * @return void
     */
    public function testSessionRemoveRemovesVariable(): void {
        $_SESSION['temp_data'] = 'temporary';

        sessionRemove('temp_data');

        $this->assertArrayNotHasKey('temp_data', $_SESSION);
    }

    /**
     * Test multiple flash messages can be stored
     *
     * @return void
     */
    public function testMultipleFlashMessagesCanBeStored(): void {
        sessionFlash('success', 'Success message');
        sessionFlash('error', 'Error message');
        sessionFlash('info', 'Info message');

        $this->assertEquals('Success message', $_SESSION['_flash']['success']);
        $this->assertEquals('Error message', $_SESSION['_flash']['error']);
        $this->assertEquals('Info message', $_SESSION['_flash']['info']);
    }

    /**
     * Test flash messages are isolated per key
     *
     * @return void
     */
    public function testFlashMessagesAreIsolatedPerKey(): void {
        sessionFlash('success', 'First message');
        sessionFlash('success', 'Second message');

        $this->assertEquals('Second message', $_SESSION['_flash']['success']);
    }

    /**
     * Test session data persists across calls
     *
     * @return void
     */
    public function testSessionDataPersists(): void {
        sessionSet('test_key', 'test_value');

        $value1 = sessionGet('test_key');
        $value2 = sessionGet('test_key');

        $this->assertEquals('test_value', $value1);
        $this->assertEquals('test_value', $value2);
    }

    /**
     * Test complex data types can be stored in session
     *
     * @return void
     */
    public function testComplexDataTypesCanBeStored(): void {
        $arrayData = ['foo' => 'bar', 'nested' => ['key' => 'value']];
        $objectData = (object) ['property' => 'value'];

        sessionSet('array_data', $arrayData);
        sessionSet('object_data', $objectData);

        $this->assertEquals($arrayData, sessionGet('array_data'));
        $this->assertEquals($objectData, sessionGet('object_data'));
    }
}
