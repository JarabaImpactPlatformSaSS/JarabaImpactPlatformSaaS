# 161. Mejoras Self-Discovery: Timeline Multifase + Copilot IA

**Fecha:** 2026-01-25  
**Versión:** 1.0  
**Vertical:** Empleabilidad  
**Módulo:** `jaraba_self_discovery`  
**Dependencias:** `jaraba_copilot_v2`

---

## 0. Resumen Ejecutivo

Mejoras al módulo Self-Discovery para implementar:
1. **Timeline de Vida en 3 fases** según metodología Osterwalder
2. **Copilot IA contextual** que asiste en tiempo real con toda la información de autodescubrimiento

---

## 1. Timeline de Vida - 3 Fases

### Fase 1: Momentos Álgidos y Bajos ✅
*Implementado* - Captura de eventos con fecha, tipo (álgido/bajo), categoría y aprendizajes.

### Fase 2: Descripción de Acontecimientos [PENDIENTE]
**Objetivo:** Identificar factores generadores de satisfacción profesional.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `event_description` | textarea | Narrativa detallada del evento |
| `satisfaction_factors` | checkboxes | Logro, Reconocimiento, Autonomía, Propósito, Crecimiento |
| `skills_demonstrated` | multiselect | Competencias demostradas (del catálogo Skills) |
| `values_reflected` | tags | Valores personales que se manifestaron |

### Fase 3: Reconocimiento de Intereses [PENDIENTE]
**Objetivo:** Autoconocimiento y patrones.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `patterns_identified` | textarea | Patrones que el usuario reconoce |
| `recurring_themes` | tags | Temas recurrentes detectados |
| `ai_insights` | readonly | Análisis IA de patrones |

---

## 2. Copilot IA Contextual

### 2.1 Contexto de Self-Discovery

El Copilot debe tener acceso completo al contexto de autodescubrimiento:

```php
// SelfDiscoveryContextService.php
class SelfDiscoveryContextService {
  
  public function getFullContext(int $uid): array {
    return [
      'life_wheel' => [
        'scores' => $this->lifeWheelService->getLatestScores($uid),
        'lowest_areas' => $this->lifeWheelService->getLowestAreas($uid, 2),
        'trend' => $this->lifeWheelService->getTrend($uid),
      ],
      'timeline' => [
        'events' => $this->timelineService->getAllEvents($uid),
        'patterns' => $this->timelineService->getIdentifiedPatterns($uid),
        'satisfaction_factors' => $this->timelineService->getTopFactors($uid),
      ],
      'riasec' => [
        'code' => $this->riasecService->getCode($uid),
        'scores' => $this->riasecService->getScores($uid),
        'suggested_careers' => $this->riasecService->getSuggestions($uid),
      ],
      'strengths' => [
        'top5' => $this->strengthsService->getTop5($uid),
      ],
    ];
  }
}
```

### 2.2 Modos de Asistencia por Herramienta

| Herramienta | Modo Copilot | Función Principal |
|-------------|--------------|-------------------|
| **Rueda de la Vida** | Coach Emocional | Reflexión sobre áreas bajas, motivación |
| **Timeline** | Consultor Táctico | Identificar patrones, competencias derivadas |
| **RIASEC** | Consultor Táctico | Sugerir carreras alineadas, exploración |
| **Fortalezas** | Sparring Partner | Estrategias para aplicar talentos |

### 2.3 Prompts Contextuales

El Copilot recibe el contexto y adapta sus respuestas:

```
CONTEXTO DEL USUARIO:
- Áreas más bajas en Rueda de Vida: {areas}
- Perfil RIASEC: {code} → {description}
- Top 3 Fortalezas: {strengths}
- Patrones identificados en Timeline: {patterns}

Usa esta información para dar orientación personalizada...
```

---

## 3. Arquitectura Técnica

### 3.1 Nuevos Archivos

| Archivo | Propósito |
|---------|-----------|
| `SelfDiscoveryContextService.php` | Agregación de contexto para Copilot |
| `TimelinePhase2Form.php` | Formulario de descripción de acontecimientos |
| `TimelinePhase3Form.php` | Formulario de reconocimiento de intereses |

### 3.2 Modificaciones

| Archivo | Cambio |
|---------|--------|
| `timeline-interactive.js` | Añadir campos Fase 2 y Fase 3 al slide panel |
| `jaraba_copilot_v2.module` | Hook para inyectar contexto Self-Discovery |

---

## 4. Fases de Implementación

| Fase | Descripción | Horas |
|------|-------------|-------|
| 1 | Timeline Fase 2 (campos de acontecimientos) | 8h |
| 2 | Timeline Fase 3 (patrones e IA) | 8h |
| 3 | SelfDiscoveryContextService | 4h |
| 4 | Integración Copilot | 6h |
| 5 | Testing y ajustes | 4h |

**Total: ~30h**

---

## 5. Verificación

### Automatizada
- Unit tests para SelfDiscoveryContextService
- Tests de integración Copilot con contexto

### Manual
1. Añadir evento con todos los campos de Fase 2
2. Completar autoconocimiento en Fase 3
3. Verificar que Copilot recibe contexto completo
4. Confirmar respuestas personalizadas del Copilot

---

## 6. Referencias

- **Tu Modelo de Negocio** - Osterwalder, Pigneur (Línea de Vida)
- Documentación Copilot v2: `/docs/tecnicos/copilot/`
