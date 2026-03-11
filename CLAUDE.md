# JARABA IMPACT PLATFORM — CLAUDE.md
# Ultima actualizacion: 2026-03-09 | Version: 1.4.0
# Ecosistema: 10 verticales, 178+ especificaciones, 80+ modulos custom, Drupal 11

## IDENTIDAD DEL PROYECTO
- Nombre: Jaraba Impact Platform (Ecosistema Jaraba)
- Filosofia: "Sin Humo" — codigo limpio, practico, sin complejidad innecesaria
- Stack: Drupal 11 + PHP 8.4 + MariaDB 10.11 + Redis 7.4 + Qdrant
- Multi-tenancy: Group Module (soft isolation) + TenantBridgeService
- Pagos: Stripe Connect (destination charges)
- IA: Claude API + Gemini API con strict grounding, 11 agentes Gen 2
- Servidor: IONOS Dedicated L-16 NVMe, 128GB RAM, AMD EPYC
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
- PHANTOM-ARG-001: args en services.yml DEBEN coincidir exactamente con params del constructor PHP. Args extra/faltantes causan errores silenciosos en runtime
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

### Precios y Valores Configurables (NO-HARDCODE-PRICE-001)
- NUNCA hardcodear precios EUR en templates Twig. SIEMPRE desde MetaSitePricingService
- Inyectar en preprocess: `$variables['ped_pricing']` via `ecosistema_jaraba_core.metasite_pricing`
- En Twig: `{{ ped_pricing.{vertical}.professional_price|default(fallback) }}`
- Competidores (Aranzadi, vLex) son excepcion: referencia externa no controlable
- Validacion: `php scripts/validation/validate-no-hardcoded-prices.php` + post-edit-lint hook
- Admin: `/admin/structure/saas-plan`

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

### Modales y Slide-Panel
- TODA accion crear/editar/ver en frontend DEBE abrirse en slide-panel (no navegar fuera)
- SLIDE-PANEL-RENDER-001: Usar renderPlain() (NO render()). Set $form['#action'] = $request->getRequestUri()
- Deteccion: isSlidePanelRequest() = isXmlHttpRequest() && !_wrapper_format
- FORM-CACHE-001: NUNCA setCached(TRUE) incondicional (LogicException en GET/HEAD)

## SEGURIDAD

### Reglas Criticas
- SECRET-MGMT-001: NUNCA secrets en config/sync/. Usar getenv() via settings.secrets.php. NO usar Key module
- AUDIT-SEC-001: Webhooks con HMAC + hash_equals(). Query string token insuficiente
- AUDIT-SEC-002: Rutas con datos tenant DEBEN usar _permission (no solo _user_is_logged_in)
- API-WHITELIST-001: Endpoints con campos dinamicos DEBEN definir ALLOWED_FIELDS y filtrar input
- CSRF-API-001: API routes via fetch() usan _csrf_request_header_token: 'TRUE' (NO _csrf_token)
- ACCESS-STRICT-001: Comparaciones ownership con (int)..===(int), NUNCA ==

### Rutas y URLs
- ROUTE-LANGPREFIX-001: URLs SIEMPRE via Url::fromRoute(). El sitio usa /es/ prefix. Paths hardcoded causan 404

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

## GRAPESJS / PAGE BUILDER

### Arquitectura
- GrapesJS 5.7 embebido en jaraba_page_builder (NO modulo separado)
- 11 plugins custom: jaraba-canvas, jaraba-blocks, jaraba-icons, jaraba-ai, jaraba-seo, jaraba-reviews, etc.
- Vendor JS local (NO CDN) para evitar CSP: js/vendor/grapes.min.js
- CANVAS-ARTICLE-001: ContentArticle reutiliza engine GrapesJS via library dependency (NO duplicar)
- Canvas editor: full-viewport HtmlResponse que bypasa page template system
- Dual architecture: GrapesJS script functions (editor) + Drupal behaviors (frontend publicado)
- ICON-EMOJI-001: NO emojis Unicode en canvas_data. Usar SVG inline con hex de marca

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

### Versiones Actuales
- DIRECTRICES: v127.0.0
- ARQUITECTURA: v115.0.0
- INDICE: v156.0.0
- FLUJO: v80.0.0
- Ultimo aprendizaje: #177
- Ultima golden rule: #118

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

### Coherencia
- Documentacion actualizada (master docs si aplica)
- CLAUDE.md actualizado si nueva regla descubierta
- Memory files actualizados si patron nuevo

### Automatizacion
- `bash scripts/validation/validate-all.sh --checklist web/modules/custom/{modulo}`
- `php scripts/validation/validate-service-consumers.php` (SERVICE-ORPHAN-001)
- `php scripts/validation/validate-compiled-assets.php` (ASSET-FRESHNESS-001)
- `php scripts/validation/validate-tenant-isolation.php` (TENANT-CHECK-001)
- `php scripts/validation/validate-test-coverage-map.php` (TEST-COVERAGE-MAP-001)
- `php scripts/validation/validate-circular-deps.php` (CONTAINER-DEPS-002)
- `php scripts/validation/validate-optional-deps.php` (OPTIONAL-CROSSMODULE-001)
- `php scripts/validation/validate-logger-injection.php` (LOGGER-INJECT-001)

## SAFEGUARD SYSTEM — 5 Capas de Defensa

| Capa | Mecanismo | Cuando |
|------|-----------|--------|
| 1 | Scripts validacion (scripts/validation/) | On demand, CI |
| 2 | Pre-commit hooks (Husky + lint-staged) | Antes de cada commit |
| 3 | CI Pipeline Gates (ci.yml + fitness-functions.yml) | En cada PR |
| 4 | Runtime Self-Checks (hook_requirements) | En /admin/reports/status |
| 5 | IMPLEMENTATION-CHECKLIST-001 (este doc) | Al completar features |
