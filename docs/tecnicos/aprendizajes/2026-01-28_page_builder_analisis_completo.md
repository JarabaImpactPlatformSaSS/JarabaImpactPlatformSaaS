# Page Builder + Site Builder: Análisis Arquitectónico Completo

> **Fecha**: 2026-01-28  
> **Sesión**: Análisis exhaustivo Docs 160-179  
> **Resultado**: Estimación total **775-970h** (€62k-€77k)

---

## Resumen Ejecutivo

Análisis completo del ecosistema Constructor de Páginas SaaS, incluyendo:
- **Core Page Builder** (Docs 160-165): 420-520h
- **Site Builder Extensions** (Docs 176-179): 200-250h
- **Gap Documentation** (Docs 166-171): 115-160h

---

## Hallazgos Clave

### 1. Arquitectura de Entidades

El sistema se basa en **3 entidades principales**:

| Entidad | Tipo | Propósito |
|---------|------|-----------|
| `page_template` | Config Entity | 55 plantillas predefinidas |
| `page_content` | Content Entity | Páginas creadas por tenants |
| `block_template` | Content Entity | 67 bloques componentizados |

**Relación jerárquica**:
```
page_template → page_content → site_page_tree → site_config
```

### 2. Site Builder Extensions (Docs 176-179)

Cuatro módulos adicionales que extienden el Page Builder:

| Doc | Sistema | Capacidades Clave |
|-----|---------|-------------------|
| 176 | Site Structure | Árbol drag-drop, URLs jerárquicas, materialized paths |
| 177 | Navigation | 5 tipos header, 5 tipos footer, mega menus |
| 178 | Blog | Posts, categorías jerárquicas, RSS, autores |
| 179 | SEO/IA | Schema.org, hreflang, Core Web Vitals, IA nativa |

### 3. Bloques Premium

Integración de **2 librerías UI premium**:

- **Aceternity UI**: 15 bloques (Spotlight, 3D Cards, Particle Effects)
- **Magic UI**: 12 bloques (Marquee, Globe, Shimmer Borders)

Adaptados a **Design Tokens** para multi-tenancy.

### 4. IA Nativa (Doc 179)

4 flujos de IA integrados:

| Flujo | Input | Output |
|-------|-------|--------|
| Sugerir estructura | vertical, industria | site_tree, menú |
| Generar post | tema, keywords, tono | post + SEO + FAQ |
| Optimizar SEO | page_id | score, issues, fixes |
| Internal linking | post_id | sugerencias con anchors |

---

## Gap Analysis

6 documentos pendientes identificados en Doc 165:

| Prioridad | Doc | Tema | Horas |
|-----------|-----|------|-------|
| P1 | 166 | i18n Multi-idioma | 20-30h |
| P1 | 167 | Analytics Integration | 15-20h |
| P1 | 170 | WCAG 2.1 AA | 20-30h |
| P2 | 168 | A/B Testing | 25-35h |
| P2 | 169 | Versioning/Rollback | 20-25h |
| P2 | 171 | Content Hub Integration | 15-20h |

---

## Roadmap Actualizado

**5 Fases, 14 Sprints, 7 meses**:

| Fase | Sprints | Enfoque | Horas |
|------|---------|---------|-------|
| 1 | 1-2 | Fundamentos (Entidades, UI) | 110-130h |
| 2 | 3-5 | Bloques Base (45) | 170-200h |
| 3 | 6-8 | Premium (22) + RBAC | 140-190h |
| 4 | 9-12 | Site Builder Extensions | 200-250h |
| 5 | 13-14 | Gap Documentation | 115-160h |

---

## Lecciones Aprendidas

### ✅ Buenas Prácticas Identificadas

1. **Entidades separadas por responsabilidad**: `page_template` (config) vs `page_content` (content)
2. **Materialized paths**: Queries eficientes para jerarquías profundas
3. **Design Tokens**: Cascada de variables CSS para multi-tenancy
4. **Schema.org por vertical**: LocalBusiness, BlogPosting, Organization

### ⚠️ Complejidades a Considerar

1. **Hreflang + Sitemap**: Requiere sincronización entre traducciones
2. **IA Token Budgets**: Límites por plan para evitar costos excesivos
3. **Core Web Vitals**: Critical CSS inline + preload hints

---

## Archivos Actualizados

| Archivo | Cambios |
|---------|---------|
| `20260126-Plan_Constructor_Paginas_SaaS_v1.md` | v1.2.0: docs 160-179, 775-970h, fases 4-5 |
| `00_INDICE_GENERAL.md` | v7.7.0: estadísticas, changelog |

---

## Referencias

- Walkthrough completo: `walkthrough.md` (artifacts)
- Plan actualizado: `docs/planificacion/20260126-Plan_Constructor_Paginas_SaaS_v1.md`
- Docs técnicos: `docs/tecnicos/20260126d-160*.md` a `docs/tecnicos/20260127a-179*.md`
