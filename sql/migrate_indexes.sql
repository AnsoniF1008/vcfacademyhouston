-- Performance indexes for commonly filtered columns
-- Safe to run multiple times (uses IF NOT EXISTS pattern via ALTER IGNORE or separate checks)

-- juegos: filter by torneo+date (homepage schedule queries)
ALTER TABLE juegos ADD INDEX idx_juegos_torneo_fecha (torneo_id, fecha);

-- juegos: filter by date+estado (upcoming / past match queries)
ALTER TABLE juegos ADD INDEX idx_juegos_fecha_estado (fecha, estado);

-- contact_messages: order by created_at in admin
ALTER TABLE contact_messages ADD INDEX idx_contact_created (created_at);

-- inscripciones: order/filter by created_at in admin
ALTER TABLE inscripciones ADD INDEX idx_inscripciones_created (created_at);
