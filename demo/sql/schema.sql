-- Fire Safe Australia — PHP/MySQL schema (GoDaddy cPanel compatible)
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  parent_id   INT NULL,
  name        VARCHAR(191) NOT NULL,
  slug        VARCHAR(191) NOT NULL UNIQUE,
  description TEXT NULL,
  image       VARCHAR(255) NULL,
  position    INT NOT NULL DEFAULT 0,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (parent_id),
  CONSTRAINT fk_cat_parent FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NULL,
  sku         VARCHAR(191) NULL UNIQUE,
  title       VARCHAR(255) NOT NULL,
  slug        VARCHAR(191) NOT NULL UNIQUE,
  description MEDIUMTEXT NULL,
  price       DECIMAL(10,2) NULL,
  images      TEXT NULL,                       -- JSON array of URL strings
  status      ENUM('draft','published') NOT NULL DEFAULT 'draft',
  featured    TINYINT(1) NOT NULL DEFAULT 0,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (category_id),
  INDEX (status),
  CONSTRAINT fk_prod_cat FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_variants (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  sku        VARCHAR(191) NOT NULL UNIQUE,
  label      VARCHAR(255) NOT NULL,
  price      DECIMAL(10,2) NULL,
  position   INT NOT NULL DEFAULT 0,
  INDEX (product_id),
  CONSTRAINT fk_var_prod FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blog_posts (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  title        VARCHAR(255) NOT NULL,
  slug         VARCHAR(191) NOT NULL UNIQUE,
  excerpt      TEXT NULL,
  content      MEDIUMTEXT NULL,
  cover_image  VARCHAR(255) NULL,
  category     VARCHAR(100) NULL,
  status       ENUM('draft','published') NOT NULL DEFAULT 'draft',
  author       VARCHAR(191) NULL,
  published_at DATETIME NULL,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enquiries (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(191) NOT NULL,
  email      VARCHAR(191) NOT NULL,
  phone      VARCHAR(50) NULL,
  items      TEXT NULL,                         -- JSON array
  message    TEXT NULL,
  status     ENUM('new','responded','closed') NOT NULL DEFAULT 'new',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  email         VARCHAR(191) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  name          VARCHAR(191) NULL,
  role          ENUM('admin','customer') NOT NULL DEFAULT 'customer',
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  `key`      VARCHAR(100) PRIMARY KEY,
  value      TEXT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS logs (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  level      ENUM('info','warn','error') NOT NULL DEFAULT 'info',
  category   VARCHAR(50) NOT NULL,
  message    TEXT NOT NULL,
  meta       TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (created_at),
  INDEX (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
