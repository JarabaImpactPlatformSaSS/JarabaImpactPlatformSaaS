# JARABA IMPACT PLATFORM — CLAUDE.md
# Ultima actualizacion: 2026-03-27 | Version: 1.13.0
# Ecosistema: 10 verticales, 196+ especificaciones, 80+ modulos custom, Drupal 11

## IDENTIDAD DEL PROYECTO
- Nombre: Jaraba Impact Platform (Ecosistema Jaraba)
- Filosofia: "Sin Humo" — codigo limpio, practico, sin complejidad innecesaria
- Stack: Drupal 11 + PHP 8.4 + MariaDB 10.11 + Redis 8.0 + Qdrant
- Multi-tenancy: Group Module (soft isolation) + TenantBridgeService
- Pagos: Stripe Connect (destination charges)
- IA: Claude API + Gemini API con strict grounding, 11 agentes Gen 2
- Servidor: IONOS Dedicated (AMD EPYC 12c/24t, 128GB DDR5, 2x1TB NVMe RAID1, Ubuntu 24.04). IP: 82.223.204.169, SSH: 2222
- Stack nativo: Nginx + PHP-FPM 8.4 + MariaDB 10.11 + Redis 8.0 + Supervisor (4 AI workers) + Tika
- Email: SMTP IONOS (smtp.ionos.es:587), From: contacto@plataformadeecosistemas.com
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
- PHANTOM-ARG-001: args en services.yml DEBEN coincidir con params del constructor. Deteccion bidireccional (phantom + missing). Validacion: validate-phantom-args.php
- OPTIONAL-PARAM-ORDER-001: Parametros opcionales (`= NULL`, `= []`, etc.) NUNCA antes de requeridos en constructors. PHP 8.4 deprecation. Aplica especialmente a servicios `@?` que DEBEN ir al final del constructor. Validacion: `php scripts/validation/validate-optional-param-order.php`
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
- UPDATE-HOOK-CATCH-RETHROW-001: catch en hook_update_N() DEBE hacer `throw new \RuntimeException(msg, 0, $e)`. NUNCA `return $errorMsg` — Drupal trata return string como exito y registra schema completado
- UPDATE-HOOK-REQUIRED-001: TODO cambio en baseFieldDefinitions(), nueva entity (Content O Config), o campo modificado DEBE incluir hook_update_N() con EntityDefinitionUpdateManager::installEntityType(). INCLUYE ConfigEntities — Drupal trackea sus entity type definitions. Sin el hook, CI pasa pero produccion diverge con "entity type needs to be installed"
- UPDATE-HOOK-FIELDABLE-001: `updateFieldableEntityType()` DEBE usar `getFieldStorageDefinitions()` (NO `getBaseFieldDefinitions()` que incluye campos computed sin storage)
- TRANSLATABLE-FIELDS-INSTALL-001: hook_update que hace entidad translatable DEBE instalar 6 campos content_translation via `installFieldStorageDefinition()` individual
- Raw SQL SOLO en .install hooks. En todo otro contexto: Entity Query o DB API

### JavaScript
- Vanilla JS + Drupal.behaviors (NO React, NO Vue, NO Angular)
- Traducciones: Drupal.t('string')
- URLs API: SIEMPRE via drupalSettings, NUNCA hardcoded (ROUTE-LANGPREFIX-001)
- XSS: Drupal.checkPlain() para datos de API insertados via innerHTML (INNERHTML-XSS-001)
- URL-PROTOCOL-VALIDATE-001: URLs externas en href DEBEN validar protocolo (http:/https:). Previene javascript: XSS
- CSRF: Token de /session/token cacheado en variable del modulo (CSRF-JS-CACHE-001)

### Twig Templates
- Textos: SIEMPRE {% trans %}texto{% endtrans %} (bloque, NO filtro |t)
- Accesibilidad: aria-labels en interactivos, headings jerarquicos, focus visible
- XSS: NUNCA |raw sin sanitizacion previa servidor (AUDIT-SEC-003)
- TWIG-RAW-AUDIT-001: |raw DEBE tener sanitizacion en controller/preprocess. Validacion: `validate-twig-raw-audit.php`
- Sin logica de negocio (solo presentacion). Usar preprocess para preparar variables
- TWIG-URL-RENDER-ARRAY-001: `url()` en Drupal 11 devuelve **render array** (NO string). NUNCA concatenar con `~`. Solo usar dentro de `{{ }}` donde escapeFilter maneja el render array. Para construir URLs concatenadas, pasar la URL como string desde preprocess PHP o usar path relativo (`'/' ~ directory ~ '/logo.svg'`)
- TWIG-INCLUDE-ONLY-001: Usar `only` en `{% include %}` de parciales para aislar el contexto. Sin `only`, TODAS las variables del template padre se filtran al parcial (incluyendo render arrays que colisionan con variables esperadas como strings). Pasar explicitamente solo las variables necesarias
- MEGAMENU-INJECT-001: Variables criticas de layout via canal secundario theme_settings (`_mega_menu_columns`) para sobrevivir `{% include ... only %}`
- TWIG-SYNTAX-LINT-001: NUNCA doble coma `,,` ni `{#` anidado en Twig. Pre-commit lint-staged + validate-twig-syntax.php

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
- VIEWPORT-DVH-001 (P0): TODA declaracion `100vh` DEBE tener linea `100dvh` inmediatamente despues. Progressive enhancement. Validacion: `validate-viewport-dvh.php`
- NOWRAP-OVERFLOW-001: `white-space: nowrap` DEBE tener `overflow: hidden; text-overflow: ellipsis;` o `// NOWRAP-SAFE:` comentario. Validacion: `validate-nowrap-overflow.php`
- FIXED-STACKING-MOBILE-001: FABs con `position: fixed` en zona inferior DEBEN sumar `calc(56px + env(safe-area-inset-bottom))` cuando `.has-bottom-nav` activo
- IMG-SEED-001: Imagenes SuccessCase via `seed-success-case-images.php` (idempotente, deploy.yml)

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
- SUCCESS-CASES-001: Todo caso de éxito DEBE provenir de SuccessCase entity. NUNCA hardcodear en controllers/templates
- CASE-STUDY-PATTERN-001: preprocess_page DEBE excluir `.case_study.` routes. Datos desde SuccessCase entity
- LANDING-CONVERSION-SCORE-001: 15 criterios clase mundial. Detalles en memory/frontend-patterns.md

### Quiz y Funnel
- CTA-DESTINATION-001: CTAs DEBEN apuntar a rutas existentes
- FUNNEL-COMPLETENESS-001: Todo CTA de conversion DEBE tener data-track-cta + data-track-position
- VERTICAL-COVERAGE-001: 9 verticales comerciales DEBEN estar en mega menu, quiz y cross-pollination

### Precios y Valores Configurables (NO-HARDCODE-PRICE-001)
- NUNCA hardcodear precios EUR en templates Twig. SIEMPRE desde MetaSitePricingService
- Inyectar en preprocess: `$variables['ped_pricing']` via `ecosistema_jaraba_core.metasite_pricing`
- En Twig: `{{ ped_pricing.{vertical}.professional_price|default(fallback) }}`
- Textos marketing configurables desde Theme Settings. Admin: `/admin/structure/saas-plan`

### Pricing 4 Tiers (PRICING-4TIER-001)
- 4 SaasPlanTier ConfigEntities: free (weight=-10), starter (0), professional (10), enterprise (20)
- 'free' es tier independiente (NO parte de starter). TODA vertical DEBE tener SaasPlan por tier
- PRICING-FEATURES-ACCUMULATE-001: Features se ACUMULAN entre tiers. MetaSitePricingService::getPricingPreview() hereda del tier anterior. SaasPlanFeatures ConfigEntity como fuente unica
- ADDON-PRICING-001: Precio addon DEBE ser <= precio Starter (~60%). Incentivo perverso si mayor
- ANNUAL-DISCOUNT-001: price_yearly = price_monthly x 12 x 0.80 (±2 EUR tolerancia)

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
- _setup-wizard.html.twig: Progress circle SVG, stepper, auto-collapse
- _daily-actions.html.twig: Grid layout, badges, color variants, slide-panel support

### Modales y Slide-Panel
- TODA accion crear/editar/ver en frontend DEBE abrirse en slide-panel (no navegar fuera)
- SLIDE-PANEL-RENDER-001: Usar renderPlain() (NO render()). Set $form['#action'] = $request->getRequestUri()
- Deteccion: isSlidePanelRequest() = isXmlHttpRequest() && !_wrapper_format
- SLIDE-PANEL-RENDER-002: Rutas `_form:` NUNCA para slide-panel. SIEMPRE `_controller:` + isSlidePanelRequest() + renderPlain()
- FORM-CACHE-001: NUNCA setCached(TRUE) incondicional (LogicException en GET/HEAD)

## SEGURIDAD

### Reglas Criticas
- SECRET-MGMT-001: NUNCA secrets en config/sync/. Usar getenv() via settings.secrets.php. NO usar Key module
- AUDIT-SEC-001: Webhooks con HMAC + hash_equals(). Query string token insuficiente
- AUDIT-SEC-002: Rutas con datos tenant DEBEN usar _permission (no solo _user_is_logged_in)
- API-WHITELIST-001: Endpoints con campos dinamicos DEBEN definir ALLOWED_FIELDS y filtrar input
- CSRF-API-001: API routes via fetch() usan _csrf_request_header_token: 'TRUE' (NO _csrf_token)
- ACCESS-STRICT-001: Comparaciones ownership con (int)..===(int), NUNCA ==
- STRIPE_WEBHOOK_SECRET: Variable obligatoria en settings.secrets.php para verificacion HMAC de webhooks Stripe (AUDIT-SEC-001)
- CSRF-LOGIN-FIX-001 v2: IONOS termina SSL; Apache/PHP recibe HTTP. Fix: `$_SERVER['HTTPS']='on'` desde X-Forwarded-Proto ANTES del bootstrap Drupal. Aplicado por `patch-settings-csrf.php` (ejecutar en cada deploy). Sin esto, SessionConfiguration.php override cookie_secure → session perdida → CSRF falla
- REDIS-ACL-001: Redis 8.0 ACL file, 3 usuarios (default/admin/monitor), key-pattern `~jaraba_*`. Validacion: `validate-redis-config.php`

### Rutas y URLs
- ROUTE-LANGPREFIX-001: URLs SIEMPRE via Url::fromRoute(). El sitio usa /es/ prefix. Paths hardcoded causan 404
- CHECKOUT-ROUTE-COLLISION-001: Commerce Checkout captura /checkout/* (step=null default). Rutas billing en /planes/checkout/*
- ROUTE-ALIAS-COLLISION-001: page_content path aliases capturan rutas de routing.yml. NUNCA usar /metodo o /certificacion como path de controller — usar /metodologia, /metodo/certificacion. Validacion: validate-route-alias-collision.php
- STRIPE-URL-PREFIX-001: stripeRequest() endpoints SIN /v1/ (base URL ya incluye /v1). /products NO /v1/products
- CSP-STRIPE-SCRIPT-001: js.stripe.com en script-src + connect-src + frame-src de CSP

## INFRAESTRUCTURA PRODUCCION

### Backup 3 Capas (BACKUP-3LAYER-001)
- Local (24h) + Hetzner S3 (30d) + NAS GoodSync SFTP (indefinido). Cron cada 6h. RPO <6h

### Deploy Safety
- DEPLOY-MAINTENANCE-SAFETY-001: Step "Disable maintenance mode" en deploy.yml DEBE tener `if: always()`. Sin esto, deploy fallido deja sitio en 503 indefinidamente
- OPCACHE-PROD-001: `validate_timestamps=0`, 256MB, 20k files. Config en `config/deploy/php/10-opcache-prod.ini`. FPM reload en deploy invalida cache
- SUPERVISOR-SLEEP-001: Workers DEBEN tener sleep 30-60s. Patron: `bash -c 'drush queue:run ...; sleep N'`
- SSH-FINGERPRINT-001: CI/CD DEBE usar fingerprint SSH fijo en Secret. NUNCA ssh-keyscan
- NGINX-HARDENING-001: `server_tokens off` + rate limiting login/API
- BACKUP-ENCRYPT-001: Backups SQL cifrados con GPG antes de almacenar
- VALIDATOR-SECURITY-RUNCHECK-001: Validators CSRF y access handlers como `run_check`
- PII-INPUT-GUARD-001: Input a LLM DEBE pasar por `checkInputPII()` antes de callProvider()

### Content Seeding Pipeline (CONTENT-SEED-PIPELINE-001)
- 3 capas datos SaaS: L1 Config (drush cim), L2 Content plataforma, L3 Content tenant
- L2 NO se sincroniza con drush cex/cim. Pipeline: scripts/content-seed/ (UUID-anchored, idempotente)
- CONTENT-SEED-INTEGRITY-001: Validador en validate-all.sh. Comandos en memory/ia-services-reference.md

### Custom Translations Pipeline (I18N-CUSTOM-PIPELINE-001)
- Traducciones custom (locales_target.customized=1) son DATOS, NO config. `drush cim` NO las propaga
- I18N-DRIFT-001: detecta drift BD vs .po > 5%. Comandos en memory/ia-services-reference.md

### Traduccion Automatica con IA (AUTO-TRANSLATE-001)
- TranslationTriggerService: SSOT de entidades traducibles. Tier 1 (canvas: page_content, content_article) + Tier 2 (texto: 9 entity types)
- hook_entity_insert + hook_entity_update encolan automaticamente en `jaraba_i18n_canvas_translation` (Redis queue)
- TranslationCatchupService: cron cada 6h detecta entidades sin traduccion (max 50/run)
- Worker Supervisor dedicado: `jaraba-i18n-translation` con queue-worker.sh (SUPERVISOR-SLEEP-001)
- AITranslationService: Claude Haiku 4.5 directo via AI Provider (NO orchestrador). Hallucination detection 7 patrones
- TRANSLATION-COVERAGE-001: Validador 5 checks (cobertura >80%, Redis routing, Supervisor worker)

### CLI Context
- DRUSH-URI-CLI-001: `drush/drush.yml` con `options.uri: https://plataformadeecosistemas.com`. Sin esto, `$GLOBALS['base_url']='http://default'` y URLs en emails/tokens/cron son inaccesibles
- EMAIL-SENDER-MATCH-001: `system.site.mail` DEBE coincidir con sender SMTP permitido. IONOS rechaza remitentes sin buzon configurado

### Email Transport Dual (EMAIL-DEDICATED-IP-001)
- Default transport: `smtp_ses` (transaccional Drupal via AWS SES eu-central-1). Fallback: `smtp_ionos`
- EMAIL-DEDICATED-IP-001: IP SES separada de IONOS. Spike bounces NO contamina correo personal
- EMAIL-BOUNCE-SYNC-001: Auto-suppress via SNS webhook `/api/v1/ses/webhook`. Hard bounce=permanente, complaint=permanente, transient=24h cooldown
- EMAIL-FAILOVER-001: Circuit breaker SES→IONOS (3 failures→15min cooldown). hook_mailer_post_render() routing dinámico. _jaraba_ses_transport_build_dsn() desde getenv()
- CONFIG-ENTITY-OVERRIDE-001: $config[] overrides NO aplican a ConfigEntity::load(). DSN DEBE construirse desde getenv() directo
- EMAIL-REPUTATION-MONITOR-001: Cron 6h, bounce >5% WARNING, complaint >0.1% CRITICAL. hook_requirements()
- DKIM RSA 2048. MAIL FROM: `mail.plataformadeecosistemas.com`. SNS topic: `arn:aws:sns:eu-central-1:152645331237:jaraba-ses-notifications`
- Validador: `validate-ses-transport-integrity.php` (10 checks)

### Monitoring
- UptimeRobot: 4 dominios (5min) + API status (5min) + SSL expiry (14 dias alerta)
- Email alertas CI: `scripts/ci-notify-email.php` via SMTP (reemplaza Slack)

## SEO MULTI-DOMINIO

### Reglas Criticas
- SEO-HREFLANG-FRONT-001: Url::fromRoute('<front>') genera /es/node porque system.site.front=/node. SIEMPRE interceptar homepage y construir URL limpia $host/$langcode
- SEO-METASITE-001: Cada dominio/metasitio DEBE tener title y description unicos en homepage. Configurables desde Theme Settings > TAB 17 SEO Multi-Dominio. 4 variantes: generic, pde, jarabaimpact, pepejaraba
- SEO-HREFLANG-ACTIVE-001: Campo seo_active_languages en Theme Settings filtra hreflang a idiomas con contenido real (default: 'es'). Idiomas en/pt-br configurados sin contenido NO se emiten en hreflang. Aplica en theme preprocess_page Y en HreflangService (ambos filtran)
- SEO-REDIRECT-NODE-001: SeoRedirectSubscriber 301 redirige /node (front page path) a /{langcode}. Match exacto — /node/123 NO se redirige. Prioridad 290 en KernelEvents::REQUEST
- SEO-CANONICAL-CLEAN-001: Canonical de homepage SIEMPRE es $host/$langcode (sin /node). Para idiomas no activos (seo_active_languages), canonical apunta al idioma activo default (es) via preg_replace del prefijo
- SEO-ROBOTS-LANGPREFIX-001: robots.txt DEBE incluir disallows con prefijo de idioma para CADA idioma configurado (/es/node/, /en/node/, /pt-br/node/). Generados dinamicamente desde LanguageManager::getLanguages()
- SEO-DEPLOY-NOTIFY-001: Post-deploy, drush jaraba:seo:notify-google envia sitemaps a Google Search Console para los 4 dominios de produccion. GoogleSeoNotificationService + SeoUrlNotificationWorker queue (180/dia/dominio). deploy.yml step con continue-on-error
- SEO-MULTIDOMAIN-001: Validador 17 checks. `php scripts/validation/validate-seo-multi-domain.php`

### robots.txt Dinamico
- web/robots.txt ELIMINADO (renombrado a .drupal-default). composer.json scaffold excluye robots.txt
- Controller dinamico: jaraba_page_builder/SitemapController::robots() genera Sitemap: con hostname del request + disallows con prefijo de idioma (SEO-ROBOTS-LANGPREFIX-001)
- Nginx: try_files $uri /index.php?$query_string para /robots.txt

### Schema.org Review Snippets
- Google SOLO acepta 17 tipos como parent de AggregateRating. ProfessionalService SI (subtipo LocalBusiness). Detalles en memory/frontend-patterns.md

## ANDALUCIA +EI — 2ª EDICION (PIIL CV 2025)

### Arquitectura de Roles
- ROL-PROGRAMA-SSOT-001: RolProgramaService es la SSOT de deteccion de roles. Cascada: Drupal role > legacy permission > entity participante. NUNCA detectar orientador via mentor_profile (acoplamiento cross-modulo eliminado)
- 4 roles: coordinador_ei, orientador_ei, formador_ei (Drupal roles en config/install) + participante (entity-driven). Cada rol tiene dashboard + wizard + daily actions
- RolProgramaLog entity para auditoria FSE+ de asignaciones/revocaciones
- StaffProfileEi entity para perfil profesional del equipo

### Reglas Clave 2ª Edicion
- PACK-VERTEBRADOR-001: Los 5 Packs de servicios son el eje vertebrador de TODA la formacion
- ENTREGABLE-SEED-001: 29 EntregableFormativoEi se crean en hook_insert. Idempotente via PortfolioEntregablesService::seedEntregables()
- ASISTENCIA-DUAL-001: AsistenciaDetalladaEi DEBE distinguir presencial/online_sincronica. Maximo 20% online
- COPILOT-PHASE-PROMPT-001: 6 system prompts por fase del programa. Config YAML editable sin deploy
- Cross-vertical bridges: 7 bridges (4 @? opcionales). Detalles en memory/andalucia-ei-2e.md

### Sprint C+D (2026-03-25)
- PROSPECCION-PIPELINE-001: ProspeccionPipelineService agrupa NegocioProspectadoEi por 6 fases embudo. Kanban drag-drop con API PATCH. CaptacionLeadsAction → prospeccion_pipeline
- LANDING-CAPTACION-001: PruebaGratuitaController ruta publica /andalucia-ei/prueba-gratuita. Formulario HTML → NegocioProspectadoEi (honeypot, urgencia, RGPD)
- CALCULADORA-PE-001: CalculadoraPuntoEquilibrioService. Gastos fijos 144€/mes, precios pack/tier, 4 escenarios. JS calculadora-pe.js consume drupalSettings
- PORTFOLIO-PUBLICO-001: PortfolioPublicoController ruta /portfolio/{participante_id}. 29 entregables por modulo + progreso
- Compliance P1: 8 validaciones (max 25/grupo, no fines semana, 11€/h, jornada parcial, auto-contratacion, ratio 1:60)
- Validador: validate-andalucia-ei-2e-sprint-cd.php (8 checks)

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
- COPILOT-BRIDGE-COVERAGE-001: Cada vertical DEBE tener CopilotBridgeService. 16/16 implementados (10 verticales + CRM, Billing, Support, Email, Social, Analytics). Modulos operativos usan __global__ como vertical key
- AI-COVERAGE-001: Todo modulo con datos de negocio DEBE tener CopilotBridge + GroundingProvider. PredictiveIntegrationService centraliza acceso a jaraba_predictive (lead scoring, churn, forecast, anomalies, retention). Validacion: `php scripts/validation/validate-ai-coverage.php`
- STREAMING-PARITY-001: StreamingOrchestratorService y CopilotOrchestratorService deben mantener paridad funcional
- GROUNDING-PROVIDER-001: Cada vertical DEBE tener GroundingProvider (tagged jaraba_copilot_v2.grounding_provider). 18 implementados. CompilerPass auto-registro
- CASCADE-SEARCH-001: Busqueda IA en 4 niveles con coste progresivo. N1=siempre (promotions+verticals, cache ~0). N2=keyword match (GroundingProviders). N3=por necesidad (Qdrant, memory). N4=bajo demanda (ToolUse, max 5 iter). Anonimos: N1+N2+N3(cache). Pro: N1-N4
- ACTIVE-PROMOTION-001: ActivePromotionService resuelve promociones activas (PromotionConfig ConfigEntity). Inyectado en Nivel 1 cascada. Cache tag promotion_config_list, max-age 300s. Admin: /admin/structure/promotion-config
- COPILOT-LEAD-CAPTURE-001: CopilotLeadCaptureService detecta intencion de compra (regex, NO LLM) y crea CRM Contact+Opportunity. Patron LEAD-MAGNET-CRM-001. Dependencias CRM opcionales (@?)
- COPILOT-FUNNEL-TRACKING-001: CopilotFunnelTrackingService loguea eventos embudo en tabla copilot_funnel_event. 8 tipos evento. Alto volumen (tabla directa, no entity)

- LCIS-AUDIT-001: Audit trail obligatorio EU AI Act Art. 12. Detalles en memory/ia-services-reference.md

## ANALYTICS Y TRACKING
- GA4-CONSENT-MODE-001: Consent Mode v2 default deny. ID en theme_settings.ga4_measurement_id. Parcial _gtm-analytics.html.twig
- META-PIXEL-CONSENT-001: fbq consent revoke por defecto (GDPR). ID en theme_settings.meta_pixel_id
- CTA-TRACKING-001: data-track-cta + data-track-position en TODOS los CTAs. JS: aida-tracking.js
- AB-TESTING-001: ABExperiment + ABVariant entities. Cookie 30d. StatisticalEngineService

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
- CLAUDE-MD-SIZE-001: CLAUDE.md DEBE mantenerse < 39,000 chars. Solo reglas MUST/NEVER/ALWAYS. Estado de implementacion → memory files. Validacion: `php scripts/validation/validate-claude-md-size.php` + pre-commit lint-staged
- STATUS-REPORT-PROACTIVE-001: 3 capas monitoring proactivo — validator (CI), cron (6h, AlertingService + email fallback), GitHub Actions (diario, auto-issue)
- ALERTING-EMAIL-FALLBACK-001: Todo servicio de alerta DEBE tener email como canal de ultimo recurso. Si webhooks fallan o no estan configurados, enviar a system.site.mail
- STATUS-BASELINE-SSOT-001: Lista de warnings esperados DEBE definirse en constante unica (StatusReportMonitorService::EXPECTED_WARNINGS_DEV/PROD) y referenciarse desde todas las capas
- MONITOR-NO-DEADCODE-001: Metodos publicos en servicios de monitoring DEBEN tener al menos 1 consumidor. Dead code en monitoring = alerta silenciada
- NUNCA sobreescribir master docs (00_*.md) con Write. SIEMPRE Edit incremental
- Pre-commit hook: max 10% perdida de lineas + umbrales absolutos:
  DIRECTRICES >= 2000 | ARQUITECTURA >= 2400 | INDICE >= 2000 | FLUJO >= 700
- COMMIT-SCOPE-001: Commits de master docs SEPARADOS de codigo. Prefijo `docs:`
- DOC-GLOSSARY-001: Todo documento extenso (>200 lineas) DEBE incluir un glosario de siglas al final. Cada sigla usada en el texto debe estar definida con su significado completo en espanol

### Versiones Actuales
- Versiones y contadores en MEMORY.md (SSOT). Mantener sincronizado

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
- Orchestrator: `bash scripts/validation/validate-all.sh --checklist web/modules/custom/{modulo}`
- Lista completa validators: `docs/validators-reference.md`

## SAFEGUARD SYSTEM — 6 Capas de Defensa

6 capas: (1) validators, (2) Pre-commit lint-staged, (3) CI Gates, (4) Runtime hook_requirements, (5) IMPLEMENTATION-CHECKLIST-001, (6) PIPELINE-E2E-001
- PREMIUM-CARD-USAGE-001: `.card--premium` (glassmorphism+3D lift+shine) del core. Sobre fondo `var(--ej-background)` (NO blanco puro). Variantes: subtle, bold. Child: `.icon--pulse-hover`
- MARKETING-TRUTH-SCOPE-001: validate-marketing-truth.php DEBE cubrir TODOS los dirs con templates publicos
- SCSS-MODULE-COMPILE-001: Modulos con SCSS propio DEBEN verificar freshness CSS > SCSS

### Pre-commit lint-staged
- PHP: PHPStan L6 | SCSS tema: compiled-assets | SCSS modulos: compile-freshness | docs/00_*.md: doc-integrity | Twig: syntax+ortografia
- JS: js-syntax | libraries.yml: library-attachments | services.yml: phantom-args+optional-deps+circular-deps+logger-injection | routing.yml: validate-all --fast
