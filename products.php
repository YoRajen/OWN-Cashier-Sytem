<?php
/*
 * Product Management  (non-admin roles, e.g. cashier)
 *
 * Products and the categories inside them, on one page.
 *
 * Planned:
 *   - Guard: require_login();   (and keep admins out: they only get Account Management)
 *   - Categories
 *       * Default categories come from the seed data (Drinks, Food, Snacks)
 *       * Add a new category (code + name), rename; block deleting a category that still has products
 *   - Products
 *       * List grouped by category: id, name, price, stock
 *       * Add product (id/code, name, price, stock, category), edit, delete
 *       * Adjust stock (restock)
 *   - Page layout: include includes/topbar.php after <body>
 */

require_once __DIR__ . '/bootstrap.php';
require_login();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OWN Cashier -- Product Management</title>
    <!-- Retro Google Fonts: Space Grotesk (Neo-Retro Grotesque) & Space Mono (Mechanical Monospace / Dot-matrix) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="<?= $tbEsc($tbBase) ?>/assets/css/styles.css?v=<?= (int) @filemtime(__DIR__ . '/assets/css/topbar.css') ?>">
</head>

<body>
</body>
</html>