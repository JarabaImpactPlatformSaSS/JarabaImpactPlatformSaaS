---
description: Cómo aplicar el patrón Premium Card a nuevas cards/dashboards
---

# Premium Cards Pattern Workflow

Este workflow asegura la aplicación consistente del patrón premium a todas las cards del sistema.

## Cuándo Usar
Aplicar SIEMPRE que crees:
- Cards de estadísticas/KPIs en dashboards
- Cards de plataformas/integraciones
- Cards de acciones rápidas
- Cards de idiomas o configuraciones

## Pasos

### 1. Verificar Patrón Documentado
Revisar el archivo de referencia:
```
web/modules/custom/ecosistema_jaraba_core/scss/_pixel-manager.scss
```
Buscar la clase `.pixel-platform-card` como modelo.

### 2. Aplicar Estilos Core (SCSS)
```scss
.mi-nueva-card {
    position: relative;
    overflow: hidden;
    padding: var(--ej-spacing-lg, 1.5rem);
    background: linear-gradient(135deg,
            rgba(255, 255, 255, 0.95) 0%,
            rgba(248, 250, 252, 0.9) 100%);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-radius: var(--ej-radius-xl, 16px);
    border: 1px solid rgba(255, 255, 255, 0.8);
    box-shadow:
        0 4px 24px rgba(0, 0, 0, 0.04),
        0 1px 2px rgba(0, 0, 0, 0.02),
        inset 0 1px 0 rgba(255, 255, 255, 0.9);
    transition:
        transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275),
        box-shadow 0.3s ease;

    // Efecto shine
    &::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 50%;
        height: 100%;
        background: linear-gradient(90deg,
                transparent,
                rgba(255, 255, 255, 0.4),
                transparent);
        transition: left 0.6s ease;
        pointer-events: none;
    }

    &:hover {
        transform: translateY(-6px) scale(1.02);
        box-shadow:
            0 20px 40px rgba(35, 61, 99, 0.12),
            0 8px 16px rgba(35, 61, 99, 0.08),
            inset 0 1px 0 rgba(255, 255, 255, 1);

        &::before {
            left: 150%;
        }
    }
}
```

// turbo
### 3. Compilar SCSS
```bash
lando ssh -c 'cd web/themes/custom/ecosistema_jaraba_theme && npm run build && drush cr'
```

### 4. Verificar en Navegador
- Hover sobre la card → debe elevarse y mostrar efecto shine
- Verificar blur de glassmorphism visible

### 5. Actualizar Documentación
Añadir nueva implementación a la lista de "Casos de Uso Verificados" en:
```
knowledge/jaraba_platform_development_standards/artifacts/standards/premium_card_pattern.md
```

## Valores Obligatorios
| Propiedad | Valor |
|-----------|-------|
| Curva de transición | `cubic-bezier(0.175, 0.885, 0.32, 1.275)` |
| Blur glassmorphism | `10px` mínimo |
| Border-radius | `16px` (`--ej-radius-xl`) |
| Hover lift | `translateY(-6px)` |
| Hover scale | `scale(1.02)` |
