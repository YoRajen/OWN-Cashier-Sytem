<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

// Already logged in (session or remember-me cookie)? Go straight to the cashier.
if (is_logged_in()) {
    if (current_user()['role'] === 'admin') {
        redirect('admin.php');
    } else {
        redirect('index.php');
    }
}

const MAX_FAILS = 5;     // failed attempts before a temporary lock
const LOCK_SECONDS = 60; // lock duration

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $remember = isset($_POST['remember']);

    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $error = 'Form expired. Please try again.';
    } elseif (($_SESSION['lock_until'] ?? 0) > time()) {
        $error = 'Too many attempts. Try again in ' . ($_SESSION['lock_until'] - time()) . ' seconds.';
    } elseif ($username === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } else {
        try {
            if (auth()->authenticate($username, $password, $remember)) {
                session_regenerate_id(true); // prevent session fixation
                unset($_SESSION['fails'], $_SESSION['lock_until']);
                redirect('login.php');
                exit;
            }

            // Generic message: don't reveal whether the username exists
            $error = 'Invalid username or password.';
            $_SESSION['fails'] = ($_SESSION['fails'] ?? 0) + 1;
            if ($_SESSION['fails'] >= MAX_FAILS) {
                $_SESSION['lock_until'] = time() + LOCK_SECONDS;
                $_SESSION['fails'] = 0;
            }
            usleep(300000); // small delay to slow down guessing
        } catch (Throwable $e) {
            error_log('Login error: ' . $e->getMessage());
            $error = 'A server error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - OWN Cashier</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css?v=<?= time(); ?>">
    <style>
        body { overflow: auto; background: var(--color-chassis); }
        .login_page { min-height: 100vh; display: grid; place-items: center; padding: 24px; }
        .login_card { width: 100%; max-width: 380px; background: var(--color-white); border: var(--retro-border-thick); box-shadow: var(--retro-shadow-lg); }
        .login_head { background: var(--color-slate); color: var(--color-white); padding: 16px 20px; border-bottom: var(--retro-border-thick); }
        .login_head h1 { margin: 0; font: 700 22px var(--font-heading); letter-spacing: .5px; }
        .login_head p { margin: 4px 0 0; font: 400 12px var(--font-mono); opacity: .8; }
        .login_body { padding: 20px; display: grid; gap: 14px; }
        .login_field label { display: block; margin-bottom: 6px; font: 700 12px var(--font-mono); text-transform: uppercase; }
        .login_field input[type=text], .login_field input[type=password] { width: 100%; padding: 10px 12px; font: 400 15px var(--font-mono); border: var(--retro-border); background: var(--color-chassis); }
        .login_field input:focus { outline: 3px solid var(--color-orange); outline-offset: 0; background: var(--color-white); }
        .login_pw { position: relative; }
        .login_pw button { position: absolute; right: 6px; top: 50%; transform: translateY(-50%); padding: 4px 8px; font: 700 11px var(--font-mono); border: var(--retro-border); background: var(--color-white); cursor: pointer; }
        .login_remember { display: flex; align-items: center; gap: 8px; font: 400 13px var(--font-mono); }
        .login_submit { padding: 12px; font: 700 15px var(--font-heading); text-transform: uppercase; color: var(--color-black); background: var(--color-orange); border: var(--retro-border-thick); box-shadow: var(--retro-shadow); cursor: pointer; }
        .login_submit:active { transform: translate(3px, 3px); box-shadow: none; }
        .login_error { padding: 10px 12px; font: 700 13px var(--font-mono); background: #ffe3d6; border: var(--retro-border); }
        .login_note { margin: 0; font: 400 12px var(--font-mono); color: var(--color-slate); text-align: center; }
    </style>
</head>

<body>
    <div class="login_page">
        <form class="login_card" method="post" action="login.php" autocomplete="on">
            <div class="login_head">
                <h1>OWN Cashier</h1>
                <p>Staff login</p>
            </div>
            <div class="login_body">
                <?php if ($error !== ''): ?>
                    <div class="login_error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

                <div class="login_field">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>"
                        autocomplete="username" maxlength="50" required autofocus>
                </div>

                <div class="login_field">
                    <label for="password">Password</label>
                    <div class="login_pw">
                        <input type="password" id="password" name="password" autocomplete="current-password" required>
                        <button type="button" id="toggle_pw" aria-label="Show password">SHOW</button>
                    </div>
                </div>

                <label class="login_remember">
                    <input type="checkbox" name="remember" value="1"> Keep me signed in for 7 days
                </label>

                <button type="submit" class="login_submit">Log in</button>
                <p class="login_note">No account? Ask your administrator.</p>
            </div>
        </form>
    </div>

    <script>
        const pw = document.getElementById('password');
        const toggle = document.getElementById('toggle_pw');
        toggle.addEventListener('click', () => {
            const show = pw.type === 'password';
            pw.type = show ? 'text' : 'password';
            toggle.textContent = show ? 'HIDE' : 'SHOW';
        });
    </script>
</body>

</html>
