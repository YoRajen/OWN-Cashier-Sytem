<?php

declare(strict_types=1);

use App\Models\TokenCredential;
use App\Services\DatabaseConnection;

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valid($_POST['csrf'] ?? null)) {
    $user = current_user();

    // Remove the remember-me token (and cookies) if one is in use
    if ($user !== null && isset($_COOKIE['token'])) {
        (new TokenCredential(DatabaseConnection::getConnectionInstance()))->deleteToken((int) $user['id']);
    }

    $_SESSION = [];
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    session_destroy();
}

redirect('login.php');
