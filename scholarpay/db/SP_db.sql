-- ScholarPay Database Schema
-- Run this in phpMyAdmin or via MySQL CLI

CREATE DATABASE IF NOT EXISTS scholarpay CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE scholarpay;

-- Users table (admins and students)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'student') NOT NULL DEFAULT 'student',
    stellar_address VARCHAR(60) NULL COMMENT 'Stellar wallet public key',
    institution VARCHAR(200) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Scholarships table
CREATE TABLE IF NOT EXISTS scholarships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT NULL,
    total_amount DECIMAL(15,7) NOT NULL COMMENT 'Amount in USDC (7 decimals)',
    remaining_amount DECIMAL(15,7) NOT NULL,
    token_address VARCHAR(60) NULL COMMENT 'USDC contract address on Stellar',
    status ENUM('active', 'inactive', 'depleted') NOT NULL DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Disbursements table (the audit trail)
CREATE TABLE IF NOT EXISTS disbursements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scholarship_id INT NOT NULL,
    student_id INT NOT NULL,
    admin_id INT NOT NULL,
    amount DECIMAL(15,7) NOT NULL COMMENT 'USDC amount disbursed',
    purpose VARCHAR(200) NOT NULL,
    stellar_tx_hash VARCHAR(100) NULL COMMENT 'On-chain transaction hash',
    ledger_sequence BIGINT NULL COMMENT 'Stellar ledger sequence number',
    status ENUM('pending', 'confirmed', 'failed') NOT NULL DEFAULT 'pending',
    disbursed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (scholarship_id) REFERENCES scholarships(id),
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (admin_id) REFERENCES users(id)
);

-- Student wallets whitelist
CREATE TABLE IF NOT EXISTS whitelisted_wallets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    stellar_address VARCHAR(60) NOT NULL,
    verified TINYINT(1) DEFAULT 0,
    whitelisted_by INT NOT NULL,
    whitelisted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (whitelisted_by) REFERENCES users(id)
);

-- Activity log
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Seed: Demo admin account (password: Admin@123)
INSERT INTO users (name, email, password, role, institution) VALUES
('Admin User', 'admin@scholarpay.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'ScholarPay Foundation')
ON DUPLICATE KEY UPDATE id=id;

-- Seed: Demo student account (password: Student@123)
INSERT INTO users (name, email, password, role, stellar_address, institution) VALUES
('Maria Santos', 'student@scholarpay.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'GBSZ2NFPQZJRRLSQ6TA5D5GNJNKBQVQVMWFP6LRGWUQZGFHK7ZFDXQ', 'University of the Philippines')
ON DUPLICATE KEY UPDATE id=id;
