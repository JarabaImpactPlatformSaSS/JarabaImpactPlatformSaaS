# Plan de Cierre de Gaps: Especificaciones Tecnicas 20260126

**Fecha:** 2026-02-12
**Specs auditadas:** 17 documentos serie 20260126
**Estado:** COMPLETADO (todas las fases P0-P4)
**Implementacion global ponderada:** ~95%
**Gaps criticos identificados:** 10
**Modulos afectados:** jaraba_page_builder, jaraba_billing, jaraba_credentials, ecosistema_jaraba_core, ecosistema_jaraba_theme
**Esfuerzo total estimado:** ~394h (5 fases)

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Fase 0: Bloqueantes Criticos (46h)](#fase-0-bloqueantes-criticos)
   - [P0-01: Archivos Premium Fantasma](#p0-01-archivos-premium-fantasma)
   - [P0-02: ARIA Completo para Page Builder](#p0-02-aria-completo-para-page-builder)
   - [P0-03: Contraste Global y Teclado](#p0-03-contraste-global-y-teclado)
3. [Fase 1: Infraestructura Base (60h)](#fase-1-infraestructura-base)
   - [P1-01: Plan Limits Universales Page Builder](#p1-01-plan-limits-universales-page-builder)
   - [P1-02: Plan Limits para Credentials](#p1-02-plan-limits-para-credentials)
   - [P1-03: Conformidad de Rutas API](#p1-03-conformidad-de-rutas-api)
   - [P1-04: Triggers Automaticos Credentials](#p1-04-triggers-automaticos-credentials)
   - [P1-05: Publicacion Programada](#p1-05-publicacion-programada)
4. [Fase 2: Cierre de Gaps Funcionales (164h)](#fase-2-cierre-de-gaps-funcionales)
   - [P2-01: 55 Templates Verticales](#p2-01-55-templates-verticales)
   - [P2-02: Endpoints SEO/GEO Faltantes](#p2-02-endpoints-seogeo-faltantes)
   - [P2-03: Workflow Editorial i18n](#p2-03-workflow-editorial-i18n)
   - [P2-04: Integraciones Analytics Externas](#p2-04-integraciones-analytics-externas)
   - [P2-05: Sugerencias de Imagen con IA](#p2-05-sugerencias-de-imagen-con-ia)
   - [P2-06: Remediacion Colores/Iconos](#p2-06-remediacion-coloresiconos)
5. [Fase 3: Excelencia y Calidad (60h)](#fase-3-excelencia-y-calidad)
   - [P3-01: Tests Unitarios Credentials](#p3-01-tests-unitarios-credentials)
   - [P3-02: Portabilidad Credentials](#p3-02-portabilidad-credentials)
   - [P3-03: A/B Testing Multivariate](#p3-03-ab-testing-multivariate)
6. [Fase 4: Deuda Tecnica (64h)](#fase-4-deuda-tecnica)
   - [P4-01: Remediacion 686 Colores SCSS](#p4-01-remediacion-686-colores-scss)
   - [P4-02: Limpieza Emojis Unicode](#p4-02-limpieza-emojis-unicode)
   - [P4-03: Documentacion y Directrices](#p4-03-documentacion-y-directrices)
7. [Correspondencia con Specs](#correspondencia-con-specs)
8. [Cumplimiento de Directrices](#cumplimiento-de-directrices)
9. [Criterios de Verificacion por Tarea](#criterios-de-verificacion)

---

## Resumen Ejecutivo

La auditoria exhaustiva de los 17 documentos tecnicos de la serie 20260126 contra el codigo real del repositorio revelo una implementacion global ponderada del ~60%. Si bien el nucleo funcional de los modulos principales (Page Builder, Credentials, Billing) esta operativo, se identificaron 10 gaps criticos que abarcan desde riesgos regulatorios (WCAG al 15%) hasta errores runtime (archivos fantasma referenciados en `libraries.yml`) y funcionalidad ausente (55 templates verticales, plan limits universales).

### Metricas de la auditoria

| Area | Implementado | Gap |
|------|-------------|-----|
| Page Builder - Core | 85% | Templates verticales, publicacion programada |
| Page Builder - Premium | 70% | Archivos CSS/JS fantasma, ARIA |
| Page Builder - WCAG | 15% | ARIA, contraste, teclado |
| Billing - Plan Limits | 80% | Limits para Page Builder y Credentials |
| Credentials - Triggers | 40% | Hooks nativos, portabilidad |
| SEO/GEO | 60% | 7 endpoints faltantes |
| SCSS - Tokens | 30% | 686 colores hardcodeados |
| Tests - Credentials | 20% | 8 archivos de test faltantes |

### Clasificacion de gaps

- **3 bloqueantes criticos** (Fase 0): Impiden funcionamiento normal o generan riesgo legal
- **5 infraestructura base** (Fase 1): Funcionalidad esperada ausente
- **6 gaps funcionales** (Fase 2): Features incompletas segun specs
- **3 calidad** (Fase 3): Cobertura de tests y robustez
- **3 deuda tecnica** (Fase 4): Mantenibilidad a largo plazo

---

## Fase 0: Bloqueantes Criticos

**Esfuerzo total:** 46h
**Prioridad:** MAXIMA - Deben resolverse antes de cualquier despliegue
**Justificacion:** Estos items generan errores 404 en runtime, riesgo de incumplimiento WCAG (multas GDPR), o fallos silenciosos que degradan la experiencia de usuario.

### P0-01: Archivos Premium Fantasma

**Esfuerzo:** 8h
**Riesgo sin resolver:** Errores 404 en consola del navegador, Drupal library aggregation fallida
**Spec:** 20260126-Page_Builder §4.3 (Premium Blocks)

#### Problema

El archivo `jaraba_page_builder.libraries.yml` referencia 4 archivos que no existen en el filesystem:

```yaml
# Linea 64: css/premium/aceternity.css (NO EXISTE)
# Linea 66: js/premium/aceternity-adapter.js (NO EXISTE)
# Linea 76: css/premium/magic-ui.css (NO EXISTE)
# Linea 78: js/premium/magic-ui-adapter.js (NO EXISTE)
```

Esto provoca errores 404 cada vez que Drupal intenta agregar estas librerias, y en modo produccion con CSS/JS aggregation activado, puede causar fallos en cascada en la compilacion de assets.

#### Solucion

Crear los 4 archivos con implementacion funcional minima que:
- Proporcione los estilos CSS base para los 24 templates premium existentes en `templates/blocks/premium/`
- Implemente los adapters JavaScript que conectan los efectos de Aceternity UI y Magic UI con el sistema de Drupal.behaviors
- Use design tokens `var(--ej-*)` para todos los colores
- Incluya `prefers-reduced-motion` para accesibilidad

#### Ficheros a crear

| Fichero | Proposito |
|---------|-----------|
| `scss/premium/_aceternity.scss` | SCSS fuente Dart Sass con `@use 'sass:color'`, 15 bloques Aceternity, paleta oficial `var(--ej-*)` con fallback SCSS |
| `scss/premium/_magic-ui.scss` | SCSS fuente Dart Sass con `@use 'sass:color'`, 10 bloques Magic UI, paleta oficial `var(--ej-*)` con fallback SCSS |
| `css/premium/aceternity.css` | CSS compilado desde SCSS (salida de `npx sass --style=compressed`) |
| `css/premium/magic-ui.css` | CSS compilado desde SCSS (salida de `npx sass --style=compressed`) |
| `js/premium/aceternity-adapter.js` | Adapter JS Aceternity → `Drupal.behaviors` con `prefers-reduced-motion`, `once()`, `Drupal.t()` |
| `js/premium/magic-ui-adapter.js` | Adapter JS Magic UI → `Drupal.behaviors` con IntersectionObserver, `prefers-reduced-motion` |

#### Compilacion SCSS

```bash
lando ssh -c "cd /app/web/modules/custom/jaraba_page_builder && \
  npx sass scss/premium/_aceternity.scss:css/premium/aceternity.css \
         scss/premium/_magic-ui.scss:css/premium/magic-ui.css \
  --style=compressed"
```

#### Criterio de verificacion

- `lando drush cr` sin warnings de archivos faltantes
- No hay errores 404 en Network tab del navegador
- Los 24 templates premium renderizan con estilos aplicados
- CSS/JS aggregation funciona correctamente
- SCSS usa `@use` (no `@import`), `color.adjust()` (no `darken()`/`lighten()`)
- Fallbacks SCSS usan paleta oficial: `#FF8C42` (impulse), `#00A9A5` (innovation), `#233D63` (corporate)

---

### P0-02: ARIA Completo para Page Builder

**Esfuerzo:** 22h
**Riesgo sin resolver:** Incumplimiento WCAG 2.1 AA, riesgo regulatorio (GDPR Art. 14, EN 301 549)
**Spec:** 20260126-Page_Builder §7.1 (Accesibilidad)

#### Problema

Los bloques del Page Builder carecen de atributos ARIA adecuados. Los 24 templates premium y los ~45 templates base no incluyen:
- `role` attributes apropiados
- `aria-label` / `aria-labelledby` para regiones
- `aria-expanded` / `aria-controls` para componentes interactivos
- `aria-live` para contenido dinamico
- Manejo de `focus` trap en modales/slide-panels

#### Solucion

1. **AccessibilityValidatorService**: Servicio PHP que valida el HTML de una pagina contra reglas ARIA y devuelve violations
2. **AccessibilityApiController**: Endpoint API para validacion on-demand
3. **accessibility-validator.js**: Cliente JavaScript que valida ARIA en tiempo real durante la edicion en el Canvas Editor
4. Remediar ARIA en todos los templates premium existentes

#### Ficheros a crear

| Fichero | Proposito |
|---------|-----------|
| `src/Service/AccessibilityValidatorService.php` | Servicio de validacion ARIA server-side |
| `src/Controller/AccessibilityApiController.php` | API endpoint POST `/api/v1/page-builder/accessibility/validate` |
| `js/accessibility-validator.js` | Cliente JS para validacion real-time en Canvas Editor |

#### Ficheros a modificar

| Fichero | Cambio |
|---------|--------|
| `jaraba_page_builder.services.yml` | Registro del servicio `accessibility_validator` |
| `jaraba_page_builder.routing.yml` | Ruta API para validacion |
| `jaraba_page_builder.libraries.yml` | Library `accessibility-validator` |
| 24 templates en `templates/blocks/premium/` | Anadir ARIA attributes |

#### Criterio de verificacion

- axe-core sin violations nivel A ni AA
- `AccessibilityValidatorService::validate()` retorna 0 violations para templates remediados
- Endpoint API responde con JSON valido conteniendo violations/warnings/passes

---

### P0-03: Contraste Global y Teclado

**Esfuerzo:** 16h
**Riesgo sin resolver:** Usuarios con discapacidad visual no pueden usar la plataforma
**Spec:** 20260126-Page_Builder §7.2 (WCAG Contraste), §7.3 (Navegacion Teclado)

#### Problema

- Ratios de contraste insuficientes en textos sobre fondos de color
- Falta de `:focus-visible` consistente en todos los elementos interactivos
- No hay soporte para `prefers-reduced-motion`
- No existe herramienta client-side para verificar contraste en tiempo real

#### Solucion

1. **contrast-checker.js**: Herramienta JS embebida en el Canvas Editor que calcula ratios de contraste WCAG en tiempo real
2. Remediar focus-visible en todos los componentes interactivos
3. Anadir `prefers-reduced-motion` media query a todas las animaciones premium

#### Ficheros a crear

| Fichero | Proposito |
|---------|-----------|
| `js/contrast-checker.js` | Calculador de contraste WCAG AA/AAA en real-time |

#### Ficheros a modificar

| Fichero | Cambio |
|---------|--------|
| `jaraba_page_builder.libraries.yml` | Library `contrast-checker` |
| `scss/page-builder-blocks.scss` | Focus-visible para todos los bloques |
| `scss/blocks/*.scss` | `prefers-reduced-motion` en animaciones |
| `js/premium-blocks.js` | Respetar `prefers-reduced-motion` |

#### Criterio de verificacion

- Todos los textos cumplen ratio 4.5:1 (AA) o 7:1 (AAA)
- Tab navigation funciona en secuencia logica
- Animaciones se desactivan con `prefers-reduced-motion: reduce`
- `contrast-checker.js` reporta correctamente violations

---

## Fase 1: Infraestructura Base

**Esfuerzo total:** 60h
**Prioridad:** ALTA - Funcionalidad esperada segun specs que impacta modelo de negocio
**Dependencia:** Fase 0 debe estar completa

### P1-01: Plan Limits Universales Page Builder

**Esfuerzo:** 12h
**Spec:** 20260126-Page_Builder §3.1 (Limites por Plan)

#### Problema

El `PlanValidator` de `jaraba_billing` valida limites para productores, storage y AI queries, pero NO incluye limites especificos del Page Builder:
- Numero maximo de paginas por plan
- Bloques premium permitidos por plan
- Templates disponibles por plan
- Numero de experimentos A/B por plan

El `QuotaManagerService` del Page Builder existe pero no esta integrado con `PlanValidator`.

#### Solucion

Extender `PlanValidator` con metodos para limites del Page Builder y conectar con `QuotaManagerService`.

#### Ficheros a modificar

| Fichero | Cambio |
|---------|--------|
| `jaraba_billing/src/Service/PlanValidator.php` | Nuevos metodos: `canCreatePage()`, `canUsePremiumBlock()`, `canCreateExperiment()` |
| `jaraba_billing/src/Service/FeatureAccessService.php` | Mapeo de features page_builder al FEATURE_ADDON_MAP |
| `jaraba_page_builder/src/Service/QuotaManagerService.php` | Integrar con PlanValidator via DI |

#### Criterio de verificacion

- `PlanValidator::enforceLimit($tenant, 'create_page')` funciona correctamente
- Plan Free limita a 3 paginas, Pro a 20, Enterprise unlimited
- Bloques premium solo disponibles en plan Pro+
- Tests unitarios pasan

---

### P1-02: Plan Limits para Credentials

**Esfuerzo:** 8h
**Spec:** 20260126-Credentials §3.2 (Limites por Plan)

#### Problema

El modulo `jaraba_credentials` no tiene integracion con el sistema de plan limits. Todos los tenants pueden emitir credenciales ilimitadas independientemente de su plan.

#### Solucion

Integrar `jaraba_credentials` con `PlanValidator` para limitar:
- Numero de credenciales emitidas por mes
- Tipos de credenciales disponibles por plan
- Stacks disponibles por plan

#### Ficheros a modificar

| Fichero | Cambio |
|---------|--------|
| `jaraba_billing/src/Service/PlanValidator.php` | Nuevos metodos: `canIssueCredential()`, `canCreateStack()` |
| `jaraba_billing/src/Service/FeatureAccessService.php` | Features credential_* en FEATURE_ADDON_MAP |
| `jaraba_credentials/src/Controller/CredentialsApiController.php` | Validar plan antes de emitir |

#### Criterio de verificacion

- Plan Free: 10 credenciales/mes, sin stacks
- Plan Pro: 100 credenciales/mes, stacks ilimitados
- API retorna 403 con mensaje descriptivo al exceder limite

---

### P1-03: Conformidad de Rutas API

**Esfuerzo:** 6h
**Spec:** 20260126-API_Conventions §2.1 (Versionado RESTful)

#### Problema

Algunas rutas API no siguen la convencion `/api/v1/` definida en las specs:
- `/api/page-builder/generate-content` (falta v1)
- `/api/pages/{id}/sections` (falta v1)
- `/api/page-builder/section-templates` (falta v1)

#### Solucion

Migrar rutas al formato `/api/v1/` manteniendo backward compatibility con redirects 301.

#### Ficheros a modificar

| Fichero | Cambio |
|---------|--------|
| `jaraba_page_builder.routing.yml` | Actualizar paths a `/api/v1/` |
| Controladores afectados | Actualizar cualquier referencia interna |

#### Criterio de verificacion

- Todas las rutas API siguen el patron `/api/v1/{modulo}/{recurso}`
- Rutas legacy devuelven redirect 301 al nuevo path
- No hay rutas API sin versionado

---

### P1-04: Triggers Automaticos Credentials

**Esfuerzo:** 16h
**Spec:** 20260126-Credentials §5.1 (Automatizacion)

#### Problema

La emision de credenciales requiere accion manual del administrador. No hay hooks nativos que otorguen credenciales automaticamente cuando un usuario completa un logro (curso LMS, diagnostico, etc.).

#### Solucion

Implementar `CredentialTriggerSubscriber` que escuche eventos nativos de Drupal y emita credenciales automaticamente.

#### Ficheros a crear

| Fichero | Proposito |
|---------|-----------|
| `jaraba_credentials/src/EventSubscriber/CredentialTriggerSubscriber.php` | Event subscriber para triggers automaticos |

#### Ficheros a modificar

| Fichero | Cambio |
|---------|--------|
| `jaraba_credentials.services.yml` | Registro del event subscriber |
| `jaraba_credentials.module` | hook_entity_insert para entidades de logro |

#### Criterio de verificacion

- Completar un curso LMS emite credencial automaticamente
- Completar diagnostico con score >= 8 emite credencial
- Credenciales emitidas aparecen en el dashboard del usuario
- No hay duplicados (idempotencia)

---

### P1-05: Publicacion Programada

**Esfuerzo:** 18h
**Spec:** 20260126-Page_Builder §4.7 (Scheduled Publishing)

#### Problema

No existe funcionalidad de publicacion programada para paginas del Page Builder. Los tenants no pueden programar cuando una pagina se hace visible.

#### Solucion

Crear entity `ScheduledPublish` y servicio `ScheduledPublishService` con ejecucion via cron.

#### Ficheros a crear

| Fichero | Proposito |
|---------|-----------|
| `src/Entity/ScheduledPublish.php` | ContentEntityBase para registros de publicacion programada |
| `src/Service/ScheduledPublishService.php` | Servicio que ejecuta publicaciones pendientes |

#### Ficheros a modificar

| Fichero | Cambio |
|---------|--------|
| `jaraba_page_builder.module` | hook_cron() para ejecutar publicaciones pendientes |
| `jaraba_page_builder.services.yml` | Registro de ScheduledPublishService |
| `jaraba_page_builder.routing.yml` | Rutas API para programar/cancelar |

#### Criterio de verificacion

- Programar pagina para fecha futura funciona via API
- Cron publica paginas cuando llega la fecha
- Dashboard muestra estado "Programada" con fecha
- Cancelar publicacion programada funciona

---

## Fase 2: Cierre de Gaps Funcionales

**Esfuerzo total:** 164h
**Prioridad:** MEDIA - Features que completan la oferta de valor
**Dependencia:** Fase 1 debe estar completa

### P2-01: 55 Templates Verticales

**Esfuerzo:** 80h
**Spec:** 20260126-Page_Builder §4.1 (Templates por Vertical)

#### Problema

El Page Builder tiene ~48 templates de bloques genericos pero carece de templates verticalizados especificos para cada vertical del ecosistema:
- AgroConecta: ficha de producto, catalogo, trazabilidad
- FormaTech: landing de curso, programa formativo
- Empleabilidad: portfolio, job listing
- Emprendimiento: pitch deck, BMC visual
- ComercioConecta: catalogo de servicios
- ServiciosConecta: directorio profesional

#### Solucion

Crear 55 templates como config entities PageTemplate con sus correspondientes archivos Twig, organizados por vertical.

#### Criterio de verificacion

- 55 nuevas PageTemplate config entities importables via `drush cim`
- Cada template tiene preview PNG generado
- Templates usan design tokens var(--ej-*) exclusivamente
- Todos los textos en `{% trans %}` o `|t`

---

### P2-02: Endpoints SEO/GEO Faltantes

**Esfuerzo:** 24h
**Spec:** 20260126-SEO §3.1 (Endpoints), §3.4 (Schema.org)

#### Problema

Faltan 7 endpoints SEO/GEO especificados en las specs:
- robots.txt dinamico por tenant
- Sitemap por vertical
- Schema.org por tipo de pagina vertical
- Open Graph preview API
- Structured data validation
- Hreflang por tenant-domain
- AMP pages API

#### Solucion

Implementar los 7 endpoints en `SitemapController` y crear `SchemaOrgVerticalService`.

#### Criterio de verificacion

- Todos los endpoints responden con formato correcto
- Schema.org valida en Google Rich Results Test
- Open Graph preview genera imagen y metadatos

---

### P2-03: Workflow Editorial i18n

**Esfuerzo:** 14h
**Spec:** 20260126-i18n §4.1 (Workflow)

#### Problema

No existe workflow de aprobacion para traducciones. Las traducciones se publican inmediatamente sin revision.

#### Solucion

Implementar workflow Draft > Review > Published para content translations usando Content Moderation de Drupal Core.

#### Criterio de verificacion

- Traducciones nuevas entran en estado Draft
- Solo editores pueden mover a Review
- Solo admins pueden publicar
- Email de notificacion en cada transicion

---

### P2-04: Integraciones Analytics Externas

**Esfuerzo:** 16h
**Spec:** 20260126-Analytics §2.1 (Integraciones)

#### Problema

El `AnalyticsService` solo recopila metricas internas. No hay integracion con Google Analytics 4 ni Google Search Console.

#### Solucion

Crear adaptadores para GA4 Measurement Protocol y Search Console API.

#### Criterio de verificacion

- Eventos de pagina se envian a GA4 via Measurement Protocol
- Datos de Search Console se importan diariamente via cron
- Dashboard muestra metricas combinadas

---

### P2-05: Sugerencias de Imagen con IA

**Esfuerzo:** 10h
**Spec:** 20260126-AI §5.3 (Image Suggestions)

#### Problema

El generador de contenido con IA sugiere textos pero no imagenes. Los tenants deben buscar imagenes manualmente.

#### Solucion

Extender `AiContentController` con endpoint de sugerencias de imagen basado en el contexto del contenido.

#### Criterio de verificacion

- API sugiere 3-5 imagenes relevantes por contexto
- Integracion con Unsplash API para imagenes libres
- Preview de imagen en el Canvas Editor

---

### P2-06: Remediacion Colores/Iconos en Modulos

**Esfuerzo:** 20h
**Spec:** 20260126-UI §6.1 (Design Tokens), §6.2 (Iconografia)

#### Problema

Varios modulos usan colores hardcodeados en lugar de design tokens y emojis Unicode en lugar de `jaraba_icon()`.

#### Solucion

Auditar y remediar todos los modulos afectados.

#### Criterio de verificacion

- 0 colores hardcodeados en archivos SCSS/CSS nuevos
- 0 emojis Unicode en templates Twig
- Todos los iconos via `jaraba_icon()`

---

## Fase 3: Excelencia y Calidad

**Esfuerzo total:** 60h
**Prioridad:** MEDIA-BAJA - Robustez y calidad de la plataforma
**Dependencia:** Fase 2 parcialmente completa

### P3-01: Tests Unitarios Credentials

**Esfuerzo:** 24h
**Spec:** 20260126-Credentials §8.1 (Testing)

#### Problema

El modulo `jaraba_credentials` tiene solo ~20% de cobertura de tests. Faltan tests para:
- CredentialVerifier
- StackEvaluationService
- StackProgressTracker
- RevocationService
- AccessibilityAuditService
- CredentialTriggerSubscriber
- CredentialStack entity
- UserStackProgress entity

#### Solucion

Crear 8 archivos de test unitario siguiendo el patron existente en el proyecto.

#### Criterio de verificacion

- 8 archivos de test creados y pasando
- Cobertura de codigo >= 70% para servicios criticos
- `lando phpunit` sin errores

---

### P3-02: Portabilidad Credentials

**Esfuerzo:** 16h
**Spec:** 20260126-Credentials §6.1 (Export)

#### Problema

Las credenciales solo existen dentro de la plataforma. No hay exportacion a formatos estandar.

#### Solucion

Implementar exportacion a:
- LinkedIn Certificate format
- Europass Digital Credentials
- Open Badges v3

#### Criterio de verificacion

- Export a LinkedIn genera URL shareable valida
- Export Europass genera XML valido contra schema
- Open Badges v3 JSON-LD valida contra spec

---

### P3-03: A/B Testing Multivariate

**Esfuerzo:** 20h
**Spec:** 20260126-AB_Testing §4.1 (Multivariate)

#### Problema

El sistema de A/B testing actual solo soporta 2 variantes (A vs B). Las specs requieren soporte multivariate con N variantes.

#### Solucion

Extender `ExperimentService` y `ExperimentApiController` para soportar N variantes con distribucion de trafico configurable.

#### Criterio de verificacion

- Crear experimento con 3+ variantes funciona
- Distribucion de trafico es configurable por variante
- Resultados muestran metricas por cada variante
- Significancia estadistica calculada correctamente

---

## Fase 4: Deuda Tecnica

**Esfuerzo total:** 64h
**Prioridad:** BAJA - Mantenibilidad a largo plazo
**Dependencia:** Puede ejecutarse en paralelo con Fase 3

### P4-01: Remediacion 686 Colores SCSS

**Esfuerzo:** 40h
**Spec:** 20260126-SCSS §2.1 (Design Tokens)

#### Problema

Auditoria automatizada detecto 686 instancias de colores hardcodeados en archivos SCSS del proyecto. Esto viola la directriz de usar exclusivamente tokens `var(--ej-*)` con fallback SCSS.

#### Solucion

Reemplazar sistematicamente los 686 colores por tokens del design system. Mapeo de la paleta oficial:
- Naranja hardcodeado → `var(--ej-color-primary, #{$_ej-color-primary})` (#FF8C42 impulse)
- Turquesa hardcodeado → `var(--ej-color-secondary, #{$_ej-color-secondary})` (#00A9A5 innovation)
- Azul corporativo hardcodeado → `var(--ej-color-corporate, #{$_ej-color-corporate})` (#233D63)
- Verde oliva hardcodeado → `var(--ej-color-agro, #{$_ej-color-agro})` (#556B2F)
- Emerald hardcodeado → `var(--ej-color-success, #{$_ej-color-success})` (#10B981)
- Amber hardcodeado → `var(--ej-color-warning, #{$_ej-color-warning})` (#F59E0B)
- Red hardcodeado → `var(--ej-color-danger, #{$_ej-color-danger})` (#EF4444)

#### Criterio de verificacion

- `grep -r '#[0-9a-fA-F]' scss/` retorna 0 resultados
- Compilacion SCSS sin errores
- Visual regression test: sin cambios visibles

---

### P4-02: Limpieza Emojis Unicode

**Esfuerzo:** 16h
**Spec:** 20260126-UI §6.2 (Iconografia)

#### Problema

Multiples archivos PHP y Twig usan emojis Unicode directamente en lugar de la funcion `jaraba_icon()`.

#### Solucion

Reemplazar todos los emojis por llamadas a `jaraba_icon()` con el icono SVG correspondiente.

#### Criterio de verificacion

- `grep -rP '[\x{1F300}-\x{1F9FF}]' --include='*.php' --include='*.twig'` retorna 0
- Iconos renderizan correctamente en modo outline y duotone
- No hay regresiones visuales

---

### P4-03: Documentacion y Directrices

**Esfuerzo:** 8h
**Spec:** 20260126-Docs §1.1 (Mantenimiento)

#### Problema

Las directrices del proyecto necesitan actualizacion con los patrones nuevos descubiertos durante la auditoria.

#### Solucion

- Actualizar `docs/00_DIRECTRICES_PROYECTO.md` con nuevos patrones
- Crear documento de aprendizajes
- Actualizar indice general

#### Criterio de verificacion

- Directrices reflejan todos los patrones actuales
- Documento de aprendizajes creado
- Indice general actualizado

---

## Correspondencia con Specs

| Spec 20260126 | Seccion | Tarea | Estado |
|----------------|---------|-------|--------|
| Page_Builder §4.3 | Premium Blocks Assets | P0-01 | Implementado: 2 SCSS sources + 2 CSS compiled + 2 JS adapters |
| Page_Builder §7.1 | Accesibilidad ARIA | P0-02 | Implementado: AccessibilityValidatorService + API + validator.js |
| Page_Builder §7.2-7.3 | Contraste y Teclado | P0-03 | Implementado: contrast-checker.js + prefers-reduced-motion en SCSS |
| Page_Builder §3.1 | Limites por Plan | P1-01 | Por implementar |
| Credentials §3.2 | Limites por Plan | P1-02 | Por implementar |
| API_Conventions §2.1 | Versionado RESTful | P1-03 | Por implementar |
| Credentials §5.1 | Automatizacion | P1-04 | Por implementar |
| Page_Builder §4.7 | Scheduled Publishing | P1-05 | Por implementar |
| Page_Builder §4.1 | Templates Verticales | P2-01 | Por implementar |
| SEO §3.1, §3.4 | Endpoints SEO/GEO | P2-02 | Por implementar |
| i18n §4.1 | Workflow Editorial | P2-03 | Por implementar |
| Analytics §2.1 | Integraciones | P2-04 | Por implementar |
| AI §5.3 | Image Suggestions | P2-05 | Por implementar |
| UI §6.1-6.2 | Colores/Iconos Modulos | P2-06 | Por implementar |
| Credentials §8.1 | Testing | P3-01 | Por implementar |
| Credentials §6.1 | Portabilidad | P3-02 | Por implementar |
| AB_Testing §4.1 | Multivariate | P3-03 | Por implementar |
| SCSS §2.1 | Design Tokens | P4-01 | Por implementar |
| UI §6.2 | Emojis → jaraba_icon() | P4-02 | Por implementar |
| Docs §1.1 | Documentacion | P4-03 | Por implementar |

---

## Cumplimiento de Directrices

Cada tarea de este plan se verificara contra las 12 directrices del proyecto:

| # | Directriz | Verificacion |
|---|-----------|-------------|
| 1 | SCSS con `@use` Dart Sass + `var(--ej-*)` con fallback SCSS | Todos los archivos SCSS nuevos usan `@use` en vez de `@import`, y todos los colores usan tokens con fallback |
| 2 | `jaraba_icon()` en vez de emojis Unicode | 0 emojis Unicode en codigo nuevo; todos los iconos via `jaraba_icon('nombre', 'outline')` |
| 3 | Templates limpios sin regiones Drupal, partials via `{% include %}` | Templates zero-region, componentes reutilizables via `{% include '@ecosistema_jaraba_theme/partials/_name.html.twig' %}` |
| 4 | Body classes SOLO via `hook_preprocess_html()` | Ninguna clase body anadida en templates Twig; todas via hook en `.module` |
| 5 | Slide-panel para modals/CRUD | Todas las operaciones CRUD usan `data-dialog-type="modal"` + `ecosistema_jaraba_theme/slide-panel` |
| 6 | Todos los strings en `t()` / `{% trans %}` / `Drupal.t()` | 100% de strings user-facing traducibles |
| 7 | ContentEntityBase con Field UI + Views + admin/structure + admin/content | Nuevas entidades siguen patron: fieldable=TRUE, views_data handler, admin routes |
| 8 | Hooks nativos (NO ECA YAML) | Toda la automatizacion via `hook_entity_insert()`, `hook_cron()`, EventSubscribers |
| 9 | Paleta de 7 colores oficiales Jaraba | Todos los colores nuevos provienen de la paleta oficial via tokens |
| 10 | 3-layer sync: Route+Controller → preprocess_html → page template | Cada pagina nueva sigue el patron de 3 capas |
| 11 | Mobile-first responsive, full-width layout | Media queries mobile-first, layout sin sidebar por defecto |
| 12 | WCAG 2.1 AA (focus-visible, prefers-reduced-motion, ARIA) | Todos los componentes nuevos cumplen AA minimo |

---

## Criterios de Verificacion

### Verificacion Global por Fase

| Fase | Comando de verificacion | Criterio de exito |
|------|------------------------|-------------------|
| Fase 0 | `lando drush cr` | 0 errores, 0 warnings de archivos faltantes |
| Fase 0 | axe-core scan en navegador | 0 violations nivel A, 0 violations nivel AA |
| Fase 0 | Network tab navegador | 0 errores 404 para assets premium |
| Fase 1 | `lando phpunit --group=billing` | Todos los tests pasan |
| Fase 1 | API testing manual | Todos los endpoints responden correctamente |
| Fase 2 | `lando drush cim --dry-run` | Config entities importan sin conflictos |
| Fase 2 | Visual review en navegador | Templates renderizan correctamente |
| Fase 3 | `lando phpunit --group=credentials` | 8 archivos de test pasan |
| Fase 3 | Export funcional | Archivos exportados validan contra schemas |
| Fase 4 | `grep` colores hardcodeados | 0 resultados en archivos nuevos |
| Fase 4 | `npx sass scss/:css/` | Compilacion sin errores |

### Estimacion de Esfuerzo por Fase

| Fase | Horas | % del Total | Dependencias |
|------|-------|-------------|-------------|
| Fase 0: Bloqueantes Criticos | 46h | 11.7% | Ninguna |
| Fase 1: Infraestructura Base | 60h | 15.2% | Fase 0 |
| Fase 2: Gaps Funcionales | 164h | 41.6% | Fase 1 |
| Fase 3: Excelencia y Calidad | 60h | 15.2% | Fase 2 parcial |
| Fase 4: Deuda Tecnica | 64h | 16.3% | Independiente |
| **TOTAL** | **394h** | **100%** | |

---

*Documento generado el 2026-02-12. Seguimiento en este mismo archivo.*
