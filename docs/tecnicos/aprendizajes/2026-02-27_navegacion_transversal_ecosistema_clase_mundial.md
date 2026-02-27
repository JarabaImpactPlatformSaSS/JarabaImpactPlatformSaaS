# Aprendizaje #144 — Navegación Transversal del Ecosistema: Banda Footer + Auth Visibility + Schema.org Dinámico

**Fecha:** 2026-02-27
**Categoría:** Meta-Sitios / SEO / UX
**Impacto:** Alto

---

## 1. Contexto

Auditoria de navegacion transversal entre los 3 meta-sitios (pepejaraba.com, jarabaimpact.com, plataformadeecosistemas.es) y el SaaS principal (plataformadeecosistemas.com) revelo ausencia total de cross-links entre dominios, botones de autenticacion inapropiados en sitios tipo brochure, y Schema.org generico sin relaciones `sameAs` entre dominios del ecosistema. Se implemento un sistema completo en 8 fases.

## 2. Patrones Clave Aprendidos

### 2.1 Banda Ecosistema en Footer via SiteConfig JSON

**Problema:** No existia ningun mecanismo de navegacion transversal entre los dominios del ecosistema. Un visitante de pepejaraba.com no tenia forma de descubrir jarabaimpact.com o plataformadeecosistemas.es.

**Solucion:** 2 nuevos campos en SiteConfig entity: `ecosystem_footer_enabled` (boolean) y `ecosystem_footer_links` (string_long con JSON array). El JSON almacena objetos `{name, url, label, current}` donde `current: true` marca el sitio actual (renderizado en bold sin link). La banda se inserta entre el copyright/social y el "Funciona con Jaraba" en `_footer.html.twig`, con clase `landing-footer__ecosystem`. SEO: links entre dominios propios con `rel="noopener"` (sin nofollow ni noreferrer para preservar autoridad SEO).

**Patron:** Para navegacion cross-domain en ecosistemas multi-sitio, usar JSON configurable por tenant con flag `current` para el sitio actual. Esto permite anadir/modificar dominios sin code deploy.

### 2.2 Visibilidad de Auth Condicionada por Tipo de Sitio

**Problema:** Los meta-sitios tipo brochure/marca personal (pepejaraba.com, plataformadeecosistemas.es) mostraban botones de Login/Registro que no tienen sentido en un sitio informativo — confunden al visitante.

**Solucion:** Nuevo campo `header_show_auth` (boolean, default TRUE) en SiteConfig. El template `_header-classic.html.twig` envuelve las acciones de auth (login, registro, mi cuenta, cerrar sesion) en un `{% if show_auth %}` condicional. El CTA configurable (ej. "Acceder al Ecosistema", "Solicita una Demo") permanece visible siempre, independiente de show_auth.

**Patron:** Separar CTA de negocio de acciones de autenticacion. El CTA siempre visible aporta conversion, el auth solo es relevante en sitios con area privada.

### 2.3 Schema.org Dinamico Person/Organization con sameAs

**Problema:** El Schema.org existente (`_ped-schema.html.twig`) estaba hardcodeado para PED, con tipo Organization fijo. No cubria pepejaraba (marca personal = Person) ni jarabaimpact, y no declaraba relaciones `sameAs` entre dominios.

**Solucion:** Nuevo template `_meta-site-schema.html.twig` que genera JSON-LD dinamico basado en `meta_site.schema_type` (Person/Organization). El tipo se determina en `preprocess_page()` por group_id (grupo 5 = Person, resto = Organization). Los links del ecosistema (excluyendo el sitio actual) se inyectan como array `sameAs`, declarando a los buscadores la relacion entre dominios.

**Patron:** Para ecosistemas multi-sitio, Schema.org `sameAs` entre dominios propios refuerza la autoridad SEO combinada. Usar `Person` para marca personal y `Organization` para empresas/plataformas.

### 2.4 Twig Boolean Gotcha con `|default(true)`

**Problema:** El filtro Twig `|default(true)` trata `false` como valor vacio y lo reemplaza por `true`. Esto causaba que `ecosystem_footer_enabled: false` se renderizara como `true`.

**Solucion:** Usar ternario explicito: `{% set eco_enabled = (ts.ecosystem_footer_enabled is defined) ? ts.ecosystem_footer_enabled : false %}`. Esta tecnica (ya documentada en Aprendizaje anterior) se aplica a todo boolean en Twig que pueda ser `false` como valor valido.

**Patron:** NUNCA usar `|default()` para booleans en Twig. Siempre usar `is defined ? value : fallback`.

### 2.5 SCSS Compilation Bypass

**Problema:** El compilador SCSS (`npx sass`) falla por error preexistente en `_reviews.scss` (Undefined variable `$ej-color-text` linea 125), bloqueando la compilacion de nuevos estilos.

**Solucion:** Escribir los nuevos estilos en el SCSS source (para que queden versionados correctamente) Y appendear el CSS compilado directamente al `css/main.css`. Esto permite progreso sin bloquear mientras se resuelve el error preexistente.

**Patron:** Cuando un error preexistente bloquea el pipeline SCSS, append al CSS compilado como workaround temporal. SIEMPRE escribir tambien en el SCSS source para que la compilacion futura lo incluya.

### 2.6 Drupal 11 No Tiene entity:updates

**Problema:** `drush entity:updates` no existe en Drupal 11. Los nuevos campos definidos en `baseFieldDefinitions()` de SiteConfig no se instalaban automaticamente.

**Solucion:** Combinar SQL `ALTER TABLE` para anadir las columnas fisicas + script PHP que llama a `\Drupal::entityDefinitionUpdateManager()->installFieldStorageDefinition()` para cada nuevo campo. El script debe escribirse via herramienta Write (no heredoc en shell, que pierde variables `$`).

**Patron:** En Drupal 11, para nuevos base fields en entidades existentes: (1) ALTER TABLE SQL, (2) PHP script con `installFieldStorageDefinition()`, (3) drush cache:rebuild.

## 3. Metricas de Ejecucion

| Metrica | Valor |
|---------|-------|
| Ficheros PHP modificados | 2 (SiteConfig.php, ecosistema_jaraba_theme.theme) |
| Ficheros Twig creados | 1 (_meta-site-schema.html.twig) |
| Ficheros Twig modificados | 3 (_footer, _header-classic, page--page-builder) |
| Ficheros SCSS modificados | 1 (_landing-page.scss) + CSS append |
| Campos nuevos en SiteConfig | 3 (ecosystem_footer_enabled, ecosystem_footer_links, header_show_auth) |
| Getters nuevos en SiteConfig | 3 (isEcosystemFooterEnabled, getEcosystemFooterLinks, isHeaderShowAuth) |
| SiteConfigs actualizados | 3 (PepeJaraba, JarabaImpact, PED) |
| Bug PHP corregido | 1 (duplicado ecosistema_jaraba_core_entity_update) |
| Verificacion browser | 4 sitios OK (pepejaraba, jarabaimpact, PED, SaaS) |

## 4. Reglas Derivadas

| Regla | Descripcion | Prioridad |
|-------|-------------|-----------|
| ECOSYSTEM-FOOTER-001 | La banda ecosistema en footer se configura via SiteConfig JSON con objetos `{name, url, label, current}`. Links entre dominios propios con `rel="noopener"` (sin nofollow). El sitio actual se marca con `current: true` (bold, sin link). Solo se renderiza si `ecosystem_footer_enabled = TRUE`. | P1 |
| HEADER-AUTH-VISIBILITY-001 | Las acciones de autenticacion (login, registro, mi cuenta) se controlan con `header_show_auth` boolean en SiteConfig. El CTA configurable del header permanece visible siempre, independiente de auth. Sitios brochure/marca personal DEBEN tener `header_show_auth = FALSE`. | P1 |
| SCHEMA-DYNAMIC-METASITE-001 | Cada meta-sitio genera Schema.org JSON-LD dinamico via `_meta-site-schema.html.twig`. Tipo `Person` para marca personal (pepejaraba), `Organization` para empresas/plataformas. Array `sameAs` con URLs de los otros dominios del ecosistema (excluyendo current). | P1 |

## 5. Referencias

- **SiteConfig entity:** `web/modules/custom/jaraba_site_builder/src/Entity/SiteConfig.php`
- **Footer band:** `web/themes/custom/ecosistema_jaraba_theme/templates/partials/_footer.html.twig` (lineas 178-205)
- **Header auth:** `web/themes/custom/ecosistema_jaraba_theme/templates/partials/_header-classic.html.twig`
- **Schema.org template:** `web/themes/custom/ecosistema_jaraba_theme/templates/partials/_meta-site-schema.html.twig`
- **Theme preprocess:** `web/themes/custom/ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme` (lineas ~2396-2430)
- **SCSS:** `web/themes/custom/ecosistema_jaraba_theme/scss/components/_landing-page.scss`
- **Directrices:** v93.0.0
- **Flujo:** v46.0.0
- **Indice:** v118.0.0
- **Aprendizajes relacionados:** #128 (Meta-Site Nav), #132 (PED Meta-Site), #139 (i18n Meta-Sites)
