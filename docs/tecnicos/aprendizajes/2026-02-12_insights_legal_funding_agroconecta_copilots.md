# Modulos 20260201 -- Insights Hub + Legal Knowledge + Funding Intelligence + AgroConecta Copilots

**Fecha:** 2026-02-12
**Autor:** Claude Opus 4.6
**Contexto:** Implementacion de 3 modulos nuevos + completar copilots AgroConecta (7 fases)
**Archivos creados:** ~171 (52 insights_hub + 54 legal_knowledge + 65 funding)
**Archivos modificados:** ~30 (consolidacion Fase 0 + integracion cross-modulo)

---

## Resumen

Se implementaron 3 modulos Drupal 11 completamente nuevos (Insights Hub, Legal Knowledge, Funding Intelligence) y se completaron los copilots de AgroConecta (Producer Copilot Fase 9, Sales Agent Fase 10), precedidos por una fase de consolidacion que resolvio duplicidades criticas en el codebase.

---

## Lecciones Aprendidas

### 1. Consolidacion previa elimina deuda tecnica acumulada

**Problema:** 3 duplicidades detectadas en la auditoria previa bloqueaban la implementacion limpia.

**Solucion (Fase 0):**
- `ConsentRecord` duplicado en jaraba_analytics y jaraba_pixels -> Se unifico usando jaraba_pixels como canonico
- `AnalyticsService` duplicado en jaraba_analytics y jaraba_page_builder -> Se renombro a `PageBuilderTrackingService`
- 3 pares entidades conversacion sin base comun -> Se crearon `CopilotConversationInterface` y `CopilotMessageInterface` en ecosistema_jaraba_core

**Leccion:** Siempre resolver duplicidades ANTES de crear nuevos modulos que podrian heredarlas.

### 2. Patron RAG Legal requiere disclaimers obligatorios

**Problema:** Las consultas normativas no pueden tener la misma confianza que un FAQ grounded en KB controlada.

**Solucion:**
- `LegalDisclaimerService` con 3 niveles: standard (>=0.75), enhanced (0.55-0.75), critical (<0.55)
- System prompt explicitamente prohibe inventar normativa
- Citas siempre con enlace BOE verificable via `LegalCitationService`

**Regla LEGAL-RAG-001:** Todo output del LegalRagService DEBE incluir disclaimer + citas verificables.

### 3. Motor de matching multi-criterio con pesos configurables

**Problema:** El matching de subvenciones no puede ser solo semantico -- requiere criterios estructurados.

**Solucion:**
- 5 criterios ponderados (Region 20%, Beneficiario 25%, Sector 20%, Tamano 15%, Semantico 20%)
- Scores compuestos 0-100 con desglose por criterio
- Threshold configurable por tenant (default 60)

**Regla FUNDING-MATCH-001:** El score semantico (Qdrant) nunca debe superar 20% del score total para evitar falsos positivos.

### 4. API clients externos necesitan rate limiting y cache

**Problema:** Las APIs publicas (BOE, BDNS, BOJA) tienen limites de velocidad no documentados.

**Solucion:**
- `FundingCacheService` con TTLs diferenciados: calls 30min, matches 5min, stats 15min
- `BoeApiClient` con retry exponencial (3 intentos, backoff 1s/2s/4s)
- Sync via cron (no en tiempo real): BOE diario 04:00 UTC, BDNS cada 6h

**Regla API-EXTERNAL-001:** Todo cliente API externo debe implementar cache + retry + cron sync (nunca sync en request del usuario).

### 5. SalesAgent en jaraba_ai_agents mantiene consistencia arquitectonica

**Problema:** ProducerCopilotAgent existia en jaraba_ai_agents pero SalesAgent solo en agroconecta_core, rompiendo el patron.

**Solucion:**
- Crear `SalesAgent.php` en jaraba_ai_agents extendiendo `SmartBaseAgent`
- Hereda Model Routing (fast/balanced/premium) automaticamente
- ConfigEntity YAML en ecosistema_jaraba_core config/install
- SalesAgentService en agroconecta_core delega al agente centralizado

**Regla AGENT-001:** Todo agente IA debe estar en jaraba_ai_agents (no en modulos verticales) para heredar Model Routing + Brand Voice + Observability.

### 6. Entidades Insights con entity_type_id descriptivo evitan colisiones

**Problema:** Nombre generico `error_log` colisionaria con watchdog/dblog de Drupal.

**Solucion:** Usar prefijo descriptivo -> `insights_error_log`, `uptime_check`, `uptime_incident`.

**Regla ENTITY-PREFIX-001:** Entity type IDs deben ser descriptivos del modulo (ej: `insights_error_log`, no `error_log`).

### 7. Web Vitals RUM tracker como JS library attachable

**Problema:** El tracker de Web Vitals debe cargarse en paginas frontend sin interferir con admin.

**Solucion:**
- JS library en jaraba_insights_hub.libraries.yml
- Attach via hook_page_attachments() solo en rutas no-admin
- Envio de metricas via Beacon API (no bloquea navegacion)
- Deduplicacion server-side por page_url + metric_name + timestamp

**Regla VITALS-001:** Trackers JS frontend deben usar Beacon API y excluir rutas admin.

---

## Reglas Nuevas

| Regla | Descripcion |
|-------|-------------|
| **LEGAL-RAG-001** | Output LegalRagService siempre con disclaimer + citas BOE verificables |
| **FUNDING-MATCH-001** | Score semantico <=20% del total para evitar falsos positivos |
| **API-EXTERNAL-001** | Clientes API externos: cache + retry + cron sync (nunca sync en request) |
| **AGENT-001** | Agentes IA en jaraba_ai_agents (no en verticales) para herencia Model Routing |
| **ENTITY-PREFIX-001** | Entity type IDs descriptivos del modulo para evitar colisiones |
| **VITALS-001** | Trackers JS con Beacon API, exclusion admin |

---

## Archivos Clave

| Archivo | Proposito |
|---------|-----------|
| `jaraba_insights_hub/src/Service/InsightsAggregatorService.php` | Servicio central que une SEO + Performance + Errors + Uptime |
| `jaraba_legal_knowledge/src/Service/LegalRagService.php` | Pipeline RAG completo: query -> Qdrant -> Claude -> citas |
| `jaraba_legal_knowledge/src/Service/BoeApiClient.php` | Cliente HTTP para API REST del BOE |
| `jaraba_funding/src/Service/Intelligence/FundingMatchingEngine.php` | Motor matching 5 criterios ponderados |
| `jaraba_funding/src/Service/Api/BdnsApiClient.php` | Cliente BDNS para convocatorias |
| `jaraba_agroconecta_core/src/Service/DemandForecasterService.php` | Prediccion demanda basada en historico |
| `jaraba_agroconecta_core/src/Service/CrossSellEngine.php` | Motor venta cruzada por categoria |
| `jaraba_ai_agents/src/Agent/SalesAgent.php` | Agente ventas con SmartBaseAgent Model Routing |
| `ecosistema_jaraba_core/src/Interface/CopilotConversationInterface.php` | Interfaz unificada conversaciones copilot |

---

## Verificacion

- [ ] `https://jaraba-saas.lndo.site/insights` muestra dashboard con 4 tabs
- [ ] `https://jaraba-saas.lndo.site/legal` permite consultas con citas BOE
- [ ] `https://jaraba-saas.lndo.site/funding` muestra convocatorias con matching
- [ ] `POST /api/v1/legal/query` retorna respuesta con disclaimer + citas
- [ ] `POST /api/v1/funding/copilot` retorna respuesta con matching score
- [ ] SalesAgent registrado en `lando drush eval "print_r(array_keys(\Drupal::service('plugin.manager.ai_agent')->getDefinitions()));"`
- [ ] 3 page templates aparecen en tema: page--insights, page--legal, page--funding
- [ ] SCSS compilado para los 3 modulos nuevos
