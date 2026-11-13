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
