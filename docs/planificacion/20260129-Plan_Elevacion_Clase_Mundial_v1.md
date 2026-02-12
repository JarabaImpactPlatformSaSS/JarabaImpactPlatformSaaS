# Plan de Elevación a Clase Mundial - Page Builder SaaS

**Fecha:** 2026-01-29  
**Versión:** 1.0  
**Autor:** Equipo Técnico Jaraba  
**Estimación Total:** 145 horas (~€11,600)

---

## 1. Visión

Elevar el módulo `jaraba_page_builder` de un **85% de cumplimiento** a un **95%+ de clase mundial**, incluyendo funcionalidades Enterprise que diferencian la plataforma de competidores como Wix, Squarespace y Webflow.

---

## 2. Estado Actual Post-Sesión

| Componente | Estado | Cobertura |
|------------|--------|-----------|
| Sitemap XML Dinámico | ✅ Implementado | 100% |
| Hreflang Multi-idioma | ✅ Implementado | 80% |
| A/B Testing Core | ✅ Implementado | 80% |
| Analytics Service | ✅ Implementado | 40% |
| Handlers Drupal | ✅ Implementado | 100% |

---

## 3. Gaps Identificados para Clase Mundial

### 3.1 Prioridad Alta (Diferenciador Enterprise)

#### Gap A: UI Visual de Experimentos A/B
- **Esfuerzo:** 20h
- **Impacto:** Diferenciador competitivo
- **Entregables:**
  - Dashboard de experimentos con gráficos de conversión
  - Wizard de creación de variantes visual
  - Notificaciones cuando se alcanza significancia estadística
  - Integración en Site Builder frontend

#### Gap B: Botón "Generar con IA" en FormBuilder
- **Esfuerzo:** 15h
- **Impacto:** UX Premium
- **Entregables:**
  - Botón per-field en todos los formularios de bloques
  - Prompts contextuales por tipo de bloque
  - Integración con Brand Voice del tenant
  - Preview antes de aplicar

---

### 3.2 Prioridad Media (ROI Visible)

#### Gap C: Analytics Dashboard Integrado
- **Esfuerzo:** 25h
- **Impacto:** Medición ROI para tenants
- **Entregables:**
  - Integración Microsoft Clarity para heatmaps
  - Métricas de rendimiento por bloque
  - Conversiones por CTA
  - Export CSV/Excel

#### Gap D: Bloques Premium Faltantes (6)
- **Esfuerzo:** 30h
- **Impacto:** Catálogo completo
- **Entregables:**
  - 3D Card Stack (Aceternity)
  - Spotlight Cards (Magic UI)
  - Video Background Hero
  - Animated Testimonials Grid
  - Feature Comparison Table
  - Interactive Timeline

---

### 3.3 Prioridad Normal (Mercado Internacional)

#### Gap E: i18n UI Avanzado
- **Esfuerzo:** 20h
- **Impacto:** Expansión internacional
- **Entregables:**
  - UI de gestión de traducciones de páginas
  - Workflow traducción asistida por IA
  - Selector de idioma en editor
  - Previsualización multi-idioma

#### Gap F: CSS Crítico Automático
- **Esfuerzo:** 20h
- **Impacto:** Core Web Vitals
- **Entregables:**
  - Extracción CSS above-the-fold
  - Lazy loading de estilos non-critical
  - Integración con rankings de Google

---

### 3.4 Prioridad Baja (Productividad)

#### Gap G: Diff Visual de Revisiones
- **Esfuerzo:** 15h
- **Impacto:** Productividad editores
- **Entregables:**
  - UI comparación lado a lado
  - Rollback con un clic
  - Historial visual de cambios

---

## 4. Orden de Implementación

| Fase | Gap | Semana | Dependencias |
|------|-----|--------|--------------|
| 1 | B: Botón "Generar con IA" | S1 | Ninguna |
| 2 | A: UI Experimentos A/B | S1-S2 | A/B Core (✅ listo) |
| 3 | C: Analytics Dashboard | S2-S3 | AnalyticsService (✅ listo) |
| 4 | D: Bloques Premium | S3-S4 | Ninguna |
| 5 | E: i18n UI | S4-S5 | HreflangService (✅ listo) |
| 6 | F: CSS Crítico | S5 | Ninguna |
| 7 | G: Diff Revisiones | S6 | Revisiones (✅ estructura lista) |

---

## 5. Especificaciones Técnicas por Gap

### Gap B: Botón "Generar con IA"

**Archivos a crear/modificar:**

| Archivo | Acción |
|---------|--------|
| `js/ai-field-generator.js` | Crear cliente JS |
| `FormBuilderService.php` | Extender para añadir botón |
| `_ai-field-button.scss` | Estilos del botón |
| `jaraba_page_builder.routing.yml` | Endpoint API |

**Flujo:**
1. Usuario hace clic en "✨ Generar con IA"
2. Modal con opciones de tono (profesional, casual, etc.)
3. Request a `/api/v1/page-builder/generate-field`
4. Respuesta mostrada en preview
5. Usuario confirma o regenera

---

### Gap A: UI Experimentos A/B

**Archivos a crear:**

| Archivo | Propósito |
|---------|-----------|
| `templates/experiments-dashboard.html.twig` | Dashboard visual |
| `js/experiments-dashboard.js` | Gráficos Chart.js |
| `scss/_experiments-dashboard.scss` | Estilos |
| `Controller/ExperimentDashboardController.php` | Backend |

**Ruta frontend:** `/experiments` (Site Builder)

---

## 6. Métricas de Éxito

| Métrica | Objetivo |
|---------|----------|
| Cobertura especificaciones | >95% |
| Tiempo carga páginas | <2s LCP |
| Conversión experimentos | +15% vs baseline |
| Adopción "Generar con IA" | >40% de campos |

---

## 7. Riesgos y Mitigaciones

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|--------------|---------|------------|
| Complejidad Chart.js | Media | Bajo | Usar librería Chart.js existente |
| Performance heatmaps | Baja | Alto | Lazy load de Clarity |
| Costos API IA | Media | Medio | Rate limiting per-tenant |

---

## 8. Próximo Paso

**Iniciar Gap B: Botón "Generar con IA"** - Es el de mayor impacto inmediato en UX y no tiene dependencias.

---

*Documento creado siguiendo directrices de documentación del proyecto.*
