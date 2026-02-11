# Aprendizaje #58: FAQ Bot Contextual (G114-4)

**Fecha:** 2026-02-11
**Contexto:** Implementación del último gap de G114 — widget chat público en `/ayuda`
**Módulo:** `jaraba_tenant_knowledge`

---

## 1. Resumen

Se implementó un chat widget público (FAQ Bot) integrado en la página `/ayuda` del Centro de Ayuda. El bot responde preguntas de **clientes finales** del tenant usando **exclusivamente** la KB indexada en Qdrant (FAQs + Políticas), con escalación automática a humano cuando no puede responder.

Con este gap cerrado, **G114 está 100% completado** (4/4 gaps).

---

## 2. Archivos Creados (6)

| Archivo | Propósito |
|---------|-----------|
| `src/Service/FaqBotService.php` | Orquestación: embedding → Qdrant → LLM grounded → escalación |
| `src/Controller/FaqBotApiController.php` | API pública POST /api/v1/help/chat + feedback |
| `templates/faq-bot-widget.html.twig` | HTML del widget chat (FAB + panel) |
| `js/faq-bot.js` | Comportamiento frontend (Drupal.behaviors.faqBot) |
| `scss/_faq-bot.scss` | Estilos BEM (.faq-bot) |
| `css/faq-bot.css` | CSS compilado |

## 3. Archivos Modificados (6)

| Archivo | Cambio |
|---------|--------|
| `jaraba_tenant_knowledge.services.yml` | +servicio `faq_bot` con 7 dependencias DI |
| `jaraba_tenant_knowledge.routing.yml` | +2 rutas públicas (chat + feedback) |
| `jaraba_tenant_knowledge.libraries.yml` | +library `faq-bot` |
| `jaraba_tenant_knowledge.module` | +hook_theme `faq_bot_widget` + variables al tema `help_center` |
| `src/Controller/HelpCenterController.php` | +attach library + drupalSettings + tenant resolution |
| `templates/help-center.html.twig` | +include del widget al final |

---

## 4. Decisiones Arquitectónicas Clave

### 4.1 Servicio Standalone vs Extensión del Orchestrator

**Decisión:** `FaqBotService` como servicio standalone en `jaraba_tenant_knowledge`, NO como extensión del `CopilotOrchestratorService` de `jaraba_copilot_v2`.

**Razón:** Audiencias y objetivos completamente distintos:
- `jaraba_copilot_v2`: Para emprendedores — 5 modos creativos (coach, consultor, sparring, cfo, devil), normative RAG, desbloqueo progresivo
- FAQ Bot: Para clientes finales — respuestas estrictamente grounded, sin creatividad, sin modos

### 4.2 Scoring 3-tier con Umbrales Diferenciados

```
score >= 0.75  →  Respuesta grounded (LLM reformula KB)
0.55 <= score < 0.75  →  Baja confianza + sugerencia de contacto
score < 0.55  →  Escalación con datos de contacto del negocio
```

**Razón:** Un chatbot público NO puede alucinar. Es preferible escalar prematuramente que dar una respuesta incorrecta sobre políticas de devolución o precios.

### 4.3 LLM Haiku para Coste-Eficiencia

Se usa `claude-3-haiku` (no Sonnet/Opus) porque:
- El FAQ Bot solo **reformula** contenido existente de la KB
- No necesita razonamiento complejo
- Volumen alto (público, sin auth) = coste relevante
- max_tokens=512, temperature=0.3 refuerzan respuestas cortas y deterministas

---

## 5. Patrones Reutilizados

| Patrón | Origen | Reutilización |
|--------|--------|---------------|
| FAB widget flotante | `contextual-copilot.js` | Estructura JS, parseMarkdown(), toggle panel |
| Rate limiting público | `PublicCopilotController.php` | FloodInterface, 10 req/min/IP |
| Qdrant search con filtros tenant | `KnowledgeIndexerService.php` | generateEmbedding(), vectorSearch(), buildFilter() |
| BEM SCSS con tokens | `_contextual-copilot.scss` | Variables, responsive, animaciones |
| Session management | `SharedTempStoreFactory` | Historial conversación con TTL 1800s |

---

## 6. Reglas Derivadas

### CHAT-001: Chatbots Públicos SIEMPRE Grounded

Todo chatbot expuesto al público (sin autenticación) debe:
1. Usar SOLO contenido de la KB como fuente de verdad
2. Incluir system prompt con prohibición explícita de conocimiento general
3. Implementar scoring con umbral de escalación (no responder "no sé" genérico)
4. Rate limit por IP via Flood API

### RAG-001: Scoring 3-tier para Respuestas Públicas

Cuando el resultado de una búsqueda vectorial se expone al público:
- Umbral alto (≥0.75): respuesta directa
- Umbral medio (0.55-0.75): respuesta con disclaimer
- Umbral bajo (<0.55): NO responder, escalar

---

## 7. Lecciones Aprendidas

1. **Diferenciación explícita entre chatbots**: Documentar claramente qué chatbot es para qué audiencia evita confusión futura y duplicación de servicios.

2. **El patrón FAB es altamente reutilizable**: La estructura JS de `contextual-copilot.js` (behaviors, toggle, parseMarkdown, scroll) se replica con cambios mínimos. Considerar extraer un `BaseFabChat` compartido.

3. **z-index layering importa**: El FAQ Bot usa z-index 9998 (debajo del copilot contextual en 9999) para evitar colisiones en páginas donde ambos coexistan.

4. **Temperature baja + max_tokens bajo = respuestas deterministas**: Para reformulación de KB, temperature=0.3 y max_tokens=512 producen respuestas consistentes sin divagaciones.

5. **SharedTempStoreFactory para sesiones anónimas**: Perfecto para conversaciones sin auth — TTL configurable, almacenamiento servidor (no depende de cookies), cleanup automático.

---

## 8. Verificación

- PHP lint: todos los archivos pasan sin errores
- YAML validation: todos los YAML pasan
- `lando drush cr`: sin errores
- Servicio registrado: `get_class()` confirma `FaqBotService`
- G114 gaps: 4/4 completados
