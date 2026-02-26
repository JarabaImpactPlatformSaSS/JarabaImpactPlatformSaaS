# Aprendizaje #134: Auditoria IA Clase Mundial — 25 Gaps hacia Paridad con Lideres del Mercado

**Fecha:** 2026-02-26
**Categoria:** Auditoria IA / Benchmarking / Planificacion Estrategica
**Impacto:** Critico — Identifica 25 gaps entre el stack IA actual y plataformas clase mundial, con plan de implementacion de 4 sprints (320-440h)

---

## 1. Contexto

Tras las elevaciones previas (28 FIX + 23 FIX + 10 GAP = 61 items implementados), el stack IA alcanzo nivel 5/5 en arquitectura backend. Sin embargo, una auditoria exhaustiva comparando contra lideres del mercado (Salesforce Agentforce, HubSpot Breeze, Shopify Sidekick, Intercom Fin, Notion AI) revelo que el nivel UX/monetizacion/testing estaba en 1.5-2.5/5.

Una **segunda auditoria profunda del codigo** corrigio 7 de los hallazgos iniciales: esos "gaps" ya existian como codigo funcional y solo necesitan refinamiento puntual.

**Resultado final:** 25 gaps = 7 refinamiento + 16 nuevos + 2 infraestructura.

---

## 2. Patrones Clave Aprendidos

### 2.1 Auditoria Comparativa: Codigo Existente vs Percepcion de Ausencia

- **Problema:** La primera auditoria reporto como "ausentes" componentes que ya estaban implementados (OnboardingWizard 409 ln, PricingController 356 ln, DemoController 246 ln, UsageDashboardController 194 ln, SchemaGeneratorService 471 ln, _dark-mode.scss 76 ln, LlmsTxtController 255 ln).
- **Solucion:** Toda auditoria de gaps DEBE incluir una segunda fase de verificacion profunda del codigo antes de clasificar un gap como "ausente". Buscar con `grep -rn` por keywords del feature, no solo por nombres de clase esperados.
- **Patron:** Clasificar gaps en 3 categorias: refinamiento (existe, mejorar), nuevo (no existe, crear), infraestructura (capacidad operacional).

### 2.2 Command Bar como Hub de UX IA

- **Problema:** Sin Command Bar, los usuarios dependen de la navegacion tradicional del menu de Drupal para acceder a funciones. Los competidores ofrecen acceso instantaneo via Cmd+K.
- **Solucion:** `CommandRegistryService` como service collector con tag `jaraba.command_provider`. Patron extensible: cualquier modulo puede registrar un provider.
- **Patron:**
  1. Service collector con `service_collector` tag (no discovery manual)
  2. Cada provider implementa `search(query, limit)` + `isAccessible(account)`
  3. Resultados fusionados y ordenados por relevancia (score descending)
  4. JS: debounce 300ms, keyboard navigation, `Drupal.checkPlain()` para XSS
  5. Configurable: toggle desde Theme Settings UI

### 2.3 Inline AI como Extension de Premium Forms

- **Problema:** Las 237 formularios `PremiumEntityFormBase` no tienen asistencia IA. Los competidores ofrecen "AI Compose" / "Magic" inline en campos de texto.
- **Solucion:** Nuevo metodo `getInlineAiFields()` en `PremiumEntityFormBase` que retorna array de fields. JS inyecta sparkle buttons. Tier fast (Haiku 4.5) para baja latencia.
- **Patron:**
  1. Opt-in per form: subclases override `getInlineAiFields()`
  2. Tier fast para minimizar latencia y coste (sugencias rapidas)
  3. Chips clickeables que insertan el valor (no reemplazan automaticamente)
  4. Integra con GrapesJS via plugin RichTextEditor toolbar button

### 2.4 Proactive Intelligence como ContentEntity

- **Problema:** No hay alertas proactivas basadas en IA. Otros SaaS notifican automaticamente: "Tu tasa de abandono subio 15%" o "Oportunidad: 3 candidatos coinciden con tu busqueda".
- **Solucion:** `ProactiveInsight` ContentEntity + `ProactiveInsightEngineWorker` QueueWorker + bell notification en header. Max 3 insights/user/dia.
- **Patron:**
  1. ContentEntity (no custom table) para aprovechar Field UI, Views, admin routes
  2. Form extiende `PremiumEntityFormBase` con `getSectionDefinitions()`
  3. QueueWorker cron con limite de insights por dia
  4. Access: owner-only view, admin-only edit/delete, TENANT-ISOLATION-ACCESS-001

### 2.5 Voice AI Client-Side First

- **Problema:** Voice AI requiere transcripcion. Whisper API (server-side) añade latencia y coste.
- **Solucion:** Web Speech API (client-side) como primary, Whisper como fallback.
- **Patron:**
  1. Feature detection: `'SpeechRecognition' in window` (Chrome, Edge, Safari)
  2. Graceful degradation: mic button no aparece si no hay soporte
  3. Transcript se inyecta en el input del copilot existente (reusa pipeline completo)
  4. Feature gated por plan (Professional+) via hook_preprocess

### 2.6 Test-Driven Gap Closure

- **Problema:** Stack IA de ~7,500 lineas con solo 6 tests (~6% coverage). Inviable para refactoring o cambios sin riesgo.
- **Solucion:** Test suite dedicada con 40+ unit, 15+ kernel, 7 prompt regression. `PromptRegressionTestBase` con golden fixtures.
- **Patron:**
  1. Golden fixtures: primera ejecucion crea el fixture, siguientes validan
  2. Normalizacion de whitespace para comparacion robusta
  3. Suite separada `PromptRegression` en phpunit.xml
  4. Cada gap nuevo incluye al menos 1 unit test antes de merge

---

## 3. Ficheros Clave

| Fichero | Cambio |
|---------|--------|
| `docs/implementacion/2026-02-26_Plan_Implementacion_Auditoria_IA_Clase_Mundial_v1.md` | CREADO — Plan de 25 secciones, ~3,500 lineas |
| `docs/00_INDICE_GENERAL.md` | MODIFICADO — v106→v107, HAL block + changelog |
| `docs/00_DOCUMENTO_MAESTRO_ARQUITECTURA.md` | MODIFICADO — v76→v77, ASCII box 25 gaps + changelog |
| `docs/00_DIRECTRICES_PROYECTO.md` | MODIFICADO — v81→v82, 6 reglas nuevas + changelog |
| `docs/tecnicos/aprendizajes/2026-02-26_auditoria_ia_clase_mundial_25_gaps.md` | CREADO — Este documento |

---

## 4. Decisiones de Diseno

1. **Auditoria en 2 fases:** Primera fase identifica gaps contra competidores. Segunda fase verifica si el codigo ya existe. Evita crear duplicados.
2. **7+16+2 clasificacion:** Refinamiento (codigo existe, mejorar) vs Nuevo (crear) vs Infraestructura (scale). Prioriza refinamiento primero (menor esfuerzo, mayor impacto).
3. **Command Bar extensible:** Service collector pattern permite que cualquier modulo futuro registre un command provider sin modificar el core.
4. **Inline AI opt-in:** `getInlineAiFields()` retorna `[]` por defecto — los forms existentes no cambian. Solo subclases que overriden habilitan AI.
5. **ProactiveInsight como entity:** ContentEntity vs custom table. Entity gana: Field UI, Views, admin routes, access handlers, revisions (futuro).
6. **Voice client-first:** Web Speech API evita enviar audio al servidor. Privacy-first, baja latencia, zero API cost para STT.
7. **Prompt Regression con fixtures:** Cambios en prompts son cambios en comportamiento. Las fixtures actuan como "contract tests" para prompts.

---

## 5. Verificacion

- Plan de implementacion creado con 25 secciones cubriendo los 25 gaps
- Tabla de cumplimiento con 30+ directrices verificadas
- Tabla de correspondencia tecnica mapeando gaps a archivos existentes
- IDs coherentes GAP-AUD-001 a GAP-AUD-025 en todo el documento
- Estimacion de horas coherente entre seccion 1.5 y seccion 20 (320-440h total)
- 4 documentos actualizados con versiones incrementadas

---

## 6. Cross-References

- Aprendizaje #129: Elevacion IA 23 FIX items
- Aprendizaje #130: Auditoria post-implementacion IA
- Aprendizaje #131: Canvas Editor Content Hub
- Aprendizaje #132: Meta-Sitio plataformadeecosistemas.es
- Aprendizaje #133: 10 GAPs Streaming + MCP + Native Tools
- Plan de implementacion: `docs/implementacion/2026-02-26_Plan_Implementacion_Auditoria_IA_Clase_Mundial_v1.md`
- Directrices v82.0.0, Arquitectura v77.0.0, Indice v107.0.0
