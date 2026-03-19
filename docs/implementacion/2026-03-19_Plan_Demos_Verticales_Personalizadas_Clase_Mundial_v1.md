# Plan de Implementacion: Demos Personalizadas por Vertical — Clase Mundial

**Fecha de creacion:** 2026-03-19 14:00
**Ultima actualizacion:** 2026-03-19 14:00
**Autor:** IA Asistente (Claude Opus 4.6)
**Version:** 1.0.0
**Categoria:** Implementacion
**Objetivo:** Elevar las 11 demos de vertical de "dashboard genérico con datos inyectados" a "experiencia personalizada que muestra el producto real del vertical"

---

## Tabla de Contenidos

1. [Diagnostico del Problema](#1-diagnostico)
2. [Arquitectura de la Solucion](#2-arquitectura)
3. [Priorizacion por Vertical](#3-priorizacion)
4. [Especificacion por Vertical](#4-especificacion-por-vertical)
   - 4.1 [JarabaLex (Legal)](#41-jarabalex)
   - 4.2 [Emprendimiento](#42-emprendimiento)
   - 4.3 [Formacion (LMS)](#43-formacion)
   - 4.4 [ServiciosConecta](#44-serviciosconecta)
   - 4.5 [ComercioConecta](#45-comercioconecta)
   - 4.6 [AgroConecta](#46-agroconecta)
   - 4.7 [Empleabilidad](#47-empleabilidad)
   - 4.8 [Andalucia +ei](#48-andalucia-ei)
   - 4.9 [Content Hub](#49-content-hub)
5. [Cambios Tecnicos Transversales](#5-cambios-tecnicos)
6. [Tabla de Correspondencia con Especificaciones](#6-correspondencia)
7. [Salvaguardas](#7-salvaguardas)
8. [Verificacion](#8-verificacion)
9. [Registro de Cambios](#9-registro)

---

## 1. Diagnostico del Problema

### 1.1 Gap critico: metricas no renderizadas

El template `demo-dashboard.html.twig` usa condiciones fijas:
```twig
{% if metrics.products_count is defined %} → Solo agro/comercio
{% if metrics.orders_last_month is defined %} → Solo agro/comercio
{% if metrics.revenue_last_month is defined %} → Solo agro + legal + servicios
{% if metrics.customers_count is defined %} → Solo agro
```

**Resultado**: 8 de 11 perfiles muestran metricas incompletas o vacias.

| Perfil | Metricas definidas | Metricas renderizadas | % visible |
|--------|-------------------|----------------------|-----------|
| lawfirm | 4 | 1 (revenue) | 25% |
| startup | 4 | 0 | 0% |
| academy | 4 | 1 (revenue) | 25% |
| servicepro | 4 | 1 (revenue) | 25% |
| jobseeker | 4 | 0 | 0% |
| socialimpact | 4 | 0 | 0% |
| creator | 4 | 0 | 0% |
| buyer | 3 | 3 | 100% |
| producer | 4 | 4 | 100% |
| winery | 4 | 4 | 100% |
| cheese | 4 | 4 | 100% |

### 1.2 Gap: dashboard generico sin narrativa vertical

El dashboard actual es identico para todos los perfiles:
- Misma estructura: Banner → Wizard → Daily Actions → Magic Moment → Metricas → Grafico → Productos → CTA
- Sin seccion de "lo que puedes hacer en este vertical" especifica
- Productos muestran iconos genericos en vez de imagenes
- El wizard muestra pasos no relevantes para la demo (suscripcion, metodos de pago, Kit Digital)

### 1.3 Gap: wizard sobredimensionado

El wizard de demo deberia tener 5 pasos max (Zeigarnik optimo). Actualmente muestra 8+ pasos incluyendo:
- Pasos globales reales (cuenta creada, vertical configurado) ✅
- Pasos de demo (explorar dashboard, generar IA, crear cuenta) ✅
- Pasos de onboarding REAL que NO aplican a demo: ✗
  - "Descubre mas verticales"
  - "Configura tus metodos de pago"
  - "Mi suscripcion"
  - "Accende Kit Digital"

---

## 2. Arquitectura de la Solucion

### 2.1 Enfoque: template unico con secciones profile-aware

NO crear 11 templates separados (mantenimiento imposible). En su lugar:

**Estrategia**: Un template `demo-dashboard.html.twig` con secciones controladas por variables del perfil, inyectadas via `DemoController` y preprocess.

```php
// En DemoController::startDemo() y demoDashboard()
$render['#vertical_context'] = $this->getVerticalContext($profile);
```

Donde `getVerticalContext()` retorna:
```php
[
  'headline' => t('Tu despacho legal, bajo control'),
  'metrics_layout' => 'legal', // legal|commerce|education|employment|impact
  'feature_highlights' => [...], // 3 features especificas del vertical
  'products_label' => t('Tus Servicios Legales'),
  'narrative_cta' => t('Gestiona tu despacho completo con IA'),
]
```

### 2.2 Metricas universales

Reemplazar las condiciones fijas por un renderizado generico:

```twig
{# ANTES (solo agro): #}
{% if metrics.products_count is defined %}...{% endif %}

{# DESPUES (universal): #}
{% for key, value in metrics %}
  <div class="demo-metric-card {{ loop.first ? 'demo-metric-card--highlight' }}">
    <span class="demo-metric-card__value">{{ value|format_metric(key) }}</span>
    <span class="demo-metric-card__label">{{ key|metric_label }}</span>
  </div>
{% endfor %}
```

Donde `format_metric` y `metric_label` son filtros Twig que:
- Formatean EUR para claves con "revenue/funding"
- Formatean porcentaje para claves con "rate/completion"
- Traducen la clave a etiqueta humana

### 2.3 Wizard acotado para demo

Filtrar los wizard steps para demo: solo mostrar los 3 steps de `demo_visitor` + los 2 globales que ya estan complete. Excluir steps que requieren accion real (pago, suscripcion).

Esto se logra sin tocar los steps existentes — el registry ya filtra por `wizardId = 'demo_visitor'`. El problema es que OTROS steps globales se inyectan. Solucion: en `getDemoWizardAndActions()`, filtrar steps que no sean relevantes.

---

## 3. Priorizacion por Vertical

Orden de implementacion segun ticket medio × TAM × urgencia:

| Sprint | Vertical | Perfil | Prioridad |
|--------|----------|--------|-----------|
| S1 | JarabaLex | lawfirm | P0 — ticket alto, 147K despachos ES |
| S1 | Emprendimiento | startup | P0 — viralidad alta |
| S1 | Formacion | academy | P0 — ingresos recurrentes |
| S2 | ServiciosConecta | servicepro | P1 — volumen autonomos |
| S2 | ComercioConecta | buyer | P1 — marketplace |
| S2 | AgroConecta | producer, winery, cheese | P1 — ya funciona bien |
| S3 | Empleabilidad | jobseeker | P2 — freemium |
| S3 | Andalucia +ei | socialimpact | P2 — nicho institucional |
| S3 | Content Hub | creator | P2 — soporte transversal |

---

## 4. Especificacion por Vertical

### 4.1 JarabaLex (Legal)

**Titular hero**: "Tu despacho legal, bajo control"

**4 metricas a mostrar**:
| Clave | Etiqueta | Formato | Icono |
|-------|----------|---------|-------|
| active_cases | Expedientes activos | Numero | verticals/legal |
| clients_managed | Clientes gestionados | Numero | business/briefcase |
| consultations_month | Consultas del mes | Numero | ui/calendar |
| revenue_last_month | Facturacion del mes | EUR | analytics/chart-bar |

**"Tu Primer Paso" (Magic Moment)**:
1. "Consulta tus expedientes" → scroll a metricas (icono: verticals/legal)
2. "Genera un informe con IA" → storytelling (icono: ai/sparkles)
3. "Explora tus servicios legales" → scroll a productos (icono: legal/law-book)

**3 productos con imagen**:
| Producto | Precio | Imagen |
|----------|--------|--------|
| Consulta Legal Inicial | €75 | catalog-legal.webp |
| Asesoria Mercantil | €200 | (generar con Nano Banana) |
| Gestion Laboral Completa | €350 | (generar con Nano Banana) |

**Seccion destacada** (nueva, entre metricas y grafico):
"**Lo que puedes hacer con JarabaLex:**"
- Gestiona expedientes con numeracion automatica (EXP-2026-NNNN)
- Analiza jurisprudencia con IA (11 agentes especializados)
- Firma documentos con validez eIDAS en toda la UE
- Genera contratos automaticamente con clausulas RGPD

**Historia IA**: Ya existe — despacho que combina tradicion juridica + eficiencia digital.

---

### 4.2 Emprendimiento

**Titular hero**: "Tu negocio, desde la idea hasta la facturacion"

**4 metricas a mostrar**:
| Clave | Etiqueta | Formato | Icono |
|-------|----------|---------|-------|
| monthly_revenue | Ingresos del mes | EUR | analytics/chart-bar |
| active_clients | Clientes activos | Numero | business/briefcase |
| projects_in_progress | Proyectos en marcha | Numero | business/target |
| conversion_rate | Tasa de conversion | Porcentaje | analytics/conversion |

**"Tu Primer Paso"**:
1. "Crea tu modelo de negocio" → storytelling (icono: business/canvas)
2. "Genera un pitch con IA" → storytelling (icono: ai/sparkles)
3. "Explora tus servicios" → scroll a productos (icono: commerce/catalog)

**Seccion destacada**:
"**Lo que puedes hacer con Emprendimiento:**"
- Construye tu modelo de negocio con plantillas por sector
- IA que analiza tu competencia y refina tu propuesta de valor
- Proyecciones financieras a 5 años con escenarios
- Diagnostico de madurez y hoja de ruta estrategica

---

### 4.3 Formacion (LMS)

**Titular hero**: "Tu academia, lista para vender cursos"

**4 metricas a mostrar**:
| Clave | Etiqueta | Formato |
|-------|----------|---------|
| courses_available | Cursos publicados | Numero |
| students_enrolled | Alumnos inscritos | Numero |
| completion_rate | Tasa de finalizacion | Porcentaje |
| revenue_last_month | Ingresos del mes | EUR |

**Seccion destacada**:
- Crea cursos con leccciones, cuestionarios y certificados
- Seguimiento de progreso en tiempo real por alumno
- Insignias y gamificacion que motivan al alumno
- Cobro directo: suscripciones, paquetes y cupones

---

### 4.4 ServiciosConecta

**Titular hero**: "Gestiona tus servicios con tu propia marca"

**4 metricas**:
| Clave | Etiqueta | Formato |
|-------|----------|---------|
| services_offered | Servicios activos | Numero |
| bookings_last_month | Reservas del mes | Numero |
| clients_active | Clientes activos | Numero |
| revenue_last_month | Ingresos del mes | EUR |

**Seccion destacada**:
- Agenda de citas con confirmacion automatica
- Presupuestos generados por IA adaptados al cliente
- Firma digital de contratos de servicio
- Reseñas verificadas que construyen tu reputacion

---

### 4.5 ComercioConecta

**Ya funciona bien** — las 3 metricas (productos, tiendas, categorias) se renderizan. Mejoras menores:
- Añadir imagen de producto en tarjetas
- Seccion destacada: "Tu tienda digital con QR, envios y analíticas"

---

### 4.6 AgroConecta

**Ya funciona bien** — las 4 metricas se renderizan. Mejoras:
- Añadir QR de trazabilidad visual en productos
- Seccion destacada: "Del campo a la mesa, con trazabilidad verificable"

---

### 4.7 Empleabilidad

**Titular hero**: "Tu carrera profesional, impulsada por IA"

**4 metricas**:
| Clave | Etiqueta | Formato |
|-------|----------|---------|
| jobs_available | Ofertas disponibles | Numero |
| applications_sent | Candidaturas enviadas | Numero |
| interviews_scheduled | Entrevistas programadas | Numero |
| profile_views | Visitas a tu perfil | Numero |

**Seccion destacada**:
- CV inteligente en 5 plantillas profesionales
- IA que detecta tus habilidades y sugiere itinerarios
- Preparacion de entrevistas con simulador IA
- Importacion directa desde LinkedIn

---

### 4.8 Andalucia +ei

**Titular hero**: "Mide y amplifica tu impacto social"

**4 metricas**:
| Clave | Etiqueta | Formato |
|-------|----------|---------|
| beneficiaries_reached | Beneficiarios alcanzados | Numero |
| active_programs | Programas activos | Numero |
| funding_secured | Financiacion captada | EUR |
| volunteer_hours | Horas de voluntariado | Numero |

---

### 4.9 Content Hub

**Titular hero**: "Publica, posiciona y conecta con tu audiencia"

**4 metricas**:
| Clave | Etiqueta | Formato |
|-------|----------|---------|
| articles_published | Articulos publicados | Numero |
| monthly_views | Visitas del mes | Numero |
| subscribers | Suscriptores | Numero |
| engagement_rate | Tasa de interaccion | Porcentaje |

---

## 5. Cambios Tecnicos Transversales

### 5.1 Renderizado universal de metricas

**Archivo**: `demo-dashboard.html.twig` y `demo-dashboard-view.html.twig`

Reemplazar las 7 condiciones fijas `{% if metrics.X is defined %}` por un bucle generico con traductor de etiquetas.

**Archivo nuevo**: `DemoMetricsFormatter.php` (servicio)
- Metodo `formatMetricValue(string $key, $value): string` → formatea EUR, %, numero
- Metodo `getMetricLabel(string $key): TranslatableMarkup` → traduce clave a etiqueta
- Metodo `getMetricIcon(string $key): array` → icono jaraba_icon por clave

### 5.2 Contexto vertical inyectado

**Archivo**: `DemoInteractiveService.php`
- Nuevo metodo `getVerticalContext(string $profileId): array`
- Retorna: headline, feature_highlights, products_label, narrative_cta

**Archivo**: `DemoController.php`
- Inyectar `#vertical_context` en render arrays de startDemo() y demoDashboard()

**Archivo**: `ecosistema_jaraba_core.module` (hook_theme)
- Añadir variable `vertical_context` a demo_dashboard y demo_dashboard_view

### 5.3 Wizard filtrado para demo

**Archivo**: `DemoController::getDemoWizardAndActions()`
- Filtrar steps del wizard para mantener max 5
- Excluir steps con routes a configuracion real (pago, suscripcion)

### 5.4 Imagenes de producto por vertical

Generar con Nano Banana 2-3 imagenes por vertical para los perfiles que no tienen (lawfirm, startup, academy, servicepro, jobseeker, socialimpact, creator).

---

## 6. Tabla de Correspondencia con Especificaciones

| Especificacion | Aplicacion |
|----------------|-----------|
| ICON-CONVENTION-001 | Iconos de metricas via getMetricIcon(), categorias verificadas |
| CSS-VAR-ALL-COLORS-001 | Metricas highlight con var(--ej-color-primary) |
| ROUTE-LANGPREFIX-001 | CTAs con path() |
| i18n {% trans %} | Etiquetas de metricas via TranslatableMarkup |
| NO-HARDCODE-PRICE-001 | Precios en productos son datos sinteticos demo, no configuracion |
| SETUP-WIZARD-DAILY-001 | Wizard acotado a 5 pasos max para demo |
| ZEIGARNIK-PRELOAD-001 | 2 globales auto-complete + 1 dashboard = 60% al entrar |
| TWIG-INCLUDE-ONLY-001 | Parciales con `only` |
| PIPELINE-E2E-001 | Service → Controller → hook_theme → Template verificado |
| ENTITY-PREPROCESS-001 | Datos inyectados via controller, no preprocess generico |

---

## 7. Salvaguardas

### 7.1 Script de validacion metricas

Nuevo script: `scripts/validation/validate-demo-metrics-render.php`
- Para cada perfil demo, verifica que TODAS las claves de demo_data tienen traduccion en DemoMetricsFormatter
- Detecta claves huerfanas (definidas en DEMO_PROFILES pero sin formato/etiqueta)

### 7.2 Test unitario renderizado metricas

Nuevo test: `tests/src/Unit/Service/DemoMetricsFormatterTest.php`
- Verifica que TODAS las claves de DEMO_PROFILES.demo_data devuelven etiqueta no vacia
- Verifica que formatos EUR/porcentaje son correctos

---

## 8. Verificacion

Para CADA vertical, verificar:
- [ ] 4 metricas visibles y con etiqueta traducida
- [ ] "Tu Primer Paso" con 3 acciones especificas del vertical
- [ ] Productos con imagenes reales (no iconos genericos)
- [ ] Titular hero personalizado
- [ ] Seccion "Lo que puedes hacer" con 4 features reales
- [ ] Wizard max 5 pasos
- [ ] 0 chinchetas
- [ ] 0 anglicismos

---

## 9. Registro de Cambios

| Fecha | Version | Descripcion |
|-------|---------|-------------|
| 2026-03-19 | 1.0.0 | Creacion. Diagnostico completo: 8 de 11 perfiles con metricas rotas. Plan de personalizacion para 9 verticales + 2 variantes agro. |
