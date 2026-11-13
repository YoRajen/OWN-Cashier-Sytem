<?php
/*
 * Account Management  (admin only)
 *
 * Planned:
 *   - Guard: require_role('admin');   (bootstrap.php)
 *   - List all users: username, email, role, created_at, updated_at
 *   - Add user (admins are the only way accounts get created; there is no register page)
 *       username, email, initial password (stored with password_hash), role (admin | cashier)
 *   - Edit user: email, role, reset password
 *   - Deactivate / delete user (cannot delete or demote yourself,
 *       and always keep at least one admin)
 *   - Page layout: include includes/topbar.php after <body>
 */

require_once __DIR__ . '/bootstrap.php';
require_role("admin");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OWN Cashier -- Account Management</title>
    <!-- Retro Google Fonts: Space Grotesk (Neo-Retro Grotesque) & Space Mono (Mechanical Monospace / Dot-matrix) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="<?= $tbEsc($tbBase) ?>/assets/css/styles.css?v=<?= (int) @filemtime(__DIR__ . '/assets/css/topbar.css') ?>">
</head>

<body>
    <?php require __DIR__ . '/includes/topbar.php'; ?>
</body>
</html>