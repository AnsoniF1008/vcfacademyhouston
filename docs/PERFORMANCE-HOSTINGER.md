# Mejorar puntuación PageSpeed en Hostinger

## 1. Activar caché en el panel (muy importante)

- Entra en **hPanel de Hostinger** → tu sitio → **Avanzado** o **Rendimiento**.
- Activa **LiteSpeed Cache** o **Cache** si está disponible.
- Así se aplican mejor las cabeceras de caché y se reduce "Use efficient cache lifetimes" y "Time to interact".

## 2. Imágenes del hero (LCP)

- **Las nuevas imágenes** que subas desde Admin → Hero Slider se **redimensionan y comprimen solas** (máx. 1920 px de ancho, calidad 82) para mejorar LCP y "Avoid enormous network payloads".
- Las imágenes que ya estaban subidas no se tocan: si quieres optimizarlas, reemplázalas subiendo de nuevo la misma slide desde el admin.
- Formato WebP reduce aún más el peso si tu PHP tiene soporte GD para WebP.

## 3. Lo que ya hace el sitio

- Preload de la primera imagen del hero.
- Imagen LCP explícita en la primera slide (`fetchpriority="high"`).
- JS con `defer`, FontAwesome asíncrono, Swiper con `defer`.
- Caché larga en `.htaccess` para estáticos (1 año).
- Lazy loading en imágenes por debajo del pliegue.

Si tras activar caché en Hostinger y optimizar las imágenes del hero la puntuación sigue igual, puede deberse a latencia del servidor (TTFB); en ese caso valorar un plan con mejor rendimiento o CDN.
