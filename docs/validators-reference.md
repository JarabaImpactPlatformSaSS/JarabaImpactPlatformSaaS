# Validators Reference — Jaraba Impact Platform

> Fuente de verdad: `scripts/validation/validate-all.sh`
> Orchestrator: `bash scripts/validation/validate-all.sh [--full|--fast|--checklist web/modules/custom/{modulo}]`
> Meta-safeguard: VALIDATOR-COVERAGE-001 detecta orphaned validators
> Ultima actualizacion: 2026-03-26

## Estadisticas

| Metrica | Valor |
|---------|-------|
| Total scripts PHP | 180 |
| run_check (CI blocker) | 127 |
| warn_check (no blocker) | 56 |
| skip_check (fast mode) | 48 |
| Orphaned validators | 0 |

## Run Checks — 121 (bloquean CI)

| Rule ID | Descripcion |
|---------|-------------|
| ADDON-IMPLEMENTATION-001 | Addon module implementation audit |
| ANDALUCIA-EI-ROLES-001 | Program role system integrity: roles exist, permissions, dashboards, wizard, daily actions (9 checks) |
| ANDALUCIA-EI-2E-SPRINT-A-001 | 2ª Edición Sprint A: fields, entities, services, copilot prompts, logos FSE+ (9 checks) |
| ANDALUCIA-EI-2E-SPRINT-CD-001 | 2ª Edición Sprint C+D: pipeline Kanban, calculadora PE, controllers, templates, field names, routes (8 checks) |
| ANDALUCIA-EI-2E-BRIDGES-001 | 2ª Edición cross-vertical bridges: 7 bridges wired + services exist |
| ANDALUCIA-EI-2E-COPILOT-001 | 2ª Edición copilot phase prompts: 6 phases × system prompts |
| ANDALUCIA-EI-2E-ENTREGABLES-001 | 2ª Edición entregables formativos: 29 entregables × seed integrity |
| ANDALUCIA-EI-2E-PACKS-001 | 2ª Edición packs de servicios integrity |
| BACKUP-HEALTH-001 | Backup infrastructure completeness |
| BIGPIPE-TIMING-001 | BigPipe + once() + drupalSettings timing risks |
| BTN-CONTRAST-DARK-001 | Button contrast on dark backgrounds |
| CASE-STUDY-COMPLETENESS-001 | Case study landing completeness (9 verticals) |
| CASE-STUDY-CONVERSION-001 | Case study conversion score 15/15 |
| CONFIG-SYNC-001 | Config install/sync consistency |
| CONTAINER-DEPS-001 | Container dependency integrity (fast) |
| CONTAINER-DEPS-002 | Circular reference detection (fast) |
| CONTENT-E2E-001 | Content pipeline E2E (both PB and CH) |
| CONTROLLER-READONLY-001 | Controller readonly inherited property detection |
| COPILOT-GROUNDING-COVERAGE-001 | Copilot grounding providers for all verticals |
| COPILOT-INTENT-ACCURACY-001 | Copilot intent detection patterns regression |
| COPILOT-PROMPT-DRIFT-001 | Copilot prompt drift detection |
| COPILOT-RESPONSE-QUALITY-001 | Copilot context quality (10 queries) |
| CRM-FUNNEL-ATTRIBUTION-001 | CRM funnel attribution pipeline |
| CSS-HIDDEN-OVERRIDE-001 | CSS display vs HTML hidden conflicts |
| CTA-CONTEXT-CONSISTENCY-001 | PDE templates use corporate CTAs (not SaaS) |
| CTA-DESTINATION-001 | CTA destinations point to existing routes |
| CTA-LOGGED-IN-001 | Conversion CTAs with logged_in conditional |
| DEMO-FEATURES-FORMAT-001 | Demo features rich format (icon+title+desc) |
| DEMO-MULTI-PROFILE-PARITY-001 | Multi-profile verticals discovery parity |
| DEMO-PROFILE-PERSPECTIVE-001 | Demo profiles B2B perspective |
| DEPLOY-MAINTENANCE-SAFETY-001 | Deploy maintenance mode safety gate |
| DEPLOY-READY-001 | Production deploy readiness |
| DI-TYPE-001 | Service DI type consistency |
| DIACRITICS-ES-001 | Spanish diacritics in page_content canvas_data |
| DOC-VERSION-DRIFT-001 | Master docs version coherence |
| DUPLICATE-HOOK-001 | Duplicate function definitions in .module files |
| EMAIL-SENDER-001 | Email sender vs SMTP allowed domain |
| ENTITY-INTEG-001 | Entity convention compliance |
| ENTITY-SCHEMA-SYNC-001 | Entity schema sync (computed orphans, translatable fields) |
| ENV-PARITY-001 | Dev/Prod environment parity |
| FEATURE-GATING-001 | Feature enforcement audit (limits vs controllers) |
| FUNNEL-COMPLETENESS-001 | Conversion CTAs have tracking attributes |
| GROUNDING-PROVIDER-HEALTH-001 | Grounding provider health check |
| HOMEPAGE-COMPLETENESS-001 | Homepage 10/10 conversion completeness |
| TRUST-STRIP-INTEGRITY-001 | Trust strip partner catalog, assets, Twig, MARKETING-TRUTH-001, CSS, JS |
| IMAGE-WEIGHT-001 | Theme image size audit — logos max 100KB, others max 300-500KB |
| DEPRECATED-TEMPLATE-USAGE-001 | No active {% include %} of @deprecated Twig templates |
| NANO-BANANA-ASSET-AUDIT-001 | AI-generated asset traceability registry (warn_check) |
| METASITE-CONTENT-COMPLETENESS-001 | Metasite per-variant content completeness (4 variantes × 32 campos) |
| METASITE-NAV-URLS-001 | Metasite mega menu URLs match real page paths |
| METASITE-VARIANT-MAP-SSOT-001 | Variant map SSOT (no hardcoded duplicates in .theme) |
| METASITE-DEAD-FIELDS-001 | Dead fields cleanup (old TAB 15 genérico) |
| HOMEPAGE-VARIANT-COHERENCE-001 | Homepage variant differentiation |
| HOMEPAGE-VIDEO-A11Y-001 | Video hero accessibility compliance |
| HOOK-DUPLICATE-001 | Duplicate function declarations in .module files |
| HOOK-THEME-COMPLETENESS-001 | hook_theme() variable completeness (L3) |
| HOOK-UPDATE-COVERAGE-001 | Entity types have install/update hooks |
| I18N-NAVPREFIX-001 | Navigation links use language_prefix |
| ICON-INTEGRITY-001 | Icon references resolve to existing SVGs |
| JS-CACHE-BUST-001 | JS library versions are current |
| JS-DATA-ATTR-PARITY-001 | Twig data-* attributes match JS selectors |
| JS-SYNTAX-LINT-001 | JavaScript static syntax lint |
| JS-TWIG-SELECTOR-001 | JS-Twig selector coherence |
| LANDING-PLAN-COHERENCE-001 | Landing page vs plan coherence (Doc 158 v3) |
| LEAD-MAGNET-CRM-001 | Lead magnet to CRM pipeline integrity |
| LOGGER-INJECT-001 | Logger injection consistency (fast) |
| MARKETING-TRUTH-001 | Marketing claims vs billing reality |
| MODULE-ORPHAN-001 | Module consistency (orphan/ghost detection) |
| NO-HARDCODE-PRICE-001 | No hardcoded EUR prices in templates |
| OPTIONAL-CROSSMODULE-001 | Cross-module hard dependency detection |
| OPTIONAL-PARAM-ORDER-001 | Optional constructor params before required (PHP 8.4) |
| ORTOGRAFIA-TRANS-001 | Ortografia en textos traducibles Twig |
| PB-ONBOARDING-001 | Page Builder onboarding integrity (wizard+daily+L1-L4) |
| PHANTOM-ARG-001 | Phantom args in services.yml vs constructor params |
| PHP-DECLARE-ORDER-001 | declare(strict_types) before use statements (PHPCBF safety) |
| PLG-COVERAGE-001 | PLG trigger coverage for Page Builder |
| PREPROCESS-ISOLATION-001 | Preprocess hooks exclude .case_study. routes |
| PRESAVE-RESILIENCE-001 | Presave hook resilience detection |
| PRICING-COHERENCE-001 | Pricing vs delivery consistency (Doc 158 v3) |
| PRICING-TIER-PARITY-001 | Pricing tier parity (4 tiers x 8 verticals) |
| PROMOTION-COPILOT-SYNC-001 | Promotion config coherence with copilot |
| PROMOTION-EXPIRY-ALERT-001 | Promotion expiry detection |
| PSR4-CLASSNAME-001 | PHP class names match filenames (PSR-4) |
| QUERY-CHAIN-001 | Dangerous query method chaining |
| QUIZ-FUNNEL-001 | Quiz vertical funnel integrity |
| ROUTE-CTRL-001 | Route-Controller method validation |
| ROUTE-PERMISSION-AUDIT-001 | Route access control completeness |
| ROUTE-SUBSCRIBER-PARITY-001 | Case study route subscriber covers legacy routes |
| MEGAMENU-INJECT-001 | Mega menu columns injected via theme_settings fallback (3 checks) |
| TEMPLATE-CONTRACT-001 | Page template include-only contract compliance (theme_settings required) |
| DOMAIN-DEFAULT-GUARD-001 | Default domain protected from meta-site resolution |
| SAFEGUARD-AEI-CAMPAIGN-001 | Andalucia +ei reclutamiento landing readiness |
| POPUP-DUAL-SELECTOR-001 | Popup dual integrity (participante+negocio) |
| CONFIG-INSTALL-DRIFT-001 | Config install vs schema drift detection |
| SCHEMA-PRICING-001 | Schema.org pricing dynamic (no hardcoded EUR) |
| SCSS-VARIABLE-EXIST-001 | SCSS variables defined before use |
| SEO-MULTIDOMAIN-001 | SEO multi-domain integrity (10 checks) |
| SERVICE-ORPHAN-001 | Orphaned service detection |
| SETUP-WIZARD-DAILY-001 | Setup Wizard + Daily Actions coverage per vertical |
| STRIPE-SYNC-001 | Stripe integration verification (Price IDs in config) |
| SUCCESS-CASES-SSOT-001 | SuccessCase entity is SSOT for case studies |
| TENANT-CHECK-001 | Tenant isolation verification |
| TENANT-USER-ROLE-001 | Group members have tenant_user Drupal role |
| TEST-COVERAGE-MAP-001 | Test coverage map |
| TRANSLATION-INTEG-001 | Translation integrity (cross-page dup, NULL titles, AI fences) |
| TWIG-INCLUDE-VARS-001 | Twig include with only passes required variables (warn, 80 pre-existing) |
| TWIG-LANGPREFIX-001 | Twig hardcoded URLs without language prefix |
| THEME-SETTINGS-INTEGRITY-001 | Theme settings form-schema-consumer coherence (dead fields, PLG schema, CSS vars) |
| TWIG-SYNTAX-LINT-001 | Twig static syntax lint |
| CONTENT-SEED-INTEGRITY-001 | Metasite content seed JSON integrity (3 metasites, 6 checks each) |
| HOMEPAGE-SMOKE-TEST-001 | Homepage differentiation per metasite (homepage_id, rendered_html, meta_title_suffix) |
| RENDERED-HTML-FRESHNESS-001 | Canvas pages rendered_html completeness (detects missing pre-render) |
| SAFEGUARD-AUTO-COUNTER-001 | Meta-safeguard: conteos validators sincronizados (validate-all.sh vs docs vs CLAUDE.md) |
| VALIDATOR-COVERAGE-001 | Meta-safeguard: orphaned validator detection |
| VERTICAL-COVERAGE-001 | All 9 commercial verticals in discovery points |
| VERTICAL-CROSS-LINK-001 | Case study cross-links in VerticalLandingController |
| VIDEO-HERO-001 | Video hero asset completeness (9 verticals) |
| BRAND-TERM-VALIDATOR-001 | Obsolete brand terms in frontend (Desarrollo Rural → Local) |

## Warn Checks — 44 (no bloquean, baseline violations aceptadas)

| Rule ID | Descripcion | Notas |
|---------|-------------|-------|
| CONFIG-DB-SYNC-001 | Config YAML vs DB sync state | Requiere DB |
| CONFIG-SCHEMA-COVERAGE-001 | Config schema completeness — open mappings | 141 baseline |
| DB-EXPORT-FRESHNESS-001 | Local DB dump freshness (< 7 days, >= 1MB) | Previene pérdida lando destroy |
| I18N-DRIFT-001 | Custom translation drift .po vs DB (< 5%) | Detecta traducciones no exportadas |
| CONTENT-DRIFT-DETECTION-001 | Content seed JSON vs DB drift detection | Post-lanzamiento |
| CSP-DOMAIN-COMPLETENESS-001 | CSP external domain cross-reference | |
| DEMO-COVERAGE-001 | Demo vertical coverage (13/13 profiles) | |
| ENTITY-FIELD-DB-SYNC-001 | Entity field definitions match DB columns | Static check |
| HOOK-REQUIREMENTS-COVERAGE-001 | Module hook_requirements() coverage | Target 95% |
| HOOK-REQUIREMENTS-GAP-001 | Modules with hook_requirements gap | 11 modules |
| ICON-COMPLETENESS-001 | Icon SVG completeness cascade | |
| PHPCS-BASELINE-DECAY-001 | PHPCS baseline size within limits (warn >8.5k, fail >9.5k) | |
| UPDATE-HOOK-CATCH-RETHROW-001 | hook_update catch blocks re-throw (not return) | 60 pre-existing |
| ICON-DYNAMIC-001 | Dynamic icon refs resolve to SVGs | |
| IMAGE-WEIGHT-001 | Oversized images in theme | 62 pre-existing |
| INFRA-HEALTH-001 | Infrastructure health | Production only |
| MERGE-API-AUDIT-001 | Database API phantom methods (expressions, addFields, onDuplicate) | |
| REDIS-ACL-001 | Redis 8.0 config: ACL, sentinel, io-threads, security (14 checks) | |
| LANDING-SECTIONS-RENDERED-001 | Landing section completeness | Requires Lando |
| LIBRARY-ATTACHMENT-001 | Bundle library declaration + CSS existence | 34 pre-existing |
| PRICING-CASE-STUDY-COHERENCE-001 | Case study pricing vs controller structure | |
| SCSS-COMPILE-FRESHNESS-001 | SCSS compiled CSS freshness vs partials | Git checkout issue |
| SCSS-MULTI-ENTRYPOINT-001 | SCSS multi-entrypoint freshness (30 shared partials) | Timestamp comparison |
| SVG-CURRENTCOLOR-001 | SVG currentColor usage in img tags | |
| TWIG-INCLUDE-ONLY-001 | Twig includes use only keyword | 144 pre-existing |
| ROUTE-REFERENCE-INTEGRITY-001 | Url::fromRoute() references existing routes | 12 pre-existing |
| TRANSLATION-API-KEYS-001 | API keys IA disponibles para traduccion (ANTHROPIC_API_KEY, Key module, permisos settings.env.php) | 4 checks |
| TRANSLATION-COVERAGE-001 | Cobertura traduccion automatica (>80% por entity type/idioma, Redis queue, Supervisor worker) | 5 checks |
| TRANSLATION-QUALITY-001 | Calidad semantica traducciones (identico al original, vacios, longitud anomala, hallucination) | 5 checks multi-entity |
| VISUAL-REGRESSION-001 | Critical page structural smoke test | Requires Lando |

## Pre-commit lint-staged (9 hooks)

| Pattern | Validator(s) |
|---------|-------------|
| `**/*.php` | PHPStan Level 6 + Drupal CodeSniffer |
| `**/*.scss` | validate-compiled-assets.php |
| `docs/00_*.md` | verify-doc-integrity.sh (DOC-GUARD-001) |
| `**/*.html.twig` | validate-twig-syntax.php + validate-twig-ortografia.php |
| `**/*.js` (modules+theme) | validate-js-syntax.php |
| `**/*.libraries.yml` | validate-library-attachments.php |
| `**/*.services.yml` | phantom-args + optional-deps + circular-deps + logger-injection |
| `**/*.routing.yml` | validate-all.sh --fast |
