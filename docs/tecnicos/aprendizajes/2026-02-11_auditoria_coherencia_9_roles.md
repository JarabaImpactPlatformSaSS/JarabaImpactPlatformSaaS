# 🔬 Auditoría Coherencia 9 Roles — Aprendizajes

**Fecha:** 2026-02-11  
**Contexto:** Cross-referencia multi-disciplinaria (9 roles senior) de especificaciones 20260118 vs codebase real  
**Impacto:** Corrección de errores en auditoría inicial y actualización de documentación

---

## 1. Nunca dar por "no implementado" sin verificar código real

### Problema
La auditoría inicial calificó Doc 134 (Stripe Billing) como **0% implementado**, basándose en:
- No existir un módulo `jaraba_billing` dedicado
- No buscar en otros módulos que podrían contener funcionalidad equivalente

### Realidad
**~35-40%** ya estaba implementado, distribuido entre dos módulos:

| Módulo | Servicios/Entidades Billing |
|--------|-----------------------------|
| `ecosistema_jaraba_core` | `JarabaStripeConnect`, `TenantSubscriptionService`, `TenantMeteringService`, `WebhookService` |
| `jaraba_foc` | `StripeConnectService`, `SaasMetricsService`, `FinancialTransaction`, `CostAllocation`, `FocMetricSnapshot` |

### Regla
> **AUDIT-001**: Antes de declarar una feature como "no implementada", buscar con `grep -rn` y `find` en TODO el codebase, no solo en el módulo esperado por la spec. La funcionalidad puede estar fragmentada entre módulos.

---

## 2. Duplicación de servicios es deuda técnica silenciosa

### Hallazgo
`JarabaStripeConnect` (en core) y `StripeConnectService` (en FOC) implementan los mismos conceptos de Stripe Connect pero con APIs diferentes.

### Impacto
- Confusión sobre cuál es la autoridad
- Riesgo de inconsistencia de estado
- Mayor superficie de bugs

### Recomendación
Consolidar en `jaraba_foc` como módulo autoridad para billing/finanzas. `ecosistema_jaraba_core` debería delegar a FOC, no implementar billing directamente.

---

## 3. CI sin tests es peor que no tener CI

### Hallazgo
`phpunit.xml` configurado correctamente. GitHub Actions `ci.yml` ejecuta PHPUnit. Pero **0 archivos `*Test.php`** en `web/modules/custom/`.

### Efecto
El pipeline CI reporta **✅ PASS** dando falsa sensación de seguridad. Es peor que no tener CI porque genera confianza injustificada.

### Regla
> **QA-001**: Incluir un _smoke test_ mínimo (al menos 1 test por módulo core) antes de activar PHPUnit en CI. Un pipeline que pasa sin tests es un indicador engañoso.

---

## 4. Los datos de billing NUNCA deben estar en `Drupal\State`

### Hallazgo
`TenantSubscriptionService` almacena `grace_period_end` y `cancel_at` en `Drupal\State API`, que es volátil y no auditable.

### Problema
- `State` no es exportable via config
- No tiene revisionado
- Se pierde en rebuilds
- No es auditable para compliance financiero

### Regla
> **BIZ-002**: Datos financieros/billing siempre en Content Entity fields (auditables, versionados, exportables). `State` es solo para flags temporales no-críticos.

---

## 5. Contar archivos en docs ≠ contar archivos reales

### Hallazgo

| Documento | Dice | Real |
|-----------|------|------|
| Directrices §2.2.1 | "8 módulos con package.json" | 14 módulos |
| Dok Maestro Theming | "9 módulos con package.json" | 14 módulos |
| Índice General Estadísticas | "17 docs arquitectura" | 26 docs |
| Índice General Estadísticas | "11 docs planificación" | 15 docs |

### Regla
> **DOC-003**: Nunca hardcodear conteos de archivos. Verificar con `find` o `fd` antes de actualizar documentación.

---

## 6. SEO/GEO e IA son los puntos más fuertes

### Validación positiva
Ambas áreas tienen implementación excepcional:

- **SEO/GEO**: `jaraba_geo` (Schema.org JSON-LD para 8 tipos), `SchemaOrgService` en Page Builder, `llms.txt`, Answer Capsules, hreflang
- **IA**: AI Trilogy completa, Copiloto v2 (5 modos), RAG + Qdrant, AI Guardrails, FinOps AI, 50+ servicios

No requieren correcciones de coherencia.

---

## Checklist — Reglas de Auditoría

- [ ] AUDIT-001: Buscar features en TODO el codebase, no solo módulo esperado
- [ ] QA-001: CI con tests vacíos = falsa seguridad
- [ ] BIZ-002: Datos billing en entities, nunca en State
- [ ] DOC-003: Verificar conteos con herramientas antes de documentar
