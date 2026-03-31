<?php

namespace App\Support;

use PDO;
use PDOException;
use PDOStatement;

/**
 * Lightweight PDO wrapper — singleton, lazy connection.
 */
class DB
{
    private static ?self $instance = null;
    private ?PDO $pdo = null;

    private function __construct() {}

    // -------------------------------------------------------------------------
    // Singleton
    // -------------------------------------------------------------------------

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // -------------------------------------------------------------------------
    // Connection
    // -------------------------------------------------------------------------

    /**
     * Establish (or return existing) PDO connection.
     */
    public function connect(): PDO
    {
        if ($this->pdo !== null) {
            return $this->pdo;
        }

        $host   = Env::get('DB_HOST', 'localhost');
        $port   = Env::get('DB_PORT', '3306');
        $dbname = Env::get('DB_NAME', 'rooted');
        $user   = Env::get('DB_USER', 'root');
        $pass   = Env::get('DB_PASS', '');

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

        try {
            $this->pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            Logger::critical('Database connection failed', [
                'message' => $e->getMessage(),
                'dsn'     => "mysql:host={$host};port={$port};dbname={$dbname}",
            ]);
            throw $e;
        }

        return $this->pdo;
    }

    // -------------------------------------------------------------------------
    // Query helpers
    // -------------------------------------------------------------------------

    /**
     * Prepare and execute a statement, returning the PDOStatement.
     *
     * @param  string  $sql
     * @param  array<mixed> $params
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch all rows.
     *
     * @param  string  $sql
     * @param  array<mixed> $params
     * @return array<int, array<string, mixed>>
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Fetch a single row or null.
     *
     * @param  string  $sql
     * @param  array<mixed> $params
     * @return array<string, mixed>|null
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * Execute a statement and return affected row count.
     *
     * @param  string  $sql
     * @param  array<mixed> $params
     */
    public function execute(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }

    /**
     * Get the last inserted ID.
     */
    public function lastInsertId(): string
    {
        return $this->connect()->lastInsertId();
    }

    // -------------------------------------------------------------------------
    // Transactions
    // -------------------------------------------------------------------------

    public function beginTransaction(): bool
    {
        return $this->connect()->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->connect()->commit();
    }

    public function rollback(): bool
    {
        return $this->connect()->rollBack();
    }

    // -------------------------------------------------------------------------
    // Static shortcuts
    // -------------------------------------------------------------------------

    public static function select(string $sql, array $params = []): array
    {
        return self::getInstance()->fetchAll($sql, $params);
    }

    public static function selectOne(string $sql, array $params = []): ?array
    {
        return self::getInstance()->fetchOne($sql, $params);
    }

    public static function statement(string $sql, array $params = []): int
    {
        return self::getInstance()->execute($sql, $params);
    }

    /**
     * Reset the singleton (used in testing).
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
