<?php

declare(strict_types=1);

use App\Services\Authentication;
use App\Services\DatabaseConnection;

require_once __DIR__ . '/autoload.php';

// Reject URLs like /login.php/anything (Apache's PATH_INFO). They break relative
// links and redirects, and are never needed by this app.
if (!empty($_SERVER['PATH_INFO'])) {
    http_response_code(404);
    exit('Not found');
}

// Secure session cookie settings (must be set before session_start)
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

DatabaseConnection::initialize();

/** URL path of the project folder, e.g. "/OWN-Cashier-Sytem" (empty if at web root).
 * @return string
 */
function base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $root = str_replace('\\', '/', (string) realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? '')));
    $dir  = str_replace('\\', '/', __DIR__); // bootstrap.php lives in the project root
 
    if ($root !== '' && stripos($dir, $root) === 0) {
        $base = rtrim(substr($dir, strlen($root)), '/');
    } else {
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    }
    return $base;
}
 
/** Redirect to a project-relative path such as "login.php" using an absolute URL path.
 * @param string $path The relative path to redirect to.
 * @return never
 */
function redirect(string $path): never
{
    header('Location: ' . base_path() . '/' . ltrim($path, '/'));
    exit;
}

/** The Authentication service bound to the shared DB connection.
 *  @return Authentication
*/
function auth(): Authentication
{
    return new Authentication(DatabaseConnection::getConnectionInstance());
}

/** The logged-in user (without password), or null.
 * @return array|null
 */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/** CSRF token for forms (created on first use).
 * @return string
 */
function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

/**
 * Validate the provided CSRF token against the session token.
 * @param string|null $token The CSRF token to validate.
 * @return bool True if the token is valid, false otherwise.
 */
function csrf_valid(?string $token): bool
{
    return is_string($token) && hash_equals(csrf_token(), $token);
}

/**
 * Restore the session from the "remember me" cookie if needed.
 * Returns true when a user is logged in afterwards.
 * @return bool 
 */
function is_logged_in(): bool
{
    return current_user() !== null || auth()->checkTokenAuthentication();
}

/**
 * Guard for pages and endpoints. Pages redirect to login.php,
 * JSON endpoints ($json = true) answer with HTTP 401.
 * @param bool $json Whether to respond with JSON (true) or redirect (false).
 * @return void
 */
function require_login(bool $json = false): void
{
    if (is_logged_in()) {
        return;
    }
    if ($json) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.']);
        exit;
    }
    redirect('login.php');
}

/** Guard by role, e.g. require_role('admin'). Roles live in users.role.
 * @param string ...$roles The roles that are allowed to access the page.
 * @return void
 */
function require_role(string ...$roles): void
{
    require_login();
    if (!in_array(current_user()['role'] ?? '', $roles, true)) {
        http_response_code(403);
        exit('403 Forbidden');
    }
}
