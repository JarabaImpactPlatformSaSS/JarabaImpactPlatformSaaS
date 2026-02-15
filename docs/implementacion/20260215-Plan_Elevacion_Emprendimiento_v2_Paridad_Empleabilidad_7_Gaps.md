# Plan de Elevacion Emprendimiento v2: Paridad con Empleabilidad (7 Gaps)

| Campo     | Valor                          |
|-----------|--------------------------------|
| Fecha     | 2026-02-15                     |
| Version   | 2.0.0                          |
| Estado    | Implementado                   |
| Vertical  | Emprendimiento                 |

---

## Resumen

Este plan cierra los 7 gaps identificados entre la vertical de Emprendimiento y la vertical de Empleabilidad, alcanzando paridad funcional completa. Cada gap replica un pattern ya validado en Empleabilidad, adaptando dimensiones, reglas y constantes al contexto emprendedor.

---

## Gaps Cerrados

### G1: EmprendimientoHealthScoreService

Servicio de Health Score con 5 dimensiones y 8 KPIs especificos para la vertical de emprendimiento.

- **5 dimensiones** evaluadas para calcular el score de salud del emprendedor.
- **8 KPIs** que alimentan las dimensiones y permiten tracking continuo.
- Replica el pattern de `EmpleabilidadHealthScoreService` adaptando metricas (e.g., `canvas_completeness` en lugar de `profile_completeness`).

### G2: EmprendimientoJourneyProgressionService

Servicio de progresion de journey con 7 reglas proactivas.

- **7 reglas proactivas** que evaluan el estado del emprendedor y disparan transiciones de fase.
- Mapeo 1:1 con las reglas de empleabilidad, pero con condiciones especificas al contexto emprendedor (e.g., completitud de BMC en lugar de completitud de perfil profesional).

### G3: EmprendimientoEmailSequenceService + 5 MJML Templates

Servicio de secuencias de email con 5 plantillas MJML dedicadas.

- **EmprendimientoEmailSequenceService** sigue el pattern identico al de empleabilidad; solo cambian las constantes (nombres de secuencia, subject lines, contenido).
- **5 plantillas MJML** creadas para cubrir los touchpoints clave del journey emprendedor.

### G4: EmprendimientoCopilotAgent

Agente de copiloto con 6 modos especializados para emprendimiento.

- **6 modos especializados** que cubren las necesidades del emprendedor en cada fase.
- Extiende el mismo `BaseAgent` que empleabilidad, pero con modes, keywords y prompts especificos para el contexto de emprendimiento.

### G5: EmprendimientoCrossVerticalBridgeService

Servicio de puentes cross-vertical con 3 puentes salientes.

- **3 puentes salientes (outgoing)** que conectan emprendimiento con otras verticales.
- La direccion del puente es relevante: estos son puentes desde emprendimiento hacia fuera, no entrantes.

### G6: CRM Sync Pipeline en jaraba_copilot_v2.module

Pipeline de sincronizacion CRM implementado dentro del modulo `jaraba_copilot_v2`.

- Replica el pattern de CRM sync ya validado en `jaraba_job_board`.
- Integrado directamente en el `.module` para mantener coherencia con la arquitectura existente.

### G7: Upgrade Triggers

Nuevos tipos de trigger y su integracion con FeatureGateService.

- **5 nuevos tipos de trigger** definidos para cubrir los eventos de upgrade en emprendimiento.
- Integracion de `fire()` en `FeatureGateService` para que los triggers se disparen correctamente al cumplirse las condiciones.

---

## Archivos Impactados

### Archivos Nuevos (10)

| # | Archivo | Gap |
|---|---------|-----|
| 1 | `EmprendimientoHealthScoreService.php` | G1 |
| 2 | `EmprendimientoJourneyProgressionService.php` | G2 |
| 3 | `EmprendimientoEmailSequenceService.php` | G3 |
| 4 | Template MJML 1 | G3 |
| 5 | Template MJML 2 | G3 |
| 6 | Template MJML 3 | G3 |
| 7 | Template MJML 4 | G3 |
| 8 | Template MJML 5 | G3 |
| 9 | `EmprendimientoCopilotAgent.php` | G4 |
| 10 | `EmprendimientoCrossVerticalBridgeService.php` | G5 |

### Archivos Modificados (6)

| # | Archivo | Gap |
|---|---------|-----|
| 1 | `ecosistema_jaraba_core.services.yml` | G1, G2, G3, G5 |
| 2 | `jaraba_copilot_v2.module` | G6 |
| 3 | `jaraba_copilot_v2.services.yml` | G4 |
| 4 | `jaraba_email.services.yml` | G3 |
| 5 | `FeatureGateService.php` | G7 |
| 6 | Configuracion de upgrade triggers | G7 |

### Modulos Tocados

1. `ecosistema_jaraba_core`
2. `jaraba_copilot_v2`
3. `jaraba_email`

---

## Resultado

Con estos 7 gaps cerrados, la vertical de Emprendimiento alcanza paridad funcional completa con la vertical de Empleabilidad. Todos los servicios core (Health Score, Journey Progression, Email Sequences, Copilot Agent, Cross-Vertical Bridges, CRM Sync, Upgrade Triggers) estan implementados y operativos.
