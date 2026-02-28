# Aprendizaje: SCSS Orphan — Parciales sin importar en main.scss

**Fecha:** 2026-02-28  
**Severidad:** Media  
**Impacto:** Las páginas 404 y 403 aparecían sin estilos CSS (diseño roto)

## Qué pasó

1. En el commit `dbb59daa` (2026-02-23), se creó `_error-pages.scss` y se añadió correctamente su `@use` en `main.scss`.
2. En el commit posterior `f833dad6`, un refactor modificó `main.scss` y **eliminó inadvertidamente** la línea `@use 'components/error-pages'`.
3. Resultado: el archivo SCSS existía con 284 líneas de estilos premium, pero nunca se compilaba → las páginas 404/403 aparecían sin estilos.

Se detectaron 3 huérfanos adicionales con el mismo problema:
- `_autofirma.scss` — incluso tenía un comentario "Añadir @import" que nunca se ejecutó
- `_commerce-product-geo.scss` — mismo caso
- `_language-switcher.scss` — sin importar en ningún entry point

## Medida preventiva implementada

### Script de detección: `scripts/check-scss-orphans.js`

- Escanea todos los parciales en `scss/components/`
- Verifica que cada uno tenga un `@use` correspondiente en `main.scss`, bundles o routes
- Sale con código 1 si hay huérfanos → **bloquea el build**

### Integración en pipeline

```json
"lint:scss": "node scripts/check-scss-orphans.js",
"build": "npm run lint:scss && npm run build:css && ..."
```

El chequeo se ejecuta **antes** de la compilación SCSS. Si alguien crea un parcial sin importarlo, `npm run build` fallará con un mensaje claro indicando qué archivo falta.

## Regla para desarrollo

> **REGLA:** Al crear cualquier archivo `_nombre.scss` en `scss/components/`, se DEBE añadir `@use 'components/nombre'` en `main.scss` (o en el bundle/route correspondiente) **en el mismo commit**.

El script `check-scss-orphans.js` lo validará automáticamente durante el build.
