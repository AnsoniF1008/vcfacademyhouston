-- Goleadores por partido: quién anotó y asistió. GF y fichas se calculan con consultas agregadas.
CREATE TABLE IF NOT EXISTS juego_goles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    juego_id INT UNSIGNED NOT NULL,
    roster_id INT UNSIGNED NOT NULL,
    goles TINYINT UNSIGNED NOT NULL DEFAULT 0,
    asistencias TINYINT UNSIGNED NOT NULL DEFAULT 0,
    UNIQUE KEY uniq_juego_roster (juego_id, roster_id),
    FOREIGN KEY (juego_id) REFERENCES juegos(id) ON DELETE CASCADE,
    FOREIGN KEY (roster_id) REFERENCES roster(id) ON DELETE CASCADE
);
