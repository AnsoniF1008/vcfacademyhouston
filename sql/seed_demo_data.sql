-- VCF Academy Houston - Demo data for full page testing
-- Run after schema.sql and seed_roster_players.sql (and migrate_player_card.sql).
-- Adds: past/future games with scores, MOTM (closed + open), roster stats & skills.

USE if0_41281527_valenciacf;

-- ---------- 1. Extra games: past (with scores) + future ----------
-- sede_id NULL so seed works even if sedes table is empty
INSERT INTO juegos (torneo_id, fecha, hora, rival, cancha, sede_id, estado, categoria, goles_vcf, goles_rival) VALUES
(1, DATE_SUB(CURDATE(), INTERVAL 21 DAY), '10:00', 'Baytown United', 'Field 3', NULL, 'finalizado', 'U10', 3, 1),
(1, DATE_SUB(CURDATE(), INTERVAL 14 DAY), '14:00', 'Katy FC', 'Field 5', NULL, 'finalizado', 'U10', 2, 2),
(1, DATE_SUB(CURDATE(), INTERVAL 7 DAY),  '11:00', 'Rangers FC', 'Field 7', NULL, 'finalizado', 'U10', 4, 0),
(1, DATE_ADD(CURDATE(), INTERVAL 7 DAY),  '10:00', 'North Shore FC', 'Field 2', NULL, 'proximo', 'U10', NULL, NULL),
(1, DATE_ADD(CURDATE(), INTERVAL 14 DAY), '12:00', 'Challenge United', 'Field 9', NULL, 'proximo', 'U10', NULL, NULL);

-- ---------- 2. MOTM closed (for the game 7 days ago) ----------
SET @juego_pasado = (SELECT id FROM juegos WHERE fecha = DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND estado = 'finalizado' ORDER BY id DESC LIMIT 1);
INSERT INTO motm_votaciones (juego_id, starts_at, ends_at, status)
SELECT @juego_pasado, DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY), 'closed'
FROM (SELECT 1) t WHERE @juego_pasado IS NOT NULL LIMIT 1;
SET @vot_closed = LAST_INSERT_ID();

INSERT INTO motm_nominees (votacion_id, nombre, foto_url, orden, roster_id)
SELECT @vot_closed, CONCAT(r.nombre, ' ', r.apellido), r.foto_url, 1, r.id FROM roster r WHERE r.id = 1 LIMIT 1;
INSERT INTO motm_nominees (votacion_id, nombre, foto_url, orden, roster_id)
SELECT @vot_closed, CONCAT(r.nombre, ' ', r.apellido), r.foto_url, 2, r.id FROM roster r WHERE r.id = 2 LIMIT 1;
INSERT INTO motm_nominees (votacion_id, nombre, foto_url, orden, roster_id)
SELECT @vot_closed, CONCAT(r.nombre, ' ', r.apellido), r.foto_url, 3, r.id FROM roster r WHERE r.id = 3 LIMIT 1;

SET @winner_nom = (SELECT id FROM motm_nominees WHERE votacion_id = @vot_closed ORDER BY orden LIMIT 1);
UPDATE motm_votaciones SET winner_nominee_id = @winner_nom WHERE id = @vot_closed;

-- Optional: one sample vote so winner has votes
INSERT IGNORE INTO motm_votes (votacion_id, nominee_id, ip_address)
SELECT @vot_closed, @winner_nom, '127.0.0.1' FROM (SELECT 1) t WHERE @vot_closed > 0 AND @winner_nom IS NOT NULL LIMIT 1;

-- ---------- 3. MOTM open (for next future game) ----------
SET @juego_futuro = (SELECT id FROM juegos WHERE fecha > CURDATE() AND estado = 'proximo' ORDER BY fecha ASC, hora ASC LIMIT 1);
INSERT INTO motm_votaciones (juego_id, starts_at, ends_at, status)
SELECT @juego_futuro, NOW(), DATE_ADD(NOW(), INTERVAL 2 HOUR), 'open'
FROM (SELECT 1) t WHERE @juego_futuro IS NOT NULL LIMIT 1;
SET @vot_open = LAST_INSERT_ID();

INSERT INTO motm_nominees (votacion_id, nombre, foto_url, orden, roster_id)
SELECT @vot_open, CONCAT(r.nombre, ' ', r.apellido), r.foto_url, 1, r.id FROM roster r WHERE r.id = 4 LIMIT 1;
INSERT INTO motm_nominees (votacion_id, nombre, foto_url, orden, roster_id)
SELECT @vot_open, CONCAT(r.nombre, ' ', r.apellido), r.foto_url, 2, r.id FROM roster r WHERE r.id = 5 LIMIT 1;
INSERT INTO motm_nominees (votacion_id, nombre, foto_url, orden, roster_id)
SELECT @vot_open, CONCAT(r.nombre, ' ', r.apellido), r.foto_url, 3, r.id FROM roster r WHERE r.id = 6 LIMIT 1;

-- ---------- 4. Roster stats (so player card modal shows numbers) ----------
INSERT INTO roster_estadisticas (roster_id, partidos_jugados, goles, asistencias, motm, clean_sheets) VALUES
(1, 10, 2, 3, 0, 0),
(2, 10, 0, 1, 0, 0),
(3, 9, 5, 2, 0, 0),
(4, 10, 1, 4, 0, 0),
(5, 8, 0, 0, 0, 2),
(6, 10, 3, 1, 0, 0),
(7, 7, 0, 2, 0, 0),
(8, 10, 2, 2, 0, 0)
ON DUPLICATE KEY UPDATE 
  partidos_jugados = VALUES(partidos_jugados),
  goles = VALUES(goles),
  asistencias = VALUES(asistencias),
  clean_sheets = VALUES(clean_sheets);

-- ---------- 5. Roster skills (radar chart in modal) ----------
INSERT INTO roster_habilidades (roster_id, pace, shooting, passing, dribbling, defense, physical) VALUES
(1, 7, 6, 8, 7, 4, 6),
(2, 5, 3, 6, 5, 8, 6),
(3, 8, 9, 6, 8, 3, 7),
(4, 6, 5, 9, 6, 5, 5),
(5, 4, 2, 5, 4, 9, 7),
(6, 7, 8, 6, 7, 4, 6),
(7, 5, 4, 7, 5, 8, 5),
(8, 6, 6, 7, 6, 6, 6)
ON DUPLICATE KEY UPDATE 
  pace = VALUES(pace), shooting = VALUES(shooting), passing = VALUES(passing),
  dribbling = VALUES(dribbling), defense = VALUES(defense), physical = VALUES(physical);
