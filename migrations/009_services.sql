-- Services proposés par les prestataires (rôle client, profil de type "services").
-- Un prestataire peut lister plusieurs prestations avec tarif et délai ; elles
-- s'affichent sur son profil public et peuvent être commandées/contactées.
CREATE TABLE IF NOT EXISTS services (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(160) NOT NULL,
    category VARCHAR(150) NULL,
    description TEXT NULL,
    price DECIMAL(10,2) NULL,                                   -- stocké en EUR (comme le reste), affiché via money()
    price_type ENUM('from','fixed','hourly','daily') NOT NULL DEFAULT 'from',
    delivery_days INT UNSIGNED NULL,                            -- délai de livraison en jours
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_services_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_services_user (user_id),
    INDEX idx_services_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
