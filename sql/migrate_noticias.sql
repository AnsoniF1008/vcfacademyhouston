-- ============================================================
-- Migración: sistema de noticias estilo blog
-- Fecha: 2026-05-19
-- Tablas: noticias_categorias, noticias
--
-- Ejecutar UNA SOLA VEZ contra la base de datos de producción.
-- ============================================================

-- Tabla de categorías
CREATE TABLE IF NOT EXISTS noticias_categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(60) NOT NULL,
    slug VARCHAR(80) NOT NULL UNIQUE,
    color VARCHAR(7) DEFAULT '#FF6600' COMMENT 'Color hex del badge',
    descripcion TEXT NULL,
    orden INT DEFAULT 0,
    activa TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_activa_orden (activa, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla principal de noticias
CREATE TABLE IF NOT EXISTS noticias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL UNIQUE COMMENT 'URL amigable: match-recap-vs-dynamo-stxcl',
    categoria_id INT NULL,
    resumen TEXT NULL COMMENT 'Extracto corto para previews (max ~200 chars recomendado)',
    contenido MEDIUMTEXT NOT NULL COMMENT 'HTML del cuerpo (sanitizado en backend)',
    imagen_destacada VARCHAR(500) NULL COMMENT 'Ruta relativa: assets/uploads/noticias/foto.jpg',
    imagen_alt VARCHAR(200) NULL,
    autor VARCHAR(100) NULL DEFAULT 'VCF Academy Houston',
    publicado TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0=borrador, 1=publicado',
    destacada TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Si =1 puede aparecer en hero/feature',
    fecha_publicacion DATETIME NULL,
    views INT UNSIGNED NOT NULL DEFAULT 0,
    meta_description VARCHAR(300) NULL COMMENT 'Para SEO',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_publicado_fecha (publicado, fecha_publicacion),
    INDEX idx_categoria (categoria_id),
    INDEX idx_destacada (destacada),

    CONSTRAINT fk_noticias_categoria
        FOREIGN KEY (categoria_id) REFERENCES noticias_categorias (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Categorías iniciales (puedes editar luego desde admin)
-- ============================================================
INSERT INTO noticias_categorias (nombre, slug, color, descripcion, orden) VALUES
    ('Match Recaps',   'match-recaps',   '#FF6600', 'Resúmenes de partidos y resultados',     1),
    ('Tournaments',    'tournaments',    '#00C853', 'Cobertura de torneos y campeonatos',     2),
    ('Academy News',   'academy-news',   '#2196F3', 'Anuncios oficiales y novedades',         3),
    ('Player Stories', 'player-stories', '#FFC107', 'Historias y logros de jugadores',        4),
    ('Training',       'training',       '#9C27B0', 'Metodología y consejos de entrenamiento', 5);

-- ============================================================
-- Noticia de ejemplo (puedes borrarla después)
-- ============================================================
INSERT INTO noticias (
    titulo, slug, categoria_id, resumen, contenido,
    imagen_destacada, imagen_alt, autor, publicado, destacada, fecha_publicacion
) VALUES (
    'Welcome to VCF Academy Houston News',
    'welcome-to-vcf-academy-houston-news',
    (SELECT id FROM noticias_categorias WHERE slug = 'academy-news'),
    'Stay updated with the latest news from VCF Academy Houston: match recaps, tournaments, player achievements and more.',
    '<p>Welcome to the official news section of VCF Academy Houston. Here you will find match recaps, tournament coverage, player stories, and important academy announcements.</p><p>Bookmark this page and follow our journey as we develop the next generation of Houston soccer talent the Valencia CF way.</p>',
    NULL,
    'VCF Academy Houston welcome banner',
    'VCF Academy Houston',
    1,
    1,
    NOW()
);

-- Verificación
SELECT 'Categorías creadas:' AS info, COUNT(*) AS total FROM noticias_categorias
UNION ALL
SELECT 'Noticias creadas:', COUNT(*) FROM noticias;
