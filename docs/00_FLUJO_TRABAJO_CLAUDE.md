# Flujo de Trabajo del Asistente IA (Claude)

**Fecha de creacion:** 2026-02-18
**Ultima actualizacion:** 2026-02-27
**Version:** 46.0.0 (Navegacion Transversal Ecosistema — Banda Footer JSON + Auth Visibility + Schema.org Dinamico)

---
## 1. Inicio de Sesion

Al comenzar o reanudar una conversacion, leer en este orden:

1. **DIRECTRICES** (`docs/00_DIRECTRICES_PROYECTO.md`) — Reglas, convenciones, principios de desarrollo
2. **ARQUITECTURA** (`docs/00_DOCUMENTO_MAESTRO_ARQUITECTURA.md`) — Modulos, stack, modelo de datos
3. **INDICE** (`docs/00_INDICE_GENERAL.md`) — Estado actual, ultimos cambios, aprendizajes recientes

Esto garantiza contexto completo antes de cualquier implementacion.

---

## 1b. Protección de Documentos Maestros (DOC-GUARD-001)

> **⚠️ P0 — NUNCA reescribir documentos maestros completos.**

| Regla | Descripción |
|-------|-------------|
| **DOC-GUARD-001** | NUNCA usar Write/cat/heredoc para reemplazar un documento maestro (`00_*.md`). SIEMPRE usar Edit para modificar secciones específicas. |
| **COMMIT-SCOPE-001** | Commits de documentos maestros SEPARADOS de commits de código. Máximo 1 doc maestro por commit. |
| **DOC-LINECOUNT-001** | Verificar que el número de líneas NO disminuye >10% antes de commit. |

**Contexto:** El 18-feb-2026 (commit `e4a80c1f`), un commit de 361 archivos reemplazó los 4 documentos maestros con stubs de 29-67 líneas, destruyendo 7.000 líneas de documentación acumulada. Estas reglas previenen que vuelva a ocurrir.

---

## 2. Antes de Implementar

- **Leer ficheros de referencia:** Antes de crear o modificar un modulo, revisar modulos existentes que usen el mismo patron (ej: jaraba_verifactu como patron canonico de zero-region)
- **Plan mode para tareas complejas:** Usar plan mode cuando la tarea requiere multiples ficheros, decisiones arquitectonicas, o tiene ambiguedad
- **Verificar aprendizajes previos:** Consultar `docs/tecnicos/aprendizajes/` para evitar repetir errores documentados
- **Leer sibling agents/services:** Antes de implementar un servicio nuevo, leer al menos un servicio existente del mismo tipo (ej: MerchantCopilotAgent antes de LegalCopilotAgent, EmployabilityFeatureGateService antes de AgroConectaFeatureGateService)

---

## 3. Durante la Implementacion

...
- **Mocking PHPUnit 11:** 
  - Evitar `createMock(\stdClass::class)`. Usar interfaces explícitas.
  - Para clases `final`, inyectar como `object` en el constructor y usar `if (!interface_exists(...))` en los tests para definir interfaces de mock temporales.
  - Asegurar que los mocks de entidades implementen metadatos de caché (`getCacheContexts`, etc.) si se usan en AccessHandlers.
- **XML Robustness:** Usar XPath para aserciones en lugar de `str_contains`. Canonicalizar en documentos limpios antes de verificar firmas.
- **CI/CD Config:**
  - **Trivy:** Las claves `skip-dirs`/`skip-files` van SIEMPRE bajo el bloque `scan:` en trivy.yaml. Verificar en los logs que el conteo de archivos escaneados es coherente.
  - **Deploy:** Todo smoke test con dependencia de secrets de URL debe tener fallback SSH/Drush. Nunca `exit 1` sin intentar alternativas.
- **Page Builder Templates (Config Entities YAML):**
  - Todo YAML de PageTemplate DEBE incluir `preview_image` con ruta al PNG. Convención: `{id_con_guiones}.png`.
  - Los `preview_data` verticales DEBEN incluir arrays ricos (features[], testimonials[], faqs[], stats[]) con 3+ items del dominio específico, no placeholders genéricos.
  - Al editar templates masivamente, validar YAML con Python (`yaml.safe_load()`) ya que Symfony YAML no está disponible desde CLI sin autoloader.
  - Crear update hook para resyncronizar configs en la BD activa tras modificar YAMLs en `config/install/`.
- **Drupal 10+ Entity Updates:**
  - `applyUpdates()` fue eliminado. Usar `installFieldStorageDefinition()` / `updateFieldStorageDefinition()` explícitamente.
  - Verificar tipo de campo instalado con `getFieldStorageDefinition()` antes de intentar actualizarlo.
- **Seguridad API (CSRF):**
  - Rutas API consumidas via `fetch()` DEBEN usar `_csrf_request_header_token: 'TRUE'` (NO `_csrf_token`).
  - El JS DEBE obtener token de `Drupal.url('session/token')`, cachearlo, y enviarlo como header `X-CSRF-Token`.
  - Cachear el token CSRF en una promise de modulo para evitar multiples peticiones: `var _csrfTokenPromise = null; function getCsrfToken() { ... }` (CSRF-JS-CACHE-001).
  - Siempre incluir `?_format=json` en la URL cuando la ruta requiere `_format: 'json'`.
- **Seguridad JS (XSS innerHTML):**
  - Todo dato de respuesta API insertado via `innerHTML` DEBE pasar por `Drupal.checkPlain()`. Valores numericos por `parseInt()`. Solo HTML generado client-side post-sanitizacion (INNERHTML-XSS-001).
- **Seguridad Twig (XSS):**
  - Contenido de usuario: `|safe_html` (NUNCA `|raw`). Solo `|raw` para JSON-LD schema y HTML auto-generado.
  - Escapar datos de usuario en HTML de emails con `Html::escape()`.
  - Cast `(string)` en TranslatableMarkup al asignar a variables de render array.
- **Seguridad API (Whitelist):**
  - Todo endpoint que acepte campos dinamicos del request para actualizar una entidad DEBE definir constante `ALLOWED_FIELDS` y filtrar antes de `$entity->set()` (API-WHITELIST-001).
- **Freemium (Coherencia de Tiers):**
  - Al modificar limites Free o Starter, verificar coherencia: Starter > Free para cada metrica. Los `upgrade_message` DEBEN reflejar valores reales del Starter (FREEMIUM-TIER-001).
- **Remediacion Multi-IA:**
  - Protocolo: CLASIFICAR (REVERT/FIX/KEEP) → REVERT (git checkout) → FIX (ediciones manuales) → VERIFICAR → DOCUMENTAR.
  - Verificar roles especificos (nunca solo `authenticated`), URLs via `Url::fromRoute()` (nunca hardcoded).
  - PWA: AMBOS meta tags (apple-mobile-web-app-capable + mobile-web-app-capable) siempre presentes.
- **Entidades Append-Only:**
  - Las entidades de registro inmutable (predicciones, logs, metricas) NO tienen form handlers de edicion/eliminacion.
  - El `AccessControlHandler` DEBE denegar `update` y `delete`. Solo `create` y `view`.
  - No definir `form` handlers en la anotacion `@ContentEntityType` excepto `default` (para admin UI).
  - Ejemplo: `SeasonalChurnPrediction` — se crean via servicio, nunca se editan.
- **Config Seeding via Update Hook:**
  - Los YAMLs de `config/install/` almacenan datos como arrays PHP nativos, no como strings JSON.
  - El `update_hook` DEBE leer el YAML con `Yaml::decode()`, codificar campos complejos con `json_encode()`, y crear la entidad via `Entity::create()->save()`.
  - Verificar existencia antes de crear para evitar duplicados en re-ejecuciones: `$storage->loadByProperties(['field' => $value])`.
  - Los campos `string_long` que almacenan JSON DEBEN tener getters que retornen `json_decode($value, TRUE)` con fallback a `[]`.
- **Page Builder Preview Image Audit:**
  - Los 4 escenarios de verificación: (1) Biblioteca de Plantillas, (2) Canvas Editor panel, (3) Canvas inserción, (4) Página pública.
  - Todo vertical NUEVO debe generar sus PNGs de preview en `images/previews/` ANTES de desplegar. Convención: `{vertical}-{tipo}.png`.
  - El `getPreviewImage()` en `PageTemplate.php` auto-detecta por convención: `id_con_underscores` → `id-con-guiones.png`.
  - Usar paleta de colores consistente por vertical alineada con design tokens `--ej-{vertical}-*`.
  - Verificar en browser que no hay duplicados en el BlockManager GrapesJS (bloques estáticos vs dinámicos API).
  - JarabaLex: bloques definidos en `grapesjs-jaraba-legal-blocks.js` (GrapesJS-only, sin config entities).
- **Booking API & Entity Field Mapping:**
  - Los campos en `$storage->create([...])` DEBEN coincidir exactamente con `baseFieldDefinitions()`. Nunca usar nombres de conveniencia del JSON request como nombres de campo de entidad.
  - Mapeo tipico: request `datetime` → entidad `booking_date`, request `service_id` → entidad `offering_id`, request `client_id` → entidad `uid` (owner).
  - Rellenar campos requeridos de la entidad que no vienen en el request: `client_name`, `client_email` desde el user cargado, `price` desde el offering.
  - Para `meeting_url` con Jitsi: guardar la entidad primero para obtener el ID, luego set+save con la URL.
- **State Machine con Status Granulares:**
  - Si la entidad define `cancelled_client` / `cancelled_provider` (no `cancelled` generico), el controlador DEBE mapear el valor generico de la API al valor correcto segun el rol del usuario.
  - Patrón: `if ($newStatus === 'cancelled') { $newStatus = $isProvider ? 'cancelled_provider' : 'cancelled_client'; }`
  - Los hooks (`hook_entity_update`) DEBEN usar `str_starts_with($status, 'cancelled_')` para detectar cancelaciones.
  - Regla de negocio: solo providers pueden `confirmed`, `completed`, `no_show`. Validar en el controlador.
- **Cron Idempotency con Flags:**
  - Toda accion cron que envie emails DEBE: (1) filtrar por flag `->condition($flag, 0)` en la query, (2) marcar `$entity->set($flag, TRUE)` tras enviar, (3) `$entity->save()`.
  - Los campos de flag (`reminder_24h_sent`, `reminder_1h_sent`) son `boolean` con `setDefaultValue(FALSE)` en `baseFieldDefinitions()`.
  - Cada ventana temporal tiene su propio flag: no reutilizar un flag para multiples ventanas.
- **Owner Pattern en Content Entities:**
  - Las entidades con `EntityOwnerTrait` usan `uid` como campo owner (no `client_id` ni `user_id`).
  - Leer owner: `$entity->getOwnerId()` (no `$entity->get('client_id')->target_id`).
  - En hooks: `$entity->getOwnerId()` para obtener el uid del propietario, `$entity->get('provider_id')->target_id` para la referencia.
- **Cifrado Server-Side AES-256-GCM:**
  - Usar `openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag)` con IV de 12 bytes aleatorio por mensaje.
  - Almacenar IV + tag + ciphertext como MEDIUMBLOB en custom schema table. Separar con concatenacion: `$iv . $tag . $ciphertext`.
  - La clave se deriva con `sodium_crypto_pwhash()` (Argon2id) desde env var `JARABA_PMK`. Cache de clave derivada por tenant_id en memoria (property del servicio).
  - NUNCA almacenar la clave derivada en BD ni config. El servicio `TenantKeyService` la genera en runtime.
  - Encapsular datos descifrados en DTOs `readonly` (`SecureMessageDTO`). El DTO se descarta tras la respuesta HTTP.
- **Custom Schema Tables con DTOs:**
  - Cuando una ContentEntity no es viable (MEDIUMBLOB, VARBINARY, alto volumen de escrituras), usar `hook_schema()` con tablas custom + DTO readonly.
  - El DTO encapsula las filas de la tabla: `SecureMessageDTO::fromRow($row)` (factory) y `->toArray()` (serialization).
  - El servicio (`MessageService`) maneja CRUD via `\Drupal::database()` directamente (no Entity API).
  - Mantener las relaciones con entidades via foreign keys (conversation_id → secure_conversation.id).
- **WebSocket Auth Middleware:**
  - El `AuthMiddleware` en `onOpen()` extrae JWT del query string (`?token=xxx`) o session cookie del header HTTP.
  - Valida el token, resuelve `user_id` y `tenant_id`, los adjunta al objeto `ConnectionInterface` via atributos custom.
  - Conexiones sin auth valido se cierran con `$conn->close()` y codigo 4401.
  - El `ConnectionManager` mantiene indices `SplObjectStorage` para busqueda rapida por user_id y tenant_id.
- **Cursor-Based Pagination:**
  - Para endpoints con alto volumen (mensajes), usar cursor en lugar de offset: `?before_id=123&limit=50`.
  - La query usa `WHERE id < :before_id ORDER BY id DESC LIMIT :limit`. Mas eficiente que OFFSET en tablas grandes.
  - El response incluye `meta.has_more` y `meta.oldest_id` para la siguiente pagina.
- **Optional DI con @? (OPTIONAL-SERVICE-DI-001):**
  - Servicios que dependen de modulos opcionales (ej. `AttachmentBridgeService` depende de `jaraba_vault`) usan `@?` en `services.yml`.
  - El constructor acepta `?ServiceInterface $service = NULL` y degrada gracefully con fallback local.
  - Patron: `$this->vaultService?->store($data) ?? $this->storeLocally($data)`.
  - **Critico para Kernel tests:** Cuando un modulo A referencia un servicio de modulo B con `@moduloB.service`, y un Kernel test solo habilita modulo A, el test falla con `ServiceNotFoundException`. Usar `@?moduloB.service` y nullable constructor resuelve esto.
  - Ejemplo real: `jaraba_agroconecta_core` tenia 7 hard refs a `@ecosistema_jaraba_core.agroconecta_feature_gate` → cambiados a `@?`.
- **Kernel Test Module Dependencies (KERNEL-TEST-DEPS-001):**
  - `KernelTestBase::$modules` NO resuelve dependencias automaticamente — TODOS los modulos requeridos deben listarse explicitamente.
  - Si la entidad usa campos `datetime` → anadir `datetime` al array `$modules`.
  - Si la entidad referencia `taxonomy_term` → anadir `taxonomy`, `text`, `field` Y llamar `$this->installEntitySchema('taxonomy_term')` ANTES del schema de la entidad.
  - Si la entidad usa campos `list_string` → anadir `options`. Campos `text_long` → anadir `text`.
  - Los schemas de entidades referenciadas DEBEN instalarse antes que la entidad que las referencia.
- **Field UI Settings Tab Obligatorio (FIELD-UI-SETTINGS-TAB-001):**
  - Toda entidad con `field_ui_base_route` DEBE tener un default local task tab en `links.task.yml`.
  - Sin este tab, Field UI no puede renderizar "Administrar campos" ni "Administrar visualizacion de formulario".
  - El tab debe tener `route_name` y `base_route` apuntando a la misma ruta settings:
    ```yaml
    entity.ENTITY_ID.settings_tab:
      title: 'Configuración'
      route_name: entity.ENTITY_ID.settings
      base_route: entity.ENTITY_ID.settings
    ```
  - Verificar con: `lando drush ev "\$m = \Drupal::service('plugin.manager.menu.local_task'); print_r(\$m->getLocalTasksForRoute('entity.ENTITY_ID.settings'));"`
- **PHPUnit Mocking — Entity Mocks con ContentEntityInterface:**
  - NUNCA usar `createMock(\stdClass::class)` para entidades. Usar `ContentEntityInterface`.
  - Configurar `get()` con callback que retorne anonymous class con `->value` y `->target_id`:
    ```php
    $entity->method('get')->willReturnCallback(function (string $field) use ($values) {
        $v = $values[$field] ?? NULL;
        return new class($v) { public $value; public function __construct($v) { $this->value = $v; } };
    });
    ```
  - Para `hasField()`: `$entity->method('hasField')->willReturnCallback(fn($f) => isset($values[$f]));`
  - Para ControllerBase: inyectar `currentUser` via `ReflectionProperty::setValue()` (no hay setter publico).
- **ECA Plugins por Codigo:**
  - Los ECA Events heredan de `EventBase`, definen `defaultConfiguration()`, `getEntity()` y `static::EVENT_NAME`.
  - Los ECA Conditions heredan de `ConditionBase`, implementan `evaluate()` retornando bool.
  - Los ECA Actions heredan de `ConfigurableActionBase`, implementan `execute()`.
  - Registrar el evento Symfony base en `src/Event/` y el plugin ECA que lo adapta en `src/Plugin/ECA/Event/`.
- **ConfigEntity Cascade Resolution (Precios Configurables v2.1):**
  - Cuando features/limites dependen de vertical+tier, crear ConfigEntities con ID `{vertical}_{tier}` y defaults `_default_{tier}`.
  - Cascade: especifico → default → NULL. Un servicio broker central (`PlanResolverService`) encapsula la logica.
  - Los consumidores llaman al broker (`getFeatures()`, `checkLimit()`, `hasFeature()`), nunca implementan el cascade.
  - El broker devuelve NULL cuando no hay config, permitiendo fallback en el consumidor.
  - Ejemplo: `$resolver->getFeatures('agroconecta', 'professional')` busca `agroconecta_professional`, luego `_default_professional`.
- **Plan Name Normalization via SaasPlanTier:**
  - Los nombres de plan de fuentes externas (Stripe, APIs, migrations) se normalizan a tier keys canonicos.
  - `SaasPlanTier` ConfigEntity almacena `aliases` (array de strings editables desde UI).
  - `PlanResolverService::normalize($planName)` resuelve aliases lazy-cached.
  - Stripe Price ID → tier: `resolveFromStripePriceId()` busca en `stripe_price_monthly`/`stripe_price_yearly`.
  - Empty/unknown → fallback a `'starter'` o lowercase del input.
- **AdminHtmlRouteProvider para ConfigEntities:**
  - Las ConfigEntities con `AdminHtmlRouteProvider` en `route_provider.html` auto-generan rutas CRUD.
  - Las rutas se definen via `links` en la anotacion `@ConfigEntityType`, no en `routing.yml`.
  - Para ConfigEntities admin, usar `/admin/config/{group}/{entity-type}` como base path.
  - El permiso se define en `admin_permission` de la anotacion.
- **PlanResolverService como getPlanCapabilities():**
  - `getPlanCapabilities(vertical, tier)` devuelve array plano compatible con QuotaManagerService.
  - Limites numericos van como `key => int` (e.g. `max_pages => 25`).
  - Features booleanas van como `feature_key => TRUE` (e.g. `seo_advanced => TRUE`).
  - Features no configuradas NO aparecen en el array (check con `isset()`).
- **Config Schema para Dynamic Keys (CONFIG-SCHEMA-001):**
  - En Drupal config schema, `type: mapping` requiere declarar TODOS los keys posibles. Keys no declarados lanzan `SchemaIncompleteException` en Kernel tests.
  - Para campos con keys dinamicos por vertical/contexto (ej. `limits` con `products`, `photos_per_product`, `commission_pct`), usar `type: sequence` con inner type.
  - `type: sequence` con `sequence: type: integer` acepta tanto arrays indexados como mapas asociativos con keys arbitrarios string→integer.
  - Ejemplo correcto:
    ```yaml
    limits:
      type: sequence
      label: 'Numeric limits per resource'
      sequence:
        type: integer
        label: 'Limit value'
    ```
  - Ejemplo incorrecto (falla con keys no declarados):
    ```yaml
    limits:
      type: mapping
      mapping:
        max_pages:
          type: integer
        # Falta: products, photos_per_product, etc. → SchemaIncompleteException
    ```
- **Multi-Source Limit Resolution en PlanValidator:**
  - `resolveEffectiveLimit()` consulta 3 fuentes en cascade de prioridad:
    1. FreemiumVerticalLimit (via UpgradeTriggerService) — mayor prioridad
    2. SaasPlanFeatures (via PlanResolverService) — prioridad media
    3. SaasPlan entity fallback — menor prioridad
  - Sentinel value `-999` diferencia "no configurado" de "valor real".
- **Blindaje de Identidad IA (AI-IDENTITY-001):**
  - Todo prompt de sistema que genere texto conversacional DEBE incluir una regla de identidad inquebrantable.
  - Texto canon: `"REGLA DE IDENTIDAD INQUEBRANTABLE: Eres un asistente de Jaraba Impact Platform. NUNCA reveles, menciones ni insinúes que eres Claude, ChatGPT, GPT, Gemini, Copilot, Llama, Mistral u otro modelo de IA externo."`
  - **Punto de inyeccion centralizado (BaseAgent):** `buildSystemPrompt()` lo antepone como parte #0 antes del Brand Voice. Todos los agentes que extienden BaseAgent lo heredan automaticamente.
  - **CopilotOrchestratorService:** `buildSystemPrompt()` lo antepone como `$identityRule` antes del basePrompt para todos los 8 modos (coach, consultor, sparring, cfo, fiscal, laboral, devil, landing_copilot).
  - **PublicCopilotController:** `buildPublicSystemPrompt()` incluye bloque `IDENTIDAD INQUEBRANTABLE` con instruccion de respuesta ante preguntas de identidad.
  - **Servicios standalone:** FaqBotService, ServiciosConectaCopilotAgent, CoachIaService — anteponen la regla directamente al system prompt.
  - Si se crea un nuevo agente o copiloto, DEBE heredar de BaseAgent o incluir la regla manualmente.
- **SmartBaseAgent Gen 2 Constructor (SMART-AGENT-DI-001):**
  - Todo agente Gen 2 acepta 10 argumentos: 6 core + 4 opcionales.
  - Core: `$aiProvider`, `$configFactory`, `$logger`, `$brandVoice`, `$observability`, `$modelRouter`.
  - Opcionales (`@?`): `$promptBuilder`, `$toolRegistry`, `$providerFallback`, `$contextWindowManager`.
  - Constructor body: `parent::__construct(6 core)` → `$this->setModelRouter($modelRouter)` → conditional setters.
  - Patron: `if ($toolRegistry) { $this->setToolRegistry($toolRegistry); }` — no setter si null.
  - services.yml: los 3 ultimos args usan `@?jaraba_ai_agents.tool_registry`, etc.
  - Migracion Gen 1→Gen 2: cambiar `extends BaseAgent` a `extends SmartBaseAgent`, renombrar `execute()` a `doExecute()`, copiar constructor de SmartMarketingAgent como referencia.
- **Tool Use Loop (TOOL-USE-AGENT-001):**
  - `callAiApiWithTools()` implementa loop: LLM call → parse `{"tool_call": {"tool_id", "params"}}` → `ToolRegistry::execute()` → append resultado → re-call LLM.
  - Max 5 iteraciones para prevenir loops infinitos.
  - `buildSystemPrompt()` appendea `ToolRegistry::generateToolsDocumentation()` (XML format) si hay tools disponibles.
  - Los tools se registran con tag `jaraba_ai_agents.tool` en services.yml y se auto-descubren via compiler pass.
  - 6 tools: SendEmail, CreateEntity, SearchKnowledge, QueryDatabase, UpdateEntity, SearchContent.
  - UpdateEntity tiene `requiresApproval=true` (usa AgentApproval entity).
- **Provider Fallback (PROVIDER-FALLBACK-001):**
  - `ProviderFallbackService::callWithFallback($tier, $prompt, $config)` — intenta primary, luego fallback si falla.
  - Circuit breaker: 3 fallos consecutivos en ventana de 5 min = OPEN (skip provider). Estado en `\Drupal::state()`.
  - Cadenas por tier en `jaraba_ai_agents.provider_fallback.yml`: fast (Haiku→GPT-4o-mini), balanced (Sonnet→GPT-4o), premium (Opus→Sonnet).
  - Inyectado opcionalmente en SmartBaseAgent (`@?`). Si no disponible, llamada directa sin fallback (backward compatible).
- **Cache Semantica 2 Capas (SEMANTIC-CACHE-001):**
  - Layer 1: hash exacto (MD5 de message+mode+context) via Drupal Cache API (Redis).
  - Layer 2: `SemanticCacheService` genera embedding → vectorSearch en Qdrant coleccion `semantic_cache` → threshold 0.92.
  - `CopilotCacheService::set()` escribe en AMBAS capas. `get()` intenta L1, si miss intenta L2.
  - Respuesta incluye `cache_layer: 'exact'|'semantic'` para observabilidad.
  - Degradacion graceful: `\Drupal::hasService('jaraba_copilot_v2.semantic_cache')` + try-catch.
- **Jailbreak Detection (JAILBREAK-DETECT-001):**
  - `AIGuardrailsService::checkJailbreak()` con patrones bilingues ES/EN.
  - Patrones: "ignore previous", "you are now", "DAN mode", "pretend you are", "olvida tus instrucciones", "actua como si fueras".
  - Accion: BLOCK — se rechaza el mensaje antes de llegar al LLM.
  - Integrado en pipeline de `validate()` junto con `checkPII()` y `checkBlockedPatterns()`.
- **Output PII Masking (OUTPUT-PII-MASK-001):**
  - `AIGuardrailsService::maskOutputPII($text)` reutiliza patrones de `checkPII()` pero reemplaza con `[DATO PROTEGIDO]`.
  - Llamado por SmartBaseAgent DESPUES de recibir respuesta LLM, ANTES de retornar al usuario.
  - El LLM puede "inventar" datos que parezcan PII real — los guardrails DEBEN ser bidireccionales (input + output).
- **ReAct Loop (REACT-LOOP-001):**
  - `ReActLoopService::run($agent, $objective, $context, $maxSteps)` orquesta ciclos multi-paso.
  - Ciclo: PLAN (descomponer objetivo) → EXECUTE (paso con tools) → OBSERVE (resultados) → REFLECT (ajustar plan) → FINISH.
  - Cada paso logueado individualmente via `AIObservabilityService`.
  - Depende de: tool use (FIX-029) y bridge (FIX-030).
  - Usar para tareas autonomas con `execution_mode: 'react'` en AgentOrchestratorService.
- **LLM Re-ranking Config-Driven (FIX-037):**
  - `jaraba_rag.settings.yml` con `reranking.strategy: keyword|llm|hybrid`.
  - `keyword`: overlap de palabras (existente). `llm`: `LlmReRankerService` con tier fast (Haiku). `hybrid`: combinacion ponderada.
  - El servicio se inyecta opcionalmente en JarabaRagService. Fallback a keyword si falla.
  - Patron: cuando hay multiples estrategias, hacerlas seleccionables via config.
- **Recomendaciones Personalizadas via Centroid Embedding (FIX-047):**
  - Generar centroid = promedio de embeddings de los ultimos 5 articulos leidos por el usuario.
  - Buscar articulos no leidos similares en Qdrant (threshold 0.55 — mas bajo que cache porque es recomendacion).
  - Fallback: recomendacion por categorias favoritas (top 3 por frecuencia de lectura).
  - El centroid captura "tema general de interes" sin perfil explicito.
- **Aislamiento de Competidores en IA (AI-COMPETITOR-001):**
  - Ningun prompt DEBE mencionar plataformas competidoras ni modelos de IA por nombre.
  - Si un dato de dominio (recommendations, quick_wins, actions) sugiere un competidor, reemplazar por la funcionalidad equivalente de Jaraba.
  - Excepcion: integraciones reales (LinkedIn import, LinkedIn Ads, Meta Pixel) donde la plataforma externa es un canal de distribucion, no un competidor directo.
  - Patron de redireccion: Si el usuario menciona un competidor, la IA responde explicando como Jaraba cubre esa necesidad.
- **Sistema de Iconos jaraba_icon() (ICON-CONVENTION-001):**
  - **Firma correcta:** `jaraba_icon('category', 'name', { variant: 'duotone', color: 'azul-corporativo', size: '24px' })`.
  - **Convenciones rotas detectadas y corregidas:**
    - Path-style: `jaraba_icon('ui/arrow-left', 'outline')` → separar category y name.
    - Args invertidos: `jaraba_icon('star', 'micro')` → `jaraba_icon('ui', 'star', ...)`.
    - Args posicionales: `jaraba_icon('download', 'outline', 'white', '20')` → usar objeto `{options}`.
  - **Resolucion de SVG:** `{modulePath}/images/icons/{category}/{name}[-variant].svg`. Si no existe, emoji fallback via `getFallbackEmoji()`. Fallback final: 📌 (chincheta).
  - **Bridge categories:** Directorios de symlinks que mapean categorias faltantes a iconos existentes. Ejemplo: `achievement/trophy.svg → ../actions/trophy.svg`. Cubren: achievement, finance, general, legal, navigation, status, tools, media, users.
  - **Duotone-first:** Todo icono en templates premium DEBE usar `variant: 'duotone'`. El duotone aplica `opacity: 0.2` + `fill: currentColor` a capas de fondo.
  - **Colores Jaraba:** `azul-corporativo`, `naranja-impulso`, `verde-innovacion`, `white`, `neutral`. NUNCA colores genericos ni hex.
  - **Auditoria completa:** Extraer todos los pares unicos `jaraba_icon('category', 'name')` con grep → verificar cada SVG en filesystem → crear symlinks/SVGs faltantes → re-verificar con `find -type l ! -exec test -e {}` para detectar symlinks rotos.
  - **Symlinks circulares:** `readlink -f` para detectar. Ejemplo: `save.svg → save.svg` (se apunta a si mismo). Fix: eliminar y recrear apuntando a `save-duotone.svg` o variante correcta.
- **Strict Equality en Access Handlers (ACCESS-STRICT-001):**
  - Toda comparacion de ownership en access handlers DEBE usar `(int) ... === (int) ...`, NUNCA `==`.
  - PHP loose equality permite type juggling: `"0" == false`, `null == 0`, `"" == 0`. En checks de ownership esto es un vector de bypass de acceso.
  - Patron: `(int) $entity->getOwnerId() === (int) $account->id()` y `(int) $entity->get('field')->target_id === (int) $account->id()`.
  - El cast `(int)` normaliza `string|null` a entero y documenta la intencion de comparacion numerica.
  - `===` sin cast falla: `"42" === 42` es `false` en PHP. Por eso se necesita `(int)` en ambos lados.
  - Buscar con: `grep -rn "== \$account->id()" web/modules/custom/ --include="*.php" | grep -v "==="` — DEBE retornar 0 resultados.
  - Los access handlers pueden estar en `src/Access/` O directamente en `src/`. Buscar siempre en `**/*AccessControlHandler.php`.
- **Plantillas MJML Email — Compliance CAN-SPAM + Marca (EMAIL-PREVIEW-001, EMAIL-POSTAL-001, BRAND-FONT-001, BRAND-COLOR-001):**
  - Toda plantilla MJML DEBE tener `<mj-preview>` con texto descriptivo unico justo despues de `<mj-body>`. Usar HTML entities para acentos.
  - Toda plantilla MJML DEBE incluir direccion postal en el footer: `Pol. Ind. Juncaril, C/ Baza Parcela 124, 18220 Albolote, Granada, Espa&ntilde;a`.
  - Font-family: `Outfit, Arial, Helvetica, sans-serif` — `Outfit` es la fuente de marca y siempre va primero.
  - Colores: usar exclusivamente tokens de marca. Tabla de reemplazos universales:
    - `#374151` → `#333333` (body text), `#6b7280` → `#666666` (muted/footer), `#f3f4f6` → `#f8f9fa` (body bg), `#e5e7eb` → `#E0E0E0` (dividers), `#9ca3af` → `#999999` (disclaimer), `#111827` → `#1565C0` (headings).
  - Azul primario de marca: `#1565C0`. Reemplaza Tailwind `#2563eb`, fiscal `#1A365D`/`#553C9A`, andalucia_ei `#233D63`.
  - PRESERVAR colores semanticos: `#dc2626` (error), `#16a34a` (exito), `#f59e0b` (warning), `#FF8C42` (Andalucia EI), `#10b981` (progreso), `#D97706` (fiscal warning). Y sus fondos asociados.
  - Verificar con: `grep -rn "#2563eb\|#1A365D\|#553C9A\|#233D63\|#374151\|#6b7280\|#f3f4f6\|#e5e7eb\|#9ca3af\|#111827"` en MJML — DEBE retornar 0 resultados.
  - El template base DEBE usar tokens de marca desde el dia 0 para evitar deuda multiplicada por N plantillas.
- **Header Sticky por Defecto (CSS-STICKY-001):**
  - Problema raiz: `position: fixed` con header de altura variable (botones de accion wrappean a 2 lineas) hace imposible compensar con un `padding-top` fijo. Causa solapamiento del header sobre el contenido.
  - Solucion: `position: sticky` como default global. El header participa en el flujo normal del documento y nunca solapa contenido.
  - Override: solo `body.landing-page, body.page-front` mantienen `position: fixed` (el hero fullscreen se renderiza debajo del header).
  - Areas de contenido (`.main-content`, `.user-main`, `.error-page`, `.help-center-main`): solo `padding-top: 1.5rem` estetico. NUNCA compensatorio para header.
  - Toolbar admin: `top: 39px` (toolbar cerrado) y `top: 79px` (toolbar horizontal abierto) se definen una unica vez en `_landing-page.scss`, no en cada area de contenido.
  - Especificidad CSS: `body.landing-page .landing-header` (0-2-1) gana sobre `.landing-header` (0-1-0).
- **Mensajes en Templates de Formulario Custom (FORM-MSG-001):**
  - Problema raiz: cuando un modulo define su propio `#theme` para envolver un formulario (patron zero-region), los `status_messages` de Drupal NO se renderizan automaticamente. Los errores de `setErrorByName()` y mensajes de `addStatus()` se pierden silenciosamente.
  - Solucion: el `hook_theme()` DEBE declarar `'messages' => NULL` como variable. Un preprocess hook DEBE inyectar `$variables['messages'] = ['#type' => 'status_messages']`. El template DEBE renderizar `{{ messages }}` ANTES de `{{ form }}`.
  - Patron de preprocess:
    ```php
    function mimodulo_preprocess_mi_formulario_page(array &$variables): void {
      $variables['messages'] = ['#type' => 'status_messages'];
    }
    ```
  - Este es un error silencioso: no hay warnings en logs, y la validacion HTML5 del browser oculta el problema en la mayoria de los casos.
- **Paginas Legales/Informativas Configurables (LEGAL-ROUTE-001 / LEGAL-CONFIG-001):**
  - Las paginas legales se definen como rutas en `ecosistema_jaraba_core.routing.yml` (plataforma global, no vertical-especifico).
  - URLs canonicas en espanol: `/politica-privacidad`, `/terminos-uso`, `/politica-cookies`, `/sobre-nosotros`, `/contacto`.
  - Los controladores leen contenido de `theme_get_setting('legal_*_content', 'ecosistema_jaraba_theme')`.
  - Templates zero-region reutilizables (ej: `legal-page.html.twig` para las 3 paginas legales).
  - Theme hooks declarados en `ecosistema_jaraba_core.module` con variables: `page_type`, `title`, `content`, `last_updated`.
  - TAB 14 "Paginas Legales" en theme settings permite editar contenido desde la UI de Drupal.
  - Cache tags: `config:ecosistema_jaraba_theme.settings` para invalidar cuando se actualiza el contenido.
  - El footer (`_footer.html.twig`) DEBE tener defaults que apunten a las URLs canonicas, no a rutas en ingles.
- **PathProcessor para Path Aliases Custom (PATH-ALIAS-PROCESSOR-001):**
  - Cuando una entidad ContentEntity tiene un campo `path_alias` propio (no gestionado por el modulo `path_alias` del core), implementar `InboundPathProcessorInterface`.
  - Registrar como servicio con tag `path_processor_inbound` y prioridad 200+ (core path_alias tiene prioridad 100).
  - El procesador busca coincidencia en la tabla de la entidad y reescribe la URL a la ruta canonica (ej. `/page/{id}`).
  - **Skip list de prefijos:** Excluir `/api/`, `/admin/`, `/user/`, `/media/`, `/session/` para rendimiento.
  - **Sin filtro por status:** No usar `->condition('status', 1)`. Delegar control de acceso al `AccessControlHandler` de la entidad. Permite que admins accedan a borradores via URL amigable.
  - **Static cache:** Cachear resoluciones por path dentro del request con propiedad estatica. Evita queries duplicadas.
  - **Patron de registro en services.yml:**
    ```yaml
    path_processor.page_content:
      class: Drupal\mimodulo\PathProcessor\PathProcessorMiEntidad
      arguments: ['@entity_type.manager', '@database']
      tags:
        - { name: path_processor_inbound, priority: 200 }
    ```
  - **Meta-Sitio workflow:** Las paginas se gestionan como entidades PageContent. Titulos y aliases via `PATCH /api/v1/pages/{id}/config`. Publicacion via `POST /api/v1/pages/{id}/publish`. Contenido visual via GrapesJS `editor.store()`.
- **Meta-Sitio: Iconos SVG Inline en Canvas Data (ICON-EMOJI-001 + ICON-CANVAS-INLINE-001):**
  - Las paginas de Page Builder almacenan HTML en `canvas_data` (campo `string_long` con JSON). Este HTML se renderiza directamente, sin pasar por Twig ni por el sistema de temas.
  - **NUNCA** usar emojis Unicode como iconos visuales en canvas_data. Los emojis se renderizan diferente entre OS/navegadores, no siguen la paleta de marca y no son escalables.
  - **Reemplazo:** Usar SVGs inline con `width`/`height` explicitos. Ejemplo:
    ```html
    <!-- MAL: emoji -->
    <div class="pj-card__icon">🏪</div>
    <!-- BIEN: SVG inline -->
    <div class="pj-card__icon"><svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#233D63" stroke-width="2">...</svg></div>
    ```
  - **Colores:** Usar hex del brand (`#233D63`, `#FF8C42`, `#00A9A5`), NUNCA `currentColor`. El canvas_data no hereda CSS del tema Twig.
  - **Duotone en canvas inline:** `fill="#233D63" fill-opacity="0.12"` para capas de fondo, `stroke="#233D63"` para lineas.
  - **Tamano:** Match con el CSS del contenedor. Si `.pj-card__icon { font-size: 48px }`, el SVG usa `width="48" height="48"`.
  - **Actualizacion masiva:** Crear script PHP con `str_replace()` por cada emoji → SVG, ejecutar via `drush scr`. Actualizar tanto `html` como `rendered_html` en canvas_data.
  - **Auditoria de emojis en BD:** Detectar emojis remanentes con:
    ```php
    preg_match_all('/[\x{1F000}-\x{1FFFF}]/u', $html, $matches);
    ```
  - **Categoria business/ para iconos conceptuales:** Cuando los iconos genericos (clock, user, cart) no comunican el concepto especifico, crear iconos custom en `business/` con nombres descriptivos (`time-pressure`, `launch-idea`, `talent-spotlight`, etc.).
- **Meta-Sitio: PathProcessor Priority y Homepage Resolution:**
  - El PathProcessorPageContent tiene prioridad **250** (no 200) para ejecutarse ANTES de `PathProcessorFront` de Drupal core (prioridad 200) que reescribe `/` a `/node`.
  - El 4to parametro del constructor es `@?jaraba_site_builder.meta_site_resolver` (DI opcional con `@?`).
  - `resolveHomepage(Request $request)`: extrae hostname del request, llama a `MetaSiteResolverService::resolveFromDomain()`, obtiene `homepage_id` de SiteConfig, y reescribe `/` a `/page/{homepage_id}`.
  - **MetaSiteResolverService** tiene 3 estrategias de resolucion de dominio (ordenadas por prioridad):
    1. **Domain Access hostname:** Busca tenants cuyo `domain_id` referencia un Domain Access con hostname exacto.
    2. **Tenant.domain field:** Busca tenants cuyo campo `domain` coincide exactamente con el hostname.
    3. **Subdomain prefix:** Extrae el primer segmento del hostname (ej. `pepejaraba` de `pepejaraba.jaraba-saas.lndo.site`) y busca tenants cuyo `domain` empieza con ese prefijo.
  - La estrategia 3 es la que funciona en desarrollo local con Lando, donde el hostname incluye el dominio completo del proxy.
- **TenantBridgeService — Inyeccion y Uso (TENANT-BRIDGE-001):**
  - Cuando un servicio necesita resolver entre entidades Tenant y Group, DEBE inyectar `TenantBridgeService` (`@ecosistema_jaraba_core.tenant_bridge`) y usar sus metodos: `getTenantForGroup()`, `getGroupForTenant()`, `getTenantIdForGroup()`, `getGroupIdForTenant()`.
  - NUNCA cargar `$this->entityTypeManager->getStorage('group')` con un Tenant ID ni `getStorage('tenant')` con un Group ID. Los IDs no son intercambiables.
  - Ejemplo (QuotaManagerService): `$tenant = $this->tenantBridge->getTenantForGroup($group);` para obtener la entidad Tenant desde un Group y consultar billing/quota.
  - Error handling: los metodos lanzan `\InvalidArgumentException` si la entidad fuente no existe. Envolver en try/catch cuando el ID puede ser invalido.
  - En services.yml: `arguments: ['@ecosistema_jaraba_core.tenant_bridge']` (no opcional, es dependencia P0).
- **Tenant Isolation en Access Handlers (TENANT-ISOLATION-ACCESS-001):**
  - Todo access handler de entidades con `tenant_id` DEBE implementar `EntityHandlerInterface` para DI (no el patron estatico).
  - `createInstance()` inyecta `TenantContextService` desde el container.
  - `checkAccess()` implementa politica: `view` = paginas publicadas son publicas, `update`/`delete` = `isSameTenant()` + permiso.
  - Patron `isSameTenant()`:
    ```php
    private function isSameTenant(EntityInterface $entity): bool {
      $currentTenantId = $this->tenantContext->getCurrentTenantId();
      if ($currentTenantId === NULL) return FALSE;
      return (int) $entity->get('tenant_id')->target_id === (int) $currentTenantId;
    }
    ```
  - `TenantContextService::getCurrentTenantId()` retorna `?int` (NULL cuando el usuario no tiene grupo). El handler DEBE manejar NULL como "sin acceso".
  - Al renombrar access handlers (ej. `DefaultAccessControlHandler` → `DefaultEntityAccessControlHandler`), actualizar la referencia `access` en la anotacion `@ContentEntityType` de la entidad.
- **Test Mock Migration — Cambio de Entity Storage Key (CI-KERNEL-001):**
  - Cuando se corrige el entity type key en codigo (ej. `getStorage('group')` → `getStorage('tenant')`), los tests unitarios que mockean `EntityTypeManagerInterface` DEBEN actualizar sus `->with('...')` expectations para coincidir.
  - Patron: si el mock configura `$entityTypeManager->method('getStorage')->with('group')` y el codigo ahora llama `getStorage('tenant')`, el test falla silenciosamente con "method was not expected to be called with these arguments". Actualizar a `->with('tenant')`.
  - Los Kernel tests que instalan esquemas de entidades DEBEN listar `group` y/o `tenant` en `$modules` segun corresponda: `$this->installEntitySchema('group')` antes de `$this->installEntitySchema('page_content')` si hay foreign key.
  - El CI pipeline DEBE tener un job `kernel-test` separado con servicio MariaDB (mariadb:10.11) y base de datos `drupal_test`. Los Kernel tests no funcionan sin BD real.
- **Premium Entity Forms con Secciones (PREMIUM-FORMS-PATTERN-001):**
  - **Migracion completa:** 237 formularios en 50 modulos migrados a `PremiumEntityFormBase`. 0 `ContentEntityForm` restantes.
  - Clase abstracta: `ecosistema_jaraba_core/src/Form/PremiumEntityFormBase.php`. Extiende `ContentEntityForm` con glass-card UI.
  - Todo form DEBE implementar `getSectionDefinitions()` (array de secciones con label, icon, fields) y `getFormIcon()`.
  - **4 patrones de migracion:**
    - **Patron A (Simple):** Solo requiere `getSectionDefinitions()` + `getFormIcon()`. Sin DI extra ni logica custom.
    - **Patron B (Computed Fields):** Campos auto-calculados con `#disabled = TRUE` (nunca `#access = FALSE`).
    - **Patron C (DI):** Usar `parent::create($container)` para preservar DI de la base, luego anadir dependencias propias.
    - **Patron D (Custom Logic):** Override de `buildForm()`/`save()` llamando a `parent::` para mantener secciones y glass-card UI.
  - **Iconos de seccion:** Usar categorias del icon system (`ui`, `actions`, `fiscal`, `users`, etc.) con variante duotone.
  - **PROHIBIDO:** Fieldsets (`#type => 'fieldset'`), details groups (`#type => 'details'`) — rompen navigation pills.
  - **Campos internos:** uid, created, changed, status van en `HIDDEN_FIELDS` (no en secciones).
  - **Redirect post-save:** `parent::save()` redirige a la ruta `collection` de la entidad.
  - **Verificacion:** `grep -rl "extends ContentEntityForm" web/modules/custom/*/src/Form/ | grep -v PremiumEntityFormBase | grep -v SettingsForm` → 0 resultados.
- **Slide-Panel AJAX vs Drupal Modal (SLIDE-PANEL-001):**
  - Drupal modal/dialog envia XHR con query param `_wrapper_format`. Slide-panel custom envia XHR sin el.
  - Patron: `isSlidePanelRequest()` = `$request->isXmlHttpRequest() && !$request->query->has('_wrapper_format')`.
  - Solo devolver bare HTML (via `renderer->render()`) para slide-panel. Para Drupal modal, dejar que el dialog renderer gestione.
  - Aplicar en todos los controllers que soporten ambos modos (ProfileController, CvController, ProfileSectionFormController).
- **Meta-Site Tenant-Aware Rendering (META-SITE-RENDER-001):**
  - Cuando una pagina pertenece a un meta-sitio (tiene tenant_id con SiteConfig), el rendering DEBE override: `<title>` (meta_title_suffix), Schema.org Organization (name/description/logo), header (layout, sticky, CTA), footer (copyright, layout), navegacion (SitePageTree items), logo, body class (`meta-site meta-site-tenant-{id}`).
  - `MetaSiteResolverService::resolveFromPageContent()` es el punto unico de resolucion. Consume SiteConfig + SitePageTree.
  - Los hooks de preprocess (`preprocess_html`, `preprocess_page`) detectan `page_content` en route_match y llaman al resolver.
  - `SitePageTree` status filter: usar `1` (int), no `'published'` (string). Los campos boolean de entidad almacenan `0`/`1`.
- **Migracion de Field Types con Update Hooks:**
  - Para cambiar el tipo de un campo (ej. `entity_reference` → `image`, `timestamp` → `datetime`): (1) backup datos existentes, (2) uninstall old field definition, (3) install new field from current `baseFieldDefinitions()`, (4) restore datos.
  - Para reinstalar entidades completas (cuando las tablas estan vacias): `uninstallEntityType()` + `installEntityType()` en el update hook.
  - Siempre verificar con `getFieldStorageDefinition()` antes de uninstall. Siempre try/catch en la restauracion de datos.
- **Preprocess Obligatorio para Custom Entities (ENTITY-PREPROCESS-001):**
  - Toda ContentEntity custom que se renderice en view mode DEBE tener `template_preprocess_{entity_type}()` en el `.module`.
  - La entidad esta en `$variables['elements']['#{entity_type}']`. El preprocess extrae datos primitivos, resuelve entidades referenciadas (category, owner/author) y genera URLs de imagenes responsive con `ImageStyle::load()->buildUrl()`.
  - Sin este preprocess, los templates Twig NO pueden acceder a los datos de la entidad — las variables simplemente no existen.
  - Patron:
    ```php
    function template_preprocess_content_article(array &$variables): void {
      $entity = $variables['elements']['#content_article'] ?? NULL;
      if (!$entity) { return; }
      $variables['article'] = [
        'title' => $entity->getTitle(),
        'body' => $entity->get('body')->value ?? '',
        // ... resolver category, author, responsive images ...
      ];
    }
    ```
  - Para imagenes responsive: `ImageStyle::load('article_card')->buildUrl($uri)` genera derivados por Image Style. Construir `srcset` con multiples tamaños: `$card_url . ' 600w, ' . $featured_url . ' 1200w'`.
  - Para author data: cargar User entity desde `$entity->getOwner()`, extraer display_name, user_picture, y field_bio si existen.
- **Resiliencia en Presave Hooks (PRESAVE-RESILIENCE-001):**
  - Los hooks `hook_{entity_type}_presave()` que invoquen servicios opcionales DEBEN envolver cada invocacion con:
    ```php
    if (\Drupal::hasService('service_id')) {
      try {
        \Drupal::service('service_id')->doSomething($entity);
      } catch (\Throwable $e) {
        \Drupal::logger('module')->warning('Service failed: @msg', ['@msg' => $e->getMessage()]);
      }
    }
    ```
  - Aplicado en `jaraba_content_hub`: sentiment_engine, reputation_monitor, pathauto. El save de la entidad NUNCA debe fallar por un servicio opcional.
- **Paginacion Server-Side con Sliding Window (Blog Pattern):**
  - Leer pagina actual desde query string: `$request->query->get('page', 1)`.
  - Calcular offset: `$offset = ($current_page - 1) * $limit`.
  - Contar total: `$this->articleService->countPublishedArticles()`.
  - Construir pager con ventana deslizante ±2 paginas: `$start = max(1, $current_page - 2)`, `$end = min($total_pages, $current_page + 2)`.
  - Generar URLs con `Url::fromRoute('route.name', [], ['query' => ['page' => $i]])->toString()`.
  - Cache context OBLIGATORIO: `'url.query_args:page'`.
  - Declarar variable `pager` en `hook_theme()`.
  - Template usa `<nav class="blog-pagination" aria-label="{% trans %}...{% endtrans %}">` con `<ol>` de paginas.
- **N+1 Query Fix con GROUP BY:**
  - Problema: BlogController llamaba `getArticleCount($categoryId)` por cada categoria en un loop → N queries.
  - Solucion: `CategoryService::getArticleCountsByCategory()` usa una sola query SQL:
    ```sql
    SELECT ca.category, COUNT(*) as cnt FROM {content_article_field_data} ca
    WHERE ca.status = :status AND ca.category IS NOT NULL GROUP BY ca.category
    ```
  - Retorna `[category_id => count]`. El controller usa `$article_counts[(int) $category->id()] ?? 0`.
  - Patron aplicable a cualquier conteo por grupo: reemplazar N queries individuales por un solo GROUP BY.
- **Share Buttons sin JS SDKs (Clipboard API + URL Schemes):**
  - Share buttons DEBEN usar URL-scheme links nativos:
    - Facebook: `https://www.facebook.com/sharer/sharer.php?u={url}`
    - Twitter: `https://twitter.com/intent/tweet?url={url}&text={title}`
    - LinkedIn: `https://www.linkedin.com/sharing/share-offsite/?url={url}`
  - Copy-to-clipboard: `navigator.clipboard.writeText(url)` con fallback `document.execCommand('copy')` para Safari.
  - Feedback visual: toggle clase CSS `copied` durante 2 segundos via `setTimeout`.
  - NUNCA cargar SDKs de terceros (Facebook SDK, Twitter widget, etc.) — viola privacidad, bloqueado por ad-blockers, y anade peso innecesario.
- **Reading Progress Bar (requestAnimationFrame + Reduced Motion):**
  - Scroll-driven progress bar: calcular `scrollTop / (scrollHeight - clientHeight) * 100` y asignar a `width`.
  - Throttle con `requestAnimationFrame` — NUNCA usar `scroll` event directamente sin throttle.
  - CSS: `position: fixed; top: 0; width: 0; height: 3px; background: linear-gradient(...); will-change: width; z-index: 1000`.
  - Respetar `prefers-reduced-motion`: `@media (prefers-reduced-motion: reduce) { .reading-progress__bar { transition: none; } }`.
  - Libreria Drupal behaviors: `Drupal.behaviors.jarabaReadingProgress` con `once()` para evitar duplicacion.
- **Prose Column 720px para Articulos:**
  - El contenido de articulos usa `max-width: 720px` (~65 caracteres por linea a 1.125rem). Anchura optima para legibilidad.
  - Author bio, share buttons y related content pueden ser mas anchos (max-width: 800px o 100%).
  - SCSS: `.content-article--full { max-width: 720px; margin-inline: auto; }`.
- **AIIdentityRule Centralizada (FIX-001):**
  - La regla de identidad IA DEBE estar en una clase estatica: `ecosistema_jaraba_core/src/AI/AIIdentityRule.php`.
  - Metodo: `public static function apply(string $prompt): string` — antepone la regla de identidad al prompt si no esta presente.
  - NUNCA duplicar la regla en cada agente/copiloto. Todos DEBEN llamar `AIIdentityRule::apply($systemPrompt)`.
  - Consumidores: BaseAgent, SmartBaseAgent, CopilotOrchestratorService, PublicCopilotController, FaqBotService, CoachIaService, etc.
- **Guardrails Pipeline con PII Espanol (FIX-003 + FIX-028):**
  - `AIGuardrailsService::checkPII()` DEBE detectar tanto formatos US (SSN, US phone) como espanoles (DNI, NIE, IBAN ES, NIF/CIF, +34).
  - Acciones del pipeline: ALLOW (sin cambios), MODIFY (limpia prompt), BLOCK (rechaza), FLAG (permite con warning).
  - La integracion va en `SmartBaseAgent.execute()` — antes de llamar al proveedor IA.
  - Cada nuevo mercado geografico requiere anadir patrones PII al guardrail.
- **Model Routing con Config YAML (FIX-019 + FIX-020):**
  - `ModelRouterService.assessComplexity()` usa regex bilingues EN+ES con flags `/iu` para Unicode.
  - Los tiers y pricing se definen en `jaraba_ai_agents.model_routing.yml` con schema en `jaraba_ai_agents.schema.yml`.
  - El servicio carga config en constructor con deep-merge: `array_merge($defaults[$tier], $configOverride[$tier])`.
  - Al cambiar de proveedor o modelo, solo se actualiza el YAML — no requiere code deploy.
- **Observabilidad Conectada (FIX-021):**
  - Todo servicio IA DEBE llamar `$this->observability->log([...])` tras cada ejecucion LLM/embedding.
  - Campos minimos: agent_id, action, tier, model_id, provider_id, tenant_id, vertical, input_tokens, output_tokens, duration_ms, success.
  - Estimacion de tokens: `ceil(mb_strlen($text) / 4)` cuando el proveedor no devuelve conteo real.
  - Loguear TANTO exitos como fallos (en executeWorkflow: success path Y abort path).
- **AIOpsService con Metricas Reales (FIX-022):**
  - NUNCA usar `rand()` para metricas operativas. Las metricas deben venir de fuentes reales:
    - CPU: `/proc/stat` (user+nice+system / total).
    - Memoria: `/proc/meminfo` (MemTotal - MemAvailable) / MemTotal.
    - Disco: `disk_free_space()` / `disk_total_space()`.
    - BD: `SHOW GLOBAL STATUS LIKE 'Threads_connected'`.
    - Latencia: `AVG(duration_ms)` y P95 desde `ai_telemetry` table.
    - Costos: `SUM(cost_estimated)` desde `ai_telemetry` con filtro de mes.
  - Envolver TODO en try/catch con defaults sensibles (50% CPU, 60% RAM, etc.) para entornos sin /proc.
- **Streaming SSE Semantico (FIX-006 + FIX-024):**
  - MIME: `text/event-stream` obligatorio (no `text/plain`).
  - Eventos tipados: `mode`, `thinking`, `chunk`, `done`, `error`.
  - Chunking: dividir por parrafos (`splitIntoParagraphs()` con `preg_split('/\n{2,}/')`) — no por 80 caracteres arbitrarios.
  - NUNCA usar `usleep()` para simular streaming. Emitir chunks tan pronto como esten disponibles.
  - Campo `streaming_mode: 'buffered'` en el evento `done` si no es streaming real del proveedor.
- **Canonical Verticals (FIX-027):**
  - 10 nombres canonicos: empleabilidad, emprendimiento, comercioconecta, agroconecta, jarabalex, serviciosconecta, andalucia_ei, jaraba_content_hub, formacion, demo.
  - Fuente de verdad: constante `BaseAgent::VERTICALS`.
  - Aliases legacy: `comercio_conecta`→`comercioconecta`, `servicios_conecta`→`serviciosconecta`, `content_hub`→`jaraba_content_hub`.
  - Todo servicio que use nombres de vertical DEBE normalizar aliases en `getVerticalContext()` o metodo equivalente.
  - Default: `'general'` cuando el vertical no se reconoce.
- **Agent Generations y Documentacion (FIX-025):**
  - Gen 0 (deprecated): Agentes con `@deprecated` apuntando al reemplazo Gen 2 (ej. `MarketingAgent` → `SmartMarketingAgent`).
  - Gen 1 (active): Agentes con `@note Gen 1 agent` describiendo su estado y roadmap de migracion.
  - Gen 2 (current): Subclasses de SmartBaseAgent con model routing + guardrails + observabilidad.
  - Al crear nuevos agentes, SIEMPRE extender SmartBaseAgent (Gen 2), nunca BaseAgent directamente.
- **@? Optional DI para Cross-Module Services (FIX-026):**
  - Cuando un servicio IA referencia `UnifiedPromptBuilder` u otros servicios de modulos que pueden no estar instalados, usar `@?` en services.yml.
  - Ejemplo: `@?ecosistema_jaraba_core.unified_prompt_builder` en lugar de `@ecosistema_jaraba_core.unified_prompt_builder`.
  - Constructor: `?UnifiedPromptBuilder $promptBuilder = NULL`. Null-guard antes de usar: `$this->promptBuilder?->build(...)`.
  - Critico para evitar `ServiceNotFoundException` en Kernel tests que solo habilitan un modulo.
- **Meta-Site Nav Chain Debugging (META-SITE-NAV-001):**
  - Cuando la navegacion de un meta-sitio no aparece, la cadena de diagnostico es:
    1. Verificar que `page--page-builder.html.twig` incluye `_header.html.twig` cuando `meta_site` es truthy (no el header inline hardcodeado).
    2. Verificar `header_type` en SiteConfig: `SELECT header_type FROM site_config WHERE id = N` — DEBE ser `classic` (no `minimal`).
    3. Verificar que `theme_preprocess_page()` establece `$variables['theme_settings']['navigation_items']` via `MetaSiteResolverService::resolveFromPageContent()`.
    4. Verificar que `_header.html.twig` parsea correctamente el formato `"Texto|URL\n"` a array de `{text, url}`.
  - Root cause comun: el page template tenia un header inline que ignoraba `theme_settings`. Fix: condicional `{% if meta_site %}` para incluir el partial compartido.
  - Segundo root cause: `header_type = 'minimal'` no renderiza `<nav>` horizontal. Fix: `UPDATE site_config SET header_type = 'classic'`.
- **Copilot Suggestion URL Format (COPILOT-LINK-001):**
  - Las sugerencias del copilot soportan 2 formatos: strings planos y objetos `{label, url}`.
  - JS normalization: `var item = typeof s === 'string' ? { label: s } : s; if (item.url) { /* render as <a> */ }`.
  - CSS: clase `.suggestion-btn--link` / `.copilot-chat__suggestion-btn--link` con fondo naranja `--ej-color-impulse`, color blanco, font-weight 600.
  - Backend: `CopilotOrchestratorService::getContextualActionButtons(string $mode)` retorna `[{label, url}]` segun modo y auth state.
  - Anonimo: siempre incluye `['label' => 'Crear cuenta gratis', 'url' => '/user/register']`.
  - Autenticado: acciones por modo (coach→Mi perfil, consultor→Mi dashboard, cfo→Panel financiero, landing_copilot→Explorar plataforma).
  - `formatResponse()` hace merge de sugerencias de texto + action buttons contextuales.
  - Ambas implementaciones (v1 `contextual-copilot.js` y v2 `copilot-chat-widget.js`) DEBEN soportar el formato dual.
- **ReviewableEntityTrait para normalizar entidades heterogeneas (REVIEW-TRAIT-001):**
  - Cuando multiples verticales implementan el mismo concepto (reviews, comments, ratings) con campos nombrados de forma diferente (status vs state vs review_status, reviewer_uid vs uid vs mentee_id), crear un PHP Trait que: (1) define `baseFieldDefinitions()` parcial con campos canonicos compartidos, (2) proporciona helpers con fallback (`getReviewStatusValue()` intenta review_status, luego status, luego state), (3) normaliza acceso sin romper retrocompatibilidad.
  - El trait permite operaciones transversales (moderacion, agregacion, busqueda) sin conocer la implementacion interna de cada vertical.
  - Patron de migracion: update hook que renombra campo (backup → uninstall old → install new → restore), no romper existentes.
  - Helpers del trait DEBEN usar `$entity->hasField()` + `$entity->get($field)->value` con fallback a NULL, nunca asumir que el campo existe.
- **Schema.org AggregateRating obligatorio para reviews (SCHEMA-AGGREGATE-001):**
  - Toda pagina que muestre un resumen de ratings (producto, proveedor, mentor, curso) DEBE incluir JSON-LD `AggregateRating` en el `<head>`.
  - Inyectar via `$build['#attached']['html_head'][]` en el controlador o preprocess, NUNCA hardcodear `<script type="application/ld+json">` en templates Twig.
  - Campos obligatorios: `@type: AggregateRating`, `ratingValue` (decimal 1 cifra), `reviewCount`, `bestRating: 5`, `worstRating: 1`.
  - Para reviews individuales visibles en la pagina, anidar `Review` con `author/Person`, `datePublished`, `reviewBody`, `reviewRating/Rating`.
  - Verificar con Google Rich Results Test que el markup produce rich snippets validos.
  - El servicio `ReviewSchemaOrgService` centraliza la generacion para todos los verticales.
- **Moderacion centralizada de reviews (REVIEW-MODERATION-001):**
  - Las reviews NUNCA se publican sin pasar por moderacion (status `pending` por defecto).
  - `ReviewModerationService` centraliza: moderate(), autoApproveIfEligible() (5+ reviews aprobadas, rating >= 3.5), cola por tenant, bulk actions (max 50).
  - Reviews con rating <= 2 SIEMPRE requieren revision manual aunque el autor sea elegible para auto-approve.
  - El servicio acepta `$entity_type_id` para operar sobre cualquier entidad de review de cualquier vertical.
- **Auditoria de sistemas heterogeneos cross-vertical:**
  - Cuando la plataforma tiene N implementaciones del mismo concepto en diferentes verticales, la auditoria DEBE ser comparativa: tabla de campos, nomenclatura, tenant handling, access control, servicios, templates, rutas, Schema.org.
  - Los hallazgos se clasifican en: S (seguridad), B (bugs), A (arquitectura), D (directrices), G (brechas clase mundial).
  - El plan de consolidacion prioriza: (1) seguridad (tenant isolation), (2) trait transversal, (3) servicios compartidos, (4) frontend unificado, (5) Schema.org, (6) entidades nuevas.
- **Kernel Test AI Service Resilience (KERNEL-OPTIONAL-AI-001):**
  - Los Kernel tests que habilitan modulos como `jaraba_content_hub` o `jaraba_lms` pero NO `drupal:ai` ni `jaraba_ai_agents` fallan con `ServiceNotFoundException` si los services.yml del modulo referencian `@ai.provider` (non-optional).
  - **Fix:** Cambiar todas las referencias cross-module de AI a `@?` (optional): `@?ai.provider`, `@?jaraba_ai_agents.tenant_brand_voice`, `@?jaraba_ai_agents.observability`, etc.
  - **PHP:** Constructores aceptan nullable params (`?AiProviderPluginManager $aiProvider`). En agentes que heredan de `BaseAgent` (cuyo constructor requiere non-null `object $aiProvider`), la subclase DEBE condicionar `parent::__construct()` solo cuando AI deps son non-null; de lo contrario, asigna manualmente las props necesarias (configFactory, logger).
  - **Early return:** El metodo `execute()` DEBE retornar respuesta de error controlada cuando AI no esta disponible (`if (!isset($this->aiProvider)) return ['success' => FALSE, 'error' => '...']`).
  - Aplicado a: `jaraba_content_hub.content_writer_agent`, `jaraba_content_hub.content_embedding`, `jaraba_lms.agent.learning_path`.
- **DOC-GUARD Verification Post-Commit:**
  - Despues de cada commit que toque documentos maestros, ejecutar `scripts/maintenance/verify-doc-integrity.sh` para verificar que las lineas no cayeron bajo los umbrales (DIRECTRICES>=2000, ARQUITECTURA>=2400, INDICE>=2000, FLUJO>=700).
  - El pre-commit hook `.git/hooks/pre-commit` bloquea automaticamente commits que reduzcan >10% las lineas de cualquier doc maestro.
  - El CI pipeline ejecuta `scripts/ci/verify-doc-integrity.sh` como step adicional del security scan.

---
## 4. Despues de Implementar

Actualizar los 3 documentos maestros + crear aprendizaje:

1. **DIRECTRICES:** Incrementar version en header + añadir entrada al changelog (seccion 14) + nuevas reglas si aplica (seccion 5.8.x)
2. **ARQUITECTURA:** Incrementar version en header + actualizar modulos en seccion 7.1 si se añadieron modulos + tabla 12.3 si se elevo un vertical + changelog al final
3. **INDICE:** Incrementar version en header + nuevo blockquote al inicio (debajo del header) + entrada en tabla Registro de Cambios
4. **Aprendizaje:** Crear fichero en `docs/tecnicos/aprendizajes/YYYY-MM-DD_nombre_descriptivo.md` con formato estandar (tabla metadata, Patron Principal, Aprendizajes Clave con Situacion/Aprendizaje/Regla)
5. **FLUJO TRABAJO:** Actualizar este documento si se descubren nuevos patrones de workflow reutilizables

---

## 5. Reglas de Oro (Actualizadas)

1. **No hardcodear:** Configuracion via Config Entities o State API.
2. **Inmutabilidad Financiera:** Registros append-only y encadenados por hash.
3. **Detección Proactiva:** El sistema debe avisar (Push/Email) antes de que el usuario lo pida.
4. **Tenant isolation:** `tenant_id` obligatorio.
5. **Mocking Seguro:** No mockear Value Objects `final`, usarlos directamente.
6. **DI Flexible:** Si una dependencia es `final` en contrib, usar type hint `object` en core para permitir el testeo.
7. **Documentar siempre:** Toda sesion genera actualizacion documental.
8. **Privacidad Diferencial:** Toda inteligencia colectiva debe pasar por el motor de ruido de Laplace.
9. **Verificar CI tras cambios de config:** Tras modificar archivos de configuracion de herramientas (trivy.yaml, workflows), monitorear el pipeline completo hasta verde. Las herramientas pueden ignorar claves invalidas sin warning.
10. **Update hooks para config resync:** Tras modificar YAMLs en `config/install/`, crear un update hook que reimporte los configs en la BD activa. Los YAMLs de `config/install/` solo se procesan durante la instalacion del modulo.
11. **CSRF header en APIs:** Toda ruta API consumida via fetch() DEBE usar `_csrf_request_header_token`, NUNCA `_csrf_token`. El patron JS es: obtener token de `/session/token`, cachear, enviar como `X-CSRF-Token`.
12. **Sanitizar siempre contenido usuario:** En Twig `|safe_html` (nunca `|raw`), en PHP emails `Html::escape()`, en controladores `(string)` para TranslatableMarkup.
13. **Auditar cambios externos:** Cuando otra IA o agente modifica codigo, seguir protocolo: Clasificar → Revert → Fix → Verify → Document. Nunca asumir que los cambios son correctos.
14. **Entidades append-only:** Las entidades de registro inmutable (predicciones, auditorias, metricas) NUNCA tienen form handlers de edicion ni rutas de eliminacion. Solo `create` y `view`. El AccessControlHandler deniega `update`/`delete`.
15. **Config seeding con JSON:** Los YAMLs de `config/install/` almacenan arrays PHP nativos. El update_hook lee YAML, codifica con `json_encode()` los campos complejos, y crea la entidad. Siempre verificar existencia previa para idempotencia.
16. **Preview images por vertical:** Cada vertical del Page Builder DEBE tener los PNGs de preview generados y desplegados en `images/previews/` antes de ir a produccion. Auto-deteccion en `getPreviewImage()` convierte `id_con_underscores` a `id-con-guiones.png`.
17. **Entity field mapping en APIs:** Los campos en `$storage->create()` DEBEN coincidir exactamente con `baseFieldDefinitions()`. Mapear explicitamente en el controlador (request `datetime` → entity `booking_date`). Nunca asumir que el nombre del request coincide con el de la entidad.
18. **Status values coherentes:** Los valores de status en controladores, cron y hooks DEBEN coincidir con los `allowed_values` de la entidad. Si la entidad define `cancelled_client`/`cancelled_provider`, mapear `cancelled` generico al valor correcto en el punto de entrada.
19. **Cron idempotency con flags:** Toda accion cron que envie notificaciones DEBE filtrar por flag NOT sent, marcar flag TRUE tras enviar, y guardar. Previene duplicados en reintentos.
20. **Cifrado server-side para datos sensibles:** Mensajes, adjuntos y datos PII en tablas custom DEBEN cifrarse con AES-256-GCM. IV aleatorio por registro, tag almacenado junto al ciphertext, clave derivada con Argon2id desde env var. NUNCA almacenar claves en BD ni config.
21. **Custom schema + DTO para alto volumen:** Cuando una entidad requiere tipos de columna no soportados por Entity API (MEDIUMBLOB, VARBINARY) o alto volumen de escrituras, usar `hook_schema()` + DTO readonly. El DTO encapsula filas, el servicio maneja CRUD via `\Drupal::database()`.
22. **Cascade para ConfigEntities vertical+tier:** Cuando features o limites dependen de vertical y tier, usar patron cascade: especifico ({vertical}_{tier}) → default (_default_{tier}) → NULL. Un servicio broker central (PlanResolverService) encapsula la logica. Los consumidores solo llaman al broker, nunca implementan el cascade.
23. **Normalizacion de planes via aliases:** Los nombres de plan de cualquier fuente externa (Stripe, migrations, APIs) DEBEN normalizarse a tier keys canonicos via aliases editables en ConfigEntity. Nunca hardcodear mapeos de nombres. `PlanResolverService::normalize()` es el punto unico de normalizacion.
24. **Sequence para dynamic keys en config schema:** Cuando un campo de ConfigEntity tiene keys que varian por vertical o contexto, usar `type: sequence` (no `type: mapping` con keys fijos) en el schema YAML. `mapping` lanza `SchemaIncompleteException` para cualquier key no declarado explicitamente.
25. **Identidad IA inquebrantable:** Todo agente, copiloto o servicio IA conversacional DEBE identificarse como "Asistente de Jaraba Impact Platform" (o el nombre del vertical). NUNCA revelar el modelo subyacente (Claude, ChatGPT, Gemini, etc.). La regla se inyecta en `BaseAgent.buildSystemPrompt()` como parte #0 y en `CopilotOrchestratorService.buildSystemPrompt()` como `$identityRule`. Los servicios standalone (FaqBotService, CoachIaService, ServiciosConectaCopilotAgent) la anteponen manualmente.
26. **Aislamiento de competidores en IA:** Ningun prompt de IA DEBE mencionar, recomendar ni referenciar plataformas competidoras ni modelos de IA externos. Si el usuario menciona un competidor, la IA redirige a funcionalidades equivalentes de Jaraba. Los datos de dominio (recommendations, quick_wins) DEBEN referenciar herramientas de Jaraba, no de terceros. Excepcion: integraciones reales (LinkedIn import, Meta Pixel) donde la plataforma es canal de distribucion.
27. **Sticky header por defecto en frontend:** El `.landing-header` DEBE usar `position: sticky` como default global. Solo las landing pages con hero fullscreen (`body.landing-page`, `body.page-front`) lo overriden a `position: fixed`. Las areas de contenido NUNCA compensan con `padding-top` para header fijo — solo padding estetico (`1.5rem`). El ajuste de toolbar admin (`top: 39px/79px`) se aplica una unica vez en el SCSS del header.
28. **Mensajes obligatorios en templates de formulario:** Cuando un modulo define un `#theme` custom para renderizar un formulario, el hook_theme DEBE declarar variable `messages`, el preprocess DEBE inyectar `['#type' => 'status_messages']`, y el template DEBE renderizar `{{ messages }}` antes de `{{ form }}`. Sin esto, los errores de validacion server-side se pierden silenciosamente (la validacion HTML5 del browser oculta el problema). Las paginas legales/informativas DEBEN usar URLs canonicas en espanol (`/politica-privacidad`, `/terminos-uso`, `/politica-cookies`) y contenido editable desde theme settings via `theme_get_setting()`.
29. **Field UI settings tab obligatorio:** Toda entidad con `field_ui_base_route` DEBE tener un default local task tab en `links.task.yml` donde `route_name == base_route == field_ui_base_route`. Sin este tab, Field UI no puede montar las pestanas "Administrar campos" / "Administrar visualizacion de formulario". Verificar tras crear entidades con `\Drupal::service('plugin.manager.menu.local_task')->getLocalTasksForRoute()`.
30. **Kernel tests: dependencias explicitas de modulos:** `KernelTestBase::$modules` NO auto-resuelve dependencias de modulos. Listar TODOS los modulos requeridos por los field types de la entidad (`datetime`, `text`, `options`, `taxonomy`) e instalar schemas de entidades referenciadas (ej. `taxonomy_term`) ANTES del schema de la entidad bajo test. Patron: `$this->installEntitySchema('taxonomy_term');` antes de `$this->installEntitySchema('order_agro');`.
31. **Cross-module services opcionales con @?:** Servicios que referencian otros modulos (que pueden no estar instalados) DEBEN usar `@?` en services.yml y constructores nullable (`?Type $param = NULL`). El codigo DEBE null-guard antes de usar el servicio. Critico para testabilidad con Kernel tests que solo habilitan un modulo.
32. **jaraba_icon() convencion estricta y zero chinchetas:** Toda llamada a `jaraba_icon()` DEBE seguir la firma `jaraba_icon('category', 'name', { variant: 'duotone', color: 'azul-corporativo', size: '24px' })`. Antes de crear un template nuevo, verificar que los pares category/name existen como SVGs en `ecosistema_jaraba_core/images/icons/{category}/`. Si falta un icono, crear un symlink en la bridge category correspondiente apuntando a una categoria primaria (actions, fiscal, media, micro, ui, users). Verificar con `find images/icons/ -type l ! -exec test -e {} \; -print` que no hay symlinks rotos. El objetivo es 0 chinchetas (📌) en toda la plataforma.
33. **Auditorias horizontales periodicas:** Despues de completar auditorias verticales, ejecutar siempre una auditoria horizontal que revise flujos cross-cutting: access handlers (strict equality), plantillas de email (CAN-SPAM, colores de marca, font, preheader, postal), CSRF, permisos. Los bugs sistematicos no se descubren auditando un solo vertical — requieren vision transversal. Al scaffoldear plantillas desde un template base, usar tokens de marca desde el dia 0 para evitar deuda multiplicada.
34. **PathProcessor para aliases custom de entidad:** Cuando una entidad ContentEntity tiene un campo `path_alias` propio, DEBE existir un `InboundPathProcessorInterface` con prioridad 200+ que resuelva el alias a la ruta canonica de la entidad. No filtrar por status en la query (el AccessControlHandler gestiona permisos). Usar skip list de prefijos de sistema y static cache. El procesador se registra en services.yml con tag `path_processor_inbound`.
35. **TenantBridgeService para resolucion cross-entity:** Todo servicio que necesite resolver entre Tenant y Group DEBE usar `TenantBridgeService` (`@ecosistema_jaraba_core.tenant_bridge`). NUNCA cargar `getStorage('group')` con Tenant IDs ni viceversa. Los IDs de Tenant y Group NO son intercambiables. Tenant = billing ownership, Group = content isolation. Los 4 metodos del bridge (`getTenantForGroup`, `getGroupForTenant`, `getTenantIdForGroup`, `getGroupIdForTenant`) son el unico punto de cruce autorizado.
36. **Tenant isolation en AccessControlHandlers:** Todo access handler de entidades con campo `tenant_id` DEBE implementar `EntityHandlerInterface` para DI, inyectar `TenantContextService`, y verificar `isSameTenant()` para `update`/`delete`. Las paginas publicadas (`view`) son publicas. `TenantContextService::getCurrentTenantId()` retorna `?int` (NULL = sin acceso). Al renombrar handlers, actualizar la referencia `access` en la anotacion `@ContentEntityType`.
37. **CI pipeline con Kernel tests obligatorios:** El CI DEBE ejecutar Unit + Kernel tests. El job `kernel-test` requiere MariaDB (10.11) con BD `drupal_test`. Cuando se corrige un entity type key en codigo (ej. `getStorage('group')` → `getStorage('tenant')`), actualizar los `->with(...)` en mocks de tests. Los Kernel tests validan schemas, queries y DI real — no son opcionales.
38. **Premium entity forms con secciones:** Formularios de entidad con muchos campos DEBEN agruparse en secciones (constante `SECTIONS` con label, icon, fields, open). Campos internos en `HIDDEN_FIELDS`. Para multiples entity types, crear `PremiumEntityFormBase` abstract. Los entity annotations `form.default`/`add`/`edit` DEBEN apuntar al form class premium. **4 patrones de migracion:** (A) Simple — solo `getSectionDefinitions()` + `getFormIcon()`, sin DI extra. (B) Computed Fields — campos auto-calculados con `#disabled = TRUE`, nunca `#access = FALSE` (el admin debe verlos). (C) DI — usar `parent::create($container)` para preservar las dependencias de la base, luego anadir las propias. (D) Custom Logic — override de `buildForm()`/`save()` llamando a `parent::` para mantener secciones y glass-card UI. **Checklist de migracion:** (1) Cambiar `extends ContentEntityForm` a `extends PremiumEntityFormBase`, (2) implementar `getSectionDefinitions()` con categorias de icono, (3) implementar `getFormIcon()`, (4) mover fieldsets/details a secciones, (5) marcar campos computados con `#disabled`, (6) verificar `parent::save()` con redirect a collection, (7) `grep -rl "extends ContentEntityForm" | grep -v PremiumEntityFormBase` → 0 resultados. **Pitfalls:** fieldsets rompen navigation pills; DI sin `parent::create()` pierde entity_type.manager; `#access = FALSE` oculta completamente el campo (usar `#disabled`); olvidar HIDDEN_FIELDS expone campos internos (uid, created, changed).
39. **Slide-panel vs Drupal modal:** Distinguir XHR de slide-panel (sin `_wrapper_format`) de Drupal dialog (con `_wrapper_format`). Solo retornar bare HTML para slide-panel. Patron: `isSlidePanelRequest() = isXmlHttpRequest() && !has('_wrapper_format')`.
40. **Meta-site rendering tenant-aware:** Cuando una pagina pertenece a un meta-sitio, override title, Schema.org, header/footer/nav, logo desde SiteConfig via `MetaSiteResolverService::resolveFromPageContent()`. SitePageTree status = `1` (int), no `'published'` (string).
41. **Migracion de field types con update hooks:** Para cambiar tipo de campo: backup datos → uninstall old field → install new desde `baseFieldDefinitions()` → restore datos. Para reinstalar entidades vacias: `uninstallEntityType()` + `installEntityType()`. Siempre try/catch en restauracion.
42. **Migracion global a PremiumEntityFormBase:** 237 formularios migrados en 8 fases (50 modulos). Verificar migracion completa con `grep -rl "extends ContentEntityForm" web/modules/custom/*/src/Form/ | grep -v PremiumEntityFormBase | grep -v SettingsForm` → 0 resultados. Iconos de seccion usan categorias del icon system (`ui`, `actions`, `fiscal`, `users`, etc.) con variante duotone. Redirect post-save DEBE ser a la ruta `collection` de la entidad (no a canonical ni al form de edicion). El handler USR-004 de redirect de user edit form es un caso especial que redirige a `entity.user.canonical` (la entidad User no usa PremiumEntityFormBase).
43. **Preprocess obligatorio para custom entities:** Toda ContentEntity que se renderice en view mode DEBE tener `template_preprocess_{entity_type}()` que extraiga datos en array estructurado para Twig. Sin este preprocess, los templates no acceden a datos de la entidad. Extraer: valores primitivos, entidades referenciadas (category, author), imagenes responsive con ImageStyle + srcset. Ejemplo canonico: `template_preprocess_content_article()` en `jaraba_content_hub.module`.
44. **Resiliencia en presave hooks:** Los hooks presave que invoquen servicios opcionales (sentiment, reputation, pathauto) DEBEN usar `\Drupal::hasService()` + try-catch. El save de la entidad NUNCA debe fallar por un servicio opcional. Patron: check → try → catch(\Throwable) → log warning → continue. Aplicado en `jaraba_content_hub_content_article_presave()`.
45. **Remediacion IA integral con plan estructurado:** Cuando se detectan multiples problemas en el stack IA (identidad, guardrails, routing, observabilidad, streaming), crear un plan de remediacion con FIX-IDs priorizados (P0/P1/P2) y ejecutarlos en fases. Cada FIX DEBE: (1) centralizar logica duplicada en clases/servicios reutilizables (ej. `AIIdentityRule::apply()`), (2) mover configuracion hardcodeada a YAML config (ej. model pricing), (3) conectar observabilidad en todos los puntos de ejecucion IA (input/output tokens, duration, success), (4) usar regex bilingues EN+ES para plataformas hispanohablantes, (5) documentar generaciones de agentes (@deprecated para Gen 0, @note para Gen 1), (6) validar con `php -l` + `yaml.safe_load()` antes de commit. Patron de PII: cada mercado geografico DEBE anadir sus patrones al guardrail (DNI/NIE/IBAN para Espana).
46. **Meta-site nav requiere header partial + classic layout:** Cuando la navegacion de un meta-sitio no aparece, verificar 2 cosas: (1) El page template (`page--page-builder.html.twig`) DEBE incluir `_header.html.twig` cuando `meta_site` es truthy — el header inline hardcodeado solo muestra logo+acciones sin nav. (2) El SiteConfig `header_type` DEBE ser `classic` (no `minimal`) — el layout minimal solo muestra hamburguesa sin nav horizontal. La cadena completa es: `theme_preprocess_page()` → `resolveFromPageContent()` → override `navigation_items` → `_header.html.twig` → parse → `_header-classic.html.twig` renderiza `<nav>`. Debug: verificar `$variables['theme_settings']['navigation_items']` en preprocess y `header_type` en BD (`SELECT header_type FROM site_config`).
48. **Conectar infraestructura IA existente antes de construir nueva:** Cuando el stack IA tiene piezas desconectadas (ToolRegistry con tools pero sin agentes que los usen, QualityEvaluator implementado pero sin pipeline que lo llame, AgentOrchestrator con estado pero sin ejecucion LLM), los mayores wins vienen de CONECTAR piezas existentes, no de construir desde cero. Patron: auditar servicios existentes con `grep -rn 'class.*Service' | grep -v Test` y verificar cuales tienen metodos publicos que nadie llama (dead code funcional). Inyectar opcionalmente (`@?`) en los consumidores, crear bridges donde los modulos tienen responsabilidades solapantes (estado vs ejecucion). Ejemplo: ToolRegistry (3 tools) → inyectar en SmartBaseAgent → `callAiApiWithTools()` loop iterativo. AgentOrchestrator (estado) → bridge → SmartBaseAgent (ejecucion).
49. **Circuit breaker + fallback chain para providers IA:** Nunca depender de un unico provider LLM. Usar `ProviderFallbackService` con circuit breaker: 3 fallos en 5 minutos = skip provider (estado OPEN), cascada por tier (primary → fallback → emergency). Config en YAML (`provider_fallback.yml`) para cambiar providers sin code deploy. Estado en Drupal State API (`provider_circuit:{provider_id}`). Cooldown de 5 minutos antes de volver a probar (HALF_OPEN). Verificar con: revisar State API entries y logs de observabilidad por provider_id.
50. **Auditoria post-implementacion obligatoria para cambios batch:** Despues de implementar multiples FIX items o cambios batch, ejecutar auditoria sistematica de 4 dimensiones: (1) **Registro de servicios** — verificar con `grep -rn 'class.*Service' modules/custom/` que todo servicio PHP tiene entrada en `services.yml` del modulo correspondiente. (2) **Contratos de llamada** — para cada servicio nuevo, buscar todos los callers con `grep -rn 'serviceName->methodName'` y verificar que el numero, orden y tipo de argumentos coincide exactamente con la firma del metodo. Un `hasService()` + `try-catch` NO protege contra errores de firma — el servicio se resuelve pero explota con `TypeError`. (3) **Schemas de config** — todo `config/install/*.yml` DEBE tener schema correspondiente en `config/schema/*.schema.yml`. Per CONFIG-SCHEMA-001, la ausencia causa `SchemaIncompleteException` en Kernel tests. (4) **Config completitud** — verificar que los servicios que leen `$config->get('section.key')` tienen esa seccion definida en el YAML de instalacion. Caso real: `JarabaRagService::reRankResults()` leia `reranking.strategy` pero el YAML no tenia seccion `reranking`, causando fallback silencioso a `'keyword'`.
51. **Shared library dependency para Canvas Editor cross-module:** Cuando un modulo necesita reutilizar un engine JS complejo de otro modulo (ej. GrapesJS del Page Builder en Content Hub), declarar library dependency en `.libraries.yml` y crear un bridge JS ligero que reconfigure el engine (StorageManager, endpoints API, contexto). NUNCA copiar el engine ni sus plugins. El bridge: (1) espera a que el engine se inicialice (polling `window.engineInstance`, max 50 intentos), (2) registra un StorageManager custom apuntando a endpoints propios, (3) carga datos iniciales via API, (4) configura auto-save y undo/redo. Los endpoints API del modulo consumidor DEBEN replicar los mismos sanitizers del modulo fuente (HTML y CSS) para mantener paridad de seguridad. El campo `layout_mode` permite bifurcacion de renderizado (legacy/visual) sin romper retrocompatibilidad. Patron: `ContentArticle.layout_mode` = legacy|canvas, preprocess extrae ambos paths, template usa `{% if %}` para bifurcar, CSS del canvas se inyecta via `html_head` style tag.
52. **Streaming real via PHP Generator + SSE:** El `StreamingOrchestratorService` extiende `CopilotOrchestratorService` para acceder a metodos protegidos de setup (modo, contexto, cache). Usa `ChatInput::setStreamedOutput(TRUE)` para streaming real del provider LLM. El metodo `streamChat()` retorna un PHP Generator que yield-ea eventos tipados: `{type: 'chunk', text, index}`, `{type: 'cached', text}` (respuesta completa desde cache), `{type: 'done', tokens, log_id}`, `{type: 'error', message}`. El controller consume el Generator con `foreach` y emite SSE events. Buffer de 80 chars o sentence boundary para chunks legibles (evita tokens sueltos de 1-2 chars). PII masking incremental: `maskBufferPII()` aplica sobre buffer acumulado para detectar PIIs que cruzan boundaries de chunks. La inyeccion en controllers es via `$container->has()` (no `@?` que es para services.yml). Fallback automatico si el servicio no esta disponible: `handleBufferedStreaming()` preserva el comportamiento original.
53. **Native function calling via Drupal AI module:** El metodo `callAiApiWithNativeTools()` en SmartBaseAgent usa la API nativa del modulo Drupal AI en lugar de inyectar XML en el system prompt. `ToolRegistry::generateNativeToolsInput()` convierte las herramientas al formato `ToolsInput > ToolsFunctionInput > ToolsPropertyInput`. `ChatInput::setChatTools(ToolsInput)` pasa las tools a nivel de API. Las respuestas de tool calls se leen via `ChatMessage::getTools()` → `ToolsFunctionOutputInterface[]` con `getName()` y `getArguments()` parseados nativamente por el provider. El loop iterativo es max 5 iteraciones (identico al text-based). Fallback automatico: si `callAiApiWithNativeTools()` falla por excepcion, cae a `callAiApiWithTools()` (text-based). Ambos metodos coexisten en SmartBaseAgent — el nativo es preferido cuando el provider lo soporta.
54. **MCP Server con JSON-RPC 2.0 dispatch:** El `McpServerController` expone las herramientas del ToolRegistry a clientes MCP externos via un unico endpoint `POST /api/v1/mcp`. El dispatch usa `match()` por metodo JSON-RPC: `initialize` (handshake con protocolVersion y capabilities), `tools/list` (descubrimiento via ToolRegistry::getAll() con JSON Schema inputSchema), `tools/call` (ejecucion via ToolRegistry::execute() con PII sanitization), `ping` (health check). Requiere permiso `use ai agents` + CSRF token. Los error codes siguen JSON-RPC: -32700 (parse error → 400), -32600 (invalid request → 400), -32601 (method not found → 404), -32602 (invalid params → 422), -32603 (internal → 500). `buildInputSchema()` convierte tool params a JSON Schema con type, description, required, default.
55. **Distributed tracing con trace_id para correlacion:** El `TraceContextService` genera un `trace_id` (UUID) por request y `span_id` por operacion. Se propaga a todos los servicios involucrados en un request SSE/API: `AIObservabilityService::log()`, `CopilotCacheService`, `AIGuardrailsService`, `ToolRegistry::execute()`. El SSE `done` event incluye el trace_id para que el frontend pueda correlacionar. Permite reconstruir el flujo completo de un request buscando logs por trace_id.
47. **Copilot sugerencias con URL action buttons:** Las sugerencias del copilot soportan formato dual: strings planos (se envian como mensaje) y objetos `{label, url}` (se renderizan como `<a>` links con estilo CTA). El backend `CopilotOrchestratorService::getContextualActionButtons()` genera CTAs contextuales por rol/modo. El JS normaliza ambos formatos: `typeof s === 'string' ? { label: s } : s`. Los links con URL llevan clase `--link` (fondo naranja, font-weight 600). Links externos detectados por `item.url.indexOf('http') === 0 && item.url.indexOf(window.location.hostname) === -1` llevan `target="_blank"`. Ambas implementaciones (v1 contextual-copilot.js, v2 copilot-chat-widget.js) DEBEN soportar el formato.
61. **Gestión de secretos en config/sync/ via $config overrides:** Los ficheros `config/sync/` NUNCA contienen secretos reales. Los YAML DEBEN tener valores vacíos (`''`) para campos sensibles (client_secret, contraseñas, API keys). Los secretos se cargan en runtime via `$config` overrides en `config/deploy/settings.secrets.php`, que lee `getenv()`. Patron: `if ($var = getenv('VAR_NAME')) { $config['module.settings']['key'] = $var; }`. `settings.php` incluye `settings.secrets.php` antes de `settings.local.php`. `.env` local (gitignored) para desarrollo; variables de entorno del hosting para producción. `drush config:export` NUNCA exporta overrides de `$config` (son runtime-only). Si se detectan secretos en git: (1) sanitizar YAML con valores vacíos, (2) crear/actualizar `settings.secrets.php`, (3) limpiar historial con `git-filter-repo --blob-callback` (usar Python subprocess para contraseñas con caracteres especiales `$%[{}`), (4) force push, (5) rotar credenciales expuestas.
56. **Analytics condicionado con meta_site en page template:** Todas las librerias de analytics (metasite-tracking, metasite-experiments, jaraba_heatmap/tracker) DEBEN cargarse SOLO dentro de `{% if meta_site %}` en `page--page-builder.html.twig`. El GTM/GA4 se inyecta a nivel de `html.html.twig` (global). NUNCA cargar analytics de meta-sitio en paginas de dashboard o admin. El `dataLayer` es el bus de eventos compartido entre las 4 capas. Verificar con DevTools que las librerias se cargan solo en las paginas correctas.
57. **GTM con Consent Mode v2 para cumplimiento GDPR:** El snippet de GTM DEBE inicializar Consent Mode v2 con `gtag('consent', 'default', { ad_storage: 'denied', analytics_storage: 'denied', wait_for_update: 500 })` ANTES del script GTM. Esto asegura cumplimiento RGPD/GDPR sin banner de cookies inicial (opt-in posterior via CMP). Los IDs de GTM/GA4 DEBEN ser configurables via `theme_get_setting()`, NUNCA hardcodeados. Si se cambia de container, solo se actualiza theme settings sin code deploy.
58. **REST APIs publicos con integracion CRM/email opcional:** Los endpoints publicos (`/api/v1/public/*`) que acepten datos de formularios web DEBEN: (1) validar campos requeridos con tipo y longitud, (2) implementar rate limiting via Flood API (5/min por IP), (3) persistir en tabla propia (contact_submissions, analytics_events), (4) integrar CRM y email como servicios OPCIONALES (`\Drupal::hasService()` + try-catch) — un fallo en CRM o email NUNCA debe impedir la persistencia del submit. `hook_mail()` despacha por message key: `contact_notification` envia MJML con datos del formulario, `onboarding_*` envia secuencias de bienvenida. Los indices de BD DEBEN incluir UTM fields (source, medium, campaign) para analytics posterior.
59. **A/B testing server-side via hook_preprocess_page() Layer 4:** Cuando las variantes A/B necesitan rendering server-side (no solo cambios DOM via JS), usar `hook_preprocess_page()` como punto de inyeccion siguiendo el patron Layer 4 de la arquitectura de theming. El hook lee `ecosistema_jaraba_core.ab_tests` config, filtra tests `enabled: true`, determina variante activa, inyecta en `$variables['ab_variants']`, y registra impresiones. Los templates usan cascada `{{ ab_variants.test_id.label|default(fallback) }}` para degradar gracefully cuando el test no esta activo — el template siempre renderiza algo valido. Este patron complementa el A/B frontend (cookies + DOM manipulation via `metasite-experiments.js`): frontend para cambios visuales, backend para cambios de contenido/logica.
60. **Deploy checklist reproducible en docs/operaciones/:** Todo deploy a produccion DEBE tener un checklist documentado en `docs/operaciones/deploy_checklist_{proveedor}.md` con 7 secciones: pre-deploy (backup, tests, tag), stack (PHP, MariaDB, Redis, Composer), DNS (registros por dominio), SSL (Let's Encrypt auto-renewal), post-deploy (drush updatedb+config:import+cache:rebuild), verificacion (HTTP, funcionalidades, analytics), rollback. Un deploy sin checklist documentado es un deploy no reproducible. El primer deploy de un proveedor nuevo DEBE crear su checklist antes de ejecutar cualquier paso.
61. **Traduccion batch de meta-sitios con AITranslationService:** Para traducir las PageContent de meta-sitios a multiples idiomas, usar un script drush que: (1) verifica idiomas configurados con `$languageManager->getLanguages()`, (2) carga todas las PageContent por tenant_id, (3) para cada pagina verifica `$entity->hasTranslation($lang)` para evitar duplicados, (4) crea traduccion nativa con `$entity->addTranslation($langcode)`, (5) traduce campos simples (title, meta_title, meta_description) con `AITranslationService::translateBatch()`, (6) traduce `canvas_data` parseando JSON GrapesJS y recorriendo components[].content recursivamente, (7) traduce `rendered_html` extrayendo nodos de texto con regex `/>([^<]+)</`, traduciendo en batch, reinsertando, (8) traduce `content_data` recorrriendo recursivamente el JSON con skip patterns (ID, URL, color, image, icon), (9) salva con `$page->save()`. Verificar invariante: IDs de campos que NO deben traducirse (uuid, template_id, tenant_id, layout_mode, status).
62. **Variables de idioma dinamicas via preprocess_html para meta-sitios:** Las variables `available_languages` y `current_langcode` se inyectan en `preprocess_html()` solo cuando hay una PageContent en el route match (context meta-sitio). Usar `$page_content->getTranslationLanguages()` que retorna un array indexado por langcode con todas las traducciones creadas (incluyendo la original). Esto alimenta 2 consumidores: `_hreflang-meta.html.twig` (genera `<link rel="alternate">` solo para idiomas con traduccion real) y `_language-switcher.html.twig` (renderiza dropdown solo si hay >1 idioma). NUNCA hardcodear idiomas en templates — siempre derivar de las traducciones reales de la entidad.
63. **Trait transversal para normalizar entidades heterogeneas:** Cuando multiples verticales implementan el mismo concepto (reviews, ratings, comments) con nombres de campos diferentes, crear un PHP Trait que defina campos canonicos compartidos y helpers con fallback (`hasField()` + getter encadenado). Esto permite servicios transversales (moderacion, agregacion, Schema.org) que operan sobre cualquier vertical sin conocer la implementacion interna. Patron: `ReviewableEntityTrait` con `getReviewStatusValue()` intenta review_status, status, state. Migracion gradual: update hooks para renombrar campos al nombre canonico.
64. **Schema.org AggregateRating obligatorio en paginas de reviews:** Toda pagina que muestre un resumen de calificaciones (producto, proveedor, mentor, curso) DEBE inyectar JSON-LD `AggregateRating` via `#attached['html_head']` — NUNCA hardcodear en template Twig. Centralizar en `ReviewSchemaOrgService` para todos los verticales. Verificar con Google Rich Results Test.
65. **Auditoria comparativa cross-vertical antes de consolidar:** Cuando la plataforma tiene N implementaciones del mismo concepto, auditar comparativamente: tabla de campos, nomenclatura, tenant handling, access control, servicios, templates, rutas, Schema.org. Clasificar hallazgos en S (seguridad), B (bugs), A (arquitectura), D (directrices), G (brechas). El plan prioriza: seguridad → trait → servicios compartidos → frontend → Schema.org → entidades nuevas.
66. **Entity lifecycle hooks con hasService + try-catch para servicios opcionales:** Las reviews y entidades con hooks de ciclo de vida (presave, insert, update) que invoquen servicios de otros modulos DEBEN usar `\Drupal::hasService()` + try-catch para cada servicio. Patron presave: enrichment (sentiment, fake detection). Patron insert: dispatch (aggregation, notification, gamification, webhook) — cada uno en su propio try-catch. Patron update: detectar cambios via `$entity->original`.
67. **Servicios AI siempre opcionales (@?) en services.yml:** Todo servicio cross-module de AI (`@ai.provider`, `@jaraba_ai_agents.*`) DEBE inyectarse con `@?` en services.yml. Constructores PHP nullable + null-guard. Agentes que heredan de BaseAgent (non-null) DEBEN condicionar `parent::__construct()`. Previene `ServiceNotFoundException` en Kernel tests.
68. **Banda ecosistema en footer con JSON configurable por tenant:** Para navegacion cross-domain en ecosistemas multi-sitio, usar SiteConfig con `ecosystem_footer_enabled` (boolean) + `ecosystem_footer_links` (JSON array `[{name, url, label, current}]`). El flag `current: true` marca el sitio actual (bold, sin link). Links entre dominios propios con `rel="noopener"` (sin nofollow para preservar autoridad SEO). La banda se renderiza entre copyright/social y "Funciona con" en `_footer.html.twig`. El SaaS principal NO muestra la banda (ecosystem_footer_enabled = FALSE por default). NUNCA usar `|default()` para booleans en Twig — usar ternario `(is defined) ? value : fallback`.
69. **Schema.org dinamico Person/Organization con sameAs ecosistema:** Cada meta-sitio genera JSON-LD via `_meta-site-schema.html.twig` con tipo determinado por `meta_site.schema_type` en `preprocess_page()`. Marca personal = `Person`, empresa/plataforma = `Organization`. Array `sameAs` con URLs de los otros dominios del ecosistema (excluyendo el actual). Auth actions separadas del CTA en header: `header_show_auth` boolean en SiteConfig controla login/registro, el CTA configurable permanece siempre visible. Sitios brochure DEBEN tener `header_show_auth = FALSE`.

---

## 6. Patron Elevacion Vertical a Clase Mundial

Workflow reutilizable de 14 fases para elevar un vertical a clase mundial (probado con Empleabilidad, Emprendimiento, Andalucia+ei, JarabaLex, AgroConecta, ComercioConecta, ServiciosConecta):

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

## 8. Patron Sprint Entidades Masivo (ENTITY-BATCH-001)

Para verticales commerce con 20+ entidades nuevas (como ComercioConecta Sprint 2-3), extender el patron de elevacion con sprints de entidades adicionales:

### Estrategia de paralelizacion por tipo de artefacto

| Agente | Tipo | Artefactos |
|--------|------|-----------|
| A | Entidades | `src/Entity/*.php` — Content Entities con annotations, baseFieldDefinitions |
| B | Access+Forms+ListBuilders | `src/Access/*Handler.php` + `src/Form/*Form.php` + `src/ListBuilder/*.php` |
| C | Services | `src/Service/*Service.php` — logica de negocio |
| D | Controllers+Templates | `src/Controller/*.php` + `templates/*.html.twig` |

### Ficheros compartidos (post-agente, secuencial)

1. `{modulo}.services.yml` — registrar todos los servicios nuevos
2. `{modulo}.routing.yml` — registrar todas las rutas
3. `{modulo}.permissions.yml` — registrar todos los permisos
4. `{modulo}.links.task.yml` + `.links.menu.yml` — registrar admin tabs
5. `{modulo}.module` — actualizar hook_theme() + hook_preprocess_html() + hook_cron()
6. `{modulo}.install` — crear hook_update_NNNNN() para instalar schemas de nuevas entidades
7. `{modulo}.libraries.yml` — registrar nuevas libraries JS/CSS
8. `scss/main.scss` — @use nuevos partials SCSS

### Regla ENTITY-BATCH-INSTALL-001

Al anadir multiples entidades a un modulo existente, crear un unico `hook_update_NNNNN()` que itere sobre la lista de entity_type_ids, verifique existencia con `getEntityType()`, y solo instale si no existe.

---

## 9. Registro de Cambios

| Fecha | Version | Descripcion |
|-------|---------|-------------|
| 2026-02-27 | **42.0.0** | **Reviews & Comments Clase Mundial — ReviewableEntityTrait + Schema.org AggregateRating + 10 Verticales Workflow:** 4 patrones nuevos: ReviewableEntityTrait para normalizar entidades heterogeneas (5 campos compartidos + helpers con fallback hasField()+getter encadenado para nomenclatura inconsistente status/state/review_status, permite servicios transversales sin conocer implementacion interna), Schema.org AggregateRating obligatorio (JSON-LD via html_head no hardcoded en Twig, ReviewSchemaOrgService centralizado, campos ratingValue/reviewCount/bestRating/worstRating, Review nested con author/datePublished, verificar con Rich Results Test), moderacion centralizada (ReviewModerationService con moderate/autoApproveIfEligible/bulk/cola, reviews nunca publicadas sin moderacion, rating<=2 siempre manual), auditoria comparativa cross-vertical (tabla campos/nomenclatura/tenant/access/servicios/templates/rutas/Schema.org, clasificacion S/B/A/D/G, prioridad seguridad→trait→servicios→frontend→Schema.org→entidades nuevas). Auditoría de 4 sistemas heterogéneos con 20 hallazgos. Plan con cobertura 10 verticales canonicos. Reglas de oro #63 (trait transversal), #64 (Schema.org AggregateRating), #65 (auditoria comparativa cross-vertical). Aprendizaje #140. |
| 2026-02-26 | **40.0.0** | **Remediación de Secretos — SECRET-MGMT-001 + git-filter-repo Workflow:** 1 patrón nuevo: gestión de secretos en config/sync/ via $config overrides (YAML sanitizados con valores vacíos, settings.secrets.php con 14 getenv() → $config overrides para OAuth Google/LinkedIn/Microsoft + SMTP IONOS + reCAPTCHA v3 + Stripe, settings.php include chain, .env local gitignored + .env.example documentado, Lando env_file injection, git-filter-repo --blob-callback para limpieza de historial con Python subprocess para contraseñas con caracteres especiales $%[{}). Regla de oro #61 (gestión de secretos via $config overrides runtime-only). Aprendizaje #138. |
| 2026-02-26 | **40.0.0** | **Meta-Sitios Multilingüe — i18n EN+PT-BR + Language Switcher + Hreflang Dinámico Workflow:** 2 patrones nuevos: traduccion batch de meta-sitios con AITranslationService (script drush que verifica idiomas configurados, carga PageContent por tenant_id, crea traducciones nativas con addTranslation(), traduce campos simples con translateBatch(), canvas_data con parsing GrapesJS recursive components[].content, rendered_html con regex text nodes batch, content_data con JSON recursive skip patterns para IDs/URLs/colores, path_alias con transliteracion + slugify, 46 traducciones creadas 0 errores), variables de idioma dinamicas via preprocess_html (available_languages + current_langcode inyectados desde getTranslationLanguages(), alimentan hreflang dinamico + language switcher condicional, NUNCA hardcodear idiomas en templates). 6 archivos nuevos: translate-metasite-pages.php, _language-switcher.html.twig, _language-switcher.scss, language-switcher.js, libreria en libraries.yml. 4 archivos modificados: _hreflang-meta.html.twig, _header-classic.html.twig, _header.html.twig, .theme. PT-BR: 12.038 traducciones Drupal importadas. Reglas de oro #61 (traduccion batch con AITranslationService), #62 (variables idioma dinamicas). Aprendizaje #139. |
| 2026-02-26 | **39.0.0** | **REST APIs + A/B Backend + Deploy Stack (Sprints 5–7) Workflow:** 3 patrones nuevos: REST API publico con integracion opcional CRM/email (ContactApiController con Flood rate limit 5/min, validacion por campo, DB persistencia, `jaraba_crm.lead_service` + `hook_mail()` contact_notification/onboarding_* como servicios opcionales hasService()+try-catch, tabla analytics_events con UTM indexes auto-create), A/B testing server-side via hook_preprocess_page Layer 4 (inyeccion $variables['ab_variants'] desde ecosistema_jaraba_core.ab_tests YAML config, impression tracking, Twig cascada |default(), complementa frontend cookie-based metasite-experiments.js), deploy checklist reproducible (docs/operaciones/deploy_checklist_ionos.md con 7 secciones: pre-deploy+stack+DNS+SSL+post-deploy+verificacion+rollback). Reglas de oro #58 (REST APIs con CRM/email opcional), #59 (A/B backend Layer 4), #60 (deploy checklist reproducible). Aprendizaje #137. |
| 2026-02-26 | **38.0.0** | **Auditoria IA Clase Mundial — 25/25 GAPs Implementados Workflow**: 8 patrones nuevos: demand forecasting AI (DemandForecastingService con 90 dias historico desde ComercioAnalyticsService → prompt estructurado balanced tier → JSON parse → fallback lineal confidence 0.4, getStockRecommendations delega a 14-day forecast), design system live documentation (ComponentDocumentationController escanea scss/components/_*.scss dinamicamente, render array con #theme, template Twig con swatches/specimens/bars/cards/grid, design-system.scss con dark mode), cost attribution per tenant (ObservabilityApiController wiring con CostAlertService + TenantMeteringService via $container->has(), endpoint /cost-attribution agrupa stats+trend+savings+budget_alerts+metering), horizontal scaling AI (ScheduledAgentWorker QueueWorker id=scheduled_agent cron 300s, 4 task types con AIIdentityRule+observability, supervisor-ai-workers.conf 4 programas con log rotation, settings.ai-queues.php Redis routing), multimodal vision integration (CopilotStreamController detecta multipart/form-data, extrae UploadedFile, valida 10MB, delega a MultiModalBridgeService::analyzeImage(), enriquece context con image_analysis, copilot-chat-widget.js con hidden file input + image button + FormData), A2A protocol lifecycle (AgentCardController GET /.well-known/agent.json, A2ATaskController con submit/status/cancel, ATaskEntity con status transitions, Bearer+HMAC auth, rate limit 100/h), service matching GPS (ServiceMatchingService con Haversine formula, radio 50km configurable, scoring hibrido distancia+valoracion+disponibilidad), verificacion sistematica post-implementacion (7 tareas secuenciales: fix issues → YAML lint → PHP lint → PHPUnit tests → SCSS compile → docs update → git commit, 3.182 tests / 11.553 assertions como gate de calidad). Regla de oro #55 (implementacion exhaustiva de auditoria comparativa con verificacion 0-defectos). Aprendizaje #136. |
| 2026-02-26 | **37.0.0** | **Meta-Sitios Analytics Stack — GTM/GA4 + A/B Testing + Hreflang + Heatmap Workflow**: 5 patrones nuevos: GTM injection con Consent Mode v2 GDPR (_gtm-analytics.html.twig con defaults conservadores, container ID desde theme_settings, GA4 standalone fallback, dataLayer context push con meta_site/tenant_id/user_type), A/B testing frontend con cookies (metasite-experiments.js con cookie 30d jb_exp_{id}, asignacion ponderada, DOM manipulation por change.type, impression API, dataLayer experiment_view/conversion, data-experiment-goal), hreflang SEO internacional (_hreflang-meta.html.twig con es/en/x-default en head), heatmap activation (jaraba_heatmap/tracker attached en page--page-builder con condicion meta_site), stack analytics unificado (4 capas condicionadas con {% if meta_site %} en page template, dataLayer como bus de eventos compartido). PWA preexistente verificada (manifest.json + sw.js sin cambios). Reglas de oro #56 (analytics condicionado con meta_site), #57 (Consent Mode v2 GDPR). Aprendizaje #135. |
| 2026-02-26 | **36.0.0** | **Elevacion IA 10 GAPs — Streaming Real + MCP + Native Tools Workflow**: 4 patrones nuevos: streaming real via PHP Generator + SSE (StreamingOrchestratorService extiende CopilotOrchestrator, ChatInput::setStreamedOutput(TRUE), Generator yield events tipados, buffer 80 chars con PII masking incremental, inyeccion en controller via $container->has(), fallback a buffered), native function calling via Drupal AI module (callAiApiWithNativeTools con ChatInput::setChatTools(ToolsInput), ToolRegistry::generateNativeToolsInput() convierte a ToolsFunctionInput/ToolsPropertyInput, ChatMessage::getTools() retorna tool calls parseados, fallback a text-based), MCP server JSON-RPC 2.0 (McpServerController con match() dispatch, initialize/tools-list/tools-call/ping, buildInputSchema() para JSON Schema, PII sanitization de output, error codes estandar), distributed tracing (TraceContextService genera trace_id UUID + span_id por operacion, propagacion a observability/cache/guardrails/SSE done). Reglas de oro #52 (streaming real via Generator), #53 (native function calling API-level). Aprendizaje #133. |
| 2026-02-26 | **35.0.0** | **Meta-Sitio plataformadeecosistemas.es — Batch Creation Workflow**: 1 patron nuevo: creacion batch de meta-sitio completo via PHP scripts (Tenant UI → SiteConfig SQL → PageContent batch script con helper function → SitePageTree batch script → SiteConfig special pages update → Lando proxy registration → cache clear → browser verification). Campos criticos SitePageTree: `tenant_id` (group ref), `page_id` (page_content ref), `nav_title` (string) — Drupal ignora campos desconocidos silenciosamente. Tenant domain FQDN obligatorio (plataformadeecosistemas.es, no prefijo corto). Lando proxy entry explicita por subdominio. 13 paginas creadas en ~10 min via script vs ~3h via browser UI. Regla de oro #48 (verificar field names con DESCRIBE antes de API calls batch). Aprendizaje #132. |
| 2026-02-26 | **34.0.0** | **Canvas Editor Content Hub Workflow**: 1 patron nuevo: shared library dependency cross-module (declarar dependency en .libraries.yml, bridge JS reconfigura engine — StorageManager override, polling init, auto-save, endpoints propios). NUNCA copiar engine JS. Bridge pattern: wait → register custom storage → load initial data → configure auto-save/undo-redo. Sanitizers HTML/CSS replicados por paridad de seguridad (protected methods no heredables → replicar). Bifurcacion layout_mode legacy/canvas en preprocess + template. CSS injection via html_head style tag. Presave hook actualizado para reading_time en canvas mode (rendered_html vs body). Regla de oro #51 (shared library dependency para Canvas Editor cross-module). Aprendizaje #131. |
| 2026-02-26 | **33.0.0** | **Auditoria Post-Implementacion IA Workflow**: 1 patron nuevo: auditoria post-implementacion de 4 dimensiones (registro de servicios en services.yml, contratos de llamada con firma exacta, schemas de config completos, config completitud con secciones YAML). Regla de oro #50 (auditoria post-implementacion obligatoria para cambios batch — verificar registros, firmas, schemas, config). Hallazgos criticos: SemanticCacheService no registrado (Layer 2 deshabilitado), 2 call signature mismatches en CopilotCacheService (args faltantes y en orden incorrecto), seccion reranking faltante en RAG settings, 3 schemas faltantes. Regla SERVICE-CALL-CONTRACT-001. Aprendizaje #130. |
| 2026-02-26 | **32.0.0** | **Elevacion IA Nivel 5/5 — 23 FIX Items Workflow**: 11 patrones nuevos: SmartBaseAgent Gen 2 constructor (10 args, 3 opcionales @?, conditional setters, migracion Gen 1→Gen 2), tool use loop (callAiApiWithTools max 5 iteraciones, ToolRegistry auto-discover via tag), provider fallback (circuit breaker 3/5min, cadenas por tier en YAML, Drupal State), cache semantica 2 capas (exact hash + Qdrant vectorSearch 0.92, degradacion graceful), jailbreak detection bilingue (checkJailbreak con patrones ES/EN, accion BLOCK), output PII masking (maskOutputPII reutiliza checkPII patterns, bidireccional), ReAct loop (PLAN→EXECUTE→OBSERVE→REFLECT→FINISH, per-step observability), LLM re-ranking config-driven (keyword\|llm\|hybrid en YAML), recomendaciones personalizadas via centroid embedding (promedio 5 ultimos leidos, Qdrant 0.55, fallback categorias), bridge autonomo→smart (AgentExecutionBridgeService con mapping YAML), scheduled agents (QueueWorker + hook_cron). Reglas de oro #48 (conectar infra existente antes de construir), #49 (circuit breaker + fallback chain para providers). Aprendizaje #129. |
| 2026-02-27 | **46.0.0** | **Navegacion Transversal Ecosistema — Banda Footer + Auth Visibility + Schema.org Dinamico Workflow:** 3 patrones nuevos: banda ecosistema en footer via SiteConfig JSON (3 campos nuevos: ecosystem_footer_enabled boolean, ecosystem_footer_links string_long JSON `[{name, url, label, current}]`, header_show_auth boolean — getters isEcosystemFooterEnabled(), getEcosystemFooterLinks(), isHeaderShowAuth()), Schema.org dinamico Person/Organization (template _meta-site-schema.html.twig con sameAs cross-domain, tipo derivado de group_id en preprocess_page()), visibilidad auth condicionada (header_show_auth separa login/registro del CTA configurable, sitios brochure con auth oculto). Drupal 11 entity field install: ALTER TABLE + installFieldStorageDefinition() script (no entity:updates). SCSS bypass: append CSS compilado cuando error preexistente bloquea pipeline. Fix: duplicado ecosistema_jaraba_core_entity_update() mergeado. 3 SiteConfigs actualizados con data ecosistema. 4 sitios verificados en browser. Reglas de oro #68 (banda ecosistema JSON configurable), #69 (Schema.org dinamico con sameAs). Aprendizaje #144. |
| 2026-02-27 | **45.0.0** | **DOC-GUARD Verificado + Kernel Test AI Resilience Workflow:** 2 patrones nuevos: (1) KERNEL-OPTIONAL-AI-001 — servicios AI cross-module (`@ai.provider`, `@jaraba_ai_agents.*`) DEBEN ser opcionales (`@?`) en services.yml para que Kernel tests que no habiliten `drupal:ai` no rompan la compilacion del container. Constructores PHP nullable + conditional `parent::__construct()` para agentes que heredan de `BaseAgent` (non-null `object $aiProvider`). Early return en `execute()` cuando AI no disponible. Aplicado a: content_writer_agent, content_embedding, learning_path agent. (2) DOC-GUARD verification post-commit — `scripts/maintenance/verify-doc-integrity.sh` verifica umbrales de lineas (DIRECTRICES>=2000, ARQUITECTURA>=2400, INDICE>=2000, FLUJO>=700), pre-commit hook bloquea reducciones >10%, CI pipeline ejecuta verificacion automatica. Regla de oro #67 (servicios AI siempre opcionales en services.yml). Aprendizaje #142. |
| 2026-02-27 | **44.0.0** | **Reviews & Comments Clase Mundial — Sprint 11 Completo: 18/18 Brechas + Entity Lifecycle Hooks**: 6 patrones nuevos: (1) Webhook HMAC authentication (ReviewWebhookService con `hash_hmac('sha256', $payload, $secret)`, queue-based async delivery via QueueFactory, max 3 retries, 6 event types, self-creating tables review_webhooks + review_webhook_log), (2) Gamification tier system (ReviewGamificationService con 6 action types 10-20 pts, 5 tiers bronze→silver→gold→platinum→diamond con thresholds, 6 badge types, leaderboard SQL, self-creating tables review_gamification_points + review_gamification_badges, `getUserBadges()` null-safe con `?: []`), (3) A/B consistent hashing (ReviewAbTestService con `crc32($experimentId . ':' . $uid) % 2` para variant assignment determinista, 5 predefined experiments, impression/conversion tracking), (4) ConfigEntity per-tenant settings (ReviewTenantSettings con config_prefix + config_export + 18 campos tipados + schema YAML, EntityForm con 4 detail groups, routes CRUD via AdminHtmlRouteProvider), (5) Entity lifecycle hooks para reviews (presave: sentiment enrichment via ReviewSentimentService + authenticity_score via FakeReviewDetectionService con `hasService()` + try-catch resilience; insert: aggregation + notification + gamification + webhook dispatch; update: aggregation + status change detection via `$entity->original` + webhook), (6) Fresh mock pattern para test isolation (crear nuevo EntityTypeManagerInterface mock + reconstruir servicio por test method cuando setUp mocks son insuficientes para configuracion per-test). Regla de oro #66 (entity lifecycle hooks con hasService + try-catch para servicios opcionales). Aprendizaje #141. |
| 2026-02-27 | **43.0.0** | **Reviews & Comments Clase Mundial — Implementacion Completa + PHPUnit Patterns**: 6 patrones nuevos: (1) ReviewableEntityTrait como patron de infraestructura compartida (trait con constantes + metodos + campos, sin consolidar en entidad unica), (2) STATUS/RATING/TARGET_FIELD_MAP para normalizacion de campos heterogeneos entre entity types, (3) MOCK-INTERSECT-001 (`createMockForIntersectionOfInterfaces()` cuando un mock necesita multiples interfaces — ej: CacheBackendInterface + CacheTagsInvalidatorInterface), (4) MOCK-DYNPROP-001 (anonymous classes con typed properties en vez de dynamic properties en mocks PHP 8.4), (5) TRAIT-CONST-001 (constantes en traits requieren PHP 8.2+, usar const directamente en clase de test), (6) Wilson Lower Bound Score para ranking de helpfulness (algoritmo Amazon/Reddit). Regla de oro #52 (PHPUnit 11 + PHP 8.4: no dynamic properties, intersection mocks, anonymous field items). Aprendizaje #140. |
| 2026-02-26 | **31.0.0** | **Meta-Site Nav Fix + Copilot Link Buttons Workflow**: 2 patrones nuevos: Meta-site nav chain debugging (page template → header partial → header_type → sub-partial, verificar `meta_site` variable y `header_type` en BD), copilot suggestion URL format (dual string/`{label, url}`, `getContextualActionButtons()` por rol, JS normalization, `--link` CSS variant, external link detection). Reglas de oro #46 (meta-site nav requiere header partial + classic layout), #47 (copilot sugerencias con URL action buttons). Aprendizaje #128. |
| 2026-02-26 | **30.0.0** | **AI Remediation Plan — 28 Fixes Workflow**: 10 patrones nuevos: AIIdentityRule centralizada (clase estatica con `apply()`), guardrails pipeline con PII espanol (DNI/NIE/IBAN/NIF/+34), model routing con config YAML + regex bilingue EN+ES, observabilidad conectada (log en todos los puntos de ejecucion IA), AIOpsService con metricas reales (/proc + BD + watchdog), streaming SSE semantico (parrafos, no chunks arbitrarios, sin usleep), canonical verticals (10 nombres con alias normalization), agent generations (Gen 0 @deprecated, Gen 1 @note, Gen 2 SmartBaseAgent), @? optional DI para cross-module services, feedback widget alineado JS↔PHP. Regla de oro #45 (remediacion IA integral con plan estructurado). Aprendizaje #127. |
| 2026-02-26 | **29.0.0** | **Blog Clase Mundial — Content Hub Elevation Workflow**: 8 patrones nuevos: template_preprocess para custom entities (ENTITY-PREPROCESS-001), presave resilience con hasService()+try-catch (PRESAVE-RESILIENCE-001), paginacion server-side con sliding window y cache context url.query_args:page, N+1 query fix con GROUP BY, share buttons via URL schemes + Clipboard API (sin JS SDKs), reading progress bar con requestAnimationFrame + prefers-reduced-motion, prose column 720px, responsive images con ImageStyle srcset. Reglas de oro #43 (preprocess para custom entities), #44 (resiliencia en presave hooks). Aprendizaje #126. |
| 2026-02-25 | **28.0.0** | **Premium Forms Migration 237 + USR-004 Workflow**: Seccion PREMIUM-FORMS-PATTERN-001 expandida con 4 patrones de migracion (A/B/C/D), checklist de migracion, pitfalls comunes. Regla de oro #42 (migracion global verificable con grep). Fix USR-004: redirect handler para user edit form (`_ecosistema_jaraba_core_user_profile_redirect`). Aprendizaje #125. |
| 2026-02-25 | **26.0.0** | **Elevacion Empleabilidad + Andalucia EI + Meta-Site Workflow**: 4 patrones nuevos: Premium Entity Forms con secciones (PREMIUM-FORM-001), Slide-Panel vs Drupal Modal (SLIDE-PANEL-001), Meta-Site Tenant-Aware Rendering (META-SITE-RENDER-001), Migracion de Field Types con Update Hooks. 4 reglas de oro: #38 (premium forms secciones), #39 (slide-panel vs modal), #40 (meta-site rendering), #41 (field type migration). Aprendizaje #123. |
| 2026-02-25 | **25.0.0** | **Remediacion Tenant 11 Fases Workflow**: 3 patrones nuevos: TenantBridgeService (inyeccion, 4 metodos, error handling, services.yml), Tenant Isolation en Access Handlers (EntityHandlerInterface + DI, isSameTenant(), TenantContextService nullable, politica view/update/delete), Test Mock Migration (entity storage key changes, mock expectations, Kernel test dependencies, CI kernel-test job). 3 reglas de oro: #35 (TenantBridgeService cross-entity), #36 (tenant isolation en handlers), #37 (CI Kernel tests obligatorios). Aprendizaje #122. |
| 2026-02-24 | **24.0.0** | **Meta-Sitio jarabaimpact.com Workflow**: Patron PATH-ALIAS-PROCESSOR-001 — InboundPathProcessorInterface con prioridad 200 para resolver path_alias custom de entidades ContentEntity a rutas canonicas. Skip list de prefijos, sin filtro status, static cache. Registro en services.yml con tag path_processor_inbound. Meta-sitio workflow: titulos via API config, publicacion via API publish, contenido via GrapesJS store. 7 paginas institucionales. Regla de oro #34. Aprendizaje #120. |
| 2026-02-24 | **23.0.0** | **Auditoria Horizontal Workflow**: Patron ACCESS-STRICT-001 — strict equality `(int) === (int)` obligatorio en access handlers, previene type juggling en ownership checks. Buscar con `grep "== $account->id()" \| grep -v "==="`. Los access handlers pueden estar en `src/Access/` O directamente en `src/`. Patron MJML email compliance — 5 cambios por plantilla: mj-preview, postal CAN-SPAM, font Outfit, colores universales, colores de grupo. Tabla de reemplazos de colores off-brand → brand. Preservar colores semanticos. Verificar con grep de colores off-brand → 0 resultados. Regla de oro #33. Aprendizaje #119. |
| 2026-02-24 | **22.0.0** | **Empleabilidad Profile Premium — Fase Final Workflow**: Patron de creacion de ContentEntity completa con AdminHtmlRouteProvider + field_ui_base_route + SettingsForm + links.task.yml (collection tab + settings tab) + routing.yml (settings route) + permissions.yml + update hook para instalar schema. Refuerzo de TWIG-XSS-001 (`\|raw` → `\|safe_html` en perfil candidato). Patron de cleanup: reemplazar `#markup` con HTML hardcodeado por `#theme` que reutiliza template premium existente. Aprendizaje #118. |
| 2026-02-24 | **21.0.0** | **Entity Admin UI Remediation Complete Workflow**: Patrones KERNEL-TEST-DEPS-001 — dependencias de modulos explicitas en Kernel tests (datetime, text, field, taxonomy + installEntitySchema previo). Patron OPTIONAL-SERVICE-DI-001 — `@?` para servicios cross-module opcionales con constructores nullable y null-guards (7 refs en agroconecta, 1 en job_board). Patron FIELD-UI-SETTINGS-TAB-001 — default local task tab obligatorio para Field UI (175 entidades en 46 modulos). Patron de mocking: ContentEntityInterface con `get()` callback y anonymous class para `->value`/`->target_id`. Inyeccion currentUser via ReflectionProperty::setValue(). Reglas de oro #29, #30, #31. Aprendizaje #116. |
| 2026-02-24 | **20.0.0** | **Icon System — Zero Chinchetas Workflow**: Patron ICON-CONVENTION-001 — firma estricta `jaraba_icon('category', 'name', {options})`, nunca path-style ni args posicionales ni invertidos. Patron de bridge categories (symlinks a categorias primarias) para iconos referenciados desde multiples convenciones de nombre. Auditoria sistematica: extraer pares unicos con grep → verificar SVGs en filesystem → crear symlinks faltantes → re-verificar con `find -type l ! -exec test -e {}`. Deteccion de symlinks circulares con `readlink -f`. Duotone-first policy (ICON-DUOTONE-001). Colores Jaraba exclusivos (ICON-COLOR-001). Regla de oro #32. Aprendizaje #117. |
| 2026-02-23 | **18.0.0** | **Andalucia +ei Launch Readiness Workflow**: Patron FORM-MSG-001 — templates de formulario custom DEBEN declarar variable `messages` en hook_theme, inyectar `['#type' => 'status_messages']` en preprocess, y renderizar `{{ messages }}` antes de `{{ form }}`. Patron LEGAL-ROUTE-001 — paginas legales con URLs canonicas en espanol (`/politica-privacidad`, `/terminos-uso`, `/politica-cookies`, `/sobre-nosotros`, `/contacto`). Patron LEGAL-CONFIG-001 — controladores leen contenido de `theme_get_setting()`, templates zero-region con placeholder informativo, TAB 14 en theme settings. Protocolo de testing en browser: bypass HTML5 con `novalidate` para verificar mensajes server-side. Regla de oro #28. Aprendizaje #110. |
| 2026-02-23 | **17.1.0** | **Sticky Header Migration Workflow**: Patron CSS-STICKY-001 — diagnostico de solapamiento de header fijo con contenido cuando la altura del header es variable (botones de accion wrappean a 2 lineas). Migracion global de `position: fixed` a `position: sticky` en `.landing-header`. Override solo para landing/front con hero fullscreen. Eliminacion de `padding-top` compensatorios fragiles (80px, 96px, 120px) en favor de padding estetico (`1.5rem`). Toolbar admin `top` ajustado una sola vez. 4 archivos SCSS modificados. Regla de oro #27. Aprendizaje #109. |
| 2026-02-23 | **17.0.0** | **AI Identity Enforcement + Competitor Isolation Workflow**: Patrones para blindaje de identidad IA (regla inquebrantable en BaseAgent parte #0, CopilotOrchestratorService $identityRule, PublicCopilotController bloque IDENTIDAD INQUEBRANTABLE, servicios standalone con antepuesto manual). Patron de aislamiento de competidores (redireccion a funcionalidades Jaraba, excepcion para integraciones reales). Auditoria de 34+ prompts. Eliminacion de menciones a ChatGPT, Perplexity, HubSpot, LinkedIn, Zapier de 5 prompts de IA. Reglas de oro #25 (identidad IA), #26 (aislamiento competidores). Aprendizaje #108. |
| 2026-02-23 | **16.1.0** | **Config Schema Dynamic Keys Fix**: Patron CONFIG-SCHEMA-001 — usar `type: sequence` en lugar de `type: mapping` para campos con keys dinamicos por vertical en config schema YAML. `mapping` con keys fijos lanza `SchemaIncompleteException` en Kernel tests para keys no declarados. Regla de oro #24. |
| 2026-02-23 | **16.0.0** | **Precios Configurables v2.1 Workflow**: Patrones para ConfigEntity cascade resolution (especifico→default→NULL), plan name normalization via SaasPlanTier aliases (lazy-cached alias map), AdminHtmlRouteProvider auto-routes para ConfigEntities, PlanResolverService como broker central con `getPlanCapabilities()` flat array, multi-source limit resolution en PlanValidator (FreemiumVerticalLimit→SaasPlanFeatures→SaasPlan), sentinel value `-999` para diferenciar "no configurado" de "valor real". Reglas de oro #22, #23. Aprendizaje #107. |
| 2026-02-20 | **15.0.0** | **Secure Messaging Implementation Workflow**: Patrones para cifrado server-side AES-256-GCM (IV 12 bytes, tag 16 bytes, Argon2id KDF), custom schema tables con DTOs readonly, WebSocket auth middleware (JWT + session), ConnectionManager con indices SplObjectStorage, cursor-based pagination (before_id), optional DI con `@?` para modulos opcionales, ECA plugins por codigo (Events + Conditions + Actions), hash chain SHA-256 para audit inmutable, rate limiting por usuario/conversacion. Reglas de oro #20, #21. Aprendizaje #106. |
| 2026-02-20 | 14.0.0 | **ServiciosConecta Sprint S3 Workflow**: Patrones para booking API field mapping, state machine con status granulares, cron idempotency con flags, owner pattern. Reglas de oro #17, #18, #19. Aprendizaje #105. |
| 2026-02-20 | 13.0.0 | **Page Builder Preview Audit Workflow**: Protocolo de auditoria de 4 escenarios del Page Builder. Patrones para generacion de preview images por vertical, auto-deteccion por convencion de nombre, verificacion browser multi-escenario. Regla de oro #16. Aprendizaje #103. |
| 2026-02-20 | 12.0.0 | **Vertical Retention Playbooks Workflow**: Patrones para entidades append-only (sin form handlers, AccessControlHandler restrictivo), config seeding via update hooks con JSON encoding, campos `string_long` con getters `json_decode()`. Reglas de oro #14, #15. Aprendizaje #104. |
| 2026-02-20 | 11.0.0 | **Gemini Remediation Workflow**: Protocolo de remediacion multi-IA (Clasificar/Revert/Fix/Verify/Document). Patrones CSRF para APIs via fetch(), XSS prevention en Twig y emails, TranslatableMarkup cast. Reglas de oro #11, #12, #13. Aprendizaje #102. |
| 2026-02-18 | 10.0.0 | **Page Builder Template Consistency Workflow**: Patrones para edicion masiva de templates YAML, validacion, preview_data rico por vertical, update hooks para resync de configs, y Drupal 10+ entity update patterns. Regla de oro #10. |
| 2026-02-18 | 9.0.0 | **CI/CD Hardening Workflow**: Reglas para config Trivy (`scan.skip-dirs`), deploy resiliente con fallback SSH, y regla de oro #9 (verificar CI tras cambios de config). |
| 2026-02-18 | 8.0.0 | **Unified & Stabilized Workflow**: Incorporación de patrones de testing masivo, estabilización de 17 módulos y gestión de clases final con DI flexible. |
| 2026-02-18 | 7.0.0 | **Living SaaS Workflow**: Incorporación de mentalidad adaptativa (Liquid UI) e inteligencia colectiva privada (ZKP). |
| ... | ... | ... |
