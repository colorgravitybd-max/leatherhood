<?php
declare(strict_types=1);

namespace LH\Core;

use PDO;
use PDOException;
use PDOStatement;

/**
 * Singleton PDO wrapper with strict prepared statements, transaction
 * helpers and row-locking utilities for the checkout pipeline.
 */
final class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;
    private int $txLevel = 0;

    private function __construct(array $cfg)
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $cfg['host'], $cfg['port'], $cfg['name'], $cfg['charset']
        );

        try {
            $this->pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$cfg['charset']} COLLATE {$cfg['collate']}, time_zone='+06:00'",
            ]);
        } catch (PDOException $e) {
            // Never leak DB errors to end-users.
            error_log('[LH][DB] '.$e->getMessage());
            http_response_code(500);
            exit('Service temporarily unavailable.');
        }
    }

    public static function boot(array $cfg): self
    {
        if (self::$instance === null) {
            self::$instance = new self($cfg);
        }
        return self::$instance;
    }

    public static function i(): self
    {
        if (self::$instance === null) {
            throw new \RuntimeException('Database not booted.');
        }
        return self::$instance;
    }

    public function pdo(): PDO { return $this->pdo; }

    public function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function one(string $sql, array $params = []): ?array
    {
        $row = $this->run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public function all(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll();
    }

    public function value(string $sql, array $params = [])
    {
        $stmt = $this->run($sql, $params);
        $val  = $stmt->fetchColumn();
        return $val === false ? null : $val;
    }

    public function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $ph   = array_map(fn($c) => ':'.$c, $cols);
        $sql  = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES (%s)',
            $table, implode('`,`', $cols), implode(',', $ph)
        );
        $this->run($sql, $data);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = [];
        foreach (array_keys($data) as $c) {
            $set[] = "`$c`=:$c";
        }
        $sql = sprintf('UPDATE `%s` SET %s WHERE %s', $table, implode(',', $set), $where);
        return $this->run($sql, $data + $whereParams)->rowCount();
    }

    /* ---------- Nested transaction helpers ---------- */

    public function begin(): void
    {
        if ($this->txLevel === 0) {
            $this->pdo->beginTransaction();
        } else {
            $this->pdo->exec('SAVEPOINT lvl'.$this->txLevel);
        }
        $this->txLevel++;
    }

    public function commit(): void
    {
        $this->txLevel = max(0, $this->txLevel - 1);
        if ($this->txLevel === 0) {
            $this->pdo->commit();
        } else {
            $this->pdo->exec('RELEASE SAVEPOINT lvl'.$this->txLevel);
        }
    }

    public function rollback(): void
    {
        if ($this->txLevel === 0) return;
        $this->txLevel--;
        if ($this->txLevel === 0) {
            $this->pdo->rollBack();
        } else {
            $this->pdo->exec('ROLLBACK TO SAVEPOINT lvl'.$this->txLevel);
        }
    }

    /**
     * Convenience runner: pass a closure, returns its value, auto-rolls-back
     * on any throwable. Closure receives this Database instance.
     */
    public function transaction(callable $fn)
    {
        $this->begin();
        try {
            $result = $fn($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Lock a single row for the duration of the current transaction.
     * Throws if the row vanishes between requests.
     */
    public function lockRow(string $table, int $id): array
    {
        $row = $this->one("SELECT * FROM `$table` WHERE id = ? FOR UPDATE", [$id]);
        if (!$row) {
            throw new \RuntimeException("Row $table#$id missing under lock.");
        }
        return $row;
    }
}
