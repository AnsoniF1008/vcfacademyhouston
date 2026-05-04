-- Match reels: aggregate views/likes + per-visitor like rows (fingerprint hash).
-- Ejecutar una vez en phpMyAdmin / CLI antes de usar api/reel-stats.php.

ALTER TABLE match_reels
  ADD COLUMN view_count INT UNSIGNED NOT NULL DEFAULT 0;

ALTER TABLE match_reels
  ADD COLUMN like_count INT UNSIGNED NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS match_reel_like_visitors (
    reel_id INT UNSIGNED NOT NULL,
    fingerprint_hash CHAR(64) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (reel_id, fingerprint_hash),
    KEY idx_fp (fingerprint_hash),
    CONSTRAINT fk_reel_like_reel FOREIGN KEY (reel_id) REFERENCES match_reels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
