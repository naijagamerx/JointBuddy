<?php
/**
 * Mock PDO Factory
 *
 * Creates mock PDO instances for testing database operations
 */

declare(strict_types=1);

use PDO;
use PDOStatement;

class MockPDOFactory {
    /**
     * Create a mock PDO instance
     *
     * @param array|null $fetchResult Result to return from fetch
     * @param array|null $fetchAllResult Result to return from fetchAll
     * @param bool|null $execResult Result to return from exec
     * @param int|null $lastInsertIdResult Result to return from lastInsertId
     * @return PDO&PHPUnit\Framework\MockObject\MockObject
     */
    public static function create(
        ?array $fetchResult = null,
        ?array $fetchAllResult = null,
        ?bool $execResult = null,
        ?int $lastInsertIdResult = null
    ): PDO {
        $pdo = PHPUnit\Framework\TestCase::createMock(PDO::class);

        // Mock prepare method
        $pdo->method('prepare')->willReturnCallback(function($query) use ($fetchResult, $fetchAllResult, $execResult) {
            return self::createMockStatement($fetchResult, $fetchAllResult, $execResult);
        });

        // Mock query method
        $pdo->method('query')->willReturnCallback(function($query) use ($fetchResult, $fetchAllResult) {
            return self::createMockStatement($fetchResult, $fetchAllResult);
        });

        // Mock exec method
        if ($execResult !== null) {
            $pdo->method('exec')->willReturn($execResult);
        }

        // Mock lastInsertId method
        if ($lastInsertIdResult !== null) {
            $pdo->method('lastInsertId')->willReturn($lastInsertIdResult);
        }

        // Mock beginTransaction, commit, rollback
        $pdo->method('beginTransaction')->willReturn(true);
        $pdo->method('commit')->willReturn(true);
        $pdo->method('rollback')->willReturn(true);

        // Mock inTransaction
        $pdo->method('inTransaction')->willReturn(false);

        return $pdo;
    }

    /**
     * Create a mock PDOStatement
     *
     * @param array|null $fetchResult
     * @param array|null $fetchAllResult
     * @param bool|null $execResult
     * @return PDOStatement&PHPUnit\Framework\MockObject\MockObject
     */
    private static function createMockStatement(
        ?array $fetchResult = null,
        ?array $fetchAllResult = null,
        ?bool $execResult = null
    ): PDOStatement {
        $statement = PHPUnit\Framework\TestCase::createMock(PDOStatement::class);

        // Mock execute
        $statement->method('execute')->willReturn(true);

        // Mock fetch
        if ($fetchResult !== null) {
            $statement->method('fetch')->willReturn($fetchResult);
        } else {
            $statement->method('fetch')->willReturn(false);
        }

        // Mock fetchAll
        if ($fetchAllResult !== null) {
            $statement->method('fetchAll')->willReturn($fetchAllResult);
        } else {
            $statement->method('fetchAll')->willReturn([]);
        }

        // Mock rowCount
        $statement->method('rowCount')->willReturn(1);

        return $statement;
    }

    /**
     * Create a mock PDO that throws an exception
     *
     * @param string $exceptionMessage
     * @param string $exceptionClass
     * @return PDO&PHPUnit\Framework\MockObject\MockObject
     */
    public static function createThrowable(
        string $exceptionMessage = 'Database error',
        string $exceptionClass = PDOException::class
    ): PDO {
        $pdo = PHPUnit\Framework\TestCase::createMock(PDO::class);

        $pdo->method('prepare')->willThrowException(new $exceptionClass($exceptionMessage));
        $pdo->method('query')->willThrowException(new $exceptionClass($exceptionMessage));
        $pdo->method('exec')->willThrowException(new $exceptionClass($exceptionMessage));

        return $pdo;
    }
}
