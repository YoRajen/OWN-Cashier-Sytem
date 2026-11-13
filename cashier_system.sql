-- =====================================================================
-- OWN Cashier System - database schema
-- Import via phpMyAdmin (Import tab) or:  mysql -u root < cashier_system.sql
-- Database name matches .env.example (DB_DATABASE=cashier_system)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS cashier_system
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE cashier_system;

-- ---------------------------------------------------------------------
-- Drop in dependency order (safe to re-run during development)
-- WARNING: this deletes existing data in these tables.
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS token_credentials;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

-- ---------------------------------------------------------------------
-- categories
-- Used by Categories.php: category_id, category_code, category_name
-- ---------------------------------------------------------------------
CREATE TABLE categories (
    category_id   INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    category_code VARCHAR(10)   NOT NULL,
    category_name VARCHAR(100)  NOT NULL,
    PRIMARY KEY (category_id),
    UNIQUE KEY uq_categories_code (category_code),
    UNIQUE KEY uq_categories_name (category_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- products
-- Used by Products.php / do_checkout.php:
--   product_id is a string code such as 'DR001'
-- ---------------------------------------------------------------------
CREATE TABLE products (
    product_id    VARCHAR(20)    NOT NULL,
    product_name  VARCHAR(150)   NOT NULL,
    product_price DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
    product_stock INT UNSIGNED   NOT NULL DEFAULT 0,
    category_id   INT UNSIGNED   NULL,
    PRIMARY KEY (product_id),
    KEY idx_products_category (category_id),
    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id) REFERENCES categories (category_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT chk_products_price CHECK (product_price >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- users
-- Users.php reads $user['id'], 'username', 'password' (password_hash value)
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    username   VARCHAR(50)   NOT NULL,
    password   VARCHAR(255)  NOT NULL COMMENT 'Store password_hash() output only, never plain text',
    email      VARCHAR(255)  NOT NULL,
    role       ENUM("admin","cashier") NOT NULL DEFAULT "cashier",
    created_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- token_credentials (OPTIONAL)
-- Needed only if you use TokenCredential.php ("remember me" tokens).
-- Columns used there: user_id, token, created_at
-- ---------------------------------------------------------------------
CREATE TABLE token_credentials (
    id         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED  NOT NULL,
    token      VARCHAR(255)  NOT NULL,
    created_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_token (token),
    KEY idx_token_user (user_id),
    CONSTRAINT fk_token_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- activity_logs
-- Append-only audit trail shown on logs.php (admin only).
-- The app should only INSERT here (and purge old rows by retention policy),
-- never UPDATE. JSON is native in MySQL 5.7+ and an alias of LONGTEXT in MariaDB.
-- ---------------------------------------------------------------------
CREATE TABLE activity_logs (
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

-- =====================================================================
-- Sample data (edit freely)
-- =====================================================================
INSERT INTO categories (category_code, category_name) VALUES
    ('DR', 'Drinks'),
    ('FD', 'Food'),
    ('SN', 'Snacks');

INSERT INTO products (product_id, product_name, product_price, product_stock, category_id) VALUES
    ('DR001', 'Mineral Water 600ml', 4000,  100, (SELECT category_id FROM categories WHERE category_code = 'DR')),
    ('DR002', 'Iced Tea',            6000,   80, (SELECT category_id FROM categories WHERE category_code = 'DR')),
    ('DR003', 'Coffee Latte',       18000,   50, (SELECT category_id FROM categories WHERE category_code = 'DR')),
    ('FD001', 'Fried Rice',         25000,   30, (SELECT category_id FROM categories WHERE category_code = 'FD')),
    ('FD002', 'Chicken Noodles',    22000,   30, (SELECT category_id FROM categories WHERE category_code = 'FD')),
    ('SN001', 'Potato Chips',       10000,   60, (SELECT category_id FROM categories WHERE category_code = 'SN')),
    ('SN002', 'Chocolate Bar',      12000,   45, (SELECT category_id FROM categories WHERE category_code = 'SN'));


-- Admin user (password is "P@ssw0rd_d3f" hashed with password_hash())
INSERT INTO users (username, password, email, role) VALUES
    ('admin', '$2y$10$3uxjLbLgDiLUtdELRU.vuuE2JAvdRngNyIVgP490S6T9nRgvJ53Qq', 'admin@example.com', 'admin');

-- Dummy cashier user (password is "C@sh13r_d3f" hashed with password_hash())
INSERT INTO users (username, password, email, role) VALUES
    ('cashier', '$2y$10$cHiDDmF5SFLugraRfNtmRuYSIxsAhWg5Bto9AjdIJ8./5GukFK7Sq', 'cashier@example.com', 'cashier');

