# 📝 Aprendizaje: changeProp + Model Defaults — Auditoría 14 Bloques GrapesJS

> **Fecha:** 2026-02-08
> **Módulo:** jaraba_page_builder
> **Contexto:** Auditoría de 14 tipos de componentes GrapesJS tras detectar labels/títulos invisibles en Stats Counter

---

## Problema Detectado

En el bloque **Stats Counter**, los labels, títulos y sufijos eran **invisibles** dentro del Canvas Editor de GrapesJS, aunque el HTML generado contenía los elementos `<span>` correctos.

**Causa raíz:** Los traits con `changeProp: true` leen propiedades del **modelo Backbone** del componente (no atributos HTML). Si la propiedad no existe en `defaults`, `this.get('stat1Label')` retorna `undefined`, generando HTML vacío.

```javascript
// ❌ INCORRECTO: changeProp sin defaults a nivel de modelo
domComponents.addType('jaraba-stats-counter', {
    model: {
        defaults: {
            traits: [
                { name: 'stat1Label', label: 'Label 1', changeProp: true, default: 'Clientes' }
                // ⚠️ El `default` del trait es para el UI del panel, NO para el modelo
            ],
        },
    },
});
// this.get('stat1Label') → undefined ❌
```

```javascript
// ✅ CORRECTO: defaults a nivel de modelo + changeProp
domComponents.addType('jaraba-stats-counter', {
    model: {
        defaults: {
            stat1Label: 'Clientes Satisfechos', // ← Propiedad del modelo
            traits: [
                { name: 'stat1Label', label: 'Label 1', changeProp: true }
            ],
        },
    },
});
// this.get('stat1Label') → 'Clientes Satisfechos' ✅
```

---

## Regla Derivada

> **REGLA GRAPEJS-001**: Todo trait con `changeProp: true` DEBE tener una propiedad correspondiente en `defaults` a nivel del modelo. El `default` dentro de la definición del trait sólo afecta al widget UI del panel de traits, NO al valor retornado por `this.get()`.

---

## Auditoría de 14 Componentes (Resultado)

| Componente | Traits con `changeProp` | Model defaults | Estado |
|---|---|---|---|
| `jaraba-stats-counter` | ✅ 12 traits | ❌ → ✅ **FIJADO** | Corregido |
| `jaraba-pricing-toggle` | ✅ 4 traits | ✅ OK | Sin issues |
| `jaraba-tabs` | ✅ 6 traits | ✅ OK | Sin issues |
| `jaraba-countdown` | ✅ 2 traits | ✅ OK | Sin issues |
| `jaraba-timeline` | ✅ 13 traits | ✅ OK | Sin issues |
| `jaraba-navigation` | ✅ ~28 traits | ✅ OK | Sin issues |
| `jaraba-button` | ✅ 2 traits | ✅ OK | Sin issues |
| `jaraba-faq` | ✅ 2 traits | ✅ OK | Sin issues |
| `jaraba-block` | Schema-driven | N/A | Sin issues |
| `jaraba-product-card` | Sin `changeProp` | ✅ OK | Sin issues |
| `jaraba-social-links` | Sin `changeProp` | ✅ OK | Sin issues |
| `jaraba-contact-form` | Sin `changeProp` | ✅ OK | Sin issues |
| `jaraba-pricing-table` | Sin `changeProp` | ✅ OK | Sin issues |

**Resultado: 1/14 bloques afectados** (Stats Counter). Los demás siguen el patrón correcto.

---

## Correcciones Aplicadas en Stats Counter

1. **Model defaults**: Añadidas 13 propiedades (`statsTitle`, `stat1Value`, `stat1Label`, `stat1Suffix`, `stat2Value`...) en `defaults`
2. **HTML template**: Añadido `<h2>` para título en `getStatsHtml()`
3. **Listener**: Añadido `change:statsTitle` en `init()`
4. **CSS labels**: `display: block; margin-top: 0.5rem; font-weight: 500`

---

## Nota Adicional: Timeline Duplicate Dots

**Bug**: Los timeline items generaban dots duplicados porque:
- El HTML inline incluía `<div class="dot">` explícitamente
- El SCSS `_timeline.scss` usa `::before` pseudo-elementos para los dots

**Fix**: Eliminados los dots inline del HTML generado por `getTimelineHtml()`.

**Regla**: Si un bloque usa pseudo-elementos CSS para decoración, el HTML template NO debe duplicar esos elementos inline.

---

## Nota: Pricing Toggle ↔ Pricing Table (No conectados)

El toggle Mensual/Anual y la tabla de precios son **componentes GrapesJS independientes**. El toggle emite `jaraba:pricing-change` pero la tabla no escucha ese evento. Conectarlos requeriría que `jaraba-pricing-table` implemente un listener global para el evento del toggle — funcionalidad nueva pendiente de planificar.

---

## Checklist para Nuevos Bloques con changeProp

- [ ] Toda propiedad usada con `this.get('prop')` DEBE estar en `defaults`
- [ ] Todo trait con `changeProp: true` DEBE tener su propiedad en `defaults`
- [ ] Verificar que `getXxxHtml()` renderiza título si es aplicable
- [ ] Verificar que labels tienen `display: block` si les aplica CSS externo
- [ ] No duplicar elementos decorativos si ya los genera CSS (pseudo-elements)
- [ ] Añadir listener `change:propertyName` en `init()` para cada propiedad
- [ ] Probar en Canvas Editor con componentes array `[]` (fuerza re-parsing HTML)

---

## Archivos Relevantes

| Archivo | Propósito |
|---|---|
| `web/modules/custom/jaraba_page_builder/js/grapesjs-jaraba-blocks.js` | 14 componentes GrapesJS (3,628 LOC) |
| `web/modules/custom/jaraba_page_builder/scss/blocks/_stats-counter.scss` | SCSS Stats Counter |
| `web/modules/custom/jaraba_page_builder/scss/blocks/_timeline.scss` | SCSS Timeline (pseudo-elements) |
| `web/modules/custom/jaraba_page_builder/js/behaviors/*.behavior.js` | 5 Drupal behaviors |

---

## Registro de Cambios

| Fecha | Versión | Descripción |
|-------|---------|-------------|
| 2026-02-08 | 1.0.0 | Creación: Auditoría 14 componentes, regla GRAPEJS-001, correcciones Stats Counter + Timeline |
