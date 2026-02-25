# Aprendizaje #121: Meta-Sitio pepejaraba.com — Hallazgos y Plan de Remediación

**Fecha:** 2026-02-25
**Módulo:** `jaraba_page_builder`, `jaraba_site_builder`, `ecosistema_jaraba_core`
**Contexto:** Construcción completa del meta-sitio pepejaraba.com (9 páginas, SiteConfig, PageTree) usando el Page Builder. Se detectaron múltiples gaps que impiden el funcionamiento como sitio independiente navegable.

---

## Estado Actual

### Lo que FUNCIONA
- **9 páginas creadas** con contenido profesional (HTML + CSS), estado "Publicado"
- **SiteConfig** configurada (nombre, tagline, email, teléfono, social links, header/footer, homepage_id=57)
- **SitePageTree** con 9 nodos (6 NAV + 3 FOOTER)
- **PathProcessor** resuelve aliases: `/inicio` → `/page/57` (HTTP 200 en todos los paths)
- **Canvas Editor** carga y es editable para HOME (page 57, creada vía JS API)
- **Frontend render** correcto en todas las páginas vía path canónico (`/page/{id}`) o alias (`/inicio`)
- **Meta tags OG** correctos por página (og:title, og:description)
- **Proxy Lando** funcional para `pepejaraba.jaraba-saas.lndo.site`

### Lo que NO funciona (Hallazgos Priorizados)

---

## HALLAZGO H1 — Homepage del Tenant NO se sirve en raíz del dominio [P0-CRÍTICO]

**Síntoma:** `https://pepejaraba.jaraba-saas.lndo.site/` muestra la landing genérica de la plataforma ("Transforma tu ecosistema digital") en lugar de la homepage del tenant (page 57, "Inicio").

**Causa raíz:** No existe lógica que:
1. Detecte el dominio/subdominio del request
2. Resuelva qué tenant corresponde a ese dominio
3. Sirva la `homepage_id` configurada en SiteConfig como `<front>`

**Componentes faltantes:**
- `EventSubscriber/DomainTenantSubscriber.php` — Request subscriber que extraiga subdomain del `$request->getHost()` y resuelva tenant via `TenantDomainService`
- Ruta dinámica de homepage por tenant que sirva `SiteConfig->homepage_id`
- Integración de `TenantContextService` con resolución por dominio (actualmente solo resuelve por `admin_user`)

**Referencia código:**
- `TenantContextService.php:92-146` — Solo resuelve por UID, resolución por Group comentada
- `TenantDomainService.php:37-49` — `getTenantByDomain()` existe pero NO se usa en ningún subscriber
- `Tenant.php:142-193` — `provisionDomainIfNeeded()` crea Domain Access entities

**Impacto:** Sin esto, ningún meta-sitio funciona como sitio independiente.

---

## HALLAZGO H2 — Domain Access hostname NO coincide con Lando proxy [P0-CRÍTICO]

**Síntoma:** El tenant "Pepe Jaraba" tiene Domain Access entity `pepejaraba_com` con hostname `pepejaraba.com`, pero el proxy Lando es `pepejaraba.jaraba-saas.lndo.site`.

**Causa raíz:** `Tenant::provisionDomainIfNeeded()` usa la configuración `jaraba_base_domain` para construir el hostname. El tenant se creó con `domain = "pepejaraba.com"` (producción), no con `"pepejaraba"` (que habría generado `pepejaraba.jaraba-saas.lndo.site`).

**Datos encontrados:**
```
Tenant 1 (Aceites del Sur): domain=aceitesdelsur.jaraba.io → Domain hostname=aceitesdelsur.jaraba-saas.lndo.site ✓
Tenant 5 (Pepe Jaraba):     domain=pepejaraba.com       → Domain hostname=pepejaraba.com                      ✗ (no coincide con Lando)
```

**Fix:** Crear Domain Access entity adicional con hostname `pepejaraba.jaraba-saas.lndo.site` para desarrollo local, o actualizar el campo `domain` del tenant a solo `"pepejaraba"`.

---

## HALLAZGO H3 — GrapesJS Canvas Editor NO carga contenido de páginas creadas por PHP [P1-ALTO]

**Síntoma:** Las 8 páginas creadas vía `build_pages_content.php` muestran canvas vacío en el editor GrapesJS. Solo la HOME (page 57, creada vía `editor.setComponents()` + `editor.store()`) carga correctamente con 15 componentes.

**Causa raíz:** Discrepancia en formato de `canvas_data`:

| Página | Método creación | `components` | `styles` | `html` | `css` | Editor carga |
|--------|----------------|--------------|----------|--------|-------|-------------|
| 57 (HOME) | JS API → `editor.store()` | `[]` | `array(55)` ← GrapesJS nativo | 7918ch | 8816ch | **SÍ** |
| 58 (Manifiesto) | PHP + abierta en editor | `[]` | `array(69)` | 3772ch | 5339ch | **NO** |
| 59-65 | PHP script solo | `[]` | `[]` | 1227-4570ch | 7679ch | **NO** |

**Flujo de carga** (`grapesjs-jaraba-canvas.js:912-992`):
1. `loadContent()` → `GET /api/v1/pages/{id}/canvas`
2. Si `components.length > 0` → carga directa ✓
3. Si no, si `html.trim().length > 0` → `editor.setComponents(data.html)` como fallback
4. El fallback existe en el código pero **no funciona de forma fiable** (posible race condition con el iframe del canvas, o error silencioso en la inyección)

**Análisis API:** El endpoint `CanvasApiController::getCanvas()` devuelve correctamente `{ components: [], styles: [], html: "...", css: "..." }` para todas las páginas. El problema está en el frontend JavaScript.

**Fix recomendado:**
- Opción A: Abrir cada página en el editor y hacer `editor.store()` para generar el formato nativo
- Opción B: Debuggear el fallback `editor.setComponents(data.html)` en `loadContent()` — puede ser un timing issue con `setTimeout(injectTemplate, 100)` que necesite mayor delay o un evento `editor:ready`
- Opción C: Crear script PHP que parsee HTML a formato de componentes GrapesJS

---

## HALLAZGO H4 — Header/Navegación del tenant NO se muestra [P1-ALTO]

**Síntoma:** Todas las páginas del meta-sitio muestran el header genérico de la plataforma ("Jaraba / Mi cuenta / Cerrar sesión") en lugar de la navegación configurada en SitePageTree (Inicio, Manifiesto, Método, Casos de éxito, Blog, Contacto).

**Causa raíz:** La vista frontend (`/page/{id}`) usa el template de página estándar de Drupal/ecosistema_jaraba_theme, que renderiza el header global de la plataforma. No existe:
1. Un template que detecte "esta página pertenece a un meta-sitio" y renderice el header del SiteConfig
2. Integración entre SitePageTree y el sistema de navegación del template
3. Un `page_alter` hook o preprocessor que inyecte la navegación del tenant

**SiteConfig tiene la configuración:**
- `header_type: minimal`
- `header_sticky: 1`
- `header_cta_text: "Acceder al Ecosistema"`
- `header_cta_url: "https://plataformadeecosistemas.com"`

**SitePageTree tiene 9 nodos** correctamente ponderados y con `show_in_navigation` configurado.

**Fix:** Implementar un template override o preprocessor que, para rutas de page_content, detecte el tenant → cargue SiteConfig → renderice header/footer/nav propios del meta-sitio.

---

## HALLAZGO H5 — Footer del meta-sitio viene del tema, no de SiteConfig [P1-ALTO]

**Síntoma:** El footer muestra las columnas "Plataforma / Empresa / Legal" del tema base de la plataforma, no el footer configurado en SiteConfig (footer_type: standard, footer_columns: 3, footer_show_social: 1, footer_show_newsletter: 1, footer_copyright: "© 2026 Pepe Jaraba...").

**Causa raíz:** Mismo problema que H4. No hay integración SiteConfig → template de footer.

---

## HALLAZGO H6 — Schema.org JSON-LD NO es tenant-aware [P2-MEDIO]

**Síntoma:** El JSON-LD en `<head>` muestra:
```json
{
  "@type": "Organization",
  "name": "Jaraba Impact Platform",
  "description": "Plataforma SaaS AI-First para productores locales..."
}
```
En lugar de datos del tenant "Pepe Jaraba".

**Causa raíz:** `SchemaOrgService` genera el JSON-LD global sin considerar el tenant actual ni la SiteConfig.

**Fix:** Cuando se renderiza una page_content, el Schema.org debería usar datos de SiteConfig del tenant.

---

## HALLAZGO H7 — SiteConfig Entity References no persisten con valor simple [P2-MEDIO]

**Síntoma:** `$config->set('homepage_id', 57); $config->save();` → el valor queda NULL tras guardar.

**Causa raíz:** Los campos `entity_reference` de Drupal requieren formato `['target_id' => $value]`, no un entero simple. Esto es comportamiento estándar de Drupal, pero:
1. No hay validación ni error visible cuando se pasa un entero
2. El formulario admin de SiteConfig debe manejar esto internamente

**Fix:** Verificar que el formulario admin de SiteConfig convierte correctamente los valores al guardar. Documentar el formato requerido para programmatic saves.

---

## HALLAZGO H8 — El campo `og:site_name` muestra "Jaraba" en vez del nombre del tenant [P2-MEDIO]

**Síntoma:** `<meta property="og:site_name" content="Jaraba" />` en todas las páginas del meta-sitio.

**Causa raíz:** El metatag `og:site_name` se genera desde la configuración global de Drupal (`system.site`), no desde SiteConfig del tenant.

**Fix:** Implementar hook_metatags_alter o token que use SiteConfig.site_name cuando la página pertenece a un tenant.

---

## HALLAZGO H9 — Title tag muestra sufijo incorrecto [P3-BAJO]

**Síntoma:** `<title>Inicio | Jaraba</title>` en lugar de `<title>Inicio | Pepe Jaraba</title>`

**SiteConfig configurado:** `meta_title_suffix: "| Pepe Jaraba"`

**Causa raíz:** El title token usa el nombre del sitio global. El `meta_title_suffix` de SiteConfig no se aplica.

---

## HALLAZGO H10 — No hay ruta de publicación frontend [P3-BAJO]

**Síntoma:** `jaraba_page_builder.page_view` NO existe como ruta nombrada.

**Evidencia:** Las páginas se sirven vía auto-route de la entidad (link templates en `PageContent.php`), no vía una ruta dedicada. Esto funciona pero limita la capacidad de:
- Generar URLs programáticas
- Integrar con el sistema de navegación
- Aplicar middleware específico de meta-sitio

---

## Resumen de Prioridades

| Prioridad | Hallazgo | Impacto | Esfuerzo estimado |
|-----------|----------|---------|-------------------|
| **P0** | H1 — Homepage por dominio | Bloqueante — ningún meta-sitio funciona | Alto — EventSubscriber + ruta dinámica |
| **P0** | H2 — Domain Access hostname mismatch | Bloqueante en local | Bajo — crear/actualizar Domain entity |
| **P1** | H3 — GrapesJS no carga PHP content | Editor inutilizable para 8/9 páginas | Medio — debug JS fallback |
| **P1** | H4 — Header/nav del tenant no se muestra | Sin navegación propia | Alto — template override + preprocessor |
| **P1** | H5 — Footer del tenant no se muestra | Sin footer propio | Alto — mismo fix que H4 |
| **P2** | H6 — Schema.org no tenant-aware | SEO incorrecto | Medio — SchemaOrgService update |
| **P2** | H7 — SiteConfig entity_reference save | Solo afecta programmatic saves | Bajo — documentar + validar |
| **P2** | H8 — og:site_name incorrecto | SEO/social sharing incorrecto | Bajo — hook_metatags_alter |
| **P3** | H9 — Title suffix incorrecto | Menor impacto SEO | Bajo — token personalizado |
| **P3** | H10 — No hay ruta page_view nombrada | Limita extensibilidad | Bajo — registrar ruta explícita |

---

## Arquitectura de la Solución (Propuesta)

### Fase 1: Domain Resolution (P0) — H1, H2
```
Request → DomainTenantSubscriber (KernelEvents::REQUEST)
  ├─ Extraer host del request
  ├─ TenantDomainService::getTenantByDomain()
  ├─ Almacenar tenant en request attributes
  └─ Si es raíz "/" → redirigir a homepage_id de SiteConfig
```

### Fase 2: Tenant-Aware Templates (P1) — H4, H5
```
page_content render → preprocess_page()
  ├─ Detectar que es page_content de un tenant
  ├─ Cargar SiteConfig del tenant
  ├─ Cargar SitePageTree para navegación
  └─ Inyectar en template: header, nav, footer propios
```

### Fase 3: GrapesJS Content Loading Fix (P1) — H3
```
grapesjs-jaraba-canvas.js → loadContent()
  ├─ Mejorar fallback HTML → setComponents()
  ├─ Esperar evento editor:ready antes de inyectar
  ├─ Añadir retry con delay progresivo
  └─ Log de diagnóstico para debugging
```

### Fase 4: SEO/Meta Tenant-Aware (P2-P3) — H6, H8, H9
```
Metatag/Schema hooks → detectar tenant
  ├─ hook_metatags_alter → og:site_name, title suffix
  ├─ SchemaOrgService → datos de SiteConfig
  └─ Token personalizado para meta_title_suffix
```

---

## Archivos Clave a Modificar

| Archivo | Acción | Hallazgo |
|---------|--------|----------|
| `ecosistema_jaraba_core/src/EventSubscriber/` | **CREAR** DomainTenantSubscriber.php | H1 |
| `ecosistema_jaraba_core/src/Service/TenantContextService.php` | **MODIFICAR** — añadir resolución por dominio | H1 |
| `jaraba_page_builder/js/grapesjs-jaraba-canvas.js` | **MODIFICAR** — fix loadContent() fallback | H3 |
| `jaraba_site_builder/` o `ecosistema_jaraba_theme/` | **CREAR** template/preprocessor para meta-sitio header/footer/nav | H4, H5 |
| `ecosistema_jaraba_core/src/Service/SchemaOrgService.php` | **MODIFICAR** — hacer tenant-aware | H6 |
| `.module` hooks | **CREAR** hook_metatags_alter, hook_tokens | H8, H9 |

---

## Páginas Creadas del Meta-Sitio (Referencia)

| ID | Título | Path | Frontend | Editor |
|----|--------|------|----------|--------|
| 57 | Inicio | /inicio | ✓ Renderiza | ✓ Editable |
| 58 | Manifiesto | /manifiesto | ✓ Renderiza | ✗ Canvas vacío |
| 59 | Método Jaraba | /metodo | ✓ Renderiza | ✗ Canvas vacío |
| 60 | Casos de Éxito | /casos-de-exito | ✓ Renderiza | ✗ Canvas vacío |
| 61 | Blog | /blog | ✓ Renderiza | ✗ Canvas vacío |
| 62 | Contacto | /contacto | ✓ Renderiza | ✗ Canvas vacío |
| 63 | Aviso Legal | /aviso-legal | ✓ Renderiza | ✗ Canvas vacío |
| 64 | Política de Privacidad | /privacidad | ✓ Renderiza | ✗ Canvas vacío |
| 65 | Política de Cookies | /cookies | ✓ Renderiza | ✗ Canvas vacío |

---

## Scripts de Diagnóstico Creados

- `web/debug_grapesjs_storage.php` — Audita campos canvas_data, rendered_html, gjs_data por página
- `web/debug_routing_paths.php` — Verifica aliases, Domain Access, TenantContext, rutas
- `web/verify_pepejaraba_final.php` — Verificación completa del meta-sitio (config + páginas + tree)
- `web/publish_pepejaraba_pages.php` — Publica todas las páginas del tenant
- `web/fix_siteconfig_links.php` — Corrige entity references en SiteConfig
