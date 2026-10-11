-- Hospital Nurses Leave Register System
-- MySQL 5.7+ / MariaDB 10.4+  |  Import via phpMyAdmin or: mysql -u root < schema.sql

CREATE DATABASE IF NOT EXISTS nurses_leave_system
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE nurses_leave_system;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS leave_applications;
DROP TABLE IF EXISTS leave_balances;
DROP TABLE IF EXISTS nurses;
DROP TABLE IF EXISTS matrons;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin', 'matron', 'nurse') NOT NULL,
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE nurses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL UNIQUE,
  employee_no VARCHAR(32) NOT NULL UNIQUE,
  full_name VARCHAR(150) NOT NULL,
  nic VARCHAR(20) NOT NULL,
  gender ENUM('female', 'male', 'other') NOT NULL DEFAULT 'female',
  phone VARCHAR(20) NOT NULL,
  ward VARCHAR(80) NOT NULL,
  department VARCHAR(80) NOT NULL,
  designation VARCHAR(80) NOT NULL DEFAULT 'Staff Nurse',
  address VARCHAR(255) NULL,
  date_of_joining DATE NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_nurses_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE matrons (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL UNIQUE,
  full_name VARCHAR(150) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  department VARCHAR(80) NOT NULL DEFAULT 'Nursing Administration',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_matrons_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE leave_balances (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nurse_id INT UNSIGNED NOT NULL,
  year SMALLINT UNSIGNED NOT NULL,
  annual_entitled SMALLINT UNSIGNED NOT NULL DEFAULT 21,
  annual_used SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  casual_entitled SMALLINT UNSIGNED NOT NULL DEFAULT 7,
  casual_used SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  medical_entitled SMALLINT UNSIGNED NOT NULL DEFAULT 14,
  medical_used SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  UNIQUE KEY uq_nurse_year (nurse_id, year),
  CONSTRAINT fk_balances_nurse FOREIGN KEY (nurse_id) REFERENCES nurses (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE leave_applications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nurse_id INT UNSIGNED NOT NULL,
  leave_type ENUM('annual', 'casual', 'medical') NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  days SMALLINT UNSIGNED NOT NULL,
  reason TEXT NOT NULL,
  medical_note VARCHAR(255) NULL,
  status ENUM('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
  reviewed_by INT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  review_comment VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_leave_status (status, created_at),
  KEY idx_leave_nurse (nurse_id, start_date),
  CONSTRAINT fk_leave_nurse FOREIGN KEY (nurse_id) REFERENCES nurses (id) ON DELETE CASCADE,
  CONSTRAINT fk_leave_reviewer FOREIGN KEY (reviewed_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB;
