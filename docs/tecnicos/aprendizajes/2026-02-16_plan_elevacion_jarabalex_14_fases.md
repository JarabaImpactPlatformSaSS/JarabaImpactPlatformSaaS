# Aprendizajes: Plan Elevacion JarabaLex v1 — 14 Fases a Clase Mundial

| Campo | Valor      |
|-------|------------|
| Fecha | 2026-02-16 |

---

## Patron Principal

El vertical JarabaLex (jaraba_legal_intelligence) fue elevado de sub-feature a vertical clase mundial mediante un plan sistematico de 14 fases, reutilizando el patron establecido por Empleabilidad, Emprendimiento y Andalucia+ei. Cada fase produce un entregable concreto y verificable. La QA integral en la fase final descubrio 2 defectos criticos (LegalCopilotAgent sin metodos de interfaz, servicio no registrado) que habrian causado errores fatales en produccion. Este patron de 14 fases es ahora un workflow documentado y reutilizable para cualquier vertical futuro.

---

## Aprendizajes Clave

### 1. AgentInterface requiere implementacion completa — no basta con heredar BaseAgent

**Situacion:** LegalCopilotAgent extendia BaseAgent y definia constantes MODES y metodos auxiliares (buildModePrompt, detectMode, getModeTemperature), pero omitia los 5 metodos requeridos por AgentInterface: execute(), getAvailableActions(), getAgentId(), getLabel(), getDescription(). PHP no lanza error al definir la clase (BaseAgent es abstract), pero cualquier intento de instanciar o usar el agente fallaria con fatal error.

**Aprendizaje:** Antes de implementar un nuevo agent, leer el sibling agent mas reciente (ej: MerchantCopilotAgent) Y la interfaz AgentInterface. BaseAgent proporciona setTenantContext() y helpers, pero NO implementa los 5 metodos abstractos del contrato. Verificar con `php -l` no detecta este problema — solo una revision manual o intento de instanciacion lo revelaria.

**Regla:** Todo CopilotAgent DEBE implementar los 5 metodos de AgentInterface: execute(), getAvailableActions(), getAgentId(), getLabel(), getDescription(). Verificar antes de commit.

### 2. Service registration es tan critico como la implementacion

**Situacion:** LegalCopilotAgent fue implementado en `src/Agent/LegalCopilotAgent.php` pero no fue registrado en `jaraba_legal_intelligence.services.yml`. El contenedor de Drupal nunca lo instanciaria, haciendolo invisible para el sistema.

**Aprendizaje:** Cada clase PHP que actua como servicio DEBE tener una entrada correspondiente en services.yml. Para agents que extienden BaseAgent (Pattern A), los 6 argumentos son: `@ai.provider`, `@config.factory`, `@logger.channel.{modulo}`, `@jaraba_ai_agents.tenant_brand_voice`, `@jaraba_ai_agents.observability`, `@ecosistema_jaraba_core.unified_prompt_builder`. Para SmartBaseAgent (Pattern B), se anade `@jaraba_ai_agents.model_router` como 7o argumento.

**Regla:** Inmediatamente despues de crear un servicio PHP, registrarlo en services.yml. Verificar que el numero y orden de argumentos coincide con el constructor.

### 3. QA paralela con agents especializados detecta defectos que el lint no encuentra

**Situacion:** Se lanzo una QA integral con 2 agentes paralelos (FASE 0-5 y FASE 6-12). Los agentes detectaron: (1) LegalCopilotAgent sin 5 metodos de interfaz, (2) servicio no registrado, (3) ternario muerto en HealthScoreService, (4) hasService guard faltante en JourneyProgressionService. PHP lint (php -l) paso los 12 ficheros sin error — ningun defecto era un error de sintaxis.

**Aprendizaje:** La QA automatica con agents paralelos es significativamente mas efectiva que la revision manual secuencial. Detecta defectos semanticos (metodos faltantes, registros de servicio omitidos, logica muerta) que las herramientas de analisis estatico basico no capturan. El costo es ~2 minutos de ejecucion paralela vs potenciales horas de debugging en produccion.

**Regla:** Toda implementacion de 10+ ficheros debe culminar con QA paralela via agents especializados, dividiendo el scope en lotes de 5-6 fases.

### 4. FunnelDefinition es ContentEntity, no ConfigEntity — seeds via hook_update_N

**Situacion:** Al implementar los 3 funnels de JarabaLex (Search Conversion, Upgrade Funnel, Citation Workflow), se necesitaba determinar el mecanismo de seeding. Se descubrio que FunnelDefinition es un Content Entity almacenado en base de datos, no un Config Entity exportable via YAML.

**Aprendizaje:** Los Content Entities se seedean mediante `hook_update_N()` en el fichero `.install` del modulo, con guard de idempotencia via `loadByProperties()`. El campo `steps` de tipo `map` requiere wrapping como `[$data['steps']]` (un solo delta con el array completo), siguiendo el patron establecido por FunnelApiController.

**Regla:** Seeds de Content Entities siempre via hook_update_N con idempotency guard. Verificar el patron de campo map consultando el controller/servicio que los crea normalmente.

### 5. El patron de elevacion vertical de 14 fases es reutilizable y predecible

**Situacion:** Tras elevar 4 verticales (Empleabilidad, Emprendimiento, Andalucia+ei, JarabaLex) con el mismo patron de 14 fases, se confirma que la estructura es estable, predecible y produce resultados consistentes.

**Aprendizaje:** Las 14 fases cubren todas las dimensiones de un vertical clase mundial: monetizacion (F0-F1), integracion copilot (F2), presentacion (F3-F6), comunicacion (F7), cross-selling (F8), engagement (F9-F11), navegacion (F12) y calidad (F13). Cada fase tiene dependencias claras (F0 antes de F1, F3 antes de F4, etc.) y produce ficheros verificables independientemente. El tiempo de implementacion se reduce con cada iteracion por la familiaridad con el patron.

**Regla:** Documentar el patron en 00_FLUJO_TRABAJO_CLAUDE.md para que sea accesible en toda sesion futura. Para nuevos verticales, replicar el patron referenciando el vertical anterior mas reciente como template.
