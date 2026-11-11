<?php

declare(strict_types=1);

namespace App\Services;
use App\Services\DatabaseConnection;
use App\Models\Users;
use App\Models\TokenCredential;


class Authentication
{
    /** @var DatabaseConnection $dbConnection The database connection instance. */
    private DatabaseConnection $dbConnection;

    /** @var mixed[] $userData An associative array to hold user data after successful authentication. */
    private array $userData;

    /**
     * Authentication constructor.
     * Initializes the Authentication class with a database connection.
     * @param DatabaseConnection|null $dbConnection An optional database connection instance. If not provided, a new instance will be created.
     */
    public function __construct(DatabaseConnection|null $dbConnection)
    {
        $this->dbConnection = $dbConnection ?? DatabaseConnection::getConnection();
    
    }

    /**
     * Authenticates a user based on provided username and password.
     * If authentication is successful, user data is stored in the session.
     * If "remember me" is checked, a token is generated and stored in the database.
     * @param string $username The username of the user attempting to authenticate.
     * @param string $password The password of the user attempting to authenticate.
     * @param bool $rememberme A flag indicating whether to remember the user for future sessions.
     * @return bool Returns true if authentication is successful, false otherwise.
     */
    public function authenticate(string $username, string $password, bool $rememberme): bool
    {
        $userModel = new Users($this->dbConnection);
        if ($userModel->verifyUserCredentials($username, $password)) {
            $this->userData = $userModel->getUserByUsername($username);
            $this->userData['password'] = null; // Remove password from user data for security reasons

            $_SESSION['user'] = $this->userData; // Store user data in session
            
            if ($rememberme) {
                $tokenCredentialModel = new TokenCredential($this->dbConnection);
                $token = $tokenCredentialModel::createToken();
                $tokenCredentialModel->insertToken($this->userData['id'], $token);
            }
            return true;
        }

        return false;
    }   
    
    /**
     * Checks if the user is authenticated based on session data or token authentication.
     * @return bool Returns true if the user is authenticated, false otherwise.
     */
    public function checkTokenAuthentication(): bool
    {
        if (isset($_COOKIE['rememberme']) && isset($_COOKIE['token'])) {
            $tokenCredentialModel = new TokenCredential($this->dbConnection);
            if ($tokenCredentialModel->isTokenValid($_COOKIE['token'])) {
                $userModel = new Users($this->dbConnection);
                $this->userData = $userModel->getUserById($tokenCredentialModel->getUserIdByToken($_COOKIE['token']));
                $this->userData['password'] = null; // Remove password from user data for security reasons
                $_SESSION['user'] = $this->userData; // Store user data in session
                return true;
            } else {
                $tokenCredentialModel->deleteToken($tokenCredentialModel->getUserIdByToken($_SESSION['token']));
            }
        }
        return false;
    }
}