# Plan de Implementacion: Onboarding 10/10 Clase Mundial

**Fecha:** 2026-03-16
**Version:** 1.0.0
**Cross-refs:** Directrices v133.0.0, Arquitectura v121.0.0, Flujo v86.0.0
**Prerequisitos:** Auditoria_Flujo_Completo_Registro_MetaSitio_Clase_Mundial_v1.md, Gap_Analysis_Onboarding_10_de_10_Clase_Mundial_v1.md

---

## INDICE DE NAVEGACION (TOC)

1. [Resumen ejecutivo](#1-resumen-ejecutivo)
2. [Arquitectura existente aprovechada](#2-arquitectura-existente-aprovechada)
3. [Sprint 1 — Quick Wins (7.5 -> 8.5)](#3-sprint-1--quick-wins)
   - 3.1 [GAP-WC-008: Progress bar precargado 25%](#31-gap-wc-008-progress-bar-precargado-25)
   - 3.2 [GAP-WC-005: Social proof en registro](#32-gap-wc-005-social-proof-en-registro)
   - 3.3 [GAP-WC-003: Reducir wizard a 4 steps obligatorios](#33-gap-wc-003-reducir-wizard-a-4-steps-obligatorios)
4. [Sprint 2 — Diferenciadores (8.5 -> 9.0)](#4-sprint-2--diferenciadores)
   - 4.1 [GAP-WC-006: Email drip behavior-based](#41-gap-wc-006-email-drip-behavior-based)
   - 4.2 [GAP-WC-007: Interactive walkthrough en dashboard](#42-gap-wc-007-interactive-walkthrough-en-dashboard)
   - 4.3 [GAP-WC-009: Onboarding del equipo invitado](#43-gap-wc-009-onboarding-del-equipo-invitado)
5. [Sprint 3 — Clase Mundial (9.0 -> 9.5)](#5-sprint-3--clase-mundial)
   - 5.1 [GAP-WC-001: Template-first per vertical](#51-gap-wc-001-template-first-per-vertical)
   - 5.2 [GAP-WC-004: Data seeding per vertical](#52-gap-wc-004-data-seeding-per-vertical)
   - 5.3 [GAP-WC-010: Mobile-optimized onboarding](#53-gap-wc-010-mobile-optimized-onboarding)
6. [Sprint 4 — 10/10 (9.5 -> 10)](#6-sprint-4--1010)
   - 6.1 [GAP-WC-002: AI-powered site generation](#61-gap-wc-002-ai-powered-site-generation)
7. [Tabla de correspondencia tecnica](#7-tabla-de-correspondencia-tecnica)
8. [Cumplimiento de directrices](#8-cumplimiento-de-directrices)
9. [Verificacion PIPELINE-E2E-001](#9-verificacion-pipeline-e2e-001)
10. [Metricas de exito](#10-metricas-de-exito)

---

## 1. RESUMEN EJECUTIVO

Este plan lleva el flujo de onboarding de 7.5/10 a 10/10 en 4 sprints incrementales. Cada sprint es independiente y desplegable en produccion. El plan se apoya en infraestructura existente:

- **70 PageTemplate** entities con TemplateRegistryService (SSOT)
- **132 bloques GrapesJS** (70 custom + 62 nativos)
- **52 Setup Wizard steps** + **39 Daily Actions** en 9 verticales
- **11 agentes IA Gen 2** con ModelRouterService
- **ECA module** instalado con 20+ flujos configurados
- **Reverse trial** implementado (ReverseTrialService)
- **Demo interactivo** + **Sandbox** pre-signup

---

## 2. ARQUITECTURA EXISTENTE APROVECHADA

### Entidades que YA existen (NO crear)
| Entidad | Modulo | Tipo | Campos clave |
|---------|--------|------|-------------|
| PageTemplate | jaraba_page_builder | Config | category, twig_template, fields_schema, preview_data, plans_required |
| PageContent | jaraba_page_builder | Content | canvas_data (JSON), rendered_html, template_id, path_alias |
| TenantOnboardingProgress | jaraba_onboarding | Content | current_step, completed_steps, step_data, skipped_steps |
| VerticalOnboardingConfig | ecosistema_jaraba_core | Config | headline, benefits, next_steps, connect_required, post_onboarding_route |
| Vertical | ecosistema_jaraba_core | Content | machine_name, enabled_features, theme_settings |
| SaasPlan | ecosistema_jaraba_core | Content | price_monthly, stripe_price_id, features, limits |
| Tenant | ecosistema_jaraba_core | Content | domain, subscription_status, stripe_customer_id, theme_overrides |
| SiteConfig | jaraba_site_builder | Content | site_name, logo_url, footer_config |
| SitePageTree | jaraba_site_builder | Content | show_in_navigation, show_in_footer, weight |

### Servicios que YA existen (NO duplicar)
| Servicio | Modulo | Responsabilidad |
|----------|--------|-----------------|
| TemplateRegistryService | jaraba_page_builder | SSOT de 70 templates |
| TemplatePickerController | jaraba_page_builder | Galeria, preview, iframe |
| SetupWizardRegistry | ecosistema_jaraba_core | 52 steps per-vertical |
| DailyActionsRegistry | ecosistema_jaraba_core | 39 acciones diarias |
| TenantOnboardingWizardService | jaraba_onboarding | Wizard 7 pasos |
| LogoColorExtractorService | jaraba_onboarding | Extraccion paleta del logo |
| MetaSiteEmailSequenceService | ecosistema_jaraba_core | 5 emails nurturing |
| MetaSitePricingService | ecosistema_jaraba_core | Precios dinámicos (NO-HARDCODE-PRICE-001) |
| OnboardingStateService | jaraba_page_builder | Tour state per-user |
| DemoFeatureGateService | ecosistema_jaraba_core | Feature gates demo |
| ReverseTrialService | jaraba_billing | 14d Pro -> Starter |

### Parciales Twig que YA existen (reutilizar)
| Parcial | Ubicacion | Proposito |
|---------|-----------|-----------|
| _empty-state.html.twig | partials/ | Estado vacio con icon + CTA |
| _setup-wizard.html.twig | partials/ | Wizard stepper con progress ring |
| _daily-actions.html.twig | partials/ | Grid de acciones diarias |
| _landing-social-proof.html.twig | partials/ | Social proof (testimonios) |
| _header.html.twig | partials/ | Header dispatcher (classic/minimal/transparent) |
| _footer.html.twig | partials/ | Footer dispatcher (minimal/standard/mega/split) |
| _hero.html.twig | partials/ | Hero section con animaciones |

---

## 3. SPRINT 1 — QUICK WINS (7.5 -> 8.5)

### 3.1 GAP-WC-008: Progress Bar Precargado 25%

**Objetivo:** Aplicar el efecto Zeigarnik — progress ring del Setup Wizard empieza en 25% en vez de 0%.

**Benchmark:** +12-28% completion rate (Zeigarnik effect research). Progreso rapido-al-inicio tiene 11.3% abandono vs 21.8% lento-al-inicio.

**Logica de negocio:**
Al crear un nuevo tenant, dos steps se marcan automaticamente como "pre-completados" porque ya ocurrieron durante el registro:
1. "Cuenta creada" — siempre verdadero al llegar al dashboard
2. "Vertical configurado" — siempre verdadero (se eligio durante registro)

Esto hace que el usuario vea 25-30% completado al llegar por primera vez, generando el impulso psicologico de "ya empece, quiero terminar".

**Implementacion tecnica:**

**Archivo a modificar:** `ecosistema_jaraba_core/src/SetupWizard/SetupWizardRegistry.php`

**Cambio:** En `getStepsForWizard()`, inyectar 2 "auto-complete" steps virtuales antes de los steps reales del vertical. Estos steps no requieren accion del usuario — su `isComplete()` siempre retorna `true`.

**Nuevos archivos:**
- `ecosistema_jaraba_core/src/SetupWizard/AutoCompleteAccountStep.php` — step virtual "Cuenta creada", `isComplete()` = true siempre
- `ecosistema_jaraba_core/src/SetupWizard/AutoCompleteVerticalStep.php` — step virtual "Vertical configurado", `isComplete()` = true siempre

**Registro via services.yml:**
```yaml
ecosistema_jaraba_core.setup_wizard.auto_account:
  class: Drupal\ecosistema_jaraba_core\SetupWizard\AutoCompleteAccountStep
  tags:
    - { name: ecosistema_jaraba_core.setup_wizard_step }

ecosistema_jaraba_core.setup_wizard.auto_vertical:
  class: Drupal\ecosistema_jaraba_core\SetupWizard\AutoCompleteVerticalStep
  tags:
    - { name: ecosistema_jaraba_core.setup_wizard_step }
```

**Directrices aplicables:**
- SETUP-WIZARD-DAILY-001: Patron transversal con tagged services
- PIPELINE-E2E-001: L1 (service) + L2 (controller ya pasa datos) + L3 (hook_theme ya declara) + L4 (template ya renderiza)

**Verificacion RUNTIME-VERIFY-001:**
1. Visitar dashboard de cualquier vertical como usuario nuevo
2. El progress ring debe mostrar >= 25%
3. Los 2 primeros steps deben tener checkmark verde

---

### 3.2 GAP-WC-005: Social Proof en Registro

**Objetivo:** Mostrar contadores de tenants activos y mini-testimonios en la pagina de registro `/registro/{vertical}`.

**Benchmark:** +20-30% completion rate. 4x mas probabilidad de compra con recomendacion.

**Logica de negocio:**
En la pagina de registro, debajo del formulario, mostrar:
- Contador: "X organizaciones ya usan {vertical_label}" (query real de tenants activos por vertical)
- 1-2 mini-testimonios curados por vertical (desde VerticalOnboardingConfig o success cases)

**Implementacion tecnica:**

**Archivo a modificar:** `ecosistema_jaraba_core/src/Controller/OnboardingController.php`

En `registerForm()`, anadir al render array:
- `#tenant_count`: resultado de entity query contando tenants activos del vertical
- `#social_proof`: array con 1-2 testimonios desde `SuccessCase` entity (ya existe en seeds) o desde `VerticalOnboardingConfig`

**Nuevo parcial Twig:**
`ecosistema_jaraba_theme/templates/partials/_registration-social-proof.html.twig`
- Muestra contador con icono `jaraba_icon('users', 'group', { variant: 'duotone', color: 'verde-innovacion' })`
- 1-2 mini cards de testimonios con foto, nombre, texto breve
- Textos con `{% trans %}` (nunca |t)
- Colores via `var(--ej-*)` tokens

**Archivo a modificar:** `ecosistema_jaraba_theme/templates/ecosistema-jaraba-register-form.html.twig`
- `{% include %}` del nuevo parcial con `only` keyword

**SCSS:**
- Nuevo parcial `scss/components/_registration-social-proof.scss`
- Importar en `main.scss`: `@use 'components/registration-social-proof';`
- Colores solo via `var(--ej-*)` (CSS-VAR-ALL-COLORS-001)
- Compilar: `npm run build` desde theme dir

**Directrices aplicables:**
- CSS-VAR-ALL-COLORS-001: Colores via var(--ej-*)
- ICON-CONVENTION-001 + ICON-DUOTONE-001: Iconos via jaraba_icon(), variante duotone default
- TWIG-INCLUDE-ONLY-001: {% include %} con only
- SCSS-001: @use con scope aislado
- SCSS-COMPILE-VERIFY-001: Verificar timestamp CSS > SCSS

---

### 3.3 GAP-WC-003: Reducir Wizard a 4 Steps Obligatorios

**Objetivo:** Reducir TTFV de ~15min a <5min haciendo opcionales los steps 3 (Fiscal), 4 (Payments) y 5 (Team).

**Benchmark:** TTFV < 5 min = +25% conversion. Cada 10 min de retraso cuesta -8%.

**Logica de negocio:**
El Onboarding Wizard actual tiene 7 pasos. Los steps 3-5 son valiosos pero no necesarios para que el usuario vea valor. El nuevo flujo:

| Step | Contenido | Obligatorio | Tiempo |
|------|-----------|-------------|--------|
| 1 | Welcome — confirmar vertical | Si | 30s |
| 2 | Identity — logo + colores | Si | 1-2min |
| 3 | Fiscal — NIF/CIF | Solo commerce (auto-skip resto) | 2min |
| 4 | Payments — Stripe Connect | Solo commerce (auto-skip resto) | 3min |
| 5 | Team — invitar colaboradores | SIEMPRE saltable (boton "Omitir" prominente) | 1min |
| 6 | Content — primer contenido/template | Si | 1-2min |
| 7 | Launch — celebracion + redirect | Si | 30s |

**Cambio clave:** El step 5 (Team) ya es saltable. Lo que falta es:
1. Hacer el boton "Omitir" mas visible (actualmente es link secundario)
2. Para verticales no-commerce, los steps 3-4 ya se auto-saltan (VERIFICADO en controller)
3. Anadir **auto-redirect en Step 7** al dashboard del vertical (GAP-FLOW-003 ya corregido)

**Implementacion tecnica:**

**Archivo a modificar:** `jaraba_onboarding/templates/onboarding-wizard-step-team.html.twig`
- Boton "Omitir este paso" mas prominente (boton secondary visible, no link gris)
- Texto: "Puedes invitar a tu equipo mas tarde desde tu dashboard"

**Archivo a modificar:** `jaraba_onboarding/src/Controller/TenantOnboardingWizardController.php`
- En `stepLaunch()`: generar `preview_url` real (actualmente string vacio)
- Redirect automatico al dashboard del vertical (reutilizar `resolveVerticalDashboardUrl()` de OnboardingController)

**Archivo a modificar:** `jaraba_onboarding/templates/onboarding-wizard-step-launch.html.twig`
- CTA principal: "Ir a tu dashboard" con URL real del vertical
- CTA secundario: "Ver tu sitio" con preview_url

**Directrices aplicables:**
- Textos {% trans %} (nunca |t, nunca hardcoded)
- ICON-CONVENTION-001: Iconos via jaraba_icon()
- ROUTE-LANGPREFIX-001: URLs via Url::fromRoute() (ya corregido en GAP-FLOW-003)

---

## 4. SPRINT 2 — DIFERENCIADORES (8.5 -> 9.0)

### 4.1 GAP-WC-006: Email Drip Behavior-Based

**Objetivo:** Migrar los 5 emails time-based a triggers behavior-based conectados al Setup Wizard.

**Benchmark:** Behavior-based > time-based. 60-80% open rate en welcome. +40% engagement.

**Logica de negocio:**
Los triggers se disparan por acciones del usuario, no por calendario:

| Trigger | Condicion | Email | Timing |
|---------|-----------|-------|--------|
| wizard_50_percent | Setup Wizard >= 50% | "Vas por buen camino" + siguiente paso | Inmediato |
| step_stalled_48h | Step no completado en 48h | "Te falta solo {step_label}" | 48h inactivo |
| wizard_complete | Setup Wizard = 100% | Celebracion + invite team CTA | Inmediato |
| first_content | Primer producto/servicio creado | "Tu primera venta esta cerca" | Inmediato |
| no_login_72h | Sin login en 72h | "Te echamos de menos" + CTA dashboard | 72h inactivo |
| trial_day_7 | Trial dia 7 | "Te quedan 7 dias de Pro" + upgrade CTA | Dia 7 |
| trial_day_1 | Trial dia 1 | "Ultimo dia de Pro" + urgencia | Dia 13 |

**Implementacion tecnica:**

**Nuevo servicio:** `ecosistema_jaraba_core/src/Service/BehaviorEmailTriggerService.php`
- Escucha eventos del Setup Wizard (completion percentage changes)
- Usa `\Drupal::service('plugin.manager.mail')` para enviar
- Templates email en `ecosistema_jaraba_core/templates/email/`

**ECA integration:** Configurar 7 flujos ECA en `config/install/eca.model.onboarding_behavior_*.yml`
- Trigger: entity_presave en TenantOnboardingProgress + cron para time-based

**Directrices aplicables:**
- OPTIONAL-CROSSMODULE-001: Si jaraba_onboarding no esta instalado, servicio degrada gracefully
- PRESAVE-RESILIENCE-001: try-catch en hooks de email
- SECRET-MGMT-001: SMTP credentials via settings.secrets.php

---

### 4.2 GAP-WC-007: Interactive Walkthrough en Dashboard

**Objetivo:** Guided tour de 4-5 pasos sobre el dashboard real en primera visita.

**Benchmark:** +400% conversion vs tour pasivo (Rocketbots). Reducen TTFV 40%.

**Logica de negocio:**
Al primer login post-onboarding, mostrar overlay con tooltips posicionados:
1. "Este es tu asistente de configuracion" → highlight Setup Wizard
2. "Tus acciones diarias aparecen aqui" → highlight Daily Actions
3. "Gestiona tu contenido desde la navegacion" → highlight nav principal
4. "Tu Copilot IA te ayuda en cualquier momento" → highlight FAB Copilot

Solo se muestra una vez (localStorage + `OnboardingStateService` backend).

**Implementacion tecnica:**

**Nuevo JS:** `ecosistema_jaraba_theme/js/guided-tour.js`
- IIFE con Drupal.behaviors pattern
- 4-5 tooltips posicionados con CSS absolute/fixed
- Overlay semi-transparente (color-mix con --ej-bg-dark)
- Navigation: "Siguiente" / "Omitir tour"
- State: localStorage `jaraba_guided_tour_completed` + API call a OnboardingStateService

**Nuevo SCSS:** `scss/components/_guided-tour.scss`
- Overlay: `background: color-mix(in srgb, var(--ej-bg-dark) 70%, transparent)` (SCSS-COLORMIX-001)
- Tooltips: `background: var(--ej-bg-surface)`, `border: 1px solid var(--ej-border-color)`
- Highlight: box-shadow pulsante en el elemento target
- z-index: 1100 (por encima de slide-panel 1050)

**Nueva library:** en `ecosistema_jaraba_theme.libraries.yml`
```yaml
guided-tour:
  version: 1.0.0
  js:
    js/guided-tour.js: { attributes: { defer: true } }
  css:
    theme:
      css/components/guided-tour.css: {}
  dependencies:
    - core/drupal
    - core/drupalSettings
    - ecosistema_jaraba_theme/global-styling
```

**Attachment:** En `hook_preprocess_page()` de cada dashboard controller, adjuntar library si es primera visita.

**Directrices aplicables:**
- SCSS-COLORMIX-001: color-mix() para runtime alpha
- CSS-VAR-ALL-COLORS-001: Colores solo via var(--ej-*)
- JS: Drupal.behaviors, Drupal.t() para traducciones
- ROUTE-LANGPREFIX-001: URLs via drupalSettings
- CSRF-JS-CACHE-001: Token cache para API calls

---

### 4.3 GAP-WC-009: Onboarding del Equipo Invitado

**Objetivo:** Los miembros invitados reciben su propio mini-onboarding de 3 pasos.

**Benchmark:** +40% retention 30d (Notion). Si el equipo no adopta, el admin churns.

**Logica de negocio:**
Cuando el admin invita a un miembro (Step 5 del wizard o desde settings):
1. Email personalizado: "Te han invitado a {tenant.name} en {vertical.label}"
2. El invitado acepta → crear cuenta → mini-onboarding:
   - Paso 1: Aceptar invitacion + crear cuenta
   - Paso 2: Completar perfil basico (nombre, foto)
   - Paso 3: Guided tour del dashboard (reutilizar GAP-WC-007)

**Implementacion tecnica:**

**Archivo a modificar:** Template de email de invitacion existente — anadir contexto del vertical + beneficios.

**Nueva ruta:** `/invitation/accept/{token}` en ecosistema_jaraba_core.routing.yml

**Nuevo controller:** `InvitationAcceptController` con DI de TenantBridgeService + currentUser.

**Tracking:** Campo `invited_by` en User entity o en Group membership metadata.

**Directrices aplicables:**
- TENANT-BRIDGE-001: Resolver tenant del invitado via TenantBridgeService
- CSRF-API-001: Token HMAC en URL de invitacion
- AUDIT-SEC-001: Webhooks/invitations con HMAC + hash_equals()

---

## 5. SPRINT 3 — CLASE MUNDIAL (9.0 -> 9.5)

### 5.1 GAP-WC-001: Template-First per Vertical

**Objetivo:** El usuario selecciona un template durante el onboarding y obtiene un sitio funcional al instante.

**Benchmark:** Canva muestra templates segundos despues del registro. Shopify Magic reduce tiempo de lanzamiento 73%.

**Logica de negocio:**
En el Step 6 (Content) del Onboarding Wizard, en vez de un formulario de primer producto/servicio, mostrar la galeria de templates filtrada por el vertical del tenant. El usuario:
1. Ve 3-5 templates del su vertical (ya existen 70 PageTemplate entities)
2. Click "Usar este template" → crea PageContent con canvas_data del template
3. Opcionalmente edita titulo y algunos campos del template
4. El metasitio ya tiene una homepage funcional

**Implementacion tecnica:**

**Archivo a modificar:** `jaraba_onboarding/templates/onboarding-wizard-step-content.html.twig`
- Reemplazar formulario de primer producto con galeria de templates
- Reutilizar estructura del `TemplatePickerController::listTemplatesAjax()` existente

**Archivo a modificar:** `jaraba_onboarding/src/Controller/TenantOnboardingWizardController.php`
- En `stepContent()`: cargar templates del vertical via TemplateRegistryService
- En `apiAdvanceStep()` para step 6: crear PageContent desde template seleccionado

**Nuevo servicio:** `jaraba_page_builder/src/Service/PageFromTemplateService.php`
- Metodo `createFromTemplate(string $templateId, int $tenantId, array $overrides): PageContent`
- Carga PageTemplate, extrae preview_data, crea PageContent con canvas_data pre-rellenado
- Asocia al tenant via SitePageTree

**Directrices aplicables:**
- CANVAS-ARTICLE-001: Reutilizar engine GrapesJS
- ZERO-REGION-002: NUNCA pasar entity objects como non-# keys
- ENTITY-FK-001: FKs a entidades del mismo modulo = entity_reference

---

### 5.2 GAP-WC-004: Data Seeding per Vertical

**Objetivo:** Al seleccionar template, pre-poblar datos demo del vertical para eliminar el "blank canvas".

**Benchmark:** Canva pre-popula. Notion pre-carga templates. Airtable crea app con datos ejemplo.

**Implementacion tecnica:**

**Nuevo servicio:** `ecosistema_jaraba_core/src/Service/VerticalDataSeedingService.php`
- Metodo `seedForTenant(int $tenantId, string $verticalId): void`
- Crea entities demo segun vertical (con flag `is_demo_data = TRUE`)
- Patrones: idempotente (check loadByProperties antes de crear)

**Fixtures per vertical (scripts reutilizables):**
| Vertical | Entities | Cantidad |
|----------|----------|----------|
| empleabilidad | JobOffer | 3 ofertas ejemplo |
| agroconecta | Product (agro) | 5 productos con fotos |
| comercioconecta | Product (retail) | 4 productos con variantes |
| serviciosconecta | ServiceOffering | 3 servicios con paquetes |
| jarabalex | LegalArea + LegalAlert | 3 areas + 3 alertas |
| jaraba_content_hub | Article + Category | 3 articulos + 2 categorias |
| formacion | Course + Lesson | 1 curso + 3 lecciones |
| emprendimiento | BusinessModelCanvas | 1 canvas pre-rellenado |
| andalucia_ei | TrainingPlan + Action | 1 plan + 2 acciones |

**Integracion:** Llamar desde `apiAdvanceStep()` en Step 6 (Content) del wizard, DESPUES de crear la pagina desde template.

---

### 5.3 GAP-WC-010: Mobile-Optimized Onboarding

**Objetivo:** Onboarding optimizado para pantallas moviles.

**Implementacion tecnica:**

**SCSS responsive refinements:**
- Touch targets minimo 44px (WCAG 2.1 AA)
- Un campo por pantalla en mobile (progressive disclosure)
- Botones "Siguiente" sticky en bottom (`position: sticky; bottom: 0`)
- Upload logo desde camara: `<input type="file" accept="image/*" capture="environment">`

**Archivo a modificar:** `scss/components/_onboarding-wizard.scss` (o crear nuevo)
- Media queries: `@media (max-width: 768px) { ... }`
- Layout: flex-direction column, gap reducidos
- Fonts: --ej-font-size-base aumentado a 18px en mobile

---

## 6. SPRINT 4 — 10/10 (9.5 -> 10)

### 6.1 GAP-WC-002: AI-Powered Site Generation

**Objetivo:** IA genera variantes de homepage, textos y contenido inicial basandose en el vertical y datos del tenant.

**Benchmark:** Squarespace Blueprint AI genera sitio de 1.4B combinaciones. Shopify Magic genera contenido automaticamente.

**Logica de negocio:**
Tras subir logo (Step 2) e IA extraer colores, el sistema:
1. Genera 3 variantes de homepage (layouts diferentes con mismo contenido)
2. Genera hero text + tagline basado en nombre organizacion + vertical
3. Genera descripciones de primeros productos/servicios
4. El usuario elige variante preferida y edita si quiere

**Implementacion tecnica:**

**Nuevo servicio:** `jaraba_ai_agents/src/Service/OnboardingContentGeneratorService.php`
- Usa agentes Gen 2 existentes (SmartBaseAgent pattern)
- Modelo: balanced tier (Sonnet 4.6) para velocidad
- Prompt: vertical context + tenant name + logo colors → JSON estructurado con hero_title, hero_subtitle, tagline, product_descriptions[]

**Integracion con Step 6:** Boton "Generar con IA" junto a la seleccion de template.

**Directrices aplicables:**
- AGENT-GEN2-PATTERN-001: Extender SmartBaseAgent, override doExecute()
- MODEL-ROUTING-CONFIG-001: Tier balanced para velocidad
- AI-GUARDRAILS-PII-001: Detectar PII en outputs
- TOOL-USE-LOOP-001: Max 5 iteraciones
- SERVICE-CALL-CONTRACT-001: Firmas exactas

---

## 7. TABLA DE CORRESPONDENCIA TECNICA

| Gap | Archivos a crear | Archivos a modificar | Entidades | Servicios | Tests |
|-----|-------------------|---------------------|-----------|-----------|-------|
| WC-008 | 2 Step classes + services.yml entries | SetupWizardRegistry.php | — | 2 auto-complete steps | Unit: isComplete() always true |
| WC-005 | 1 parcial Twig + 1 SCSS | OnboardingController.php, register-form template | — | Entity query (count) | Kernel: tenant count query |
| WC-003 | — | 3 Twig templates (team, launch, wizard) | — | — | Functional: wizard flow timing |
| WC-006 | 1 servicio + 7 ECA configs + email templates | — | — | BehaviorEmailTriggerService | Unit: trigger conditions |
| WC-007 | 1 JS + 1 SCSS + 1 library | hook_preprocess_page | — | OnboardingStateService (ya existe) | — |
| WC-009 | 1 controller + 1 route + email template | routing.yml, User entity | — | InvitationAcceptController | Functional: invitation flow |
| WC-001 | 1 servicio + Twig modificado | step-content template, WizardController | PageContent (crear) | PageFromTemplateService | Kernel: create from template |
| WC-004 | 1 servicio + 9 fixture files | apiAdvanceStep() | Demo entities | VerticalDataSeedingService | Kernel: seed + cleanup |
| WC-010 | — | SCSS (responsive) | — | — | Visual: mobile screenshots |
| WC-002 | 1 agente IA + UI template | Step 6 template | — | OnboardingContentGeneratorService | Unit: prompt structure |

---

## 8. CUMPLIMIENTO DE DIRECTRICES

| Directriz | Aplicacion en este plan |
|-----------|------------------------|
| CSS-VAR-ALL-COLORS-001 | Todo SCSS usa var(--ej-*). NUNCA hex hardcoded |
| ICON-CONVENTION-001 | jaraba_icon() con variante duotone default |
| ICON-DUOTONE-001 | Todos los iconos nuevos en variante duotone |
| ICON-COLOR-001 | Solo colores de paleta Jaraba |
| SCSS-001 | @use con scope aislado en cada parcial |
| SCSS-COMPILE-VERIFY-001 | npm run build + verificar timestamps |
| SCSS-COLORMIX-001 | color-mix() para alpha en runtime |
| TWIG-INCLUDE-ONLY-001 | {% include %} con only en parciales |
| ZERO-REGION-001 | Variables via hook_preprocess_page, no controller |
| ZERO-REGION-003 | #attached en preprocess, no en controller |
| ROUTE-LANGPREFIX-001 | URLs via Url::fromRoute() |
| TENANT-BRIDGE-001 | TenantBridgeService para Tenant<->Group |
| TENANT-001 | Toda query filtra por tenant |
| PREMIUM-FORMS-PATTERN-001 | Forms via PremiumEntityFormBase |
| SETUP-WIZARD-DAILY-001 | Tagged services via CompilerPass |
| PIPELINE-E2E-001 | 4 capas L1-L4 verificadas por feature |
| OPTIONAL-CROSSMODULE-001 | @? para deps cross-modulo |
| PRESAVE-RESILIENCE-001 | try-catch en hooks con servicios opcionales |
| NO-HARDCODE-PRICE-001 | Precios via MetaSitePricingService |
| SECRET-MGMT-001 | Secrets via getenv() en settings.secrets.php |
| CSRF-API-001 | _csrf_request_header_token en rutas POST |
| UPDATE-HOOK-REQUIRED-001 | hook_update_N si nueva entity o campo |
| TRANSLATABLE-FIELDDATA-001 | SQL con _field_data para translatable |

---

## 9. VERIFICACION PIPELINE-E2E-001

Para CADA feature implementada, verificar las 4 capas:

| Capa | Que verificar | Comando/Accion |
|------|---------------|----------------|
| L1 | Service inyectado en controller | Grep constructor + create() |
| L2 | Controller pasa datos al render array | Grep #variable en return array |
| L3 | hook_theme() declara las variables | Grep variables array en .module |
| L4 | Template usa las variables con textos traducidos | Grep {% trans %} en .html.twig |

**Anti-patron:** Completar L1-L2 sin verificar L3-L4. Drupal descarta variables no declaradas en hook_theme() silenciosamente.

---

## 10. METRICAS DE EXITO

| Metrica | Actual (est.) | Post-Sprint 1 | Post-Sprint 2 | Post-Sprint 3 | Post-Sprint 4 |
|---------|---------------|---------------|---------------|---------------|---------------|
| TTFV | ~15-20 min | < 5 min | < 5 min | < 3 min | < 2 min |
| Wizard completion | ~40-50% | > 60% | > 70% | > 75% | > 80% |
| Activation rate | ~30-35% | > 45% | > 55% | > 60% | > 65% |
| Trial-to-paid | ~15-20% | > 25% | > 30% | > 35% | > 40% |
| Score clase mundial | 7.5/10 | 8.5/10 | 9.0/10 | 9.5/10 | 10/10 |

---

*Plan generado tras auditoria de codigo real + investigacion de mercado con 40+ fuentes. Todos los archivos referenciados verificados contra el codebase actual.*
