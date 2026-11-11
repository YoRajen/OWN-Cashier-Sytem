<?php
declare(strict_types=1);

namespace App\Services;
use App\Services\LoadEnv;
use PDO;

/**
 * Declare Using Environment Variables and default values for database connection parameters.
 */

$loader = LoadEnv::fromFile(__DIR__ . '/../../.env');
$loader->load();
$_ENV['DB_TYPE'] = $_ENV['DB_TYPE'] ?? 'mysql';
$_ENV['DB_HOST'] = $_ENV['DB_HOST'] ?? 'localhost';
$_ENV['DB_DATABASE'] = $_ENV['DB_DATABASE'] ?? 'cashier_system';
$_ENV['DB_USER'] = $_ENV['DB_USER'] ?? 'root';
$_ENV['DB_PASSWORD'] = $_ENV['DB_PASSWORD'] ?? '';
$_ENV['DB_CHARSET'] = $_ENV['DB_CHARSET'] ?? 'utf8mb4';


/**
 * Class DatabaseConnection,
 * Class responsible for managing the database connection. Using PDO for database interactions.
 * @package App\Services
 */
class DatabaseConnection{

    /** @var \PDO|null $connection The PDO connection instance. */
    private static ?\PDO $connection = null;
    /** @var DatabaseConnection|null $instance The singleton instance of the DatabaseConnection class. */
    private static ?DatabaseConnection $instance = null;
    /** @var \PDOStatement $curStatement The current PDO statement for executing queries. */
    private ?\PDOStatement $curStatement;

    /**
     * DatabaseConnection constructor.
     * Initializes the database connection using environment variables.
     * @throws \PDOException If the connection fails.
     */
    private function __construct()
    {
        $dsn = sprintf(
            '%s:host=%s;dbname=%s;charset=%s',
            $_ENV['DB_TYPE'],
            $_ENV['DB_HOST'],
            $_ENV['DB_DATABASE'],
            $_ENV['DB_CHARSET']
        );

        try {
            if (self::$connection === null) {
                self::$connection = new \PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASSWORD']);
                self::$connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                self::$connection->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            }
        } catch (\PDOException $e) {
            throw new \PDOException("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Get the singleton instance of the DatabaseConnection class.
     * @return void The singleton instance.
     */
    public static function initialize(): void
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
    }

    /**
     * Get the singleton instance of the DatabaseConnection class.
     * @return DatabaseConnection The singleton instance.
     * @throws \RuntimeException If the instance is not initialized.
     */
    public static function getConnectionInstance(): DatabaseConnection
    {
        if (self::$instance === null) {
            throw new \RuntimeException("DatabaseConnection instance is not initialized. Call initialize() first.");
        }
        return self::$instance;
    }

    /** 
     * Set the PDO connection instance.
     * @param \PDO $connection The PDO connection instance to set.
     */
    public static function setConnection(\PDO $connection): void
    {
        self::$connection = $connection;
    }

    /**
     * Get the PDO connection instance.
     * @return \PDO The PDO connection instance.
     */
    public static function getConnection(): \PDO
    {
        return self::$connection;
    }

    /**
     * Execute a SQL query with optional parameters.
     * @param string $sql The SQL query to execute.
     * @param array $params Optional parameters for the SQL query.
     * @throws \PDOException If the query execution fails.
     */
    public function query(string $sql, array $params = []): bool
    {
        if ($this->connection === null) {
            throw new \PDOException("Database connection is not established.");
        }
        $this->curStatement = $this->connection->prepare($sql);
        return $this->curStatement->execute($params);
    }

    /**
     * Fetch all results from the last executed query.
     * @return array The fetched results as an associative array.
     * @throws \RuntimeException If no query has been executed yet.
     */
    public function getFetchResults(): array
    {
        if (!isset($this->curStatement)) {
            throw new \RuntimeException("No query has been executed yet.");
        }
        // Destroy the current statement after fetching results to free up resources
        $results = $this->curStatement->fetchAll();
        $this->curStatement = null; // Close the statement after fetching results

        return $results;
    }

    /**
     * Get the number of rows affected by the last executed query.
     * @return int The number of rows affected.
     * @throws \RuntimeException If no query has been executed yet.
     */
    public function getCount(): int
    {
        if (!isset($this->curStatement)) {
            throw new \RuntimeException("No query has been executed yet.");
        }
        return $this->curStatement->rowCount();
    }

    /**
     * Get the last inserted ID from the database.
     * @return string The last inserted ID.
     * @throws \PDOException If the connection is not established.
     */
    public function getLastInsertId(): string
    {
        if ($this->connection === null) {
            throw new \PDOException("Database connection is not established.");
        }
        return $this->connection->lastInsertId();
    }
    

    public function __destruct()
    {
        $this->connection = null; // Close the connection when the object is destroyed
        $this->curStatement = null; // Close the statement when the object is destroyed
        $this->instance = null; // Clear the singleton instance when the object is destroyed
    }

}