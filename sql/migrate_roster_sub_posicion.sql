-- Sub-posición / rol específico (ej. Defensa → Lateral derecho, Delantero → Extremo izquierdo)
ALTER TABLE roster ADD COLUMN sub_posicion VARCHAR(80) NULL AFTER posicion;
