# Plan de Implementacion: Cierre 3 Gaps P0 ICV 2025 — Ficha Tecnica + Plazos + Insercion SS
# Version: 1.0 | Fecha: 18 marzo 2026
# Estado: Plan de Implementacion aprobado para ejecucion
# Dependencias: Pautas Gestion Tecnica ICV 25 (SAE, 18/03/2026), jaraba_andalucia_ei
# Origen: Auditoria confrontacion Pautas ICV 2025 vs implementacion actual (93% cumplimiento)
# Equipo: Claude Code (integramente)

---

## Tabla de Contenidos (TOC)

1. [Resumen ejecutivo](#1-resumen-ejecutivo)
2. [Correspondencia de especificaciones tecnicas](#2-correspondencia-de-especificaciones-tecnicas)
3. [Correspondencia de directrices del proyecto](#3-correspondencia-de-directrices-del-proyecto)
4. [GAP-1: FichaTecnicaEi Entity](#4-gap-1-fichatecnicaei-entity)
   - 4.1 [Justificacion normativa](#41-justificacion-normativa)
   - 4.2 [Estructura de la entity](#42-estructura-de-la-entity)
   - 4.3 [Campos baseFieldDefinitions](#43-campos-basefielddefinitions)
   - 4.4 [AccessControlHandler](#44-accesscontrolhandler)
   - 4.5 [PremiumEntityFormBase](#45-premiumentityformbase)
   - 4.6 [Ratio validation (1 tecnico : 60 proyectos)](#46-ratio-validation)
   - 4.7 [Rutas admin](#47-rutas-admin)
   - 4.8 [hook_update_10029](#48-hook_update_10029)
5. [GAP-2: Enforcement de plazos normativos](#5-gap-2-enforcement-de-plazos-normativos)
   - 5.1 [Plazo 15 dias naturales (recibos STO)](#51-plazo-15-dias-naturales)
   - 5.2 [Plazo 10 dias habiles (VoBo formacion)](#52-plazo-10-dias-habiles)
   - 5.3 [Plazo 2 meses (incentivo participacion)](#53-plazo-2-meses-incentivo)
   - 5.4 [Plazo 18 meses (duracion programa)](#54-plazo-18-meses-duracion-programa)
   - 5.5 [Dias habiles: calculo correcto](#55-dias-habiles-calculo-correcto)
   - 5.6 [Integracion en AlertasNormativasService](#56-integracion-alertasnormativas)
   - 5.7 [Daily Action: PlazosVencidosAction](#57-daily-action-plazosvencidosaction)
6. [GAP-3: Validacion insercion Seguridad Social](#6-gap-3-validacion-insercion-seguridad-social)
   - 6.1 [Criterios normativos de insercion](#61-criterios-normativos)
   - 6.2 [Calculo automatico de duracion SS](#62-calculo-automatico-duracion)
   - 6.3 [Sector agrario (3 meses Sistema Especial)](#63-sector-agrario)
   - 6.4 [Combinacion regimenes SS](#64-combinacion-regimenes)
   - 6.5 [Integracion en FaseTransitionManager](#65-integracion-fase-transition)
   - 6.6 [Desglose fiscal incentivo (528 EUR - IRPF 2%)](#66-desglose-fiscal-incentivo)
7. [Fase complementaria: formacion fines de semana + coste hora](#7-fase-complementaria)
8. [Verificacion RUNTIME-VERIFY-001 + PIPELINE-E2E-001](#8-verificacion)
9. [Testing](#9-testing)
10. [Cronograma de ejecucion](#10-cronograma)

---

## 1. Resumen ejecutivo

### Contexto

La auditoria de confrontacion entre las "Pautas de Gestion Tecnica ICV 2025" (SAE, 18/03/2026)
y la implementacion actual de `jaraba_andalucia_ei` revelo 93% de cumplimiento con 3 gaps P0:

| # | Gap | Cobertura actual | Riesgo |
|---|-----|-----------------|--------|
| GAP-1 | Ficha Tecnica entity | 0% | SAE exige validacion antes de iniciar |
| GAP-2 | Plazos normativos | 0-60% | Perdida de justificacion |
| GAP-3 | Insercion SS duracion | 80% | Verificacion manual costosa |

### Que NO se implementa en este plan

- No se implementa integracion directa con SILA (sistema SAE) — fuera de scope
- No se implementa firma electronica PAdES — dependencia futura
- No se implementa teleformacion/e-learning (prohibida por normativa)
- No se modifican entities existentes — solo se añaden nuevas y se amplian servicios

### Modulos afectados

| Modulo | Archivos nuevos | Archivos modificados |
|--------|----------------|---------------------|
| `jaraba_andalucia_ei` | 6 nuevos | 4 modificados |

---

## 2. Correspondencia de especificaciones tecnicas

| Seccion Pautas ICV 2025 | Gap | Componente resultante |
|--------------------------|-----|----------------------|
| §3.2 Ficha Tecnica | GAP-1 | `FichaTecnicaEi` ContentEntity |
| §3.4 Personal Tecnico (ratio 1:60) | GAP-1 | `FichaTecnicaEi::validateRatio()` |
| §5.1.A Recibos 15 dias | GAP-2 | `PlazoEnforcementService` |
| §5.1.B.3 VoBo 10 dias habiles | GAP-2 | `PlazoEnforcementService::diasHabiles()` |
| §5.1.C Incentivo 2 meses | GAP-2 | `PlazoEnforcementService::checkIncentivoDeadline()` |
| §3.1 Programa 18 meses | GAP-2 | `PlazoEnforcementService::checkProgramaExpiry()` |
| §5.2.B Insercion 4 meses cuenta ajena | GAP-3 | `InsercionValidatorService::validateDuracionSS()` |
| §5.2.B.1 Sector agrario 3 meses | GAP-3 | `InsercionValidatorService::validateAgrario()` |
| §5.1.C Incentivo 528 EUR - IRPF 2% | GAP-3 | Desglose fiscal en IncentiveReceiptService |

---

## 3. Correspondencia de directrices del proyecto

| Directriz | Aplicacion | Verificacion |
|----------|------------|-------------|
| TENANT-001 | FichaTecnicaEi tiene tenant_id FK | validate-tenant-isolation.php |
| PREMIUM-FORMS-PATTERN-001 | FichaTecnicaEiForm extiende PremiumEntityFormBase | Manual |
| ACCESS-RETURN-TYPE-001 | checkAccess() retorna AccessResultInterface | PHPStan L6 |
| ENTITY-FK-001 | tenant_id = entity_reference a group | validate-entity-integrity.php |
| AUDIT-CONS-001 | AccessControlHandler en anotacion | validate-entity-integrity.php |
| UPDATE-HOOK-REQUIRED-001 | hook_update_10029 con installEntityType() | validate-entity-integrity.php |
| UPDATE-HOOK-CATCH-001 | try-catch con \Throwable | Manual |
| OPTIONAL-CROSSMODULE-001 | @? para deps cross-modulo | validate-optional-deps.php |
| PHANTOM-ARG-001 | YAML args = constructor params | validate-phantom-args.php |
| FIELD-UI-SETTINGS-TAB-001 | field_ui_base_route + default local task | validate-entity-integrity.php |
| SETUP-WIZARD-DAILY-001 | Daily Action plazos vencidos | Manual |
| ICON-DUOTONE-001 | Iconos duotone en form y daily actions | Manual |
| PIPELINE-E2E-001 | L1-L4 verificado | Manual |

---

## 4. GAP-1: FichaTecnicaEi Entity

### 4.1 Justificacion normativa

La Ficha Tecnica (§3.2 Pautas) es el documento fundacional del programa que recoge:
- Datos de la entidad gestora y ubicaciones
- Personal: representante, coordinador/a, personal tecnico
- Titulacion academica del personal tecnico
- Ratio obligatorio: 1 tecnico por cada 60 proyectos por provincia
- Debe validarse por SSCC del SAE antes de poder empezar

Sin esta entidad, no hay trazabilidad estructurada del equipo del proyecto.

### 4.2 Estructura de la entity

**Archivo:** `src/Entity/FichaTecnicaEi.php`

```
id = "ficha_tecnica_ei"
label = "Ficha Técnica PIIL"
handlers:
  list_builder, views_data, form (PremiumEntityFormBase), access, route_provider
base_table = "ficha_tecnica_ei"
admin_permission = "administer andalucia ei"
entity_keys: id, uuid, label = "expediente_ref"
links: canonical, add-form, edit-form, delete-form, collection
  /admin/content/fichas-tecnicas-ei
field_ui_base_route = jaraba_andalucia_ei.ficha_tecnica_ei.settings
```

### 4.3 Campos baseFieldDefinitions (17 campos)

| Campo | Tipo | Obligatorio | Descripcion |
|-------|------|-------------|-------------|
| tenant_id | entity_reference (group) | SI | FK al tenant |
| expediente_ref | string(50) | SI | SC/ICV/NNNN/2025 (label) |
| provincia | list_string | SI | malaga, sevilla (PIIL SC/ICV/0111/2025) |
| sede_direccion | string(255) | SI | Direccion sede operativa |
| sede_operativa | boolean | SI | Sede operativa durante ejecucion |
| representante_nombre | string(255) | SI | Representante legal entidad |
| representante_nif | string(20) | SI | NIF representante |
| coordinador_nombre | string(255) | SI | Coordinador/a del programa |
| coordinador_nif | string(20) | SI | NIF coordinador/a |
| personal_tecnico | string_long | NO | JSON array: [{nombre, nif, titulacion, provincia, email, telefono}] |
| proyectos_concedidos | integer | SI | Numero proyectos por provincia |
| ratio_tecnicos_requeridos | integer | SI | ceil(proyectos / 60) |
| estado_validacion | list_string | SI | borrador, enviada, validada, rechazada |
| fecha_envio_sae | datetime | NO | Fecha envio al SAE |
| fecha_validacion_sae | datetime | NO | Fecha validacion por SSCC |
| observaciones | string_long | NO | Notas del SAE sobre subsanaciones |
| created / changed | created/changed | AUTO | Timestamps |

### 4.4 AccessControlHandler

Patron identico a los demas handlers del modulo: `administer andalucia ei` como admin bypass,
`manage ficha tecnica ei` para operaciones CRUD, tenant isolation en update/delete.

### 4.5 PremiumEntityFormBase

4 secciones:
1. **Expediente**: expediente_ref, provincia, proyectos_concedidos, tenant_id
2. **Sede**: sede_direccion, sede_operativa
3. **Personal directivo**: representante_nombre, representante_nif, coordinador_nombre, coordinador_nif
4. **Equipo tecnico**: personal_tecnico (JSON editor), ratio_tecnicos_requeridos

### 4.6 Ratio validation (1 tecnico : 60 proyectos)

Metodo `validateRatio()` en la entity:
```
ratio_requerido = ceil(proyectos_concedidos / 60)
personal_count = count(json_decode(personal_tecnico))
if personal_count < ratio_requerido → validation error
```

### 4.7 Rutas admin

- `/admin/content/fichas-tecnicas-ei` → Collection (EntityListBuilder)
- `/admin/content/ficha-tecnica-ei/add` → Add form
- `/admin/content/ficha-tecnica-ei/{id}/edit` → Edit form
- `/admin/config/andalucia-ei/ficha-tecnica` → Field UI settings

### 4.8 hook_update_10029

```php
function jaraba_andalucia_ei_update_10029(): string {
  // UPDATE-HOOK-REQUIRED-001 + UPDATE-HOOK-CATCH-001
  try {
    $updateManager = \Drupal::entityDefinitionUpdateManager();
    if (!$updateManager->getEntityType('ficha_tecnica_ei')) {
      $entityType = \Drupal::entityTypeManager()->getDefinition('ficha_tecnica_ei', FALSE);
      if ($entityType) {
        $updateManager->installEntityType($entityType);
        return 'FichaTecnicaEi entity type installed.';
      }
    }
    return 'ficha_tecnica_ei already exists.';
  } catch (\Throwable $e) {
    return 'Error: ' . $e->getMessage();
  }
}
```

---

## 5. GAP-2: Enforcement de plazos normativos

### 5.1 Plazo 15 dias naturales (recibos STO)

**Normativa:** §5.1.A/B — Recibo de servicio debe subirse al STO en maximo 15 dias naturales
desde la realizacion de la actuacion.

**Implementacion:**
- Nuevo campo `recibo_fecha_upload` (datetime) en `ActuacionSto` → hook_update_10030
- `PlazoEnforcementService::checkReciboDeadline(ActuacionSto $actuacion)`:
  - Calcula: `fecha_actuacion + 15 dias` = deadline
  - Si `recibo_servicio_id` es NULL y hoy > deadline → alerta CRITICO
  - Si `recibo_servicio_id` es NULL y hoy > deadline - 3 dias → alerta ALTO (prevencion)

### 5.2 Plazo 10 dias habiles (VoBo formacion)

**Normativa:** §5.1.B.3 — Solicitud VoBo con minimo 10 dias habiles antes del inicio del curso.

**Implementacion:**
- `PlazoEnforcementService::checkVoboDeadline(AccionFormativaEi $accion)`:
  - Calcula: `fecha_inicio_curso - 10 dias habiles` = deadline envio
  - Si `estado == 'borrador'` y hoy > deadline → alerta CRITICO (ya no hay tiempo)
  - Si `estado == 'borrador'` y hoy > deadline - 5 habiles → alerta ALTO (urgente)
  - Usa `diasHabiles()` para calculo correcto (excluir sabados/domingos/festivos)

### 5.3 Plazo 2 meses (incentivo participacion)

**Normativa:** §5.1.C — Pago del incentivo maximo 2 meses tras finalizar acciones de atencion.

**Implementacion:**
- `PlazoEnforcementService::checkIncentivoDeadline(ProgramaParticipanteEi $p)`:
  - Si `es_persona_atendida == TRUE` y `incentivo_recibido == FALSE` y `incentivo_renuncia == FALSE`:
    - Calcula: `fecha_persona_atendida + 60 dias` = deadline
    - Si hoy > deadline → alerta CRITICO
    - Si hoy > deadline - 15 dias → alerta ALTO

### 5.4 Plazo 18 meses (duracion programa)

**Normativa:** §3.1 — El Programa tiene duracion de 18 meses segun resolucion de concesion.

**Implementacion:**
- `PlazoEnforcementService::checkProgramaExpiry(int $tenantId)`:
  - Lee `fecha_inicio_programa` del tenant config
  - Calcula: `fecha_inicio + 18 meses` = fecha_fin
  - Si hoy > fecha_fin - 30 dias → alerta ALTO (1 mes para fin)
  - Si hoy > fecha_fin → BLOQUEAR nuevas inscripciones y actuaciones

### 5.5 Dias habiles: calculo correcto

**Normativa:** §5.1.B.3 especifica "dias habiles", no naturales.

**Metodo:** `PlazoEnforcementService::diasHabiles(int $dias, \DateTimeInterface $desde): \DateTimeInterface`
- Excluye sabados y domingos
- Excluye festivos nacionales y autonomicos (Andalucia)
- Constante FESTIVOS_2026 con fechas fijas (enero a diciembre)
- Patron: iterar dia a dia, incrementar solo si es laborable

### 5.6 Integracion en AlertasNormativasService

Ampliar `getAlertas(int $tenantId)` con llamadas a PlazoEnforcementService:

```php
// Inyectar PlazoEnforcementService como @? opcional
if ($this->plazoService) {
  $alertasPlazos = $this->plazoService->getAlertasPlazos($tenantId);
  $alertas = array_merge($alertas, $alertasPlazos);
}
```

Esto integra las alertas de plazos en el dashboard del coordinador sin romper el servicio existente.

### 5.7 Daily Action: PlazosVencidosAction

**Archivo:** `src/DailyActions/PlazosVencidosAction.php`

- ID: `coordinador_ei.plazos_vencidos`
- Dashboard: `coordinador_ei`
- Label: "Plazos normativos"
- Badge: count de alertas CRITICO + ALTO
- Color: `naranja-impulso` (urgencia)
- Ruta: dashboard del coordinador con filtro de alertas

---

## 6. GAP-3: Validacion insercion Seguridad Social

### 6.1 Criterios normativos

**Normativa §5.2.B:**
- 4 meses alta jornada completa cuenta ajena
- O inicio actividad por cuenta propia (autonomo)
- Periodos no consecutivos: minimo 1 mes continuado a tiempo completo o 2 meses a tiempo parcial
- Combinable entre regimenes SS excepto con Sistema Especial Agrario

### 6.2 Calculo automatico de duracion SS

**Servicio nuevo:** `InsercionValidatorService`

**Archivo:** `src/Service/InsercionValidatorService.php`

**Constructor DI:**
```php
public function __construct(
  protected readonly EntityTypeManagerInterface $entityTypeManager,
  protected readonly LoggerInterface $logger,
) {}
```

**Metodo principal:**
```php
public function validateInsercion(ProgramaParticipanteEi $participante): array {
  $tipo = $participante->get('tipo_insercion')->value;
  $fechaInsercion = $participante->get('fecha_insercion')->value;

  if (!$tipo || !$fechaInsercion) {
    return ['valid' => FALSE, 'message' => 'Tipo y fecha de inserción requeridos.'];
  }

  $mesesAlta = $this->calcularMesesAlta($participante);

  switch ($tipo) {
    case 'cuenta_ajena':
      $minMeses = 4; // Jornada completa
      break;
    case 'cuenta_propia':
      $minMeses = 4; // RETA
      break;
    case 'agrario':
      $minMeses = 3; // Sistema Especial Agrario
      break;
    default:
      return ['valid' => FALSE, 'message' => 'Tipo de inserción no válido.'];
  }

  return [
    'valid' => $mesesAlta >= $minMeses,
    'meses_alta' => $mesesAlta,
    'meses_requeridos' => $minMeses,
    'message' => $mesesAlta >= $minMeses
      ? 'Inserción válida: ' . $mesesAlta . ' meses.'
      : 'Insuficiente: ' . $mesesAlta . '/' . $minMeses . ' meses.',
  ];
}
```

### 6.3 Sector agrario (3 meses Sistema Especial)

**Normativa §5.2.B.1:** Sector agrario requiere minimo 3 meses en Sistema Especial
para Trabajadores por Cuenta Ajena Agrarios.

- Campo `tipo_insercion` ya tiene valor `agrario`
- `InsercionValidatorService::validateAgrario()` aplica el umbral de 3 meses
- NO combinable con otros regimenes para el computo de insercion

### 6.4 Combinacion regimenes SS

**Normativa §5.2.B:** Se pueden combinar Regimen General + RETA pero NO con Sistema Especial Agrario.

**Campo nuevo:** `periodos_alta_ss` (string_long, JSON) en `InsercionLaboral` entity

Estructura JSON:
```json
[
  {"regimen": "general", "fecha_inicio": "2026-06-01", "fecha_fin": "2026-08-31", "jornada": "completa"},
  {"regimen": "reta", "fecha_inicio": "2026-09-01", "fecha_fin": "2026-10-31", "jornada": "completa"}
]
```

Validacion: si algun periodo es `agrario`, no se combina con otros.

### 6.5 Integracion en FaseTransitionManager

Ampliar `verificarPrerrequisitos()` para la transicion a fase `seguimiento`:

```php
case 'seguimiento':
  // Existing: check ≥40h orientación inserción
  // NEW: validate inserción SS duration
  if ($this->insercionValidator) {
    $result = $this->insercionValidator->validateInsercion($participante);
    if (!$result['valid']) {
      return ['success' => FALSE, 'message' => $result['message']];
    }
  }
```

### 6.6 Desglose fiscal incentivo (528 EUR - IRPF 2%)

**Normativa Anexo IV:** Base imponible 528,00 EUR, IRPF 2% = 10,56 EUR, neto 517,44 EUR.

**Ampliar IncentiveReceiptService::generarRecibo():**
```php
$desglose = [
  'base_imponible' => 528.00,
  'irpf_porcentaje' => 2.0,
  'irpf_importe' => 10.56,
  'total_percibir' => 517.44,
];
```

---

## 7. Fase complementaria

### Restriccion formacion fines de semana

**Normativa §5.1.B.1/B.2:** No se validara formacion presencial ni online en fines de semana ni festivos.

**Implementacion:** Validacion en `SesionProgramadaEi::preSave()`:
```php
$diaSemana = (int) date('N', strtotime($fecha));
if ($diaSemana >= 6 && $this->isFormacion()) {
  // Warning: sesión formativa programada en fin de semana
}
```

### Coste maximo formacion presencial (11 EUR/alumno/hora)

**Normativa §5.1.B.1:** Importe maximo 11 EUR por alumno/hora de formacion presencial.

**Implementacion:** Validacion en `AccionFormativaEi`:
```php
public function getCosteAlumnoHora(): float {
  $costeTotal = (float) $this->get('coste_total')->value;
  $horas = (float) $this->get('horas_previstas')->value;
  $alumnos = (int) $this->get('alumnos_previstos')->value;
  if ($horas <= 0 || $alumnos <= 0) return 0;
  return $costeTotal / ($horas * $alumnos);
}
```

---

## 8. Verificacion RUNTIME-VERIFY-001

| # | Check | Metodo |
|---|-------|--------|
| 1 | Tabla ficha_tecnica_ei creada | `drush ev "echo \Drupal::database()->schema()->tableExists('ficha_tecnica_ei') ? 'YES' : 'NO';"` |
| 2 | Entity type installed | `drush ev "echo \Drupal::entityDefinitionUpdateManager()->getEntityType('ficha_tecnica_ei') ? 'YES' : 'NO';"` |
| 3 | Rutas accesibles | `curl /admin/content/fichas-tecnicas-ei` |
| 4 | PlazoEnforcementService funcional | `drush ev "echo \Drupal::hasService('jaraba_andalucia_ei.plazo_enforcement') ? 'YES' : 'NO';"` |
| 5 | InsercionValidatorService funcional | `drush ev "echo \Drupal::hasService('jaraba_andalucia_ei.insercion_validator') ? 'YES' : 'NO';"` |

### PIPELINE-E2E-001

| Capa | Verificacion |
|------|-------------|
| L1 | PlazoEnforcementService inyectado en AlertasNormativasService via @? |
| L2 | AlertasNormativasService pasa alertas plazos al controller coordinador |
| L3 | hook_theme() declara variables alertas_plazos |
| L4 | Template coordinador-dashboard muestra alertas plazos con badges |

---

## 9. Testing

### Unit Tests

**PlazoEnforcementServiceTest (8 tests):**
- testDiasHabilesExcluyeFinDeSemana()
- testDiasHabilesExcluyeFestivos()
- testCheckReciboDeadlineVencido()
- testCheckReciboDeadlineEnPlazo()
- testCheckVoboDeadlineDiasHabiles()
- testCheckIncentivoDeadlineVencido()
- testCheckProgramaExpiryBloqueo()
- testCheckProgramaExpiryAlerta()

**InsercionValidatorServiceTest (6 tests):**
- testCuentaAjena4Meses()
- testCuentaPropia4Meses()
- testAgrario3Meses()
- testInsuficienteMeses()
- testCombinacionRegimenes()
- testAgrarioNoCombina()

**FichaTecnicaEiTest (3 tests):**
- testRatioValidation1por60()
- testPersonalTecnicoJsonParsing()
- testExpedienteRefFormat()

---

## 10. Cronograma de ejecucion

| Fase | Componentes | Archivos |
|------|------------|----------|
| GAP-1 | FichaTecnicaEi entity + form + access + list builder + hook_update | 6 nuevos |
| GAP-2 | PlazoEnforcementService + diasHabiles() + Daily Action + alertas integration | 3 nuevos + 2 modificados |
| GAP-3 | InsercionValidatorService + FaseTransitionManager integration + desglose fiscal | 1 nuevo + 2 modificados |
| Complementaria | Validacion fines de semana + coste hora | 0 nuevos + 2 modificados |
| Testing | 17 unit tests | 3 nuevos |
| Docs | Master docs update | Commit separado |

**Total:** ~10 archivos nuevos + ~6 modificados + 17 tests

---

*Plan de implementacion generado 18 marzo 2026.*
*Basado en: Pautas Gestion Tecnica ICV 25 (SAE, 18/03/2026), CLAUDE.md v1.5.4, modulo jaraba_andalucia_ei.*
*Auditoria previa: 93% cumplimiento, 3 gaps P0, 4 gaps P1-P2.*
*Este plan es ejecutable por Claude Code sin ambiguedad.*
