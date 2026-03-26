# Aprendizaje #172 — Plan Maestro Andalucia +ei: 12 Sprints Clase Mundial

**Fecha:** 2026-03-10
**Regla de Oro:** #112
**Reglas nuevas:** SERVICE-CONSUMER-001, AEI-PIIL-001

---

## Contexto

Implementacion del Plan Maestro Andalucia +ei con 12 sprints para cumplimiento PIIL CV 2025 (Junta de Andalucia + FSE+) a nivel clase mundial. Auditoria completa revelo 10 gaps P0 bloqueantes, 10 P1 y 5 P2.

## Hallazgo 1 — SERVICE-CONSUMER-001 (P0)

### Problema

Servicios registrados en `services.yml` sin ningun consumidor (huerfanos). El servicio existe en el container pero nunca se invoca, lo que indica codigo muerto o integracion incompleta.

### Solucion

Regla: TODO servicio registrado DEBE tener al menos 1 consumidor real (hook, controller, otro servicio). Validacion: `php scripts/validation/validate-service-consumers.php`.

Ejemplo resuelto: `documento_firma_orchestrator` conectado via `jaraba_andalucia_ei_expediente_documento_insert()`.

## Hallazgo 2 — AEI-PIIL-001 (P0)

### Problema

El modulo `jaraba_andalucia_ei` implementaba parcialmente las 6 fases PIIL: Acogida, Diagnostico, Atencion, Insercion, Seguimiento, Cierre. Faltaban mecanismos de firma electronica, matching anonimizado, plan de emprendimiento, badges, adaptaciones, push notifications.

### Solucion

12 sprints sistematicos:

| Sprint | Ambito | Estado |
|--------|--------|--------|
| 1 | Firma electronica (tactil + AutoFirma + sello) | Implementado |
| 2 | Workflow de estados (8 estados, 3 metodos) | Implementado |
| 3 | Orquestador documentos-firma (37 categorias, 4 flujos) | Implementado |
| 4 | FirmaWorkflowService + tests (26 tests) | Implementado |
| 5 | Twig templates firma (slide-panel + firma masiva) | Implementado |
| 6 | Matching anonimizado empresa-candidato | Implementado |
| 7 | Plan emprendimiento (4 fases, 20 campos) | Implementado |
| 8 | Adaptacion itinerario por barreras | Implementado |
| 9 | Calendario programa con alertas | Implementado |
| 10 | Copilot context provider | Implementado |
| 11 | Badges por hitos PIIL | Implementado |
| 12 | Push notifications en transiciones | Implementado |

## Hallazgo 3 — Preprocess hooks para templates

### Problema

3 templates (alumni, impacto-publico, firma-masiva) carecian de preprocess hook en `.module`, violando ENTITY-PREPROCESS-001 y ZERO-REGION-003 (drupalSettings no se inyecta sin preprocess).

### Solucion

Anadir `jaraba_andalucia_ei_preprocess_andalucia_ei_{template}()` para cada template, con library attachment y drupalSettings donde aplica.

## Hallazgo 4 — Permissions gap

### Problema

2 rutas (`firma.slide_panel` y `matching.anonimizado`) referenciaban permisos no declarados en `permissions.yml`.

### Solucion

Declarar `use digital signature` y `view andalucia ei matching anonimizado` en el fichero de permisos.

## Reglas consolidadas

- **SERVICE-CONSUMER-001**: Todo servicio con consumidor. Script: `validate-service-consumers.php`.
- **AEI-PIIL-001**: 6 fases PIIL completas con firma, matching, emprendimiento, badges, push.
- **ENTITY-PREPROCESS-001**: Obligatorio para toda entity/template con view mode.
- **ZERO-REGION-003**: drupalSettings via preprocess, no via controller `#attached`.

## Metricas

- 10 gaps P0 resueltos (bloqueantes)
- 231 tests pasando (205 unit + 26 firma)
- 982 servicios, 0 huerfanos
- 15/15 validaciones PASS (full mode)
- 4 master docs actualizados (v122/v110/v151/v75)
