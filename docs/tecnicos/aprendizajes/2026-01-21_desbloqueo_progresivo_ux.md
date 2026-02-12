# Aprendizaje: Sistema de Desbloqueo Progresivo UX para Vertical Emprendimiento

**Fecha:** 2026-01-21  
**Módulo:** `jaraba_copilot_v2` (Planificado)  
**Programa:** Andalucía +ei v2.0

---

## Contexto

Al planificar la implementación del Copiloto de Emprendimiento v2 para el programa Andalucía +ei, se identificó que un despliegue tradicional de funcionalidades "todo de golpe" generaría sobrecarga cognitiva en el avatar Javier (emprendedor con baja madurez digital).

## Problema

- **Opción A (Tradicional)**: Desarrollar e integrar componentes incrementalmente, pero mostrar todo al usuario desde el día 1
- **Resultado**: Avatar abrumado por exceso de opciones, abandono temprano, baja adopción

## Solución Adoptada: Desbloqueo Progresivo UX (Opción B)

El sistema de **Feature Unlock by Program Week** alinea el desbloqueo de funcionalidades con el recorrido del programa de 12 semanas:

```php
// FeatureUnlockService.php
const UNLOCK_MAP = [
    0 => ['dime_test', 'profile_basic'],                    // Semana 0
    1 => ['copilot_coach', 'pills_1_3', 'kit_emocional'],   // Semanas 1-3
    4 => ['canvas_vpc', 'canvas_bmc', 'experiments_discovery'], // Semanas 4-6
    7 => ['copilot_cfo', 'calculadora_precio', 'test_card'],   // Semanas 7-9
    10 => ['mentoring_marketplace', 'calendar_sessions'],    // Semanas 10-11
    12 => ['experiments_commitment', 'demo_day', 'certificado'] // Semana 12
];
```

## Beneficios

| Aspecto | Sin Desbloqueo | Con Desbloqueo Progresivo |
|---------|----------------|---------------------------|
| Sobrecarga inicial | Alta | Mínima |
| Time-to-First-Value | Variable | < 5 min (solo DIME) |
| Curva de aprendizaje | Empinada | Gradual |
| Engagement semanal | Decrece | Se mantiene (curiosidad) |
| Drop-off rate | > 20% | < 5% objetivo |

## Implementación Técnica

### 1. Servicio de Desbloqueo

```php
public function isFeatureUnlocked(string $feature, EntrepreneurProfile $profile): bool {
    $weekNumber = $profile->getCurrentProgramWeek();
    return in_array($feature, $this->getUnlockedFeatures($profile));
}
```

### 2. UI de Feature Bloqueada

```twig
{% if not is_unlocked %}
<div class="feature-locked">
    <span class="feature-locked__icon">🔒</span>
    <p>{{ 'Disponible en Semana @week'|t({'@week': unlock_week}) }}</p>
    <p class="preview">{{ feature_preview }}</p>
</div>
{% endif %}
```

### 3. Diseño Visual

- Funciones bloqueadas visibles pero en grayscale
- Tooltip explicativo con semana de desbloqueo
- Preview de "lo que podrás hacer" para generar expectativa

## Modos del Copiloto por Semana

| Semanas | Modos Activos |
|---------|---------------|
| 1-3 | 🧠 Coach Emocional |
| 4-6 | + 🔧 Consultor Táctico, 🥊 Sparring |
| 7-9 | + 💰 CFO Sintético, 😈 Abogado del Diablo |
| 10-12 | Todos los modos completos |

## Relación con Metodología Osterwalder

El desbloqueo progresivo mapea directamente con los tipos de experimentos:

| Fase Programa | Tipo Experimento | Evidencia |
|---------------|------------------|-----------|
| Semanas 1-3 | DISCOVERY | Débil (cualitativa) |
| Semanas 4-6 | DISCOVERY + INTEREST | Media |
| Semanas 7-9 | INTEREST + PREFERENCE | Fuerte |
| Semanas 10-12 | PREFERENCE + COMMITMENT | Muy fuerte |

## Métricas de Éxito

- **Feature Discovery Rate**: % de usuarios que usan cada feature en su semana de desbloqueo
- **Weekly Engagement**: Sesiones por semana (debe mantenerse o crecer)
- **Program Completion**: % que llega a Semana 12 (target: > 85%)
- **NPS Progresivo**: Debe aumentar semana a semana

## Lecciones Clave

1. **El usuario no necesita ver todo desde el principio** - Menos es más
2. **La expectativa genera engagement** - El "coming soon" bien diseñado motiva
3. **Alinear features con capacidades** - No dar herramientas antes de la formación
4. **Feedback visual importante** - Grayscale + tooltip > ocultar completamente

---

## Referencias

- [implementation_plan.md](file:///C:/Users/Pepe%20Jaraba/.gemini/antigravity/brain/c37dc4ca-dbac-4120-89a6-989c53614650/implementation_plan.md) - Plan v3.1 con mapa de desbloqueo
- [20260121a-Especificaciones_Tecnicas_Copiloto_v2_Claude.md](file:///z:/home/PED/JarabaImpactPlatformSaaS/docs/tecnicos/20260121a-Especificaciones_Tecnicas_Copiloto_v2_Claude.md) - Especificación completa del Copiloto
- [20260115c-Programa Maestro Andalucía +ei V2.0_Gemini.md](file:///z:/home/PED/JarabaImpactPlatformSaaS/docs/tecnicos/20260115c-Programa%20Maestro%20Andaluc%C3%ADa%20+ei%20V2.0_Gemini.md) - Estructura del programa
