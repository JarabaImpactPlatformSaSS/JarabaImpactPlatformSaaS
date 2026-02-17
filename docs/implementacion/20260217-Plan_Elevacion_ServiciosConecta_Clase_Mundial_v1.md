# Plan de Elevacion ServiciosConecta Clase Mundial v1

| Metadato | Valor |
|----------|-------|
| **Documento** | Plan de Elevacion a Clase Mundial |
| **Vertical** | ServiciosConecta |
| **Version** | 1.0.0 |
| **Fecha** | 2026-02-17 |
| **Estado** | Completado |
| **Modulo Principal** | `jaraba_servicios_conecta` |
| **Modulos Afectados** | `ecosistema_jaraba_core`, `ecosistema_jaraba_theme`, `jaraba_page_builder`, `jaraba_journey` |
| **Avatares** | `profesional` (8 pasos), `cliente_servicios` (6 pasos) |
| **Comision SaaS** | 10% (la mas alta de todos los verticales) |
| **Inspiracion Principal** | JarabaLex (vault cifrado, calendario, facturacion, IA legal, copilot RAG) |
| **Estimacion Total** | 14 Fases — 290-370 horas — EUR 13,050-16,650 |

---

## Tabla de Contenidos (TOC)

- [1. Contexto y Motivacion](#1-contexto-y-motivacion)
- [2. Estado Actual — Diagnostico Exhaustivo](#2-estado-actual)
- [3. Analisis de Gaps — Matriz de Paridad 26 Componentes](#3-analisis-de-gaps)
- [4. Inspiracion JarabaLex — Patrones Replicables](#4-inspiracion-jarabalex)
- [5. Plan de Fases (F0-F13)](#5-plan-de-fases)
- [6. Detalle por Fase](#6-detalle-por-fase)
- [7. Tabla de Correspondencia con Especificaciones Tecnicas](#7-tabla-correspondencia)
- [8. Cumplimiento de Directrices del Proyecto](#8-cumplimiento-directrices)
- [9. Inventario de Entidades, Servicios y Endpoints](#9-inventario)
- [10. Paleta de Colores y Design Tokens](#10-paleta)
- [11. Plantillas Page Builder Premium](#11-page-builder-premium)
- [12. Recorridos de Usuario (User Journeys)](#12-user-journeys)
- [13. Dependencias Cruzadas y Cross-Vertical](#13-dependencias-cruzadas)
- [14. Verificacion y QA](#14-verificacion)
- [15. Registro de Cambios](#15-changelog)

---

## 1. Contexto y Motivacion

ServiciosConecta es el quinto vertical comercializable del Ecosistema Jaraba. Su proposito es digitalizar el capital intelectual y profesional de zonas rurales y periurbanas: abogados, clinicas, arquitectos, consultores, asesores fiscales, coaches. Su diferenciador fundamental es: **"No vendemos stock, vendemos confianza, tiempo y conocimiento"**.

### 1.1 Problema Detectado

El vertical cuenta con una Fase 1 funcional (5 Content Entities, 3 Controllers, 3 Services, marketplace basico, 11 bloques PB) pero esta muy por debajo del nivel de clase mundial alcanzado por Empleabilidad, Emprendimiento, Andalucia+EI, JarabaLex y AgroConecta. La puntuacion de paridad actual es:

**Paridad: 5/26 componentes (19.2%)** — La mas baja de todos los verticales.

### 1.2 Objetivo

Elevar ServiciosConecta al 100% de paridad con los verticales de clase mundial, incluyendo:
- Infraestructura de elevacion completa (FeatureGate, HealthScore, Journey, Emails, CrossVertical)
- Correccion de bugs criticos (releaseSlot() inexistente)
- Cumplimiento total de directrices (colores, iconos, SCSS, i18n, zero-region)
- Plantillas PB premium de primer nivel
- Recorridos completos: visitante no registrado → registro → embudo de ventas → escalera de valor → cross-vertical

---

## 2. Estado Actual

### 2.1 Lo que EXISTE (Fase 1 implementada)

| Componente | Estado | Archivos |
|-----------|--------|----------|
| 5 Content Entities | Implementado | ProviderProfile, ServiceOffering, Booking, AvailabilitySlot, ServicePackage |
| 3 Controllers | Implementado | MarketplaceController, ProviderPortalController, ServiceApiController |
| 3 Services | Implementado | ProviderService, ServiceOfferingService, AvailabilityService |
| 2 Taxonomias | Seed en install | servicios_category (39 subespecialidades), servicios_modality |
| 7 REST API endpoints | Implementado | CRUD providers, offerings, bookings, availability |
| 15 Permisos | Implementado | Granulares por entidad y rol |
| 5 Templates | Implementado | marketplace, provider-detail, dashboard, offerings, calendar |
| 2 Partials | Implementado | provider-card, service-card |
| 11 Bloques PB | Implementado | hero, features, pricing, stats, testimonials, social_proof, faq, gallery, map, cta, content |
| SCSS (4 partials) | Implementado (NO COMPLIANT) | variables, marketplace, provider-detail, provider-dashboard, components |
| JS (3 behaviors) | Implementado | marketplace, provider-portal, booking |
| Hooks | Implementado | preprocess_html, entity_insert/update, cron, mail |
| JourneyDefinition | Implementado | 2 avatares en jaraba_journey |
| FreemiumVerticalLimit | 2 configs | bookings_per_month, services |
| Landing page | Implementado | VerticalLandingController::serviciosConecta() |
| Lead Magnet | Implementado | Template Propuesta Profesional |

### 2.2 BUG CRITICO Detectado

`AvailabilityService::releaseSlot()` es invocado desde `hook_entity_update()` (linea ~230) y `hook_cron()` pero **el metodo NO EXISTE** en la clase. Esto causaria un error fatal PHP en produccion cuando se cancela una reserva.

### 2.3 Violaciones de Directrices Detectadas

| Violacion | Ubicacion | Correccion |
|-----------|-----------|------------|
| Color `#1E40AF` (no en paleta) | `scss/_variables.scss:11` | → `var(--ej-color-corporate, #233D63)` |
| Color `#059669` (Tailwind green) | `scss/_variables.scss:14` | → `var(--ej-color-success, #10B981)` |
| Color `#7C3AED` (Tailwind purple) | `scss/_variables.scss:15` | → `var(--ej-color-innovation, #00A9A5)` |
| Color `#DC2626` (Tailwind red) | `scss/_variables.scss:21` | → `var(--ej-color-danger, #EF4444)` |
| Emoji `&#128188;` en hero PB | `hero.html.twig:32` | → `jaraba_icon('ui', 'briefcase')` |
| Emoji `&#128101;`, `&#128196;`, `&#11088;` en features PB | `features.html.twig:18-20` | → `jaraba_icon()` |
| No existe `page--servicios.html.twig` zero-region | tema | Crear siguiendo patron `page--vertical-landing.html.twig` |
| No existe `hook_preprocess_page()` | `.module` | Crear con variables zero-region |
| No existe `ServiciosConectaSettingsForm` | `src/Form/` | Crear para configuracion del vertical |
| No existe `jaraba_servicios_conecta.settings.yml` | `config/install/` | Crear config base |

---

## 3. Analisis de Gaps — Matriz de Paridad 26 Componentes

| # | Componente | Empleabilidad | Emprendimiento | Andalucia+EI | JarabaLex | AgroConecta | ComercioConecta | **ServiciosConecta** | Gap |
|---|-----------|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| 1 | FeatureGateService | OK | OK | OK | OK | OK | OK | **FALTA** | G1 |
| 2 | UpgradeTriggerService + fire() | OK | OK | OK | OK | OK | OK | **FALTA** | G2 |
| 3 | CopilotBridgeService | OK | OK | OK | OK | OK | OK | **FALTA** | G3 |
| 4 | hook_preprocess_page (zero-region) | OK | OK | OK | OK | OK | OK | **FALTA** | G4 |
| 5 | page template zero-region | OK | OK | OK | OK | OK | OK | **FALTA** | G5 |
| 6 | SCSS compliance (color-mix, var) | OK | OK | OK | OK | OK | OK | **FALTA** | G6 |
| 7 | Design Token Config Entity | OK | OK | OK | OK | OK | OK | **FALTA** | G7 |
| 8 | EmailSequenceService + MJML | OK | OK | OK | OK | OK | OK | **FALTA** | G8 |
| 9 | CRM sync hooks | OK | OK | OK | OK | OK | OK | **FALTA** | G9 |
| 10 | CrossVerticalBridgeService | OK | OK | OK | OK | OK | OK | **FALTA** | G10 |
| 11 | JourneyProgressionService | OK | OK | OK | OK | OK | - | **FALTA** | G11 |
| 12 | HealthScoreService | OK | OK | OK | OK | OK | - | **FALTA** | G12 |
| 13 | Dedicated CopilotAgent | OK | OK | OK | OK | - | - | **FALTA** | G13 |
| 14 | Avatar Navigation entry | OK | OK | OK | OK | OK | OK | **VERIFICAR** | G14 |
| 15 | FunnelDefinition config | OK | OK | OK | OK | OK | - | **FALTA** | G15 |
| 16 | ExperimentService (A/B) | OK | OK | OK | - | OK | - | **FALTA** | G16 |
| 17 | Settings form + config | OK | OK | OK | OK | OK | OK | **FALTA** | G17 |
| 18 | Review entity | OK | OK | - | - | OK | OK | **FALTA** | G18 |
| 19 | hook_theme_suggestions_page_alter | OK | OK | OK | OK | OK | OK | **PARCIAL** | G19 |
| 20 | PB bloques premium (is_premium) | OK | OK | OK | OK | OK | OK | **FALTA** | G20 |
| 21 | Bug: releaseSlot() no existe | - | - | - | - | - | - | **BUG** | G21 |
| 22 | Templates faltantes (bookings, profile) | - | - | - | - | - | - | **FALTA** | G22 |
| 23 | Partials faltantes (booking-card, review-card) | - | - | - | - | - | - | **FALTA** | G23 |
| 24 | ProviderService N+1 query antipatron | - | - | - | - | - | - | **PERF** | G24 |
| 25 | fields_schema incompleto en PB blocks | - | - | - | - | - | - | **FALTA** | G25 |
| 26 | BaseAgent vertical context 'serviciosconecta' | OK | OK | OK | OK | OK | OK | **VERIFICAR** | G26 |

**Resultado: 5/26 OK (19.2%) — 21 gaps criticos**

---

## 4. Inspiracion JarabaLex — Patrones Replicables

JarabaLex (9 modulos, 137 archivos, 22,703 LOC) es el vertical mas sofisticado del ecosistema. ServiciosConecta debe replicar estos patrones fundamentales:

| Patron JarabaLex | Equivalente ServiciosConecta | Prioridad |
|-----------------|----------------------------|-----------|
| **Legal Vault** (cifrado AES-256-GCM, versionado) | **Buzon de Confianza** — custodia documental cifrada cliente-profesional | P0 FASE 2+ |
| **Legal Calendar** (plazos, audiencias, deadlines) | **Booking Engine completo** — calendario con sincronizacion Google/Outlook | P0 FASE 2+ |
| **Legal Billing** (tarifas por caso, time tracking) | **Sistema Facturacion** — tarifas por servicio, paquetes, split payment | FASE 9 |
| **Legal Intelligence Hub** (RAG + NLP + Qdrant) | **Copilot de Servicios** — RAG sobre documentos del caso | FASE 6 |
| **Legal Templates** (plantillas documentales) | **Presupuestador Automatico** — plantillas de presupuestos | FASE 5 |
| **Case Management** (expedientes + milestones) | **Gestion de Casos** — triaje IA + derivacion profesional | FASE 5 |
| **LexNet Integration** (integracion con servicios externos) | **Calendar Sync** — Google Calendar + Outlook | FASE 2+ |
| **Legal Knowledge Base** (base de conocimiento) | **Knowledge Base Profesional** — articulos, guias por especialidad | FUTURO |
| **14-Phase Elevation Pattern** | **14 Fases de Elevacion** (este plan) | ESTE PLAN |

### 4.1 Lecciones Clave de JarabaLex

1. **Separar vertical del modulo core**: JarabaLex se elevo a vertical independiente. ServiciosConecta ya es independiente.
2. **Exponer colores como CSS custom properties**: `--ej-{vertical}-*` en `:root {}`.
3. **Zero-region obligatorio**: `page--servicios.html.twig` siguiendo patron `page--fiscal.html.twig`.
4. **FeatureGate con tabla dedicada**: `{serviciosconecta_feature_usage}` para tracking de uso.
5. **Registrar features en FeatureAccessService::FEATURE_ADDON_MAP**.
6. **Peso dedicado en TemplateRegistry**: rango 220-229 para serviciosconecta (ya asignado).

---

## 5. Plan de Fases (F0-F13)

| Fase | Nombre | Prioridad | Horas | Gaps |
|------|--------|-----------|-------|------|
| **F0** | Bug Fix + Settings + FeatureGateService | P0 CRITICA | 20-25h | G1, G17, G21, G24 |
| **F1** | UpgradeTriggerService + Upsell Contextual IA | P0 CRITICA | 15-18h | G2 |
| **F2** | CopilotBridgeService Dedicado | P1 ALTA | 15-18h | G3, G26 |
| **F3** | hook_preprocess_page + Body Classes Consolidadas | P0 CRITICA | 8-10h | G4, G19 |
| **F4** | Page Template Zero-Region + Copilot FAB | P0 CRITICA | 15-18h | G5, G22, G23 |
| **F5** | SCSS Compliance + Design Tokens | P1 ALTA | 12-15h | G6, G7 |
| **F6** | Review Entity + Service | P1 ALTA | 18-22h | G18 |
| **F7** | Email Sequences MJML (6 secuencias) | P1 ALTA | 20-25h | G8 |
| **F8** | CrossVerticalBridgeService (4 puentes) | P2 MEDIA | 15-18h | G10 |
| **F9** | JourneyProgressionService (8 reglas proactivas) | P0 CRITICA | 20-25h | G11 |
| **F10** | HealthScoreService (5 dimensiones + 8 KPIs) | P1 ALTA | 18-22h | G12 |
| **F11** | CopilotAgent Dedicado + CRM Integration | P1 ALTA | 25-30h | G9, G13 |
| **F12** | Avatar Navigation + Funnel Analytics + A/B | P2 MEDIA | 15-18h | G14, G15, G16 |
| **F13** | Page Builder Premium + QA Integral | P1 ALTA | 40-50h | G20, G25 |
| **TOTAL** | | | **256-314h** | 26/26 |

---

## 6. Detalle por Fase

### FASE 0 — Bug Fix + Settings + FeatureGateService {#fase-0}

**Prioridad:** P0 CRITICA | **Estimacion:** 20-25h | **Gaps:** G1, G17, G21, G24

**Descripcion:** Corregir el bug critico de `releaseSlot()`, crear la configuracion base del modulo, implementar FeatureGateService para monetizacion por plan.

#### 6.0.1 Archivos a CORREGIR (Bug G21)

| Archivo | Correccion |
|---------|------------|
| `jaraba_servicios_conecta/src/Service/AvailabilityService.php` | Implementar metodo `releaseSlot(int $bookingId): void` que libere el slot de disponibilidad cuando se cancela una reserva. |
| `jaraba_servicios_conecta/src/Service/ProviderService.php` | Refactorizar `getActiveCities()` para usar query directa con `DISTINCT` en vez de cargar todas las entidades (G24). |

#### 6.0.2 Archivos a CREAR

| Archivo | Descripcion |
|---------|------------|
| `jaraba_servicios_conecta/config/install/jaraba_servicios_conecta.settings.yml` | Config base: `booking_buffer_minutes: 15`, `max_advance_booking_days: 60`, `require_prepayment: false`, `auto_cancel_hours: 24`, `reminder_hours: [24, 1]`, `commission_rate: 10`, `stripe_connect_mode: 'express'`. |
| `jaraba_servicios_conecta/config/schema/jaraba_servicios_conecta.schema.yml` | Schema para la config anterior. |
| `jaraba_servicios_conecta/src/Form/ServiciosConectaSettingsForm.php` | FormBase en `/admin/config/jaraba/servicios-conecta` con todos los campos configurables. Todos los labels con `$this->t()`. |
| `ecosistema_jaraba_core/src/Service/ServiciosConectaFeatureGateService.php` | Siguiendo patron exacto de `AgroConectaFeatureGateService`. Features a gatear: `max_services` (CUMULATIVE), `max_bookings_per_month` (MONTHLY), `calendar_sync` (BINARY), `buzon_confianza` (BINARY), `firma_digital` (BINARY), `ai_triage` (BINARY), `video_conferencing` (BINARY), `analytics_dashboard` (BINARY). |
| `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.freemium_vertical_limit.serviciosconecta_free_services.yml` | Free: max 3 servicios. |
| `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.freemium_vertical_limit.serviciosconecta_free_bookings.yml` | Free: max 10 reservas/mes. |
| `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.freemium_vertical_limit.serviciosconecta_starter_services.yml` | Starter: max 10 servicios. |
| `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.freemium_vertical_limit.serviciosconecta_starter_bookings.yml` | Starter: max 50 reservas/mes. |

#### 6.0.3 Archivos a MODIFICAR

| Archivo | Modificacion |
|---------|-------------|
| `ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml` | Registrar `ecosistema_jaraba_core.serviciosconecta_feature_gate`. |
| `ecosistema_jaraba_core/src/Service/FeatureAccessService.php` | Anadir `'serviciosconecta'` al `FEATURE_ADDON_MAP` con features: `calendar_sync`, `buzon_confianza`, `firma_digital`, `ai_triage`, `video_conferencing`, `analytics_dashboard`. |
| `jaraba_servicios_conecta/jaraba_servicios_conecta.routing.yml` | Anadir ruta settings form. |

#### 6.0.4 Escalera de Valor (SaaS Plans)

| Plan | Precio | Servicios | Reservas/mes | Calendar Sync | Buzon | Firma Digital | IA Triaje | Video | Analytics |
|------|--------|-----------|-------------|:---:|:---:|:---:|:---:|:---:|:---:|
| Free | 0 EUR | 3 | 10 | - | - | - | - | - | - |
| Starter | 29 EUR/mes | 10 | 50 | OK | - | - | - | - | - |
| Profesional | 79 EUR/mes | Ilimitados | Ilimitados | OK | OK | OK | OK | OK | OK |
| Enterprise | Custom | Ilimitados | Ilimitados | OK | OK | OK | OK | OK | OK + custom |

---

### FASE 1 — UpgradeTriggerService + Upsell Contextual IA {#fase-1}

**Prioridad:** P0 CRITICA | **Estimacion:** 15-18h | **Gap:** G2

#### 6.1.1 Archivos a CREAR

| Archivo | Descripcion |
|---------|------------|
| `ecosistema_jaraba_core/src/Service/ServiciosConectaUpgradeTriggerService.php` | 5 triggers: `services_limit_80` (4/5 en free), `bookings_limit_80` (8/10 en free), `calendar_sync_attempt` (feature gated), `buzon_attempt` (feature gated), `analytics_attempt` (feature gated). Cada trigger genera: `upgrade_message` traducible, `upgrade_cta_url` (/billing/upgrade), `upgrade_plan_suggested`. |

#### 6.1.2 Archivos a MODIFICAR

| Archivo | Modificacion |
|---------|-------------|
| `jaraba_servicios_conecta/src/Controller/ProviderPortalController.php` | Inyectar `ServiciosConectaFeatureGateService` y `ServiciosConectaUpgradeTriggerService`. Llamar `fire()` en: `offerings()` (trigger services_limit), `bookings()` (trigger bookings_limit), `calendar()` (trigger calendar_sync). |
| `jaraba_servicios_conecta/jaraba_servicios_conecta.services.yml` | Registrar servicios inyectados. |
| `ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml` | Registrar `ecosistema_jaraba_core.serviciosconecta_upgrade_trigger`. |

---

### FASE 2 — CopilotBridgeService Dedicado {#fase-2}

**Prioridad:** P1 ALTA | **Estimacion:** 15-18h | **Gaps:** G3, G26

#### 6.2.1 Archivos a CREAR

| Archivo | Descripcion |
|---------|------------|
| `ecosistema_jaraba_core/src/Service/ServiciosConectaCopilotBridgeService.php` | Contexto inyectado en BaseAgent: perfil profesional actual, servicios publicados, reservas activas, metricas (total bookings, rating, revenue mes), especialidades. Metodos: `getVerticalContext(int $userId): array`, `getSuggestedActions(): array`, `getMarketInsights(): array`. |

#### 6.2.2 Archivos a MODIFICAR

| Archivo | Modificacion |
|---------|-------------|
| `ecosistema_jaraba_core/src/Service/BaseAgent.php` | Anadir `'serviciosconecta'` al `getVerticalContext()` con inyeccion del bridge. |
| `ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml` | Registrar `ecosistema_jaraba_core.serviciosconecta_copilot_bridge`. |

---

### FASE 3 — hook_preprocess_page + Body Classes Consolidadas {#fase-3}

**Prioridad:** P0 CRITICA | **Estimacion:** 8-10h | **Gaps:** G4, G19

#### 6.3.1 Archivos a MODIFICAR

| Archivo | Modificacion |
|---------|-------------|
| `jaraba_servicios_conecta/jaraba_servicios_conecta.module` | Implementar `hook_preprocess_page()` con variables zero-region: `clean_content`, `clean_messages`, `site_name`, `site_slogan`, `logo`, `logged_in`, `theme_settings`, `vertical_key = 'serviciosconecta'`, `footer_copyright`, `current_provider` (si autenticado), `provider_stats`. |
| `jaraba_servicios_conecta/jaraba_servicios_conecta.module` | Implementar `hook_theme_suggestions_page_alter()`: rutas marketplace/provider-detail → `page__servicios_marketplace`; rutas provider-portal → `page__servicios_dashboard`. |
| `ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme` | Verificar que `jaraba_servicios_conecta.` ya esta en el mapa `$module_body_classes` (confirmado en linea 1500). |

---

### FASE 4 — Page Template Zero-Region + Copilot FAB {#fase-4}

**Prioridad:** P0 CRITICA | **Estimacion:** 15-18h | **Gaps:** G5, G22, G23

#### 6.4.1 Archivos a CREAR

| Archivo | Descripcion |
|---------|------------|
| `ecosistema_jaraba_theme/templates/page--servicios-marketplace.html.twig` | Siguiendo patron `page--vertical-landing.html.twig`: `<!DOCTYPE html>`, `{% include '@.../partials/_header.html.twig' %}`, `{{ clean_content }}`, `{% include '@.../partials/_footer.html.twig' %}`, `{% include '@.../partials/_copilot-fab.html.twig' %}`. |
| `ecosistema_jaraba_theme/templates/page--servicios-dashboard.html.twig` | Template dashboard profesional zero-region con sidebar de navegacion contextual y `{{ clean_content }}`. |
| `jaraba_servicios_conecta/templates/servicios-provider-bookings.html.twig` | Listado de reservas del profesional con tabs (pendientes/confirmadas/completadas/canceladas). |
| `jaraba_servicios_conecta/templates/servicios-provider-profile.html.twig` | Formulario de edicion de perfil profesional. |
| `jaraba_servicios_conecta/templates/servicios-booking-form.html.twig` | Formulario de reserva publica (seleccion de servicio + fecha + hora + datos cliente). |
| `jaraba_servicios_conecta/templates/partials/booking-card.html.twig` | Tarjeta de reserva reutilizable (fecha, hora, servicio, estado, acciones). |
| `jaraba_servicios_conecta/templates/partials/review-card.html.twig` | Tarjeta de resena reutilizable (estrellas, texto, autor, fecha). |
| `jaraba_servicios_conecta/templates/partials/availability-calendar.html.twig` | Calendario de disponibilidad (grid semanal con slots coloreados). |
| `jaraba_servicios_conecta/templates/partials/stats-grid.html.twig` | Grid de KPIs del profesional (reservas, ingresos, rating, conversion). |

#### 6.4.2 Archivos a MODIFICAR

| Archivo | Modificacion |
|---------|-------------|
| `jaraba_servicios_conecta/jaraba_servicios_conecta.module` | Registrar nuevos templates en `hook_theme()`. |

---

### FASE 5 — SCSS Compliance + Design Tokens {#fase-5}

**Prioridad:** P1 ALTA | **Estimacion:** 12-15h | **Gaps:** G6, G7

#### 6.5.1 Archivos a MODIFICAR (SCSS Migration)

| Archivo | Modificacion |
|---------|-------------|
| `jaraba_servicios_conecta/scss/_variables.scss` | Reemplazar TODOS los colores Tailwind por `var(--ej-*, fallback)` del Jaraba palette. Mapeo: `$servicios-primary` → `var(--ej-color-corporate, #233D63)`, `$servicios-secondary` → `var(--ej-color-success, #10B981)`, `$servicios-accent` → `var(--ej-color-innovation, #00A9A5)`, `$servicios-danger` → `var(--ej-color-danger, #EF4444)`, `$servicios-warning` → `var(--ej-color-warning, #F59E0B)`. Anadir variables verticales: `--ej-servicios-primary`, `--ej-servicios-accent` en `:root {}`. |
| `jaraba_servicios_conecta/scss/_marketplace.scss` | Migrar `rgba()` a `color-mix(in srgb, ...)`. |
| `jaraba_servicios_conecta/scss/_provider-dashboard.scss` | Migrar `rgba()` a `color-mix(in srgb, ...)`. |
| `jaraba_servicios_conecta/scss/_provider-detail.scss` | Migrar `rgba()` a `color-mix(in srgb, ...)`. |
| `jaraba_servicios_conecta/scss/_components.scss` | Migrar `rgba()` a `color-mix(in srgb, ...)`. |

#### 6.5.2 Archivos a CREAR

| Archivo | Descripcion |
|---------|------------|
| `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.design_token_config.serviciosconecta.yml` | Config Entity: `id: serviciosconecta`, `label: ServiciosConecta`, `primary_color: '#233D63'`, `accent_color: '#00A9A5'`, `gradient_start: '#233D63'`, `gradient_end: '#00A9A5'`. |

#### 6.5.3 Compilacion SCSS

```bash
docker exec jarabasaas_appserver_1 bash -c "cd /app/web/modules/custom/jaraba_servicios_conecta && npx sass scss/main.scss css/jaraba-servicios-conecta.css --style=compressed"
```

---

### FASE 6 — Review Entity + Service {#fase-6}

**Prioridad:** P1 ALTA | **Estimacion:** 18-22h | **Gap:** G18

**Descripcion:** Crear la entidad ReviewServicios para resenas de clientes sobre profesionales/servicios. Inspirado en `ReviewAgro` de AgroConecta.

#### 6.6.1 Archivos a CREAR

| Archivo | Descripcion |
|---------|------------|
| `jaraba_servicios_conecta/src/Entity/ReviewServicios.php` | ContentEntityBase con: `provider_id` (ref ProviderProfile), `offering_id` (ref ServiceOffering, nullable), `booking_id` (ref Booking), `rating` (1-5), `title`, `comment`, `status` (pending/approved/rejected), `provider_response`, `response_date`. Con AdminHtmlRouteProvider, AccessControlHandler, ListBuilder. |
| `jaraba_servicios_conecta/src/Access/ReviewServiciosAccessControlHandler.php` | View: publico si approved. Create: authenticated + ha completado booking. Update: solo provider_response. Delete: solo admin. |
| `jaraba_servicios_conecta/src/ListBuilder/ReviewServiciosListBuilder.php` | Lista admin con columnas: Provider, Rating (estrellas), Comment (truncado), Status, Date. |
| `jaraba_servicios_conecta/src/Form/ReviewServiciosForm.php` | Formulario de resena (modal slide-panel). Rating con estrellas interactivas. |
| `jaraba_servicios_conecta/src/Service/ReviewService.php` | Metodos: `submitReview()`, `approveReview()`, `getProviderReviews()`, `recalculateAverageRating()`, `canUserReview()`. |

#### 6.6.2 Archivos a MODIFICAR

| Archivo | Modificacion |
|---------|-------------|
| `jaraba_servicios_conecta/jaraba_servicios_conecta.module` | Anadir hook para recalcular `average_rating` y `total_reviews` del ProviderProfile cuando se aprueba una resena. |
| `jaraba_servicios_conecta/jaraba_servicios_conecta.services.yml` | Registrar `jaraba_servicios_conecta.review`. |
| `jaraba_servicios_conecta/jaraba_servicios_conecta.links.task.yml` | Anadir tab "Resenas" en admin/content. |
| `jaraba_servicios_conecta/jaraba_servicios_conecta.permissions.yml` | Verificar permisos existentes (ya definidos: manage, view, submit, respond). |

---

### FASE 7 — Email Sequences MJML (6 secuencias) {#fase-7}

**Prioridad:** P1 ALTA | **Estimacion:** 20-25h | **Gap:** G8

#### 6.7.1 Archivos a CREAR

| Archivo | Descripcion |
|---------|------------|
| `ecosistema_jaraba_core/src/Service/ServiciosConectaEmailSequenceService.php` | 6 secuencias lifecycle. Siguiendo patron de `AgroConectaEmailSequenceService`. |

#### 6.7.2 Secuencias de Email

| Secuencia | Trigger | Contenido |
|-----------|---------|-----------|
| SEQ_SVC_001 | Registro como profesional | Bienvenida + 3 primeros pasos (completar perfil, publicar servicio, configurar disponibilidad) |
| SEQ_SVC_002 | Primer servicio publicado | "Comparte tu perfil profesional" + enlace directo |
| SEQ_SVC_003 | 7 dias inactividad | Re-engagement con metricas pendientes y sugerencias IA |
| SEQ_SVC_004 | 80% limite servicios (2/3 en free) | Upgrade a Starter con caso de exito |
| SEQ_SVC_005 | 80% limite reservas o intento feature gated | Upgrade a Profesional con ROI calculado |
| SEQ_SVC_006 | 30 dias suscrito | Resumen mensual + sugerencias IA + cross-sell a otros verticales |

---

### FASE 8 — CrossVerticalBridgeService (4 puentes) {#fase-8}

**Prioridad:** P2 MEDIA | **Estimacion:** 15-18h | **Gap:** G10

#### 6.8.1 Archivos a CREAR

| Archivo | Descripcion |
|---------|------------|
| `ecosistema_jaraba_core/src/Service/ServiciosConectaCrossVerticalBridgeService.php` | 4 puentes outgoing con condiciones de activacion y CTAs. |

#### 6.8.2 Puentes Outgoing

| Destino | Condicion | Mensaje | CTA |
|---------|-----------|---------|-----|
| Emprendimiento | >20 reservas totales AND rating > 4.5 | `{% trans %}Escala tu consulta: crea tu propia marca profesional con herramientas de emprendimiento.{% endtrans %}` | `/emprender` |
| Fiscal | Plan >= Starter AND >10 reservas/mes | `{% trans %}Automatiza tu facturacion profesional con VeriFactu y Facturae.{% endtrans %}` | `/fiscal/dashboard` |
| Formacion | >50 reservas totales | `{% trans %}Comparte tu conocimiento: crea cursos online para multiplicar tu alcance.{% endtrans %}` | `/courses` |
| Empleabilidad | Journey state = at_risk AND 30 dias inactivo | `{% trans %}Mientras reactivas tu consulta, explora oportunidades de empleo en tu sector.{% endtrans %}` | `/empleabilidad` |

---

### FASE 9 — JourneyProgressionService (8 reglas proactivas) {#fase-9}

**Prioridad:** P0 CRITICA | **Estimacion:** 20-25h | **Gap:** G11

#### 6.9.1 Archivos a CREAR

| Archivo | Descripcion |
|---------|------------|
| `ecosistema_jaraba_core/src/Service/ServiciosConectaJourneyProgressionService.php` | 8 reglas proactivas para avatar `profesional` + 2 para `cliente_servicios`. Cache via State API (`serviciosconecta_proactive_pending_{userId}`, TTL 3600s). |

#### 6.9.2 Reglas Proactivas (Avatar `profesional`)

| Rule ID | Estado | Condicion | Mensaje | Canal |
|---------|--------|-----------|---------|-------|
| `incomplete_profile` | discovery | Perfil sin foto, bio o especialidades | `{% trans %}Completa tu perfil profesional para aparecer en el marketplace.{% endtrans %}` | `fab_expand` |
| `no_services` | discovery | 0 servicios publicados, 3 dias desde registro | `{% trans %}Publica tu primer servicio y empieza a recibir reservas.{% endtrans %}` | `fab_dot` |
| `no_availability` | activation | Servicios publicados pero 0 slots de disponibilidad | `{% trans %}Configura tu horario de disponibilidad para recibir reservas.{% endtrans %}` | `fab_expand` |
| `first_booking_nudge` | activation | Slots configurados, 0 reservas en 7 dias | `{% trans %}Comparte tu perfil en redes sociales para recibir tus primeras reservas.{% endtrans %}` | `fab_badge` |
| `review_response` | engagement | Resenas sin responder >3 dias | `{% trans %}Tienes resenas sin responder. Responde para mejorar tu reputacion.{% endtrans %}` | `fab_dot` |
| `services_limit_approaching` | conversion | Plan free + 2/3 servicios (67% limite) | `{% trans %}Tu catalogo de servicios esta casi lleno. Pasa a Starter y ofrece hasta 10.{% endtrans %}` | `fab_expand` |
| `upgrade_professional` | conversion | Plan Starter + >30 reservas/mes (60% limite) | `{% trans %}Tu volumen de reservas crece. Pasa a Profesional para desbloquear IA, firma digital y buzon cifrado.{% endtrans %}` | `fab_expand` |
| `b2b_expansion` | retention | >50 reservas totales + rating >4.5 | `{% trans %}Tu consulta tiene nivel de excelencia. Explora oportunidades de expansion con emprendimiento.{% endtrans %}` | `fab_dot` |

#### 6.9.3 Reglas Proactivas (Avatar `cliente_servicios`)

| Rule ID | Condicion | Mensaje | Canal |
|---------|-----------|---------|-------|
| `booking_abandoned` | Reserva iniciada sin completar en 24h | `{% trans %}Tu reserva esta pendiente. Completa el proceso antes de que se agote la disponibilidad.{% endtrans %}` | `fab_dot` |
| `rebooking_nudge` | Ultima reserva hace >30 dias | `{% trans %}Descubre las novedades de tus profesionales favoritos.{% endtrans %}` | `fab_badge` |

---

### FASE 10 — HealthScoreService (5 dimensiones + 8 KPIs) {#fase-10}

**Prioridad:** P1 ALTA | **Estimacion:** 18-22h | **Gap:** G12

#### 6.10.1 Archivos a CREAR

| Archivo | Descripcion |
|---------|------------|
| `ecosistema_jaraba_core/src/Service/ServiciosConectaHealthScoreService.php` | Metodos: `calculateUserHealth(int $userId): array`, `calculateVerticalKpis(): array`. |

#### 6.10.2 Dimensiones de Salud (5, peso total = 1.0)

| Dimension | Peso | Calculo |
|-----------|------|---------|
| `profile_completeness` | 0.20 | Foto (20) + Bio (20) + Especialidades (20) + Credenciales (20) + Horario (20) |
| `booking_activity` | 0.30 | Reservas ultimo mes (40) + Tasa completadas vs canceladas (30) + Revenue trend (30) |
| `client_satisfaction` | 0.25 | Rating promedio (40) + Resenas respondidas (30) + Tiempo respuesta (30) |
| `copilot_usage` | 0.10 | Usos copilot 7 dias (50) + Acciones ejecutadas (50) |
| `marketplace_presence` | 0.15 | Servicios publicados (30) + SEO score (30) + Verificacion credenciales (20) + Disponibilidad activa (20) |

---

### FASE 11 — CopilotAgent Dedicado + CRM Integration {#fase-11}

**Prioridad:** P1 ALTA | **Estimacion:** 25-30h | **Gaps:** G9, G13

#### 6.11.1 Archivos a CREAR

| Archivo | Descripcion |
|---------|------------|
| `ecosistema_jaraba_core/src/Service/ServiciosConectaCopilotAgent.php` | Extiende BaseAgent con 6 modos: `schedule_optimizer` (sugiere horarios optimos), `quote_assistant` (genera presupuestos), `client_communicator` (redacta mensajes a clientes), `review_responder` (sugiere respuestas a resenas), `marketing_advisor` (ideas de marketing), `faq` (FAQ del vertical). |

#### 6.11.2 Archivos a MODIFICAR (CRM Hooks)

| Archivo | Modificacion |
|---------|-------------|
| `jaraba_servicios_conecta/jaraba_servicios_conecta.module` | Anadir `hook_entity_insert/update` para CRM pipeline: crear/actualizar contactos CRM cuando se registra profesional, se completa reserva, se deja resena. Inyectar `jaraba_crm.pipeline` (fail-open si modulo no existe). |

---

### FASE 12 — Avatar Navigation + Funnel Analytics + A/B {#fase-12}

**Prioridad:** P2 MEDIA | **Estimacion:** 15-18h | **Gaps:** G14, G15, G16

#### 6.12.1 Archivos a CREAR/MODIFICAR

| Archivo | Accion |
|---------|--------|
| `ecosistema_jaraba_core/src/Service/AvatarNavigationService.php` | MODIFICAR: Anadir entrada `'profesional'` con items de navegacion: Dashboard, Servicios, Reservas, Calendario, Perfil, Analytics. |
| `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.funnel_definition.serviciosconecta_profesional.yml` | CREAR: Funnel de 6 etapas: `visit_landing` → `register` → `complete_profile` → `publish_service` → `first_booking` → `recurring_client`. |
| `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.funnel_definition.serviciosconecta_cliente.yml` | CREAR: Funnel de 4 etapas: `visit_marketplace` → `view_provider` → `book_service` → `leave_review`. |
| `ecosistema_jaraba_core/src/Service/ServiciosConectaExperimentService.php` | CREAR: A/B testing para: `pricing_display` (mensual vs anual), `booking_flow` (1-step vs multi-step), `cta_copy` (variaciones de texto). |

---

### FASE 13 — Page Builder Premium + QA Integral {#fase-13}

**Prioridad:** P1 ALTA | **Estimacion:** 40-50h | **Gaps:** G20, G25

#### 6.13.1 Bloques PB a ELEVAR a Premium

Los 11 bloques existentes necesitan:

1. **Reemplazar TODOS los emojis** por `jaraba_icon()` con variante duotone
2. **Completar `fields_schema`** en cada YAML config (cta_secondary_text, features array, etc.)
3. **Crear 4 bloques premium nuevos** (inspirados en AgroConecta premium):

| Bloque Premium | Template | Contenido |
|---------------|----------|-----------|
| `serviciosconecta_booking_widget` | Previsualizacion embebible del booking engine | Calendario compacto + servicios + CTA reservar |
| `serviciosconecta_provider_spotlight` | Perfil destacado de profesional | Foto, credenciales verificadas, rating, servicios, CTA |
| `serviciosconecta_trust_badges` | Badges de confianza y credenciales | Iconos verificacion, colegiacion, seguro RC, firma digital |
| `serviciosconecta_case_studies` | Casos de exito con metricas | Antes/despues, testimonial, KPIs de impacto |

4. **Schema.org** en bloques premium: `ProfessionalService`, `AggregateRating`, `FAQPage`
5. **Patron PB-PREMIUM-001**: clase `jaraba-block jaraba-block--premium`, `data-effect="fade-up"`, staggered delays, `jaraba_icon()`, schema.org

#### 6.13.2 QA Integral

| Verificacion | Herramienta |
|-------------|-------------|
| PHP lint | `docker exec jarabasaas_appserver_1 bash -c "php -l ..."` todos los archivos PHP nuevos/modificados |
| SCSS compilation | `npx sass scss/main.scss css/... --style=compressed` sin errores |
| i18n audit | Grep todos los strings visibles verificando `$this->t()`, `|t`, `{% trans %}` |
| Color audit | Grep SCSS por colores hex no-paleta (#1E40AF, #059669, #7C3AED, etc.) |
| Emoji audit | Grep templates por `&#` o emojis Unicode — todos deben ser `jaraba_icon()` |
| Template audit | Verificar que todos los templates usan `{{ clean_content }}` y no `{{ page.content }}` |
| Permissions audit | Verificar todas las rutas tienen `_permission` o `_access: 'TRUE'` |
| Entity admin | Verificar tabs en /admin/content y links en /admin/structure |

---

## 7. Tabla de Correspondencia con Especificaciones Tecnicas

| Fase | Spec Tecnica | Codigo |
|------|-------------|--------|
| F0 | 82_Services_Core | Bug fix + settings |
| F0 | 01_Core_Entidades | FeatureGateService |
| F1 | 82_Services_Core | UpgradeTrigger |
| F2 | 93_Copilot_Servicios | CopilotBridge |
| F3-F4 | Directrices Theming | Zero-region |
| F5 | Arquitectura Theming Master | SCSS compliance |
| F6 | 97_Reviews_Ratings | Review entity |
| F7 | 98_Notificaciones_Multicanal | Email sequences |
| F8 | Cross-Vertical Patterns | Bridges |
| F9 | Journey Engine Doc 103 | JourneyProgression |
| F10 | HealthScore Pattern | HealthScore |
| F11 | 93_Copilot_Servicios + 91_AI_Triaje | CopilotAgent |
| F12 | Avatar Detection + Funnel | Navigation + Funnel |
| F13 | PB Premium Pattern | Page Builder |

---

## 8. Cumplimiento de Directrices del Proyecto

| Directriz | Cumplimiento en este Plan |
|-----------|--------------------------|
| **i18n: textos traducibles** | Todos los strings UI con `$this->t()` en PHP, `|t` en Twig, `Drupal.t()` en JS. Emails MJML con `{% trans %}`. |
| **SCSS: Federated Design Tokens** | Solo `var(--ej-*, fallback)` en SCSS satelite. Nunca `$ej-*` local. `color-mix(in srgb, ...)` en lugar de `rgba()`. |
| **Dart Sass moderno** | `@use 'sass:color'` (nunca `@import`). `color.adjust()` en lugar de `darken()/lighten()`. |
| **Frontend limpio sin regiones** | Templates zero-region con `{{ clean_content }}`. Sin `{{ page.content }}` ni bloques heredados. |
| **Body classes via hook_preprocess_html()** | Ya implementado. Se extiende con `hook_preprocess_page()` para variables zero-region. |
| **CRUD en modales** | Reviews se abren en modal slide-panel con `data-dialog-type="modal"` + `use-ajax`. |
| **Entidades con Field UI y Views** | ReviewServicios con `AdminHtmlRouteProvider`, `AccessControlHandler`, `ListBuilder`. Tabs en admin/content. |
| **No hardcodear configuracion** | `jaraba_servicios_conecta.settings.yml` + `ServiciosConectaSettingsForm` para toda config. |
| **Parciales Twig reutilizables** | 4 nuevos partials: booking-card, review-card, availability-calendar, stats-grid. |
| **Iconos SVG duotone** | Todos los emojis reemplazados por `jaraba_icon('category', 'name', {variant: 'duotone'})`. |
| **AI via @ai.provider** | CopilotAgent usa abstraccion BaseAgent → MultiAiProviderService. |
| **Variables configurables desde UI** | Design Tokens CSS custom properties inyectados via `hook_preprocess_html()` del tema. |
| **Ejecucion en Docker** | Todos los comandos con `docker exec jarabasaas_appserver_1 bash -c "..."`. |

---

## 9. Inventario de Entidades, Servicios y Endpoints

### 9.1 Entidades (6 total tras FASE 6)

| Entidad | Tabla | Estado |
|---------|-------|--------|
| ProviderProfile | provider_profile | EXISTE |
| ServiceOffering | service_offering | EXISTE |
| Booking | booking | EXISTE |
| AvailabilitySlot | availability_slot | EXISTE |
| ServicePackage | service_package | EXISTE |
| **ReviewServicios** | **review_servicios** | **NUEVO F6** |

### 9.2 Servicios (13 total)

| Service ID | Clase | Ubicacion | Estado |
|-----------|-------|-----------|--------|
| jaraba_servicios_conecta.provider | ProviderService | Modulo | EXISTE |
| jaraba_servicios_conecta.service_offering | ServiceOfferingService | Modulo | EXISTE |
| jaraba_servicios_conecta.availability | AvailabilityService | Modulo | EXISTE (FIX F0) |
| jaraba_servicios_conecta.review | ReviewService | Modulo | NUEVO F6 |
| ecosistema_jaraba_core.serviciosconecta_feature_gate | ServiciosConectaFeatureGateService | Core | NUEVO F0 |
| ecosistema_jaraba_core.serviciosconecta_upgrade_trigger | ServiciosConectaUpgradeTriggerService | Core | NUEVO F1 |
| ecosistema_jaraba_core.serviciosconecta_copilot_bridge | ServiciosConectaCopilotBridgeService | Core | NUEVO F2 |
| ecosistema_jaraba_core.serviciosconecta_email_sequence | ServiciosConectaEmailSequenceService | Core | NUEVO F7 |
| ecosistema_jaraba_core.serviciosconecta_cross_vertical_bridge | ServiciosConectaCrossVerticalBridgeService | Core | NUEVO F8 |
| ecosistema_jaraba_core.serviciosconecta_journey_progression | ServiciosConectaJourneyProgressionService | Core | NUEVO F9 |
| ecosistema_jaraba_core.serviciosconecta_health_score | ServiciosConectaHealthScoreService | Core | NUEVO F10 |
| ecosistema_jaraba_core.serviciosconecta_copilot_agent | ServiciosConectaCopilotAgent | Core | NUEVO F11 |
| ecosistema_jaraba_core.serviciosconecta_experiment | ServiciosConectaExperimentService | Core | NUEVO F12 |

---

## 10. Paleta de Colores y Design Tokens

### 10.1 Colores Verticales ServiciosConecta

| Variable CSS | SCSS Fallback | Hex | Uso |
|-------------|--------------|-----|-----|
| `--ej-servicios-primary` | `$servicios-primary` | var(--ej-color-corporate, #233D63) | Azul corporativo, confianza |
| `--ej-servicios-accent` | `$servicios-accent` | var(--ej-color-innovation, #00A9A5) | Turquesa, innovacion digital |
| `--ej-servicios-success` | `$servicios-success` | var(--ej-color-success, #10B981) | Verde, verificado, completado |
| `--ej-servicios-warning` | `$servicios-warning` | var(--ej-color-warning, #F59E0B) | Ambar, pendiente, atencion |
| `--ej-servicios-danger` | `$servicios-danger` | var(--ej-color-danger, #EF4444) | Rojo, cancelado, error |
| `--ej-servicios-impulse` | `$servicios-impulse` | var(--ej-color-impulse, #FF8C42) | Naranja, CTAs, accion |

### 10.2 Colores Prohibidos (eliminar de SCSS)

| Color actual | Archivo | Reemplazo |
|-------------|---------|-----------|
| `#1E40AF` | _variables.scss:11 | `var(--ej-color-corporate, #233D63)` |
| `#059669` | _variables.scss:14 | `var(--ej-color-success, #10B981)` |
| `#7C3AED` | _variables.scss:15 | `var(--ej-color-innovation, #00A9A5)` |
| `#DC2626` | _variables.scss:21 | `var(--ej-color-danger, #EF4444)` |
| `#06B6D4` | _variables.scss:24 | `var(--ej-color-innovation, #00A9A5)` |

---

## 11. Plantillas Page Builder Premium

### 11.1 Bloques Existentes (11) — Mejoras

Todos los bloques actuales (`is_premium: false`) se mantienen pero con:
- Emojis → `jaraba_icon()` duotone
- `fields_schema` completado
- Schema.org donde aplique

### 11.2 Bloques Premium Nuevos (4)

| Bloque | `is_premium` | Plans | Weight | Icono |
|--------|:---:|--------|--------|-------|
| `serviciosconecta_booking_widget` | true | professional, enterprise | 230 | `calendar` |
| `serviciosconecta_provider_spotlight` | true | professional, enterprise | 231 | `star` |
| `serviciosconecta_trust_badges` | true | starter, professional, enterprise | 232 | `check` |
| `serviciosconecta_case_studies` | true | professional, enterprise | 233 | `target` |

---

## 12. Recorridos de Usuario (User Journeys)

### 12.1 Visitante No Registrado → Registro

```
SEO/SEM/Redes → /servicios (landing publica)
  → Explora marketplace (filtros: especialidad, ciudad, online)
  → Ve perfil de profesional (/servicios/profesional/{slug})
  → Schema.org ProfessionalService (SEO + GEO)
  → CTA: "Reservar consulta" → Registro obligatorio
  → Lead Magnet: "Template Propuesta Profesional" (PDF)
  → Copilot FAB: "¿Necesitas ayuda? Te oriento al profesional adecuado"
  → Onboarding Wizard (seleccion avatar: profesional o cliente)
```

### 12.2 Profesional — Embudo de Ventas + Escalera de Valor

```
DISCOVERY (Free)
  → Completar perfil (foto, bio, especialidades, credenciales)
  → Publicar hasta 3 servicios
  → Configurar disponibilidad (slots semanales)
  → Recibir hasta 10 reservas/mes
  → [SEQ_SVC_001: Email bienvenida]
  → [Journey: incomplete_profile, no_services nudges]

ACTIVATION (Free → Starter 29 EUR/mes)
  → Primera reserva confirmada
  → Primera resena recibida
  → [SEQ_SVC_002: "Comparte tu perfil"]
  → [UpgradeTrigger: services_limit_80, bookings_limit_80]
  → Feature unlocked: Calendar Sync (Google/Outlook)
  → Hasta 10 servicios, 50 reservas/mes

ENGAGEMENT (Starter → Profesional 79 EUR/mes)
  → Responder resenas (nudge si >3 dias sin responder)
  → 30+ reservas/mes
  → [SEQ_SVC_004: Upgrade a Profesional]
  → Features unlocked: Buzon Confianza, Firma Digital, IA Triaje, Video, Analytics

CONVERSION (Profesional)
  → Buzon de Confianza para intercambio seguro de documentos
  → Firma digital PAdES de contratos
  → IA Triaje clasifica casos entrantes
  → Presupuestador automatico
  → Dashboard analytics completo
  → [SEQ_SVC_006: Resumen mensual + cross-sell]

RETENTION / EXPANSION
  → Cross-vertical bridges: Emprendimiento, Fiscal, Formacion
  → [HealthScore: 5 dimensiones monitorizadas]
  → [JourneyProgression: nudges proactivos]
  → Copilot 6 modos especializados
```

### 12.3 Cliente Servicios — Embudo

```
DISCOVERY → BOOKING → SATISFACTION → RETENTION
  → Buscar profesional (marketplace con facetas)
  → Ver perfil + resenas + credenciales
  → Seleccionar servicio + fecha/hora
  → Pagar (si prepaid) o confirmar
  → Recibir recordatorios (24h, 1h)
  → Asistir a cita (presencial/online/hibrido)
  → Dejar resena
  → Re-booking nudge a los 30 dias
```

### 12.4 Recorrido Cross-Vertical

```
ServiciosConecta (profesional exitoso)
  ↓ >20 reservas + rating >4.5
  → Bridge: Emprendimiento ("Escala tu consulta")
  ↓ Plan >= Starter + >10 reservas/mes
  → Bridge: Fiscal ("Automatiza facturacion")
  ↓ >50 reservas totales
  → Bridge: Formacion ("Crea cursos online")
  ↓ Journey at_risk + 30 dias inactivo
  → Bridge: Empleabilidad ("Explora oportunidades")
```

---

## 13. Dependencias Cruzadas y Cross-Vertical

| Modulo | Dependencia | Tipo |
|--------|-----------|------|
| `ecosistema_jaraba_core` | Core platform services | HARD |
| `jaraba_journey` | JourneyDefinition + JourneyEngineService | HARD (ya existe) |
| `jaraba_billing` | FeatureGateService, plans, Stripe Connect | HARD |
| `jaraba_crm` | Pipeline sync, contact management | SOFT (fail-open) |
| `jaraba_email` | MJML rendering, SendGrid dispatch | SOFT (fail-open) |
| `jaraba_rag` | Qdrant vectors para CopilotAgent | SOFT (futuro) |
| `jaraba_geo` | Schema.org LocalBusiness/ProfessionalService | SOFT (ya integrado) |
| `ecosistema_jaraba_theme` | Templates zero-region, CSS variables | HARD |
| `jaraba_page_builder` | 15 block templates (11 + 4 premium) | HARD |

---

## 14. Verificacion y QA

### 14.1 Verificacion por Fase

| Fase | Verificacion |
|------|-------------|
| F0 | Instalar modulo sin errores. Verificar `releaseSlot()` funciona al cancelar booking. Settings form accesible en /admin/config/jaraba/servicios-conecta. |
| F1 | Publicar 3 servicios (limite free), verificar que al intentar el 4o aparece upgrade modal. |
| F3-F4 | Navegar a /servicios y /mi-servicio — verificar que usan templates zero-region (sin sidebar admin, full-width). |
| F5 | Compilar SCSS sin errores. Grep `#1E40AF\|#059669\|#7C3AED\|#DC2626` debe dar 0 resultados. |
| F6 | Completar booking, dejar resena, verificar que average_rating se recalcula. |
| F13 | Verificar 15 bloques PB en GrapesJS canvas. Sin emojis. Con `jaraba_icon()`. |

### 14.2 URLs a Verificar

| URL | Tipo | Verificacion |
|-----|------|-------------|
| `/servicios` | Publica | Marketplace con filtros, paginacion, cards de profesionales |
| `/servicios/profesional/{slug}` | Publica | Perfil con Schema.org JSON-LD |
| `/servicios/reservar/{id}` | Publica (auth) | Formulario de reserva |
| `/mi-servicio` | Autenticada | Dashboard KPIs profesional |
| `/mi-servicio/servicios` | Autenticada | Listado servicios propios |
| `/mi-servicio/reservas` | Autenticada | Listado reservas |
| `/mi-servicio/calendario` | Autenticada | Calendario disponibilidad |
| `/mi-servicio/perfil` | Autenticada | Editar perfil |
| `/admin/config/jaraba/servicios-conecta` | Admin | Settings form |
| `/admin/content` tabs | Admin | Profesionales, Servicios, Reservas, Disponibilidad, Paquetes, Resenas |

---

## 15. Registro de Cambios

| Fecha | Version | Descripcion |
|-------|---------|-------------|
| 2026-02-17 | 1.0.0 | Creacion del Plan de Elevacion ServiciosConecta Clase Mundial v1 |
