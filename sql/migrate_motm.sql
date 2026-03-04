-- Man of the Match - votaciones y votos
-- Run after juegos exists.

CREATE TABLE IF NOT EXISTS motm_votaciones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    juego_id INT UNSIGNED NOT NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    status ENUM('open','closed') NOT NULL DEFAULT 'open',
    winner_nominee_id INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (juego_id) REFERENCES juegos(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS motm_nominees (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    votacion_id INT UNSIGNED NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    foto_url VARCHAR(255) NULL,
    orden TINYINT NOT NULL,
    FOREIGN KEY (votacion_id) REFERENCES motm_votaciones(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS motm_votes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    votacion_id INT UNSIGNED NOT NULL,
    nominee_id INT UNSIGNED NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    fingerprint_hash VARCHAR(64) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY one_per_ip (votacion_id, ip_address),
    KEY idx_votacion_fp (votacion_id, fingerprint_hash),
    FOREIGN KEY (votacion_id) REFERENCES motm_votaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (nominee_id) REFERENCES motm_nominees(id) ON DELETE CASCADE
);
