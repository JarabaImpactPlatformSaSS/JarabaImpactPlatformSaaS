# Plan de Implementación: Reestructuración del Modelo de Actuaciones PIIL

**Fecha**: 2026-03-11
**Versión**: 1.0.0
**Módulo principal**: `jaraba_andalucia_ei`
**Módulos transversales**: `ecosistema_jaraba_core`, `ecosistema_jaraba_theme`, `jaraba_lms`
**Estado**: Planificado
**Prioridad**: P0 (Alineamiento normativo crítico)
**Sprint objetivo**: Sprint 14
**Rama**: `feature/piil-actuaciones-restructure`

---

## Índice de Navegación (TOC)

- [PARTE I — DIAGNÓSTICO Y CONTEXTO NORMATIVO](#parte-i--diagnóstico-y-contexto-normativo)
  - [1. Resumen Ejecutivo](#1-resumen-ejecutivo)
  - [2. Marco Normativo de Referencia](#2-marco-normativo-de-referencia)
    - [2.1. Estructura PIIL según BBRR (Orden 29/09/2023)](#21-estructura-piil-según-bbrr-orden-29092023)
    - [2.2. Estructura Operativa según Manual STO ICV25](#22-estructura-operativa-según-manual-sto-icv25)
    - [2.3. Conceptos Clave: Persona Atendida y Persona Insertada](#23-conceptos-clave-persona-atendida-y-persona-insertada)
  - [3. Diagnóstico de la Arquitectura Actual](#3-diagnóstico-de-la-arquitectura-actual)
    - [3.1. Entidades Existentes y su Rol](#31-entidades-existentes-y-su-rol)
    - [3.2. Gaps Identificados](#32-gaps-identificados)
    - [3.3. Matriz de Confusión Actual](#33-matriz-de-confusión-actual)
- [PARTE II — ARQUITECTURA PROPUESTA](#parte-ii--arquitectura-propuesta)
  - [4. Modelo de Datos Reestructurado](#4-modelo-de-datos-reestructurado)
    - [4.1. Taxonomía Canónica de Actuaciones](#41-taxonomía-canónica-de-actuaciones)
    - [4.2. Modificaciones a SesionProgramadaEi](#42-modificaciones-a-sesionprogramadaei)
    - [4.3. Modificaciones a AccionFormativaEi](#43-modificaciones-a-accionformativaei)
    - [4.4. Modificaciones a ActuacionSto](#44-modificaciones-a-actuacionsto)
    - [4.5. Modificaciones a PlanFormativoEi](#45-modificaciones-a-planformativoei)
    - [4.6. Modificaciones a ProgramaParticipanteEi](#46-modificaciones-a-programaparticipanteei)
    - [4.7. Modificaciones a InscripcionSesionEi](#47-modificaciones-a-inscripcionsesionei)
    - [4.8. Nueva Entidad: MaterialDidacticoEi](#48-nueva-entidad-materialdidacticoei)
  - [5. Servicios Afectados](#5-servicios-afectados)
    - [5.1. Servicios a Modificar](#51-servicios-a-modificar)
    - [5.2. Nuevo Servicio: ActuacionComputeService](#52-nuevo-servicio-actuacioncomputeservice)
  - [6. Flujo de Datos End-to-End](#6-flujo-de-datos-end-to-end)
    - [6.1. Flujo Fase de Atención](#61-flujo-fase-de-atención)
    - [6.2. Flujo Fase de Inserción](#62-flujo-fase-de-inserción)
    - [6.3. Flujo de Cómputo Automático](#63-flujo-de-cómputo-automático)
- [PARTE III — PLAN DE EJECUCIÓN](#parte-iii--plan-de-ejecución)
  - [7. Sprints y Tareas](#7-sprints-y-tareas)
    - [7.1. Sprint 14-A: Reestructuración de Entidades (Backend)](#71-sprint-14-a-reestructuración-de-entidades-backend)
    - [7.2. Sprint 14-B: Servicios y Lógica de Negocio](#72-sprint-14-b-servicios-y-lógica-de-negocio)
    - [7.3. Sprint 14-C: UI/UX del Dashboard Coordinador](#73-sprint-14-c-uiux-del-dashboard-coordinador)
    - [7.4. Sprint 14-D: Tests y Validación](#74-sprint-14-d-tests-y-validación)
  - [8. Migración de Datos](#8-migración-de-datos)
  - [9. Estrategia de Rollback](#9-estrategia-de-rollback)
- [PARTE IV — ESPECIFICACIONES TÉCNICAS](#parte-iv--especificaciones-técnicas)
  - [10. Tabla de Correspondencia: Especificaciones Técnicas](#10-tabla-de-correspondencia-especificaciones-técnicas)
  - [11. Tabla de Cumplimiento de Directrices](#11-tabla-de-cumplimiento-de-directrices)
  - [12. Criterios de Aceptación](#12-criterios-de-aceptación)
  - [13. Archivos Afectados](#13-archivos-afectados)

---

## PARTE I — DIAGNÓSTICO Y CONTEXTO NORMATIVO

### 1. Resumen Ejecutivo

El módulo `jaraba_andalucia_ei` gestiona el Programa de Itinerarios Integrados para el Empleo (PIIL) de la Junta de Andalucía, cofinanciado por el Fondo Social Europeo Plus (FSE+). La normativa reguladora (Orden de 29/09/2023 de BBRR y Manual Técnico STO ICV25) establece una estructura clara de actuaciones organizada en dos fases: **Atención** e **Inserción**, cada una con tipos de actuaciones diferenciados.

**Problema**: La arquitectura actual mezcla en la entidad `SesionProgramadaEi` tipos de sesión que corresponden a fases y naturalezas distintas. La constante `TIPOS_SESION` incluye tanto `formacion_presencial`/`formacion_online` (que deberían ser hijas de `AccionFormativaEi`) como `orientacion_individual`/`orientacion_grupal` (que no distingue entre orientación laboral de Fase Atención y orientación para la inserción de Fase Inserción). Además, faltan campos de alineamiento con el STO (`contenido_sto`, `subcontenido_sto`), campos de materiales didácticos, y la lógica de cómputo de `es_persona_atendida`/`es_persona_insertada` no está implementada.

**Objetivo**: Reestructurar el modelo de datos para que refleje fielmente la normativa, permita la trazabilidad completa de actuaciones para justificación FSE+, y habilite el cómputo automático de los indicadores de persona atendida/insertada.

**Impacto estimado**: 8 entidades modificadas, 1 entidad nueva, 6 servicios modificados, 1 servicio nuevo, 2 hook_update_N(), migración de datos existentes.

---

### 2. Marco Normativo de Referencia

#### 2.1. Estructura PIIL según BBRR (Orden 29/09/2023)

La Orden de 29 de septiembre de 2023 (BOJA) establece las Bases Reguladoras para la concesión de subvenciones en régimen de concurrencia competitiva para la puesta en marcha de Itinerarios Integrados para el Empleo en Andalucía (PIIL). El Artículo 2 define los conceptos subvencionables:

```
PIIL — Itinerario Integrado para el Empleo
├── A) Acciones para la ATENCIÓN
│   ├── 1. Actuaciones de Orientación Laboral
│   │   ├── Sesiones individuales (diagnóstico, diseño itinerario, seguimiento)
│   │   └── Sesiones grupales de orientación (búsqueda activa, competencias)
│   │
│   └── 2. Actuaciones de Formación (SIEMPRE grupales)
│       ├── Acciones formativas (requieren VoBo SAE)
│       └── Sesiones formativas (hijas de cada acción formativa)
│
└── B) Acciones para la INSERCIÓN
    ├── 1. Actuaciones de Orientación para la Inserción
    │   ├── Sesiones individuales (acompañamiento, intermediación)
    │   └── Sesiones grupales (networking, emprendimiento, clubes empleo)
    │
    └── 2. Actuaciones de Prospección Empresarial
        └── Visitas, contactos, acuerdos con empresas
```

**Nota crítica**: Las actuaciones de orientación de Fase Atención y las de Fase Inserción son TIPOS DISTINTOS con finalidades diferentes:
- **Orientación Laboral (Atención)**: diagnóstico, diseño de itinerario, adquisición de competencias transversales
- **Orientación para la Inserción (Inserción)**: acompañamiento activo en la búsqueda, intermediación directa con empresas, clubes de empleo

#### 2.2. Estructura Operativa según Manual STO ICV25

El Manual de Gestión del Personal Técnico del STO (ICV25_012026) define los flujos operativos:

**Sección 3.4 — Acciones individuales de orientación:**
- Campos: Contenido (dropdown STO), Subcontenido (dropdown STO dependiente), Duración, Observaciones
- Contenidos tipificados: "Información", "Diagnóstico", "Diseño/Planificación", "Desarrollo", "Seguimiento", etc.
- Subcontenidos dependientes del contenido seleccionado

**Sección 4 — Actuaciones grupales:**
- 4.1. Sesiones grupales de orientación: max 20 participantes, contenido tipificado
- 4.2. Acciones de formación:
  - 4.2.1. Programación (datos de la acción: título, horas, modalidad, fechas)
  - 4.2.2. Inscripción de participantes
  - 4.2.3. **Solicitud de VoBo al SAE** (flujo de 8 estados: borrador → enviada → favorable/desfavorable/silencio)
  - 4.2.4. Registro de asistencia por sesión

**Campos del STO no presentes en la arquitectura actual:**
- `contenido_sto`: selección predefinida del dropdown del STO
- `subcontenido_sto`: selección dependiente de `contenido_sto`
- Estos campos son obligatorios para la justificación ante la DG de Empleo

#### 2.3. Conceptos Clave: Persona Atendida y Persona Insertada

**Persona Atendida** (Art. 5 BBRR):
- Mínimo 10 horas de orientación laboral (de las cuales al menos 2h individuales)
- Mínimo 50 horas de formación
- Asistencia mínima del 75% a las acciones formativas en las que participa
- Cómputo: `horas_orientacion_laboral >= 10 AND horas_orientacion_individual >= 2 AND horas_formacion >= 50 AND porcentaje_asistencia >= 75`

**Persona Insertada** (Art. 5 BBRR):
- Ser persona atendida (requisito previo)
- Mínimo 40 horas de orientación para la inserción
- Contrato de trabajo de mínimo 4 meses de duración o alta en RETA
- Cómputo: `es_persona_atendida AND horas_orientacion_insercion >= 40 AND (contrato_minimo_4_meses OR alta_reta)`

**Nota**: Los cómputos de horas son la suma de horas de actuaciones con `asistencia = TRUE` en las inscripciones del participante, filtradas por tipo de actuación y fase.

---

### 3. Diagnóstico de la Arquitectura Actual

#### 3.1. Entidades Existentes y su Rol

| Entidad | Campos clave | Rol actual | Rol correcto |
|---------|-------------|------------|--------------|
| `AccionFormativaEi` | titulo, horas_totales, modalidad, estado_vobo (8 estados), course_id, interactive_content_id | Acción formativa con workflow VoBo | Correcto, pero falta contenido_sto, subcontenido_sto, materiales |
| `SesionProgramadaEi` | tipo_sesion (6 valores mezclados), accion_formativa_id, fecha, duracion_horas | Sesión genérica para TODO | Debe limitarse a sesiones de orientación y sesiones formativas (hijas de AccionFormativaEi) |
| `PlanFormativoEi` | accion_formativa_ids (JSON), horas_formacion/orientacion_previstas (computed) | Integración de acciones | Correcto, ampliar con fase y cumplimiento |
| `ActuacionSto` | tipo_actuacion (6 valores sin fase), contenido, duracion, participante_id | Registro unitario de actuación | Falta campo `fase` para distinguir Atención vs Inserción |
| `InscripcionSesionEi` | sesion_id, participante_id, asistencia, actuacion_sto_id | Registro de asistencia | Correcto, falta lógica auto-generación ActuacionSto |
| `ProgramaParticipanteEi` | es_persona_atendida, es_persona_insertada (sin cómputo), 50+ campos | Participante del programa | Falta implementar lógica de cómputo |

#### 3.2. Gaps Identificados

**GAP-01: Mezcla de tipos en SesionProgramadaEi**
La constante `TIPOS_SESION` actual:
```php
'formacion_presencial' => 'Formación presencial',
'formacion_online' => 'Formación online',
'orientacion_individual' => 'Orientación individual',
'orientacion_grupal' => 'Orientación grupal',
'tutoria' => 'Tutoría',
'taller' => 'Taller',
```
Problemas:
- `formacion_presencial`/`formacion_online` deberían ser la modalidad de `AccionFormativaEi`, no un tipo de sesión independiente. Las sesiones formativas son HIJAS de una acción formativa aprobada con VoBo.
- `orientacion_individual`/`orientacion_grupal` no distingue si pertenece a Fase Atención (orientación laboral) o Fase Inserción (orientación para la inserción).
- `tutoria` y `taller` son subtipos que no aparecen como categorías autónomas en la normativa.

**GAP-02: Sin campo `fase` en ActuacionSto**
`TIPOS_ACTUACION` no distingue fase:
```php
'orientacion_individual' => 'Orientación individual',
'orientacion_grupal' => 'Orientación grupal',
'formacion' => 'Formación',
'tutoria' => 'Tutoría',
'prospeccion' => 'Prospección empresarial',
'intermediacion' => 'Intermediación laboral',
```
Sin `fase`, es imposible saber si una "orientación individual" computa para persona atendida (Fase Atención) o persona insertada (Fase Inserción).

**GAP-03: Sin campos STO en actuaciones**
Las actuaciones carecen de `contenido_sto` y `subcontenido_sto`, campos obligatorios para el reporte al STO y la justificación FSE+.

**GAP-04: Sin materiales didácticos en AccionFormativaEi**
Las acciones formativas no pueden vincularse a materiales didácticos, recursos o contenidos del LMS más allá de `course_id` (referencia simple a jaraba_lms).

**GAP-05: Cómputo de persona atendida/insertada NO implementado**
Los campos `es_persona_atendida` y `es_persona_insertada` en `ProgramaParticipanteEi` están definidos como booleanos almacenados pero NO tienen lógica de recalculación en `preSave()` ni en ningún servicio. La descripción del campo documenta los requisitos, pero el código no los implementa.

**GAP-06: Sin vínculo obligatorio sesión→acción formativa para formación**
Una sesión de tipo `formacion_presencial` puede crearse sin `accion_formativa_id`, lo que genera sesiones huérfanas que no pasan por el workflow VoBo.

#### 3.3. Matriz de Confusión Actual

| Concepto normativo | Entidad actual | Problema |
|-------------------|---------------|----------|
| Orientación laboral individual (Atención) | SesionProgramadaEi tipo=orientacion_individual | No distingue fase |
| Orientación laboral grupal (Atención) | SesionProgramadaEi tipo=orientacion_grupal | No distingue fase |
| Sesión formativa presencial (Atención) | SesionProgramadaEi tipo=formacion_presencial | Debería ser hija de AccionFormativaEi |
| Sesión formativa online (Atención) | SesionProgramadaEi tipo=formacion_online | Debería ser hija de AccionFormativaEi |
| Orientación inserción individual (Inserción) | SesionProgramadaEi tipo=orientacion_individual | Mismo tipo que orientación Atención |
| Orientación inserción grupal (Inserción) | SesionProgramadaEi tipo=orientacion_grupal | Mismo tipo que orientación Atención |
| Prospección empresarial (Inserción) | ProspeccionEmpresarial entity | Correcto |
| Tutoría | SesionProgramadaEi tipo=tutoria | Sin categoría normativa clara |
| Taller | SesionProgramadaEi tipo=taller | Subconjunto de orientación grupal |

---

## PARTE II — ARQUITECTURA PROPUESTA

### 4. Modelo de Datos Reestructurado

#### 4.1. Taxonomía Canónica de Actuaciones

La nueva taxonomía refleja fielmente la normativa PIIL:

```
PIIL — Modelo de Datos Reestructurado
│
├── SesionProgramadaEi (reestructurada)
│   ├── tipo_sesion:
│   │   ├── orientacion_laboral_individual    ← Fase Atención
│   │   ├── orientacion_laboral_grupal        ← Fase Atención
│   │   ├── orientacion_insercion_individual  ← Fase Inserción
│   │   ├── orientacion_insercion_grupal      ← Fase Inserción
│   │   └── tutoria_seguimiento               ← Transversal
│   ├── fase: atencion | insercion (computado desde tipo_sesion)
│   ├── contenido_sto + subcontenido_sto (nuevos)
│   └── accion_formativa_id: NULL (SIEMPRE para orientación)
│
├── AccionFormativaEi (ampliada)
│   ├── modalidad: presencial | online | mixta (ya existe parcialmente)
│   ├── contenido_sto + subcontenido_sto (nuevos)
│   ├── materiales (nuevo: referencia a MaterialDidacticoEi)
│   └── Sesiones hijas: SesionProgramadaEi con tipo=sesion_formativa
│       └── tipo_sesion: sesion_formativa (nuevo valor exclusivo)
│
├── PlanFormativoEi (ampliado)
│   ├── accion_formativa_ids (JSON, ya existe)
│   ├── cumple_persona_atendida (computado, nuevo)
│   └── Integra AccionFormativaEi aprobadas con VoBo
│
├── ActuacionSto (ampliada)
│   ├── fase: atencion | insercion (nuevo campo)
│   ├── contenido_sto + subcontenido_sto (nuevos)
│   └── tipo_actuacion actualizado con distinción de fase
│
├── MaterialDidacticoEi (NUEVA entidad)
│   ├── titulo, descripcion, tipo_material, archivo/url
│   └── Vinculable a AccionFormativaEi y sesiones
│
└── ProgramaParticipanteEi (con cómputo)
    ├── es_persona_atendida (con lógica implementada)
    └── es_persona_insertada (con lógica implementada)
```

#### 4.2. Modificaciones a SesionProgramadaEi

**Archivo**: `web/modules/custom/jaraba_andalucia_ei/src/Entity/SesionProgramadaEi.php`
**Interface**: `web/modules/custom/jaraba_andalucia_ei/src/Entity/SesionProgramadaEiInterface.php`

**Cambio 1: Reemplazar TIPOS_SESION**

Constante actual (6 valores mezclados) → Constante nueva (6 valores alineados con normativa):

```php
// ANTES (confuso):
public const TIPOS_SESION = [
  'formacion_presencial', 'formacion_online',
  'orientacion_individual', 'orientacion_grupal',
  'tutoria', 'taller',
];

// DESPUÉS (alineado con BBRR):
public const TIPOS_SESION = [
  'orientacion_laboral_individual' => 'Orientación laboral individual',
  'orientacion_laboral_grupal' => 'Orientación laboral grupal',
  'orientacion_insercion_individual' => 'Orientación para la inserción individual',
  'orientacion_insercion_grupal' => 'Orientación para la inserción grupal',
  'sesion_formativa' => 'Sesión formativa',
  'tutoria_seguimiento' => 'Tutoría de seguimiento',
];

// Mapa de fase por tipo (para cómputo automático):
public const FASE_POR_TIPO = [
  'orientacion_laboral_individual' => 'atencion',
  'orientacion_laboral_grupal' => 'atencion',
  'orientacion_insercion_individual' => 'insercion',
  'orientacion_insercion_grupal' => 'insercion',
  'sesion_formativa' => 'atencion',
  'tutoria_seguimiento' => 'transversal',
];
```

**Cambio 2: Nuevo campo `fase`** (computed desde tipo_sesion)

```php
$fields['fase'] = BaseFieldDefinition::create('list_string')
  ->setLabel(t('Fase PIIL'))
  ->setDescription(t('Fase del itinerario: Atención o Inserción. Calculado automáticamente desde el tipo de sesión.'))
  ->setSetting('allowed_values', [
    'atencion' => 'Fase de Atención',
    'insercion' => 'Fase de Inserción',
    'transversal' => 'Transversal',
  ])
  ->setDisplayConfigurable('view', TRUE)
  ->setDisplayConfigurable('form', FALSE); // Computado, no editable
```

En `preSave()`:
```php
$tipo = $this->get('tipo_sesion')->value;
$this->set('fase', static::FASE_POR_TIPO[$tipo] ?? 'transversal');
```

**Cambio 3: Nuevos campos STO**

```php
$fields['contenido_sto'] = BaseFieldDefinition::create('list_string')
  ->setLabel(t('Contenido STO'))
  ->setDescription(t('Contenido según tipificación del STO. Obligatorio para justificación FSE+.'))
  ->setSetting('allowed_values_function', 'jaraba_andalucia_ei_contenidos_sto')
  ->setDisplayConfigurable('form', TRUE)
  ->setDisplayConfigurable('view', TRUE);

$fields['subcontenido_sto'] = BaseFieldDefinition::create('list_string')
  ->setLabel(t('Subcontenido STO'))
  ->setDescription(t('Subcontenido dependiente del contenido STO seleccionado.'))
  ->setSetting('allowed_values_function', 'jaraba_andalucia_ei_subcontenidos_sto')
  ->setDisplayConfigurable('form', TRUE)
  ->setDisplayConfigurable('view', TRUE);
```

**Cambio 4: Validación de accion_formativa_id**

En `preSave()`, si `tipo_sesion === 'sesion_formativa'`, el campo `accion_formativa_id` es OBLIGATORIO. Para todos los demás tipos, debe ser NULL.

```php
if ($this->get('tipo_sesion')->value === 'sesion_formativa') {
  if (empty($this->get('accion_formativa_id')->target_id)) {
    throw new \InvalidArgumentException('Las sesiones formativas DEBEN estar vinculadas a una acción formativa.');
  }
}
else {
  $this->set('accion_formativa_id', NULL);
}
```

**Cambio 5: Migración de valores legacy**

Mapa de migración para datos existentes:

| Valor actual | Valor nuevo | Criterio |
|-------------|-------------|----------|
| `formacion_presencial` | `sesion_formativa` | Si tiene accion_formativa_id |
| `formacion_online` | `sesion_formativa` | Si tiene accion_formativa_id |
| `orientacion_individual` | `orientacion_laboral_individual` | Default (sin forma de distinguir fase) |
| `orientacion_grupal` | `orientacion_laboral_grupal` | Default (sin forma de distinguir fase) |
| `tutoria` | `tutoria_seguimiento` | Directo |
| `taller` | `orientacion_laboral_grupal` | Los talleres son orientación grupal |

**Nota de migración**: Las sesiones de orientación existentes se migran a Fase Atención por defecto. El coordinador deberá revisar manualmente las que pertenezcan a Fase Inserción, usando una vista administrativa con filtro de fecha y participante para facilitar la reclasificación.

#### 4.3. Modificaciones a AccionFormativaEi

**Archivo**: `web/modules/custom/jaraba_andalucia_ei/src/Entity/AccionFormativaEi.php`

**Cambio 1: Nuevos campos STO**

```php
$fields['contenido_sto'] = BaseFieldDefinition::create('list_string')
  ->setLabel(t('Contenido STO'))
  ->setDescription(t('Tipificación de contenido según STO para acciones formativas.'))
  ->setSetting('allowed_values_function', 'jaraba_andalucia_ei_contenidos_formacion_sto')
  ->setDisplayConfigurable('form', TRUE)
  ->setDisplayConfigurable('view', TRUE);

$fields['subcontenido_sto'] = BaseFieldDefinition::create('list_string')
  ->setLabel(t('Subcontenido STO'))
  ->setDescription(t('Subcontenido formativo según tipificación STO.'))
  ->setSetting('allowed_values_function', 'jaraba_andalucia_ei_subcontenidos_formacion_sto')
  ->setDisplayConfigurable('form', TRUE)
  ->setDisplayConfigurable('view', TRUE);
```

**Cambio 2: Campo de materiales didácticos**

```php
$fields['materiales'] = BaseFieldDefinition::create('entity_reference')
  ->setLabel(t('Materiales Didácticos'))
  ->setDescription(t('Materiales y recursos vinculados a esta acción formativa.'))
  ->setSetting('target_type', 'material_didactico_ei')
  ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
  ->setDisplayOptions('form', [
    'type' => 'entity_reference_autocomplete',
    'weight' => 10,
  ])
  ->setDisplayConfigurable('form', TRUE)
  ->setDisplayConfigurable('view', TRUE);
```

**Cambio 3: Campo `modalidad` (si no existe como list_string)**

Verificar que existe con valores alineados:
```php
public const MODALIDADES = [
  'presencial' => 'Presencial',
  'online' => 'Online',
  'mixta' => 'Mixta',
];
```

**Cambio 4: Método helper para obtener sesiones hijas**

```php
/**
 * Obtiene todas las sesiones formativas programadas para esta acción.
 *
 * @return \Drupal\jaraba_andalucia_ei\Entity\SesionProgramadaEiInterface[]
 */
public function getSesionesFormativas(): array {
  return \Drupal::entityTypeManager()
    ->getStorage('sesion_programada_ei')
    ->loadByProperties([
      'accion_formativa_id' => $this->id(),
      'tipo_sesion' => 'sesion_formativa',
    ]);
}
```

#### 4.4. Modificaciones a ActuacionSto

**Archivo**: `web/modules/custom/jaraba_andalucia_ei/src/Entity/ActuacionSto.php`

**Cambio 1: Nuevo campo `fase`**

```php
$fields['fase'] = BaseFieldDefinition::create('list_string')
  ->setLabel(t('Fase PIIL'))
  ->setDescription(t('Fase del itinerario a la que pertenece esta actuación.'))
  ->setSetting('allowed_values', [
    'atencion' => 'Fase de Atención',
    'insercion' => 'Fase de Inserción',
  ])
  ->setRequired(TRUE)
  ->setDisplayConfigurable('form', TRUE)
  ->setDisplayConfigurable('view', TRUE);
```

**Cambio 2: Actualizar TIPOS_ACTUACION para incluir distinción de fase**

```php
// Los tipos se mantienen pero el campo `fase` los contextualiza:
public const TIPOS_ACTUACION = [
  'orientacion_laboral_individual' => 'Orientación laboral individual',
  'orientacion_laboral_grupal' => 'Orientación laboral grupal',
  'orientacion_insercion_individual' => 'Orientación inserción individual',
  'orientacion_insercion_grupal' => 'Orientación inserción grupal',
  'formacion' => 'Formación',
  'tutoria' => 'Tutoría de seguimiento',
  'prospeccion' => 'Prospección empresarial',
  'intermediacion' => 'Intermediación laboral',
];

// Fase se auto-computa pero también es editable para corrección:
public const FASE_POR_TIPO = [
  'orientacion_laboral_individual' => 'atencion',
  'orientacion_laboral_grupal' => 'atencion',
  'orientacion_insercion_individual' => 'insercion',
  'orientacion_insercion_grupal' => 'insercion',
  'formacion' => 'atencion',
  'tutoria' => 'atencion',
  'prospeccion' => 'insercion',
  'intermediacion' => 'insercion',
];
```

**Cambio 3: Nuevos campos STO**

```php
$fields['contenido_sto'] = BaseFieldDefinition::create('list_string')
  ->setLabel(t('Contenido STO'))
  ->setDescription(t('Contenido de la actuación según tipificación del STO.'))
  ->setSetting('allowed_values_function', 'jaraba_andalucia_ei_contenidos_sto')
  ->setDisplayConfigurable('form', TRUE)
  ->setDisplayConfigurable('view', TRUE);

$fields['subcontenido_sto'] = BaseFieldDefinition::create('list_string')
  ->setLabel(t('Subcontenido STO'))
  ->setDescription(t('Subcontenido según tipificación STO.'))
  ->setSetting('allowed_values_function', 'jaraba_andalucia_ei_subcontenidos_sto')
  ->setDisplayConfigurable('form', TRUE)
  ->setDisplayConfigurable('view', TRUE);
```

#### 4.5. Modificaciones a PlanFormativoEi

**Archivo**: `web/modules/custom/jaraba_andalucia_ei/src/Entity/PlanFormativoEi.php`

**Cambio 1: Campos de cómputo enriquecidos**

Añadir al `preSave()` existente:

```php
// Nuevos campos computados:
$fields['horas_orientacion_insercion_previstas'] = BaseFieldDefinition::create('decimal')
  ->setLabel(t('Horas orientación inserción previstas'))
  ->setDescription(t('Horas de orientación para la inserción planificadas.'))
  ->setSetting('precision', 10)
  ->setSetting('scale', 2)
  ->setDisplayConfigurable('view', TRUE);

$fields['cumple_persona_atendida'] = BaseFieldDefinition::create('boolean')
  ->setLabel(t('¿Cumple persona atendida?'))
  ->setDescription(t('TRUE cuando el plan cubre ≥10h orientación laboral + ≥50h formación.'))
  ->setDefaultValue(FALSE)
  ->setDisplayConfigurable('view', TRUE);

$fields['cumple_persona_insertada'] = BaseFieldDefinition::create('boolean')
  ->setLabel(t('¿Cumple persona insertada?'))
  ->setDescription(t('TRUE cuando el plan cubre los requisitos de persona atendida + ≥40h orientación inserción.'))
  ->setDefaultValue(FALSE)
  ->setDisplayConfigurable('view', TRUE);
```

En `preSave()`, ampliar la lógica existente:
```php
// Existente: horas_formacion_previstas, horas_orientacion_previstas, horas_totales_previstas
// Nuevo:
$this->set('cumple_persona_atendida',
  $this->get('horas_orientacion_previstas')->value >= 10
  && $this->get('horas_formacion_previstas')->value >= 50
);
$this->set('cumple_persona_insertada',
  $this->get('cumple_persona_atendida')->value
  && $this->get('horas_orientacion_insercion_previstas')->value >= 40
);
```

#### 4.6. Modificaciones a ProgramaParticipanteEi

**Archivo**: `web/modules/custom/jaraba_andalucia_ei/src/Entity/ProgramaParticipanteEi.php`

**Cambio principal**: Implementar la lógica de cómputo que actualmente solo está documentada en la descripción de los campos.

Se creará un nuevo servicio `ActuacionComputeService` (ver sección 5.2) que recalcula los valores. El `preSave()` de `ProgramaParticipanteEi` NO implementará la lógica directamente porque requiere consultas a otras entidades (InscripcionSesionEi, ActuacionSto), lo cual es costoso en preSave. En su lugar:

**Nuevos campos de desglose de horas (para trazabilidad):**

```php
$fields['horas_orientacion_laboral'] = BaseFieldDefinition::create('decimal')
  ->setLabel(t('Horas orientación laboral'))
  ->setDescription(t('Total horas de orientación laboral (Fase Atención) con asistencia confirmada.'))
  ->setSetting('precision', 10)
  ->setSetting('scale', 2)
  ->setDefaultValue(0)
  ->setDisplayConfigurable('view', TRUE);

$fields['horas_orientacion_laboral_individual'] = BaseFieldDefinition::create('decimal')
  ->setLabel(t('Horas orientación laboral individual'))
  ->setDescription(t('Desglose: horas individuales de orientación laboral.'))
  ->setSetting('precision', 10)
  ->setSetting('scale', 2)
  ->setDefaultValue(0)
  ->setDisplayConfigurable('view', TRUE);

$fields['horas_formacion'] = BaseFieldDefinition::create('decimal')
  ->setLabel(t('Horas formación'))
  ->setDescription(t('Total horas de formación con asistencia confirmada (≥75%).'))
  ->setSetting('precision', 10)
  ->setSetting('scale', 2)
  ->setDefaultValue(0)
  ->setDisplayConfigurable('view', TRUE);

$fields['horas_orientacion_insercion'] = BaseFieldDefinition::create('decimal')
  ->setLabel(t('Horas orientación inserción'))
  ->setDescription(t('Total horas de orientación para la inserción (Fase Inserción) con asistencia confirmada.'))
  ->setSetting('precision', 10)
  ->setSetting('scale', 2)
  ->setDefaultValue(0)
  ->setDisplayConfigurable('view', TRUE);

$fields['porcentaje_asistencia_formacion'] = BaseFieldDefinition::create('decimal')
  ->setLabel(t('% asistencia formación'))
  ->setDescription(t('Porcentaje de asistencia a sesiones formativas.'))
  ->setSetting('precision', 5)
  ->setSetting('scale', 2)
  ->setDefaultValue(0)
  ->setDisplayConfigurable('view', TRUE);
```

**Lógica de `es_persona_atendida` (en ActuacionComputeService):**
```php
$es_atendida = (
  $participante->get('horas_orientacion_laboral')->value >= 10.0
  && $participante->get('horas_orientacion_laboral_individual')->value >= 2.0
  && $participante->get('horas_formacion')->value >= 50.0
  && $participante->get('porcentaje_asistencia_formacion')->value >= 75.0
);
```

**Lógica de `es_persona_insertada` (en ActuacionComputeService):**
```php
$es_insertada = (
  $participante->get('es_persona_atendida')->value
  && $participante->get('horas_orientacion_insercion')->value >= 40.0
  && $this->tieneContratoValido($participante) // ≥4 meses SS o alta RETA
);
```

#### 4.7. Modificaciones a InscripcionSesionEi

**Archivo**: `web/modules/custom/jaraba_andalucia_ei/src/Entity/InscripcionSesionEi.php`

**Cambio: Trigger de recalculación en postSave()**

Cuando una inscripción cambia su campo `asistencia`, debe disparar la recalculación del participante:

```php
public function postSave(EntityStorageInterface $storage, $update = TRUE): void {
  parent::postSave($storage, $update);

  // Si cambió el campo asistencia, recalcular indicadores del participante.
  if (!$update || $this->get('asistencia')->value !== $this->original->get('asistencia')->value) {
    $participante_id = $this->get('participante_id')->target_id;
    if ($participante_id && \Drupal::hasService('jaraba_andalucia_ei.actuacion_compute')) {
      try {
        \Drupal::service('jaraba_andalucia_ei.actuacion_compute')
          ->recalcularIndicadores((int) $participante_id);
      }
      catch (\Throwable $e) {
        \Drupal::logger('jaraba_andalucia_ei')->error(
          'Error recalculando indicadores para participante @id: @message',
          ['@id' => $participante_id, '@message' => $e->getMessage()]
        );
      }
    }
  }
}
```

#### 4.8. Nueva Entidad: MaterialDidacticoEi

**Archivo nuevo**: `web/modules/custom/jaraba_andalucia_ei/src/Entity/MaterialDidacticoEi.php`

Entidad ligera para gestionar materiales y recursos didácticos vinculables a acciones formativas.

**Campos:**

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | integer (autoincrement) | ID primario |
| `titulo` | string(255) | Título del material |
| `descripcion` | text_long | Descripción del contenido y objetivos |
| `tipo_material` | list_string | Tipo: documento, video, presentacion, guia, ejercicio, evaluacion |
| `archivo` | file | Archivo adjunto (PDF, DOCX, PPTX, etc.) |
| `url_externa` | uri | URL externa (si el recurso está en plataforma LMS u otro servicio) |
| `duracion_estimada` | decimal(5,2) | Duración estimada en horas |
| `tenant_id` | entity_reference → tenant | Aislamiento multi-tenant |
| `created` | created | Timestamp de creación |
| `changed` | changed | Timestamp de última modificación |
| `owner_id` | entity_reference → user | Autor/responsable |

**Anotaciones requeridas** (conforme AUDIT-CONS-001):
- `"access"` = `"Drupal\jaraba_andalucia_ei\MaterialDidacticoEiAccessControlHandler"`
- `"views_data"` = `"Drupal\views\EntityViewsData"` (conforme Views integration)
- `field_ui_base_route` para Field UI (conforme FIELD-UI-SETTINGS-TAB-001)

**Scaffold**: Usar `/create-entity jaraba_andalucia_ei material_didactico_ei` para generar:
- Entity class con baseFieldDefinitions()
- Interface
- AccessControlHandler con tenant isolation (TENANT-ISOLATION-ACCESS-001)
- Form extendiendo PremiumEntityFormBase (PREMIUM-FORMS-PATTERN-001)
- hook_theme() + template_preprocess_material_didactico_ei() (ENTITY-PREPROCESS-001)
- Routing (admin/content + slide-panel)
- hook_update_N() con installEntityType() (UPDATE-HOOK-REQUIRED-001)

---

### 5. Servicios Afectados

#### 5.1. Servicios a Modificar

| Servicio | Archivo | Cambios |
|---------|---------|---------|
| `CoordinadorHubService` | `src/Service/CoordinadorHubService.php` | Actualizar queries de sesiones para usar nuevos tipos, separar estadísticas por fase |
| `SesionProgramadaService` | `src/Service/SesionProgramadaService.php` | Validar tipos nuevos, enforce accion_formativa_id para sesion_formativa |
| `InscripcionSesionService` | `src/Service/InscripcionSesionService.php` | Integrar trigger de recalculación, pasar fase a ActuacionSto |
| `ActuacionStoService` | `src/Service/ActuacionStoService.php` | Crear actuaciones con campo fase y campos STO |
| `VoboSaeWorkflowService` | `src/Service/VoboSaeWorkflowService.php` | Sin cambios estructurales (el workflow VoBo solo aplica a AccionFormativaEi) |
| `EsfPlusIndicadorService` | `src/Service/EsfPlusIndicadorService.php` | Leer es_persona_atendida/insertada computados en vez de lógica manual |

**Detalle CoordinadorHubService:**

El método `getFormacionStats()` actualmente cuenta sesiones con `tipo_sesion IN ('formacion_presencial', 'formacion_online')`. Debe cambiar a:
```php
// ANTES:
->condition('tipo_sesion', ['formacion_presencial', 'formacion_online'], 'IN')

// DESPUÉS:
->condition('tipo_sesion', 'sesion_formativa')
```

Nuevo método `getEstadisticasPorFase()`:
```php
public function getEstadisticasPorFase(int $programa_id): array {
  return [
    'atencion' => [
      'orientacion_laboral' => $this->getHorasPorFaseYTipo($programa_id, 'atencion', ['orientacion_laboral_individual', 'orientacion_laboral_grupal']),
      'formacion' => $this->getHorasFormacion($programa_id),
    ],
    'insercion' => [
      'orientacion_insercion' => $this->getHorasPorFaseYTipo($programa_id, 'insercion', ['orientacion_insercion_individual', 'orientacion_insercion_grupal']),
      'prospeccion' => $this->getActuacionesProspeccion($programa_id),
    ],
  ];
}
```

#### 5.2. Nuevo Servicio: ActuacionComputeService

**Archivo nuevo**: `src/Service/ActuacionComputeService.php`

**Responsabilidades:**
1. Recalcular horas desglosadas por tipo y fase para un participante
2. Evaluar si el participante cumple los requisitos de persona atendida
3. Evaluar si el participante cumple los requisitos de persona insertada
4. Actualizar los campos en ProgramaParticipanteEi

**Dependencias:**
```yaml
jaraba_andalucia_ei.actuacion_compute:
  class: Drupal\jaraba_andalucia_ei\Service\ActuacionComputeService
  arguments:
    - '@entity_type.manager'
    - '@logger.channel.jaraba_andalucia_ei'
```

**Interfaz pública:**
```php
interface ActuacionComputeServiceInterface {
  /**
   * Recalcula todos los indicadores de cumplimiento de un participante.
   *
   * Consulta InscripcionSesionEi + ActuacionSto para sumar horas por tipo/fase,
   * evalúa persona atendida e insertada, y actualiza ProgramaParticipanteEi.
   *
   * @param int $participante_id
   *   ID del ProgramaParticipanteEi.
   *
   * @return array
   *   Array con los indicadores calculados:
   *   - horas_orientacion_laboral: float
   *   - horas_orientacion_laboral_individual: float
   *   - horas_formacion: float
   *   - porcentaje_asistencia_formacion: float
   *   - horas_orientacion_insercion: float
   *   - es_persona_atendida: bool
   *   - es_persona_insertada: bool
   */
  public function recalcularIndicadores(int $participante_id): array;

  /**
   * Recalcula indicadores para todos los participantes de un programa.
   *
   * Ejecuta en batch para evitar timeouts. Ideal para recalculación nocturna
   * o tras migraciones masivas.
   *
   * @param int $programa_id
   *   ID del programa.
   *
   * @return int
   *   Número de participantes actualizados.
   */
  public function recalcularPrograma(int $programa_id): int;
}
```

**Algoritmo de `recalcularIndicadores()`:**

```php
public function recalcularIndicadores(int $participante_id): array {
  $participante = $this->entityTypeManager
    ->getStorage('programa_participante_ei')
    ->load($participante_id);

  if (!$participante) {
    return [];
  }

  // 1. Obtener todas las inscripciones con asistencia=TRUE del participante.
  $inscripciones = $this->entityTypeManager
    ->getStorage('inscripcion_sesion_ei')
    ->loadByProperties([
      'participante_id' => $participante_id,
      'asistencia' => TRUE,
    ]);

  // 2. Cargar sesiones y sumar horas por tipo.
  $horas = [
    'orientacion_laboral' => 0.0,
    'orientacion_laboral_individual' => 0.0,
    'formacion' => 0.0,
    'orientacion_insercion' => 0.0,
  ];
  $sesiones_formativas_asistidas = 0;
  $sesiones_formativas_totales = 0;

  foreach ($inscripciones as $inscripcion) {
    $sesion = $inscripcion->get('sesion_id')->entity;
    if (!$sesion) {
      continue;
    }

    $tipo = $sesion->get('tipo_sesion')->value;
    $duracion = (float) $sesion->get('duracion_horas')->value;

    switch ($tipo) {
      case 'orientacion_laboral_individual':
        $horas['orientacion_laboral'] += $duracion;
        $horas['orientacion_laboral_individual'] += $duracion;
        break;
      case 'orientacion_laboral_grupal':
        $horas['orientacion_laboral'] += $duracion;
        break;
      case 'sesion_formativa':
        $horas['formacion'] += $duracion;
        $sesiones_formativas_asistidas++;
        break;
      case 'orientacion_insercion_individual':
      case 'orientacion_insercion_grupal':
        $horas['orientacion_insercion'] += $duracion;
        break;
    }
  }

  // 3. Calcular porcentaje de asistencia formación.
  // Total de sesiones formativas donde el participante está inscrito (asista o no).
  $todas_inscripciones_formativas = $this->entityTypeManager
    ->getStorage('inscripcion_sesion_ei')
    ->getQuery()
    ->accessCheck(FALSE)
    ->condition('participante_id', $participante_id)
    ->condition('sesion_id.entity:sesion_programada_ei.tipo_sesion', 'sesion_formativa')
    ->count()
    ->execute();

  $porcentaje_asistencia = $todas_inscripciones_formativas > 0
    ? ($sesiones_formativas_asistidas / $todas_inscripciones_formativas) * 100
    : 0.0;

  // 4. Evaluar persona atendida.
  $es_atendida = (
    $horas['orientacion_laboral'] >= 10.0
    && $horas['orientacion_laboral_individual'] >= 2.0
    && $horas['formacion'] >= 50.0
    && $porcentaje_asistencia >= 75.0
  );

  // 5. Evaluar persona insertada.
  $es_insertada = (
    $es_atendida
    && $horas['orientacion_insercion'] >= 40.0
    && $this->tieneContratoValido($participante)
  );

  // 6. Actualizar participante.
  $participante->set('horas_orientacion_laboral', $horas['orientacion_laboral']);
  $participante->set('horas_orientacion_laboral_individual', $horas['orientacion_laboral_individual']);
  $participante->set('horas_formacion', $horas['formacion']);
  $participante->set('horas_orientacion_insercion', $horas['orientacion_insercion']);
  $participante->set('porcentaje_asistencia_formacion', $porcentaje_asistencia);
  $participante->set('es_persona_atendida', $es_atendida);
  $participante->set('es_persona_insertada', $es_insertada);
  $participante->save();

  return [
    'horas_orientacion_laboral' => $horas['orientacion_laboral'],
    'horas_orientacion_laboral_individual' => $horas['orientacion_laboral_individual'],
    'horas_formacion' => $horas['formacion'],
    'porcentaje_asistencia_formacion' => $porcentaje_asistencia,
    'horas_orientacion_insercion' => $horas['orientacion_insercion'],
    'es_persona_atendida' => $es_atendida,
    'es_persona_insertada' => $es_insertada,
  ];
}

/**
 * Verifica si el participante tiene un contrato válido ≥4 meses.
 */
private function tieneContratoValido(ProgramaParticipanteEiInterface $participante): bool {
  // Verificar campo de inserción laboral vinculado.
  $inserciones = $this->entityTypeManager
    ->getStorage('insercion_laboral')
    ->loadByProperties([
      'participante_id' => $participante->id(),
    ]);

  foreach ($inserciones as $insercion) {
    $fecha_inicio = $insercion->get('fecha_inicio_contrato')->value;
    $fecha_fin = $insercion->get('fecha_fin_contrato')->value;
    $tipo_contrato = $insercion->get('tipo_contrato')->value;

    // Alta en RETA (autónomo) cuenta directamente.
    if ($tipo_contrato === 'autonomo_reta') {
      return TRUE;
    }

    // Para contratos por cuenta ajena, verificar ≥4 meses.
    if ($fecha_inicio && $fecha_fin) {
      $inicio = new \DateTimeImmutable($fecha_inicio);
      $fin = new \DateTimeImmutable($fecha_fin);
      $diff = $inicio->diff($fin);
      $meses = ($diff->y * 12) + $diff->m;
      if ($meses >= 4) {
        return TRUE;
      }
    }
  }

  return FALSE;
}
```

---

### 6. Flujo de Datos End-to-End

#### 6.1. Flujo Fase de Atención

```
1. Coordinador programa sesiones de orientación laboral
   └── SesionProgramadaEi (tipo=orientacion_laboral_individual|grupal, fase=atencion)
       ├── contenido_sto + subcontenido_sto (seleccionados al programar)
       └── max_plazas, fecha, hora_inicio, hora_fin, duracion_horas

2. Coordinador crea acciones formativas
   └── AccionFormativaEi (titulo, horas, modalidad, contenido_sto)
       ├── materiales → MaterialDidacticoEi[] (documentos, vídeos, guías)
       ├── course_id → jaraba_lms (si aplica)
       └── Solicita VoBo SAE → VoboSaeWorkflowService (8 estados)

3. Una vez VoBo favorable, programa sesiones formativas
   └── SesionProgramadaEi (tipo=sesion_formativa, accion_formativa_id=X)
       └── Hereda contenido_sto de la AccionFormativaEi padre

4. Participantes se inscriben
   └── InscripcionSesionEi (sesion_id, participante_id)

5. Coordinador registra asistencia
   └── InscripcionSesionEi.asistencia = TRUE/FALSE
       └── postSave() → ActuacionComputeService.recalcularIndicadores()
           └── Genera/actualiza ActuacionSto (tipo, fase=atencion, contenido_sto)
           └── Actualiza ProgramaParticipanteEi (horas, es_persona_atendida)

6. PlanFormativoEi integra acciones aprobadas
   └── accion_formativa_ids = [1, 5, 8]
   └── preSave() computa: horas_formacion_previstas, cumple_persona_atendida
```

#### 6.2. Flujo Fase de Inserción

```
1. Coordinador programa sesiones de orientación para la inserción
   └── SesionProgramadaEi (tipo=orientacion_insercion_individual|grupal, fase=insercion)
       ├── contenido_sto + subcontenido_sto
       └── Solo participantes con es_persona_atendida=TRUE deberían participar

2. Participantes se inscriben y asisten
   └── InscripcionSesionEi → postSave() → recalcularIndicadores()
       └── Acumula horas_orientacion_insercion en ProgramaParticipanteEi

3. Prospección empresarial (ya existente)
   └── ProspeccionEmpresarial entity (sin cambios)

4. Inserción laboral
   └── InsercionLaboral entity (contrato, fecha_inicio, fecha_fin, tipo)
       └── Al crear/modificar → trigger recalculación es_persona_insertada

5. Evaluación automática
   └── es_persona_insertada = es_persona_atendida
       && horas_orientacion_insercion >= 40
       && (contrato >= 4 meses || alta RETA)
```

#### 6.3. Flujo de Cómputo Automático

```
                    ┌─────────────────────────────────┐
                    │   InscripcionSesionEi::postSave  │
                    │   (asistencia cambia)            │
                    └─────────────┬───────────────────┘
                                  │
                                  ▼
                    ┌─────────────────────────────────┐
                    │  ActuacionComputeService         │
                    │  ::recalcularIndicadores()       │
                    └─────────────┬───────────────────┘
                                  │
                    ┌─────────────┼───────────────────┐
                    │             │                     │
                    ▼             ▼                     ▼
            ┌──────────┐  ┌──────────────┐  ┌──────────────────┐
            │ Sumar    │  │ Calcular %   │  │ Verificar       │
            │ horas    │  │ asistencia   │  │ contrato ≥4m    │
            │ por tipo │  │ formación    │  │ (InsercionLaboral)│
            └────┬─────┘  └──────┬───────┘  └────────┬─────────┘
                 │               │                     │
                 └───────────────┼─────────────────────┘
                                 ▼
                    ┌─────────────────────────────────┐
                    │  ProgramaParticipanteEi::save()  │
                    │  • horas_orientacion_laboral     │
                    │  • horas_formacion               │
                    │  • horas_orientacion_insercion    │
                    │  • porcentaje_asistencia          │
                    │  • es_persona_atendida            │
                    │  • es_persona_insertada           │
                    └─────────────────────────────────┘
                                 │
                                 ▼
                    ┌─────────────────────────────────┐
                    │  Dashboard KPIs (real-time)      │
                    │  EsfPlusIndicadorService         │
                    │  (lee valores ya computados)     │
                    └─────────────────────────────────┘
```

**Triggers de recalculación:**
1. `InscripcionSesionEi::postSave()` — cuando cambia asistencia
2. `InsercionLaboral::postSave()` — cuando se crea/modifica un contrato
3. `ActuacionComputeService::recalcularPrograma()` — batch nocturno (cron) o tras migración
4. Botón manual en dashboard coordinador: "Recalcular indicadores"

---

## PARTE III — PLAN DE EJECUCIÓN

### 7. Sprints y Tareas

#### 7.1. Sprint 14-A: Reestructuración de Entidades (Backend)

**Duración estimada**: Primeros 3-4 días

| # | Tarea | Archivos | Prioridad |
|---|-------|----------|-----------|
| A1 | Crear entidad MaterialDidacticoEi | `/create-entity` scaffold | P0 |
| A2 | Añadir constante CONTENIDOS_STO y SUBCONTENIDOS_STO al módulo | `jaraba_andalucia_ei.module` | P0 |
| A3 | Modificar SesionProgramadaEi: nuevos TIPOS_SESION, campo fase, campos STO | Entity + Interface | P0 |
| A4 | Modificar AccionFormativaEi: campos STO, campo materiales | Entity + Interface | P0 |
| A5 | Modificar ActuacionSto: campo fase, nuevos TIPOS_ACTUACION, campos STO | Entity + Interface | P0 |
| A6 | Modificar PlanFormativoEi: campos cumplimiento, horas_orientacion_insercion | Entity | P1 |
| A7 | Modificar ProgramaParticipanteEi: campos desglose horas | Entity | P0 |
| A8 | hook_update_N() para TODOS los cambios de baseFieldDefinitions | .install | P0 |
| A9 | Migración de datos: script para transformar TIPOS_SESION legacy | scripts/maintenance/ | P0 |

**hook_update_N() requeridos (UPDATE-HOOK-REQUIRED-001):**

```php
/**
 * Install MaterialDidacticoEi entity type and update existing entities with new fields.
 */
function jaraba_andalucia_ei_update_100XX(): void {
  $entity_definition_update_manager = \Drupal::entityDefinitionUpdateManager();

  // 1. Instalar nueva entidad MaterialDidacticoEi.
  $entity_type = \Drupal::entityTypeManager()
    ->getDefinition('material_didactico_ei');
  $entity_definition_update_manager->installEntityType($entity_type);

  // 2. Actualizar SesionProgramadaEi con nuevos campos.
  $fields = \Drupal::service('entity_field.manager')
    ->getFieldStorageDefinitions('sesion_programada_ei');
  foreach (['fase', 'contenido_sto', 'subcontenido_sto'] as $field_name) {
    if (isset($fields[$field_name])) {
      $field = $fields[$field_name];
      $field->setName($field_name);
      $field->setTargetEntityTypeId('sesion_programada_ei');
      $entity_definition_update_manager->installFieldStorageDefinition(
        $field_name, 'sesion_programada_ei', 'jaraba_andalucia_ei', $field
      );
    }
  }

  // 3. Actualizar AccionFormativaEi con nuevos campos.
  $fields = \Drupal::service('entity_field.manager')
    ->getFieldStorageDefinitions('accion_formativa_ei');
  foreach (['contenido_sto', 'subcontenido_sto', 'materiales'] as $field_name) {
    if (isset($fields[$field_name])) {
      $field = $fields[$field_name];
      $field->setName($field_name);
      $field->setTargetEntityTypeId('accion_formativa_ei');
      $entity_definition_update_manager->installFieldStorageDefinition(
        $field_name, 'accion_formativa_ei', 'jaraba_andalucia_ei', $field
      );
    }
  }

  // 4. Actualizar ActuacionSto con nuevos campos.
  $fields = \Drupal::service('entity_field.manager')
    ->getFieldStorageDefinitions('actuacion_sto');
  foreach (['fase', 'contenido_sto', 'subcontenido_sto'] as $field_name) {
    if (isset($fields[$field_name])) {
      $field = $fields[$field_name];
      $field->setName($field_name);
      $field->setTargetEntityTypeId('actuacion_sto');
      $entity_definition_update_manager->installFieldStorageDefinition(
        $field_name, 'actuacion_sto', 'jaraba_andalucia_ei', $field
      );
    }
  }

  // 5. Actualizar PlanFormativoEi con nuevos campos.
  $fields = \Drupal::service('entity_field.manager')
    ->getFieldStorageDefinitions('plan_formativo_ei');
  foreach (['horas_orientacion_insercion_previstas', 'cumple_persona_atendida', 'cumple_persona_insertada'] as $field_name) {
    if (isset($fields[$field_name])) {
      $field = $fields[$field_name];
      $field->setName($field_name);
      $field->setTargetEntityTypeId('plan_formativo_ei');
      $entity_definition_update_manager->installFieldStorageDefinition(
        $field_name, 'plan_formativo_ei', 'jaraba_andalucia_ei', $field
      );
    }
  }

  // 6. Actualizar ProgramaParticipanteEi con campos de desglose.
  $fields = \Drupal::service('entity_field.manager')
    ->getFieldStorageDefinitions('programa_participante_ei');
  $new_fields = [
    'horas_orientacion_laboral', 'horas_orientacion_laboral_individual',
    'horas_formacion', 'horas_orientacion_insercion', 'porcentaje_asistencia_formacion',
  ];
  foreach ($new_fields as $field_name) {
    if (isset($fields[$field_name])) {
      $field = $fields[$field_name];
      $field->setName($field_name);
      $field->setTargetEntityTypeId('programa_participante_ei');
      $entity_definition_update_manager->installFieldStorageDefinition(
        $field_name, 'programa_participante_ei', 'jaraba_andalucia_ei', $field
      );
    }
  }
}
```

**Script de migración de datos** (`scripts/maintenance/migrate_sesion_tipos_piil.php`):

```php
// Mapa de migración de TIPOS_SESION legacy a nuevos valores.
$migration_map = [
  'formacion_presencial' => 'sesion_formativa',
  'formacion_online' => 'sesion_formativa',
  'orientacion_individual' => 'orientacion_laboral_individual',
  'orientacion_grupal' => 'orientacion_laboral_grupal',
  'tutoria' => 'tutoria_seguimiento',
  'taller' => 'orientacion_laboral_grupal',
];

// Actualizar tipo_sesion en todas las sesiones existentes.
// Establecer fase computada.
// Para sesiones formación: verificar que tienen accion_formativa_id.
// Log: sesiones sin accion_formativa_id que eran formación → requieren revisión manual.
```

#### 7.2. Sprint 14-B: Servicios y Lógica de Negocio

**Duración estimada**: 2-3 días

| # | Tarea | Archivos | Prioridad |
|---|-------|----------|-----------|
| B1 | Crear ActuacionComputeService + Interface | src/Service/ | P0 |
| B2 | Registrar servicio en services.yml | services.yml | P0 |
| B3 | Modificar CoordinadorHubService para nuevos tipos | src/Service/ | P0 |
| B4 | Modificar InscripcionSesionService para trigger recalculación | src/Service/ | P0 |
| B5 | Modificar ActuacionStoService para campo fase y STO | src/Service/ | P1 |
| B6 | Implementar postSave() en InscripcionSesionEi | Entity | P0 |
| B7 | Implementar postSave() en InsercionLaboral (trigger recalculación) | Entity | P1 |
| B8 | Crear funciones allowed_values para contenidos STO | .module | P1 |
| B9 | Crear comando drush para recalculación batch | src/Commands/ | P2 |

**Registro del servicio (services.yml):**
```yaml
jaraba_andalucia_ei.actuacion_compute:
  class: Drupal\jaraba_andalucia_ei\Service\ActuacionComputeService
  arguments:
    - '@entity_type.manager'
    - '@logger.channel.jaraba_andalucia_ei'
```

**Funciones allowed_values para contenidos STO (en .module):**

```php
/**
 * Provides allowed values for contenido_sto field.
 *
 * Valores basados en los dropdowns del STO (Manual Gestión ICV25, Sección 3.4).
 */
function jaraba_andalucia_ei_contenidos_sto(FieldStorageDefinitionInterface $definition, ?FieldableEntityInterface $entity = NULL): array {
  return [
    'informacion' => 'Información',
    'diagnostico' => 'Diagnóstico',
    'diseno_planificacion' => 'Diseño/Planificación del itinerario',
    'desarrollo_competencias' => 'Desarrollo de competencias',
    'busqueda_activa_empleo' => 'Búsqueda activa de empleo',
    'seguimiento' => 'Seguimiento',
    'intermediacion' => 'Intermediación',
    'acompanamiento_insercion' => 'Acompañamiento a la inserción',
    'emprendimiento' => 'Emprendimiento',
    'club_empleo' => 'Club de empleo',
  ];
}

/**
 * Provides allowed values for subcontenido_sto field.
 *
 * Valores dependientes del contenido_sto (referencia: Manual STO ICV25).
 * En el formulario, se filtra dinámicamente via JS según contenido seleccionado.
 */
function jaraba_andalucia_ei_subcontenidos_sto(FieldStorageDefinitionInterface $definition, ?FieldableEntityInterface $entity = NULL): array {
  // Todos los subcontenidos posibles (el filtrado por contenido_sto es en form alter via JS).
  return [
    'entrevista_inicial' => 'Entrevista inicial',
    'balance_competencias' => 'Balance de competencias',
    'orientacion_profesional' => 'Orientación profesional',
    'cv_carta' => 'CV y carta de presentación',
    'preparacion_entrevistas' => 'Preparación de entrevistas',
    'competencias_digitales' => 'Competencias digitales',
    'competencias_transversales' => 'Competencias transversales',
    'marca_personal' => 'Marca personal y networking',
    'autoempleo' => 'Autoempleo y emprendimiento',
    'plan_empresa' => 'Plan de empresa',
    'seguimiento_insercion' => 'Seguimiento post-inserción',
    'contacto_empresas' => 'Contacto con empresas',
    'gestion_ofertas' => 'Gestión de ofertas',
  ];
}
```

#### 7.3. Sprint 14-C: UI/UX del Dashboard Coordinador

**Duración estimada**: 2-3 días

| # | Tarea | Archivos | Prioridad |
|---|-------|----------|-----------|
| C1 | Reorganizar dashboard en dos paneles de fase: Atención + Inserción | coordinador-dashboard.html.twig | P0 |
| C2 | Panel Fase Atención: KPIs orientación laboral + formación | Twig + preprocess | P0 |
| C3 | Panel Fase Inserción: KPIs orientación inserción + prospección | Twig + preprocess | P0 |
| C4 | Actualizar formularios slide-panel para nuevos tipos de sesión | Form classes | P0 |
| C5 | Formulario MaterialDidacticoEi en slide-panel | Form (PremiumEntityFormBase) | P1 |
| C6 | Filtro de contenido_sto dinámico en formularios (JS) | js/ | P1 |
| C7 | SCSS para nuevos paneles de fase | coordinador-hub.scss | P0 |
| C8 | KPIs de persona atendida/insertada en panel ESF+ | Twig + preprocess | P1 |

**Estructura propuesta del dashboard reestructurado:**

```html
{# coordinador-dashboard.html.twig — Sección Actuaciones #}

{# ══════════ FASE DE ATENCIÓN ══════════ #}
<section class="hub-coordinador__fase hub-coordinador__fase--atencion">
  <header class="hub-coordinador__fase-header">
    {{ jaraba_icon('ui', 'shield-check', { variant: 'duotone', color: 'azul-corporativo', size: '32px' }) }}
    <div>
      <h2 class="hub-coordinador__fase-title">{% trans %}Fase de Atención{% endtrans %}</h2>
      <p class="hub-coordinador__fase-subtitle">{% trans %}Orientación laboral y formación{% endtrans %}</p>
    </div>
  </header>

  {# Orientación Laboral #}
  <div class="hub-coordinador__actuacion-bloque">
    <h3>{% trans %}Orientación Laboral{% endtrans %}</h3>
    {# KPIs: sesiones individuales, grupales, horas totales #}
    {# Lista de próximas sesiones de orientación laboral #}
    {# Botones: Nueva Sesión Individual, Nueva Sesión Grupal #}
  </div>

  {# Formación #}
  <div class="hub-coordinador__actuacion-bloque">
    <h3>{% trans %}Formación{% endtrans %}</h3>
    {# KPIs: acciones formativas (aprobadas/pendientes VoBo), horas previstas #}
    {# Lista de acciones formativas con estado VoBo #}
    {# Sesiones formativas programadas (hijas de acciones aprobadas) #}
    {# Botones: Nueva Acción Formativa, Nueva Sesión Formativa #}
  </div>
</section>

{# ══════════ FASE DE INSERCIÓN ══════════ #}
<section class="hub-coordinador__fase hub-coordinador__fase--insercion">
  <header class="hub-coordinador__fase-header">
    {{ jaraba_icon('analytics', 'target', { variant: 'duotone', color: 'verde-innovacion', size: '32px' }) }}
    <div>
      <h2 class="hub-coordinador__fase-title">{% trans %}Fase de Inserción{% endtrans %}</h2>
      <p class="hub-coordinador__fase-subtitle">{% trans %}Orientación para la inserción y prospección{% endtrans %}</p>
    </div>
  </header>

  {# Orientación para la Inserción #}
  <div class="hub-coordinador__actuacion-bloque">
    <h3>{% trans %}Orientación para la Inserción{% endtrans %}</h3>
    {# KPIs: sesiones individuales, grupales, horas totales #}
    {# Solo participantes con es_persona_atendida=TRUE #}
  </div>

  {# Prospección Empresarial #}
  <div class="hub-coordinador__actuacion-bloque">
    <h3>{% trans %}Prospección Empresarial{% endtrans %}</h3>
    {# Datos de ProspeccionEmpresarial existente #}
  </div>
</section>
```

**SCSS para paneles de fase (coordinador-hub.scss):**

```scss
// Fase containers
.hub-coordinador__fase {
  border-radius: 16px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  border: 1px solid var(--ej-color-border-subtle, #e5e7eb);
  background: var(--ej-color-surface, #ffffff);
}

.hub-coordinador__fase--atencion {
  border-left: 4px solid var(--ej-color-azul-corporativo, #233D63);
}

.hub-coordinador__fase--insercion {
  border-left: 4px solid var(--ej-color-verde-innovacion, #00A9A5);
}

.hub-coordinador__fase-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--ej-color-border-subtle, #e5e7eb);
}

.hub-coordinador__fase-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--ej-color-text-primary, #1a1a2e);
  margin: 0;
}

.hub-coordinador__fase-subtitle {
  font-size: 0.875rem;
  color: var(--ej-color-text-secondary, #64748b);
  margin: 0.25rem 0 0;
}

.hub-coordinador__actuacion-bloque {
  margin-bottom: 1.5rem;
  padding: 1rem;
  border-radius: 12px;
  background: color-mix(in srgb, var(--ej-color-surface, #ffffff) 95%, var(--ej-color-azul-corporativo, #233D63));

  h3 {
    font-size: 1rem;
    font-weight: 600;
    color: var(--ej-color-text-primary, #1a1a2e);
    margin: 0 0 1rem;
  }
}
```

#### 7.4. Sprint 14-D: Tests y Validación

**Duración estimada**: 2 días

| # | Tarea | Tipo | Prioridad |
|---|-------|------|-----------|
| D1 | Unit test: ActuacionComputeService (lógica de cómputo) | Unit | P0 |
| D2 | Unit test: FASE_POR_TIPO mapping | Unit | P0 |
| D3 | Kernel test: Migración de TIPOS_SESION legacy | Kernel | P0 |
| D4 | Kernel test: postSave trigger de recalculación | Kernel | P1 |
| D5 | Kernel test: MaterialDidacticoEi CRUD | Kernel | P1 |
| D6 | Functional test: Dashboard coordinador muestra fases correctas | Functional | P2 |
| D7 | Ejecutar validación scripts: validate-entity-integrity, validate-service-consumers | CI | P0 |
| D8 | Verificar RUNTIME-VERIFY-001 (5 dependencias runtime) | Manual | P0 |

**Test clave — ActuacionComputeServiceTest:**

```php
/**
 * @covers ::recalcularIndicadores
 */
public function testPersonaAtendidaCumpleRequisitos(): void {
  // Setup: participante con inscripciones que suman:
  // - 12h orientación laboral (3h individual + 9h grupal)
  // - 55h formación (80% asistencia)
  $resultado = $this->service->recalcularIndicadores($participante->id());

  $this->assertTrue($resultado['es_persona_atendida']);
  $this->assertGreaterThanOrEqual(10.0, $resultado['horas_orientacion_laboral']);
  $this->assertGreaterThanOrEqual(2.0, $resultado['horas_orientacion_laboral_individual']);
  $this->assertGreaterThanOrEqual(50.0, $resultado['horas_formacion']);
  $this->assertGreaterThanOrEqual(75.0, $resultado['porcentaje_asistencia_formacion']);
}

/**
 * @covers ::recalcularIndicadores
 */
public function testPersonaAtendidaFallaPorHorasIndividuales(): void {
  // Setup: tiene 10h orientación laboral pero SOLO 1h individual (necesita 2h)
  $resultado = $this->service->recalcularIndicadores($participante->id());

  $this->assertFalse($resultado['es_persona_atendida']);
  $this->assertGreaterThanOrEqual(10.0, $resultado['horas_orientacion_laboral']);
  $this->assertLessThan(2.0, $resultado['horas_orientacion_laboral_individual']);
}

/**
 * @covers ::recalcularIndicadores
 */
public function testPersonaInsertadaCumpleRequisitos(): void {
  // Setup: persona atendida + 45h orientación inserción + contrato 6 meses
  $resultado = $this->service->recalcularIndicadores($participante->id());

  $this->assertTrue($resultado['es_persona_atendida']);
  $this->assertTrue($resultado['es_persona_insertada']);
  $this->assertGreaterThanOrEqual(40.0, $resultado['horas_orientacion_insercion']);
}
```

---

### 8. Migración de Datos

**Estrategia**: Migración en dos fases ejecutadas en el mismo hook_update_N().

**Fase 1: Migración de esquema** (automática via hook_update)
- Instalar nuevos campos en entidades existentes
- Instalar nueva entidad MaterialDidacticoEi

**Fase 2: Migración de datos** (script en scripts/maintenance/)

```php
// scripts/maintenance/migrate_sesion_tipos_piil.php

$migration_map = [
  'formacion_presencial' => 'sesion_formativa',
  'formacion_online' => 'sesion_formativa',
  'orientacion_individual' => 'orientacion_laboral_individual',
  'orientacion_grupal' => 'orientacion_laboral_grupal',
  'tutoria' => 'tutoria_seguimiento',
  'taller' => 'orientacion_laboral_grupal',
];

$sesiones = \Drupal::entityTypeManager()
  ->getStorage('sesion_programada_ei')
  ->loadMultiple();

$log = ['migrated' => 0, 'warnings' => []];

foreach ($sesiones as $sesion) {
  $tipo_old = $sesion->get('tipo_sesion')->value;
  $tipo_new = $migration_map[$tipo_old] ?? $tipo_old;

  // Warning: sesión de formación sin accion_formativa_id.
  if ($tipo_new === 'sesion_formativa' && empty($sesion->get('accion_formativa_id')->target_id)) {
    $log['warnings'][] = "Sesión #{$sesion->id()} ({$tipo_old}): Sin accion_formativa_id. Requiere revisión manual.";
  }

  $sesion->set('tipo_sesion', $tipo_new);
  // fase se computa en preSave().
  $sesion->save();
  $log['migrated']++;
}

// Migrar ActuacionSto tipos.
$actuacion_map = [
  'orientacion_individual' => 'orientacion_laboral_individual',
  'orientacion_grupal' => 'orientacion_laboral_grupal',
  // formacion, prospeccion, intermediacion se mantienen.
  'tutoria' => 'tutoria',
];

// Output log.
print "Migradas: {$log['migrated']} sesiones.\n";
foreach ($log['warnings'] as $w) {
  print "⚠ {$w}\n";
}
```

**Post-migración:**
1. Ejecutar `ActuacionComputeService::recalcularPrograma()` para todos los programas activos
2. Verificar que los KPIs del dashboard muestran valores consistentes
3. Generar informe de sesiones que requieren revisión manual (las que eran formación sin accion_formativa_id)

---

### 9. Estrategia de Rollback

**Nivel 1: Rollback de datos**
- Backup de tablas afectadas antes de migración (automático en script)
- Los valores legacy se guardan en campo `legacy_tipo_sesion` (texto auxiliar temporal)

**Nivel 2: Rollback de código**
- Rama `feature/piil-actuaciones-restructure` permite revert limpio
- Sin cambios destructivos en campos existentes (solo adiciones + renombramientos)

**Nivel 3: Compatibilidad temporal**
- Durante 1 sprint tras el deploy, mantener los valores legacy en TIPOS_SESION como deprecated
- `@trigger_error()` si se usan los valores legacy

---

## PARTE IV — ESPECIFICACIONES TÉCNICAS

### 10. Tabla de Correspondencia: Especificaciones Técnicas

| Concepto Normativo (BBRR/STO) | Entidad Drupal | Campo/Constante | Estado Actual | Acción Requerida |
|-------------------------------|---------------|----------------|---------------|-----------------|
| Orientación laboral individual (Atención) | SesionProgramadaEi | tipo_sesion='orientacion_laboral_individual' | ❌ Mezclado en 'orientacion_individual' | Renombrar + campo fase |
| Orientación laboral grupal (Atención) | SesionProgramadaEi | tipo_sesion='orientacion_laboral_grupal' | ❌ Mezclado en 'orientacion_grupal' | Renombrar + campo fase |
| Sesión formativa presencial (Atención) | SesionProgramadaEi | tipo_sesion='sesion_formativa' + AccionFormativaEi.modalidad='presencial' | ❌ tipo='formacion_presencial' independiente | Vincular a AccionFormativaEi obligatoriamente |
| Sesión formativa online (Atención) | SesionProgramadaEi | tipo_sesion='sesion_formativa' + AccionFormativaEi.modalidad='online' | ❌ tipo='formacion_online' independiente | Vincular a AccionFormativaEi obligatoriamente |
| Acción formativa (Atención) | AccionFormativaEi | Entidad completa con VoBo | ✅ Correcto | Añadir campos STO y materiales |
| VoBo SAE | AccionFormativaEi | estado_vobo (8 estados) | ✅ Correcto | Sin cambios |
| Orientación inserción individual (Inserción) | SesionProgramadaEi | tipo_sesion='orientacion_insercion_individual' | ❌ No existe | Crear nuevo tipo |
| Orientación inserción grupal (Inserción) | SesionProgramadaEi | tipo_sesion='orientacion_insercion_grupal' | ❌ No existe | Crear nuevo tipo |
| Prospección empresarial (Inserción) | ProspeccionEmpresarial | Entidad independiente | ✅ Correcto | Sin cambios |
| Intermediación laboral (Inserción) | ActuacionSto | tipo_actuacion='intermediacion' | ✅ Correcto | Añadir fase='insercion' |
| Contenido STO | SesionProgramadaEi + ActuacionSto | contenido_sto | ❌ No existe | Crear campo |
| Subcontenido STO | SesionProgramadaEi + ActuacionSto | subcontenido_sto | ❌ No existe | Crear campo |
| Persona atendida (≥10h orient. + ≥50h form.) | ProgramaParticipanteEi | es_persona_atendida | ⚠️ Campo existe, sin lógica | Implementar cómputo |
| Persona insertada (atendida + ≥40h insert. + contrato) | ProgramaParticipanteEi | es_persona_insertada | ⚠️ Campo existe, sin lógica | Implementar cómputo |
| Plan formativo (integración acciones) | PlanFormativoEi | accion_formativa_ids + computed hours | ✅ Parcialmente correcto | Añadir campos cumplimiento |
| Materiales didácticos | MaterialDidacticoEi (NUEVA) | titulo, archivo, url, tipo | ❌ No existe | Crear entidad |
| Fase PIIL (Atención vs Inserción) | SesionProgramadaEi + ActuacionSto | fase | ❌ No existe | Crear campo en ambas |
| Tutoría de seguimiento | SesionProgramadaEi | tipo_sesion='tutoria_seguimiento' | ⚠️ Existe como 'tutoria' | Renombrar |
| Taller | — | — | ⚠️ tipo='taller' independiente | Absorber en orientacion_laboral_grupal |
| Registro asistencia | InscripcionSesionEi | asistencia (boolean) | ✅ Correcto | Añadir trigger recalculación |
| Generación ActuacionSto | InscripcionSesionEi/ActuacionStoService | actuacion_sto_id | ✅ Parcialmente | Pasar fase y campos STO |
| Indicadores ESF+ | IndicadorFsePlus + EsfPlusIndicadorService | Entidad + servicio | ✅ Correcto | Leer computados de participante |
| Inserción laboral | InsercionLaboral | fecha_inicio/fin, tipo_contrato | ✅ Correcto | Añadir trigger recalculación |
| Expediente documentos | ExpedienteDocumento | 35+ campos, firma digital | ✅ Correcto | Sin cambios |

---

### 11. Tabla de Cumplimiento de Directrices

| Directriz | Ámbito | Cómo se Cumple | Verificación |
|-----------|--------|---------------|-------------|
| **TENANT-001** | Toda query filtra por tenant | ActuacionComputeService filtra inscripciones por participante (que ya está scoped a tenant). MaterialDidacticoEi tiene campo tenant_id | `validate-tenant-isolation.php` |
| **TENANT-ISOLATION-ACCESS-001** | AccessControlHandler | MaterialDidacticoEiAccessControlHandler verifica tenant match en update/delete | Kernel test |
| **PREMIUM-FORMS-PATTERN-001** | Entity forms | MaterialDidacticoEiForm extiende PremiumEntityFormBase. Forms modificados mantienen herencia | Code review |
| **UPDATE-HOOK-REQUIRED-001** | Nuevos campos/entidad | hook_update_100XX() instala MaterialDidacticoEi + todos los nuevos campos via installFieldStorageDefinition() | `validate-entity-integrity.php` |
| **UPDATE-FIELD-DEF-001** | installFieldStorageDefinition | Cada campo usa setName() + setTargetEntityTypeId() antes de instalar | hook_update code |
| **UPDATE-HOOK-CATCH-001** | Error handling en hook_update | try-catch con \Throwable (NO \Exception) | Code review |
| **ENTITY-FK-001** | Referencias entre entidades | materiales → MaterialDidacticoEi = entity_reference (mismo módulo). contenido_sto = list_string (no FK) | Schema review |
| **AUDIT-CONS-001** | AccessControlHandler | MaterialDidacticoEi anotación @ContentEntityType tiene "access" handler | Entity annotation |
| **ENTITY-PREPROCESS-001** | template_preprocess | template_preprocess_material_didactico_ei() en .module | `validate-entity-integrity.php` |
| **PRESAVE-RESILIENCE-001** | Servicios opcionales en preSave | SesionProgramadaEi preSave usa valores estáticos FASE_POR_TIPO. postSave usa hasService() + try-catch | Code review |
| **ENTITY-001** | Interfaces | MaterialDidacticoEi implements EntityOwnerInterface, EntityChangedInterface | Entity class |
| **LABEL-NULLSAFE-001** | Entity labels | MaterialDidacticoEi tiene 'label' en entity_keys → label() no devuelve NULL | Entity annotation |
| **CSS-VAR-ALL-COLORS-001** | SCSS colores | Todos los colores en nuevos SCSS usan var(--ej-*, fallback). Ningún hex hardcoded | `grep -r '#[0-9a-fA-F]' scss/routes/coordinador-hub.scss` |
| **SCSS-COMPILE-VERIFY-001** | CSS compilado | Tras cada edición SCSS, recompilar y verificar timestamp CSS > SCSS | `validate-compiled-assets.php` |
| **SCSS-001** | @use scope | Cada parcial SCSS nuevo incluye `@use '../variables' as *;` | `check-scss-orphans.js` |
| **ICON-CONVENTION-001** | Iconos | jaraba_icon() con category/name/variant. Variant: duotone (default) | Twig template review |
| **ICON-DUOTONE-001** | Variante default | Todos los iconos en dashboard usan variante duotone | Template review |
| **ICON-COLOR-001** | Colores de iconos | Solo paleta Jaraba: azul-corporativo, verde-innovacion, naranja-impulso, white, neutral | Template review |
| **ROUTE-LANGPREFIX-001** | URLs en JS | Todas las URLs via drupalSettings, nunca hardcoded | JS review |
| **CSRF-API-001** | API routes | Endpoints existentes ya usan _csrf_request_header_token | Routing YAML |
| **TWIG trans** | Traducciones | Todos los textos en Twig usan {% trans %} (bloque, NO filtro \|t) | Template review |
| **SLIDE-PANEL-RENDER-001** | Formularios modales | Formularios de MaterialDidacticoEi y nuevos tipos de sesión abren en slide-panel | Controller + JS |
| **FORM-CACHE-001** | FormState cache | Sin setCached(TRUE) incondicional | Form code |
| **OPTIONAL-CROSSMODULE-001** | DI cross-module | ActuacionComputeService solo usa @entity_type.manager y @logger (core). Sin deps cross-module | services.yml |
| **LOGGER-INJECT-001** | Logger injection | @logger.channel.jaraba_andalucia_ei → constructor acepta LoggerInterface $logger | Constructor review |
| **PHANTOM-ARG-001** | args = constructor params | services.yml args coinciden exactamente con parámetros del constructor | `validate-optional-deps.php` |
| **CONTAINER-DEPS-002** | Sin circular deps | ActuacionComputeService no crea ciclos (solo depende de core services) | `validate-circular-deps.php` |
| **CONTROLLER-READONLY-001** | No readonly en herencia | Controllers no usan protected readonly en propiedades heredadas de ControllerBase | Code review |
| **ACCESS-RETURN-TYPE-001** | checkAccess return type | MaterialDidacticoEiAccessControlHandler::checkAccess() retorna AccessResultInterface | Code review |
| **FIELD-UI-SETTINGS-TAB-001** | Field UI tab | MaterialDidacticoEi tiene field_ui_base_route + default local task | Routing YAML |
| **Views integration** | views_data | MaterialDidacticoEi anotación incluye views_data = EntityViewsData | Entity annotation |
| **NO-HARDCODE-PRICE-001** | Sin precios hardcoded | No aplica directamente (no hay precios en actuaciones) | N/A |
| **TRANSLATABLE-FIELDDATA-001** | SQL field_data | Si entidades son translatable, queries usan _field_data | SQL review |
| **QUERY-CHAIN-001** | No encadenar addExpression | Queries en ActuacionComputeService usan Entity Query (no raw SQL) | Code review |
| **INNERHTML-XSS-001** | XSS en JS | Datos de API sanitizados con Drupal.checkPlain() antes de innerHTML | JS review |
| **SECRET-MGMT-001** | Sin secrets en config | No aplica (no hay secrets nuevos) | N/A |
| **DOC-GUARD-001** | Master docs | Este documento va en docs/tareas/, no modifica master docs | Commit scope |

---

### 12. Criterios de Aceptación

**Funcionales:**
- [ ] Las sesiones de orientación laboral se crean con tipo diferenciado (individual/grupal) en Fase Atención
- [ ] Las sesiones de orientación para la inserción se crean con tipo diferenciado en Fase Inserción
- [ ] Las sesiones formativas SOLO se crean vinculadas a una AccionFormativaEi aprobada
- [ ] Los campos contenido_sto y subcontenido_sto están disponibles en formularios de sesiones y actuaciones
- [ ] Los materiales didácticos se vinculan a acciones formativas
- [ ] Al registrar asistencia, se recalculan automáticamente los indicadores del participante
- [ ] El dashboard muestra las actuaciones separadas por fase (Atención / Inserción)
- [ ] Los KPIs de persona atendida e insertada reflejan los valores computados
- [ ] La migración de datos existentes se ejecuta sin errores
- [ ] Las sesiones formativas huérfanas (sin accion_formativa_id) se identifican para revisión manual

**Técnicos:**
- [ ] Todos los scripts de validación pasan: `bash scripts/validation/validate-all.sh`
- [ ] SCSS compilado: timestamp CSS > SCSS
- [ ] Tests: Unit para ActuacionComputeService, Kernel para entidades modificadas
- [ ] hook_update_N() instalados sin errores en `drush updatedb`
- [ ] Sin regresiones en dashboard coordinador existente
- [ ] RUNTIME-VERIFY-001: 5 checks (CSS, DB, rutas, data-*, drupalSettings)

---

### 13. Archivos Afectados

**Entidades (modificadas):**
- `web/modules/custom/jaraba_andalucia_ei/src/Entity/SesionProgramadaEi.php`
- `web/modules/custom/jaraba_andalucia_ei/src/Entity/SesionProgramadaEiInterface.php`
- `web/modules/custom/jaraba_andalucia_ei/src/Entity/AccionFormativaEi.php`
- `web/modules/custom/jaraba_andalucia_ei/src/Entity/AccionFormativaEiInterface.php`
- `web/modules/custom/jaraba_andalucia_ei/src/Entity/ActuacionSto.php`
- `web/modules/custom/jaraba_andalucia_ei/src/Entity/PlanFormativoEi.php`
- `web/modules/custom/jaraba_andalucia_ei/src/Entity/ProgramaParticipanteEi.php`
- `web/modules/custom/jaraba_andalucia_ei/src/Entity/InscripcionSesionEi.php`

**Entidades (nuevas):**
- `web/modules/custom/jaraba_andalucia_ei/src/Entity/MaterialDidacticoEi.php`
- `web/modules/custom/jaraba_andalucia_ei/src/Entity/MaterialDidacticoEiInterface.php`

**Servicios (modificados):**
- `web/modules/custom/jaraba_andalucia_ei/src/Service/CoordinadorHubService.php`
- `web/modules/custom/jaraba_andalucia_ei/src/Service/SesionProgramadaService.php`
- `web/modules/custom/jaraba_andalucia_ei/src/Service/InscripcionSesionService.php`
- `web/modules/custom/jaraba_andalucia_ei/src/Service/ActuacionStoService.php`
- `web/modules/custom/jaraba_andalucia_ei/src/Service/EsfPlusIndicadorService.php`

**Servicios (nuevos):**
- `web/modules/custom/jaraba_andalucia_ei/src/Service/ActuacionComputeService.php`
- `web/modules/custom/jaraba_andalucia_ei/src/Service/ActuacionComputeServiceInterface.php`

**Access control (nuevos):**
- `web/modules/custom/jaraba_andalucia_ei/src/MaterialDidacticoEiAccessControlHandler.php`

**Forms (nuevos):**
- `web/modules/custom/jaraba_andalucia_ei/src/Form/MaterialDidacticoEiForm.php`

**Templates:**
- `web/modules/custom/jaraba_andalucia_ei/templates/coordinador-dashboard.html.twig`

**SCSS/CSS:**
- `web/themes/custom/ecosistema_jaraba_theme/scss/routes/coordinador-hub.scss`
- `web/themes/custom/ecosistema_jaraba_theme/css/routes/coordinador-hub.css` (compilado)

**Instalación/migración:**
- `web/modules/custom/jaraba_andalucia_ei/jaraba_andalucia_ei.install` (hook_update_N)
- `web/modules/custom/jaraba_andalucia_ei/jaraba_andalucia_ei.module` (funciones allowed_values, preprocess)
- `web/modules/custom/jaraba_andalucia_ei/jaraba_andalucia_ei.services.yml`

**Scripts:**
- `scripts/maintenance/migrate_sesion_tipos_piil.php` (nuevo)

**Tests:**
- `tests/src/Unit/ActuacionComputeServiceTest.php` (nuevo)
- `tests/src/Kernel/SesionProgramadaEiMigrationTest.php` (nuevo)
- `tests/src/Kernel/MaterialDidacticoEiTest.php` (nuevo)

---

*Documento generado: 2026-03-11 | Módulo: jaraba_andalucia_ei | Sprint: 14*
*Directrices de aplicación: 35+ directrices verificadas (ver sección 11)*
*Normativa de referencia: Orden 29/09/2023 BBRR PIIL + Manual STO ICV25_012026*
