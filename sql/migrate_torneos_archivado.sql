-- ============================================================
-- Migración: agregar campo `archivado` a torneos_info
-- Fecha: 2026-05-19
-- Propósito: permitir archivar manualmente torneos desde el admin
--
-- Ejecutar UNA SOLA VEZ contra la base de datos de producción.
-- ============================================================

ALTER TABLE torneos_info
    ADD COLUMN archivado TINYINT(1) NOT NULL DEFAULT 0
        COMMENT 'Si está en 1, el torneo se muestra en la sección "pasados" sin importar las fechas de sus juegos.'
        AFTER temporada;

CREATE INDEX idx_torneos_info_archivado ON torneos_info (archivado);
