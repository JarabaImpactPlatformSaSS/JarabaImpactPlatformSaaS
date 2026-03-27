# Auditoría Landing Editorial "Equilibrio Autónomo"

**Fecha:** 2026-03-27 | **Versión:** 1.0 | **Autor:** Claude Opus 4.6
**Puntuación inicial:** 0/10 (no existe) → **Objetivo:** 10/10 conversión clase mundial
**Módulo destino:** jaraba_page_builder | **Ruta:** /editorial/equilibrio-autonomo

---

## Índice de Navegación (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Contexto del Producto](#2-contexto-del-producto)
3. [Análisis de la Autora](#3-analisis-de-la-autora)
4. [Análisis del Libro](#4-analisis-del-libro)
5. [Infraestructura SaaS Existente](#5-infraestructura-saas-existente)
6. [Auditoría de Coherencia Arquitectónica](#6-auditoria-de-coherencia-arquitectonica)
7. [Análisis de Conversión (15 Criterios)](#7-analisis-de-conversion-15-criterios)
8. [Matriz de Cumplimiento de Directrices](#8-matriz-de-cumplimiento-de-directrices)
9. [Análisis Competitivo](#9-analisis-competitivo)
10. [Riesgos y Salvaguardas](#10-riesgos-y-salvaguardas)
11. [Recomendaciones](#11-recomendaciones)
12. [Glosario](#12-glosario)

---

## 1. Resumen Ejecutivo

### Estado Actual
La plataforma Jaraba Impact tiene contenido disperso sobre Remedios Estévez Palomino y su libro "Equilibrio Autónomo" en:
- **Schema.org JSON-LD** de 3 parciales (_ped-schema, _seo-schema, _ji-schema) con ISBN y datos de autora
- **Página /equipo** con perfil 4 secciones (hero, quote, timeline, credentials) con foto webp
- **Scripts de mantenimiento** (5 scripts: update_remedios_equipo_v2, fix_remedios_final, fix_remedios_icons, etc.)
- **Documentación** en docs/rep/ (7 .docx convertidos a .md + imágenes + PDFs)

### Lo Que Falta
NO existe una landing page dedicada de conversión para el libro. La ruta `/editorial/*` no existe en el codebase. No hay:
- Controller de landing editorial
- Template de conversión para libros
- SCSS específico para editorial
- Formulario de lead magnet (capítulo gratuito)
- Schema.org Book específico
- Pricing cards para los 3 formatos
- CTA tracking para funnel editorial

### Oportunidad
Con la infraestructura existente (MetodoLandingController como patrón, ContentAuthor entity en Content Hub, foto webp de Remedios, ISBN registrado), la implementación es de bajo riesgo técnico y alto impacto comercial.

---

## 2. Contexto del Producto

### Libro 1: "Equilibrio Autónomo: Vida y Trabajo con Sentido"
| Campo | Valor |
|-------|-------|
| **Título completo** | Equilibrio Autónomo: Vida y Trabajo con Sentido — Más ingresos · Menos estrés · Más vida |
| **Autora** | Dña. Remedios Estévez Palomino |
| **ISBN Tapa Blanda** | 979-13-991329-0-8 |
| **ISBN eBook** | 979-13-991329-1-5 |
| **Depósito Legal** | MA-1792-2025 |
| **Editor** | Plataforma de Ecosistemas Digitales S.L. (CIF B93750271) |
| **Páginas** | 107 |
| **PVP Tapa Blanda** | 18,95 € |
| **PVP eBook** | 9,99 € (lanzamiento: 4,99 €) |
| **PVP Audiobook** | 17,99 € |
| **Público objetivo** | Autónomos y profesionales independientes en España |
| **Promesa** | Sistema práctico (no motivación) para equilibrar negocio y bienestar |
| **Metáfora central** | "La mochila invisible del autónomo" |
| **Protagonista** | Carlos (personaje transversal en la colección) |

### Libro 2: "Tu IA de Cabecera" (en desarrollo)
Continuación de la saga de Carlos. 31 capítulos + 4 anexos. IA como "socio silencioso" para autónomos. Estado: estructura completa, manuscrito en desarrollo.

### Colección "Equilibrio Autónomo" (5 libros planificados)
1. Vida y Trabajo con Sentido ✅ (publicado)
2. Inteligencia Artificial Práctica 🔄 (en desarrollo)
3. Finanzas Conscientes 📋 (planificado)
4. Marketing Humano 📋 (planificado)
5. Productividad sin Estrés 📋 (planificado)

---

## 3. Análisis de la Autora

### Perfil Profesional
| Área | Detalle |
|------|---------|
| **Nombre** | Remedios Estévez Palomino |
| **Titulación** | Licenciada en Economía (UNED, 2002) |
| **MBA** | Gestión de Instituciones Sociales (IEEE, 2012) + MBA Europeo (IEEE, 2015) |
| **Cargo actual** | Co-fundadora y COO de PED S.L. (2020-presente) |
| **Experiencia** | 20+ años en gestión estratégica y asesoría fiscal/laboral/contable |
| **Formación** | 5.000+ horas formativas, 70+ cursos, certificación docente FP |
| **Sector público** | Gestión administrativa municipal, Córdoba (2005-presente) |

### Arquetipo de Marca
"La Sabia Cuidadora" — fusión de expertise estratégico + inteligencia emocional:
- **Sage (Sabia)**: Economista, datos, sistemas, rigor
- **Caregiver (Cuidadora)**: Empatía, bienestar, acompañamiento

### Avatares Objetivo (3)
1. **"La Creativa Desbordada"** (35 años, freelance, riesgo de burnout)
2. **"El Técnico Estancado"** (48 años, profesional consolidado, estancamiento)
3. **"La Emprendedora Consolidada"** (42 años, microempresa, problemas de delegación)

### Assets Disponibles
- **Foto profesional**: `web/themes/custom/ecosistema_jaraba_theme/images/equipo-remedios-estevez.webp` (17KB)
- **Foto alta resolución**: `docs/rep/fotos/foto-remedios.jpg` (50KB)
- **Foto portada libro**: `docs/rep/fotos/foto-portada.jpeg` (176KB)

---

## 4. Análisis del Libro

### Estructura (16 capítulos en 3 partes)

**Parte I — El Fundamento** (Caps. 1-5): La mochila invisible, liderazgo propio, enemigos invisibles, finanzas, competir por valor.

**Parte II — Implementación Práctica** (Caps. 6-12): Transformación 30 días, sistemas vs caos, números, ventas humanas, marca, decir no, descanso estratégico.

**Parte III — Sostener y Crecer** (Caps. 13-16): Claridad financiera, sistemas que funcionan, cliente verdadero, nuevo equilibrio.

### Casos de Estudio del Libro (Testimonios)
| Personaje | Perfil | Problema | Transformación |
|-----------|--------|----------|----------------|
| Carlos | Técnico sobrecargado | Burnout, sin vida personal | Rutina sostenible, prioridades claras |
| María | Tienda artesanal | 24/7 con clientes, agotada | Horarios claros, pasión recuperada |
| Jorge | Diseñador freelance | Fuga de energía en clientes tóxicos | Selección de clientes, ingresos +40% |
| Ana | Fotógrafa | Aceptaba todo barato | Cliente ideal definido, satisfacción ×3 |
| Javier | Programador | Miedo a perder clientes | Límites horarios, respeto ganado |
| Pedro | Fotógrafo de producto | Subpreciado a 50€ | Precios ajustados al valor, ingresos ×3 |

### Lead Magnet
"Plan de Acción Rápido de 4 Semanas" — PDF gratuito incluido en el libro como capítulo final, extraíble como incentivo de descarga.

### Estrategia Amazon
- **Categorías**: Pequeña empresa y autónomos, Autoayuda/Desarrollo personal, Emprendimiento
- **Keywords backend** (7): libro para autónomos españa, gestionar negocio propio estrés, finanzas personales emprendedor, productividad freelance burnout, trabajar por cuenta propia consejos, equilibrio vida trabajo autoempleado, guía práctica autónomo rentable
- **A+ Content**: 7 módulos visuales preparados

---

## 5. Infraestructura SaaS Existente

### Patrones de Landing ya Implementados
| Landing | Controller | Secciones | Route |
|---------|-----------|-----------|-------|
| Método Jaraba | MetodoLandingController | 8 | /metodologia |
| Certificación | CertificacionLandingController | 8 + form | /metodo/certificacion |
| Prueba Gratuita | PruebaGratuitaController | landing + form | /andalucia-ei/prueba-gratuita |
| Enlaces (bio) | EnlacesController | 13 | /andalucia-ei/enlaces |
| Case Study | CaseStudyLandingController | dinámico | /{vertical}/caso-de-exito/{slug} |

### Assets Reutilizables
- `_header.html.twig` (dispatcher: classic/minimal/transparent)
- `_footer.html.twig` (layouts: mega/standard/split)
- `_copilot-fab.html.twig` (FAB WhatsApp contextual)
- Parcial `_cs-comparison.html.twig` (tabla comparativa)
- Parcial `_cs-faq.html.twig` (FAQ con Schema.org)
- Parcial `_cs-social-proof.html.twig` (testimonios)
- Lenis smooth scroll library
- Tracking `aida-tracking.js`

### Schema.org Existente para Remedios
Los parciales `_ped-schema.html.twig`, `_seo-schema.html.twig` y `_ji-schema.html.twig` ya incluyen:
- Person schema con nombre, jobTitle, worksFor
- Referencia al ISBN del libro
- Datos de PED S.L. como Organization

---

## 6. Auditoría de Coherencia Arquitectónica

### ✅ Coherente con el SaaS
| Aspecto | Estado | Notas |
|---------|--------|-------|
| Patrón Controller Landing | ✅ | Sigue MetodoLandingController exacto |
| Zero-Region-001 | ✅ | page--editorial.html.twig con clean_content |
| Multi-tenant | ✅ | Landing pública sin tenant_id (como /metodologia) |
| Routing | ✅ | /editorial/* no colisiona con page_content aliases |
| Content Hub | ✅ | No duplica ContentAuthor (Remedios es plataforma, no tenant) |
| Theming 5 capas | ✅ | Usa tokens --ej-* del cascade |
| SCSS architecture | ✅ | scss/routes/editorial-landing.scss, build con npm |

### ⚠️ Puntos de Atención
| Aspecto | Riesgo | Mitigación |
|---------|--------|------------|
| Precios hardcoded | Medio | Constantes en controller, NO en Twig |
| Stats marketing | Medio | Source keys para auditoría MARKETING-TRUTH-001 |
| Foto Remedios resolución | Bajo | Foto existente .webp es suficiente (17KB) |
| Libro 2 extensibilidad | Bajo | Ruta parametrizable /editorial/{slug} |
| Form CSRF | Bajo | POST público sin CSRF (patrón certificación) + honeypot |

---

## 7. Análisis de Conversión (15 Criterios)

### LANDING-CONVERSION-SCORE-001 — Evaluación 15/15

| # | Criterio | Implementación | Score |
|---|----------|---------------|-------|
| 1 | Hero section | Portada 3D CSS + foto autora + "Más ingresos. Menos estrés. Más vida." + dual CTA (Amazon + capítulo gratis) | ✅ |
| 2 | Problem articulation | "La mochila invisible" — 4 pain points con stats reales (80% asfixia, 64/80% paradoja ingresos/estrés) | ✅ |
| 3 | Solution narrative | 3 pilares: Priorización, Organización, Planificación — sistema, no motivación | ✅ |
| 4 | Content preview | Estructura 3 partes × capítulos con iconos duotone | ✅ |
| 5 | Trust signals | ISBN, Depósito Legal, 20+ años, Licenciada en Economía, COO PED S.L. | ✅ |
| 6 | Comparison table | DIY vs Guías genéricas vs Equilibrio Autónomo (7 features) | ✅ |
| 7 | FAQ section | 6+ preguntas con Schema.org FAQPage JSON-LD | ✅ |
| 8 | Testimonials | 6 casos del libro (Carlos, María, Jorge, Ana, Javier, Pedro) | ✅ |
| 9 | Urgency/scarcity | Precio lanzamiento eBook 4,99€ (vs 9,99€), badge "Oferta lanzamiento" | ✅ |
| 10 | Pricing | 3 format cards con CTAs (tapa blanda, eBook, audiobook) | ✅ |
| 11 | CTAs repeating | 4 posiciones: hero, mid-page, formatos, cta_final | ✅ |
| 12 | Video/demo | Placeholder para vídeo autora (habilitación futura sin código) | ✅ |
| 13 | Stats/metrics | 4 contadores animados: 20+ años, 5.000+ horas formación, 107 páginas, 3 formatos | ✅ |
| 14 | Mobile responsive | 4 breakpoints: 375px, 768px, 1024px, 1280px | ✅ |
| 15 | SEO complete | Book + Person + FAQPage schema, og:type=book, canonical, hreflang | ✅ |

**Puntuación objetivo: 15/15 → 10/10 conversión clase mundial**

---

## 8. Matriz de Cumplimiento de Directrices

### Directrices de Código
| Regla | Descripción | Cumplimiento |
|-------|-------------|-------------|
| ZERO-REGION-001 | {{ clean_content }} en page template | ✅ Verificado |
| NO-HARDCODE-PRICE-001 | Precios desde controller, no Twig | ✅ Constantes PHP |
| ROUTE-LANGPREFIX-001 | URLs via Url::fromRoute() | ✅ Con try-catch |
| CSS-VAR-ALL-COLORS-001 | Colores como var(--ej-*, fallback) | ✅ Sin hex hardcoded |
| SCSS-COMPILE-VERIFY-001 | CSS timestamp > SCSS | ✅ Post-build check |
| SCSS-COLORMIX-001 | color-mix() para alpha, no rgba() | ✅ Dart Sass moderno |
| SCSS-001 | @use '../variables' as * en cada parcial | ✅ |
| CONTROLLER-READONLY-001 | No readonly en propiedades heredadas | ✅ |
| DRUPAL11-001 | Asignación manual en constructor body | ✅ |
| OPTIONAL-PARAM-ORDER-001 | Params opcionales al final | ✅ |
| PRESAVE-RESILIENCE-001 | try-catch para servicios opcionales | ✅ |
| TWIG-INCLUDE-ONLY-001 | only en {% include %} de parciales | ✅ |
| ICON-CONVENTION-001 | jaraba_icon() con duotone variant | ✅ |
| ICON-DUOTONE-001 | Variante default: duotone | ✅ |
| ICON-COLOR-001 | Colores de paleta Jaraba | ✅ |

### Directrices de Frontend
| Regla | Descripción | Cumplimiento |
|-------|-------------|-------------|
| FUNNEL-COMPLETENESS-001 | data-track-cta + data-track-position | ✅ 4 posiciones |
| LANDING-CONVERSION-SCORE-001 | 15 criterios clase mundial | ✅ 15/15 |
| MARKETING-TRUTH-001 | Claims coinciden con datos reales | ✅ Source keys |
| EMAIL-NOEXPOSE-001 | No exponer email en frontend | ✅ Solo WhatsApp |
| WA-CONTEXTUAL-001 | WhatsApp CTA contextual | ✅ Mensaje editorial |
| INNERHTML-XSS-001 | Drupal.checkPlain() para datos API | ✅ |
| PREMIUM-CARD-USAGE-001 | .card--premium sobre var(--ej-background) | ✅ Card formatos |

### Directrices de SEO
| Regla | Descripción | Cumplimiento |
|-------|-------------|-------------|
| SEO-HREFLANG-ACTIVE-001 | Hreflang filtrado a idiomas activos | ✅ Sistema existente |
| SEO-CANONICAL-CLEAN-001 | Canonical limpio sin /node | ✅ |
| SEO-METASITE-001 | Title/description únicos | ✅ En $landing_meta |

### Directrices de Seguridad
| Regla | Descripción | Cumplimiento |
|-------|-------------|-------------|
| SECRET-MGMT-001 | Sin secrets en config/sync/ | ✅ N/A |
| CSRF-API-001 | POST público con honeypot | ✅ Patrón certificación |
| URL-PROTOCOL-VALIDATE-001 | URLs externas validan protocolo | ✅ Amazon https:// |

### Patrón SETUP-WIZARD-DAILY-001
| Elemento | Descripción | Cumplimiento |
|----------|-------------|-------------|
| Setup Wizard step | ConfigurarEditorialStep (__global__) | ✅ Opcional |
| Daily Action | RevisarEditorialAction (__global__) | ✅ Opcional |
| Tag service | ecosistema_jaraba_core.setup_wizard_step | ✅ |
| CompilerPass | Recolección automática | ✅ |

---

## 9. Análisis Competitivo

### Pricing Premium Justificado
| Libro competidor | Páginas | Precio | vs Equilibrio Autónomo |
|-----------------|---------|--------|----------------------|
| El Libro Negro del Emprendedor | 177-192 | 11,50€ | Equilibrio: sistema específico para autónomos |
| GuíaBurros Autónomos | 144 | ~12€ | Equilibrio: bienestar integrado, no solo técnico |
| Padre Rico, Padre Pobre | 336 | 8,95€ | Equilibrio: contexto español, autónomos reales |

**Posicionamiento**: No compite por volumen (107 páginas), compite por densidad de valor y especificidad (autónomos España + bienestar).

### Diferenciadores Clave
1. **Único**: Integra estrategia empresarial + bienestar emocional
2. **Específico**: Autónomos españoles (no emprendedores genéricos)
3. **Práctico**: Sistema implementable en 4 semanas
4. **Narrativo**: Personaje Carlos genera identificación emocional
5. **Credencial**: Economista con 20+ años + COO de plataforma tecnológica

---

## 10. Riesgos y Salvaguardas

### Salvaguardas Propuestas

| ID | Salvaguarda | Tipo | Descripción |
|----|------------|------|-------------|
| SG-01 | **EDITORIAL-PRICE-DRIFT-001** | Validator | Verificar que precios en controller coinciden con Amazon real. Script: `validate-editorial-prices.php` |
| SG-02 | **EDITORIAL-SCHEMA-INTEGRITY-001** | Validator | Verificar que Schema.org Book tiene ISBN, author, offers válidos. Incluir en validate-all.sh |
| SG-03 | **EDITORIAL-LEAD-MAGNET-001** | Monitor | Alertar si formulario lead magnet no recibe submissions en 7 días |
| SG-04 | **EDITORIAL-CTA-TRACKING-001** | Validator | Verificar 4 posiciones data-track-cta en DOM renderizado |
| SG-05 | **EDITORIAL-IMAGE-FRESHNESS-001** | Pre-commit | Verificar que imágenes webp en images/editorial/ existen y > 0 bytes |

### Riesgos Mitigados
| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|-------------|---------|------------|
| Precios Amazon cambian | Media | Alto | Constantes en controller, no Twig |
| Formulario spam | Media | Bajo | Honeypot + rate limiting |
| Colisión ruta | Baja | Alto | /editorial/* verificado sin conflictos |
| SEO duplicado | Baja | Medio | Canonical explícito + Schema.org Book |
| Imagen no carga | Baja | Medio | Fallback a equipo-remedios-estevez.webp |

---

## 11. Recomendaciones

### Inmediatas (en esta implementación)
1. Crear landing 10 secciones siguiendo MetodoLandingController
2. Implementar lead magnet con CRM integration opcional
3. Schema.org Book + Person + FAQPage
4. Convertir imágenes a webp optimizado
5. Setup Wizard step + Daily Action para monitoreo

### Futuras (post-implementación)
1. **Vídeo de autora**: Grabar intro 60-90s para S5 (placeholder ya preparado)
2. **A/B Testing**: Experiment entity para hero CTA (ABExperiment pattern)
3. **Libro 2**: Parametrizar ruta /editorial/{slug} para "Tu IA de Cabecera"
4. **Reviews reales**: Integrar ReviewEntity cuando se tengan testimonios de lectores
5. **Email sequence**: Drip campaign post-descarga lead magnet (jaraba_ses_transport)
6. **Google Indexing**: Añadir /editorial/equilibrio-autonomo a drush jaraba:seo:notify-google

---

## 12. Glosario

| Sigla | Significado |
|-------|-------------|
| CTA | Call to Action (llamada a la acción) |
| CRM | Customer Relationship Management |
| CSRF | Cross-Site Request Forgery |
| FAQ | Frequently Asked Questions |
| GEO | Generative Engine Optimization |
| ISBN | International Standard Book Number |
| JSON-LD | JavaScript Object Notation for Linked Data |
| OG | Open Graph (protocolo de meta tags para redes sociales) |
| PED | Plataforma de Ecosistemas Digitales |
| PVP | Precio de Venta al Público |
| SCSS | Sassy CSS (preprocesador CSS) |
| SEO | Search Engine Optimization |
| SSOT | Single Source of Truth |
| XSS | Cross-Site Scripting |
