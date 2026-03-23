# JARABA IMPACT PLATFORM — CLAUDE.md
# Ultima actualizacion: 2026-03-23 | Version: 1.8.0
# Ecosistema: 10 verticales, 196+ especificaciones, 80+ modulos custom, Drupal 11

## IDENTIDAD DEL PROYECTO
- Nombre: Jaraba Impact Platform (Ecosistema Jaraba)
- Filosofia: "Sin Humo" — codigo limpio, practico, sin complejidad innecesaria
- Stack: Drupal 11 + PHP 8.4 + MariaDB 10.11 + Redis 7.4 + Qdrant
- Multi-tenancy: Group Module (soft isolation) + TenantBridgeService
- Pagos: Stripe Connect (destination charges)
- IA: Claude API + Gemini API con strict grounding, 11 agentes Gen 2
- Servidor: IONOS Dedicated AE12-128 NVMe (AMD EPYC 4465P 12c/24t, 128GB DDR5, 2x1TB NVMe RAID1, Ubuntu 24.04)
- IP produccion: 82.223.204.169, SSH puerto 2222, usuario jaraba
- Stack nativo: Nginx + PHP-FPM 8.4 (24 workers, OPcache 256MB validate_timestamps=0) + MariaDB 10.11 (InnoDB 16GB) + Redis 7.4 + Supervisor (4 AI workers con sleep) + Tika (Docker)
- Backup 3 capas: local (24h) + Hetzner Object Storage S3 (30d) + NAS GoodSync SFTP (indefinido). Cron cada 6h. RPO <6h
- Email: SMTP IONOS (smtp.ionos.es:587), From: contacto@plataformadeecosistemas.com. Symfony Mailer + email-wrap.html.twig premium
- Monitoring: UptimeRobot (4 dominios + API status + SSL expiry)
- Dev local: Lando (.lando.yml), NO docker-compose.yml
- URL dev: https://jaraba-saas.lndo.site/
- Repo privado GitHub, CI: GitHub Actions (ci.yml, security-scan.yml, deploy.yml)

## 10 VERTICALES CANONICOS (VERTICAL-CANONICAL-001)
empleabilidad, emprendimiento, comercioconecta, agroconecta, jarabalex,
serviciosconecta, andalucia_ei, jaraba_content_hub, formacion, demo
Source of truth: `BaseAgent::VERTICALS` en jaraba_ai_agents

## ARQUITECTURA MULTI-TENANT

### Reglas Cardinales
- TENANT-BRIDGE-001: SIEMPRE usar `TenantBridgeService` para resolver Tenant<->Group. NUNCA getStorage('group') con Tenant IDs
- TENANT-ISOLATION-ACCESS-001: Todo AccessControlHandler con tenant_id DEBE verificar tenant match para update/delete
- TENANT-001: TODA query DEBE filtrar por tenant. Sin excepciones
- TENANT-002: Usar `ecosistema_jaraba_core.tenant_context` para obtener tenant. NUNCA resolver via queries ad-hoc
- DOMAIN-ROUTE-CACHE-001: Cada hostname multi-tenant DEBE tener su propia Domain entity. Sin ella, RouteProvider cachea por [domain] key en Redis y path processors NO se ejecutan en cache HIT
- VARY-HOST-001: SecurityHeadersSubscriber appends `Vary: Host` en prioridad -10 para CDN/reverse proxy multi-tenant
- JS-AGGREGATION-LAZY-001: Nginx location para `/sites/.*/files/(css|js)/` DEBE usar `try_files $uri /index.php?$query_string` (NO `=404`). Drupal 11 lazy-genera agregados CSS/JS en la primera peticion. Sin PHP fallback, Nginx devuelve 404

### Servicios Clave
- TenantContextService: resuelve via admin_user + group membership
- TenantBridgeService: Tenant entities = billing, Group entities = content isolation
- TenantResolverService::getCurrentTenant() devuelve GroupInterface (NO TenantInterface)
- Para TenantInterface desde Group: TenantBridgeService::getTenantForGroup()

## CONVENCIONES DE CODIGO

### Modulos Personalizados
- Prefijo: jaraba_* (NUNCA otro prefijo)
- Estructura: jaraba_{vertical}_{feature} (ej: jaraba_agro_catalog)
- Core transversal: ecosistema_jaraba_core
- Services: inyeccion de dependencias SIEMPRE. \Drupal::service() solo en .module y hooks procedurales
- Optional services: `@?service_name` en services.yml, constructor acepta `?ServiceInterface $service = NULL`
- OPTIONAL-CROSSMODULE-001: Toda referencia cross-modulo en services.yml DEBE usar `@?` (opcional). Solo `ecosistema_jaraba_core` y submodulos propios permiten `@` hard. Validacion: `php scripts/validation/validate-optional-deps.php`
- CONTAINER-DEPS-002: NUNCA crear dependencias circulares en services.yml. Si A necesita B y B necesita A, una direccion DEBE ser `@?` o lazy-load via `\Drupal::service()`. Validacion: `php scripts/validation/validate-circular-deps.php`
- LOGGER-INJECT-001: Si services.yml inyecta `@logger.channel.X`, el constructor PHP DEBE aceptar `LoggerInterface $logger` directamente (NO llamar `->get('channel')`). `->get()` solo es valido con `@logger.factory`. Validacion: `php scripts/validation/validate-logger-injection.php`
- PHANTOM-ARG-001: args en services.yml DEBEN coincidir exactamente con params del constructor PHP. Deteccion bidireccional: args de MAS (phantom) Y de MENOS (missing). Missing es mas peligroso ($container->has() devuelve TRUE pero get() lanza TypeError transitivo). Validacion: `php scripts/validation/validate-phantom-args.php` + 12 tests regresion en tests/test-phantom-args-parser.php
- STRIPE-ENV-UNIFY-001: Secrets de Stripe via `getenv()` en settings.secrets.php. NUNCA en config/sync/. Multiples config namespaces (core.stripe, foc.settings, legal_billing.settings) via un solo fichero

### PHP
- PHP 8.4 — respeta sus restricciones:
  - CONTROLLER-READONLY-001: ControllerBase::$entityTypeManager NO tiene declaracion de tipo. Subclases NO DEBEN usar `protected readonly` en constructor promotion para propiedades heredadas
  - DRUPAL11-001: Hijos NO pueden redeclarar typed properties del padre. Asignar manualmente en constructor body
  - TRAIT-CONST-001: No acceder constantes de trait via `TraitName::CONSTANT`. Usar instancia de clase que usa el trait
- declare(strict_types=1) en archivos nuevos (93% cobertura actual)
- PHPStan: Level 6 con baseline (phpstan.neon + phpstan-security.neon + phpstan-baseline.neon). Baseline 41K+ entradas. Regenerar con `--generate-baseline` si se corrigen errores en lote
- ACCESS-RETURN-TYPE-001: checkAccess() DEBE declarar `: AccessResultInterface` (NO `: AccessResult`). parent::checkAccess() devuelve AccessResultInterface; return type mas restrictivo causa PHPStan error
- Drupal Coding Standards + DrupalPractice (PHPCS en CI)

### Entity Forms — PREMIUM-FORMS-PATTERN-001
- TODA entity form DEBE extender `PremiumEntityFormBase` (NUNCA ContentEntityForm)
- DEBE implementar `getSectionDefinitions()` y `getFormIcon()`
- Fieldsets/details groups PROHIBIDOS. Campos computed: `#disabled = TRUE`
- DI: patron `parent::create()`. 237 forms migrados en 50 modulos
- CHECKBOX-SCOPE-001: Toggle switch SOLO para `.form-type-boolean`. Multi-option (`.form-checkboxes`/`.form-radios`) usa card-style
- OBSERVER-SCROLL-ROOT-001: IntersectionObserver en slide-panel DEBE usar `.slide-panel__body` como root
- STICKY-NAV-WRAP-001: Nav pills en slide-panel con `flex-wrap: wrap` + `position: sticky` + `scroll-margin-top: 100px`

### Content Entities — Reglas Obligatorias
- ENTITY-FK-001: FKs a entidades del mismo modulo = entity_reference. Cross-modulo opcional = integer. tenant_id SIEMPRE entity_reference
- AUDIT-CONS-001: TODA ContentEntity DEBE tener AccessControlHandler en anotacion
- ENTITY-PREPROCESS-001: TODA ContentEntity con view mode DEBE tener template_preprocess_{type}() en .module
- PRESAVE-RESILIENCE-001: Presave hooks con servicios opcionales DEBEN usar hasService() + try-catch
- ENTITY-001: TODA entity con EntityOwnerTrait DEBE declarar `implements EntityOwnerInterface, EntityChangedInterface`
- LABEL-NULLSAFE-001: 20+ entities no tienen "label" en entity_keys. $entity->label() puede devolver NULL
- Admin nav: entities de configuracion en /admin/structure, entities de contenido en /admin/content
- Field UI: TODA entity con field_ui_base_route DEBE tener default local task tab (FIELD-UI-SETTINGS-TAB-001)
- Views: declarar `"views_data" = "Drupal\views\EntityViewsData"` en anotacion

### Database/Queries
- TRANSLATABLE-FIELDDATA-001: Entities translatable guardan campos en {type}_field_data, NO en tabla base. SQL directo DEBE usar _field_data
- QUERY-CHAIN-001: addExpression() y join() devuelven alias (string), NO $this. NUNCA encadenar ->addExpression()->execute()
- DATETIME-ARITHMETIC-001: datetime = VARCHAR 'Y-m-d\TH:i:s', created/changed = INT Unix. Usar UNIX_TIMESTAMP(REPLACE(field,'T',' ')) para convertir
- UPDATE-FIELD-DEF-001: updateFieldStorageDefinition() EXIGE setName($field_name) y setTargetEntityTypeId($entity_type_id) en el BaseFieldDefinition. baseFieldDefinitions() NO los establece — estan en la key del array, no dentro del objeto
- UPDATE-HOOK-CATCH-001: try-catch en hook_update_N() DEBE usar \Throwable (NO \Exception). PHP 8.4 TypeError extiende \Error, no \Exception. catch(\Exception) deja pasar TypeErrors y mata updatedb
- UPDATE-HOOK-REQUIRED-001: TODO cambio en baseFieldDefinitions(), nueva entity (Content O Config), o campo modificado DEBE incluir hook_update_N() con EntityDefinitionUpdateManager::installEntityType(). INCLUYE ConfigEntities — Drupal trackea sus entity type definitions. Sin el hook, CI pasa pero produccion diverge con "entity type needs to be installed"
- UPDATE-HOOK-FIELDABLE-001: Al usar `updateFieldableEntityType()`, DEBE usarse `getFieldStorageDefinitions()` (NO `getBaseFieldDefinitions()`). `getBaseFieldDefinitions()` incluye campos computed (ej: metatag del modulo metatag) que NO tienen storage. Incluirlos crea orphan entries en key-value store `entity.definitions.installed` que generan errores permanentes en /admin/reports/status
- TRANSLATABLE-FIELDS-INSTALL-001: Cuando un hook_update_N() hace una entidad `translatable = TRUE`, DEBE instalar los 6 campos de content_translation: source, outdated, uid, status, created, changed. `updateFieldableEntityType()` solo actualiza el entity type — los campos del modulo content_translation se proveen via hook y requieren `installFieldStorageDefinition()` individual
- Raw SQL SOLO en .install hooks. En todo otro contexto: Entity Query o DB API

### JavaScript
- Vanilla JS + Drupal.behaviors (NO React, NO Vue, NO Angular)
- Traducciones: Drupal.t('string')
- URLs API: SIEMPRE via drupalSettings, NUNCA hardcoded (ROUTE-LANGPREFIX-001)
- XSS: Drupal.checkPlain() para datos de API insertados via innerHTML (INNERHTML-XSS-001)
- CSRF: Token de /session/token cacheado en variable del modulo (CSRF-JS-CACHE-001)

### Twig Templates
- Textos: SIEMPRE {% trans %}texto{% endtrans %} (bloque, NO filtro |t)
- Accesibilidad: aria-labels en interactivos, headings jerarquicos, focus visible
- XSS: NUNCA |raw sin sanitizacion previa servidor (AUDIT-SEC-003)
- Sin logica de negocio (solo presentacion). Usar preprocess para preparar variables
- TWIG-URL-RENDER-ARRAY-001: `url()` en Drupal 11 devuelve **render array** (NO string). NUNCA concatenar con `~`. Solo usar dentro de `{{ }}` donde escapeFilter maneja el render array. Para construir URLs concatenadas, pasar la URL como string desde preprocess PHP o usar path relativo (`'/' ~ directory ~ '/logo.svg'`)
- TWIG-INCLUDE-ONLY-001: Usar `only` en `{% include %}` de parciales para aislar el contexto. Sin `only`, TODAS las variables del template padre se filtran al parcial (incluyendo render arrays que colisionan con variables esperadas como strings). Pasar explicitamente solo las variables necesarias
- TWIG-SYNTAX-LINT-001: NUNCA doble coma `,,` en mappings Twig (causa SyntaxError en Twig 3.x → 500 en runtime). NUNCA `{#` anidado dentro de comentarios Twig (cierra prematuramente). Usar estilo consistente de keys en mappings (sin comillas preferido). Validacion: `php scripts/validation/validate-twig-syntax.php` + pre-commit lint-staged

## THEMING — ecosistema_jaraba_theme

### Arquitectura
- Tema UNICO: `ecosistema_jaraba_theme` (base theme: false)
- NO existe jaraba_theme ni tema base separado
- 5 capas de tokens: SCSS Tokens -> CSS Custom Properties -> Component Tokens -> Tenant Override -> Vertical Presets

### CSS Custom Properties (CSS-VAR-ALL-COLORS-001 — P0)
- Prefijo: `--ej-*` (NUNCA --jaraba-*, NUNCA hex hardcoded)
- CADA color en SCSS DEBE ser `var(--ej-*, fallback)`. Sin excepciones
- Colores de marca: azul-corporativo #233D63, naranja-impulso #FF8C42, verde-innovacion #00A9A5
- Variables inyectadas desde Drupal UI via hook_preprocess_html() -> <style>:root { --ej-* }</style>
- 35+ variables configurables desde Apariencia > Ecosistema Jaraba Theme (13 vertical tabs, 70+ opciones)
- Los tenants pueden sobreescribir via TenantThemeConfig entity (ThemeTokenService cascade)

### SCSS — Reglas
- Dart Sass moderno: @use (NO @import), @use 'sass:color', color-mix()
- SCSS-ENTRY-CONSOLIDATION-001: Si existen name.scss y _name.scss en mismo directorio, Dart Sass falla. Consolidar en entry point
- SCSS-COMPILE-VERIFY-001 (P0): Tras CADA edicion .scss, SIEMPRE recompilar y verificar timestamp CSS > SCSS
- SCSS-COLORMIX-001: Migrar rgba() a color-mix(in srgb, {token} {pct}%, transparent)
- SCSS-COMPILETIME-001: Variables SCSS que alimentan color.scale/adjust/change DEBEN ser hex estatico, NUNCA var(). Para runtime alpha usar color-mix()
- SCSS-001: @use crea scope aislado. Cada parcial DEBE incluir `@use '../variables' as *;`
- Huerfanos: scripts/check-scss-orphans.js detecta parciales sin @use en main.scss (bloquea build)
- Compilacion: `npm run build` desde web/themes/custom/ecosistema_jaraba_theme/
- Route SCSS: scss/routes/{name}.scss -> css/routes/{name}.css -> library route-{name} -> hook_page_attachments_alter()
- Bundle SCSS: scss/bundles/{name}.scss -> css/bundles/{name}.css -> library bundle-{name}

### Iconos (ICON-CONVENTION-001)
- Funcion: `{{ jaraba_icon('category', 'name', { variant: 'duotone', color: 'azul-corporativo', size: '24px' }) }}`
- Variante default: duotone (ICON-DUOTONE-001). Solo outline para contextos minimalistas
- Colores SOLO de paleta Jaraba (ICON-COLOR-001): azul-corporativo, naranja-impulso, verde-innovacion, white, neutral
- SVG en canvas_data: hex explicito en stroke/fill, NUNCA currentColor (ICON-CANVAS-INLINE-001)
- NO emojis Unicode como iconos visuales en Page Builder (ICON-EMOJI-001)
- ICON-INTEGRITY-001: Toda referencia jaraba_icon() DEBE resolverse a SVG real. Validacion: `php scripts/validation/validate-icon-references.php`. Detecta categoria incorrecta con hint "Found in category X instead!"
- ICON-INTEGRITY-002: renderIcon() cascade fallback: SVG exacto → genérico categoría → placeholder invisible. NUNCA emoji chincheta. Logger warning en dev. Validación: `php scripts/validation/validate-icon-completeness.php`
- ICON-CANVAS-INLINE-002: SVGs via `<img>` NO soportan currentColor (se interpreta como negro). Categoría `ai/` DEBE usar hex inline (#233D63, #FF8C42, #00A9A5)
- MARKETING-TRUTH-001: Claims marketing en templates DEBEN coincidir con billing real. 14 días trial Stripe, NO "gratis para siempre". Validación: `php scripts/validation/validate-marketing-truth.php`
- CASE-STUDY-PATTERN-001: Cada vertical comercial tiene landing caso de éxito (15 secciones, LANDING-CONVERSION-SCORE-001 15/15). Controller unificado `CaseStudyLandingController` en `jaraba_success_cases`. Template único `case-study-landing.html.twig` con 15 parciales `_cs-*.html.twig`. Datos desde `SuccessCase` entity (SUCCESS-CASES-001). Precios desde `MetaSitePricingService` (NO-HARDCODE-PRICE-001). URLs via `Url::fromRoute()` (ROUTE-LANGPREFIX-001). Módulos con str_starts_with en preprocess_page DEBEN excluir `.case_study.` routes. 9/9 cubiertos (Demo excluido). Validación: `php scripts/validation/validate-case-study-completeness.php` + `php scripts/validation/validate-case-study-conversion-score.php` + `php scripts/validation/validate-success-cases-ssot.php`
- SUCCESS-CASES-001: Todo caso de éxito publicado DEBE provenir de la entidad centralizada `SuccessCase`. NUNCA hardcodear métricas, testimonios o narrativas en controllers/templates. API: `/api/success-cases`. Seed: `scripts/migration/seed-success-cases.php`. Validación: `php scripts/validation/validate-success-cases-ssot.php`
- DEMO-VERTICAL-PATTERN-001: Patrón replicable demos verticales: 9 componentes parametrizados por profileId, 11/11 perfiles
- LEAD-MAGNET-CRM-001: PublicSubscribeController auto-crea CRM Contact (source=lead_magnet) + Opportunity (stage=mql) para sources lead_magnet_*. Servicios CRM opcionales via $container->has(). Patrón idéntico a VerticalQuizService::createCrmLead()
- VIDEO-HERO-001: Video hero autoplaying 9/9 verticales con IntersectionObserver pause/play + prefers-reduced-motion + navigator.connection.saveData. JS: landing-hero-video.js. Vídeos en themes/custom/ecosistema_jaraba_theme/videos/hero-*.mp4
- LANDING-CONVERSION-SCORE-001: 15 criterios para landing 10/10 clase mundial (hero+urgency, trust badges, pain points, steps, features, comparison, social proof, lead magnet, pricing tiers, FAQ, final CTA, sticky CTA, reveal animations, tracking, mobile-first)
- HOMEPAGE-ELEVATION-001: Homepage SaaS + 3 metasitios elevados a 10/10. 4 variantes via `homepage_variant` en preprocess (pepejaraba=5 marca persona, jarabaimpact=6 franquicia, pde=7 corporativo, generic). Parciales: _homepage-pain-points, _homepage-pricing-preview, _homepage-comparison, _homepage-features. Video hero demo-showcase.mp4. Validator: HOMEPAGE-COMPLETENESS-001 (15 checks)

### Quiz de Recomendacion de Vertical
- Ruta: `/test-vertical` (publica, accesible sin login)
- Entity: `QuizResult` — persiste respuestas, scores, recomendacion IA, email, UTM, ip_hash (GDPR)
- Service: `VerticalQuizService` — scoring estatico (reglas) + IA async (tier fast) + CRM lead
- 4 preguntas: perfil, sector, necesidad, urgencia → scoring 9 verticales → top 1 + 2 alternativas
- CRM: Auto-crea Contact (source=quiz_vertical) + Opportunity (stage=mql, BANT parcial)
- QUIZ-FUNNEL-001: Validacion integridad del funnel quiz. 18 checks. `php scripts/validation/validate-quiz-funnel.php`
- CTA-DESTINATION-001: Validar que CTAs apuntan a rutas existentes. `php scripts/validation/validate-cta-destinations.php`
- FUNNEL-COMPLETENESS-001: Todo CTA de conversion DEBE tener data-track-cta + data-track-position. `php scripts/validation/validate-funnel-tracking.php`
- VERTICAL-COVERAGE-001: Los 9 verticales comerciales DEBEN estar en mega menu, quiz y cross-pollination. `php scripts/validation/validate-vertical-coverage.php`
- Setup Wizard: CompletarQuizStep (global, opcional, weight 85) — incentiva quiz a logueados que no lo hicieron
- Daily Action: ExplorarQuizAction (global) — visible solo si uid no tiene QuizResult
- MegaMenuBridgeService: Lee SiteMenuItem entities tipo mega_column → genera mega_menu_columns. Fallback a array PHP estatico si no hay menu configurado en UI
- Post-registro: TenantOnboardingService paso 9 vincula quiz_uuid → user via VerticalQuizService::linkResultToUser()
- Email drip: QuizFollowUpCron en hook_cron — 3 fases (24h, 72h, 7d) para leads no convertidos con email
- QUIZ-FOLLOWUP-DRIP-001: hook_mail case 'quiz_followup' con subject por fase + CTA registro

### Precios y Valores Configurables (NO-HARDCODE-PRICE-001)
- NUNCA hardcodear precios EUR en templates Twig. SIEMPRE desde MetaSitePricingService
- Inyectar en preprocess: `$variables['ped_pricing']` via `ecosistema_jaraba_core.metasite_pricing`
- En Twig: `{{ ped_pricing.{vertical}.professional_price|default(fallback) }}`
- Competidores (Aranzadi, vLex) son excepcion: referencia externa no controlable
- Textos marketing (FAQs, propuestas de valor, tax disclaimer) DEBEN ser configurables desde Theme Settings. Fallbacks hardcoded solo para instalaciones frescas
- Validacion: `php scripts/validation/validate-no-hardcoded-prices.php` + post-edit-lint hook
- Admin: `/admin/structure/saas-plan`

### Modelo de Pricing 4 Tiers (PRICING-4TIER-001)
- 4 SaasPlanTier ConfigEntities: free (weight=-10), starter (weight=0), professional (weight=10), enterprise (weight=20)
- El alias 'free' NO es parte de starter — es un tier independiente
- TODA vertical comercial DEBE tener SaasPlan ContentEntity para cada tier
- Validacion: `php scripts/validation/validate-pricing-tiers.php`

### Features Acumulativas (PRICING-FEATURES-ACCUMULATE-001)
- Las features de pricing se ACUMULAN entre tiers: Free < Starter < Professional < Enterprise
- MetaSitePricingService::getPricingPreview() combina: SaasPlan features (base) + SaasPlanFeatures config (avanzadas) + herencia del tier anterior
- Fuente unica: SaasPlanFeatures ConfigEntity para TODOS los tiers (incluyendo {vertical}_free)
- Si falta SaasPlanFeatures para un tier, se usa _default_{tier} como fallback

### Coherencia Addon Pricing (ADDON-PRICING-001)
- Precio addon DEBE ser <= precio Starter del vertical correspondiente
- Regla orientativa: addon_price ≈ 60% del Starter price
- Un addon mas caro que el Starter crea incentivo perverso
- Validacion: `php scripts/validation/validate-pricing-tiers.php` CHECK 6

### Descuento Anual (ANNUAL-DISCOUNT-001)
- price_yearly DEBE ser price_monthly x 12 x 0.80 (descuento 20%, "2 meses gratis")
- Tolerancia: ± 2 EUR por redondeo
- Badge dinamico en pricing-page.html.twig (calculado desde tiers reales, NO hardcoded)
- Validacion: `php scripts/validation/validate-pricing-tiers.php` CHECK 3

### Compilacion CSS Componentes (SCSS-COMPONENT-BUILD-001)
- TODO CSS cargado como library separada (css/components/*.css, css/routes/*.css) DEBE estar incluido en `npm run build` del tema
- `build:components` en package.json compila pricing-page.css + pricing-hub.css
- Si un CSS de componente no esta en el pipeline de build, los cambios SCSS no se reflejan y el navegador sirve estilos obsoletos

## FRONTEND — ZERO REGION PATTERN

### Paginas Frontend Limpias
- Cada ruta frontend tiene template page--{ruta}.html.twig con layout limpio
- `{{ clean_content }}` en vez de `{{ page.content }}` — extrae solo system_main_block
- `{{ clean_messages }}` para mensajes — extrae system_messages_block
- Body classes via hook_preprocess_html() (NUNCA attributes.addClass() en template)
- Template suggestions via hook_theme_suggestions_page_alter() y $ecosistema_prefixes

### Zero Region Rules
- ZERO-REGION-001: Variables y drupalSettings via hook_preprocess_page(). Controller devuelve solo ['#type'=>'markup', '#markup'=>'']
- ZERO-REGION-002: NUNCA pasar entity objects como non-# keys en render arrays
- ZERO-REGION-003: #attached del controller NO se procesa. Usar $variables['#attached']['drupalSettings'] en preprocess

### Parciales Twig (65+ archivos en templates/partials/)
- _header.html.twig (dispatcher: classic/minimal/transparent)
- _footer.html.twig (layouts: mega/standard/split, 3 columnas nav configurables desde UI)
- _copilot-fab.html.twig, _command-bar.html.twig, _avatar-nav.html.twig, _bottom-nav.html.twig
- _skeleton.html.twig, _empty-state.html.twig, _review-card.html.twig, _article-card.html.twig
- ANTES de crear contenido nuevo: verificar si existe parcial reutilizable
- Footer content (links, copyright, social) configurado desde Theme Settings UI, NO hardcoded
- ZEIGARNIK-PRELOAD-001: 2 auto-complete global steps (__global__) inyectados en TODOS los wizards (weight -20, -10). Wizards arrancan 25-33% completados. Efecto Zeigarnik: +12-28% tasa de finalizacion
- _setup-wizard.html.twig (203 lineas): Progress circle SVG, stepper, auto-collapse, jaraba_icon()
- _daily-actions.html.twig (88 lineas): Grid layout, badges, color variants, slide-panel support

### Modales y Slide-Panel
- TODA accion crear/editar/ver en frontend DEBE abrirse en slide-panel (no navegar fuera)
- SLIDE-PANEL-RENDER-001: Usar renderPlain() (NO render()). Set $form['#action'] = $request->getRequestUri()
- Deteccion: isSlidePanelRequest() = isXmlHttpRequest() && !_wrapper_format
- SLIDE-PANEL-RENDER-002: Rutas con `_form:` en routing.yml NUNCA sirven para slide-panel — Drupal renderiza pagina completa con header/footer/blocks. Para slide-panel, SIEMPRE crear ruta con `_controller:` que detecte isSlidePanelRequest() y use renderPlain(). Patron: ver TenantSelfServiceController::planSlidePanel(), CoordinadorFormController::handleEntityForm()
- FORM-CACHE-001: NUNCA setCached(TRUE) incondicional (LogicException en GET/HEAD)

## SEGURIDAD

### Reglas Criticas
- SECRET-MGMT-001: NUNCA secrets en config/sync/. Usar getenv() via settings.secrets.php. NO usar Key module
- AUDIT-SEC-001: Webhooks con HMAC + hash_equals(). Query string token insuficiente
- AUDIT-SEC-002: Rutas con datos tenant DEBEN usar _permission (no solo _user_is_logged_in)
- API-WHITELIST-001: Endpoints con campos dinamicos DEBEN definir ALLOWED_FIELDS y filtrar input
- CSRF-API-001: API routes via fetch() usan _csrf_request_header_token: 'TRUE' (NO _csrf_token)
- ACCESS-STRICT-001: Comparaciones ownership con (int)..===(int), NUNCA ==
- ACCESS-RETURN-TYPE-001: checkAccess() DEBE declarar `: AccessResultInterface` (NO `: AccessResult`). parent::checkAccess() devuelve AccessResultInterface; return type mas restrictivo causa PHPStan error. 68 handlers migrados
- STRIPE_WEBHOOK_SECRET: Variable obligatoria en settings.secrets.php para verificacion HMAC de webhooks Stripe (AUDIT-SEC-001). Sin ella, checkout.session.completed e invoice.payment_failed no se verifican
- CSRF-LOGIN-FIX-001 v2: IONOS termina SSL; Apache/PHP recibe HTTP. Fix: `$_SERVER['HTTPS']='on'` desde X-Forwarded-Proto ANTES del bootstrap Drupal. Aplicado por `patch-settings-csrf.php` (ejecutar en cada deploy). Sin esto, SessionConfiguration.php override cookie_secure → session perdida → CSRF falla

### Rutas y URLs
- ROUTE-LANGPREFIX-001: URLs SIEMPRE via Url::fromRoute(). El sitio usa /es/ prefix. Paths hardcoded causan 404
- CHECKOUT-ROUTE-COLLISION-001: Commerce Checkout captura /checkout/* (step=null default). Rutas billing en /planes/checkout/*
- STRIPE-URL-PREFIX-001: stripeRequest() endpoints SIN /v1/ (base URL ya incluye /v1). /products NO /v1/products
- CSP-STRIPE-SCRIPT-001: js.stripe.com en script-src + connect-src + frame-src de CSP

## SEO MULTI-DOMINIO

### Reglas Criticas
- SEO-HREFLANG-FRONT-001: Url::fromRoute('<front>') genera /es/node porque system.site.front=/node. SIEMPRE interceptar homepage y construir URL limpia $host/$langcode
- SEO-METASITE-001: Cada dominio/metasitio DEBE tener title y description unicos en homepage. Configurables desde Theme Settings > TAB 17 SEO Multi-Dominio. 4 variantes: generic, pde, jarabaimpact, pepejaraba
- SEO-HREFLANG-ACTIVE-001: Campo seo_active_languages en Theme Settings filtra hreflang a idiomas con contenido real (default: 'es'). Idiomas en/pt-br configurados sin contenido NO se emiten en hreflang
- SEO-MULTIDOMAIN-001: Validador 10 checks. `php scripts/validation/validate-seo-multi-domain.php`

### robots.txt Dinamico
- web/robots.txt ELIMINADO (renombrado a .drupal-default). composer.json scaffold excluye robots.txt
- Controller dinamico: jaraba_page_builder/SitemapController::robots() genera Sitemap: con hostname del request
- Nginx: try_files $uri /index.php?$query_string para /robots.txt

### Schema.org Review Snippets
- Google SOLO acepta 17 tipos como parent de AggregateRating: Product, Course, LocalBusiness, Event, Book, Recipe, SoftwareApplication, HowTo, Organization, Movie, Game, etc.
- EducationalOccupationalProgram y Service (schema.org/Service) NO estan en la lista
- ProfessionalService SI es valido (subtipo de LocalBusiness)
- ReviewSeoController::VERTICAL_SCHEMA_TYPE_MAP mapea verticales a tipos validos
- ReviewSchemaOrgService::resolveSchemaType() mapea entity types a tipos validos

## IA — STACK COMPLETO

### Arquitectura (11 Agentes Gen 2)
- AGENT-GEN2-PATTERN-001: Extienden SmartBaseAgent. Override doExecute() (NO execute())
- SMART-AGENT-CONSTRUCTOR-001: 10 args constructor (6 core + 4 optional @?)
- MODEL-ROUTING-CONFIG-001: 3 tiers en YAML — fast (Haiku 4.5), balanced (Sonnet 4.6), premium (Opus 4.6)
- TOOL-USE-LOOP-001: callAiApiWithTools() con max 5 iteraciones
- NATIVE-TOOLS-001: callAiApiWithNativeTools() con ChatInput::setChatTools()
- MCP-SERVER-001: POST /api/v1/mcp (JSON-RPC 2.0)
- AI-IDENTITY-RULE: Centralizado en AIIdentityRule::apply(). NUNCA duplicar
- AI-GUARDRAILS-PII-001: Detecta DNI, NIE, IBAN ES, NIF/CIF, +34. Bidireccional (input + output)
- SERVICE-CALL-CONTRACT-001: Firmas de metodo DEBEN coincidir exactamente. hasService() NO protege contra TypeError
- COPILOT-BRIDGE-COVERAGE-001: Cada vertical DEBE tener CopilotBridgeService. 10/10 implementados (Demo, Legal, Empleabilidad, Emprendimiento, ComercioConecta, AgroConecta, AndaluciaEi, ContentHub, Formacion, ServiciosConecta)
- STREAMING-PARITY-001: StreamingOrchestratorService y CopilotOrchestratorService deben mantener paridad funcional
- GROUNDING-PROVIDER-001: Cada vertical DEBE tener GroundingProvider (tagged: jaraba_copilot_v2.grounding_provider). 10/10 implementados. CompilerPass en JarabaCopilotV2ServiceProvider. ContentGroundingService v2 usa providers si disponibles, fallback legacy si no
- CASCADE-SEARCH-001: Busqueda IA en 4 niveles con coste progresivo. N1=siempre (promotions+verticals, cache ~0). N2=keyword match (GroundingProviders). N3=por necesidad (Qdrant, memory). N4=bajo demanda (ToolUse, max 5 iter). Anonimos: N1+N2+N3(cache). Pro: N1-N4
- ACTIVE-PROMOTION-001: ActivePromotionService resuelve promociones activas (PromotionConfig ConfigEntity). Inyectado en Nivel 1 cascada. Cache tag promotion_config_list, max-age 300s. Admin: /admin/structure/promotion-config
- COPILOT-LEAD-CAPTURE-001: CopilotLeadCaptureService detecta intencion de compra (regex, NO LLM) y crea CRM Contact+Opportunity. Patron LEAD-MAGNET-CRM-001. Dependencias CRM opcionales (@?)
- COPILOT-FUNNEL-TRACKING-001: CopilotFunnelTrackingService loguea eventos embudo en tabla copilot_funnel_event. 8 tipos evento. Alto volumen (tabla directa, no entity)

### Legal Coherence Intelligence System (LCIS)
- LCIS-AUDIT-001: Audit trail obligatorio para EU AI Act Art. 12. Toda respuesta legal trazable
- LCIS-GRAPH-001: Normative graph con derogaciones/modificaciones entre normas juridicas
- 9 capas: KB → IntentClassifier → NormativeGraph → PromptRule → Response → Validator → Verifier → Disclaimer → Feedback

### Servicios Clave IA
- ModelRouterService, ProviderFallbackService (circuit breaker), ContextWindowManager
- ReActLoopService, HandoffDecisionService, ToolRegistry (tagged services)
- StreamingOrchestratorService (SSE via PHP Generator), TraceContextService
- SemanticCacheService (Qdrant), AgentLongTermMemoryService, AgentBenchmarkService
- AutoDiagnosticService (self-healing via State API), PromptVersionService
- FairUsePolicyService: enforcement de limites por plan, burst tolerance, grace period
- ActivePromotionService: conciencia centralizada de promociones activas (PromotionConfig ConfigEntity)
- ContentGroundingService v2: 10 GroundingProviders (CompilerPass + tagged services). Busca contenido real en TODOS los entity types del ecosistema
- CopilotLeadCaptureService: deteccion intencion compra (regex) + CRM leads. Patron LEAD-MAGNET-CRM-001
- CopilotFunnelTrackingService: tabla copilot_funnel_event, 8 tipos evento embudo ventas

## GRAPESJS / PAGE BUILDER

### Arquitectura
- GrapesJS 5.7 embebido en jaraba_page_builder (NO modulo separado)
- 11 plugins custom: jaraba-canvas, jaraba-blocks, jaraba-icons, jaraba-ai, jaraba-seo, jaraba-reviews, etc.
- Vendor JS local (NO CDN) para evitar CSP: js/vendor/grapes.min.js
- CANVAS-ARTICLE-001: ContentArticle reutiliza engine GrapesJS via library dependency (NO duplicar)
- Canvas editor: full-viewport HtmlResponse que bypasa page template system
- Dual architecture: GrapesJS script functions (editor) + Drupal behaviors (frontend publicado)
- ICON-EMOJI-001: NO emojis Unicode en canvas_data. Usar SVG inline con hex de marca
- SAFEGUARD-CANVAS-001: 4 capas proteccion canvas — (1) Backup pre-save (canvas_data snapshot), (2) Restore via revision history, (3) Presave JSON validation, (4) Post-save rendered output verification

## TESTING

### Configuracion
- phpunit.xml en raiz del proyecto. Suites: Unit, Kernel, Functional, PromptRegression
- CI: Unit + Kernel tests con MariaDB 10.11 service container (CI-KERNEL-001)
- Coverage minimo: 80% en modulos jaraba_*

### Reglas de Testing
- KERNEL-TEST-DEPS-001: $modules NO auto-resuelve dependencias. Listar TODOS los modulos requeridos
- KERNEL-TEST-001: KernelTestBase SOLO cuando test necesita DB/entities. Para reflexion/constantes: TestCase
- KERNEL-SYNTH-001: Dependencias de modulos no cargados: registrar como synthetic en register(ContainerBuilder)
- KERNEL-TIME-001: Assertions de timestamp con tolerancia +/-1 segundo
- MOCK-DYNPROP-001: PHP 8.4 prohibe dynamic properties en mocks. Usar clases anonimas con typed properties
- MOCK-METHOD-001: createMock() solo soporta metodos de la interface dada. Usar ContentEntityInterface para hasField()
- TEST-CACHE-001: Entity mocks DEBEN implementar getCacheContexts, getCacheTags, getCacheMaxAge

## DOCUMENTACION — REGLAS CRITICAS

### DOC-GUARD-001
- NUNCA sobreescribir master docs (00_*.md) con Write. SIEMPRE Edit incremental
- Pre-commit hook: max 10% perdida de lineas + umbrales absolutos:
  DIRECTRICES >= 2000 | ARQUITECTURA >= 2400 | INDICE >= 2000 | FLUJO >= 700
- COMMIT-SCOPE-001: Commits de master docs SEPARADOS de codigo. Prefijo `docs:`
- DOC-GLOSSARY-001: Todo documento extenso (>200 lineas) DEBE incluir un glosario de siglas al final. Cada sigla usada en el texto debe estar definida con su significado completo en espanol

### Versiones Actuales
- DIRECTRICES: v160.0.0
- ARQUITECTURA: v146.0.0
- INDICE: v190.0.0
- FLUJO: v112.0.0
- Ultimo aprendizaje: #213
- Ultima golden rule: #149

## RUNTIME-VERIFY-001 — VERIFICACION POST-IMPLEMENTACION
Tras completar un feature, verificar 5 dependencias runtime:
1. CSS compilado (timestamp > SCSS)
2. Tablas DB creadas (si aplica)
3. Rutas en routing.yml accesibles
4. data-* selectores matchean entre JS y HTML
5. drupalSettings inyectado correctamente

> La diferencia entre "el codigo existe" y "el usuario lo experimenta" requiere
> verificacion en CADA capa: PHP -> Twig -> SCSS -> CSS compilado -> JS -> drupalSettings -> DOM final.

## IMPLEMENTATION-CHECKLIST-001 — Verificacion Post-Implementacion

Tras completar CUALQUIER feature, verificar ANTES de considerar "terminado":

### Complitud
- Servicio registrado en services.yml Y consumido por al menos 1 otro servicio/controller
- Rutas en routing.yml apuntan a clases/metodos existentes
- Si entity nueva: AccessControlHandler, hook_theme, template_preprocess, Views data
- Si SCSS nuevo: compilado, library registrada, hook_page_attachments_alter

### Integridad
- Tests existen: Unit para servicios, Kernel para entities, Functional para rutas
- hook_update_N() si cambio baseFieldDefinitions o nueva entity (Content O Config). ConfigEntities tambien necesitan installEntityType()
- Config export si nuevas config entities
- Verificar: `php scripts/validation/validate-entity-integrity.php` (detecta entities sin hook_update_N)

### Consistencia
- Patron PREMIUM-FORMS-PATTERN-001 en forms
- CONTROLLER-READONLY-001 en controllers
- CSS-VAR-ALL-COLORS-001 en SCSS
- TENANT-001 en queries

### Pipeline E2E (PIPELINE-E2E-001)
- Si feature involucra dashboards, verificar 4 capas:
  - L1: Service inyectado en controller (constructor + create())
  - L2: Controller pasa datos al render array (#setup_wizard, #daily_actions)
  - L3: hook_theme() declara las variables en el array 'variables'
  - L4: Template incluye parciales con textos traducidos y `only` keyword

### Setup Wizard + Daily Actions (SETUP-WIZARD-DAILY-001)
- Patron transversal: SetupWizardRegistry + DailyActionsRegistry (tagged services via CompilerPass)
- 52 wizard steps + 39 daily actions en 9 verticales
- ZEIGARNIK-PRELOAD-001: 2 auto-complete global steps (`__global__`) inyectados en TODOS los wizards. Wizards arrancan 33-50% (efecto Zeigarnik +12-28% completion)
- User-scoped verticals (candidate, legal, content_hub): usar `currentUser()->id()` como contextId, NO 0
- Tenant-scoped verticals (agro, comercio, servicios, lms, ei): usar `TenantContextService::getCurrentTenantId()`
- Anti-patron: Completar L1-L2 sin verificar L3-L4. Drupal descarta variables no declaradas en hook_theme() silenciosamente

### Coherencia
- Documentacion actualizada (master docs si aplica)
- CLAUDE.md actualizado si nueva regla descubierta
- Memory files actualizados si patron nuevo

### Automatizacion
- `bash scripts/validation/validate-all.sh --checklist web/modules/custom/{modulo}`
- `php scripts/validation/validate-service-consumers.php` (SERVICE-ORPHAN-001)
- `php scripts/validation/validate-compiled-assets.php` (ASSET-FRESHNESS-001)
- `php scripts/validation/validate-scss-compile-freshness.php` (SCSS-COMPILE-FRESHNESS-001) — verifica que CADA CSS route/bundle es más reciente que TODOS los SCSS parciales que importa
- `php scripts/validation/validate-case-study-conversion-score.php` (CASE-STUDY-CONVERSION-001) — 15/15 LANDING-CONVERSION-SCORE
- `php scripts/validation/validate-success-cases-ssot.php` (SUCCESS-CASES-SSOT-001) — SuccessCase entity es SSOT
- `php scripts/validation/validate-tenant-isolation.php` (TENANT-CHECK-001)
- `php scripts/validation/validate-test-coverage-map.php` (TEST-COVERAGE-MAP-001)
- `php scripts/validation/validate-circular-deps.php` (CONTAINER-DEPS-002)
- `php scripts/validation/validate-optional-deps.php` (OPTIONAL-CROSSMODULE-001)
- `php scripts/validation/validate-logger-injection.php` (LOGGER-INJECT-001)
- `php scripts/validation/validate-icon-references.php` (ICON-INTEGRITY-001)
- `php scripts/validation/validate-quiz-funnel.php` (QUIZ-FUNNEL-001)
- `php scripts/validation/validate-cta-destinations.php` (CTA-DESTINATION-001)
- `php scripts/validation/validate-funnel-tracking.php` (FUNNEL-COMPLETENESS-001)
- `php scripts/validation/validate-vertical-coverage.php` (VERTICAL-COVERAGE-001)
- `php scripts/validation/validate-wizard-daily-coverage.php` (SETUP-WIZARD-DAILY-001)
- `php scripts/validation/validate-page-builder-onboarding.php` (PB-ONBOARDING-001)
- `php scripts/validation/validate-content-pipeline-e2e.php` (CONTENT-E2E-001)
- `php scripts/validation/validate-plg-triggers.php` (PLG-COVERAGE-001)
- `php scripts/validation/validate-icon-completeness.php` (ICON-COMPLETENESS-001)
- `php scripts/validation/validate-marketing-truth.php` (MARKETING-TRUTH-001)
- `php scripts/validation/validate-case-study-completeness.php` (CASE-STUDY-COMPLETENESS-001)
- `php scripts/validation/validate-hook-theme-completeness.php` (HOOK-THEME-COMPLETENESS-001)
- `php scripts/validation/validate-duplicate-hooks.php` (DUPLICATE-HOOK-001)
- `php scripts/validation/validate-scss-variables.php` (SCSS-VARIABLE-EXIST-001)
- `php scripts/validation/validate-library-attachments.php` (LIBRARY-ATTACHMENT-001) [warn]
- `php scripts/validation/validate-twig-syntax.php` (TWIG-SYNTAX-LINT-001)
- `php scripts/validation/validate-twig-include-only.php` (TWIG-INCLUDE-ONLY-001) [warn]
- `php scripts/validation/validate-route-permissions.php` (ROUTE-PERMISSION-AUDIT-001)
- `php scripts/validation/validate-hook-update-coverage.php` (HOOK-UPDATE-COVERAGE-001)
- `php scripts/validation/validate-js-syntax.php` (JS-SYNTAX-LINT-001)
- `php scripts/validation/validate-csp-completeness.php` (CSP-DOMAIN-COMPLETENESS-001) [warn]
- `php scripts/validation/validate-hook-requirements-coverage.php` (HOOK-REQUIREMENTS-COVERAGE-001) [warn]
- `php scripts/validation/validate-validator-coverage.php` (VALIDATOR-COVERAGE-001) — meta-safeguard
- `php scripts/validation/validate-pricing-tiers.php` (PRICING-TIER-PARITY-001 + PRICING-4TIER-001 + ANNUAL-DISCOUNT-001)
- `php scripts/validation/validate-schema-org-pricing.php` (SCHEMA-PRICING-001)
- `php scripts/validation/validate-pricing-coherence.php` (PRICING-COHERENCE-001)
- `php scripts/validation/validate-copilot-grounding-coverage.php` (COPILOT-GROUNDING-COVERAGE-001)
- `php scripts/validation/validate-promotion-copilot-sync.php` (PROMOTION-COPILOT-SYNC-001)
- `php scripts/validation/validate-copilot-intent-patterns.php` (COPILOT-INTENT-ACCURACY-001)
- `php scripts/validation/validate-copilot-response-quality.php` (COPILOT-RESPONSE-QUALITY-001)
- `php scripts/validation/validate-promotion-expiry-alert.php` (PROMOTION-EXPIRY-ALERT-001)
- `php scripts/validation/validate-grounding-provider-health.php` (GROUNDING-PROVIDER-HEALTH-001)
- `php scripts/validation/validate-crm-funnel-attribution.php` (CRM-FUNNEL-ATTRIBUTION-001)
- `php scripts/validation/validate-copilot-prompt-drift.php` (COPILOT-PROMPT-DRIFT-001)
- `php scripts/validation/validate-homepage-completeness.php` (HOMEPAGE-COMPLETENESS-001)
- `php scripts/validation/validate-homepage-variant-coherence.php` (HOMEPAGE-VARIANT-COHERENCE-001)
- `php scripts/validation/validate-homepage-video-a11y.php` (HOMEPAGE-VIDEO-A11Y-001)
- `php scripts/validation/validate-seo-multi-domain.php` (SEO-MULTIDOMAIN-001)
- `php scripts/validation/validate-email-sender.php` (EMAIL-SENDER-001)
- `php scripts/validation/validate-deploy-safety.php` (DEPLOY-MAINTENANCE-SAFETY-001)
- `php scripts/validation/validate-backup-health.php` (BACKUP-HEALTH-001)

## SAFEGUARD SYSTEM — 6 Capas de Defensa (99 scripts, 100% madurez)

| Capa | Mecanismo | Cuando | Cobertura |
|------|-----------|--------|-----------|
| 1 | 99 scripts validacion (scripts/validation/) | On demand, CI | 105 checks (87 run + 18 warn) |
| 2 | Pre-commit hooks (Husky + lint-staged, chmod +x obligatorio) | Antes de cada commit | 8 file types: PHP/SCSS/MD/Twig/services.yml/routing.yml/JS |
| 3 | CI Pipeline Gates (ci.yml + fitness-functions.yml) | Push + PR | PHPStan L6, tests, security scan, 26 arch checks |
| 4 | Runtime Self-Checks (hook_requirements) | En /admin/reports/status | 83/86 modulos (96%) |
| 5 | IMPLEMENTATION-CHECKLIST-001 (este doc) | Al completar features | Complitud+Integridad+Consistencia+Coherencia |
| 6 | PIPELINE-E2E-001 (4 capas L1-L4) | Al completar features con UI dashboard | Service→Controller→hook_theme→Template |

### Pre-commit lint-staged (detalle)
- `**/*.php`: PHPStan Level 6
- `**/*.scss`: validate-compiled-assets.php
- `docs/00_*.md`: verify-doc-integrity.sh (DOC-GUARD-001)
- `**/*.html.twig`: validate-twig-syntax.php (TWIG-SYNTAX-LINT-001) + validate-twig-ortografia.php
- `**/*.js` (modules+theme): validate-js-syntax.php (JS-SYNTAX-LINT-001)
- `**/*.libraries.yml`: validate-library-attachments.php (LIBRARY-ATTACHMENT-001)
- `**/*.services.yml`: validate-phantom-args + validate-optional-deps + validate-circular-deps + validate-logger-injection (~3s)
- `**/*.routing.yml`: validate-all.sh --fast
