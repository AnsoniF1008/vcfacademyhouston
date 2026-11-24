# VCF Academy Houston — Next.js + Node.js Platform (v2.0)

Plataforma oficial renovada en **Node.js (Next.js 15 App Router + React 19 + TypeScript + Tailwind CSS + SQLite nativo)** para **VCF Academy Houston** (Katy & Houston, TX).

## 🚀 Mejoras Principales sobre la versión PHP anterior

1. **Arquitectura 100% Node.js + TypeScript (`Next.js App Router`)**:
   - Renderizado híbrido ultrarrápido y API Routes tipadas y validadas con **Zod**.
   - **Base de datos SQLite integrada (`node:sqlite` en `data/vcf.db`)** con auto-migración y datos semilla reales (los 18 jugadores U10, estadísticas, radar de habilidades, partidos, torneos, noticias, sedes, canchas y donaciones) listos al instante sin necesidad de instalar XAMPP ni MySQL.
2. **Diseño Visual Valencia CF Mejorado**:
   - Estética oficial **Valencia CF** (`#FF6600` Naranja, `#0A0A0C` Negro Obsidiana, `#D4AF37` Dorado, tipografías `Oswald` y `Montserrat`).
   - **Tarjetas interactivas estilo FIFA Ultimate Team (FUT)** con cálculo automático de `OVR`, filtros instantáneos por línea (`GK`, `DEF`, `MID`, `FWD`), buscador en vivo y **Modal con Gráfico Radar SVG de 6 ejes** (`Pace`, `Shooting`, `Passing`, `Dribbling`, `Defense`, `Physical`).
   - **Laboratorio Táctico Interactivo ("Know Your Role")** con 4 sistemas tácticos (`4-3-3`, `4-2-3-1`, `4-4-2`, `3-5-2`), líneas de pase dinámicas y cambios en vivo desde el banquillo.
   - **Votación Man of the Match (MOTM)** en tiempo real con barras de porcentaje animadas, protección por IP/dispositivo y cuenta regresiva.
3. **Panel de Administración SPA Moderno (`/admin`)**:
   - Sin recargas de página: gestiona Plantilla, Habilidades Radar, Torneos, Partidos, Goleadores, MOTM, Jugador del Mes, Noticias/Blog, Sedes/Canchas, Inscripciones, Mensajes de Contacto, Donaciones y Usuarios con Roles RBAC (`super_admin`, `editor_coach`, `staff_campo`).

## 🛠️ Cómo ejecutar el proyecto

```bash
# 1. Instalar dependencias
npm install

# 2. Iniciar servidor de desarrollo (http://localhost:3000)
npm run dev

# 3. Compilar para producción
npm run build
npm start
```

## 🔐 Acceso al Panel de Administración

- **URL:** `http://localhost:3000/admin` (o `http://localhost:3000/admin/login`)
- **Usuario por defecto:** `admin`
- **Contraseña por defecto:** `password`
