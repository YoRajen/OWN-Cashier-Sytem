<?php

declare(strict_types=1);

namespace App\Models;
use App\Services\DatabaseConnection;

/**
 * Class TokenCredential,
 * Class responsible for managing token-related operations in the application.
 */
class TokenCredential extends BaseModel
{
    /** @var array|null $tokenData An associative array to hold token data after retrieval from the database */
    private ?array $tokenData = null;
    /** @var int $lastUpdatedTokenData A timestamp indicating the last time token data was updated */
    private int $lastUpdatedTokenData = 0;

    /** @var int $TOKEN_LENGTH The length of the token to be generated. */
    private static int $TOKEN_LENGTH = 32;
    /** @var int $TOKEN_EXPIRATION The expiration time for the token in seconds (7 days). */
    private static int $TOKEN_EXPIRATION = 7 * 24 * 60 * 60;
    /** @var int $MAX_LAST_UPDATED_TOKEN_DATA The maximum time (in seconds) to consider token data as up-to-date */
    private static int $MAX_LAST_UPDATED_TOKEN_DATA = 30;

    /**
     * TokenCredential constructor.
     * Initializes the TokenCredential class with a database connection.
     * @param DatabaseConnection|null $dbConnection An optional database connection instance. If not provided, a new instance will be created.
     */
    public function __construct(DatabaseConnection|null $dbConnection)
    {
        $this->dbConnection = $dbConnection ?? DatabaseConnection::getConnectionInstance();
    }

    /**
     * Loads all tokens from the database and caches the result.
     * If the cached data is still valid (within the defined time limit), it returns the cached data.
     * @return array|null An array of token data or null if no tokens are found.
     */
    public function loadAllTokens(): ?array
    {
        if ($this->isTokenDataUpToDate()) {
            return $this->tokenData ?? [];
        }

        $this->dbConnection->query('SELECT * FROM token_credentials');
        $this->tokenData = $this->dbConnection->getFetchResults();
        $this->lastUpdatedTokenData = time();

        return $this->tokenData;
    }

    /**
     * Retrieves a token by the associated user ID.
     * @param int $userId The ID of the user for whom to retrieve the token.
     * @return array|null An associative array of token data or null if no token is found for the given user ID.
     */
    public function getTokenByUserId(int $userId): ?array
    {
        foreach ($this->getCachedTokens() as $token) {
            if ($token['user_id'] === $userId) {
                return $token;
            }
        }

        return null;
    }

    /**
     * Retrieves a token by its value.
     * @param string $token The token value to search for.
     * @return array|null An associative array of token data or null if the token is not found.
     */
    public function getTokenByToken(string $token): ?array
    {
        foreach ($this->getCachedTokens() as $tokenData) {
            if ($tokenData['token'] === $token) {
                return $tokenData;
            }
        }

        return null;
    }

    /**
     * Retrieves the user ID associated with a given token.
     * @param string $token The token for which to retrieve the associated user ID.
     * @return int|null The user ID if found, or null if not found.
     */
    public function getUserIdByToken(string $token): ?int
    {
        foreach ($this->getCachedTokens() as $tokenData) {
            if ($tokenData['token'] === $token) {
                return (int)$tokenData['user_id'];
            }
        }

        return null;
    }

    /**
     * Inserts a new token into the database for the specified user ID and sets the corresponding cookies.
     * @param int $userId The ID of the user for whom the token is being created.
     * @param string $token The token to be inserted into the database.
     * @return bool True if the token was successfully inserted, false otherwise.
     */
    public function insertToken(int $userId, string $token): bool
    {
        $this->dbConnection->getConnection()->beginTransaction();
        if ($this->dbConnection->query('INSERT INTO token_credentials (user_id, token) VALUES (:user_id, :token)', [
            'user_id' => $userId,
            'token' => $token
        ])) {
            setcookie('rememberme', '1', time() + self::$TOKEN_EXPIRATION, "/"); // Set a cookie for the remember me functionality
            setcookie('token', $token, time() + self::$TOKEN_EXPIRATION, "/"); // Set a cookie for the token
            $this->dbConnection->getConnection()->commit();
            return true;
        } else {
            $this->dbConnection->getConnection()->rollBack();
            return false;
        }
    }

    /**
     * Deletes the token associated with the given user ID from the database and removes the corresponding cookies.
     * @param int $userId The ID of the user whose token should be deleted.
     * @return bool True if the token was successfully deleted, false otherwise.
     */
    public function deleteToken(int $userId): bool
    {
        $this->dbConnection->getConnection()->beginTransaction();
        if ($this->dbConnection->query('DELETE FROM token_credentials WHERE user_id = :user_id', [
            'user_id' => $userId
        ])) {
            unset($_COOKIE['rememberme']); // Remove the remember me cookie
            setcookie('rememberme', '', time() - 3600, "/"); // Expire the remember me cookie
            unset($_COOKIE['token']); // Remove the token cookie
            setcookie('token', '', time() - 3600, "/"); // Expire the token cookie
            $this->dbConnection->getConnection()->commit();
            return true;
        } else {
            $this->dbConnection->getConnection()->rollBack();
            return false;
        }
    }

    /**
     * Returns cached token rows, refreshing the cache when it has expired.
     * @return array Token rows.
     */
    public function getCachedTokens(): ?array
    {
        return $this->isTokenDataUpToDate() 
            ? $this->tokenData ?? []
            : $this->loadAllTokens();
    }

    /**
     * Checks if the provided token is valid and not expired.
     * @param string $token The token to validate.
     * @return bool True if the token is valid and not expired, false otherwise.
     */
    public function isTokenValid(string $token): bool
    {
       $result = array_values(array_filter($this->getCachedTokens(), fn($t) => $t['token'] === $token));

        return !empty($result) && (time() - strtotime($result[0]['created_at'])) < self::$TOKEN_EXPIRATION;
    }

    /**
     * Generates a secure random token of the specified length.
     * @param int $length The length of the token to generate. Default is 32 characters.
     * @return string A securely generated random token.
     */
    public static function createToken (?int $length = null): string
    {
        return bin2hex(random_bytes($length ?? self::$TOKEN_LENGTH));
    }

    /**
     * Checks if the token data is up-to-date based on the last updated timestamp.
     * @return bool True if the token data is up-to-date, false otherwise.
     */
    private function isTokenDataUpToDate(): bool
    {
        return $this->tokenData !== null
            && isset($this->lastUpdatedTokenData) && (time() - $this->lastUpdatedTokenData) < self::$MAX_LAST_UPDATED_TOKEN_DATA;
    }
}