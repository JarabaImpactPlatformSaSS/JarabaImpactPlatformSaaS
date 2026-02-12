# Sprint C4: IA Asistente Integrada — Page Builder

**Fecha:** 2026-02-11  
**Módulo:** `jaraba_page_builder`  
**Contexto:** Plan de Mejoras Page/Site Builder v3.1 — Fase 4 (IA)  
**Aprendizaje #:** 59

---

## Resumen

Implementación completa del Sprint C4 (IA Asistente Integrada) que extiende el Page Builder con 4 funcionalidades de IA:
- **C4.1** Sugerencias SEO con IA
- **C4.2** Generación de Templates con IA
- **C4.3** Selectores de Vertical y Tono
- **C4.4** Prompt-to-Page (generación de página completa)

---

## Archivos Creados / Modificados

### Nuevos (Backend)
| Archivo | LOC | Propósito |
|---------|-----|-----------|
| `src/Service/SeoSuggestionService.php` | ~370 | Analiza HTML con LLM, genera sugerencias SEO accionables. Fallback heurístico (H1, meta, alt, headings, keywords, links) |
| `src/Service/AiTemplateGeneratorService.php` | ~470 | Genera landing pages HTML+CSS desde prompt. Integra ContentGrounding + BrandVoice. Fallback con 6 secciones |

### Modificados (Backend)
| Archivo | Cambio |
|---------|--------|
| `jaraba_page_builder.services.yml` | +2 servicios registrados (`seo_suggestion`, `ai_template_generator`) |
| `jaraba_page_builder.routing.yml` | +3 rutas API (seo-ai-suggest, ai/generate-template, ai/generate-page) |
| `src/Controller/AiContentController.php` | +3 endpoints, +2 propiedades, DI actualizado en `create()` |

### Modificados (Frontend)
| Archivo | Cambio |
|---------|--------|
| `js/grapesjs-jaraba-ai.js` | Upgrade v1→v2 (+240 LOC): selectores vertical/tono, mode toggle, prompt-to-page, panel SEO |
| `templates/canvas-editor.html.twig` | +botón 🤖 "SEO IA" en toolbar, conectado a comando GrapesJS |

---

## Lecciones Aprendidas

### 1. Patrón IA consistente para servicios backend
Todos los servicios de IA del módulo siguen el mismo patrón reutilizable:
1. `\Drupal::service('ai.provider')` → `getDefaultProviderForOperationType('chat')`
2. `ChatInput` + `ChatMessage` (system + user)
3. `ContentGroundingService` (opcional, enriquece prompts con datos reales)
4. `CopilotQueryLoggerService` (opcional, auditoría de queries)
5. `TenantBrandVoiceService` (opcional, adapta tono por vertical)
6. Fallback robusto cuando el proveedor IA no está configurado

### 2. Inyección condicional de servicios en controladores
Los nuevos servicios se inyectan mediante `$container->has()` en el `create()`, siguiendo el patrón existente del módulo. Esto permite que el controlador funcione incluso si los servicios opcionales no están disponibles.

### 3. Frontend toggle de modo en modales GrapesJS
El toggle Sección vs Página completa usa un patrón de radio buttons con CSS que muestra/oculta la configuración de secciones:
```javascript
document.querySelectorAll('[name=ai-mode]').forEach(r => {
    r.addEventListener('change', (e) => {
        sectionsCfg.style.display = e.target.value === 'full-page' ? 'block' : 'none';
    });
});
```

### 4. Botones toolbar Twig → comandos GrapesJS
Para conectar botones Twig de la toolbar externa con comandos GrapesJS, el patrón es:
1. Añadir `<button id="xxx">` en el Twig
2. En el plugin JS: `document.getElementById('xxx').addEventListener('click', () => editor.runCommand('cmd'))`
3. El comando debe estar registrado previamente con `editor.Commands.add()`

### 5. Vertical auto-selección desde drupalSettings
El selector de vertical se auto-selecciona basándose en `drupalSettings.jarabaCanvas.vertical`, aprovechando datos que ya están disponibles en el contexto del Page Builder.

---

## Dependencias entre Servicios

```
SeoSuggestionService
├── @ai.provider (chat)
├── jaraba_copilot_v2.content_grounding (opcional)
├── jaraba_copilot_v2.query_logger (opcional)
└── @logger.factory

AiTemplateGeneratorService
├── @ai.provider (chat)
├── jaraba_copilot_v2.content_grounding (opcional)
├── jaraba_ai_agents.brand_voice (opcional)
├── jaraba_copilot_v2.query_logger (opcional)
└── @logger.factory
```

---

## Rutas API Añadidas

| Ruta | Método | Controller | Permiso |
|------|--------|------------|---------|
| `/api/v1/page-builder/seo-ai-suggest` | POST | `seoSuggest()` | `access page builder` |
| `/api/v1/page-builder/ai/generate-template` | POST | `generateAITemplate()` | `access page builder` |
| `/api/v1/page-builder/ai/generate-page` | POST | `generateFullPage()` | `access page builder` |

---

## Completitud del Plan v3.1

Con el Sprint C4, el Plan de Mejoras Page/Site Builder v3.1 queda **100% completado**:

| Fase | Sprints | Estado |
|------|---------|--------|
| Fase 1: UX Polish | A1, A2, A3, C3 | ✅ 100% |
| Fase 2: Site Builder Premium | B1, B2 | ✅ 100% |
| Fase 3: Features Avanzadas | C1, C2 | ✅ 100% |
| Fase 4: IA | C4 (C4.1-C4.4) | ✅ 100% |
