# Aprendizaje #174 — I18N Navigation Language Prefix + Canvas Safeguard 4 Capas

**Fecha:** 2026-03-10
**Contexto:** Implementacion de internacionalizacion completa de navegacion + sistema de proteccion canvas contra perdida de datos.
**Cross-refs:** Directrices v124.0.0, Arquitectura v112.0.0, Indice v153.0.0, Flujo v77.0.0

---

## Problema

1. **Navegacion sin prefijo de idioma**: Todos los enlaces del menu (5 variantes header + footer + mobile overlay) usaban rutas hardcoded sin `/en/` o `/pt-br/`, rompiendo la navegacion multi-idioma.
2. **PathProcessor filtraba por langcode**: `/en/equipo` no encontraba la entity porque el alias EN es `/team`. El filtro por langcode actual impedia la resolucion cross-language.
3. **Language switcher generaba URLs feas**: Sin outbound path processor para page_content, `Url::fromRoute()` producía `/en/page/87` en vez de `/en/team`.
4. **68 path_alias con double-slash**: Bug en translate-all-pages.php: `'/' . preg_replace(...)` + `'/' . trim(...)` creaba `//`.
5. **1 path_alias alucinado por IA**: Page 59/EN: frase completa como slug.
6. **Canvas data vulnerable**: Sin backup ni deteccion de sobreescritura accidental.

## Solucion

### I18N-NAVPREFIX-001 — Prefijo de idioma en navegacion
- `preprocess_page` inyecta `language_prefix` (vacio para default, `/en` o `/pt-br`)
- Alias `{{ lp }}` en Twig, cada href interno: `href="{{ lp }}/ruta"`
- 5 headers + footer + mobile overlay actualizados
- `validate-nav-i18n.php` detecta hardcoded paths, integrado en validate-all.sh fast mode

### I18N-PATHPROCESSOR-001 — PathProcessor sin filtro langcode
- Eliminado filtro `$currentLangcode` de la query en PathProcessorPageContent
- Entity ID es unico cross-language; Drupal EntityConverter carga la traduccion
- `/en/equipo` ahora resuelve correctamente (mismo entity ID, diferente alias por idioma)

### I18N-LANGSWITCHER-001 — Language switcher con path_alias traducido
- Para rutas page_content, construye URL desde `$translation->get('path_alias')->value`
- Prefijo de idioma antepuesto si no es idioma por defecto
- Fallback a `Url::fromRoute()` si no hay alias traducido

### SAFEGUARD-CANVAS-001 — 4 capas proteccion canvas_data
1. **Backup script** (`scripts/maintenance/backup-canvas.php`): backup/restore/list/diff
2. **Presave hook**: shrinkage >70% → CRITICAL log, empty → CRITICAL, contamination → WARNING
3. **Translation safeguards**: entity reload despues de CADA save, size mismatch abort ±60%
4. **Validation script**: cross-page duplication, NULL titles, AI fences

### SAFEGUARD-ALIAS-001 — Deteccion alucinacion path_alias
- >80 chars o patrones de frase (`translate`, `ready-to`) → fallback a slugificacion titulo
- Single leading slash enforcement (corrige `//`)
- 68 double-slash corregidos en BD, 1 alucinacion corregida

## Regla de Oro #115

> **Entity reload entre saves de traduccion es OBLIGATORIO** (`resetCache() + load()`) o la segunda traduccion sobrescribe la primera con datos stale del cache. Validar SIEMPRE los path_alias generados por IA: longitud, patrones de frase, single leading slash.

## Ficheros Clave

### Nuevos
- `scripts/maintenance/backup-canvas.php` — Backup/restore canvas_data
- `scripts/validation/validate-nav-i18n.php` — Deteccion hardcoded paths

### Modificados
- `PathProcessorPageContent.php` — Eliminado filtro langcode
- `ecosistema_jaraba_theme.theme` — +language_prefix, +I18N-LANGSWITCHER-001
- `_header.html.twig` — +lp prefix, +language_links pass-through
- `_header-classic.html.twig` — +lp prefix en megamenu + auth
- `_header-hero.html.twig` — +lp prefix
- `_header-minimal.html.twig` — +lp prefix
- `_header-split.html.twig` — +lp prefix
- `_header-centered.html.twig` — +lp prefix
- `_footer.html.twig` — +lp prefix en nav columns
- `jaraba_page_builder.module` — +presave hook canvas safeguard
- `translate-all-pages.php` — double-slash fix + SAFEGUARD-ALIAS-001
- `validate-all.sh` — +I18N-NAVPREFIX-001 check
