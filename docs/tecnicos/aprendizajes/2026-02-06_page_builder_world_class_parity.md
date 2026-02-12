# Page Builder World-Class - Paridad 100% Bloques-Templates

> **Fecha**: 2026-02-06  
> **Categoría**: Page Builder / GrapesJS / Arquitectura  
> **Impacto**: Alto

---

## Contexto

El Page Builder utilizaba dos sistemas separados para manejar contenido visual:
1. **Templates YAML** (70 archivos en `config/install/`)
2. **Bloques GrapesJS** (originalmente 37 en JS)

Esta duplicación generaba desincronización y gaps de funcionalidad.

---

## Problema Resuelto

### Gap de Paridad
- Templates YAML: **70**
- Bloques JS (antes): **37**
- Gap: **33 bloques faltantes**

### Solución Implementada

1. **25 nuevos bloques** en 4 categorías nuevas:
   - Commerce (4): product-card, product-grid, cart-summary, payment-methods
   - Social (5): social-links, social-proof, sharing-buttons, logo-carousel, trust-badges
   - Advanced (5): timeline, tabs-content, table-data, code-embed, map-embed
   - Utilities (5): alert-banner, countdown, step-wizard, newsletter-form, cookie-banner

2. **Template Registry Service (SSoT)**:
   - Servicio PHP centralizado
   - API REST con 5 endpoints
   - Caché con TTL 1 hora
   - Filtrado por plan de suscripción

---

## Archivos Clave

| Archivo | Descripción |
|---------|-------------|
| `TemplateRegistryService.php` | SSoT para 70 templates |
| `TemplateRegistryApiController.php` | 5 endpoints REST |
| `grapesjs-jaraba-blocks.js` | 70 bloques custom |

---

## API REST Implementada

```
GET  /api/v1/page-builder/templates
GET  /api/v1/page-builder/templates/{id}
GET  /api/v1/page-builder/templates/stats
GET  /api/v1/page-builder/templates/categories
POST /api/v1/page-builder/templates/cache/invalidate
```

---

## Lecciones Aprendidas

1. **SSoT es crítico**: Mantener dos fuentes de verdad genera drift inevitable
2. **LoggerChannelFactoryInterface**: Usar `@logger.factory` cuando el canal no existe
3. **Namespace consistency**: `ecosistema_jaraba_core` ≠ `jaraba_core`
4. **API design**: Múltiples formatos (`list`, `gallery`, `blocks`, `grouped`) en mismo endpoint

---

## Métricas Finales (Post-Fase 3)

| Métrica | Valor |
|---------|-------|
| Templates YAML | **70** |
| Bloques API | **70** |
| Bloques Estáticos | **~132** |
| Total en Canvas | **~202** |
| Categorías | **24** |
| Paridad | **100%** ✅ |
| Feature Flags | ✅ Implementado |
| Analytics | ✅ Tracking activo |

---

## Documentos Relacionados

- [Arquitectura Unificada Templates-Bloques](../arquitectura/2026-02-06_arquitectura_unificada_templates_bloques.md)
- [Feature Flags y Analytics](./2026-02-06_template_registry_feature_flags.md)
- [Plan Elevación World-Class](../arquitectura/2026-02-06_plan_elevacion_page_builder_clase_mundial.md)
