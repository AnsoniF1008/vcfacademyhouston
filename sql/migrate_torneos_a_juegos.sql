-- Torneos uno-a-muchos: torneos_info + juegos; migrar desde torneos y eliminar torneos.
-- Ejecutar en la BD existente (phpMyAdmin o mysql < migrate_torneos_a_juegos.sql).

-- 1. Crear tablas nuevas
CREATE TABLE torneos_info (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre_torneo VARCHAR(150) NOT NULL,
    temporada VARCHAR(50) NULL
);

CREATE TABLE juegos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    torneo_id INT UNSIGNED NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NULL,
    rival VARCHAR(150) NULL,
    cancha VARCHAR(100) NULL,
    sede_id INT UNSIGNED NULL,
    ubicacion_mapa_url VARCHAR(500) NULL,
    estado ENUM('proximo', 'live', 'finalizado') NOT NULL DEFAULT 'proximo',
    categoria VARCHAR(20) NULL,
    FOREIGN KEY (torneo_id) REFERENCES torneos_info(id) ON DELETE CASCADE,
    FOREIGN KEY (sede_id) REFERENCES sedes(id) ON DELETE SET NULL
);

-- 2. Migrar: un torneos_info por nombre distinto (temporada = primera categoria vista)
INSERT INTO torneos_info (nombre_torneo, temporada)
SELECT nombre, MIN(categoria) FROM torneos GROUP BY nombre;

-- 3. Migrar cada partido a juegos
INSERT INTO juegos (torneo_id, fecha, hora, rival, cancha, sede_id, ubicacion_mapa_url, estado, categoria)
SELECT tinfo.id, t.fecha, t.hora, t.oponente, t.ubicacion, NULL, t.ubicacion_mapa_url, t.estado, t.categoria
FROM torneos t
JOIN torneos_info tinfo ON tinfo.nombre_torneo = t.nombre;

-- 4. Eliminar tabla antigua
DROP TABLE torneos;
