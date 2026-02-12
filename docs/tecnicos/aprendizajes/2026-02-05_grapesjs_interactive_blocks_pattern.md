# Patrón de Interactividad en Bloques GrapesJS

> **Fecha:** 2026-02-05
> **Módulo:** jaraba_page_builder
> **Contexto:** Implementación de FAQ Accordion con expand/collapse

---

## Problema Detectado

Los atributos `onclick` inline en el HTML de bloques GrapesJS son **sanitizados** por el editor, resultando en bloques sin interactividad.

**Ejemplo INCORRECTO (no funciona):**
```html
<button onclick="this.parentElement.classList.toggle('open');">...</button>
```

El HTML exportado pierde el atributo `onclick`.

---

## Solución: Arquitectura Dual

Para bloques interactivos en el Page Builder Canvas v3, se requiere una implementación dual:

### 1. Componente GrapesJS (para el editor)

Definir el componente con la propiedad `script` que se ejecuta dentro del iframe del canvas:

```javascript
// En grapesjs-jaraba-blocks.js
const faqScript = function () {
    const items = this.querySelectorAll('.jaraba-faq__item');
    items.forEach(function (item) {
        const button = item.querySelector('.jaraba-faq__toggle');
        const answer = item.querySelector('.jaraba-faq__answer');
        if (button && answer) {
            button.addEventListener('click', function () {
                item.classList.toggle('jaraba-faq__item--open');
                // Actualizar icono y altura...
            });
        }
    });
};

domComponents.addType('jaraba-faq', {
    model: {
        defaults: {
            script: faqScript, // ← Script ejecutado en canvas
            // ...
        }
    },
    view: {
        onRender() {
            faqScript.call(this.el); // ← También ejecutar en editor
        }
    }
});
```

### 2. Drupal Behavior (para páginas públicas)

Crear un archivo JS separado que se cargue en las páginas públicas:

```javascript
// En js/jaraba-faq-accordion.js
Drupal.behaviors.jarabaFaqAccordion = {
    attach: function (context) {
        // Inicializar accordions con delegación de eventos
    }
};
```

### 3. Biblioteca Drupal

Registrar la biblioteca y cargarla automáticamente:

```yaml
# En jaraba_page_builder.libraries.yml
faq-accordion:
  version: 1.0
  js:
    js/jaraba-faq-accordion.js: {}
  dependencies:
    - core/drupal
    - core/once
```

```php
// En hook_page_attachments()
$attachments['#attached']['library'][] = 'jaraba_page_builder/faq-accordion';
```

---

## Estilos SCSS

Seguir las directrices del proyecto: usar variables inyectables.

```scss
// En scss/blocks/_faq-accordion.scss
.jaraba-faq__answer {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease-out;
}

.jaraba-faq__item--open .jaraba-faq__answer {
    max-height: 500px; // O usar scrollHeight dinámico
}
```

---

## Checklist para Bloques Interactivos

- [ ] Definir componente GrapesJS con `script` property
- [ ] Crear Drupal behavior para páginas públicas
- [ ] Usar SCSS con `var(--ej-*, $fallback)` 
- [ ] Registrar biblioteca y cargar en `hook_page_attachments()`
- [ ] Compilar SCSS con `npx sass --style=compressed`
- [ ] Verificar en editor Y en página pública
- [ ] **GRAPEJS-001**: Todo trait `changeProp: true` DEBE tener propiedad en `defaults` a nivel del modelo
- [ ] No duplicar elementos decorativos inline si CSS ya los genera con pseudo-elements

---

## Referencias

- [GrapesJS Components & JS](https://grapesjs.com/docs/modules/Components-js.html)
- [Arquitectura Theming SCSS](../arquitectura/2026-02-05_arquitectura_theming_saas_master.md)
- [SCSS Workflow](/.agent/workflows/scss-estilos.md)
- [Auditoría changeProp 14 Componentes](./2026-02-08_grapesjs_changeprop_model_defaults_audit.md)
