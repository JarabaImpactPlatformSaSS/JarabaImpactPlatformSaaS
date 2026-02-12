# Aprendizaje: Integración Servicios IA - Patrones AgroConecta

**Fecha:** 2026-01-26  
**Contexto:** Implementación de gaps IA en copiloto público  
**Versión:** 1.0

---

## Resumen

Se implementaron 3 servicios IA inspirados en patrones de AgroConecta:

1. **parseMarkdown** - CTAs clickeables en respuestas IA
2. **CopilotQueryLoggerService** - Analytics de queries
3. **ContentGroundingService** - Grounding en contenido real

---

## Patrones Reutilizados

### 1. parseMarkdown (Frontend)

**Origen:** `agroconecta_core/copilot-chat.js`  
**Destino:** `ecosistema_jaraba_core/contextual-copilot.js`

```javascript
function parseMarkdown(text) {
  // 1. Extraer enlaces [texto](url) y protegerlos
  // 2. Escapar HTML del resto
  // 3. Restaurar enlaces como <a class="copilot-link">
  // 4. Convertir **negrita** y saltos de línea
}
```

**Clave:** Escapar HTML DESPUÉS de extraer enlaces para evitar XSS.

---

### 2. CopilotQueryLoggerService

**Origen:** `agroconecta_core/CopilotQueryLogger.php`  
**Destino:** `jaraba_copilot_v2/CopilotQueryLoggerService.php`

**Tabla BD:** `copilot_query_log`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| source | varchar(64) | public, emprendimiento, empleabilidad |
| query | mediumtext | Pregunta del usuario |
| response | mediumtext | Respuesta generada |
| context_data | text | JSON con contexto |
| mode | varchar(64) | Modo del copiloto |
| was_helpful | tinyint | 1=útil, 0=no útil, NULL=sin feedback |
| created | int | Timestamp |

**Métodos principales:**
- `logQuery()` - Guarda cada consulta
- `getStats()` - Estadísticas por período
- `getProblematicQueries()` - Queries con feedback negativo
- `getFrequentQuestions()` - Agrupación para identificar FAQs

---

### 3. ContentGroundingService

**Origen:** `ConsumerCopilotController::getContentContext()`  
**Destino:** `jaraba_copilot_v2/ContentGroundingService.php`

**Métodos:**
- `searchOffers()` - Busca ofertas de empleo publicadas
- `searchEmprendimientos()` - Busca proyectos de emprendedores
- `searchProducts()` - Busca productos Commerce

**Output de ejemplo:**
```
💼 OFERTAS DE EMPLEO DISPONIBLES:
• **Desarrollador Full Stack** en TechCorp [Ver oferta](/node/123)
```

---

## Arquitectura IA Actualizada

```
┌─────────────────────────────────────────────────────────┐
│                   PublicCopilotController               │
├─────────────────────────────────────────────────────────┤
│ ┌─────────────┐  ┌──────────────┐  ┌─────────────────┐  │
│ │QueryLogger  │  │ContentGround │  │CopilotOrchest.  │  │
│ │(Analytics)  │  │(Grounding)   │  │(Router Multi-AI)│  │
│ └──────┬──────┘  └──────┬───────┘  └────────┬────────┘  │
│        │                │                   │           │
│        ▼                ▼                   ▼           │
│ ┌──────────────────────────────────────────────────────┐│
│ │            CopilotCacheService (Redis)               ││
│ └──────────────────────────────────────────────────────┘│
│        │                │                   │           │
│        ▼                ▼                   ▼           │
│ ┌────────────┐  ┌────────────────┐  ┌────────────────┐  │
│ │copilot_    │  │ NodeStorage    │  │ Anthropic/     │  │
│ │query_log   │  │ ProductStorage │  │ OpenAI/Gemini  │  │
│ └────────────┘  └────────────────┘  └────────────────┘  │
└─────────────────────────────────────────────────────────┘
```

---

## Archivos Modificados/Creados

| Archivo | Acción | Líneas |
|---------|--------|--------|
| `CopilotQueryLoggerService.php` | ✅ Creado | ~290 |
| `ContentGroundingService.php` | ✅ Creado | ~310 |
| `jaraba_copilot_v2.install` | ✅ Creado | ~90 |
| `jaraba_copilot_v2.services.yml` | ✏️ Actualizado | +14 |
| `PublicCopilotController.php` | ✏️ Actualizado | +35 |
| `contextual-copilot.js` | ✏️ Actualizado | +34 |

---

## Checklist Pre-Implementación IA

> ⚠️ **REGLA**: Antes de implementar cualquier feature IA, verificar si existe en AgroConecta.

- [ ] ¿Existe servicio similar en AgroConecta?
- [ ] ¿Qué adaptaciones necesita para Jaraba?
- [ ] ¿Qué tablas BD necesita?
- [ ] ¿Se puede reusar el prompt?

---

## Tags

`#ia` `#patrones` `#agroconecta` `#copiloto` `#grounding` `#analytics`
