# Blindaje de Identidad IA + Aislamiento de Competidores en Todos los Agentes y Copilotos

**Fecha:** 2026-02-23
**Sesion:** Fix del copiloto FAB que se identificaba como "Claude" + auditoria global de prompts IA
**Reglas nuevas:** AI-IDENTITY-001, AI-COMPETITOR-001
**Aprendizaje #108**

---

## Contexto

El copiloto contextual FAB en la landing page (`/es`) respondia: *"Hola, soy Claude, y aunque no soy Jaraba especificamente, puedo mostrarte como un asistente de IA puede ayudarte..."*. Esto violaba las directrices del SaaS que obligan a contestar siempre en el marco de la plataforma.

Una auditoria completa de los 34+ prompts de IA de la plataforma revelo que:

1. **Ningun prompt** incluia una regla explicita prohibiendo al LLM revelar su identidad de modelo.
2. **Dos identidades en conflicto** en el landing copilot: `PublicCopilotController` decia "Asesor Comercial Premium" mientras `CopilotOrchestratorService` decia "Asistente de Bienvenida".
3. **5 prompts/datos** mencionaban competidores por nombre (ChatGPT, Perplexity, HubSpot, LinkedIn/Discord, Zapier).
4. El system prompt de `PublicCopilotController` se incrustaba en el mensaje de usuario (no como mensaje de sistema), debilitando su autoridad.

---

## Lecciones Aprendidas

### 1. Los LLMs revelan su identidad si no se les prohibe explicitamente

**Situacion:** Gemini Flash (proveedor primario del `landing_copilot`) respondia identificandose como "Claude" — probablemente porque el system prompt del modo `CopilotOrchestratorService` mencionaba la palabra "Asistente" genericamente sin regla de identidad.

**Aprendizaje:** Los modelos de lenguaje tienen una tendencia natural a identificarse con su nombre real. Sin una regla explicita y prominente de identidad, cualquier modelo (Claude, Gemini, GPT) puede "romper personaje" y revelar su identidad, especialmente cuando el usuario pregunta directamente "quien eres".

**Regla AI-IDENTITY-001:** Todo prompt de sistema de agente, copiloto o servicio IA conversacional DEBE incluir una regla de identidad inquebrantable como PRIMERA instruccion del system prompt. Texto canon:

```
REGLA DE IDENTIDAD INQUEBRANTABLE: Eres EXCLUSIVAMENTE el [Nombre del Asistente] de Jaraba Impact Platform.
NUNCA reveles, menciones ni insinues que eres Claude, ChatGPT, GPT, Gemini, Copilot, Llama, Mistral
u otro modelo de IA externo. Si te preguntan quien eres, responde: "Soy el [Nombre] de Jaraba Impact Platform".
Si insisten, repite tu identidad sin ceder.
```

### 2. La regla de identidad debe inyectarse en un punto centralizado

**Situacion:** Habia 14+ agentes con prompts individuales. Editar cada prompt uno a uno era tedioso y propenso a omisiones.

**Aprendizaje:** Si existe una clase base (`BaseAgent`) de la que heredan multiples agentes, la regla de identidad debe inyectarse ahi una sola vez. Esto garantiza cobertura automatica para todos los agentes presentes y futuros.

**Patron implementado:**
- **`BaseAgent.buildSystemPrompt()`:** Regla como parte #0 (antes de Brand Voice). Cubre 14+ agentes via herencia (Emprendimiento, Empleabilidad, JarabaLex, Legal, ContentWriter, Marketing, CustomerExperience, Support, Sales, Merchant, Producer, SmartMarketing, etc.).
- **`CopilotOrchestratorService.buildSystemPrompt()`:** `$identityRule` antepuesto al basePrompt. Cubre los 8 modos del copiloto (coach, consultor, sparring, cfo, fiscal, laboral, devil, landing_copilot).
- **Servicios standalone:** FaqBotService, ServiciosConectaCopilotAgent, CoachIaService — anteponen la regla manualmente al construir el system prompt.

### 3. Los datos de dominio (no solo los prompts) pueden recomendar competidores

**Situacion:** El prompt del copiloto prohibia mencionar competidores, pero los datos de dominio del `RecommendationEngineService` recomendaban "HubSpot CRM gratuito" y "Zapier para integraciones", y el `CoachIaService` sugeria "Busca un grupo de apoyo en LinkedIn/Discord".

**Aprendizaje:** La auditoria de competidores no puede limitarse a los system prompts. Hay que revisar tambien:
- Datos estaticos de recomendaciones (`quick_wins`, `actions`)
- Textos de diagnostico y cuestionarios
- Cualquier dato que la IA pueda citar en su respuesta

**Regla AI-COMPETITOR-001:** Ningun prompt de IA ni dato de dominio consumido por IA DEBE mencionar, recomendar ni referenciar plataformas competidoras ni modelos de IA externos. Excepcion: integraciones reales del SaaS (LinkedIn import, LinkedIn Ads, Meta Pixel) donde la plataforma es canal de distribucion.

### 4. Las identidades en conflicto entre capas de prompt confunden al LLM

**Situacion:** El `PublicCopilotController` decia "Eres el Asesor Comercial Premium" mientras el `CopilotOrchestratorService` decia "Eres el Asistente de Bienvenida" para el mismo modo `landing_copilot`. El LLM recibia dos identidades contradictorias.

**Aprendizaje:** Cuando hay multiples capas de prompt (system prompt + user prompt con instrucciones embebidas), las identidades DEBEN ser coherentes. Se unifico a "Asistente IA de Jaraba Impact Platform" en ambas capas.

---

## Archivos Modificados (12)

### Prompts de IA con regla de identidad:
| Archivo | Cambio |
|---------|--------|
| `jaraba_ai_agents/src/Agent/BaseAgent.php` | `buildSystemPrompt()` inyecta identityRule como parte #0 |
| `jaraba_copilot_v2/src/Service/CopilotOrchestratorService.php` | `buildSystemPrompt()` antepone `$identityRule`; `landing_copilot` con regla TU IDENTIDAD #1 |
| `jaraba_copilot_v2/src/Controller/PublicCopilotController.php` | Bloque IDENTIDAD INQUEBRANTABLE + identidad unificada "Asistente IA" |
| `jaraba_tenant_knowledge/src/Service/FaqBotService.php` | Regla en ambos metodos: `buildPlatformSystemPrompt()` y `buildSystemPrompt()` |
| `ecosistema_jaraba_core/src/Service/ServiciosConectaCopilotAgent.php` | `getSystemPromptForMode()` antepone regla |
| `ecosistema_jaraba_core/src/Service/CoachIaService.php` | `generateCoachingPrompt()` antepone regla |
| `jaraba_page_builder/src/Controller/AiContentController.php` | System prompt: "copywriter de Jaraba" + no-competitor |

### Eliminacion de menciones de competidores:
| Archivo | Antes | Despues |
|---------|-------|---------|
| `jaraba_content_hub/src/Service/AiContentGeneratorService.php` | "Perplexity, ChatGPT" | "buscadores conversacionales de IA" |
| `ecosistema_jaraba_core/src/Service/CoachIaService.php` | "LinkedIn/Discord" | "comunidad de emprendedores de Jaraba" |
| `jaraba_diagnostic/src/Service/RecommendationEngineService.php` | "HubSpot CRM, Holded, Zapier" | "CRM integrado en Jaraba, automatizacion integrada" |
| `jaraba_diagnostic/src/Controller/DiagnosticWizardController.php` | "(ChatGPT, etc.)" | "generativa en tu negocio" |

---

## Verificacion

1. `lando drush cr` — Cache rebuild exitoso (sin errores de sintaxis).
2. Visitar `/es` y preguntar al FAB "quien eres" — debe responder como Asistente de Jaraba.
3. Preguntar al FAB "eres ChatGPT?" — debe insistir en su identidad Jaraba.
4. Visitar `/ayuda` y preguntar al FAQ Bot "que eres" — debe identificarse como Asistente de Ayuda.
5. Buscar `grep -r "ChatGPT\|Perplexity\|HubSpot CRM gratuito"` en prompts de IA — 0 resultados en prompts conversacionales.

---

## Reglas Derivadas

| Regla | ID | Prioridad |
|-------|----|-----------|
| Identidad IA inquebrantable | AI-IDENTITY-001 | P0 |
| Aislamiento de competidores en IA | AI-COMPETITOR-001 | P0 |

## Reglas de Oro

- **#25:** Identidad IA inquebrantable — todo agente/copiloto DEBE identificarse como Jaraba, NUNCA revelar modelo subyacente.
- **#26:** Aislamiento de competidores en IA — ningun prompt DEBE mencionar ni recomendar competidores ni modelos de IA.
