# Plan de Implementacion: Claude Code Development Pipeline
# Basado en Doc 178 — Corregido y Alineado con el Estado Real del Proyecto

**Fecha:** 2026-02-28
**Version:** 1.0.0
**Estado:** IMPLEMENTADO — Todas las fases completadas (2026-02-28)
**Dependencias:** CLAUDE.md v1.0.0 (ya creado), directrices v102, arquitectura v91
**Horas estimadas:** 40-60h (vs 180-240h del Doc 178 original, reducido por eliminar lo incorrecto y lo que ya existe)

---

## Indice de Navegacion (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Contexto y Justificacion](#2-contexto-y-justificacion)
   - 2.1 [Problema que Resuelve](#21-problema-que-resuelve)
   - 2.2 [Analisis del Doc 178 Original](#22-analisis-del-doc-178-original)
   - 2.3 [Lo que YA Existe y NO Se Debe Recrear](#23-lo-que-ya-existe-y-no-se-debe-recrear)
3. [CLAUDE.md — Memoria Persistente (COMPLETADO)](#3-claudemd--memoria-persistente-completado)
   - 3.1 [Estructura y Contenido](#31-estructura-y-contenido)
   - 3.2 [Diferencias con la Propuesta del Doc 178](#32-diferencias-con-la-propuesta-del-doc-178)
   - 3.3 [Mantenimiento y Actualizacion](#33-mantenimiento-y-actualizacion)
   - 3.4 [Uso Dual: Claude Code + Claude Chat](#34-uso-dual-claude-code--claude-chat)
4. [Fase 1: Subagentes Especializados](#4-fase-1-subagentes-especializados)
   - 4.1 [Subagente Reviewer (Writer/Reviewer Pattern)](#41-subagente-reviewer-writerreviewer-pattern)
   - 4.2 [Subagente Tester](#42-subagente-tester)
   - 4.3 [Subagente Security Auditor](#43-subagente-security-auditor)
   - 4.4 [Estructura de Archivos](#44-estructura-de-archivos)
5. [Fase 2: Hooks — Quality Gates Automaticos](#5-fase-2-hooks--quality-gates-automaticos)
   - 5.1 [Hook Pre-Tool-Use (Proteccion de Archivos Criticos)](#51-hook-pre-tool-use-proteccion-de-archivos-criticos)
   - 5.2 [Hook Post-Tool-Use (Lint Automatico)](#52-hook-post-tool-use-lint-automatico)
   - 5.3 [Integracion con Pre-Commit Existente](#53-integracion-con-pre-commit-existente)
6. [Fase 3: Slash Commands — Workflows Repetibles](#6-fase-3-slash-commands--workflows-repetibles)
   - 6.1 [Catalogo de Commands Recomendados](#61-catalogo-de-commands-recomendados)
   - 6.2 [/fix-issue — Resolucion Autonoma de Issues](#62-fix-issue--resolucion-autonoma-de-issues)
   - 6.3 [/audit-wcag — Auditoria de Accesibilidad](#63-audit-wcag--auditoria-de-accesibilidad)
   - 6.4 [/create-entity — Scaffolding de Entidad](#64-create-entity--scaffolding-de-entidad)
   - 6.5 [/verify-runtime — Verificacion Post-Implementacion](#65-verify-runtime--verificacion-post-implementacion)
7. [Fase 4: MCP Servers (Evaluacion Selectiva)](#7-fase-4-mcp-servers-evaluacion-selectiva)
   - 7.1 [Stripe MCP (Recomendado)](#71-stripe-mcp-recomendado)
   - 7.2 [GitHub MCP (Ya Cubierto por gh CLI)](#72-github-mcp-ya-cubierto-por-gh-cli)
   - 7.3 [Sentry MCP (Evaluacion Futura)](#73-sentry-mcp-evaluacion-futura)
   - 7.4 [MCPs Descartados](#74-mcps-descartados)
8. [Fase 5: Documento "Estado del SaaS" para Claude Chat](#8-fase-5-documento-estado-del-saas-para-claude-chat)
   - 8.1 [Proposito y Estructura](#81-proposito-y-estructura)
   - 8.2 [Diferencia con CLAUDE.md](#82-diferencia-con-claudemd)
   - 8.3 [Actualizacion y Mantenimiento](#83-actualizacion-y-mantenimiento)
9. [Tabla de Correspondencia Tecnica](#9-tabla-de-correspondencia-tecnica)
10. [Tabla de Cumplimiento de Directrices](#10-tabla-de-cumplimiento-de-directrices)
11. [Roadmap y Priorizacion](#11-roadmap-y-priorizacion)
12. [Verificacion Post-Implementacion](#12-verificacion-post-implementacion)

---

## 1. Resumen Ejecutivo

Este plan define la implementacion REAL del pipeline de desarrollo Claude Code para Jaraba Impact Platform, basado en el analisis critico del Documento 178 original. El Doc 178 contenia 27 discrepancias factuales con el estado real del proyecto (PHP 8.3 vs 8.4, 5 vs 10 verticales, `--jaraba-*` vs `--ej-*`, React vs Vanilla JS, etc.) y duplicaba capacidades existentes (CI/CD, pre-commit hooks, PII detection, MCP server).

Este plan corrige todas las discrepancias, elimina lo que ya existe, y se enfoca en los componentes de VALOR REAL:
- **CLAUDE.md** (COMPLETADO): 236 lineas con las 140+ reglas reales del proyecto
- **Subagentes** (8-12h): Writer/Reviewer pattern para eliminar auto-sesgo
- **Hooks** (4-8h): Pre-tool-use para proteger archivos criticos
- **Slash Commands** (12-16h): 4 workflows repetibles de alto impacto
- **MCP Servers** (8-12h): Solo Stripe MCP (los demas ya cubiertos por herramientas existentes)
- **Documento Estado SaaS** (4-8h): Para Claude Chat como Project Knowledge

**Inversion total:** 40-60h (vs 180-240h propuestas en Doc 178)
**ROI:** Mayor que el Doc 178 porque NO introduce errores ni contradice directrices existentes

---

## 2. Contexto y Justificacion

### 2.1 Problema que Resuelve

El ecosistema Jaraba cuenta con:
- **178+ documentos tecnicos** (>500,000 palabras)
- **80+ modulos custom** en `web/modules/custom/`
- **140+ reglas nombradas** (TENANT-BRIDGE-001 hasta LEGAL-LANDING-UNIFIED-001)
- **4 master docs** protegidos por pre-commit hook (DOC-GUARD-001)
- **152 aprendizajes** documentados en `docs/tecnicos/aprendizajes/`
- **89 golden rules** en FLUJO_TRABAJO_CLAUDE

Un agente Claude sin contexto apropiado genera codigo que:
- Usa versiones de PHP/MariaDB incorrectas
- Propone tecnologias no presentes (React, Key module, ECA)
- Ignora patrones obligatorios (PremiumEntityFormBase, TenantBridgeService)
- Hardcodea URLs, colores y prefijos CSS incorrectos
- Crea archivos SCSS sin importarlos en main.scss

El CLAUDE.md creado resuelve esto proporcionando el contexto correcto desde el primer instante de cada sesion.

### 2.2 Analisis del Doc 178 Original

El Doc 178 (`docs/tecnicos/20260228b-178_Claude_Code_Pipeline_Completo_v1_Claude.md`) fue evaluado desde 15 perspectivas de experto senior. Puntuaciones:

| Dimension | Puntuacion | Motivo |
|---|---|---|
| Precision factual | 2/10 | 27 discrepancias criticas con la realidad |
| Valor estrategico | 7/10 | Vision de pipeline agente-orquestado correcta |
| Ejecutabilidad tal cual | 1/10 | Implementarlo romperia el proyecto |
| Valor rescatable | 6/10 | CLAUDE.md, Writer/Reviewer, hooks, Stripe MCP |
| Alineacion con directrices | 2/10 | Ignora/contradice 15+ directrices |
| ROI (corregido) | 5/10 | Si se reescribe, es alcanzable |

**27 Discrepancias Criticas Identificadas:**

| # | Doc 178 Dice | Realidad | Severidad |
|---|---|---|---|
| 1 | PHP 8.3 | PHP 8.4 | CRITICA |
| 2 | MariaDB 11.2 | MariaDB 10.11 | CRITICA |
| 3 | 5 verticales | 10 verticales (VERTICAL-CANONICAL-001) | CRITICA |
| 4 | CSS `--jaraba-*` | `--ej-*` (3,488 ocurrencias SCSS) | CRITICA |
| 5 | Theme `jaraba_theme` | `ecosistema_jaraba_theme` (base theme: false) | CRITICA |
| 6 | React frontend | Vanilla JS + Drupal.behaviors | CRITICA |
| 7 | Colores #1B4F72 | #233D63, #FF8C42, #00A9A5 (ICON-COLOR-001) | ALTA |
| 8 | Drupal Key module | getenv() via settings.secrets.php (SECRET-MGMT-001) | ALTA |
| 9 | claude-sonnet-4-5 default | 3 tiers routing (MODEL-ROUTING-CONFIG-001) | MEDIA |
| 10 | GroupContextInterface | TenantContextService + TenantBridgeService | ALTA |
| 11 | css/presets/{industria}.css | No existen como archivos separados | ALTA |
| 12 | docker-compose.yml | No existe; usa .lando.yml exclusivamente | MEDIA |
| 13 | web/core/phpunit.xml.dist | phpunit.xml en raiz del proyecto | MEDIA |
| 14 | NUNCA raw SQL | Permitido en .install (TRANSLATABLE-FIELDDATA-001) | MEDIA |
| 15 | Fuentes Montserrat+Roboto | Outfit por defecto (configurable desde UI) | BAJA |
| 16 | ECA module activo | Sin evidencia de uso real de ECA | ALTA |
| 17 | ContentEntityForm | PremiumEntityFormBase (PREMIUM-FORMS-PATTERN-001) | ALTA |
| 18 | drush mcp-tools:serve | McpServerController custom en jaraba_ai_agents | ALTA |
| 19 | Key module para secretos | settings.secrets.php + getenv() | ALTA |
| 20 | claude plugin install | No existe plugin system para Claude Code | MEDIA |
| 21 | entity revisions para todo | Muchas entities NO tienen revisions | BAJA |
| 22 | \Drupal::service() NUNCA | Aceptado en .module y hooks procedurales | MEDIA |
| 23 | Inyeccion AI obligatoria | Optional services con @? (KERNEL-OPTIONAL-AI-001) | MEDIA |
| 24 | Audit logging nuevo | AIObservabilityService + TraceContext YA existen | ALTA |
| 25 | PII detection nuevo | AIGuardrailsService::checkPII() YA implementado | ALTA |
| 26 | Pre-commit PHPCS+PHPStan | CI ya ejecuta ambos; pre-commit solo DOC-GUARD-001 | MEDIA |
| 27 | SOC2/ISO 27001 claims | Sin certificacion actual; claims prematuros | BAJA |

### 2.3 Lo que YA Existe y NO Se Debe Recrear

| Capacidad | Estado Actual | Ubicacion |
|---|---|---|
| CI/CD pipeline completo | 8 workflow files GitHub Actions | .github/workflows/ |
| Pre-commit hook | DOC-GUARD-001 activo | .git/hooks/pre-commit |
| PHPCS en CI | Drupal + DrupalPractice standards | ci.yml job: lint |
| PHPStan Level 6 | + baseline 587KB + security rules | phpstan.neon + phpstan-security.neon |
| PHPUnit Unit + Kernel | 4 suites, coverage 80% | phpunit.xml |
| PII detection | AIGuardrailsService (ES/US patterns) | ecosistema_jaraba_core |
| MCP Server | McpServerController (JSON-RPC 2.0) | jaraba_ai_agents |
| Audit logging | AIObservabilityService + TraceContextService | jaraba_ai_agents |
| Secret management | settings.secrets.php + getenv() | config/deploy/ |
| 16 workflow docs | Pattern guides para desarrollo | .agent/workflows/ |
| SCSS orphan detection | check-scss-orphans.js | theme/scripts/ |
| Slide-panel system | Global singleton JS behavior | theme/js/slide-panel.js |
| Theme settings UI | 13 vertical tabs, 70+ opciones | ecosistema_jaraba_theme.theme |

---

## 3. CLAUDE.md — Memoria Persistente (COMPLETADO)

### 3.1 Estructura y Contenido

El CLAUDE.md ha sido creado en la raiz del repositorio con 236 lineas cubriendo:

| Seccion | Lineas | Contenido Clave |
|---|---|---|
| Identidad del Proyecto | 1-13 | Stack correcto (PHP 8.4, MariaDB 10.11, etc.) |
| 10 Verticales Canonicos | 15-18 | VERTICAL-CANONICAL-001 con los 10 nombres |
| Arquitectura Multi-Tenant | 20-34 | TENANT-BRIDGE-001, servicios clave, patrones |
| Convenciones de Codigo | 36-108 | PHP, entities, DB, JS, Twig — 30+ reglas |
| Theming | 110-148 | --ej-*, SCSS, iconos, CSS-VAR-ALL-COLORS-001 |
| Frontend Zero Region | 150-176 | clean_content, parciales, slide-panel |
| Seguridad | 178-190 | SECRET-MGMT-001, CSRF, XSS, acceso |
| IA Stack | 192-216 | 11 agentes Gen 2, servicios clave, reglas |
| GrapesJS | 218-226 | Page Builder, CANVAS-ARTICLE-001 |
| Testing | 228-240 | phpunit.xml, CI, reglas de mocking PHP 8.4 |
| Documentacion | 242-252 | DOC-GUARD-001, versiones actuales |
| Runtime Verify | 254-260 | RUNTIME-VERIFY-001, verificacion multi-capa |

### 3.2 Diferencias con la Propuesta del Doc 178

| Aspecto | Doc 178 CLAUDE.md | CLAUDE.md Real |
|---|---|---|
| Lineas | ~240 (con errores) | 236 (verificado) |
| PHP version | 8.3 | 8.4 |
| Verticales | 5 | 10 |
| CSS prefix | --jaraba-* | --ej-* |
| Frontend | React | Vanilla JS + Drupal.behaviors |
| Theme | jaraba_theme | ecosistema_jaraba_theme |
| Colores | #1B4F72, #E67E22 | #233D63, #FF8C42, #00A9A5 |
| Secretos | Key module | settings.secrets.php + getenv() |
| Multi-tenant | GroupContextInterface | TenantBridgeService + TenantContextService |
| Forms | ContentEntityForm | PremiumEntityFormBase |
| IA | claude-sonnet-4-5 generico | 3 tiers, 11 agentes Gen 2, 25+ servicios |
| GrapesJS | No mencionado | Seccion completa |
| Zero Region | No mencionado | Seccion completa |
| Slide-panel | No mencionado | SLIDE-PANEL-RENDER-001 |
| SCSS orphans | No mencionado | check-scss-orphans.js |
| Testing PHP 8.4 | No mencionado | MOCK-DYNPROP-001, TRAIT-CONST-001 |

### 3.3 Mantenimiento y Actualizacion

El CLAUDE.md DEBE actualizarse cuando:
1. Se crea una nueva regla nombrada (XXX-001)
2. Cambia una version de tecnologia (PHP, MariaDB, etc.)
3. Se anade o elimina un vertical
4. Cambian versiones de master docs
5. Se descubre un nuevo aprendizaje critico

**Patron de actualizacion:** Edit incremental (NO Write completo). Cada actualizacion incluye cambio de version y fecha en la cabecera.

**Responsable:** Quien crea la regla/aprendizaje actualiza el CLAUDE.md en el mismo commit (analogo a COMMIT-SCOPE-001 para master docs, pero el CLAUDE.md puede ir junto al codigo).

### 3.4 Uso Dual: Claude Code + Claude Chat

El CLAUDE.md sirve para DOS propositos:

**Claude Code (CLI):**
- Se lee automaticamente al inicio de cada sesion
- Proporciona contexto inmediato sin necesidad de explorar el codebase
- Previene errores factuales en codigo generado

**Claude Chat (web/app):**
- Subir como Project Knowledge en un "Project" de Claude
- Cualquier especificacion tecnica generada en Claude Chat estara alineada con la realidad
- Previene documentos como el Doc 178 con 27 discrepancias

**Para Claude Chat se recomienda crear adicionalmente** el documento "Estado del SaaS" (Fase 5) que cubre aspectos de negocio/producto no presentes en CLAUDE.md.

---

## 4. Fase 1: Subagentes Especializados

**Horas estimadas:** 8-12h
**Prioridad:** P1
**Ubicacion:** `.claude/agents/`

### 4.1 Subagente Reviewer (Writer/Reviewer Pattern)

**Proposito:** Eliminar el sesgo de auto-revision. Una instancia de Claude escribe codigo; otra independiente lo revisa sin conocer el proceso de creacion.

**Archivo:** `.claude/agents/reviewer.md`

**Criterios de revision adaptados al proyecto real:**

1. **Seguridad (BLOQUEANTE)**
   - Data leak cross-tenant: TODA query filtra por tenant (TENANT-001)
   - XSS: Drupal.checkPlain() para innerHTML, {% trans %} sin |raw (AUDIT-SEC-003, INNERHTML-XSS-001)
   - Secrets: getenv() via settings.secrets.php, NUNCA hardcoded (SECRET-MGMT-001)
   - CSRF: _csrf_request_header_token en API routes (CSRF-API-001)
   - Access: (int) === (int) en ownership checks (ACCESS-STRICT-001)

2. **Arquitectura (BLOQUEANTE)**
   - Forms extienden PremiumEntityFormBase (PREMIUM-FORMS-PATTERN-001)
   - Multi-tenant via TenantBridgeService (TENANT-BRIDGE-001)
   - Entity forms tienen AccessControlHandler (AUDIT-CONS-001)
   - URLs via Url::fromRoute() (ROUTE-LANGPREFIX-001)
   - CSS colores via var(--ej-*, fallback) (CSS-VAR-ALL-COLORS-001)

3. **Calidad (WARNING)**
   - SCSS compilado tras edicion (SCSS-COMPILE-VERIFY-001)
   - Parciales SCSS importados en main.scss (check-scss-orphans.js)
   - Textos traducibles: {% trans %} en Twig, Drupal.t() en JS, $this->t() en PHP
   - Zero region: clean_content, body classes via hook_preprocess_html()
   - Slide-panel: renderPlain() + $form['#action'] (SLIDE-PANEL-RENDER-001)

4. **Performance (ADVISORY)**
   - Cache tags con context 'group' para multi-tenant
   - entity_reference vs integer segun FK rules (ENTITY-FK-001)
   - drupalSettings via preprocess en zero-region pages (ZERO-REGION-003)

### 4.2 Subagente Tester

**Archivo:** `.claude/agents/tester.md`

**Adaptaciones al proyecto real:**

- PHPUnit config en raiz: `phpunit.xml` (NO web/core/phpunit.xml.dist)
- 4 suites: Unit, Kernel, Functional, PromptRegression
- DB test: `mysql://drupal:drupal@127.0.0.1:3306/drupal_jaraba_test`
- KERNEL-TEST-DEPS-001: Listar TODOS los modulos en $modules explicitamente
- MOCK-DYNPROP-001: PHP 8.4 prohibe dynamic properties en mocks
- KERNEL-SYNTH-001: Servicios de modulos no cargados como synthetic
- KERNEL-TIME-001: Tolerancia +/-1 segundo en timestamps
- TEST-CACHE-001: Entity mocks con getCacheContexts/Tags/MaxAge
- Multi-tenant: SIEMPRE crear 2 grupos y verificar aislamiento

### 4.3 Subagente Security Auditor

**Archivo:** `.claude/agents/security-auditor.md`

**Adaptaciones al proyecto real:**

- Secretos: Buscar getenv() pattern, NO Key module
- PII: AIGuardrailsService ya detecta DNI/NIE/IBAN ES/NIF/CIF/+34
- SQL: Raw SQL permitido SOLO en .install (TRANSLATABLE-FIELDDATA-001)
- phpstan-security.neon ya banea: eval(), exec(), shell_exec(), system(), Connection::query()
- CSRF: _csrf_request_header_token (NO _csrf_token) para API routes
- Tenant isolation: DefaultEntityAccessControlHandler con checkTenantIsolation()

### 4.4 Estructura de Archivos

```
.claude/
  agents/
    reviewer.md          # Code reviewer con criterios Jaraba
    tester.md             # Test generator con reglas PHP 8.4
    security-auditor.md   # Security auditor con stack real
```

---

## 5. Fase 2: Hooks — Quality Gates Automaticos

**Horas estimadas:** 4-8h
**Prioridad:** P1
**Ubicacion:** `.claude/hooks/` y `.claude/settings.json`

### 5.1 Hook Pre-Tool-Use (Proteccion de Archivos Criticos)

**Archivo:** `.claude/hooks/pre-tool-use.sh`

**Proposito:** Prevenir modificaciones accidentales a archivos criticos por parte del agente Claude Code. Este hook se ejecuta ANTES de que Claude Code modifique un archivo.

**Logica:**

1. **Archivos PROTEGIDOS (bloquea edicion):**
   - `composer.lock` — solo modificable via `composer update`
   - `.lando.yml` — configuracion de entorno local
   - `web/sites/default/settings.php` — settings de Drupal
   - `config/deploy/settings.secrets.php` — secretos
   - `phpstan-baseline.neon` — baseline de 587KB

2. **Archivos ADVERTENCIA (permite pero avisa):**
   - `docs/00_*.md` — master docs (DOC-GUARD-001 los protege via pre-commit)
   - `docs/07_*.md` — doc adicional protegido
   - `.github/workflows/*` — pipelines CI/CD

3. **Directorios PERMITIDOS (sin restriccion):**
   - `web/modules/custom/`
   - `web/themes/custom/`
   - `docs/` (excepto master docs)
   - `.claude/`
   - `scripts/`

**Variables de entorno disponibles en hooks Claude Code:**
- `$CLAUDE_TOOL_INPUT_PATH` — ruta del archivo que se va a modificar
- `$CLAUDE_TOOL_NAME` — nombre de la herramienta (Edit, Write, etc.)

### 5.2 Hook Post-Tool-Use (Lint Automatico)

**Archivo:** `.claude/hooks/post-edit-lint.sh`

**Proposito:** Ejecutar validaciones ligeras inmediatamente despues de que Claude edita un archivo, sin esperar al commit.

**Logica:**
1. Si el archivo es `.php`: ejecutar `phpcs --standard=Drupal,DrupalPractice` solo sobre ESE archivo
2. Si el archivo es `.scss`: verificar que tiene `@use` en entry point correspondiente
3. Si el archivo es `.js`: ejecutar ESLint solo sobre ESE archivo
4. Si el archivo es `.twig`: verificar que no contiene `|raw` sin contexto seguro

**Nota:** Este hook es ligero (< 3 segundos) para no interrumpir el flujo de trabajo. Las validaciones completas se ejecutan en CI.

### 5.3 Integracion con Pre-Commit Existente

El pre-commit hook existente (DOC-GUARD-001) en `.git/hooks/pre-commit` NO debe sobreescribirse. Los hooks de Claude Code son COMPLEMENTARIOS:

| Hook | Tipo | Trigger | Proposito |
|---|---|---|---|
| `.git/hooks/pre-commit` | Git hook | `git commit` | DOC-GUARD-001 line count |
| `.claude/hooks/pre-tool-use.sh` | Claude hook | Edit/Write tool | Proteger archivos criticos |
| `.claude/hooks/post-edit-lint.sh` | Claude hook | Despues de Edit/Write | Lint inmediato |

**settings.json necesario:**

```json
{
  "hooks": {
    "PreToolUse": [
      {
        "matcher": "Edit|Write",
        "hook": ".claude/hooks/pre-tool-use.sh"
      }
    ],
    "PostToolUse": [
      {
        "matcher": "Edit|Write",
        "hook": ".claude/hooks/post-edit-lint.sh"
      }
    ]
  }
}
```

**Importante:** Este `settings.json` se fusiona con el `settings.local.json` existente. No sobreescribir el local que tiene los permisos de Bash y WebFetch.

---

## 6. Fase 3: Slash Commands — Workflows Repetibles

**Horas estimadas:** 12-16h
**Prioridad:** P2
**Ubicacion:** `.claude/commands/`

### 6.1 Catalogo de Commands Recomendados

Se seleccionan 4 commands de alto impacto (vs 8 propuestos en Doc 178). Los descartados duplican funcionalidad existente o tienen bajo ROI:

| Command | Proposito | Adoptado | Motivo |
|---|---|---|---|
| /fix-issue | Resolver issue GitHub/Sentry | SI | Alto impacto, workflow frecuente |
| /audit-wcag | Auditoria WCAG 2.1 AA | SI | Obligatorio para accesibilidad |
| /create-entity | Scaffolding ContentEntity | SI | Garantiza cumplimiento de 10+ reglas |
| /verify-runtime | Verificacion RUNTIME-VERIFY-001 | SI | Cierra gap "codigo vs experiencia" |
| /deploy-tenant | Deploy configuracion tenant | NO | Workflow infrecuente, manual suficiente |
| /create-vertical | Scaffolding de vertical | NO | Requiere planificacion extensa, no automatizable |
| /create-eca | Regla ECA documentada | NO | ECA no se usa en el proyecto |
| /stripe-webhook | Handler webhook Stripe | NO | Ya existe patron documentado |

### 6.2 /fix-issue — Resolucion Autonoma de Issues

**Archivo:** `.claude/commands/fix-issue.md`

**Descripcion:** Resuelve un issue de GitHub analizando el contexto, implementando el fix, generando tests y creando un PR.

**Flujo:**

1. **Analizar:** Leer issue via `gh issue view $ISSUE_ID`, identificar archivos afectados
2. **Branch:** `git checkout -b fix/$ISSUE_ID`
3. **Implementar:** Fix minimo siguiendo CLAUDE.md, sin refactorizar innecesariamente
4. **Tests:** Generar test que reproduce el bug (rojo) y pasa con el fix (verde)
5. **Verificar:** RUNTIME-VERIFY-001 — CSS compilado, rutas, drupalSettings
6. **Review:** Invocar subagente reviewer
7. **PR:** Commit + push + `gh pr create` con descripcion detallada

**Adaptaciones al proyecto:**
- Usar `phpunit.xml` de raiz (NO web/core/phpunit.xml.dist)
- Tests Kernel con MariaDB 10.11
- SCSS: recompilar si se toco algun .scss
- Commit separado para docs si se modifica un master doc (COMMIT-SCOPE-001)

### 6.3 /audit-wcag — Auditoria de Accesibilidad

**Archivo:** `.claude/commands/audit-wcag.md`

**Descripcion:** Ejecuta auditoria WCAG 2.1 AA sobre una URL o template, reportando issues y sugiriendo fixes.

**Checklist adaptado al proyecto:**
- Contraste: ratio >= 4.5:1 texto, >= 3:1 UI (usando colores --ej-*)
- Teclado: Tab, Enter, Escape, Arrow keys funcionales
- Focus: outline >= 2px con contraste suficiente
- Screen reader: landmarks, headings jerarquicos, aria-live
- Responsive: funcional 320px a 2560px
- Motion: prefers-reduced-motion respetado (animaciones con .no-js fallback)
- Touch: targets minimo 44x44px
- i18n: {% trans %} en todo texto visible

### 6.4 /create-entity — Scaffolding de Entidad

**Archivo:** `.claude/commands/create-entity.md`

**Descripcion:** Genera una ContentEntity completa cumpliendo TODAS las reglas del proyecto. Es el command de mayor valor porque automatiza el cumplimiento de 10+ reglas simultaneamente.

**Genera:**

1. **Entity class** con:
   - `@ContentEntityType` annotation con `access`, `form`, `views_data`, `route_provider`, `list_builder`
   - `field_ui_base_route` apuntando a settings form
   - `admin_permission`
   - `EntityOwnerInterface + EntityChangedInterface` si aplica
   - `baseFieldDefinitions()` con indexes (AUDIT-PERF-001)
   - `tenant_id` como entity_reference (ENTITY-FK-001)

2. **Form class** extendiendo `PremiumEntityFormBase`:
   - `getSectionDefinitions()` y `getFormIcon()`
   - Sin fieldsets/details

3. **AccessControlHandler**:
   - tenant_id verification para update/delete (TENANT-ISOLATION-ACCESS-001)
   - (int) === (int) comparisons (ACCESS-STRICT-001)

4. **Settings form** (FormBase minimo para Field UI)

5. **Routing:**
   - Admin routes bajo /admin/content/ o /admin/structure/
   - links.menu.yml con parent correcto

6. **Module file:**
   - `template_preprocess_{type}()` (ENTITY-PREPROCESS-001)

7. **Template Twig** con {% trans %} y sin logica de negocio

### 6.5 /verify-runtime — Verificacion Post-Implementacion

**Archivo:** `.claude/commands/verify-runtime.md`

**Descripcion:** Ejecuta RUNTIME-VERIFY-001 de forma sistematica tras completar un feature. Verifica la cadena completa: PHP -> Twig -> SCSS -> CSS compilado -> JS -> drupalSettings -> DOM.

**Checklist automatizado:**

1. **CSS compilado:** Timestamp de .css > timestamp de .scss mas reciente
2. **SCSS orphans:** Ejecutar `node scripts/check-scss-orphans.js`
3. **Rutas:** Verificar que las rutas del .routing.yml responden 200 (via `lando drush router:match`)
4. **drupalSettings:** Verificar que las URLs de API usan Url::fromRoute() (grep por hardcoded paths)
5. **Templates:** Verificar que los data-* selectores en JS matchean los del HTML
6. **Traducciones:** grep por textos sin {% trans %} en templates Twig nuevos/modificados
7. **Colores:** grep por hex hardcoded en SCSS nuevos (debe ser var(--ej-*))
8. **Body classes:** Verificar que se inyectan via hook_preprocess_html() (no en template)
9. **Iconos:** Verificar uso de jaraba_icon() con colores de paleta (ICON-COLOR-001)
10. **Slide-panel:** Si hay forms frontend, verificar renderPlain() pattern

---

## 7. Fase 4: MCP Servers (Evaluacion Selectiva)

**Horas estimadas:** 8-12h (solo Stripe)
**Prioridad:** P2-P3

### 7.1 Stripe MCP (Recomendado)

**Justificacion:** El proyecto usa Stripe Connect con destination charges. Un MCP Server permite interactuar con datos de test para desarrollo y debugging sin salir de Claude Code.

**Configuracion:**

```json
{
  "name": "stripe-connect",
  "transport": "stdio",
  "command": "npx",
  "args": ["-y", "@stripe/mcp", "--tools=all", "--api-key=$STRIPE_SECRET_KEY"],
  "safety": {
    "confirm_write_operations": true,
    "environment": "test"
  }
}
```

**Reglas de uso:**
- SIEMPRE test mode keys en desarrollo (SECRET-MGMT-001)
- NUNCA loguear numeros de tarjeta completos
- Webhook handlers DEBEN ser idempotentes con hash_equals() (AUDIT-SEC-001)
- API keys via getenv(), NUNCA en configuracion MCP versionada

### 7.2 GitHub MCP (Ya Cubierto por gh CLI)

**Evaluacion:** Claude Code ya tiene acceso completo a `gh` CLI (issues, PRs, workflows, API). El GitHub MCP Server anade poco valor incremental.

**Recomendacion:** POSPONER. Si el equipo crece y se necesita automatizacion de PRs mas sofisticada, reconsiderar.

### 7.3 Sentry MCP (Evaluacion Futura)

**Evaluacion:** Util si se implementa Sentry en produccion. Actualmente el proyecto usa logging propio via AIObservabilityService. Sin Sentry activo, este MCP no tiene proposito.

**Recomendacion:** POSPONER hasta que Sentry este configurado en produccion.

### 7.4 MCPs Descartados

| MCP | Motivo de Descarte |
|---|---|
| Drupal MCP (`drush mcp-tools:serve`) | No existe. El MCP propio del proyecto es McpServerController en jaraba_ai_agents |
| Google Stitch MCP | Servicio no maduro. El prototipado se hace directamente en SCSS/Twig |
| Semgrep MCP | PHPStan Level 6 + security rules ya cubren el analisis estatico |

---

## 8. Fase 5: Documento "Estado del SaaS" para Claude Chat

**Horas estimadas:** 4-8h
**Prioridad:** P2

### 8.1 Proposito y Estructura

Crear un documento complementario al CLAUDE.md orientado a **negocio y producto** para subir como Project Knowledge en Claude Chat. Esto garantiza que cualquier especificacion futura generada en Claude Chat este alineada con el estado real.

**Estructura propuesta:**

1. **Vision y Filosofia**: "Sin Humo", multi-vertical SaaS, impacto social
2. **Mapa de Verticales**: 10 verticales con estado (activo/beta/planificado), features clave, modulos
3. **Inventario de Modulos**: 80+ modulos con proposito y estado
4. **Stack Tecnologico**: Versiones exactas, servicios, infraestructura
5. **Arquitectura Multi-Tenant**: Patron Group Module, cascade de theming, aislamiento
6. **Sistema de IA**: 11 agentes Gen 2, servicios, tiers, herramientas
7. **Frontend UX Patterns**: Zero region, slide-panel, command bar, theming configurable
8. **SEO/GEO**: Schema.org patterns, OG/Twitter, hreflang, RSS
9. **Infraestructura CI/CD**: 8 workflows, deploy, security scan
10. **Metricas de Complejidad**: LOC, entities, services, routes, tests
11. **Lo que Existe vs Lo que Falta**: Mapa honest de completitud

### 8.2 Diferencia con CLAUDE.md

| Aspecto | CLAUDE.md | Estado del SaaS |
|---|---|---|
| Audiencia | Claude Code (agente de desarrollo) | Claude Chat (analisis y planificacion) |
| Tono | Imperativo ("DEBE", "NUNCA") | Descriptivo ("El sistema usa...", "Existen...") |
| Foco | Reglas de codigo y patrones | Negocio, producto, features, estado |
| Longitud | ~240 lineas (conciso) | ~500-800 lineas (descriptivo) |
| Ubicacion | Raiz del repo (CLAUDE.md) | Claude Chat Project Knowledge |

### 8.3 Actualizacion y Mantenimiento

El documento "Estado del SaaS" se actualiza:
- Cuando se completa un vertical o feature mayor
- Cuando cambia el stack tecnologico
- Mensualmente como revision periodica
- Cuando se genera una nueva especificacion que cambia el estado del SaaS

---

## 9. Tabla de Correspondencia Tecnica

| Componente Pipeline | Doc 178 Original | Este Plan (Corregido) | Cambio |
|---|---|---|---|
| CLAUDE.md | 240 lineas con 27 errores | 236 lineas verificadas | Reescrito completamente |
| Skills (7) | Theming, ECA, Stripe, Multi-tenant, SEPE, AI, Blueprint | NO como skills separados | Absorbidos en CLAUDE.md (mas eficiente) |
| Subagentes (3) | Reviewer, Tester, Security | Reviewer, Tester, Security | Adaptados al proyecto real |
| Hooks (5) | Pre-commit, Post-commit, Pre-push, Notification, Pre-tool-use | Pre-tool-use, Post-tool-use | Reducidos a 2 (los demas ya existen o son innecesarios) |
| MCP (6) | Drupal, Stitch, Stripe, GitHub, Sentry, Semgrep | Solo Stripe | 5 descartados (ya cubiertos o no disponibles) |
| Commands (8) | deploy-tenant, create-vertical, audit-wcag, fix-issue, generate-api, create-eca, stripe-webhook, tenant-theme | fix-issue, audit-wcag, create-entity, verify-runtime | 4 high-impact vs 8 dispersos |
| Plugin jaraba-dev | npm package distribuible | NO | Equipo de 1 persona, sin ROI |
| Gobernanza OAuth 2.1 | Framework completo | NO (premature) | Implementar cuando haya equipo y staging |
| Horas estimadas | 180-240h | 40-60h | -75% sin perder valor |

---

## 10. Tabla de Cumplimiento de Directrices

| Directriz | Cumplimiento en Este Plan |
|---|---|
| **DOC-GUARD-001** | CLAUDE.md no es master doc (no 00_*.md). No requiere proteccion especial pero si mantenimiento |
| **COMMIT-SCOPE-001** | CLAUDE.md puede ir junto al codigo (no es master doc). Subagentes/hooks en commit separado |
| **CSS-VAR-ALL-COLORS-001** | CLAUDE.md documenta la regla. Reviewer subagent la verifica (P0) |
| **SCSS-COMPILE-VERIFY-001** | /verify-runtime command la ejecuta sistematicamente |
| **ROUTE-LANGPREFIX-001** | CLAUDE.md la documenta. Reviewer la verifica como BLOQUEANTE |
| **PREMIUM-FORMS-PATTERN-001** | /create-entity command genera forms con PremiumEntityFormBase automaticamente |
| **TENANT-BRIDGE-001** | CLAUDE.md la documenta como primera regla de multi-tenant |
| **SECRET-MGMT-001** | CLAUDE.md documenta el patron correcto (getenv, NO Key module) |
| **ICON-COLOR-001** | CLAUDE.md documenta colores reales. /verify-runtime los verifica |
| **ICON-CONVENTION-001** | CLAUDE.md documenta jaraba_icon() con signatura correcta |
| **ICON-DUOTONE-001** | CLAUDE.md documenta variante duotone por defecto |
| **SLIDE-PANEL-RENDER-001** | CLAUDE.md documenta renderPlain() pattern. Reviewer lo verifica |
| **ZERO-REGION-001/002/003** | CLAUDE.md tiene seccion completa. /create-entity genera templates compatibles |
| **ENTITY-PREPROCESS-001** | /create-entity genera preprocess hook automaticamente |
| **AUDIT-CONS-001** | /create-entity genera AccessControlHandler automaticamente |
| **TRANSLATABLE-FIELDDATA-001** | CLAUDE.md documenta la regla. Security auditor la verifica |
| **QUERY-CHAIN-001** | CLAUDE.md documenta. Reviewer lo detecta como BLOQUEANTE |
| **DATETIME-ARITHMETIC-001** | CLAUDE.md documenta. Reviewer lo detecta como BLOQUEANTE |
| **RUNTIME-VERIFY-001** | /verify-runtime command dedicado |
| **VERTICAL-CANONICAL-001** | CLAUDE.md lista los 10 verticales correctos |
| **MODEL-ROUTING-CONFIG-001** | CLAUDE.md documenta 3 tiers (no modelo unico) |
| **AGENT-GEN2-PATTERN-001** | CLAUDE.md documenta SmartBaseAgent + doExecute() |
| **SERVICE-CALL-CONTRACT-001** | CLAUDE.md documenta. Reviewer lo verifica |
| **CANVAS-ARTICLE-001** | CLAUDE.md documenta reutilizacion GrapesJS via library dependency |
| **FIELD-UI-SETTINGS-TAB-001** | /create-entity genera settings form automaticamente |
| **KERNEL-TEST-DEPS-001** | Tester subagent genera $modules explicitamente |
| **MOCK-DYNPROP-001** | Tester subagent usa clases anonimas con typed properties |
| **CI-KERNEL-001** | CLAUDE.md documenta MariaDB 10.11 service container |
| **I18N** | CLAUDE.md documenta {% trans %} obligatorio. Reviewer lo verifica |
| **SCSS-ENTRY-CONSOLIDATION-001** | CLAUDE.md documenta. /verify-runtime lo verifica |
| **CONTROLLER-READONLY-001** | CLAUDE.md documenta restriccion PHP 8.4 |
| **LEGAL-LANDING-UNIFIED-001** | CLAUDE.md documenta patron 301 redirect |
| **HELP-CENTER-SEED-001** | CLAUDE.md documenta tenant_id=NULL pattern |
| **UNIFIED-SEARCH-001** | CLAUDE.md documenta hasDefinition() + Url::fromRoute() |

---

## 11. Roadmap y Priorizacion

### Fase 0: COMPLETADA
| Entregable | Estado | Ubicacion |
|---|---|---|
| CLAUDE.md real (236 lineas, verificado) | COMPLETADO | `/CLAUDE.md` |

### Fase 1: COMPLETADA
| Entregable | Estado | Ubicacion |
|---|---|---|
| Subagente Reviewer | COMPLETADO | `.claude/agents/reviewer/agent.md` |
| Subagente Tester | COMPLETADO | `.claude/agents/tester/agent.md` |
| Subagente Security Auditor | COMPLETADO | `.claude/agents/security-auditor/agent.md` |
| Hook pre-tool-use (proteccion archivos) | COMPLETADO | `.claude/hooks/pre-tool-use.sh` |
| Hook post-edit-lint (lint automatico) | COMPLETADO | `.claude/hooks/post-edit-lint.sh` |
| settings.json con hooks config | COMPLETADO | `.claude/settings.json` |

### Fase 2: COMPLETADA
| Entregable | Estado | Ubicacion |
|---|---|---|
| /fix-issue command | COMPLETADO | `.claude/commands/fix-issue.md` |
| /create-entity command | COMPLETADO | `.claude/commands/create-entity.md` |
| /verify-runtime command | COMPLETADO | `.claude/commands/verify-runtime.md` |
| /audit-wcag command | COMPLETADO | `.claude/commands/audit-wcag.md` |

### Fase 3: COMPLETADA
| Entregable | Estado | Ubicacion |
|---|---|---|
| Stripe MCP config | COMPLETADO | `.mcp.json` |
| Documento Estado del SaaS | COMPLETADO | `docs/tecnicos/2026-02-28_Estado_SaaS_Claude_Chat_v1.md` |

### Total: Todas las fases implementadas

---

## 12. Verificacion Post-Implementacion

### Para cada fase, verificar:

**Fase 0 (CLAUDE.md):**
- [x] Archivo existe en raiz del repositorio
- [x] Claude Code lo lee automaticamente al iniciar sesion (verificado)
- [x] Todas las versiones de tecnologia son correctas (PHP 8.4, MariaDB 10.11)
- [x] Los 10 verticales estan listados
- [x] CSS prefix --ej-* documentado (NO --jaraba-*)
- [x] Colores de marca correctos: #233D63, #FF8C42, #00A9A5

**Fase 1 (Subagentes + Hooks):**
- [x] `.claude/agents/reviewer/agent.md` existe y usa 20+ reglas del proyecto real
- [x] `.claude/agents/tester/agent.md` existe y referencia phpunit.xml de raiz
- [x] `.claude/agents/security-auditor/agent.md` existe y referencia SECRET-MGMT-001
- [x] `.claude/hooks/pre-tool-use.sh` protege 5 archivos criticos, avisa en 3 mas
- [x] `.claude/hooks/post-edit-lint.sh` verifica PHP, SCSS, JS, Twig post-edicion
- [x] `.claude/settings.json` configurado con PreToolUse y PostToolUse hooks

**Fase 2 (Slash Commands):**
- [x] `/fix-issue` — 8 pasos: analizar, branch, investigar, implementar, tests, verify, commit, PR
- [x] `/create-entity` — genera 9 archivos: entity, form, access, list builder, settings, routing, menu, task, preprocess
- [x] `/verify-runtime` — ejecuta 12 checks de RUNTIME-VERIFY-001
- [x] `/audit-wcag` — 10 secciones de auditoria WCAG 2.1 AA adaptado al proyecto

**Fase 3 (MCP + Estado SaaS):**
- [x] Stripe MCP configurado en `.mcp.json` (test mode via env var)
- [x] Documento Estado del SaaS creado (793 lineas, 17 secciones)
- [ ] Pendiente: subir a Claude Chat como Project Knowledge (accion manual del usuario)

### Test de Regresion del CLAUDE.md:

Despues de crear el CLAUDE.md, ejecutar una sesion Claude Code fresca y pedir:
1. "Crea una ContentEntity llamada `jaraba_test_entity`" — verificar que usa PremiumEntityFormBase, --ej-*, tenant_id como entity_reference
2. "Anade un color al SCSS" — verificar que usa var(--ej-*, fallback)
3. "Cual es la version de PHP?" — debe responder 8.4, no 8.3
4. "Cuantos verticales tiene la plataforma?" — debe responder 10

---

**Fin del documento de implementacion**

Version: 2.0.0 | Fecha: 2026-02-28 | Estado: IMPLEMENTADO
Basado en evaluacion critica del Doc 178 desde 15 perspectivas de experto senior
Alineado con directrices v102, arquitectura v91, flujo v55, 152 aprendizajes, 89 golden rules

### Archivos Creados en la Implementacion (15 archivos)

| # | Archivo | Proposito | Lineas |
|---|---------|-----------|--------|
| 1 | `CLAUDE.md` | Memoria persistente Claude Code | 236 |
| 2 | `docs/tecnicos/2026-02-28_Estado_SaaS_Claude_Chat_v1.md` | Project Knowledge para Claude Chat | 793 |
| 3 | `docs/implementacion/2026-02-28_Plan_Implementacion_Claude_Code_Pipeline_v1.md` | Este plan | 760+ |
| 4 | `.claude/agents/reviewer/agent.md` | Subagente de revision de codigo | ~150 |
| 5 | `.claude/agents/tester/agent.md` | Subagente de generacion de tests | ~170 |
| 6 | `.claude/agents/security-auditor/agent.md` | Subagente de auditoria de seguridad | ~190 |
| 7 | `.claude/hooks/pre-tool-use.sh` | Proteccion de archivos criticos | ~70 |
| 8 | `.claude/hooks/post-edit-lint.sh` | Lint automatico post-edicion | ~130 |
| 9 | `.claude/settings.json` | Configuracion de hooks Claude Code | ~20 |
| 10 | `.claude/commands/fix-issue.md` | Slash command: resolver issues GitHub | ~110 |
| 11 | `.claude/commands/create-entity.md` | Slash command: scaffolding ContentEntity | ~250 |
| 12 | `.claude/commands/verify-runtime.md` | Slash command: verificacion RUNTIME-VERIFY-001 | ~200 |
| 13 | `.claude/commands/audit-wcag.md` | Slash command: auditoria WCAG 2.1 AA | ~210 |
| 14 | `.mcp.json` | Configuracion MCP Server (Stripe) | ~10 |
