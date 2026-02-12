# Plan de Auditoría: Correspondencia Miniatura ↔ Vista Previa del Page Builder

> **Fecha**: 2026-02-02  
> **Autor**: Claude AI  
> **Estado**: Planificado  
> **Prioridad**: Alta

---

## Resumen Ejecutivo

Los previews de templates del Page Builder **no corresponden visualmente** con sus miniaturas PNG en `/page-builder/templates`. Este documento detalla el problema, la solución y el plan de implementación.

---

## 1. Problema Identificado

### Síntoma
Al navegar a `/page-builder/templates/{template_id}/preview`, el contenido renderizado no coincide con la miniatura mostrada en el picker de templates.

### Causa Raíz
El sistema tiene 3 fuentes de datos para previews (en orden de prioridad):

1. **`preview_data` del YAML** - Datos curados específicos ✅ (ideal)
2. **`getHardcodedPreviewData()`** - Solo 2 templates tienen datos
3. **`generateSampleValue()`** - Fallback genérico ❌ (problema)

**Resultado**: Solo 2 de ~60 templates tienen datos apropiados. El resto muestra contenido genérico tipo "Título de Ejemplo Atractivo" que no corresponde con las miniaturas.

### Archivos Involucrados
- Controlador: `jaraba_page_builder/src/Controller/TemplatePickerController.php`
- Método: `getPreviewData()` (líneas 562-588)
- Configuraciones: `config/install/jaraba_page_builder.template.*.yml`

---

## 2. Solución Propuesta

### Enfoque
Añadir campo `preview_data` curado a cada archivo YAML de template que contenga exactamente los mismos textos, imágenes e iconos que aparecen en la miniatura PNG.

### Formato YAML

```yaml
# Al final de cada archivo jaraba_page_builder.template.{id}.yml
preview_data:
  quote: "La innovación distingue a los líderes de los seguidores."
  author: "Steve Jobs"
  author_title: "Co-fundador, Apple"
  author_image: "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=200&q=80"
  style: highlighted
  background: light
```

---

## 3. Inventario de Templates

### Resumen por Categoría

| Categoría | Total | Con Datos | Sin Datos |
|-----------|-------|-----------|-----------|
| Content | 16 | 0 | 16 |
| Hero | 4 | 1 | 3 |
| Features | 6 | 1 | 5 |
| CTA | 4 | 0 | 4 |
| Premium | 19 | 0 | 19 |
| Layout | 3 | 0 | 3 |
| Forms | 2 | 0 | 2 |
| Otras | 6 | 0 | 6 |
| **Total** | **~60** | **2** | **~58** |

### Categoría: Content (16 templates) - PRIORIDAD ALTA

| ID | Archivo YAML |
|----|--------------|
| blockquote | `jaraba_page_builder.template.blockquote.yml` |
| accordion_content | `jaraba_page_builder.template.accordion_content.yml` |
| blog_cards | `jaraba_page_builder.template.blog_cards.yml` |
| cards_grid | `jaraba_page_builder.template.cards_grid.yml` |
| comparison_table | `jaraba_page_builder.template.comparison_table.yml` |
| course_catalog | `jaraba_page_builder.template.course_catalog.yml` |
| faq_accordion | `jaraba_page_builder.template.faq_accordion.yml` |
| how_it_works | `jaraba_page_builder.template.how_it_works.yml` |
| image_text_block | `jaraba_page_builder.template.image_text_block.yml` |
| portfolio_gallery | `jaraba_page_builder.template.portfolio_gallery.yml` |
| profile_cards | `jaraba_page_builder.template.profile_cards.yml` |
| recommended_courses | `jaraba_page_builder.template.recommended_courses.yml` |
| rich_text | `jaraba_page_builder.template.rich_text.yml` |
| tabs_content | `jaraba_page_builder.template.tabs_content.yml` |
| team_grid | `jaraba_page_builder.template.team_grid.yml` |
| timeline | `jaraba_page_builder.template.timeline.yml` |

### Categoría: Hero (4 templates)

| ID | Estado |
|----|--------|
| hero_fullscreen | ✅ Tiene datos |
| split_hero | ❌ Pendiente |
| video_hero | ❌ Pendiente |
| job_search_hero | ❌ Pendiente |

### Categoría: Features (6 templates)

| ID | Estado |
|----|--------|
| features_grid | ✅ Tiene datos |
| animated_beam | ❌ Pendiente |
| bento_grid | ❌ Pendiente |
| feature_highlight | ❌ Pendiente |
| icon_cards | ❌ Pendiente |
| services_grid | ❌ Pendiente |

### Categoría: Premium (19 templates)

animated_background, animated_counter, card_flip_3d, comparison_slider, floating_cards, glassmorphism_cards, gradient_cards, hover_glow_cards, orbit_animation, parallax_hero, particle_hero, scroll_reveal, split_screen, spotlight_text, sticky_scroll, testimonials_3d, text_gradient, typewriter_text, spotlight_grid

### Otras Categorías (~15 templates)

- **Layout**: columns_layout, divider_section, footer_section
- **Forms**: contact_form, newsletter_signup
- **Conversion**: cta_section
- **Events**: event_calendar
- **Media**: image_gallery, video_embed
- **Trust**: logo_grid, partners_carousel, social_proof
- **Maps**: map_locations
- **Social Proof**: marquee_logos, testimonials_slider
- **Pricing**: pricing_table
- **Commerce**: product_showcase
- **Stats**: stats_counter

---

## 4. Plan de Implementación por Fases

### Fase 1: Templates Más Visibles (6 templates)
**Tiempo estimado**: 30-45 minutos

1. `blockquote` - Mencionado específicamente como problemático
2. `testimonials_slider` - Similar visual
3. `icon_cards` - Muy usado en landing pages
4. `services_grid` - Core para servicios
5. `team_grid` - Común en "about"
6. `pricing_table` - Crítico para conversión

### Fase 2: Hero y CTA (7 templates)
**Tiempo estimado**: 30 minutos

- split_hero, video_hero, job_search_hero
- alert_banner, banner_strip, countdown_timer, download_box

### Fase 3: Features y Layout (9 templates)
**Tiempo estimado**: 40 minutos

- animated_beam, bento_grid, feature_highlight, icon_cards, services_grid
- columns_layout, divider_section, footer_section, cta_section

### Fase 4: Premium (19 templates)
**Tiempo estimado**: 1.5 horas

- Todos los templates de la categoría Premium

### Fase 5: Resto (17 templates)
**Tiempo estimado**: 1 hora

- Forms, Media, Trust, Maps, Social Proof, Commerce, Stats

---

## 5. Proceso de Implementación

### Para cada template:

1. **Abrir miniatura PNG** en `/page-builder/templates`
2. **Extraer textos** exactos de la miniatura
3. **Identificar imágenes** usadas (URLs de Unsplash típicamente)
4. **Editar YAML** añadiendo `preview_data:`
5. **Importar config**: `drush cim --partial`
6. **Verificar preview**: navegar a `/page-builder/templates/{id}/preview`

### Comandos de Verificación

```bash
# Importar configuración actualizada
drush cim --partial --source=modules/custom/jaraba_page_builder/config/install -y

# Limpiar caché
drush cr

# Verificar en navegador
open https://jaraba-saas.lndo.site/es/page-builder/templates/{template_id}/preview
```

---

## 6. Criterios de Aceptación

Para cada template, verificar:

- [ ] Textos del preview coinciden con miniatura
- [ ] Iconos coinciden (categoría y nombre correcto)
- [ ] Imágenes son las mismas o equivalentes
- [ ] Layout y estilo visual son idénticos
- [ ] Colores y variantes coinciden
- [ ] Responsivo (funciona en viewport switcher)

---

## 7. Riesgos y Mitigación

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|--------------|---------|------------|
| Miniaturas desactualizadas | Media | Alto | Actualizar PNG después de curar datos |
| Imágenes de Unsplash caídas | Baja | Medio | Usar URLs con parámetros de tamaño |
| Config import falla | Baja | Alto | Backup de config antes de cambios |

---

## 8. Dependencias

- Acceso a miniaturas PNG en `/page-builder/templates`
- Permisos de edición en `config/install/`
- Drush disponible para importar configuración

---

## 9. Estimación Total

| Fase | Templates | Tiempo |
|------|-----------|--------|
| 1 | 6 | 45 min |
| 2 | 7 | 30 min |
| 3 | 9 | 40 min |
| 4 | 19 | 90 min |
| 5 | 17 | 60 min |
| **Total** | **~58** | **~4.5 horas** |

---

## 10. Siguiente Paso

Aprobar este plan y comenzar con Fase 1 (6 templates prioritarios).
