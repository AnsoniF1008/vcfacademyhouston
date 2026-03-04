-- Roster (plantilla) - jugadores por categoría
-- Run after categorias exists.

CREATE TABLE IF NOT EXISTS roster (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    dorsal INT NULL COMMENT 'Número de camiseta',
    posicion ENUM('Portero','Defensa','Mediocampista','Delantero') NULL,
    foto_url VARCHAR(255) NULL,
    categoria_id INT UNSIGNED NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
);
