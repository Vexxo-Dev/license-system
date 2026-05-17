CREATE DATABASE IF NOT EXISTS license_system;
USE license_system;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NULL,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(180) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(32) NOT NULL DEFAULT 'viewer',
    status VARCHAR(32) NOT NULL DEFAULT 'active',
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    industry VARCHAR(120) NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'active',
    total_licenses INT NOT NULL DEFAULT 0,
    active_users INT NOT NULL DEFAULT 0,
    primary_contact_name VARCHAR(120) NOT NULL,
    primary_contact_email VARCHAR(180) NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS licenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    license_key VARCHAR(64) NOT NULL UNIQUE,
    client_id INT NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'active',
    type VARCHAR(32) NOT NULL,
    expires_at DATE NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_licenses_client FOREIGN KEY (client_id) REFERENCES clients(id)
        ON DELETE CASCADE
);

INSERT INTO users (full_name, email, password_hash, role, status, last_login_at, created_at, updated_at)
VALUES
    ('Admin User', 'admin@organization.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', NULL, NOW(), NOW());

INSERT INTO clients (name, industry, status, total_licenses, active_users, primary_contact_name, primary_contact_email, created_at, updated_at)
VALUES
    ('Acme Corp', 'Technology', 'active', 1250, 1180, 'Jane Doe', 'admin@acmecorp.com', NOW(), NOW()),
    ('Global Logistics', 'Transportation', 'active', 850, 420, 'Robert Smith', 'it@globallogistics.com', NOW(), NOW()),
    ('HealthNet Systems', 'Healthcare', 'over_limit', 500, 512, 'Sarah Jenkins', 'sysadmin@healthnet.org', NOW(), NOW());

INSERT INTO licenses (license_key, client_id, status, type, expires_at, created_at, updated_at)
VALUES
    ('LP-ENT-8X9A-V2M4', 1, 'active', 'ENTERPRISE', '2024-12-31', NOW(), NOW()),
    ('LP-PRO-1B2C-3D4E', 2, 'expired', 'PROFESSIONAL', '2023-10-15', NOW(), NOW()),
    ('LP-ENT-9Y8Z-7X6W', 3, 'active', 'ENTERPRISE', '2025-06-30', NOW(), NOW()),
    ('LP-STD-5F6G-7H8J', 1, 'active', 'STANDARD', '2024-03-12', NOW(), NOW()),
    ('LP-PRO-K9L8-M7N6', 2, 'active', 'PROFESSIONAL', '2024-11-05', NOW(), NOW());
