# Plan de Implementacion: UserProfileSectionProvider Extensible + Hub Unificado Andalucia +ei

**Fecha de creacion:** 2026-03-06 14:00
**Ultima actualizacion:** 2026-03-06 14:00
**Autor:** IA Asistente (Claude Opus 4.6)
**Version:** 1.0.0
**Estado:** Planificado
**Categoria:** Elevacion Clase Mundial — Arquitectura Extensible + Vertical Andalucia +ei
**Modulos afectados:** `ecosistema_jaraba_core`, `ecosistema_jaraba_theme`, `jaraba_andalucia_ei`, `jaraba_mentoring`, `jaraba_candidate`
**Especificacion referencia:** Diagnostico sesion 2026-03-06 (investigacion hub unificado + deteccion roles programa)
**Prioridad:** P0 (arquitectura extensible) + P1 (hub coordinador + cards perfil)
**Directrices de aplicacion:** ZERO-REGION-001, PREMIUM-FORMS-PATTERN-001, CSS-VAR-ALL-COLORS-001, SCSS-COLORMIX-001, SCSS-COMPILETIME-001, SCSS-001, TENANT-001, TENANT-BRIDGE-001, TENANT-ISOLATION-ACCESS-001, UPDATE-HOOK-REQUIRED-001, OPTIONAL-CROSSMODULE-001, CONTAINER-DEPS-002, LOGGER-INJECT-001, SLIDE-PANEL-RENDER-001, ENTITY-PREPROCESS-001, PRESAVE-RESILIENCE-001, ICON-CONVENTION-001, ICON-DUOTONE-001, ICON-COLOR-001, ROUTE-LANGPREFIX-001, CSRF-API-001, ACCESS-STRICT-001, INNERHTML-XSS-001, CSRF-JS-CACHE-001, FORM-CACHE-001, IMPLEMENTATION-CHECKLIST-001, RUNTIME-VERIFY-001
**Esfuerzo estimado:** 30-40 horas
**Rutas principales:** `/user/{uid}` (perfil), `/andalucia-ei/coordinador` (hub unificado)

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
   - 1.1 [Que se implementa](#11-que-se-implementa)
   - 1.2 [Por que se implementa](#12-por-que-se-implementa)
   - 1.3 [Alcance y exclusiones](#13-alcance-y-exclusiones)
   - 1.4 [Filosofia de implementacion](#14-filosofia-de-implementacion)
2. [Diagnostico del Estado Actual](#2-diagnostico-del-estado-actual)
   - 2.1 [Sistema de perfil de usuario actual](#21-sistema-de-perfil-de-usuario-actual)
   - 2.2 [Hub coordinador actual](#22-hub-coordinador-actual)
   - 2.3 [Deteccion de roles de programa actual](#23-deteccion-de-roles-de-programa-actual)
   - 2.4 [Gaps identificados](#24-gaps-identificados)
3. [Arquitectura Objetivo](#3-arquitectura-objetivo)
   - 3.1 [Patron UserProfileSectionProvider](#31-patron-userprofilesectionprovider)
   - 3.2 [Diagrama de flujo de deteccion de roles](#32-diagrama-de-flujo-de-deteccion-de-roles)
   - 3.3 [Arquitectura del Hub Unificado Coordinador](#33-arquitectura-del-hub-unificado-coordinador)
   - 3.4 [Mapa de servicios y dependencias](#34-mapa-de-servicios-y-dependencias)
4. [Requisitos Previos](#4-requisitos-previos)
5. [Fases de Implementacion](#5-fases-de-implementacion)
   - 5.1 [Fase 1 — UserProfileSectionProvider: Infraestructura Extensible (P0)](#51-fase-1--userprofilesectionprovider-infraestructura-extensible-p0)
   - 5.2 [Fase 2 — Migracion de 5 Secciones Hardcoded a Tagged Services (P0)](#52-fase-2--migracion-de-5-secciones-hardcoded-a-tagged-services-p0)
   - 5.3 [Fase 3 — Andalucia +ei UserProfileSection: Deteccion de Roles (P1)](#53-fase-3--andalucia-ei-userprofilesection-deteccion-de-roles-p1)
   - 5.4 [Fase 4 — Hub Unificado Coordinador: CRUD + Triage (P1)](#54-fase-4--hub-unificado-coordinador-crud--triage-p1)
   - 5.5 [Fase 5 — Hub Unificado Coordinador: Gestion Operativa Avanzada (P1)](#55-fase-5--hub-unificado-coordinador-gestion-operativa-avanzada-p1)
   - 5.6 [Fase 6 — SCSS, Iconografia y Responsive (P1)](#56-fase-6--scss-iconografia-y-responsive-p1)
   - 5.7 [Fase 7 — Tests, Validacion y Runtime Verify (P2)](#57-fase-7--tests-validacion-y-runtime-verify-p2)
6. [Tabla de Correspondencia con Especificaciones Tecnicas](#6-tabla-de-correspondencia-con-especificaciones-tecnicas)
7. [Tabla de Cumplimiento de Directrices del Proyecto](#7-tabla-de-cumplimiento-de-directrices-del-proyecto)
8. [Arquitectura Frontend y Templates](#8-arquitectura-frontend-y-templates)
   - 8.1 [Templates Twig nuevos y modificados](#81-templates-twig-nuevos-y-modificados)
   - 8.2 [Parciales reutilizables](#82-parciales-reutilizables)
   - 8.3 [SCSS y compilacion](#83-scss-y-compilacion)
   - 8.4 [Variables CSS inyectables desde Drupal UI](#84-variables-css-inyectables-desde-drupal-ui)
   - 8.5 [Iconografia](#85-iconografia)
   - 8.6 [Internacionalizacion (i18n)](#86-internacionalizacion-i18n)
9. [Verificacion y Testing](#9-verificacion-y-testing)
   - 9.1 [Tests automatizados](#91-tests-automatizados)
   - 9.2 [Checklist RUNTIME-VERIFY-001](#92-checklist-runtime-verify-001)
   - 9.3 [Checklist IMPLEMENTATION-CHECKLIST-001](#93-checklist-implementation-checklist-001)
10. [Inventario Completo de Ficheros](#10-inventario-completo-de-ficheros)
11. [Recorrido del Usuario (User Journey)](#11-recorrido-del-usuario-user-journey)
12. [Troubleshooting](#12-troubleshooting)
13. [Referencias](#13-referencias)
14. [Registro de Cambios](#14-registro-de-cambios)

---

## 1. Resumen Ejecutivo

### 1.1 Que se implementa

Dos sistemas complementarios que elevan a clase mundial la experiencia de usuario autenticado y la gestion del programa Andalucia +ei:

**A) UserProfileSectionProvider (arquitectura extensible):**
Sistema de secciones extensibles para la pagina de perfil de usuario (`/user/{uid}`) basado en el patron CompilerPass + tagged services, identico al ya probado `TenantSettingsRegistry`. Actualmente las 5 secciones del perfil (Mi Perfil Profesional, Mi Vertical, Mi Negocio, Administracion, Cuenta) estan hardcoded en ~320 lineas dentro de `ecosistema_jaraba_theme_preprocess_page__user()` (lineas 3410-3729 del fichero .theme). Este refactor:

- Extrae cada seccion a un tagged service independiente que implementa `UserProfileSectionInterface`.
- Crea un `UserProfileSectionRegistry` que las recolecta via `UserProfileSectionPass` (CompilerPass).
- Permite a CUALQUIER modulo registrar secciones adicionales sin tocar el theme ni el core.
- Conserva el comportamiento visual actual: la plantilla `page--user.html.twig` ya renderiza `user_quick_sections` correctamente.

**B) Andalucia +ei User Profile Section + Hub Unificado Coordinador:**
- Nueva seccion `AndaluciaEiUserProfileSection` que detecta si el usuario actual tiene uno o mas roles dentro del programa (Participante, Orientador, Coordinador) y muestra una tarjeta contextual con accesos rapidos especificos por rol.
- Elevacion del dashboard coordinador de read-only a hub operativo completo con acciones CRUD via slide-panel: triage de solicitudes, transiciones de fase, asignacion de mentores, exportacion STO, y metricas de impacto.

### 1.2 Por que se implementa

**Deuda arquitectonica critica:**
Las 320 lineas hardcoded en el theme preprocess violan el principio de responsabilidad unica y hacen imposible que los 80+ modulos custom del ecosistema contribuyan secciones al perfil sin editar un fichero monolitico. Cada nueva vertical que quiera mostrar informacion contextual en el perfil requiere tocar `ecosistema_jaraba_theme.theme`, creando riesgo de regresiones.

**Experiencia de usuario incompleta:**
Un participante de Andalucia +ei que accede a su perfil (`/user/{uid}`) no ve NINGUNA referencia a su participacion en el programa. No hay accesos rapidos a su portal, expediente, sesiones de mentoria ni cursos. Debe recordar URLs o navegar manualmente.

**Gestion fragmentada no es clase mundial:**
El coordinador del programa tiene que acceder a 10+ rutas admin separadas para gestionar participantes, solicitudes, expedientes, mentores, sesiones y exportaciones STO. No existe un punto unico de gestion operativa. Las plataformas clase mundial (Salesforce, HubSpot, Monday.com) consolidan todas las acciones en un hub con paneles contextuales.

### 1.3 Alcance y exclusiones

**INCLUIDO:**
- `UserProfileSectionInterface` + `UserProfileSectionRegistry` + `UserProfileSectionPass` en `ecosistema_jaraba_core`
- 5 tagged services para las secciones existentes (migracion sin cambio visual)
- `AndaluciaEiUserProfileSection` con deteccion de 3 roles del programa
- Hub unificado coordinador con 6 paneles de accion (slide-panel CRUD)
- SCSS nuevo para hub coordinador y card de perfil Andalucia +ei
- Tests Unit + Kernel para la infraestructura extensible
- Permisos granulares para acciones del hub

**EXCLUIDO:**
- Cambios en la plantilla `page--user.html.twig` (ya soporta el array `user_quick_sections`)
- Migracion de otros modulos a tagged services (cada vertical lo hara en su propio sprint)
- Desarrollo de un hub para orientadores (se mantiene el dashboard actual)
- Cambios en la deteccion de avatar base (`AvatarDetectionService`)

### 1.4 Filosofia de implementacion

- **"Sin Humo"**: Patron probado (TenantSettingsRegistry), sin sobreingeniera.
- **Compatibilidad total**: Las 5 secciones migradas producen EXACTAMENTE el mismo output que el codigo hardcoded.
- **Zero Region**: Todo el frontend via controllers + preprocess, sin bloques Drupal.
- **Mobile-first**: Hub coordinador responsivo con CSS Grid + `min()`.
- **i18n**: Todos los strings con `t()` / `{% trans %}`. Sin texto hardcoded.

---

## 2. Diagnostico del Estado Actual

### 2.1 Sistema de perfil de usuario actual

**Localizacion:** `ecosistema_jaraba_theme.theme`, funcion `ecosistema_jaraba_theme_preprocess_page__user()`, lineas 3258-3757.

**Estructura actual:**

| Seccion | ID | Condicion de visibilidad | Lineas | Servicios opcionales usados |
|---------|-----|--------------------------|--------|-----------------------------|
| Mi Perfil Profesional | `professional_profile` | Siempre visible | 3443-3538 | `jaraba_candidate.profile_completion` |
| Mi Vertical | `my_vertical` | Si avatar != 'general' | 3540-3608 | `ecosistema_jaraba_core.avatar_detection`, `ecosistema_jaraba_core.avatar_navigation` |
| Mi Negocio | `my_business` | Si tiene tenant | 3610-3650 | `ecosistema_jaraba_core.tenant_context` |
| Administracion | `administration` | Solo rol `administrator` | 3652-3680 | Ninguno |
| Cuenta | `account` | Siempre visible | 3682-3711 | Ninguno |

**Helpers locales** (lineas 3416-3441):
- `$resolve(route, params)`: Resuelve ruta a URL con try-catch.
- `$make_link(label, route, icon_cat, icon_name, color, options)`: Construye link con soporte slide-panel.

**Template:** `page--user.html.twig` ya itera sobre `user_quick_sections` (linea 162-216) con soporte para:
- Icono duotone via `jaraba_icon()`
- Subtitulo opcional
- Widget de completitud de perfil (solo para `professional_profile`)
- Grid de cards con slide-panel
- Cross-vertical badges
- Animacion escalonada via `animation-delay`

**Observacion critica:** El template es AGNOSTICO del contenido de las secciones. Solo necesita el array con la estructura correcta. Esto significa que la migracion a tagged services es transparente para el frontend.

### 2.2 Hub coordinador actual

**Localizacion:** `jaraba_andalucia_ei/src/Controller/CoordinadorDashboardController.php`

**Ruta:** `/andalucia-ei/coordinador` — Permiso: `administer andalucia ei`

**Funcionalidad actual:**
- Estadisticas del programa (total participantes, activos, insertados, tasa de insercion)
- Distribucion por fases (acogida, diagnostico, atencion, insercion, baja)
- Utilizacion de mentores (activos, sesiones, promedios)
- Actividad reciente (ultimos 10 movimientos)
- Contadores de sesiones y solicitudes

**Gaps criticos del hub coordinador:**

| Gap | Descripcion | Impacto |
|-----|-------------|---------|
| HUB-001 | Dashboard 100% read-only, cero acciones | Coordinador NO puede operar desde el hub |
| HUB-002 | No hay triage de solicitudes | Solicitudes requieren ir a `/admin/content/andalucia-ei/solicitudes` |
| HUB-003 | No hay transicion de fases | Cambiar fase requiere editar entity individual en admin |
| HUB-004 | No hay asignacion de mentores | Asignar mentor requiere editar participante en admin |
| HUB-005 | No hay exportacion STO integrada | Requiere ir a `/admin/content/andalucia-ei/export-sto` separadamente |
| HUB-006 | No hay acciones masivas | No hay checkboxes, no hay bulk operations |
| HUB-007 | No hay filtros/busqueda | No se puede filtrar por fase, orientador, fechas |
| HUB-008 | No tiene slide-panel para operaciones CRUD | Navega a paginas admin que rompen el contexto |

### 2.3 Deteccion de roles de programa actual

**No existe.** Actualmente no hay ningun mecanismo que detecte si un usuario Drupal tiene roles dentro del programa Andalucia +ei. La seccion "Mi Vertical" del perfil usa `AvatarDetectionService` que detecta avatar generico (jobseeker, entrepreneur, etc.) pero NO distingue roles especificos de programa.

**Mecanismo de deteccion propuesto (basado en entidades existentes):**

| Rol Programa | Entidad fuente | Condicion | Campo clave |
|-------------|----------------|-----------|-------------|
| Participante | `programa_participante_ei` | `user_id = $uid` AND `fase_actual != 'baja'` | `fase_actual`, `tenant_id` |
| Orientador | `mentor_profile` | `user_id = $uid` AND `status = 'active'` | `specialties`, `tenant_id` |
| Coordinador | Permiso Drupal | `$account->hasPermission('administer andalucia ei')` | — |

Un usuario puede tener multiples roles simultaneamente (ej: un orientador que tambien es coordinador). La card del perfil debe mostrar TODOS los roles detectados con sus respectivos accesos rapidos.

### 2.4 Gaps identificados (resumen)

| ID | Gap | Severidad | Fase |
|----|-----|-----------|------|
| GAP-UPS-001 | Secciones de perfil hardcoded (320 lineas en .theme) | P0 | Fase 1-2 |
| GAP-UPS-002 | No existe patron extensible para modulos contribuyan secciones | P0 | Fase 1 |
| GAP-UPS-003 | No hay deteccion de roles de programa para perfil | P1 | Fase 3 |
| GAP-UPS-004 | Participante no ve nada de Andalucia +ei en perfil | P1 | Fase 3 |
| GAP-UPS-005 | Orientador no ve sus asignaciones en perfil | P1 | Fase 3 |
| GAP-UPS-006 | Coordinador sin hub operativo unificado | P1 | Fase 4-5 |
| GAP-UPS-007 | Cero acciones CRUD en dashboard coordinador | P1 | Fase 4 |
| GAP-UPS-008 | No hay triage de solicitudes integrado | P1 | Fase 4 |
| GAP-UPS-009 | No hay gestion de fases integrada | P1 | Fase 5 |
| GAP-UPS-010 | No hay asignacion de mentores integrada | P1 | Fase 5 |
| GAP-UPS-011 | No hay exportacion STO integrada en hub | P1 | Fase 5 |
| GAP-UPS-012 | Falta SCSS especifico para hub coordinador y card perfil | P1 | Fase 6 |

---

## 3. Arquitectura Objetivo

### 3.1 Patron UserProfileSectionProvider

El patron replica EXACTAMENTE la arquitectura de `TenantSettingsRegistry` que ya esta probada y funcionando en produccion:

```
ecosistema_jaraba_core/
  src/
    UserProfile/
      UserProfileSectionInterface.php   <- Interface (analogo a TenantSettingsSectionInterface)
      UserProfileSectionRegistry.php    <- Registry (analogo a TenantSettingsRegistry)
    DependencyInjection/
      Compiler/
        UserProfileSectionPass.php      <- CompilerPass (analogo a TenantSettingsSectionPass)
  ecosistema_jaraba_core.services.yml   <- Registro del registry service
```

**Interface `UserProfileSectionInterface`:**

```php
interface UserProfileSectionInterface {
  public function getId(): string;
  public function getTitle(): string;
  public function getSubtitle(): string;
  public function getIcon(): array;  // ['category' => 'ui', 'name' => 'user']
  public function getColor(): string; // 'innovation', 'impulse', 'corporate', etc.
  public function getWeight(): int;
  public function getLinks(int $uid): array;
  public function isApplicable(int $uid): bool;
  public function getExtraData(int $uid): array; // Para widgets custom (ej: completitud)
}
```

**Diferencias con `TenantSettingsSectionInterface`:**

| Aspecto | TenantSettings | UserProfile |
|---------|---------------|-------------|
| Parametro clave | Ninguno (sesion) | `$uid` (usuario target) |
| Salida | Una sola ruta | Array de links (multiples cards) |
| Widgets custom | No | Si (`getExtraData` para completitud, badges, etc.) |
| Visibilidad | `isAccessible()` | `isApplicable($uid)` — puede depender de entities |
| Contexto | Admin tenant | Frontend usuario |

**Flujo de datos:**

```
1. Usuario accede a /user/{uid}
2. ecosistema_jaraba_theme_preprocess_page__user() se ejecuta
3. Obtiene UserProfileSectionRegistry via \Drupal::service()
4. Llama registry->getApplicableSections($uid)
5. Cada section evalua isApplicable($uid) → filtra
6. Cada section genera getLinks($uid) + getExtraData($uid)
7. Se construye array $sections con formato identico al actual
8. Se asigna a $variables['user_quick_sections']
9. page--user.html.twig renderiza sin cambios
```

### 3.2 Diagrama de flujo de deteccion de roles

```
Usuario accede a /user/{uid}
       |
       v
AndaluciaEiUserProfileSection::isApplicable($uid)
       |
       +---> Consulta programa_participante_ei WHERE user_id=$uid AND fase_actual!='baja'
       |     ¿Existe? → rol_participante = TRUE, fase = $entity->fase_actual
       |
       +---> Consulta mentor_profile WHERE user_id=$uid AND status='active'
       |     ¿Existe? → rol_orientador = TRUE
       |
       +---> $account->hasPermission('administer andalucia ei')
       |     ¿Si? → rol_coordinador = TRUE
       |
       v
¿Algun rol TRUE? → isApplicable = TRUE
       |
       v
getLinks($uid) genera cards contextuales:
  - Si participante: Mi Portal, Mi Expediente, Mis Sesiones, Informe Progreso
  - Si orientador: Panel Orientador, Mis Participantes, Sesiones Pendientes
  - Si coordinador: Hub Coordinador, Gestion Solicitudes, Export STO, Metricas
```

### 3.3 Arquitectura del Hub Unificado Coordinador

El hub coordinador se transforma de dashboard read-only a centro de operaciones:

```
/andalucia-ei/coordinador
  |
  +-- Panel Principal (metricas KPI en cards)
  |     ├── Total participantes activos
  |     ├── Solicitudes pendientes de triage
  |     ├── Sesiones programadas esta semana
  |     └── Tasa de insercion
  |
  +-- Tab: Solicitudes (triage)
  |     ├── Lista filtrable por estado (pendiente/aprobada/rechazada)
  |     ├── Accion: Aprobar → crea participante (slide-panel)
  |     ├── Accion: Rechazar → motivo (slide-panel)
  |     └── Accion: Solicitar info adicional (slide-panel)
  |
  +-- Tab: Participantes (gestion)
  |     ├── Lista filtrable por fase, orientador, edicion
  |     ├── Accion: Cambiar fase (slide-panel con confirmacion)
  |     ├── Accion: Asignar/cambiar mentor (slide-panel)
  |     ├── Accion: Ver expediente (slide-panel)
  |     └── Accion: Ver detalle completo (slide-panel)
  |
  +-- Tab: Sesiones (seguimiento)
  |     ├── Lista con proximas sesiones y estado
  |     ├── Filtros por mentor, participante, estado
  |     └── Accion: Ver hoja de servicio (slide-panel)
  |
  +-- Tab: Exportacion STO
  |     ├── Generacion de paquete XML
  |     ├── Historial de exportaciones
  |     └── Validacion pre-exportacion
  |
  +-- Tab: Metricas
        ├── Distribucion por fases (donut chart)
        ├── Utilizacion mentores (barras)
        ├── Actividad reciente (timeline)
        └── Evolución temporal (lineas)
```

**Patron frontend del hub:**
- Layout principal: tabs horizontales gestionados por JS vanilla (`Drupal.behaviors.coordinadorHub`)
- Cada tab carga datos via `drupalSettings` (datos iniciales) + API fetch para paginacion
- Acciones CRUD: slide-panel con formularios renderizados via `renderPlain()` (SLIDE-PANEL-RENDER-001)
- URLs de API: SIEMPRE via `drupalSettings` (ROUTE-LANGPREFIX-001)
- CSRF: `_csrf_request_header_token: 'TRUE'` en routing (CSRF-API-001)

### 3.4 Mapa de servicios y dependencias

```
ecosistema_jaraba_core:
  ecosistema_jaraba_core.user_profile_section_registry  (sin deps)
    <- UserProfileSectionPass agrega tagged services

ecosistema_jaraba_core (tagged services para secciones base):
  ecosistema_jaraba_core.user_profile_section.professional_profile
    @? jaraba_candidate.profile_completion
    @? ecosistema_jaraba_core.avatar_detection
  ecosistema_jaraba_core.user_profile_section.my_vertical
    @? ecosistema_jaraba_core.avatar_detection
    @? ecosistema_jaraba_core.avatar_navigation
  ecosistema_jaraba_core.user_profile_section.my_business
    @? ecosistema_jaraba_core.tenant_context
  ecosistema_jaraba_core.user_profile_section.administration
    (sin deps externas)
  ecosistema_jaraba_core.user_profile_section.account
    (sin deps externas)

jaraba_andalucia_ei (tagged service para seccion programa):
  jaraba_andalucia_ei.user_profile_section.andalucia_ei
    @ entity_type.manager
    @? ecosistema_jaraba_core.tenant_context
    tag: ecosistema_jaraba_core.user_profile_section

jaraba_andalucia_ei (servicios hub coordinador):
  jaraba_andalucia_ei.coordinador_hub_service
    @ entity_type.manager
    @? ecosistema_jaraba_core.tenant_context
    @ logger.channel.jaraba_andalucia_ei
```

**Cumplimiento OPTIONAL-CROSSMODULE-001:** Toda dependencia cross-modulo usa `@?`. Solo dependencias del propio modulo o `ecosistema_jaraba_core` usan `@`.

**Cumplimiento CONTAINER-DEPS-002:** No hay ciclos. El flujo es unidireccional: registry <- pass <- tagged services.

---

## 4. Requisitos Previos

| # | Requisito | Estado | Notas |
|---|-----------|--------|-------|
| 1 | `TenantSettingsRegistry` pattern probado en produccion | OK | En uso desde 2026-03-05 |
| 2 | `page--user.html.twig` con soporte `user_quick_sections` | OK | 310 lineas, Zero Region compliant |
| 3 | Entidad `programa_participante_ei` con campo `user_id` | OK | Entity reference a user |
| 4 | Entidad `mentor_profile` con campo `user_id` | OK | Entity reference a user |
| 5 | Permiso `administer andalucia ei` definido | OK | `jaraba_andalucia_ei.permissions.yml` |
| 6 | Rutas existentes del coordinador y participante | OK | `jaraba_andalucia_ei.routing.yml` (37 rutas) |
| 7 | `StoExportService` funcional | OK | Con `StoExportController` |
| 8 | `FaseTransitionManager` funcional | OK | Gestiona transiciones de fases |
| 9 | Dart Sass instalado en theme | OK | `npm run build` desde theme dir |
| 10 | CI pipeline con validacion de tenant isolation | OK | 15/15 PASS |

---

## 5. Fases de Implementacion

### 5.1 Fase 1 — UserProfileSectionProvider: Infraestructura Extensible (P0)

**Objetivo:** Crear la infraestructura reutilizable: interface, registry y CompilerPass.

**Duracion estimada:** 3-4 horas

#### 5.1.1 UserProfileSectionInterface

**Fichero:** `web/modules/custom/ecosistema_jaraba_core/src/UserProfile/UserProfileSectionInterface.php`

```php
<?php
declare(strict_types=1);
namespace Drupal\ecosistema_jaraba_core\UserProfile;

interface UserProfileSectionInterface {
  // Identificador unico de la seccion (ej: 'professional_profile')
  public function getId(): string;

  // Titulo visible traducido (ej: 'Mi Perfil Profesional')
  public function getTitle(int $uid): string;

  // Subtitulo breve traducido
  public function getSubtitle(int $uid): string;

  // Icono para jaraba_icon() — ICON-CONVENTION-001
  // Return ['category' => 'ui', 'name' => 'user']
  public function getIcon(): array;

  // Color semantico de la seccion (innovation, impulse, corporate, neutral, etc.)
  public function getColor(): string;

  // Peso para ordenar (menor = primero). Rango recomendado: 0-100
  public function getWeight(): int;

  // Array de links contextuales para el usuario dado.
  // Cada link: ['label'=>, 'url'=>, 'icon_category'=>, 'icon_name'=>,
  //             'color'=>, 'description'=>, 'slide_panel'=>bool,
  //             'slide_panel_title'=>, 'cross_vertical'=>bool]
  // Las URLs DEBEN resolverse via Url::fromRoute() (ROUTE-LANGPREFIX-001)
  public function getLinks(int $uid): array;

  // Determina si esta seccion es aplicable/visible para el usuario dado.
  // Evaluacion lazy: solo se llama getLinks() si isApplicable() devuelve TRUE.
  public function isApplicable(int $uid): bool;

  // Datos extra para widgets custom (completitud, badges, contadores).
  // El template page--user.html.twig los consume bajo $section['extra_key'].
  // Default: [] (sin datos extra)
  public function getExtraData(int $uid): array;
}
```

**Decisiones de diseno:**
- El parametro `$uid` se pasa a `getTitle`, `getSubtitle`, `getLinks`, `isApplicable` y `getExtraData` porque el contenido puede variar segun el usuario visualizado (ej: titulo "Participante — Fase Insercion" vs "Coordinador del Programa").
- `getIcon()` y `getColor()` NO reciben `$uid` porque son propiedades estaticas de la seccion.
- `getLinks()` retorna array ya resuelto con URLs string (no objetos Url), listo para consumir en Twig.

#### 5.1.2 UserProfileSectionRegistry

**Fichero:** `web/modules/custom/ecosistema_jaraba_core/src/UserProfile/UserProfileSectionRegistry.php`

```php
<?php
declare(strict_types=1);
namespace Drupal\ecosistema_jaraba_core\UserProfile;

class UserProfileSectionRegistry {
  protected array $sections = [];

  public function addSection(UserProfileSectionInterface $section): void {
    $this->sections[$section->getId()] = $section;
  }

  // Devuelve secciones aplicables al usuario, ordenadas por peso.
  public function getApplicableSections(int $uid): array {
    $applicable = array_filter(
      $this->sections,
      fn(UserProfileSectionInterface $s) => $s->isApplicable($uid)
    );
    usort($applicable, fn($a, $b) => $a->getWeight() <=> $b->getWeight());
    return $applicable;
  }

  // Construye el array completo para $variables['user_quick_sections'].
  // Output identico al formato actual del preprocess.
  public function buildSectionsArray(int $uid): array {
    $result = [];
    foreach ($this->getApplicableSections($uid) as $section) {
      $links = $section->getLinks($uid);
      if (empty($links)) {
        continue; // No mostrar secciones sin links resueltos
      }
      $entry = [
        'id' => $section->getId(),
        'title' => $section->getTitle($uid),
        'subtitle' => $section->getSubtitle($uid),
        'icon_category' => $section->getIcon()['category'] ?? 'ui',
        'icon_name' => $section->getIcon()['name'] ?? 'info',
        'links' => array_values($links),
        'color' => $section->getColor(),
      ];
      $extraData = $section->getExtraData($uid);
      if (!empty($extraData)) {
        $entry = array_merge($entry, $extraData);
      }
      $result[] = $entry;
    }
    return $result;
  }

  public function getAllSections(): array {
    return $this->sections;
  }

  public function getSection(string $id): ?UserProfileSectionInterface {
    return $this->sections[$id] ?? NULL;
  }
}
```

**Metodo clave `buildSectionsArray()`:** Produce EXACTAMENTE el mismo formato que consume `page--user.html.twig`, garantizando compatibilidad total. El template no necesita cambios.

#### 5.1.3 UserProfileSectionPass (CompilerPass)

**Fichero:** `web/modules/custom/ecosistema_jaraba_core/src/DependencyInjection/Compiler/UserProfileSectionPass.php`

```php
<?php
declare(strict_types=1);
namespace Drupal\ecosistema_jaraba_core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class UserProfileSectionPass implements CompilerPassInterface {
  public function process(ContainerBuilder $container): void {
    if (!$container->has('ecosistema_jaraba_core.user_profile_section_registry')) {
      return;
    }
    $definition = $container->findDefinition('ecosistema_jaraba_core.user_profile_section_registry');
    $taggedServices = $container->findTaggedServiceIds('ecosistema_jaraba_core.user_profile_section');
    foreach ($taggedServices as $id => $tags) {
      $definition->addMethodCall('addSection', [new Reference($id)]);
    }
  }
}
```

#### 5.1.4 Registro en services.yml y EcosistemaJarabaCoreServiceProvider

**Adicion a `ecosistema_jaraba_core.services.yml`:**

```yaml
ecosistema_jaraba_core.user_profile_section_registry:
  class: Drupal\ecosistema_jaraba_core\UserProfile\UserProfileSectionRegistry
```

**Adicion al ServiceProvider** (o fichero existente que registra compiler passes):
El CompilerPass `UserProfileSectionPass` debe registrarse en el metodo `register()` del `EcosistemaJarabaCoreServiceProvider`, de la misma forma que `TenantSettingsSectionPass`.

#### 5.1.5 Tests

- **Unit test:** `UserProfileSectionRegistryTest` — verifica addSection, getApplicableSections (filtrado + orden), buildSectionsArray (formato output).
- **Kernel test:** Verifica que el CompilerPass inyecta servicios taggeados correctamente.

---

### 5.2 Fase 2 — Migracion de 5 Secciones Hardcoded a Tagged Services (P0)

**Objetivo:** Migrar las 5 secciones actuales del preprocess a tagged services sin cambio visual.

**Duracion estimada:** 6-8 horas

#### 5.2.1 Seccion 1: ProfessionalProfileSection (peso 10)

**Fichero:** `web/modules/custom/ecosistema_jaraba_core/src/UserProfile/Section/ProfessionalProfileSection.php`

**Logica migrada desde:** Lineas 3443-3538 del .theme

**Servicios opcionales:**
- `@?jaraba_candidate.profile_completion` — para el widget de completitud

**Implementacion clave:**
- `isApplicable()`: Siempre `TRUE` (seccion universal).
- `getLinks()`: Constructor CV, Ver perfil profesional, Autoconocimiento — resueltos via `Url::fromRoute()`.
- `getExtraData()`: Si el servicio de completitud existe, devuelve `['profile_completeness' => [...]]` con porcentaje, secciones, CTA message.

**Compatibilidad especial:** El template `page--user.html.twig` (linea 177) busca especificamente `section.id == 'professional_profile'` y `section.profile_completeness` para renderizar el widget de completitud. La seccion DEBE mantener este ID y formato de datos.

#### 5.2.2 Seccion 2: MyVerticalSection (peso 20)

**Fichero:** `web/modules/custom/ecosistema_jaraba_core/src/UserProfile/Section/MyVerticalSection.php`

**Logica migrada desde:** Lineas 3540-3608 del .theme

**Servicios opcionales:**
- `@?ecosistema_jaraba_core.avatar_detection`
- `@?ecosistema_jaraba_core.avatar_navigation`

**Implementacion clave:**
- `isApplicable()`: Detecta avatar via `AvatarDetectionService::detect()`. Retorna `TRUE` solo si avatar != 'general'.
- `getTitle()`: Dinamico segun avatar — `t('Mi Vertical: @label', ['@label' => $avatarLabel])`.
- `getLinks()`: Los obtiene de `AvatarNavigationService::getNavigationItems()`, mapeando al formato estandar.

#### 5.2.3 Seccion 3: MyBusinessSection (peso 30)

**Fichero:** `web/modules/custom/ecosistema_jaraba_core/src/UserProfile/Section/MyBusinessSection.php`

**Logica migrada desde:** Lineas 3610-3650 del .theme

**Servicios opcionales:**
- `@?ecosistema_jaraba_core.tenant_context`

**Implementacion clave:**
- `isApplicable()`: Verifica que el usuario tenga tenant activo via `TenantContextService::getCurrentTenant()`.
- `getLinks()`: Dashboard Tenant, Configuracion, Cambiar plan, Centro de ayuda.

#### 5.2.4 Seccion 4: AdministrationSection (peso 80)

**Fichero:** `web/modules/custom/ecosistema_jaraba_core/src/UserProfile/Section/AdministrationSection.php`

**Logica migrada desde:** Lineas 3652-3680 del .theme

**Implementacion clave:**
- `isApplicable()`: `$this->currentUser->hasRole('administrator')`.
- `getLinks()`: Admin Center, Gestion Tenants, Gestion Usuarios, Finanzas.
- No usa servicios opcionales cross-modulo.

#### 5.2.5 Seccion 5: AccountSection (peso 100)

**Fichero:** `web/modules/custom/ecosistema_jaraba_core/src/UserProfile/Section/AccountSection.php`

**Logica migrada desde:** Lineas 3682-3711 del .theme

**Implementacion clave:**
- `isApplicable()`: Siempre `TRUE` (seccion universal).
- `getLinks()`: Editar perfil (con slide-panel), Cerrar sesion.
- **Nota:** El link "Cerrar sesion" usa URL hardcoded `/user/logout` en el codigo actual. En la migracion, usar `Url::fromRoute('user.logout')` para cumplir ROUTE-LANGPREFIX-001.

#### 5.2.6 Registro en services.yml

```yaml
# ecosistema_jaraba_core.services.yml
ecosistema_jaraba_core.user_profile_section.professional_profile:
  class: Drupal\ecosistema_jaraba_core\UserProfile\Section\ProfessionalProfileSection
  arguments:
    - '@?jaraba_candidate.profile_completion'
  tags:
    - { name: ecosistema_jaraba_core.user_profile_section }

ecosistema_jaraba_core.user_profile_section.my_vertical:
  class: Drupal\ecosistema_jaraba_core\UserProfile\Section\MyVerticalSection
  arguments:
    - '@?ecosistema_jaraba_core.avatar_detection'
    - '@?ecosistema_jaraba_core.avatar_navigation'
  tags:
    - { name: ecosistema_jaraba_core.user_profile_section }

ecosistema_jaraba_core.user_profile_section.my_business:
  class: Drupal\ecosistema_jaraba_core\UserProfile\Section\MyBusinessSection
  arguments:
    - '@?ecosistema_jaraba_core.tenant_context'
  tags:
    - { name: ecosistema_jaraba_core.user_profile_section }

ecosistema_jaraba_core.user_profile_section.administration:
  class: Drupal\ecosistema_jaraba_core\UserProfile\Section\AdministrationSection
  arguments:
    - '@current_user'
  tags:
    - { name: ecosistema_jaraba_core.user_profile_section }

ecosistema_jaraba_core.user_profile_section.account:
  class: Drupal\ecosistema_jaraba_core\UserProfile\Section\AccountSection
  tags:
    - { name: ecosistema_jaraba_core.user_profile_section }
```

#### 5.2.7 Refactor del preprocess

La funcion `ecosistema_jaraba_theme_preprocess_page__user()` se simplifica:

**Antes (lineas 3410-3729 — 320 lineas de secciones hardcoded):**
```php
// -- Quick Access Sections ------------------------------------------------
$sections = [];
// ... 320 lineas de logica hardcoded ...
$variables['user_quick_sections'] = $sections;
```

**Despues (~15 lineas):**
```php
// -- Quick Access Sections (via UserProfileSectionRegistry) ----------------
if (\Drupal::hasService('ecosistema_jaraba_core.user_profile_section_registry')) {
  try {
    $registry = \Drupal::service('ecosistema_jaraba_core.user_profile_section_registry');
    $variables['user_quick_sections'] = $registry->buildSectionsArray((int) $uid);
  }
  catch (\Throwable $e) {
    \Drupal::logger('ecosistema_jaraba_theme')->error('Error building profile sections: @error', [
      '@error' => $e->getMessage(),
    ]);
    $variables['user_quick_sections'] = [];
  }
}
else {
  $variables['user_quick_sections'] = [];
}
```

**Nota critica:** El bloque de backward compatibility (lineas 3722-3729) que aplana links en `user_quick_links` tambien debe mantenerse despues del nuevo bloque.

#### 5.2.8 Verificacion de paridad

Antes de considerar esta fase completa, se debe verificar que el output de `buildSectionsArray()` es BIT-A-BIT identico al output del codigo hardcoded para al menos 5 escenarios:

1. Usuario anonimo con perfil publico
2. Usuario autenticado basico (solo cuenta)
3. Usuario con avatar jobseeker + tenant
4. Usuario con avatar entrepreneur + tenant + admin
5. Usuario administrador global

---

### 5.3 Fase 3 — Andalucia +ei UserProfileSection: Deteccion de Roles (P1)

**Objetivo:** Crear la seccion de perfil que detecta roles del programa y muestra cards contextuales.

**Duracion estimada:** 5-6 horas

#### 5.3.1 AndaluciaEiUserProfileSection

**Fichero:** `web/modules/custom/jaraba_andalucia_ei/src/UserProfile/AndaluciaEiUserProfileSection.php`

**Clase:**
```php
<?php
declare(strict_types=1);
namespace Drupal\jaraba_andalucia_ei\UserProfile;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\ecosistema_jaraba_core\UserProfile\UserProfileSectionInterface;

class AndaluciaEiUserProfileSection implements UserProfileSectionInterface {
  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $currentUser,
    protected readonly ?TenantContextService $tenantContext = NULL,
  ) {}

  public function getId(): string { return 'andalucia_ei_programa'; }
  public function getIcon(): array { return ['category' => 'verticals', 'name' => 'andalucia-ei']; }
  public function getColor(): string { return 'andalucia'; }
  public function getWeight(): int { return 15; } // Entre professional_profile(10) y my_vertical(20)

  public function isApplicable(int $uid): bool {
    // Al menos uno de los 3 roles detectados.
    return $this->isParticipante($uid)
        || $this->isOrientador($uid)
        || $this->isCoordinador();
  }
  // ... (deteccion de roles y generacion de links segun seccion 3.2)
}
```

**Links por rol:**

| Rol | Link | Ruta | Icono | Color |
|-----|------|------|-------|-------|
| Participante | Mi Portal | `jaraba_andalucia_ei.participante_portal` | `general/user` | andalucia |
| Participante | Mi Expediente | `jaraba_andalucia_ei.expediente_hub` | `general/folder` | andalucia |
| Participante | Mis Sesiones | (nueva ruta o existente) | `general/calendar` | andalucia |
| Participante | Informe Progreso | `jaraba_andalucia_ei.informe_progreso_pdf` | `general/file-text` | andalucia |
| Orientador | Panel Orientador | `jaraba_andalucia_ei.orientador_dashboard` | `business/briefcase` | andalucia |
| Orientador | Mis Participantes | `entity.programa_participante_ei.collection` | `general/users` | andalucia |
| Coordinador | Hub Coordinador | `jaraba_andalucia_ei.coordinador_dashboard` | `ui/dashboard` | andalucia |
| Coordinador | Gestion Solicitudes | `entity.solicitud_ei.collection` | `general/inbox` | andalucia |
| Coordinador | Exportar STO | `jaraba_andalucia_ei.sto_export` | `general/download` | andalucia |

**Titulo dinamico por roles:**
- Solo participante: "Andalucia +ei — Fase: {fase_actual}"
- Solo orientador: "Andalucia +ei — Orientador"
- Solo coordinador: "Andalucia +ei — Coordinacion"
- Multiples: "Andalucia +ei — {rol1}, {rol2}" (ej: "Orientador, Coordinador")

**Extra data para participante:** Badge de fase con color contextual (acogida=gris, diagnostico=azul, atencion=naranja, insercion=verde).

#### 5.3.2 Registro en services.yml

```yaml
# jaraba_andalucia_ei.services.yml
jaraba_andalucia_ei.user_profile_section.andalucia_ei:
  class: Drupal\jaraba_andalucia_ei\UserProfile\AndaluciaEiUserProfileSection
  arguments:
    - '@entity_type.manager'
    - '@current_user'
    - '@?ecosistema_jaraba_core.tenant_context'
  tags:
    - { name: ecosistema_jaraba_core.user_profile_section }
```

**Cumplimiento OPTIONAL-CROSSMODULE-001:** `@?ecosistema_jaraba_core.tenant_context` es opcional.

#### 5.3.3 Deteccion de roles (metodos privados)

```php
private function isParticipante(int $uid): bool {
  try {
    $storage = $this->entityTypeManager->getStorage('programa_participante_ei');
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('user_id', $uid)
      ->condition('fase_actual', 'baja', '!=')
      ->range(0, 1);
    $this->addTenantCondition($query);
    return !empty($query->execute());
  }
  catch (\Throwable) { return FALSE; }
}

private function isOrientador(int $uid): bool {
  try {
    if (!$this->entityTypeManager->hasDefinition('mentor_profile')) {
      return FALSE;
    }
    $storage = $this->entityTypeManager->getStorage('mentor_profile');
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('user_id', $uid)
      ->condition('status', 'active')
      ->range(0, 1);
    $this->addTenantCondition($query);
    return !empty($query->execute());
  }
  catch (\Throwable) { return FALSE; }
}

private function isCoordinador(): bool {
  return $this->currentUser->hasPermission('administer andalucia ei');
}
```

**Cumplimiento TENANT-001:** Todas las queries filtran por tenant via `addTenantCondition()`.

**Cumplimiento PRESAVE-RESILIENCE-001:** try-catch con `\Throwable` protege contra entities no instaladas.

---

### 5.4 Fase 4 — Hub Unificado Coordinador: CRUD + Triage (P1)

**Objetivo:** Transformar el dashboard coordinador de read-only a hub operativo con triage de solicitudes y gestion de participantes.

**Duracion estimada:** 8-10 horas

#### 5.4.1 Nuevo servicio: CoordinadorHubService

**Fichero:** `web/modules/custom/jaraba_andalucia_ei/src/Service/CoordinadorHubService.php`

Servicio que consolida queries y acciones del hub coordinador. Separa la logica de negocio del controller.

**Metodos principales:**

| Metodo | Descripcion | Parametros |
|--------|-------------|------------|
| `getPendingSolicitudes()` | Solicitudes en estado 'pendiente' | `?int $tenantId, int $limit = 20, int $offset = 0` |
| `getActiveParticipants()` | Participantes con filtros | `array $filters, ?int $tenantId, int $limit = 20` |
| `approveSolicitud()` | Aprueba y crea participante | `int $solicitudId` |
| `rejectSolicitud()` | Rechaza con motivo | `int $solicitudId, string $reason` |
| `changeParticipantPhase()` | Transicion de fase | `int $participanteId, string $newPhase` |
| `assignMentor()` | Asigna mentor a participante | `int $participanteId, int $mentorProfileId` |
| `getHubKpis()` | Metricas KPI para las cards | `?int $tenantId` |
| `getUpcomingSessions()` | Sesiones proximas con detalle | `?int $tenantId, int $days = 7` |

**Todas las queries filtran por tenant (TENANT-001).**

#### 5.4.2 Refactor del CoordinadorDashboardController

El controller actual se refactoriza para:

1. **Inyectar** `CoordinadorHubService` + `TenantContextService` (si no lo tiene ya)
2. **Servir datos iniciales** via `drupalSettings` (metricas KPI, primera pagina de solicitudes, primera pagina de participantes)
3. **Nuevas rutas API** para operaciones CRUD (slide-panel)

**Nuevas rutas:**

```yaml
# jaraba_andalucia_ei.routing.yml — Adiciones para Hub Coordinador

# --- API: Hub Coordinador ---
jaraba_andalucia_ei.api.hub.solicitudes:
  path: '/api/v1/andalucia-ei/hub/solicitudes'
  defaults:
    _controller: 'Drupal\jaraba_andalucia_ei\Controller\CoordinadorHubApiController::listSolicitudes'
  methods: [GET]
  requirements:
    _permission: 'administer andalucia ei'
    _format: 'json'

jaraba_andalucia_ei.api.hub.solicitud.approve:
  path: '/api/v1/andalucia-ei/hub/solicitud/{id}/approve'
  defaults:
    _controller: 'Drupal\jaraba_andalucia_ei\Controller\CoordinadorHubApiController::approveSolicitud'
  methods: [POST]
  requirements:
    _permission: 'administer andalucia ei'
    _csrf_request_header_token: 'TRUE'
    id: '\d+'

jaraba_andalucia_ei.api.hub.solicitud.reject:
  path: '/api/v1/andalucia-ei/hub/solicitud/{id}/reject'
  defaults:
    _controller: 'Drupal\jaraba_andalucia_ei\Controller\CoordinadorHubApiController::rejectSolicitud'
  methods: [POST]
  requirements:
    _permission: 'administer andalucia ei'
    _csrf_request_header_token: 'TRUE'
    id: '\d+'

jaraba_andalucia_ei.api.hub.participant.change_phase:
  path: '/api/v1/andalucia-ei/hub/participant/{id}/phase'
  defaults:
    _controller: 'Drupal\jaraba_andalucia_ei\Controller\CoordinadorHubApiController::changePhase'
  methods: [POST]
  requirements:
    _permission: 'administer andalucia ei'
    _csrf_request_header_token: 'TRUE'
    id: '\d+'

jaraba_andalucia_ei.api.hub.participant.assign_mentor:
  path: '/api/v1/andalucia-ei/hub/participant/{id}/mentor'
  defaults:
    _controller: 'Drupal\jaraba_andalucia_ei\Controller\CoordinadorHubApiController::assignMentor'
  methods: [POST]
  requirements:
    _permission: 'administer andalucia ei'
    _csrf_request_header_token: 'TRUE'
    id: '\d+'

jaraba_andalucia_ei.api.hub.participants:
  path: '/api/v1/andalucia-ei/hub/participants'
  defaults:
    _controller: 'Drupal\jaraba_andalucia_ei\Controller\CoordinadorHubApiController::listParticipants'
  methods: [GET]
  requirements:
    _permission: 'administer andalucia ei'
    _format: 'json'

jaraba_andalucia_ei.api.hub.sessions:
  path: '/api/v1/andalucia-ei/hub/sessions'
  defaults:
    _controller: 'Drupal\jaraba_andalucia_ei\Controller\CoordinadorHubApiController::listSessions'
  methods: [GET]
  requirements:
    _permission: 'administer andalucia ei'
    _format: 'json'

jaraba_andalucia_ei.api.hub.kpis:
  path: '/api/v1/andalucia-ei/hub/kpis'
  defaults:
    _controller: 'Drupal\jaraba_andalucia_ei\Controller\CoordinadorHubApiController::getKpis'
  methods: [GET]
  requirements:
    _permission: 'administer andalucia ei'
    _format: 'json'
```

#### 5.4.3 Nuevo controller: CoordinadorHubApiController

**Fichero:** `web/modules/custom/jaraba_andalucia_ei/src/Controller/CoordinadorHubApiController.php`

Controller dedicado a las operaciones API del hub. Separado del controller de renderizado HTML (`CoordinadorDashboardController`) para mantener responsabilidad unica.

**Patron de respuesta:** `JsonResponse` con estructura estandar:
```json
{
  "success": true,
  "data": { ... },
  "message": "Solicitud aprobada correctamente"
}
```

**Seguridad:**
- Todas las rutas POST con `_csrf_request_header_token: 'TRUE'` (CSRF-API-001)
- Permiso `administer andalucia ei` en todas las rutas
- Validacion de input: IDs numericos, fases contra lista permitida, longitud de motivos de rechazo
- Logging de todas las acciones via `LoggerInterface`

#### 5.4.4 Template del Hub

**Fichero:** Modificar `templates/andalucia-ei-coordinador-dashboard.html.twig` (existente)

**Estructura de tabs:**

```twig
<div class="hub-coordinador">
  {# KPI Cards #}
  <div class="hub-kpis">
    {% for kpi in kpis %}
      <div class="hub-kpi-card hub-kpi-card--{{ kpi.color }}">
        {{ jaraba_icon(kpi.icon_category, kpi.icon_name, { size: '32px', variant: 'duotone' }) }}
        <span class="hub-kpi-card__value">{{ kpi.value }}</span>
        <span class="hub-kpi-card__label">{{ kpi.label }}</span>
      </div>
    {% endfor %}
  </div>

  {# Tab Navigation #}
  <nav class="hub-tabs" role="tablist" aria-label="{% trans %}Secciones del hub{% endtrans %}">
    <button class="hub-tab hub-tab--active" role="tab" data-tab="solicitudes">
      {% trans %}Solicitudes{% endtrans %}
      {% if pending_count > 0 %}
        <span class="hub-tab__badge">{{ pending_count }}</span>
      {% endif %}
    </button>
    <button class="hub-tab" role="tab" data-tab="participantes">{% trans %}Participantes{% endtrans %}</button>
    <button class="hub-tab" role="tab" data-tab="sesiones">{% trans %}Sesiones{% endtrans %}</button>
    <button class="hub-tab" role="tab" data-tab="exportacion">{% trans %}Exportacion STO{% endtrans %}</button>
    <button class="hub-tab" role="tab" data-tab="metricas">{% trans %}Metricas{% endtrans %}</button>
  </nav>

  {# Tab Panels #}
  <div class="hub-panels">
    {# Cada panel se renderiza aqui #}
  </div>
</div>
```

**Accesibilidad:**
- `role="tablist"` en navegacion
- `role="tab"` + `aria-selected` en botones
- `role="tabpanel"` + `aria-labelledby` en paneles
- Focus management con JS (flechas izq/der para navegar tabs)
- ARIA live region para notificaciones de acciones

---

### 5.5 Fase 5 — Hub Unificado Coordinador: Gestion Operativa Avanzada (P1)

**Objetivo:** Completar el hub con funcionalidades avanzadas de gestion.

**Duracion estimada:** 5-6 horas

#### 5.5.1 Panel de Solicitudes (Triage)

**Funcionalidad completa:**
- Lista de solicitudes con columnas: Nombre, Email, Fecha solicitud, Estado, Acciones
- Filtros: estado (pendiente/aprobada/rechazada/info_adicional), rango de fechas
- Accion "Aprobar": Abre slide-panel con resumen de solicitud + boton de confirmacion. Al confirmar:
  1. Cambia estado solicitud a 'aprobada'
  2. Crea entidad `programa_participante_ei` con datos de la solicitud
  3. Asigna tenant_id
  4. Envia email de bienvenida al participante (si `jaraba_email` disponible)
- Accion "Rechazar": Slide-panel con textarea para motivo. Al confirmar:
  1. Cambia estado solicitud a 'rechazada'
  2. Guarda motivo en campo `rejection_reason`
  3. Envia email de rechazo (si `jaraba_email` disponible)
- Accion "Solicitar info": Slide-panel con textarea. Cambia estado a 'info_adicional'.

#### 5.5.2 Panel de Participantes

**Funcionalidad completa:**
- Lista con columnas: Nombre, Fase, Orientador, Horas totales, Ultima actividad, Acciones
- Filtros: fase, orientador asignado, busqueda por nombre
- Accion "Cambiar fase": Slide-panel con selector de fase + confirmacion. Usa `FaseTransitionManager` existente.
- Accion "Asignar mentor": Slide-panel con lista de mentores activos (filtrados por tenant). Muestra carga actual de cada mentor.
- Accion "Ver expediente": Slide-panel con resumen de documentos del expediente.
- Accion "Ver detalle": Slide-panel con todos los datos del participante.

#### 5.5.3 Panel de Sesiones

**Funcionalidad:**
- Lista de sesiones de mentoria de la semana/mes
- Columnas: Fecha, Mentor, Participante, Estado, Hoja de servicio
- Filtros: rango de fechas, mentor, estado
- Accion "Ver hoja de servicio": Slide-panel con PDF preview

#### 5.5.4 Panel de Exportacion STO

**Funcionalidad:**
- Formulario de generacion de exportacion (reutiliza `StoExportService`)
- Selector de periodo y edicion
- Validacion pre-exportacion (participantes con datos incompletos)
- Historial de exportaciones previas (via State API)

#### 5.5.5 Panel de Metricas

**Funcionalidad:**
- Reutiliza datos del controller actual (`buildProgramStats`, `getPhaseDistribution`, etc.)
- Renderizado con CSS puro (barras de progreso, donut charts con conic-gradient)
- NO dependencias JS externas (no Chart.js, no D3) — "Sin Humo"
- Actividad reciente como timeline con iconos contextuales

---

### 5.6 Fase 6 — SCSS, Iconografia y Responsive (P1)

**Objetivo:** Crear los estilos del hub coordinador y la card de perfil Andalucia +ei.

**Duracion estimada:** 4-5 horas

#### 5.6.1 SCSS del Hub Coordinador

**Fichero:** `web/themes/custom/ecosistema_jaraba_theme/scss/routes/_coordinador-hub.scss`

**Compilacion:** Importar desde `scss/main.scss` o como library separada.

**Estructura CSS:**

```scss
@use '../variables' as *;  // SCSS-001

.hub-coordinador {
  max-width: min(1200px, 95vw);
  margin: 0 auto;
  padding: var(--ej-spacing-lg, 2rem);
}

.hub-kpis {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: var(--ej-spacing-md, 1rem);
  margin-bottom: var(--ej-spacing-xl, 2.5rem);
}

.hub-kpi-card {
  background: var(--ej-color-surface, #ffffff);
  border-radius: var(--ej-radius-lg, 12px);
  padding: var(--ej-spacing-lg, 1.5rem);
  box-shadow: var(--ej-shadow-sm);
  // ...colores por variante --andalucia, --innovation, etc.
  &--andalucia {
    border-left: 4px solid var(--ej-color-andalucia, #00A9A5);
  }
}

.hub-tabs {
  display: flex;
  gap: var(--ej-spacing-xs, 0.25rem);
  border-bottom: 2px solid var(--ej-color-border, #e5e7eb);
  margin-bottom: var(--ej-spacing-lg, 1.5rem);
  overflow-x: auto; // Mobile scroll horizontal
  -webkit-overflow-scrolling: touch;
}

.hub-tab {
  padding: var(--ej-spacing-sm, 0.75rem) var(--ej-spacing-md, 1rem);
  border: none;
  background: transparent;
  color: var(--ej-color-text-secondary, #6b7280);
  cursor: pointer;
  white-space: nowrap;
  transition: color 0.2s, border-color 0.2s;

  &--active {
    color: var(--ej-color-primary, #233D63);
    border-bottom: 2px solid var(--ej-color-primary, #233D63);
    margin-bottom: -2px;
  }

  &__badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    border-radius: 10px;
    background: var(--ej-color-danger, #EF4444);
    color: var(--ej-color-white, #ffffff);
    font-size: 0.75rem;
    font-weight: 600;
  }
}
```

**Cumplimiento CSS-VAR-ALL-COLORS-001:** Todos los colores via `var(--ej-*, fallback)`. Sin hex hardcoded.

**Cumplimiento SCSS-COLORMIX-001:** Cualquier transparencia via `color-mix(in srgb, var(--ej-color-x) 15%, transparent)`.

**Cumplimiento SCSS-COMPILETIME-001:** Variables SCSS que alimentan `color.scale/adjust/change` son hex estatico.

#### 5.6.2 SCSS de la Card de Perfil Andalucia +ei

**Fichero:** Adicion a `scss/_user-profile.scss` (existente) o nuevo parcial `scss/components/_andalucia-ei-profile-card.scss`

```scss
// Card especifica del vertical Andalucia +ei en perfil de usuario
.quick-access-section--andalucia {
  border-left: 4px solid var(--ej-color-andalucia, #00A9A5);

  .quick-access__card--andalucia {
    &:hover {
      background: color-mix(in srgb, var(--ej-color-andalucia, #00A9A5) 8%, transparent);
    }
  }
}

// Badge de fase del participante
.andalucia-phase-badge {
  display: inline-flex;
  align-items: center;
  gap: var(--ej-spacing-xs, 0.25rem);
  padding: 2px 8px;
  border-radius: var(--ej-radius-full, 9999px);
  font-size: 0.75rem;
  font-weight: 500;

  &--acogida { background: color-mix(in srgb, var(--ej-color-neutral, #6b7280) 15%, transparent); }
  &--diagnostico { background: color-mix(in srgb, var(--ej-color-corporate, #233D63) 15%, transparent); }
  &--atencion { background: color-mix(in srgb, var(--ej-color-impulse, #FF8C42) 15%, transparent); }
  &--insercion { background: color-mix(in srgb, var(--ej-color-innovation, #00A9A5) 15%, transparent); }
}
```

#### 5.6.3 Iconografia

**Cumplimiento ICON-CONVENTION-001 + ICON-DUOTONE-001 + ICON-COLOR-001:**

| Contexto | Categoria | Nombre | Variante | Color |
|----------|-----------|--------|----------|-------|
| KPI participantes | general | users | duotone | andalucia |
| KPI solicitudes | general | inbox | duotone | impulse |
| KPI sesiones | general | calendar | duotone | corporate |
| KPI insercion | business | target | duotone | innovation |
| Tab solicitudes | general | inbox | duotone | — |
| Tab participantes | general | users | duotone | — |
| Tab sesiones | general | calendar | duotone | — |
| Tab STO | general | download | duotone | — |
| Tab metricas | ui | bar-chart | duotone | — |
| Accion aprobar | actions | check | duotone | innovation |
| Accion rechazar | actions | x | duotone | danger |
| Accion cambiar fase | actions | refresh | duotone | impulse |
| Accion asignar mentor | general | user-plus | duotone | corporate |
| Card perfil programa | verticals | andalucia-ei | duotone | andalucia |

**Colores SOLO de paleta Jaraba:** azul-corporativo, naranja-impulso, verde-innovacion, white, neutral + andalucia (vertical-specific).

#### 5.6.4 Responsive (Mobile-First)

```scss
// Breakpoints del ecosistema
// $breakpoint-sm: 640px;
// $breakpoint-md: 768px;
// $breakpoint-lg: 1024px;

.hub-kpis {
  grid-template-columns: 1fr; // Mobile: una columna

  @media (min-width: 640px) {
    grid-template-columns: repeat(2, 1fr);
  }
  @media (min-width: 1024px) {
    grid-template-columns: repeat(4, 1fr);
  }
}

.hub-tabs {
  // Mobile: scroll horizontal ya incluido
  // Touch: -webkit-overflow-scrolling: touch
}

.hub-table {
  // Mobile: tabla horizontal scrollable
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;

  // O alternativa: cards en mobile
  @media (max-width: 639px) {
    // Convertir tabla a stack de cards
  }
}
```

#### 5.6.5 Library declaration

```yaml
# jaraba_andalucia_ei.libraries.yml — Adicion
coordinador-hub:
  version: 1.x
  css:
    theme:
      css/coordinador-hub.css: {}
  js:
    js/coordinador-hub.js: {}
  dependencies:
    - core/drupal
    - core/drupalSettings
    - core/once
    - ecosistema_jaraba_theme/slide-panel
```

---

### 5.7 Fase 7 — Tests, Validacion y Runtime Verify (P2)

**Objetivo:** Garantizar cobertura de tests y verificacion end-to-end.

**Duracion estimada:** 4-5 horas

#### 5.7.1 Tests Unit

| Test | Clase | Que verifica |
|------|-------|-------------|
| `UserProfileSectionRegistryTest` | `Tests\Unit\UserProfile\UserProfileSectionRegistryTest` | addSection, getApplicableSections, buildSectionsArray, ordenamiento por peso |
| `AndaluciaEiUserProfileSectionTest` | `Tests\Unit\UserProfile\AndaluciaEiUserProfileSectionTest` | Deteccion de roles con mocks, generacion de links, titulo dinamico |

#### 5.7.2 Tests Kernel

| Test | Clase | Que verifica |
|------|-------|-------------|
| `UserProfileSectionPassTest` | `Tests\Kernel\UserProfile\UserProfileSectionPassTest` | CompilerPass inyecta tagged services en registry |
| `CoordinadorHubServiceTest` | `Tests\Kernel\Service\CoordinadorHubServiceTest` | Queries con tenant isolation, aprobacion solicitud crea participante |

#### 5.7.3 Tests Functional

| Test | Ruta | Que verifica |
|------|------|-------------|
| `CoordinadorHubApiTest` | `/api/v1/andalucia-ei/hub/*` | Permisos, CSRF, respuestas JSON, acciones CRUD |

#### 5.7.4 Checklist RUNTIME-VERIFY-001

- [ ] CSS compilado: timestamp `coordinador-hub.css` > `_coordinador-hub.scss`
- [ ] Tablas DB: Sin cambios (usa entities existentes)
- [ ] Rutas accesibles: Nuevas rutas API responden 200/403 correctamente
- [ ] `data-*` selectores: `data-tab`, `data-slide-panel`, `data-slide-panel-url` matchean JS
- [ ] `drupalSettings`: URLs de API inyectadas correctamente en controller

#### 5.7.5 Checklist IMPLEMENTATION-CHECKLIST-001

- [ ] Servicio `user_profile_section_registry` registrado en services.yml Y consumido en preprocess
- [ ] CompilerPass registrado en ServiceProvider
- [ ] Rutas nuevas en routing.yml apuntan a clases existentes
- [ ] Tagged services tienen tag correcto
- [ ] Tests existen para infraestructura (Unit + Kernel)
- [ ] SCSS compilado, library registrada, hook_page_attachments_alter si necesario
- [ ] Patron PREMIUM-FORMS-PATTERN-001: N/A (no hay forms nuevas)
- [ ] CSS-VAR-ALL-COLORS-001 en SCSS: Verificado
- [ ] TENANT-001 en queries: Verificado

---

## 6. Tabla de Correspondencia con Especificaciones Tecnicas

| Especificacion | Seccion del Plan | Estado |
|----------------|------------------|--------|
| Spec 32: Mentoring Sessions v1 | Fase 3 (links a sesiones), Fase 4 (panel sesiones) | Dependencia existente |
| Spec Andalucia +ei: Programa completo | Fase 3 (deteccion roles), Fase 4-5 (hub) | Referencia principal |
| Spec Tenant Settings Hub | Fase 1 (patron replicado) | Patron base |
| Spec Premium Forms | N/A | No aplica (no forms nuevas) |
| Spec Avatar Navigation | Fase 2 (seccion MyVertical) | Migrada sin cambios |
| Spec Zero Region Policy | Todas las fases frontend | Cumplimiento verificado |
| Spec Slide-Panel Forms | Fase 4-5 (acciones CRUD hub) | Patron aplicado |

---

## 7. Tabla de Cumplimiento de Directrices del Proyecto

| ID Directriz | Directriz | Como se cumple | Fase |
|-------------|-----------|----------------|------|
| ZERO-REGION-001 | Variables via preprocess, controller devuelve solo markup | Hub usa preprocess para drupalSettings, controller return type markup | 4-5 |
| ZERO-REGION-002 | No pasar entity objects como non-# keys | buildSectionsArray devuelve arrays primitivos, no entities | 1-2 |
| ZERO-REGION-003 | #attached del controller NO se procesa | drupalSettings inyectado en preprocess, no en controller | 4-5 |
| CSS-VAR-ALL-COLORS-001 | Todo color como var(--ej-*, fallback) | Verificado en todo SCSS nuevo | 6 |
| SCSS-COLORMIX-001 | color-mix() en vez de rgba() | Transparencias via color-mix(in srgb) | 6 |
| SCSS-001 | Cada parcial con @use '../variables' as * | Incluido en cabecera de cada .scss | 6 |
| SCSS-COMPILE-VERIFY-001 | Verificar timestamp CSS > SCSS post-edicion | En RUNTIME-VERIFY-001 checklist | 7 |
| TENANT-001 | Toda query filtra por tenant | addTenantCondition() en todas las queries | 3-5 |
| TENANT-ISOLATION-ACCESS-001 | AccessHandler verifica tenant | API endpoints verifican permiso + tenant via service | 4 |
| OPTIONAL-CROSSMODULE-001 | Cross-modulo usa @? | TenantContextService con @? en todos los services.yml | 1-5 |
| CONTAINER-DEPS-002 | Sin dependencias circulares | Flujo unidireccional registry <- pass <- services | 1 |
| LOGGER-INJECT-001 | @logger.channel.X -> LoggerInterface | Constructor acepta LoggerInterface directamente | 4-5 |
| SLIDE-PANEL-RENDER-001 | renderPlain() + $form['#action'] | Aplicado en todos los endpoints de formulario slide-panel | 4-5 |
| ROUTE-LANGPREFIX-001 | URLs via Url::fromRoute() o drupalSettings | Todas las URLs resueltas via Url::fromRoute(), JS usa drupalSettings | 2-5 |
| CSRF-API-001 | POST API routes con _csrf_request_header_token | Todas las rutas POST del hub con 'TRUE' | 4 |
| ACCESS-STRICT-001 | Comparaciones con (int) === (int) | Tenant ID comparisons con strict int | 3-5 |
| ICON-CONVENTION-001 | jaraba_icon('category', 'name', opts) | Todos los iconos via funcion Twig | 6 |
| ICON-DUOTONE-001 | Variante default duotone | variant: 'duotone' en todos los usos | 6 |
| ICON-COLOR-001 | Colores solo de paleta Jaraba | Solo azul-corporativo, naranja-impulso, verde-innovacion, neutral, andalucia | 6 |
| INNERHTML-XSS-001 | Drupal.checkPlain() para datos API | Aplicado en JS del hub para datos insertados dinamicamente | 4-5 |
| CSRF-JS-CACHE-001 | Token /session/token cacheado en variable | Token cacheado en modulo JS del hub | 4-5 |
| FORM-CACHE-001 | No setCached(TRUE) incondicional | No aplica (API endpoints, no forms stateful) | — |
| UPDATE-HOOK-REQUIRED-001 | hook_update_N para cambios de schema | No hay cambios de schema (usa entities existentes) | — |
| PREMIUM-FORMS-PATTERN-001 | Toda entity form extiende PremiumEntityFormBase | No hay entity forms nuevas | — |
| PRESAVE-RESILIENCE-001 | Servicios opcionales con hasService + try-catch | Aplicado en deteccion de roles y servicios cross-modulo | 3 |
| IMPLEMENTATION-CHECKLIST-001 | Verificacion post-implementacion | Checklist completo en Fase 7 | 7 |
| RUNTIME-VERIFY-001 | 5 checks: CSS, DB, rutas, data-*, drupalSettings | Checklist completo en Fase 7 | 7 |

---

## 8. Arquitectura Frontend y Templates

### 8.1 Templates Twig nuevos y modificados

| Template | Modulo/Theme | Accion | Descripcion |
|----------|-------------|--------|-------------|
| `page--user.html.twig` | ecosistema_jaraba_theme | SIN CAMBIOS | Ya soporta `user_quick_sections` |
| `andalucia-ei-coordinador-dashboard.html.twig` | jaraba_andalucia_ei | MODIFICAR | Agregar tabs, paneles CRUD, KPI cards |
| `_hub-solicitud-row.html.twig` | jaraba_andalucia_ei/partials | NUEVO | Fila de solicitud con acciones |
| `_hub-participant-row.html.twig` | jaraba_andalucia_ei/partials | NUEVO | Fila de participante con acciones |
| `_hub-session-row.html.twig` | jaraba_andalucia_ei/partials | NUEVO | Fila de sesion |
| `_hub-kpi-card.html.twig` | jaraba_andalucia_ei/partials | NUEVO | Card de KPI reutilizable |

### 8.2 Parciales reutilizables

Verificar y reutilizar parciales existentes ANTES de crear nuevos:

| Parcial existente | Ubicacion | Reutilizable en |
|------------------|-----------|-----------------|
| `_slide-panel.html.twig` | ecosistema_jaraba_theme/partials | Hub: todas las acciones CRUD |
| `_empty-state.html.twig` | ecosistema_jaraba_theme/partials | Hub: tabs sin datos |
| `_skeleton.html.twig` | ecosistema_jaraba_theme/partials | Hub: loading states |
| `_profile-completeness.html.twig` | ecosistema_jaraba_theme/partials | Perfil: widget completitud (ya usado) |

### 8.3 SCSS y compilacion

| Fichero SCSS | CSS generado | Library |
|-------------|-------------|---------|
| `scss/routes/_coordinador-hub.scss` | `css/routes/coordinador-hub.css` | `jaraba_andalucia_ei/coordinador-hub` |
| `scss/components/_andalucia-ei-profile-card.scss` | Compilado en main.css | `ecosistema_jaraba_theme/global` |

**Compilacion:** `npm run build` desde `web/themes/custom/ecosistema_jaraba_theme/`

**Verificacion:** `SCSS-COMPILE-VERIFY-001` — timestamp CSS > SCSS

### 8.4 Variables CSS inyectables desde Drupal UI

Las variables CSS del hub usan tokens existentes del ecosistema (`--ej-color-*`, `--ej-spacing-*`, `--ej-radius-*`, `--ej-shadow-*`). No se requieren nuevas variables, ya que:

- `--ej-color-primary` → color principal de tabs activos
- `--ej-color-surface` → fondo de cards
- `--ej-color-border` → bordes de tabla y tabs
- `--ej-color-danger` → badge de solicitudes pendientes
- `--ej-color-innovation` → acciones positivas (aprobar)
- `--ej-color-impulse` → acciones de transicion (cambiar fase)

Si se necesita un color especifico para el vertical Andalucia +ei (ej: `--ej-color-andalucia`), debe configurarse en el Design Token Config del vertical (ya existente post-Fase 3 del plan de elevacion 20260215c).

### 8.5 Iconografia

Todos los iconos usados en este plan estan documentados en la tabla de la seccion 5.6.3. Verificar que existen los SVGs en el directorio de iconos del theme antes de implementar. Si falta alguno, crear segun las especificaciones ICON-CANVAS-INLINE-001 (hex explicito en stroke/fill, NUNCA currentColor).

### 8.6 Internacionalizacion (i18n)

**Regla:** TODO texto visible al usuario usa `t()` en PHP o `{% trans %}` en Twig.

**Strings a traducir (ejemplos):**

| Contexto | Texto original | Metodo |
|----------|---------------|--------|
| Card perfil | "Andalucia +ei — Fase: @fase" | `$this->t('Andalucia +ei — Fase: @fase', ['@fase' => $fase])` |
| Tab hub | "Solicitudes" | `{% trans %}Solicitudes{% endtrans %}` |
| Accion | "Solicitud aprobada correctamente" | `$this->t('Solicitud aprobada correctamente')` |
| KPI | "@count participantes activos" | `$this->formatPlural($count, '1 participante activo', '@count participantes activos')` |
| Filtro | "Filtrar por fase" | `{% trans %}Filtrar por fase{% endtrans %}` |
| Empty state | "No hay solicitudes pendientes" | `{% trans %}No hay solicitudes pendientes{% endtrans %}` |
| Error API | "Error al procesar la solicitud" | JSON en respuesta, no traducido (API interna) |

---

## 9. Verificacion y Testing

### 9.1 Tests automatizados

**Estructura de tests:**

```
tests/src/
  Unit/
    UserProfile/
      UserProfileSectionRegistryTest.php
      Section/
        ProfessionalProfileSectionTest.php
        AccountSectionTest.php
    jaraba_andalucia_ei/
      UserProfile/
        AndaluciaEiUserProfileSectionTest.php
  Kernel/
    UserProfile/
      UserProfileSectionPassTest.php
    jaraba_andalucia_ei/
      Service/
        CoordinadorHubServiceTest.php
```

**Reglas de testing aplicadas:**
- KERNEL-TEST-DEPS-001: $modules lista TODOS los modulos requeridos
- KERNEL-TEST-001: KernelTestBase solo cuando necesita DB/entities
- MOCK-DYNPROP-001: Clases anonimas con typed properties para mocks
- MOCK-METHOD-001: createMock con ContentEntityInterface para hasField()
- TEST-CACHE-001: Entity mocks implementan getCacheContexts/getCacheTags/getCacheMaxAge
- KERNEL-TIME-001: Tolerancia +/-1s en assertions de timestamp

### 9.2 Checklist RUNTIME-VERIFY-001

Ejecutar despues de CADA fase:

| # | Check | Comando/Verificacion | Fase |
|---|-------|---------------------|------|
| 1 | CSS compilado | `ls -la css/routes/coordinador-hub.css` vs `scss/routes/_coordinador-hub.scss` | 6 |
| 2 | Tablas DB | N/A (sin cambios de schema) | — |
| 3 | Rutas accesibles | `drush route:list --path=/api/v1/andalucia-ei/hub` | 4 |
| 4 | data-* selectores | Inspeccionar DOM, verificar `data-tab`, `data-slide-panel` | 4-5 |
| 5 | drupalSettings | `console.log(drupalSettings.jarabaAndaluciaEi)` en browser | 4-5 |
| 6 | Permisos | Verificar acceso con usuario sin permiso → 403 | 4 |
| 7 | CSRF | Verificar POST sin token → 403 | 4 |
| 8 | Tenant isolation | Verificar que datos de otro tenant no son visibles | 3-5 |
| 9 | Slide-panel | Verificar apertura y cierre correctos | 4-5 |
| 10 | i18n | Verificar que strings aparecen con prefijo /es/ | Todas |
| 11 | Mobile | Verificar layout en 375px width | 6 |
| 12 | Accesibilidad | Tab navigation con teclado, screen reader labels | 4-6 |

### 9.3 Checklist IMPLEMENTATION-CHECKLIST-001

**Complitud:**
- [ ] Servicio `user_profile_section_registry` registrado y consumido
- [ ] CompilerPass registrado en ServiceProvider
- [ ] 5 secciones migradas como tagged services
- [ ] `AndaluciaEiUserProfileSection` registrada con tag
- [ ] `CoordinadorHubService` registrado y consumido por controller
- [ ] `CoordinadorHubApiController` con todas las rutas
- [ ] SCSS compilado, library registrada

**Integridad:**
- [ ] Tests Unit para registry y secciones
- [ ] Tests Kernel para CompilerPass y hub service
- [ ] Sin hook_update_N necesario (no hay cambios de schema)

**Consistencia:**
- [ ] CSS-VAR-ALL-COLORS-001 verificado en todo SCSS nuevo
- [ ] TENANT-001 verificado en todas las queries
- [ ] ROUTE-LANGPREFIX-001 verificado en todos los links

**Coherencia:**
- [ ] MEMORY.md actualizado con patron UserProfileSectionProvider
- [ ] INDICE master doc actualizado con referencia a este plan

**Automatizacion:**
- [ ] `bash scripts/validation/validate-all.sh --checklist web/modules/custom/jaraba_andalucia_ei`
- [ ] `php scripts/validation/validate-optional-deps.php`
- [ ] `php scripts/validation/validate-tenant-isolation.php`
- [ ] `php scripts/validation/validate-circular-deps.php`

---

## 10. Inventario Completo de Ficheros

### Ficheros NUEVOS

| Fichero | Modulo | Fase | Descripcion |
|---------|--------|------|-------------|
| `src/UserProfile/UserProfileSectionInterface.php` | ecosistema_jaraba_core | 1 | Interface para secciones de perfil |
| `src/UserProfile/UserProfileSectionRegistry.php` | ecosistema_jaraba_core | 1 | Registry con CompilerPass |
| `src/DependencyInjection/Compiler/UserProfileSectionPass.php` | ecosistema_jaraba_core | 1 | CompilerPass para tagged services |
| `src/UserProfile/Section/ProfessionalProfileSection.php` | ecosistema_jaraba_core | 2 | Seccion Mi Perfil Profesional |
| `src/UserProfile/Section/MyVerticalSection.php` | ecosistema_jaraba_core | 2 | Seccion Mi Vertical |
| `src/UserProfile/Section/MyBusinessSection.php` | ecosistema_jaraba_core | 2 | Seccion Mi Negocio |
| `src/UserProfile/Section/AdministrationSection.php` | ecosistema_jaraba_core | 2 | Seccion Administracion |
| `src/UserProfile/Section/AccountSection.php` | ecosistema_jaraba_core | 2 | Seccion Cuenta |
| `src/UserProfile/AndaluciaEiUserProfileSection.php` | jaraba_andalucia_ei | 3 | Card de programa en perfil |
| `src/Service/CoordinadorHubService.php` | jaraba_andalucia_ei | 4 | Logica de negocio del hub |
| `src/Controller/CoordinadorHubApiController.php` | jaraba_andalucia_ei | 4 | API endpoints CRUD |
| `js/coordinador-hub.js` | jaraba_andalucia_ei | 4 | JS vanilla para tabs, fetch, slide-panel |
| `templates/partials/_hub-solicitud-row.html.twig` | jaraba_andalucia_ei | 4 | Fila de solicitud |
| `templates/partials/_hub-participant-row.html.twig` | jaraba_andalucia_ei | 5 | Fila de participante |
| `templates/partials/_hub-session-row.html.twig` | jaraba_andalucia_ei | 5 | Fila de sesion |
| `templates/partials/_hub-kpi-card.html.twig` | jaraba_andalucia_ei | 4 | Card KPI reutilizable |
| `scss/routes/_coordinador-hub.scss` | ecosistema_jaraba_theme | 6 | Estilos del hub |
| `scss/components/_andalucia-ei-profile-card.scss` | ecosistema_jaraba_theme | 6 | Estilos card perfil |
| `tests/src/Unit/UserProfile/UserProfileSectionRegistryTest.php` | ecosistema_jaraba_core | 7 | Test Unit registry |
| `tests/src/Unit/UserProfile/AndaluciaEiUserProfileSectionTest.php` | jaraba_andalucia_ei | 7 | Test Unit seccion perfil |
| `tests/src/Kernel/UserProfile/UserProfileSectionPassTest.php` | ecosistema_jaraba_core | 7 | Test Kernel CompilerPass |
| `tests/src/Kernel/Service/CoordinadorHubServiceTest.php` | jaraba_andalucia_ei | 7 | Test Kernel hub service |

### Ficheros MODIFICADOS

| Fichero | Modulo | Fase | Cambio |
|---------|--------|------|--------|
| `ecosistema_jaraba_core.services.yml` | ecosistema_jaraba_core | 1-2 | Agregar registry + 5 tagged services |
| `EcosistemaJarabaCoreServiceProvider.php` | ecosistema_jaraba_core | 1 | Registrar UserProfileSectionPass |
| `ecosistema_jaraba_theme.theme` | ecosistema_jaraba_theme | 2 | Reemplazar 320 lineas hardcoded por ~15 lineas con registry |
| `jaraba_andalucia_ei.services.yml` | jaraba_andalucia_ei | 3-4 | Agregar tagged service + hub service |
| `jaraba_andalucia_ei.routing.yml` | jaraba_andalucia_ei | 4 | Agregar ~8 rutas API hub |
| `jaraba_andalucia_ei.libraries.yml` | jaraba_andalucia_ei | 6 | Agregar library coordinador-hub |
| `andalucia-ei-coordinador-dashboard.html.twig` | jaraba_andalucia_ei | 4-5 | Reescribir con tabs y paneles CRUD |
| `scss/main.scss` | ecosistema_jaraba_theme | 6 | Import de nuevos parciales |

---

## 11. Recorrido del Usuario (User Journey)

### Journey 1: Participante accede a su perfil

```
1. Participante Maria navega a /user/42
2. page--user.html.twig renderiza profile hero + account info
3. UserProfileSectionRegistry evalua secciones:
   a. ProfessionalProfileSection: isApplicable=TRUE → 3 links + completitud
   b. MyVerticalSection: avatar=general → isApplicable=FALSE (skip)
   c. AndaluciaEiUserProfileSection: programa_participante_ei encontrado →
      isApplicable=TRUE → 4 links (Mi Portal, Mi Expediente, Mis Sesiones, Informe)
      Titulo: "Andalucia +ei — Fase: Atencion"
   d. AccountSection: isApplicable=TRUE → 2 links
4. Maria ve:
   - Card "Mi Perfil Profesional" con widget de completitud
   - Card "Andalucia +ei — Fase: Atencion" con badge naranja y 4 accesos rapidos
   - Card "Cuenta" con editar perfil y cerrar sesion
5. Maria hace clic en "Mi Portal" → navega a /andalucia-ei/mi-participacion
```

### Journey 2: Coordinador gestiona solicitudes desde el hub

```
1. Coordinador Carlos navega a /andalucia-ei/coordinador
2. Ve 4 KPI cards: 127 participantes activos, 5 solicitudes pendientes, 12 sesiones esta semana, 67% tasa insercion
3. Tab "Solicitudes" activo por defecto (tiene badge "5")
4. Ve lista de 5 solicitudes pendientes con nombre, email, fecha
5. Hace clic en "Aprobar" en solicitud de Ana Garcia
6. Se abre slide-panel con resumen:
   - Nombre, email, telefono, situacion laboral, formacion
   - Boton "Confirmar aprobacion"
7. Carlos confirma → API POST /api/v1/andalucia-ei/hub/solicitud/15/approve
8. La solicitud desaparece de la lista, badge cambia a "4"
9. Se crea automaticamente programa_participante_ei para Ana
10. (Si jaraba_email disponible) Ana recibe email de bienvenida
```

### Journey 3: Coordinador cambia fase de participante

```
1. Carlos va al tab "Participantes"
2. Filtra por fase "diagnostico"
3. Hace clic en "Cambiar fase" en Pedro Lopez
4. Slide-panel muestra:
   - Fase actual: Diagnostico
   - Selector de nueva fase: Atencion (pre-seleccionado como siguiente logica)
   - Campo de notas (opcional)
   - Boton "Confirmar transicion"
5. Carlos selecciona "Atencion" y confirma
6. API POST → FaseTransitionManager ejecuta la transicion
7. La fila de Pedro se actualiza con la nueva fase
```

---

## 12. Troubleshooting

| Problema | Causa probable | Solucion |
|----------|---------------|----------|
| Secciones de perfil vacias despues de migracion | Registry no tiene tagged services | Verificar que CompilerPass esta registrado en ServiceProvider |
| Tag `ecosistema_jaraba_core.user_profile_section` no encontrado | Falta registro en services.yml | Verificar que cada seccion tiene el tag correcto |
| `AndaluciaEiUserProfileSection` no aparece | Modulo jaraba_andalucia_ei no instalado | Verificar `drush pm:list --filter=jaraba_andalucia_ei` |
| Error "Service not found" en preprocess | Cache de container desactualizado | `drush cr` |
| Hub coordinador muestra datos de otro tenant | Falta `addTenantCondition` en alguna query | Revisar `CoordinadorHubService` queries con `validate-tenant-isolation.php` |
| Slide-panel no abre en hub | JS no cargado o data-* attributes incorrectos | Verificar library en `#attached`, inspeccionar DOM |
| CSRF 403 en acciones POST del hub | Token no enviado en header | Verificar JS envia `X-CSRF-Token` header |
| KPIs muestran 0 | Tenant no resuelto | Verificar `TenantContextService::getCurrentTenant()` en el contexto |

---

## 13. Referencias

| Documento | Ubicacion | Relevancia |
|-----------|-----------|------------|
| CLAUDE.md (directrices proyecto) | `/CLAUDE.md` | Directrices maestras |
| Plan Elevacion Andalucia +ei v1 | `docs/implementacion/20260215c-Plan_Elevacion_Andalucia_EI_Clase_Mundial_v1_Claude.md` | 12 fases implementadas, baseline |
| Plan Mentoring + Cursos | `docs/implementacion/2026-03-06_Plan_Implementacion_Andalucia_Ei_Mentoring_Cursos_Clase_Mundial_v1.md` | 9 fases complementarias |
| TenantSettingsRegistry (patron base) | `ecosistema_jaraba_core/src/TenantSettings/TenantSettingsRegistry.php` | Patron identico replicado |
| TenantSettingsSectionPass | `ecosistema_jaraba_core/src/DependencyInjection/Compiler/TenantSettingsSectionPass.php` | CompilerPass de referencia |
| TenantSettingsSectionInterface | `ecosistema_jaraba_core/src/TenantSettings/TenantSettingsSectionInterface.php` | Interface de referencia |
| User Profile Preprocess | `ecosistema_jaraba_theme.theme:3258-3757` | Codigo a refactorizar |
| page--user.html.twig | `ecosistema_jaraba_theme/templates/page--user.html.twig` | Template consumidor (sin cambios) |
| CoordinadorDashboardController | `jaraba_andalucia_ei/src/Controller/CoordinadorDashboardController.php` | Controller a extender |
| Permisos Andalucia +ei | `jaraba_andalucia_ei/jaraba_andalucia_ei.permissions.yml` | 14 permisos existentes |
| Routing Andalucia +ei | `jaraba_andalucia_ei/jaraba_andalucia_ei.routing.yml` | 37 rutas existentes |

---

## 14. Registro de Cambios

| Version | Fecha | Autor | Cambios |
|---------|-------|-------|---------|
| 1.0.0 | 2026-03-06 | Claude Opus 4.6 | Creacion del plan completo con 7 fases, 22 ficheros nuevos, 8 modificados |
