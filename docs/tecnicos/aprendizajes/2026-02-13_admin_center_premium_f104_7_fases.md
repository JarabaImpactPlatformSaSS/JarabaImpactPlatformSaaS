# Aprendizaje #76 — Admin Center Premium (Spec f104) — 7 FASEs

**Fecha:** 2026-02-13
**Contexto:** Implementacion completa del Admin Center Premium para el SaaS, cubriendo las 7 fases de la spec f104.

---

## Resumen

Dashboard administrativo SaaS clase mundial con shell sidebar + topbar, 8 paginas especializadas, dark mode automatico y accesibilidad WCAG 2.1 AA. Implementado integramente en ecosistema_jaraba_core siguiendo Zero Region Policy, DI condicional y ApiResponseTrait.

---

## Decisiones Clave

### 1. Arquitectura Shell (FASE 1)

**Decision:** Layout de 3 zonas (sidebar colapsable + topbar + content area) en lugar de tabs o paginas independientes.

**Razon:** Un shell unificado permite navegacion sin reload, shortcuts globales (G D, G T, G U...), y consistencia visual en todas las paginas admin. El sidebar se colapsa en mobile con overlay.

**Patron:**
- `AdminCenterController` como router principal (8 metodos = 8 paginas)
- `AdminCenterAggregatorService` centraliza scorecards, quick links, recent activity
- `AdminCenterLayoutService` genera menu sections y breadcrumbs
- Template `admin-center-shell.html.twig` con partials sidebar + topbar

### 2. DataTable Reusable Vanilla JS (FASE 2)

**Decision:** Clase `AdminCenterDataTable` en vanilla JS con sort, filter, paginacion server-side y skeletons.

**Razon:** Evita dependencia de librerias externas (DataTables jQuery, AG Grid). Se instancia multiples veces con configuracion diferente (tenants, users, logs). Server-side pagination via fetch() + API REST envelope `{success, data, meta}`.

**Patron:**
```javascript
new Drupal.AdminCenterDataTable(container, {
  apiUrl: '/api/v1/admin/tenants',
  columns: [...],
  filters: [...],
  rowActions: [...]
});
```

### 3. Optional Service Injection (FASEs 5-6)

**Decision:** Servicios de modulos opcionales (jaraba_foc, jaraba_customer_success, jaraba_analytics) se inyectan condicionalmente via `EcosistemaJarabaCoreServiceProvider`.

**Razon:** Admin Center vive en ecosistema_jaraba_core pero necesita datos de modulos que pueden no estar instalados. Se usa `~` (NULL) en services.yml + conditional `new Reference()` en el ServiceProvider.

**Patron:**
```yaml
# services.yml
arguments:
  - '@config.factory'
  - '~'  # FocAlertStorage - optional
```

```php
// EcosistemaJarabaCoreServiceProvider.php
if ($container->has('jaraba_foc.alert_storage')) {
    $definition->replaceArgument(1, new Reference('jaraba_foc.alert_storage'));
}
```

### 4. Dark Mode via CSS Custom Properties (FASE 7)

**Decision:** Mixins SCSS `dark-tokens` y `dark-components` aplicados a dos selectores: `body.dark-mode .admin-center-shell` y `@media (prefers-color-scheme: dark) body.page-admin-center`.

**Razon:** Activacion dual (manual via toggle body class + automatica via media query). Los mixins evitan duplicacion de CSS. Todos los tokens estan en `var(--ej-*, fallback)`, asi que el dark mode solo sobreescribe custom properties.

### 5. API Keys con SHA-256 (FASE 7)

**Decision:** Almacenar API keys hasheadas con SHA-256 en Drupal config. La key original se muestra solo una vez al momento de creacion.

**Razon:** Security by design — si la config se filtra, los keys hasheados no son reversibles. El patron `bin2hex(random_bytes(24))` genera keys de 48 caracteres suficientemente aleatorios.

### 6. `_admin_route: FALSE` para forzar Frontend Theme

**Decision:** Todas las rutas del Admin Center usan `_admin_route: FALSE` en routing.yml.

**Razon:** Sin esto, Drupal aplicaria el admin theme (Seven/Claro) en lugar del theme custom ecosistema_jaraba_theme. Esto permite que el shell premium use el diseno propio con variables CSS del ecosistema.

---

## Archivos Creados/Modificados

### Servicios (5 nuevos)
- `src/Service/AdminCenterAggregatorService.php` — Scorecards + Quick Links + Recent Activity
- `src/Service/AdminCenterLayoutService.php` — Menu sections + Breadcrumbs
- `src/Service/AdminCenterTenantService.php` — Tenants CRUD + filtros
- `src/Service/AdminCenterUserService.php` — Users CRUD + avatar detection
- `src/Service/AdminCenterSettingsService.php` — Config + Plans + Integrations + API Keys

### Controllers (2 modificados)
- `src/Controller/AdminCenterController.php` — 8 metodos pagina (dashboard, tenants, users, finance, alerts, analytics, logs, settings)
- `src/Controller/AdminCenterApiController.php` — 30+ endpoints REST

### Templates Twig (10)
- `admin-center-shell.html.twig` — Shell con sidebar + topbar + content
- `admin-center-sidebar.html.twig` — Navegacion lateral colapsable
- `admin-center-topbar.html.twig` — Barra superior con search + user
- `admin-center-tenants.html.twig` — DataTable tenants
- `admin-center-users.html.twig` — DataTable usuarios
- `admin-center-finance.html.twig` — Centro Financiero SaaS
- `admin-center-alerts.html.twig` — Alertas + Playbooks
- `admin-center-analytics.html.twig` — KPIs + Chart.js trends
- `admin-center-logs.html.twig` — Visor de logs con filtros
- `admin-center-settings.html.twig` — 4-tab settings page

### SCSS Parciales (10)
- `_admin-center-layout.scss` — Shell (510 LOC)
- `_admin-center-datatable.scss` — DataTable reusable
- `_admin-center-finance.scss` — Finance dashboard
- `_admin-center-alerts.scss` — Alerts dashboard
- `_admin-center-analytics.scss` — Analytics + logs
- `_admin-center-settings.scss` — Settings tabs + forms
- `_admin-center-dark-mode.scss` — Dark tokens + components + a11y

### JavaScript (10)
- `js/admin-center-layout.js` — Sidebar toggle + shortcuts
- `js/admin-center-datatable.js` — AdminCenterDataTable class
- `js/admin-center-tenants-init.js` — Tenants DataTable config
- `js/admin-center-users-init.js` — Users DataTable config
- `js/admin-center-finance-init.js` — Finance scorecards + metrics
- `js/admin-center-alerts-init.js` — Alerts cards + playbook grid
- `js/admin-center-analytics-init.js` — Chart.js + AI telemetry
- `js/admin-center-logs-init.js` — Log viewer + source tabs
- `js/admin-center-settings-init.js` — Settings tabs + API key CRUD

---

## Reglas Derivadas

| Regla | Descripcion |
|-------|-------------|
| **ADMIN-CENTER-001** | Toda pagina del Admin Center usa el shell layout (sidebar + topbar). No crear paginas admin standalone fuera del shell |
| **ADMIN-CENTER-002** | APIs del Admin Center siguen envelope `{success, data, meta}` via `ApiResponseTrait`. Nunca devolver arrays planos |
| **ADMIN-CENTER-003** | Servicios de modulos opcionales se inyectan con `~` NULL + ServiceProvider condicional. Nunca usar `\Drupal::service()` para dependencias opcionales en servicios (solo en ApiController para servicios no inyectados) |
| **ADMIN-CENTER-004** | Dark mode tokens se definen una sola vez en mixin y se aplican a body.dark-mode + @media prefers-color-scheme:dark. Nunca duplicar declaraciones |
| **ADMIN-CENTER-005** | Rutas Admin Center llevan `_admin_route: FALSE` para forzar frontend theme. Sin esto el shell premium no funciona |

---

## Metricas

| Metrica | Valor |
|---------|-------|
| FASEs completadas | 7 / 7 |
| Servicios nuevos | 5 |
| API endpoints | 30+ |
| Templates Twig | 10 |
| Parciales SCSS | 10 |
| Archivos JS | 10 |
| Libraries Drupal | 12 |
| Rutas routing.yml | ~40 (8 paginas + 30+ APIs) |
| LOC SCSS estimados | ~2,500 |
| LOC JS estimados | ~2,000 |
