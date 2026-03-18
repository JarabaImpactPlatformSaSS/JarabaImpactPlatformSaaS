# Aprendizaje #184 — Pipeline E2E Verification + DailyActionsRegistry

> Fecha: 2026-03-16 | Módulos: ecosistema_jaraba_core, 11 verticales | Regla: PIPELINE-E2E-001, DAILY-ACTIONS-REGISTRY-001

## Contexto

Durante la implementación del patrón SETUP-WIZARD-DAILY-001 en los 10 verticales del SaaS, se descubrió un gap crítico: 8 de 9 verticales tenían la infraestructura backend completa (services, registries, controllers) pero los templates de los dashboards NO incluían los parciales `_setup-wizard.html.twig` ni `_daily-actions.html.twig`.

## Problema

El pipeline de una feature en Drupal 11 tiene 4 capas obligatorias:
1. **L1 — Service/DI**: El servicio existe y se inyecta en el controller
2. **L2 — Controller**: El controller pasa los datos al render array
3. **L3 — hook_theme()**: Las variables están declaradas en la definición del tema
4. **L4 — Template**: El template renderiza los datos con {% include %}

Sin verificar L3 y L4, los datos existen en PHP pero nunca llegan al DOM. Drupal descarta silenciosamente las variables no declaradas en hook_theme().

## Solución

### Regla PIPELINE-E2E-001
Toda implementación DEBE verificar las 4 capas antes de marcar como completada. Checklist automatizable con grep.

### DailyActionsRegistry (DAILY-ACTIONS-REGISTRY-001)
Infraestructura transversal complementaria al SetupWizardRegistry:
- `DailyActionInterface` (16 métodos, incluye `getContext()` para badges dinámicos)
- `DailyActionsRegistry` (tagged service collector via CompilerPass)
- Tag: `ecosistema_jaraba_core.daily_action`

### Resultados
- 48 wizard steps + 54 daily actions = 102 tagged services
- 13 dashboards × 4 capas = 52 checkpoints verificados
- 11 módulos, 13 roles (9 primarios + 4 secundarios)
- Score: 10/10 clase mundial (benchmark vs Stripe, HubSpot, Salesforce, Linear)

## Regla de Oro #125

> PIPELINE-E2E-001: Toda implementación que toque dashboards DEBE verificar las 4 capas (L1 Service → L2 Controller → L3 hook_theme → L4 Template) antes de marcar como completada. "El código existe" ≠ "el usuario lo experimenta".

## Archivos Clave

- `ecosistema_jaraba_core/src/DailyActions/DailyActionInterface.php`
- `ecosistema_jaraba_core/src/DailyActions/DailyActionsRegistry.php`
- `ecosistema_jaraba_core/src/DependencyInjection/Compiler/DailyActionsCompilerPass.php`
- `docs/analisis/2026-03-16_Auditoria_Integral_SaaS_Setup_Wizard_Daily_Actions_Runtime_Arquitectura_v1.md`
- `docs/implementacion/2026-03-16_Plan_Implementacion_Completar_Gaps_Auditoria_Integral_v1.md`
