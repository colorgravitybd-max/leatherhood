<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

/**
 * Thin PDO singleton. All callers MUST use prepared statements via
 * `pdo()->prepare(...)` or the helper run()/one()/all() shortcuts.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );

        try {
            self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,   // real prepared statements
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ]);
        } catch (PDOException $e) {
            // Never leak credentials to the client.
            error_log('[ELHOE] DB connect failed: ' . $e->getMessage());
            if (APP_DEBUG) {
                throw new RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
            }
            throw new RuntimeException('Database unavailable.');
        }

        return self::$pdo;
    }

    /** Execute a prepared statement with bound params and return the statement. */
    public static function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** Fetch a single row (or null). */
    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row ?: null;
    }

    /** Fetch all rows. */
    public static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    /** Fetch a single scalar value (first column of first row). */
    public static function scalar(string $sql, array $params = []): mixed
    {
        $stmt = self::run($sql, $params);
        $val = $stmt->fetchColumn();
        return $val === false ? null : $val;
    }

    public static function lastId(): string
    {
        return self::pdo()->lastInsertId();
    }
}
