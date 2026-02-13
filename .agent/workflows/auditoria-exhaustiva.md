---
description: Proceso de auditoría exhaustiva multidisciplinar del SaaS
---

# Auditoría Exhaustiva SaaS

Procedimiento para realizar una auditoría multidisciplinar completa del SaaS.

## Pre-requisitos

- Acceso a `/docs/tecnicos/` para especificaciones
- Acceso a `/web/modules/custom/` para código fuente

## Metodología (15 Disciplinas)

La auditoría debe cubrir 15 perspectivas:

1. **Consultor de Negocio Senior** - Modelo de negocio, Unit Economics, GTM
2. **Analista Financiero Senior** - FOC, métricas SaaS, cash flow
3. **Experto en Producto Senior** - Product-Market Fit, verticales, roadmap
4. **Arquitecto SaaS Senior** - Multi-tenancy, escalabilidad, patrones
5. **Ingeniero de Software Senior** - Código, tests, estándares
6. **Ingeniero UX Senior** - Diseño, accesibilidad, journeys
7. **Ingeniero SEO/GEO Senior** - Schema.org, Answer Capsules, llms.txt
8. **Ingeniero IA Senior** - Agentes, RAG, guardrails, costos
9. **Tech Lead Drupal** - Content Entities, Field UI, SCSS inyectable, i18n
10. **Ingeniero de Seguridad** - HMAC, permisos, sanitización, OWASP
11. **Ingeniero de Rendimiento** - Índices DB, locking, caching, colas async
12. **Ingeniero de Consistencia** - Servicios canónicos, API versioning, patrones
13. **Ingeniero DevOps/CI** - Pipelines, SAST, DAST, contenedores, Trivy
14. **Ingeniero de Testing** - PHPUnit, Cypress, k6, cobertura, tipos test
15. **Ingeniero de Observabilidad** - Telemetría, logging, métricas, alertas

## Pasos de la Auditoría

### 1. Inventario Documental
```bash
# Listar todos los documentos técnicos
Get-ChildItem -Path "docs/tecnicos" -Filter "*.md" | Sort-Object Name
```

### 2. Clasificar por Categoría
- Core Platform (01-07)
- Vertical Empleabilidad (08-24)
- Vertical Emprendimiento (25-45)
- Vertical AgroConecta Commerce (47-61, 80-82)
- Vertical ComercioConecta (62-79)
- Vertical ServiciosConecta (82-99)
- Frontend Architecture (100-104)
- SEPE Teleformación (105-107)
- Platform Features (108-127)
- **AI Content Hub (128)** ⭐
- **AI Skills System (129)** ⭐
- Infrastructure (130-148)
- **Marketing AI Stack Nativo (149-157)** ⭐
- Copiloto v2 (20260121a-*)

### 3. Verificar Implementación
Para cada documento spec, verificar en código:
```bash
# Buscar módulos custom
Get-ChildItem -Path "web/modules/custom" -Directory

# Buscar entidades
Get-ChildItem -Path "web/modules/custom/*/src/Entity" -Filter "*.php" -Recurse

# Buscar servicios
Get-ChildItem -Path "web/modules/custom/*/*.services.yml"
```

> [!CAUTION]
> **REGLA CRÍTICA (lección 2026-02-09):** `grep` y `find` NUNCA son suficientes para afirmar que código NO existe.
> La auditoría v1.0 del Page Builder usó grep y concluyó que 3 funcionalidades no existían. Las 3 SÍ existían.
>
> **Protocolo obligatorio:**
> 1. `grep`/`find` para **localizar** archivos rápido (fase rápida)
> 2. `view_file` para **leer completo** cada archivo relevante (fase exhaustiva)
> 3. Si grep devuelve 0 resultados → **OBLIGATORIO** leer el archivo completo
> 4. Verificar diferentes variaciones del patrón (prefijos, namespaces, encoding)
> 5. Afirmaciones de alto impacto requieren verificación con 2+ métodos independientes
>
> **Referencia:** [2026-02-09_auditoria_v2_falsos_positivos_page_builder.md](docs/tecnicos/aprendizajes/2026-02-09_auditoria_v2_falsos_positivos_page_builder.md)

> [!CAUTION]
> **7 VERIFICACIONES OBLIGATORIAS (lección 2026-02-13):** La Auditoría Integral descubrió patrones críticos recurrentes:
>
> 1. **Índices DB**: Verificar que TODA Content Entity tiene `->addIndex()` en `baseFieldDefinitions()` para tenant_id + campos frecuentes
> 2. **LockBackendInterface**: Verificar que TODA operación financiera (Stripe, créditos, facturación) adquiere lock exclusivo
> 3. **AccessControlHandler**: Verificar que TODA Content Entity declara `access` handler en anotación `@ContentEntityType`
> 4. **Servicios duplicados**: Verificar que NO existen servicios con la misma responsabilidad en múltiples módulos (ej: TenantContextService duplicado)
> 5. **tenant_id consistente**: Verificar que tenant_id es `entity_reference` (no `integer`) en TODAS las entidades
> 6. **`|raw` en Twig**: Verificar que TODO uso de `|raw` tiene sanitización server-side previa (`Xss::filterAdmin()`)
> 7. **`_user_is_logged_in` vs `_permission`**: Verificar que rutas sensibles usan `_permission` con permiso granular
>
> **Referencia:** [Auditoría Integral Estado SaaS v1](docs/tecnicos/auditorias/20260213-Auditoria_Integral_Estado_SaaS_v1_Claude.md)

### 4. Calcular Conformidad
- Listar specs implementadas / total specs
- Identificar gaps por categoría
- Estimar esfuerzo pendiente (horas)

### 5. Generar Informe

Crear documento en `docs/tecnicos/` con formato:
```
YYYYMMDD-Auditoria_Exhaustiva_Multidisciplinar_vX_Claude.md
```

Incluir:
- Matriz de conformidad por disciplina
- Gaps críticos identificados
- Roadmap de remediación priorizado
- Estimación de esfuerzo

### 6. Crear Plan de Implementación

Crear documento de plan:
```
YYYYMMDD-Plan_Implementacion_Gaps_Auditoria_vX_Claude.md
```

### 7. Actualizar Plan Maestro

Si hay gaps críticos, actualizar el Plan Maestro Unificado:
```
docs/planificacion/YYYYMMDD-Plan_Maestro_Unificado_SaaS_vX_Claude.md
```

### 8. Documentar Aprendizajes

Registrar lecciones aprendidas:
```
docs/tecnicos/aprendizajes/YYYY-MM-DD_auditoria_exhaustiva_gaps_resueltos.md
```

## Documentos de Referencia

### Auditoría Integral (2026-02-13 — Más Reciente) ⭐
- [Auditoría Integral Estado SaaS v1](docs/tecnicos/auditorias/20260213-Auditoria_Integral_Estado_SaaS_v1_Claude.md) - 65 hallazgos (7 Críticos), 15 disciplinas, 62 módulos
- [Plan Remediación Auditoría Integral v1](docs/implementacion/20260213-Plan_Remediacion_Auditoria_Integral_v1.md) - 3 fases, ~250-350h
- [Aprendizajes Auditoría Integral](docs/tecnicos/aprendizajes/2026-02-13_auditoria_integral_estado_saas.md) - 11 reglas AUDIT-*
- [Directrices v20.0.0](docs/00_DIRECTRICES_PROYECTO.md) - Secciones 4.7 y 5.8.3 con reglas AUDIT-*

### Auditoría v2.1 (2026-02-09) ⭐
- [Plan v2.1 (Corrección Falsos Positivos)](docs/planificacion/20260209-Plan_Elevacion_Page_Site_Builder_v2.md) - Score 10/10, 3 falsos positivos corregidos
- [Aprendizajes v2.1](docs/tecnicos/aprendizajes/2026-02-09_auditoria_v2_falsos_positivos_page_builder.md) - Regla "nunca confiar solo en grep"
- [Arquitectura v1.2.0](docs/arquitectura/2026-02-08_plan_elevacion_page_builder_clase_mundial.md) - Gaps G1/G2/G7 marcados como falsos positivos

### Auditoría 2026-01-28 ⭐
- [Auditoría Page Builder Clase Mundial](./docs/arquitectura/2026-01-28_auditoria_page_builder_clase_mundial.md) - 7.5/10, 550-720h pendientes
- [Aprendizajes Auditoría Page Builder](./docs/tecnicos/aprendizajes/2026-01-28_page_builder_auditoria_clase_mundial.md)
- [Plan Constructor Páginas v1](./docs/planificacion/20260126-Plan_Constructor_Paginas_SaaS_v1.md) - Actualizado con estado

### Auditoría 2026-01-23
- [Plan Maestro Unificado v3.0](./docs/planificacion/20260123-Plan_Maestro_Unificado_SaaS_v3_Claude.md) - 7 bloques, ~4,500h, 24 meses
- [Bloque G: AI Skills System](./docs/implementacion/20260123g-Bloque_G_AI_Skills_Implementacion_Claude.md) - Nuevo, 200-250h
- [Aprendizajes Auditoría](./docs/tecnicos/aprendizajes/2026-01-23_auditoria_exhaustiva_gaps_resueltos.md)

### Auditorías Anteriores
- [Auditoría 2026-01-22](./docs/tecnicos/20260122a-Auditoria_Exhaustiva_Multidisciplinar_v1_Claude.md)
- [Plan Implementación Gaps](./docs/tecnicos/20260122b-Plan_Implementacion_Gaps_Auditoria_v1_Claude.md)

## Umbrales de Conformidad

| Nivel | Conformidad | Acción |
|-------|-------------|--------|
| ✅ Excelente | >90% | Mantenimiento |
| ⚠️ Aceptable | 60-90% | Mejora continua |
| ❌ Crítico | <60% | Plan de remediación urgente |

## Gaps Críticos Típicos a Verificar

| Gap ID | Área | Verificación |
|--------|------|--------------|
| G1 | AI Content Hub | ¿Doc 128 en Plan Maestro? |
| G2 | AI Skills System | ¿Doc 129 en implementación? |
| G3 | Marketing Stack | ¿Docs 149-157 consolidados? |
| G4 | Dependencies obsoletas | ¿ActiveCampaign → jaraba_email? |
| G5 | Page Builder Schema.org | ¿Schemas por vertical implementados? |
| G6 | Site Builder Extensions | ¿Docs 176-179 iniciados? |
| G7 | WCAG 2.1 AA | ¿Blockes cumplen accesibilidad? |
| G8 | Índices DB Content Entities | ¿268 entidades tienen `->addIndex()` en `baseFieldDefinitions()`? |
| G9 | AccessControlHandler | ¿Todas las Content Entities declaran `access` handler? |
| G10 | Servicios duplicados | ¿TenantContextService, ImpactCreditService sin duplicados? |
| G11 | tenant_id entity_reference | ¿Todas las entidades usan entity_reference (no integer)? |
| G12 | API versioning | ¿Todas las rutas API con prefijo /api/v1/? |

## Frecuencia Recomendada

- **Auditoría completa**: Trimestral
- **Revisión de gaps**: Mensual
- **Verificación de progreso**: Semanal
- **Actualización Plan Maestro**: Por auditoría
