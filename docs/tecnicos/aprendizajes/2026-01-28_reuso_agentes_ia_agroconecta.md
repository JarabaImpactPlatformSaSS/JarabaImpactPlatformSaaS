# Aprendizaje: Reuso Agentes IA AgroConecta

> **Fecha:** 2026-01-28  
> **Contexto:** Migración agentes marketing de AgroConecta al SaaS  
> **Artefactos:**  
> - [Auditoría](../20260128-Auditoria_Arquitectura_IA_SaaS_v1_Claude.md)  
> - [Implementación](../../implementacion/20260128h-Bloque_H_AI_Agents_Multi_Vertical_Implementacion_Claude.md)

---

## Contexto del Problema

El proyecto AgroConecta tiene 8 agentes IA probados en producción:
- MarketingAgent, StorytellingAgent, CustomerExperienceAgent
- RecipeAgent, PricingAgent, SustainabilityAgent
- SupportAgent, CopilotAgent

El SaaS necesita capacidades similares para 4 verticales.

---

## Lecciones Aprendidas

### 1. Copiloto vs Agentes: Paradigmas Complementarios

| Copiloto | Agentes |
|----------|---------|
| Conversacional | Orientado a acciones |
| Mensaje libre | Acción explícita |
| Texto + sugerencias | JSON estructurado |

**Decisión:** Mantener ambos sistemas, no fusionarlos.

### 2. Consistencia Arquitectónica

El SaaS tiene directrices claras:
- `@ai.provider` para todas las llamadas LLM
- ConfigEntity `AIAgent` para registro
- Niveles de autonomía 0-3

**Acción:** Asegurar que agentes migrados cumplan estas directrices.

### 3. Multi-Tenancy Crítico

Los agentes de AgroConecta son mono-tenant. Para el SaaS:

```php
// ANTES (AgroConecta)
$config = $this->configFactory->get('agroconecta_core.brand_voice');

// DESPUÉS (SaaS)
$config = $this->configFactory->get("jaraba_ai_agents.brand_voice.{$tenantId}");
```

### 4. ROI del Reuso

| Concepto | Sin Reuso | Con Reuso |
|----------|-----------|-----------|
| Desarrollo | 145h | 52h |
| Ahorro | - | ~€6,000 |

---

## Checklist Aplicable

Antes de migrar cualquier agente IA:

- [ ] ¿Existe servicio similar en SaaS?
- [ ] ¿Usa `@ai.provider` (no HTTP directo)?
- [ ] ¿Multi-tenancy implementado?
- [ ] ¿Brand Voice por tenant?
- [ ] ¿Registrado en ConfigEntity `AIAgent`?
- [ ] ¿Prompts traducibles (i18n)?

---

## Referencias

- Workflow: `.agent/workflows/ai-integration.md`
- Directrices: `docs/00_DIRECTRICES_PROYECTO.md`
- ConfigEntity: `ecosistema_jaraba_core/src/Entity/AIAgent.php`
