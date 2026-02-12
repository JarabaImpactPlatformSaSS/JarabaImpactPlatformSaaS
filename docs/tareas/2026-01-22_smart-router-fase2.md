# Tareas Pendientes: Smart Router + RAG - Fase 2

**Fecha:** 2026-01-22  
**Contexto:** Continuación de la sesión 2026-01-21 (AI FinOps + Smart Routing)  
**Estado:** ✅ COMPLETADO

---

## Resumen del Estado

La **Fase 1** está completa: servicios creados, registrados y documentados.  
La **Fase 2** está completa: servicios integrados en el flujo productivo del Copiloto.

---

## Tareas Completadas

### 1. ✅ Integrar ModeDetector en API del Copiloto
**Archivo:** `jaraba_copilot_v2/src/Controller/CopilotApiController.php`

**Cambios realizados:**
- `chat()` ahora usa `detectAndChat()` cuando no se especifica modo
- Añadido `mode_detection` en response para debugging
- Fallback a modo 'coach' si modo detectado está bloqueado

---

### 2. ✅ Integrar NormativeRAG en flujo de chat
**Archivos:**
- `jaraba_copilot_v2/src/Service/CopilotOrchestratorService.php`
- `jaraba_copilot_v2/jaraba_copilot_v2.services.yml`

**Cambios realizados:**
- Añadida propiedad `$normativeRag` e inyección en constructor
- Refactorizado `enrichWithNormativeKnowledge()` para usar Qdrant primero
- Fallback automático a búsqueda por keywords si Qdrant falla
- Añadido modo 'cfo' a los que usan RAG normativo

---

### 3. ✅ Alertas de Costes IA (hook_cron)
**Archivo:** `ecosistema_jaraba_core/ecosistema_jaraba_core.module`

**Cambios realizados:**
- Añadido case `ai_cost_alert` en `hook_mail()` 
- Añadido bloque AI COST MONITORING en `hook_cron()`
- Creada función `_check_ai_costs()` con throttling horario
- Creada función `_get_ai_metrics_from_state()` (fallback)
- Creada función `_send_ai_cost_alert()` con throttling diario
- Umbrales configurables: €3/día (warning), €5/día (critical)

---

### 4. ✅ Gráficos históricos de costes IA (Chart.js)
**Estado:** ✅ COMPLETADO

**Cambios realizados:**
- Librería `finops-dashboard` actualizada con Chart.js CDN (4.4.1)
- Creado `js/finops-ai-charts.js` con renderizado de gráficos
- Template actualizado con canvas para Chart.js (tendencia diaria + distribución proveedor)
- `getAiCostHistory()` creado en `FinOpsDashboardController`
- `trackAiUsage()` actualizado con tracking diario/mensual en State API

---

## Archivos Modificados

| Archivo | Cambio |
|---------|--------|
| [CopilotOrchestratorService.php](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/jaraba_copilot_v2/src/Service/CopilotOrchestratorService.php) | NormativeRAGService integrado |
| [jaraba_copilot_v2.services.yml](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/jaraba_copilot_v2/jaraba_copilot_v2.services.yml) | Dependencia RAG añadida |
| [CopilotApiController.php](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/jaraba_copilot_v2/src/Controller/CopilotApiController.php) | detectAndChat() integrado |
| [ecosistema_jaraba_core.module](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.module) | AI Cost alerts + hook_cron |

---

## Verificación

- ✅ Cache rebuild exitoso (3x)
- ✅ Sin errores de sintaxis PHP
- ✅ Servicios registrados correctamente

