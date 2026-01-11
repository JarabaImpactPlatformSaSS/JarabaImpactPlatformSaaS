---
description: Directrices para trabajar con estilos SCSS y variables inyectables
---

# Workflow: Estilos SCSS y Variables Inyectables

## Ubicación de Archivos

- **SCSS parciales**: `web/modules/custom/ecosistema_jaraba_core/scss/`
- **CSS compilado**: `web/modules/custom/ecosistema_jaraba_core/css/ecosistema-jaraba-core.css`
- **Archivo principal**: `scss/main.scss`

## Variables

### Variables SCSS (`_variables.scss`)
- `$ej-color-primary-fallback`, `$ej-spacing-md`, etc.
- Valores por defecto que se usan durante la compilación

### Variables CSS Inyectables (`_injectable.scss`)
- `var(--ej-color-primary)`, `var(--ej-text-muted)`, etc.
- Se pueden sobrescribir en runtime desde Drupal (por vertical/tenant)

## Reglas Obligatorias

1. **Siempre usar variables CSS inyectables** cuando existan:
   ```scss
   // ✅ CORRECTO
   color: var(--ej-color-primary, #{$ej-color-primary-fallback});
   
   // ❌ INCORRECTO
   color: #2E7D32;
   ```

2. **Crear un parcial SCSS** por cada dashboard/componente:
   ```
   scss/_mi-componente.scss
   ```

3. **Importar en main.scss**:
   ```scss
   @use 'mi-componente';
   ```

4. **Usar `@use 'variables' as *;`** al inicio de cada parcial

5. **No crear archivos .css directamente** - siempre SCSS que se compila

## Compilación

```bash
# En el directorio del módulo
npm run build:css
# o
sass scss/main.scss css/ecosistema-jaraba-core.css
```

## Variables Inyectables Disponibles

| Variable CSS | Descripción |
|--------------|-------------|
| `--ej-color-primary` | Color primario (por tenant) |
| `--ej-color-secondary` | Color secundario |
| `--ej-color-accent` | Color de acento |
| `--ej-color-success` | Verde para éxito |
| `--ej-color-warning` | Naranja para alertas |
| `--ej-color-error` | Rojo para errores |
| `--ej-font-family` | Tipografía principal |
| `--ej-text-primary` | Color texto principal |
| `--ej-text-muted` | Color texto secundario |
| `--ej-border-color` | Color de bordes |
| `--ej-shadow-md` | Sombra media |

## Checklist Pre-Commit

- [ ] ¿Usé variables CSS inyectables donde aplica?
- [ ] ¿Creé parcial SCSS (no CSS directo)?
- [ ] ¿Añadí import en main.scss?
- [ ] ¿Compilé el CSS?
