# Bloque B: Copiloto Emprendimiento v3 - Documento de Implementación
## Hiperpersonalización + Metodologías Osterwalder/Blank/Kaufman

**Fecha de creación:** 2026-01-23  
**Versión:** 1.0.0

---

## 📑 Tabla de Contenidos (TOC)

1. [Matriz de Especificaciones](#1-matriz-de-especificaciones)
2. [Checklist Multidisciplinar](#2-checklist-multidisciplinar)
3. [Pasos de Implementación](#3-pasos-de-implementación)
4. [Checklist Directrices](#4-checklist-directrices)
   - [4.1 Referencias Obligatorias](#41-referencias-obligatorias)
   - [4.2 Checklist Específico](#42-checklist-específico-bloque-b)
5. [Registro de Cambios](#5-registro-de-cambios)

---

## 1. Matriz de Especificaciones

### 1.1 Copiloto v2 Existente

| Doc | Archivo | Contenido |
|-----|---------|-----------|
| Prompt v2 | [20260121a-copilot_prompt_master_v2.md](../tecnicos/20260121a-copilot_prompt_master_v2.md) | Prompt maestro 7 modos |
| Specs v2 | [20260121a-Especificaciones_Tecnicas_Copiloto_v2_Claude.md](../tecnicos/20260121a-Especificaciones_Tecnicas_Copiloto_v2_Claude.md) | Arquitectura completa |
| Experimentos | [20260121a-experiment_library_complete.json](../tecnicos/20260121a-experiment_library_complete.json) | 44 experimentos |

### 1.2 Metodologías Osterwalder

| Libro | Aplicación | Nuevo Modo |
|-------|------------|------------|
| Business Model Generation | 10 patrones BMG | Business Pattern Expert |
| Value Proposition Design | VPC Canvas | VPC Designer |
| Testing Business Ideas | Test/Learning Cards | (existente mejorado) |
| Invincible Company | Explore/Exploit | Pivot Advisor |
| Business Model You | Personal Canvas | (integrado en contexto) |

### 1.3 Blank/Dorf + Kaufman

| Fuente | Aplicación |
|--------|------------|
| Startup Owner's Manual | 4 fases Customer Discovery |
| MBA Personal | 12 formas de valor |

---

## 2. Checklist Multidisciplinar

### 2.1 Negocio
- [ ] ¿Mejora conversión de emprendedores?
- [ ] ¿Reduce churn programa?

### 2.2 Producto
- [ ] ¿VPC Canvas integrado con BMC existente?
- [ ] ¿Customer Discovery trackeable?

### 2.3 IA
- [ ] ¿Tokens optimizados por modo?
- [ ] ¿RAG para metodologías?
- [ ] ¿Historial en contexto sin exceder límites?

---

## 3. Pasos de Implementación

### Sprint B1-B2: EntrepreneurContextService (16h)

#### Paso 1: Ampliar contexto BD
```php
// src/Service/EntrepreneurContextService.php
class EntrepreneurContextService {
    public function getFullContext(int $userId): array {
        return [
            'profile' => $this->getProfile($userId),
            'recent_conversations' => $this->getRecentTopics($userId, 10),
            'frequent_questions' => $this->getFrequentPatterns($userId),
            'bmc_status' => $this->getBmcValidationStatus($userId),
            'experiments_completed' => $this->getExperiments($userId),
            'field_exits_count' => $this->getFieldExitsCount($userId),
        ];
    }
}
```

#### Paso 2: Entity field_exit
- [ ] Crear entity `field_exit` (Content Entity)
- [ ] Campos: entrepreneur_id, exit_type, contacts_count, learnings

### Sprint B3-B4: VPC Designer (24h)

- [ ] Crear `ValuePropositionCanvasService`
- [ ] Nuevo modo en prompt: VPC Designer
- [ ] Triggers: "propuesta de valor", "diferencial"

### Sprint B5-B6: Customer Discovery (24h)

- [ ] Crear `CustomerDiscoveryService` con 4 fases
- [ ] Tracker "Sal del Edificio"
- [ ] Modo Customer Discovery Coach

### Sprint B7-B8: Test/Learning Cards (16h)

- [ ] `TestCardGeneratorService`
- [ ] `LearningCardService`
- [ ] Entity `entrepreneur_learning`

### Sprint B9-B10: Patrones + Pivot (16h)

- [ ] 10 patrones BMG en JSON
- [ ] `BusinessPatternDetectorService`
- [ ] `PivotDetectorService`

---

## 4. Checklist Directrices ⚠️

> **VERIFICAR ANTES DE CADA COMMIT**

### 4.1 Referencias Obligatorias

- 📋 [DIRECTRICES_DESARROLLO.md](../tecnicos/DIRECTRICES_DESARROLLO.md) - Checklist central
- 📁 Workflows `.agent/workflows/`:
  - `/scss-estilos` - SCSS y variables inyectables
  - `/i18n-traducciones` - Internacionalización
  - `/sdc-components` - SDC con Compound Variants
  - `/drupal-custom-modules` - Content Entities

### 4.2 Checklist Específico Bloque B

| Área | Verificar |
|------|-----------|
| **SCSS** | Copilot widget usa `var(--ej-*)`, iconos duotone |
| **i18n** | Textos traducibles, modos en español |
| **Entities** | `entrepreneur_learning` con Views |
| **SDC** | component.yml + twig + scss |

---

## 5. Registro de Cambios

| Fecha | Versión | Descripción |
|-------|---------|-------------|
| 2026-01-23 | 1.0.0 | Creación inicial |
| 2026-01-23 | 1.1.0 | Expandida sección 4 - Directrices Obligatorias |

