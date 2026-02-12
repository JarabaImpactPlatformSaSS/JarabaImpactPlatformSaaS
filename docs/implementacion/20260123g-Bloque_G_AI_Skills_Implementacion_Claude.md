# Bloque G: AI Skills System - Documento de Implementación
## Sistema de Enseñanza y Especialización Continua de Agentes IA

**Fecha de creación:** 2026-01-23 20:50  
**Última actualización:** 2026-01-23 20:50  
**Autor:** IA Asistente (Claude)  
**Versión:** 1.0.0

---

## 📑 Tabla de Contenidos (TOC)

1. [Matriz de Especificaciones](#1-matriz-de-especificaciones)
2. [Checklist Multidisciplinar](#2-checklist-multidisciplinar-8-expertos)
3. [G.1 Entidades Base](#3-g1-entidades-base)
4. [G.2 SkillManager Service](#4-g2-skillmanager-service)
5. [G.3 APIs REST](#5-g3-apis-rest)
6. [G.4 Editor Visual](#6-g4-editor-visual)
7. [G.5 Skills Predefinidas](#7-g5-skills-predefinidas)
8. [Checklist Directrices Obligatorias](#8-checklist-directrices-obligatorias)
9. [Registro de Cambios](#9-registro-de-cambios)

---

## 1. Matriz de Especificaciones

### 1.1 Documento de Referencia Principal

| Doc | Archivo | Contenido Clave |
|-----|---------|-----------------|
| 129 | [20260118i1-129_Platform_AI_Skills_System_v1_Claude.md](../tecnicos/20260118i1-129_Platform_AI_Skills_System_v1_Claude.md) | Arquitectura completa 50KB |
| 129-Anexo | [20260118i2-129_Platform_AI_Skills_System_v1_AnexoA_Claude.md](../tecnicos/20260118i2-129_Platform_AI_Skills_System_v1_AnexoA_Claude.md) | Skills de ejemplo |

### 1.2 Propuesta de Valor

| Aspecto | RAG Tradicional | Skills System |
|---------|-----------------|---------------|
| Conocimiento | Información factual (QUÉ) | Procedimental (CÓMO) |
| Personalización | Por tenant (datos) | Tenant + Vertical + Agente + Tarea |
| Aprendizaje | Estático (indexación) | Dinámico (versiones, A/B) |
| Diferenciación | Commodity | **Ventaja competitiva única** |

### 1.3 🔄 Estrategia de Reuso

> ⚠️ **VERIFICACIÓN PREVIA**: Antes de cada paso, ejecutar análisis de reuso.

| Componente Reutilizable | Módulo Origen | Acción |
|-------------------------|---------------|--------|
| Claude API Client | `jaraba_copilot_v2` | Extender |
| Qdrant Integration | `jaraba_rag` | Referenciar |
| Monaco Editor | npm package | Instalar |
| Design Tokens | `jaraba_theming` | Integrar |

---

## 2. Checklist Multidisciplinar (8 Expertos)

### 2.1 Consultor de Negocio Senior

| Verificación | Estado | Notas |
|--------------|--------|-------|
| ¿Diferenciador competitivo claro? | [ ] | Skills vs RAG commodity |
| ¿Modelo freemium para skills? | [ ] | Starter: 0, Pro: 10, Enterprise: ilimitado |
| ¿Monetización por skill marketplace? | [ ] | Enterprise only |

### 2.2 Arquitecto SaaS Senior

| Verificación | Estado | Notas |
|--------------|--------|-------|
| ¿Herencia jerárquica 4 capas? | [ ] | Core → Vertical → Agent → Tenant |
| ¿Aislamiento skills por tenant? | [ ] | scope_id + tenant_id |
| ¿Cache Redis para skills frecuentes? | [ ] | TTL 3600s |

### 2.3 Ingeniero Software Senior

| Verificación | Estado | Notas |
|--------------|--------|-------|
| ¿PHPUnit para SkillManager? | [ ] | resolveSkills(), applyInheritance() |
| ¿Jest para Editor React? | [ ] | Monaco integration |
| ¿Versioning con revisions? | [ ] | Drupal Revision System |

### 2.4 Ingeniero IA Senior

| Verificación | Estado | Notas |
|--------------|--------|-------|
| ¿Prompt injection seguro? | [ ] | `<skills>...</skills>` block |
| ¿Token budget por skill? | [ ] | Max 50KB por skill |
| ¿Embeddings para skill matching? | [ ] | Qdrant collection `skills_embeddings` |

### 2.5 Ingeniero UX Senior

| Verificación | Estado | Notas |
|--------------|--------|-------|
| ¿Editor Monaco con preview? | [ ] | Split view |
| ¿Test Console funcional? | [ ] | Input → Output con LLM real |
| ¿Version History con diff? | [ ] | React Diff Viewer |

---

## 3. G.1 Entidades Base

> **Referencia:** Doc 129 sección 3

### 3.1 Crear módulo (Sprint G1: 15h)

```bash
# Estructura del módulo
modules/custom/jaraba_skills/
├── jaraba_skills.info.yml
├── jaraba_skills.module
├── jaraba_skills.services.yml
├── jaraba_skills.routing.yml
├── jaraba_skills.permissions.yml
├── src/
│   ├── Entity/
│   │   ├── AiSkill.php
│   │   ├── AiSkillRevision.php
│   │   ├── AiSkillUsage.php
│   │   └── AiSkillEmbedding.php
│   ├── Service/
│   │   ├── SkillManager.php
│   │   └── SkillPromptBuilder.php
│   ├── ValueObject/
│   │   ├── SkillContext.php
│   │   └── ResolvedSkillSet.php
│   └── Controller/
│       └── SkillApiController.php
└── config/install/
```

### 3.2 Entidad ai_skill (20h)

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | SERIAL | PRIMARY KEY |
| uuid | UUID | Identificador único |
| machine_name | VARCHAR(64) | Nombre máquina, único por scope |
| label | VARCHAR(255) | Nombre legible |
| description | TEXT | Descripción |
| scope | VARCHAR(32) | ENUM: core, vertical, agent, tenant |
| scope_id | VARCHAR(64) | ID del scope específico |
| tenant_id | INT | FK groups.id (solo scope=tenant) |
| task_types | JSON | Array de task_type strings |
| content | LONGTEXT | Markdown de la skill (max 50KB) |
| priority | INT | Mayor = más prioridad |
| requires_skills | JSON | Array de machine_names dependientes |
| is_active | BOOLEAN | DEFAULT TRUE |
| is_locked | BOOLEAN | DEFAULT FALSE (core skills locked) |

- [ ] Crear Content Entity con annotation
- [ ] Handler `views_data` para Views
- [ ] Handler `list_builder` para admin
- [ ] 4 archivos YAML (routing, menu, task, action)

### 3.3 Entidad ai_skill_revision (10h)

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | SERIAL | ID de revisión |
| skill_id | INT | FK ai_skill.id |
| version | INT | Auto increment por skill |
| content | LONGTEXT | Contenido en esta versión |
| change_summary | VARCHAR(500) | Resumen del cambio |
| changed_by | INT | FK users.uid |
| is_active | BOOLEAN | Solo una TRUE por skill |

### 3.4 Entidad ai_skill_usage (10h)

| Campo | Tipo | Descripción |
|-------|------|-------------|
| skill_id | INT | FK ai_skill.id |
| skill_version | INT | Versión usada |
| tenant_id | INT | Tenant del contexto |
| agent_type | VARCHAR(64) | Tipo agente |
| task_type | VARCHAR(64) | Tarea ejecutada |
| input_tokens | INT | Tokens input |
| output_tokens | INT | Tokens output |
| user_feedback | TINYINT | -1 a 1 |

---

## 4. G.2 SkillManager Service

> **Referencia:** Doc 129 sección 9.1

### 4.1 Servicio Principal (Sprint G3-G4: 40-50h)

```php
<?php
// src/Service/SkillManager.php

class SkillManager {
    
    /**
     * Resuelve skills aplicables para un contexto.
     * 
     * Pipeline:
     * 1. Load Core Skills (siempre aplican)
     * 2. Load Vertical Skills (si $context->vertical)
     * 3. Load Agent Skills (si $context->agentType)
     * 4. Load Tenant Skills (máxima prioridad)
     * 5. Resolve Dependencies
     * 6. Apply Inheritance/Overrides
     * 7. Sort by Priority (mayor primero)
     */
    public function resolveSkills(SkillContext $context): ResolvedSkillSet;
    
    /**
     * Genera bloque <skills> para inyectar en prompt.
     */
    public function generatePromptSection(ResolvedSkillSet $skillSet): string;
    
    /**
     * Busca skills por similitud semántica.
     */
    public function searchSkillsBySimilarity(
        string $query,
        string $tenantId,
        int $limit = 5
    ): array;
    
    /**
     * Registra uso de skills para analytics.
     */
    public function logSkillUsage(
        ResolvedSkillSet $skillSet,
        string $tenantId,
        string $agentType,
        string $taskType,
        int $inputTokens,
        int $outputTokens,
        int $latencyMs
    ): void;
}
```

### 4.2 Value Objects (10h)

```php
// src/ValueObject/SkillContext.php
final class SkillContext {
    public function __construct(
        private ?string $tenantId,
        private ?string $vertical,
        private ?string $agentType,
        private string $taskType,
        private array $additionalContext = []
    ) {}
    
    public static function fromRequest(array $data): self;
}

// src/ValueObject/ResolvedSkillSet.php
final class ResolvedSkillSet {
    /** @param AiSkill[] $skills */
    public function __construct(private array $skills) {}
    
    public function getSkills(): array;
    public function getTotalTokens(): int;
    public function getMachineNames(): array;
}
```

---

## 5. G.3 APIs REST

> **Referencia:** Doc 129 sección 6

### 5.1 Endpoints (Sprint G5-G6: 30-40h)

| Método | Endpoint | Descripción | Permisos |
|--------|----------|-------------|----------|
| GET | `/api/v1/skills` | Listar skills del tenant | authenticated |
| POST | `/api/v1/skills` | Crear skill | admin, skill_manager |
| GET | `/api/v1/skills/{id}` | Detalle skill | authenticated |
| PUT | `/api/v1/skills/{id}` | Actualizar (crea revisión) | admin, skill_manager |
| DELETE | `/api/v1/skills/{id}` | Desactivar | admin |
| GET | `/api/v1/skills/{id}/revisions` | Historial versiones | authenticated |
| POST | `/api/v1/skills/{id}/rollback/{version}` | Restaurar versión | admin |
| POST | `/api/v1/skills/{id}/test` | Probar skill | admin, skill_manager |
| GET | `/api/v1/skills/{id}/analytics` | Métricas de uso | admin |
| **POST** | **`/api/v1/skills/resolve`** | **Resolver skills para contexto** | system, agent |

### 5.2 Endpoint de Resolución (Crítico)

```http
POST /api/v1/skills/resolve
Content-Type: application/json

{
  "tenant_id": "bodega_robles",
  "agent_type": "producer_copilot",
  "vertical": "agroconecta",
  "task_type": "create_product",
  "user_query": "Quiero subir mi aceite de oliva",
  "context": {
    "user_id": 12345,
    "product_category": "aceites"
  }
}
```

**Response:**

```json
{
  "resolved_skills": [
    {
      "machine_name": "tone_guidelines",
      "scope": "core",
      "priority": 100,
      "content": "## Voz Sin Humo\n\nUsa un tono cercano..."
    },
    {
      "machine_name": "product_listing_agro",
      "scope": "vertical",
      "priority": 80,
      "content": "## Product Listing AgroConecta\n\n..."
    }
  ],
  "merged_prompt_section": "<skills>\n...\n</skills>",
  "total_tokens": 1847,
  "resolution_time_ms": 45
}
```

---

## 6. G.4 Editor Visual

> **Referencia:** Doc 129 sección 8

### 6.1 Componentes React (Sprint G7-G8: 40-50h)

| Componente | Tecnología | Funcionalidad |
|------------|------------|---------------|
| Skill Browser | React Tree View | Navegar jerarquía Core→Tenant |
| Markdown Editor | Monaco Editor | Syntax highlighting, autocomplete |
| Live Preview | React Markdown | Vista previa en tiempo real |
| Metadata Panel | React Hook Form | task_types, prioridad, dependencias |
| Test Console | Custom React | Probar con input real |
| Version History | React Diff Viewer | Cambios entre versiones |
| Analytics Panel | Recharts | Gráficos uso, efectividad |

### 6.2 Permisos por Plan

| Funcionalidad | Starter | Growth | Pro | Enterprise |
|---------------|---------|--------|-----|------------|
| Ver Skills Core | ✓ | ✓ | ✓ | ✓ |
| Ver Skills Vertical | ✓ | ✓ | ✓ | ✓ |
| Crear Skills Tenant | ✗ | 3 max | 10 max | Ilimitado |
| Editor Visual | ✗ | Básico | Completo | Completo |
| Test Console | ✗ | ✗ | ✓ | ✓ |
| Analytics | ✗ | Básico | Avanzado | Avanzado |
| Import/Export | ✗ | ✗ | ✓ | ✓ |

---

## 7. G.5 Skills Predefinidas

> **Referencia:** Doc 129 sección 5

### 7.1 Skills Core (7 skills) - Sprint G9

| Skill | Propósito | Aplica a |
|-------|-----------|----------|
| `tone_guidelines` | Voz "Sin Humo" Jaraba: cercana, práctica | Todas |
| `gdpr_handling` | Manejo de datos personales | Cualquier interacción con datos |
| `escalation_protocol` | Cuándo escalar a humano | Conversaciones problemáticas |
| `answer_capsule` | Técnica GEO para respuestas citables | Contenido público |
| `accessibility_writing` | WCAG 2.1 AA | Todo el contenido |
| `error_recovery` | Responder cuando algo falla | Errores y excepciones |
| `feedback_collection` | Solicitar feedback sin ser intrusivo | Final de interacciones |

### 7.2 Skills por Vertical (28 skills) - Sprint G10

#### Empleabilidad (7 skills)
- `cv_optimization`, `interview_preparation`, `salary_negotiation`
- `linkedin_optimization`, `cover_letter_writing`
- `job_search_strategy`, `skill_gap_analysis`

#### Emprendimiento (7 skills)
- `canvas_coaching`, `pitch_deck_review`, `financial_projection`
- `competitive_analysis`, `mvp_validation`
- `pricing_strategy`, `go_to_market`

#### AgroConecta (6 skills)
- `product_listing_agro`, `seasonal_marketing`, `traceability_story`
- `quality_certification`, `recipe_content`, `b2b_proposal`

#### ComercioConecta (5 skills)
- `flash_offer_design`, `local_seo_content`, `customer_retention`
- `inventory_alert`, `review_response`

#### ServiciosConecta (5 skills)
- `case_summarization`, `client_communication`, `document_generation`
- `appointment_prep`, `quote_generation`

---

## 8. Checklist Directrices Obligatorias

> ⚠️ **VERIFICAR ANTES DE CADA COMMIT**

### 8.1 Referencias Obligatorias

- 📋 [DIRECTRICES_DESARROLLO.md](../tecnicos/DIRECTRICES_DESARROLLO.md) - Checklist central
- 📁 Workflows `.agent/workflows/`:
  - `/drupal-custom-modules` - Content Entities
  - `/i18n-traducciones` - Internacionalización
  - `/scss-estilos` - Variables inyectables
  - `/ai-integration` - Patrones de IA

### 8.2 Checklist Específico Bloque G

| Área | Verificar |
|------|-----------|
| **Content Entities** | `ai_skill`, `ai_skill_revision`, `ai_skill_usage` con views_data |
| **Handlers** | list_builder, form handlers, views_data |
| **4 YAMLs** | routing, links.menu, links.task, links.action |
| **i18n** | Labels, task_types, scope names traducibles |
| **SCSS** | Editor usa `var(--ej-*)`, dark mode para Monaco |
| **APIs** | OAuth2, X-Tenant-ID header, rate limiting |
| **Testing** | PHPUnit para servicios, Jest para React |

### 8.3 Integración con Pipeline RAG

```
USER MESSAGE
    │
    ▼
1. INTENT ANALYSIS  ← Detectar task_type
    │
    ▼
2. CONTEXT LOAD  ← tenant_id, vertical, agent_type
    │
    ├──────────────────────┐
    ▼                      ▼
3a. RAG RETRIEVAL    3b. SKILL RESOLUTION  ← NUEVO
    │                      │
    └──────────┬───────────┘
               ▼
4. PROMPT ASSEMBLY
    ├── System Prompt
    ├── SKILLS BLOCK  ← <skills>...</skills>
    ├── RAG Context
    └── User Message
               │
               ▼
5. LLM INFERENCE
               │
               ▼
6. SKILL USAGE LOGGING  ← NUEVO
```

---

## 9. Resumen de Inversión

| Sprint | Semanas | Horas | Entregable |
|--------|---------|-------|------------|
| G1-G2 | 1-2 | 40-50h | Entidades base: ai_skill, revisions, usage |
| G3-G4 | 3-4 | 40-50h | SkillManager Service: resolución jerárquica |
| G5-G6 | 5-6 | 30-40h | APIs REST: /resolve endpoint crítico |
| G7-G8 | 7-8 | 40-50h | Editor Visual: Monaco + React |
| G9-G10 | 9-10 | 50-60h | 35 Skills predefinidas (Core + Verticales) |
| **TOTAL** | **10 semanas** | **200-250h** | **Sistema completo** |

---

## 10. Registro de Cambios

| Fecha | Versión | Descripción |
|-------|---------|-------------|
| 2026-01-23 | 1.0.0 | Creación inicial - Documento de implementación Bloque G |

---

**Jaraba Impact Platform | Bloque G: AI Skills System | Enero 2026**
