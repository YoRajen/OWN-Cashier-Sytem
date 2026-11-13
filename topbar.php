<?php
/**
 * Shared top bar.
 *
 * Include it right after <body> on every page behind the login:
 *     <?php require __DIR__ . '/includes/topbar.php'; ?>
 * bootstrap.php must be loaded first (current_user(), base_path(), csrf_token()).
 *
 *   role "admin"     -> Account Management
 *   any other role   -> Dashboard, Product Management
 *
 * The brand ("OWN Cashier") links to each role's home page:
 * admin -> accounts.php, everyone else -> index.php (the cashier screen).
 */

$tbUser = current_user();
if ($tbUser === null) {
    return; // guests get no top bar
}

$tbIsAdmin = ($tbUser['role'] ?? '') === 'admin';

$tbNav = $tbIsAdmin
    ? [
        ['label' => 'Account Management', 'file' => 'accounts.php'],
    ]
    : [
        ['label' => 'Dashboard',          'file' => 'dashboard.php'],
        ['label' => 'Product Management', 'file' => 'products.php'],
    ];

$tbHome    = $tbIsAdmin ? 'accounts.php' : 'index.php';
$tbCurrent = basename($_SERVER['SCRIPT_NAME'] ?? '');
$tbBase    = base_path();
$tbEsc     = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
?>
<link rel="stylesheet" href="<?= $tbEsc($tbBase) ?>/assets/topbar.css?v=<?= (int) @filemtime(__DIR__ . '/../assets/topbar.css') ?>">

<header class="top_bar top_bar_nav">
    <div class="brand_container">
        <a class="title brand_link" href="<?= $tbEsc($tbBase . '/' . $tbHome) ?>">OWN Cashier</a>
    </div>

    <nav class="topbar_nav" aria-label="Main navigation">
        <?php foreach ($tbNav as $item): ?>
            <a class="nav_link<?= $tbCurrent === $item['file'] ? ' is_current' : '' ?>"
               href="<?= $tbEsc($tbBase . '/' . $item['file']) ?>"
               <?= $tbCurrent === $item['file'] ? 'aria-current="page"' : '' ?>>
                <?= $tbEsc($item['label']) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="topbar_right">
        <div class="clock_display">
            <span class="date_time" id="date_time"></span>
        </div>

        <div class="topbar_user">
            <span class="topbar_name"><?= $tbEsc((string) ($tbUser['username'] ?? '')) ?></span>
            <span class="topbar_role"><?= $tbEsc((string) ($tbUser['role'] ?? 'user')) ?></span>
        </div>

        <form class="topbar_logout" method="post" action="<?= $tbEsc($tbBase) ?>/logout.php">
            <input type="hidden" name="csrf" value="<?= $tbEsc(csrf_token()) ?>">
            <button type="submit" class="nav_link nav_button">Log out</button>
        </form>
    </div>
</header>
