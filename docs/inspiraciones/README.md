# Carpeta de Inspiraciones - Page Builder SaaS

> **Fecha de creación**: 2026-01-26
> **Propósito**: Repositorio de HTML de referencia para plantillas premium

---

## Descripción

Esta carpeta contiene archivos HTML de inspiración para cada vertical del Ecosistema Jaraba. Los desarrolladores y diseñadores utilizan estos archivos como referencia para:

1. **Identificar patrones de diseño** de landing pages SaaS de alto rendimiento
2. **Extraer estructuras de bloques** para el Page Builder
3. **Documentar mejores prácticas** de conversión y UX

---

## Estructura de Carpetas

```
inspiraciones/
├── README.md                    # Este archivo
├── empleabilidad/               # Vertical Empleabilidad
│   ├── landing-candidatos.html
│   ├── landing-empresas.html
│   ├── casos-exito.html
│   └── pricing.html
├── emprendimiento/              # Vertical Emprendimiento
│   ├── landing-programa.html
│   ├── mentores.html
│   └── recursos.html
├── agroconecta/                 # Vertical AgroConecta
│   ├── marketplace.html
│   ├── productores.html
│   └── trazabilidad.html
├── comercio/                    # Vertical ComercioConecta
│   ├── landing-comercios.html
│   ├── ofertas-flash.html
│   └── fidelizacion.html
├── servicios/                   # Vertical ServiciosConecta
│   ├── landing-profesionales.html
│   ├── booking.html
│   └── reseñas.html
└── genericos/                   # Multi-vertical
    ├── about.html
    ├── contact.html
    ├── faq.html
    └── pricing-comparison.html
```

---

## Formato de Archivos

Cada archivo HTML debe incluir un **comentario de cabecera** con metadatos:

```html
<!--
  ╔══════════════════════════════════════════════════════════════╗
  ║  PLANTILLA DE INSPIRACIÓN - Page Builder Jaraba             ║
  ╠══════════════════════════════════════════════════════════════╣
  ║  Vertical:        Empleabilidad                              ║
  ║  Template Target: emp_landing_main                           ║
  ║  Bloques:         hero_fullscreen, features_grid,            ║
  ║                   testimonials_slider, pricing_cards         ║
  ║  Fuente:          https://ejemplo.com/landing                ║
  ║  Fecha:           2026-01-26                                 ║
  ║  Autor:           [Nombre]                                   ║
  ╚══════════════════════════════════════════════════════════════╝
-->
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>[Nombre de la página] - Inspiración</title>
  <!-- Sin estilos externos, HTML limpio -->
</head>
<body>
  
  <!-- BLOQUE: Hero Fullscreen -->
  <section data-block-type="hero_fullscreen">
    <!-- Contenido del bloque -->
  </section>
  
  <!-- BLOQUE: Features Grid -->
  <section data-block-type="features_grid">
    <!-- Contenido del bloque -->
  </section>
  
</body>
</html>
```

---

## Convenciones

### Atributos de Bloques

Usar `data-block-type` para identificar cada sección:

```html
<section data-block-type="hero_fullscreen">
<section data-block-type="features_grid">
<section data-block-type="pricing_cards">
<section data-block-type="testimonials_slider">
<section data-block-type="cta_banner">
```

### Campos Configurables

Marcar campos que serán editables con `data-field`:

```html
<h1 data-field="title">Título editable</h1>
<p data-field="subtitle">Subtítulo editable</p>
<a data-field="cta_primary" href="#">CTA Primario</a>
```

---

## Fuentes de Inspiración Recomendadas

| Fuente | URL | Especialidad |
|--------|-----|--------------|
| Landingfolio | landingfolio.com | Landing pages SaaS |
| SaaSFrame | saasframe.io | UI patterns SaaS |
| Lapa Ninja | lapa.ninja | Diseños premium |
| Mobbin | mobbin.com | Mobile patterns |
| Dribbble | dribbble.com | Conceptos visuales |

---

## Proceso de Contribución

1. **Identificar** una landing page de referencia
2. **Extraer** el HTML limpio (sin CSS/JS externos)
3. **Anotar** con comentarios de bloque (`data-block-type`)
4. **Marcar** campos editables (`data-field`)
5. **Documentar** con cabecera de metadatos
6. **Commit** al repositorio

---

## Relación con Page Builder

Los archivos de esta carpeta alimentan el desarrollo del **Constructor de Páginas SaaS** definido en los documentos técnicos 162-171:

| Doc | Relación |
|-----|----------|
| 162 | Arquitectura general y plantillas |
| 163 | Bloques premium (Aceternity/Magic UI) |
| 164 | Optimización SEO/GEO |
| 165 | Gap analysis |

---

## Referencias

- [Doc 162 - Page Builder Sistema Completo](file:///z:/home/PED/JarabaImpactPlatformSaaS/docs/tecnicos/20260126d-162_Page_Builder_Sistema_Completo_EDI_v1_Claude.md)
- [Directrices del Proyecto](file:///z:/home/PED/JarabaImpactPlatformSaaS/docs/00_DIRECTRICES_PROYECTO.md)
