# Aprendizaje: Admin Center D - Impersonation, RBAC Matrix, Scheduled Reports

**Fecha:** 2026-02-06
**Módulo:** ecosistema_jaraba_core
**Contexto:** Implementación de gaps pendientes del Admin Center (Bloque D)

---

## 1. Sistema de Impersonación

### Patrón Implementado

```php
// Puntos clave del ImpersonationService:

// 1. Nunca impersonar a UID 1 (superadmin)
if ($targetUid === 1) {
    return ['success' => FALSE, 'error' => 'No se puede impersonar al superadministrador.'];
}

// 2. Timeout automático (30 minutos)
const MAX_SESSION_DURATION = 1800;

// 3. TempStore para datos de sesión
$tempStore = $this->privateTempStoreFactory->get('impersonation');
$tempStore->set(self::SESSION_KEY, $sessionData);

// 4. Logging de eventos (start, end, timeout)
$logEntry = $this->entityTypeManager->getStorage('impersonation_audit_log')->create([...]);
```

### Campos del Audit Log

| Campo | Tipo | Propósito |
|-------|------|-----------|
| admin_uid | entity_reference | Usuario que impersona |
| target_uid | entity_reference | Usuario impersonado |
| event_type | list_string | start/end/timeout |
| session_duration | integer | Duración en segundos |
| admin_ip | string | IP del admin |
| user_agent | string | User-Agent del navegador |
| reason | string | Motivo de la sesión |

### Seguridad

- **Permisos granulares**: `impersonate tenants`, `view impersonation logs`, `administer impersonation`
- **PrivateTempStore**: Aislamiento por usuario, sin compartir sesiones
- **Logging completo**: Trazabilidad de todas las acciones

---

## 2. RBAC Matrix Visual

### Arquitectura del Controller

```php
// RbacMatrixController::buildMatrix()
// 1. Obtener roles (excepto anonymous/authenticated)
$roles = $roleStorage->loadMultiple();

// 2. Obtener todos los permisos del sistema
$allPermissions = $permissionHandler->getPermissions();

// 3. Construir matriz role x permission
foreach ($permissionsData as $perm) {
    foreach ($roles as $role) {
        $matrix[$perm['id']][$role->id()] = $role->hasPermission($perm['id']);
    }
}
```

### Toggle AJAX Pattern

```javascript
// rbac-matrix.js
toggle.addEventListener('change', function() {
  fetch('/api/v1/admin/rbac/toggle', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ role, permission, enabled }),
  });
});
```

### SCSS con Design Tokens

```scss
.rbac-matrix-table {
  th {
    padding: var(--ej-spacing-sm, 0.5rem);
    background: var(--ej-bg-muted, #f9fafb);
  }
}

.rbac-checkbox-mark {
  &::after {
    // Checkmark visual
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
  }
}
```

---

## 3. Scheduled Reports / Alert Rules

### ScheduledReport Entity

- **5 tipos de reporte**: financial, tenant_health, ai_usage, security, custom
- **3 frecuencias**: daily, weekly, monthly
- **Método calculateNextRun()**: Calcula próxima ejecución basada en frecuencia

### AlertRule Entity

- **Métricas monitoreables**: MRR, Churn, AI Tokens, Error Rate, Response Time
- **Operadores**: Mayor que, Menor que, Igual, Cambio porcentual
- **Cooldown**: Evita alertas repetitivas (configurable en minutos)
- **Método evaluateCondition()**: Evalúa si se cumple la regla

```php
public function evaluateCondition(float $currentValue, ?float $previousValue = NULL): bool {
    switch ($this->get('operator')->value) {
        case self::OP_CHANGE_PERCENT:
            $changePercent = (($currentValue - $previousValue) / $previousValue) * 100;
            return abs($changePercent) >= $threshold;
    }
}
```

---

## 4. Registro de Themes y Libraries

### hook_theme()

```php
'rbac_matrix' => [
  'variables' => [
    'roles' => [],
    'permissions' => [],
    'matrix' => [],
    'modules' => [],
  ],
  'template' => 'rbac-matrix',
  'path' => $path . '/templates',
],
```

### Library en .libraries.yml

```yaml
rbac-matrix:
  css:
    theme:
      css/rbac-matrix.css: {}
  js:
    js/rbac-matrix.js: {}
  dependencies:
    - core/drupal
    - core/once
```

---

## 5. Lecciones Aprendidas

1. **TempStore vs Session**: PrivateTempStoreFactory es mejor para datos aislados por usuario
2. **Entity vs Config**: Content Entities para audit logs (múltiples instancias) vs Config Entities para reglas (configurables)
3. **AJAX Toggle Pattern**: Disable durante request → revert on error → enable after response
4. **Filtro cliente vs servidor**: Filtro por módulo implementado en JavaScript para UX instantánea

---

## Tags

`impersonation` `rbac` `audit-log` `scheduled-reports` `alert-rules` `admin-center` `security`
