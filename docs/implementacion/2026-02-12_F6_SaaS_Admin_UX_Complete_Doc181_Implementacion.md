# F6 — SaaS Admin UX Complete (Doc 181) — Plan de Implementacion

**Fecha:** 2026-02-12
**Fase:** F6 de 12
**Modulo:** `ecosistema_jaraba_core` (extension)
**Estimacion:** 24-32h
**Dependencias:** F1 (ECA), F5 (Onboarding), servicios FOC/Billing/CS existentes

---

## 1. Objetivo

Crear el Admin Center Dashboard unificado que agrega KPIs de todos los modulos,
implementar el Command Palette global (Cmd+K) y completar los shortcuts de teclado.

## 2. Estado Actual (Pre-implementacion)

### 2.1 Servicios disponibles para agregar

| Servicio | Modulo | Metodos clave |
|----------|--------|---------------|
| `jaraba_foc.saas_metrics` | jaraba_foc | `calculateMRR()`, `calculateARR()` |
| `jaraba_customer_success.health_calculator` | jaraba_customer_success | `calculate(tenant_id)` |
| `jaraba_customer_success.churn_prediction` | jaraba_customer_success | `predict(tenant_id)` |
| `jaraba_billing.tenant_subscription` | jaraba_billing | `startTrial()`, `activateSubscription()` |
| `ecosistema_jaraba_core.tenant_analytics` | core | `getSalesTrend()` |
| `ecosistema_jaraba_core.impersonation` | core | `start()` |
| `ecosistema_jaraba_core.alerting` | core | alertas Slack/webhook |
| `AlertRule` entity | core | 5 metricas, 4 operadores |

### 2.2 Dashboards admin existentes

| Dashboard | Ruta | Modulo |
|-----------|------|--------|
| Platform Health | `/admin/health` | core |
| FinOps | `/admin/finops` | core |
| Analytics | `/admin/jaraba/analytics` | jaraba_analytics |
| Customer Success | `/admin/structure/customer-success` | jaraba_customer_success |
| Compliance | `/admin/seguridad` | core |
| RBAC Matrix | `/admin/people/rbac-matrix` | core |

### 2.3 Gaps a cerrar

| Gap | Tipo | Prioridad |
|-----|------|-----------|
| Admin Center Dashboard unificado | Nuevo controller + template | Critico |
| Admin Search API (fuzzy) | Nuevo endpoint | Critico |
| Command Palette global (Cmd+K) | Nuevo JS | Critico |
| Keyboard Shortcuts (G+T, G+F, etc.) | Nuevo JS | Alto |

## 3. Arquitectura

### 3.1 AdminCenterController

Ruta: `/admin/jaraba/center` (`_admin_route: TRUE`)

Agrega datos de todos los servicios en un unico dashboard.

### 3.2 AdminSearchApiController

Ruta: `GET /api/v1/admin/search?q={query}`

Busqueda fuzzy de tenants, usuarios y entidades.

### 3.3 Command Palette

JS: `ecosistema_jaraba_core/js/admin-command-palette.js`

Adjuntado via `hook_preprocess_html()` en todas las rutas admin.

Comandos:
| Comando | Shortcut | Accion |
|---------|----------|--------|
| `go tenants` | G+T | Lista tenants |
| `go finance` | G+F | Dashboard FOC |
| `go users` | G+U | Lista usuarios |
| `search [query]` | / | Buscar tenant/usuario |
| `impersonate [email]` | I | Login como usuario |
| `alerts` | A | Ver alertas activas |
| `help` | ? | Mostrar todos los comandos |

## 4. Verificacion

- [ ] Ruta `/admin/jaraba/center` registrada
- [ ] API `/api/v1/admin/search` registrada
- [ ] Library `admin-center` definida
- [ ] Library `admin-command-palette` definida
- [ ] `hook_theme()` con `admin_center_dashboard`
- [ ] `hook_preprocess_html()` adjunta command palette en admin
- [ ] `drush cr` exitoso
