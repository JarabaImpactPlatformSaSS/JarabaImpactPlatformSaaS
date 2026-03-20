# Plan de Publicacion: Historia JarabaLex
## 7 Canales de Distribucion — Estrategia Completa

> **Tipo:** Plan de marketing de contenidos
> **Fecha:** 20 de marzo de 2026
> **Activo:** Historia "Como JarabaLex transformo un pequeno despacho de abogados"
> **Objetivo:** Maximizar la conversion del vertical JarabaLex (prioridad #1, SOM 2,4M EUR ano 5)
> **Audiencia:** Abogados individuales y pequenos despachos (1-5 profesionales) en Espana

---

## INDICE

1. [Resumen de la estrategia](#1-resumen)
2. [Canal 1: Landing dedicada en el SaaS](#2-canal-1-landing)
3. [Canal 2: Secuencia de email (5 emails)](#3-canal-2-email)
4. [Canal 3: LinkedIn (3 posts)](#4-canal-3-linkedin)
5. [Canal 4: Social proof en pagina de precios](#5-canal-4-social-proof)
6. [Canal 5: PDF descargable (lead magnet)](#6-canal-5-pdf)
7. [Canal 6: Colegios de Abogados (partnership)](#7-canal-6-colegios)
8. [Canal 7: YouTube / Video](#8-canal-7-video)
9. [Recursos visuales generados](#9-recursos-visuales)
10. [Metricas y KPIs por canal](#10-metricas)
11. [Calendario de ejecucion](#11-calendario)
12. [Glosario de siglas](#12-glosario)

---

## 1. RESUMEN DE LA ESTRATEGIA

### Principio fundamental

Un abogado necesita ver el mensaje 5-7 veces antes de tomar accion. La historia debe estar en TODOS los canales simultaneamente, no en uno solo. Cada canal adapta el formato pero el mensaje central es identico: **"Un despacho de 2 abogados en Malaga redujo un 75% el tiempo de busqueda juridica y cancelo Aranzadi tras 14 dias de prueba."**

### Funnel de conversion multicanal

```
AWARENESS (ver el mensaje)
  ├─ LinkedIn Post #1 (datos impactantes)
  ├─ LinkedIn Post #2 (comparativa Aranzadi)
  └─ YouTube video (3 min, si se produce)
       │
CONSIDERATION (profundizar)
  ├─ Landing dedicada /jarabalex/caso-de-exito/despacho-martinez
  ├─ PDF descargable (lead magnet con gate de email)
  └─ Presentacion en Colegio de Abogados
       │
DECISION (actuar)
  ├─ Secuencia email (5 toques en 12 dias)
  ├─ Social proof en /planes/jarabalex
  └─ CTA directo → /planes/checkout/{jarabalex_pro}
```

### Presupuesto estimado

| Concepto | Coste | Notas |
|----------|-------|-------|
| Imagenes (Nano Banana) | 0 EUR | Ya generadas (6 WebP) |
| Landing dedicada (desarrollo) | 0 EUR | Implementacion interna |
| Secuencia email (config) | 0 EUR | Uso de hook_mail existente |
| LinkedIn posts | 0 EUR | Publicacion organica |
| PDF diseno | 50-150 EUR | Canva Pro o diseñador freelance |
| LinkedIn Ads (opcional) | 300-500 EUR/mes | Segmentacion abogados Espana |
| Video YouTube (futuro) | 500-1.500 EUR | Freelance motion graphics |
| Eventos colegios (futuro) | 100-300 EUR/evento | Desplazamiento + material |
| **Total minimo (canales 1-4)** | **0 EUR** | Solo tiempo de implementacion |
| **Total con ads + PDF** | **350-650 EUR/mes** | ROI esperado: 5-10x |

---

## 2. CANAL 1: LANDING DEDICADA EN EL SAAS

### Especificacion tecnica

| Campo | Valor |
|-------|-------|
| Ruta | `/jarabalex/caso-de-exito/despacho-martinez` |
| Controller | `LegalLandingController::caseStudy()` o `CaseStudyController` |
| Template | `jarabalex-case-study.html.twig` (zero-region, sin sidebar) |
| Library | `ecosistema_jaraba_theme/jarabalex-case-study` |
| SCSS | `scss/routes/jarabalex-case-study.scss` |
| Acceso | Publico (`_access: 'TRUE'`) |
| Cache | 24h, tags: `case_study_list` |
| Schema.org | `Article` + `Review` + `AggregateRating` |

### Estructura de la landing (9 secciones)

**Seccion 1: Hero**
- Imagen de fondo: `malaga-hero.webp` (vista aerea Malaga atardecer)
- Overlay oscuro semitransparente
- Titular: "Como un pequeno despacho en Malaga redujo un 75% el tiempo de busqueda juridica"
- Subtitulo: "La historia de Martinez & Asociados: de Aranzadi a JarabaLex en 14 dias"
- CTA principal: "Empieza tu prueba gratis de 14 dias" → `/planes/jarabalex`
- CTA secundario: "Leer la historia completa ↓" (scroll suave)

**Seccion 2: El despacho (contexto)**
- Imagen: `elena-despacho.webp` (Elena en su despacho)
- Texto: Resumen del dia tipico antes de JarabaLex (3 parrafos)
- Datos clave en badges: "2 abogados", "12 anos de experiencia", "Tributario + Mercantil"
- Tono: empatia, identificacion

**Seccion 3: El dolor (metricas negativas)**
- Imagen: `antes-despues.webp` (infografia antes/despues)
- 4 tarjetas con metricas "antes":
  - 45 min por busqueda juridica
  - 320 EUR/mes en Aranzadi (solo base de datos)
  - 2,5h redactando contestaciones desde cero
  - 1-2 plazos vencidos por trimestre
- Tono: urgencia, "¿te suena?"

**Seccion 4: El descubrimiento**
- Icono de LinkedIn + cita del companero
- Comparativa visual: Aranzadi 320€ vs JarabaLex 149€
- Lista de lo que incluye JarabaLex y Aranzadi no: copiloto IA, casos, calendario, boveda, facturacion, plantillas

**Seccion 5: Los 14 dias (progresion)**
- Timeline visual vertical con 7 hitos:
  - Dia 1: Primera busqueda (3 min vs 45 min)
  - Dia 2: El copiloto convence a Pablo
  - Dia 4: Primer expediente digitalizado
  - Dia 7: Alertas configuradas
  - Dia 10: Primera plantilla generada
  - Dia 12: Primera factura sin Excel
  - Dia 14: Decision de contratar
- Imagen: `elena-pablo.webp` (los dos socios mirando la pantalla)
- Cada hito con icono duotone (jaraba_icon)

**Seccion 6: Los resultados (metricas positivas)**
- Imagen: `dashboard-legal.webp` (dashboard del producto)
- Tabla comparativa antes/despues con animacion al scroll:
  - Busqueda: -75%
  - Coste herramientas: -53%
  - Plazos vencidos: -100%
  - Capacidad de casos: +40%
  - Ingresos: +32%
- Efecto de contadores animados (IntersectionObserver)

**Seccion 7: Cita testimonial**
- Blockquote grande con el "post de LinkedIn" de Elena
- Foto de perfil (generica profesional)
- Nombre, cargo, empresa
- Estrellas (5/5)
- Schema.org `Review`

**Seccion 8: Comparativa de precios**
- Reutilizar `_pricing-social-proof.html.twig` existente
- Mini-cards de 3 planes (Free, Starter 49€, Professional 149€)
- Badge "Ahorra 171€/mes vs Aranzadi" en el plan Professional
- CTA: "Empieza tu prueba gratis"

**Seccion 9: CTA final**
- Fondo azul corporativo
- Titulo: "¿Y si los proximos 14 dias cambian tu despacho?"
- Subtitulo: "Sin tarjeta de credito. Sin permanencia. Cancela cuando quieras."
- Boton naranja grande: "Probar JarabaLex gratis"
- Logos de confianza: RGPD, datos en UE, cifrado AES-256

### SEO

| Meta | Valor |
|------|-------|
| Title | "Caso de exito: Despacho de abogados reduce 75% tiempo busqueda - JarabaLex" |
| Description | "Martinez & Asociados, despacho de 2 abogados en Malaga, cancelo Aranzadi tras 14 dias con JarabaLex. Ahorro de 171€/mes y 2h/dia." |
| H1 | "Como un pequeno despacho en Malaga redujo un 75% el tiempo de busqueda juridica" |
| URL canonica | `/jarabalex/caso-de-exito/despacho-martinez` |
| Schema.org | `Article` + `Review` (AggregateRating 5/5) |
| Open Graph | Imagen: `malaga-hero.webp` |

---

## 3. CANAL 2: SECUENCIA DE EMAIL (5 EMAILS)

### Trigger

Se activa cuando un usuario completa el formulario de diagnostico legal gratuito (`/jarabalex/diagnostico-legal`) y NO se registra en las siguientes 24 horas.

### Secuencia

**Email 1 — Dia 0: "El dolor"**

| Campo | Contenido |
|-------|-----------|
| Asunto | "45 minutos buscando una sentencia del TEAC. ¿Te suena?" |
| Preview | "Elena Martinez, abogada en Malaga, perdía media mañana cada dia..." |
| Cuerpo | 3 parrafos describiendo el dia tipico de Elena. Sin CTA agresivo. Solo: "¿Te identificas? Manana te cuento que descubrio." |
| Imagen | `elena-despacho.webp` (200px ancho) |
| CTA | Texto simple: "Leer la historia completa →" (enlace a landing) |

**Email 2 — Dia 2: "El descubrimiento"**

| Campo | Contenido |
|-------|-----------|
| Asunto | "Lo que encontro esta abogada en LinkedIn a las 10 de la noche" |
| Preview | "Un post de un compañero de facultad cambió todo..." |
| Cuerpo | El descubrimiento de JarabaLex via LinkedIn. La comparativa de precios 320€ vs 149€. "Elena hizo clic en 'Empieza gratis'. Tu tambien puedes." |
| CTA | Boton: "Probar JarabaLex gratis (14 dias)" → `/planes/jarabalex` |

**Email 3 — Dia 5: "Los 3 segundos"**

| Campo | Contenido |
|-------|-----------|
| Asunto | "3 segundos vs 45 minutos: la primera busqueda de Elena" |
| Preview | "Escribio su consulta y en 1,8 segundos tenia 7 resultados..." |
| Cuerpo | La primera busqueda, el resumen IA, los key holdings, el copiloto que convencio a Pablo. Incluir la cita de Pablo: "Vale. Quiero mi propia cuenta." |
| Imagen | `busqueda-ia.webp` |
| CTA | "Haz tu primera busqueda ahora" → `/jarabalex/diagnostico-legal` |

**Email 4 — Dia 8: "Los numeros"**

| Campo | Contenido |
|-------|-----------|
| Asunto | "320€ menos al mes y 2 horas libres cada dia" |
| Preview | "Después de 14 dias, estos fueron los resultados..." |
| Cuerpo | Tabla de metricas antes/despues. Los numeros hablan solos. |
| Imagen | `antes-despues.webp` |
| CTA | "Ver planes y precios" → `/planes/jarabalex` |

**Email 5 — Dia 12: "El efecto viral"**

| Campo | Contenido |
|-------|-----------|
| Asunto | "Lo que Elena les dijo a sus colegas de profesion" |
| Preview | "Su post en LinkedIn tuvo 312 reacciones..." |
| Cuerpo | El post de LinkedIn de Elena (completo). "47 comentarios. 312 reacciones. Y tu todavia no lo has probado." |
| CTA | "Empieza hoy — 14 dias gratis, sin tarjeta" → registro JarabaLex |

### Implementacion tecnica

- Usar `QuizFollowUpCron` como patron (QUIZ-FOLLOWUP-DRIP-001)
- Nuevo servicio: `LegalCaseStudyDripService` en `jaraba_legal_intelligence`
- Trigger: Contact entity con `source = legal_diagnostico` + sin user_id vinculado
- Dedup: campo `_drip_case_study_sent` en Contact entity data
- hook_mail key: `legal_case_study_drip_{n}` (5 keys)
- Cron: ejecutar diariamente, batch 25 emails/run

---

## 4. CANAL 3: LINKEDIN (3 POSTS)

### Post 1 — "El gancho" (datos)

```
Un despacho de 2 abogados en Malaga.
Tributario y mercantil.
12 anos de experiencia.

Antes:
→ 45 min buscando una sentencia del TEAC
→ 320€/mes en Aranzadi (solo base de datos)
→ Facturas en Excel
→ Plazos calculados a mano

Despues de 14 dias con JarabaLex:
→ 3 min por busqueda (-75%)
→ 149€/mes (ahorro de 171€/mes)
→ Facturacion automatica integrada
→ Calendario con plazos en dias habiles
→ Copiloto IA que entiende derecho espanol

¿El resultado tras 3 meses?
+40% de capacidad de casos.
+32% de ingresos.
0 plazos vencidos.

La tecnologia no va a sustituir a los abogados.
Pero los abogados que usan tecnologia van a sustituir a los que no.

🔗 Enlace en comentarios

#LegalTech #Abogados #IA #DerechoTributario #JarabaLex
```

**Publicar:** Lunes 9:00 (mejor engagement profesional)
**Imagen adjunta:** `antes-despues.webp`
**Primer comentario:** Enlace a `/jarabalex/caso-de-exito/despacho-martinez`

### Post 2 — "La comparativa" (polemic controlada)

```
Aranzadi: 320€/mes.
→ Base de datos de legislacion
→ Busqueda por palabras clave
→ Sin IA
→ Sin gestion de casos
→ Sin calendario
→ Sin facturacion

JarabaLex: 149€/mes.
→ Busqueda semantica con IA (CENDOJ, BOE, TEAC, TJUE, EUR-Lex)
→ Copiloto legal en 8 modos especializados
→ Gestion de expedientes completa
→ Calendario de plazos (dias habiles automaticos)
→ Boveda documental cifrada (AES-256)
→ Facturacion integrada con Stripe
→ Plantillas de documentos con IA
→ Integracion LexNET
→ 14 dias gratis sin tarjeta

La pregunta no es si cambiar.
Es cuando.

#LegalTech #Aranzadi #AlternativaAranzadi #IA #Abogados
```

**Publicar:** Miercoles 10:00
**Imagen:** Comparativa visual 2 columnas (crear con Canva o Nano Banana)
**Riesgo:** Puede generar debate → BIEN, mas visibilidad

### Post 3 — "El testimonial" (social proof)

```
Llevo tres meses usando JarabaLex y llevo semanas queriendo compartirlo.

Somos un despacho de 2 abogados en Malaga, especializados en tributario y mercantil.

Antes pagabamos 320€/mes por Aranzadi y dedicabamos media manana a buscar jurisprudencia.

Ahora pagamos 149€/mes por JarabaLex y encontramos cualquier resolucion en segundos, con resumen de IA, legislacion citada y estado de vigencia.

Pero lo mejor no es la busqueda.

Es todo lo demas:
✓ El copiloto que entiende derecho espanol
✓ El calendario que calcula plazos en dias habiles
✓ La boveda documental cifrada
✓ Las plantillas que generan borradores en minutos
✓ La facturacion integrada

Si eres abogado y sigues usando herramientas del siglo pasado, hazte un favor: prueba los 14 dias gratis.

Yo no he vuelto a abrir Aranzadi.

— Elena Martinez, Martinez & Asociados (Malaga)

🔗 jarabalex.com
```

**Publicar:** Viernes 11:00
**Formato:** Solo texto (sin imagen → LinkedIn prioriza texto puro en feed)
**Nota:** Cuando haya un beta tester real, adaptar con datos reales y nombre real

---

## 5. CANAL 4: SOCIAL PROOF EN PAGINA DE PRECIOS

### Implementacion

Insertar en `/planes/jarabalex` (ya preparado via `_pricing-social-proof.html.twig`):

**Testimonial:**
Cita de Elena: "Encontramos cualquier resolucion en segundos, con resumen de IA, legislacion citada y estado de vigencia."

**Metricas:**
- "-75% tiempo de busqueda"
- "-53% coste vs Aranzadi"
- "+40% capacidad de casos"

**Configuracion desde Theme Settings:**
Campo `plg_social_proof_testimonials` con JSON:
```json
[{
  "quote": "Encontramos cualquier resolucion en segundos, con resumen de IA y legislacion citada.",
  "name": "Elena Martinez",
  "role": "Socia fundadora",
  "company": "Martinez & Asociados, Malaga",
  "avatar_url": ""
}]
```

---

## 6. CANAL 5: PDF DESCARGABLE (LEAD MAGNET)

### Especificacion

| Campo | Valor |
|-------|-------|
| Titulo | "Caso de exito: Como un despacho de 2 abogados transformo su practica con IA legal" |
| Formato | PDF A4, 6-8 paginas |
| Diseno | Profesional, colores JarabaLex (azul #233D63 + naranja #FF8C42) |
| Gate | Email + nombre + especialidad juridica |
| Distribucion | LinkedIn Ads (audiencia: abogados Espana, 25-55 anos) |
| CTA interno | QR + URL a `/planes/jarabalex` en cada pagina |
| Coste ads | 0,50-1,50 EUR por lead (estimacion LinkedIn Ads sector legal) |

### Contenido del PDF

1. Portada con imagen `malaga-hero.webp`
2. Resumen ejecutivo (1 pagina)
3. El problema: el dia a dia sin IA legal (1 pagina, con imagen `elena-despacho.webp`)
4. La solucion: los 14 dias de trial (2 paginas, timeline visual)
5. Los resultados: tabla antes/despues (1 pagina, con imagen `antes-despues.webp`)
6. Comparativa de planes (1 pagina, mini pricing table)
7. Contraportada con QR y CTA: "Empieza tu prueba gratis de 14 dias"

### Creacion

- Opcion A: Canva Pro (plantilla profesional, 1-2h)
- Opcion B: Freelance disenador en Fiverr (50-150 EUR, 2-3 dias)
- Opcion C: python-docx → PDF (automatizado pero menos visual)

---

## 7. CANAL 6: COLEGIOS DE ABOGADOS (PARTNERSHIP)

### Propuesta de valor para el Colegio

"Ofrecemos una sesion gratuita de 90 minutos para vuestros colegiados sobre como la IA puede ayudar a los pequenos despachos, con demostracion en vivo y caso practico real."

### Formato del evento

| Bloque | Duracion | Contenido |
|--------|----------|-----------|
| Intro | 10 min | Estado de la IA en el sector legal (datos Break the Limit 2025) |
| Caso practico | 20 min | La historia de Martinez & Asociados (narrada) |
| Demo en vivo | 30 min | Busqueda real, copiloto, expediente, calendario, factura |
| Q&A | 20 min | Preguntas de los asistentes |
| Cierre | 10 min | Oferta exclusiva colegiados (1 mes gratis adicional) |

### Colegios objetivo (Ola 1)

| Colegio | Colegiados | Ciudad | Contacto via |
|---------|-----------|--------|-------------|
| ICA Malaga | ~8.500 | Malaga | Email + evento presencial |
| ICA Sevilla | ~12.000 | Sevilla | Email + Formacion continua |
| ICA Granada | ~4.500 | Granada | Email + webinar |

### Material necesario

- Presentacion 15 slides (Canva/Google Slides)
- Demo environment con datos ficticios
- Flyers fisicos (opcional, 100 uds)
- Codigo promocional exclusivo colegiados

---

## 8. CANAL 7: YOUTUBE / VIDEO

### Especificacion

| Campo | Valor |
|-------|-------|
| Titulo | "De Aranzadi a JarabaLex: 14 dias que cambiaron un despacho" |
| Duracion | 3-4 minutos |
| Formato | Narracion en primera persona (Elena) + screencast del producto |
| Estilo | Motion graphics sobrios, tipografia Outfit |
| Musica | Instrumental suave (tipo Epidemic Sound) |
| CTA final | URL + QR + "14 dias gratis" |

### Guion resumen (3 min)

| Segundo | Visual | Audio/Naracion |
|---------|--------|----------------|
| 0-15 | Malaga atardecer + titulo | "Me llamo Elena. Soy abogada en Malaga..." |
| 15-45 | Mesa desordenada + reloj | "Mi dia empezaba buscando sentencias. 45 minutos..." |
| 45-75 | Pantalla LinkedIn + clic | "Una noche vi un post de un companero..." |
| 75-120 | Screencast busqueda JarabaLex | "Escribi mi consulta y en 3 segundos..." |
| 120-160 | Screencast copiloto + calendario | "El copiloto entiende derecho espanol..." |
| 160-190 | Tabla antes/despues animada | "En 3 meses: -75% busqueda, +32% ingresos..." |
| 190-210 | Pantalla con CTA | "14 dias gratis. Sin tarjeta. Sin permanencia." |

### Produccion

- **Opcion A:** Freelance motion graphics (500-1.500 EUR, 1-2 semanas)
- **Opcion B:** Interno con Canva Video + screencast OBS (0 EUR, 1 semana)
- **Prioridad:** Baja (Canal 7). Implementar tras validar canales 1-4

---

## 9. RECURSOS VISUALES GENERADOS

6 imagenes creadas con Nano Banana (Gemini 2.5 Flash), convertidas a WebP:

| Archivo | Contenido | Uso principal | Tamano |
|---------|-----------|---------------|--------|
| `elena-despacho.webp` | Elena en su despacho con laptop | Landing seccion 2, Email 1 | 55 KB |
| `dashboard-legal.webp` | Dashboard JarabaLex en monitor | Landing seccion 6, PDF | 48 KB |
| `antes-despues.webp` | Infografia antes vs despues | Landing seccion 3, LinkedIn Post 1, Email 4 | 60 KB |
| `elena-pablo.webp` | Elena y Pablo mirando pantalla | Landing seccion 5, Email 3 | 72 KB |
| `busqueda-ia.webp` | Close-up busqueda juridica IA | Landing seccion 5, Email 3 | 61 KB |
| `malaga-hero.webp` | Vista aerea Malaga atardecer | Landing hero, PDF portada, Video intro | 113 KB |

**Ubicacion:** `web/themes/custom/ecosistema_jaraba_theme/images/jarabalex-case-study/`

---

## 10. METRICAS Y KPIS POR CANAL

| Canal | KPI principal | Objetivo mes 1 | Herramienta medicion |
|-------|-------------|----------------|---------------------|
| Landing dedicada | Conversion CTA → registro | 5-8% de visitantes | data-track-cta + analytics |
| Email drip | Open rate / click rate | 40% open, 8% click | Drupal mail logs + tracking |
| LinkedIn organic | Engagement rate | >3% (posts profesionales) | LinkedIn Analytics |
| Social proof pricing | Impacto en conversion /planes | +10-15% vs sin social proof | A/B test |
| PDF lead magnet | Coste por lead | <1,50 EUR | LinkedIn Campaign Manager |
| Colegios | Asistentes → trial | 15-25% de asistentes | Codigo promo tracking |
| YouTube | Visualizaciones → clics | 500 views mes 1 | YouTube Studio + UTM |

---

## 11. CALENDARIO DE EJECUCION

### Semana 1 (inmediata)
- [x] Historia escrita y revisada (MD + DOCX)
- [x] 6 imagenes generadas (Nano Banana → WebP)
- [ ] Landing dedicada implementada en el SaaS
- [ ] 3 posts LinkedIn redactados y programados

### Semana 2
- [ ] Social proof actualizado en /planes/jarabalex con cita Elena
- [ ] Secuencia email configurada (5 emails)
- [ ] PDF disenado (Canva o freelance)

### Semana 3
- [ ] LinkedIn Post 1 publicado (lunes)
- [ ] LinkedIn Post 2 publicado (miercoles)
- [ ] LinkedIn Post 3 publicado (viernes)
- [ ] Landing verificada en Google Search Console

### Mes 2
- [ ] LinkedIn Ads activados (PDF como lead magnet)
- [ ] Contacto con ICA Malaga para primer evento
- [ ] Primer informe de metricas (conversion landing, email rates)

### Mes 3
- [ ] Evento en ICA Malaga (o webinar)
- [ ] Video YouTube producido y publicado (si metricas justifican)
- [ ] Iteracion basada en datos: que canal convierte mas

---

## 12. GLOSARIO DE SIGLAS

| Sigla | Significado |
|-------|------------|
| **AES-256** | Advanced Encryption Standard 256-bit — cifrado de documentos |
| **B2B2C** | Business-to-Business-to-Consumer — modelo de venta indirecta |
| **CTA** | Call to Action — boton o enlace que invita a la accion |
| **ICA** | Ilustre Colegio de Abogados |
| **KPI** | Key Performance Indicator — indicador clave de rendimiento |
| **LCIS** | Legal Coherence Intelligence System — sistema de validacion juridica |
| **MRR** | Monthly Recurring Revenue — ingreso recurrente mensual |
| **QR** | Quick Response — codigo bidimensional escaneable |
| **RGPD** | Reglamento General de Proteccion de Datos |
| **ROI** | Return on Investment — retorno de la inversion |
| **SaaS** | Software as a Service — software por suscripcion en la nube |
| **SEO** | Search Engine Optimization — optimizacion para motores de busqueda |
| **SOM** | Serviceable Obtainable Market — mercado capturado realistamente |
| **UTM** | Urchin Tracking Module — parametros de seguimiento en URLs |
| **WebP** | Formato de imagen web de Google (compresion superior a JPEG) |

---

*Plan generado el 20 de marzo de 2026.*
*Todos los recursos visuales creados con Nano Banana (Gemini 2.5 Flash Image).*
*Jaraba Impact Platform — "Sin Humo".*
