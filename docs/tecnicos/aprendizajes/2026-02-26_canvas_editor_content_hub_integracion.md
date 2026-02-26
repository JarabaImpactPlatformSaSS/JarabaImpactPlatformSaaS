# Aprendizaje #131: Canvas Editor GrapesJS Integrado en Content Hub

**Fecha:** 2026-02-26
**Sesion:** Integracion Canvas Editor en Content Hub
**Contexto:** Reutilizacion del engine GrapesJS del Page Builder para articulos del Content Hub con shared library dependency pattern.

---

## Resumen

| Dimension | Valor |
|-----------|-------|
| Ficheros creados | 7 (controller x2, install, template, JS, architecture doc, learning doc) |
| Ficheros modificados | 7 (entity, interface, routing, permissions, module, form, SCSS) |
| Campos nuevos | 3 (layout_mode, canvas_data, rendered_html) |
| Lineas SCSS | +420 |
| Errores PHP/SCSS | 0 |
| Regla nueva | CANVAS-ARTICLE-001 (P1) |
| Regla de oro | #51 |

---

## Hallazgo 1: Shared Library Dependency Pattern

### Problema
El Content Hub necesitaba el Canvas Editor GrapesJS (engine + 13 plugins + 67 bloques) para articulos premium. Copiar el engine (~3000 LOC JS) crearia deuda tecnica y divergencia.

### Solucion
Declarar library dependency en `jaraba_content_hub.libraries.yml`:
```yaml
article-canvas-editor:
  js:
    js/article-canvas-editor.js: {}
  dependencies:
    - jaraba_page_builder/grapesjs-canvas
```

Un bridge JS ligero (`article-canvas-editor.js`, ~440 LOC) reconfigura el engine:
1. Polling de `window.jarabaCanvasEditor` (max 50 intentos x 200ms)
2. Registro de StorageManager custom `jaraba-article-rest`
3. Carga de datos iniciales via API GET
4. Auto-save con debounce 5s
5. Undo/redo sincronizado con UndoManager de GrapesJS

### Impacto
Zero code duplication del engine. Las actualizaciones al Page Builder (bloques, plugins) benefician automaticamente al Content Hub.

### Regla
**CANVAS-ARTICLE-001** — NUNCA copiar engine JS. Usar library dependency + bridge JS.

---

## Hallazgo 2: Sanitizers Protected No Heredables

### Problema
`CanvasApiController` del Page Builder tiene 3 sanitizers (`sanitizePageBuilderHtml`, `sanitizeCss`, `sanitizeHtml`) como metodos `protected`. No son heredables desde `ArticleCanvasApiController` porque el controlador del Content Hub NO extiende al del Page Builder (son entidades diferentes).

### Solucion
Replicar los 3 sanitizers con logica identica en `ArticleCanvasApiController`. Ambos eliminan:
- **HTML:** `<script>`, event handlers (`on*`), `javascript:` URIs, `<object>`, `<embed>`, atributos `data-gjs-*`, clases `gjs-*`
- **CSS:** `javascript:`, `expression()`, `@import`, `behavior:`, `-moz-binding:`

### Impacto
Paridad de seguridad entre Page Builder y Content Hub. Si se necesita actualizar sanitizers, hay que tocar ambos controladores.

### Mejora futura
Extraer sanitizers a un trait `CanvasSanitizerTrait` o a un servicio compartido `CanvasSanitizationService`.

---

## Hallazgo 3: Campo Body Required + Canvas Mode

### Problema
`ContentArticle::baseFieldDefinitions()` tiene `body->setRequired(TRUE)`. En canvas mode, el body no es necesario porque el contenido se gestiona desde GrapesJS. Pero el constraint de required impide guardar.

### Solucion
Doble bypass:
1. **Form level:** `$form['body']['widget'][0]['#required'] = FALSE;` cuando `isCanvasMode()`
2. **Save level:** Si body esta vacio en canvas mode, set placeholder: `$entity->set('body', $this->t('[Content managed by Canvas Editor]'))`

No se modifica `baseFieldDefinitions()` para mantener la constraint en articulos legacy.

### Impacto
Retrocompatibilidad total. Articulos legacy siguen requiriendo body. Canvas mode no requiere body.

---

## Hallazgo 4: CSS Injection para Canvas Articles

### Problema
Los articulos canvas tienen estilos CSS custom almacenados en `canvas_data` (JSON). En la vista publica, estos estilos no se aplican automaticamente.

### Solucion
En `jaraba_content_hub_content_article_view()` (hook dentro de `_inject_seo_tags()`):
1. Leer `canvas_data` JSON del articulo
2. Extraer campo `css`
3. Sanitizar con `strip_tags()` + eliminacion de `expression()`, `@import`, `-moz-binding`
4. Inyectar via `html_head` como `<style>` tag

### Impacto
CSS del canvas se renderiza en la vista publica sin archivos CSS adicionales.

---

## Hallazgo 5: Bifurcacion Layout Mode en Preprocess + Template

### Problema
Los articulos pueden ser legacy (textarea body) o canvas (visual editor). El template debe renderizar ambos modos.

### Solucion
Patron de bifurcacion:
1. **Preprocess:** `$variables['article']['layout_mode']` y `$variables['article']['rendered_html']` extraidos de la entidad
2. **Template:** `{% if article.layout_mode == 'canvas' and article.rendered_html %}` → renderiza `rendered_html|raw` en div `.canvas-content`. Else → renderiza `body|raw` (comportamiento original)

### Impacto
Cero impacto en articulos existentes. El modo se determina por campo, no por logica externa.

---

## Hallazgo 6: Reading Time en Canvas Mode

### Problema
El presave hook calcula `reading_time` con `strip_tags($entity->get('body')->value)`. En canvas mode, el body contiene un placeholder, no el contenido real.

### Solucion
Verificar `layout_mode` en presave:
```php
if ($entity->getLayoutMode() === 'canvas' && !$entity->get('rendered_html')->isEmpty()) {
  $text = strip_tags($entity->get('rendered_html')->value);
} else {
  $text = strip_tags($entity->get('body')->value);
}
```

### Impacto
Reading time preciso para ambos modos.

---

## Archivos Creados

| Archivo | Proposito |
|---------|-----------|
| `jaraba_content_hub/src/Controller/ArticleCanvasEditorController.php` | Controlador UI del editor |
| `jaraba_content_hub/src/Controller/ArticleCanvasApiController.php` | REST API (GET/PATCH canvas) |
| `jaraba_content_hub/jaraba_content_hub.install` | Update hook 10001 (3 campos) |
| `jaraba_content_hub/templates/article-canvas-editor.html.twig` | Template del editor |
| `jaraba_content_hub/js/article-canvas-editor.js` | JS bridge para GrapesJS |

## Archivos Modificados

| Archivo | Cambio |
|---------|--------|
| `jaraba_content_hub/src/Entity/ContentArticle.php` | +3 campos, +4 getters |
| `jaraba_content_hub/src/Entity/ContentArticleInterface.php` | +4 metodos |
| `jaraba_content_hub/jaraba_content_hub.routing.yml` | +3 rutas |
| `jaraba_content_hub/jaraba_content_hub.permissions.yml` | +1 permiso |
| `jaraba_content_hub/jaraba_content_hub.module` | Theme hook, body classes, preprocess, CSS injection, presave |
| `jaraba_content_hub/jaraba_content_hub.libraries.yml` | +1 library |
| `jaraba_content_hub/src/Form/ContentArticleForm.php` | Layout mode, canvas link, body handling |
| `ecosistema_jaraba_theme/scss/_content-hub.scss` | +420 lineas editor styles |

---

## Reglas Nuevas

| Codigo | Prioridad | Descripcion |
|--------|-----------|-------------|
| CANVAS-ARTICLE-001 | P1 | Canvas Editor compartido via library dependency + bridge JS + endpoints API propios |

## Reglas de Oro

| # | Regla |
|---|-------|
| 51 | Shared library dependency para Canvas Editor cross-module — NUNCA copiar engine, declarar dependency + bridge JS que reconfigura StorageManager y endpoints |
