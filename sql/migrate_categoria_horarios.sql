-- Categories can have multiple training slots (e.g. Monday 5pm, Wednesday 6pm, Friday 6pm)
-- dia_semana: 1 = Monday, 7 = Sunday
CREATE TABLE IF NOT EXISTS categoria_horarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT UNSIGNED NOT NULL,
    dia_semana TINYINT NOT NULL COMMENT '1=Mon 2=Tue 3=Wed 4=Thu 5=Fri 6=Sat 7=Sun',
    hora TIME NOT NULL,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE,
    KEY idx_categoria (categoria_id)
);
