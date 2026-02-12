# Auditoría Frontend Multidisciplinar - Hallazgos Fase 1

**Fecha:** 2026-01-26  
**Tipo:** Aprendizaje / Informe de Auditoría  
**Estado:** COMPLETADO

---

## Resumen Ejecutivo

Auditoría del frontend del SaaS Jaraba Impact Platform verificando:
- Iconografía SVG vs Emojis
- Paleta de colores inyectable
- Consistencia de Copilot FAB
- Templates Clean Canvas vs Standard
- **Valor diferencial de IA contextual**

---

## 🤖 Valor Diferencial: SaaS Impulsado por IA Contextual

> **El SaaS Jaraba Impact Platform integra IA de forma nativa en cada vertical, ofreciendo copilotos especializados que entienden el contexto del usuario y proporcionan asistencia hiperpersonalizada.**

### Arquitectura IA Core

| Capa | Componente | Valor para el Usuario |
|------|------------|----------------------|
| **RAG Multi-Tenant** | `jaraba_rag` con Qdrant | Respuestas fundamentadas en datos reales, cero alucinaciones |
| **Smart Router** | `ModeDetectorService` | IA detecta automáticamente qué tipo de ayuda necesitas |
| **Multi-Proveedor** | Claude + Gemini + OpenAI | Sistema resiliente, siempre disponible 24/7 |
| **Grounding Validator** | Anti-alucinaciones | Solo respuestas verificables, nunca información inventada |

### Copilotos por Vertical

#### 1. **Empleabilidad** - AI Copilot del Candidato
| Capacidad | Qué hace por ti |
|-----------|-----------------|
| Profile Coach | Mejora tu CV y perfil profesional con sugerencias personalizadas |
| Job Advisor | Recomienda ofertas basándose en tu perfil, no keywords genéricos |
| Interview Prep | Prepara entrevistas simuladas según el puesto específico |
| Learning Guide | Sugiere cursos que realmente necesitas para subir de nivel |
| Application Helper | Redacta cartas de presentación adaptadas a cada oferta |

**Patrones implementados:**
- Sistema RAG con grounding estricto
- Acciones accionables con deep links
- Tono empático en rechazos

#### 2. **Emprendimiento** - Copiloto de Negocio v2
| Capacidad | Qué hace por ti |
|-----------|-----------------|
| 5 Modos Adaptativos | Coach Emocional / Consultor / Sparring / CFO / Abogado del Diablo |
| 44 Experimentos | Validación guiada basada en "Testing Business Ideas" de Osterwalder |
| Desbloqueo Progresivo | Funcionalidades según tu fase del programa |
| Dashboard BMC | Visualización del estado de validación de tu modelo de negocio |

**Patrones implementados:**
- Contexto dinámico inyectado (carril, DIME, bloqueos)
- Detección emocional automática
- Test Cards + Learning Cards digitales

#### 3. **Commerce** - Consumer Copilot
| Capacidad | Qué hace por ti |
|-----------|-----------------|
| Búsqueda Semántica | Encuentra productos por intención, no solo por palabras |
| Recomendaciones | Productos sugeridos según tu perfil de compra |
| FAQ Inteligente | Respuestas sobre la tienda fundamentadas en datos reales |
| Asistente de Compra | Guía conversacional para encontrar lo que buscas |

**Patrones implementados:**
- Aislamiento multi-tenant de Knowledge Base
- Answer Capsules para respuestas SEO-ready
- Filosofía "Gourmet Digital" - IA invisible, protagonismo del producto

### Módulos IA Implementados

```
web/modules/custom/
├── jaraba_copilot_v2/     # Copiloto Emprendimiento (15 servicios)
│   ├── CopilotOrchestratorService.php (36KB)
│   ├── ModeDetectorService.php        # Smart Router
│   ├── NormativeRAGService.php        # RAG semántico
│   ├── EntrepreneurContextService.php # Contexto dinámico
│   └── FeatureUnlockService.php       # Desbloqueo progresivo
├── jaraba_rag/            # RAG Multi-Tenant (5 servicios)
│   ├── JarabaRagService.php           # Orquestador principal
│   ├── GroundingValidator.php         # Anti-alucinaciones
│   ├── QueryAnalyticsService.php      # Detección de gaps
│   └── TenantContextService.php       # Aislamiento por tenant
├── jaraba_matching/       # Matching IA (6 servicios)
│   ├── EmbeddingService.php           # Vectorización
│   ├── MatchingService.php            # Job-Candidate match
│   └── RecommendationService.php      # Recomendaciones
└── ai_provider_google_gemini/         # Proveedor IA alternativo
```

### Comunicación del Valor en Frontend

**Patrones establecidos a seguir:**

1. **`_features.html.twig`** - Card "IA Integrada":
   - Copy: *"Copiloto de carrera, matching inteligente, asistente de emprendimiento y recomendaciones personalizadas"*
   - Badge: "Con IA"

2. **`jaraba-rag-dashboard.html.twig`** - Sección Información:
   - Explica tecnologías (Qdrant, Grounding, Multi-Tenant)
   - Formato: icono + título + descripción del beneficio

3. **`_copilot-fab.html.twig`** - FAB contextual:
   - Disponible 24/7
   - Acciones específicas según vertical
   - Greeting personalizado por contexto

### ✅ Copiloto Público: Ya Reutiliza Patrones de Verticales

El copiloto de la landing (`/api/public-copilot/chat`) **ya está integrado** con la arquitectura de los copilotos de verticales:

| Componente | Reutilizado de | Implementación |
|------------|----------------|----------------|
| `CopilotOrchestratorService` | Emprendimiento v2 | Mismo servicio, modo `landing_copilot` |
| Rate Limiting | Patrón core | 10 req/min por IP |
| Suggestions | Empleabilidad | Botones de acción contextuales |
| Feedback Loop | RAG Analytics | Tabla `jaraba_copilot_feedback` |
| Detección B2B/B2C | Patrones de intención | Keywords para instituciones/consultores |

**Archivo:** `jaraba_copilot_v2/src/Controller/PublicCopilotController.php` (427 líneas)

```php
// El copiloto público YA usa el mismo orquestador que los verticales
$response = $this->copilotOrchestrator->chat(
    $this->buildPublicEnrichedMessage($message, $context),
    $publicContext,
    'landing_copilot'  // Modo específico para embudo de ventas
);
```

**System Prompt implementado:**
- Detecta tipo de visitante (candidato, emprendedor, productor, institución, consultor)
- Enfatiza valor de Jaraba según perfil detectado
- Nunca menciona competidores
- Siempre termina con siguiente paso concreto

---

## Hallazgos Principales

### ✅ Tema Principal Limpio

El tema `ecosistema_jaraba_theme/templates/` **NO tiene emojis**.
Todos los partials (`_header.html.twig`, `_copilot-fab.html.twig`, etc.) usan iconos SVG.

### ⚠️ Emojis en Módulos Custom (~50 instancias)

| Módulo | Archivo | Emojis | Prioridad |
|--------|---------|--------|-----------|
| jaraba_mentoring | mentor-catalog.html.twig | 👤⭐🎯 | Alta |
| jaraba_mentoring | session-booking.html.twig | 👤⭐✅🎉⏱️ | Alta |
| jaraba_foc | foc-dashboard.html.twig | 💰📈📊⚖️⏱️⚠️ | Media |
| jaraba_candidate | my-profile-empty.html.twig | ✨🎯 | Alta |
| ecosistema_jaraba_core | finops-dashboard.html.twig | 📊⚠️🚨⚙️✓⭐ | Baja |
| ecosistema_jaraba_core | marketplace-product.html.twig | ❤️✨🛡️↩️✅ | Media |

**Acción**: Reemplazar con `jaraba_icon()`:
```twig
{{ jaraba_icon('actions', 'star', { color: 'impulse', size: '18px' }) }}
```

### 🟡 Colores Hardcodeados en SCSS

~286 ocurrencias en archivos SCSS. La mayoría en `admin-settings.scss`.

**Categorías:**
1. ✅ Con `var()` y fallback - Correcto
2. ⚠️ Sin `var()` - A revisar

---

## Verificación Browser

| Página | Emojis | Paleta | Copilot FAB | Contenido |
|--------|--------|--------|-------------|-----------|
| `/jobseeker` | ✅ | ✅ | ✅ | ❌ Vacío |
| `/employer` | ✅ | ✅ | ✅ | ✅ OK |
| `/entrepreneur/dashboard` | ⚠️ 📌 | ✅ | ✅ | ✅ OK |

---

## Problema Crítico: icons.css

**Error en todas las páginas:**
```
Refused to apply style from 'icons.css' because its MIME type ('text/html')
```

El archivo no existe o la ruta está mal configurada en el tema.

**Solución**: Verificar `ecosistema_jaraba_theme.libraries.yml` y la existencia del archivo.

---

## Lecciones Aprendidas

1. **Separación módulo/tema correcta**: `jaraba_theming` (lógica) vs `ecosistema_jaraba_theme` (assets)
2. **Tema principal limpio**: Los partials del tema principal cumplen estándares
3. **Emojis en módulos**: Se concentran en dashboards específicos, no en el core
4. **Error icons.css**: Presente en todas las rutas, debe corregirse

---

## ✅ Correcciones Realizadas (2026-01-26)

### 1. Error icons.css CORREGIDO
- **Problema**: Archivo referenciado en libraries pero inexistente
- **Solución**: Creado `css/icons.css` con clases utilitarias para iconos

### 2. Dashboard /jobseeker CORREGIDO
- **Problema**: Contenido vacío (template no registrado)
- **Solución**: 
  - Añadido `jobseeker_dashboard` a hook_theme() en jaraba_candidate.module
  - Corregido route name en theme suggestions: `jaraba_job_board.jobseeker_dashboard` → `jaraba_candidate.dashboard`

### 3. Emojis Migrados a SVG (~25 instancias)

| Módulo | Archivo | Emojis | Estado |
|--------|---------|--------|--------|
| jaraba_candidate | my-profile-empty.html.twig | ✨📄🎯 | ✅ Migrado |
| jaraba_candidate | jaraba_candidate.module | 🎯 | ✅ Migrado |
| jaraba_self_discovery | self-discovery-dashboard.html.twig | ℹ️ (x2) | ✅ Migrado |
| jaraba_mentoring | mentor-catalog.html.twig | 👤⭐📅🎯 | ✅ Migrado |
| jaraba_mentoring | session-booking.html.twig | 👤⭐📅✅⭐⏱️💬📅🎉 | ✅ Migrado |
| jaraba_rag | jaraba-rag-dashboard.html.twig | 💬⚡✅🎯📭 | ✅ Migrado |

### Emojis Pendientes (~50 en baja prioridad)

| Módulo | Archivo | Prioridad |
|--------|---------|-----------|
| jaraba_foc | foc-dashboard.html.twig | 📊 Baja (FinOps interno) |
| ecosistema_jaraba_core | finops-dashboard.html.twig | 📊 Baja (Admin) |
| ecosistema_jaraba_core | diagnostic-widget.html.twig | 📊 Baja (Demo) |
| ecosistema_jaraba_core | marketplace-product.html.twig | 🟡 Media |
| ecosistema_jaraba_core | tenant-self-service-dashboard.html.twig | 📊 Baja |
| jaraba_lms | badge-verification.html.twig | 📊 Baja |

---

## Próximos Pasos

1. [x] ~~Corregir error `icons.css`~~ ✅
2. [x] ~~Investigar /jobseeker contenido vacío~~ ✅
3. [x] ~~Migrar emojis en módulos prioritarios~~ ✅ (25 migrados)
4. [x] ~~Landing pages verticales con layout full-width~~ ✅ (5 landing pages)
5. [x] ~~Iconos SVG representativos para landing pages~~ ✅ (12+ iconos creados)
6. [ ] Migrar emojis restantes (~50 en módulos baja prioridad)
7. [ ] Verificar copilots en cada contexto

---

## Referencias

- [Plan de Auditoría](./20260126-Plan_Auditoria_Frontend_Multidisciplinar_v1_Claude.md)
- [Arquitectura Frontend Extensible](./2026-01-25_arquitectura_frontend_extensible.md)
