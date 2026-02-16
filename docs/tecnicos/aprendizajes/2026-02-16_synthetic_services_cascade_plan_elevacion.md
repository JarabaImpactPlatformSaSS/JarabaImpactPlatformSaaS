# Aprendizaje #89 — Synthetic Services Cascade: Plan Elevacion JarabaLex

| Campo | Valor |
|-------|-------|
| Fecha | 2026-02-16 |
| CI Run Fallido | [22075990606](https://github.com/JarabaImpactPlatformSaSS/JarabaImpactPlatformSaaS/actions/runs/22075990606) |
| CI Run Corregido | [22076325163](https://github.com/JarabaImpactPlatformSaSS/JarabaImpactPlatformSaaS/actions/runs/22076325163) |
| Commits fix | `d270cfdb`, `9c2590d6` |

---

## Contexto

El commit `cb11746b` (Plan Elevacion JarabaLex v1 — 14 fases) anadio nuevas dependencias de servicios en `jaraba_legal_intelligence.services.yml`:

- `ecosistema_jaraba_core.jarabalex_feature_gate` (Fase 4: Feature Gating)
- `jaraba_ai_agents.tenant_brand_voice` (Copilot Agent)
- `jaraba_ai_agents.observability` (Copilot Agent)
- `ecosistema_jaraba_core.unified_prompt_builder` (Copilot Agent)

Los 3 Kernel tests existentes no los registraban como sinteticos, causando **18 errores** (3 clases x 5/5/8 metodos).

---

## Patron Cascada de Servicios Sinteticos

### Problema: Whack-a-Mole en CI

El primer fix solo registro `jarabalex_feature_gate`. CI revelo inmediatamente el siguiente servicio faltante (`tenant_brand_voice`). Esto genero 2 commits donde un solo commit habria bastado.

### Causa Raiz

Al anadir servicios a un `.services.yml`, el compilador DI de Drupal valida **todas** las dependencias `@service_name` del modulo, no solo las del servicio que se uso. Si el test habilita `jaraba_legal_intelligence`, **todos** sus servicios deben poder compilarse, aunque el test no los use directamente.

### Aprendizaje

**KERNEL-SYNTH-002:** Al anadir nuevos servicios con dependencias externas a un `.services.yml`, se DEBEN actualizar **todos** los Kernel tests del modulo en el **mismo commit**. El checklist es:

1. Extraer TODAS las dependencias `@nombre` del `.services.yml`
2. Filtrar las que pertenecen a modulos NO listados en `$modules`
3. Registrar CADA una como sintetica en `register()` + mock en `setUp()`
4. Hacerlo en un solo commit para evitar cascada de fallos CI

### Comando de verificacion

```bash
# Extraer todas las dependencias externas de un services.yml
grep -oP "'@\K[^'@?][^']*" web/modules/custom/MODULO/MODULO.services.yml | \
  sort -u | \
  grep -v "^logger\.\|^entity_type\.\|^config\.\|^database\|^current_user\|^http_client\|^queue\|^service_container\|^plugin\.\|^string_translation" | \
  grep -v "^MODULO\."
```

---

## Servicios Afectados

| Servicio | Modulo proveedor | Razon de dependencia |
|----------|-----------------|----------------------|
| `ecosistema_jaraba_core.jarabalex_feature_gate` | ecosistema_jaraba_core | Feature gating por plan (5 servicios) |
| `ecosistema_jaraba_core.tenant_context` | ecosistema_jaraba_core | Contexto multi-tenant (ya existia) |
| `ecosistema_jaraba_core.unified_prompt_builder` | ecosistema_jaraba_core | Prompt builder para copilot agent |
| `jaraba_ai_agents.tenant_brand_voice` | jaraba_ai_agents | Brand voice para copilot agent |
| `jaraba_ai_agents.observability` | jaraba_ai_agents | Observabilidad AI para copilot agent |
| `ai.provider` | ai (contrib) | Proveedor AI (ya existia) |

## Archivos Modificados

| Archivo | Tests | Cambio |
|---------|-------|--------|
| `tests/src/Kernel/LegalAlertServiceTest.php` | 5 | +4 synthetic + 4 mocks |
| `tests/src/Kernel/LegalIngestionTest.php` | 5 | +4 synthetic + 4 mocks |
| `tests/src/Kernel/LegalResolutionEntityTest.php` | 8 | +4 synthetic + 4 mocks |

---

## Metricas

| Metrica | Valor |
|---------|-------|
| Errores CI iniciales | 18 |
| Errores CI finales | 0 |
| Commits de correccion | 2 (debio ser 1) |
| Servicios sinteticos anadidos | 4 nuevos (6 total) |
| Tiempo CI desperdiciado | ~6 min (1 run fallido extra) |
| Deploy a produccion | OK (run 22076325163) |

---

## Regla

**KERNEL-SYNTH-002:** Al introducir nuevas dependencias `@service` en un `.services.yml`, actualizar TODOS los Kernel tests del modulo en el MISMO commit. Extraer la lista completa de dependencias externas, no solo la del servicio que se esta anadiendo. El compilador DI valida el modulo entero, no servicios individuales.
