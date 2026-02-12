# Plan de Implementacion: F3 — Visitor Journey Complete (Doc 178)
## Funnel AIDA, Lead Magnets, OAuth Social, Selector de Vertical

**Fecha de creacion:** 2026-02-12
**Ultima actualizacion:** 2026-02-12
**Autor:** IA Asistente (Claude Opus 4.6)
**Version:** 1.0.0
**Categoria:** Plan de Implementacion — Cierre de Gap F3
**Codigo:** IMPL-F3-VISITOR-JOURNEY-v1
**Fase del Plan Maestro:** F3 de 12 (PLAN-20260128-GAPS-v1, §8)
**Documento de Especificacion:** `178_Visitor_Journey_Complete_v1`

---

## Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Analisis de Estado Actual](#2-analisis-de-estado-actual)
3. [Gaps Identificados](#3-gaps-identificados)
4. [Arquitectura de la Solucion](#4-arquitectura-de-la-solucion)
5. [Componente 1: Selector de Vertical Homepage](#5-componente-1-selector-de-vertical-homepage)
6. [Componente 2: Social Auth OAuth](#6-componente-2-social-auth-oauth)
7. [Componente 3: Lead Magnet Emprendimiento](#7-componente-3-lead-magnet-emprendimiento)
8. [Componente 4: Lead Magnet AgroConecta](#8-componente-4-lead-magnet-agroconecta)
9. [Componente 5: Lead Magnet ComercioConecta](#9-componente-5-lead-magnet-comercioconecta)
10. [Componente 6: Lead Magnet ServiciosConecta](#10-componente-6-lead-magnet-serviciosconecta)
11. [Componente 7: Tracking AIDA + Visitor Detection JS](#11-componente-7-tracking-aida--visitor-detection-js)
12. [Tabla de Correspondencia con Especificaciones](#12-tabla-de-correspondencia-con-especificaciones)
13. [Checklist de Cumplimiento de Directrices](#13-checklist-de-cumplimiento-de-directrices)
14. [Plan de Verificacion](#14-plan-de-verificacion)
15. [Registro de Cambios](#15-registro-de-cambios)

---

## 1. Resumen Ejecutivo

### Proposito

Esta fase implementa el journey completo del visitante anonimo desde el primer contacto hasta la conversion, siguiendo el modelo AIDA (Awareness → Interest → Desire → Action). Es CRITICA para la estrategia PLG (Product-Led Growth) porque define cada touchpoint que convierte trafico en usuarios registrados.

### Que resuelve

| Problema Actual | Solucion F3 |
|-----------------|-------------|
| La homepage no tiene selector de vertical: el visitante no sabe a donde ir | Selector visual de 5 verticales con deteccion automatica via AvatarDetectionService |
| No hay lead magnets implementados para ninguna vertical | 4 lead magnets especificos: Calculadora, Guia PDF, Auditoria SEO, Template |
| No existe OAuth social (Google/LinkedIn) | Instalacion y configuracion de drupal/social_auth con Google y LinkedIn |
| No hay tracking AIDA del funnel de conversion | Eventos de tracking integrados con jaraba_pixels (13 tipos) |
| No hay deteccion de vertical del visitante en frontend | JS client-side con cascada UTM → keyword → geoloc → selector |

### Metricas de Exito (Doc 178 §2)

| Metrica | Target |
|---------|--------|
| Bounce rate | < 40% |
| Lead magnet conversion | > 15% |
| Visitor-to-signup rate | > 5% |
| Activation rate | > 60% |
| Trial-to-paid rate | > 25% |

---

## 2. Analisis de Estado Actual

### 2.1 Infraestructura Existente

| Componente | Ubicacion | Estado | Funcion |
|-----------|-----------|--------|---------|
| Homepage | `page--front.html.twig` | Operativa | Parciales: hero, features, stats, footer, copilot-fab |
| AvatarDetectionService | `ecosistema_jaraba_core/src/Service/` | Operativo | Deteccion cascada 4 niveles: domain → path/UTM → group → role |
| VerticalLandingController | `ecosistema_jaraba_core/src/Controller/` | Operativo | Landings por vertical individuales |
| jaraba_pixels | Modulo completo | Operativo | 13 tipos de evento, 4 plataformas (Meta, GA4, LinkedIn, TikTok) |
| Journey Engine | `jaraba_journey/` | Operativo | 7 definiciones de journey vertical-especificas |
| FreemiumVerticalLimit | `ecosistema_jaraba_core/` (F2) | Recien implementado | 45 limites por vertical+plan+feature |
| UpgradeTriggerService | `ecosistema_jaraba_core/` (F2) | Recien implementado | 5 tipos de trigger de upgrade |
| Slide-Panel | `_slide-panel.scss` | Operativo | Patron modal para acciones CRUD |
| Todos los modulos verticales | `jaraba_agroconecta_core`, `jaraba_comercio_conecta`, `jaraba_servicios_conecta` | Existen | Modulos base de cada vertical |

### 2.2 Que Falta

| Componente | Estado | Prioridad |
|-----------|--------|-----------|
| Selector visual de vertical en homepage | No existe | P0 - Critico |
| Lead magnet Emprendimiento (Calculadora Madurez) | Solo spec, no implementado | P1 |
| Lead magnet AgroConecta (Guia PDF) | No existe | P1 |
| Lead magnet ComercioConecta (Auditoria SEO) | No existe | P1 |
| Lead magnet ServiciosConecta (Template) | No existe | P1 |
| Social Auth (Google + LinkedIn) | No instalado | P2 |
| Visitor vertical detection JS | No existe | P2 |
| Tracking AIDA completo | Parcial (jaraba_pixels existe, eventos no wired) | P2 |

---

## 3. Gaps Identificados

| Gap ID | Seccion Doc 178 | Descripcion | Impacto |
|--------|----------------|-------------|---------|
| G1 | §4.1 | Homepage sin selector de vertical | Visitantes pierden orientacion, bounce rate alto |
| G2 | §3.3 | Lead magnets no implementados | Sin captura de leads por vertical, conversion = 0% |
| G3 | §3.4 | Sin OAuth social | Friccion en registro, menor tasa de signup |
| G4 | §3.1 | Sin deteccion de vertical client-side | No se pre-selecciona vertical correcta |
| G5 | §5 | Tracking AIDA incompleto | Sin datos para optimizar funnel |

---

## 4. Arquitectura de la Solucion

### 4.1 Diagrama de Flujo del Visitante

```
Visitante Anonimo
    │
    ▼
Homepage (page--front.html.twig)
    │
    ├── [JS] visitor_vertical_detection.js
    │       ├── UTM? → vertical detectada
    │       ├── Keyword? → vertical mapeada
    │       ├── Geoloc rural? → agroconecta
    │       └── Default → selector visual
    │
    ├── _vertical-selector.html.twig (NUEVO)
    │       5 cards con deteccion highlight
    │
    ▼
Landing Vertical (/empleabilidad, /agroconecta, etc.)
    │
    ├── Lead Magnet por vertical
    │       ├── Emprendimiento: Calculadora Madurez Digital
    │       ├── AgroConecta: Guia PDF sin intermediarios
    │       ├── ComercioConecta: Auditoria SEO Local
    │       └── ServiciosConecta: Template Propuesta
    │
    ├── [Tracking] lead_magnet_start → lead_magnet_complete
    │
    ▼
Registro (con OAuth Google/LinkedIn)
    │
    ▼
Onboarding + Activation (existente via jaraba_journey)
```

### 4.2 Nuevos Archivos

```
ecosistema_jaraba_theme/
├── templates/partials/
│   └── _vertical-selector.html.twig          (NUEVO)
├── scss/components/
│   └── _vertical-selector.scss                (NUEVO)
├── js/
│   └── visitor-vertical-detection.js          (NUEVO)
└── scss/main.scss                             (MODIFICAR: @use nuevo)

ecosistema_jaraba_core/
├── src/Controller/
│   └── LeadMagnetController.php               (NUEVO)
├── templates/
│   ├── lead-magnet--calculadora-madurez.html.twig (NUEVO)
│   ├── lead-magnet--guia-agro.html.twig       (NUEVO)
│   ├── lead-magnet--auditoria-seo.html.twig   (NUEVO)
│   └── lead-magnet--template-propuesta.html.twig (NUEVO)
└── ecosistema_jaraba_core.routing.yml         (MODIFICAR: 4 rutas lead)
```

---

## 5. Componente 1: Selector de Vertical Homepage

### 5.1 Parcial `_vertical-selector.html.twig`

Ubicacion: `web/themes/custom/ecosistema_jaraba_theme/templates/partials/_vertical-selector.html.twig`

Se incluye en `page--front.html.twig` entre _hero y _features:

```twig
{% include '@ecosistema_jaraba_theme/partials/_vertical-selector.html.twig' with {
  verticals: verticals,
  detected_vertical: detected_vertical
} %}
```

### 5.2 Datos de Verticales

Se inyectan desde `ecosistema_jaraba_theme.theme` via `hook_preprocess_page__front()`:

| Vertical | Icon | Color | URL | Titulo | Descripcion |
|----------|------|-------|-----|--------|-------------|
| empleabilidad | verticals/empleabilidad | azul-corporativo | /empleabilidad | Empleo | Encuentra tu trabajo ideal |
| emprendimiento | verticals/emprendimiento | naranja-impulso | /emprendimiento | Emprende | Lanza tu idea de negocio |
| agroconecta | verticals/agroconecta | verde-agro | /agroconecta | AgroConecta | Vende online sin intermediarios |
| comercioconecta | verticals/comercioconecta | naranja-impulso | /comercioconecta | Comercio | Digitaliza tu tienda |
| serviciosconecta | verticals/serviciosconecta | verde-innovacion | /serviciosconecta | Servicios | Gestiona citas y clientes |

### 5.3 Deteccion de Vertical Automatica

El parcial recibe `detected_vertical` desde `AvatarDetectionService` para resaltar la card correspondiente con clase `vertical-card--highlighted`.

### 5.4 SCSS `_vertical-selector.scss`

- Grid responsive: 5 columnas en desktop, 2+3 en tablet, 1 en movil
- Hover con `var(--ej-transition-spring)` y scale
- Card destacada con borde `var(--ej-color-impulse)` y sombra
- Iconos SVG duotone 48px
- Todo con `var(--ej-*)` CSS Custom Properties

---

## 6. Componente 2: Social Auth OAuth

### 6.1 Modulos a Instalar

```bash
lando composer require drupal/social_auth drupal/social_auth_google drupal/social_auth_linkedin
lando drush en social_auth social_auth_google social_auth_linkedin -y
```

### 6.2 Configuracion

- Google: `/admin/config/social-api/social-auth/google`
- LinkedIn: `/admin/config/social-api/social-auth/linkedin`
- Credenciales via Key module (no hardcodeadas)

### 6.3 Integracion con Formulario de Registro

Parcial `_auth-social-buttons.html.twig` con botones OAuth que se incluye en la pagina de registro.

---

## 7. Componente 3: Lead Magnet Emprendimiento

**Calculadora de Madurez Digital**

- Ruta: `/emprendimiento/calculadora-madurez` (publica, sin auth)
- Tipo: Formulario interactivo multi-paso
- Campos: 5-7 preguntas sobre madurez digital
- Resultado: Score 0-100 con recomendaciones
- Captura: Email obligatorio + nombre
- Email: Resultado + CTA "Ver mas detalles"
- Tracking: `lead_magnet_start`, `lead_magnet_complete`
- Conversion target: > 18%

---

## 8. Componente 4: Lead Magnet AgroConecta

**Guia "Vende Online sin Intermediarios"**

- Ruta: `/agroconecta/guia-vende-online` (publica, sin auth)
- Tipo: PDF descargable tras captura de email
- Campos: Email obligatorio + nombre + tipo de producto
- Resultado: PDF generado/descargado automaticamente
- Email: Link de descarga + CTA "Crea tu tienda gratis"
- Tracking: `lead_magnet_start`, `lead_magnet_complete`
- Conversion target: > 15%

---

## 9. Componente 5: Lead Magnet ComercioConecta

**Auditoria SEO Local Gratuita**

- Ruta: `/comercioconecta/auditoria-seo` (publica, sin auth)
- Tipo: Formulario rapido (URL + email)
- Campos: URL del negocio + email + nombre del negocio
- Resultado: Score SEO basico con recomendaciones
- Email: Resultado + CTA "Mejora tu SEO con ComercioConecta"
- Tracking: `lead_magnet_start`, `lead_magnet_complete`
- Conversion target: > 22%

---

## 10. Componente 6: Lead Magnet ServiciosConecta

**Template Propuesta Profesional**

- Ruta: `/serviciosconecta/template-propuesta` (publica, sin auth)
- Tipo: Documento descargable tras captura de email
- Campos: Email obligatorio + nombre + tipo de servicio
- Resultado: Template DOCX descargable
- Email: Link descarga + CTA "Gestiona tus clientes con ServiciosConecta"
- Tracking: `lead_magnet_start`, `lead_magnet_complete`
- Conversion target: > 12%

---

## 11. Componente 7: Tracking AIDA + Visitor Detection JS

### 11.1 Eventos de Tracking (Doc 178 §5)

| Evento | Propiedades | Integracion |
|--------|------------|-------------|
| `page_view` | url, referrer, vertical, utm_* | jaraba_pixels (ya existe) |
| `cta_click` | cta_id, cta_text, position | Nuevo: data-attributes en CTAs |
| `lead_magnet_start` | magnet_type, vertical | Nuevo: al abrir formulario LM |
| `lead_magnet_complete` | magnet_type, result, time_spent | Nuevo: al completar LM |
| `signup_start` | method, vertical, source | Nuevo: al iniciar registro |
| `signup_complete` | user_id, vertical, plan | Nuevo: al completar registro |

### 11.2 visitor-vertical-detection.js

Script JS que detecta la vertical del visitante anonimo:
1. UTM explicito (`?utm_vertical=agroconecta`) → prioridad maxima
2. Keyword de busqueda (mapa de keywords → vertical)
3. Geolocalizacion rural (opcional, via IP)
4. Default: muestra selector visual

El resultado se almacena en `sessionStorage` y se usa para:
- Highlight en el selector de vertical
- Pre-seleccion en formularios de registro
- Contextualizacion de mensajes del copilot

---

## 12. Tabla de Correspondencia con Especificaciones

| Seccion Doc 178 | Componente F3 | Archivo(s) | Estado |
|------------------|--------------|------------|--------|
| §2 Modelo AIDA | Tracking AIDA completo | visitor-vertical-detection.js | A implementar |
| §3.1 Awareness | Homepage + deteccion vertical | _vertical-selector.html.twig | A implementar |
| §3.2 Interest | Tracking scroll, hesitation | visitor-vertical-detection.js | A implementar |
| §3.3 Desire: LM Empleabilidad | Diagnostico Express TTV | Ya existente (parcial) | Revisar |
| §3.3 Desire: LM Emprendimiento | Calculadora Madurez Digital | LeadMagnetController + template | A implementar |
| §3.3 Desire: LM AgroConecta | Guia PDF Vende Online | LeadMagnetController + template | A implementar |
| §3.3 Desire: LM ComercioConecta | Auditoria SEO Local | LeadMagnetController + template | A implementar |
| §3.3 Desire: LM ServiciosConecta | Template Propuesta | LeadMagnetController + template | A implementar |
| §3.4 Action: Registro | OAuth Google + LinkedIn | drupal/social_auth | A instalar |
| §3.6 Conversion: Upgrade | UpgradeTriggerService | Ya implementado (F2) | Completado |
| §4.1 Homepage Universal | Selector de vertical | _vertical-selector.html.twig | A implementar |
| §5 Tracking | Eventos AIDA | jaraba_pixels integracion | A implementar |

---

## 13. Checklist de Cumplimiento de Directrices

### 13.1 Codigo

| Directriz | Cumple | Detalle |
|-----------|--------|---------|
| Rutas publicas sin auth para lead magnets | Pendiente | Controller con `_access: 'TRUE'` |
| Patron parciales Twig `{% include %}` | Pendiente | _vertical-selector reutilizable |
| Hooks nativos Drupal | Pendiente | hook_preprocess_page__front para datos |
| i18n `$this->t()` en PHP | Pendiente | Todos los textos traducibles |

### 13.2 Frontend

| Directriz | Cumple | Detalle |
|-----------|--------|---------|
| i18n `{% trans %}` en Twig | Pendiente | Textos del selector y lead magnets |
| SCSS solo `var(--ej-*)` | Pendiente | Variables inyectables con fallbacks |
| Dart Sass moderno `@use` | Pendiente | Sin `@import` |
| Mobile-first layout | Pendiente | Breakpoints progresivos |
| Iconos SVG duotone | Pendiente | `jaraba_icon('verticals', ...)` |
| Slide-panel para acciones | No aplica | Lead magnets son paginas, no modales |

### 13.3 Theming

| Directriz | Cumple | Detalle |
|-----------|--------|---------|
| Variables inyectables desde UI | Pendiente | Colores via CSS Custom Properties |
| Paginas limpias sin regiones Drupal | Pendiente | Templates propias por ruta |
| hook_preprocess_html para body classes | Pendiente | Clase por vertical detectada |

---

## 14. Plan de Verificacion

1. **Selector de Vertical**: Verificar en browser que aparece entre hero y features, 5 cards, hover animado, highlight detectado
2. **Social Auth**: Verificar que botones Google y LinkedIn aparecen en /user/register
3. **Lead Magnets**: Verificar 4 rutas publicas, formularios funcionales, captura de email, tracking de eventos
4. **Tracking**: Verificar en consola que eventos AIDA se disparan correctamente
5. **Mobile**: Verificar layout responsive en 375px, 768px, 1024px

---

## 15. Registro de Cambios

| Fecha | Version | Cambio |
|-------|---------|--------|
| 2026-02-12 | 1.0.0 | Creacion del documento |
