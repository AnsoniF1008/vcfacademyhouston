-- Star of the Month voting: staff nominates 3, community votes (1 per IP per votation)
CREATE TABLE IF NOT EXISTS star_votaciones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mes VARCHAR(20) NOT NULL COMMENT 'e.g. 2026-03',
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    status ENUM('open','closed') NOT NULL DEFAULT 'open',
    winner_nominee_id INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_status_ends (status, ends_at)
);

CREATE TABLE IF NOT EXISTS star_nominees (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    votacion_id INT UNSIGNED NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    categoria VARCHAR(50) NULL,
    foto_url VARCHAR(255) NULL,
    descripcion_logro TEXT NULL,
    orden TINYINT NOT NULL,
    roster_id INT UNSIGNED NULL,
    FOREIGN KEY (votacion_id) REFERENCES star_votaciones(id) ON DELETE CASCADE,
    KEY idx_votacion (votacion_id)
);

CREATE TABLE IF NOT EXISTS star_votes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    votacion_id INT UNSIGNED NOT NULL,
    nominee_id INT UNSIGNED NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    vote_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY one_per_ip (votacion_id, ip_address),
    FOREIGN KEY (votacion_id) REFERENCES star_votaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (nominee_id) REFERENCES star_nominees(id) ON DELETE CASCADE
);
