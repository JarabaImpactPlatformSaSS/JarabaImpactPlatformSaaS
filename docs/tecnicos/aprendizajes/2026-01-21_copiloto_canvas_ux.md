# Aprendizajes: Copiloto Canvas UX

**Fecha:** 2026-01-21  
**Módulo:** `jaraba_business_tools`  
**Versión:** v12.9

---

## 1. Integración Botón Header → FAB

### Problema
El botón "Analizar con IA" en el header del canvas abría el FAB pero lo cerraba inmediatamente.

### Causa Raíz
`canvas-editor.js` tenía una función `analyzeWithAi()` que hacía `location.reload()` después de la llamada API, cerrando el panel que el FAB había abierto.

### Solución
Usar **eventos custom** para comunicación entre módulos JS:

```javascript
// canvas-editor.js - Dispara evento
document.dispatchEvent(new CustomEvent('canvas-analyze-request', {
    detail: { canvasId: canvasId }
}));

// entrepreneur-agent-fab.js - Escucha evento
document.addEventListener('canvas-analyze-request', function (e) {
    container.openPanel();
    addMessage(chatMessages, Drupal.t('Analizar Canvas con IA'), 'user');
    handleAction('analyze_canvas', chatMessages, settings, panel);
});
```

### Lección
**Evitar `location.reload()`** cuando hay interacción con modales/paneles. Preferir actualización parcial del DOM o eventos.

---

## 2. Auto-Scroll en Chat

### Implementación
```javascript
setTimeout(function() {
    msg.scrollIntoView({ behavior: 'smooth', block: 'end' });
}, 50);
```

### Nota
El `setTimeout` con 50ms es necesario para que el DOM se actualice antes del scroll.

---

## 3. Rating Buttons (Feedback Loop)

### Estructura HTML generada
```html
<div class="response-rating">
    <span class="rating-label">¿Te fue útil?</span>
    <button class="rating-btn rating-up" data-rating="up" title="Sí, útil">👍</button>
    <button class="rating-btn rating-down" data-rating="down" title="No, mejorar">👎</button>
</div>
```

### SCSS
Estilos en `_agent-fab.scss` líneas 434-480:
- `.response-rating` - flex container
- `.rating-btn` - botones circulares con hover states
- `.rating-thanks` - feedback visual post-click

### TODO
Conectar con backend `CopilotMessage` entity para persistir ratings.

---

## 4. CanvasAiService - Sugerencias Contextuales

### Sectores Soportados (7)
1. `comercio`
2. `servicios`
3. `agro`
4. `tecnologia`
5. `hosteleria`
6. `formacion`
7. `general` (fallback)

### Bloques con Sugerencias (9)
Cada sector tiene sugerencias para todos los bloques del Business Model Canvas:
- customer_segments
- value_propositions
- channels
- customer_relationships
- revenue_streams
- key_resources
- key_activities
- key_partners
- cost_structure

### Fallback Inteligente
```php
return $defaults[$sector][$blockType] ?? $defaults['general'][$blockType] ?? [];
```

---

## 5. PDF Export con Branding

### Localización de Librerías
SortableJS y html2pdf.js se integran **localmente** en `libraries/` del módulo para evitar dependencias CDN:
- `libraries/sortable/Sortable.min.js`
- `libraries/html2pdf/html2pdf.bundle.min.js`

### Header PDF
- Título del canvas
- Nombre del propietario
- Sector y etapa
- Versión y completitud

### Footer PDF
- "Jaraba Impact Platform" centrado
- Copyright dinámico con año
- Número de página

### Limitación SVG
Los iconos SVG no se renderizan correctamente con `html2canvas`. Solución: excluirlos del export o usar data URIs.

---

## 6. Estilos para Response Wrapper

### Clases añadidas a `_agent-fab.scss`
```scss
.agent-response-wrapper { margin-bottom: 8px; }
.tip-message { 
    background: var(--ej-color-bg-muted, #f3f4f6);
    border-left: 3px solid var(--ej-color-primary, #0ea5e9);
}
.follow-up { font-style: italic; color: var(--ej-text-muted); }
.response-actions { display: flex; flex-wrap: wrap; gap: 8px; }
.response-cta { 
    background: var(--ej-color-primary);
    border-radius: 20px;
}
```

---

## Archivos Modificados

| Archivo | Cambios |
|---------|---------|
| `entrepreneur-agent-fab.js` | Auto-scroll, `addAgentResponse()`, rating buttons, evento custom |
| `canvas-editor.js` | `analyzeWithAi()` → evento custom |
| `_agent-fab.scss` | Estilos response-wrapper, tip, follow-up, actions, CTA |
| `CanvasAiService.php` | `getFallbackAnalysis()`, `detectBasicIncoherences()`, 7 sectores |

---

## Verificación

✅ FAB abre y permanece abierto  
✅ Botón header dispara análisis en chat  
✅ Auto-scroll funciona  
✅ Rating buttons 👍👎 visibles  
✅ Sin errores en consola  
✅ Sin `location.reload()`
