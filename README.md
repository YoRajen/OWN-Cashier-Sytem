# OWN Cashier System

A web-based cashier (point of sale) and product management application built with
plain PHP (PDO), MySQL, HTML, CSS and a little JavaScript. No framework, no Composer required.

> Work in progress. See [TODO.md](TODO.md) for what is done and what is planned.

## Features

**Working now**

- Login with username and password, optional "keep me signed in" (7 days), and logout
- Accounts are created by an administrator only (there is no register page)
- Role-based top bar (admin or cashier)
- Product list with live stock and prices in IDR (Rp), filterable by category
- Cart and checkout: stock is deducted inside a database transaction with row locking,
  so two checkouts cannot sell the same stock twice

**Planned** (pages exist as empty placeholders)

- Account Management (admin): add, edit and deactivate users and roles
- Log Management (admin): audit trail of logins, account changes, product changes and checkouts
- Dashboard (cashier): sales metrics
- Product Management (cashier): add and edit products and categories

## Roles and navigation

| Role | Top bar | Home page |
|------|---------|-----------|
| `admin` | Account Management, Log Management | `accounts.php` |
| `cashier` (any other role) | Dashboard, Product Management | `index.php` (cashier screen) |

The top bar lives in `includes/topbar.php`. Clicking the "OWN Cashier" brand returns to the role's home page.
The role is stored in `users.role` and copied into the session at login.

> Navigation only hides links. Access control must be enforced on the server of every page and endpoint
> with `require_login()` or `require_role('admin')` from `bootstrap.php`.

## Pages

| URL | Who | Status |
|-----|-----|--------|
| `login.php` | everyone | done |
| `logout.php` | logged in (POST) | done |
| `index.php` | logged in | done (cashier screen) |
| `actions/do_checkout.php` | logged in (JSON API) | done |
| `dashboard.php` | cashier | placeholder |
| `products.php` | cashier | placeholder |
| `accounts.php` | admin | placeholder |
| `logs.php` | admin | placeholder |

## Requirements

- XAMPP (or any Apache + PHP + MySQL/MariaDB stack)
- PHP 8.1 or newer, with the `pdo_mysql` extension
- MySQL or MariaDB with InnoDB tables

## Setup (XAMPP on Windows)

1. Put the project in `C:\xampp\htdocs\OWN-Cashier-Sytem` and start **Apache** and **MySQL** in the XAMPP control panel.
2. Copy `.env.example` to `.env` in the project root and fill in your database settings:
   ```
   DB_TYPE=mysql
   DB_HOST=localhost
   DB_DATABASE=cashier_system
   DB_USER=root
   DB_PASSWORD=
   DB_CHARSET=utf8mb4
   ```
3. Import `database/cashier_system.sql` in phpMyAdmin (Import tab).
   It creates the database, tables and sample categories and products.
   **It drops and recreates the tables, so do not re-import over real data.**
4. Create the first admin. Generate a password hash:
   ```
   C:\xampp\php\php.exe -r "echo password_hash('your-password', PASSWORD_DEFAULT);"
   ```
   then run in phpMyAdmin:
   ```sql
   INSERT INTO users (username, password, email, role)
   VALUES ('admin', 'PASTE_HASH_HERE', 'admin@example.com', 'admin');
   ```
5. Open `http://localhost/OWN-Cashier-Sytem/login.php`.

If you already have a database from an earlier version, add the role column instead of re-importing:

```sql
ALTER TABLE users
    ADD COLUMN role ENUM('admin','cashier') NOT NULL DEFAULT 'cashier' AFTER email;
```

and create the log table (the same statement as in `database/cashier_system.sql`, without the `DROP`):

```sql
CREATE TABLE IF NOT EXISTS activity_logs (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED    NULL COMMENT 'NULL for guests, failed logins and system events',
    username    VARCHAR(50)     NULL COMMENT 'Snapshot, so the log stays readable if the user is renamed or deleted',
    level       ENUM('info','warning','error') NOT NULL DEFAULT 'info',
    action      VARCHAR(50)     NOT NULL COMMENT 'e.g. login.success, login.failed, logout, user.create, product.update, checkout.success',
    entity_type VARCHAR(50)     NULL COMMENT 'e.g. user, product, category, sale',
    entity_id   VARCHAR(50)     NULL COMMENT 'Products use string ids such as DR001',
    description VARCHAR(500)    NULL,
    details     JSON            NULL COMMENT 'Extra data such as old/new values. Never passwords or tokens',
    ip_address  VARCHAR(45)     NULL COMMENT 'Long enough for IPv6',
    user_agent  VARCHAR(255)    NULL,
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_logs_created (created_at),
    KEY idx_logs_user (user_id, created_at),
    KEY idx_logs_action (action, created_at),
    KEY idx_logs_entity (entity_type, entity_id),
    KEY idx_logs_level (level, created_at),
    CONSTRAINT fk_logs_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Project structure

```
OWN-Cashier-Sytem/
├── index.php              Cashier screen (product grid, cart, checkout)
├── login.php              Login form and handler
├── logout.php             Logout (POST + CSRF)
├── dashboard.php          Placeholder: sales metrics (cashier)
├── products.php           Placeholder: products and categories (cashier)
├── accounts.php           Placeholder: user accounts (admin)
├── logs.php               Placeholder: audit log viewer (admin)
├── bootstrap.php          Session, autoload, DB, redirect(), require_login(), require_role()
├── autoload.php           PSR-4 style autoloader for the App\ namespace
├── script.js              Cashier screen logic (cart, checkout, filters, clock)
├── styles.css             Main stylesheet (retro POS theme)
├── actions/
│   └── do_checkout.php    Checkout endpoint (JSON)
├── includes/
│   └── topbar.php         Shared role-aware top bar
├── assets/
│   └── topbar.css         Top bar styles
├── database/
│   └── cashier_system.sql Schema and sample data
├── App/
│   ├── Models/            BaseModel, Users, Products, Categories, TokenCredential
│   └── Services/          DatabaseConnection, LoadEnv, Authentication
├── .env.example           Template for database settings
└── db.php                 Legacy mysqli connection (to be removed, see TODO.md)
```

## Database tables

| Table | Purpose |
|-------|---------|
| `users` | Accounts: username, password hash, email, role, created_at, updated_at |
| `categories` | Product categories (code and name) |
| `products` | Products with price, stock and category |
| `token_credentials` | "Keep me signed in" tokens |
| `activity_logs` | Append-only audit log (see below) |

### Activity log

`activity_logs` records who did what and when. It is only written to (and purged by a retention policy),
never edited.

| Column | Meaning |
|--------|---------|
| `id` | Auto-increment primary key |
| `user_id` | Acting user; `NULL` for guests, failed logins and system events (`SET NULL` if the user is deleted) |
| `username` | Snapshot of the name at that time, so old entries stay readable |
| `level` | `info`, `warning` or `error` |
| `action` | Event name, e.g. `login.success`, `login.failed`, `logout`, `user.create`, `product.update`, `checkout.success` |
| `entity_type`, `entity_id` | What was affected, e.g. `product` / `DR001` |
| `description` | Short human-readable text |
| `details` | JSON with extra data such as old and new values |
| `ip_address`, `user_agent` | Where the request came from |
| `created_at` | When it happened |

Nothing writes to this table yet; the logger service is listed in [TODO.md](TODO.md).

## How it works

- **Autoloading:** `autoload.php` maps `App\Services\X` to `App/Services/X.php`.
- **Database:** `DatabaseConnection` is a PDO singleton configured from `.env`.
  Call `DatabaseConnection::initialize()` once, then use `getConnectionInstance()`.
- **Auth:** `Authentication::authenticate()` checks the password with `password_verify()` and stores the user
  (without the password) in `$_SESSION['user']`. "Keep me signed in" stores a token in `token_credentials`
  and in cookies.
- **Redirects:** use `redirect('page.php')` from `bootstrap.php`. It builds an absolute path from the project folder.
  Do not use relative `Location:` headers.

## Security notes

- Passwords are only ever stored with `password_hash()`.
- Login and logout forms use CSRF tokens; the session id is regenerated after login.
- Repeated failed logins lock the login form for 60 seconds (per session only; see TODO.md).
- Audit logging is planned (`activity_logs`): passwords, tokens and other secrets must never be written to it.
- Never commit `.env`. Keep it listed in `.gitignore`.
