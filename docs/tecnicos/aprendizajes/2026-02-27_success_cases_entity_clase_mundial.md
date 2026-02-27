# Aprendizaje #143 — jaraba_success_cases: Implementación de Casos de Éxito Clase Mundial

**Fecha:** 2026-02-27
**Módulo:** `jaraba_success_cases`
**Contexto:** Implementación de un módulo completo para gestión centralizada de casos de éxito en 4 meta-sitios.

---

## Resumen

Se creó el módulo `jaraba_success_cases` (26 ficheros) con Content Entity `SuccessCase`, frontend controller (list/detail/API), 3 templates Twig, 5 parciales SCSS y JS behaviors. Se preparó previamente una carpeta de recursos con briefs pre-rellenos y métricas SSOT.

## Decisiones de Diseño Clave

### 1. Content Entity vs Nodes
**Decisión:** Content Entity custom (no nodos Drupal).
**Razón:** Consistente con el 100% del proyecto (ningún módulo usa nodos). Control total sobre campos sin overhead de revisiones/workflows de nodos. Integración limpia con Views y Field UI.

### 2. Multimedia via Field UI (no base fields)
**Decisión:** Los campos multimedia (foto perfil, vídeo testimonial, logo empresa) se gestionan vía Field UI.
**Razón:** Flexibilidad para que el admin añada/modifique campos multimedia sin despliegue de código. Permite usar Media entities con image styles y responsive images.

### 3. Métricas como JSON string (no campos separados)
**Decisión:** Campo `metrics_json` como `string_long` con datos JSON.
**Razón:** Cada caso de éxito tiene métricas diferentes (uno puede tener "revenue_increase", otro "new_clients"). Un JSON flexible permite KVPs ilimitados sin migrar entity schema.

### 4. Slug auto-generado con transliteración española
**Decisión:** Auto-slug en `preSave()` con transliteración manual de á,é,í,ó,ú,ñ,ü.
**Razón:** No depender de módulos contrib como Pathauto para una funcionalidad limitada. La transliteración cubre el 99% de nombres en español.

### 5. API JSON para Page Builder
**Decisión:** Endpoint `/api/success-cases` que devuelve JSON con datos de cards.
**Razón:** Permite reutilizar los bloques `testimonials-slider` existentes del Page Builder, mapeando campos de SuccessCase a los campos del testimonial slider.

## Patrones Técnicos Aplicados

| Patrón | Dónde se aplicó |
|--------|----------------|
| **Zero Region Policy** | `page--success-cases.html.twig` con `{{ clean_content }}` |
| **DRUPAL11-002** (PHP 8.4) | Controller: `$this->entityTypeManager` asignado manualmente, sin promotion |
| **4 YAML obligatorios** | `.links.menu.yml`, `.links.task.yml`, `.links.action.yml`, `.routing.yml` |
| **Content Entity Checklist** | Entity + ListBuilder + AccessControl + Form + Settings + RouteProvider |
| **Federated Design Tokens** | `var(--ej-color-corporate)`, `var(--ej-font-family)` en todo SCSS |
| **BEM con prefijo de módulo** | `.sc-card`, `.sc-hero`, `.sc-detail`, `.sc-section` |
| **Glassmorphism + 3D Hover** | Card premium con backdrop-blur, shine-on-hover, translateY lift |
| **Schema.org JSON-LD** | `<script type="application/ld+json">` con Review + Person + Rating |
| **i18n** | Todos los textos UI en `{% trans %}` |
| **prefers-reduced-motion** | Desactiva animaciones para usuarios con preferencia de movimiento reducido |

## Errores Evitados

1. **No puse `protected` en propiedades de ControllerBase** (DRUPAL11-002).
2. **Body classes en `hook_preprocess_html()`**, no en template — el patrón `attributes.addClass()` en el template no funciona para `<body>`.
3. **Format guidelines ocultas** via `hook_form_alter()` recursivo — previene confusión UX en slide-panel.
4. **Cacheability metadata** en access control handler — `AccessResult::allowed()->cachePerPermissions()` y `addCacheableDependency($entity)`.

## Ficheros Clave

| Fichero | Propósito |
|---------|-----------|
| `src/Entity/SuccessCase.php` | 20+ base fields, auto-slug, Field UI base route |
| `src/Controller/SuccessCasesController.php` | list + detail + API, vertical filter, pager |
| `templates/success-case-detail.html.twig` | Narrativa Challenge/Solution/Result + Schema.org |
| `scss/_card.scss` | Glassmorphism + 3D hover + vertical badges |
| `scripts/seed_success_cases.php` | Seeder idempotente por slug |

## Regla

**SUCCESS-CASES-001 (P1):** Todo caso de éxito publicado en cualquier meta-sitio DEBE provenir de la entidad centralizada `SuccessCase`. No se permiten testimonios hardcodeados en templates de Page Builder que no provengan del API `/api/success-cases`. Las métricas globales deben consultarse desde `_metricas-globales.md` SSOT.
