# F12 — Lenis Integration Premium

**Fecha**: 2026-02-12
**Fase**: 12 de 12 (FINAL)
**Spec**: Plan_Cierre_Gaps_Specs_20260128_Clase_Mundial.md §17
**Horas estimadas**: 8-12h
**Impacto**: Arquitectura Técnica 9.0 → 10.0

## Objetivo

Integrar Lenis smooth scroll en landing pages y homepage para navegación premium.
Esta fase cierra el plan de 12 fases para alcanzar nivel "Clase Mundial".

## Alcance

### Entregables
1. **`js/lenis-scroll.js`** — Drupal.behaviors con Lenis, accesibilidad, exclusión admin
2. **Librería CDN Lenis** — Registro en `libraries.yml` (patrón CDN como Alpine.js)
3. **Librería `lenis-scroll`** — Registro en `libraries.yml` con dependencia Lenis CDN
4. **Attachment condicional** — Solo páginas frontend: homepage + landing pages

### Directrices aplicadas
- **Accesibilidad**: `@media (prefers-reduced-motion: reduce)` → desactivar
- **Mobile-first**: `smoothTouch: false` (no interferir con scroll táctil nativo)
- **Admin exclusion**: `body.path-admin` → no activar
- **i18n**: No aplica (JS sin strings visibles)
- **CSS Variables**: No requiere SCSS adicional (Lenis es puro JS)

## Arquitectura

```
ecosistema_jaraba_theme/
├── js/
│   └── lenis-scroll.js          ← NUEVO (Drupal.behaviors)
└── ecosistema_jaraba_theme.libraries.yml  ← MODIFICADO (+2 libraries)
```

### Páginas destino
- `page--front.html.twig` (homepage)
- Landing pages verticales (via hook condicional en `.theme`)
- **EXCLUIDAS**: dashboards admin, auth pages, canvas editors

### Compatibilidad con scroll existente
- `scroll-animations.js` tiene `smoothScroll` behavior (scrollIntoView)
- Lenis intercepta el scroll nativo → scrollIntoView sigue funcionando
- `back-to-top.js` usa `window.scrollTo` → compatible con Lenis
- No hay conflictos: Lenis mejora la experiencia sin romper lo existente

## Verificación

- [ ] `drush cr` exitoso
- [ ] Librería `lenis-scroll` registrada en theme
- [ ] JS sin errores de sintaxis
- [ ] No se carga en páginas admin
- [ ] Respeta `prefers-reduced-motion`
