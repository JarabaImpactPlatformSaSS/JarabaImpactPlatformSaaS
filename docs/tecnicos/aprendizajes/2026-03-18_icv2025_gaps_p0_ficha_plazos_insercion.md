# Aprendizaje #196 — ICV 2025 Gaps P0: Ficha Técnica + Plazos Normativos + Inserción SS

**Fecha:** 2026-03-18
**Módulos:** jaraba_andalucia_ei
**Reglas nuevas:** ICV25-FICHA-001, ICV25-PLAZO-001, ICV25-INSERCION-001
**Regla de oro:** #137

---

## Contexto

Auditoría de confrontación entre las "Pautas de Gestión Técnica ICV 2025" (SAE, 18/03/2026, 22 páginas) y la implementación de `jaraba_andalucia_ei`. Resultado: 93% cumplimiento con 3 gaps P0 cerrados en este sprint.

## Hallazgos

### GAP-1: FichaTecnicaEi Entity (§3.2 Pautas — de 0% a 100%)

Nueva ContentEntity con 17 campos: expediente_ref (SC/ICV/NNNN/2025), provincia, sede, representante legal + NIF, coordinador/a + NIF, personal_tecnico (JSON array con nombre, NIF, titulación, provincia, contacto), proyectos_concedidos, estado_validacion SAE (4 estados: borrador→enviada→validada→rechazada).

Ratio normativo implementado: `getRatioRequerido()` = ceil(proyectos/60). `cumpleRatio()` valida que personal_tecnico_count >= ratio. §3.4 Pautas: "al menos un miembro técnico por cada sesenta proyectos resueltos por provincia".

PremiumEntityFormBase 4 secciones: expediente, sede, personal directivo, equipo técnico.

### GAP-2: PlazoEnforcementService (§5.1/§3.1 — de 0-60% a 100%)

4 plazos normativos enforced:
1. **15 días naturales** (§5.1.A/B): recibos de servicio al STO. Alerta ALTO a 12 días, CRITICO al vencer.
2. **10 días hábiles** (§5.1.B.3): solicitud VoBo formación antes de inicio curso. Método `restarDiasHabiles()` excluye sábados, domingos y festivos nacionales+Andalucía 2026 (12 fechas).
3. **2 meses** (§5.1.C): pago incentivo €528 tras determinación persona atendida. Alerta a 45 y 60 días.
4. **18 meses** (§3.1): duración máxima programa. Alerta a 30 días, bloqueo al vencer.

El cálculo de días hábiles fue el componente más crítico. Las Pautas dicen "10 días hábiles" (no naturales) para VoBo. Usar días naturales causaría desvalidación de acciones formativas por el SAE.

Daily Action `PlazosVencidosAction` con badge dinámico: cuenta alertas CRITICO + ALTO.

### GAP-3: InsercionValidatorService (§5.2.B — de 80% a 95%)

Validación automática de 3 tipos de inserción:
- Cuenta ajena: ≥4 meses alta jornada completa
- Cuenta propia: ≥4 meses RETA
- Sector agrario: ≥3 meses Sistema Especial (NO combinable con otros regímenes — §5.2.B.1)

Desglose fiscal incentivo (Anexo IV): base 528,00€, IRPF 2% = 10,56€, neto 517,44€. Constantes centralizadas en `InsercionValidatorService::INCENTIVO_*`.

### PHPStan + Safeguard

Todos los servicios pasan PHANTOM-ARG-001, OPTIONAL-CROSSMODULE-001, LOGGER-INJECT-001. Entity pasa ENTITY-INTEG-001. Validación fast 10/10 PASS.

## Reglas nuevas

- **ICV25-FICHA-001**: Ficha Técnica entity DEBE existir con ratio 1:60 y estado validación SAE antes de operar.
- **ICV25-PLAZO-001**: Los 4 plazos normativos DEBEN estar enforced con alertas (15d recibos, 10d hábiles VoBo, 2m incentivo, 18m programa). Días hábiles = excluir fines de semana + festivos.
- **ICV25-INSERCION-001**: Inserción laboral requiere validación automática de duración SS por tipo (4m ajena, 4m propia, 3m agrario). Agrario NO combinable.

## Regla de oro #137

Los plazos normativos del PIIL DEBEN calcularse con días HÁBILES (no naturales) cuando la normativa lo especifica. `restarDiasHabiles()` DEBE excluir sábados, domingos Y festivos oficiales. Un error en este cálculo causa desvalidación de acciones formativas por el SAE — pérdida económica directa.

## Cross-refs

- Pautas Gestión Técnica ICV 2025 (SAE, 18/03/2026)
- Plan: docs/implementacion/20260318-Plan_Implementacion_ICV25_Gaps_P0_Ficha_Plazos_Insercion_v1_Claude.md
- 7 tests, 17 assertions
