# Aprendizaje #109: Sticky Header Migration — position: fixed → sticky

**Fecha:** 2026-02-23
**Contexto:** Solapamiento del header de navegacion sobre el contenido en la pagina de perfil de usuario (`/es/user/23`)
**Impacto:** Global — afecta a todas las paginas del frontend excepto landing pages con hero fullscreen

---

## 1. Problema

El `.landing-header` usaba `position: fixed` como posicionamiento global. Esto requeria que cada area de contenido compensara con un `padding-top` estatico (80px en `.main-content`, 96px en `.user-main`, 80px en `.error-page`).

**Causa raiz:** La altura del header es **variable**. Los botones de accion (`.btn-ghost`) como "Mi cuenta" y "Cerrar sesion" wrappean a 2 lineas en ciertos viewport widths, aumentando la altura del header de ~72px a ~92px+. Un `padding-top` fijo nunca puede compensar correctamente una altura variable.

**Sintoma visible:** El avatar y la tarjeta hero del perfil de usuario quedaban ocultos detras del header.

---

## 2. Diagnostico

### Intentos fallidos
1. **Aumentar padding-top de 96px a 120px**: No soluciona el problema de raiz. El header sigue solapando porque su altura varia dinamicamente.

### Investigacion profunda
- Verificado que `homepage.css` (critical CSS) se inyecta en TODAS las paginas via `preprocess_html()`, no solo en la landing.
- Confirmado que el template `page--user.html.twig` se usa correctamente (template suggestion activa).
- Confirmado que las body classes `page-user` y `full-width-layout` se inyectan en `preprocess_html()`.
- Analizado que `.btn-ghost` en el header causa wrapping a 2 lineas, haciendo la altura del header impredecible.

### Conclusion
`position: fixed` saca al header del flujo del documento. El contenido no "sabe" donde termina el header. `position: sticky` mantiene al header en el flujo del documento — cuando hace scroll, el header se "pega" al top pero el contenido nunca queda debajo de el.

---

## 3. Solucion Implementada

### Cambio global en `_landing-page.scss`
```scss
// ANTES: position: fixed (requiere padding-top compensatorio)
.landing-header {
    position: fixed;
    top: 0;
    // ...
}

// DESPUES: position: sticky (participa en el flujo del documento)
.landing-header {
    position: sticky;
    top: 0;
    // ...

    // Toolbar admin adjustments (una sola vez)
    .toolbar-fixed & {
        top: 39px;
    }
    .toolbar-fixed.toolbar-tray-open.toolbar-horizontal & {
        top: 79px;
    }
}

// Override SOLO para landing pages con hero fullscreen
body.landing-page,
body.page-front {
    .landing-header {
        position: fixed;
    }
}
```

### Eliminacion de padding-top compensatorios
| Archivo | Antes | Despues |
|---------|-------|---------|
| `_page-premium.scss` `.main-content` | `padding-top: 80px` | `padding-top: 1.5rem` |
| `_user-pages.scss` `.user-main` | `padding-top: 96px` (luego 120px) | `padding-top: 1.5rem` |
| `_error-pages.scss` `.error-page` | `padding-top: 80px` | `padding-top: 1.5rem` |

### Especificidad CSS
- `.landing-header` → especificidad (0, 1, 0) → `position: sticky` (default)
- `body.landing-page .landing-header` → especificidad (0, 2, 1) → `position: fixed` (override)
- El override SIEMPRE gana sin importar el orden en el archivo.

---

## 4. Regla Derivada

**CSS-STICKY-001** (P0): El `.landing-header` DEBE usar `position: sticky` por defecto. Solo las landing pages con hero fullscreen (`body.landing-page`, `body.page-front`) usan `position: fixed`. Las areas de contenido NO DEBEN tener `padding-top` compensatorio para header fijo — solo padding estetico (`1.5rem`). El ajuste de toolbar admin (`top: 39px/79px`) se aplica globalmente en el SCSS del header.

---

## 5. Archivos Modificados

1. `scss/components/_landing-page.scss` — sticky default + fixed override + toolbar top
2. `scss/components/_page-premium.scss` — padding 80px → 1.5rem
3. `scss/components/_error-pages.scss` — padding 80px → 1.5rem
4. `scss/_user-pages.scss` — padding 120px → 1.5rem, eliminados overrides scoped

---

## 6. Leccion Aprendida

> **Nunca uses `position: fixed` para un header cuya altura puede variar.** `position: sticky` es estrictamente superior para headers de navegacion porque participa en el flujo del documento, eliminando la necesidad de compensar con `padding-top` fragiles. Reservar `position: fixed` unicamente para overlays intencionales (heroes fullscreen, modales).

### Patron de decision
```
¿El elemento debe solapar contenido intencionalmente?
  SI → position: fixed (heroes, modales, overlays)
  NO → position: sticky (headers, barras de navegacion, toolbars)
```

---

## 7. Trazabilidad

- **Regla:** CSS-STICKY-001
- **Regla de Oro:** #27
- **Directrices:** v63.0.0
- **Arquitectura:** v62.2.0
- **Flujo de Trabajo:** v17.1.0
- **Indice General:** v83.0.0
- **Commit:** 3355dae2
