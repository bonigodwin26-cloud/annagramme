CREATE DATABASE IF NOT EXISTS anagramme_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE anagramme_db;

CREATE TABLE IF NOT EXISTS verifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(128) NOT NULL,
    chaine_1 TEXT NOT NULL,
    chaine_2 TEXT NOT NULL,
    resultat TINYINT(1) NOT NULL,
    date_verification TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_verifications_session_date (session_id, date_verification)
) ENGINE=InnoDB;
