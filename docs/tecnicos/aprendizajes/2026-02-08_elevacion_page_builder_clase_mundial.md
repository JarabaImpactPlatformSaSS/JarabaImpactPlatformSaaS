# 📝 Aprendizajes: Elevación Page Builder & Site Builder a Clase Mundial

**Fecha:** 2026-02-08  
**Contexto:** Revisión exhaustiva del ecosistema Page Builder + Site Builder cruzando 6 documentos de arquitectura + 8 archivos de código fuente  
**Versión:** 1.0.0

---

## Aprendizajes Clave

### 1. ✅ Cross-Referencing Documentación vs. Código como Herramienta de Diagnóstico

**Patrón descubierto**: La forma más efectiva de evaluar el estado real de un sistema complejo es **cruzar sistemáticamente** la documentación (especificaciones, planes, auditorías) con el código real. En este caso, la documentación indicaba 6 suites E2E pero el código real tenía 9, revelando un gap de documentación desactualizada.

**Lección**: Antes de planificar mejoras, siempre verificar afirmaciones de docs contra `grep`/`view_file` del código. Los gaps reales suelen ser diferentes de los documentados.

### 2. 🏗️ Patrón Dual Architecture: script + Drupal.behaviors (GrapesJS)

**Descubrimiento**: Solo el bloque FAQ Accordion implementa correctamente la Dual Architecture descrita en la especificación GrapesJS §4. Los demás bloques interactivos (Stats Counter, Pricing Toggle, Tabs, Timeline, Countdown) están como HTML estático sin interactividad real.

**Regla derivada**:
```
Todo bloque GrapesJS que requiera interactividad DEBE implementar:
1. `script` property (function regular, NO arrow, `this` = DOM element)
2. `view.onRender()` duplicando la lógica para el editor
3. `Drupal.behaviors.jarabaXxx` para páginas públicas
4. Biblioteca en `jaraba_page_builder.libraries.yml`
```

**Referencia**: `docs/tecnicos/aprendizajes/2026-02-05_grapesjs_interactive_blocks_pattern.md`

### 3. ⚠️ PostMessage sin Receptor = Feature Rota Silenciosa

**Bug descubierto**: El sistema de parciales (`grapesjs-jaraba-partials.js`) envía eventos `postMessage` (`JARABA_HEADER_CHANGE`, `JARABA_FOOTER_CHANGE`) para hot-swap de header/footer, pero **no existe receptor** en el iframe que los procese. La funcionalidad aparece completa en el emisor (traits + UI + postMessage) pero falla silenciosamente.

**Lección**: Siempre verificar ambos extremos de un canal de comunicación (emisor + receptor). Un `postMessage` sin listener es código muerto que genera falsas expectativas.

### 4. 🧪 Tests E2E con `expect(true).to.be.true` = Falso Positivo

**Anti-patrón detectado**: Varios tests en `canvas-editor.cy.js` usan fallbacks tipo:
```javascript
cy.get('.selector').should('exist').then(() => { ... })
  .catch(() => { expect(true).to.be.true; }); // ❌ SIEMPRE pasa
```

**Impacto**: Estos tests siempre pasan, dando falsa confianza. Un test que nunca falla es peor que no tener test.

**Regla derivada**: NUNCA usar `expect(true).to.be.true` como fallback en Cypress. Si un selector puede no existir, usar `.should('not.exist')` o `cy.get().if()`.

### 5. 📊 Métricas de Madurez del Canvas Editor

**Estado real cuantificado**:

| Métrica | Valor |
|---|---|
| Archivos JS del editor | 8 plugins |
| LOC total plugins | ~5,000+ |
| Bloques registrados | 70 (Jaraba) + 62 (nativos) = 132 |
| Categorías de bloques | 17 |
| Tests E2E | 9 suites, 508 líneas |
| Bloques con interactividad dual | 1/5 (solo FAQ) = 20% |
| changeProp bugs (auditoría post) | 1/14 (Stats Counter) = 7% |
| Hot-swap funcional | 0% (emisor sin receptor) |

**Post-auditoría changeProp**: Ver [2026-02-08_grapesjs_changeprop_model_defaults_audit.md](./2026-02-08_grapesjs_changeprop_model_defaults_audit.md)

### 6. 🔗 Template Registry SSoT como Fuente de Verdad

**Confirmación**: El patrón Template Registry (PHP → REST API → `loadBlocksFromRegistry()` en JS) funciona correctamente como SSoT. Los campos `isLocked`, `isPremium`, `requiredPlan` y `setupBlockAnalytics()` están implementados en `grapesjs-jaraba-blocks.js`.

**Implicación**: Nuevos bloques se registran via YAML en PHP y automáticamente aparecen en el Canvas Editor sin tocar JS. Solo los bloques que necesitan interactividad custom requieren definición manual en JS.

### 7. 📋 Estructura de Documentación del Page Builder

**Inventario completo**: El Page Builder/Site Builder tiene **20+ documentos** distribuidos en:
- `docs/arquitectura/`: Theming, templates-bloques, especificación GrapesJS
- `docs/planificacion/`: Plan constructor, plan elevación, auditoría elevación
- `docs/tecnicos/`: Docs 160-179 (specs individuales)
- `docs/tecnicos/aprendizajes/`: 10+ aprendizajes específicos
- `docs/tecnicos/auditorias/`: Auditoría Page Builder clase mundial

**Regla**: Todo cambio significativo al Page Builder debe reflejarse en al menos 3 documentos: el arquitectónico, el de aprendizajes, y el índice general.

---

## Archivos Relevantes

| Archivo | Propósito |
|---|---|
| `web/modules/custom/jaraba_page_builder/js/grapesjs-jaraba-blocks.js` | Plugin de bloques (2,514 LOC) |
| `web/modules/custom/jaraba_page_builder/js/grapesjs-jaraba-canvas.js` | Motor Canvas (1,036 LOC) |
| `web/modules/custom/jaraba_page_builder/js/grapesjs-jaraba-partials.js` | Parciales H/F (368 LOC) |
| `web/modules/custom/jaraba_page_builder/js/grapesjs-jaraba-command-palette.js` | Command Palette (434 LOC) |
| `tests/e2e/cypress/e2e/canvas-editor.cy.js` | Tests E2E (508 LOC) |

---

## Registro de Cambios

| Fecha | Versión | Descripción |
|-------|---------|-------------|
| 2026-02-08 | 1.0.0 | Creación: 7 aprendizajes de la revisión exhaustiva Page Builder |
