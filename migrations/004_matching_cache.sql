CREATE TABLE IF NOT EXISTS matching_cache (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    query_hash VARCHAR(64) NOT NULL,
    score TINYINT UNSIGNED NOT NULL,
    summary VARCHAR(255) NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_matching_cache_user_query (user_id, query_hash),
    CONSTRAINT fk_matching_cache_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;