# Aprendizaje #127: Remediacion Integral del Stack IA — 28 Fixes en 3 Fases

**Fecha:** 2026-02-26
**Categoria:** Arquitectura IA / DevOps / Seguridad
**Impacto:** Alto — Estandariza, asegura y optimiza toda la infraestructura IA del SaaS

---

## 1. Contexto

El stack IA del SaaS habia acumulado deuda tecnica: reglas de identidad duplicadas en 14+ archivos, guardrails incompletos (solo PII de formato US), metricas simuladas con `rand()`, streaming falso con `usleep()`, pricing hardcodeado, observabilidad desconectada, y verticales con nombres inconsistentes. Un plan de remediacion integral (`20260226-Plan_Remediacion_Integral_IA_SaaS_v1_Claude.md`) priorizo 28 fixes en 3 fases.

## 2. Patrones Clave Aprendidos

### 2.1 Centralizar Logica Duplicada en Clase Estatica
- **Problema:** La regla de identidad IA estaba copiada manualmente en 14+ archivos. Cualquier cambio requeria editar todos.
- **Solucion:** Crear `AIIdentityRule::apply()` como clase estatica y llamarla desde cada punto de inyeccion.
- **Patron:** Cuando una regla de negocio aparece en 3+ puntos, extraer a una clase con metodo estatico `apply()` o servicio singleton.

### 2.2 Config YAML para Valores que Cambian sin Code Deploy
- **Problema:** Los modelos IA, pricing y thresholds de complejidad estaban hardcodeados en `ModelRouterService`.
- **Solucion:** Mover a `jaraba_ai_agents.model_routing.yml` con schema en `jaraba_ai_agents.schema.yml`.
- **Patron:** Deep-merge en constructor: `array_merge($this->defaults[$tier], $configOverride[$tier])`. El codigo mantiene defaults sensibles como fallback.

### 2.3 Regex Bilingues para Plataformas Hispanohablantes
- **Problema:** `assessComplexity()` solo detectaba keywords en ingles (`analyze`, `creative`, `translate`), ignorando prompts en espanol.
- **Solucion:** Regex con alternativas bilingues: `/\b(analyze|analiza|compara|evalua|sintetiza)\b/iu`.
- **Patron:** Flag `/iu` (case-insensitive + Unicode) obligatorio para patrones que procesen texto multilingue con acentos.

### 2.4 Metricas Reales vs Metricas Simuladas
- **Problema:** `AIOpsService` usaba `rand(40, 85)` para CPU, memoria, latencia — completamente inutil para monitoring.
- **Solucion:** Leer de `/proc/stat`, `/proc/meminfo`, `disk_free_space()`, queries a `ai_telemetry` y `watchdog`.
- **Patron:** Siempre envolver en try/catch con defaults sensibles. En entornos sin `/proc` (Windows, containers restringidos), los defaults permiten que el servicio siga funcionando.

### 2.5 Observabilidad en TODOS los Puntos de Ejecucion IA
- **Problema:** `BrandVoiceTrainerService` y `WorkflowExecutorService` tenian `observability` inyectado pero nunca llamaban `->log()`.
- **Solucion:** Anadir `$this->observability->log([...])` en cada ejecucion exitosa Y fallida.
- **Patron:** En workflows, loguear en AMBOS paths: success (`on_success`) y abort (`on_failure`). Estimacion de tokens: `ceil(mb_strlen($text) / 4)`.

### 2.6 Feedback Widget: Alinear Frontend y Backend
- **Problema:** El JS enviaba `{was_helpful: 0|1, source}` pero el PHP esperaba `{rating: 'up'|'down', message_id, user_message, assistant_response}`.
- **Solucion:** Reescribir el JS para recorrer `state.messages` hacia atras, encontrar el user message asociado, y enviar todos los campos esperados.
- **Patron:** Antes de implementar un endpoint de feedback, verificar el contrato de ambos lados (JS y PHP) y documentar el schema compartido.

### 2.7 Streaming Semantico vs Chunks Arbitrarios
- **Problema:** `splitIntoChunks()` cortaba cada 80 caracteres, rompiendo palabras y enviando fragmentos sin sentido.
- **Solucion:** `splitIntoParagraphs()` con `preg_split('/\n{2,}/')` — divide por doble salto de linea (unidad semantica).
- **Patron:** El chunking de streaming DEBE respetar unidades semanticas (parrafos, oraciones). Nunca cortar por longitud arbitraria.

### 2.8 PII por Mercado Geografico
- **Problema:** Los guardrails solo detectaban SSN y telefono US. DNI, NIE, IBAN ES, NIF/CIF pasaban sin filtrar.
- **Solucion:** Anadir patrones espanoles a `checkPII()` y IBAN ES a `BLOCKED_PATTERNS`.
- **Patron:** Cada mercado geografico requiere un set de patrones PII. Al expandir a nuevos paises, anadir patrones ANTES de lanzar.

### 2.9 Canonical Verticals con Alias Normalization
- **Problema:** Verticales con nombres inconsistentes: `comercio_conecta` vs `comercioconecta`, `content_hub` vs `jaraba_content_hub`.
- **Solucion:** Constante `BaseAgent::VERTICALS` como fuente de verdad. Normalizacion de aliases legacy en cada servicio.
- **Patron:** Definir nombres canonicos UNA VEZ y normalizar en el borde (input) en lugar de aceptar variantes en el core.

### 2.10 Agent Generations con Annotations
- **Problema:** No habia forma de saber que agentes estaban deprecados, activos o en migracion.
- **Solucion:** `@deprecated` para Gen 0 (con referencia al reemplazo), `@note Gen 1` para activos legacy, Gen 2 = SmartBaseAgent subclasses.
- **Patron:** Las PHPDoc annotations son el lugar correcto para documentar el lifecycle de clases. Mas duradero que comentarios o docs externos.

## 3. Metricas de Ejecucion

- **28 FIX items** ejecutados en 3 fases (P0 criticos, P1 importantes, P2 mejoras)
- **55 ficheros** modificados
- **+3678 / -236 lineas**
- **0 errores** de sintaxis PHP (`php -l`) ni YAML (`yaml.safe_load()`)
- **1 commit**: `9dc314ee feat(ai): implement full AI remediation plan — 28 fixes across 3 phases`

## 4. Reglas Derivadas

| ID | Descripcion | Prioridad |
|----|-------------|-----------|
| AI-GUARDRAILS-PII-001 | PII espanol obligatorio en guardrails | P0 |
| AI-OBSERVABILITY-001 | Log de observabilidad en todo punto de ejecucion IA | P0 |
| VERTICAL-CANONICAL-001 | 10 nombres canonicos de vertical con normalizacion | P0 |
| MODEL-ROUTING-CONFIG-001 | Modelos y pricing en YAML config (no hardcodeado) | P1 |
| AI-STREAMING-001 | SSE con MIME correcto, chunks semanticos, sin usleep | P1 |

## 5. Referencias

- Plan de remediacion: `docs/tecnicos/20260226-Plan_Remediacion_Integral_IA_SaaS_v1_Claude.md`
- AIIdentityRule: `ecosistema_jaraba_core/src/AI/AIIdentityRule.php`
- AIGuardrailsService: `ecosistema_jaraba_core/src/Service/AIGuardrailsService.php`
- ModelRouterService: `jaraba_ai_agents/src/Service/ModelRouterService.php`
- Model routing config: `jaraba_ai_agents/config/install/jaraba_ai_agents.model_routing.yml`
- AIOpsService: `ecosistema_jaraba_core/src/Service/AIOpsService.php`
- CopilotStreamController: `jaraba_copilot_v2/src/Controller/CopilotStreamController.php`
- BaseAgent (canonical verticals): `jaraba_ai_agents/src/Agent/BaseAgent.php`
