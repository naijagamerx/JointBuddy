<?php
/**
 * Base Test Case
 *
 * Provides common setup and teardown for all test cases
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

abstract class BaseTestCase extends TestCase {
    /**
     * Original global state backup
     *
     * @var array
     */
    protected array $globalStateBackup = [];

    /**
     * Set up before each test
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();

        // Backup global state
        $this->globalStateBackup = [
            '_GET' => $_GET ?? [],
            '_POST' => $_POST ?? [],
            '_SESSION' => $_SESSION ?? [],
            '_COOKIE' => $_COOKIE ?? [],
            '_SERVER' => $_SERVER ?? [],
            '_FILES' => $_FILES ?? [],
        ];

        // Reset global state
        $this->resetGlobalState();

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
        // Restore global state
        $_GET = $this->globalStateBackup['_GET'] ?? [];
        $_POST = $this->globalStateBackup['_POST'] ?? [];
        $_SESSION = $this->globalStateBackup['_SESSION'] ?? [];
        $_COOKIE = $this->globalStateBackup['_COOKIE'] ?? [];
        $_SERVER = $this->globalStateBackup['_SERVER'] ?? [];
        $_FILES = $this->globalStateBackup['_FILES'] ?? [];

        // Clear Services singleton
        if (class_exists('Services')) {
            Services::reset();
        }

        parent::tearDown();
    }

    /**
     * Reset global state to clean defaults
     *
     * @return void
     */
    protected function resetGlobalState(): void {
        $_GET = [];
        $_POST = [];
        $_SESSION = [];
        $_COOKIE = [];
        $_FILES = [];
        $_REQUEST = [];

        // Reset server variables to defaults
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['DOCUMENT_ROOT'] = BASE_PATH;
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        // Clear cached URL helper values
        if (isset($GLOBALS['cannabuddy_base_url'])) {
            $GLOBALS['cannabuddy_base_url'] = null;
        }
        if (isset($GLOBALS['cannabuddy_base_path'])) {
            $GLOBALS['cannabuddy_base_path'] = null;
        }
    }

    /**
     * Set a mock server variable
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected function setServerVar(string $key, $value): void {
        $_SERVER[$key] = $value;
    }

    /**
     * Set a mock POST variable
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected function setPostVar(string $key, $value): void {
        $_POST[$key] = $value;
    }

    /**
     * Set a mock GET variable
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected function setGetVar(string $key, $value): void {
        $_GET[$key] = $value;
    }

    /**
     * Set a mock session variable
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected function setSessionVar(string $key, $value): void {
        $_SESSION[$key] = $value;
    }

    /**
     * Create a mock PDO instance
     *
     * @param array|null $fetchResult
     * @return PDO&\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createMockPDO(?array $fetchResult = null): PDO {
        $pdo = $this->createMock(PDO::class);

        $pdo->method('prepare')->willReturnCallback(function($query) use ($fetchResult) {
            $statement = $this->createMock(\PDOStatement::class);
            $statement->method('execute')->willReturn(true);

            if ($fetchResult !== null) {
                $statement->method('fetch')->willReturn($fetchResult);
                $statement->method('fetchAll')->willReturn([$fetchResult]);
            } else {
                $statement->method('fetch')->willReturn(false);
                $statement->method('fetchAll')->willReturn([]);
            }

            $statement->method('rowCount')->willReturn($fetchResult ? 1 : 0);

            return $statement;
        });

        $pdo->method('lastInsertId')->willReturn('1');
        $pdo->method('exec')->willReturn(1);
        $pdo->method('query')->willReturnCallback(function($query) use ($fetchResult) {
            $statement = $this->createMock(\PDOStatement::class);
            $statement->method('fetch')->willReturn($fetchResult);
            $statement->method('fetchAll')->willReturn($fetchResult ? [$fetchResult] : []);
            return $statement;
        });

        $pdo->method('beginTransaction')->willReturn(true);
        $pdo->method('commit')->willReturn(true);
        $pdo->method('rollback')->willReturn(true);
        $pdo->method('inTransaction')->willReturn(false);

        return $pdo;
    }

    /**
     * Assert that an exception was thrown with a specific message
     *
     * @param string $exceptionClass
     * @param string $exceptionMessage
     * @param callable $callback
     * @return void
     */
    protected function assertExceptionThrown(
        string $exceptionClass,
        string $exceptionMessage,
        callable $callback
    ): void {
        try {
            $callback();
            $this->fail("Expected exception {$exceptionClass} was not thrown");
        } catch (\Exception $e) {
            $this->assertInstanceOf($exceptionClass, $e);
            $this->assertStringContainsString($exceptionMessage, $e->getMessage());
        }
    }
}
