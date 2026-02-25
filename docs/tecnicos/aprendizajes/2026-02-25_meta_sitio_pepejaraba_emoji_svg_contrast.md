# Aprendizaje #124: Meta-Sitio pepejaraba.com — Emoji→SVG + Contrast Fixes

**Fecha:** 2026-02-25
**Modulo:** `jaraba_page_builder`, `ecosistema_jaraba_core` (Icon Engine)
**Contexto:** Post-remediacion del meta-sitio pepejaraba.com. Tras implementar las 5 fases del plan de remediacion (MetaSiteResolverService, PathProcessor, theme overrides, GrapesJS, SEO), la verificacion visual revelo dos problemas de calidad: (1) emojis Unicode en lugar de iconos SVG del sistema, y (2) falta de contraste en hero y CTA de la homepage.

---

## Problemas Detectados

### P1: Emojis Unicode en canvas_data (11 instancias)
Las paginas del meta-sitio pepejaraba usaban emojis Unicode como iconos visuales en elementos `<div class="pj-card__icon">`. Esto viola la directriz del sistema de iconos del SaaS (ICON-CONVENTION-001) y produce renderizado inconsistente entre plataformas/navegadores.

**Auditoria completa:**

| Pagina | ID | Emoji | Contexto |
|--------|----|-------|----------|
| Inicio | 57 | `<U+1F3EA>` (tienda) | "Para el Negocio que no Tiene Tiempo" |
| Inicio | 57 | `<U+1F680>` (cohete) | "Para la Idea que Necesita un Impulso" |
| Inicio | 57 | `<U+1F464>` (persona) | "Para el Talento que se Siente Invisible" |
| Inicio | 57 | `<U+1F4BC>` (maletin) | "Empleo" |
| Inicio | 57 | `<U+1F331>` (plantula) | "Emprendimiento" |
| Inicio | 57 | `<U+1F6D2>` (carrito) | "Comercio" |
| Casos de Exito | 60 | `<U+1F4CB>` (clipboard) | Empty state |
| Blog | 61 | `<U+270D><U+FE0F>` (escribiendo) | Empty state |
| Contacto | 62 | `<U+1F4E7>` (email) | Info de contacto |
| Contacto | 62 | `<U+1F4F1>` (movil) | Info de contacto |
| Contacto | 62 | `<U+1F4CD>` (ubicacion) | Info de contacto |

### P2: Contraste insuficiente en hero y CTA (page 57)
El hero usaba texto blanco sobre un gradiente casi transparente (`rgba(0,0,0,0.15)`). La seccion CTA final tenia el mismo problema. Ambos fallaban WCAG 2.1 AA (ratio < 4.5:1).

---

## Solucion Implementada

### S1: 12 iconos SVG custom creados (6 regular + 6 duotone)

Se crearon 6 iconos conceptualmente especificos para el contexto del meta-sitio de consultoria en transformacion digital, con version regular y duotone cada uno:

| Icono | Fichero | Concepto Visual |
|-------|---------|-----------------|
| `time-pressure` | `business/time-pressure[-duotone].svg` | Reloj alarma con campanas de urgencia |
| `launch-idea` | `business/launch-idea[-duotone].svg` | Bombilla con chispas de ignicion |
| `talent-spotlight` | `business/talent-spotlight[-duotone].svg` | Persona con rayos de visibilidad |
| `career-connect` | `business/career-connect[-duotone].svg` | Maletin con check de matching |
| `seed-momentum` | `business/seed-momentum[-duotone].svg` | Plantula con flecha de impulso ascendente |
| `store-digital` | `business/store-digital[-duotone].svg` | Tienda con senal WiFi/digital |

**Convenciones SVG seguidas:**
- `viewBox="0 0 24 24"`, `stroke-width="2"`, `stroke-linecap="round"`, `stroke-linejoin="round"`
- Duotone: `fill="currentColor" fill-opacity="0.12-0.18"` para capas de fondo
- Renderizado inline en canvas_data con `width="48" height="48"` para match con `font-size: 48px`

### S2: Reemplazo de emojis en canvas_data (22 reemplazos)

Se creo un script PHP ejecutado via `drush scr` que:
1. Itera las 4 paginas afectadas (57, 60, 61, 62)
2. Reemplaza cada emoji Unicode por el SVG inline correspondiente en `html` y `rendered_html`
3. Re-serializa el `canvas_data` JSON con `JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`

**Resultado:** 22 reemplazos (12 en page 57, 2 en page 60, 2 en page 61, 6 en page 62).

**Detalle critico — colores en SVG inline de canvas_data:**
En canvas_data el HTML es raw (no procesado por Twig), por lo que `currentColor` no hereda del CSS del tema. Los SVGs DEBEN usar colores hex explicitos del brand (`#233D63` azul corporativo) en lugar de `currentColor` cuando se insertan en canvas_data.

### S3: Iconos existentes reutilizados para paginas interiores

Para paginas 60-62 se inlinaron versiones duotone de iconos estandar del sistema:
- `clipboard` (pagina 60): Rectangulo con clip y lineas de documento
- `edit` (pagina 61): Lapiz con trazo diagonal
- `mail` (pagina 62): Sobre con flap triangular
- `smartphone` (pagina 62): Rectangulo de dispositivo movil
- `map-pin` (pagina 62): Pin de ubicacion con circulo interior

### S4: Contraste hero + CTA (page 57, implementado en sesion anterior)
- `.pj-hero`: `background: linear-gradient(135deg, #233D63 0%, #1a3050 50%, #162740 100%)`
- `.pj-hero__overlay`: Opacidad incrementada de 0.15→0.25 y 0.1→0.2
- `.pj-final-cta`: Mismo gradiente azul oscuro + `text-align: center`

---

## Lecciones Aprendidas

### L1: SVGs inline en canvas_data NO heredan CSS del tema
**Problema:** `stroke="currentColor"` funciona en templates Twig donde el CSS del tema define `color` en el contenedor. En canvas_data, el HTML se inyecta directamente en el DOM sin contexto CSS del tema, por lo que `currentColor` puede resolver a un color no deseado (negro por defecto).

**Solucion:** Usar colores hex explicitos del brand en los atributos `stroke` y `fill` de los SVGs inline. Ejemplo: `stroke="#233D63"` en lugar de `stroke="currentColor"`.

**Regla:** ICON-CANVAS-INLINE-001 (nueva).

### L2: Los emojis en canvas_data son invisibles para auditorias de templates
**Problema:** La auditoria de iconos "Zero Chinchetas" (Aprendizaje #117) verifico 305 pares `jaraba_icon()` en templates Twig, pero no audito el contenido de canvas_data almacenado en la BD. Los emojis en paginas de Page Builder no pasan por `jaraba_icon()` y por tanto no generan el fallback de chincheta.

**Solucion:** Las auditorias de iconos deben incluir un scan de canvas_data via query a la BD:
```php
$pages = \Drupal::entityTypeManager()->getStorage('page_content')->loadMultiple();
foreach ($pages as $page) {
  $html = json_decode($page->get('canvas_data')->value, true)['html'] ?? '';
  preg_match_all('/[\x{1F000}-\x{1FFFF}]/u', $html, $matches);
  // Report any emoji matches
}
```

**Regla:** ICON-EMOJI-001 (nueva).

### L3: La categoria `business/` del icon system necesita iconos conceptuales
**Problema:** Los iconos existentes en el sistema (ui/clock, ui/user, commerce/cart) son genericos. Para meta-sitios de consultoria, los iconos deben comunicar conceptos especificos (urgencia temporal, idea necesitando impulso, talento buscando visibilidad).

**Solucion:** Crear iconos conceptuales en la categoria `business/` que combinen elementos visuales para comunicar conceptos de negocio especificos. Cada icono tiene un nombre descriptivo (`time-pressure`, `launch-idea`, `talent-spotlight`, etc.) y version duotone.

### L4: El CSS `font-size: 48px` de las tarjetas afecta al tamano de SVGs inline
**Problema:** La clase `.pj-card__icon` tiene `font-size: 48px` que dimensiona correctamente los emojis Unicode. Al reemplazar por SVGs inline, estos deben especificar `width="48" height="48"` explicitamente para mantener la misma proporcion visual.

---

## Ficheros Creados (12)

```
web/modules/custom/ecosistema_jaraba_core/images/icons/business/time-pressure.svg
web/modules/custom/ecosistema_jaraba_core/images/icons/business/time-pressure-duotone.svg
web/modules/custom/ecosistema_jaraba_core/images/icons/business/launch-idea.svg
web/modules/custom/ecosistema_jaraba_core/images/icons/business/launch-idea-duotone.svg
web/modules/custom/ecosistema_jaraba_core/images/icons/business/talent-spotlight.svg
web/modules/custom/ecosistema_jaraba_core/images/icons/business/talent-spotlight-duotone.svg
web/modules/custom/ecosistema_jaraba_core/images/icons/business/career-connect.svg
web/modules/custom/ecosistema_jaraba_core/images/icons/business/career-connect-duotone.svg
web/modules/custom/ecosistema_jaraba_core/images/icons/business/seed-momentum.svg
web/modules/custom/ecosistema_jaraba_core/images/icons/business/seed-momentum-duotone.svg
web/modules/custom/ecosistema_jaraba_core/images/icons/business/store-digital.svg
web/modules/custom/ecosistema_jaraba_core/images/icons/business/store-digital-duotone.svg
```

## Ficheros Modificados (4 — via script DB update)

```
page_content.canvas_data (page 57): 6 emojis → 6 SVGs inline
page_content.canvas_data (page 60): 1 emoji → 1 SVG inline
page_content.canvas_data (page 61): 1 emoji → 1 SVG inline (+ variante sin VS16)
page_content.canvas_data (page 62): 3 emojis → 3 SVGs inline
```

## Reglas Nuevas

| ID | Prioridad | Descripcion |
|----|-----------|-------------|
| ICON-CANVAS-INLINE-001 | P0 | Los SVGs inline en `canvas_data` DEBEN usar colores hex explicitos del brand (#233D63, #FF8C42, etc.), NUNCA `currentColor`. El canvas_data no hereda CSS del tema. |
| ICON-EMOJI-001 | P0 | Las paginas de Page Builder NO DEBEN contener emojis Unicode como iconos. Toda auditoria de iconos DEBE incluir un scan de canvas_data en la BD ademas de los templates Twig. |

## Regla de Oro #38
> **Los meta-sitios de Page Builder son ciudadanos de primera clase del sistema de iconos.** El contenido de canvas_data debe cumplir las mismas directrices visuales que los templates Twig: SVGs del sistema de iconos, colores de la paleta Jaraba, variantes duotone para contextos premium. Los emojis Unicode nunca son aceptables en paginas de produccion.
