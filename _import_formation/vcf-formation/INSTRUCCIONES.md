# Formation Viewer Enhanced — Instrucciones de instalación

## Archivo incluido
- `formation-viewer.php` — El bloque completo (HTML + CSS + JS)

---

## INSTALACIÓN EN 3 PASOS

### Paso 1 — Abre tu index.php actual
Busca la sección que contiene el "Know Your Role" / Formation Viewer actual.
Busca algo como:
```
<!-- ══ FORMATION VIEWER ══ -->
```
o
```
<section id="formation">
```

### Paso 2 — Reemplaza el bloque completo
Borra todo el bloque de la sección de formación (desde `<section id="formation">` 
hasta su `</section>` de cierre) y pega el contenido de `formation-viewer.php`.

### Paso 3 — Agrega el CSS
El CSS está incluido dentro del archivo en un bloque `<style>`.
Para mejor organización, puedes moverlo a tu `assets/css/vcf-style.css`.
Busca el comentario:
```
/* ── CSS DEL FORMATION VIEWER ── */
```
y copia todo hasta el siguiente comentario de cierre.

---

## PERSONALIZACIÓN

### Cambiar jugadores asignados
Al inicio del archivo, en el array `$formation_players`, 
actualiza los datos de cada posición:

```php
$formation_players = [
  'GK'  => ['name' => 'Matías Astorino', 'initials' => 'MA', 'num' => '23', 'photo' => 'roster-xxx.jpg'],
  'RB'  => ['name' => 'Tu Jugador',      'initials' => 'TJ', 'num' => '6',  'photo' => 'roster-yyy.jpg'],
  // etc.
];
```

Las fotos deben estar en `assets/uploads/` — usa el mismo nombre de archivo
que ya tienes para las fotos del roster.

### Cambiar stats de temporada
En el HTML, busca la sección `<!-- Team season stats -->` y actualiza los números
directamente, o conéctalos a tu base de datos con PHP igual que en el resto del sitio.

### Cambiar descripciones de posiciones
En el JavaScript, en el objeto `FORMATIONS`, cada posición tiene:
- `name`: Nombre del rol
- `desc`: Descripción táctica
- `attrs`: Array de 4 atributos clave

---

## NOTAS TÉCNICAS
- El CSS usa las variables `--font-display` y `--font-body` de tu `vcf-style.css`
- Los colores usan las variables `--vcf-orange`, `--vcf-dark2`, etc.
- Compatible con el CSS del rediseño que ya está instalado
- Responsive: en móvil el panel se mueve debajo de la cancha
