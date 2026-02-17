# Flujo de Trabajo del Asistente IA (Claude)

**Fecha de creacion:** 2026-02-16
**Version:** 3.0.0

---

## 1. Inicio de Sesion

Al comenzar o reanudar una conversacion, leer en este orden:

1. **DIRECTRICES** (`docs/00_DIRECTRICES_PROYECTO.md`) — Reglas, convenciones, principios de desarrollo
2. **ARQUITECTURA** (`docs/00_DOCUMENTO_MAESTRO_ARQUITECTURA.md`) — Modulos, stack, modelo de datos
3. **INDICE** (`docs/00_INDICE_GENERAL.md`) — Estado actual, ultimos cambios, aprendizajes recientes

Esto garantiza contexto completo antes de cualquier implementacion.

---

## 2. Antes de Implementar

- **Leer ficheros de referencia:** Antes de crear o modificar un modulo, revisar modulos existentes que usen el mismo patron (ej: jaraba_verifactu como patron canonico de zero-region)
- **Plan mode para tareas complejas:** Usar plan mode cuando la tarea requiere multiples ficheros, decisiones arquitectonicas, o tiene ambiguedad
- **Verificar aprendizajes previos:** Consultar `docs/tecnicos/aprendizajes/` para evitar repetir errores documentados
- **Leer sibling agents/services:** Antes de implementar un servicio nuevo, leer al menos un servicio existente del mismo tipo (ej: MerchantCopilotAgent antes de LegalCopilotAgent, EmployabilityFeatureGateService antes de AgroConectaFeatureGateService)

---

## 3. Durante la Implementacion

Respetar las directrices del proyecto, incluyendo:

- **i18n:** `TranslatableMarkup` / `$this->t()` para strings visibles al usuario
- **SCSS:** BEM, `@use` (no `@import`), `color-mix()` (no `rgba()`), Federated Design Tokens `var(--ej-*)`
- **Zero-region:** Variables via `hook_preprocess_page()`, NO via controller render array (ZERO-REGION-001/002/003)
- **Hooks:** Hooks nativos PHP en `.module` (NO ECA YAML para logica compleja)
- **Content Entities:** Para todo dato gestionable (Field UI + Views + Access Control)
- **API REST:** Envelope `{success, data, error, message}`, prefijo `/api/v1/`
- **Seguridad:** `_permission` en rutas sensibles, HMAC webhooks, sanitizacion antes de `|raw`
- **Tests:** PHPUnit por modulo (Unit + Kernel + Functional segun aplique)
- **SCSS compilacion:** Via Docker NVM (`/user/.nvm/versions/node/v20.20.0/bin`)
- **declare(strict_types=1):** Obligatorio en todo fichero PHP nuevo
- **tenant_id:** Siempre `entity_reference` a group, NUNCA integer
- **DB indexes:** Obligatorios en `tenant_id` + campos frecuentes
- **Iconos:** SVG duotone via sistema centralizado, no emojis en codigo (PB-PREMIUM-001: `jaraba_icon()` obligatorio)
- **Modales CRUD:** `data-dialog-type="modal"` con `drupal.dialog.ajax`
- **Legal/Copilot:** Disclaimer + citas verificables obligatorias (LEGAL-RAG-001), FeatureGate en servicios con limites (LEGAL-GATE-001)
- **Body classes:** Siempre via `hook_preprocess_html()`, nunca `attributes.addClass()` en template (LEGAL-BODY-001)
- **FeatureGate:** 3 tipos de features: CUMULATIVE (count entities), MONTHLY (track usage), BINARY (plan check) (FEATUREGATE-TYPES-001)
- **Page Builder Premium:** `jaraba-block--premium` + `data-effect="fade-up"` + schema.org donde aplique (PB-PREMIUM-001)

---

## 4. Despues de Implementar

Actualizar los 3 documentos maestros + crear aprendizaje:

1. **DIRECTRICES:** Incrementar version en header + añadir entrada al changelog (seccion 14) + nuevas reglas si aplica (seccion 5.8.x)
2. **ARQUITECTURA:** Incrementar version en header + actualizar modulos en seccion 7.1 si se añadieron modulos + tabla 12.3 si se elevo un vertical + changelog al final
3. **INDICE:** Incrementar version en header + nuevo blockquote al inicio (debajo del header) + entrada en tabla Registro de Cambios
4. **Aprendizaje:** Crear fichero en `docs/tecnicos/aprendizajes/YYYY-MM-DD_nombre_descriptivo.md` con formato estandar (tabla metadata, Patron Principal, Aprendizajes Clave con Situacion/Aprendizaje/Regla)
5. **FLUJO TRABAJO:** Actualizar este documento si se descubren nuevos patrones de workflow reutilizables

---

## 5. Reglas de Oro

1. **No hardcodear:** Configuracion via Config Entities o State API, nunca valores en codigo
2. **Content Entities para todo:** Datos gestionables siempre como Content Entity con Field UI + Views
3. **`declare(strict_types=1)`:** En todo fichero PHP nuevo
4. **Tenant isolation:** `tenant_id` como entity_reference en toda entidad, filtrado obligatorio
5. **Rate limiting:** En toda operacion costosa (exports, AI calls, bulk operations)
6. **Patron zero-region:** 3 hooks obligatorios (hook_theme + hook_theme_suggestions_page_alter + hook_preprocess_page)
7. **Documentar siempre:** Toda sesion con cambios significativos genera actualizacion documental
8. **Emojis prohibidos en codigo:** Usar `jaraba_icon()` en Twig, nunca HTML entities (&#XXXXX;)

---

## 6. Patron Elevacion Vertical a Clase Mundial

Workflow reutilizable de 14 fases para elevar un vertical a clase mundial (probado con Empleabilidad, Emprendimiento, Andalucia+ei, JarabaLex, AgroConecta):

| Fase | Entregable | Ficheros clave |
|------|-----------|----------------|
| 0 | FeatureGateService + FreemiumVerticalLimit configs | `src/Service/{Vertical}FeatureGateService.php` + `config/install/*.yml` |
| 1 | UpgradeTriggerService — milestones de conversion | `UpgradeTriggerService.php` (actualizar o crear) |
| 2 | CopilotBridgeService — puente copilot + vertical | `src/Service/{Vertical}CopilotBridgeService.php` |
| 3 | hook_preprocess_html — body classes del vertical | `{modulo}.module` |
| 4 | Page template zero-region + Copilot FAB | `page--{vertical}.html.twig` |
| 5 | SCSS compliance (BEM, color-mix, var(--ej-*)) | `scss/` + `package.json` |
| 6 | Design token config vertical | `config/install/ecosistema_jaraba_core.design_token_config.vertical_{id}.yml` |
| 7 | Email sequences MJML (5-6 templates) | `mjml/{vertical}/seq_*.mjml` |
| 8 | CrossVerticalBridgeService | `src/Service/{Vertical}CrossVerticalBridgeService.php` |
| 9 | JourneyProgressionService — reglas proactivas FAB | `src/Service/{Vertical}JourneyProgressionService.php` |
| 10 | HealthScoreService — 5 dimensiones + 8 KPIs | `src/Service/{Vertical}HealthScoreService.php` |
| 11 | ExperimentService — A/B testing eventos | `src/Service/{Vertical}ExperimentService.php` |
| 12 | Avatar navigation + funnel analytics | `AvatarNavigationService.php` + `config/install/*.funnel_definition.*.yml` |
| 13 | QA integral — PHP lint + audit agents paralelos | Todos los ficheros de las 12 fases anteriores |

### Checklist rapido por fase

- Cada servicio nuevo: `declare(strict_types=1)`, constructor DI readonly, canal de log dedicado
- Cada FeatureGateService: `check()` retorna `FeatureGateResult`, `fire()` para eventos denied, 3 tipos features (CUMULATIVE/MONTHLY/BINARY)
- Cada CopilotAgent: DEBE implementar todos los metodos de `AgentInterface` (execute, getAvailableActions, getAgentId, getLabel, getDescription)
- Cada servicio: registrar en `{modulo}.services.yml` con argumentos que coincidan con el constructor
- QA final: PHP lint en todos los ficheros, verificar interfaces completas, verificar service registration

### Estrategia de paralelizacion (PARALLEL-ELEV-001)

Al ejecutar elevacion vertical con agentes paralelos, agrupar por independencia de ficheros:

| Grupo | Fases | Ficheros tocados |
|-------|-------|-----------------|
| A | 0 (solo) | FeatureGateService.php + config/install/*.yml |
| B | 1-2 | UpgradeTriggerService.php + CopilotBridgeService.php |
| C | 3-4 | .module + page--{vertical}.html.twig |
| D | 5-6 | scss/*.scss + config/install/design_token*.yml |
| E | 7-8 | mjml/*.mjml + CrossVerticalBridgeService.php |
| F | 9-10-11 | Journey + Health + Experiment services |
| G | 12 | AvatarNavigationService.php + config/install/funnel*.yml |
| H | PB templates | templates/blocks/verticals/{vertical}/*.html.twig + config/install/pb*.yml |

Ficheros compartidos (`services.yml`, `.module`, `.install`) se editan en el hilo principal tras completar agentes.

---

## 7. Patron Elevacion Page Builder Premium (PB-PREMIUM-001 + PB-BATCH-001)

Workflow para elevar templates PB de un vertical a premium:

1. **Dividir en lotes:** 3-4 templates por agente (ej: hero+features+stats+content / testimonials+pricing+faq+cta / gallery+map+social_proof)
2. **Asignar YML al lote mas ligero:** El agente con menos templates actualiza los 11 YML configs
3. **Patron premium por template:**
   - `jaraba-block jaraba-block--premium` en `<section>`
   - `data-effect="fade-up"` en section
   - Staggered `data-delay="{{ loop.index0 * 100 }}"` en items iterados
   - `jaraba_icon('category', 'name', { variant, size, color })` — NUNCA emojis HTML entities
   - Schema.org donde aplique: FAQ → `<script type="application/ld+json">` FAQPage, map → LocalBusiness `itemscope`, social_proof → AggregateRating
   - Funcionalidades especificas: lightbox (`data-lightbox`), counters (`data-counter`), pricing toggle, countdown timer, star ratings
4. **Patron YML config:**
   - `is_premium: true`
   - `animation: fade-up`
   - `plans_required: [starter, professional, enterprise]` (corregir duplicados)
   - `fields_schema.properties` con array schemas para campos de listas

---

## 8. Registro de Cambios

| Fecha | Version | Descripcion |
|-------|---------|-------------|
| 2026-02-17 | **3.0.0** | Seccion 3 ampliada (FEATUREGATE-TYPES-001, PB-PREMIUM-001). Seccion 5 nueva regla 8 (emojis prohibidos). Seccion 6 actualizada (AgroConecta anadido a verticales probados, checklist FeatureGate 3 tipos, estrategia paralelizacion PARALLEL-ELEV-001, paths de ficheros corregidos). Nueva seccion 7 Patron Elevacion Page Builder Premium (PB-PREMIUM-001 + PB-BATCH-001). Seccion 4.2 ampliada (tabla 12.3 en Arquitectura). Aprendido durante Plan Elevacion AgroConecta Clase Mundial v1 |
| 2026-02-16 | **2.0.0** | Seccion 6 Patron Elevacion Vertical (14 fases + checklist). Seccion 2 ampliada (leer sibling agents). Seccion 3 ampliada (LEGAL-RAG-001, LEGAL-GATE-001, LEGAL-BODY-001). Seccion 4 ampliada (actualizacion flujo trabajo). Seccion 7 Registro de Cambios. Aprendido durante Plan Elevacion JarabaLex v1 |
| 2026-02-16 | **1.0.0** | Creacion inicial: 5 secciones (Inicio, Antes, Durante, Despues, Reglas de Oro). Aprendido durante documentacion zero-region pattern |
