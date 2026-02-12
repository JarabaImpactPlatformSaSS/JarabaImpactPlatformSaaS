# 📚 Aprendizaje: Refactorización del Copiloto a Include Twig Global

**Fecha:** 2026-01-26  
**Área:** Frontend / Arquitectura IA  
**Impacto:** Alto

---

## Contexto

Durante la auditoría frontend, se detectó que el copiloto en `/entrepreneur/dashboard` mostraba "**Asistente de Selección**" en lugar del contexto correcto de emprendimiento. Esto ocurría porque:

1. El copiloto usaba bloques Drupal configurados manualmente en la BD
2. Cada bloque tenía un `avatar_type` estático
3. La configuración estaba dispersa y era difícil de auditar

---

## Decisión Arquitectónica

### ❌ Patrón Anterior (Bloques Drupal)

```
┌─────────────────────────────────────┐
│  Múltiples bloques FAB             │
│  - Configuración en BD             │
│  - avatar_type: manual             │
│  - Inconsistente                   │
└─────────────────────────────────────┘
```

**Problemas:**
- Contexto incorrecto en dashboards
- Difícil de mantener
- Viola principio de "páginas limpias"

### ✅ Patrón Nuevo (Include Twig Global)

```
html.html.twig
└── {% include '_copilot-fab.html.twig' %}
    └── CopilotContextService.getContext()
```

**Beneficios:**
- Un único punto de inclusión
- Detección 100% automática
- Consistente con directrices del proyecto
- Fácil de auditar y mantener

---

## Implementación

### 1. CopilotContextService (ya creado)

```php
// Detecta automáticamente avatar por roles
$context = [
    'avatar' => 'entrepreneur',  // detectado por roles
    'vertical' => 'emprendimiento',
    'tenant_name' => 'Mi Org',
    'plan' => 'Premium',
    'user_name' => 'Juan',
];
```

### 2. Preprocess Hook (pendiente)

```php
function ecosistema_jaraba_theme_preprocess_html(&$variables) {
    $copilot_service = \Drupal::service('ecosistema_jaraba_core.copilot_context');
    $variables['copilot_context'] = $copilot_service->getContext();
}
```

### 3. Include en html.html.twig (pendiente)

```twig
{% if copilot_context %}
  {% include '@ecosistema_jaraba_theme/partials/_copilot-fab.html.twig' 
     with { context: copilot_context } only %}
{% endif %}
```

---

## Mapeo de Detección

| Contexto | Prioridad | Fuente |
|----------|-----------|--------|
| Roles del usuario | 1 | `$user->getRoles()` |
| Tenant asociado | 2 | `field_admin_user_id` en Tenant |
| Ruta del dashboard | 3 | `RouteMatch::getRouteName()` |
| Ruta de landing | 4 | URL patterns |
| Fallback | 5 | `general` |

---

## Lecciones Aprendidas

1. **Evitar configuración en UI para componentes globales**: Los FAB, headers, footers deben usar detección automática, no configuración manual.

2. **Centralizar lógica de contexto**: Un servicio (`CopilotContextService`) maneja toda la detección, facilitando debugging y testing.

3. **Priorizar roles sobre rutas**: El avatar del usuario logado tiene precedencia sobre la ruta actual.

4. **Documentar antes de implementar**: Escribir la arquitectura primero evita refactorizaciones costosas.

---

## Archivos Relacionados

- [CopilotContextService.php](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/ecosistema_jaraba_core/src/Service/CopilotContextService.php)
- [Arquitectura Copiloto](file:///z:/home/PED/JarabaImpactPlatformSaaS/docs/arquitectura/2026-01-26_arquitectura_copiloto_contextual.md)
- [contextual-copilot-fab.html.twig](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/ecosistema_jaraba_core/templates/contextual-copilot-fab.html.twig)
