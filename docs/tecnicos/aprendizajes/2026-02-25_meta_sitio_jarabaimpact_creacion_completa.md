# Aprendizaje #126: Meta-Sitio jarabaimpact.com -- Creacion Completa B2B Institucional

**Fecha:** 2026-02-25
**Modulo:** `jaraba_site_builder`, `jaraba_page_builder`, `ecosistema_jaraba_core`
**Contexto:** Creacion del segundo meta-sitio del ecosistema Jaraba: jarabaimpact.com, el brazo institucional B2B. Diferenciado de pepejaraba.com (marca personal). Incluye 9 paginas, SitePageTree, SiteConfig, Tenant entity, Domain Access y un fix critico en PathProcessorPageContent.

---

## Resumen Ejecutivo

Se creo el meta-sitio **jarabaimpact.com** con arquitectura completa:
- **9 paginas** con contenido HTML/CSS profesional (prefijo `ji-`)
- **SitePageTree** con 6 nodos de navegacion + 3 nodos de footer (legales)
- **SiteConfig** con homepage, paginas legales, social links
- **Tenant entity** vinculando dominio a Group 6
- **Domain Access** para dev (jarabaimpact.jaraba-saas.lndo.site) y prod (jarabaimpact.com)
- **Fix critico** en PathProcessorPageContent para resolucion domain-aware

---

## Arquitectura de Paginas

| # | Titulo | Path | Page ID | Tipo |
|---|--------|------|---------|------|
| 1 | Inicio (Homepage) | /inicio | 73 | Nueva |
| 2 | Certificacion de Consultores | /certificacion | 74 | Nueva |
| 3 | Contacto | /contacto-institucional | 71 | Actualizada |
| 4 | Aviso Legal | /aviso-legal-ji | 75 | Nueva |
| 5 | Politica de Privacidad | /privacidad-ji | 76 | Nueva |
| 6 | Politica de Cookies | /cookies-ji | 77 | Nueva |
| 7 | Plataforma | /plataforma | 66 | Actualizada |
| 8 | Impacto | /impacto | 68 | Actualizada |
| 9 | Programas Institucionales | /programas | 69 | Actualizada |

## Identidad Visual (Design Tokens)

| Token | Valor | CSS Variable |
|-------|-------|-------------|
| Primary | `#1B4F72` | `--ji-primary` |
| Secondary | `#17A589` | `--ji-secondary` |
| Accent | `#E67E22` | `--ji-accent` |
| Dark | `#0E2F4A` | `--ji-dark` |
| Text | `#2D3748` | `--ji-text` |
| Light | `#F0F9FF` | `--ji-light` |
| Font Display | Montserrat 700/800 | `--ji-font-display` |
| Font Body | Inter 400/500 | `--ji-font-body` |
| CSS Prefix | `ji-` | -- |

---

## Problemas Detectados y Soluciones

### P1: PathProcessor no era domain-aware para rutas non-root (CRITICO)

**Problema:** El `PathProcessorPageContent` usaba `TenantContextService` para resolver el tenant actual, pero este servicio resuelve por `admin_user` del Tenant entity, NO por dominio. Para el admin (uid=1) que posee multiples tenants, siempre devolvia el primer Tenant (Aceites del Sur, ID=1), causando que `/inicio` en jarabaimpact resolviese a la homepage de pepejaraba.

**Cadena de fallo:**
1. Browser visita `jarabaimpact.jaraba-saas.lndo.site/inicio`
2. PathProcessor llama `TenantContextService->getCurrentTenantId()` -> devuelve 1 (Aceites)
3. Query `page_content WHERE path_alias=/inicio AND tenant_id=1` -> 0 resultados
4. Cae al fallthrough sin resolver -> Drupal muestra contenido incorrecto

**Solucion:** Modificar PathProcessor para priorizar `MetaSiteResolverService` (domain-based) sobre `TenantContextService` (user-based):

```php
// PRIORITY: Domain-based resolution via MetaSiteResolverService takes
// precedence over user-based TenantContextService.
$tenantId = NULL;
if ($this->metaSiteResolver) {
  $metaSite = $this->metaSiteResolver->resolveFromRequest($request);
  if ($metaSite) {
    $tenantId = $metaSite['group_id'];
  }
}
// Fallback to user-based tenant context if no domain match.
if ($tenantId === NULL && $this->tenantContext) {
  $tenant = $this->tenantContext->getCurrentTenant();
  if ($tenant && $tenant->hasField('group_id')) {
    $tenantId = (int) ($tenant->get('group_id')->target_id ?? 0) ?: NULL;
  }
}
```

**Archivo:** `web/modules/custom/jaraba_page_builder/src/PathProcessor/PathProcessorPageContent.php`

### P2: Entidad Tenant necesaria (no solo Group + Domain Access)

**Problema:** El setup inicial creo Group (ID 6), Domain Access entities y SiteConfig, pero el `MetaSiteResolverService` resuelve dominios a traves de la entidad `Tenant` (no directamente por Domain Access `third_party_settings`).

**Las 3 estrategias del MetaSiteResolverService:**
1. Domain Access hostname -> Tenant.domain_id -> Tenant.group_id
2. Tenant.domain exact match -> Tenant.group_id
3. Subdomain prefix match -> Tenant.domain STARTS_WITH -> Tenant.group_id

Todas requieren una entidad Tenant que vincule `domain`, `domain_id` y `group_id`.

**Solucion:** Crear entidad Tenant ID 6 con:
- `domain`: jarabaimpact.com
- `domain_id`: jarabaimpact_jaraba_saas_lndo_site (Domain Access entity)
- `group_id`: 6 (Group entity)
- `vertical`: 2 (ImpactHub)
- `subscription_plan`: 3 (Enterprise)
- `subscription_status`: active

### P3: SitePageTree status field usa '1' no 'published'

**Problema:** El campo `status` de SitePageTree se define como `list_string` con valores `draft/published/archived`, pero el `buildMetaSiteContext()` del MetaSiteResolverService filtra por `'status' => 1` (integer). Esto funciona porque Drupal almacena el campo booleano/list como integers internamente, y `'published'` se guarda como `1` en la BD.

**Leccion:** Al crear SitePageTree nodes via script, usar `'status' => 'published'` (string) que Drupal convierte automaticamente.

---

## Lecciones Aprendidas

### L1: Domain-based > User-based para PathProcessor multi-tenant
El PathProcessor DEBE priorizar la resolucion por dominio sobre la resolucion por usuario. Un admin que gestiona multiples tenants necesita que el dominio que visita determine el contexto, no su relacion usuario-tenant.

**Regla:** PATH-DOMAIN-PRIORITY-001

### L2: La entidad Tenant es el puente obligatorio entre Domain y Group
No basta con crear Group + Domain Access + SiteConfig. El MetaSiteResolverService requiere una entidad Tenant que vincule los tres. El orden de creacion correcto es:
1. Group (tenant type)
2. Domain Access entities (dev + prod)
3. Tenant entity (con domain, domain_id, group_id)
4. SiteConfig (con tenant_id = group_id)
5. PageContent (con tenant_id = group_id)
6. SitePageTree (con tenant_id = group_id)

**Regla:** TENANT-BRIDGE-001

### L3: Scripts PHP temporales vs drush eval
Los `drush eval` con PHP complejo fallan por escaping de backslashes en Docker. Los scripts `.php` ejecutados via `drush scr` son la unica via fiable para operaciones multi-entidad.

### L4: El prefijo CSS ji- distingue meta-sitios
Cada meta-sitio debe tener su propio prefijo CSS:
- `pj-` para pepejaraba.com (marca personal)
- `ji-` para jarabaimpact.com (institucional B2B)
Esto evita colisiones de estilos cuando el CSS se inyecta en el mismo DOM via canvas_data.

---

## Entidades Creadas

| Entidad | ID | Descripcion |
|---------|-----|-------------|
| Group | 6 | Jaraba Impact - Institucional B2B |
| Tenant | 6 | jarabaimpact.com -> Group 6 |
| SiteConfig | 2 | Jaraba Impact config |
| Domain Access | jarabaimpact_jaraba_saas_lndo_site | Dev proxy |
| Domain Access | jarabaimpact_com | Produccion |
| PageContent | 73 | Inicio (Homepage) |
| PageContent | 74 | Certificacion |
| PageContent | 75 | Aviso Legal |
| PageContent | 76 | Politica de Privacidad |
| PageContent | 77 | Politica de Cookies |
| PageContent | 66 | Plataforma (actualizada) |
| PageContent | 68 | Impacto (actualizada) |
| PageContent | 69 | Programas (actualizada) |
| PageContent | 71 | Contacto (actualizada) |
| SitePageTree | 11-19 | 9 nodos (6 NAV + 3 FOOTER) |

## Ficheros Modificados

| Fichero | Cambio |
|---------|--------|
| `web/modules/custom/jaraba_page_builder/src/PathProcessor/PathProcessorPageContent.php` | Fix: domain-based tenant resolution priority |
| `.lando.yml` | Proxy: jarabaimpact.jaraba-saas.lndo.site |
| `web/build_jarabaimpact_pages.php` | Script de construccion (referencia, no ejecutar) |

## Reglas Nuevas

| ID | Prioridad | Descripcion |
|----|-----------|-------------|
| PATH-DOMAIN-PRIORITY-001 | P0 | PathProcessor DEBE priorizar MetaSiteResolverService (domain) sobre TenantContextService (user) para determinar el tenant de una peticion HTTP. |
| TENANT-BRIDGE-001 | P0 | Todo meta-sitio REQUIERE entidad Tenant que vincule domain, domain_id y group_id. No basta con Group + Domain Access. |

## Regla de Oro #39
> **La identidad de un meta-sitio la define el dominio, no el usuario.** Cuando un admin gestiona multiples tenants, el PathProcessor debe resolver el contenido por hostname del request (via MetaSiteResolverService) y solo caer al contexto de usuario como fallback. Un usuario puede pertenecer a N tenants, pero un dominio siempre pertenece a exactamente uno.
