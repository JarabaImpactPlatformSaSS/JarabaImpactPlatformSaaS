# F9 — B2B Sales Flow (Doc 186) — Plan de Implementacion

**Fecha:** 2026-02-12
**Fase:** F9 de 12
**Modulo:** `jaraba_crm` (extension)
**Estimacion:** 16-20h
**Dependencias:** jaraba_crm existente (v3.0)

---

## 1. Objetivo

Completar el flujo de ventas B2B con pipeline predefinido
(Lead→MQL→SQL→Demo→Propuesta→Negociacion→Cerrado), sistema de
cualificacion BANT y automatizacion de playbooks por etapa.

## 2. Estado Actual

### 2.1 Modulo jaraba_crm

| Componente | Estado |
|------------|--------|
| Opportunity entity | 9 campos base (title, contact_id, value, stage, probability, expected_close, notes, tenant_id, uid) |
| Pipeline stages | 6 etapas genericas (lead, qualified, proposal, negotiation, closed_won, closed_lost) |
| PipelineStageService | createDefaultStages() con 7 etapas por tenant |
| allowed_values.yml | Directriz #20: valores desde YAML |
| BANT qualification | No existe |
| Sales Playbook | No existe |

### 2.2 Gap

| Gap | Tipo |
|-----|------|
| Etapas B2B (MQL, SQL, Demo) | Nuevas etapas pipeline |
| BANT fields (4 criterios + score) | Nuevos campos en Opportunity |
| SalesPlaybookService | Nuevo servicio |
| API playbook endpoint | Nueva ruta |

## 3. Arquitectura

### 3.1 Pipeline Stages B2B

| Machine Name | Label | Probabilidad | Color |
|-------------|-------|-------------|-------|
| lead | Lead | 10% | #94A3B8 |
| mql | MQL | 20% | #3B82F6 |
| sql | SQL | 40% | #F59E0B |
| demo | Demo | 60% | #8B5CF6 |
| proposal | Propuesta | 70% | #EC4899 |
| negotiation | Negociacion | 80% | #EF4444 |
| closed_won | Cerrado ganado | 100% | #22C55E |
| closed_lost | Cerrado perdido | 0% | #6B7280 |

### 3.2 BANT Qualification

| Campo | Tipo | Valores |
|-------|------|---------|
| bant_budget | list_string | none, exploring, allocated, approved |
| bant_authority | list_string | user, influencer, decision_maker, champion |
| bant_need | list_string | none, identified, urgent, critical |
| bant_timeline | list_string | none, 12mo, 6mo, 3mo, immediate |
| bant_score | integer | 0-4 (computed from BANT fields) |

Score = count of fields at max level (approved + champion + critical + immediate).

### 3.3 SalesPlaybookService

Match expression on stage + BANT score para recomendar accion:
- lead → Secuencia email nurturing (5 emails / 30 dias)
- mql → Llamada descubrimiento (SPIN framework, 15-20 min)
- sql + BANT>=3 → Demo personalizada
- sql + BANT<3 → Re-cualificar BANT
- demo → Enviar propuesta 3 opciones de plan
- proposal → Follow-up 3 dias si no hay respuesta
- negotiation → Preparar contrato + onboarding plan

## 4. Archivos Modificados/Creados

| Archivo | Accion |
|---------|--------|
| jaraba_crm.allowed_values.yml | Modificado (nuevas etapas + BANT values) |
| jaraba_crm.allowed_values.inc | Modificado (callbacks BANT) |
| src/Entity/Opportunity.php | Modificado (5 campos BANT) |
| src/Service/PipelineStageService.php | Modificado (defaults B2B) |
| src/Service/SalesPlaybookService.php | Nuevo |
| jaraba_crm.services.yml | Modificado |
| jaraba_crm.routing.yml | Modificado |
| jaraba_crm.permissions.yml | Modificado |
| jaraba_crm.install | Modificado (update hook) |

## 5. Verificacion

- [ ] `drush cr` exitoso
- [ ] 8 etapas pipeline en allowed_values
- [ ] 5 campos BANT en Opportunity entity
- [ ] Service `jaraba_crm.sales_playbook` registrado
- [ ] Ruta `/api/v1/crm/opportunities/{id}/playbook` activa
- [ ] BANT allowed values cargan correctamente (4 grupos)
- [ ] PipelineStageService defaults actualizados (8 etapas)
