-- Performance indexes round 2: covers MOTM, roster lookups, scorers, hero slider
-- Designed to be re-run safely (the runner script swallows "Duplicate key name" errors)

-- juego_goles: aggregations by juego and per-player totals
ALTER TABLE juego_goles ADD INDEX idx_juego_goles_juego (juego_id);
ALTER TABLE juego_goles ADD INDEX idx_juego_goles_roster (roster_id);

-- motm_votaciones: status + ends_at scan
ALTER TABLE motm_votaciones ADD INDEX idx_motm_votaciones_status_ends (status, ends_at);

-- motm_votes: counts grouped by votacion_id, fraud check by IP
ALTER TABLE motm_votes ADD INDEX idx_motm_votes_votacion (votacion_id);
ALTER TABLE motm_votes ADD INDEX idx_motm_votes_votacion_nominee (votacion_id, nominee_id);
ALTER TABLE motm_votes ADD INDEX idx_motm_votes_votacion_ip (votacion_id, ip_address);

-- motm_nominees: lookup nominees per votacion ordered by orden
ALTER TABLE motm_nominees ADD INDEX idx_motm_nominees_votacion (votacion_id, orden);

-- jugador_mes: order by created_at when picking the latest star of the month
ALTER TABLE jugador_mes ADD INDEX idx_jugador_mes_created (created_at);

-- hero_slides: filter active and order
ALTER TABLE hero_slides ADD INDEX idx_hero_slides_active_order (activo, orden, id);

-- roster: filter active by category
ALTER TABLE roster ADD INDEX idx_roster_categoria_activo (categoria_id, activo);
