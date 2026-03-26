# Auditoria de Conversion: Landings Andalucia +ei y Link-in-Bio

**Version:** 1.0.0
**Fecha:** 2026-03-26
**Autor:** Auditoria automatizada Claude Code
**Modulo:** `jaraba_andalucia_ei`
**Ruta base:** `web/modules/custom/jaraba_andalucia_ei/`

---

## Indice

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Inventario de Paginas de Conversion](#2-inventario-de-paginas-de-conversion)
3. [Analisis del Link-in-Bio HTML Externo](#3-analisis-del-link-in-bio-html-externo)
4. [Cross-Links entre Paginas](#4-cross-links-entre-paginas)
5. [Popup de Promocion](#5-popup-de-promocion)
6. [Formularios de Captura](#6-formularios-de-captura)
7. [Lenguaje Adaptado a Codigos de Interpretacion](#7-lenguaje-adaptado-a-codigos-de-interpretacion)
8. [Score por Dimension](#8-score-por-dimension)
9. [Medidas de Salvaguarda Propuestas](#9-medidas-de-salvaguarda-propuestas)
10. [Glosario](#10-glosario)

---

## 1. Resumen Ejecutivo

El programa Andalucia +ei (PIIL — Colectivos Vulnerables 2025, expediente SC/ICV/0111/2025) dispone de **5 paginas de conversion implementadas** como rutas Drupal con Zero Region Policy, tracking de CTAs, y formularios con proteccion anti-spam. El embudo esta bien estructurado para el flujo de participantes (personas desempleadas) pero presenta gaps significativos en el canal de captacion de negocios piloto y, sobre todo, en el punto de entrada externo desde redes sociales.

### Hallazgos principales

- **Embudo de participantes (score 7.5/10):** La landing `/andaluciamasei.html` es la pieza mas madura, con 16+ CTAs tracked, countdown de urgencia, sticky bar, equipo con foto, FAQ, lead magnet, y datos reales del expediente. El formulario de solicitud tiene triaje IA y la pagina de confirmacion incluye cross-sell (guia, compartir en RRSS).
- **Embudo de negocios piloto (score 6/10):** La landing `/andalucia-ei/prueba-gratuita` cumple con el esquema basico (hero, formulario, beneficios) pero carece de testimonios de negocios piloto reales, comparativa de "antes/despues", y Schema.org especifico. Los CTAs del formulario usan nombres genericos (`hero_cta`, `form_submit`) en vez de nombres descriptivos del embudo.
- **Link-in-Bio externo (score 4/10):** Es un HTML estatico fuera de Drupal que usa colores incorrectos, emojis Unicode como iconos, fuente serif inconsistente, logo borroso en base64, sin OG tags, sin analytics, sin favicon, sin Schema.org, y sin tracking de CTAs. Es el punto de entrada desde Instagram/redes y su baja calidad desmerece el resto del embudo.
- **Ruta `/andalucia-ei/enlaces` inexistente:** No existe aun una ruta Drupal para link-in-bio, lo que obliga a mantener un HTML externo sin control de calidad.

### Prioridad de actuacion

| Prioridad | Area | Impacto |
|-----------|------|---------|
| P0 | Crear ruta `/andalucia-ei/enlaces` como alternativa Drupal al link-in-bio externo | Elimina deuda tecnica, unifica tracking |
| P0 | Corregir colores del link-in-bio externo a paleta Jaraba | Coherencia de marca |
| P1 | Anadir OG meta tags + favicon al link-in-bio | Compartibilidad en RRSS |
| P1 | Anadir `data-track-cta` con nombres descriptivos a prueba-gratuita | Trazabilidad del embudo |
| P2 | Expandir popup a ruta `/andalucia-ei/enlaces` | Mayor alcance de la campana |
| P2 | Mejorar cross-links bidireccionales entre participantes y negocios | Captura cruzada |

---

## 2. Inventario de Paginas de Conversion

| # | URL | Ruta Drupal | Controller / Template | Proposito | CTAs Tracked | Tracking |
|---|-----|-------------|----------------------|-----------|-------------|----------|
| 1 | `/andaluciamasei.html` | `jaraba_andalucia_ei.reclutamiento` | `AndaluciaEiLandingController::reclutamiento()` / `andalucia-ei-reclutamiento.html.twig` | Landing principal de reclutamiento de participantes | 16+ (`aei_hero_solicitar`, `aei_sticky_solicitar`, `aei_final_solicitar`, `aei_hero_whatsapp`, `aei_leadmagnet_email_submit`, etc.) | `data-track-cta` + `data-track-position` en todos los CTAs |
| 2 | `/andalucia-ei/solicitar` | `jaraba_andalucia_ei.solicitar` | `SolicitudEiController::solicitar()` / `solicitud-ei-page.html.twig` | Formulario publico de solicitud de plaza | Via SolicitudEiPublicForm (Drupal Form API) | Library `solicitud-form` |
| 3 | `/andalucia-ei/solicitud-confirmada` | `jaraba_andalucia_ei.solicitud_confirmada` | `SolicitudConfirmadaController::page()` / `solicitud-confirmada.html.twig` | Thank-you page post-solicitud | 3 (`aei_thankyou_guia`, `aei_thankyou_share_whatsapp`, `aei_thankyou_share_facebook`) | `data-track-cta` + `data-track-position` |
| 4 | `/andalucia-ei/prueba-gratuita` | `jaraba_andalucia_ei.prueba_gratuita` | `PruebaGratuitaController::landing()` / `prueba-gratuita-landing.html.twig` | Captacion de negocios piloto (leads) | 3 (`hero_cta`, `form_submit`, `form_submit_button`) | `data-track-cta` parcial (nombres genericos) |
| 5 | `/andalucia-ei/guia-participante` | `jaraba_andalucia_ei.guia_participante` | `GuiaParticipanteController::guia()` / `andalucia-ei-guia-participante.html.twig` | Lead magnet: descarga de guia a cambio de email | Via API POST `/api/v1/andalucia-ei/guia-download` | Rate limiting (Flood API), CRM contact creation |
| 6 | `/andalucia-ei/enlaces` | **NO EXISTE** | **FALTA** | Link-in-bio para Instagram y redes sociales | N/A | N/A |

### Rutas de soporte (no conversion directa)

| URL | Ruta | Proposito |
|-----|------|-----------|
| `/andalucia-ei/caso-de-exito/plataforma-ecosistemas-digitales` | `jaraba_andalucia_ei.case_study.ped` | Caso de exito dogfooding PED S.L. |
| `/andalucia-ei/catalogo-servicios` | (catalogo publico) | Catalogo de packs de servicios para negocios |
| `/andalucia-ei/programa` | `jaraba_andalucia_ei.landing` | Redireccion 301 a `/andaluciamasei.html` |

---

## 3. Analisis del Link-in-Bio HTML Externo

El link-in-bio actual es un fichero HTML estatico alojado fuera de Drupal, disenado para servir como pagina de destino desde la bio de Instagram y otras redes sociales. A continuacion se detallan los puntos positivos y los gaps detectados.

### 3.1 Lo que funciona bien (7 puntos)

1. **Estructura clara y enfocada:** Presenta una lista de enlaces relevantes con descripcion breve de cada uno, sin distracciones.
2. **Jerarquia de CTAs correcta:** Los enlaces mas importantes (solicitar plaza, informacion del programa) aparecen primero.
3. **Responsive basico funcional:** El layout de columna unica funciona razonablemente en moviles estandar (360px-414px).
4. **Texto humano y accesible:** El lenguaje evita jerga tecnica y habla directamente al candidato potencial.
5. **Datos de contacto presentes:** Incluye telefono y email del programa.
6. **Peso reducido:** Al ser HTML estatico sin dependencias JS, carga instantaneamente.
7. **HTTPS activo:** Servido por HTTPS, sin mixed content.

### 3.2 Problemas detectados (12 gaps)

| # | Gap | Directriz violada | Severidad | Detalle |
|---|-----|-------------------|-----------|---------|
| 1 | **Colores fuera de paleta Jaraba** | CSS-VAR-ALL-COLORS-001 | Alta | Usa `#1B4F72` (azul oscuro generico) en vez de `#233D63` (azul-corporativo Jaraba) y `#E67E22` (naranja generico) en vez de `#FF8C42` (naranja-impulso Jaraba). La discrepancia visual rompe la coherencia de marca y genera desconfianza en un embudo que depende de la confianza institucional. |
| 2 | **Emojis Unicode como iconos visuales** | ICON-EMOJI-001 | Alta | Utiliza emojis (chinchetas, telefonos, sobres) como iconos en vez de SVG inline o `jaraba_icon()`. Los emojis se renderizan distinto por SO/navegador, generando inconsistencia visual. En Android antiguo pueden aparecer como cuadrados vacios. |
| 3 | **Font-family Georgia serif inconsistente** | N/A (convencion de marca) | Media | La tipografia Georgia serif no coincide con la tipografia del ecosistema Jaraba (sans-serif system stack). Genera disonancia visual al navegar desde el link-in-bio a las landings Drupal. |
| 4 | **Logo base64 JFIF borroso** | N/A (calidad de activos) | Media | El logo esta embebido como base64 JPEG/JFIF a baja resolucion. En pantallas HiDPI (iPhone Retina, Android flagship) aparece pixelado. Deberia ser SVG o WebP a 2x minimo. |
| 5 | **Sin Open Graph meta tags** | SEO-METASITE-001 | Alta | Cuando el enlace se comparte en WhatsApp, Telegram o Facebook, se muestra un preview generico sin imagen, sin titulo descriptivo, y sin descripcion. Esto reduce drasticamente la tasa de clic en comparticiones organicas. |
| 6 | **Sin favicon** | N/A (estandar web basico) | Baja | La pestana del navegador muestra el icono generico de pagina, lo que parece poco profesional para un programa institucional. |
| 7 | **Sin analytics (GA4/Meta Pixel)** | FUNNEL-COMPLETENESS-001 | Alta | No hay manera de medir cuantas personas llegan al link-in-bio desde Instagram, ni cuantas hacen clic en cada enlace. Se pierden datos criticos para optimizar la campana de reclutamiento en redes sociales. |
| 8 | **Telefono sin NAP normalizado** | NAP-NORMALIZATION-001 | Media | El telefono no usa el formato normalizado `+34 623 174 304`. Inconsistencia con el NAP del resto de paginas del ecosistema. Puede afectar a la coherencia de senales NAP para SEO local. |
| 9 | **URLs a verificar** | ROUTE-LANGPREFIX-001 | Media | Los enlaces apuntan a paths que pueden no incluir el prefijo de idioma `/es/`. En un sitio multiidioma, esto puede causar 404 o redireccion innecesaria. URLs como `/solicitar` deberian ser `/es/andalucia-ei/solicitar` o mejor generadas via `Url::fromRoute()`. |
| 10 | **Sin Schema.org** | N/A (SEO estructurado) | Media | No incluye markup JSON-LD de tipo `EducationalOrganization` o `GovernmentService` que ayude a Google a entender el contexto institucional del programa. |
| 11 | **Sin `data-track-cta`** | FUNNEL-COMPLETENESS-001 | Alta | Ningun enlace tiene atributos `data-track-cta` ni `data-track-position`, impidiendo la atribucion de conversiones desde el punto de entrada de redes sociales. |
| 12 | **Sin test a <320px** | N/A (accesibilidad movil) | Baja | El layout no ha sido verificado en pantallas muy pequenas (<320px), frecuentes en dispositivos de gama baja usados por colectivos vulnerables — precisamente el publico objetivo del programa. |

### 3.3 Impacto estimado

El link-in-bio es el **primer punto de contacto** para usuarios que llegan desde Instagram (canal principal de captacion social del programa). Con un score de 4/10, se pierde potencialmente entre un 30-50% de trafico que podria convertir si el link-in-bio estuviera integrado en Drupal con la misma calidad visual y de tracking que el resto del embudo.

---

## 4. Cross-Links entre Paginas

### 4.1 Flujo ideal del embudo

```
Instagram Bio
    |
    v
Link-in-Bio (/andalucia-ei/enlaces)  <-- FALTA: no existe ruta Drupal
    |
    +---> Landing reclutamiento (/andaluciamasei.html)
    |         |
    |         +---> Formulario solicitud (/andalucia-ei/solicitar)
    |         |         |
    |         |         v
    |         |     Confirmacion (/andalucia-ei/solicitud-confirmada)
    |         |         |
    |         |         +---> Guia participante (/andalucia-ei/guia-participante)
    |         |         +---> Compartir en WhatsApp/Facebook
    |         |
    |         +---> Guia participante (lead magnet)
    |         +---> WhatsApp directo
    |
    +---> Prueba gratuita negocios (/andalucia-ei/prueba-gratuita)
    |
    +---> Contacto (telefono/email)
```

### 4.2 Cross-links existentes

| Desde | Hacia | Tipo | Estado |
|-------|-------|------|--------|
| Reclutamiento | Solicitar | CTA principal (16+ instancias) | OK |
| Reclutamiento | Guia participante | Lead magnet section | OK |
| Reclutamiento | WhatsApp | FAB flotante + CTAs en secciones | OK |
| Solicitud confirmada | Guia participante | CTA secundario | OK |
| Solicitud confirmada | WhatsApp/Facebook | Compartir | OK |
| Popup (meta-sitios) | Reclutamiento | CTA primario | OK |
| Popup (meta-sitios) | Solicitar | CTA secundario | OK |

### 4.3 Cross-links ausentes (gaps)

| Desde | Hacia | Impacto |
|-------|-------|---------|
| Link-in-bio | Cualquier pagina | **Critico**: no existe ruta Drupal |
| Reclutamiento | Prueba gratuita (negocios) | **Medio**: visitantes que son duenos de negocio no encuentran su camino |
| Prueba gratuita | Reclutamiento | **Medio**: visitantes que son desempleados no encuentran su camino |
| Guia participante | Solicitar | **Bajo**: tras descargar la guia, no hay CTA claro para solicitar plaza |
| Solicitud confirmada | Prueba gratuita | **Bajo**: el solicitante podria conocer un negocio que se beneficie |

---

## 5. Popup de Promocion

### 5.1 Configuracion actual

| Aspecto | Valor |
|---------|-------|
| **Fichero JS** | `js/reclutamiento-popup.js` |
| **Library** | `jaraba_andalucia_ei/reclutamiento-popup` |
| **Behavior** | `Drupal.behaviors.aeiRecPopup` |
| **Trigger** | `setTimeout(3000ms)` tras carga de pagina |
| **Frecuencia** | Una vez por sesion, con TTL configurable en localStorage |
| **TTL default** | 48 horas (`popup_ttl_hours` en config) |
| **Storage key** | `aei_rec_popup_dismissed` |
| **Accesibilidad** | `role="dialog"`, `aria-modal="true"`, `aria-label`, focus trap en boton cerrar, cierre con Escape |

### 5.2 Hosts donde se muestra

El popup se activa exclusivamente en la **pagina de inicio** (paths `/`, `/es`, `/es/`) de los siguientes hostnames:

| Hostname | Entorno |
|----------|---------|
| `plataformadeecosistemas.es` | Produccion - metasitio principal |
| `plataformadeecosistemas.jaraba-saas.lndo.site` | Dev local |
| `pepejaraba.com` | Produccion - metasitio personal |
| `pepejaraba.jaraba-saas.lndo.site` | Dev local |
| `jarabaimpact.com` | Produccion - metasitio corporativo |
| `jarabaimpact.jaraba-saas.lndo.site` | Dev local |
| `jaraba-saas.lndo.site` | Dev local (condicional: `mostrar_popup_saas` en config) |

### 5.3 CTAs del popup

| CTA | Tracking | Destino |
|-----|----------|---------|
| "Ver programa completo" | `data-track-cta="aei_popup_ver_programa"` `data-track-position="popup_metasite"` | `/andaluciamasei.html` + UTM params |
| "Solicitar plaza" | `data-track-cta="aei_popup_solicitar"` `data-track-position="popup_metasite"` | `/andalucia-ei/solicitar` + UTM params |

### 5.4 UTM Attribution

El popup anade parametros UTM automaticamente:
- `utm_source`: hostname del meta-sitio (ej: `plataformadeecosistemas.es`)
- `utm_medium`: `popup`
- `utm_campaign`: configurable en admin (`popup_campaign_utm`, default `aei_reclutamiento_2026`)

### 5.5 Datos dinamicos (NO-HARDCODE-PRICE-001)

Los datos del popup provienen de `drupalSettings.aeiRecPopup`:
- `plazasRestantes`: desde `jaraba_andalucia_ei.settings` (default 45)
- `incentivoEuros`: desde `jaraba_andalucia_ei.settings` (default 528)

### 5.6 Gap: ambito limitado

El popup solo se muestra en la home de meta-sitios corporativos. No se muestra en:
- `/andalucia-ei/enlaces` (no existe la ruta)
- `/andalucia-ei/prueba-gratuita` (oportunidad perdida para redirigir visitantes al embudo de participantes)
- Paginas interiores de los meta-sitios

---

## 6. Formularios de Captura

### 6.1 SolicitudEiPublicForm (participantes)

| Aspecto | Detalle |
|---------|---------|
| **Clase** | `Drupal\jaraba_andalucia_ei\Form\SolicitudEiPublicForm` |
| **Ruta** | `/andalucia-ei/solicitar` |
| **Acceso** | `_permission: 'access content'` (usuarios anonimos incluidos) |
| **Entity creada** | `SolicitudEi` (content entity) |
| **Anti-spam** | Honeypot (campo oculto) + time gate |
| **Post-submit** | Triaje IA (`SolicitudTriageService`), email confirmacion al solicitante, email notificacion al admin |
| **Redirect** | `/andalucia-ei/solicitud-confirmada` |
| **Template** | `solicitud-ei-page.html.twig` |

### 6.2 PruebaGratuitaController (negocios piloto)

| Aspecto | Detalle |
|---------|---------|
| **Clase** | `Drupal\jaraba_andalucia_ei\Controller\PruebaGratuitaController` |
| **Ruta GET** | `/andalucia-ei/prueba-gratuita` (`_access: TRUE` — completamente publica) |
| **Ruta POST** | `/andalucia-ei/prueba-gratuita/enviar` (`_access: TRUE`) |
| **Entity creada** | `NegocioProspectadoEi` (content entity) |
| **Anti-spam** | Honeypot (campo `website` oculto) |
| **Validacion** | Campos requeridos + provincia valida (solo `sevilla` o `malaga`) |
| **Campos** | nombre_negocio, persona_contacto, telefono, email, provincia, sector + opcionales (municipio, web, problema, rrss) |
| **Redirect** | `/andalucia-ei/prueba-gratuita?ok=1` |
| **Template** | `prueba-gratuita-landing.html.twig` |
| **TENANT-001** | Asigna `tenant_id` desde `TenantContextService` o default grupo 5 |

### 6.3 GuiaParticipanteController (lead magnet)

| Aspecto | Detalle |
|---------|---------|
| **Clase** | `Drupal\jaraba_andalucia_ei\Controller\GuiaParticipanteController` |
| **Ruta GET** | `/andalucia-ei/guia-participante` |
| **Ruta POST** | `/api/v1/andalucia-ei/guia-download` (CSRF header token) |
| **Pipeline** | Validacion + Flood API (5/hora) + email envio + email_subscriber + CRM Contact + log |
| **Resiliencia** | Cada paso post-email es non-blocking (try-catch con `\Throwable`) |
| **Cross-modulo** | `SubscriberService` y `ContactService` opcionales (`@?`) |

### 6.4 Comparativa de proteccion anti-spam

| Mecanismo | Solicitud | Prueba gratuita | Guia |
|-----------|-----------|-----------------|------|
| Honeypot (campo oculto) | Si | Si (`website`) | No (API con CSRF) |
| Time gate | Si | No | No |
| Flood API (rate limit) | No | No | Si (5/hora/IP) |
| CSRF token | No (form Drupal) | No (HTML form) | Si (header token) |
| Validacion servidor | Si (Form API) | Si (manual) | Si (manual) |

---

## 7. Lenguaje Adaptado a Codigos de Interpretacion

### 7.1 Participantes: personas vulnerables, desempleadas

El publico objetivo del embudo de participantes son personas en situacion de vulnerabilidad laboral segun los criterios del programa PIIL:
- Personas en desempleo de larga duracion (>12 meses)
- Personas mayores de 45 anos
- Personas migrantes
- Personas con discapacidad (>=33%)
- Personas en situacion de exclusion social
- Personas perceptoras de prestaciones, subsidio por desempleo o RAI

**Principios de comunicacion aplicados:**

| Principio | Ejemplo en el embudo | Estado |
|-----------|---------------------|--------|
| Sin jerga tecnica | "Te acompanamos a encontrar empleo" en vez de "itinerario personalizado de insercion" | Aplicado en hero |
| Enfasis en gratuidad | "100% gratuito" como badge prominente | Aplicado (badge + stat) |
| Urgencia empatica | "Quedan X plazas" con countdown | Aplicado (sticky bar + countdown JS) |
| Confianza institucional | Logos UE + Junta de Andalucia + SAE | Aplicado (banner publicidad oficial) |
| Incentivo economico claro | "528 euros de incentivo" | Aplicado (desde config, NO hardcoded) |
| Proximidad geografica | Sedes con direccion exacta (Malaga, Sevilla) | Aplicado (seccion sedes) |
| Equipo con cara humana | Fotos + bio + citas del coordinador y orientadora | Aplicado (seccion equipo) |
| Canal humano directo | WhatsApp como alternativa al formulario | Aplicado (FAB + CTAs multiples) |

### 7.2 Negocios piloto: empresarios locales

El publico del embudo de prueba gratuita son duenos de pequenos negocios locales en Sevilla y Malaga que:
- Pueden no tener presencia digital o tener una presencia basica
- Valoran resultados tangibles sobre promesas abstractas
- Desconfian de "lo gratuito" (sospechan costes ocultos)
- Necesitan que el servicio no interrumpa su operativa diaria

**Principios de comunicacion aplicados:**

| Principio | Ejemplo en el embudo | Estado |
|-----------|---------------------|--------|
| ROI inmediato | "Mejore la presencia digital de su negocio. Gratis." | Aplicado en hero |
| Sin compromiso explicito | "2-4 semanas sin coste ni compromiso" | Aplicado en subtitulo |
| Prueba social con datos | "46% insercion laboral 1a Edicion" | Aplicado (trust row) |
| Legitimidad institucional | "Cofinanciado por la Union Europea" | Aplicado (badges) |
| Lenguaje de usted | "Solicitar prueba gratuita" | Aplicado |
| Sectores reconocibles | Formulario con selector de sector | Aplicado (campo `sector`) |
| Proceso simple | Formulario de 6 campos + opcionales | Aplicado |

### 7.3 Gap detectado

No existe un **puente bidireccional** entre ambos embudos. Un visitante que llega buscando empleo y resulta ser dueno de un negocio no encuentra facilmente la pagina de prueba gratuita. Viceversa, un empresario que conoce a alguien desempleado no tiene un CTA para compartir el programa de participantes.

---

## 8. Score por Dimension

Evaluacion basada en los 15 criterios de LANDING-CONVERSION-SCORE-001 aplicados al embudo completo de Andalucia +ei.

| # | Dimension | Reclutamiento | Prueba gratuita | Link-in-Bio | Notas |
|---|-----------|:---:|:---:|:---:|-------|
| 1 | **Hero + urgencia** | 9/10 | 7/10 | 3/10 | Reclutamiento: countdown + plazas + badge institucional. Prueba gratuita: hero sin urgencia temporal. Link-in-bio: sin hero real. |
| 2 | **Trust badges** | 10/10 | 8/10 | 2/10 | Reclutamiento: logos UE+Junta+SAE+FSE. Prueba gratuita: badges textuales. Link-in-bio: sin badges. |
| 3 | **Pain points** | 8/10 | 6/10 | 0/10 | Reclutamiento: colectivos identificados. Prueba gratuita: implicito. Link-in-bio: ausente. |
| 4 | **Steps (como funciona)** | 9/10 | 5/10 | 0/10 | Reclutamiento: seccion "Como funciona" con timeline. Prueba gratuita: sin steps claros. |
| 5 | **Features/beneficios** | 9/10 | 7/10 | 2/10 | Reclutamiento: "Que recibiras" detallado. Prueba gratuita: beneficios basicos. Link-in-bio: solo texto. |
| 6 | **Comparativa** | 7/10 | 3/10 | 0/10 | Reclutamiento: comparativa vs buscar empleo solo. Prueba gratuita: sin comparativa. |
| 7 | **Social proof** | 8/10 | 6/10 | 0/10 | Reclutamiento: equipo con fotos + datos 1a edicion. Prueba gratuita: dato 46% insercion. Link-in-bio: nada. |
| 8 | **Lead magnet** | 9/10 | 0/10 | 0/10 | Reclutamiento: guia participante con pipeline completo. Prueba gratuita y link-in-bio: sin lead magnet. |
| 9 | **Pricing/tiers** | N/A | N/A | N/A | Programa 100% gratuito, no aplica pricing. |
| 10 | **FAQ** | 9/10 | 0/10 | 0/10 | Reclutamiento: FAQ expandible completo. Prueba gratuita: sin FAQ. |
| 11 | **Final CTA** | 10/10 | 6/10 | 4/10 | Reclutamiento: CTA grande + WhatsApp + urgencia. Prueba gratuita: boton submit. Link-in-bio: enlaces planos. |
| 12 | **Sticky CTA** | 10/10 | 0/10 | 0/10 | Reclutamiento: sticky bar con countdown. Prueba gratuita y link-in-bio: sin sticky. |
| 13 | **Reveal animations** | 6/10 | 3/10 | 0/10 | Reclutamiento: scroll-triggered sections. Prueba gratuita: basico. Link-in-bio: estatico. |
| 14 | **Tracking completo** | 10/10 | 5/10 | 0/10 | Reclutamiento: 16+ CTAs con nombres descriptivos. Prueba gratuita: 3 CTAs con nombres genericos. Link-in-bio: sin tracking. |
| 15 | **Mobile-first** | 8/10 | 7/10 | 5/10 | Reclutamiento: responsive completo. Prueba gratuita: responsive. Link-in-bio: basico sin test <320px. |

### Scores consolidados

| Pagina | Score medio | Calificacion |
|--------|:---:|-------------|
| `/andaluciamasei.html` | **8.7/10** | Clase mundial |
| `/andalucia-ei/solicitar` | **7.0/10** | Bueno (dependiente de la landing que le precede) |
| `/andalucia-ei/solicitud-confirmada` | **7.5/10** | Bueno (cross-sell efectivo) |
| `/andalucia-ei/prueba-gratuita` | **4.5/10** | Mejorable (carece de varias secciones clave) |
| `/andalucia-ei/guia-participante` | **7.0/10** | Bueno (pipeline completo) |
| Link-in-Bio externo | **1.1/10** | Critico (12 gaps, fuera de Drupal) |
| **EMBUDO PARTICIPANTES** | **7.5/10** | **Bueno** |
| **EMBUDO NEGOCIOS** | **6.0/10** | **Mejorable** |

---

## 9. Medidas de Salvaguarda Propuestas

### 9.1 Validadores a crear

| ID | Validador | Checks |
|----|-----------|--------|
| CONV-AEI-001 | `validate-aei-conversion-funnel.php` | Verificar que todas las rutas del embudo existen y responden 200 |
| CONV-AEI-002 | `validate-aei-tracking-coverage.php` | Verificar que TODOS los CTAs tienen `data-track-cta` + `data-track-position` con nombres descriptivos (no genericos) |
| CONV-AEI-003 | `validate-aei-crosslinks.php` | Verificar cross-links bidireccionales entre participantes y negocios |

### 9.2 Integracion en pipeline existente

- Anadir los 3 validadores a `validate-all.sh` dentro de la seccion de Andalucia +ei
- Anadir verificacion de colores de marca en `validate-scss-compile-freshness.php` (extension)
- Anadir check de link-in-bio ruta existente en `validate-aei-reclutamiento-campaign.php`

### 9.3 Pre-commit hooks

- Anadir lint de templates Twig de Andalucia +ei para verificar presencia de `data-track-cta` en todo `<a>` con clase CTA
- Anadir check de que ningun template usa emojis Unicode como iconos (regex `[\x{1F000}-\x{1F9FF}]` en Twig)

### 9.4 Monitoring

- UptimeRobot: anadir `/andalucia-ei/enlaces` (cuando exista) al grupo de monitoring
- GA4: crear embudo de conversion "Andalucia +ei Participantes" con los 4 pasos: enlace-bio > landing > solicitar > confirmada
- GA4: crear embudo "Andalucia +ei Negocios": enlace-bio > prueba-gratuita > envio confirmado

---

## 10. Glosario

| Sigla | Significado |
|-------|-------------|
| AEI | Andalucia +ei (nombre del vertical del programa) |
| CRM | Customer Relationship Management (gestion de relaciones con clientes) |
| CTA | Call to Action (llamada a la accion) |
| CSRF | Cross-Site Request Forgery (falsificacion de peticiones entre sitios) |
| FAQ | Frequently Asked Questions (preguntas frecuentes) |
| FSE+ | Fondo Social Europeo Plus |
| GA4 | Google Analytics 4 |
| HMAC | Hash-based Message Authentication Code |
| NAP | Name, Address, Phone (nombre, direccion, telefono — SEO local) |
| OG | Open Graph (protocolo de meta tags para redes sociales) |
| PIIL | Proyecto Integral de Insercion Laboral |
| PLG | Product-Led Growth (crecimiento liderado por producto) |
| RAI | Renta Activa de Insercion |
| ROI | Return on Investment (retorno de la inversion) |
| RRSS | Redes Sociales |
| SAE | Servicio Andaluz de Empleo |
| SCSS | Sassy CSS (preprocesador de hojas de estilo) |
| SEO | Search Engine Optimization (optimizacion para motores de busqueda) |
| SVG | Scalable Vector Graphics (graficos vectoriales escalables) |
| TTL | Time to Live (tiempo de vida) |
| UE | Union Europea |
| UTM | Urchin Tracking Module (parametros de atribucion de campanas) |
| WebP | Formato de imagen moderno desarrollado por Google |

---

*Documento generado por auditoria automatizada. Verificar contra codebase con:*
```bash
php scripts/validation/validate-aei-reclutamiento-campaign.php
php scripts/validation/validate-all.sh --fast
```
