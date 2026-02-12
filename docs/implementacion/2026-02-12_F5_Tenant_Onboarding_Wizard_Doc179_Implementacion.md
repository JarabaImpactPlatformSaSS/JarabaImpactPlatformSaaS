# F5 — Tenant Onboarding Wizard (Doc 179) — Plan de Implementacion

**Fecha:** 2026-02-12
**Fase:** F5 de 12
**Modulo:** `jaraba_onboarding` (extension del existente)
**Estimacion:** 32-40h
**Dependencias:** F1 (ECA Registry), F2 (Freemium/Trial), F4 (Landing Pages)

---

## 1. Objetivo

Implementar el wizard de 7 pasos para configuracion inicial del tenant, con persistencia de progreso, asistencia IA para extraccion de paleta de colores del logo, y celebracion al completar.

## 2. Estado Actual (Pre-implementacion)

### 2.1 Lo que ya existe

| Componente | Estado | Ubicacion |
|-----------|--------|-----------|
| `UserOnboardingProgress` entity | Completo | `jaraba_onboarding/src/Entity/` |
| `OnboardingTemplate` entity | Completo | `jaraba_onboarding/src/Entity/` |
| `OnboardingOrchestratorService` | Completo | `jaraba_onboarding/src/Service/` |
| `OnboardingChecklistService` | Completo | `jaraba_onboarding/src/Service/` |
| `OnboardingGamificationService` | Completo | `jaraba_onboarding/src/Service/` |
| `OnboardingContextualHelpService` | Completo | `jaraba_onboarding/src/Service/` |
| `OnboardingAnalyticsService` | Completo | `jaraba_onboarding/src/Service/` |
| `OnboardingDashboardController` | Completo | Ruta `/onboarding` |
| `OnboardingApiController` | Completo | 3 endpoints REST |
| Core `TenantOnboardingService` | Completo | `ecosistema_jaraba_core` |
| Core `OnboardingController` (4 pasos) | Completo | Rutas `/registro/*` |
| FOC `StripeConnectService` (Standard) | Completo | `jaraba_foc` |
| Mentoring `StripeConnectService` (Express) | Completo | `jaraba_mentoring` |
| `AvatarDetectionService` | Completo | `ecosistema_jaraba_core` |
| JS celebrations (confetti) | Completo | `js/onboarding-celebrations.js` |

### 2.2 Gaps a cerrar

| Gap | Tipo | Prioridad |
|-----|------|-----------|
| `TenantOnboardingProgress` entity | Nueva entidad | Critico |
| `TenantOnboardingWizardController` | Nuevo controller (7 pasos) | Critico |
| `TenantOnboardingWizardService` | Nuevo servicio | Critico |
| `LogoColorExtractorService` | Nuevo servicio (IA vision) | Alto |
| NIF/CIF validation utility | Nuevo | Alto |
| 7 step templates + progress bar | Nuevos templates | Critico |
| Wizard SCSS | Nuevo | Critico |
| Wizard JS (navigation, logo upload) | Nuevo | Critico |

## 3. Arquitectura

### 3.1 Nueva Content Entity: `TenantOnboardingProgress`

```
jaraba_onboarding/src/Entity/TenantOnboardingProgress.php
```

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| `id` | integer | ID automatico |
| `tenant_id` | entity_reference → group | Tenant (Group) asociado |
| `user_id` | entity_reference → user | Usuario que realiza el wizard |
| `current_step` | integer | Paso actual (1-7) |
| `completed_steps` | string_long (JSON) | Array de pasos completados |
| `step_data` | string_long (JSON) | Datos capturados por paso |
| `started_at` | created | Timestamp de inicio |
| `completed_at` | timestamp | NULL si incompleto |
| `time_spent_seconds` | integer | Tiempo total invertido |
| `skipped_steps` | string_long (JSON) | Pasos omitidos |
| `vertical` | list_string | Vertical del tenant |

### 3.2 Wizard Controller

Ruta: `/onboarding/wizard/{step}` (frontend limpio, sin regiones Drupal admin)

7 metodos step + Stripe callback:
- `stepWelcome()` — Confirmar vertical (30s)
- `stepIdentity()` — Logo + AI paleta + colores (2min)
- `stepFiscal()` — NIF/CIF + direccion (2min, solo commerce)
- `stepPayments()` — Stripe Connect redirect (3min, solo commerce)
- `stepTeam()` — Invitar colaboradores (1min, saltable)
- `stepContent()` — Primer producto/servicio segun vertical (3min)
- `stepLaunch()` — Confetti + preview + compartir (30s)
- `stripeCallback()` — Return de Stripe Connect

### 3.3 Servicios

| Servicio | Funcion |
|----------|---------|
| `TenantOnboardingWizardService` | save/advance, skip, getProgress, completeWizard |
| `LogoColorExtractorService` | AI vision para extraer paleta de logo |

### 3.4 Templates

| Template | Tipo |
|----------|------|
| `onboarding-wizard.html.twig` | Wrapper principal del wizard |
| `onboarding-wizard-step-welcome.html.twig` | Paso 1 |
| `onboarding-wizard-step-identity.html.twig` | Paso 2 |
| `onboarding-wizard-step-fiscal.html.twig` | Paso 3 |
| `onboarding-wizard-step-payments.html.twig` | Paso 4 |
| `onboarding-wizard-step-team.html.twig` | Paso 5 |
| `onboarding-wizard-step-content.html.twig` | Paso 6 |
| `onboarding-wizard-step-launch.html.twig` | Paso 7 |
| `_onboarding-progress-bar.html.twig` | Parcial barra de progreso |

## 4. Pasos del Wizard por Vertical

| Paso | AgroConecta | ComercioConecta | ServiciosConecta | Empleabilidad | Emprendimiento |
|------|-------------|-----------------|------------------|---------------|----------------|
| 1 Welcome | Si | Si | Si | Si | Si |
| 2 Identity | Si | Si | Si | Si | Si |
| 3 Fiscal | Si | Si | Si | Skip | Skip |
| 4 Payments | Si | Si | Si | Skip | Skip |
| 5 Team | Saltable | Saltable | Saltable | Saltable | Saltable |
| 6 Content | Producto | Producto | Servicio | CV/Perfil | Plan negocio |
| 7 Launch | Si | Si | Si | Si | Si |

## 5. Verificacion

- [ ] Entity schema correcto (`drush entity:updates`)
- [ ] 8+ rutas registradas (7 pasos + callback)
- [ ] Library `wizard` en `.libraries.yml`
- [ ] `hook_theme()` con 9 entradas nuevas
- [ ] SCSS compilado sin errores
- [ ] `drush cr` exitoso
