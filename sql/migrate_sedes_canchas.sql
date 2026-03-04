-- Migration: Sedes + Canchas (parks with specific fields)
-- Run this ONLY on an existing database that still has the old sedes table
-- (with link_google_maps). New installs use schema.sql which already has
-- sedes + canchas. Skip this file if you get "Unknown column 'link_google_maps'".

USE if0_41281527_valenciacf;

-- Add new column and migrate data (skip if sedes already has mapa_general_url)
ALTER TABLE sedes ADD COLUMN mapa_general_url TEXT NULL AFTER direccion;
UPDATE sedes SET mapa_general_url = link_google_maps WHERE link_google_maps IS NOT NULL AND link_google_maps != '';
ALTER TABLE sedes MODIFY direccion TEXT;
ALTER TABLE sedes DROP COLUMN link_google_maps;

-- Canchas: specific fields within each sede (supports Google Maps pin / lat,long URL)
CREATE TABLE IF NOT EXISTS canchas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sede_id INT UNSIGNED NOT NULL,
    numero_cancha VARCHAR(100) NOT NULL,
    indicaciones_extra TEXT NULL,
    mapa_url VARCHAR(500) NULL,
    FOREIGN KEY (sede_id) REFERENCES sedes(id) ON DELETE CASCADE
);
