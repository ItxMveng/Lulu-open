ALTER TABLE users
    ADD COLUMN verification_status ENUM('pending', 'verified', 'rejected') NULL DEFAULT NULL AFTER status;

CREATE TABLE IF NOT EXISTS company_verifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    legal_name VARCHAR(190) NOT NULL,
    registration_number VARCHAR(120) NULL,
    country VARCHAR(120) NULL,
    website VARCHAR(190) NULL,
    sector VARCHAR(190) NULL,
    description TEXT NULL,
    document_path VARCHAR(255) NULL,
    contact_phone VARCHAR(60) NULL,
    admin_note TEXT NULL,
    submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME NULL,
    CONSTRAINT fk_company_verifications_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
