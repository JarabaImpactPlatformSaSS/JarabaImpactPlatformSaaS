# Plan Cierre Gaps Clase Mundial — F9-F12 Completion

**Fecha**: 2026-02-12
**Fases**: F9, F10, F11, F12 (final del plan de 12 fases)
**Estado**: Completado al 100%

## Resumen

Cierre de las 4 fases finales del plan "Clase Mundial" (Specs 20260128), elevando la plataforma a nivel de madurez 5.0/5.0 en arquitectura. Cubre B2B Sales Flow, Scaling Infrastructure, IA Clase Mundial, y Lenis Premium UX.

---

## F9 — B2B Sales Flow (Doc 186)

### Implementacion

- **Pipeline B2B 8 etapas**: Lead→MQL→SQL→Demo→Proposal→Negotiation→Won→Lost
- **BANT Qualification**: 4 campos (Budget, Authority, Need, Timeline) + score 0-4 computado
- **SalesPlaybookService**: match expression `stage + BANT score` → recomendacion next action
- **2 nuevos API endpoints**: GET playbook, PUT bant
- **Update hook 10001**: Migracion schema para 5 campos BANT

### Patrones

1. **Computed field en preSave()**: El BANT score se computa automaticamente antes de guardar. Usa una constante `BANT_MAX_VALUES` que mapea cada campo a su valor maximo para calcular el score.

2. **Directriz #20 — YAML allowed values**: Todos los valores de campos list_string se gestionan desde `jaraba_crm.allowed_values.yml`, cargados via callbacks en `jaraba_crm.allowed_values.inc`. Zero hardcoding en PHP.

3. **match expression para playbook**: El SalesPlaybookService usa `match ($stage)` con sub-condiciones sobre BANT score para recomendar acciones diferenciadas (e.g., SQL con BANT>=3 → demo, SQL con BANT<3 → re-cualificar).

### Regla

| ID | Regla | Descripcion |
|----|-------|-------------|
| BANT-001 | Computed field preSave | Para campos derivados, computar en preSave() con constante de referencia. No depender de hooks externos. |

---

## F10 — Scaling Infrastructure (Doc 187)

### Implementacion

- **`scripts/restore_tenant.sh`** (17KB): 4 comandos (backup, restore, list, tables), compatible Lando/IONOS
- **`tests/performance/multi_tenant_load_test.js`** (13KB): k6 con 4 escenarios y 7 custom metrics
- **`monitoring/prometheus/rules/scaling_alerts.yml`** (6KB): 10 alert rules + 5 recording rules
- **`docs/arquitectura/scaling-horizontal-guide.md`** (13KB): 3 fases de escalado

### Patrones

1. **INFORMATION_SCHEMA auto-discovery**: En lugar de hardcodear las 159+ tablas con `tenant_id`, el script de backup las descubre dinamicamente via `SELECT TABLE_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE COLUMN_NAME = 'tenant_id'`. Zero mantenimiento cuando se anaden nuevas entidades.

2. **k6 tenant isolation check**: Cada respuesta API se verifica para asegurar que no contiene datos de otros tenants. Se usa una custom metric `tenantIsolationFailures` con threshold=0.

3. **Prometheus 3 fases escalado**: Las alertas de scaling se disenan en 3 umbrales (50 tenants → Fase 2, 200 tenants → Fase 3) con recording rules para capacity planning.

### Regla

| ID | Regla | Descripcion |
|----|-------|-------------|
| SCALING-001 | INFORMATION_SCHEMA auto-discovery | Para scripts que operan sobre todas las tablas de un tenant, usar INFORMATION_SCHEMA para descubrir tablas dinamicamente. Nunca hardcodear listas de tablas. |

---

## F11 — Elevacion IA Clase Mundial

### Implementacion

- **BrandVoiceTrainerService**: Qdrant collection `jaraba_brand_voice` (1536 dims), feedback loop (approve/reject/edit), alineacion coseno, refinamiento LLM
- **PromptExperimentService**: A/B testing de prompts integrado con `jaraba_ab_testing`, auto-evaluacion via QualityEvaluatorService
- **MultiModal Preparation**: 2 interfaces PHP (input/output), exception custom, bridge stub
- **3 API controllers**: 8 rutas, 1 permiso nuevo

### Patrones

1. **Feedback loop Qdrant**: Los ejemplos aprobados/editados se indexan automaticamente como vectores en Qdrant. El score de alineacion se calcula buscando los 5 vectores mas cercanos y promediando la similitud coseno. Threshold 0.75 para alineacion buena.

2. **Integracion A/B Testing existente**: En lugar de crear un sistema de A/B nuevo, se reutiliza `jaraba_ab_testing` completo (StatisticalEngineService, VariantAssignmentService) con `experiment_type='prompt_variant'` y variant_data JSON para almacenar config de prompts (system_prompt, temperature, model_tier).

3. **Interfaces MultiModal**: Preparar interfaces PHP limpias (MultiModalInputInterface, MultiModalOutputInterface) con un bridge stub permite que el codigo futuro (Whisper, DALL-E, ElevenLabs) se integre sin cambiar los consumidores. La excepcion `MultiModalNotAvailableException` se lanza si se llama a una capacidad no disponible.

### Regla

| ID | Regla | Descripcion |
|----|-------|-------------|
| BRAND-VOICE-001 | Feedback loop Qdrant | Para entrenamiento de brand voice, indexar automaticamente en Qdrant los ejemplos aprobados/editados. Usar collection separada del knowledge base general. |

---

## F12 — Lenis Integration Premium

### Implementacion

- **Lenis v1.3.17 CDN** (jsDelivr, patron identico a Alpine.js)
- **`lenis-scroll.js`**: Drupal.behaviors + once(), prefers-reduced-motion, admin exclusion
- **Attach**: homepage template + hook_preprocess_html landing pages verticales

### Patrones

1. **CDN externo con patron Alpine.js**: Registrar librerias externas como CDN en `libraries.yml` con version fija, siguiendo el mismo patron ya usado para Alpine.js. La libreria local depende de la CDN externa.

2. **Triple proteccion**: (1) `prefers-reduced-motion: reduce` → return, (2) `body.path-admin` → return, (3) `typeof Lenis === 'undefined'` → return. Garantiza que Lenis solo se activa donde corresponde.

3. **Dual attachment strategy**: Template attach para homepage (pagina especifica), hook_preprocess_html attach para landing pages verticales (multiples rutas). Evita duplicar logica de rutas.

### Regla

| ID | Regla | Descripcion |
|----|-------|-------------|
| LENIS-001 | CDN + admin exclusion | Librerias frontend de UX premium deben: (1) usar CDN con version fija, (2) excluir admin pages, (3) respetar prefers-reduced-motion. |

---

## Errores y Soluciones

| Error | Solucion |
|-------|----------|
| Windows line endings (\r\n) en restore_tenant.sh | `sed -i 's/\r$//' script.sh` antes de validar con `bash -n` |
| drush eval ParseError con regex/quotes complejas | Simplificar verificacion: usar Grep tool para verificar source + drush eval solo para service checks |
| 159+ tablas con tenant_id imposible de hardcodear | INFORMATION_SCHEMA auto-discovery |

---

## Resumen de Reglas Nuevas

| ID | Fase | Descripcion |
|----|------|-------------|
| BANT-001 | F9 | Computed field preSave con constante de referencia |
| SCALING-001 | F10 | INFORMATION_SCHEMA auto-discovery para tablas tenant |
| BRAND-VOICE-001 | F11 | Feedback loop Qdrant con collection separada |
| LENIS-001 | F12 | CDN + admin exclusion + prefers-reduced-motion |

## Referencias

- F9 Plan: `docs/implementacion/2026-02-12_F9_B2B_Sales_Flow_Doc186_Implementacion.md`
- F10 Plan: `docs/implementacion/2026-02-12_F10_Scaling_Infrastructure_Doc187_Implementacion.md`
- F11 Plan: `docs/implementacion/2026-02-12_F11_Elevacion_IA_Clase_Mundial_Implementacion.md`
- F12 Plan: `docs/implementacion/2026-02-12_F12_Lenis_Integration_Premium_Implementacion.md`
- Scaling Guide: `docs/arquitectura/scaling-horizontal-guide.md`
- Spec Master: `docs/implementacion/2026-02-12_Plan_Cierre_Gaps_Specs_20260128_Clase_Mundial.md`
