<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\DatabaseConnection;



/**
 * Class Users,
 * Class responsible for managing user-related operations in the application.
 */
class Users extends BaseModel
{

    /** @var array|null $userData An associative array to hold user data after retrieval from the database */
    private ?array $userData;
    /** @var int $lastUpdatedUserData A timestamp indicating the last time user data was updated */
    private int $lastUpdatedUserData = 0;
    /** @var int $MAX_LAST_UPDATED_USER_DATA The maximum time (in seconds) to consider user data as up-to-date */
    private static int $MAX_LAST_UPDATED_USER_DATA = 5;

    /**
     * Users constructor.
     * Initializes the Users class with a database connection.
     * @param DatabaseConnection|null $dbConnection An optional database connection instance. If not provided, a new instance will be created.
     */
    public function __construct(DatabaseConnection|null $dbConnection)
    {
        $this->dbConnection = $dbConnection ?? DatabaseConnection::getConnectionInstance();
    }

    /**
     * Loads all users from the database and caches the result.
     * If the cached data is still valid (within the defined time limit), it returns the cached data.
     * @return array|null An array of user data or null if no users are found.
     */
    public function loadAllUsers(): ?array
    {
        if ($this->isUserDataUpToDate()) {
            return $this->userData ?? [];
        }

        $this->dbConnection->query('SELECT * FROM users');
        $this->userData = $this->dbConnection->getFetchResults();
        $this->lastUpdatedUserData = time();

        return $this->userData;
    }

    /**
     * Retrieves a user by their ID.
     * @param int $id The ID of the user to retrieve.
     * @return array|null An associative array of user data or null if the user is not found.
     */
    public function getUserById(int $id): ?array
    {
        $user = array_filter($this->getCachedUsers(), function ($user) use ($id) {
            return $user['id'] === $id;
        });

        return $user ? array_values($user)[0] : null;
    }

    /**
     * Retrieves a user by their username.
     * @param string $username The username of the user to retrieve.
     * @return array|null An associative array of user data or null if the user is not found.
     */
    public function getUserByUsername(string $username): ?array
    {
        $user = null;

        $user = array_filter($this->getCachedUsers(), function ($user) use ($username) {
            return $user['username'] === $username;
        });
        
        return $user ? array_values($user)[0] : null;
    }

    /**
     * Verifies user credentials by checking the provided username and password against the stored data.
     * @param string $username The username to verify.
     * @param string $password The password to verify.
     * @return bool True if the credentials are valid, false otherwise.
     */
    public function verifyUserCredentials(string $username, string $password): bool
    {
        $user = $this->getUserByUsername($username);
        if ($user) {
            // Assuming the password is stored as a hashed value in the database
            return password_verify($password, $user['password']);
        }
        return false;
    }

    /**
     * Returns cached user rows, refreshing the cache when it has expired.
     * @return array User rows.
     */
    public function getCachedUsers(): ?array
    {
        return $this->isUserDataUpToDate()
            ? $this->userData ?? []
            : $this->loadAllUsers();
    }

    /**
     * Checks if the cached user data is still valid based on the defined time limit.
     * @return bool True if the cached data is still valid, false otherwise.
     */
    private function isUserDataUpToDate(): bool
    {
        return isset($this->lastUpdatedUserData) && (time() - $this->lastUpdatedUserData) < self::$MAX_LAST_UPDATED_USER_DATA;
    }
}