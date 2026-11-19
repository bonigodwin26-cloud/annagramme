CREATE DATABASE IF NOT EXISTS anagramme_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE anagramme_db;

CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pseudo VARCHAR(100) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS verifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    session_id VARCHAR(128) NOT NULL,
    chaine_1 TEXT NOT NULL,
    chaine_2 TEXT NOT NULL,
    resultat TINYINT(1) NOT NULL,
    date_verification TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_verifications_utilisateur_date (user_id, date_verification),
    INDEX idx_verifications_session_date (session_id, date_verification),
    CONSTRAINT fk_verifications_utilisateur
        FOREIGN KEY (user_id) REFERENCES utilisateurs(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;
