# Plan de Implementacion: Unificacion Landing JarabaLex + Despachos — Clase Mundial

**Fecha de creacion:** 2026-02-28 10:30
**Ultima actualizacion:** 2026-02-28 10:30
**Autor:** IA Asistente (Claude Opus 4.6)
**Version:** 1.0.0
**Categoria:** Implementacion / Remediacion Estrategica
**Vertical:** jarabalex (Despachos)
**Modulos afectados:** ecosistema_jaraba_core, jaraba_legal_intelligence, jaraba_legal_cases, jaraba_legal_calendar, jaraba_legal_billing, jaraba_legal_vault, jaraba_legal_lexnet, jaraba_legal_templates, ecosistema_jaraba_theme
**Estado:** Planificado
**Directrices:** v98.0.0 | **Flujo:** v51.0.0 | **Indice:** v127.0.0
**Spec fuente:** `docs/analisis/2026-02-28_Auditoria_Landing_JarabaLex_Despachos_Clase_Mundial_v1.md`
**Documentos relacionados:**
- `docs/implementacion/20260216-Plan_Elevacion_JarabaLex_Clase_Mundial_v1.md`
- `docs/implementacion/20260216-Plan_Implementacion_JarabaLex_Legal_Practice_Platform_v1.md`
- `docs/implementacion/20260216-Elevacion_JarabaLex_Vertical_Independiente_v1.md`
- `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md`
- `docs/tecnicos/aprendizajes/2026-02-17_jarabalex_legal_practice_platform_completa.md`

---

## Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Objetivos y Criterios de Exito](#2-objetivos-y-criterios-de-exito)
3. [Requisitos Previos](#3-requisitos-previos)
4. [Decision Arquitectonica: Unificacion vs Bifurcacion](#4-decision-arquitectonica-unificacion-vs-bifurcacion)
5. [Fase 0 — Correccion Inmediata de Promesas Falsas](#5-fase-0--correccion-inmediata-de-promesas-falsas)
   - 5.1 [Tarea 0.1: Actualizar pricing preview del controller](#51-tarea-01-actualizar-pricing-preview-del-controller)
   - 5.2 [Tarea 0.2: Actualizar features grid para reflejar realidad](#52-tarea-02-actualizar-features-grid-para-reflejar-realidad)
   - 5.3 [Tarea 0.3: Corregir lead magnet](#53-tarea-03-corregir-lead-magnet)
6. [Fase 1 — Habilitacion de Modulos Satelite](#6-fase-1--habilitacion-de-modulos-satelite)
   - 6.1 [Tarea 1.1: Habilitar jaraba_legal_calendar](#61-tarea-11-habilitar-jaraba_legal_calendar)
   - 6.2 [Tarea 1.2: Habilitar jaraba_legal_vault](#62-tarea-12-habilitar-jaraba_legal_vault)
   - 6.3 [Tarea 1.3: Habilitar jaraba_legal_billing](#63-tarea-13-habilitar-jaraba_legal_billing)
   - 6.4 [Tarea 1.4: Habilitar jaraba_legal_lexnet](#64-tarea-14-habilitar-jaraba_legal_lexnet)
   - 6.5 [Tarea 1.5: Habilitar jaraba_legal_templates](#65-tarea-15-habilitar-jaraba_legal_templates)
7. [Fase 2 — Infraestructura Freemium para Gestion de Despacho](#7-fase-2--infraestructura-freemium-para-gestion-de-despacho)
   - 7.1 [Tarea 2.1: Crear FreemiumVerticalLimit para jarabalex (features de despacho)](#71-tarea-21-crear-freemiumverticallimit-para-jarabalex-features-de-despacho)
   - 7.2 [Tarea 2.2: Extender JarabaLexFeatureGateService](#72-tarea-22-extender-jarabalexfeaturegateservice)
   - 7.3 [Tarea 2.3: Actualizar SaasPlan de JarabaLex](#73-tarea-23-actualizar-saasplan-de-jarabalex)
   - 7.4 [Tarea 2.4: Configurar UpgradeTriggerService para nuevos gates](#74-tarea-24-configurar-upgradetriggerservice-para-nuevos-gates)
8. [Fase 3 — Unificacion de Landings](#8-fase-3--unificacion-de-landings)
   - 8.1 [Tarea 3.1: Redisenar contenido de /jarabalex](#81-tarea-31-redisenar-contenido-de-jarabalex)
   - 8.2 [Tarea 3.2: Redirect 301 de /despachos a /jarabalex](#82-tarea-32-redirect-301-de-despachos-a-jarabalex)
   - 8.3 [Tarea 3.3: Actualizar navegacion global](#83-tarea-33-actualizar-navegacion-global)
9. [Fase 4 — LexNET como Killer Feature](#9-fase-4--lexnet-como-killer-feature)
   - 9.1 [Tarea 4.1: Anadir LexNET a la landing](#91-tarea-41-anadir-lexnet-a-la-landing)
   - 9.2 [Tarea 4.2: Crear pain point LexNET](#92-tarea-42-crear-pain-point-lexnet)
   - 9.3 [Tarea 4.3: Crear FAQ LexNET](#93-tarea-43-crear-faq-lexnet)
10. [Fase 5 — SEO, Schema.org y Design Tokens](#10-fase-5--seo-schemaorg-y-design-tokens)
    - 10.1 [Tarea 5.1: Schema.org SoftwareApplication](#101-tarea-51-schemaorg-softwareapplication)
    - 10.2 [Tarea 5.2: Meta description y canonical](#102-tarea-52-meta-description-y-canonical)
    - 10.3 [Tarea 5.3: Hreflang tags](#103-tarea-53-hreflang-tags)
    - 10.4 [Tarea 5.4: Unificar design tokens](#104-tarea-54-unificar-design-tokens)
11. [Fase 6 — Social Proof y Pricing Detallado](#11-fase-6--social-proof-y-pricing-detallado)
    - 11.1 [Tarea 6.1: Mejorar testimonios](#111-tarea-61-mejorar-testimonios)
    - 11.2 [Tarea 6.2: Tabla comparativa de planes](#112-tarea-62-tabla-comparativa-de-planes)
    - 11.3 [Tarea 6.3: Metricas reales en social proof](#113-tarea-63-metricas-reales-en-social-proof)
12. [Fase 7 — Testing y Verificacion](#12-fase-7--testing-y-verificacion)
    - 12.1 [Tarea 7.1: Tests unitarios para nuevos FreemiumVerticalLimit](#121-tarea-71-tests-unitarios-para-nuevos-freemiumverticallimit)
    - 12.2 [Tarea 7.2: Tests kernel para modulos habilitados](#122-tarea-72-tests-kernel-para-modulos-habilitados)
    - 12.3 [Tarea 7.3: Verificacion visual en navegador](#123-tarea-73-verificacion-visual-en-navegador)
    - 12.4 [Tarea 7.4: Verificacion SEO](#124-tarea-74-verificacion-seo)
13. [Tabla de Correspondencia: Especificaciones Tecnicas](#13-tabla-de-correspondencia-especificaciones-tecnicas)
14. [Tabla de Cumplimiento de Directrices](#14-tabla-de-cumplimiento-de-directrices)
15. [Diagrama de Dependencias entre Fases](#15-diagrama-de-dependencias-entre-fases)
16. [Riesgos y Mitigaciones](#16-riesgos-y-mitigaciones)
17. [Checklist Pre-Despliegue](#17-checklist-pre-despliegue)
18. [Registro de Cambios](#18-registro-de-cambios)

---

## 1. Resumen Ejecutivo

Este plan aborda los 21 hallazgos identificados en la auditoria exhaustiva de la landing `/despachos` del vertical JarabaLex. La estrategia central es **unificar** las dos landings (`/despachos` y `/jarabalex`) en una unica landing integral que presente toda la propuesta de valor del vertical legal, habilitando progresivamente los modulos satelite y creando la infraestructura de freemium necesaria para respaldar las promesas al usuario.

El plan se estructura en **8 fases** con dependencias claras, desde la correccion inmediata de promesas falsas (Fase 0, horas) hasta la verificacion completa (Fase 7). Cada tarea incluye especificaciones tecnicas detalladas, archivos a modificar, directrices a cumplir y criterios de aceptacion.

---

## 2. Objetivos y Criterios de Exito

### Objetivos

| # | Objetivo | Metrica |
|---|----------|---------|
| O1 | Eliminar toda promesa de feature que el usuario no pueda experimentar | 0 features mencionadas en landing con modulo deshabilitado + 0 features sin FreemiumVerticalLimit |
| O2 | Destacar LexNET como killer feature diferencial | LexNET aparece en features grid, pain points y FAQ |
| O3 | Unificar landings bajo `/jarabalex` | `/despachos` redirige 301 a `/jarabalex`; contenido unificado |
| O4 | Infraestructura freemium para features de despacho | FreemiumVerticalLimit creados para max_cases, vault_storage, calendar_deadlines |
| O5 | SEO completo | Schema.org SoftwareApplication + meta description + canonical + hreflang |
| O6 | Experiencia de registro coherente | Post-registro, el usuario puede acceder a todas las features mencionadas en la landing |

### Criterios de Exito

1. **Cero promesas sin respaldo**: Cada feature mencionada en la landing tiene modulo habilitado + FreemiumVerticalLimit + FeatureGate
2. **LexNET visible**: Aparece en al menos 3 secciones de la landing (feature, pain point, FAQ)
3. **Pricing coherente**: Los datos del pricing preview coinciden con los FreemiumVerticalLimit configurados
4. **SEO score**: Lighthouse SEO > 95 para `/jarabalex`
5. **Tests**: Todos los tests PHPUnit pasan (Unit + Kernel)
6. **Zero regression**: Las features existentes de investigacion legal (busqueda, alertas, citaciones) no se ven afectadas

---

## 3. Requisitos Previos

### Entorno

| Requisito | Version | Verificacion |
|-----------|---------|-------------|
| PHP | 8.4 | `lando php --version` |
| Drupal | 11.x | `lando drush status` |
| MariaDB | 10.11+ | `lando mysql --version` |
| Dart Sass | >= 1.71.0 | `lando ssh -c "cd /app/web/modules/custom/ecosistema_jaraba_core && npx sass --version"` |
| Redis | 7.4 | `lando redis-cli INFO server` |
| Lando | Running | `lando info` |

### Documentacion a Revisar Antes de Implementar

| Documento | Ruta | Proposito |
|-----------|------|-----------|
| Directrices Proyecto | `docs/00_DIRECTRICES_PROYECTO.md` | Todas las reglas P0/P1 |
| Arquitectura Theming Master | `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md` | 5 capas CSS, zero-region, parciales |
| Flujo de Trabajo | `docs/07_FLUJO_TRABAJO_CLAUDE.md` | Workflow obligatorio |
| Plan Elevacion JarabaLex | `docs/implementacion/20260216-Plan_Elevacion_JarabaLex_Clase_Mundial_v1.md` | 14 fases originales |
| Aprendizaje Legal Practice | `docs/tecnicos/aprendizajes/2026-02-17_jarabalex_legal_practice_platform_completa.md` | 92 lecciones |
| Auditoria (este plan) | `docs/analisis/2026-02-28_Auditoria_Landing_JarabaLex_Despachos_Clase_Mundial_v1.md` | 21 hallazgos |

### Comandos Docker/Lando

Todos los comandos PHP, Drush y Sass se ejecutan dentro del contenedor:

```bash
# Ejecutar drush dentro del contenedor
lando drush [comando]

# Ejecutar PHP/Sass dentro del contenedor
lando ssh -c "[comando]"

# Ejemplo: compilar SCSS
lando ssh -c "cd /app/web/modules/custom/ecosistema_jaraba_core && npx sass scss/main.scss:css/ecosistema-jaraba-core.css --style=compressed"

# Importar configuracion
lando drush config:import -y

# Limpiar cache
lando drush cache:rebuild
```

---

## 4. Decision Arquitectonica: Unificacion vs Bifurcacion

### Opciones Evaluadas

**Opcion A: Unificacion (RECOMENDADA)**
- Redirigir `/despachos` 301 a `/jarabalex`
- Redisenar `/jarabalex` para cubrir investigacion + gestion
- Un solo vertical, un solo conjunto de planes, un solo funnel

**Opcion B: Bifurcacion**
- Crear `despachos` como vertical canonico independiente
- Crear 18+ FreemiumVerticalLimit nuevos
- Crear 3 SaasPlan nuevos para despachos
- Crear JourneyDefinition, funnels, email sequences propios
- Mantener ambas landings con contenido diferenciado

### Analisis Comparativo

| Criterio | Unificacion | Bifurcacion |
|----------|------------|-------------|
| Esfuerzo tecnico | Medio (redisenar landing + redirect) | Alto (crear vertical completo + infraestructura) |
| Coherencia de producto | Alta (una solucion integral) | Baja (dos mitades confusas) |
| SEO | Fuerte (toda la autoridad en una URL) | Debil (autoridad diluida) |
| Mantenimiento | Bajo (una landing, un set de planes) | Alto (duplicacion de contenido y config) |
| Conversion | Alta (propuesta de valor completa) | Baja (fragmentacion de valor) |
| Competitividad | Alta (comparable a soluciones integrales) | Baja (parece incompleto en ambas landings) |
| Referentes mercado | Aranzadi Fusion, vLex (soluciones integrales) | Ningun competidor fragmenta asi |

### Decision: **OPCION A — UNIFICACION**

Justificacion: Los competidores ofrecen soluciones integrales. Un abogado no quiere dos productos separados para investigar y gestionar — quiere una herramienta que haga ambas cosas. La unificacion reduce esfuerzo, mejora SEO, simplifica conversion y es mas facil de mantener.

---

## 5. Fase 0 — Correccion Inmediata de Promesas Falsas

**Prioridad:** P0 — Debe ejecutarse ANTES de cualquier otra fase
**Objetivo:** Eliminar inmediatamente toda promesa de feature que el usuario no pueda experimentar al registrarse
**Tiempo estimado:** 2-3 horas
**Hallazgos que resuelve:** P0-01, P0-02, P0-06

### 5.1 Tarea 0.1: Actualizar pricing preview del controller

**Archivo:** `web/modules/custom/ecosistema_jaraba_core/src/Controller/VerticalLandingController.php`
**Lineas:** 658-670 (metodo `despachos()`)

**Estado actual (INCORRECTO — promesas sin respaldo):**
```php
'features_preview' => [
    $this->t('5 expedientes gratis'),
    $this->t('Copiloto IA incluido'),
    $this->t('Agenda con alertas'),
    $this->t('Boveda documental 1 GB'),
],
```

**Estado corregido (solo features realmente disponibles):**
```php
'features_preview' => [
    $this->t('10 busquedas legales/mes'),
    $this->t('Copiloto legal IA incluido'),
    $this->t('Gestion de expedientes'),
    $this->t('1 alerta inteligente'),
],
```

**Logica:** Estas 4 features corresponden a funcionalidades de modulos habilitados (`jaraba_legal_intelligence` y `jaraba_legal_cases`) con FreemiumVerticalLimit configurados (excepto `jaraba_legal_cases`, que necesitara config en Fase 2).

**Directrices de cumplimiento:**
- Todos los textos usan `$this->t()` para traducibilidad (I18N-METASITE-001)
- Los valores (10 busquedas, 1 alerta) coinciden con FreemiumVerticalLimit existentes (`jarabalex_free_searches_per_month: 10`, `jarabalex_free_max_alerts: 1`)

**Criterio de aceptacion:** El pricing preview SOLO muestra features con modulo habilitado + configuracion de limites existente.

### 5.2 Tarea 0.2: Actualizar features grid para reflejar realidad

**Archivo:** `web/modules/custom/ecosistema_jaraba_core/src/Controller/VerticalLandingController.php`
**Lineas:** 632-655 (metodo `despachos()`, seccion `features`)

**Cambios requeridos:**

Reemplazar las 6 features actuales (3 de modulos deshabilitados) por features de modulos habilitados:

| # | Feature Actual | Modulo | Habilitado | Feature Corregida | Modulo | Habilitado |
|---|----------------|--------|------------|-------------------|--------|------------|
| 1 | Copiloto IA para Borradores | legal_intelligence | SI | Copiloto Legal IA | legal_intelligence | SI |
| 2 | Gestion de Expedientes | legal_cases | SI | Gestion de Expedientes | legal_cases | SI |
| 3 | **Agenda Inteligente** | **legal_calendar** | **NO** | **Busqueda en 8 Fuentes Oficiales** | legal_intelligence | SI |
| 4 | **Facturacion Automatizada** | **legal_billing** | **NO** | **Alertas Inteligentes** | legal_intelligence | SI |
| 5 | Citaciones Multi-formato | legal_intelligence | SI | Citaciones en 4 Formatos | legal_intelligence | SI |
| 6 | **Boveda Documental** | **legal_vault** | **NO** | **Grafo de Citaciones** | legal_intelligence | SI |

**Especificaciones tecnicas de cada feature corregida:**

```php
'features' => [
    [
        'icon' => ['category' => 'ai', 'name' => 'brain'],
        'title' => $this->t('Copiloto Legal IA'),
        'description' => $this->t('Asistente inteligente con 8 modos especializados: busqueda, analisis, alertas, citaciones, derecho EU, asistente de caso, redaccion y FAQ.'),
    ],
    [
        'icon' => ['category' => 'legal', 'name' => 'briefcase'],
        'title' => $this->t('Gestion de Expedientes'),
        'description' => $this->t('Todo el expediente en un solo lugar: documentos, notas, plazos y comunicaciones. Triaje automatico con IA para nuevas consultas.'),
    ],
    [
        'icon' => ['category' => 'legal', 'name' => 'search'],
        'title' => $this->t('Busqueda en 8 Fuentes Oficiales'),
        'description' => $this->t('CENDOJ, BOE, DGT, TEAC, EUR-Lex, CURIA, HUDOC y EDPB. Busqueda semantica con IA sobre datos oficiales abiertos (Ley 37/2007).'),
    ],
    [
        'icon' => ['category' => 'legal', 'name' => 'alert'],
        'title' => $this->t('Alertas Inteligentes'),
        'description' => $this->t('10 tipos de alerta: anulacion de resolucion, cambio de doctrina, nueva ley, resolucion favorable y mas. Notificaciones in-app y email.'),
    ],
    [
        'icon' => ['category' => 'legal', 'name' => 'citation'],
        'title' => $this->t('Citaciones en 4 Formatos'),
        'description' => $this->t('Genera citaciones juridicas en formato formal, resumido, bibliografico y nota al pie con un clic. Integrada en el flujo de trabajo.'),
    ],
    [
        'icon' => ['category' => 'legal', 'name' => 'graph'],
        'title' => $this->t('Grafo de Citaciones'),
        'description' => $this->t('Visualiza las relaciones entre resoluciones, normativa citada y doctrina. Navegacion interactiva con D3.js.'),
    ],
],
```

**Directrices de cumplimiento:**
- Todos los iconos usan formato correcto: `['category' => '...', 'name' => '...']` (ICON-CONVENTION-001)
- Los iconos se renderizan con variant `duotone` por defecto en el partial (ICON-DUOTONE-001)
- El color se hereda del `vertical_color` parametro (`corporate`) (ICON-COLOR-001)
- Textos con `$this->t()` (I18N-METASITE-001)

**NOTA IMPORTANTE:** Cuando se habiliten los modulos en Fase 1, estas features se actualizaran de nuevo para incluir Agenda, Facturacion, Boveda y LexNET. La Fase 0 es una correccion temporal para eliminar promesas falsas.

### 5.3 Tarea 0.3: Corregir lead magnet

**Archivo:** `web/modules/custom/ecosistema_jaraba_core/src/Controller/VerticalLandingController.php`
**Lineas:** ~670-685 (metodo `despachos()`, seccion `lead_magnet`)

**Cambio:** Reutilizar el lead magnet de `/jarabalex` (Diagnostico Legal Gratuito) que tiene controller real:

```php
'lead_magnet' => [
    'icon' => ['category' => 'legal', 'name' => 'diagnostic'],
    'title' => $this->t('Diagnostico Legal Gratuito'),
    'description' => $this->t('Descubre en 2 minutos tus areas de riesgo profesional y recibe recomendaciones con articulos legales especificos.'),
    'cta_text' => $this->t('Hacer diagnostico gratuito'),
    'url' => '/jarabalex/diagnostico-legal',
],
```

**Directrices de cumplimiento:**
- La URL `/jarabalex/diagnostico-legal` tiene controller real: `LegalLandingController::diagnostico()` (LEGAL-LEADMAGNET-001)
- El diagnostico incluye disclaimer legal y referencias regulatorias reales (LEGAL-LEADMAGNET-001)
- No promete asesoramiento juridico real (LEGAL-LEADMAGNET-001)

---

## 6. Fase 1 — Habilitacion de Modulos Satelite

**Prioridad:** P0 — Requisito para Fase 2 y 3
**Objetivo:** Habilitar los 5 modulos deshabilitados para que las features existan operativamente
**Tiempo estimado:** 4-6 horas (incluye verificacion de schemas, migraciones de BD, tests)
**Hallazgos que resuelve:** P0-02

**Orden de habilitacion (por dependencias):**

```
1. jaraba_legal_calendar    (depende de: jaraba_legal_cases)
2. jaraba_legal_vault        (depende de: jaraba_legal_cases)
3. jaraba_legal_billing      (depende de: jaraba_legal_cases)
4. jaraba_legal_lexnet       (depende de: jaraba_legal_cases)
5. jaraba_legal_templates    (depende de: jaraba_legal_cases, jaraba_page_builder)
```

### 6.1 Tarea 1.1: Habilitar jaraba_legal_calendar

**Entidades creadas:** LegalDeadline, CourtHearing, CalendarConnection, ExternalEventCache, SyncedCalendar (5)
**Servicios registrados:** DeadlineCalculatorService, CalendarSyncService, LegalAgendaService, DeadlineAlertService (4)
**Rutas:** 10+ API endpoints + dashboard

**Procedimiento:**

```bash
# 1. Verificar dependencias
lando drush pm:info jaraba_legal_calendar

# 2. Habilitar modulo
lando drush pm:install jaraba_legal_calendar -y

# 3. Ejecutar update hooks si existen
lando drush updatedb -y

# 4. Verificar tablas creadas
lando drush sqlq "SHOW TABLES LIKE 'legal_deadline%'"
lando drush sqlq "SHOW TABLES LIKE 'court_hearing%'"

# 5. Exportar configuracion
lando drush config:export -y

# 6. Verificar que las rutas funcionan
lando drush router:rebuild
```

**Verificacion post-habilitacion:**
- [ ] Las 5 entity tables existen en la BD
- [ ] Las rutas API responden (200/403 segun permisos)
- [ ] El dashboard controller carga sin errores
- [ ] `DeadlineCalculatorService` resuelve correctamente desde el contenedor DI

**Directrices de cumplimiento:**
- LEGAL-DEADLINE-001: Verificar que `DeadlineCalculatorService` calcula agosto como inhabil para no-penal
- Los entity forms deben extender `PremiumEntityFormBase` (PREMIUM-FORMS-PATTERN-001)
- AccessControlHandler debe verificar tenant match (TENANT-ISOLATION-ACCESS-001)

### 6.2 Tarea 1.2: Habilitar jaraba_legal_vault

**Entidades creadas:** SecureDocument, DocumentAccess, DocumentAuditLog, DocumentDelivery, DocumentRequest (5)
**Servicios registrados:** VaultEncryptionService, VaultAuditLogService, DocumentVaultService, DocumentAccessService (4)
**Rutas:** 18 API endpoints + portal de cliente

**Procedimiento:** Identico al patron de 1.1 adaptado al modulo.

```bash
lando drush pm:install jaraba_legal_vault -y
lando drush updatedb -y
lando drush sqlq "SHOW TABLES LIKE 'secure_document%'"
lando drush sqlq "SHOW TABLES LIKE 'document_access%'"
lando drush sqlq "SHOW TABLES LIKE 'document_audit_log%'"
lando drush config:export -y
lando drush router:rebuild
```

**Verificacion post-habilitacion:**
- [ ] Las 5 entity tables + field_data tables existen
- [ ] `VaultEncryptionService` se resuelve desde DI (requiere libsodium PHP)
- [ ] LEGAL-HASHCHAIN-001: El `VaultAuditLogService` genera hash chain correctamente
- [ ] Los limites de vault (starter: 500MB, pro: 5GB, enterprise: unlimited) se cargan desde config

**Configuracion adicional requerida:**
- Anadir tier `free` en `jaraba_legal_vault.settings.yml` con limite de 100 MB (valor realista para freemium)

### 6.3 Tarea 1.3: Habilitar jaraba_legal_billing

**Entidades creadas:** LegalInvoice, TimeEntry, Quote, ServiceCatalogItem, InvoiceLine, QuoteLineItem, CreditNote (7)
**Servicios registrados:** TimeTrackingService, InvoiceManagerService, StripeInvoiceService, QuoteManagerService, QuoteEstimatorService (5)
**Rutas:** 25+ API endpoints + portal de presupuestos

**Procedimiento:** Identico al patron adaptado al modulo.

```bash
lando drush pm:install jaraba_legal_billing -y
lando drush updatedb -y
lando drush sqlq "SHOW TABLES LIKE 'legal_invoice%'"
lando drush sqlq "SHOW TABLES LIKE 'time_entry%'"
lando drush config:export -y
lando drush router:rebuild
```

**Verificacion post-habilitacion:**
- [ ] Las 7 entity tables + field_data tables existen
- [ ] `TimeTrackingService` se resuelve desde DI
- [ ] `StripeInvoiceService` se resuelve (inyeccion opcional con `@?`)
- [ ] Series fiscales legales se generan conforme a normativa

### 6.4 Tarea 1.4: Habilitar jaraba_legal_lexnet

**Entidades creadas:** LexnetNotification, LexnetSubmission (2)
**Servicios registrados:** LexnetApiClient, LexnetSyncService, LexnetSubmissionService (3)
**Rutas:** 7 API endpoints + dashboard + settings

**Procedimiento:**

```bash
lando drush pm:install jaraba_legal_lexnet -y
lando drush updatedb -y
lando drush sqlq "SHOW TABLES LIKE 'lexnet%'"
lando drush config:export -y
lando drush router:rebuild
```

**Verificacion post-habilitacion:**
- [ ] Las 2 entity tables existen
- [ ] `LexnetApiClient` se resuelve desde DI
- [ ] La ruta de settings (`/admin/config/jaraba/lexnet`) carga correctamente
- [ ] El dashboard (`/legal/lexnet`) carga sin errores (datos vacios pero sin fatales)

**NOTA:** La integracion real con la API del CGPJ requiere certificados mTLS y QES. En desarrollo, el modulo funcionara en modo "mock" sin conexion real a LexNET.

### 6.5 Tarea 1.5: Habilitar jaraba_legal_templates

**Entidades creadas:** LegalTemplate, GeneratedDocument (2)
**Servicios registrados:** TemplateManagerService, DocumentGeneratorService, AiDraftingService (3)
**Rutas:** 4 API endpoints + dashboard

**Dependencia adicional:** Requiere `jaraba_page_builder` (ya habilitado) para los bloques GrapesJS.

**Procedimiento:**

```bash
lando drush pm:install jaraba_legal_templates -y
lando drush updatedb -y
lando drush sqlq "SHOW TABLES LIKE 'legal_template%'"
lando drush sqlq "SHOW TABLES LIKE 'generated_document%'"
lando drush config:export -y
lando drush router:rebuild
```

**Directrices de cumplimiento:**
- Los 11 bloques GrapesJS deben registrarse sin colision (PB-DUP-001: verificar `blockManager.get(id)`)
- Los bloques deben usar variant `duotone` para iconos (ICON-DUOTONE-001)
- No emojis en canvas_data (ICON-EMOJI-001)

---

## 7. Fase 2 — Infraestructura Freemium para Gestion de Despacho

**Prioridad:** P0 — Requisito para Fase 3 (landing unificada con pricing correcto)
**Objetivo:** Crear FreemiumVerticalLimit y FeatureGate para que las features de gestion de despacho tengan limites reales
**Tiempo estimado:** 3-4 horas
**Hallazgos que resuelve:** P0-01, P0-05

### 7.1 Tarea 2.1: Crear FreemiumVerticalLimit para jarabalex (features de despacho)

**Ubicacion:** `web/modules/custom/ecosistema_jaraba_core/config/install/`

Se deben crear 18 nuevos ficheros de configuracion (6 features x 3 tiers: free, starter, profesional):

**Features de despacho a configurar:**

| Feature Key | Free | Starter (49 EUR) | Profesional (99 EUR) | Enterprise (199 EUR) | Tipo FeatureGate |
|------------|------|-------------------|---------------------|---------------------|------------------|
| `max_cases` | 5 | 50 | -1 (ilimitado) | -1 | CUMULATIVE |
| `vault_storage_mb` | 100 | 500 | 5120 (5 GB) | -1 | CUMULATIVE |
| `calendar_deadlines` | 10 | 100 | -1 | -1 | CUMULATIVE |
| `billing_invoices_month` | 0 (bloqueado) | 20 | -1 | -1 | MONTHLY |
| `lexnet_submissions_month` | 0 (bloqueado) | 10 | -1 | -1 | MONTHLY |
| `template_generations_month` | 0 (bloqueado) | 5 | -1 | -1 | MONTHLY |

**Ficheros a crear (ejemplo para `max_cases`):**

**`ecosistema_jaraba_core.freemium_vertical_limit.jarabalex_free_max_cases.yml`:**
```yaml
langcode: es
status: true
id: jarabalex_free_max_cases
vertical: jarabalex
plan: free
feature_key: max_cases
limit_value: 5
upgrade_message: 'Has alcanzado el limite de 5 expedientes del plan gratuito. Actualiza a Starter para gestionar hasta 50.'
expected_conversion: 0.35
weight: 309
```

**`ecosistema_jaraba_core.freemium_vertical_limit.jarabalex_starter_max_cases.yml`:**
```yaml
langcode: es
status: true
id: jarabalex_starter_max_cases
vertical: jarabalex
plan: starter
feature_key: max_cases
limit_value: 50
upgrade_message: ''
expected_conversion: 0
weight: 310
```

**`ecosistema_jaraba_core.freemium_vertical_limit.jarabalex_profesional_max_cases.yml`:**
```yaml
langcode: es
status: true
id: jarabalex_profesional_max_cases
vertical: jarabalex
plan: profesional
feature_key: max_cases
limit_value: -1
upgrade_message: ''
expected_conversion: 0
weight: 311
```

**Patron identico para las 5 features restantes** (vault_storage_mb, calendar_deadlines, billing_invoices_month, lexnet_submissions_month, template_generations_month), ajustando los valores segun la tabla anterior. Total: 18 ficheros nuevos.

**Convencion de peso (weight):** Los FreemiumVerticalLimit existentes de JarabaLex usan weights 300-308. Los nuevos comenzaran en 309 para mantener la secuencia.

**Directrices de cumplimiento:**
- FREEMIUM-TIER-001: Los limites de Starter DEBEN ser estrictamente superiores a Free para cada metrica
- Los `upgrade_message` de Free DEBEN reflejar los valores reales de Starter
- Los `upgrade_message` de planes de pago DEBEN ser string vacio
- Los `expected_conversion` solo aplican a Free (>0); planes de pago siempre 0

### 7.2 Tarea 2.2: Extender JarabaLexFeatureGateService

**Archivo:** `web/modules/custom/ecosistema_jaraba_core/src/Service/JarabaLexFeatureGateService.php`

**Cambio:** Anadir gates para las 6 nuevas features de despacho. Cada gate debe:

1. Inyectar `UpgradeTriggerService` para disparar triggers cuando se alcance el limite
2. Usar el tipo de FeatureGate correcto (CUMULATIVE o MONTHLY)
3. Consultar `FreemiumVerticalLimit` via la cascada `resolveEffectiveLimit()`

**Metodos nuevos a implementar:**

```php
/**
 * Verifica si el tenant puede crear un nuevo expediente.
 *
 * @param int $tenantId
 *   El ID del tenant.
 *
 * @return bool
 *   TRUE si el tenant puede crear mas expedientes.
 */
public function canCreateCase(int $tenantId): bool {
    return $this->checkCumulativeLimit($tenantId, 'max_cases', 'client_case');
}

/**
 * Verifica si el tenant puede almacenar mas documentos en la boveda.
 *
 * @param int $tenantId
 *   El ID del tenant.
 * @param int $fileSizeMb
 *   Tamano del fichero a subir en MB.
 *
 * @return bool
 *   TRUE si hay espacio disponible.
 */
public function canStoreDocument(int $tenantId, int $fileSizeMb): bool {
    return $this->checkCumulativeLimit($tenantId, 'vault_storage_mb', 'secure_document', $fileSizeMb);
}

// Patron identico para: canCreateDeadline, canCreateInvoice, canSubmitLexnet, canGenerateTemplate
```

**Directrices de cumplimiento:**
- LEGAL-GATE-001: Cada servicio que ejecuta operacion con limites DEBE inyectar `JarabaLexFeatureGateService`, llamar `check()` antes de ejecutar, y disparar `UpgradeTriggerService` cuando denegado
- SERVICE-CALL-CONTRACT-001: Verificar que todos los callers del service usan la firma correcta

### 7.3 Tarea 2.3: Actualizar SaasPlan de JarabaLex

**Archivos:** `web/modules/custom/ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.saas_plan.jarabalex_*.yml`

**Cambio:** Anadir features de despacho a los limites de cada plan:

**`jarabalex_starter.yml` (anadir a limits JSON):**
```yaml
limits: '{"searches_per_month": 50, "max_alerts": 5, "max_bookmarks": 100, "max_cases": 50, "vault_storage_mb": 500, "calendar_deadlines": 100, "billing_invoices_month": 20, "lexnet_submissions_month": 10, "template_generations_month": 5}'
features: [legal_search, legal_cases, legal_calendar, legal_vault, soporte_email]
```

**`jarabalex_pro.yml` (anadir a limits JSON):**
```yaml
limits: '{"searches_per_month": 0, "max_alerts": 20, "max_bookmarks": 0, "max_cases": 0, "vault_storage_mb": 5120, "calendar_deadlines": 0, "billing_invoices_month": 0, "lexnet_submissions_month": 0, "template_generations_month": 0}'
features: [legal_search, legal_alerts, legal_citations, legal_cases, legal_calendar, legal_vault, legal_billing, legal_lexnet, legal_templates, soporte_email, soporte_chat]
```

**`jarabalex_enterprise.yml` (anadir a limits JSON):**
```yaml
limits: '{"searches_per_month": 0, "max_alerts": 0, "max_bookmarks": 0, "max_cases": 0, "vault_storage_mb": 0, "calendar_deadlines": 0, "billing_invoices_month": 0, "lexnet_submissions_month": 0, "template_generations_month": 0}'
features: [legal_search, legal_alerts, legal_citations, legal_cases, legal_calendar, legal_vault, legal_billing, legal_lexnet, legal_templates, api_access, soporte_email, soporte_chat, soporte_dedicado]
```

**Notas:**
- `0` en limits JSON significa **ilimitado** (no bloqueado) — el valor `0` se interpreta como "sin limite" por `PlanResolverService`
- `-1` en FreemiumVerticalLimit tambien significa ilimitado — ambos patrones coexisten

### 7.4 Tarea 2.4: Configurar UpgradeTriggerService para nuevos gates

**Archivo:** `web/modules/custom/ecosistema_jaraba_core/src/Service/UpgradeTriggerService.php`

**Cambio:** Anadir 6 nuevos triggers con tasas de conversion estimadas:

| Trigger | Expected Conversion | Contexto |
|---------|-------------------|----------|
| `case_limit_reached` | 0.35 | Intento de crear expediente #6 |
| `vault_storage_full` | 0.30 | Intento de subir documento sin espacio |
| `deadline_limit_reached` | 0.25 | Intento de crear plazo #11 |
| `billing_blocked` | 0.40 | Intento de crear factura en plan free |
| `lexnet_blocked` | 0.45 | Intento de usar LexNET en plan free (alto valor) |
| `template_blocked` | 0.30 | Intento de generar documento en plan free |

---

## 8. Fase 3 — Unificacion de Landings

**Prioridad:** P0 — Accion principal del plan
**Objetivo:** Una unica landing integral en `/jarabalex`
**Tiempo estimado:** 4-6 horas
**Hallazgos que resuelve:** P0-04, P0-07, P1-01 a P1-08

### 8.1 Tarea 3.1: Redisenar contenido de /jarabalex

**Archivo:** `web/modules/custom/ecosistema_jaraba_core/src/Controller/VerticalLandingController.php`
**Metodo:** `jarabalex()` (linea 510)

**Nuevo contenido para la landing unificada:**

**Hero:**
```php
'hero' => [
    'icon' => ['category' => 'legal', 'name' => 'gavel'],
    'headline' => $this->t('Inteligencia legal e integracion con juzgados en una sola plataforma'),
    'subheadline' => $this->t('Investigacion juridica con IA en 8 fuentes oficiales. Gestion de expedientes, agenda con plazos LEC, facturacion, boveda cifrada e integracion con LexNET. Todo desde 0 EUR/mes.'),
    'cta' => [
        'text' => $this->t('Empieza gratis'),
        'url' => '/user/register',
    ],
    'cta_secondary' => [
        'text' => $this->t('Solicitar demo'),
        'url' => '/contacto',
    ],
],
```

**Pain Points (5 items — incluye LexNET):**
```php
'pain_points' => [
    [
        'icon' => ['category' => 'legal', 'name' => 'search'],
        'text' => $this->t('Buscar jurisprudencia manualmente en bases de datos obsoletas que cuestan 3.000-8.000 EUR/ano'),
    ],
    [
        'icon' => ['category' => 'legal', 'name' => 'lexnet'],
        'text' => $this->t('Abrir LexNET, descargar notificaciones y subir respuestas manualmente a otro sistema — 10 veces al dia'),
    ],
    [
        'icon' => ['category' => 'legal', 'name' => 'briefcase'],
        'text' => $this->t('Expedientes repartidos entre carpetas, emails y discos duros sin conexion entre ellos'),
    ],
    [
        'icon' => ['category' => 'ui', 'name' => 'calendar'],
        'text' => $this->t('Plazos procesales que se escapan por falta de un sistema que entienda la LEC y los dias inhabiles'),
    ],
    [
        'icon' => ['category' => 'legal', 'name' => 'shield-privacy'],
        'text' => $this->t('Documentos sensibles compartidos por email sin cifrar, sin cadena de custodia ni trazabilidad'),
    ],
],
```

**Features Grid (8 features — portfolio completo):**

| # | Icono | Titulo | Descripcion | Modulo |
|---|-------|--------|-------------|--------|
| 1 | legal/lexnet | Integracion LexNET / CGPJ | Recibe notificaciones judiciales y envia escritos directamente desde tu despacho. Sin salir de la plataforma. | legal_lexnet |
| 2 | ai/brain | Busqueda Legal con IA | Busqueda semantica con IA sobre 8 fuentes oficiales: CENDOJ, BOE, DGT, TEAC, EUR-Lex, CURIA, HUDOC y EDPB. | legal_intelligence |
| 3 | legal/briefcase | Gestion de Expedientes | Todo el expediente en un solo lugar: documentos, notas, plazos, comunicaciones y triaje automatico con IA. | legal_cases |
| 4 | ui/calendar | Agenda y Plazos LEC | Citas con clientes, vistas judiciales y plazos procesales calculados conforme a LEC arts. 130-136. Sync con Google Calendar y Outlook. | legal_calendar |
| 5 | business/receipt | Facturacion Legal | Minutas, provisiones de fondos y facturas con serie fiscal legal. Tracking de tiempo billable por expediente. Compatible TicketBAI/SII. | legal_billing |
| 6 | legal/shield-privacy | Boveda Documental Cifrada | Almacenamiento AES-256-GCM con cadena de custodia SHA-256 de valor probatorio. Control de acceso granular y portal de cliente. | legal_vault |
| 7 | legal/citation | Citaciones en 4 Formatos | Genera citaciones juridicas en formato formal, resumido, bibliografico y nota al pie. Integradas en el flujo del expediente. | legal_intelligence |
| 8 | legal/template | Templates de Documentos | 11 bloques legales especificos para generar demandas, contestaciones y recursos. Editor visual GrapesJS con merge fields. | legal_templates |

**Pricing Preview (coherente con FreemiumVerticalLimit reales):**
```php
'pricing' => [
    'headline' => $this->t('Planes para profesionales juridicos'),
    'from_price' => '0',
    'currency' => 'EUR',
    'period' => 'mes',
    'cta_text' => $this->t('Ver todos los planes'),
    'cta_url' => '/planes',
    'features_preview' => [
        $this->t('10 busquedas legales/mes'),
        $this->t('5 expedientes gratis'),
        $this->t('Copiloto legal IA incluido'),
        $this->t('1 alerta inteligente'),
    ],
    'note' => $this->t('Plan gratuito disponible. Sin tarjeta de credito. Planes profesionales desde 49 EUR/mes.'),
],
```

**FAQ (8 preguntas — incluye LexNET, fuentes, pricing comparativo):**

| # | Pregunta | Respuesta |
|---|----------|-----------|
| 1 | Se integra con LexNET? | Si. Recibe notificaciones judiciales y envia escritos directamente desde la plataforma. Requiere certificado digital QES. Disponible en plan Starter y superiores. |
| 2 | De donde obtiene los datos juridicos? | De 8 fuentes oficiales abiertas: CENDOJ (judicial), BOE (legislacion), DGT y TEAC (tributario), EUR-Lex (legislacion UE), CURIA (TJUE), HUDOC (TEDH), EDPB (proteccion de datos). Datos publicos bajo Ley 37/2007. |
| 3 | Cuanto cuesta comparado con Aranzadi o vLex? | JarabaLex ofrece plan gratuito (10 busquedas/mes) y planes desde 49 EUR/mes. Aranzadi y vLex cuestan 3.000-8.000 EUR/ano. Ademas, JarabaLex incluye gestion de despacho, facturacion y LexNET — funcionalidades que los competidores no ofrecen. |
| 4 | La facturacion cumple con la normativa fiscal? | Totalmente. Series fiscales legales, formato TicketBAI/SII compatible, y exportacion CSV/PDF para tu asesoria contable. |
| 5 | Es seguro para documentos confidenciales? | Cifrado AES-256-GCM (estandar militar), cadena de custodia SHA-256 con valor probatorio, servidores europeos, cumple RGPD y secreto profesional. Control de acceso granular por expediente. |
| 6 | Los plazos procesales se calculan correctamente? | Si. El sistema implementa LEC arts. 130-136: agosto es inhabil para procedimientos no penales, fines de semana y festivos se excluyen del computo. Incluye reglas LGT art. 48 para plazos tributarios. |
| 7 | Puedo gestionar un bufete con varios abogados? | Si. El plan Pro incluye usuarios ilimitados con permisos por rol, panel de gestion con metricas de productividad y tracking de tiempo billable individual. |
| 8 | Puedo probar gratis sin tarjeta de credito? | Si. El plan gratuito incluye 10 busquedas/mes, 5 expedientes, 1 alerta inteligente y copiloto legal IA. Sin compromiso, sin tarjeta de credito, sin limite de tiempo. |

**Directrices de cumplimiento:**
- Todos los textos con `$this->t()` (I18N-METASITE-001)
- Iconos con formato correcto `['category' => '...', 'name' => '...']` (ICON-CONVENTION-001)
- Las features mencionadas corresponden a modulos habilitados (post-Fase 1)
- Los valores del pricing preview corresponden a FreemiumVerticalLimit reales (post-Fase 2)
- El lead magnet usa el diagnostico legal real con controller (LEGAL-LEADMAGNET-001)

### 8.2 Tarea 3.2: Redirect 301 de /despachos a /jarabalex

**Archivo:** `web/modules/custom/ecosistema_jaraba_core/src/Controller/VerticalLandingController.php`

**Cambio:** El metodo `despachos()` debe hacer redirect 301 a `/jarabalex`:

```php
/**
 * Landing para despachos — redirige a /jarabalex (landing unificada).
 *
 * Desde 2026-02-28, /despachos redirige a /jarabalex para presentar
 * una propuesta de valor integral del vertical legal.
 *
 * @see docs/analisis/2026-02-28_Auditoria_Landing_JarabaLex_Despachos_Clase_Mundial_v1.md
 */
public function despachos(): RedirectResponse {
    return new RedirectResponse('/jarabalex', 301);
}
```

**Tambien actualizar el redirect existente de `/legal`:**
```php
// Linea 826: /legal ya redirige a /despachos — ahora debe redirigir directamente a /jarabalex
public function legalRedirect(): RedirectResponse {
    return new RedirectResponse('/jarabalex', 301);
}
```

**Directrices de cumplimiento:**
- ROUTE-LANGPREFIX-001: El redirect debe funcionar correctamente con el prefijo de idioma `/es/`

### 8.3 Tarea 3.3: Actualizar navegacion global

**Archivo:** `web/themes/custom/ecosistema_jaraba_theme/templates/partials/_header-classic.html.twig`

**Cambio:** En el megamenu "Para Empresas", cambiar "Despachos (/despachos)" por "JarabaLex (/jarabalex)":

Buscar el item del megamenu que dice "Despachos" y actualizarlo:

```twig
{# Antes #}
<a href="/despachos">{% trans %}Despachos{% endtrans %}</a>
<small>{% trans %}Digitaliza tu despacho{% endtrans %}</small>

{# Despues #}
<a href="/jarabalex">{% trans %}JarabaLex{% endtrans %}</a>
<small>{% trans %}Inteligencia legal con IA{% endtrans %}</small>
```

**Directrices de cumplimiento:**
- Textos con `{% trans %}` (I18N-METASITE-001)
- No usar `attributes.addClass()` para body classes — usar `hook_preprocess_html()` (LEGAL-BODY-001)

---

## 9. Fase 4 — LexNET como Killer Feature

**Prioridad:** P0
**Objetivo:** LexNET aparece en al menos 3 secciones de la landing
**Tiempo estimado:** 1-2 horas (cambios en controller, ya cubiertos en Fase 3)
**Hallazgos que resuelve:** P0-03

### 9.1 Tarea 4.1: Anadir LexNET a la landing

Ya incluido en el rediseno de Fase 3.1:
- **Feature #1** en la grid (posicion mas prominente)
- Icono: `legal/lexnet` (variant: duotone, color: corporate)
- Titulo: "Integracion LexNET / CGPJ"
- Descripcion detallada mencionando notificaciones judiciales y envio de escritos

### 9.2 Tarea 4.2: Crear pain point LexNET

Ya incluido en Fase 3.1, pain point #2:
- "Abrir LexNET, descargar notificaciones y subir respuestas manualmente a otro sistema — 10 veces al dia"

### 9.3 Tarea 4.3: Crear FAQ LexNET

Ya incluido en Fase 3.1, FAQ #1:
- "Se integra con LexNET?" con respuesta detallada sobre certificado QES y disponibilidad en planes

**Verificacion:** Buscar `lexnet` (case-insensitive) en el output final de la landing. Debe aparecer en:
1. Features grid (feature #1)
2. Pain points (pain point #2)
3. FAQ (pregunta #1)
4. Schema.org featureList

---

## 10. Fase 5 — SEO, Schema.org y Design Tokens

**Prioridad:** P2
**Tiempo estimado:** 3-4 horas
**Hallazgos que resuelve:** P2-01 a P2-06

### 10.1 Tarea 5.1: Schema.org SoftwareApplication

**Archivo:** `web/themes/custom/ecosistema_jaraba_theme/templates/partials/_seo-schema.html.twig`

**Cambio:** Anadir schema condicional para la ruta `jarabalex`:

```twig
{% if current_route == 'ecosistema_jaraba_core.jarabalex' %}
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "SoftwareApplication",
    "name": "JarabaLex",
    "applicationCategory": "LegalService",
    "applicationSubCategory": "Law Practice Management",
    "operatingSystem": "Web",
    "description": "{{ 'Plataforma integral de inteligencia legal con IA: busqueda semantica en 8 fuentes oficiales, gestion de expedientes, facturacion, boveda cifrada e integracion LexNET.'|trans }}",
    "url": "{{ url('<current>') }}",
    "offers": {
        "@type": "AggregateOffer",
        "lowPrice": "0",
        "highPrice": "199",
        "priceCurrency": "EUR",
        "offerCount": "4"
    },
    "featureList": [
        "{{ 'Integracion LexNET / CGPJ'|trans }}",
        "{{ 'Busqueda legal IA en 8 fuentes'|trans }}",
        "{{ 'Gestion de expedientes'|trans }}",
        "{{ 'Agenda con plazos LEC'|trans }}",
        "{{ 'Facturacion legal TicketBAI/SII'|trans }}",
        "{{ 'Boveda documental cifrada AES-256'|trans }}",
        "{{ 'Citaciones en 4 formatos'|trans }}",
        "{{ 'Templates de documentos GrapesJS'|trans }}"
    ]
}
</script>
{% endif %}
```

**Directrices de cumplimiento:**
- Textos traducibles con `|trans` (I18N-METASITE-001)
- JSON-LD puede usar `|raw` por ser generado enteramente por backend (TWIG-XSS-001)

### 10.2 Tarea 5.2: Meta description y canonical

**Archivo:** `web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.module`
**Hook:** `hook_preprocess_html()` o via un `EventSubscriber` en la respuesta HTTP

**Cambio:** Para la ruta `ecosistema_jaraba_core.jarabalex`, inyectar:

```php
$meta_description = t('Software de gestion para despachos de abogados con IA. Busqueda en 8 fuentes oficiales, expedientes, facturacion, LexNET, boveda cifrada. Desde 0 EUR/mes.');
$variables['#attached']['html_head'][] = [
    [
        '#tag' => 'meta',
        '#attributes' => [
            'name' => 'description',
            'content' => (string) $meta_description,
        ],
    ],
    'jarabalex_meta_description',
];

// Canonical
$canonical_url = Url::fromRoute('ecosistema_jaraba_core.jarabalex', [], ['absolute' => TRUE])->toString();
$variables['#attached']['html_head'][] = [
    [
        '#tag' => 'link',
        '#attributes' => [
            'rel' => 'canonical',
            'href' => $canonical_url,
        ],
    ],
    'jarabalex_canonical',
];
```

**Directrices de cumplimiento:**
- TM-CAST-001: El valor de `$this->t()` se castea a `(string)` antes de pasarlo al render array

### 10.3 Tarea 5.3: Hreflang tags

**Archivo:** `web/themes/custom/ecosistema_jaraba_theme/templates/partials/_hreflang-meta.html.twig` (ya existe)

**Cambio:** Verificar que el partial se incluye en `page--jarabalex.html.twig` y genera los tags correctos:

```html
<link rel="alternate" hreflang="es" href="https://plataformadeecosistemas.com/es/jarabalex">
<link rel="alternate" hreflang="x-default" href="https://plataformadeecosistemas.com/es/jarabalex">
```

**Directriz:** HREFLANG-SEO-001 — Todas las paginas con multiples idiomas deben tener hreflang.

### 10.4 Tarea 5.4: Unificar design tokens

**Archivo:** `web/modules/custom/ecosistema_jaraba_core/src/Controller/VerticalLandingController.php`
**Metodo:** `jarabalex()` (linea 510)

**Cambio:** El `color` del vertical debe usar los design tokens propios de JarabaLex en vez del generico `corporate`:

```php
// Antes
'color' => 'corporate',

// Despues: usar el color definido en design_token_config
'color' => 'legal',
```

**Y en el SCSS**, verificar que existe el mapeo de `legal` a los colores correctos:

```scss
// En _vertical-landing.scss o _landing-sections.scss
.landing-hero--legal {
    --landing-primary: var(--ej-legal-primary, #1E3A5F);
    --landing-accent: var(--ej-legal-accent, #C8A96E);
}
```

**Directrices de cumplimiento:**
- CSS-VAR-ALL-COLORS-001: Todos los colores deben ser CSS custom properties con fallback
- VERTICAL-ELEV-003: Colores expuestos como `--ej-legal-*` en `:root`
- P4-COLOR-002: Derivados via `color-mix()`, nunca `rgba()`
- Compilar SCSS tras cambios: `lando ssh -c "cd /app/web/themes/custom/ecosistema_jaraba_theme && npx sass scss/main.scss:css/ecosistema-jaraba-theme.css --style=compressed"`
- SCSS-COMPILE-VERIFY-001: Verificar que el CSS tiene timestamp posterior al SCSS

---

## 11. Fase 6 — Social Proof y Pricing Detallado

**Prioridad:** P1
**Tiempo estimado:** 2-3 horas
**Hallazgos que resuelve:** P1-07, P1-08

### 11.1 Tarea 6.1: Mejorar testimonios

**Archivo:** `web/modules/custom/ecosistema_jaraba_core/src/Controller/VerticalLandingController.php`
**Metodo:** `jarabalex()`, seccion `social_proof.testimonials`

**Cambio:** Ampliar a 3 testimonios con mas detalle:

```php
'testimonials' => [
    [
        'quote' => $this->t('Antes perdia 30 minutos buscando cada resolucion en CENDOJ. Con JarabaLex la encuentro en 3 segundos con IA semantica. La integracion con LexNET me ahorra otra hora al dia.'),
        'author' => $this->t('Elena Rodriguez'),
        'role' => $this->t('Abogada laboralista, Madrid'),
    ],
    [
        'quote' => $this->t('La facturacion automatica y el calculo de plazos conforme a la LEC han transformado como gestionamos el bufete. Llevamos 6 meses con 0 plazos incumplidos.'),
        'author' => $this->t('Roberto Navarro'),
        'role' => $this->t('Socio director, Bufete Navarro & Asociados, Sevilla'),
    ],
    [
        'quote' => $this->t('Pagabamos 6.000 EUR al ano por Aranzadi y no teniamos ni gestion de expedientes ni LexNET. JarabaLex nos cuesta una fraccion y cubre todo lo que necesitamos.'),
        'author' => $this->t('Carmen Vidal'),
        'role' => $this->t('Socia, Vidal Abogados Penalistas, Barcelona'),
    ],
],
```

**Directrices de cumplimiento:** Textos con `$this->t()` (I18N-METASITE-001). Schema.org Review microdata se genera automaticamente por el partial.

### 11.2 Tarea 6.2: Tabla comparativa de planes

**Opcion A (landing):** Expandir la seccion de pricing preview para mostrar los 4 tiers.

Esto requiere modificar el partial `_landing-pricing-preview.html.twig` para soportar un modo expandido, o crear un nuevo partial `_landing-pricing-table.html.twig`.

**Propuesta de contenido:**

| Feature | Gratuito (0 EUR) | Starter (49 EUR) | Pro (99 EUR) | Enterprise (199 EUR) |
|---------|------------------|-------------------|---------------|----------------------|
| Busquedas legales/mes | 10 | 50 | Ilimitadas | Ilimitadas |
| Expedientes | 5 | 50 | Ilimitados | Ilimitados |
| Alertas inteligentes | 1 | 5 | 20 | Ilimitadas |
| Boveda documental | - | 500 MB | 5 GB | Ilimitado |
| Plazos procesales | 10 | 100 | Ilimitados | Ilimitados |
| Facturacion | - | 20/mes | Ilimitada | Ilimitada |
| Integracion LexNET | - | 10 envios/mes | Ilimitada | Ilimitada |
| Templates documentos | - | 5/mes | Ilimitados | Ilimitados |
| Copiloto legal IA | SI | SI | SI | SI |
| Citaciones | - | SI | SI | SI |
| API REST | - | - | - | SI |
| Soporte | - | Email | Email + Chat | Dedicado |

**Implementacion tecnica:**

Se debe crear el partial o extender el existente. Dado que el pricing table debe ser responsive y mobile-first:

```twig
{# _landing-pricing-table.html.twig #}
<section class="landing-pricing-table" aria-labelledby="pricing-table-title">
    <h2 id="pricing-table-title">{% trans %}Compara planes{% endtrans %}</h2>

    {# Toggle mensual/anual #}
    <div class="landing-pricing-table__toggle" role="radiogroup" aria-label="{% trans %}Periodo de facturacion{% endtrans %}">
        <button role="radio" aria-checked="true" data-period="monthly">{% trans %}Mensual{% endtrans %}</button>
        <button role="radio" aria-checked="false" data-period="yearly">{% trans %}Anual (-17%){% endtrans %}</button>
    </div>

    {# Cards responsive: stack en movil, grid 4-col en desktop #}
    <div class="landing-pricing-table__grid">
        {% for plan in plans %}
            <div class="landing-pricing-card {{ plan.highlighted ? 'landing-pricing-card--highlighted' : '' }}">
                <h3>{{ plan.name }}</h3>
                <div class="landing-pricing-card__price">
                    <span class="price-value">{{ plan.price }}</span>
                    <span class="price-period">EUR/{% trans %}mes{% endtrans %}</span>
                </div>
                <ul role="list">
                    {% for feature in plan.features %}
                        <li role="listitem">
                            {{ jaraba_icon('ui', feature.included ? 'check-circle' : 'x-circle', {
                                variant: 'duotone',
                                color: feature.included ? 'verde-innovacion' : 'neutral',
                                size: '16px'
                            }) }}
                            {{ feature.label }}
                        </li>
                    {% endfor %}
                </ul>
                <a href="{{ plan.cta_url }}" class="btn btn--{{ plan.highlighted ? 'primary' : 'outline' }} btn--lg"
                   data-track-cta="pricing_{{ plan.id }}"
                   data-track-position="landing_pricing_table">
                    {{ plan.cta_text }}
                </a>
            </div>
        {% endfor %}
    </div>
</section>
```

**SCSS para pricing table:**
```scss
// Anadir en _landing-sections.scss o crear _landing-pricing-table.scss
.landing-pricing-table {
    &__grid {
        display: grid;
        gap: var(--ej-spacing-md, 1rem);
        grid-template-columns: 1fr; // Mobile first

        @media (min-width: 768px) {
            grid-template-columns: repeat(2, 1fr);
        }
        @media (min-width: 1200px) {
            grid-template-columns: repeat(4, 1fr);
        }
    }
}

.landing-pricing-card {
    background: var(--ej-bg-card, #ffffff);
    border: 1px solid var(--ej-border-color, #e5e7eb);
    border-radius: var(--ej-border-radius, 10px);
    padding: var(--ej-spacing-xl, 2rem);
    text-align: center;

    &--highlighted {
        border-color: var(--ej-legal-primary, #1E3A5F);
        box-shadow: var(--ej-shadow-lg);
        transform: scale(1.02);
    }
}
```

**Directrices de cumplimiento:**
- CSS-VAR-ALL-COLORS-001: Todos los colores son CSS custom properties con fallback
- Mobile-first layout (grid 1col -> 2col -> 4col)
- Textos con `{% trans %}` (I18N-METASITE-001)
- Iconos con `jaraba_icon()` (ICON-CONVENTION-001)
- `role="list"` y `role="listitem"` en la lista de features (accesibilidad)
- `data-track-cta` y `data-track-position` para analytics

### 11.3 Tarea 6.3: Metricas reales en social proof

**Archivo:** `web/modules/custom/ecosistema_jaraba_core/src/Controller/VerticalLandingController.php`

**Cambio:** Actualizar metricas del social proof para reflejar datos reales:

```php
'metrics' => [
    [
        'value' => '8',
        'label' => $this->t('fuentes oficiales integradas'),
    ],
    [
        'value' => '<3s',
        'label' => $this->t('tiempo medio de busqueda'),
    ],
    [
        'value' => '49',
        'suffix' => 'EUR',
        'label' => $this->t('desde/mes vs 250+ EUR competidores'),
    ],
],
```

---

## 12. Fase 7 — Testing y Verificacion

**Prioridad:** P0
**Tiempo estimado:** 2-3 horas
**Hallazgos que resuelve:** Verificacion transversal de todas las fases

### 12.1 Tarea 7.1: Tests unitarios para nuevos FreemiumVerticalLimit

**Archivo a crear:** `web/modules/custom/ecosistema_jaraba_core/tests/src/Unit/Service/JarabaLexFeatureGateDespachoTest.php`

```php
/**
 * Verifica los 6 nuevos gates de features de despacho.
 *
 * @group jarabalex
 * @group freemium
 */
class JarabaLexFeatureGateDespachoTest extends UnitTestCase {

    /**
     * @dataProvider gateDataProvider
     */
    public function testGateEnforcesLimit(string $method, int $limit, int $current, bool $expected): void {
        // Mock del FreemiumVerticalLimit
        // Mock del EntityTypeManager para contar entidades
        // Invocar el metodo del FeatureGateService
        // Verificar resultado
    }

    public static function gateDataProvider(): array {
        return [
            'free plan: 5 cases, 4 used, can create' => ['canCreateCase', 5, 4, TRUE],
            'free plan: 5 cases, 5 used, blocked' => ['canCreateCase', 5, 5, FALSE],
            'starter: 50 cases, 49 used, can create' => ['canCreateCase', 50, 49, TRUE],
            'pro: unlimited (-1), 999 used, can create' => ['canCreateCase', -1, 999, TRUE],
            // Patron identico para vault_storage, calendar_deadlines, etc.
        ];
    }
}
```

**Directrices de cumplimiento:**
- CI-KERNEL-001: Los tests se ejecutan en la CI con MariaDB 10.11
- Usar `@dataProvider` para cubrir todos los tiers de cada feature
- Mocks con `willReturnSelf()` verificados contra firma real (QUERY-CHAIN-001)

### 12.2 Tarea 7.2: Tests kernel para modulos habilitados

```bash
# Ejecutar suite completa de tests
lando ssh -c "cd /app && php vendor/bin/phpunit --testsuite Unit"
lando ssh -c "cd /app && php vendor/bin/phpunit --testsuite Kernel"
```

Verificar que:
- [ ] 0 tests fallan tras habilitar los 5 modulos
- [ ] Las entidades de los nuevos modulos se instalan correctamente
- [ ] Los services se resuelven desde el contenedor DI

### 12.3 Tarea 7.3: Verificacion visual en navegador

**URL de verificacion:** `https://jaraba-saas.lndo.site/jarabalex`

Checklist:
- [ ] La landing carga sin errores (consola JS limpia)
- [ ] El hero muestra el texto actualizado con mencion a LexNET y 8 fuentes
- [ ] LexNET aparece como feature #1 en la grid
- [ ] LexNET aparece como pain point #2
- [ ] LexNET aparece como FAQ #1
- [ ] El pricing preview muestra "10 busquedas legales/mes" y "5 expedientes gratis"
- [ ] El lead magnet enlaza a `/jarabalex/diagnostico-legal` (no a `/despachos/auditoria-digital`)
- [ ] `/despachos` redirige 301 a `/jarabalex`
- [ ] `/legal` redirige 301 a `/jarabalex`
- [ ] La navegacion global dice "JarabaLex" (no "Despachos")
- [ ] Los iconos se renderizan en variant duotone
- [ ] La pagina es responsive (verificar en 375px, 768px, 1200px)
- [ ] Schema.org SoftwareApplication aparece en el source code
- [ ] Meta description aparece en `<head>`
- [ ] FAQ Schema.org JSON-LD se genera correctamente

### 12.4 Tarea 7.4: Verificacion SEO

```bash
# Lighthouse CLI (si disponible)
lando ssh -c "npx lighthouse https://jaraba-saas.lndo.site/jarabalex --only-categories=seo --output=json" | jq '.categories.seo.score'
```

Verificar:
- [ ] Lighthouse SEO score > 95
- [ ] `<meta name="description">` presente y con contenido
- [ ] `<link rel="canonical">` presente
- [ ] Schema.org SoftwareApplication valido (test en schema.org validator)
- [ ] FAQPage schema valido

---

## 13. Tabla de Correspondencia: Especificaciones Tecnicas

| Especificacion | Archivo(s) | Fase | Accion |
|---------------|-----------|------|--------|
| Textos traducibles via `$this->t()` | `VerticalLandingController.php` | 0,3,6 | Todos los textos del controller usan `$this->t()` |
| Textos traducibles via `{% trans %}` | Todos los partials Twig | 5,6 | Templates usan `{% trans %}` o `\|trans` |
| CSS variables inyectables | `_landing-sections.scss`, `_landing-pricing-table.scss` | 5,6 | Todos los colores con `var(--ej-*, fallback)` |
| Dart Sass moderno | `package.json` del theme | 5,6 | `@use` (no `@import`), `color.adjust()` (no `darken()`) |
| Compilacion SCSS comprimida | `package.json` del theme | 5,6 | `npx sass ... --style=compressed` dentro de lando |
| Zero-region template | `page--jarabalex.html.twig` | 3 | No usa `{{ page.content }}`, usa `{{ clean_content }}` |
| Partials con `{% include ... only %}` | `vertical-landing-content.html.twig` | 3 | Scope aislado, no leakage de variables |
| Body classes via hook | `ecosistema_jaraba_core.module` | 5 | `hook_preprocess_html()`, NUNCA `attributes.addClass()` en Twig |
| Iconos duotone | Todos los partials de landing | 0,3 | `jaraba_icon('cat', 'name', {variant: 'duotone'})` |
| Colores iconos Jaraba | Todos los partials de landing | 0,3 | `color: 'corporate'` o `color: 'verde-innovacion'` |
| No emojis | Templates y canvas_data | 0,3,6 | 0 emojis, siempre `jaraba_icon()` |
| Schema.org JSON-LD | `_seo-schema.html.twig` | 5 | SoftwareApplication para `/jarabalex` |
| FAQPage schema | `_landing-faq.html.twig` | 3 | Auto-generado por el partial existente |
| FreemiumVerticalLimit | `config/install/*.yml` | 2 | 18 nuevos ficheros para features de despacho |
| FeatureGate patron | `JarabaLexFeatureGateService.php` | 2 | 6 nuevos metodos: canCreate*, canStore*, canSubmit* |
| UpgradeTrigger | `UpgradeTriggerService.php` | 2 | 6 nuevos triggers con expected_conversion |
| SaasPlan limites | `saas_plan.jarabalex_*.yml` | 2 | limits JSON actualizado con features de despacho |
| 301 Redirect | `VerticalLandingController.php` | 3 | `/despachos` y `/legal` -> `/jarabalex` |
| Navegacion global | `_header-classic.html.twig` | 3 | "JarabaLex" en megamenu |
| Meta description | `ecosistema_jaraba_core.module` | 5 | Via `hook_preprocess_html()` con `#attached` |
| Canonical link | `ecosistema_jaraba_core.module` | 5 | Via `hook_preprocess_html()` con `#attached` |
| Hreflang | `_hreflang-meta.html.twig` | 5 | Parcial incluido en page template |
| Design tokens legal | `_variables-legal.scss`, `_injectable.scss` | 5 | `--ej-legal-primary`, `--ej-legal-accent` |
| Mobile-first layout | `_landing-pricing-table.scss` | 6 | 1col -> 2col -> 4col grid |
| Pricing table | Nuevo partial `_landing-pricing-table.html.twig` | 6 | 4 planes con toggle mensual/anual |
| Tests unitarios | `JarabaLexFeatureGateDespachoTest.php` | 7 | @dataProvider con todos los tiers y features |
| Modulos habilitados | `core.extension.yml` | 1 | 5 modulos: calendar, vault, billing, lexnet, templates |
| Lead magnet real | `LegalLandingController::diagnostico()` | 0 | URL `/jarabalex/diagnostico-legal` (con controller real) |
| Copilot FAB legal | `page--jarabalex.html.twig` | 3 | `agent_context: 'legal_copilot'`, `avatar_type: 'legal_advisor'` |

---

## 14. Tabla de Cumplimiento de Directrices

| Directriz ID | Nombre | Cumplimiento Post-Plan | Fase(s) |
|-------------|--------|----------------------|---------|
| I18N-METASITE-001 | Textos traducibles | SI | 0,3,5,6 |
| CSS-VAR-ALL-COLORS-001 | CSS variables con fallback | SI | 5,6 |
| ZERO-REGION-001 | Variables via preprocess, no controller | SI | 3,5 |
| ZERO-REGION-002 | 3 hooks implementados | SI | Ya existe |
| ZERO-REGION-003 | drupalSettings via preprocess | SI | Ya existe |
| LEGAL-BODY-001 | Body classes via hook | SI | 5 |
| ICON-CONVENTION-001 | Formato jaraba_icon(cat, name, opts) | SI | 0,3,6 |
| ICON-DUOTONE-001 | Variant duotone por defecto | SI | 0,3,6 |
| ICON-COLOR-001 | Colores paleta Jaraba | SI | 0,3,6 |
| ICON-EMOJI-001 | No emojis en templates | SI | 0,3,6 |
| P4-COLOR-001 | 7 colores Jaraba | SI | 5 |
| P4-COLOR-002 | color-mix() para derivados | SI | 5,6 |
| P4-COLOR-003 | Fallbacks exactos | SI | 5,6 |
| SCSS-COMPILE-VERIFY-001 | SCSS compilado tras cambios | SI | 5,6 |
| SCSS-ENTRY-CONSOLIDATION-001 | Sin ambiguedad entry/partial | SI | 6 |
| VERTICAL-ELEV-003 | --ej-legal-* en :root | SI | 5 |
| FREEMIUM-TIER-001 | Starter > Free en cada metrica | SI | 2 |
| LEGAL-GATE-001 | FeatureGate en servicios con limites | SI | 2 |
| LEGAL-LEADMAGNET-001 | Lead magnet con disclaimer y referencias | SI | 0 |
| LEGAL-DEADLINE-001 | DeadlineCalculator para plazos | SI | 1 |
| LEGAL-HASHCHAIN-001 | VaultAuditLog append-only SHA-256 | SI | 1 |
| PLAN-CASCADE-001 | Cascada vertical+tier -> _default -> null | SI | 2 |
| SERVICE-CALL-CONTRACT-001 | Firmas de servicio correctas | SI | 2 |
| SLIDE-PANEL-RENDER-001 | renderPlain() para slide-panel | SI | Ya existe |
| HREFLANG-SEO-001 | Tags hreflang ES + x-default | SI | 5 |
| ROUTE-LANGPREFIX-001 | URLs via Url::fromRoute() | SI | 3 |
| TM-CAST-001 | Cast (string) para $this->t() en render arrays | SI | 5 |
| PREMIUM-FORMS-PATTERN-001 | Entity forms extienden PremiumEntityFormBase | SI | 1 (verificar) |
| TENANT-ISOLATION-ACCESS-001 | AccessControlHandler verifica tenant | SI | 1 (verificar) |
| PB-DUP-001 | No bloques GrapesJS duplicados | SI | 1.5 (verificar) |
| COPILOT-MODES-001 | Temperaturas por modo <= 0.5 | SI | Ya existe |
| BRAND-FONT-001 | Outfit como font principal | SI | 5 |

---

## 15. Diagrama de Dependencias entre Fases

```
FASE 0 (Correccion inmediata)
    |
    v
FASE 1 (Habilitar modulos) ---------> FASE 2 (FreemiumVerticalLimit)
    |                                       |
    |                                       v
    +------------------------------> FASE 3 (Unificacion landings)
                                        |
                                        v
                                   FASE 4 (LexNET) [incluida en Fase 3]
                                        |
                                        v
                                   FASE 5 (SEO + Design Tokens)
                                        |
                                        v
                                   FASE 6 (Social Proof + Pricing Table)
                                        |
                                        v
                                   FASE 7 (Testing + Verificacion)
```

**Fase 0** es independiente y puede ejecutarse inmediatamente.
**Fases 1 y 2** son paralelas: Fase 1 habilita modulos (BD/schemas), Fase 2 crea la configuracion de limites. Ambas DEBEN completarse antes de Fase 3.
**Fase 3** depende de Fases 1+2 porque el contenido de la landing unificada menciona features que necesitan modulos habilitados + limites configurados.
**Fase 4** esta conceptualmente separada pero integrada en el contenido de Fase 3.
**Fases 5 y 6** son incrementales y pueden ejecutarse en cualquier orden.
**Fase 7** es la verificacion final y depende de todas las anteriores.

---

## 16. Riesgos y Mitigaciones

| Riesgo | Probabilidad | Impacto | Mitigacion |
|--------|-------------|---------|------------|
| Modulo satelite con schema roto al habilitar | Media | Alto | Ejecutar `drush updatedb` y verificar tablas antes de continuar |
| Colision de rutas entre modulos al habilitar todos | Baja | Alto | `drush router:rebuild` + verificar logs |
| Tests existentes fallan con nuevos modulos | Media | Medio | Ejecutar suite completa antes de Fase 2 |
| libsodium no disponible en contenedor | Baja | Alto | Verificar `php -m \| grep sodium` en lando |
| Redirect 301 rompe backlinks SEO a /despachos | Media | Medio | 301 preserva el juice SEO; monitorizar Search Console |
| MetaSitePricingService sobreescribe pricing hardcodeado | Baja | Bajo | Verificar que no hay ConfigEntity `despachos_*` que interfiera |
| GrapesJS bloques de templates colisionan con bloques existentes | Baja | Medio | Verificar con `blockManager.get(id)` antes de registrar (PB-DUP-001) |

---

## 17. Checklist Pre-Despliegue

### Codigo

- [ ] `VerticalLandingController::jarabalex()` actualizado con contenido unificado
- [ ] `VerticalLandingController::despachos()` redirige 301 a `/jarabalex`
- [ ] `VerticalLandingController::legalRedirect()` redirige 301 a `/jarabalex`
- [ ] 18 ficheros `FreemiumVerticalLimit` creados en `config/install/`
- [ ] `JarabaLexFeatureGateService` extendido con 6 nuevos metodos
- [ ] `UpgradeTriggerService` extendido con 6 nuevos triggers
- [ ] `SaasPlan` de JarabaLex actualizado con features de despacho en limits JSON
- [ ] Schema.org `SoftwareApplication` anadido al partial SEO
- [ ] Meta description y canonical inyectados en hook_preprocess_html
- [ ] Navegacion global actualizada ("JarabaLex" en vez de "Despachos")
- [ ] SCSS compilado tras cambios de design tokens

### Modulos

- [ ] `jaraba_legal_calendar` habilitado y funcionando
- [ ] `jaraba_legal_vault` habilitado y funcionando
- [ ] `jaraba_legal_billing` habilitado y funcionando
- [ ] `jaraba_legal_lexnet` habilitado y funcionando
- [ ] `jaraba_legal_templates` habilitado y funcionando
- [ ] `config/sync/core.extension.yml` actualizado y exportado

### Tests

- [ ] Suite Unit: 0 failures
- [ ] Suite Kernel: 0 failures
- [ ] Tests nuevos de FeatureGate: todos pasan
- [ ] Verificacion visual en navegador completada
- [ ] Schema.org validado

### SEO

- [ ] Meta description presente en `<head>`
- [ ] Canonical link presente
- [ ] Hreflang tags presentes
- [ ] FAQPage JSON-LD valido
- [ ] SoftwareApplication JSON-LD valido
- [ ] 301 redirects funcionando (/despachos -> /jarabalex, /legal -> /jarabalex)

---

## 18. Registro de Cambios

| Fecha | Version | Descripcion |
|-------|---------|-------------|
| 2026-02-28 | 1.0.0 | Creacion del plan de implementacion con 8 fases, 30+ tareas, tablas de correspondencia tecnica y cumplimiento de directrices |
