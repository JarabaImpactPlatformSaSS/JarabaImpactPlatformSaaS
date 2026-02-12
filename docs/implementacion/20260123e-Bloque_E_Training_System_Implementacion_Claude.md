# Bloque E: Training & Certification - Documento de Implementación
## Escalera de Valor + Certificación Método Jaraba™

**Fecha de creación:** 2026-01-23  
**Versión:** 1.0.0

---

## 📑 Tabla de Contenidos (TOC)

1. [Matriz de Especificaciones](#1-matriz-de-especificaciones)
2. [Checklist Multidisciplinar](#2-checklist-multidisciplinar)
3. [Pasos de Implementación](#3-pasos-de-implementación)
4. [Checklist Directrices](#4-checklist-directrices)
   - [4.1 Referencias Obligatorias](#41-referencias-obligatorias)
   - [4.2 Checklist Específico](#42-checklist-específico-bloque-e)
5. [Registro de Cambios](#5-registro-de-cambios)

---

## 1. Matriz de Especificaciones

| Doc | Archivo |
|-----|---------|
| 46 | [20260115j-46_Training_Certification_System_v1_Claude.md](../tecnicos/20260115j-46_Training_Certification_System_v1_Claude.md) |
| LMS | [20260115g-08_Empleabilidad_LMS_Core_v1_Claude.md](../tecnicos/20260115g-08_Empleabilidad_LMS_Core_v1_Claude.md) |

---

## 2. Checklist Multidisciplinar

### Negocio
- [ ] ¿Escalera de valor 6 peldaños?
- [ ] ¿MRR de Club Jaraba trackeable?
- [ ] ¿Royalties de certificados calculados?

### Finanzas
- [ ] ¿Stripe Connect para mentorías?
- [ ] ¿Revenue deferred para suscripciones?

---

## 3. Pasos de Implementación

### Sprint E1-E2: Training Product (24h)

#### Entity training_product
| Campo | Tipo | Descripción |
|-------|------|-------------|
| title | string | Nombre producto |
| product_type | enum | lead_magnet, microcourse, membership... |
| ladder_level | int | 0-5 |
| price | decimal | Precio base |
| billing_type | enum | free, one_time, recurring, cohort |
| next_product_id | FK | Siguiente en escalera |

- [ ] Crear entity (Content Entity)
- [ ] APIs REST CRUD
- [ ] Integración Stripe

### Sprint E3-E4: Certification Program (24h)

#### Entity certification_program
| Campo | Tipo |
|-------|------|
| certification_type | enum: consultant, entity, regional_franchise |
| entry_fee | decimal |
| annual_fee | decimal |
| royalty_percent | decimal |
| required_courses | JSON array |
| exam_required | boolean |
| minimum_score | int |

- [ ] Crear entity
- [ ] Flujo inscripción

### Sprint E5-E6: Exámenes + Badges (24h)

- [ ] Sistema exámenes (H5P o custom)
- [ ] Open Badge 3.0 emission
- [ ] Verificación pública `/verify/{uuid}`

### Sprint E7-E8: ECA Flows (20h)

- [ ] ECA-TRAIN-001: Upsell post-compra
- [ ] ECA-TRAIN-002: Propuesta certificación
- [ ] ECA-TRAIN-003: Emisión badge
- [ ] ECA-TRAIN-004: Tracking royalties

### Sprint E9-E10: Dashboard (16h)

- [ ] Dashboard certificados
- [ ] Directorio público consultores
- [ ] Métricas conversión escalera

### Sprint E11-E12: Territorios (16h)

- [ ] Sistema territorios exclusivos
- [ ] Mapa franquicias
- [ ] Royalties por zona

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

### 4.2 Checklist Específico Bloque E

| Área | Verificar |
|------|-----------|
| **Entities** | `training_product`, `certification_program`, `user_certification` |
| **SCSS** | Badges con paleta marca, iconos duotone |
| **i18n** | Niveles escalera, tipos certificación traducibles |
| **SDC** | component.yml + twig + scss |

---

## 5. Registro de Cambios

| Fecha | Versión | Descripción |
|-------|---------|-------------|
| 2026-01-23 | 1.0.0 | Creación inicial |
| 2026-01-23 | 1.1.0 | Expandida sección 4 - Directrices Obligatorias |

