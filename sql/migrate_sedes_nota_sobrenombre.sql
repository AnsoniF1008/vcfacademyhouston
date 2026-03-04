-- Optional: add nota_acceso (sede) and sobrenombre (cancha) for Mestalla-style UI
-- Run once. If column already exists, ignore the error.
USE if0_41281527_valenciacf;

ALTER TABLE sedes ADD COLUMN nota_acceso TEXT NULL AFTER mapa_general_url;
ALTER TABLE canchas ADD COLUMN sobrenombre VARCHAR(100) NULL AFTER numero_cancha;
