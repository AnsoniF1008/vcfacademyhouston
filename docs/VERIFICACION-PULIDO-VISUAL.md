# Verificación: pulido visual (brief “academia de élite”)

Estado actual del proyecto frente a las propuestas de mejora.

---

## 1. Star of the Month (Dashboard / Jugador del mes)

| Propuesta | Estado | Detalle |
|----------|--------|---------|
| **Vista previa en vivo** | No implementado | En Admin no hay una tarjeta que se actualice en tiempo real a la derecha mientras se rellena el formulario. Solo se muestra el “Current Star” después de guardar. |
| **Efecto “cromo”** | Parcial | La tarjeta pública tiene borde naranja y sombra, pero la foto es rectangular (aspect-ratio 1). No hay bordes redondeados tipo cromo ni degradado naranja alrededor de la foto. |
| **Jersey Number (dorsal)** | No implementado | La tabla `jugador_mes` no tiene campo `dorsal`. No hay campo en el formulario ni número grande detrás del nombre en la tarjeta pública. |

**Archivos relacionados:** `admin/jugador-mes.php`, `index.php` (bloque Star), `assets/css/style.css` (`.vcf-star-card`).

---

## 2. Know Your Role (campo interactivo)

| Propuesta | Estado | Detalle |
|----------|--------|---------|
| **Campo en perspectiva 3D** | No implementado | El campo es plano (2D), con fondo en gradiente verde y líneas. No hay imagen ni CSS en perspectiva tipo “pizarra inclinada”. |
| **Iconos de rol** | No implementado | Los 11 puntos son círculos naranjas con número (1–11). No hay iconos (guante portero, escudo defensa, rayo delantero, etc.). |
| **Micro-interacciones** | Parcial | Al hacer clic se muestra el popover. No hay expansión del punto ni oscurecimiento del campo para resaltar el texto. |
| **Pulsación (glow) más suave** | Implementado | Los puntos tienen animación `vcf-pulse` y sombra naranja. |

**Archivos relacionados:** `index.php` (sección Know Your Role), `assets/css/style.css` (`.vcf-pitch`, `.vcf-position-point`), `assets/js/main.js` (popover).

---

## 3. Hero Slider y tipografía

| Propuesta | Estado | Detalle |
|----------|--------|---------|
| **Tipografía “impacto”** | Parcial | Se usa **Oswald** para títulos (`--font-heading`). El brief pedía Bebas Neue u Oswald; Oswald está aplicado. No está Barlow Condensed. |
| **Botón con glow naranja** | No implementado | El botón del hero (READ NOW / Read More) no tiene efecto de brillo (box-shadow naranja). |
| **Filtro alto contraste en imágenes** | No implementado | Las imágenes del slider no llevan `filter` (p. ej. `contrast()` o `saturate()`) para resaltar blanco/negro/naranja. |
| **Gradiente inferior oscuro** | Implementado | `.vcf-hero-slide-overlay` usa `linear-gradient(0deg, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0) 50%)` para oscurecer la parte inferior y mejorar la legibilidad del texto. |

**Archivos relacionados:** `assets/css/style.css` (`:root` fuentes, `.vcf-hero-slide-*`), `index.php` (hero con Swiper).

---

## 4. Badge “Official VCF Methodology”

| Propuesta | Estado | Detalle |
|----------|--------|---------|
| **Sello / badge de certificación** | No implementado | No existe un elemento tipo “Official VCF Methodology” en ninguna esquina de la web. |

---

## Resumen

- **Implementado o parcial:** Tipografía Oswald, gradiente oscuro en el hero, pulsación en puntos del pitch, popover al clic.
- **Pendiente:** Vista previa en vivo y dorsal en Star of the Month; efecto cromo en la tarjeta; campo 3D e iconos en Know Your Role; micro-interacción (expandir punto + oscurecer campo); botón hero con glow; filtro contraste en slider; badge “Official VCF Methodology”.

Si quieres, el siguiente paso puede ser implementar solo algunas de estas mejoras (por ejemplo: dorsal + vista previa en Star of the Month, y botón con glow en el hero).
