SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE TABLE IF NOT EXISTS `livre_dor` (
    `id`      INT AUTO_INCREMENT PRIMARY KEY,
    `nom`     VARCHAR(100) NOT NULL DEFAULT '',
    `email`   VARCHAR(255) NOT NULL DEFAULT '',
    `ville`   VARCHAR(100) NOT NULL DEFAULT '',
    `date`    DATETIME NOT NULL,
    `message` TEXT NOT NULL,
    `image`   VARCHAR(500) DEFAULT NULL,
    `ip`      VARCHAR(45) NOT NULL,
    `actif`   TINYINT(1) NOT NULL DEFAULT 1,
    INDEX `idx_livre_dor_ip` (`ip`),
    INDEX `idx_livre_dor_actif` (`actif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `newsletter` (
    `id`    INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `ip`    VARCHAR(45) NOT NULL,
    `date`  DATETIME NOT NULL,
    `actif` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `newsletter_emails` (
    `id`    INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(255) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
