-- Create the database if it doesn't exist, using the name defined in the .env file.
CREATE DATABASE IF NOT EXISTS shortener_db;

-- Switch to the new database
USE shortener_db;

-- Table to store the link mappings
CREATE TABLE IF NOT EXISTS links (
    -- Primary key, auto-incremented
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,

    -- The unique, short code used in the URL (e.g., "abc12")
    short_code VARCHAR(10) NOT NULL,

    -- The full, original URL being shortened. VARCHAR(2048) handles typical maximum URL length.
    long_url VARCHAR(2048) NOT NULL,

    -- The timestamp when the link was created
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- CRITICAL: Enforce uniqueness on the short_code to prevent two different long URLs
    -- from accidentally sharing the same short code.
    UNIQUE KEY (short_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
