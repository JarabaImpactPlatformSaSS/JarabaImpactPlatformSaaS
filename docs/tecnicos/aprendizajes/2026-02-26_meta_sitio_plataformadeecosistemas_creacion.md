# Aprendizaje #129: Meta-Sitio plataformadeecosistemas.es — Creacion Completa

**Fecha:** 2026-02-26
**Sesion:** Creacion del tercer meta-sitio corporativo PED S.L.
**Contexto:** Hub corporativo/legal para due diligence, reguladores, inversores y prensa

---

## Resumen

Creacion completa del meta-sitio **plataformadeecosistemas.es** con 13 paginas, navegacion y configuracion. Tercer meta-sitio del ecosistema tras pepejaraba.com (marca personal) y jarabaimpact.com (institucional B2B). Este sitio cumple funcion corporativa/legal (LSSI-CE, due diligence, transparencia).

### IDs Resultantes

| Entidad | ID | Detalles |
|---------|-----|----------|
| Tenant | 7 | "Plataforma de Ecosistemas Digitales" |
| Group | 7 | Auto-provisionado por Tenant.postSave() |
| SiteConfig | 3 | header_type='classic', footer_columns=4 |
| PageContent | 78-90 | 13 paginas (Homepage=78, Contacto=79, etc.) |
| SitePageTree | 20-32 | 13 nodos (7 nav + 3 footer + 3 internas) |

### Design Tokens

| Token | Valor |
|-------|-------|
| Primary | #1B4F72 |
| Secondary | #17A589 |
| Accent | #E67E22 |
| CSS Prefix | `ped-` |
| Gradient | `linear-gradient(135deg, #1B4F72 0%, #17A589 100%)` |
| Font Display | Montserrat |
| Font Body | Inter |

---

## Hallazgo 1: Tenant Domain Requiere FQDN

### Problema
Al crear el Tenant via UI con domain="ped", la validacion del formulario rechaza con "El dominio no tiene un formato valido".

### Root Cause
El campo `domain` en `Tenant.php` usa `UniqueField` constraint y el formulario valida que contenga al menos un punto (`.`). La funcion `provisionDomainIfNeeded()` puede expandir subdominios, pero la validacion del form se ejecuta ANTES.

### Solucion
Usar FQDN completo: `plataformadeecosistemas.es` en lugar de `ped`.

### Impacto en dev
El subdomain prefix para Lando debe coincidir con el inicio del dominio almacenado. Como el dominio es `plataformadeecosistemas.es`, el subdomain dev es `plataformadeecosistemas.jaraba-saas.lndo.site` (largo pero funcional).

### Regla
**TENANT-DOMAIN-FQDN-001:** El campo domain del Tenant SIEMPRE debe ser un FQDN con punto (ej: `midominio.es`), nunca un prefijo corto. El subdomain de desarrollo derivara del primer segmento.

---

## Hallazgo 2: SitePageTree Field Names vs Entity API

### Problema
Al crear SitePageTree entries via PHP script con `$sptStorage->create([...])`, los campos `group_id`, `page_content_id` y `title` resultaron en valores NULL en la BD.

### Root Cause
Los nombres de campo en la entidad SitePageTree NO coinciden con lo que uno esperaria:

| Campo esperado | Campo real en entity | Target |
|---------------|---------------------|--------|
| `group_id` | `tenant_id` | entity_reference -> group |
| `page_content_id` | `page_id` | entity_reference -> page_content |
| `title` | `nav_title` | string (100 chars) |

La Drupal Entity API acepta campos desconocidos silenciosamente sin error, simplemente los ignora. No hay validacion de required en create(), solo en save() form mode.

### Solucion
Correccion via SQL UPDATE directo para los 13 registros ya creados.

### Regla
**SPT-FIELD-NAMES-001:** Al crear SitePageTree via API, los campos correctos son: `tenant_id` (group ref), `page_id` (page_content ref), `nav_title` (string). Verificar SIEMPRE con `DESCRIBE table` antes de scripts batch.

### Regla de Oro
**Regla de oro #48: Antes de crear entidades via API, verificar los nombres exactos de campo con DESCRIBE table o leyendo baseFieldDefinitions(). Drupal ignora campos desconocidos silenciosamente.**

---

## Hallazgo 3: Lando Proxy Requiere Registro Explicito

### Problema
Navegar a `ped.jaraba-saas.lndo.site` retornaba error SSL/conexion. El subdominio no existia en el proxy Lando.

### Root Cause
Lando `.lando.yml` proxy section debe listar explicitamente cada subdominio. No hay wildcard support por defecto.

### Solucion
Agregar entrada en `.lando.yml` proxy section:
```yaml
proxy:
  appserver:
    - plataformadeecosistemas.jaraba-saas.lndo.site
```
Seguido de `lando start` para registrar el nuevo proxy.

### Regla
**LANDO-PROXY-001:** Cada meta-sitio nuevo requiere su propia entrada en `.lando.yml` proxy section. Sin ella, el subdominio no resuelve.

---

## Hallazgo 4: Creacion Batch Eficiente via PHP Scripts

### Patron Validado
La creacion de 13 paginas + navegacion via PHP scripts ejecutados con `lando drush php-script` es dramaticamente mas eficiente que el browser UI:

1. **Homepage**: Script individual que lee HTML de archivo y crea PageContent
2. **12 paginas restantes**: Script batch con funcion helper `createPedPage()`
3. **Navegacion + special pages**: Script que crea SitePageTree + actualiza SiteConfig

### Ventajas
- Reproducible (scripts versionables)
- 10x mas rapido que UI browser
- Menos errores de interaccion
- Canvas_data JSON generado correctamente

### Flujo recomendado para futuros meta-sitios
1. Generar HTML de homepage en archivo separado
2. PHP script batch para todas las paginas
3. PHP script para SitePageTree + SiteConfig updates
4. Verificar con `DESCRIBE table` los campos correctos
5. Lando proxy + cache clear + browser verification

---

## Hallazgo 5: SiteConfig Admin UI Rota

### Problema
Navegar a `/admin/content/site-configs` produce PHP exception: `SiteConfigListBuilder` class not found.

### Workaround
Crear/editar SiteConfig directamente via SQL INSERT/UPDATE en lugar de UI.

### Nota
Este bug ya existia en sesiones anteriores. Se mantiene el workaround SQL.

---

## Arquitectura de Paginas Creada

### Navegacion Principal (7 items)

| Nav Title | Path | Page ID | Weight |
|-----------|------|---------|--------|
| Inicio | /inicio | 78 | 0 |
| Empresa | /sobre-nosotros | 83 | 1 |
| Ecosistema | /ecosistema | 84 | 2 |
| Impacto | /impacto | 85 | 3 |
| Partners | /partners | 86 | 4 |
| Prensa | /prensa | 90 | 5 |
| Contacto | /contacto | 79 | 6 |

### Footer Legal (3 items)

| Nav Title | Path | Page ID | Weight |
|-----------|------|---------|--------|
| Aviso Legal | /aviso-legal | 80 | 10 |
| Politica de Privacidad | /politica-privacidad | 81 | 11 |
| Politica de Cookies | /politica-cookies | 82 | 12 |

### Paginas Internas (3 items, sin nav ni footer)

| Nav Title | Path | Page ID | Weight |
|-----------|------|---------|--------|
| Equipo Directivo | /equipo | 87 | 20 |
| Transparencia | /transparencia | 88 | 21 |
| Certificaciones | /certificaciones | 89 | 22 |

---

## Archivos Modificados/Creados

| Archivo | Accion |
|---------|--------|
| `.lando.yml` | Agregada entrada proxy `plataformadeecosistemas.jaraba-saas.lndo.site` |
| `web/modules/custom/jaraba_page_builder/content/ped-homepage.html` | Creado: HTML homepage con 5 secciones |
| `web/create_ped_pages.php` | Creado: Script creacion homepage |
| `web/create_ped_all_pages.php` | Creado: Script batch 12 paginas |
| `web/create_ped_navigation.php` | Creado: Script SitePageTree + SiteConfig |

---

## Reglas Nuevas

| Codigo | Prioridad | Descripcion |
|--------|-----------|-------------|
| TENANT-DOMAIN-FQDN-001 | P1 | Domain del Tenant debe ser FQDN con punto |
| SPT-FIELD-NAMES-001 | P0 | SitePageTree usa tenant_id, page_id, nav_title |
| LANDO-PROXY-001 | P1 | Cada meta-sitio necesita entrada proxy en .lando.yml |

## Reglas de Oro

| # | Regla |
|---|-------|
| 48 | Antes de crear entidades via API, verificar nombres de campo con DESCRIBE table. Drupal ignora campos desconocidos silenciosamente. |
