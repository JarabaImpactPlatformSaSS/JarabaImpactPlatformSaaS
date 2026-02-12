# Aprendizaje: Sistema Límites IA y Verificación Vertical Emprendimiento

**Fecha:** 2026-01-22  
**Contexto:** Implementación límites de uso IA por plan SaaS y auditoría vertical Emprendimiento  
**Tags:** `#ai-limits` `#finops` `#vertical-emprendimiento` `#svg-icons`

---

## 1. Sistema de Límites IA por Plan

### Problema
Los planes SaaS necesitan diferenciar el acceso a la IA con límites de tokens mensuales.

### Solución
Implementado `AIUsageLimitService` en `ecosistema_jaraba_core`:

```php
class AIUsageLimitService {
    const PLAN_LIMITS = [
        'basico' => 10000,      // 10K tokens/mes
        'profesional' => 50000, // 50K tokens/mes  
        'premium' => 200000,    // 200K tokens/mes
    ];
    
    public function checkLimit(Tenant $tenant): array
    public function recordUsage(Tenant $tenant, int $tokens): void
    public function shouldSendAlert(Tenant $tenant): bool
}
```

### Integración
1. **CopilotController** - Bloqueo HTTP 429 cuando se excede límite
2. **TenantDashboard** - Tarjeta "Uso de IA" con barra progreso
3. **FinOpsDashboard** - Columna "AI Tokens" en tabla tenants
4. **Email alerta** al 80% de consumo

### Lección
Los límites por plan deben ser visibles tanto para el tenant (su propio consumo) como para el admin (todos los tenants). Usar colores semáforo: verde < 50%, amarillo 50-80%, rojo > 80%.

---

## 2. Sistema de Iconos SVG jaraba_icon()

### Problema
El FinOps Dashboard tenía emoticonos (🏢💾🔌⚡) en lugar de iconos SVG consistentes, y algunos iconos usaban color 'neutral' que no se veía sobre fondo oscuro.

### Solución

**Iconos creados (~30 total):**
- `ui/`: plug, shopping-cart, file-signature, code, map-pin, webhook, qr-code, brain, arrow-right, list, heart, building, database (+ versiones duotone)
- `business/`: money, money-duotone
- `analytics/`: trend-up

**Directriz actualizada:**
1. Todos los iconos deben tener versión outline + duotone
2. Usar colores visibles sobre fondo oscuro: `azul-corporativo`, `verde-innovacion`, `naranja-impulso`, `danger`
3. Evitar `color: 'neutral'` en elementos sobre fondos oscuros

### Ejemplo de Reemplazo
```twig
{# Antes #}
<span>🏢</span>

{# Después #}
{{ jaraba_icon('ui', 'building', { color: 'azul-corporativo', size: '18px' }) }}
```

---

## 3. Auditoría Vertical Emprendimiento - 100% Implementado

### Hallazgo Clave
Los tres módulos del vertical Emprendimiento están **completamente implementados**:

| Módulo | Entidades | Servicios | Controllers | Templates | Hooks/ECA |
|--------|-----------|-----------|-------------|-----------|-----------|
| jaraba_mentoring | 9 | 4 | 7 | 4 | 7 |
| jaraba_business_tools | 6 | 4 | 5 | 5 | 6 |
| jaraba_copilot_v2 | 6 | 8 | 4 | 1 | 1 |

### Servicios Clave Implementados

**jaraba_mentoring:**
- `StripeConnectService` - Pagos split con Stripe Connect
- `MentorMatchingService` - Algoritmo matching 6 factores ponderados
- `VideoMeetingService` - Jitsi Meet + ICS calendar
- `SessionSchedulerService` - Disponibilidad y reservas

**jaraba_business_tools:**
- `CanvasAiService` (22KB) - Análisis coherencia BMC con IA
- `CanvasService` - CRUD y versionado automático
- `SroiCalculatorService` - Cálculo SROI impacto

**jaraba_copilot_v2:**
- `FeatureUnlockService` (17KB) - Desbloqueo progresivo por semana
- `CopilotOrchestratorService` (29KB) - Orquestador 5 modos
- `ModeDetectorService` - Detección automática de modo

### Lección
Usar el workflow `/drupal-eca-hooks` - las automatizaciones ECA se implementan como hooks nativos en el .module, NO con la UI BPMN de ECA. Esto permite versionado Git y testing unitario.

---

## 4. Patrón de Verificación de Módulos

### Proceso Aplicado
1. **Listar entidades** - `src/Entity/*.php`
2. **Listar servicios** - `src/Service/*.php`
3. **Verificar outline del .module** - hooks implementados
4. **Verificar templates** - `templates/*.html.twig`
5. **Confirmar habilitación** - `drush pm:list --status=enabled`

### Comando Útil
```bash
docker exec jarabasaas_appserver_1 drush pm:list --status=enabled --format=list | findstr /i "jaraba"
```

---

## 5. Tareas Pendientes Identificadas

### Para Verificación Funcional (Próxima Sesión)
1. Pruebas navegador: `/mentors`, `/canvas`, copilot widget
2. Datos de prueba: mentores, Canvas, hipótesis MVP
3. Configuración producción: Stripe Connect, Jitsi privado

---

## Referencias

- `AIUsageLimitService.php` - Sistema límites IA
- `finops-dashboard.html.twig` - Dashboard FinOps iconos
- `jaraba_mentoring.module` - 613 líneas con hooks ECA
- `jaraba_business_tools.module` - 382 líneas con hooks ECA
- Workflow: `.agent/workflows/drupal-eca-hooks.md`
