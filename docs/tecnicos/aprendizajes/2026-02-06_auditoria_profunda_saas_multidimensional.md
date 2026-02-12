# Auditoria Profunda SaaS Multidimensional - Lecciones Aprendidas

> **Fecha**: 2026-02-06
> **Categoria**: Auditoria / Seguridad / Arquitectura / AI / Rendimiento
> **Impacto**: CRITICO - 87 hallazgos identificados, 17 bloqueantes para produccion

---

## Contexto

Se realizó una auditoría profunda del SaaS desde 10 disciplinas senior simultáneas: consultoría de negocio, desarrollo de carreras, análisis financiero, marketing, publicidad, arquitectura SaaS, ingeniería de software, UX, Drupal/GrapesJS, SEO/GEO e IA. La auditoría cubre backend (40 módulos custom), frontend (theme + page builder), AI/RAG (Qdrant + Copilot + Agents), seguridad, rendimiento, accesibilidad y modelo de negocio.

## Problema Identificado

La plataforma, con Madurez Arquitectónica 5.0 y 280+ documentos técnicos, tiene fundamentos sólidos pero presenta **87 hallazgos** que impiden un despliegue seguro en producción con tráfico real:

- **17 CRITICOS**: Bloquean producción (inyección de prompts, APIs sin auth, Redis inactivo)
- **32 ALTOS**: Requieren corrección pre-release (memory leaks, N+1 queries, sin tests)
- **26 MEDIOS**: Sprint siguiente (accesibilidad, optimización, responsive)
- **12 BAJOS**: Deuda técnica planificada

## Archivos Clave

| Archivo | Hallazgos | Severidad |
|---------|-----------|-----------|
| `ecosistema_jaraba_core/src/Service/PlanValidator.php` | TODOs sin implementar (storage, AI counter) | CRITICA |
| `jaraba_rag/src/Service/JarabaRagService.php` | Prompt injection, alucinaciones sin manejo, sin cache | CRITICA |
| `jaraba_rag/src/Service/TenantContextService.php` | Filtro Qdrant `should` vs `must` (fuga cross-tenant) | CRITICA |
| `jaraba_copilot_v2/src/Service/CopilotOrchestratorService.php` | Sin circuit breaker, contexto ilimitado | CRITICA |
| `ecosistema_jaraba_core/src/Controller/StripeController.php` | Claves en config DB vs env vars | CRITICA |
| `ecosistema_jaraba_core/ecosistema_jaraba_core.routing.yml` | APIs publicas sin auth, webhooks sin HMAC | CRITICA |
| `ecosistema_jaraba_theme/js/scroll-animations.js` | Memory leaks (event listeners sin cleanup) | ALTA |
| `ecosistema_jaraba_theme/js/slide-panel.js` | Bug: variable `error` vs `e` en catch | ALTA |
| `ecosistema_jaraba_core/src/Service/TenantManager.php` | God Object, retornos inconsistentes | ALTA |
| `ecosistema_jaraba_theme/css/ecosistema-jaraba-theme.css` | 518KB render-blocking sin critical CSS | ALTA |

## Lecciones Aprendidas

### 1. Seguridad AI: Los prompts son superficie de ataque
- **Aprendizaje:** Cualquier input interpolado en un system prompt (nombre de tenant, vertical, contexto) es un vector de inyección de prompts. Tratar inputs a LLMs con la misma rigurosidad que inputs SQL.
- **Directriz nueva:** Toda interpolación en prompts debe pasar por sanitización y validación contra whitelist.

### 2. Multi-tenancy requiere verificación en TODAS las capas
- **Aprendizaje:** El aislamiento de tenant en Qdrant usaba `should` (OR) permitiendo potencial fuga de datos. La multi-tenancy debe verificarse en cada capa: DB, vector store, cache, API.
- **Directriz nueva:** Usar `must` (AND) obligatorio para tenant_id en filtros Qdrant.

### 3. Rate limiting es obligatorio en endpoints AI
- **Aprendizaje:** Sin rate limiting, un solo usuario puede generar costes ilimitados en APIs de OpenAI/Anthropic. El servicio RateLimiterService existía pero NO estaba integrado en las rutas.
- **Directriz nueva:** Todo endpoint que invoque LLM/embedding debe tener rate limiting por tenant y por usuario.

### 4. Circuit breaker previene efecto cascada en proveedores
- **Aprendizaje:** Al fallar un proveedor LLM, el sistema reintentaba los 3 proveedores en cada request, multiplicando costes y latencia por 3x.
- **Directriz nueva:** Implementar circuit breaker con estado half-open tras N fallos consecutivos.

### 5. Redis configurado no significa Redis activo
- **Aprendizaje:** El servicio Redis estaba declarado en `.lando.yml` pero nunca configurado como cache backend en Drupal, dejando toda la cache en base de datos.
- **Directriz nueva:** Verificar en el health check que Redis responde Y que `cache_default` usa Redis backend.

### 6. JavaScript en Drupal requiere cleanup de event listeners
- **Aprendizaje:** Los behaviors de Drupal se re-ejecutan en cada AJAX, creando listeners duplicados si no se usa `once()` o cleanup en `detach`.
- **Directriz nueva:** Todo `addEventListener` en behaviors debe usar `once('name', element)` o implementar `detach`.

### 7. Los TODOs en código son deuda invisible
- **Aprendizaje:** 20+ TODOs en servicios core (PlanValidator, WebhookController, SandboxService) significan funcionalidades SaaS core que NO funcionan (límites de plan, notificaciones, sandbox).
- **Directriz nueva:** Los TODOs en servicios core deben tener issue tracking asociado y deadline.

### 8. Cache de embeddings y respuestas RAG reduce costes 30-40%
- **Aprendizaje:** Sin cache, cada reindexación de contenido idéntico generaba nuevos embeddings (coste API). Sin cache de respuestas, queries repetidas ejecutaban el pipeline RAG completo.
- **Directriz nueva:** Cache embeddings por SHA-256 del texto+modelo (TTL 7 días). Cache respuestas RAG por hash de query+opciones+tenant (TTL 1 hora, solo confianza >= 0.5).

### 9. Todos los valores configurables deben gestionarse desde la UI de Drupal
- **Aprendizaje:** Los límites de rate limiting estaban hardcodeados en constantes. El usuario requirió formularios de administración para poder ajustar valores sin tocar código.
- **Directriz nueva:** Todo valor configurable debe tener un formulario ConfigFormBase con valores por defecto seguros como fallback.

### 10. Headers de seguridad deben configurarse a nivel de aplicación
- **Aprendizaje:** Sin CORS, CSP ni HSTS, la aplicación era vulnerable a ataques XSS y clickjacking. Los headers deben ser configurables para adaptar a diferentes entornos.
- **Directriz nueva:** Usar EventSubscriber para inyectar headers de seguridad en cada response, con formulario admin para configuración.

## Métricas Finales

| Métrica | Valor |
|---------|-------|
| Total hallazgos | **87** |
| Resueltos Fase 1 | **12** (70% de críticos) |
| Resueltos Fase 2 | **7** (22% de altos) |
| **Total resueltos** | **19/87 (22%)** |
| Pendientes críticos | **5** |
| Módulos auditados | **40 custom + 56 contrib** |
| Archivos críticos identificados | **20** |
| Archivos modificados (remediación) | **18** |
| Formularios admin creados | **2** (Rate Limits, Security Headers) |
| Disciplinas aplicadas | **10** |
| Riesgo global | **MEDIO** (reducido desde MEDIO-ALTO) |

## Documentos Relacionados

- [Auditoria completa](../auditorias/20260206-Auditoria_Profunda_SaaS_Multidimensional_v1_Claude.md) - Documento principal con los 87 hallazgos detallados
- [Auditoria Directrices Nucleares](../auditorias/20260130-Auditoria_Exhaustiva_Directrices_Nucleares_v1_Claude.md) - Auditoría previa de conformidad
- [Auditoria PageBuilder](../auditorias/20260129_Auditoria_PageBuilder_20260126d_v1.md) - Auditoría específica de Page Builder
- [Documento Maestro Arquitectura](../../00_DOCUMENTO_MAESTRO_ARQUITECTURA.md)
- [Directrices del Proyecto](../../00_DIRECTRICES_PROYECTO.md)
