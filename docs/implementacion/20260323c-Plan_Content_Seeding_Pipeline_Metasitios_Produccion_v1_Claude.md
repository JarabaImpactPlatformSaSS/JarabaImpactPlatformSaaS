# Plan de Implementación: Content Seeding Pipeline — Sincronización de Metasitios Local → Producción

**Versión:** 1.0.0
**Fecha:** 2026-03-23
**Autor:** Claude Opus 4.6 (1M context)
**Prioridad:** P0 — Bloqueante para lanzamiento
**Estado:** Pendiente de implementación
**Directriz principal:** CONTENT-SEED-PIPELINE-001 (nueva)
**Reglas de aplicación:** TENANT-001, TENANT-BRIDGE-001, SAFEGUARD-CANVAS-001, ROUTE-LANGPREFIX-001, UPDATE-HOOK-CATCH-001, SECRET-MGMT-001, DEPLOY-MAINTENANCE-SAFETY-001, SEO-METASITE-001, HOMEPAGE-ELEVATION-001

---

## Índice de Navegación (TOC)

1. [Contexto y Causa Raíz](#1-contexto-y-causa-raíz)
2. [Inventario de Contenido Local](#2-inventario-de-contenido-local)
   - 2.1 [PageContent por Metasitio](#21-pagecontent-por-metasitio)
   - 2.2 [SiteConfig (Configuración de Sitio)](#22-siteconfig-configuración-de-sitio)
   - 2.3 [SitePageTree (Navegación)](#23-sitepagetree-navegación)
   - 2.4 [SiteMenu / SiteMenuItem (Menús)](#24-sitemenu--sitemenuitem-menús)
   - 2.5 [Anomalías Detectadas](#25-anomalías-detectadas)
3. [Arquitectura del Content Seeding Pipeline](#3-arquitectura-del-content-seeding-pipeline)
   - 3.1 [Filosofía: Export-First, UUID-Anchored](#31-filosofía-export-first-uuid-anchored)
   - 3.2 [Estructura de Archivos](#32-estructura-de-archivos)
   - 3.3 [Flujo de Datos](#33-flujo-de-datos)
   - 3.4 [Estrategia de IDs vs UUIDs](#34-estrategia-de-ids-vs-uuids)
4. [Fase 1: Script de Exportación (export-metasite-content.php)](#4-fase-1-script-de-exportación)
   - 4.1 [Lógica de Exportación](#41-lógica-de-exportación)
   - 4.2 [Formato JSON de Salida](#42-formato-json-de-salida)
   - 4.3 [Manejo de canvas_data y rendered_html](#43-manejo-de-canvas_data-y-rendered_html)
   - 4.4 [Integridad Referencial en Exportación](#44-integridad-referencial-en-exportación)
5. [Fase 2: Script de Importación (import-metasite-content.php)](#5-fase-2-script-de-importación)
   - 5.1 [Estrategia de Resolución de IDs](#51-estrategia-de-resolución-de-ids)
   - 5.2 [Orden de Importación (Dependencias)](#52-orden-de-importación-dependencias)
   - 5.3 [Idempotencia](#53-idempotencia)
   - 5.4 [Vinculación de SiteConfig](#54-vinculación-de-siteconfig)
   - 5.5 [Generación de rendered_html Faltante](#55-generación-de-rendered_html-faltante)
6. [Fase 3: Validador Post-Importación](#6-fase-3-validador-post-importación)
7. [Fase 4: Integración en Deploy Pipeline](#7-fase-4-integración-en-deploy-pipeline)
8. [Fase 5: Salvaguardas y Rollback](#8-fase-5-salvaguardas-y-rollback)
9. [Verificación "Código Existe vs Usuario Experimenta"](#9-verificación-código-existe-vs-usuario-experimenta)
10. [Setup Wizard + Daily Actions Compliance](#10-setup-wizard--daily-actions-compliance)
11. [Auditoría SEO Post-Migración](#11-auditoría-seo-post-migración)
12. [Conversión Clase Mundial 10/10](#12-conversión-clase-mundial-1010)
13. [Tabla de Correspondencia de Especificaciones Técnicas](#13-tabla-de-correspondencia-de-especificaciones-técnicas)
14. [Checklist de Directrices de Aplicación](#14-checklist-de-directrices-de-aplicación)
15. [Glosario](#15-glosario)

---

## 1. Contexto y Causa Raíz

### El Problema

La base de datos de producción (IONOS, 82.223.204.169) tiene **0 registros** en las tablas de contenido del Page Builder y Site Builder, mientras que el entorno local/desarrollo tiene contenido completo y diferenciado para los 3 metasitios del Ecosistema Jaraba.

### Cadena de Impacto

```
PageContent = 0 en producción
    ↓
SiteConfig.homepage_id = NULL (no hay page a la que apuntar)
    ↓
PathProcessorPageContent.resolveHomepage() → NULL
    ↓
"/" NO se reescribe a "/page/{homepage_id}"
    ↓
Drupal sirve system.site.front = "/node" (homepage genérica)
    ↓
Title = "Impulsa tu ecosistema digital | Jaraba" (variante 'generic')
    ↓
Nav = verticales SaaS genéricas, no diferenciadas por metasitio
    ↓
Meta tags = genéricos → SEO degradado → canibalización de keywords
    ↓
Setup Wizard "CrearPrimeraPaginaStep" → isComplete() = FALSE
```

### Por Qué Ocurre

Las content entities (PageContent, SitePageTree, SiteMenuItem) son **datos de contenido**, no configuración. El deploy pipeline sincroniza:
- ✅ Configuración (`drush config:import` desde config/sync/)
- ✅ Esquema de entidades (`hook_update_N()` + EntityDefinitionUpdateManager)
- ✅ Templates de Page Builder (129 configs en config/install/)
- ❌ **Contenido de plataforma** — nunca se implementó un pipeline para esta capa

### Clasificación de Capas de Datos en SaaS

| Capa | Ejemplo | Sincronización | Estado |
|------|---------|----------------|--------|
| **L1: Config** | Permisos, vistas, entity types | `drush cex/cim` | ✅ Funcional |
| **L2: Content de plataforma** | Homepages, legal, navegación | **Sin pipeline** | ❌ GAP |
| **L3: Content de tenant** | Productos, pedidos, CRM | Creado por usuario | ✅ N/A pre-lanzamiento |

Este plan cierra el gap de la **Capa L2**.

---

## 2. Inventario de Contenido Local

### 2.1 PageContent por Metasitio

#### PepeJaraba (Tenant 5, Group 5) — 8 páginas, modo `canvas`

| ID | UUID | Título | path_alias | canvas_data | rendered_html | Estado |
|----|------|--------|------------|-------------|---------------|--------|
| 57 | 58ce59e4-... | Inicio | /inicio | 33KB | 11KB | ✅ Completo |
| 58 | d39169c5-... | Manifiesto | /manifiesto | 28KB | 4KB | ✅ Completo |
| 59 | ba196e7c-... | Método Jaraba | /metodo | 12KB | 4KB | ✅ Completo |
| 61 | 58a91a4a-... | Blog | /blog | 9KB | 2KB | ✅ Completo |
| 62 | f49129de-... | Contacto | /contacto | 14KB | 6KB | ✅ Completo |
| 63 | 059d8d8e-... | Aviso Legal | /aviso-legal | 10KB | 2KB | ✅ Completo |
| 64 | ce7b24d9-... | Política de Privacidad | /privacidad | 10KB | 2KB | ✅ Completo |
| 65 | 03df5b5f-... | Política de Cookies | /cookies | 9KB | 1KB | ✅ Completo |

#### JarabaImpact (Tenant 6, Group 6) — 12 páginas, modo `legacy`

| ID | UUID | Título | path_alias | canvas_data | rendered_html | Estado |
|----|------|--------|------------|-------------|---------------|--------|
| 73 | 1193c66c-... | Inicio | /inicio | 25KB | 10KB | ✅ Completo |
| 56 | ec97da07-... | Infraestructura Digital... | /jarabaimpact | 89B | 866B | ⚠️ Stub |
| 66 | 13d8738d-... | Plataforma | /plataforma | 19KB | 5KB | ✅ Completo |
| 67 | 4cf291d5-... | Verticales SaaS | /verticales | 8KB | 5KB | ✅ Completo |
| 68 | 3a7a5bc4-... | Impacto | /impacto | 18KB | 4KB | ✅ Completo |
| 69 | 01b091d9-... | Programas Institucionales | /programas | 19KB | 5KB | ✅ Completo |
| 70 | 9e131827-... | Centro de Recursos | /recursos | 89B | 3KB | ⚠️ Stub canvas |
| 71 | a1cec73a-... | Contacto | /contacto-institucional | 17KB | 4KB | ✅ Completo |
| 74 | 59889b2b-... | Certificación de Consultores | /certificacion | 24KB | 9KB | ✅ Completo |
| 75 | a5f291c8-... | Aviso Legal | /aviso-legal-ji | 15KB | 2KB | ✅ Completo |
| 76 | 92734f8b-... | Política de Privacidad | /privacidad-ji | 15KB | 2KB | ✅ Completo |
| 77 | 886c3a7f-... | Política de Cookies | /cookies-ji | 15KB | 1KB | ✅ Completo |

#### PED — Plataforma de Ecosistemas Digitales (Tenant 7, Group 7) — 13 páginas, modo `canvas`

| ID | UUID | Título | path_alias | canvas_data | rendered_html | Estado |
|----|------|--------|------------|-------------|---------------|--------|
| 78 | 9b16bbe4-... | Inicio | /inicio | 25KB | 23KB | ✅ Completo |
| 79 | afaceae8-... | Contacto | /contacto | 5KB | ❌ 0B | ⚠️ Sin render |
| 80 | 4a297f92-... | Aviso Legal | /aviso-legal | 6KB | ❌ 0B | ⚠️ Sin render |
| 81 | 920cdfd3-... | Política de Privacidad | /politica-privacidad | 9KB | ❌ 0B | ⚠️ Sin render |
| 82 | 19e71203-... | Política de Cookies | /politica-cookies | 7KB | ❌ 0B | ⚠️ Sin render |
| 83 | 5f0c3fff-... | Empresa | /sobre-nosotros | 11KB | ❌ 0B | ⚠️ Sin render |
| 84 | 1b5c6da5-... | Ecosistema | /ecosistema | 6KB | ❌ 0B | ⚠️ Sin render |
| 85 | 221aa572-... | Impacto | /impacto | 5KB | ❌ 0B | ⚠️ Sin render |
| 86 | e5bd8e5f-... | Partners | /partners | 5KB | ❌ 0B | ⚠️ Sin render |
| 87 | b3bf1d6c-... | Equipo Directivo | /equipo | 32KB | ❌ 0B | ⚠️ Sin render |
| 88 | ca445843-... | Transparencia | /transparencia | 4KB | ❌ 0B | ⚠️ Sin render |
| 89 | 106e89a3-... | Certificaciones | /certificaciones | 5KB | ❌ 0B | ⚠️ Sin render |
| 90 | b02ff56a-... | Prensa | /prensa | 4KB | ❌ 0B | ⚠️ Sin render |

**HALLAZGO CRÍTICO:** 12 de 13 páginas de PED carecen de `rendered_html`. El ViewBuilder tiene fallback (`canvas_data.html`), pero esto implica JSON decode + sanitización en cada page load. El script de importación DEBE generar `rendered_html` desde `canvas_data.html` antes de importar.

### 2.2 SiteConfig (Configuración de Sitio)

Los 3 SiteConfig **ya existen en producción** (IDs 1, 2, 3 con tenant_ids 5, 6, 7), pero con `homepage_id = NULL`. La importación NO debe recrearlos — solo **actualizar** los campos de referencia.

| SC ID | UUID | Tenant | site_name | homepage_id | privacy | terms | cookies | blog | meta_suffix |
|-------|------|--------|-----------|-------------|---------|-------|---------|------|-------------|
| 1 | c8eeb082-... | 5 | Pepe Jaraba | 57 | 64 | 63 | 65 | 61 | \| Pepe Jaraba |
| 2 | d8e5ab05-... | 6 | Jaraba Impact | 73 | 76 | 75 | 77 | NULL | \| Jaraba Impact |
| 3 | 3e07528c-... | 7 | PED | 78 | 81 | 80 | 82 | NULL | \| PED S.L. |

### 2.3 SitePageTree (Navegación)

33 entradas totales, distribuidas por tenant:

**PepeJaraba (tenant 5) — 9 entradas:**

| SPT ID | UUID | page_id | nav_title | nav | footer | weight |
|--------|------|---------|-----------|-----|--------|--------|
| 2 | 70029c26-... | 57 | Inicio | ✅ | ❌ | 0 |
| 3 | 49918a67-... | 58 | Manifiesto | ✅ | ❌ | 1 |
| 4 | 37c648f3-... | 59 | Método | ✅ | ❌ | 2 |
| 5 | 83f072ff-... | **60** | Casos de éxito | ✅ | ❌ | 3 |
| 6 | c79efdd8-... | 61 | Blog | ✅ | ❌ | 4 |
| 7 | b5a17dd6-... | 62 | Contacto | ✅ | ❌ | 5 |
| 8 | 73dfe39f-... | 63 | Aviso Legal | ❌ | ✅ | 10 |
| 9 | d614a2fd-... | 64 | Privacidad | ❌ | ✅ | 11 |
| 10 | 520c3e96-... | 65 | Cookies | ❌ | ✅ | 12 |

**JarabaImpact (tenant 6) — 9 entradas:**

| SPT ID | UUID | page_id | nav_title | nav | footer | weight |
|--------|------|---------|-----------|-----|--------|--------|
| 11 | fdbd749f-... | 73 | Inicio | ✅ | ❌ | 0 |
| 12 | 2c599cb5-... | 66 | Plataforma | ✅ | ❌ | 1 |
| 13 | 1a1e31ae-... | 74 | Certificación | ✅ | ❌ | 2 |
| 14 | 3535b3e0-... | 68 | Impacto | ✅ | ❌ | 3 |
| 15 | 5c951f47-... | 69 | Programas | ✅ | ❌ | 4 |
| 16 | fa1c980b-... | 71 | Contacto | ✅ | ❌ | 5 |
| 17 | 7b0ae475-... | 75 | Aviso Legal | ❌ | ✅ | 10 |
| 18 | ffb50071-... | 76 | Privacidad | ❌ | ✅ | 11 |
| 19 | b55d01a2-... | 77 | Cookies | ❌ | ✅ | 12 |

**PED (tenant 7) — 14 entradas:**

| SPT ID | UUID | page_id | nav_title | nav | footer | weight |
|--------|------|---------|-----------|-----|--------|--------|
| 20 | d49359ac-... | 78 | Inicio | ✅ | ❌ | 0 |
| 21 | 62ddba1a-... | 83 | Empresa | ✅ | ❌ | 1 |
| 22 | 2ea299ea-... | 84 | Ecosistema | ✅ | ❌ | 2 |
| 23 | 1aa7c464-... | 85 | Impacto | ✅ | ❌ | 3 |
| 30 | b6f08711-... | 87 | Equipo | ✅ | ❌ | 4 |
| 24 | 80af5219-... | 86 | Partners | ✅ | ❌ | 5 |
| 25 | 976148e2-... | 90 | Prensa | ✅ | ❌ | 6 |
| 26 | aa678eb6-... | 79 | Contacto | ✅ | ❌ | 7 |
| 33 | 0eeb1b0e-... | **NULL** | Centro de Ayuda | ❌ | ❌ | 8 |
| 27 | 2ec29328-... | 80 | Aviso Legal | ❌ | ✅ | 10 |
| 28 | 4b365009-... | 81 | Política de Privacidad | ❌ | ✅ | 11 |
| 29 | f6cd8d16-... | 82 | Política de Cookies | ❌ | ✅ | 12 |
| 31 | 61da8654-... | 88 | Transparencia | ❌ | ❌ | 21 |
| 32 | 0f0947ae-... | 89 | Certificaciones | ❌ | ❌ | 22 |

### 2.4 SiteMenu / SiteMenuItem (Menús)

Solo 1 SiteMenu (PepeJaraba, ID 1) con 6 items que apuntan a pages 50-55 (fuera de los 3 metasitios actuales). Los metasitios JarabaImpact y PED usan **SitePageTree** como fuente de navegación, no SiteMenu.

### 2.5 Anomalías Detectadas

| # | Anomalía | Entidad | Impacto | Acción |
|---|----------|---------|---------|--------|
| A1 | SPT ID 5 apunta a page_id 60 (no existe) | SitePageTree | Link roto "Casos de éxito" en PepeJaraba | Excluir de exportación, loguear warning |
| A2 | SPT ID 33 tiene page_id NULL | SitePageTree | "Centro de Ayuda" PED sin página | Exportar como placeholder (nav_external_url futuro) |
| A3 | 12/13 páginas PED sin rendered_html | PageContent | ViewBuilder usa fallback lento | Generar pre-import desde canvas_data.html |
| A4 | 46 páginas con tenant_id NULL | PageContent | Páginas huérfanas/test | NO exportar — no pertenecen a ningún metasitio |
| A5 | SiteMenuItem refs pages 50-55 (no en metasitios) | SiteMenuItem | Menú PepeJaraba legacy | Exportar pero marcar como legacy |
| A6 | Pages 56, 70 (JI) con canvas_data ~89 bytes | PageContent | Stubs mínimos | Exportar tal cual, rendered_html tiene contenido |

---

## 3. Arquitectura del Content Seeding Pipeline

### 3.1 Filosofía: Export-First, UUID-Anchored

El pipeline sigue el principio **"Export de local, Import idempotente en destino"**:

1. **Export**: Serializa todas las content entities de los 3 metasitios a JSON
2. **Import**: Lee JSON y crea/actualiza entities en el destino usando UUID como ancla
3. **Idempotente**: Ejecutar N veces produce el mismo resultado
4. **UUID-Anchored**: Los IDs autoincrementales difieren entre entornos; los UUIDs son estables

**Por qué UUID y no ID:**
- En local, PageContent ID 78 = PED Homepage
- En producción, ID 78 puede ya estar ocupado por otro contenido futuro
- UUID `9b16bbe4-cdf0-4960-98ce-aca79571530d` es globalmente único
- El script busca por UUID; si existe, actualiza. Si no, crea con ese UUID

### 3.2 Estructura de Archivos

```
scripts/
├── content-seed/
│   ├── export-metasite-content.php    ← Exporta entities → JSON
│   ├── import-metasite-content.php    ← Importa JSON → entities
│   ├── validate-content-sync.php      ← Validador post-importación
│   └── data/                          ← Directorio de JSONs exportados
│       ├── metasite-pepejaraba.json
│       ├── metasite-jarabaimpact.json
│       └── metasite-ped.json
└── validation/
    └── validate-content-seed-integrity.php  ← Validador para validate-all.sh
```

### 3.3 Flujo de Datos

```
LOCAL/DESARROLLO                           PRODUCCIÓN
═══════════════                            ══════════

1. Crear/editar contenido via UI
   (Page Builder, Canvas Editor)
        │
        ▼
2. drush php:script scripts/content-seed/export-metasite-content.php
        │
        ▼
3. scripts/content-seed/data/metasite-*.json
   (versionado en git)
        │
        ▼
4. git push → CI → deploy                 ──────────────────►
                                           5. drush php:script
                                              scripts/content-seed/import-metasite-content.php
                                                    │
                                                    ▼
                                           6. PageContent, SitePageTree creados
                                              SiteConfig.homepage_id actualizado
                                                    │
                                                    ▼
                                           7. drush php:script
                                              scripts/content-seed/validate-content-sync.php
                                                    │
                                                    ▼
                                           8. PathProcessor resuelve "/" → /page/{id}
                                              Homepage diferenciada por dominio ✅
```

### 3.4 Estrategia de IDs vs UUIDs

| Campo | Exportación | Importación |
|-------|-------------|-------------|
| `id` | Se exporta como referencia informativa | Se IGNORA — autoincrementado en destino |
| `uuid` | Se exporta como **ancla primaria** | Se usa para buscar entidad existente |
| `tenant_id` (Group ref) | Se exporta como `tenant_group_id` | Se resuelve por Tenant domain → Group ID |
| `homepage_id` (PageContent ref) | Se exporta como `homepage_uuid` | Se resuelve post-import via UUID lookup |
| `page_id` en SPT | Se exporta como `page_uuid` | Se resuelve post-import via UUID lookup |

**Resolución de tenant_id en destino:**
```
Producción tiene Tenant "pepejaraba.com" → group_id puede ser diferente al local.
Script: loadByProperties(['domain' => 'pepejaraba.com']) → tenant → group_id
```

---

## 4. Fase 1: Script de Exportación

### 4.1 Lógica de Exportación

El script `export-metasite-content.php`:

1. Recibe como argumento el/los tenant domains a exportar (o `--all` para los 3)
2. Para cada tenant:
   a. Resuelve Tenant → Group ID
   b. Exporta todos los PageContent con ese tenant_id
   c. Exporta todos los SitePageTree con ese tenant_id
   d. Exporta el SiteConfig con ese tenant_id
   e. Exporta SiteMenu/SiteMenuItem si existen para ese tenant
3. Genera un JSON por metasitio en `scripts/content-seed/data/`
4. Valida integridad referencial antes de escribir

**Ejecución:**
```bash
lando drush php:script scripts/content-seed/export-metasite-content.php -- --all
# o individualmente:
lando drush php:script scripts/content-seed/export-metasite-content.php -- --domain=pepejaraba.com
```

### 4.2 Formato JSON de Salida

```json
{
  "_metadata": {
    "format_version": "1.0.0",
    "exported_at": "2026-03-23T14:30:00+01:00",
    "source_environment": "local",
    "tenant_domain": "plataformadeecosistemas.es",
    "tenant_name": "Plataforma de Ecosistemas Digitales",
    "tenant_id_local": 7,
    "group_id_local": 7,
    "entity_counts": {
      "page_content": 13,
      "site_page_tree": 14,
      "site_config": 1,
      "site_menu": 0,
      "site_menu_item": 0
    }
  },
  "page_content": [
    {
      "uuid": "9b16bbe4-cdf0-4960-98ce-aca79571530d",
      "local_id": 78,
      "title": "Inicio",
      "path_alias": "/inicio",
      "template_id": "multiblock",
      "layout_mode": "canvas",
      "status": true,
      "meta_title": "Plataforma de Ecosistemas Digitales | PED S.L.",
      "meta_description": "Infraestructura digital para transformación...",
      "canvas_data": "{ ... JSON completo del editor GrapesJS ... }",
      "rendered_html": "<div class='hero'>...</div>",
      "sections": "[{...}]",
      "langcode": "es"
    }
  ],
  "site_page_tree": [
    {
      "uuid": "d49359ac-f483-45f5-af3e-968515722a77",
      "local_id": 20,
      "page_uuid": "9b16bbe4-cdf0-4960-98ce-aca79571530d",
      "page_local_id": 78,
      "parent_uuid": null,
      "nav_title": "Inicio",
      "weight": 0,
      "depth": 0,
      "show_in_navigation": true,
      "show_in_footer": false,
      "show_in_sitemap": true,
      "show_in_breadcrumbs": true,
      "nav_icon": null,
      "nav_highlight": false,
      "nav_external_url": null,
      "status": "published"
    }
  ],
  "site_config": {
    "uuid": "3e07528c-12ff-11f1-b20b-32093e731e9a",
    "local_id": 3,
    "site_name": "Plataforma de Ecosistemas Digitales",
    "homepage_uuid": "9b16bbe4-cdf0-4960-98ce-aca79571530d",
    "blog_index_uuid": null,
    "privacy_policy_uuid": "920cdfd3-d689-4828-82d2-17544fb182cb",
    "terms_conditions_uuid": "4a297f92-f4e2-486c-90b2-b8e63112e126",
    "cookies_policy_uuid": "19e71203-f4e2-4cc2-8b9e-2b22705b027e",
    "meta_title_suffix": "| PED S.L.",
    "header_type": "classic",
    "footer_type": "standard",
    "footer_columns": 4,
    "footer_copyright": "© {year} Plataforma de Ecosistemas Digitales S.L. — CIF: B93750271 — Calle Héroe de Sostoa 12, 29002 Málaga",
    "footer_col1_title": "Plataforma de Ecosistemas Digitales",
    "footer_col2_title": "Empresa",
    "footer_col3_title": "Legal",
    "footer_show_social": true,
    "footer_show_newsletter": false,
    "contact_email": "info@plataformadeecosistemas.es",
    "contact_phone": "+34 623 174 304",
    "header_cta_text": "Contacto",
    "header_cta_url": "/contacto",
    "header_sticky": true,
    "header_transparent": false,
    "ecosystem_footer_enabled": true,
    "social_links": "[...]",
    "ecosystem_footer_links": "[...]"
  }
}
```

### 4.3 Manejo de canvas_data y rendered_html

**Regla crítica:** `canvas_data` contiene el estado completo de GrapesJS (JSON con `components`, `styles`, `html`, `css`). El campo `rendered_html` es una snapshot pre-renderizada para evitar JSON decode en cada page load.

**Pipeline de renderizado:**
```
canvas_data (JSON string)
    → json_decode()
    → $data['html'] (HTML crudo del editor)
    → sanitizePageBuilderHtml() (whitelist de tags seguros)
    → rendered_html (campo en DB)
```

**En exportación:**
- Si `rendered_html` tiene contenido → exportar tal cual
- Si `rendered_html` está vacío pero `canvas_data` tiene `html` → **generar durante export** extrayendo `canvas_data.html` y aplicando sanitización básica
- Si ambos vacíos → exportar vacíos (la página se renderizará vacía)

**IMPORTANTE:** Las 12 páginas de PED sin `rendered_html` necesitan este procesamiento. El export script debe:
1. Decodificar `canvas_data` JSON
2. Extraer el campo `html`
3. Guardar como `rendered_html` en el JSON de exportación
4. Opcionalmente, actualizar la entidad local (--fix-local flag)

### 4.4 Integridad Referencial en Exportación

Antes de escribir el JSON, el script verifica:

| Verificación | Acción si falla |
|-------------|-----------------|
| SiteConfig.homepage_id apunta a PageContent exportado | ERROR — abortar |
| SiteConfig.privacy_policy_id apunta a PC exportado | ERROR — abortar |
| SitePageTree.page_id apunta a PC exportado | WARNING si NULL (placeholder), ERROR si apunta a ID inexistente |
| SitePageTree.parent_id apunta a SPT exportado | WARNING — exportar sin parent |
| SiteMenuItem.page_id apunta a PC exportado | WARNING — exportar con page_uuid null |

---

## 5. Fase 2: Script de Importación

### 5.1 Estrategia de Resolución de IDs

El script `import-metasite-content.php` opera en 4 fases con resolución progresiva de IDs:

```
Fase A: Resolver tenant_id en destino
  ├─ Input: tenant_domain del JSON metadata
  ├─ Query: Tenant::loadByProperties(['domain' => $domain])
  ├─ Output: $targetGroupId (Group ID en producción)
  └─ ABORT si tenant no existe en destino

Fase B: Crear/actualizar PageContent (sin refs cruzadas)
  ├─ Para cada page en JSON:
  │   ├─ Buscar por UUID: loadByProperties(['uuid' => $uuid])
  │   ├─ Si existe → actualizar campos (title, canvas_data, rendered_html, etc.)
  │   ├─ Si no existe → crear con UUID forzado, tenant_id = $targetGroupId
  │   └─ Guardar mapping: uuid → nuevo ID en destino
  └─ Output: $pageUuidToIdMap = ['uuid-xxx' => 42, ...]

Fase C: Crear/actualizar SitePageTree (con refs a PageContent)
  ├─ Para cada SPT en JSON:
  │   ├─ Resolver page_uuid → page_id via $pageUuidToIdMap
  │   ├─ Resolver parent_uuid → parent_id via $sptUuidToIdMap (orden topológico)
  │   ├─ Buscar por UUID existente
  │   ├─ Crear/actualizar con IDs resueltos
  │   └─ Guardar mapping: uuid → nuevo ID
  └─ Output: $sptUuidToIdMap

Fase D: Actualizar SiteConfig (con refs a PageContent)
  ├─ Buscar SiteConfig existente por tenant_id = $targetGroupId
  ├─ Resolver homepage_uuid → homepage_id via $pageUuidToIdMap
  ├─ Resolver privacy_policy_uuid → id via map
  ├─ Resolver terms_conditions_uuid → id via map
  ├─ Resolver cookies_policy_uuid → id via map
  ├─ Actualizar campos de configuración (header, footer, meta, contact)
  └─ Guardar SiteConfig
```

### 5.2 Orden de Importación (Dependencias)

```
1. PageContent        ← Sin dependencias externas (solo tenant_id)
2. SitePageTree       ← Depende de PageContent (page_id)
3. SiteMenu           ← Sin dependencias (solo tenant_id)
4. SiteMenuItem       ← Depende de SiteMenu (menu_id) + PageContent (page_id)
5. SiteConfig UPDATE  ← Depende de PageContent (homepage_id, privacy_id, etc.)
```

**NUNCA importar en paralelo** — el orden es estricto por dependencias de foreign keys.

### 5.3 Idempotencia

Cada entidad se busca por UUID antes de crear:

```php
$existing = $storage->loadByProperties(['uuid' => $data['uuid']]);
if (!empty($existing)) {
  $entity = reset($existing);
  // Actualizar campos
  $entity->set('title', $data['title']);
  // ... etc
}
else {
  $entity = $storage->create([
    'uuid' => $data['uuid'],
    'title' => $data['title'],
    // ... etc
  ]);
}
$entity->save();
```

**Regla:** En actualización, `canvas_data` y `rendered_html` SOLO se sobreescriben si el JSON tiene contenido no vacío. Esto previene borrado accidental si se ejecuta un export parcial.

### 5.4 Vinculación de SiteConfig

Los SiteConfig **ya existen en producción** (creados por el sistema cuando se provisionaron los tenants). La importación:

1. **NO crea** nuevos SiteConfig
2. **Actualiza** los campos de referencia (homepage_id, privacy_policy_id, etc.)
3. **Actualiza** los campos de configuración (header, footer, meta, contact, social)
4. **Preserva** campos que no están en el JSON (ej: google_analytics_id configurado en producción)

### 5.5 Generación de rendered_html Faltante

Para las 12 páginas de PED sin rendered_html, el import script incluye un paso de generación:

```php
if (empty($data['rendered_html']) && !empty($data['canvas_data'])) {
  $canvasData = json_decode($data['canvas_data'], TRUE);
  if (!empty($canvasData['html'])) {
    $data['rendered_html'] = $canvasData['html'];
    // La sanitización la hace el ViewBuilder al servir
  }
}
```

Esto asegura que el campo `rendered_html` esté poblado para un rendering eficiente sin JSON decode en cada request.

---

## 6. Fase 3: Validador Post-Importación

### validate-content-sync.php

Script de validación que ejecuta 15 checks post-importación:

| # | Check | Descripción | Tipo |
|---|-------|-------------|------|
| 1 | PAGES_EXIST | PageContent por metasitio > 0 | FAIL |
| 2 | HOMEPAGE_LINKED | SiteConfig.homepage_id NOT NULL | FAIL |
| 3 | HOMEPAGE_RESOLVES | PathProcessor "/" → /page/{id} | FAIL |
| 4 | LEGAL_PAGES_LINKED | privacy + terms + cookies NOT NULL | FAIL |
| 5 | SPT_INTEGRITY | Todos los SPT.page_id apuntan a PC existentes | FAIL |
| 6 | RENDERED_HTML_COVERAGE | % de páginas con rendered_html > 0 | WARN si < 80% |
| 7 | PATH_ALIAS_UNIQUE | Sin duplicados de path_alias por tenant | FAIL |
| 8 | TENANT_ISOLATION | Todas las entities pertenecen al tenant correcto | FAIL |
| 9 | CANVAS_DATA_VALID | canvas_data es JSON válido | WARN |
| 10 | NAV_COMPLETENESS | Al menos 3 items show_in_navigation por tenant | WARN |
| 11 | FOOTER_COMPLETENESS | Al menos 2 items show_in_footer por tenant | WARN |
| 12 | META_TITLE_SUFFIX | SiteConfig.meta_title_suffix NOT NULL | WARN |
| 13 | HEADER_CTA | header_cta_text y header_cta_url NOT NULL | WARN |
| 14 | CONTACT_INFO | contact_email y contact_phone NOT NULL | WARN |
| 15 | UUID_CONSISTENCY | UUIDs en destino coinciden con JSON source | FAIL |

**Integración con validate-all.sh:**
```bash
# En validate-all.sh:
run_check "content-seed-integrity" "php scripts/validation/validate-content-seed-integrity.php"
```

---

## 7. Fase 4: Integración en Deploy Pipeline

### Opción A: Manual (pre-lanzamiento)

Para el lanzamiento inicial, la importación se ejecuta manualmente via SSH:

```bash
ssh -p 2222 jaraba@82.223.204.169
cd /var/www/jaraba
drush php:script scripts/content-seed/import-metasite-content.php -- --all
drush php:script scripts/content-seed/validate-content-sync.php
drush cr
```

### Opción B: Automática (post-lanzamiento, opcional)

Agregar step en deploy.yml activable por variable de entorno:

```yaml
- name: Seed metasite content (if enabled)
  if: env.SEED_CONTENT == 'true'
  run: |
    ssh -p 2222 jaraba@${{ secrets.IONOS_HOST }} "
      cd /var/www/jaraba &&
      drush php:script scripts/content-seed/import-metasite-content.php -- --all &&
      drush php:script scripts/content-seed/validate-content-sync.php &&
      drush cr
    "
```

**IMPORTANTE:** Este step SIEMPRE va DESPUÉS de `drush updatedb` y `drush config:import` (para que las tablas y schemas estén actualizados), y ANTES de `drush cr` final.

### Posición en pipeline:

```
1. Pre-deploy backup
2. Enable maintenance mode
3. git pull
4. composer install
5. drush updatedb          ← Schema actualizado
6. drush config:import     ← Config sincronizada
7. → SEED CONTENT ←       ← Nuevo step (aquí)
8. drush cr                ← Cache limpio con contenido
9. Disable maintenance mode
10. Smoke tests
```

---

## 8. Fase 5: Salvaguardas y Rollback

### Salvaguarda 1: Backup Pre-Seed (CONTENT-SEED-BACKUP-001)

Antes de importar, el script crea un backup parcial de las tablas afectadas:

```bash
# Generado automáticamente por import-metasite-content.php
mysqldump --single-transaction \
  page_content page_content_field_data page_content_revision page_content_field_revision \
  site_page_tree site_config site_config_field_data \
  site_menu site_menu_item \
  > /opt/jaraba/backups/pre_seed/content_pre_seed_$(date +%Y%m%d_%H%M%S).sql
```

### Salvaguarda 2: Dry Run Mode

```bash
drush php:script scripts/content-seed/import-metasite-content.php -- --all --dry-run
```

Modo que simula toda la importación sin escribir en DB. Muestra:
- Entidades que se crearían vs actualizarían
- Resolución de UUIDs → IDs
- Warnings de integridad referencial
- Estimación de tamaño de datos

### Salvaguarda 3: Rollback Script

```bash
# Si algo sale mal, restaurar desde backup parcial:
mysql drupal_jaraba < /opt/jaraba/backups/pre_seed/content_pre_seed_YYYYMMDD_HHMMSS.sql
drush cr
```

### Salvaguarda 4: Versionado de JSONs

Los archivos `scripts/content-seed/data/metasite-*.json` están en git. Cualquier cambio pasa por:
- Code review (el diff muestra exactamente qué contenido cambia)
- CI validation (estructura JSON válida, UUIDs consistentes)
- Rollback via `git revert`

### Salvaguarda 5: SAFEGUARD-CANVAS-001 Compliance

El import script respeta las 3 capas de SAFEGUARD-CANVAS-001:
1. ✅ Backup pre-save (Salvaguarda 1)
2. ✅ Restore via revision history (entities son revisionables)
3. ✅ Presave JSON validation (hook existente en jaraba_page_builder.module)
4. ⚠️ Post-save rendered output verification — **se añade como check 16 en el validador**

### Salvaguarda 6: Validador en validate-all.sh (nueva regla)

**CONTENT-SEED-INTEGRITY-001:** Nuevo validador que verifica en CUALQUIER entorno:
- Si SiteConfig.homepage_id está configurado y apunta a PageContent existente
- Si las páginas legales referenciadas existen
- Si la navegación (SitePageTree) tiene integridad referencial

Esto detecta proactivamente la situación de "producción sin contenido" antes de que un usuario la experimente.

---

## 9. Verificación "Código Existe vs Usuario Experimenta"

### Matriz de Verificación Post-Migración

| # | Capa | Verificación | Herramienta | Pre-migración | Post-migración |
|---|------|-------------|-------------|---------------|----------------|
| 1 | Schema DB | Tablas page_content, site_page_tree existen | `SHOW TABLES` | ✅ Existen (vacías) | ✅ Existen (con datos) |
| 2 | Content | PageContent > 0 por metasitio | validate-content-sync.php | ❌ 0 registros | ✅ 33 registros |
| 3 | Config | SiteConfig.homepage_id NOT NULL | validate-content-sync.php | ❌ NULL | ✅ Vinculado |
| 4 | PathProcessor | "/" → /page/{homepage_id} | curl + check redirect | ❌ Sirve /node | ✅ Sirve homepage |
| 5 | Title | `<title>` contiene nombre del metasitio | curl + grep `<title>` | ❌ Genérico | ✅ Diferenciado |
| 6 | Navigation | Header muestra nav items del metasitio | curl + grep nav links | ❌ Genérica | ✅ Diferenciada |
| 7 | Footer | Footer con links legales | curl + grep footer | ❌ Genérico | ✅ Con legal |
| 8 | SEO: OG | og:title diferenciado | curl + grep og:title | ❌ Genérico | ✅ Por metasitio |
| 9 | SEO: hreflang | hreflang con URL del metasitio | curl + grep hreflang | ⚠️ Parcial | ✅ Correcto |
| 10 | Canvas | rendered_html servido | curl + check body size | ❌ Vacío | ✅ Contenido visible |
| 11 | Setup Wizard | CrearPrimeraPaginaStep.isComplete() | Dashboard login | ❌ FALSE | ✅ TRUE |
| 12 | Performance | No JSON decode on page load | New Relic / logs | N/A | ✅ rendered_html directo |

---

## 10. Setup Wizard + Daily Actions Compliance

### Estado Actual en Producción (pre-migración)

| Componente | Vertical | Estado | Causa |
|-----------|----------|--------|-------|
| **ElegirPlantillaStep** | page_builder | ❓ Depende de templates config | Probablemente OK (templates en config/install) |
| **CrearPrimeraPaginaStep** | page_builder | ❌ isComplete=FALSE | 0 PageContent por tenant |
| **PersonalizarContenidoStep** | page_builder | ❌ isComplete=FALSE | Depende de contenido existente |
| **PublicarPaginaStep** | page_builder | ❌ isComplete=FALSE | No hay páginas publicadas |
| **NuevaPaginaAction** (daily) | page_builder | ⚠️ badge=quota_max | Sin contexto de páginas existentes |
| **EditarPaginaPrincipalAction** (daily) | page_builder | ⚠️ Sin homepage | No puede resolver homepage |
| **CrearPaginaGlobalAction** (global daily) | __global__ | ✅ Visible | Solo verifica moduleExists |

### Estado Esperado Post-Migración

| Componente | Estado | Verificación |
|-----------|--------|--------------|
| CrearPrimeraPaginaStep | ✅ isComplete=TRUE | count(page_content WHERE tenant_id) > 0 |
| PersonalizarContenidoStep | ✅ isComplete=TRUE | Páginas con canvas_data no vacío |
| PublicarPaginaStep | ✅ isComplete=TRUE | Páginas con status=1 |
| NuevaPaginaAction | ✅ badge muestra remaining | Quota calculada correctamente |
| EditarPaginaPrincipalAction | ✅ Funcional | Homepage resoluble |

### Zeigarnik Compliance

Post-migración, el wizard de Page Builder mostrará:
- ✅ Cuenta creada (auto-complete, weight -20)
- ✅ Vertical configurado (auto-complete, weight -10)
- ✅ Elegir plantilla (completado)
- ✅ Crear primera página (completado — hay páginas)
- ✅ Personalizar contenido (completado — canvas_data presente)
- ✅ Publicar página (completado — status=1)

**Resultado:** Wizard 100% completo (collapsed) → UX profesional, sin tareas pendientes falsas.

---

## 11. Auditoría SEO Post-Migración

### Checks SEO por Metasitio

| # | Check | PepeJaraba | JarabaImpact | PED |
|---|-------|------------|--------------|-----|
| 1 | `<title>` diferenciado | "Inicio \| Pepe Jaraba" | "Inicio \| Jaraba Impact" | "Inicio \| PED S.L." |
| 2 | `meta[description]` único | ✅ meta_description por page | ✅ | ✅ |
| 3 | `og:title` / `og:description` | ✅ | ✅ | ✅ |
| 4 | `canonical` URL correcta | https://pepejaraba.com/es/ | https://jarabaimpact.com/es/ | https://plataformadeecosistemas.es/es/ |
| 5 | `hreflang` con dominio correcto | ✅ SEO-HREFLANG-FRONT-001 | ✅ | ✅ |
| 6 | robots.txt dinámico con Sitemap | ✅ por hostname | ✅ | ✅ |
| 7 | Schema.org Organization | ✅ (PepeJaraba como Person) | ✅ (Organization) | ✅ (Organization) |
| 8 | Páginas legales vinculadas | 3/3 (privacy, terms, cookies) | 3/3 | 3/3 |
| 9 | Sitemap XML incluye pages | ✅ show_in_sitemap=true | ✅ | ✅ |
| 10 | H1 único por página | Verificar post-import | Verificar | Verificar |

### Validador SEO existente

`validate-seo-multi-domain.php` (10 checks) ya cubre la mayoría. Post-migración debe pasar 10/10.

---

## 12. Conversión Clase Mundial 10/10

### LANDING-CONVERSION-SCORE-001 — Estado por Homepage

| # | Criterio | PepeJaraba HP | JarabaImpact HP | PED HP |
|---|----------|---------------|-----------------|--------|
| 1 | Hero + urgency badge | ✅ canvas | ✅ legacy (rendered_html) | ✅ canvas |
| 2 | Trust badges | Verificar canvas | Verificar | Verificar |
| 3 | Pain points | Verificar | Verificar | Verificar |
| 4 | Steps/método | ✅ (Método Jaraba page) | Verificar | Verificar |
| 5 | Features grid | Verificar | ✅ (Verticales page) | Verificar |
| 6 | Comparison table | Verificar | Verificar | Verificar |
| 7 | Social proof/testimonials | Verificar | Verificar | Verificar |
| 8 | Lead magnet | Verificar | Verificar | Verificar |
| 9 | Pricing tiers | Verificar (enlace externo) | N/A (B2B) | Verificar |
| 10 | FAQ con Schema.org | Verificar | Verificar | Verificar |
| 11 | Final CTA | Verificar | Verificar | Verificar |
| 12 | Sticky CTA | Verificar (JS behavior) | Verificar | Verificar |
| 13 | Reveal animations | Verificar (JS IntersectionObserver) | Verificar | Verificar |
| 14 | Tracking (data-track-*) | Verificar | Verificar | Verificar |
| 15 | Mobile-first responsive | Verificar (44px touch targets) | Verificar | Verificar |

**NOTA IMPORTANTE:** El scoring de conversión 10/10 solo puede verificarse 100% con las homepages renderizadas en navegador. Los datos de canvas_data/rendered_html necesitan inspección visual. El plan incluye un paso de verificación visual post-importación en cada dominio de producción.

### Elementos que Faltan para 10/10 Completo

1. **rendered_html de PED:** 12/13 páginas sin pre-render → Resolver en export
2. **Verificación visual:** No se puede confirmar 10/10 sin ver las páginas renderizadas en los 3 dominios de producción
3. **Tracking:** Confirmar que `data-track-cta` y `data-track-position` están en los CTAs de canvas_data
4. **Sticky CTA JS:** El behavior `landing-sticky-cta.js` necesita los selectores CSS presentes en el HTML renderizado
5. **Reveal animations:** `scroll-animations.js` observa `.reveal-element` — confirmar presencia en canvas HTML

---

## 13. Tabla de Correspondencia de Especificaciones Técnicas

| Especificación | Archivo/Componente | Descripción | Cumplimiento |
|---------------|-------------------|-------------|--------------|
| CONTENT-SEED-PIPELINE-001 (nueva) | scripts/content-seed/*.php | Pipeline de exportación/importación de contenido de plataforma | A implementar |
| CONTENT-SEED-BACKUP-001 (nueva) | import-metasite-content.php | Backup parcial pre-seed de tablas afectadas | A implementar |
| CONTENT-SEED-INTEGRITY-001 (nueva) | validate-content-seed-integrity.php | Validador de integridad en validate-all.sh | A implementar |
| TENANT-001 | Todas las queries | Filtrar por tenant_id | ✅ En export/import |
| TENANT-BRIDGE-001 | Resolución tenant→group | TenantBridgeService o domain lookup | ✅ En import |
| SAFEGUARD-CANVAS-001 | presave hook + backup | 4 capas de protección canvas | ✅ Respetado |
| ROUTE-LANGPREFIX-001 | URLs en JSON | Paths relativos, no absolutos | ✅ path_alias sin /es/ |
| UPDATE-HOOK-CATCH-001 | try-catch en import | \Throwable, no \Exception | ✅ En import |
| SECRET-MGMT-001 | Credenciales SSH/DB | Via GitHub Secrets, no en código | ✅ En deploy |
| DEPLOY-MAINTENANCE-SAFETY-001 | Maintenance mode | if:always() en disable | ✅ Ya existente |
| SEO-METASITE-001 | meta_title_suffix | Diferenciado por metasitio | ✅ En SiteConfig |
| SEO-HREFLANG-FRONT-001 | Homepage URL | Interceptar "/" correctamente | ✅ Post-migración |
| HOMEPAGE-ELEVATION-001 | Homepage content | 4 variantes diferenciadas | ✅ En canvas_data |
| SETUP-WIZARD-DAILY-001 | Wizard steps | isComplete() correcto post-import | ✅ Verificado |
| ZEIGARNIK-PRELOAD-001 | Auto-complete steps | 2 global steps siempre TRUE | ✅ Sin cambios |
| LABEL-NULLSAFE-001 | Entity labels | Null-safe en operaciones | ✅ En import |
| ENTITY-FK-001 | Foreign keys | entity_reference para tenant_id | ✅ Via Entity API |
| ACCESS-RETURN-TYPE-001 | AccessControlHandler | : AccessResultInterface | N/A (sin nuevo handler) |
| PRESAVE-RESILIENCE-001 | Presave hooks | hasService() + try-catch | ✅ Hooks existentes |
| TRANSLATABLE-FIELDDATA-001 | SQL directo | Usar _field_data tables | ✅ En export SQL |
| PHANTOM-ARG-001 | services.yml | Args match constructor | N/A (sin nuevo servicio) |
| OPTIONAL-CROSSMODULE-001 | Cross-module refs | @? en services.yml | N/A (scripts, no servicios) |
| TWIG-URL-RENDER-ARRAY-001 | Templates | url() como render array | N/A (sin nuevos templates) |
| CSS-VAR-ALL-COLORS-001 | Canvas HTML | --ej-* tokens | ✅ En canvas_data existente |
| SCSS-COMPILE-VERIFY-001 | CSS compilado | Timestamp CSS > SCSS | N/A (sin cambios SCSS) |
| NO-HARDCODE-PRICE-001 | Pricing en pages | Via MetaSitePricingService | ✅ En canvas_data existente |
| ICON-CONVENTION-001 | Iconos en canvas | jaraba_icon() o SVG inline | ✅ En canvas_data existente |
| MARKETING-TRUTH-001 | Claims marketing | 14 días trial, no "gratis siempre" | Verificar en canvas_data |
| INNERHTML-XSS-001 | JS injection safety | Drupal.checkPlain() | N/A (sin nuevo JS) |
| CSRF-JS-CACHE-001 | CSRF tokens | /session/token cacheado | N/A (sin nuevo JS) |

---

## 14. Checklist de Directrices de Aplicación

### Pre-Implementación
- [ ] Leer CLAUDE.md completo (directrices actuales)
- [ ] Revisar 00_DIRECTRICES_PROYECTO.md v162
- [ ] Revisar 00_FLUJO_TRABAJO_CLAUDE.md v112
- [ ] Verificar que los 3 metasitios existen como Tenant en producción
- [ ] Verificar que los SiteConfig existen en producción (con homepage_id NULL)
- [ ] Verificar que las tablas page_content, site_page_tree están vacías en producción

### Implementación
- [ ] Script de export usa Entity API de Drupal (NO SQL directo para lectura de entities)
- [ ] Script de import usa Entity API (create/save, NO INSERT directo)
- [ ] try-catch con \Throwable (UPDATE-HOOK-CATCH-001)
- [ ] UUIDs como ancla de idempotencia (no IDs)
- [ ] Resolución de tenant via domain → Tenant → Group (TENANT-BRIDGE-001)
- [ ] canvas_data → rendered_html generado para páginas faltantes
- [ ] Backup pre-seed automático (CONTENT-SEED-BACKUP-001)
- [ ] Dry-run mode funcional
- [ ] JSON exportado versionado en git (scripts/content-seed/data/)
- [ ] Validador post-importación con 15 checks

### Post-Implementación
- [ ] Ejecutar export en local → generar JSONs
- [ ] Revisar JSONs en code review (tamaño, integridad, sin secrets)
- [ ] Dry-run en producción via SSH
- [ ] Import real en producción
- [ ] Ejecutar validate-content-sync.php → 15/15 PASS
- [ ] Verificar homepage en navegador: pepejaraba.com, jarabaimpact.com, plataformadeecosistemas.es
- [ ] Verificar SEO (title, meta, OG, hreflang) en los 3 dominios
- [ ] Verificar Setup Wizard → Page Builder steps completos
- [ ] Verificar navegación (header/footer) diferenciada
- [ ] Ejecutar validate-all.sh → nuevo validator PASS
- [ ] Actualizar CLAUDE.md con nueva directriz CONTENT-SEED-PIPELINE-001
- [ ] Actualizar docs/validators-reference.md con nuevo validador

### Documentación
- [ ] CLAUDE.md: añadir CONTENT-SEED-PIPELINE-001
- [ ] validators-reference.md: añadir validate-content-seed-integrity.php
- [ ] Memory: actualizar con estado de migración

---

## 15. Glosario

| Sigla/Término | Significado |
|--------------|-------------|
| **SPT** | SitePageTree — entidad que organiza páginas en estructura jerárquica de navegación |
| **SC** | SiteConfig — entidad de configuración por tenant (header, footer, homepage, SEO, contacto) |
| **PC** | PageContent — entidad de contenido del Page Builder (canvas o multiblock) |
| **SMI** | SiteMenuItem — elemento individual de menú dentro de un SiteMenu |
| **UUID** | Universally Unique Identifier — identificador estable entre entornos (v4, RFC 4122) |
| **canvas_data** | Campo JSON que almacena el estado completo del editor GrapesJS (components, styles, html, css) |
| **rendered_html** | Campo texto con HTML pre-renderizado del canvas para serving eficiente sin JSON decode |
| **PathProcessor** | Servicio Drupal que intercepta URLs entrantes y las reescribe (ej: "/" → "/page/78") |
| **Metasitio** | Instancia del SaaS con dominio propio, branding diferenciado y contenido independiente |
| **Tenant** | Entidad de facturación/suscripción, vinculada a un Group (aislamiento de contenido) |
| **Group** | Entidad de Drupal Group Module que implementa soft isolation multi-tenant |
| **Content Seeding** | Proceso de poblar una base de datos con contenido inicial/fundacional |
| **Idempotencia** | Propiedad de una operación que produce el mismo resultado ejecutándose N veces |
| **SSOT** | Single Source of Truth — fuente única de verdad para un dato |
| **L2 Content** | Contenido de plataforma (homepages, legal, navegación) — entre config y contenido de usuario |
| **Dry Run** | Modo de ejecución que simula sin escribir en DB |
| **Pipeline E2E** | Verificación end-to-end de todas las capas desde DB hasta DOM renderizado |

---

## Estimación de Alcance

| Componente | Archivos | Complejidad |
|-----------|----------|-------------|
| export-metasite-content.php | 1 | Media (Entity API + JSON serialization) |
| import-metasite-content.php | 1 | Alta (UUID resolution + dependency order + idempotency) |
| validate-content-sync.php | 1 | Media (15 checks) |
| validate-content-seed-integrity.php | 1 | Baja (subset de checks para validate-all.sh) |
| data/metasite-*.json | 3 | Generados por export |
| deploy.yml update | 1 | Baja (1 step condicional) |
| CLAUDE.md update | 1 | Baja (nueva directriz) |
| validators-reference.md update | 1 | Baja (nueva entrada) |

**Total:** 5 scripts nuevos + 3 JSONs generados + 2 docs actualizados

---

*Documento generado como parte del Ecosistema Jaraba Impact Platform — "Sin Humo"*
*Versión del SaaS: DIRECTRICES v162.0.0 | ARQUITECTURA v147.0.0*
