# Aprendizaje #78: Bloques Verticales — De Planos a Diseñados (55 Templates)

**Fecha:** 2026-02-14
**Sesión:** Bloques Verticales — De Planos a Diseñados
**Módulo:** jaraba_page_builder
**Archivos afectados:** 60 (1 nuevo + 55 reescritos + 3 modificados + 1 compilado)
**Reglas nuevas:** PB-VERTICAL-001, PB-VERTICAL-002

---

## Contexto

Los 55 bloques verticales (5 verticales × 11 tipos) del Page Builder eran HTML genérico idéntico sin CSS específico. Cada template Twig contenía la misma estructura HTML independientemente de si era un hero, pricing, FAQ o galería. No existía SCSS para los bloques de sección. Se diseñaron todos con HTML semántico único por tipo y un SCSS completo unificado.

---

## Lecciones Aprendidas

### 1. CSS Custom Properties cascade para esquemas de color por vertical

**Situación:** Cada vertical (agroconecta, comercioconecta, serviciosconecta, empleabilidad, emprendimiento) necesita su propio esquema de color, aplicado a los 11 tipos de bloque sin duplicar reglas CSS.

**Aprendizaje:** Usar `--pb-accent` como CSS custom property en el modifier BEM del vertical permite que todos los estilos base y tipo-específicos hereden el color automáticamente. Con `color-mix()` se generan variantes (hover, background, border) desde una sola variable:
```scss
.pb-section--agroconecta { --pb-accent: #556B2F; }
.pb-section--comercioconecta { --pb-accent: #FF8C42; }
// Los estilos base usan: background: color-mix(in srgb, var(--pb-accent) 10%, white);
```

**Regla PB-VERTICAL-002:** Los esquemas de color por vertical se definen via CSS custom property `--pb-accent` en el modifier BEM del vertical. Nunca duplicar reglas de color por vertical.

### 2. Un SCSS único para 11 layouts + 5 colores via BEM modifiers

**Situación:** Se necesitaban estilos para 55 combinaciones (5 verticales × 11 tipos). Crear 55 archivos SCSS individuales o incluso 11 parciales era innecesariamente fragmentado.

**Aprendizaje:** Un solo archivo `_pb-sections.scss` (570 LOC) cubre toda la matriz usando BEM modifiers:
- Base: `.pb-section` (container, title, subtitle, media, cta)
- Vertical modifier: `.pb-section--agroconecta` (solo define `--pb-accent`)
- Type modifier: `.pb-section--hero` (layout específico: split, gradient, etc.)
- Cascade: `.pb-section--hero.pb-section--agroconecta` hereda ambos sin reglas adicionales

**Regla:** Usar BEM modifiers ortogonales (vertical + tipo) para multiplicar estilos sin explosión combinatoria.

### 3. Templates Twig con `|default()` extensivo para preview sin datos

**Situación:** Los templates se renderizan tanto en el canvas del editor (con datos del JSON del bloque) como en preview (sin datos reales). Sin `|default()`, los previews mostraban errores o contenido vacío.

**Aprendizaje:** Todo campo en los templates Twig debe tener un valor por defecto sensato via `|default('Texto de ejemplo')`. Esto permite que el preview muestre una representación visual completa del bloque sin requerir datos reales:
```twig
<h1 class="pb-section__title">{{ title|default('Título del Bloque') }}</h1>
<p class="pb-section__subtitle">{{ subtitle|default('Descripción breve del contenido') }}</p>
```

**Regla:** Todo template de bloque vertical DEBE usar `|default()` en cada variable para garantizar preview funcional.

### 4. `renderTemplatePreview()` debe renderizar el Twig real

**Situación:** El método `renderTemplatePreview()` en PHP generaba un placeholder HTML genérico para todos los tipos de bloque, ignorando los templates Twig recién diseñados.

**Aprendizaje:** El preview debe renderizar el template Twig real con datos de ejemplo (no un placeholder genérico). Se implementó con fallback graceful: intenta renderizar el Twig real, si falla retorna el placeholder genérico. Así el editor muestra el diseño real del bloque.

**Regla:** `renderTemplatePreview()` DEBE intentar renderizar el template Twig real con datos de ejemplo, usando fallback a placeholder genérico solo si la renderización falla.

### 5. Compilación page-builder-blocks.scss vs parciales individuales

**Situación:** El Page Builder ya tenía múltiples parciales SCSS individuales compilados separadamente. El nuevo `_pb-sections.scss` debía integrarse sin romper el pipeline existente.

**Aprendizaje:** Se usa un entrypoint unificado `page-builder-blocks.scss` que importa todos los parciales con `@use`:
```scss
@use 'blocks/pb-sections';
// ... otros parciales
```
Compilación: `npx sass scss/page-builder-blocks.scss css/jaraba-page-builder.css --style=compressed`
Resultado: 47KB, 257 reglas `.pb-section`, 0 referencias a `Inter` (fuente correcta: Outfit).

**Regla:** Nuevos parciales SCSS del Page Builder se importan via `@use` en el entrypoint unificado, nunca se compilan individualmente.

---

## Reglas Nuevas

| Regla | Descripción |
|-------|-------------|
| **PB-VERTICAL-001** | Todo template vertical DEBE tener HTML semántico único por tipo (no HTML genérico compartido). Hero usa `<section>` con split layout, FAQ usa `<details>/<summary>`, Pricing usa `<table>`, etc. |
| **PB-VERTICAL-002** | Los esquemas de color por vertical se definen via CSS custom property `--pb-accent` en el modifier BEM del vertical. Los estilos base y tipo-específicos heredan el color via cascade. |

---

## Resumen de Cambios

| Archivo | Cambio |
|---------|--------|
| `scss/blocks/_pb-sections.scss` | **NUEVO** — 570 LOC: base `.pb-section` + 5 esquemas color + 11 layouts + responsive + `prefers-reduced-motion` |
| 55 templates Twig (`templates/blocks/sections/`) | **REESCRITOS** — HTML semántico único por tipo con `\|default()` extensivo (5 verticales × 11 tipos) |
| `src/Service/PageBuilderService.php` | **MODIFICADO** — `renderTemplatePreview()` renderiza Twig real con fallback |
| `scss/page-builder-blocks.scss` | **MODIFICADO** — `@use 'blocks/pb-sections'` añadido |
| `css/jaraba-page-builder.css` | **COMPILADO** — 47KB, 257 reglas `.pb-section` |
| `css/jaraba-page-builder.css.map` | **COMPILADO** — sourcemap actualizado |

---

## Resultado

| Métrica | Antes | Después |
|---------|-------|---------|
| Templates con HTML semántico | 0 / 55 | 55 / 55 |
| SCSS para secciones verticales | 0 LOC | 570 LOC |
| Esquemas de color vertical | 0 | 5 (via `--pb-accent`) |
| Layouts tipo-específicos | 0 | 11 |
| CSS compilado reglas `.pb-section` | 0 | 257 |
| Preview con diseño real | No (placeholder genérico) | Sí (Twig real + fallback) |

---

## Verificación

- [x] 55 templates Twig reescritos con HTML semántico único por tipo
- [x] `_pb-sections.scss` compilado sin errores (570 LOC)
- [x] 5 esquemas de color via `--pb-accent` + `color-mix()`
- [x] CSS compilado 47KB, 257 reglas `.pb-section`, 0 referencias `Inter`
- [x] `renderTemplatePreview()` renderiza Twig real con fallback graceful
