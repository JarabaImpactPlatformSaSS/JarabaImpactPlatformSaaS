# 🔍 Análisis Estratégico: Constructor de Sitios de Clase Mundial

**Fecha:** 2026-02-03  
**Autor:** Análisis Multi-disciplinar (Negocio, Finanzas, Arquitectura, UX, Drupal, SEO, IA)  
**Versión:** 1.1

---

## 📊 Resumen Ejecutivo

El ecosistema de construcción tiene **DOS módulos** que trabajan en conjunto:

| Módulo | Propósito | Estado | Rutas Frontend |
|--------|-----------|--------|----------------|
| **Page Builder** | Crear páginas con templates/bloques | Funcional | `/page-builder`, `/my-pages` |
| **Site Builder** | Organizar estructura del sitio | Funcional | `/site-builder`, `/tree`, `/config` |

### Matriz de Madurez

| Dimensión | Estado Actual | Clase Mundial | Prioridad |
|-----------|---------------|---------------|-----------|
| Templates disponibles | 70+ | ✅ Suficiente | — |
| Experiencia edición | 3/10 (Formularios) | 9/10 (Visual) | **P0** |
| Drag-and-drop | 0/10 | 9/10 | **P0** |
| Preview en tiempo real | 2/10 | 9/10 | **P0** |
| Generación IA | 5/10 (Existe agent) | 9/10 (Proactivo) | P1 |
| Site Structure | 6/10 (Site Builder) | 8/10 | P2 |

---

## 🔬 Benchmark: Constructores Clase Mundial (2025-2026)

### Patrones UX Identificados

| Plataforma | Modelo | Diferenciador | Aplicabilidad |
|------------|--------|---------------|---------------|
| **Webflow** | Canvas visual + clases CSS | Control pixel-perfect | Alta (profesionales) |
| **Wix ADI** | Chat IA → sitio completo | Zero-friction para SMB | **Muy Alta** |
| **Framer** | Wireframer IA + animaciones | Motion design | Media |
| **Squarespace** | Blueprint AI + estética | Estética premium | Alta |

### Features Críticas para Clase Mundial

1. **AI-First Onboarding**: Usuario describe negocio → IA genera sitio completo
2. **Canvas Visual**: Sidebar de bloques + iframe preview en vivo
3. **Edición Inline**: Click en texto → editar directo
4. **Responsive Preview**: Toggle desktop/tablet/mobile instantáneo
5. **Auto-Save**: Guardado optimista sin interrupciones

---

## 🏛️ Arquitectura del Ecosistema Dual

### Page Builder (`jaraba_page_builder`)
```
/page-builder              → Dashboard con acciones rápidas
/page-builder/templates    → Picker visual de 70+ templates  
/my-pages                  → Listado de páginas del tenant
/page/{id}                 → Vista canónica de página
/page/{id}/edit (FALTA)    → Canvas Editor visual ❌
```

### Site Builder (`jaraba_site_builder`)
```
/site-builder              → Dashboard estructura 
/site-builder/tree         → Árbol drag-drop de navegación ✅
/site-builder/config       → Configuración global del sitio
/site-builder/redirects    → Gestión de redirects SEO
/site-builder/sitemap      → Visualización sitemap
```

### Integración Entre Módulos
- Site Builder consume páginas de Page Builder via `getAvailablePages`
- Page Builder Dashboard tiene enlace a Site Builder (`/site-builder`)
- Ambos comparten layout frontend limpio (Zero Region Policy)

---

## 🔐 Arquitectura Multi-Tenant

El SaaS utiliza `TenantContextService` para aislar datos por organización:

| Componente | Contexto Tenant | Implementación |
|------------|-----------------|----------------|
| **Site Builder** | ✅ Integrado | `SiteStructureService`, `SiteConfigApiController` |
| **Page Builder** | ✅ Integrado | `PageContent.uid`, control acceso por grupo |
| **AI Agents** | ✅ Integrado | `AgentOrchestrator.setTenantContext()` |
| **RAG/Copilot** | ✅ Integrado | Contexto inyectado automáticamente |

### Implicaciones para Canvas Editor

El nuevo Canvas Editor **DEBE**:
1. Heredar contexto tenant del `TenantContextService`
2. Filtrar templates disponibles según plan del tenant
3. Aplicar design tokens (colores, fonts) del tenant automáticamente
4. Aislar preview iframe para evitar cross-tenant leaks

---

## 🏗️ Arquitectura Actual vs Propuesta

### Flujo Actual (Fricción Alta)

```
/page-builder (Dashboard)
    ↓
/page-builder/templates (Picker visual)
    ↓
createFromTemplate() → Crea PageContent
    ↓
REDIRECT → /admin/content/pages/{id}/edit (Formulario Drupal)
    ↓
Guardar → Ver página
```

**Problemas:**
- Formulario admin Drupal = UX pobre
- Sin preview en tiempo real
- Edición de JSON content_data en campos ocultos
- Modo Multi-Block tiene template registrado pero **sin ruta frontend**

### Flujo Propuesto (Zero Friction)

```
/page-builder (Dashboard con métricas)
    ↓
/page-builder/create (Wizard IA simplificado)
    ├─ "¿Qué tipo de página quieres?" → Categorías
    ├─ "Elige un estilo" → Templates filtrados
    └─ IA sugiere contenido inicial
    ↓
/page/{id}/editor (Canvas Editor visual)
    ├─ Sidebar: Bloques arrastrables
    ├─ Canvas: Iframe con preview + Drop zones
    └─ Panel: Edición de bloque seleccionado
    ↓
Publicar → /page/{slug} (URL limpia)
```

---

## 🔴 Gaps Críticos Identificados

### Gap #1: Sin Canvas Editor Frontend (Prioridad P0)

**Estado:** Template `section_editor` registrado en hook_theme pero:
- Sin ruta frontend `/page/{id}/editor`
- Sin controller que lo renderice
- CSS/JS parcialmente implementado pero sin conexión

**Solución:** Crear ruta + controller para Canvas Editor visual

### Gap #2: Edición vía Formularios Drupal (Prioridad P0)

**Estado:** `createFromTemplate()` redirige a `/admin/content/pages/{id}/edit`

**Problema:** Formulario genérico Drupal, no experiencia visual

**Solución:** Interceptar edición → enviar a Canvas Editor

### Gap #3: Sin IA Proactiva en Creación (Prioridad P1)

**Estado:** `ContentWriterAgent` existe pero solo en modal de edición

**Solución:** Integrar IA desde el wizard inicial:
- "Describe tu negocio en 2-3 frases"
- IA genera Hero + About + CTA automáticamente

### Gap #4: Preview Separation (Prioridad P1)

**Estado:** Preview requiere guardar + abrir nueva pestaña

**Solución:** Iframe live con comunicación postMessage

---

## 📋 Plan de Implementación Priorizado

### Fase 1: Canvas Editor MVP (30-40h) — CRÍTICA

| Tarea | Descripción | Horas |
|-------|-------------|-------|
| 1.1 | Crear ruta `/page/{id}/editor` con controller | 4h |
| 1.2 | Template Twig con layout side-by-side | 4h |
| 1.3 | Sidebar: Lista de secciones con SortableJS | 8h |
| 1.4 | Canvas: Iframe preview con auto-refresh | 8h |
| 1.5 | CRUD: Añadir/editar/eliminar secciones via API | 10h |
| 1.6 | Viewport toggle (desktop/tablet/mobile) | 4h |

**Entregable:** Editor visual funcional con drag-and-drop

### Fase 2: Wizard IA de Creación (20-30h) — ALTA

| Tarea | Descripción | Horas |
|-------|-------------|-------|
| 2.1 | Nueva ruta `/page-builder/create` con wizard | 6h |
| 2.2 | Step 1: Selección categoría/template | 4h |
| 2.3 | Step 2: Input de contexto para IA | 4h |
| 2.4 | Step 3: Generación automática con preview | 10h |
| 2.5 | Redirect a Canvas Editor | 2h |

**Entregable:** Flujo "describe negocio → obtén página" en <2 minutos

### Fase 3: UX Polish (15-20h) — MEDIA

| Tarea | Descripción | Horas |
|-------|-------------|-------|
| 3.1 | Edición inline de textos en preview | 8h |
| 3.2 | Undo/Redo stack | 6h |
| 3.3 | Keyboard shortcuts | 4h |
| 3.4 | Animaciones micro-interacciones | 4h |

**Entregable:** Experiencia premium pulida

---

## 💰 Análisis Financiero

### Inversión
| Fase | Horas | Costo (@€80/h) |
|------|-------|----------------|
| Canvas Editor MVP | 40h | €3,200 |
| Wizard IA | 25h | €2,000 |
| UX Polish | 20h | €1,600 |
| **Total** | **85h** | **€6,800** |

### ROI Proyectado
- **Reducción churn**: UX premium retiene ~15% más usuarios
- **Diferenciación**: Constructor nativo vs dependencia Elementor/Webflow
- **Valor percibido**: Feature clase mundial justifica pricing premium

---

## 🎯 Decisión Requerida

> [!IMPORTANT]
> Se requiere aprobación para proceder con **Fase 1: Canvas Editor MVP** (40h / €3,200).

### Alternativas:

| Opción | Descripción | Inversión | Recomendación |
|--------|-------------|-----------|---------------|
| **A** | Canvas Editor completo (Fase 1-3) | 85h | ✅ Recomendada |
| **B** | Solo Canvas Editor MVP (Fase 1) | 40h | ✅ MVP viable |
| **C** | Mejorar formulario actual | 15h | ❌ No resuelve gap |
| **D** | Mantener estado actual | 0h | ❌ Deuda UX crítica |

---

## 📚 Referencias

- [Plan Canvas Visual v1](file:///z:/home/PED/JarabaImpactPlatformSaaS/docs/planificacion/20260203-Page_Builder_Canvas_Visual_v1.md)
- [Auditoría Page Builder](file:///z:/home/PED/JarabaImpactPlatformSaaS/docs/arquitectura/2026-01-28_auditoria_page_builder_clase_mundial.md)
- [Análisis Meta-Sitio](file:///z:/home/PED/JarabaImpactPlatformSaaS/docs/arquitectura/2026-02-02_analisis_estrategico_metasitio_clase_mundial.md)
