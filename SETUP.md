# VCF Academy Houston - Setup completo

## Estado actual

El proyecto está listo para usar. Se ha creado:

- **Base de datos** `if0_41281527_valenciacf` con todas las tablas
- **Carpetas** `assets/img/` (para hero.jpg) y `assets/uploads/` (fotos Jugador del Mes)
- **Migración** aplicada para `contact_messages` e `inscripciones`

## Cómo probar

1. **Inicia XAMPP** (Apache + MySQL)
2. Si la base de datos no existe: en phpMyAdmin crea la BD `if0_41281527_valenciacf` e importa `sql/schema.sql`.
3. Abre: http://localhost/valencia/
4. **Admin**: http://localhost/valencia/admin/
   - Usuario: `admin`
   - Contraseña: `password`
   - Cambia la contraseña tras el primer login

## Zona horaria

La aplicación usa **America/Chicago** (CST/CDT, Houston) para las fechas y horas de partidos y para el countdown. Está configurado en `config/database.php`; si el servidor está en otra zona, las horas de los partidos se muestran y calculan correctamente en hora de Houston.

## Hero image (opcional)

Coloca una imagen en `assets/img/hero.jpg` para el fondo del hero. Si no existe, se usa un gradiente.

## Escudo VCF (Latest Results)

Para mostrar el escudo oficial de VCF Houston en la tabla "Latest Results", guarda el logo en **`assets/img/vcf-crest.png`** o **`assets/img/vcf-crest.svg`**. Si no existe ninguno, se muestra un icono genérico de escudo.
