# Aprendizajes: Self-Discovery & Copilot Awareness

**Fecha:** 2026-01-25  
**Contexto:** Implementación del módulo `jaraba_self_discovery` con integración Copilot  
**Estado:** ✅ Fase 1 completada, Fase 2-3 pendientes

---

## 1. Arquitectura de Self-Discovery

### 1.1 Herramientas del Vertical Empleabilidad

| Herramienta | Objetivo | Fases |
|-------------|----------|-------|
| **Rueda de la Vida** | Evaluar satisfacción en 8 áreas | 1 fase: sliders 1-10 |
| **Mi Línea de Vida** | Identificar patrones profesionales | 3 fases (ver §2) |
| **Mis Intereses** | Perfil RIASEC | Cuestionario estándar |
| **Mis Fortalezas** | Top 5 talentos | Cuestionario + reflexión |

### 1.2 Las 3 Fases de "Mi Línea de Vida"

> [!IMPORTANT]
> El Timeline NO es solo anotar eventos. Es un proceso de reflexión guiada en 3 fases.

#### Fase 1: Anotar Momentos Álgidos y Bajos
- **Qué hace el usuario**: Añade eventos con fecha, título, tipo (álgido/bajo)
- **Output**: Gráfico de línea de vida (curva emocional)
- **Estado actual**: ✅ Implementado

#### Fase 2: Describir los Acontecimientos
- **Qué hace el usuario**: Para cada evento, describe:
  - Qué pasó exactamente
  - Qué factores generaron satisfacción/insatisfacción
  - Qué habilidades usó
  - Quiénes estaban involucrados
- **Output**: Análisis de patrones de satisfacción profesional
- **Estado actual**: ⏳ Pendiente

#### Fase 3: Reconocer tus Intereses
- **Qué hace el usuario**: Reflexiona sobre:
  - ¿Qué actividades te generan "flow"?
  - ¿Qué temas te apasionan?
  - ¿En qué contextos te sientes más realizado?
- **Output**: Perfil de intereses emergentes
- **Estado actual**: ⏳ Pendiente

---

## 2. Copilot Awareness (Contexto Consciente)

### 2.1 Requisito Crítico

El Copiloto IA **DEBE** ser consciente en todo momento de:

1. **Qué herramienta está usando el usuario**
2. **Qué datos ha anotado** (eventos, scores, reflexiones)
3. **En qué fase del proceso está**
4. **Cuál es el objetivo de autodiagnóstico**

### 2.2 Implementación Técnica

```
┌─────────────────────────────────────────────────────────┐
│                    COPILOT CONTEXT                      │
├─────────────────────────────────────────────────────────┤
│  {                                                      │
│    "user_journey": {                                    │
│      "vertical": "empleabilidad",                       │
│      "avatar": "job_seeker",                            │
│      "current_tool": "timeline",                        │
│      "current_phase": 1,                                │
│      "completion_rate": 0.33                            │
│    },                                                   │
│    "self_discovery_data": {                             │
│      "life_wheel": { "career": 4, "health": 7, ... },   │
│      "timeline_events": [                               │
│        { "date": "2020", "type": "high", ... },         │
│        { "date": "2023", "type": "low", ... }           │
│      ],                                                 │
│      "timeline_reflections": { ... },                   │
│      "riasec_profile": null,                            │
│      "strengths_top5": null                             │
│    }                                                    │
│  }                                                      │
└─────────────────────────────────────────────────────────┘
```

### 2.3 Puntos de Integración

| Evento | Acción del Copilot |
|--------|-------------------|
| Usuario guarda evento timeline | Actualizar contexto, ofrecer reflexión |
| Usuario completa Fase 1 | Sugerir pasar a Fase 2 |
| Patrón detectado | Mostrar insight ("Noto que tus momentos álgidos involucran trabajo en equipo...") |
| Usuario bloqueado | Ofrecer preguntas guía |
| Herramientas completadas | Generar síntesis de autodiagnóstico |

---

## 3. Aprendizajes Técnicos

### 3.1 Content Entities en Drupal

```php
// Tipo de parámetro en métodos que usan currentUser()->id()
// Drupal devuelve STRING, no INT
protected function hasAssessment(string $entity_type, int|string $uid): bool
```

### 3.2 Iconos jaraba_icon()

```twig
{# Usar nombres EXACTOS de la biblioteca #}
{{ jaraba_icon('analytics', 'radar', { color: 'verde-innovacion', size: '48px' }) }}

{# Nombres de colores válidos: #}
{# - azul-corporativo #}
{# - naranja-impulso #}
{# - verde-innovacion #}
```

### 3.3 Gráfico Canvas de Timeline

- Una sola línea continua (no puntos separados)
- Curvas Bezier para suavidad
- Zonas de color: verde arriba (álgido), rojo abajo (bajo)
- Fechas en eje X, no junto a los puntos

### 3.4 Slide-Panel para Modales CRUD

```javascript
// Workflow documentado en: .agent/workflows/slide-panel-modales.md
Drupal.behaviors.slidePanel.open('panel-id');
Drupal.behaviors.slidePanel.close('panel-id');
```

---

## 4. Próximos Pasos

### Fase 2 del Timeline (2-3 días)
- [ ] Añadir campo de descripción expandida por evento
- [ ] Añadir campos de reflexión (factores de satisfacción)
- [ ] Crear slide-panel para editar eventos existentes

### Fase 3 del Timeline (1-2 días)
- [ ] UI de síntesis de intereses emergentes
- [ ] Preguntas guía del Copilot

### Integración Copilot (3-4 días)
- [ ] Crear API endpoint `/api/v1/copilot/self-discovery-context`
- [ ] Hook ECA: "Usuario guarda evento" → Actualizar contexto
- [ ] Prompt templates para asistencia en autodiagnóstico

---

## 5. Referencias

| Documento | Ubicación |
|-----------|-----------|
| Spec técnica inicial | `docs/tecnicos/20260125-160_Empleabilidad_Self_Discovery_Tools_v1_Claude.md` |
| Workflow Slide-Panel | `.agent/workflows/slide-panel-modales.md` |
| Módulo Drupal | `web/modules/custom/jaraba_self_discovery/` |
