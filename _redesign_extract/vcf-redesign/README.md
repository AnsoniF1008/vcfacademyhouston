# VCF Academy Houston — Rediseño Completo
## Inspirado en valenciacf.com

---

## ESTRUCTURA DE ARCHIVOS

```
vcf-redesign/
│
├── index.php                  ← Página principal (reemplaza tu index.php)
├── calendar.php               ← Calendario (reemplaza tu calendar.php)
├── contact.php                ← Contacto (reemplaza tu contact.php)
├── recaudaciones.php          ← Support (reemplaza tu recaudaciones.php)
│
├── includes/
│   ├── header.php             ← Header compartido (nuevo archivo)
│   └── footer.php             ← Footer compartido (nuevo archivo)
│
└── assets/
    └── css/
        └── vcf-style.css      ← CSS global (nuevo archivo)
```

---

## PASOS DE INSTALACIÓN (FTP/cPanel)

### 1. Sube el CSS
- Sube `assets/css/vcf-style.css` a tu servidor en esa misma ruta
- URL final: `https://vcfacademyhouston.com/assets/css/vcf-style.css`

### 2. Crea la carpeta includes
- Crea la carpeta `/includes/` en la raíz de tu sitio
- Sube `includes/header.php` y `includes/footer.php`

### 3. Reemplaza las páginas
**MUY IMPORTANTE**: Antes de reemplazar cada archivo, guarda una copia de respaldo.

- Sube `index.php` → reemplaza tu `index.php` actual
- Sube `calendar.php` → reemplaza tu `calendar.php` actual
- Sube `contact.php` → reemplaza tu `contact.php` actual
- Sube `recaudaciones.php` → reemplaza tu `recaudaciones.php` actual

### 4. Verifica las rutas
En `includes/header.php` y `footer.php`, la variable `$base_url = '/'` asume
que el sitio está en la raíz. Si está en un subdirectorio, ajústala.

---

## INTEGRAR TU LÓGICA PHP EXISTENTE

Cada archivo PHP tiene comentarios marcados como:
```php
// ── Mantén aquí tu lógica PHP existente ──
```

En esos bloques, copia tus queries de base de datos originales.
El rediseño solo cambia el HTML/CSS de salida, NO la lógica de datos.

### Variables que necesitas pasar a las secciones:

**index.php — Próximo partido:**
```php
$next_match = [
  'date_label' => 'SAT APR 11',
  'time'       => '4:00 PM',
  'opponent'   => 'AHFC 13B PREMIER 1C',
  'venue'      => 'ZUBE PARK FIELD 1',
  'datetime'   => '2026-04-11T16:00:00',  // Para el countdown
  'info_url'   => '#tournaments',
];
```

**index.php — Stats de temporada:**
```php
$stats = ['W'=>1, 'L'=>2, 'D'=>2, 'GF'=>13, 'GA'=>17, 'PTS'=>5];
```

**index.php — Jugadores:** Array `$players` con los campos:
`num, initials, name, pos, pos_short, photo, apps, goals, assists, motm, clean_sheets, pace, shoot, drib, def, phys, pass`

**index.php — Partidos:** Array `$matches` con:
`date, time, opponent, location, status (finished/upcoming), score_home, score_away, cal_id`

---

## PERSONALIZACIÓN RÁPIDA

### Cambiar colores
En `vcf-style.css`, línea ~10, las variables CSS:
```css
--vcf-orange: #FF6B00;   /* Color primario VCF */
--vcf-dark:   #111111;   /* Fondo oscuro */
```

### Cambiar la imagen del hero
En `index.php`, busca:
```php
style="background-image: url('assets/uploads/hero-69a9bf305146f.jpg')"
```
Reemplaza con la URL de tu imagen hero.

### Añadir más categorías de equipo
En `index.php`, la sección `#roster` tiene un array `$players`.
Para agregar B11 o B15, duplica la sección con un nuevo filtro.

---

## PÁGINAS PENDIENTES (para un siguiente paso)
- `privacy.php` — Política de privacidad (rediseño menor)
- `admin/` — Panel de administración (no tocado)

---

## SOPORTE
Si algo no se ve bien, verifica:
1. ¿Se cargó `vcf-style.css`? (Abre DevTools → Network)
2. ¿Existen `includes/header.php` y `includes/footer.php`?
3. ¿La variable `$base_url` apunta a la raíz correcta?

---
*VCF Academy Houston © 2026 — Diseño inspirado en valenciacf.com*
