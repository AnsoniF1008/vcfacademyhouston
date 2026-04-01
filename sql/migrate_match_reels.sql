-- Match Reels: clips de goles (vídeo, caption, opcional jugador/partido)
CREATE TABLE IF NOT EXISTS match_reels (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    video_url VARCHAR(500) NOT NULL,
    player_id INT UNSIGNED NULL,
    match_id INT UNSIGNED NULL,
    caption TEXT NULL,
    orden SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (player_id) REFERENCES roster(id) ON DELETE SET NULL,
    FOREIGN KEY (match_id) REFERENCES juegos(id) ON DELETE SET NULL,
    INDEX idx_orden_created (orden, created_at)
);
