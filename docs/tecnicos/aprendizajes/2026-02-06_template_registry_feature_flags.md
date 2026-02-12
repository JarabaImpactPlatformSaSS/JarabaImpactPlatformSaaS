# 📚 Feature Flags y Analytics en Template Registry

> **Fecha**: 2026-02-06  
> **Sprint**: Fase 3 Extensión  
> **Módulo**: `jaraba_page_builder`

---

## 1. Contexto

Tras completar la Fase 2 (Consolidación) del Template Registry SSoT, se implementó la Fase 3 para añadir:

1. **Feature Flags** - Restricción de bloques por plan de suscripción
2. **Analytics Tracking** - Métricas de uso de bloques

---

## 2. Implementación Feature Flags

### 2.1 Backend (PHP)

En `TemplateRegistryService::getAsGrapesJSBlocks()`:

```php
$isLocked = $isPremium && !$this->isTemplateAccessible($template, $currentPlan);

$blocks[] = [
    'isLocked' => $isLocked,
    'isPremium' => $isPremium,
    'requiredPlan' => $template['required_plan'] ?? 'free',
    'attributes' => [
        'data-is-locked' => $isLocked ? 'true' : 'false',
        'data-required-plan' => $template['required_plan'] ?? 'free',
    ],
];
```

### 2.2 Frontend (JavaScript)

En `loadBlocksFromRegistry()`:

```javascript
const isLocked = block.isLocked || false;
const blockLabel = isLocked ? `🔒 ${block.label}` : block.label;

blockManager.add(block.id, {
    label: blockLabel,
    content: isLocked ? '' : block.content,
    disable: isLocked,
});
```

### 2.3 Estilos (SCSS)

```scss
.gjs-block[data-is-locked="true"] {
    opacity: 0.6;
    pointer-events: none;
    
    &::before {
        content: '🔒';
        position: absolute;
        top: 4px;
        right: 4px;
    }
}
```

---

## 3. Analytics Tracking

### 3.1 Implementación

```javascript
const setupBlockAnalytics = () => {
    editor.on('block:drag:stop', (component, block) => {
        console.log(`📊 Block Used: ${block.get('id')}`);
        
        if (drupalSettings.jaraba_page_builder?.analyticsEnabled) {
            fetch('/api/v1/page-builder/analytics/block-used', {
                method: 'POST',
                body: JSON.stringify({
                    block_id: blockId,
                    category: blockCategory,
                    page_id: drupalSettings.jaraba_page_builder?.pageId,
                }),
            });
        }
    });
};
```

---

## 4. Lecciones Aprendidas

### 4.1 ✅ GrapesJS `disable` Property

El Block Manager soporta `disable: true` para prevenir el arrastre de bloques sin necesidad de CSS adicional.

### 4.2 ✅ Labels con Emoji

GrapesJS renderiza correctamente emojis Unicode en labels de bloques, permitiendo indicadores visuales sin SVG custom.

### 4.3 ✅ Fallback Resiliente

La estrategia híbrida (bloques estáticos + API) asegura que el editor funcione incluso si la API falla.

---

## 5. Archivos Modificados

| Archivo | Cambio |
|---------|--------|
| `TemplateRegistryService.php` | +`isLocked`, `isPremium`, `requiredPlan` |
| `grapesjs-jaraba-blocks.js` | +`setupBlockAnalytics()`, locked handling |
| `_canvas-editor.scss` | +84 líneas estilos premium/locked |

---

## 6. Referencias

- [Arquitectura Unificada](../arquitectura/2026-02-06_arquitectura_unificada_templates_bloques.md)
- [Matriz de Bloques](./2026-02-06_matriz_bloques_page_builder.md)
