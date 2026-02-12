# 160. Herramientas de Autodescubrimiento IA - Empleabilidad

**Fecha:** 2026-01-25  
**Versión:** 1.0  
**Vertical:** Empleabilidad  
**Módulo:** `jaraba_self_discovery`  
**Estimación:** ~84h

---

## 0. Decisiones de Implementación ✅

| Decisión | Valor | Notas |
|----------|-------|-------|
| **Orden de implementación** | Óptimo técnico | 1. Rueda de Vida → 2. Timeline → 3. RIASEC → 4. Fortalezas |
| **Integración con Skills** | Automática | Competencias identificadas se añaden al perfil de skills |
| **Control de acceso** | Desbloqueo progresivo | Ligado al plan del tenant (Basic, Pro, Enterprise) |

### Desbloqueo por Plan

| Plan | Herramientas Disponibles |
|------|--------------------------|
| **Basic** | Rueda de la Vida |
| **Pro** | + Timeline de Vida + RIASEC |
| **Enterprise** | + Assessment de Fortalezas + Exportación PDF |

---

## 1. Resumen Ejecutivo

Sistema de herramientas de autodescubrimiento profesional basadas en metodologías reconocidas:

| Metodología | Fuente | Herramientas |
|-------------|--------|--------------|
| **Línea de Vida** | Osterwalder - "Tu Modelo de Negocio" | Rueda de la Vida, Ansia Secreta, Roles, Timeline, Intereses, Competencias |
| **StrengthsFinder** | Clifton/Gallup | Assessment de Talentos, Top 5 Fortalezas |
| **RIASEC** | Holland | Test de Intereses Vocacionales |

---

## 2. Herramientas Detalladas

### 2.1 Rueda de la Vida (8 Áreas)

**Propósito:** Evaluar satisfacción en las principales áreas de la vida.

| Área | Descripción |
|------|-------------|
| Trabajo/Carrera | Satisfacción profesional actual |
| Finanzas | Estabilidad económica |
| Salud | Bienestar físico y mental |
| Familia | Relaciones familiares |
| Social | Amistades y redes |
| Desarrollo Personal | Crecimiento y aprendizaje |
| Ocio | Tiempo libre y hobbies |
| Entorno | Ambiente físico de vida |

**UX:** Radar chart interactivo con sliders 1-10 por área.

### 2.2 Timeline de Vida

**Propósito:** Identificar patrones en momentos significativos.

**Campos por evento:**
- Fecha/Período
- Descripción
- Tipo: Álgido/Bajo
- Categoría: Personal/Profesional
- Emociones asociadas
- Aprendizajes

**Análisis IA:** Identificación de patrones, competencias derivadas, valores subyacentes.

### 2.3 Test RIASEC (Holland)

**Propósito:** Mapear intereses vocacionales en 6 dimensiones.

| Tipo | Descripción | Carreras típicas |
|------|-------------|------------------|
| **R**ealista | Práctico, mecánico | Ingeniería, construcción |
| **I**nvestigador | Analítico, científico | Investigación, medicina |
| **A**rtístico | Creativo, expresivo | Diseño, artes, escritura |
| **S**ocial | Colaborador, empático | Educación, RRHH, salud |
| **E**mprendedor | Líder, persuasivo | Ventas, dirección, startups |
| **C**onvencional | Organizado, metódico | Administración, finanzas |

**UX:** 42 preguntas + Hexágono de resultados.

### 2.4 Assessment de Fortalezas

**Propósito:** Identificar los 5 talentos dominantes del candidato.

**Formato:** 40 preguntas situacionales (A vs B).

**Resultado:** Top 5 fortalezas con:
- Descripción detallada
- Cómo aplicar en el trabajo
- Posibles puntos ciegos
- Roles ideales

---

## 3. Arquitectura Técnica

### 3.1 Content Entities

```php
// LifeWheelAssessment
id, user_id, scores (8 campos 1-10), notes, created

// LifeTimeline  
id, user_id, events (JSON), ai_analysis, created

// InterestProfile (RIASEC)
id, user_id, answers (JSON), scores (6), dominant_types, created

// StrengthAssessment
id, user_id, answers (40), top_strengths (5), created
```

### 3.2 Servicios

| Servicio | Responsabilidad |
|----------|-----------------|
| `LifeWheelService` | Cálculo de scores, historial |
| `TimelineAnalysisService` | Análisis IA de patrones |
| `RiasecService` | Scoring Holland, sugerencias |
| `StrengthAnalysisService` | Algoritmo de talentos |
| `SelfDiscoveryIntegrationService` | Conexión con Skills |

### 3.3 Rutas

| Ruta | Herramienta |
|------|-------------|
| `/my-profile/self-discovery` | Dashboard |
| `/my-profile/self-discovery/life-wheel` | Rueda de la Vida |
| `/my-profile/self-discovery/timeline` | Timeline |
| `/my-profile/self-discovery/interests` | RIASEC |
| `/my-profile/self-discovery/strengths` | Fortalezas |

### 3.4 Integración Copilot IA

| Herramienta | Modo Copilot |
|-------------|--------------|
| Rueda de la Vida | Coach Emocional |
| Ansia Secreta | Coach Emocional |
| Timeline | Consultor Táctico |
| Intereses RIASEC | Consultor Táctico |
| Fortalezas | Sparring Partner |

---

## 4. Fases de Implementación

| Fase | Descripción | Horas |
|------|-------------|-------|
| 1 | Fundamentos (módulo, entities, routing) | 8h |
| 2 | Rueda de la Vida (chart radar, historial) | 16h |
| 3 | Timeline de Vida (D3.js, análisis IA) | 20h |
| 4 | Test RIASEC (42 items, hexágono) | 16h |
| 5 | Assessment Fortalezas (40 preguntas) | 16h |
| 6 | Dashboard integrado + PDF | 8h |

**Total: ~84h**

---

## 5. Dependencias

| Dependencia | Propósito |
|-------------|-----------|
| `jaraba_candidate` | Perfil del candidato |
| `jaraba_copilot_v2` | Integración IA |
| `chart.js` | Radar chart |
| `d3.js` | Timeline interactivo |

---

## 6. Verificación

### Automatizada
- Unit tests para servicios de cálculo
- Integración tests para APIs

### Manual
1. Completar Rueda de la Vida → verificar chart
2. Añadir eventos al timeline → ver análisis IA
3. Completar cuestionario RIASEC → ver hexágono
4. Realizar assessment → ver Top 5
5. Verificar dashboard unificado

---

## 7. Referencias

- **Tu Modelo de Negocio** - Osterwalder, Pigneur
- **Descubre tus Fortalezas** - Clifton, Gallup
- **Making Vocational Choices** - Holland (RIASEC)

---

## 8. Estado de Implementación (Actualizado 2026-01-25)

### ✅ Herramientas Completadas

| Herramienta | Estado | Archivos Principales |
|-------------|--------|---------------------|
| **Dashboard** | ✅ Completado | `SelfDiscoveryController.php`, `self-discovery-dashboard.html.twig` |
| **Rueda de la Vida** | ✅ Completado | `LifeWheelAssessmentForm.php`, `life-wheel-chart.js` |
| **Timeline de Vida** | ✅ Completado | `timeline-interactive.js`, `self-discovery-timeline.html.twig` |
| **RIASEC** | ✅ Completado | `InterestsAssessmentForm.php`, `riasec-chart.js` |
| **Fortalezas** | ✅ Completado | `StrengthsAssessmentForm.php` |

### Detalles Técnicos

#### RIASEC (Mis Intereses Vocacionales)
- **36 preguntas** organizadas en 6 categorías (R-I-A-S-E-C)
- **Sliders con escala numérica 1-5** visible y etiquetas "Nada/Mucho"
- **Gráfico hexagonal** con Chart.js para resultados
- **Código de 3 letras** generado (ej: SAE, RIC)
- **Sugerencias de carreras** según perfil

#### Fortalezas (Mis 5 Talentos)
- **24 fortalezas** basadas en VIA Character Strengths
- **Sistema de comparación de pares** (A vs B)
- **20 rondas de selección** con barra de progreso
- **Top 5 ranking** con descripciones y consejos aplicación
- **Estilos premium** con animaciones y hover effects

### Archivos Creados/Modificados

```
web/modules/custom/jaraba_self_discovery/
├── src/Form/
│   ├── InterestsAssessmentForm.php  [NUEVO]
│   └── StrengthsAssessmentForm.php  [NUEVO]
├── js/
│   └── riasec-chart.js              [NUEVO]
├── scss/
│   └── self-discovery.scss          [ACTUALIZADO - +400 líneas RIASEC/Fortalezas]
├── templates/
│   ├── self-discovery-interests.html.twig  [ACTUALIZADO]
│   └── self-discovery-strengths.html.twig  [ACTUALIZADO]
└── jaraba_self_discovery.libraries.yml     [ACTUALIZADO - librería riasec]
```

### Iconos Personalizados Creados

```
web/modules/custom/ecosistema_jaraba_core/images/icons/analytics/
├── life-wheel.svg         [NUEVO - rueda de 8 segmentos]
├── life-wheel-duotone.svg [NUEVO - versión duotone]
├── timeline.svg           [NUEVO - línea temporal con puntos]
└── timeline-duotone.svg   [NUEVO - versión duotone]
```

---

## 9. Integración Copilot Proactivo (Actualizado 2026-01-25)

### 9.1 SelfDiscoveryContextService

Servicio que agrega contexto completo del usuario para inyección en el Copilot IA:

```php
getFullContext(?int $uid): array [
    'life_wheel' => [...],    // Scores, áreas bajas
    'timeline' => [...],       // Eventos (localStorage)
    'riasec' => [...],         // Código, scores, descripción
    'strengths' => [...],      // Top 5
    'summary' => '...'         // Texto para prompt
]
```

### 9.2 API Endpoint Contextual

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/api/v1/self-discovery/copilot/context` | POST | Respuestas proactivas basadas en perfil |

**Request:**
```json
{ "query": "¿Qué carrera me recomiendas?" }
```

**Response:**
```json
{
  "success": true,
  "response": {
    "message": "📊 <strong>Tu perfil RIASEC: RIA</strong>...",
    "tips": ["🎯 Carreras recomendadas:", "• Ingeniería", "• Diseño"],
    "actions": [
      { "label": "Ver ofertas compatibles", "url": "/jobs", "icon": "💼" }
    ],
    "followUp": "¿Quieres que te ayude a preparar tu CV?"
  }
}
```

### 9.3 Detección de Consultas

| Palabras clave | Contexto usado |
|---------------|----------------|
| riasec, carrera, profesión, trabajo | RIASEC + Fortalezas |
| fortaleza, talento | Strengths Top 5 |
| mejorar, rueda, equilibrio | Life Wheel low areas |

### 9.4 Integración Frontend (agent-fab.js)

El FAB del Copilot ahora llama al endpoint en lugar de mostrar mensaje genérico:

```javascript
fetch('/api/v1/self-discovery/copilot/context', {
    method: 'POST',
    body: JSON.stringify({ query: message })
})
.then(data => addAgentResponse(data.response));
```

---

## 10. Bug Fixes Aplicados (2026-01-25)

| Bug | Causa | Solución |
|-----|-------|----------|
| Fortalezas: bucle infinito | `$form_state->set()` no persiste en AJAX | Usar `getStorage()/setStorage()` |
| RIASEC: canvas vacío | Dependency `chartjs` no existía | CDN Chart.js directo |
| Timeline: emojis inconsistentes | Fuentes variables | SVG inline |
| Copilot: respuesta genérica | Sin llamada a backend | Endpoint `/api/v1/self-discovery/copilot/context` |

