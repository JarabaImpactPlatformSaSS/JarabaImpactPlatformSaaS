# Plan de Implementacion: Centro de Ayuda Clase Mundial — `/ayuda`

- **Version**: v1.0.0
- **Fecha**: 2026-02-28
- **Estado**: EN PROGRESO
- **Modulos afectados**: `jaraba_support`, `ecosistema_jaraba_theme`
- **Ruta principal**: `/ayuda`

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Objetivos](#2-objetivos)
3. [Arquitectura Tecnica](#3-arquitectura-tecnica)
4. [Archivos Modificados](#4-archivos-modificados)
5. [Datos Semilla — 25 FAQs](#5-datos-semilla--25-faqs)
6. [Integracion con Sistema de Soporte](#6-integracion-con-sistema-de-soporte)
7. [SEO y Schema.org](#7-seo-y-schemaorg)
8. [Cumplimiento de Directrices](#8-cumplimiento-de-directrices)
9. [Verificacion](#9-verificacion)
10. [Historial de Cambios](#10-historial-de-cambios)

---

## 1. Resumen Ejecutivo

La ruta `/ayuda` contaba con infraestructura tecnica completa — rutas registradas, controlador, plantillas Twig, SCSS (694 LOC), JavaScript y widget de FAQ Bot — pero **cero contenido real**. El resultado era una experiencia de usuario rota:

- **Hero vacio**: titulo generico con "0 articulos disponibles" como contador.
- **Secciones ocultas**: los condicionales Twig (`{% if categories %}`, `{% if popular_articles %}`) ocultaban bloques enteros al no existir datos.
- **CTA sin accion**: el bloque call-to-action renderizaba sin botones funcionales.
- **FAQ Bot sin sugerencias**: el widget aparecia pero no ofrecia preguntas sugeridas ni resultados.
- **Categorias e-commerce**: el sistema anterior usaba categorias de comercio electronico (envios, devoluciones, metodos de pago) que no corresponden a una plataforma SaaS multi-vertical con 10 verticales.

Esta situacion violaba la directriz **RUNTIME-VERIFY-001**: toda funcionalidad desplegada debe verificarse con datos reales en runtime, no solo con tests unitarios.

El presente plan transforma `/ayuda` en un Centro de Ayuda profesional, funcional desde el primer despliegue, con 25 FAQs semilla, 8 categorias SaaS, SEO completo y enlaces cruzados con el sistema de soporte (`jaraba_support`).

---

## 2. Objetivos

| # | Objetivo | Metrica de exito |
|---|----------|-----------------|
| O1 | Transformar `/ayuda` en un Centro de Ayuda profesional que funcione out-of-the-box | Hero con contador > 0, todas las secciones visibles |
| O2 | Integrar con `jaraba_support` (ticketing) | Quick Links a `/soporte/crear` y `/soporte` operativos |
| O3 | Unificar `/ayuda` (FAQs) + `/ayuda/kb` (Knowledge Base) | Cross-links bidireccionales y busqueda unificada |
| O4 | 25 FAQs semilla cubriendo 8 categorias SaaS | `update_10003` ejecuta sin errores, 25 entidades creadas |
| O5 | SEO: FAQPage + BreadcrumbList JSON-LD + OG/Twitter | Google Rich Results Test pasa sin errores |
| O6 | Quick Links panel + CTA con botones de accion | Botones funcionales con rutas resueltas via `Url::fromRoute()` |
| O7 | Busqueda unificada FAQ + KB via endpoint API | `/api/help-center/search?q=` devuelve resultados mixtos |
| O8 | Responsive completo (mobile-first) | Layout correcto en 320px, 768px, 1024px, 1440px |

---

## 3. Arquitectura Tecnica

### 3.1 Nuevas Categorias SaaS

Se reemplazan las 5 categorias e-commerce por 8 categorias alineadas con la plataforma SaaS multi-vertical:

| ID Maquina | Nombre | Icono | Descripcion |
|------------|--------|-------|-------------|
| `getting_started` | Primeros Pasos | `play-circle` | Guias de inicio rapido, onboarding, configuracion inicial |
| `account` | Cuenta y Perfil | `user` | Gestion de cuenta, perfil profesional, preferencias |
| `features` | Funcionalidades | `zap` | Page Builder, Content Hub, formularios, widgets |
| `billing` | Planes y Facturacion | `credit-card` | Suscripciones, metodos de pago, facturas, upgrades |
| `ai_copilot` | IA y Copiloto | `cpu` | Copiloto IA, agentes autonomos, generacion de contenido |
| `integrations` | Integraciones | `link` | APIs, webhooks, conexiones con terceros, MCP |
| `security` | Seguridad y Privacidad | `shield` | 2FA, permisos, RGPD, politica de datos |
| `troubleshooting` | Solucion de Problemas | `tool` | Errores comunes, rendimiento, compatibilidad |

### 3.2 Refactorizacion de HelpCenterController

**Metodos nuevos/modificados**:

- `getCategoryMeta(string $categoryId): array` — Devuelve nombre, icono, descripcion y color para cada categoria. Centraliza la definicion de las 8 categorias SaaS. Reemplaza el array hardcoded en el metodo `index()`.
- `buildHelpCenterSeoHead(): array` — Genera las meta tags OG, Twitter Card y canonical para la pagina principal del Centro de Ayuda. Sigue el patron de `SeoService` del Content Hub.
- `buildFaqPageSchema(array $faqs): array` — Genera el JSON-LD `FAQPage` con todas las preguntas y respuestas visibles. Cumple con las directrices de Schema.org de Google.
- `searchApi(Request $request): JsonResponse` — Endpoint unificado que busca simultaneamente en FAQs (`faq_item` entity) y articulos de Knowledge Base (`kb_article` entity). Devuelve resultados mezclados con score de relevancia, tipo de fuente y URL.

**Patron de resolucion de rutas**: Todas las URLs generadas para JavaScript usan `Url::fromRoute()` pasadas via `drupalSettings`, cumpliendo **ROUTE-LANGPREFIX-001** (el sitio usa prefijo `/es/`).

### 3.3 Template (help-center.html.twig)

Estructura de bloques actualizada:

1. **Hero**: titulo, subtitulo, barra de busqueda, contador de articulos (real).
2. **Trust Signals**: "Respuesta en menos de 24h", "Base de conocimiento 24/7", "Soporte humano disponible".
3. **Categorias**: grid responsive 2x4 (desktop) / 1 columna (mobile) con las 8 categorias SaaS.
4. **Articulos Populares**: top 6 FAQs por numero de visualizaciones.
5. **Quick Links**: panel lateral con enlaces directos a `/soporte/crear`, `/soporte`, `/ayuda/kb`.
6. **Cross-link KB**: banner "Explora nuestra Base de Conocimiento" con enlace a `/ayuda/kb`.
7. **CTA**: bloque con dos botones de accion — "Contactar Soporte" (`/contacto`) y "Crear Ticket" (`/soporte/crear`).
8. **FAQ Bot**: widget flotante con sugerencias iniciales precargadas desde las FAQs semilla.

### 3.4 SCSS

Aproximadamente 200 LOC nuevas distribuidas en componentes:

- `.help-center-trust-signals`: flexbox con iconos y texto, gap responsive.
- `.help-center-quick-links`: panel lateral con borde izquierdo de acento, lista de enlaces.
- `.help-center-kb-crosslink`: banner con gradiente sutil y boton CTA.
- `.help-center-cta--dual`: layout de dos botones (primario + secundario).
- Todos los colores usan CSS custom properties (`var(--color-*)`) cumpliendo **CSS-VAR-ALL-COLORS-001**.

### 3.5 JavaScript

- Correccion de URLs en `drupalSettings`: se pasan rutas resueltas via `Url::fromRoute()` en el controller, eliminando paths hardcoded que fallaban con el prefijo `/es/`.
- `IntersectionObserver` para animaciones de entrada progresiva en las tarjetas de categoria y articulos populares.
- Debounce en la barra de busqueda (300ms) antes de llamar al endpoint `/api/help-center/search`.

### 3.6 Hook de Actualizacion: update_10003

Implementado en `jaraba_support.install`:

1. Elimina las categorias e-commerce existentes (si las hay).
2. Crea las 8 categorias SaaS con sus metadatos (icono, orden, estado activo).
3. Inserta 25 FAQs semilla distribuidas en las 8 categorias.
4. Cada FAQ incluye: titulo, cuerpo HTML, categoria, peso (orden), estado publicado.
5. Es idempotente: verifica existencia antes de crear.

---

## 4. Archivos Modificados

| # | Archivo | Descripcion |
|---|---------|-------------|
| 1 | `web/modules/custom/jaraba_support/src/Controller/HelpCenterController.php` | Refactorizacion: `getCategoryMeta()`, `buildHelpCenterSeoHead()`, `buildFaqPageSchema()`, `searchApi()`. Categorias SaaS. Trust signals. Quick Links. |
| 2 | `web/modules/custom/jaraba_support/templates/help-center.html.twig` | Trust signals, quick links, KB cross-link, CTA con botones, FAQ Bot sugerencias. Condicionales revisados. |
| 3 | `web/modules/custom/jaraba_support/jaraba_support.install` | `jaraba_support_update_10003()`: migracion de categorias + 25 FAQs semilla. |
| 4 | `web/modules/custom/jaraba_support/jaraba_support.routing.yml` | Ruta `/api/help-center/search` para busqueda unificada. |
| 5 | `web/modules/custom/jaraba_support/jaraba_support.libraries.yml` | Dependencia de `core/drupalSettings` para URLs resueltas. |
| 6 | `web/themes/custom/ecosistema_jaraba_theme/scss/_help-center.scss` | ~200 LOC: trust signals, quick links, KB crosslink, CTA dual, animaciones. |
| 7 | `web/themes/custom/ecosistema_jaraba_theme/js/help-center.js` | Fix drupalSettings URLs, IntersectionObserver, debounce busqueda. |
| 8 | `web/modules/custom/jaraba_support/jaraba_support.module` | `hook_page_attachments_alter()` para inyectar drupalSettings con rutas resueltas en `/ayuda`. |
| 9 | `docs/implementacion/2026-02-28_Plan_Implementacion_Centro_Ayuda_Clase_Mundial_v1.md` | Este documento de plan de implementacion. |

**Total**: 9 archivos modificados, 0 archivos de codigo nuevos, 1 documento nuevo.

---

## 5. Datos Semilla — 25 FAQs

### getting_started (4 FAQs)
1. Como crear mi cuenta en la plataforma
2. Primeros pasos despues de registrarme
3. Como personalizar mi perfil profesional
4. Guia rapida del panel de administracion

### account (3 FAQs)
5. Como cambiar mi contrasena
6. Como actualizar mi informacion de perfil
7. Como configurar las notificaciones

### features (4 FAQs)
8. Como usar el Page Builder para crear paginas
9. Como publicar articulos en el Content Hub
10. Como gestionar formularios de contacto
11. Como configurar mi dominio personalizado

### billing (3 FAQs)
12. Que planes de suscripcion hay disponibles
13. Como actualizar mi plan (upgrade)
14. Como descargar mis facturas

### ai_copilot (4 FAQs)
15. Que es el Copiloto IA y como activarlo
16. Como generar contenido con inteligencia artificial
17. Limites de uso del Copiloto IA segun mi plan
18. Como entrenar al Copiloto con la voz de mi marca

### integrations (3 FAQs)
19. Como conectar mi cuenta de Google Analytics
20. Como configurar webhooks para automatizaciones
21. APIs disponibles y documentacion tecnica

### security (2 FAQs)
22. Como activar la autenticacion en dos pasos (2FA)
23. Politica de privacidad y cumplimiento RGPD

### troubleshooting (2 FAQs)
24. Mi pagina no carga correctamente — pasos de diagnostico
25. Errores comunes al publicar contenido y sus soluciones

---

## 6. Integracion con Sistema de Soporte

### 6.1 Quick Links

El panel de Quick Links en `/ayuda` incluye enlaces directos al sistema de soporte (`jaraba_support`):

- **Crear Ticket**: enlace a `/soporte/crear` via `Url::fromRoute('jaraba_support.ticket_create')`.
- **Mis Tickets**: enlace a `/soporte` via `Url::fromRoute('jaraba_support.ticket_list')`.
- **Base de Conocimiento**: enlace a `/ayuda/kb` via `Url::fromRoute('jaraba_support.kb')`.

### 6.2 CTA (Call to Action)

El bloque CTA al pie del Centro de Ayuda ofrece dos acciones:

- **Contactar Soporte** (boton primario): enlace a `/contacto`.
- **Crear Ticket** (boton secundario): enlace a `/soporte/crear`.

### 6.3 Ticket Deflection

El endpoint de busqueda (`searchApi`) actua como mecanismo de ticket deflection: antes de dirigir al usuario a crear un ticket, se le muestran FAQs y articulos de KB relevantes. Esto reduce la carga del equipo de soporte al resolver preguntas frecuentes de forma autoservicio.

### 6.4 FAQ Bot

El widget de FAQ Bot (ya existente en infraestructura) se activa con sugerencias iniciales extraidas de las 5 FAQs mas populares. El bot utiliza el mismo endpoint de busqueda para obtener resultados contextuales.

---

## 7. SEO y Schema.org

### 7.1 FAQPage JSON-LD

Se genera un bloque `<script type="application/ld+json">` con schema `FAQPage` que incluye todas las FAQs visibles en la pagina. Cada FAQ se mapea a un `Question` con `acceptedAnswer` de tipo `Answer`. Esto habilita los rich snippets de FAQ en los resultados de Google.

### 7.2 BreadcrumbList JSON-LD

Estructura de breadcrumbs:
```
Inicio > Centro de Ayuda
Inicio > Centro de Ayuda > [Categoria]
Inicio > Centro de Ayuda > [Categoria] > [Articulo]
```

### 7.3 QAPage para Articulos Individuales

Cada FAQ individual (vista completa) usa el schema `QAPage` con la pregunta como `mainEntity`.

### 7.4 Meta Tags OG y Twitter

- `og:title`: "Centro de Ayuda — Jaraba Impact Platform"
- `og:description`: descripcion dinamica con el numero de articulos disponibles.
- `og:type`: `website`
- `og:url`: URL canonica con prefijo de idioma.
- `twitter:card`: `summary_large_image`
- `canonical`: `<link rel="canonical">` apuntando a la URL sin parametros de query.

---

## 8. Cumplimiento de Directrices

| Directriz | Estado | Detalle |
|-----------|--------|---------|
| RUNTIME-VERIFY-001 | CUMPLE | 25 FAQs semilla garantizan contenido visible desde el primer despliegue |
| CSS-VAR-ALL-COLORS-001 | CUMPLE | Todos los colores nuevos usan `var(--color-*)`, cero valores hexadecimales hardcoded |
| ROUTE-LANGPREFIX-001 | CUMPLE | URLs para JS pasan via `Url::fromRoute()` + `drupalSettings`, no paths hardcoded |
| ICON-CONVENTION-001 | CUMPLE | Los 8 iconos de categoria usan nombres del set Feather Icons estandar del proyecto |
| TRANSLATABLE-FIELDDATA-001 | CUMPLE | Queries directas a FAQs usan `_field_data` table, no base table |
| QUERY-CHAIN-001 | CUMPLE | `addExpression()` y `join()` no se encadenan; sentencias separadas |
| PREMIUM-FORMS-PATTERN-001 | CUMPLE | Formularios de FAQ/KB extienden `PremiumEntityFormBase`, no `ContentEntityForm` |
| PRESAVE-RESILIENCE-001 | CUMPLE | Presave hooks usan `hasService()` + try-catch para servicios opcionales |
| DOC-GUARD-001 | CUMPLE | Documento nuevo, no modifica master docs. Edicion incremental via Edit |
| I18N / trans | CUMPLE | Todos los strings visibles usan `$this->t()` o `{% trans %}` en Twig |
| SCSS-ENTRY-CONSOLIDATION-001 | CUMPLE | `_help-center.scss` parcial importado desde `main.scss`, sin ambiguedad de entry point |
| TENANT-ISOLATION-ACCESS-001 | CUMPLE | FAQs son publicas (view), edicion/eliminacion requiere verificacion de tenant |
| Schema.org | CUMPLE | FAQPage + BreadcrumbList + QAPage con markup JSON-LD valido |
| SECRET-MGMT-001 | N/A | No se manejan secretos en este modulo |
| SLIDE-PANEL-RENDER-001 | N/A | No se usan slide-panels en el Centro de Ayuda |
| FORM-CACHE-001 | CUMPLE | No se invoca `setCached(TRUE)` incondicionalmente |
| LABEL-NULLSAFE-001 | CUMPLE | `PremiumEntityFormBase` maneja null-safe en `getFormTitle()` |
| DATETIME-ARITHMETIC-001 | CUMPLE | Queries con campos datetime usan `UNIX_TIMESTAMP(REPLACE(...))` |
| TENANT-BRIDGE-001 | N/A | FAQs son globales de plataforma, no segmentadas por tenant |
| SERVICE-CALL-CONTRACT-001 | CUMPLE | Firmas de metodos verificadas con grep en todos los callers |

---

## 9. Verificacion

### 9.1 Despliegue

```bash
# 1. Ejecutar actualizaciones de base de datos
drush updb -y

# 2. Limpiar caches
drush cr
```

### 9.2 Verificacion Funcional

1. **Visitar `/ayuda`**: verificar que el hero muestra "25 articulos disponibles" (o el numero correcto).
2. **Categorias visibles**: las 8 tarjetas de categoria se renderizan con icono, nombre y contador.
3. **Articulos populares**: la seccion muestra al menos 6 FAQs.
4. **Busqueda**: escribir una consulta en la barra de busqueda y verificar resultados mixtos (FAQ + KB).
5. **Quick Links**: verificar que los enlaces a `/soporte/crear`, `/soporte` y `/ayuda/kb` son funcionales.
6. **CTA**: los botones "Contactar Soporte" y "Crear Ticket" navegan correctamente.
7. **FAQ Bot**: el widget muestra sugerencias iniciales al abrirse.

### 9.3 Verificacion SEO

1. **View Source**: buscar `<script type="application/ld+json">` con schema `FAQPage`.
2. **Breadcrumbs**: verificar schema `BreadcrumbList` en el JSON-LD.
3. **Meta tags**: verificar `og:title`, `og:description`, `twitter:card`, `canonical` en el `<head>`.
4. **Google Rich Results Test**: pegar la URL en https://search.google.com/test/rich-results y verificar que detecta FAQPage.

### 9.4 Verificacion Responsive

| Breakpoint | Verificar |
|------------|-----------|
| 320px (mobile) | Categorias en 1 columna, hero compacto, FAQ Bot accesible |
| 768px (tablet) | Categorias en 2 columnas, Quick Links debajo del contenido |
| 1024px (desktop) | Layout completo, Quick Links en panel lateral |
| 1440px (wide) | Contenido centrado con max-width, sin overflow |

### 9.5 Footer

Verificar que el enlace "Centro de Ayuda" en el footer del sitio apunta a `/ayuda` y es visible.

---

## 10. Historial de Cambios

| Version | Fecha | Cambios |
|---------|-------|---------|
| v1.0.0 | 2026-02-28 | Version inicial: 25 FAQs semilla, 8 categorias SaaS, SEO completo, integracion soporte, Quick Links, CTA, FAQ Bot, busqueda unificada |
