---
description: >
  Auditoria de accesibilidad WCAG 2.1 AA adaptada al stack de Jaraba Impact Platform.
  Verifica contraste, teclado, screen readers, responsive, motion, touch targets,
  i18n, y patrones especificos del proyecto (--ej-*, slide-panel, zero-region).
  Uso: /audit-wcag [url_o_template]
argument-hint: "[url_o_template]"
allowed-tools: Read, Grep, Glob, Bash(lando *), Bash(curl *), Bash(node *)
---

# /audit-wcag — Auditoria de Accesibilidad WCAG 2.1 AA

Ejecuta auditoria de accesibilidad para `$ARGUMENTS` (puede ser una URL como
`/ayuda`, un template como `help-center.html.twig`, o un modulo como `jaraba_support`).

## Criterios Adaptados al Proyecto

Colores de marca Jaraba:
- Azul corporativo: #233D63 (contraste sobre blanco: 10.2:1)
- Naranja impulso: #FF8C42 (contraste sobre blanco: 2.7:1 — ATENCION con texto pequeño)
- Verde innovacion: #00A9A5 (contraste sobre blanco: 3.2:1 — ATENCION con texto pequeño)

## 1. Contraste de Color (WCAG 1.4.3, 1.4.6)

### Texto Normal (>= 4.5:1)

```bash
echo "=== Colores con posible problema de contraste ==="
# Naranja sobre blanco: 2.7:1 — INSUFICIENTE para texto normal
grep -rn 'color.*var(--ej-naranja\|color.*#FF8C42\|color.*#ff8c42' \
  web/themes/custom/ecosistema_jaraba_theme/scss/ \
  web/modules/custom/*/scss/ \
  --include='*.scss' | \
  grep -v 'background\|border\|fill\|stroke\|shadow\|outline' | \
  head -10

# Verde sobre blanco: 3.2:1 — INSUFICIENTE para texto normal
grep -rn 'color.*var(--ej-verde\|color.*#00A9A5\|color.*#00a9a5' \
  web/themes/custom/ecosistema_jaraba_theme/scss/ \
  web/modules/custom/*/scss/ \
  --include='*.scss' | \
  grep -v 'background\|border\|fill\|stroke\|shadow\|outline' | \
  head -10
```

**Regla**: Naranja y verde de Jaraba NO cumplen 4.5:1 para texto sobre fondo blanco.
Soluciones: usar sobre fondo oscuro, usar solo para decoracion/iconos, o usar
variante oscurecida.

### Texto Grande (>= 3:1)
Texto >= 18pt (24px) o >= 14pt (18.66px) bold: ratio minimo 3:1.

### Componentes UI (>= 3:1)
Bordes, iconos, controles: ratio minimo 3:1.

## 2. Navegacion por Teclado (WCAG 2.1.1, 2.1.2, 2.4.3)

### Verificar en Templates

```bash
echo "=== Elementos interactivos sin tabindex o role ==="
grep -rn '<div.*onclick\|<span.*onclick' \
  web/modules/custom/$ARGUMENTS/templates/ \
  web/themes/custom/ecosistema_jaraba_theme/templates/ \
  --include='*.html.twig' | \
  grep -v 'role=\|tabindex=' | \
  head -10

echo "=== Links sin href (no tabulables) ==="
grep -rn '<a ' \
  web/modules/custom/$ARGUMENTS/templates/ \
  --include='*.html.twig' | \
  grep -v 'href=' | \
  head -5
```

### Verificar en JS

```bash
echo "=== Click handlers sin keydown equivalente ==="
grep -rn "addEventListener.*'click'" \
  web/modules/custom/$ARGUMENTS/ \
  --include='*.js' | head -10

echo "=== Verificar que existe keydown para cada click ==="
grep -rn "addEventListener.*'keydown\|addEventListener.*'keypress'" \
  web/modules/custom/$ARGUMENTS/ \
  --include='*.js' | head -10
```

**Regla**: Todo lo que responde a click DEBE responder a Enter/Space.
Slide-panel: cerrar con Escape, trap focus dentro del panel.

## 3. Focus Visible (WCAG 2.4.7)

```bash
echo "=== outline: none sin alternativa ==="
grep -rn 'outline:\s*none\|outline:\s*0' \
  web/themes/custom/ecosistema_jaraba_theme/scss/ \
  web/modules/custom/*/scss/ \
  --include='*.scss' | \
  grep -v 'focus-visible\|focus-within\|:focus' | \
  head -10
```

**Regla**: `outline: none` SOLO es aceptable si se reemplaza con otro indicador
visual de focus (box-shadow, border, etc.) con >= 2px y contraste >= 3:1.

## 4. Landmarks y Headings (WCAG 1.3.1, 2.4.6)

```bash
echo "=== Templates sin landmarks ==="
# Verificar que paginas tienen <main>, <nav>, <header>, <footer>
for tpl in web/themes/custom/ecosistema_jaraba_theme/templates/page--*.html.twig; do
  echo "--- $tpl ---"
  grep -c '<main\|<nav\|<header\|<footer\|role="main"\|role="navigation"' "$tpl" 2>/dev/null
done

echo "=== Headings no jerarquicos ==="
# Verificar que no hay saltos en jerarquia (h1 -> h3 sin h2)
grep -rn '<h[1-6]' \
  web/modules/custom/$ARGUMENTS/templates/ \
  --include='*.html.twig' | \
  grep -oP '<h[1-6]' | sort
```

**Regla**: Cada pagina DEBE tener un `<h1>` unico. Jerarquia: h1 > h2 > h3.
No saltar niveles (h1 -> h3 sin h2).

## 5. Imagenes y Media (WCAG 1.1.1)

```bash
echo "=== Imagenes sin alt ==="
grep -rn '<img ' \
  web/modules/custom/$ARGUMENTS/templates/ \
  web/themes/custom/ecosistema_jaraba_theme/templates/ \
  --include='*.html.twig' | \
  grep -v 'alt=' | \
  head -10

echo "=== SVG sin aria-label o aria-hidden ==="
grep -rn '<svg' \
  web/modules/custom/$ARGUMENTS/templates/ \
  --include='*.html.twig' | \
  grep -v 'aria-label\|aria-hidden\|role=' | \
  head -10
```

**Regla**: Imagenes decorativas: `alt=""` + `aria-hidden="true"`.
Imagenes informativas: `alt="descripcion"`.
SVG decorativos: `aria-hidden="true"`. SVG informativos: `role="img" aria-label="..."`.

## 6. Formularios (WCAG 1.3.1, 3.3.2, 4.1.2)

```bash
echo "=== Inputs sin label asociado ==="
grep -rn '<input ' \
  web/modules/custom/$ARGUMENTS/templates/ \
  --include='*.html.twig' | \
  grep -v 'type="hidden"\|type="submit"' | \
  grep -v 'aria-label\|aria-labelledby\|id=' | \
  head -10

echo "=== Campos required sin aria-required ==="
grep -rn 'required' \
  web/modules/custom/$ARGUMENTS/templates/ \
  --include='*.html.twig' | \
  grep -v 'aria-required' | \
  head -5
```

**Nota**: PremiumEntityFormBase genera labels automaticamente para entity forms.
Verificar solo en templates custom y forms standalone.

## 7. Responsive (WCAG 1.4.10)

```bash
echo "=== Contenido con overflow hidden fijo ==="
grep -rn 'overflow:\s*hidden' \
  web/themes/custom/ecosistema_jaraba_theme/scss/ \
  web/modules/custom/*/scss/ \
  --include='*.scss' | \
  grep -v '@media\|text-overflow\|overflow-x\|overflow-y' | \
  head -10

echo "=== Anchos fijos que pueden romper en movil ==="
grep -rn 'width:\s*[0-9]\{3,\}px' \
  web/themes/custom/ecosistema_jaraba_theme/scss/ \
  --include='*.scss' | \
  grep -v 'max-width\|min-width\|@media' | \
  head -10
```

**Regla**: Mobile-first. Todo DEBE funcionar en 320px.
No `overflow: hidden` que oculte contenido. No anchos fijos > 300px sin media query.

## 8. Motion y Animaciones (WCAG 2.3.1, 2.3.3)

```bash
echo "=== Animaciones sin prefers-reduced-motion ==="
grep -rn 'animation:\|transition:\|@keyframes' \
  web/themes/custom/ecosistema_jaraba_theme/scss/ \
  web/modules/custom/*/scss/ \
  --include='*.scss' | \
  grep -v 'prefers-reduced-motion\|\.no-js' | \
  head -10

echo "=== Animaciones con opacity:0 sin fallback no-JS ==="
grep -rn 'opacity:\s*0' \
  web/themes/custom/ecosistema_jaraba_theme/scss/ \
  --include='*.scss' | \
  grep -v '\.no-js\|\.is-visible\|hover\|focus\|:active' | \
  head -10
```

**Regla**: TODA animacion DEBE respetar `prefers-reduced-motion: reduce`.
Elementos con `opacity: 0` DEBEN tener fallback `.no-js` que los muestre.

## 9. Touch Targets (WCAG 2.5.5)

```bash
echo "=== Botones/links potencialmente pequenos ==="
grep -rn 'padding:\s*[0-3]px\|height:\s*[0-9]\{1,2\}px\b\|width:\s*[0-9]\{1,2\}px\b' \
  web/themes/custom/ecosistema_jaraba_theme/scss/ \
  --include='*.scss' | \
  grep -i 'btn\|button\|link\|nav\|tab\|action' | \
  head -10
```

**Regla**: Touch targets MINIMO 44x44px (recomendado 48x48px).

## 10. Idioma y Traduccion

```bash
echo "=== Textos visibles sin wrapper de traduccion ==="
grep -rn '>\s*[A-ZÁÉÍÓÚ][a-záéíóúñ ]\{5,\}[^{]*</' \
  web/modules/custom/$ARGUMENTS/templates/ \
  --include='*.html.twig' | \
  grep -v 'trans\|{#' | \
  head -10

echo "=== HTML sin lang attribute ==="
grep -rn '<html' \
  web/themes/custom/ecosistema_jaraba_theme/templates/ \
  --include='*.html.twig' | \
  grep -v 'lang=' | \
  head -3
```

**Regla**: TODOS los textos visibles con {% trans %}.
`<html>` DEBE tener `lang="es"` (o dinamico con {{ html_attributes }}).

## Formato del Reporte

```
# Auditoria WCAG 2.1 AA — {fecha}
## Ambito: {url_o_modulo}

### Resumen
| Criterio | Nivel | Estado | Issues |
|----------|-------|--------|--------|
| 1.1.1 Alt text | A | OK/FAIL | N |
| 1.3.1 Info/Relationships | A | OK/FAIL | N |
| 1.4.3 Contrast (min) | AA | OK/FAIL | N |
| 2.1.1 Keyboard | A | OK/FAIL | N |
| 2.4.3 Focus Order | A | OK/FAIL | N |
| 2.4.7 Focus Visible | AA | OK/FAIL | N |
| 2.5.5 Touch Target | AAA | OK/WARN | N |
| 3.3.2 Labels | A | OK/FAIL | N |
| 4.1.2 Name/Role/Value | A | OK/FAIL | N |

### Issues Encontrados
(Detalle por issue con linea, archivo, criterio WCAG, y remediacion)

### Aprobados
(Areas auditadas sin problemas)
```
