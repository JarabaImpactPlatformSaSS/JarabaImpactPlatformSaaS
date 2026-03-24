# Plan de Implementación: Sistema de Roles del Programa Andalucía +ei — Clase Mundial

> **Versión:** 1.0.0
> **Fecha:** 2026-03-24
> **Autor:** Claude Opus 4.6 (1M context)
> **Estado:** Propuesta — pendiente de aprobación
> **Prioridad:** P0 (roles inexistentes) + P1 (coherencia arquitectónica)
> **Score actual:** 2.5/15 (auditoría `2026-03-24_Auditoria_Sistema_Roles_Andalucia_Ei_v1.md`)
> **Score objetivo:** 15/15 (10/10 clase mundial)
> **Módulos afectados:** `jaraba_andalucia_ei`, `jaraba_mentoring`, `ecosistema_jaraba_core`, `ecosistema_jaraba_theme`
> **Directriz raíz:** TENANT-001, PREMIUM-FORMS-PATTERN-001, SETUP-WIZARD-DAILY-001, ZERO-REGION-001, SLIDE-PANEL-RENDER-001, IMPLEMENTATION-CHECKLIST-001, ICON-CONVENTION-001, CSS-VAR-ALL-COLORS-001, SCSS-COMPILE-VERIFY-001

---

## Índice de Navegación (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Diagnóstico del Problema](#2-diagnóstico-del-problema)
   - 2.1 [Caso de uso fallido: un coordinador intenta asignar un formador](#21-caso-de-uso-fallido-un-coordinador-intenta-asignar-un-formador)
   - 2.2 [Análisis de causa raíz](#22-análisis-de-causa-raíz)
   - 2.3 [Flujo actual vs flujo deseado](#23-flujo-actual-vs-flujo-deseado)
3. [Arquitectura de la Solución](#3-arquitectura-de-la-solución)
   - 3.1 [Principio: Roles Drupal como SSOT de asignación](#31-principio-roles-drupal-como-ssot-de-asignación)
   - 3.2 [Principio: RolProgramaService como SSOT de detección](#32-principio-rolprogramaservice-como-ssot-de-detección)
   - 3.3 [Principio: Asignación desde dashboard del coordinador (slide-panel)](#33-principio-asignación-desde-dashboard-del-coordinador-slide-panel)
   - 3.4 [Principio: Auditoría de cambios de rol](#34-principio-auditoría-de-cambios-de-rol)
   - 3.5 [Principio: Formador como ciudadano de primera clase](#35-principio-formador-como-ciudadano-de-primera-clase)
   - 3.6 [Diagrama de flujo de datos del sistema de roles](#36-diagrama-de-flujo-de-datos-del-sistema-de-roles)
4. [Especificaciones Técnicas Detalladas](#4-especificaciones-técnicas-detalladas)
   - 4.1 [SPEC-ROL-001: Roles Drupal con permisos pre-asignados](#41-spec-rol-001-roles-drupal-con-permisos-pre-asignados)
   - 4.2 [SPEC-ROL-002: RolProgramaService (detección unificada)](#42-spec-rol-002-rolprogramaservice-detección-unificada)
   - 4.3 [SPEC-ROL-003: AsignacionRolController (slide-panel desde dashboard)](#43-spec-rol-003-asignacionrolcontroller-slide-panel-desde-dashboard)
   - 4.4 [SPEC-ROL-004: AsignacionRolForm (PremiumEntityFormBase)](#44-spec-rol-004-asignacionrolform-premiumentityformbase)
   - 4.5 [SPEC-ROL-005: RolProgramaLog entity (auditoría)](#45-spec-rol-005-rolprogramalog-entity-auditoría)
   - 4.6 [SPEC-ROL-006: Dashboard del Formador](#46-spec-rol-006-dashboard-del-formador)
   - 4.7 [SPEC-ROL-007: Setup Wizard del Formador (3 steps)](#47-spec-rol-007-setup-wizard-del-formador-3-steps)
   - 4.8 [SPEC-ROL-008: Daily Actions del Formador (3 actions)](#48-spec-rol-008-daily-actions-del-formador-3-actions)
   - 4.9 [SPEC-ROL-009: Notificaciones de asignación de rol](#49-spec-rol-009-notificaciones-de-asignación-de-rol)
   - 4.10 [SPEC-ROL-010: Perfil profesional del staff (StaffProfileEi entity)](#410-spec-rol-010-perfil-profesional-del-staff-staffprofileei-entity)
5. [Pipeline E2E por Componente](#5-pipeline-e2e-por-componente)
6. [Tabla de Correspondencia con Directrices](#6-tabla-de-correspondencia-con-directrices)
7. [Tabla de Correspondencia con Especificaciones Técnicas](#7-tabla-de-correspondencia-con-especificaciones-técnicas)
8. [Plan de Fases](#8-plan-de-fases)
   - 8.1 [Fase 0 — Fundamentos: Roles Drupal + auto-provisioning](#81-fase-0--fundamentos-roles-drupal--auto-provisioning)
   - 8.2 [Fase 1 — RolProgramaService: detección unificada](#82-fase-1--rolprogramaservice-detección-unificada)
   - 8.3 [Fase 2 — Dashboard del Formador + Wizard + Daily Actions](#83-fase-2--dashboard-del-formador--wizard--daily-actions)
   - 8.4 [Fase 3 — Asignación de roles desde dashboard coordinador](#84-fase-3--asignación-de-roles-desde-dashboard-coordinador)
   - 8.5 [Fase 4 — Auditoría de roles + StaffProfileEi](#85-fase-4--auditoría-de-roles--staffprofileei)
   - 8.6 [Fase 5 — Salvaguardas, validadores y tests](#86-fase-5--salvaguardas-validadores-y-tests)
9. [Verificación RUNTIME-VERIFY-001](#9-verificación-runtime-verify-001)
10. [Salvaguardas y Validadores Propuestos](#10-salvaguardas-y-validadores-propuestos)
11. [Riesgos y Mitigaciones](#11-riesgos-y-mitigaciones)
12. [Criterios de Aceptación 10/10 Clase Mundial](#12-criterios-de-aceptación-1010-clase-mundial)
13. [Glosario de Siglas](#13-glosario-de-siglas)

---

## 1. Resumen Ejecutivo

El sistema de roles del programa Andalucía +ei tiene una **arquitectura fragmentada** donde 2 de los 4 roles del programa (formador, orientador) no funcionan correctamente: los roles Drupal referenciados en el código no existen en config, el formador carece completamente de dashboard/wizard/acciones, y no hay mecanismo para que un coordinador asigne roles desde el frontend del SaaS.

### Objetivo

Elevar el sistema de roles de **2.5/15** a **15/15** (10/10 clase mundial) mediante:

1. **3 roles Drupal** con permisos pre-asignados en `config/install/` (auto-provisioning)
2. **RolProgramaService** como SSOT de detección unificada (reemplaza el híbrido actual)
3. **Dashboard completo del formador** con Setup Wizard (3 steps) y Daily Actions (3 actions)
4. **Asignación de roles desde el dashboard** del coordinador via slide-panel (sin admin Drupal)
5. **Auditoría de cambios de rol** con entity `RolProgramaLog` para cumplimiento FSE+
6. **Perfil profesional del staff** (`StaffProfileEi` entity) para trazabilidad normativa
7. **Validador de integridad** `validate-andalucia-ei-roles.php` (8 checks)

### Impacto esperado

| Métrica | Antes | Después | Mejora |
|---------|-------|---------|--------|
| Roles con flujo completo | 1/4 | 4/4 | +300% |
| Score clase mundial | 2.5/15 | 15/15 | +500% |
| Roles Drupal en config | 0/3 | 3/3 | De 0 a completo |
| Dashboards funcionales | 2/4 | 4/4 | +100% |
| Setup Wizard steps formador | 0 | 3 | De 0 a completo |
| Daily Actions formador | 0 | 3 | De 0 a completo |
| Asignación desde frontend | No | Sí | Nuevo |
| Auditoría de roles | No | Sí | Nuevo (FSE+) |
| Validadores | 0 | 1 (8 checks) | Nuevo |

---

## 2. Diagnóstico del Problema

### 2.1 Caso de uso fallido: un coordinador intenta asignar un formador

**Escenario real:** María, coordinadora del programa PIIL en Sevilla, contrata a un nuevo formador (Juan) para impartir el módulo de Competencias Digitales. María necesita darle acceso al sistema para que pueda ver las sesiones, pasar lista y subir materiales.

**Flujo actual (ROTO):**

```
1. María → /andalucia-ei/coordinador (dashboard)
2. María busca "Asignar formador" → NO existe opción
3. María → /admin/people (necesita permisos de super-admin)
4. María NO tiene acceso a admin Drupal (tenant sin acceso a tema admin)
5. María → contacta al administrador del SaaS
6. Admin → /admin/people → busca a Juan → intenta asignar rol "formador_ei"
7. Rol "formador_ei" NO EXISTE en el sistema
8. Admin → /admin/people/roles → crea rol manualmente
9. Admin → /admin/people/permissions → asigna permisos uno a uno
10. Juan accede → NO tiene dashboard → no sabe qué hacer
11. Juan → abandona
```

**Resultado:** 11 pasos, 3 personas involucradas, 2 bloqueos técnicos, 0 productividad para Juan.

### 2.2 Análisis de causa raíz

```
CAUSA RAÍZ 1: Roles Drupal nunca fueron creados
├── config/install/ no contiene user.role.*.yml
├── hook_install() no crea roles programáticamente
└── EFECTO: getRolProgramaUsuario() nunca devuelve 'formador' ni 'orientador' (por vía de rol)

CAUSA RAÍZ 2: Diseño centrado en el participante
├── El participante tiene flujo entity-driven completo
├── Los roles profesionales se asumieron como "configuración manual"
└── EFECTO: Brecha entre la sofisticación del flujo de participante y los flujos profesionales

CAUSA RAÍZ 3: Orientador acoplado a módulo externo
├── Detección via mentor_profile (jaraba_mentoring) — módulo de emprendimiento
├── Concepto de "orientador PIIL" ≠ "mentor de emprendimiento"
└── EFECTO: Acoplamiento semántico incorrecto + detección dual incoherente

CAUSA RAÍZ 4: Frontend limpio incompleto
├── Dashboards de Coord/Orient son frontend limpio (ZERO-REGION)
├── Pero la gestión de roles vive en admin Drupal (/admin/people)
└── EFECTO: El coordinador no puede operar autónomamente
```

### 2.3 Flujo actual vs flujo deseado

**Flujo ACTUAL (asignar orientador):**

```
Coordinador → no puede → Admin SaaS → /admin/people → buscar usuario
→ editar → asignar rol (que no existe) → BLOQUEADO
```

**Flujo DESEADO (clase mundial):**

```
Coordinador → /andalucia-ei/coordinador → sección "Equipo del programa"
→ click "Asignar profesional" → slide-panel abre
→ buscar usuario por nombre/email → seleccionar rol (Orientador/Formador/Coordinador)
→ confirmar → rol asignado automáticamente + email al profesional
→ profesional accede → Setup Wizard aparece → onboarding completo
```

---

## 3. Arquitectura de la Solución

### 3.1 Principio: Roles Drupal como SSOT de asignación

Los roles Drupal son el mecanismo canónico de Drupal para asignar permisos a usuarios. En lugar de reinventar un sistema de roles paralelo, usamos los roles Drupal como la **fuente única de verdad (SSOT)** para la asignación.

**3 roles a crear en `config/install/`:**

| Rol machine name | Label | Permisos pre-asignados |
|-----------------|-------|----------------------|
| `coordinador_ei` | Coordinador Andalucía +ei | `administer andalucia ei`, `access andalucia ei coordinador dashboard`, `manage andalucia ei solicitudes`, `manage andalucia ei fases`, `export sto data`, `view andalucia ei justificacion economica` |
| `orientador_ei` | Orientador Andalucía +ei | `access andalucia ei orientador dashboard`, `view programa participante ei`, `register andalucia ei actuacion`, `sign andalucia ei hoja servicio`, `create sesion programada ei`, `edit sesion programada ei` |
| `formador_ei` | Formador Andalucía +ei | `access andalucia ei formador`, `view sesion programada ei`, `mark attendance sesion ei`, `view material didactico ei`, `create material didactico ei` |

**Ventajas:**
- Compatible con el sistema de permisos de Drupal nativo
- Aprovecha la infraestructura existente de `user.role.*.yml` + `user.role.*.permissions.yml`
- Los roles se exportan/importan con `drush cex/cim`
- Funciona con Group Module (un tenant-member puede tener rol de programa adicional)

### 3.2 Principio: RolProgramaService como SSOT de detección

El servicio actual `AccesoProgramaService` tiene lógica de detección dispersa e incoherente. Se refactorizará para:

1. **Priorizar el rol Drupal** como mecanismo principal de detección
2. **Mantener la detección entity-driven** para participante/alumni (ya funciona correctamente)
3. **Eliminar fallbacks ambiguos** como `hasPermission('view programa participante ei')` para orientador
4. **Incorporar la señal de `mentor_profile`** como evidencia secundaria (no primaria) para orientador

**Cascada de detección unificada:**

```
getRolProgramaUsuario($account):
  1. Si tiene rol 'coordinador_ei'              → return 'coordinador'
  2. Si tiene rol 'orientador_ei'               → return 'orientador'
  3. Si tiene rol 'formador_ei'                 → return 'formador'
  4. Si tiene permiso 'administer andalucia ei' → return 'coordinador'  // backward compat
  5. Si tiene programa_participante_ei activo:
     a. fase_actual == 'alumni'                 → return 'alumni'
     b. fase_actual != 'baja'                   → return 'participante'
  6. return 'none'
```

**Nota sobre backward compatibility:** El paso 4 mantiene compatibilidad con coordinadores que fueron configurados manualmente antes de que exista el rol `coordinador_ei`. Una vez migrados, este fallback puede eliminarse.

### 3.3 Principio: Asignación desde dashboard del coordinador (slide-panel)

El coordinador debe poder gestionar el equipo del programa sin salir de su dashboard. Siguiendo el patrón SLIDE-PANEL-RENDER-001:

- **Sección "Equipo del programa"** en el dashboard del coordinador
- **Lista de profesionales** asignados (nombre, rol, fecha de asignación, estado)
- **Botón "Asignar profesional"** → abre slide-panel con formulario
- **Formulario:** buscar usuario por nombre/email + seleccionar rol + notas
- **Al guardar:** asigna rol Drupal + crea log de auditoría + envía notificación

**Patrón técnico:**

```php
// En AsignacionRolController:
if ($this->isSlidePanelRequest($request)) {
    $form['#action'] = $request->getRequestUri();
    $html = (string) $this->renderer->renderPlain($form);
    return new Response($html);
}
```

### 3.4 Principio: Auditoría de cambios de rol

Para cumplimiento normativo FSE+ y trazabilidad PIIL, cada cambio de rol se registra en una entity `RolProgramaLog`:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `user_id` | entity_reference(user) | Usuario afectado |
| `assigned_by` | entity_reference(user) | Quién realizó la asignación |
| `rol_programa` | list_string | Rol asignado/revocado |
| `accion` | list_string | `asignar` / `revocar` / `transferir` |
| `motivo` | text | Motivo de la acción (texto libre) |
| `tenant_id` | entity_reference(tenant) | Tenant (TENANT-001) |
| `created` | created | Timestamp automático |

**Uso en informes FSE+:** El coordinador puede exportar el historial de asignaciones como parte de la justificación documental del programa.

### 3.5 Principio: Formador como ciudadano de primera clase

El formador pasa de ser un "rol fantasma" a un ciudadano de primera clase con:

1. **Dashboard dedicado** (`/andalucia-ei/formador`) con:
   - Sesiones asignadas (hoy, esta semana, próximas)
   - Control de asistencia de cada sesión
   - Materiales didácticos (subir, ver, organizar)
   - Calendario del programa (vista semanal)
   - KPIs: horas impartidas, asistencia media, sesiones completadas

2. **Setup Wizard** (3 steps):
   - `FormadorPerfilStep` (peso 10): Completar perfil profesional
   - `FormadorSesionesStep` (peso 20): Revisar sesiones asignadas
   - `FormadorMaterialStep` (peso 30): Subir primer material didáctico

3. **Daily Actions** (3 actions):
   - `FormadorSesionesHoyAction`: Sesiones que imparte hoy
   - `FormadorAsistenciaAction`: Pasar asistencia pendiente
   - `FormadorMaterialesAction`: Materiales por revisar/subir

### 3.6 Diagrama de flujo de datos del sistema de roles

```
                    ┌──────────────────────────────────────┐
                    │    COORDINADOR DASHBOARD              │
                    │    /andalucia-ei/coordinador          │
                    │                                        │
                    │  ┌──────────────────────────────┐     │
                    │  │ Sección "Equipo del Programa" │     │
                    │  │                                │     │
                    │  │  👤 Ana López — Orientadora    │     │
                    │  │  👤 Juan Pérez — Formador      │     │
                    │  │  👤 María Ruiz — Coordinadora  │     │
                    │  │                                │     │
                    │  │  [+ Asignar profesional]       │───────┐
                    │  └──────────────────────────────┘     │   │
                    └──────────────────────────────────────┘   │
                                                                │
                    ┌──────────────────────────────────────┐   │
                    │    SLIDE-PANEL                         │◀──┘
                    │                                        │
                    │  Buscar usuario: [____________]        │
                    │  Rol: (○) Coordinador                  │
                    │       (●) Orientador                   │
                    │       (○) Formador                     │
                    │  Notas: [________________________]    │
                    │                                        │
                    │  [Cancelar]  [Asignar profesional]    │
                    └───────────────────┬──────────────────┘
                                        │
                                        ▼
                    ┌──────────────────────────────────────┐
                    │    AsignacionRolController             │
                    │                                        │
                    │  1. Asigna rol Drupal al usuario       │
                    │  2. Crea RolProgramaLog (auditoría)    │
                    │  3. Crea/activa StaffProfileEi         │
                    │  4. Envía notificación (email+in-app)  │
                    └───────────────────┬──────────────────┘
                                        │
                    ┌───────────────────┼──────────────────┐
                    ▼                   ▼                   ▼
            ┌─────────────┐   ┌──────────────┐   ┌──────────────┐
            │ ORIENTADOR   │   │ FORMADOR      │   │ COORDINADOR  │
            │ Dashboard    │   │ Dashboard     │   │ (ya asignado)│
            │ /orientador  │   │ /formador     │   │              │
            │              │   │               │   │              │
            │ Wizard: 3    │   │ Wizard: 3     │   │ Wizard: 4    │
            │ Actions: 4   │   │ Actions: 3    │   │ Actions: 5   │
            └─────────────┘   └──────────────┘   └──────────────┘
```

---

## 4. Especificaciones Técnicas Detalladas

### 4.1 SPEC-ROL-001: Roles Drupal con permisos pre-asignados

**Objetivo:** Crear los 3 roles del programa como config entities que se instalan automáticamente con el módulo.

**Ficheros a crear:**

#### `config/install/user.role.coordinador_ei.yml`

```yaml
langcode: es
status: true
dependencies: {}
id: coordinador_ei
label: 'Coordinador Andalucía +ei'
weight: 5
is_admin: false
permissions:
  - 'administer andalucia ei'
  - 'access andalucia ei coordinador dashboard'
  - 'manage andalucia ei solicitudes'
  - 'manage andalucia ei fases'
  - 'view programa participante ei'
  - 'create programa participante ei'
  - 'edit programa participante ei'
  - 'view solicitud ei'
  - 'edit solicitud ei'
  - 'view expediente documento'
  - 'view actuacion sto'
  - 'view indicador fse plus'
  - 'view insercion laboral'
  - 'view prospeccion empresarial'
  - 'create prospeccion empresarial'
  - 'edit prospeccion empresarial'
  - 'export sto data'
  - 'view andalucia ei justificacion economica'
  - 'use digital signature'
  - 'view accion formativa ei'
  - 'create accion formativa ei'
  - 'edit accion formativa ei'
  - 'view sesion programada ei'
  - 'create sesion programada ei'
  - 'edit sesion programada ei'
  - 'view plan formativo ei'
  - 'create plan formativo ei'
  - 'edit plan formativo ei'
  - 'assign andalucia ei roles'
```

#### `config/install/user.role.orientador_ei.yml`

```yaml
langcode: es
status: true
dependencies: {}
id: orientador_ei
label: 'Orientador Andalucía +ei'
weight: 6
is_admin: false
permissions:
  - 'access andalucia ei orientador dashboard'
  - 'view programa participante ei'
  - 'edit programa participante ei'
  - 'register andalucia ei actuacion'
  - 'sign andalucia ei hoja servicio'
  - 'view expediente documento'
  - 'create expediente documento'
  - 'view actuacion sto'
  - 'create actuacion sto'
  - 'view sesion programada ei'
  - 'create sesion programada ei'
  - 'edit sesion programada ei'
  - 'use digital signature'
  - 'view insercion laboral'
  - 'create insercion laboral'
  - 'view plan emprendimiento ei'
```

#### `config/install/user.role.formador_ei.yml`

```yaml
langcode: es
status: true
dependencies: {}
id: formador_ei
label: 'Formador Andalucía +ei'
weight: 7
is_admin: false
permissions:
  - 'access andalucia ei formador'
  - 'access andalucia ei formador dashboard'
  - 'view sesion programada ei'
  - 'mark attendance sesion ei'
  - 'view material didactico ei'
  - 'create material didactico ei'
  - 'edit material didactico ei'
  - 'view accion formativa ei'
  - 'view inscripcion sesion ei'
  - 'view plan formativo ei'
```

**Nuevo permiso a añadir en `jaraba_andalucia_ei.permissions.yml`:**

```yaml
access andalucia ei formador dashboard:
  title: 'Acceder al panel del formador'
  description: 'Permite acceder al dashboard de formador del programa.'

assign andalucia ei roles:
  title: 'Asignar roles del programa'
  description: 'Permite asignar y revocar roles de coordinador, orientador y formador a usuarios del tenant.'
  restrict access: true
```

**hook_update_N para instalaciones existentes:**

Se necesita `hook_update_N()` que:
1. Cree los 3 roles si no existen (idempotente)
2. Asigne los permisos listados a cada rol
3. Log: "Roles del programa Andalucía +ei creados/actualizados"

### 4.2 SPEC-ROL-002: RolProgramaService (detección unificada)

**Objetivo:** Reemplazar la lógica dispersa de `AccesoProgramaService` con un servicio de detección unificado y coherente.

**Fichero:** `jaraba_andalucia_ei/src/Service/RolProgramaService.php`

**Interfaz:**

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Session\AccountInterface;

/**
 * Servicio centralizado para detección y gestión de roles del programa.
 *
 * SSOT (Single Source of Truth) de roles del programa Andalucía +ei.
 * Reemplaza la lógica dispersa entre AccesoProgramaService y
 * AndaluciaEiUserProfileSection.
 *
 * Cascada de detección:
 * 1. Rol Drupal (coordinador_ei > orientador_ei > formador_ei)
 * 2. Permiso legacy (administer andalucia ei → coordinador)
 * 3. Entity participante (programa_participante_ei → participante/alumni)
 * 4. Ninguno → 'none'
 *
 * TENANT-001: Todas las queries filtran por tenant_id.
 */
interface RolProgramaServiceInterface {

  /**
   * Constantes de roles del programa.
   */
  public const ROL_COORDINADOR = 'coordinador';
  public const ROL_ORIENTADOR = 'orientador';
  public const ROL_FORMADOR = 'formador';
  public const ROL_PARTICIPANTE = 'participante';
  public const ROL_ALUMNI = 'alumni';
  public const ROL_NONE = 'none';

  /**
   * Roles profesionales (staff) del programa.
   */
  public const ROLES_STAFF = [
    self::ROL_COORDINADOR,
    self::ROL_ORIENTADOR,
    self::ROL_FORMADOR,
  ];

  /**
   * Mapping rol programa → rol Drupal.
   */
  public const ROL_DRUPAL_MAP = [
    self::ROL_COORDINADOR => 'coordinador_ei',
    self::ROL_ORIENTADOR => 'orientador_ei',
    self::ROL_FORMADOR => 'formador_ei',
  ];

  /**
   * Determina el rol principal del usuario en el programa.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   La cuenta de usuario a evaluar.
   *
   * @return string
   *   Uno de las constantes ROL_*.
   */
  public function getRolProgramaUsuario(AccountInterface $account): string;

  /**
   * Verifica si el usuario tiene un rol específico en el programa.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   La cuenta de usuario.
   * @param string $rol
   *   Constante ROL_* a verificar.
   *
   * @return bool
   *   TRUE si el usuario tiene el rol indicado.
   */
  public function tieneRol(AccountInterface $account, string $rol): bool;

  /**
   * Verifica si el usuario es staff del programa (coordinador, orientador o formador).
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   La cuenta de usuario.
   *
   * @return bool
   *   TRUE si tiene cualquier rol profesional.
   */
  public function esStaff(AccountInterface $account): bool;

  /**
   * Asigna un rol de programa a un usuario.
   *
   * @param int $uid
   *   ID del usuario.
   * @param string $rol
   *   Constante ROL_* a asignar (solo ROLES_STAFF).
   * @param string $motivo
   *   Motivo de la asignación (para auditoría).
   *
   * @return bool
   *   TRUE si se asignó correctamente.
   *
   * @throws \InvalidArgumentException
   *   Si el rol no es un rol de staff válido.
   */
  public function asignarRol(int $uid, string $rol, string $motivo = ''): bool;

  /**
   * Revoca un rol de programa a un usuario.
   *
   * @param int $uid
   *   ID del usuario.
   * @param string $rol
   *   Constante ROL_* a revocar (solo ROLES_STAFF).
   * @param string $motivo
   *   Motivo de la revocación (para auditoría).
   *
   * @return bool
   *   TRUE si se revocó correctamente.
   */
  public function revocarRol(int $uid, string $rol, string $motivo = ''): bool;

  /**
   * Obtiene todos los usuarios con un rol específico en el tenant actual.
   *
   * @param string $rol
   *   Constante ROL_* a buscar.
   *
   * @return array
   *   Array de objetos usuario con el rol indicado.
   */
  public function getUsuariosPorRol(string $rol): array;

}
```

**Responsabilidades:**

1. **Detección unificada** — un solo punto de entrada para saber qué rol tiene un usuario
2. **Asignación programática** — asigna el rol Drupal correspondiente + crea log de auditoría
3. **Revocación** — revoca el rol Drupal + crea log de auditoría
4. **Listado por rol** — para la sección "Equipo del programa" del dashboard del coordinador
5. **Tenant-aware** — filtra por tenant_id en todas las operaciones

**Registro en `services.yml`:**

```yaml
jaraba_andalucia_ei.rol_programa:
  class: Drupal\jaraba_andalucia_ei\Service\RolProgramaService
  arguments:
    - '@entity_type.manager'
    - '@current_user'
    - '@?ecosistema_jaraba_core.tenant_context'
    - '@logger.channel.jaraba_andalucia_ei'
    - '@?jaraba_andalucia_ei.rol_programa_log'
```

**Refactorización de AccesoProgramaService:**

`AccesoProgramaService` se simplifica para delegar al nuevo servicio:

```php
public function getRolProgramaUsuario(AccountInterface $account): string {
  return $this->rolProgramaService->getRolProgramaUsuario($account);
}

public function puedeAccederDashboardCoordinador(AccountInterface $account): bool {
  return $this->rolProgramaService->tieneRol($account, RolProgramaServiceInterface::ROL_COORDINADOR);
}

public function puedeAccederDashboardOrientador(AccountInterface $account): bool {
  return $this->rolProgramaService->tieneRol($account, RolProgramaServiceInterface::ROL_ORIENTADOR);
}

public function puedeAccederDashboardFormador(AccountInterface $account): bool {
  return $this->rolProgramaService->tieneRol($account, RolProgramaServiceInterface::ROL_FORMADOR);
}
```

### 4.3 SPEC-ROL-003: AsignacionRolController (slide-panel desde dashboard)

**Objetivo:** Permitir al coordinador asignar/revocar roles del programa desde su dashboard via slide-panel.

**Fichero:** `jaraba_andalucia_ei/src/Controller/AsignacionRolController.php`

**Rutas a añadir en `routing.yml`:**

```yaml
jaraba_andalucia_ei.asignar_rol:
  path: '/andalucia-ei/coordinador/asignar-rol'
  defaults:
    _controller: '\Drupal\jaraba_andalucia_ei\Controller\AsignacionRolController::asignarRolForm'
    _title: 'Asignar profesional al programa'
  requirements:
    _permission: 'assign andalucia ei roles'

jaraba_andalucia_ei.revocar_rol:
  path: '/andalucia-ei/coordinador/revocar-rol/{uid}'
  defaults:
    _controller: '\Drupal\jaraba_andalucia_ei\Controller\AsignacionRolController::revocarRolForm'
    _title: 'Revocar rol de profesional'
  requirements:
    _permission: 'assign andalucia ei roles'
    uid: \d+

jaraba_andalucia_ei.equipo_programa:
  path: '/andalucia-ei/coordinador/equipo'
  defaults:
    _controller: '\Drupal\jaraba_andalucia_ei\Controller\AsignacionRolController::equipoPrograma'
    _title: 'Equipo del programa'
  requirements:
    _permission: 'assign andalucia ei roles'
```

**Patrón slide-panel (SLIDE-PANEL-RENDER-001 + SLIDE-PANEL-RENDER-002):**

```php
public function asignarRolForm(Request $request): Response|array {
  $form = $this->formBuilder->getForm(AsignacionRolForm::class);

  if ($this->isSlidePanelRequest($request)) {
    $form['#action'] = $request->getRequestUri();
    $html = (string) $this->renderer->renderPlain($form);
    return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
  }

  return $form;
}
```

### 4.4 SPEC-ROL-004: AsignacionRolForm (PremiumEntityFormBase)

**Objetivo:** Formulario de asignación de rol del programa con patrón premium del SaaS.

**Fichero:** `jaraba_andalucia_ei/src/Form/AsignacionRolForm.php`

**Nota sobre herencia:** Este formulario NO extiende `PremiumEntityFormBase` porque no es un entity form sino un `FormBase`. Usa los mismos patrones visuales (glassmorphism, iconos duotone) via CSS classes del tema.

**Campos del formulario:**

| Campo | Tipo | Descripción | Validación |
|-------|------|-------------|------------|
| `usuario` | entity_autocomplete (user) | Buscar usuario por nombre o email | Required. Debe ser miembro del tenant actual |
| `rol_programa` | radios | Coordinador / Orientador / Formador | Required |
| `motivo` | textarea | Motivo de la asignación (para auditoría FSE+) | Optional pero recomendado |

**Iconos (ICON-CONVENTION-001):**

```php
'coordinador' => [
  'label' => $this->t('Coordinador'),
  'icon' => ['category' => 'users', 'name' => 'user-shield', 'variant' => 'duotone', 'color' => 'azul-corporativo'],
  'description' => $this->t('Gestión integral del programa, solicitudes, fases y justificación.'),
],
'orientador' => [
  'label' => $this->t('Orientador'),
  'icon' => ['category' => 'users', 'name' => 'user-check', 'variant' => 'duotone', 'color' => 'verde-innovacion'],
  'description' => $this->t('Seguimiento individualizado, actuaciones STO y firma de hojas de servicio.'),
],
'formador' => [
  'label' => $this->t('Formador'),
  'icon' => ['category' => 'education', 'name' => 'graduation-cap', 'variant' => 'duotone', 'color' => 'naranja-impulso'],
  'description' => $this->t('Impartición de sesiones, control de asistencia y materiales didácticos.'),
],
```

**Textos traducibles:** Todos los labels y descriptions usan `$this->t()` (directriz de interfaz siempre traducible).

### 4.5 SPEC-ROL-005: RolProgramaLog entity (auditoría)

**Objetivo:** Entity de contenido para registrar cada cambio de rol del programa, cumpliendo trazabilidad FSE+ Art. 12.

**Fichero:** `jaraba_andalucia_ei/src/Entity/RolProgramaLog.php`

**Campos (baseFieldDefinitions):**

| Campo | Tipo | Required | Descripción |
|-------|------|----------|-------------|
| `id` | integer (auto) | — | Primary key |
| `user_id` | entity_reference(user) | Sí | Usuario afectado por el cambio |
| `assigned_by` | entity_reference(user) | Sí | Usuario que realizó la acción |
| `rol_programa` | list_string | Sí | Valores: `coordinador`, `orientador`, `formador` |
| `accion` | list_string | Sí | Valores: `asignar`, `revocar` |
| `motivo` | text_long | No | Motivo de la acción (texto libre) |
| `tenant_id` | entity_reference(tenant) | Sí | TENANT-001 |
| `created` | created | — | Timestamp automático |

**Anotación entity (cumplimiento directrices):**

```php
/**
 * @ContentEntityType(
 *   id = "rol_programa_log",
 *   label = @Translation("Log de Rol del Programa"),
 *   handlers = {
 *     "access" = "Drupal\jaraba_andalucia_ei\Access\RolProgramaLogAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "list_builder" = "Drupal\jaraba_andalucia_ei\ListBuilder\RolProgramaLogListBuilder",
 *   },
 *   base_table = "rol_programa_log",
 *   admin_permission = "administer andalucia ei",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/rol-programa-log",
 *   },
 *   field_ui_base_route = "entity.rol_programa_log.settings",
 * )
 */
```

**Directrices cumplidas:**
- `AUDIT-CONS-001`: AccessControlHandler en anotación
- `ENTITY-001`: EntityOwnerInterface + EntityChangedInterface (via assigned_by como owner)
- `ENTITY-FK-001`: tenant_id como entity_reference
- `UPDATE-HOOK-REQUIRED-001`: hook_update_N con installEntityType
- `views_data` declarado para integración con Views

### 4.6 SPEC-ROL-006: Dashboard del Formador

**Objetivo:** Dashboard completo para el rol de formador, siguiendo el mismo patrón que el dashboard de coordinador y orientador.

**Ficheros:**

| Fichero | Descripción |
|---------|-------------|
| `src/Controller/FormadorDashboardController.php` | Controller con datos del dashboard |
| `templates/formador-dashboard.html.twig` | Template principal |
| `templates/partials/_formador-sesiones.html.twig` | Parcial: sesiones asignadas |
| `templates/partials/_formador-asistencia.html.twig` | Parcial: control de asistencia |
| `templates/partials/_formador-materiales.html.twig` | Parcial: materiales didácticos |
| `templates/partials/_formador-calendario.html.twig` | Parcial: calendario del programa |
| `templates/partials/_formador-kpis.html.twig` | Parcial: métricas del formador |

**Ruta:**

```yaml
jaraba_andalucia_ei.formador_dashboard:
  path: '/andalucia-ei/formador'
  defaults:
    _controller: '\Drupal\jaraba_andalucia_ei\Controller\FormadorDashboardController::dashboard'
    _title: 'Panel del Formador'
  requirements:
    _permission: 'access andalucia ei formador dashboard'
```

**Template page (ZERO-REGION-001):**

Se necesita `page--andalucia-ei--formador.html.twig` en el tema, siguiendo el patrón de las 35 page templates existentes. Usa `{{ clean_content }}` y `{{ clean_messages }}`.

**Controller (ZERO-REGION-001, ZERO-REGION-003):**

```php
public function dashboard(): array {
  // Controller devuelve solo markup mínimo.
  // Los datos se inyectan via hook_preprocess_page() en drupalSettings.
  return [
    '#type' => 'markup',
    '#markup' => '',
  ];
}
```

**Preprocess (en .module):**

```php
// En hook_preprocess_page():
if ($route_name === 'jaraba_andalucia_ei.formador_dashboard') {
  $rolProgramaService = \Drupal::service('jaraba_andalucia_ei.rol_programa');
  $currentUser = \Drupal::currentUser();

  // Datos del formador via drupalSettings (ZERO-REGION-003).
  $variables['#attached']['drupalSettings']['formadorDashboard'] = [
    'sesiones_hoy' => $formadorService->getSesionesHoy($currentUser->id()),
    'asistencia_pendiente' => $formadorService->getAsistenciaPendiente($currentUser->id()),
    'materiales_count' => $formadorService->getMaterialesCount($currentUser->id()),
    'horas_impartidas' => $formadorService->getHorasImpartidas($currentUser->id()),
    'asistencia_media' => $formadorService->getAsistenciaMedia($currentUser->id()),
  ];

  // Setup Wizard + Daily Actions (SETUP-WIZARD-DAILY-001).
  $variables['#setup_wizard'] = $setupWizardRegistry->getSteps('formador_ei', $currentUser->id());
  $variables['#daily_actions'] = $dailyActionsRegistry->getActions('formador_ei', $currentUser->id());
}
```

**SCSS (CSS-VAR-ALL-COLORS-001, SCSS-COMPILE-VERIFY-001):**

Fichero `scss/routes/formador-dashboard.scss` compilado a `css/routes/formador-dashboard.css`:

```scss
@use '../variables' as *;

.formador-dashboard {
  &__header {
    background: var(--ej-bg-surface, $ej-color-gray-50);
    border-bottom: 2px solid var(--ej-primary, $ej-color-impulse);
    padding: 1.5rem;
  }

  &__kpis {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
  }

  &__sesiones {
    margin-bottom: 2rem;
  }

  &__asistencia-card {
    background: var(--ej-bg-card, #fff);
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px color-mix(in srgb, var(--ej-shadow, #000) 10%, transparent);
  }
}
```

**Library (jaraba_andalucia_ei.libraries.yml):**

```yaml
formador-dashboard:
  version: VERSION
  css:
    theme:
      css/routes/formador-dashboard.css: {}
  js:
    js/formador-dashboard.js: {}
  dependencies:
    - core/drupal
    - core/drupalSettings
    - core/once
```

### 4.7 SPEC-ROL-007: Setup Wizard del Formador (3 steps)

**Ficheros:**

| Fichero | Step ID | Weight | Descripción |
|---------|---------|--------|-------------|
| `src/SetupWizard/FormadorPerfilStep.php` | `formador_ei.perfil` | 10 | Completar perfil profesional (StaffProfileEi) |
| `src/SetupWizard/FormadorSesionesStep.php` | `formador_ei.sesiones` | 20 | Revisar sesiones asignadas (≥1 sesión visible) |
| `src/SetupWizard/FormadorMaterialStep.php` | `formador_ei.material` | 30 | Subir primer material didáctico |

**Registro en services.yml (tagged services via CompilerPass):**

```yaml
jaraba_andalucia_ei.setup_wizard.formador_perfil:
  class: Drupal\jaraba_andalucia_ei\SetupWizard\FormadorPerfilStep
  arguments:
    - '@entity_type.manager'
    - '@current_user'
  tags:
    - { name: ecosistema_jaraba_core.setup_wizard_step }

jaraba_andalucia_ei.setup_wizard.formador_sesiones:
  class: Drupal\jaraba_andalucia_ei\SetupWizard\FormadorSesionesStep
  arguments:
    - '@entity_type.manager'
    - '@current_user'
  tags:
    - { name: ecosistema_jaraba_core.setup_wizard_step }

jaraba_andalucia_ei.setup_wizard.formador_material:
  class: Drupal\jaraba_andalucia_ei\SetupWizard\FormadorMaterialStep
  arguments:
    - '@entity_type.manager'
    - '@current_user'
  tags:
    - { name: ecosistema_jaraba_core.setup_wizard_step }
```

**Iconos (ICON-CONVENTION-001, ICON-DUOTONE-001):**

| Step | Categoría | Nombre | Variante | Color |
|------|-----------|--------|----------|-------|
| Perfil | `users` | `user-edit` | `duotone` | `naranja-impulso` |
| Sesiones | `education` | `clipboard` | `duotone` | `naranja-impulso` |
| Material | `content` | `book-open` | `duotone` | `naranja-impulso` |

### 4.8 SPEC-ROL-008: Daily Actions del Formador (3 actions)

**Ficheros:**

| Fichero | Action ID | Descripción |
|---------|-----------|-------------|
| `src/DailyActions/FormadorSesionesHoyAction.php` | `formador_ei.sesiones_hoy` | Sesiones que imparte hoy con hora y lugar |
| `src/DailyActions/FormadorAsistenciaAction.php` | `formador_ei.asistencia` | Sesiones con asistencia pendiente de pasar |
| `src/DailyActions/FormadorMaterialesAction.php` | `formador_ei.materiales` | Materiales por subir o actualizar |

**Registro en services.yml:**

```yaml
jaraba_andalucia_ei.daily_action.formador_sesiones_hoy:
  class: Drupal\jaraba_andalucia_ei\DailyActions\FormadorSesionesHoyAction
  arguments:
    - '@entity_type.manager'
    - '@current_user'
    - '@?ecosistema_jaraba_core.tenant_context'
  tags:
    - { name: ecosistema_jaraba_core.daily_action }

jaraba_andalucia_ei.daily_action.formador_asistencia:
  class: Drupal\jaraba_andalucia_ei\DailyActions\FormadorAsistenciaAction
  arguments:
    - '@entity_type.manager'
    - '@current_user'
    - '@?ecosistema_jaraba_core.tenant_context'
  tags:
    - { name: ecosistema_jaraba_core.daily_action }

jaraba_andalucia_ei.daily_action.formador_materiales:
  class: Drupal\jaraba_andalucia_ei\DailyActions\FormadorMaterialesAction
  arguments:
    - '@entity_type.manager'
    - '@current_user'
    - '@?ecosistema_jaraba_core.tenant_context'
  tags:
    - { name: ecosistema_jaraba_core.daily_action }
```

### 4.9 SPEC-ROL-009: Notificaciones de asignación de rol

**Objetivo:** Al asignar o revocar un rol, el usuario afectado recibe notificación por email y, si está disponible, in-app.

**Mecanismo:**

1. `RolProgramaService::asignarRol()` dispara `hook_jaraba_andalucia_ei_rol_asignado`
2. El módulo implementa el hook para enviar email via `EiMultichannelNotificationService` (OPTIONAL-CROSSMODULE-001: `@?`)
3. Email template: `email/asignacion-rol-programa.html.twig`

**Template email (textos traducibles):**

```twig
{% trans %}Hola {{ nombre }},{% endtrans %}

{% trans %}Has sido asignado/a como <strong>{{ rol_label }}</strong> en el programa Andalucía +ei.{% endtrans %}

{% trans %}Accede a tu panel para comenzar:{% endtrans %}
<a href="{{ dashboard_url }}">{{ dashboard_url }}</a>

{% trans %}Si tienes preguntas, contacta con el coordinador del programa.{% endtrans %}
```

### 4.10 SPEC-ROL-010: Perfil profesional del staff (StaffProfileEi entity)

**Objetivo:** Registrar la información profesional de coordinadores, orientadores y formadores para cumplimiento normativo FSE+ (cualificación del personal que interviene en el programa).

**Fichero:** `jaraba_andalucia_ei/src/Entity/StaffProfileEi.php`

**Campos:**

| Campo | Tipo | Required | Descripción |
|-------|------|----------|-------------|
| `id` | integer (auto) | — | Primary key |
| `user_id` | entity_reference(user) | Sí | Usuario del perfil |
| `rol_programa` | list_string | Sí | coordinador / orientador / formador |
| `titulacion` | string(255) | No | Titulación académica principal |
| `experiencia_anios` | integer | No | Años de experiencia profesional |
| `certificaciones` | text_long | No | Certificaciones relevantes (JSON array) |
| `especialidades` | text_long | No | Áreas de especialización (JSON array) |
| `fecha_incorporacion` | datetime | No | Fecha de incorporación al programa |
| `status` | list_string | Sí | `active` / `inactive` |
| `tenant_id` | entity_reference(tenant) | Sí | TENANT-001 |
| `created` | created | — | Automático |
| `changed` | changed | — | Automático |

**Relación con roles:** Al asignar un rol via `RolProgramaService::asignarRol()`, si no existe `StaffProfileEi` para ese usuario+rol, se crea automáticamente con status=active. Al revocar, se marca como inactive.

**Admin links:**

| Link | Ruta |
|------|------|
| Collection | `/admin/content/staff-profiles-ei` |
| Canonical | `/admin/content/staff-profile-ei/{id}` |
| Edit | `/admin/content/staff-profile-ei/{id}/edit` |
| Settings | `/admin/structure/staff-profile-ei` |

---

## 5. Pipeline E2E por Componente

Verificación de las 4 capas (PIPELINE-E2E-001) para cada componente nuevo:

### Dashboard del Formador

| Capa | Verificación | Detalle |
|------|-------------|---------|
| **L1: Service → Controller** | `FormadorDashboardService` inyectado en `FormadorDashboardController` via constructor + `create()` | DI con `@?ecosistema_jaraba_core.tenant_context` |
| **L2: Controller → render array** | Controller pasa datos mínimos; datos reales via `drupalSettings` en preprocess | `#setup_wizard`, `#daily_actions` en preprocess |
| **L3: hook_theme() → variables** | `'formador_dashboard' => ['variables' => [...]]` declarado en `.module` | Variables: `setup_wizard`, `daily_actions`, `formador_data` |
| **L4: Template → parciales** | `formador-dashboard.html.twig` incluye 5 parciales con `{% include ... only %}` | Textos con `{% trans %}`, `jaraba_icon()` |

### Asignación de Roles (slide-panel)

| Capa | Verificación | Detalle |
|------|-------------|---------|
| **L1: Service → Controller** | `RolProgramaService` inyectado en `AsignacionRolController` | `asignarRol()` + `revocarRol()` + `getUsuariosPorRol()` |
| **L2: Controller → Response** | `renderPlain()` para slide-panel (SLIDE-PANEL-RENDER-001) | `$form['#action'] = $request->getRequestUri()` |
| **L3: Form → submit** | `AsignacionRolForm::submitForm()` → `RolProgramaService::asignarRol()` | Log auditoría + notificación |
| **L4: Frontend → slide-panel** | Botón "Asignar profesional" en `coordinador-dashboard.html.twig` → fetch AJAX → slide-panel | `data-slide-panel-url` attribute |

### RolProgramaLog entity

| Capa | Verificación | Detalle |
|------|-------------|---------|
| **L1: Entity definition** | `baseFieldDefinitions()` con 7 campos | `user_id`, `assigned_by`, `rol_programa`, `accion`, `motivo`, `tenant_id`, `created` |
| **L2: hook_update_N** | `installEntityType('rol_programa_log')` en update hook | Requerido por UPDATE-HOOK-REQUIRED-001 |
| **L3: Access Handler** | `RolProgramaLogAccessControlHandler` en anotación | Solo `view` para coordinadores, `create` para sistema |
| **L4: Views integration** | `views_data = EntityViewsData` en anotación | Exportable desde Views UI |

### StaffProfileEi entity

| Capa | Verificación | Detalle |
|------|-------------|---------|
| **L1: Entity definition** | `baseFieldDefinitions()` con 10 campos | Perfil profesional |
| **L2: hook_update_N** | `installEntityType('staff_profile_ei')` en update hook | UPDATE-HOOK-REQUIRED-001 |
| **L3: Form** | `StaffProfileEiForm extends PremiumEntityFormBase` | PREMIUM-FORMS-PATTERN-001 |
| **L4: Field UI + Views** | `field_ui_base_route` + `views_data` en anotación | FIELD-UI-SETTINGS-TAB-001 |

---

## 6. Tabla de Correspondencia con Directrices

| Directriz CLAUDE.md | Cumplimiento en este plan | Detalle |
|---------------------|--------------------------|---------|
| **TENANT-001** | ✅ | Todas las queries de RolProgramaService, StaffProfileEi y RolProgramaLog filtran por tenant_id |
| **TENANT-ISOLATION-ACCESS-001** | ✅ | AccessControlHandler de RolProgramaLog y StaffProfileEi verifican tenant match |
| **PREMIUM-FORMS-PATTERN-001** | ✅ | StaffProfileEiForm extiende PremiumEntityFormBase. AsignacionRolForm usa patrón visual equivalente |
| **SETUP-WIZARD-DAILY-001** | ✅ | 3 wizard steps + 3 daily actions para formador via tagged services |
| **ZERO-REGION-001** | ✅ | Dashboard formador usa page template limpio + clean_content |
| **ZERO-REGION-003** | ✅ | Datos via drupalSettings en preprocess, NO en controller |
| **SLIDE-PANEL-RENDER-001** | ✅ | AsignacionRolController usa renderPlain() para slide-panel |
| **SLIDE-PANEL-RENDER-002** | ✅ | Ruta con _controller (NO _form) para slide-panel |
| **ICON-CONVENTION-001** | ✅ | Todos los iconos via `jaraba_icon()` con categoría/nombre/variante/color |
| **ICON-DUOTONE-001** | ✅ | Variante duotone por defecto en wizard steps y formulario |
| **ICON-COLOR-001** | ✅ | Colores solo de paleta Jaraba (azul-corporativo, naranja-impulso, verde-innovacion) |
| **CSS-VAR-ALL-COLORS-001** | ✅ | SCSS usa `var(--ej-*, $fallback)` para todos los colores |
| **SCSS-COMPILE-VERIFY-001** | ✅ | `npm run build` incluye formador-dashboard.css. Verificar timestamp CSS > SCSS |
| **SCSS-001** | ✅ | `@use '../variables' as *;` en cada parcial SCSS |
| **CONTROLLER-READONLY-001** | ✅ | Controllers NO usan readonly en props heredadas |
| **ACCESS-RETURN-TYPE-001** | ✅ | `checkAccess()` retorna `AccessResultInterface` (no AccessResult) |
| **ENTITY-FK-001** | ✅ | `tenant_id` como entity_reference; `user_id`/`assigned_by` como entity_reference |
| **AUDIT-CONS-001** | ✅ | AccessControlHandler en anotación de ambas entities |
| **ENTITY-PREPROCESS-001** | ✅ | `template_preprocess_formador_dashboard()` en .module |
| **ENTITY-001** | ✅ | Entities implementan EntityOwnerInterface + EntityChangedInterface |
| **UPDATE-HOOK-REQUIRED-001** | ✅ | hook_update_N con installEntityType para ambas entities nuevas |
| **FIELD-UI-SETTINGS-TAB-001** | ✅ | field_ui_base_route + default local task tab en StaffProfileEi |
| **OPTIONAL-CROSSMODULE-001** | ✅ | Notificaciones via `@?jaraba_andalucia_ei.ei_multichannel_notification` |
| **PHANTOM-ARG-001** | ✅ | Args en services.yml coinciden exactamente con constructor PHP |
| **LOGGER-INJECT-001** | ✅ | `@logger.channel.jaraba_andalucia_ei` → constructor acepta `LoggerInterface $logger` |
| **ROUTE-LANGPREFIX-001** | ✅ | URLs via `Url::fromRoute()`, NUNCA hardcoded |
| **TWIG-INCLUDE-ONLY-001** | ✅ | Parciales con `{% include ... only %}` |
| **IMPLEMENTATION-CHECKLIST-001** | ✅ | Verificado en Pipeline E2E (sección 5) |
| **RUNTIME-VERIFY-001** | ✅ | Checklist en sección 9 |

---

## 7. Tabla de Correspondencia con Especificaciones Técnicas

| SPEC ID | Título | Ficheros principales | Gap resuelto | Directrices aplicadas |
|---------|--------|---------------------|-------------|----------------------|
| **SPEC-ROL-001** | Roles Drupal con permisos | `config/install/user.role.{coordinador,orientador,formador}_ei.yml` + hook_update_N | GAP-ROLES-001, GAP-ROLES-002 | IMPLEMENTATION-CHECKLIST-001, UPDATE-HOOK-REQUIRED-001 |
| **SPEC-ROL-002** | RolProgramaService | `src/Service/RolProgramaService.php` + refactor AccesoProgramaService | GAP-ROLES-004, GAP-ROLES-006 | TENANT-001, OPTIONAL-CROSSMODULE-001, PHANTOM-ARG-001 |
| **SPEC-ROL-003** | AsignacionRolController | `src/Controller/AsignacionRolController.php` + routing.yml | GAP-ROLES-005 | SLIDE-PANEL-RENDER-001, SLIDE-PANEL-RENDER-002, ZERO-REGION-001 |
| **SPEC-ROL-004** | AsignacionRolForm | `src/Form/AsignacionRolForm.php` | GAP-ROLES-005 | ICON-CONVENTION-001, i18n ($this->t()), TENANT-001 |
| **SPEC-ROL-005** | RolProgramaLog entity | `src/Entity/RolProgramaLog.php` + ACH + ListBuilder | GAP-ROLES-007 | AUDIT-CONS-001, ENTITY-FK-001, ENTITY-001, UPDATE-HOOK-REQUIRED-001 |
| **SPEC-ROL-006** | Dashboard Formador | Controller + template + 5 parciales + SCSS + library + JS | GAP-ROLES-003 | ZERO-REGION-001, ZERO-REGION-003, CSS-VAR-ALL-COLORS-001, SCSS-COMPILE-VERIFY-001 |
| **SPEC-ROL-007** | Setup Wizard Formador | 3 steps PHP (tagged services) | GAP-ROLES-003 | SETUP-WIZARD-DAILY-001, ICON-DUOTONE-001 |
| **SPEC-ROL-008** | Daily Actions Formador | 3 actions PHP (tagged services) | GAP-ROLES-003 | SETUP-WIZARD-DAILY-001, TENANT-001 |
| **SPEC-ROL-009** | Notificaciones de rol | Hook + email template | GAP-ROLES-005 | OPTIONAL-CROSSMODULE-001, i18n ({% trans %}) |
| **SPEC-ROL-010** | StaffProfileEi entity | `src/Entity/StaffProfileEi.php` + form + ACH | GAP-ROLES-010 (FSE+ trazabilidad) | PREMIUM-FORMS-PATTERN-001, ENTITY-FK-001, FIELD-UI-SETTINGS-TAB-001 |

---

## 8. Plan de Fases

### 8.1 Fase 0 — Fundamentos: Roles Drupal + auto-provisioning

**Objetivo:** Crear los 3 roles Drupal como config entities y asegurar auto-provisioning.

**Ficheros a crear:**
- `config/install/user.role.coordinador_ei.yml`
- `config/install/user.role.orientador_ei.yml`
- `config/install/user.role.formador_ei.yml`

**Ficheros a modificar:**
- `jaraba_andalucia_ei.permissions.yml` — añadir 2 permisos nuevos
- `jaraba_andalucia_ei.install` — hook_update_N para instalaciones existentes

**Criterios de aceptación:**
- [ ] Los 3 roles existen en config y se instalan con el módulo
- [ ] Cada rol tiene todos los permisos listados en SPEC-ROL-001
- [ ] hook_update_N es idempotente (no falla si los roles ya existen)
- [ ] `drush cex` exporta los roles correctamente

### 8.2 Fase 1 — RolProgramaService: detección unificada

**Objetivo:** Crear el servicio de detección unificada y refactorizar AccesoProgramaService.

**Ficheros a crear:**
- `src/Service/RolProgramaServiceInterface.php`
- `src/Service/RolProgramaService.php`

**Ficheros a modificar:**
- `jaraba_andalucia_ei.services.yml` — registrar nuevo servicio
- `src/Service/AccesoProgramaService.php` — delegar al nuevo servicio
- `src/UserProfile/AndaluciaEiUserProfileSection.php` — usar RolProgramaService

**Criterios de aceptación:**
- [ ] `getRolProgramaUsuario()` devuelve correctamente los 6 valores posibles
- [ ] La detección de orientador es coherente entre AccesoProgramaService y UserProfileSection
- [ ] `esStaff()` devuelve TRUE para los 3 roles profesionales
- [ ] Backward compatibility: usuarios con `administer andalucia ei` siguen siendo coordinadores
- [ ] Tests: mínimo 12 casos (4 roles × 3 escenarios: tiene rol, no tiene, edge case)

### 8.3 Fase 2 — Dashboard del Formador + Wizard + Daily Actions

**Objetivo:** Crear el dashboard completo del formador como ciudadano de primera clase.

**Ficheros a crear:**
- `src/Controller/FormadorDashboardController.php`
- `src/Service/FormadorDashboardService.php`
- `templates/formador-dashboard.html.twig`
- 5 parciales en `templates/partials/`
- `scss/routes/formador-dashboard.scss` → compilar a `css/routes/formador-dashboard.css`
- `js/formador-dashboard.js`
- 3 SetupWizard steps en `src/SetupWizard/`
- 3 DailyActions en `src/DailyActions/`
- Page template en el tema: `page--andalucia-ei--formador.html.twig`

**Ficheros a modificar:**
- `jaraba_andalucia_ei.routing.yml` — ruta del dashboard
- `jaraba_andalucia_ei.services.yml` — servicio + wizard steps + daily actions
- `jaraba_andalucia_ei.libraries.yml` — library del dashboard
- `jaraba_andalucia_ei.module` — hook_theme + hook_preprocess_page
- `ecosistema_jaraba_theme/package.json` — build:components incluye formador-dashboard
- `ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme` — theme suggestion para page template

**Criterios de aceptación:**
- [ ] Dashboard accesible en `/andalucia-ei/formador` con rol `formador_ei`
- [ ] 5 parciales renderizan correctamente con datos reales
- [ ] Setup Wizard muestra 3 steps con iconos duotone correctos
- [ ] Daily Actions muestran 3 acciones contextuales
- [ ] SCSS compilado (timestamp CSS > SCSS)
- [ ] Layout mobile-first (sin max-width breakpoints)
- [ ] Textos traducibles ({% trans %} en Twig, $this->t() en PHP)
- [ ] Page template limpio (clean_content, sin sidebar admin)

### 8.4 Fase 3 — Asignación de roles desde dashboard coordinador

**Objetivo:** El coordinador puede asignar y revocar roles del programa desde su dashboard.

**Ficheros a crear:**
- `src/Controller/AsignacionRolController.php`
- `src/Form/AsignacionRolForm.php`
- `templates/partials/_equipo-programa.html.twig`

**Ficheros a modificar:**
- `jaraba_andalucia_ei.routing.yml` — 3 rutas nuevas
- `jaraba_andalucia_ei.services.yml` — inyección de RolProgramaService
- `templates/coordinador-dashboard.html.twig` — incluir sección equipo
- `src/Service/CoordinadorHubService.php` — añadir datos de equipo

**Criterios de aceptación:**
- [ ] Sección "Equipo del programa" visible en dashboard coordinador
- [ ] Botón "Asignar profesional" abre slide-panel
- [ ] Formulario permite buscar usuario + seleccionar rol + motivo
- [ ] Al guardar: rol Drupal asignado + log creado + notificación enviada
- [ ] Revocar: slide-panel con confirmación + rol revocado + log creado
- [ ] Slide-panel funciona correctamente (renderPlain, no full page)

### 8.5 Fase 4 — Auditoría de roles + StaffProfileEi

**Objetivo:** Crear las entities de auditoría y perfil profesional.

**Ficheros a crear:**
- `src/Entity/RolProgramaLog.php`
- `src/Access/RolProgramaLogAccessControlHandler.php`
- `src/ListBuilder/RolProgramaLogListBuilder.php`
- `src/Entity/StaffProfileEi.php`
- `src/Access/StaffProfileEiAccessControlHandler.php`
- `src/Form/StaffProfileEiForm.php` (extends PremiumEntityFormBase)
- `src/ListBuilder/StaffProfileEiListBuilder.php`
- `templates/staff-profile-ei.html.twig`

**Ficheros a modificar:**
- `jaraba_andalucia_ei.install` — hook_update_N para ambas entities
- `jaraba_andalucia_ei.routing.yml` — admin routes para ambas entities
- `jaraba_andalucia_ei.module` — hook_theme para StaffProfileEi

**Criterios de aceptación:**
- [ ] RolProgramaLog registra cada asignación/revocación con todos los campos
- [ ] StaffProfileEi con formulario premium (getSectionDefinitions + getFormIcon)
- [ ] Ambas entities accesibles en `/admin/content/` con listado
- [ ] Field UI disponible en `/admin/structure/` para StaffProfileEi
- [ ] Views integration funcional para ambas entities
- [ ] hook_update_N idempotente con installEntityType

### 8.6 Fase 5 — Salvaguardas, validadores y tests

**Objetivo:** Cerrar el ciclo con validadores, tests y documentación.

**Ficheros a crear:**
- `scripts/validation/validate-andalucia-ei-roles.php` (8 checks)
- `tests/src/Unit/Service/RolProgramaServiceTest.php`
- `tests/src/Kernel/Entity/RolProgramaLogTest.php`
- `tests/src/Kernel/Entity/StaffProfileEiTest.php`
- `tests/src/Unit/Controller/AsignacionRolControllerTest.php`

**Ficheros a modificar:**
- `scripts/validation/validate-all.sh` — añadir nuevo validador
- `docs/validators-reference.md` — documentar validador
- `jaraba_andalucia_ei.install` — hook_requirements runtime check

**Criterios de aceptación:**
- [ ] Validador pasa 8/8 checks en `validate-all.sh`
- [ ] Tests: ≥20 nuevos (12 unit RolProgramaService + 4 kernel entities + 4 functional)
- [ ] hook_requirements muestra warning si falta algún rol
- [ ] `docs/validators-reference.md` actualizado con nuevo validador

---

## 9. Verificación RUNTIME-VERIFY-001

Checklist obligatorio post-implementación:

| # | Verificación | Comando / Método | Criterio de éxito |
|---|-------------|-----------------|-------------------|
| 1 | **CSS compilado** | `stat css/routes/formador-dashboard.css` vs `stat scss/routes/formador-dashboard.scss` | Timestamp CSS > SCSS |
| 2 | **Tablas DB creadas** | `lando drush entity:updates` | 0 pending updates para `rol_programa_log` y `staff_profile_ei` |
| 3 | **Rutas accesibles** | `lando drush router:match /andalucia-ei/formador` | Route found: `jaraba_andalucia_ei.formador_dashboard` |
| 4 | **Roles Drupal existen** | `lando drush role:list` | `coordinador_ei`, `orientador_ei`, `formador_ei` listados |
| 5 | **Permisos asignados** | `lando drush role:perm:list coordinador_ei` | Todos los permisos de SPEC-ROL-001 presentes |
| 6 | **drupalSettings inyectado** | Inspeccionar `<script>` en `/andalucia-ei/formador` | `drupalSettings.formadorDashboard` presente |
| 7 | **data-* selectores match** | Inspeccionar DOM del slide-panel de asignación | `data-slide-panel-url` apunta a ruta correcta |
| 8 | **Setup Wizard renderiza** | Login como formador → visitar dashboard | 3 steps visibles con iconos duotone |
| 9 | **Daily Actions renderiza** | Login como formador → visitar dashboard | 3 acciones contextuales visibles |
| 10 | **Slide-panel funciona** | Click "Asignar profesional" en dashboard coordinador | Formulario se abre en slide-panel (no full page) |
| 11 | **Auditoría registra** | Asignar un rol → verificar RolProgramaLog | Entity creada con todos los campos |
| 12 | **Notificación enviada** | Asignar un rol → verificar cola de email | Email en cola con template correcto |

---

## 10. Salvaguardas y Validadores Propuestos

### 10.1 Validador: `validate-andalucia-ei-roles.php`

**Ubicación:** `scripts/validation/validate-andalucia-ei-roles.php`

**8 checks:**

| # | Check ID | Tipo | Descripción |
|---|----------|------|-------------|
| 1 | `roles_exist` | `run_check` | Los 3 roles Drupal existen en config (`user.role.{coordinador,orientador,formador}_ei`) |
| 2 | `permissions_assigned` | `run_check` | Cada rol tiene sus permisos de gateway asignados según SPEC-ROL-001 |
| 3 | `dashboards_per_role` | `run_check` | Cada rol staff tiene ruta de dashboard en routing.yml |
| 4 | `wizard_per_role` | `run_check` | Cada wizard_id profesional tiene ≥1 step registrado en services.yml |
| 5 | `daily_actions_per_role` | `run_check` | Cada rol profesional tiene ≥1 daily action en services.yml |
| 6 | `detection_coherent` | `run_check` | `AccesoProgramaService` y `UserProfileSection` usan `RolProgramaService` |
| 7 | `log_entity_installed` | `run_check` | `rol_programa_log` entity existe y tiene tabla en DB |
| 8 | `no_comodin_permisos` | `warn_check` | Ningún permiso se usa como gate para >1 rol distinto |

### 10.2 Runtime check (hook_requirements)

En `jaraba_andalucia_ei.install`, añadir check runtime:

```php
function jaraba_andalucia_ei_requirements($phase): array {
  $requirements = [];

  if ($phase === 'runtime') {
    $roles_needed = ['coordinador_ei', 'orientador_ei', 'formador_ei'];
    $missing = [];
    foreach ($roles_needed as $role_id) {
      if (!Role::load($role_id)) {
        $missing[] = $role_id;
      }
    }
    if (!empty($missing)) {
      $requirements['andalucia_ei_roles'] = [
        'title' => t('Roles del programa Andalucía +ei'),
        'value' => t('Faltan roles: @roles', ['@roles' => implode(', ', $missing)]),
        'severity' => REQUIREMENT_WARNING,
      ];
    }
  }

  return $requirements;
}
```

### 10.3 Pre-commit hook (lint-staged)

Si se modifica `AccesoProgramaService.php`, `RolProgramaService.php` o `jaraba_andalucia_ei.permissions.yml`, ejecutar `validate-andalucia-ei-roles.php` automáticamente.

---

## 11. Riesgos y Mitigaciones

| # | Riesgo | Probabilidad | Impacto | Mitigación |
|---|--------|-------------|---------|------------|
| 1 | **Backward compatibility** — coordinadores existentes configurados con permiso directo pierden acceso | Media | Alto | Cascada de detección mantiene fallback a `hasPermission('administer andalucia ei')` en paso 4 |
| 2 | **Colisión config/install** — roles ya existen manualmente en algún entorno | Baja | Medio | hook_update_N usa `Role::load()` con guard + idempotencia |
| 3 | **Cross-módulo mentor_profile** — orientadores detectados por mentor_profile pero sin rol Drupal | Media | Medio | RolProgramaService detecta señal mentor_profile como evidencia secundaria + log warning recomendando asignar rol |
| 4 | **Performance equipo programa** — query de usuarios por rol en dashboard coordinador | Baja | Bajo | Cache con tag `config:user.role.{role}`, max 50 usuarios por tenant |
| 5 | **Notificaciones spam** — asignación/revocación rápida genera emails múltiples | Baja | Bajo | Debounce: no enviar si hay otro cambio en últimos 5 minutos |

---

## 12. Criterios de Aceptación 10/10 Clase Mundial

Para considerar el sistema de roles como clase mundial (15/15):

- [ ] (1 punto) **Roles auto-provisionados** al instalar el módulo — `config/install/user.role.*.yml`
- [ ] (1 punto) **Cada rol tiene dashboard dedicado** — coordinador + orientador + formador + participante = 4/4
- [ ] (1 punto) **Cada rol tiene Setup Wizard** — coordinador (4) + orientador (3) + formador (3) = 10 steps
- [ ] (1 punto) **Cada rol tiene Daily Actions** — coordinador (5) + orientador (4) + formador (3) = 12 actions
- [ ] (1 punto) **Asignación de roles desde dashboard** del coordinador via slide-panel
- [ ] (1 punto) **Desasignación y transferencia** de roles con confirmación y auditoría
- [ ] (1 punto) **Detección de rol unificada** — RolProgramaService como SSOT
- [ ] (1 punto) **Permisos granulares** por rol sin comodines semánticos
- [ ] (1 punto) **Auditoría de cambios** — RolProgramaLog entity con trazabilidad FSE+
- [ ] (1 punto) **Perfil profesional del staff** — StaffProfileEi entity para normativa
- [ ] (1 punto) **Notificaciones** al asignar/revocar roles (email + template traducible)
- [ ] (1 punto) **Slide-panel** para todas las operaciones (no navegar fuera del dashboard)
- [ ] (1 punto) **Validador de integridad** en `scripts/validation/` (8 checks)
- [ ] (1 punto) **Tests automatizados** — ≥20 tests cubriendo detección + asignación + revocación + auditoría
- [ ] (1 punto) **Documentación** — auditoría (esta) + plan (este) + validador reference actualizado

---

## 13. Glosario de Siglas

| Sigla | Significado |
|-------|------------|
| ACH | Access Control Handler — clase PHP que controla permisos de acceso a una entity en Drupal |
| AJAX | Asynchronous JavaScript and XML — comunicación asíncrona cliente-servidor |
| BBRR | Bases Reguladoras — marco jurídico que define las reglas de un programa de subvenciones |
| BMC | Business Model Canvas — lienzo de modelo de negocio utilizado en emprendimiento |
| CSRF | Cross-Site Request Forgery — ataque de falsificación de solicitud entre sitios |
| CSS | Cascading Style Sheets — hojas de estilo para presentación visual |
| DACI | Documento de Aceptación y Compromiso Individual — documento firmado por el participante al ingresar al programa PIIL |
| DB | Database — base de datos |
| DI | Dependency Injection — patrón de inyección de dependencias en el contenedor de servicios de Drupal |
| DOM | Document Object Model — representación del documento HTML como árbol de objetos |
| E2E | End-to-End — verificación de extremo a extremo de todo el pipeline |
| FSE+ | Fondo Social Europeo Plus — instrumento financiero de la UE para empleo e inclusión social, cofinancia el programa al 85% |
| ICV25 | Inserción CV 2025 — formato de exportación de datos para el sistema STO de la Junta de Andalucía |
| JSON | JavaScript Object Notation — formato de intercambio de datos |
| KPI | Key Performance Indicator — indicador clave de rendimiento |
| PIIL | Programa Integral de Inserción Laboral — tipo de programa de la Junta de Andalucía para inserción laboral de colectivos vulnerables |
| SCSS | Sassy CSS — preprocesador CSS utilizado en el tema del ecosistema (Dart Sass moderno) |
| SEO | Search Engine Optimization — optimización para motores de búsqueda |
| SPEC | Specification — especificación técnica detallada de un componente |
| SaaS | Software as a Service — modelo de distribución de software como servicio en la nube |
| SSOT | Single Source of Truth — fuente única de verdad para un dato o concepto |
| STO | Servicio Técnico de Orientación — sistema de la Junta de Andalucía para tracking de actuaciones de orientación laboral |
| TOC | Table of Contents — índice de navegación del documento |
| UX | User Experience — experiencia de usuario |
| VoBo | Visto Bueno — aprobación formal (workflow de 8 estados para acciones formativas SAE) |
| WCAG | Web Content Accessibility Guidelines — directrices de accesibilidad web del W3C |
| YAML | YAML Ain't Markup Language — formato de serialización de datos usado en configuración Drupal |
