-- Hero Slider: banners con imagen, título y botón (inspirado web oficial VCF)
CREATE TABLE IF NOT EXISTS hero_slides (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    image_url VARCHAR(500) NOT NULL,
    title VARCHAR(200) NOT NULL,
    button_text VARCHAR(100) DEFAULT 'Read More',
    button_url VARCHAR(500) DEFAULT NULL,
    orden SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
