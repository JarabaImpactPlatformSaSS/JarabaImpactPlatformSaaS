# F7 — Entity Admin Dashboard Elena (Doc 182) — Plan de Implementacion

**Fecha:** 2026-02-12
**Fase:** F7 de 12
**Modulo:** `jaraba_analytics` (extension)
**Estimacion:** 24-32h
**Dependencias:** F5 (Onboarding), F6 (Admin Center pattern)

---

## 1. Objetivo

Crear el dashboard para el avatar Elena (administradora institucional), con Grant
Burn Rate tracker, gestion de cohortes y generacion de informes para justificacion
de fondos publicos.

## 2. Estado Actual (Pre-implementacion)

### 2.1 Servicios existentes en jaraba_analytics

| Servicio | Funcion |
|----------|---------|
| `jaraba_analytics.cohort_analysis` | Retencion por cohorte (buildRetentionCurve) |
| `jaraba_analytics.analytics_data` | Capa de datos |
| `jaraba_analytics.report_execution` | Ejecucion de informes custom |
| `jaraba_analytics.report_scheduler` | Informes programados por email |
| `jaraba_analytics.analytics_aggregator` | Agregacion diaria via cron |

### 2.2 Servicio PDF disponible

| Servicio | Modulo | Metodos |
|----------|--------|---------|
| `branded_pdf` | ecosistema_jaraba_core | `generateInvoice()`, `generateCertificate()` |
| `jaraba_whitelabel.branded_pdf` | jaraba_whitelabel | PDF con marca reseller |

### 2.3 Gaps a cerrar

| Gap | Tipo | Prioridad |
|-----|------|--------------|
| GrantTrackingService | Nuevo servicio | Critico |
| InstitutionalReportService | Nuevo servicio (5 templates PDF) | Critico |
| ProgramDashboardController | Nuevo controller | Critico |
| Ruta /programa/dashboard | Nueva ruta frontend | Critico |
| Permiso access programa dashboard | Nueva permission | Alto |
| Template programa-dashboard | Nuevo template Twig | Alto |
| CSS programa-dashboard | Nuevos estilos | Alto |
| JS grant-burn-rate chart | Chart.js visualizacion | Alto |

## 3. Arquitectura

### 3.1 GrantTrackingService

Calcula la tasa de consumo de un grant respecto a su linea temporal:
- `calculateBurnRate(grant_total, spent, start_date, end_date)` → burn_rate, expected_rate, deviation, alert
- Alerta si desviacion > 15%

### 3.2 InstitutionalReportService

Genera 5 tipos de informes PDF reutilizando BrandedPdfService:

| Template | Contenido | Formato |
|----------|-----------|---------|
| Seguimiento Mensual | Alumnos, progreso, incidencias | PDF A4 |
| Memoria Economica | Desglose gastos por partida | PDF A4 |
| Informe de Impacto | Insercion laboral, creacion empresa | PDF A4 |
| Justificacion Tecnica | Evidencias actividad formativa | PDF A4 |
| Certificados Asistencia | Generacion masiva por cohorte | PDF A4 batch |

### 3.3 ProgramDashboardController

Ruta: `/programa/dashboard`

Agrega: burn rate, cohortes activas, informes disponibles, KPIs del programa.

### 3.4 APIs

| Endpoint | Metodo | Funcion |
|----------|--------|---------|
| `/api/v1/programa/grant-status` | GET | Estado actual del grant |
| `/api/v1/programa/reports/generate` | POST | Genera informe PDF |

## 4. Verificacion

- [ ] Ruta `/programa/dashboard` registrada
- [ ] API `/api/v1/programa/grant-status` registrada
- [ ] API `/api/v1/programa/reports/generate` registrada
- [ ] Permiso `access programa dashboard` definido
- [ ] Library `programa-dashboard` definida
- [ ] Service `jaraba_analytics.grant_tracking` registrado
- [ ] Service `jaraba_analytics.institutional_report` registrado
- [ ] hook_theme con `programa_dashboard`
- [ ] Template `programa-dashboard.html.twig` creado
- [ ] `drush cr` exitoso
