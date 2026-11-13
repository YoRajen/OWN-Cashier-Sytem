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
