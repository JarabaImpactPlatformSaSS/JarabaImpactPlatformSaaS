# Aprendizajes: Plan Maestro 7 Fases — Cierre Gaps Specs 20260202-20260204

**Fecha:** 2026-02-12
**Contexto:** Implementacion completa del Plan Maestro que cerro 15 gaps identificados en 9 documentos tecnicos, alcanzando 100% de cumplimiento con las especificaciones.
**Alcance:** 7 fases, ~80 archivos creados/modificados, 121+ test methods

---

## 1. Plugin Architecture Pattern (@InteractiveType)

**Situacion:** Necesitabamos 5 tipos de contenido interactivo adicionales al QuestionSet existente, todos con scoring, xAPI y renderizado propio.

**Aprendizaje:** El patron de plugins Drupal con annotation personalizada (@InteractiveType) escala perfectamente. La clave esta en:

- `InteractiveTypeBase` como abstract con helpers compartidos (`calculatePercentage`, `determinePassed`, `validate` basico)
- `InteractiveTypeInterface` con contrato claro: 8 metodos (getSchema, validate, render, calculateScore, getXapiVerbs, getLabel, getDescription, getIcon)
- `InteractiveTypeManager` con `getTypeOptions()` (sorted by weight) y `getGroupedOptions()` (grouped by category)
- Cada plugin es autocontenido: schema JSON, validacion, rendering, scoring, xAPI verbs

**Regla INT-001:** Todo plugin @InteractiveType DEBE extender InteractiveTypeBase e implementar los 8 metodos del contrato. Los tests unitarios deben cubrir al menos calculateScore con 3+ escenarios (perfecto, parcial, zero).

---

## 2. EventSubscriber Priority para Completion Flow

**Situacion:** CompletionSubscriber necesitaba ejecutarse antes que otros subscribers para garantizar que XP y certificaciones se otorguen correctamente.

**Aprendizaje:** Priority 100 en getSubscribedEvents() asegura ejecucion temprana. El subscriber filtra por entity type (`interactive_result`) antes de procesar, evitando overhead innecesario.

**Regla INT-002:** EventSubscribers que disparan side-effects criticos (XP, certificaciones) deben usar priority >= 100 y filtrar por entity type en el primer condicional.

---

## 3. Editor Zero-Region Pattern

**Situacion:** El editor de contenido interactivo necesitaba una pagina limpia sin regiones de Drupal (header, sidebar, footer).

**Aprendizaje:** El patron zero-region se implementa con:
1. `EditorController` que devuelve render array con `#theme => 'page__interactive_editor'`
2. Template Twig `page--interactive-editor.html.twig` sin regiones
3. `hook_preprocess_html()` para anadir body classes
4. JS orquestador (`content-editor.js`) que carga sub-editors dinamicamente segun `content_type`

---

## 4. PHPUnit Testing Pattern: Pure Unit vs Drupal UnitTestCase

**Situacion:** Los tests necesitaban probar plugins que extienden PluginBase (requiere DI) y servicios con dependencias de Drupal.

**Aprendizaje:** Para tests unitarios puros:
- Usar `PHPUnit\Framework\TestCase` (NO `Drupal\Tests\UnitTestCase`) para evitar bootstrap de Drupal
- `getMockBuilder(PluginClass::class)->disableOriginalConstructor()->onlyMethods([])->getMock()` para obtener instancias reales con constructor deshabilitado
- `#[\PHPUnit\Framework\Attributes\Test]` en lugar de prefijo `test` (PHPUnit 10+)
- Intersection types `InterfaceName&MockObject` para propiedades mock
- `\ReflectionMethod` para testear metodos protected
- `stdClass` con `addMethods()` para mocks de entidades Drupal
- Try/catch para `\Drupal::` static calls que fallan en unit tests

**Regla TEST-003:** Tests unitarios puros DEBEN extender `PHPUnit\Framework\TestCase`, NO `Drupal\Tests\UnitTestCase`. Usar `disableOriginalConstructor()` para plugins. Envolver `\Drupal::` en try/catch.

---

## 5. CacheTagsInvalidator Deduplication

**Situacion:** Al invalidar multiples page_content tags, los tags derivados (collection, sitemap, preview) se duplicaban.

**Aprendizaje:** `array_unique()` sobre el array de tags adicionales antes de delegar al inner invalidator. Tags propagados:
- `page_content:N` → `page_content_list` (coleccion) + `jaraba_sitemap` (sitemap) + `canvas_preview` (preview general) + `canvas_preview:N` (preview especifica)

**Regla PB-002:** Todo CacheTagsInvalidator que propague tags derivados DEBE usar `array_unique()` antes de delegar al inner invalidator.

---

## 6. Stripe Purchase Flow con Fallback

**Situacion:** PurchaseService necesitaba manejar tanto pagos Stripe como productos gratuitos, con fallback cuando Stripe no esta configurado.

**Aprendizaje:** El flujo es:
1. Validar producto (exists + isPublished)
2. Bifurcar: isFree() → enrollment directo, else → Stripe PaymentIntent
3. Stripe: `\Drupal::hasService('jaraba_commerce.stripe')` check antes de crear PaymentIntent
4. Fallback: si Stripe no disponible, retornar `type: 'pending'` para procesamiento manual
5. Certificacion: tipos especificos (`certification_consultant/entity/regional_franchise`) crean UserCertification automaticamente

**Regla TRN-001:** Servicios de pago DEBEN verificar `\Drupal::hasService()` antes de llamar a Stripe. Siempre proveer fallback manual.

---

## 7. SCSS Zero-Import Enforcement

**Situacion:** Auditoria de 14 modulos SCSS encontro usos de `@import` (deprecado en Dart Sass) y `darken()`/`lighten()`.

**Aprendizaje:** La politica estricta es:
- 0 usos de `@import` → todo `@use` (Dart Sass moderno)
- 0 usos de `darken()`/`lighten()` → `color.adjust()` o `color.scale()`
- 0 definiciones `$ej-*` en modulos satelite → solo en `ecosistema_jaraba_core/scss/_variables.scss`
- Modulos satelite solo consumen via `var(--ej-*, $fallback)` con fallback inline

**Regla SCSS-002:** Cero tolerancia a `@import` en SCSS. Todo modulo debe usar `@use 'sass:color'` y `@use '../path/variables' as *`.

---

## 8. Seed Script Idempotency Pattern

**Situacion:** El script seed_pepejaraba.php necesitaba ser ejecutable multiples veces sin crear duplicados.

**Aprendizaje:** Pattern para scripts idempotentes:
1. `loadByProperties()` antes de cada `create()`
2. Si existe, reusar la entidad existente (no crear)
3. Imprimir mensajes claros: "ya existe (ID: X)" vs "creado (ID: X)"
4. UUID para secciones de PageContent (no IDs numericos)
5. `try/catch` global con logging a `\Drupal::logger()`

**Regla SEED-001:** Todo seed script DEBE ser idempotente (verificar existencia antes de crear). Usar `loadByProperties()` con campos unicos.

---

## 9. Cypress E2E Testing Patterns para Canvas Editor

**Situacion:** 5 specs Cypress necesitaban testear el Canvas Editor (GrapesJS) con login, carga del editor, y operaciones complejas.

**Aprendizaje:** Patrones efectivos:
- `getEditor()` helper: `cy.window({ timeout: 8000 }).its('editor', { timeout: 8000 })`
- Login en `beforeEach`: visitar `/es/user/login`, rellenar campos, esperar redirect
- Timeout largo (15000ms) para carga inicial del editor
- `editor.addComponents()` para anadir bloques via API
- `editor.getWrapper().components()` para verificar modelo
- `cy.intercept('PATCH', '**/api/v1/pages/*/canvas')` para verificar guardado
- `performance.now()` para mediciones de rendimiento
- `contentDocument.querySelectorAll()` para verificar DOM del iframe

---

## 10. Paralelizacion de Agentes para Implementacion Masiva

**Situacion:** 11 archivos de test, 6 editors JS, 5 Cypress specs necesitaban crearse simultaneamente.

**Aprendizaje:** La paralelizacion de agentes (4-5 en paralelo) reduce dramaticamente el tiempo total:
- Agrupar archivos por dependencia: plugins juntos, servicios juntos
- Cada agente recibe el patron de referencia completo (CartRecoveryServiceTest como template)
- Validacion PHP syntax (`php -l`) al final de cada agente
- Consolidar resultados y verificar con `find` / `glob`

---

## Resumen de Reglas Nuevas

| ID | Regla | Ambito |
|----|-------|--------|
| INT-001 | Plugin @InteractiveType: extender Base, implementar 8 metodos, 3+ test scenarios | jaraba_interactive |
| INT-002 | EventSubscriber priority >= 100 para side-effects criticos | jaraba_interactive |
| PB-002 | CacheTagsInvalidator: array_unique() antes de delegar | jaraba_page_builder |
| TRN-001 | PurchaseService: hasService() check + fallback manual | jaraba_training |
| SCSS-002 | Zero @import enforcement: todo @use, zero darken/lighten | Global SCSS |
| TEST-003 | PHPUnit\Framework\TestCase para unit tests puros, no UnitTestCase | Testing |
| SEED-001 | Seed scripts idempotentes: loadByProperties() antes de create() | Scripts |
