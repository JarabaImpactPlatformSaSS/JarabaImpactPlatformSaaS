# Bloque C: Journey Engine - Documento de Implementación
## Navegación Inteligente por Avatar (19 Roles)

**Fecha de creación:** 2026-01-23  
**Versión:** 2.0.0  
**Estado:** ✅ **100% COMPLETADO** (2026-01-24)

---

## 📑 Tabla de Contenidos (TOC)

1. [Matriz de Especificaciones](#1-matriz-de-especificaciones)
2. [Checklist Multidisciplinar](#2-checklist-multidisciplinar)
3. [Pasos de Implementación](#3-pasos-de-implementación)
4. [Archivos Creados](#4-archivos-creados)
5. [Registro de Cambios](#5-registro-de-cambios)

---

## 1. Matriz de Especificaciones

### 1.1 Documento Principal

| Doc | Archivo |
|-----|---------|
| 103 | [20260117f-103_UX_Journey_Specifications_Avatar_v1_Claude.md](../tecnicos/20260117f-103_UX_Journey_Specifications_Avatar_v1_Claude.md) |

### 1.2 Avatares por Vertical

| Vertical | Avatares | Estado |
|----------|----------|--------|
| AgroConecta | Productor, B2B, Consumidor | ✅ |
| ComercioConecta | Comerciante, Comprador | ✅ |
| ServiciosConecta | Profesional, Cliente | ✅ |
| Empleabilidad | JobSeeker, Employer, Orientador | ✅ |
| Emprendimiento | Emprendedor, Mentor, Gestor | ✅ |
| Andalucía +ei | Beneficiario, Técnico, Admin | ✅ |
| Certificación | Estudiante, Formador, Admin LMS | ✅ |

---

## 2. Checklist Multidisciplinar

### UX Senior
- [x] ¿7 estados de journey implementados?
- [x] ¿Time to Value < 5 min?
- [x] ¿Progressive Disclosure aplicado?

### IA Senior
- [x] ¿Triggers de intervención configurados? (11 tipos)
- [x] ¿Cross-sell contextual no intrusivo? (NO_INTRUSION_RULES)

### Dev Senior
- [x] ¿SCSS con variables inyectables?
- [x] ¿i18n con $this->t()?
- [x] ¿API REST documentada? (6 endpoints)

---

## 3. Pasos de Implementación

### Sprint C1-C2: Core Engine ✅
- [x] Módulo `jaraba_journey`
- [x] Entity `JourneyState` (7 estados, 19 avatares)
- [x] `JourneyEngineService` (340 líneas)
- [x] `JourneyContextService` (145 líneas)
- [x] `JourneyTriggerService` (260 líneas)
- [x] `JourneyApiController` (190 líneas, 6 endpoints)

### Sprint C3-C4: AgroConecta + Empleabilidad + Emprendimiento ✅
- [x] `AgroConectaJourneyDefinition` (290 líneas)
- [x] `EmpleabilidadJourneyDefinition` (210 líneas)
- [x] `EmprendimientoJourneyDefinition` (240 líneas + Copilot v3)

### Sprint C5-C6: Comercio + Servicios ✅
- [x] `ComercioConectaJourneyDefinition` (175 líneas)
- [x] `ServiciosConectaJourneyDefinition` (175 líneas)

### Sprint C7-C10: +ei + Certificación ✅
- [x] `AndaluciaEiJourneyDefinition` (170 líneas)
- [x] `CertificacionJourneyDefinition` (180 líneas)

### Sprint C11-C12: Dashboard + Testing ✅
- [x] `JourneyDashboardController` (380 líneas)
- [x] `JourneySettingsForm` (130 líneas)
- [x] `_journey-dashboard.scss` (230 líneas, variables inyectables)

---

## 4. Archivos Creados

| Ruta | Líneas |
|------|--------|
| `jaraba_journey/jaraba_journey.info.yml` | 9 |
| `jaraba_journey/jaraba_journey.module` | 20 |
| `jaraba_journey/jaraba_journey.services.yml` | 35 |
| `jaraba_journey/jaraba_journey.routing.yml` | 85 |
| `jaraba_journey/jaraba_journey.permissions.yml` | 15 |
| `jaraba_journey/jaraba_journey.links.menu.yml` | 7 |
| `jaraba_journey/src/Entity/JourneyState.php` | 280 |
| `jaraba_journey/src/Entity/JourneyStateInterface.php` | 50 |
| `jaraba_journey/src/Service/JourneyEngineService.php` | 340 |
| `jaraba_journey/src/Service/JourneyContextService.php` | 145 |
| `jaraba_journey/src/Service/JourneyTriggerService.php` | 260 |
| `jaraba_journey/src/Service/JourneyDefinitionLoader.php` | 220 |
| `jaraba_journey/src/Controller/JourneyApiController.php` | 190 |
| `jaraba_journey/src/Controller/JourneyDashboardController.php` | 380 |
| `jaraba_journey/src/Form/JourneySettingsForm.php` | 130 |
| `jaraba_journey/src/JourneyDefinition/*` (7 archivos) | ~1,460 |
| `ecosistema_jaraba_core/scss/_journey-dashboard.scss` | 230 |
| **TOTAL** | **~3,856** |

---

## 5. Registro de Cambios

| Fecha | Versión | Descripción |
|-------|---------|-------------|
| 2026-01-23 | 1.0.0 | Creación inicial |
| 2026-01-23 | 1.1.0 | Expandida sección 4 - Directrices Obligatorias |
| 2026-01-24 | 2.0.0 | **100% COMPLETADO** - Core Engine, 7 verticales, 19 avatares, Dashboard Admin |
