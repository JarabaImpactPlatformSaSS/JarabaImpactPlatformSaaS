# Aprendizaje #133: Elevacion IA — 10 GAPs: Streaming Real, MCP Server, Native Tools, Tracing, Memoria Semantica

**Fecha:** 2026-02-26
**Categoria:** Arquitectura IA / Streaming / Interoperabilidad / Memoria
**Impacto:** Critico — Completa la elevacion del stack IA a nivel 5/5 conectando streaming real, function calling nativo, protocolo MCP, distributed tracing y memoria semantica de agentes

---

## 1. Contexto

Tras la elevacion de 23 FIX items (Aprendizaje #129) y su auditoria post-implementacion (Aprendizaje #130), el stack IA alcanzo nivel 5/5 en capacidades autonomas pero tenia 10 gaps criticos de integracion avanzada:

- **Streaming** era buffered (LLM completo → chunking artificial) en vez de real (token-by-token)
- **Function calling** usaba XML en system prompt + JSON parsing manual en vez del soporte nativo de la API
- No habia **MCP server** para clientes externos (Claude Desktop, VS Code)
- No habia **distributed tracing** para correlacionar requests SSE/API
- La **memoria de agentes** era session-only sin persistencia semantica

10 GAPs (GAP-01 a GAP-10) en 3 fases completan la elevacion.

---

## 2. Patrones Clave Aprendidos

### 2.1 Streaming Real via PHP Generator + SSE (GAP-01)
- **Problema:** `CopilotStreamController` recibia toda la respuesta del LLM, luego la chunkeaba artificialmente con `splitIntoParagraphs()` y `usleep()` entre chunks.
- **Solucion:** `StreamingOrchestratorService` que extiende `CopilotOrchestratorService` y usa `ChatInput::setStreamedOutput(TRUE)`. El metodo `streamChat()` retorna un **PHP Generator** que yield-ea eventos tipados (`chunk`, `cached`, `done`, `error`). El controller consume el Generator con foreach y emite SSE events.
- **Patron:**
  1. El servicio retorna `Generator` (no array ni string) — el caller decide como consumir
  2. Buffer de 80 chars o sentence boundary para evitar tokens sueltos de 1-2 chars
  3. PII masking incremental via `maskBufferPII()` aplicado a cada chunk acumulado
  4. Fallback automatico: si `StreamingOrchestratorService` no esta disponible, se usa `handleBufferedStreaming()` (comportamiento original)
  5. Inyeccion opcional en controller via `$container->has()` (los controllers usan `create()`, no services.yml)

### 2.2 Native Function Calling via Drupal AI Module (GAP-09)
- **Problema:** `callAiApiWithTools()` inyectaba XML tool documentation en el system prompt y parseaba JSON `{"tool_call": {...}}` manualmente del texto del LLM. Fragil y dependiente del formato de respuesta.
- **Solucion:** `callAiApiWithNativeTools()` usa la API nativa del modulo Drupal AI: `ChatInput::setChatTools(ToolsInput)`. El LLM retorna `ChatMessage::getTools()` con `ToolsFunctionOutputInterface[]` que contienen nombre y argumentos parseados.
- **Patron:**
  1. `ToolRegistry::generateNativeToolsInput()` convierte las tools al formato `ToolsInput > ToolsFunctionInput > ToolsPropertyInput`
  2. El loop iterativo es identico (max 5) pero no requiere parsing de JSON
  3. Fallback automatico: si `callAiApiWithNativeTools()` falla (excepcion), cae a `callAiApiWithTools()` (text-based)
  4. Los tool results se appendean como mensajes `assistant` con tool_id
  5. Coexistencia: ambos metodos (nativo y text-based) viven en SmartBaseAgent

### 2.3 MCP Server JSON-RPC 2.0 (GAP-08)
- **Problema:** Las herramientas del SaaS (SendEmail, CreateEntity, SearchKnowledge, etc.) solo eran accesibles internamente por agentes. No habia forma de que clientes MCP externos (Claude Desktop, VS Code Copilot) las invocaran.
- **Solucion:** `McpServerController` en un unico endpoint `POST /api/v1/mcp` que despacha via `match()`:
  - `initialize` → handshake con protocolVersion y capabilities
  - `tools/list` → listado de tools con JSON Schema inputSchema
  - `tools/call` → ejecucion via ToolRegistry::execute() con PII sanitization
  - `ping` → health check
- **Patron:**
  1. Un solo endpoint POST, dispatch por metodo JSON-RPC (no endpoints separados)
  2. JSON-RPC 2.0 estricto: `jsonrpc: "2.0"`, `id`, `method`, `params`
  3. Error codes estandar: -32700 (parse), -32600 (invalid request), -32601 (method not found), -32602 (invalid params), -32603 (internal)
  4. Tool output sanitizado via AIGuardrailsService::maskOutputPII() antes de retornar
  5. Permisos: `use ai agents` + CSRF token

### 2.4 Distributed Tracing (GAP-02)
- **Problema:** No habia forma de correlacionar un request SSE del copilot con las llamadas internas a LLM, tools, cache, etc.
- **Solucion:** `TraceContextService` genera `trace_id` (UUID) por request y `span_id` por operacion. Se inyecta en `AIObservabilityService::log()` y en los SSE events.
- **Patron:**
  1. Generar trace_id una vez al inicio del request SSE
  2. Propagarlo a cada servicio involucrado (observability, cache, guardrails)
  3. Incluir trace_id en el SSE `done` event para que el frontend pueda correlacionar
  4. Los logs de observabilidad incluyen trace_id y span_id para busqueda posterior

### 2.5 Agent Long-Term Memory (GAP-07)
- **Problema:** Los agentes no tenian memoria entre conversaciones. Cada sesion empezaba de cero.
- **Solucion:** `AgentLongTermMemoryService` (ya implementado en FIX-039) se conecta con el streaming pipeline. Backed por Qdrant (semantic recall) + tabla BD (structured facts). Types: fact, preference, interaction_summary, correction.
- **Patron:**
  1. `remember()` despues de cada interaccion exitosa
  2. `recall()` en `buildSystemPrompt()` como seccion `<agent_memory>`
  3. Embeddings generados via `JarabaRagService::generateEmbedding()`
  4. Busqueda semantica en Qdrant con threshold configurable

### 2.6 Streaming-Aware PII Masking (GAP-03 + GAP-10)
- **Problema:** Los guardrails validaban input completo y output completo, pero durante streaming el texto llega por chunks — un PII puede estar partido entre 2 chunks.
- **Solucion:** Buffer acumulativo: `maskBufferPII()` aplica masking sobre el buffer completo acumulado, no sobre cada chunk individual. Asi detecta PIIs que cruzan boundaries de chunks.
- **Patron:**
  1. Acumular texto en buffer conforme llegan chunks
  2. Aplicar masking sobre buffer completo
  3. Emitir solo el delta (texto nuevo post-masking)
  4. Si un PII se detecta en medio, el chunk emitido ya incluye `[DATO PROTEGIDO]`

---

## 3. Ficheros Clave

| Fichero | GAP | Cambio |
|---------|-----|--------|
| `StreamingOrchestratorService.php` | GAP-01 | CREADO — PHP Generator streaming, buffer chunking, PII masking |
| `CopilotStreamController.php` | GAP-01 | MODIFICADO — handleRealStreaming() consume Generator |
| `copilot-chat-widget.js` | GAP-01 | MODIFICADO — cached event, smarter chunk joining |
| `jaraba_copilot_v2.services.yml` | GAP-01 | MODIFICADO — registro streaming_orchestrator |
| `ToolRegistry.php` | GAP-09 | MODIFICADO — generateNativeToolsInput() |
| `SmartBaseAgent.php` | GAP-09 | MODIFICADO — callAiApiWithNativeTools() + executeLlmCallWithTools() |
| `McpServerController.php` | GAP-08 | CREADO — JSON-RPC 2.0 MCP server |
| `jaraba_ai_agents.routing.yml` | GAP-08 | MODIFICADO — ruta POST /api/v1/mcp |

---

## 4. Decisiones de Diseno

1. **Generator vs Callback:** Se eligio PHP Generator (`yield`) sobre callbacks porque permite que el caller controle el flujo (puede parar de consumir) y es mas idiomatico en PHP 8.4.
2. **Herencia vs Composicion para Streaming:** `StreamingOrchestratorService extends CopilotOrchestratorService` para acceder a 6+ metodos protegidos de setup (modo, contexto, cache, etc.) sin duplicar codigo.
3. **Un endpoint MCP vs multiples:** Un solo `POST /api/v1/mcp` con dispatch por metodo JSON-RPC, siguiendo la especificacion MCP 2025-11-25.
4. **Native tools como complemento:** `callAiApiWithNativeTools()` coexiste con `callAiApiWithTools()` (text-based). La version nativa es preferida pero cae a text-based automaticamente si falla.
5. **Controller DI vs services.yml:** Los controllers usan `create()` con `$container->has()` para inyeccion condicional, no `@?` (que es para services.yml).

---

## 5. Verificacion

- 0 errores `php -l` en todos los ficheros PHP modificados/creados
- 0 errores YAML en todos los services.yml y routing.yml modificados
- Fallbacks automaticos verificados: streaming → buffered, native tools → text-based

---

## 6. Cross-References

- Aprendizaje #129: Elevacion IA 23 FIX items
- Aprendizaje #130: Auditoria post-implementacion IA
- Aprendizaje #131: Canvas Editor Content Hub
- Aprendizaje #132: Meta-Sitio plataformadeecosistemas.es
- Arquitectura: `docs/arquitectura/2026-02-26_arquitectura_elevacion_ia_nivel5.md`
- Directrices v81.0.0, Flujo v36.0.0, Indice v106.0.0
