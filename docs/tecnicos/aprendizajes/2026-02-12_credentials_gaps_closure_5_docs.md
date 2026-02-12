# Aprendizaje: Cierre de 5 Gaps Sistema de Credenciales

**Fecha:** 2026-02-12
**Módulo:** `jaraba_credentials` + 2 submódulos
**Specs:** Docs 170, 172, 173, 174, 175

---

## Contexto

El módulo `jaraba_credentials` implementa el sistema de credenciales Open Badge 3.0 de la plataforma. La auditoría de especificaciones técnicas 20260126 identificó 5 gaps críticos que impedían la cobertura completa del sistema.

## Gaps Cerrados

### Gap 1: RevocationEntry + Audit Trail (Doc 172)

**Problema:** No existía entidad para registro de revocaciones. La revocación era un cambio de status sin trazabilidad.

**Solución:**
- Content Entity `RevocationEntry` con campos: `credential_id`, `revoked_by_uid`, `reason` (fraud/error/request/policy), `notes`, `tenant_id`
- `RevocationService` con 3 métodos: `revoke()`, `isRevoked()`, `getRevocationHistory()`
- `CredentialVerifier` actualizado para consultar `RevocationEntry` antes de validar
- API endpoint `POST /api/v1/credentials/{id}/revoke`

**Regla CRED-001:** La revocación de credenciales SIEMPRE crea un `RevocationEntry` como audit trail inmutable, además de actualizar el status de `IssuedCredential`.

### Gap 2: Stackable Credentials (Doc 173)

**Problema:** No existía mecanismo para combinar badges individuales en diplomas/certificaciones compuestas.

**Solución:**
- Content Entity `CredentialStack`: define combinaciones (nombre, required_templates JSON, min_required, bonus_credits/xp, eqf_level, ects_credits)
- Content Entity `UserStackProgress`: rastrea progreso (completed_templates JSON, progress_percent, status)
- `StackEvaluationService`: evalúa automáticamente al emitir cada credencial
- `StackProgressTracker`: progreso incremental, stacks recomendados (top 10 por %)
- 5 endpoints REST API (`/api/v1/stacks/*`)

**Regla CRED-002:** La evaluación de stacks se dispara en `hook_entity_insert` de `issued_credential`. La recursión se previene verificando `str_contains($evidence, 'stack_id')`.

### Gap 3: Credenciales Emprendimiento (Doc 175)

**Problema:** No existían badges específicos para la vertical de emprendimiento.

**Solución:**
- Submódulo `jaraba_credentials_emprendimiento` con dependencias: jaraba_credentials + jaraba_business_tools
- 15 template YAMLs en config/install (12 badges individuales + 3 diplomas compuestos)
- 3 servicios: `EmprendimientoCredentialService` (15 tipos), `EmprendimientoExpertiseService` (5 niveles), `EmprendimientoJourneyTracker` (6 fases)
- Diplomas progresivos: Emprendedor Digital Basico → Avanzado → Transformador Digital Expert
- Hooks nativos para business_diagnostic, canvas, MVP, mentoring

**Regla CRED-003:** Los templates de credencial se distribuyen como YAML en `config/install/` y se importan automáticamente al habilitar el submódulo con `drush en`.

### Gap 4: Cross-Vertical Credentials (Doc 174)

**Problema:** No existían badges que reconocieran logros transversales entre verticales (ej: empleabilidad + emprendimiento).

**Solución:**
- Submódulo `jaraba_credentials_cross_vertical`
- Content Entity `CrossVerticalRule`: reglas con condiciones por vertical (credentials_count, milestones, transactions, gmv_threshold)
- Content Entity `CrossVerticalProgress`: progreso por vertical con overall_percent
- `CrossVerticalEvaluator`: evaluación en `hook_entity_insert` + cron diario (rate-limited con State API)
- Sistema de rareza visual: common, rare, epic, legendary (con estilos gradientes)

**Regla CRED-004:** La evaluación cross-vertical en cron se limita a 1 ejecución diaria usando `\Drupal::state()` con timestamp de última ejecución (86400s cooldown).

### Gap 5: WCAG 2.1 AA (Doc 170)

**Problema:** Los templates existentes carecían de atributos ARIA, focus visible y soporte prefers-reduced-motion.

**Solución:**
- `AccessibilityAuditService`: auditoría programática (alt text, heading hierarchy, empty links, contraste WCAG con luminancia relativa)
- Templates existentes actualizados: `role`, `aria-label`, `aria-live`, `tabindex`, `aria-describedby`
- SCSS: `:focus-visible` con `--ej-focus-ring-*` en todos los elementos interactivos
- SCSS: `@media (prefers-reduced-motion: reduce)` deshabilitando animaciones
- JS: navegación por teclado (arrow keys) en grids de credenciales
- JS: modal share con `role="dialog"`, `aria-modal`, Escape key, focus management
- JS: toast con `role="status"`, `aria-live="assertive"`

**Regla CRED-005:** Todo componente nuevo del sistema de credenciales DEBE incluir: (1) focus-visible con variables --ej-focus-ring-*, (2) prefers-reduced-motion media query, (3) navegación por teclado con arrow keys en grids, (4) ARIA labels en contenedores region/main.

## Patrones Arquitectónicos

### ECA: Hooks Nativos, NO YAML BPMN

Per `.agent/workflows/drupal-eca-hooks.md`, todas las automatizaciones se implementan como hooks nativos de Drupal (`hook_entity_insert`, `hook_entity_update`, `hook_cron`), NO como configuraciones YAML de ECA. Esto asegura:
- Versionado en Git
- Testabilidad unitaria
- Control de flujo preciso
- Rendimiento predecible

### Prevención de Recursión

Las evaluaciones automáticas (stacks, cross-vertical) verifican el campo `evidence` del JSON de la credencial antes de re-evaluar:
```php
if (str_contains($evidence, '"stack_id"')) return; // Skip stack credentials
if (str_contains($evidence, '"cross_vertical_rule_id"')) return; // Skip CV credentials
```

### Submódulos vs Módulo Padre

Los gaps 3 y 4 se implementaron como submódulos dentro de `jaraba_credentials/modules/` porque:
- Tienen dependencias adicionales (jaraba_business_tools)
- Son opcionales (no todo tenant necesita badges de emprendimiento)
- Pueden habilitarse/deshabilitarse independientemente
- Sus config YAMLs se importan solo al activar el submódulo

## Inventario Final

| Componente | Archivos | Entidades | Servicios | Endpoints |
|------------|----------|-----------|-----------|-----------|
| jaraba_credentials (core) | 45+ | 6 (IssuerProfile, CredentialTemplate, IssuedCredential, RevocationEntry, CredentialStack, UserStackProgress) | 11 | 12 |
| jaraba_credentials_emprendimiento | 29 | 0 (usa templates YAML) | 3 | 5 |
| jaraba_credentials_cross_vertical | 22 | 2 (CrossVerticalRule, CrossVerticalProgress) | 2 | 3 |
| **Total** | **115** | **8** | **16** | **20** |

## Archivos Clave

- `src/Entity/RevocationEntry.php` — Audit trail de revocaciones
- `src/Entity/CredentialStack.php` — Definición de stacks
- `src/Entity/UserStackProgress.php` — Progreso hacia stacks
- `src/Service/RevocationService.php` — Revocación con audit trail
- `src/Service/StackEvaluationService.php` — Auto-evaluación de stacks
- `src/Service/StackProgressTracker.php` — Progreso incremental
- `src/Service/AccessibilityAuditService.php` — Auditoría WCAG
- `modules/jaraba_credentials_emprendimiento/` — 15 badges emprendimiento
- `modules/jaraba_credentials_cross_vertical/` — Badges transversales

## Reglas Nuevas

| Regla | Descripción |
|-------|-------------|
| CRED-001 | Revocación siempre crea RevocationEntry inmutable |
| CRED-002 | Anti-recursión stacks via evidence JSON check |
| CRED-003 | Templates credencial como YAML config/install |
| CRED-004 | Cron cross-vertical rate-limited 86400s con State API |
| CRED-005 | WCAG obligatorio: focus-visible + reduced-motion + keyboard nav + ARIA |
| ECA-001 | Hooks nativos, NO YAML BPMN para automatizaciones |
