-- Para InfinityFree: crea antes la BD en el panel, selecciónala en phpMyAdmin e importa este archivo.
-- No incluye CREATE DATABASE ni USE (la BD ya está seleccionada).

-- Admin users for panel login (default password: password - CHANGE AFTER FIRST LOGIN)
CREATE TABLE admin_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO admin_users (username, password_hash) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Sedes
CREATE TABLE sedes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    direccion TEXT NOT NULL,
    mapa_general_url TEXT NULL,
    nota_acceso TEXT NULL
);

INSERT INTO sedes (nombre, direccion, mapa_general_url) VALUES
('Houston Sports Complex', '1234 Soccer Way, Houston, TX 77001', 'https://maps.google.com/?q=Houston+Sports+Complex'),
('Bear Creek Park', '3535 War Memorial Dr, Houston, TX 77084', 'https://maps.google.com/?q=Bear+Creek+Park+Houston');

-- Canchas
CREATE TABLE canchas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sede_id INT UNSIGNED NOT NULL,
    numero_cancha VARCHAR(100) NOT NULL,
    sobrenombre VARCHAR(100) NULL,
    indicaciones_extra TEXT NULL,
    mapa_url VARCHAR(500) NULL,
    FOREIGN KEY (sede_id) REFERENCES sedes(id) ON DELETE CASCADE
);

INSERT INTO canchas (sede_id, numero_cancha, sobrenombre, indicaciones_extra, mapa_url) VALUES
(1, 'Field #3 (U10)', NULL, 'Near the main parking lot', 'https://www.google.com/maps?q=29.7604,-95.3698'),
(1, 'Field #5 (U12)', 'The Mestalla Pitch', 'South side, next to playground', NULL),
(2, 'Field #5 (U8-U10)', 'The Mestalla Pitch', 'Near the playground area.', NULL),
(2, 'Field #12 (U12-U14)', NULL, 'Behind the soccer office building.', NULL);

-- Torneos (cabecera) y Juegos (partidos, N por torneo)
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
    goles_vcf INT NULL,
    goles_rival INT NULL,
    rival_logo_url VARCHAR(500) NULL,
    FOREIGN KEY (torneo_id) REFERENCES torneos_info(id) ON DELETE CASCADE,
    FOREIGN KEY (sede_id) REFERENCES sedes(id) ON DELETE SET NULL
);

INSERT INTO torneos_info (nombre_torneo, temporada) VALUES
('Houston Spring Cup 2026', 'Spring 2026'),
('STXCL League U10', '2026');

INSERT INTO juegos (torneo_id, fecha, hora, rival, cancha, sede_id, estado, categoria) VALUES
(1, '2026-02-21', '10:00', 'North Shore FC', 'Field 7', 1, 'proximo', 'U10'),
(1, '2026-02-22', '14:00', 'Challenge United', 'Field 2', 1, 'proximo', 'U10'),
(2, '2026-03-01', '12:00', 'Houston Wolves SC', 'Field 9', 2, 'proximo', 'U10');

-- Jugador del mes
CREATE TABLE jugador_mes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    categoria VARCHAR(20) NOT NULL,
    foto_url VARCHAR(255) DEFAULT NULL,
    descripcion_logro TEXT NOT NULL,
    mes VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO jugador_mes (nombre, categoria, descripcion_logro, mes) VALUES
('Alex Rodriguez', 'U10', 'Congratulations to Alex for showing incredible leadership and technical growth during this month\'s sessions in the U10 division. ¡Amunt!', 'March 2026');

-- Categorias
CREATE TABLE categorias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(20) NOT NULL,
    horarios_entrenamiento TEXT
);

INSERT INTO categorias (nombre, horarios_entrenamiento) VALUES
('U6', 'Tue/Thu 4:00 PM - 5:00 PM'),
('U8', 'Mon/Wed 4:30 PM - 5:30 PM'),
('U10', 'Tue/Thu 5:00 PM - 6:30 PM'),
('U12', 'Mon/Wed 5:30 PM - 7:00 PM');

-- Roster (plantilla por categoría)
CREATE TABLE roster (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    dorsal INT NULL,
    posicion ENUM('Portero','Defensa','Mediocampista','Delantero') NULL,
    foto_url VARCHAR(255) NULL,
    categoria_id INT UNSIGNED NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
);

-- Man of the Match
CREATE TABLE motm_votaciones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    juego_id INT UNSIGNED NOT NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    status ENUM('open','closed') NOT NULL DEFAULT 'open',
    winner_nominee_id INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (juego_id) REFERENCES juegos(id) ON DELETE CASCADE
);

CREATE TABLE motm_nominees (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    votacion_id INT UNSIGNED NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    foto_url VARCHAR(255) NULL,
    orden TINYINT NOT NULL,
    FOREIGN KEY (votacion_id) REFERENCES motm_votaciones(id) ON DELETE CASCADE
);

CREATE TABLE motm_votes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    votacion_id INT UNSIGNED NOT NULL,
    nominee_id INT UNSIGNED NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    fingerprint_hash VARCHAR(64) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY one_per_ip (votacion_id, ip_address),
    KEY idx_votacion_fp (votacion_id, fingerprint_hash),
    FOREIGN KEY (votacion_id) REFERENCES motm_votaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (nominee_id) REFERENCES motm_nominees(id) ON DELETE CASCADE
);

-- Contact form
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inscripciones
CREATE TABLE IF NOT EXISTS inscripciones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    child_name VARCHAR(100) NOT NULL,
    child_category VARCHAR(20) NOT NULL,
    message TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
