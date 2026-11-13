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
