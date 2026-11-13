# TODO

Legend: `[x]` done, `[ ]` to do.

## 1. Fix before anything else

These are bugs in the existing backend that break login, "remember me" or the cashier screen.

- [ ] `DatabaseConnection`: replace `$this->connection` / `$this->instance` with `self::$connection` / `self::$instance`
      (`query()`, `getLastInsertId()`, `__destruct()`)
- [ ] `TokenCredential`: give `$tokenData` a default (`private ?array $tokenData = null;`)
- [ ] `TokenCredential::isTokenValid()`: wrap `array_filter` in `array_values()` before reading `$result[0]`
- [ ] `Authentication`: rewrite `checkTokenAuthentication()` (uses undefined `$_SESSION['token']`,
      and logs in a ghost user if the account was deleted) and use `getConnectionInstance()` in the constructor
- [ ] `BaseModel::__serialize()` must return `array`
- [ ] `LoadEnv::fromFile()` must accept a file path, and skip lines without `=`
- [ ] `index.php`: sort categories by `category_id`, not `id`
- [ ] Add the `role` column to `users` (`ALTER TABLE ...`, see README)
- [ ] Create the first admin account (see README, step 4)

## 2. Done

- [x] Autoloader (`autoload.php`)
- [x] SQL schema and sample data
- [x] `do_checkout.php` converted to PDO and `DatabaseConnection`
- [x] Login page, logout, session bootstrap, CSRF, login lockout
- [x] Absolute redirects and rejection of `/page.php/extra` URLs
- [x] Role-aware top bar (`includes/topbar.php`)
- [x] Placeholder pages: `accounts.php`, `dashboard.php`, `products.php`
- [x] README rewritten

## 3. Wire up the top bar and access control

- [ ] Include `includes/topbar.php` in `index.php` (replace the old `.top_bar` block) and remove the old logout form if you added one
- [ ] `index.php`: `require_login();` at the top
- [ ] `actions/do_checkout.php`: `require_login(true);`
- [ ] Decide what an admin sees at `index.php` (currently admins land on `accounts.php`; the top bar offers no cashier link)
- [ ] `accounts.php`: `require_role('admin');`
- [ ] `dashboard.php` and `products.php`: `require_login();` and send admins to `accounts.php`
- [ ] Move `cashier_system.sql` into `database/`

## 4. Account Management (admin)

- [ ] List users (username, email, role, created, updated)
- [ ] Add user (hash the password server-side, validate username/email uniqueness)
- [ ] Edit email and role, reset password
- [ ] Deactivate or delete user; protect against deleting or demoting yourself and the last admin
- [ ] Optional: `is_active` column so accounts can be disabled without deleting history
- [ ] Optional: force password change on first login

## 5. Product Management

- [ ] List products grouped by category
- [ ] Add, edit and delete products
- [ ] Restock (adjust stock) with validation
- [ ] Categories: keep the defaults, add new ones, rename, block deleting non-empty categories
- [ ] Create new IDs automatically from the category code (e.g. `DR004`)
- [ ] Server-side validation (price >= 0, stock >= 0, unique product id)

## 6. Dashboard

- [ ] Record sales: new `sales` and `sale_items` tables, written inside the checkout transaction
      (checkout currently only deducts stock, so there is no history to report on)
- [ ] Store the price at time of sale in `sale_items` (prices change later)
- [ ] Metrics: today's revenue, transactions, items sold, low stock, best sellers, revenue per day and per category
- [ ] Date range filter

## 7. Roles and permissions (when needed)

- [ ] Keep the single `users.role` column while there are only two roles
- [ ] If rules grow, add `App/Services/Authorization.php` (role to permission map) and
      `require_permission('products.manage')`, instead of many `require_role()` calls

## 8. Security and hardening

- [ ] Store a hash of the remember-me token (`sha256`), not the token itself; one token per device instead of one per user
- [ ] Set `Secure` / `HttpOnly` / `SameSite` on the remember-me cookies
- [ ] Rate-limit logins per username and IP in the database (the current lock is per session only)
- [ ] CSRF token on `do_checkout.php` and all future POST endpoints
- [ ] Remove `db.php` once nothing requires it
- [ ] Run Apache with `AcceptPathInfo Off` (optional; the app already rejects path info)
- [ ] Use a dedicated MySQL user instead of `root` with an empty password

## 9. Quality

- [ ] Query users by username/id in SQL instead of loading every row into the cache
- [ ] Return consistent types from PDO (ids compared with `===`), or cast explicitly
- [ ] Basic tests for checkout (insufficient stock, duplicate items, rollback)
- [ ] Receipt printing / transaction history
- [ ] Translate or localise UI text if needed
