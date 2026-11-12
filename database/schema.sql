-- ===========================================================================
-- Legacy MySQL schema for existing installations only.
--
-- The application now uses MongoDB. Keep this schema only as a reference for
-- source installations being migrated with tools/migrate-mysql-to-mongodb.php.
-- ===========================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

CREATE DATABASE IF NOT EXISTS `swasti_homoeo`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE `swasti_homoeo`;

-- --------------------------------------------------------------- admin user

CREATE TABLE IF NOT EXISTS `users` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`          VARCHAR(120)  NOT NULL,
    `email`         VARCHAR(190)  NOT NULL,
    `password_hash` VARCHAR(255)  NOT NULL,
    `role`          VARCHAR(30)   NOT NULL DEFAULT 'admin',
    `is_active`     TINYINT(1)    NOT NULL DEFAULT 1,
    `last_login_at` DATETIME      DEFAULT NULL,
    `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `users_email_unique` (`email`),
    KEY `users_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------- appointment booking

CREATE TABLE IF NOT EXISTS `appointments` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference_code` VARCHAR(24)  NOT NULL,
    `type`           ENUM('consultation','follow_up') NOT NULL DEFAULT 'consultation',
    `full_name`      VARCHAR(120) NOT NULL,
    `phone`          VARCHAR(25)  NOT NULL,
    `email`          VARCHAR(190) DEFAULT NULL,
    `age`            SMALLINT UNSIGNED DEFAULT NULL,
    `preferred_date` DATE         NOT NULL,
    `preferred_time` VARCHAR(5)   NOT NULL,
    `reason`         VARCHAR(1500) NOT NULL,
    `message`        TEXT         DEFAULT NULL,
    `consent`        TINYINT(1)   NOT NULL DEFAULT 0,
    `status`         ENUM('new','confirmed','completed','cancelled','no_show') NOT NULL DEFAULT 'new',
    `admin_note`     VARCHAR(1000) DEFAULT NULL,
    `spam_score`     TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `ip_address`     VARCHAR(45)  DEFAULT NULL,
    `user_agent`     VARCHAR(255) DEFAULT NULL,
    `source`         VARCHAR(40)  NOT NULL DEFAULT 'website',
    `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `appointments_reference_unique` (`reference_code`),
    KEY `appointments_status_index` (`status`),
    KEY `appointments_type_index` (`type`),
    KEY `appointments_date_index` (`preferred_date`),
    KEY `appointments_created_index` (`created_at`),
    KEY `appointments_phone_index` (`phone`),
    KEY `appointments_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------- contact messages

CREATE TABLE IF NOT EXISTS `messages` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(120) NOT NULL,
    `phone`      VARCHAR(25)  NOT NULL,
    `email`      VARCHAR(190) DEFAULT NULL,
    `subject`    VARCHAR(190) NOT NULL DEFAULT 'General enquiry',
    `message`    TEXT         NOT NULL,
    `status`     ENUM('unread','read','archived') NOT NULL DEFAULT 'unread',
    `spam_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `ip_address` VARCHAR(45)  DEFAULT NULL,
    `user_agent` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `messages_status_index` (`status`),
    KEY `messages_created_index` (`created_at`),
    KEY `messages_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------ FAQ list

CREATE TABLE IF NOT EXISTS `faqs` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `question`     VARCHAR(255) NOT NULL,
    `answer`       TEXT         NOT NULL,
    `category`     VARCHAR(80)  NOT NULL DEFAULT 'General',
    `sort_order`   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `is_published` TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `faqs_published_order_index` (`is_published`, `sort_order`),
    KEY `faqs_category_index` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------- rate limiting / sign-in lockouts

CREATE TABLE IF NOT EXISTS `rate_limits` (
    `bucket_key`  VARCHAR(60)  NOT NULL,
    `hits`        INT UNSIGNED NOT NULL DEFAULT 1,
    `window_start` BIGINT UNSIGNED NOT NULL,
    `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`bucket_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------------------------
-- Optional maintenance task. Run monthly (or delete these lines if you would
-- rather manage cron jobs yourself):
--
-- DELETE FROM `rate_limits`      WHERE `window_start` < UNIX_TIMESTAMP() - 86400;
-- DELETE FROM `messages`         WHERE `status` = 'archived' AND `updated_at` < NOW() - INTERVAL 1 YEAR;
-- DELETE FROM `appointments`     WHERE `status` IN ('cancelled','no_show') AND `updated_at` < NOW() - INTERVAL 2 YEAR;
-- --------------------------------------------------------------------------
