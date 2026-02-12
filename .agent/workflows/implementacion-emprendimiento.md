---
description: Implementación del vertical Emprendimiento con Copiloto v2 y Desbloqueo Progresivo UX
---

# Workflow: Implementación Vertical Emprendimiento

Este workflow documenta el proceso para implementar el vertical Emprendimiento con el Copiloto v2 y el patrón de Desbloqueo Progresivo UX.

---

## Principio Rector: Desbloqueo Progresivo UX

> **El emprendedor (Javier) ve exactamente lo que necesita cuando lo necesita.**
> La plataforma "crece" con él a lo largo de las 12 semanas del programa.

---

## Pasos de Implementación

### 1. Crear FeatureUnlockService

Implementar el servicio que controla qué features están disponibles por semana:

```php
// Ubicación: web/modules/custom/jaraba_copilot_v2/src/Service/FeatureUnlockService.php

namespace Drupal\jaraba_copilot_v2\Service;

class FeatureUnlockService {
    
    const UNLOCK_MAP = [
        0 => ['dime_test', 'profile_basic'],
        1 => ['copilot_coach', 'pills_1_3', 'kit_emocional'],
        4 => ['canvas_vpc', 'canvas_bmc', 'experiments_discovery'],
        7 => ['copilot_cfo', 'calculadora_precio', 'test_card'],
        10 => ['mentoring_marketplace', 'calendar_sessions'],
        12 => ['experiments_commitment', 'demo_day', 'certificado']
    ];
    
    public function isFeatureUnlocked(string $feature, $profile): bool {
        $weekNumber = $profile->getCurrentProgramWeek();
        return in_array($feature, $this->getUnlockedFeatures($profile));
    }
}
```

### 2. Crear UI de Feature Bloqueada

// turbo
Implementar template Twig para funcionalidades bloqueadas:

```twig
{# Ubicación: web/modules/custom/jaraba_copilot_v2/templates/feature-locked.html.twig #}

{% if not is_unlocked %}
<div class="feature-locked">
    <div class="feature-locked__icon">🔒</div>
    <div class="feature-locked__message">
        {{ 'Esta funcionalidad estará disponible en la Semana @week'|t({'@week': unlock_week}) }}
    </div>
    <div class="feature-locked__preview">
        {{ feature_preview }}
    </div>
</div>
{% endif %}
```

### 3. Integrar Entregables Copiloto v2

Los siguientes archivos están listos en `docs/tecnicos/20260121a-*`:

| Archivo | Destino |
|---------|---------|
| `copilot_integration.module` | `web/modules/custom/jaraba_copilot_v2/` |
| `experiment_library_*.json` | `web/modules/custom/jaraba_copilot_v2/data/` |
| `CopilotChatWidget.jsx` | `web/themes/custom/ecosistema_jaraba/js/components/` |
| `BMCValidationDashboard.jsx` | `web/themes/custom/ecosistema_jaraba/js/components/` |
| `migraciones_sql_copiloto_v2.sql` | Ejecutar en base de datos |

### 4. Crear Content Entities

Crear las entidades base siguiendo el patrón de Content Entities:

- **`entrepreneur_profile`**: Perfil emprendedor + DIME + carril
- **`hypothesis`**: Hipótesis de negocio + bloque BMC
- **`experiment`**: Experimento de validación + Test/Learning Cards
- **`copilot_session`**: Sesión de conversación con el copiloto

Ver workflow `/drupal-custom-modules` para detalles de implementación.

### 5. Mapa de Desbloqueo por Semana

| Semana | Funcionalidades |
|--------|-----------------|
| **0** | DIME + Clasificación Carril + Perfil Básico |
| **1-3** | Copiloto Coach + Píldoras 1-3 + Kit Emocional |
| **4-6** | +Canvas VPC/BMC + Experimentos DISCOVERY |
| **7-9** | +Copiloto CFO/Devil + Calculadora + Dashboard Validación |
| **10-11** | +Mentores + Calendario + Círculos Responsabilidad |
| **12** | +Demo Day + Certificado + Club Alumni |

---

## 5 Modos del Copiloto

| Modo | Trigger | Semana Disponible |
|------|---------|-------------------|
| 🧠 Coach Emocional | miedo, bloqueo, impostor | 1 |
| 🔧 Consultor Táctico | cómo hago, paso a paso | 4 |
| 🥊 Sparring Partner | qué te parece, feedback | 4 |
| 💰 CFO Sintético | precio, cobrar, rentable | 7 |
| 😈 Abogado del Diablo | estoy seguro, funcionará | 7 |

---

## Verificación

1. **Verificar desbloqueo progresivo**: Crear usuario de prueba en Semana 0 y confirmar que solo ve DIME
2. **Verificar transiciones**: Avanzar semana y confirmar que nuevas features se desbloquean
3. **Verificar UI bloqueada**: Confirmar que features futuras muestran mensaje "Disponible en Semana X"
4. **Verificar modos copiloto**: Confirmar que solo modos desbloqueados responden

---

## Referencias

- Plan de Implementación v3.1: `brain/*/implementation_plan.md`
- Especificaciones Copiloto v2: `docs/tecnicos/20260121a-Especificaciones_Tecnicas_Copiloto_v2_Claude.md`
- Programa Andalucía +ei: `docs/tecnicos/20260115c-Programa Maestro Andalucía +ei V2.0_Gemini.md`
- Aprendizaje: `docs/tecnicos/aprendizajes/2026-01-21_desbloqueo_progresivo_ux.md`
