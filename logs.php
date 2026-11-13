<?php
/*
 * Log Management  (admin only)
 *
 * Read-only viewer for the activity_logs table (append-only audit trail).
 *
 * Planned:
 *   - Guard: require_role('admin');   (bootstrap.php)
 *   - Table with pagination, newest first: time, user, level, action, entity, description, IP
 *   - Filters: date range, user, level (info | warning | error), action, entity type / id
 *   - Detail view for one entry, including the JSON "details" (old / new values)
 *   - Export the filtered result to CSV
 *   - Retention: purge entries older than N days (the only DELETE ever run on this table)
 *   - Entries are never edited or deleted one by one from the UI
 *
 * Needs (see TODO.md):
 *   - App/Services/ActivityLogger.php  -> ActivityLogger::log(...) called from login, logout,
 *                                         account, product and checkout code
 *   - App/Models/ActivityLogs.php      -> reading and filtering for this page
 *
 * Never store passwords, tokens or other secrets in the log.
 * Page layout: include includes/topbar.php after <body>
 */

require_once __DIR__ . '/bootstrap.php';
require_role("admin");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OWN Cashier -- Log Management</title>
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