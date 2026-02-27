# 📂 Recursos de Casos de Éxito — Guía de Uso

> **Tipo:** Guía de recursos multimedia
> **Versión:** 1.0.0
> **Fecha:** 2026-02-27
> **Estado:** Vigente ✅

---

## 📑 Tabla de Contenidos

1. [Propósito](#1-propósito)
2. [Estructura de Carpetas](#2-estructura-de-carpetas)
3. [Cómo Añadir un Nuevo Caso](#3-cómo-añadir-un-nuevo-caso)
4. [Especificaciones de Archivos](#4-especificaciones-de-archivos)
5. [Flujo de Trabajo](#5-flujo-de-trabajo)
6. [Naming Conventions](#6-naming-conventions)
7. [Permisos y Legal](#7-permisos-y-legal)

---

## 1. Propósito

Esta carpeta contiene los **recursos fuente** (fotos, vídeos, datos, briefs) que alimentan los casos de éxito publicados en los 4 puntos de presencia del ecosistema:

| Meta-sitio | URL | Audiencia | Framing |
|------------|-----|-----------|---------|
| **pepejaraba.com** | `/casos-de-exito` | Profesionales | Historia personal de transformación |
| **jarabaimpact.com** | `/impacto` | Empresas/Instituciones | Reto → Solución → Resultado (ROI) |
| **plataformadeecosistemas.es** | `/impacto` | Instituciones públicas | Evidencia institucional + KPIs agregados |
| **jaraba-saas.lndo.site** | `/instituciones` | Administraciones | Testimoniales de programas públicos |

> **Principio:** Un solo repositorio de datos fuente, 4 framings distintos generados automáticamente.

---

## 2. Estructura de Carpetas

```
docs/assets/casos-de-exito/
├── _README.md                    ← Este archivo (instrucciones)
├── _plantilla-caso.md            ← Plantilla para rellenar por cada caso
├── _metricas-globales.md         ← Fuente única de verdad para KPIs del ecosistema
│
├── marcela-calabia/              ← Subdirectorio por persona
│   ├── brief.md                  ← Datos del caso (usa _plantilla-caso.md)
│   ├── foto-perfil.jpg           ← Foto profesional
│   ├── video-entrevista.mp4      ← Vídeo testimonial (o YouTube ID en .txt)
│   ├── logo-empresa.svg          ← Logo del negocio (si aplica)
│   └── recursos-extra/           ← Material adicional
│
├── angel-martinez/
│   ├── brief.md
│   └── ...
│
├── luis-miguel-criado/
│   ├── brief.md
│   └── ...
│
└── [nuevo-caso]/                 ← Copiar y renombrar para nuevos casos
    ├── brief.md
    └── ...
```

---

## 3. Cómo Añadir un Nuevo Caso

### Paso 1: Crear subdirectorio
Crea una carpeta con el nombre de la persona en formato `nombre-apellido` (minúsculas, guiones):

```
docs/assets/casos-de-exito/ana-garcia-lopez/
```

### Paso 2: Copiar la plantilla
Copia `_plantilla-caso.md` al nuevo directorio como `brief.md`:

```
cp _plantilla-caso.md ana-garcia-lopez/brief.md
```

### Paso 3: Rellenar el brief
Abre `brief.md` y rellena todos los campos. Los campos marcados con ★ son **obligatorios**, el resto son opcionales pero mejoran significativamente la calidad del caso.

### Paso 4: Copiar recursos multimedia
Copia las fotos y vídeos al subdirectorio siguiendo las convenciones de naming (sección 6).

### Paso 5: Notificar al equipo técnico
Una vez completo, el equipo técnico (Antigravity) leerá el brief y generará:
- Content entity en Drupal con 4 view modes
- Templates Twig premium para cada meta-sitio
- SCSS con design tokens del ecosistema
- Seeders para poblar las páginas

---

## 4. Especificaciones de Archivos

### 4.1 Fotos

| Tipo | Formato | Dimensiones | Peso máx | Naming |
|------|---------|-------------|----------|--------|
| **Perfil** ★ | JPG/PNG | 800×800 mín (cuadrada) | 2MB | `foto-perfil.jpg` |
| **Antes** | JPG/PNG | 1200×800 mín | 3MB | `foto-antes.jpg` |
| **Después** | JPG/PNG | 1200×800 mín | 3MB | `foto-despues.jpg` |
| **Proyecto** | JPG/PNG | 1200×800 mín | 3MB | `foto-proyecto.jpg` |

> ⚠️ La foto de perfil es **obligatoria** — sin rostro no hay conexión emocional con el visitante.

### 4.2 Vídeos

| Opción | Formato | Duración ideal | Peso máx | Naming |
|--------|---------|----------------|----------|--------|
| **Archivo local** | MP4 (H.264) | 2-5 min | 100MB | `video-entrevista.mp4` |
| **YouTube** | Texto con ID | — | — | `video-youtube-id.txt` |
| **Vimeo** | Texto con ID | — | — | `video-vimeo-id.txt` |
| **Clip corto** | MP4 (H.264) | 15-30s | 20MB | `video-clip-corto.mp4` |

> Para vídeos > 100MB, es mejor subirlos a YouTube y copiar solo el ID.

### 4.3 Logos

| Formato preferido | Alternativa | Naming |
|-------------------|-------------|--------|
| SVG (vectorial) | PNG con fondo transparente (400×400 mín) | `logo-empresa.svg` o `logo-empresa.png` |

### 4.4 Capturas

| Tipo | Formato | Naming |
|------|---------|--------|
| Captura web antes | PNG | `captura-web-antes.png` |
| Captura web después | PNG | `captura-web-despues.png` |
| Infografía | PNG/SVG | `infografia-resultados.png` |

---

## 5. Flujo de Trabajo

```
┌─────────────────────────────────────────────────────────────┐
│  1. RECOPILAR                                                │
│     Pepe recopila fotos, vídeos, datos de cada persona       │
│                           ↓                                  │
│  2. DOCUMENTAR                                               │
│     Pepe rellena brief.md con la plantilla                   │
│                           ↓                                  │
│  3. COPIAR                                                   │
│     Pepe copia todo a docs/assets/casos-de-exito/{nombre}/   │
│                           ↓                                  │
│  4. PROCESAR                                                 │
│     Antigravity lee briefs y procesa multimedia               │
│                           ↓                                  │
│  5. IMPLEMENTAR                                              │
│     Antigravity crea entity + templates + SCSS + seeders     │
│                           ↓                                  │
│  6. PROPAGAR                                                 │
│     Contenido se propaga automáticamente a 4 meta-sitios     │
│                           ↓                                  │
│  7. VERIFICAR                                                │
│     Verificación en navegador de los 4 sitios                │
└─────────────────────────────────────────────────────────────┘
```

---

## 6. Naming Conventions

| Elemento | Convención | Ejemplo |
|----------|------------|---------|
| **Directorio** | `nombre-apellido` (kebab-case) | `marcela-calabia/` |
| **Brief** | Siempre `brief.md` | `brief.md` |
| **Fotos** | `foto-{tipo}.{ext}` | `foto-perfil.jpg` |
| **Vídeos** | `video-{tipo}.{ext}` | `video-entrevista.mp4` |
| **IDs plataforma** | `video-{plataforma}-id.txt` | `video-youtube-id.txt` |
| **Logos** | `logo-empresa.{ext}` | `logo-empresa.svg` |
| **Extras** | Descriptivo en kebab-case | `captura-web-antes.png` |

---

## 7. Permisos y Legal

> [!CAUTION]
> **Antes de publicar cualquier caso**, asegúrate de que:
> - [ ] La persona ha dado **consentimiento explícito** para uso de imagen/nombre
> - [ ] Ha **revisado y aprobado** el texto del caso
> - [ ] Si hay vídeo: tiene permiso de **difusión pública**
> - [ ] Cumple con **RGPD/LOPD-GDD** (datos personales mínimos necesarios)

Documenta el estado de permisos en la sección correspondiente del `brief.md`.

---

## Referencias Cruzadas

- [Auditoría de Consistencia](../../analisis/2026-02-27_Auditoria_Consistencia_Casos_Exito_Metasitios_v1.md) — Estado actual de los 4 sitios
- [Directrices del Proyecto](../../00_DIRECTRICES_PROYECTO.md) — Convenciones generales
- [Arquitectura Theming](../../arquitectura/2026-02-05_arquitectura_theming_saas_master.md) — Patrón SCSS Federated Design Tokens
