# Plan de Implementacion: Pestañas "Mira la plataforma" — Clase Mundial

**Fecha de creacion:** 2026-03-19 13:00
**Ultima actualizacion:** 2026-03-19 13:00
**Autor:** IA Asistente (Claude Opus 4.6)
**Version:** 1.0.0
**Categoria:** Implementacion
**Documento fuente:** Revision directa con el Product Owner + auditoria de features existentes

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Analisis de Features Verificadas](#2-analisis-de-features-verificadas)
3. [Estructura de 7 Pestañas](#3-estructura-de-7-pestanas)
4. [Especificacion por Pestaña](#4-especificacion-por-pestana)
   - 4.1 [Casos de uso](#41-casos-de-uso)
   - 4.2 [Tu panel](#42-tu-panel)
   - 4.3 [Copiloto IA](#43-copiloto-ia)
   - 4.4 [Cobra en linea](#44-cobra-en-linea)
   - 4.5 [Tu catalogo](#45-tu-catalogo)
   - 4.6 [Publica y posiciona](#46-publica-y-posiciona)
   - 4.7 [Crece sin limites](#47-crece-sin-limites)
5. [Tabla de Correspondencia con Especificaciones Tecnicas](#5-tabla-de-correspondencia)
6. [Cumplimiento de Directrices](#6-cumplimiento-de-directrices)
7. [Verificacion](#7-verificacion)
8. [Registro de Cambios](#8-registro-de-cambios)

---

## 1. Resumen Ejecutivo

La seccion "Mira la plataforma" de la homepage es el punto de conversion mas critico del SaaS: el visitante decide aqui si el producto resuelve su problema. Las pestañas anteriores (Panel, Copiloto IA, Analiticas, Firma Digital, Constructor web) mostraban features tecnicas sin responder las 3 preguntas del usuario:

1. ¿Que hace esto por MI?
2. ¿Como resuelve MIS puntos de dolor?
3. ¿Que diferencia esto de otras soluciones?

Este plan redefine las 7 pestañas para maximizar conversion, basandose en features VERIFICADAS como existentes y funcionales en el SaaS.

---

## 2. Analisis de Features Verificadas

Todas las features mostradas en las pestañas han sido verificadas contra el codigo fuente:

| Feature | Modulo | Entidades/Servicios | Estado |
|---------|--------|---------------------|--------|
| Pagos Stripe Connect | jaraba_billing | StripeCustomerService, CheckoutSessionService | Produccion |
| Bizum (Redsys) | jaraba_billing | RedsysGatewayService con HMAC-SHA256 | Produccion |
| SEPA | jaraba_billing | BillingPaymentMethod entity, sepa_debit | Produccion |
| Catalogo productos | jaraba_agroconecta_core, jaraba_comercio_conecta | ProductAgro, ProductRetail | Produccion |
| Page Builder | jaraba_page_builder | PageContent, 11 plugins GrapesJS | Produccion |
| Content Hub | jaraba_content_hub | ContentArticle, ContentAuthor, ContentCategory | Produccion |
| SEO automatizado | jaraba_page_builder, jaraba_geo | SeoAuditService, HreflangService, SchemaOrgService | Produccion |
| Geo/Local SEO | jaraba_geo, jaraba_comercio_conecta | EeatService, LocalBusinessProfile, AnswerCapsuleService | Produccion |
| Migracion vertical | jaraba_addons | TenantVerticalService, VerticalAddonBillingService | Produccion |
| CRM | jaraba_crm | Contact, Opportunity, Activity, Company | Produccion |
| Copiloto IA | jaraba_copilot_v2, jaraba_ai_agents | 11 agentes Gen 2, StreamingOrchestratorService | Produccion |

---

## 3. Estructura de 7 Pestañas

| Pos | ID tab | Etiqueta | Icono | Reemplaza |
|-----|--------|----------|-------|-----------|
| 1 | usecases | Casos de uso | verticals/layers | (mantiene) |
| 2 | dashboard | Tu panel | analytics/chart-bar | (mantiene) |
| 3 | copilot | Copiloto IA | ai/copilot | (mantiene, icono corregido) |
| 4 | payments | Cobra en linea | commerce/cart | Firma Digital |
| 5 | catalog | Tu catalogo | commerce/store | Constructor web |
| 6 | publish | Publica y posiciona | ui/globe | Analiticas |
| 7 | grow | Crece sin limites | verticals/rocket | (nuevo) |

Razon del orden: sigue el viaje mental del visitante:
"¿Para quien es?" → "¿Que vere?" → "¿La IA me ayuda?" → "¿Puedo cobrar?" → "¿Que vendo?" → "¿Me encuentran?" → "¿Puedo crecer?"

---

## 4. Especificacion por Pestaña

### 4.1 Casos de uso

**Sin cambios** — carrusel de 6 verticales con imagen + texto + CTA. Ya implementado y validado.

### 4.2 Tu panel

**Mockup de navegador** con:
- Barra lateral: Panel, Productos, Clientes, Facturacion, Copiloto IA
- Grid de 3 tarjetas: Clientes activos (1.247), Ingresos del mes (€8.450), Satisfaccion (89%)
- Grafico SVG de actividad ascendente

**Texto que transmite**: "Cada mañana, tu panel te muestra el pulso de tu negocio."

### 4.3 Copiloto IA

**Mockup de conversacion** con contexto legal realista:
- Usuario: "Necesito redactar un contrato de prestacion de servicios..."
- IA: respuesta con clausulas RGPD, condiciones de pago, jurisdiccion
- Animacion de typing

**Texto que transmite**: "Tu asistente IA entiende tu sector y trabaja contigo."

### 4.4 Cobra en linea (NUEVO)

**Mockup de pasarela de pago** mostrando:
- Formulario de checkout integrado (estilo Stripe Embedded)
- Iconos de metodos de pago: Visa, Mastercard, Apple Pay, Google Pay, Bizum, SEPA
- Ejemplo: "Factura #2026-0034 — €150,00"
- Badge: "Pagos seguros · PCI DSS · Sin comisiones ocultas"

**Texto que transmite**: "Cobra a tus clientes desde el primer dia. Tarjeta, Bizum, SEPA o Apple Pay."

### 4.5 Tu catalogo (NUEVO)

**Mockup de tienda digital** mostrando:
- Grid de 3 productos con foto, nombre, precio, boton "Comprar"
- Sidebar con categorias y filtros
- QR de trazabilidad en un producto

**Texto que transmite**: "Tu tienda digital propia, con tu marca y tus precios."

### 4.6 Publica y posiciona (NUEVO)

**Mockup dividido en 2 areas**:
- Izquierda: Editor visual (Page Builder simplificado) con bloques arrastrables
- Derecha: Panel SEO mostrando:
  - Puntuacion SEO: 92/100
  - "Posicionamiento local: Malaga, Sevilla, Jaen"
  - "Schema.org: Negocio local verificado"
  - "Multiidioma: ES, EN, FR, DE"

**Texto que transmite**: "Publica tu web y aparece en Google. Sin programar, sin agencia."

### 4.7 Crece sin limites (NUEVO)

**Diagrama visual de expansion** mostrando:
- Centro: icono del vertical primario (ej: emprendimiento)
- Flechas hacia 3 verticales addon conectados (formacion, comercio, legal)
- Ejemplo narrativo: "Empezaste emprendiendo. Ahora vendes cursos, tienes tienda y necesitas asesoria legal. Todo en la misma plataforma."
- Badge: "10 verticales · Activa los que necesites · Sin migrar datos"

**Texto que transmite**: "Tu negocio crece. Tu plataforma crece contigo."

---

## 5. Tabla de Correspondencia con Especificaciones Tecnicas

| Especificacion | Pestañas afectadas |
|----------------|-------------------|
| ICON-CONVENTION-001 | Todas (jaraba_icon con categorias verificadas) |
| CSS-VAR-ALL-COLORS-001 | Todas (var(--ej-*) en SCSS) |
| ROUTE-LANGPREFIX-001 | CTAs con path() |
| i18n {% trans %} | Todos los textos |
| SCSS Dart Sass moderno | @use, color-mix(), var() |
| TWIG-INCLUDE-ONLY-001 | Parcial _product-demo.html.twig |
| NO-HARDCODE-PRICE-001 | Precios en mockup son ejemplos visuales, no reales |
| ZERO-REGION-001 | Parcial incluido en page--front.html.twig |
| Mobile-first | flex-wrap en tabs, grid responsive en mockups |

---

## 6. Cumplimiento de Directrices

| Directriz | Cumplimiento |
|-----------|-------------|
| Sin anglicismos | SI — todos los textos en español de España |
| Sin promesas irreales | SI — cada feature verificada contra codigo |
| Filosofia "Sin Humo" | SI — textos claros orientados al beneficio |
| ICON-DUOTONE-001 | SI — variante duotone en todos los iconos |
| Dart Sass moderno | SI — @use, color-mix() |
| Variables inyectables UI | SI — todos los colores via --ej-* |
| Textos traducibles | SI — {% trans %} en todos los textos |
| Pagos: no hardcodear precios | SI — valores de ejemplo visual |

---

## 7. Verificacion

### RUNTIME-VERIFY-001
- [ ] 7 tabs renderizadas en HTML
- [ ] 7 paneles con contenido
- [ ] 0 chinchetas (iconos verificados)
- [ ] 0 anglicismos en textos visibles
- [ ] Carrusel funcional (JS dots + auto-rotacion)
- [ ] Tabs funcionales (JS switching)
- [ ] SCSS compilado (timestamp CSS > SCSS)
- [ ] Mobile responsive (flex-wrap en tabs)

---

## 8. Registro de Cambios

| Fecha | Version | Descripcion |
|-------|---------|-------------|
| 2026-03-19 | 1.0.0 | Creacion. 7 pestañas definidas. Features verificadas contra codigo. |
