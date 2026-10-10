<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;
use Throwable;
use Pdo\Mysql;

/**
 * One MySQL connection for the whole request.
 *
 * Models use this class. They never build a DSN or concatenate SQL values.
 * Security choices made here:
 * - Credentials come from the environment, not from a committed config file.
 * - The DSN is assembled only after the host, port, and database name are checked.
 * - Native prepares are required (no emulated prepares).
 * - Multi-statements and LOCAL INFILE are off.
 * - Values are bound. Table and column names, which cannot be bound, must match a strict allow-list.
 * - PDO errors are logged. Callers receive a generic RuntimeException so SQL is not shown to the browser.
 */

final class Database
{
    private static ?self $instance = null;

    private function __construct(private PDO $pdo)
    {

    }

    /**
     * Open the connection once. Call this from public/index.php after config is loaded.
     *
     * Expected environment variables: DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD.
     */
    public static function boot(bool $debug = false): void
    {
        if (self::$instance !== null) {
            return;
        }

        $host = self::env('DB_HOST', '127.0.0.1');
        $port = (int) self::env('DB_PORT', '3306');
        $name = self::env('DB_DATABASE', 'dayfold');
        $user = self::env('DB_USER');
        $pass = self::env('DB_PASSWORD');

        self::assertHost($host);
        self::assertIdent($name, 'database name');

        if ($port < 1 || $port > 65535) {
            throw new RuntimeException("Database port must be between 1 and 65535.");
        }

        if ($user === '' || $pass === '') {
            throw new RuntimeException("Database credentials are not configured.");
        }

        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $name);

        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::ATTR_PERSISTENT => false,
            Mysql::ATTR_MULTI_STATEMENTS => false,
            Mysql::ATTR_LOCAL_INFILE => false,
            Mysql::ATTR_INIT_COMMAND =>
                "SET time_zone = '+00:00', sql_mode = 'STRICT_ALL_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'",
        ];

        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            self::fail($e, $debug);
        }

        self::$instance = new self($pdo);
    }

    public static function connection(): self
    {
        if (self::$instance === null) {
            throw new RuntimeException('Database has not been started.');
        }
        return self::$instance;
    }

    /**
     * Run an INSERT, UPDATE, or DELETE. Returns the number of affected rows.
     *
     * @param array<string, scalar|null> $params
     */
    public function execute(string $sql, array $params = []): int
    {
        $statement = $this->prepare($sql, $params);
        return $statement->rowCount();
    }

    /**
     * Return every row. An empty result is an empty array, not an error.
     *
     * @param array<string, scalar|null> $params
     * @return list<array<string, mixed>>
     */
    public function all(string $sql, array $params = []): array
    {
        $statement = $this->prepare($sql, $params);
        $rows = $statement->fetchAll();
        return is_array($rows) ? $rows : [];
    }

    /**
     * Return the first row, or null when nothing matched.
     *
     * @param array<string, scalar|null> $params
     * @return array<string, mixed>|null
     */
    public function first(string $sql, array $params = []): ?array
    {
        $statement = $this->prepare($sql, $params);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    /**
     * Return one column from the first row. Useful for COUNT and lookups.
     *
     * @param array<string, scalar|null> $params
     */
    public function value(string $sql, array $params = []): mixed
    {
        $statement = $this->prepare($sql, $params);
        $value = $statement->fetchColumn();
        return $value === false ? null : $value;
    }

    /** Last auto-increment id. PDO returns this as a string so large ids are not truncated. */
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Run several writes as one unit. A thrown exception rolls the unit back.
     * The callback receives this same Database instance.
     */
    public function transaction(callable $work): mixed
    {
        if ($this->pdo->inTransaction()) {
            return $work($this);
        }
        $this->pdo->beginTransaction();
        try {
            $result = $work($this);
            $this->pdo->commit();
            return $result;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Quote a table or column name. Placeholders cannot bind identifiers,
     * so this rejects anything except letters, numbers, and underscores.
     */
    public static function ident(string $name): string
    {
        self::assertIdent($name, 'identifier');
        return '`' . $name . '`';
    }

    /**
     * Named placeholders only. Arrays and objects are rejected so a value
     * cannot smuggle extra structure into the query.
     *
     * @param array<string, scalar|null> $params
     */
    private function bind(PDOStatement $statement, array $params): void
    {
        foreach ($params as $key => $value) {
            if (!is_string($key)) {
                throw new InvalidArgumentException('Use named placeholders such as :email.');
            }
            $name = str_starts_with($key, ':') ? $key : ':' . $key;
            if (preg_match('/^:[A-Za-z_][A-Za-z0-9_]*$/', $name) !== 1) {
                throw new InvalidArgumentException('Invalid parameter name.');
            }
            if (is_array($value) || is_object($value)) {
                throw new InvalidArgumentException('Parameter values must be scalar or null.');
            }
            $type = match (true) {
                $value === null => PDO::PARAM_NULL,
                is_bool($value) => PDO::PARAM_BOOL,
                is_int($value) => PDO::PARAM_INT,
                default => PDO::PARAM_STR,
            };
            $statement->bindValue($name, $value, $type);
        }
    }

    /**
     * @param array<string, scalar|null> $params
     */
    private function prepare(string $sql, array $params): PDOStatement
    {
        if (preg_match('/;\s*\S/', $sql) === 1) {
            throw new InvalidArgumentException('Multiple SQL statements are not allowed.');
        }
        try {
            $statement = $this->pdo->prepare($sql);
            $this->bind($statement, $params);
            $statement->execute();
        } catch (PDOException $e) {
            self::fail($e, false);
        }
        return $statement;
    }


    private static function env(string $key, string $default = ''): string
    {
        $value = getenv($key);

        if ($value === false || $value === '') {
            return $default;
        }

        return $value;
    }

    private static function assertHost(string $host): void
    {
        $isName = preg_match('/^[a-zA-Z0-9.-]+$/', $host) === 1;
        $isIp = filter_var($host, FILTER_VALIDATE_IP) !== false;

        if (!$isName && !$isIp) {
            throw new RuntimeException("Invalid database host: {$host}");
        }
    }

    private static function assertIdent(string $value, string $type): void
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $value) !== 1) {
            throw new InvalidArgumentException("Invalid {$type}: {$value}. Only letters, numbers, and underscores are allowed.");
        }
    }

    /** @return never */
    private static function fail(PDOException $e, bool $debug): void
    {
        error_log('Database error: ' . $e->getMessage());

        if ($debug) {
            throw new RuntimeException("Database request failed.", 0, $e);
        }

        throw new RuntimeException("Database request failed.");
    }
}
