# Aprendizaje #225 — SEO Google Search Console Fix + Indexing Automation 10/10

**Fecha:** 2026-03-26
**Autor:** Claude Opus 4.6
**Commits:** b7482c792, ffff469b9, 52f486040, c11816207, d5eee39be
**Modulos:** jaraba_page_builder, jaraba_insights_hub, ecosistema_jaraba_theme

---

## Contexto

Google Search Console reporto 7 problemas para jarabaimpact.com: canonical mismatch en /es/node,
duplicados pt-br, sitemap con URLs .html que 404, robots.txt sin prefijos de idioma.
Se implementaron 7 fixes inmediatos + automatizacion completa de notificaciones a Google.

## 5 Hallazgos Clave

### Hallazgo #1: Sitemap .html es el fix con mayor ROI
- SitemapController::staticPages() hardcodeaba .html en 8 URLs de verticales
- Las rutas en routing.yml NO tienen .html — el sitemap apuntaba a URLs que 404
- Fix: eliminar .html. Google deja de desperdiciar crawl budget inmediatamente
- REGLA: URLs en sitemap DEBEN coincidir exactamente con rutas en routing.yml

### Hallazgo #2: Cross-language canonical es mejor que 301 para idiomas inactivos
- Para pt-br/en sin contenido real, canonical apuntando a /es/ es reversible
- Si se usaran 301 redirects y luego se activa pt-br, Google tardaria semanas en desaprender
- REGLA: SEO-CANONICAL-CLEAN-001 — usar canonical cross-language, no redirect, para consolidar

### Hallazgo #3: sc-domain: vs https:// en Google Search Console API
- Propiedades de dominio usan `sc-domain:example.com` como siteUrl
- Propiedades de URL usan `https://example.com/`
- El servicio DEBE leer el site_url de la conexion OAuth, no construirlo
- REGLA: GoogleSeoNotificationService usa connection.site_url para la API

### Hallazgo #4: settings.env.php se pierde en git reset --hard
- El deploy usa `git reset --hard FETCH_HEAD` que sobreescribe settings.env.php
- deploy.yml DEBE regenerar settings.env.php DESPUES del pull con GitHub secrets
- REGLA: Credenciales nuevas requieren entrada en deploy.yml settings.env.php generation

### Hallazgo #5: OAuth scope upgrade requiere re-autorizacion
- Cambiar scope de webmasters.readonly a webmasters+indexing NO se propaga a tokens existentes
- Los refresh tokens mantienen el scope original — re-auth obligatoria
- REGLA: Documentar re-auth como paso post-deploy cuando se amplia scope

## 8 Reglas Nuevas

| Regla | Severidad | Descripcion |
|-------|:---------:|-------------|
| SEO-REDIRECT-NODE-001 | P0 | SeoRedirectSubscriber 301 /node → /{langcode} |
| SEO-CANONICAL-CLEAN-001 | P0 | Canonical homepage sin /node + cross-language para idiomas inactivos |
| SEO-ROBOTS-LANGPREFIX-001 | P1 | Disallows con prefijo idioma en robots.txt dinamico |
| SEO-DEPLOY-NOTIFY-001 | P1 | Post-deploy sitemap submission automatico via Drush |
| SEO-MULTIDOMAIN-001 | P1 | Validador ampliado de 10 a 17 checks |

## 3 Reglas de Oro

- **#158**: Sitemap URLs DEBEN coincidir con rutas de routing.yml — .html fantasma = crawl budget desperdiciado
- **#159**: OAuth scope upgrade requiere re-autorizacion — refresh tokens NO heredan scopes nuevos
- **#160**: sc-domain: vs https:// — leer site_url de la conexion, NUNCA construirlo

## Verificacion Produccion

| Dominio | Sitemaps | Indexing API | Estado |
|---------|:--------:|:------------:|:------:|
| plataformadeecosistemas.com | 4/4 OK | HTTP 200 | ACTIVO |
| jarabaimpact.com | 4/4 OK | HTTP 200 | ACTIVO |
| pepejaraba.com | 4/4 OK | HTTP 200 | ACTIVO |
| plataformadeecosistemas.es | 4/4 OK | HTTP 200 | ACTIVO |

## Glosario

| Termino | Definicion |
|---------|-----------|
| **GSC** | Google Search Console |
| **sc-domain** | Formato de propiedad de dominio en GSC (vs propiedad URL) |
| **Indexing API** | API de Google para notificar cambios de URL (200 req/dia/propiedad) |
| **SeoNotificationLog** | Entity de tracking de notificaciones enviadas a Google |
