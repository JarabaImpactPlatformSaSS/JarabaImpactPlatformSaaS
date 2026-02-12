# Iconos SVG y Landing Pages de Verticales

**Fecha:** 2026-01-26  
**Tipo:** Aprendizaje / Patrones Frontend  
**Módulos:** `ecosistema_jaraba_core`, `ecosistema_jaraba_theme`

---

## Resumen

Implementación de sistema de iconos SVG y landing pages de verticales con layout full-width, partículas animadas y template Twig reutilizable.

---

## 1. Sistema de Iconos SVG

### 1.1 Estructura de Directorios

```
web/modules/custom/ecosistema_jaraba_core/images/icons/
├── actions/        # Acciones (star, heart, check, etc.)
├── ai/             # Iconos IA (brain, screening, robot)
├── analytics/      # Métricas (dashboard, chart-line, gauge)
├── business/       # Negocio (target, canvas, grant, institution)
├── ui/             # UI general (search, user, building, package)
└── verticals/      # Verticales (rocket, briefcase, leaf)
```

### 1.2 Convención de Nombres

| Tipo | Formato | Ejemplo |
|------|---------|---------|
| **Normal** | `{nombre}.svg` | `target.svg` |
| **Duotone** | `{nombre}-duotone.svg` | `target-duotone.svg` |

### 1.3 Función Twig `jaraba_icon()`

```twig
{{ jaraba_icon('business', 'target', {
  variant: 'duotone',
  color: 'impulse',
  size: '32px'
}) }}
```

**Parámetros:**
- `category`: Subcarpeta de iconos
- `name`: Nombre del icono (sin extensión)
- `options.variant`: `'normal'` o `'duotone'`
- `options.color`: Variable CSS (`impulse`, `innovation`, `corporate`)
- `options.size`: Tamaño en CSS

### 1.4 Iconos Creados (2026-01-26)

| Icono | Categoría | Propósito |
|-------|-----------|-----------|
| `interview` | business | Dos caras dialogando (entrevistas) |
| `cv-optimized` | business | Documento con estrella |
| `tracking-board` | business | Dashboard con cards |
| `canvas` | business | BMC 9 bloques |
| `institution` | business | Edificio + corazón (ONG/Admin) |
| `grant` | business | Documento con euro |
| `ods` | business | ODS sostenibilidad |
| `ecosystem` | business | Nodos conectados |
| `screening` | ai | Persona + IA + check |
| `dashboard` | analytics | Panel con gráficos |
| `storefront` | ui | Tienda |
| `package` | ui | Caja/pedido |

---

## 2. Landing Pages de Verticales

### 2.1 Rutas Implementadas

| Ruta | Vertical | Color |
|------|----------|-------|
| `/empleo` | Empleabilidad (Candidatos) | `innovation` |
| `/talento` | Empleabilidad (Empresas) | `innovation` |
| `/emprender` | Emprendimiento | `impulse` |
| `/comercio` | Comercio Local | `success` |
| `/instituciones` | B2G / ONGs | `corporate` |

### 2.2 Arquitectura

```
Controlador (PHP)
└── VerticalLandingController.php
    ├── empleo() → buildLanding($config)
    ├── talento() → buildLanding($config)
    ├── emprender() → buildLanding($config)
    ├── comercio() → buildLanding($config)
    └── instituciones() → buildLanding($config)

Template Twig
└── partials/vertical-landing-content.html.twig
    ├── Hero con partículas (hero-landing)
    ├── Beneficios con iconos SVG
    └── CTAs primario/secundario

Template de Página
└── page--vertical-landing.html.twig
    └── Layout full-width sin sidebar
```

### 2.3 Registro en hook_theme()

```php
// ecosistema_jaraba_core.module
'vertical_landing_content' => [
  'variables' => [
    'vertical_data' => [],
  ],
  'template' => 'partials/vertical-landing-content',
  'path' => $theme_path . '/templates',
],
```

### 2.4 Partículas Animadas

Las landing pages usan las mismas clases CSS que la homepage:

```twig
<section class="vertical-landing__hero hero-landing">
  <div class="hero-landing__bg">
    <div class="hero-landing__gradient"></div>
    <div class="hero-landing__particles"></div>
  </div>
  <!-- Contenido -->
</section>
```

**CSS en `_hero-landing.scss`:**
- `particleFloat`: Animación 20s de orbes flotantes
- `gradientPulse`: Animación 8s de gradiente radial

---

## 3. Copy de /instituciones (B2G)

### 3.1 Propuesta de Valor

> "Tu plataforma de desarrollo local. Formación, empleo y emprendimiento con tu marca. Impulsado por IA."

### 3.2 Beneficios

| Beneficio | Descripción |
|-----------|-------------|
| **Tu marca, tu plataforma** | Identidad corporativa propia: logo, colores, dominio personalizado |
| **Formación y empleo** | Conecta talento local con empresas de tu territorio |
| **Copiloto IA incluido** | Asistencia inteligente para candidatos y emprendedores |
| **Métricas de impacto** | Dashboards ODS y reportes para justificar subvenciones |

---

## 4. Reglas y Patrones

### 4.1 ❌ Nunca usar emojis

```twig
{# ❌ INCORRECTO #}
<span>🚀 {{ title }}</span>

{# ✅ CORRECTO #}
<span class="icon">{{ jaraba_icon('verticals', 'rocket') }}</span>
<span>{{ title }}</span>
```

### 4.2 Siempre crear versión duotone

Los iconos duotone usan `var(--icon-fill)` para colores semitransparentes:

```svg
<rect fill="var(--icon-fill, rgba(0,169,165,0.15))" stroke="currentColor"/>
```

### 4.3 Template único con datos variables

El controlador pasa configuración, el template es genérico:

```php
return [
  '#theme' => 'vertical_landing_content',
  '#vertical_data' => [
    'key' => 'empleo',
    'title' => $this->t('...'),
    'benefits' => [...],
  ],
];
```

---

## 5. Comandos Útiles

```bash
# Limpiar cache después de crear iconos
lando drush cr

# Verificar que el archivo SVG existe
ls web/modules/custom/ecosistema_jaraba_core/images/icons/business/

# Compilar SCSS si se añaden estilos
npx sass scss/main.scss:css/style.css --style=compressed
```

---

## Referencias

- [Arquitectura Frontend Extensible](./2026-01-25_arquitectura_frontend_extensible.md)
- [Auditoría Frontend Hallazgos](./2026-01-26_auditoria_frontend_hallazgos.md)
