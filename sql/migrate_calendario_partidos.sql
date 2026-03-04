-- Calendario de partidos: hora, oponente, ubicacion_mapa_url, estado 'live'
-- Ejecutar en la BD existente (phpMyAdmin o: mysql -u user -p database < migrate_calendario_partidos.sql)

ALTER TABLE torneos
    ADD COLUMN hora TIME NULL AFTER fecha,
    ADD COLUMN oponente VARCHAR(150) NULL AFTER hora,
    ADD COLUMN ubicacion_mapa_url VARCHAR(500) NULL AFTER ubicacion;

ALTER TABLE torneos
    MODIFY COLUMN estado ENUM('proximo', 'live', 'finalizado') NOT NULL DEFAULT 'proximo';
