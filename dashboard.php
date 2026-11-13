<?php
/*
 * Dashboard  (non-admin roles, e.g. cashier)
 *
 * Planned metrics:
 *   - Today's revenue and number of transactions
 *   - Items sold today
 *   - Low-stock / out-of-stock products
 *   - Best-selling products
 *   - Revenue per day (last 7 / 30 days)
 *   - Revenue by category
 *
 * Needs: sales history tables (see TODO.md), because checkout currently only deducts stock.
 *
 * Planned:
 *   - Guard: require_login();   (and keep admins out: they only get Account Management)
 *   - Page layout: include includes/topbar.php after <body>
 */

require_once __DIR__ . '/bootstrap.php';
require_role("cashier");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OWN Cashier -- Dashboard</title>
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