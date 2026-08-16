ALTER TABLE applications
    ADD COLUMN match_score TINYINT UNSIGNED NULL AFTER status,
    ADD COLUMN analysis JSON NULL AFTER match_score,
    ADD COLUMN interview_at DATETIME NULL AFTER analysis,
    ADD COLUMN interview_location VARCHAR(255) NULL AFTER interview_at,
    ADD COLUMN interview_note TEXT NULL AFTER interview_location;

ALTER TABLE applications
    ADD INDEX idx_applications_score (entreprise_id, match_score);
