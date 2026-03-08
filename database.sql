CREATE DATABASE IF NOT EXISTS `bbshoots` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `bbshoots`;

CREATE TABLE IF NOT EXISTS `clients` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(150) NOT NULL,
  `email`      VARCHAR(200) NOT NULL UNIQUE,
  `phone`      VARCHAR(20)  NOT NULL,
  `password`   VARCHAR(255) NOT NULL,
  `status`     ENUM('active','suspended','removed') DEFAULT 'active',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `bookings` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `booking_ref`    VARCHAR(20) NOT NULL UNIQUE,
  `client_id`      INT UNSIGNED NULL,
  `client_name`    VARCHAR(150) NOT NULL,
  `email`          VARCHAR(200) NOT NULL,
  `phone`          VARCHAR(20)  NOT NULL,
  `event_type`     VARCHAR(100) NOT NULL,
  `event_date`     DATE NOT NULL,
  `location`       VARCHAR(255) NOT NULL,
  `package`        ENUM('Basic','Standard','Premium') NOT NULL,
  `notes`          TEXT,
  `status`         ENUM('pending','confirmed','completed','cancelled','rejected') DEFAULT 'pending',
  `payment_status` ENUM('pending','paid','refunded') DEFAULT 'pending',
  `created_at`     DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `projects` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `booking_id`  INT UNSIGNED NOT NULL UNIQUE,
  `status`      ENUM('scheduled','shooting','editing','review','completed') DEFAULT 'scheduled',
  `video_urls`  TEXT DEFAULT '[]',
  `admin_notes` TEXT,
  `updated_at`  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `notifications` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `type`       VARCHAR(50) NOT NULL,
  `message`    TEXT NOT NULL,
  `is_read`    TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(150) NOT NULL,
  `email`      VARCHAR(200) NOT NULL,
  `message`    TEXT NOT NULL,
  `is_read`    TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO `clients` (`name`,`email`,`phone`,`password`) VALUES
('Priya Sharma','priya@demo.com','9876543210','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

INSERT INTO `bookings` (`booking_ref`,`client_id`,`client_name`,`email`,`phone`,`event_type`,`event_date`,`location`,`package`,`notes`,`status`) VALUES
('BK-2025-001',1,'Priya Sharma','priya@demo.com','9876543210','Wedding','2025-04-15','Taj Gateway, Visakhapatnam','Premium','Beach-side ceremony','confirmed'),
('BK-2025-002',1,'Priya Sharma','priya@demo.com','9876543210','Birthday Party','2025-05-01','Rushikonda Beach','Standard','Surprise party','pending');

INSERT INTO `projects` (`booking_id`,`status`,`video_urls`,`admin_notes`) VALUES
(1,'editing','[]','Great footage, editing in progress'),
(2,'scheduled','[]','');

INSERT INTO `notifications` (`type`,`message`) VALUES
('new_booking','New booking BK-2025-001 from Priya Sharma — Wedding'),
('new_booking','New booking BK-2025-002 from Priya Sharma — Birthday Party');