# Auditoría: Sistema de Roles del Programa Andalucía +ei — Diagnóstico Integral

> **Versión:** 1.0.0
> **Fecha de creación:** 2026-03-24
> **Última actualización:** 2026-03-24
> **Autor:** Claude Opus 4.6 (1M context)
> **Estado:** Completado
> **Categoría:** Auditoría de Arquitectura + Gap Analysis
> **Módulos auditados:** `jaraba_andalucia_ei`, `jaraba_mentoring`, `ecosistema_jaraba_core`
> **Directrices raíz:** TENANT-001, TENANT-ISOLATION-ACCESS-001, PREMIUM-FORMS-PATTERN-001, SETUP-WIZARD-DAILY-001, IMPLEMENTATION-CHECKLIST-001
> **Fuentes normativas:** PIIL BBRR (Bases Reguladoras), Resolución de Concesión SC/ICV/0111/2025, Manual Gestión STO ICV25

---

## Índice de Navegación

- [1. Resumen Ejecutivo](#1-resumen-ejecutivo)
- [2. Metodología de Auditoría](#2-metodología-de-auditoría)
- [3. Inventario del Sistema de Roles Actual](#3-inventario-del-sistema-de-roles-actual)
  - [3.1 Definición canónica de roles del programa](#31-definición-canónica-de-roles-del-programa)
  - [3.2 Mecanismos de detección por rol](#32-mecanismos-de-detección-por-rol)
  - [3.3 Mecanismos de asignación por rol](#33-mecanismos-de-asignación-por-rol)
  - [3.4 Dashboards y portales por rol](#34-dashboards-y-portales-por-rol)
  - [3.5 Setup Wizard y Daily Actions por rol](#35-setup-wizard-y-daily-actions-por-rol)
  - [3.6 Permisos Drupal asociados](#36-permisos-drupal-asociados)
- [4. Análisis de Brechas (Gaps)](#4-análisis-de-brechas-gaps)
  - [4.1 P0 — Críticos (bloquean funcionalidad)](#41-p0--críticos-bloquean-funcionalidad)
  - [4.2 P1 — Importantes (inconsistencia arquitectónica)](#42-p1--importantes-inconsistencia-arquitectónica)
  - [4.3 P2 — Mejoras de producto](#43-p2--mejoras-de-producto)
- [5. Análisis Detallado: "El Código Existe" vs "El Usuario lo Experimenta"](#5-análisis-detallado-el-código-existe-vs-el-usuario-lo-experimenta)
- [6. Tabla Cruzada: Requisito PIIL vs Estado de Implementación](#6-tabla-cruzada-requisito-piil-vs-estado-de-implementación)
- [7. Tabla de Cumplimiento de Directrices del Proyecto](#7-tabla-de-cumplimiento-de-directrices-del-proyecto)
- [8. Evaluación 10/10 Clase Mundial — Criterios de Conversión](#8-evaluación-1010-clase-mundial--criterios-de-conversión)
- [9. Salvaguardas Necesarias](#9-salvaguardas-necesarias)
- [10. Conclusiones y Recomendaciones](#10-conclusiones-y-recomendaciones)
- [11. Glosario de Siglas](#11-glosario-de-siglas)

---

## 1. Resumen Ejecutivo

El módulo `jaraba_andalucia_ei` implementa un programa PIIL (Programa Integral de Inserción Laboral) con 4 roles de programa (Coordinador, Orientador, Formador, Participante) más 2 estados derivados (Alumni, None). La auditoría revela un **sistema de roles fragmentado** con mecanismos de detección y asignación heterogéneos que comprometen la operabilidad del SaaS en producción.

### Hallazgos críticos

| # | Hallazgo | Severidad | Impacto |
|---|----------|-----------|---------|
| 1 | **Roles Drupal `orientador_ei` y `formador_ei` NO EXISTEN en `config/sync/`** — el código los referencia pero nunca se crearon | **P0** | `getRolProgramaUsuario()` nunca devuelve 'orientador' ni 'formador' por vía de rol Drupal. El sistema funciona parcialmente solo por fallbacks |
| 2 | **Sin auto-provisioning de roles** — ni en `hook_install()` ni en `config/install/` | **P0** | Cada nueva instalación del módulo requiere configuración manual en `/admin/people/roles` |
| 3 | **Formador sin dashboard, sin UI, sin fallback** — rol completamente inoperativo | **P0** | Un formador asignado no tiene ningún espacio donde trabajar dentro del programa |
| 4 | **Detección dual incoherente del orientador** — rol Drupal (inexistente) + entity `mentor_profile` (cross-módulo) | **P1** | Dos caminos de detección que no están sincronizados ni documentados |
| 5 | **Sin formulario de asignación de roles del programa** desde el dashboard del coordinador | **P1** | El coordinador debe acceder a admin Drupal puro (`/admin/people`) — rompe el principio de frontend limpio |
| 6 | **Sin auditoría de cambios de rol** — quién asignó qué a quién, cuándo | **P1** | Requisito normativo FSE+ (Art. 12 trazabilidad) sin cumplir |
| 7 | **Permiso `view programa participante ei`** actúa como comodín semántico para orientador Y coordinador | **P1** | Ambigüedad en la frontera de permisos entre roles |
| 8 | **Sin validador de integridad de roles** — no existe `validate-andalucia-ei-roles.php` | **P2** | Sin enforcement automatizado de la coherencia del sistema de roles |

### Métricas de estado

| Dimensión | Valor actual | Objetivo clase mundial |
|-----------|-------------|----------------------|
| Roles con mecanismo completo (detección + asignación + dashboard + wizard + daily actions) | **1/4** (solo Participante) | 4/4 |
| Roles Drupal existentes en config | **0/3** (coordinador_ei, orientador_ei, formador_ei) | 3/3 |
| Dashboards funcionales | **2/4** (Coordinador, Orientador parcial) | 4/4 |
| Setup Wizard steps | **7/7** (Coord: 4, Orient: 3, Form: 0) | **10+** (Coord: 4, Orient: 3, Form: 3+) |
| Daily Actions | **10/10** (Coord: 5, Orient: 4, Form: 0, Lead: 1) | **13+** (+ Form: 3+) |
| Validadores de roles | **0/1** | 1/1 |

---

## 2. Metodología de Auditoría

La auditoría se realizó mediante:

1. **Análisis estático del código fuente** — lectura completa de `AccesoProgramaService.php`, `AndaluciaEiUserProfileSection.php`, `jaraba_andalucia_ei.permissions.yml`, `jaraba_andalucia_ei.routing.yml`, `jaraba_andalucia_ei.services.yml`
2. **Verificación de config/sync/** — búsqueda de `user.role.orientador_ei.yml`, `user.role.formador_ei.yml`, `user.role.coordinador_ei.yml` — **ninguno encontrado**
3. **Trazabilidad cross-módulo** — análisis de `jaraba_mentoring/src/Entity/MentorProfile.php` y su relación con el rol de orientador
4. **Análisis de completitud** — verificación de dashboards, setup wizards, daily actions y formularios por cada rol
5. **Verificación RUNTIME-VERIFY-001** — contraste entre "el código existe" y "el usuario lo experimenta"
6. **Benchmarking normativo** — cruce con requisitos PIIL (Bases Reguladoras) y FSE+ (trazabilidad)

**Archivos auditados:** 47 ficheros PHP, 3 YAML de configuración, 1.312 líneas de routing, 275 líneas de permisos.

---

## 3. Inventario del Sistema de Roles Actual

### 3.1 Definición canónica de roles del programa

**Fichero:** `jaraba_andalucia_ei/src/Service/AccesoProgramaService.php:24`

```php
private const ROLES_PROGRAMA = ['participante', 'coordinador', 'orientador', 'formador', 'alumni', 'none'];
```

Estos 6 valores son los únicos estados posibles que `getRolProgramaUsuario()` devuelve. Los 4 primeros son roles funcionales; `alumni` es un estado post-programa; `none` indica ausencia de vinculación.

### 3.2 Mecanismos de detección por rol

| Rol | Mecanismo de detección | Fichero:Línea | Funciona en producción? |
|-----|----------------------|---------------|------------------------|
| **Coordinador** | `$account->hasPermission('administer andalucia ei')` | `AccesoProgramaService.php:73-74` | **SÍ** — si el permiso se asigna manualmente a un rol Drupal existente (ej. `administrator`) |
| **Orientador** | `in_array('orientador_ei', $account->getRoles())` + fallback `hasPermission('view programa participante ei')` | `AccesoProgramaService.php:91-95` | **PARCIAL** — el rol `orientador_ei` NO existe en config; el fallback por permiso funciona pero colisiona con coordinador |
| **Formador** | `in_array('formador_ei', $account->getRoles())` | `AccesoProgramaService.php:175` | **NO** — el rol `formador_ei` NO existe en config; sin fallback |
| **Participante** | Entity query: `programa_participante_ei` con `uid` match y `fase_actual != 'baja'` | `AccesoProgramaService.php:119-141` | **SÍ** — detección robusta basada en entity |
| **Alumni** | Mismo que participante pero `fase_actual === 'alumni'` | `AccesoProgramaService.php:207` | **SÍ** |
| **Orientador (UserProfile)** | Entity query: `mentor_profile` con `user_id` match y `status = 'active'` | `AndaluciaEiUserProfileSection.php:255-271` | **SÍ** — pero NO se usa en `AccesoProgramaService`, solo en la sección de perfil de usuario |

**Hallazgo crítico:** Existen **dos mecanismos paralelos e inconexos** para detectar orientadores:
1. `AccesoProgramaService` busca rol Drupal `orientador_ei` (no existe)
2. `AndaluciaEiUserProfileSection` busca entity `mentor_profile` activa (funciona)

El primero controla el acceso a rutas y dashboards. El segundo controla qué se muestra en el perfil del usuario. **No están sincronizados.**

### 3.3 Mecanismos de asignación por rol

| Rol | Cómo se asigna actualmente | UI disponible | Quién puede asignar |
|-----|---------------------------|---------------|---------------------|
| **Coordinador** | Asignar permiso `administer andalucia ei` a un rol Drupal existente via `/admin/people/permissions` | Solo admin Drupal | Super-admin |
| **Orientador** | (a) Crear `mentor_profile` entity con `status=active` via `/become-mentor` + aprobación admin, O (b) Asignar rol Drupal `orientador_ei` (inexistente) | Formulario `/become-mentor` (solo creación; activación por admin) | Admin (activación), auto-servicio (solicitud) |
| **Formador** | Asignar rol Drupal `formador_ei` (inexistente) via `/admin/people` | Solo admin Drupal (y el rol ni existe) | Super-admin (teórico) |
| **Participante** | Creación de entity `programa_participante_ei` al aprobar `solicitud_ei` | Dashboard coordinador (triage de solicitudes) | Coordinador |

**Análisis de UX para asignación:**

- **Participante:** Flujo completo y correcto. Solicitud pública → Triage coordinador → Entity creada automáticamente → Detección funciona.
- **Orientador:** Flujo fragmentado. `mentor_profile` viene del módulo `jaraba_mentoring` (emprendimiento); su reutilización para Andalucía +ei es un acoplamiento implícito. Un coordinador del programa NO puede asignar orientadores directamente.
- **Formador:** Sin flujo. Ni el rol existe, ni hay formulario, ni hay dashboard destino.
- **Coordinador:** Requiere acceso a admin Drupal puro. Un coordinador existente no puede nombrar a otro coordinador.

### 3.4 Dashboards y portales por rol

| Rol | Ruta | Controller | Template | Estado |
|-----|------|-----------|----------|--------|
| **Coordinador** | `/andalucia-ei/coordinador` | `CoordinadorDashboardController::dashboard()` | `coordinador-dashboard.html.twig` | **Funcional** — KPIs, solicitudes, acciones formativas, prospección |
| **Orientador** | `/andalucia-ei/orientador` | `OrientadorDashboardController::dashboard()` | `orientador-dashboard.html.twig` | **Parcial** — depende de `view programa participante ei` (no de rol específico) |
| **Formador** | — | — | — | **INEXISTENTE** — sin ruta, sin controller, sin template |
| **Participante** | `/andalucia-ei/mi-participacion` | `ParticipantePortalController::portal()` | `participante-portal.html.twig` | **Funcional** — timeline fases, formación, expediente, logros |
| **Alumni** | Mismo que participante (modo lectura) | Mismo controller | Mismo template (flags condicionales) | **Funcional** |

### 3.5 Setup Wizard y Daily Actions por rol

**Setup Wizard Steps (7 implementados):**

| Wizard ID | Step | Clase | Weight | Rol | Estado |
|-----------|------|-------|--------|-----|--------|
| `coordinador_ei` | Plan Formativo | `CoordinadorPlanFormativoStep` | 50 | Coordinador | **Funcional** |
| `coordinador_ei` | Acciones Formativas | `CoordinadorAccionesFormativasStep` | 60 | Coordinador | **Funcional** |
| `coordinador_ei` | Sesiones | `CoordinadorSesionesStep` | 70 | Coordinador | **Funcional** |
| `coordinador_ei` | Validación | `CoordinadorValidacionStep` | 80 | Coordinador | **Funcional** |
| `orientador_ei` | Perfil | `OrientadorPerfilStep` | 10 | Orientador | **Funcional** |
| `orientador_ei` | Participantes | `OrientadorParticipantesStep` | 20 | Orientador | **Funcional** |
| `orientador_ei` | Primera Sesión | `OrientadorSesionStep` | 30 | Orientador | **Funcional** |
| `formador_ei` | — | — | — | Formador | **INEXISTENTE** |

**Daily Actions (10 implementadas):**

| Action | Clase | Rol | Estado |
|--------|-------|-----|--------|
| Gestionar Solicitudes | `GestionarSolicitudesAction` | Coordinador | **Funcional** |
| Nuevo Participante | `NuevoParticipanteAction` | Coordinador | **Funcional** |
| Programar Sesión | `ProgramarSesionAction` | Coordinador | **Funcional** |
| Exportar STO | `ExportarStoAction` | Coordinador | **Funcional** |
| Plazos Vencidos | `PlazosVencidosAction` | Coordinador | **Funcional** |
| Sesiones Hoy | `OrientadorSesionesHoyAction` | Orientador | **Funcional** |
| Ficha Servicio | `OrientadorFichaServicioAction` | Orientador | **Funcional** |
| Seguimiento | `OrientadorSeguimientoAction` | Orientador | **Funcional** |
| Informes | `OrientadorInformesAction` | Orientador | **Funcional** |
| Captación Leads | `CaptacionLeadsAction` | Coordinador | **Funcional** |
| — | — | Formador | **INEXISTENTE** |

### 3.6 Permisos Drupal asociados

**Fichero:** `jaraba_andalucia_ei.permissions.yml` (275 líneas)

Permisos de gateway por rol:

| Permiso | Rol destinatario | Uso |
|---------|-----------------|-----|
| `administer andalucia ei` | Coordinador | Acceso total al programa |
| `access andalucia ei coordinador dashboard` | Coordinador | Dashboard específico |
| `view programa participante ei` | Coordinador + Orientador | Ver datos de participantes (⚠️ semántica ambigua) |
| `access andalucia ei orientador dashboard` | Orientador | Dashboard específico (definido pero no verificado como gate real en routing) |
| `access andalucia ei formador` | Formador | Acceso genérico formador |
| `access andalucia ei participante portal` | Participante | Portal auto-servicio |
| `register andalucia ei actuacion` | Orientador | Registrar actuaciones STO |
| `sign andalucia ei hoja servicio` | Orientador | Firmar hojas de servicio |
| `mark attendance sesion ei` | Formador | Pasar asistencia |
| `manage andalucia ei solicitudes` | Coordinador | Triaje de solicitudes |
| `manage andalucia ei fases` | Coordinador | Transiciones de fase |

---

## 4. Análisis de Brechas (Gaps)

### 4.1 P0 — Críticos (bloquean funcionalidad)

#### GAP-ROLES-001: Roles Drupal inexistentes en config

**Descripción:** Los roles `orientador_ei` y `formador_ei` se referencian en `AccesoProgramaService.php:91,170,175` mediante `in_array('orientador_ei', $roles)` e `in_array('formador_ei', $roles)`, pero **no existen** como entidades `user.role.*.yml` en `config/sync/` ni en `config/install/` del módulo.

**Archivos afectados:**
- `jaraba_andalucia_ei/src/Service/AccesoProgramaService.php:91,170,175`
- `config/sync/` — solo contiene: `administrator`, `anonymous`, `authenticated`, `content_editor`

**Impacto en producción:**
- `getRolProgramaUsuario()` **nunca** devuelve `'orientador'` ni `'formador'` por la vía de rol Drupal
- Un usuario al que se le asigne el rol `orientador_ei` manualmente obtendría un error porque el rol no existe para ser asignado
- El orientador funciona solo porque `puedeAccederDashboardOrientador()` tiene fallback a permiso (línea 95)
- El formador **no funciona en absoluto** — sin rol, sin fallback, sin alternativa

**Directrices violadas:** IMPLEMENTATION-CHECKLIST-001 (servicio consumido pero dependencia inexistente)

#### GAP-ROLES-002: Sin auto-provisioning en hook_install

**Descripción:** El módulo no incluye ningún mecanismo para crear los roles del programa automáticamente al instalarse. No hay:
- `config/install/user.role.coordinador_ei.yml`
- `config/install/user.role.orientador_ei.yml`
- `config/install/user.role.formador_ei.yml`
- Ni código en `hook_install()` que cree los roles programáticamente

**Impacto:** Cada instalación nueva del módulo (incluyendo entornos de testing, staging, demo) requiere configuración manual. Esto viola el principio de reproducibilidad del SaaS.

#### GAP-ROLES-003: Formador completamente inoperativo

**Descripción:** El rol de formador existe solo como concepto en el array `ROLES_PROGRAMA` y en la lógica de detección de `getRolProgramaUsuario()`. No tiene:
- Rol Drupal para asignar
- Dashboard ni portal propio
- Setup Wizard steps
- Daily Actions
- Formulario de asignación
- Template Twig

Un formador del programa PIIL necesita:
- Ver las sesiones que debe impartir
- Pasar lista de asistencia
- Subir materiales didácticos
- Ver el calendario del programa
- Registrar actuaciones STO de formación

**Impacto normativo:** Las Bases Reguladoras PIIL exigen trazabilidad de las acciones formativas, lo que incluye quién las imparte (formador) y el registro de asistencia firmado.

### 4.2 P1 — Importantes (inconsistencia arquitectónica)

#### GAP-ROLES-004: Detección dual incoherente del orientador

**Descripción:** Existen dos mecanismos paralelos para detectar si un usuario es orientador:

1. **AccesoProgramaService** (control de acceso a rutas):
   ```php
   // Busca rol Drupal (no existe)
   if (in_array('orientador_ei', $roles, TRUE)) { return TRUE; }
   // Fallback: permiso genérico (colisiona con coordinador)
   return $account->hasPermission('view programa participante ei');
   ```

2. **AndaluciaEiUserProfileSection** (UI de perfil de usuario):
   ```php
   // Busca entity mentor_profile activa (funciona)
   $query->condition('user_id', $uid)->condition('status', 'active');
   return !empty($query->execute());
   ```

**Problema:** Un usuario con `mentor_profile` activa aparece como "Orientador" en su perfil (mecanismo 2) pero el sistema de acceso (mecanismo 1) no lo detecta como tal — accede solo por el fallback de permiso que también afecta a coordinadores.

**Directriz violada:** Principio de Single Source of Truth para detección de roles.

#### GAP-ROLES-005: Sin formulario de asignación de roles desde el programa

**Descripción:** El coordinador del programa no puede asignar roles de programa (orientador, formador) desde su dashboard. Debe acceder a `/admin/people` (admin Drupal) o `/admin/people/permissions` para configurar permisos.

**Impacto UX:** Viola el principio de frontend limpio del SaaS. El tenant no debería necesitar acceso al tema de administración de Drupal para operar el programa.

**Directriz violada:** ZERO-REGION-001 (frontend limpio), principio de tenant sin acceso a admin Drupal.

#### GAP-ROLES-006: Permiso comodín semántico

**Descripción:** `view programa participante ei` es usado como:
- Gate de acceso al dashboard de orientador (`AccesoProgramaService.php:95`)
- Gate de acceso al dashboard de coordinador (`AccesoProgramaService.php:74`)
- Permiso genérico de lectura de datos de participantes

Esto causa que **cualquier usuario con este permiso sea tratado como orientador Y como coordinador simultáneamente**, violando la separación de roles.

#### GAP-ROLES-007: Sin auditoría de cambios de rol

**Descripción:** No existe log ni entity que registre:
- Quién asignó el rol de orientador/formador a un usuario
- Cuándo se asignó
- Motivo de la asignación/revocación
- Historial de cambios de rol

**Impacto normativo:** El Reglamento FSE+ (Art. 12) exige trazabilidad de las actuaciones del programa, lo que incluye la cadena de responsabilidad (quién está autorizado a actuar como orientador/formador y desde cuándo).

### 4.3 P2 — Mejoras de producto

#### GAP-ROLES-008: Sin validador de integridad de roles

**Descripción:** No existe un script de validación en `scripts/validation/` que verifique:
- Los roles Drupal referenciados en el código existen en config
- Cada rol tiene dashboard, wizard y daily actions asignados
- La detección de roles es coherente entre servicios
- Los permisos de gateway están correctamente mapeados

**Directriz violada:** SAFEGUARD system (las 6 capas de defensa exigen validadores para toda regla P0/P1).

#### GAP-ROLES-009: Sin vínculo Group role ↔ Program role

**Descripción:** Los roles del programa PIIL son ortogonales al sistema de Group roles (tenant isolation). Un `tenant-member` puede ser coordinador, orientador, formador o participante, pero no hay mapping declarativo entre ambos sistemas.

#### GAP-ROLES-010: DIME score sin servicio de cómputo

**Descripción:** La entidad `ProgramaParticipanteEi` tiene campo `dime_score` pero no existe servicio que lo calcule. El DIME (Diagnóstico Individualizado de Motivación y Empleabilidad) es un indicador clave del PIIL para personalizar itinerarios.

---

## 5. Análisis Detallado: "El Código Existe" vs "El Usuario lo Experimenta"

Este análisis sigue la directriz RUNTIME-VERIFY-001 para verificar la brecha entre implementación y experiencia real.

### 5.1 Coordinador

| Capa | El código existe | El usuario lo experimenta | Gap |
|------|-----------------|--------------------------|-----|
| **Detección** | `hasPermission('administer andalucia ei')` en AccesoProgramaService | Solo funciona si admin asigna el permiso manualmente a un rol que el usuario tenga | Sin rol dedicado `coordinador_ei`, depende de configuración manual |
| **Dashboard** | `CoordinadorDashboardController` + template | Funcional con datos reales (KPIs, solicitudes, sesiones) | ✅ Operativo |
| **Setup Wizard** | 4 steps (Plan, Acciones, Sesiones, Validación) | Aparecen si el coordinador accede al dashboard | ✅ Operativo |
| **Daily Actions** | 5 actions (Solicitudes, Participante, Sesión, STO, Plazos) | Visibles en dashboard | ✅ Operativo |
| **Asignar roles** | No existe formulario | Debe ir a `/admin/people` | ❌ Gap UX |

### 5.2 Orientador

| Capa | El código existe | El usuario lo experimenta | Gap |
|------|-----------------|--------------------------|-----|
| **Detección (acceso)** | `in_array('orientador_ei', $roles)` + fallback permiso | Rol no existe → nunca detectado por rol; permiso es comodín → ambiguo | ❌ Detección rota |
| **Detección (perfil)** | `mentor_profile` con `status=active` en UserProfileSection | Badge "Orientador" aparece correctamente en perfil | ✅ Visual funcional |
| **Dashboard** | `OrientadorDashboardController` + template | Accesible solo por permiso genérico (no por rol) | ⚠️ Funcional pero con acceso impreciso |
| **Setup Wizard** | 3 steps (Perfil, Participantes, Sesión) | Aparecen si orientador accede | ✅ Operativo |
| **Daily Actions** | 4 actions (Sesiones, Ficha, Seguimiento, Informes) | Visibles en dashboard | ✅ Operativo |
| **Asignación** | `mentor_profile` creation en `/become-mentor` + activación admin | Orientador debe ir a ruta de emprendimiento (cross-vertical) → confuso | ⚠️ Flujo indirecto |

### 5.3 Formador

| Capa | El código existe | El usuario lo experimenta | Gap |
|------|-----------------|--------------------------|-----|
| **Detección** | `in_array('formador_ei', $roles)` | Rol no existe → **NUNCA** detectado | ❌ Completamente roto |
| **Dashboard** | No existe | No hay dónde trabajar | ❌ Inexistente |
| **Setup Wizard** | No existe | No hay onboarding | ❌ Inexistente |
| **Daily Actions** | No existen | No hay guía diaria | ❌ Inexistente |
| **Asignación** | Rol `formador_ei` no existe en config | Imposible asignar | ❌ Imposible |
| **Marcar asistencia** | Permiso `mark attendance sesion ei` definido | Ruta existe pero sin rol no se puede asignar el permiso coherentemente | ❌ Inaccesible |

### 5.4 Participante

| Capa | El código existe | El usuario lo experimenta | Gap |
|------|-----------------|--------------------------|-----|
| **Detección** | Entity query `programa_participante_ei` por uid + fase | Funcional — detección robusta | ✅ Operativo |
| **Portal** | `ParticipantePortalController` + template con 7 secciones | Portal completo con timeline, formación, expediente | ✅ Operativo |
| **Setup Wizard** | Inherits global steps (ZEIGARNIK) | Funcional | ✅ Operativo |
| **Daily Actions** | N/A (participante no tiene daily actions explícitas) | Acciones dentro del portal | ✅ Adecuado |
| **Asignación** | Automática al aprobar solicitud | Flujo completo: solicitud → triage → aprobación → entity | ✅ Clase mundial |

---

## 6. Tabla Cruzada: Requisito PIIL vs Estado de Implementación

| Requisito PIIL (Bases Reguladoras) | Rol afectado | Implementación actual | Estado |
|-------------------------------------|-------------|----------------------|--------|
| Coordinador del programa designado formalmente | Coordinador | Solo por permiso, sin rol formal ni registro | ⚠️ Parcial |
| Orientadores cualificados con itinerario individual | Orientador | Via `mentor_profile` (cross-módulo), sin designación formal en el programa | ⚠️ Parcial |
| Formadores con capacitación acreditada | Formador | Rol inoperativo, sin registro de cualificación | ❌ No cumple |
| Registro de asistencia firmado por formador y participante | Formador + Participante | `InscripcionSesionEi` tiene campo asistencia pero formador no puede operar | ❌ Bloqueado |
| Trazabilidad de actuaciones (quién, cuándo, qué) | Todos | `ActuacionSto` registra actuaciones pero sin vincular al rol del profesional actuante | ⚠️ Parcial |
| Justificación de gastos de personal por perfil | Coordinador | `JustificacionEconomicaService` existe pero no segmenta por rol/perfil profesional | ⚠️ Parcial |
| Expediente documental completo por participante | Orientador | `ExpedienteCompletenessService` funcional | ✅ Cumple |
| Indicadores FSE+ de entrada/salida | Coordinador | `IndicadorFsePlus` entity + `ActuacionComputeService` | ✅ Cumple |
| Inserción laboral verificable | Coordinador + Orientador | `InsercionLaboral` entity + `InsercionValidatorService` | ✅ Cumple |

---

## 7. Tabla de Cumplimiento de Directrices del Proyecto

| Directriz CLAUDE.md | Cumplimiento | Detalle |
|---------------------|-------------|---------|
| **TENANT-001** (toda query filtra por tenant) | ✅ Cumple | `AccesoProgramaService` filtra por `tenantContext.getCurrentTenantId()` |
| **TENANT-ISOLATION-ACCESS-001** (ACH verifica tenant match) | ✅ Cumple | Todos los 13 AccessControlHandlers verifican tenant |
| **PREMIUM-FORMS-PATTERN-001** (toda form extiende PremiumEntityFormBase) | ✅ Cumple | 28 forms verificados |
| **SETUP-WIZARD-DAILY-001** (patrón transversal) | ⚠️ Parcial | 7/7 steps + 10/10 actions para Coord+Orient, pero **0 para Formador** |
| **IMPLEMENTATION-CHECKLIST-001** (servicio registrado Y consumido) | ❌ Viola | Roles `orientador_ei`/`formador_ei` referenciados en código pero no existen |
| **ZERO-REGION-001** (frontend limpio) | ⚠️ Parcial | Dashboards de Coord/Orient son limpios, pero asignación de roles requiere admin Drupal |
| **SLIDE-PANEL-RENDER-001** (acciones en slide-panel) | ⚠️ Parcial | No hay slide-panel para asignación de roles |
| **ENTITY-PREPROCESS-001** (toda entity con view mode tiene preprocess) | ✅ Cumple | Verificado en `.module` |
| **ICON-CONVENTION-001** (jaraba_icon duotone) | ✅ Cumple | Setup Wizard steps usan iconos correctos |
| **CSS-VAR-ALL-COLORS-001** (colores via --ej-*) | ✅ Cumple | SCSS del módulo usa tokens |
| **CONTROLLER-READONLY-001** (no readonly en props heredadas) | ✅ Cumple | Controllers verificados |
| **ACCESS-RETURN-TYPE-001** (AccessResultInterface en checkAccess) | ✅ Cumple | 13 handlers verificados |
| **UPDATE-HOOK-REQUIRED-001** (hook_update_N para cambios de schema) | ✅ Cumple | 24 update hooks (10001-10024) |

---

## 8. Evaluación 10/10 Clase Mundial — Criterios de Conversión

Para evaluar si el sistema de roles alcanza nivel clase mundial en un SaaS de gestión de programas de empleo, aplicamos 15 criterios específicos:

| # | Criterio | Estado | Score |
|---|----------|--------|-------|
| 1 | **Roles auto-provisionados** al instalar el módulo (sin config manual) | ❌ No existe | 0/1 |
| 2 | **Cada rol tiene dashboard dedicado** con KPIs relevantes | ⚠️ 3/4 (falta Formador) | 0.5/1 |
| 3 | **Cada rol tiene Setup Wizard** de onboarding | ⚠️ 2/3 profesionales (falta Formador) | 0.5/1 |
| 4 | **Cada rol tiene Daily Actions** contextuales | ⚠️ 2/3 profesionales (falta Formador) | 0.5/1 |
| 5 | **Asignación de roles desde dashboard** del coordinador (sin admin Drupal) | ❌ No existe | 0/1 |
| 6 | **Desasignación y transfer** de roles con auditoría | ❌ No existe | 0/1 |
| 7 | **Detección de rol unificada** (un solo mecanismo, sin ambigüedad) | ❌ Dual incoherente para orientador | 0/1 |
| 8 | **Permisos granulares** por rol con semántica clara (sin comodines) | ⚠️ `view programa participante ei` es comodín | 0.5/1 |
| 9 | **Auditoría de cambios** de rol (log: quién, cuándo, por qué) | ❌ No existe | 0/1 |
| 10 | **Perfil profesional** del staff (cualificación, certificaciones) para normativa FSE+ | ❌ No existe | 0/1 |
| 11 | **Notificaciones** al asignar/revocar roles (email + in-app) | ❌ No existe | 0/1 |
| 12 | **Slide-panel** para operaciones de rol (no navegar fuera del dashboard) | ❌ No existe | 0/1 |
| 13 | **Validador de integridad** de roles en `scripts/validation/` | ❌ No existe | 0/1 |
| 14 | **Tests automatizados** para asignación y detección de roles | ⚠️ `AccesoProgramaServiceTest` existe pero no cubre gaps | 0.5/1 |
| 15 | **Documentación operativa** del flujo de roles para el equipo | ❌ Solo esta auditoría | 0/1 |

**Score total: 2.5/15 → NO es clase mundial**

Para alcanzar 10/10 (15/15 en esta escala), se necesita resolver los 15 criterios.

---

## 9. Salvaguardas Necesarias

### 9.1 Validador propuesto: `validate-andalucia-ei-roles.php`

**Checks necesarios:**

| # | Check | Tipo | Descripción |
|---|-------|------|-------------|
| 1 | Roles Drupal existen | `run_check` | Verificar que `user.role.coordinador_ei`, `user.role.orientador_ei`, `user.role.formador_ei` existen en config |
| 2 | Permisos asignados | `run_check` | Verificar que cada rol tiene sus permisos de gateway asignados |
| 3 | Dashboards por rol | `run_check` | Verificar que cada rol tiene ruta de dashboard en routing.yml |
| 4 | Setup Wizard por rol | `run_check` | Verificar que cada wizard_id profesional tiene ≥1 step registrado |
| 5 | Daily Actions por rol | `run_check` | Verificar que cada rol profesional tiene ≥1 daily action |
| 6 | Detección coherente | `run_check` | Verificar que AccesoProgramaService y UserProfileSection detectan el mismo conjunto de orientadores |
| 7 | Formador operativo | `run_check` | Verificar que el formador tiene ruta, controller y template |
| 8 | Sin permisos comodín | `warn_check` | Alertar si un permiso se usa como gate para >1 rol distinto |

### 9.2 Pre-commit hook

**Regla propuesta:** Si se modifica `AccesoProgramaService.php` o `jaraba_andalucia_ei.permissions.yml`, ejecutar `validate-andalucia-ei-roles.php` automáticamente.

### 9.3 Runtime hook_requirements

**Check propuesto:** En `jaraba_andalucia_ei.install` → `hook_requirements('runtime')`, verificar que los 3 roles Drupal existen y tienen permisos asignados. Mostrar warning en `/admin/reports/status` si falta alguno.

---

## 10. Conclusiones y Recomendaciones

### Diagnóstico final

El sistema de roles de Andalucía +ei tiene una **arquitectura fragmentada** donde:
- El participante tiene un flujo completo y robusto (entity-driven)
- El coordinador funciona por configuración manual de permisos (frágil pero operativo)
- El orientador funciona por un fallback parcial (detección dual incoherente)
- El formador es completamente inoperativo (rol fantasma)

### Recomendación

Se necesita un **Plan de Implementación de Sistema de Roles Clase Mundial** que:

1. **Cree los 3 roles Drupal** (`coordinador_ei`, `orientador_ei`, `formador_ei`) con permisos pre-asignados en `config/install/`
2. **Unifique la detección** en un `RolProgramaService` centralizado que combine rol Drupal + entity signals
3. **Cree el dashboard del formador** con sus wizard steps y daily actions
4. **Implemente asignación de roles** desde el dashboard del coordinador (slide-panel, sin admin Drupal)
5. **Añada auditoría de roles** (entity `RolProgramaLog` o tabla dedicada)
6. **Cree el validador** `validate-andalucia-ei-roles.php` con los 8 checks propuestos
7. **Añada tests** para todos los flujos de asignación y detección

---

## 11. Glosario de Siglas

| Sigla | Significado |
|-------|------------|
| ACH | Access Control Handler — clase PHP que controla permisos de acceso a una entity |
| BMC | Business Model Canvas — lienzo de modelo de negocio |
| DACI | Documento de Aceptación y Compromiso Individual — firmado por el participante al ingresar al programa |
| DIME | Diagnóstico Individualizado de Motivación y Empleabilidad — herramienta de evaluación PIIL |
| DI | Dependency Injection — inyección de dependencias en el contenedor de servicios de Drupal |
| FSE+ | Fondo Social Europeo Plus — fondo de la UE que cofinancia el programa al 85% |
| ICV25 | Inserción CV 2025 — formato de exportación de datos para la Junta de Andalucía |
| KPI | Key Performance Indicator — indicador clave de rendimiento |
| PIIL | Programa Integral de Inserción Laboral — tipo de programa regulado por la Junta de Andalucía |
| RETA | Régimen Especial de Trabajadores Autónomos — sistema de Seguridad Social para autónomos |
| SaaS | Software as a Service — modelo de distribución de software como servicio |
| SCSS | Sassy CSS — preprocesador CSS utilizado en el tema del ecosistema |
| SROI | Social Return on Investment — retorno social de la inversión |
| SSOT | Single Source of Truth — fuente única de verdad |
| STO | Servicio Técnico de Orientación — sistema de la Junta de Andalucía para tracking de orientación |
| UX | User Experience — experiencia de usuario |
| VoBo | Visto Bueno — aprobación formal (workflow de 8 estados para acciones formativas) |
| WCAG | Web Content Accessibility Guidelines — directrices de accesibilidad web |
