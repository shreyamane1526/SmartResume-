-- Create database if not exists
CREATE DATABASE IF NOT EXISTS smartresume;

-- Use the database
USE smartresume;

-- Create contact_messages table
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(50),
    company VARCHAR(100),
    subject VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    newsletter TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
