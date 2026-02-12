# Self-Discovery Bug Fixes + Copilot Proactivo

**Fecha:** 2026-01-25  
**Módulo:** `jaraba_self_discovery`  
**Sesión:** Debugging y mejora UX herramientas Self-Discovery

---

## Problemas Resueltos

### 1. Formulario Fortalezas - Bucle Infinito

**Síntoma:** El formulario no avanzaba de "1 de 20" y mostraba las mismas fortalezas repetidamente.

**Causa raíz:** Uso incorrecto de `$form_state->set()` para persistir datos entre rebuilds AJAX.

**Solución:**
```php
// ❌ INCORRECTO - No persiste correctamente
$form_state->set('current_step', $step);

// ✅ CORRECTO - Usar getStorage()/setStorage()
$storage = $form_state->getStorage();
$storage['current_step'] = $step;
$form_state->setStorage($storage);
```

**Lección:** En Drupal Form API con AJAX, siempre usar `$form_state->getStorage()` para datos que deben persistir entre rebuilds.

---

### 2. Gráfico RIASEC - Canvas Vacío

**Síntoma:** El canvas del gráfico hexagonal aparecía en el DOM pero sin contenido visual.

**Causa raíz:** Library dependía de `ecosistema_jaraba_core/chartjs` que no existía.

**Solución:**
```yaml
# jaraba_self_discovery.libraries.yml
riasec:
  version: 1.x
  js:
    https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js: { type: external, minified: true }
    js/riasec-chart.js: {}
```

**Lección:** Verificar siempre que las dependencias de library existan antes de usarlas. Drupal no falla silenciosamente pero tampoco es obvio el error.

---

### 3. Timeline Emojis

**Síntoma:** Los emojis de factores de impacto se veían inconsistentes en diferentes navegadores/fuentes.

**Solución:** Reemplazar emojis con SVG inline para consistencia cross-browser.

---

## Integración Copilot Proactivo

### Problema Original

El Copilot (agent-fab.js) solo devolvía mensajes genéricos:
> "Entendido. Estoy analizando tu consulta sobre: X"

No proporcionaba recomendaciones reales basadas en el perfil del usuario.

### Solución Implementada

1. **Nuevo endpoint API:** `/api/v1/self-discovery/copilot/context`

2. **SelfDiscoveryApiController::getCopilotContext():**
   - Detecta tipo de consulta (RIASEC, fortalezas, mejora)
   - Obtiene contexto completo del usuario vía `SelfDiscoveryContextService`
   - Genera respuesta proactiva con datos reales

3. **Respuestas contextuales:**

| Si pregunta sobre... | Respuesta incluye |
|---------------------|-------------------|
| Carrera/RIASEC | Código RIASEC, carreras sugeridas, fortalezas |
| Fortalezas | Top 5 con descripciones |
| Mejora | Áreas bajas de Rueda de Vida |

4. **agent-fab.js modificado:**
```javascript
// Antes: setTimeout con mensaje genérico
// Ahora: fetch() al endpoint contextual
fetch('/api/v1/self-discovery/copilot/context', {
    method: 'POST',
    body: JSON.stringify({ query: message })
})
.then(data => addAgentResponse(data.response));
```

---

## Servicios Clave

### SelfDiscoveryContextService

Agrega contexto completo del usuario para el Copilot:

```php
getFullContext(?int $uid): array
├── life_wheel → scores, lowest_areas
├── timeline → (localStorage, frontend)
├── riasec → code, scores, description
├── strengths → top5, top_strength
└── summary → texto para prompt IA
```

---

## Archivos Modificados

| Archivo | Cambio |
|---------|--------|
| `StrengthsAssessmentForm.php` | Reescrito usando `getStorage()` |
| `jaraba_self_discovery.libraries.yml` | Añadido Chart.js CDN |
| `jaraba_self_discovery.routing.yml` | Nueva ruta API copilot/context |
| `SelfDiscoveryApiController.php` | +170 líneas: endpoint contextual |
| `agent-fab.js` | fetch() a API en lugar de setTimeout |
| `jaraba_job_board.libraries.yml` | Versión 1.0 → 1.1 (cache bust) |

---

## Patrones Aprendidos

### 1. Form State Storage en Drupal
Para formularios multi-paso con AJAX, usar siempre:
- `$form_state->getStorage()` para leer
- `$form_state->setStorage($storage)` para escribir

### 2. Library Dependencies
Verificar existencia de dependencies antes de declararlas. Usar CDN externos para dependencias universales (Chart.js, D3).

### 3. Copilot Context Injection
Patrón para hacer copilotos "conscientes del contexto":
1. Servicio de contexto que agrega datos del usuario
2. Endpoint API que procesa consulta + contexto
3. Frontend que llama a API en lugar de respuestas hardcoded

### 4. Cache Busting Libraries
Incrementar versión en `.libraries.yml` para forzar recarga de JS cacheado.

---

## Verificación

- ✅ Fortalezas avanza correctamente (1→2→3→...→20)
- ✅ Gráfico RIASEC renderiza hexágono
- ✅ Copilot muestra perfil RIASEC y fortalezas reales
- ✅ Botones de acción funcionales (Ver ofertas, Explorar cursos)
