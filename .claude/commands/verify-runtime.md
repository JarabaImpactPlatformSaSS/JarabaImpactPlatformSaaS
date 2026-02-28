---
description: >
  Verificacion RUNTIME-VERIFY-001 post-implementacion. Ejecuta 12 checks
  sistematicos para cerrar el gap entre "el codigo existe" y "el usuario
  lo experimenta". Uso: /verify-runtime [ruta_o_modulo]
argument-hint: "[ruta_o_modulo]"
allowed-tools: Read, Grep, Glob, Bash(node *), Bash(find *), Bash(stat *), Bash(lando *), Bash(npm *), Bash(diff *)
---

# /verify-runtime — Verificacion Post-Implementacion RUNTIME-VERIFY-001

Ejecuta verificacion sistematica de la cadena completa:
**PHP -> Twig -> SCSS -> CSS compilado -> JS -> drupalSettings -> DOM final**

Ambito de verificacion: `$ARGUMENTS` (puede ser una ruta como `/ayuda`, un modulo
como `jaraba_support`, o vacio para verificar los ultimos archivos modificados).

## Check 1: CSS Compilado (Timestamp)

Verificar que el CSS compilado es mas reciente que el SCSS fuente.

```bash
# Obtener timestamp del CSS mas reciente
CSS_DIR="web/themes/custom/ecosistema_jaraba_theme/css"
SCSS_DIR="web/themes/custom/ecosistema_jaraba_theme/scss"

echo "=== SCSS vs CSS Timestamps ==="
# Encontrar el SCSS mas reciente
LATEST_SCSS=$(find "$SCSS_DIR" -name '*.scss' -printf '%T@ %p\n' 2>/dev/null | sort -rn | head -1)
LATEST_CSS=$(find "$CSS_DIR" -name '*.css' -not -name '*.map' -printf '%T@ %p\n' 2>/dev/null | sort -rn | head -1)

echo "SCSS mas reciente: $LATEST_SCSS"
echo "CSS mas reciente: $LATEST_CSS"
```

**CRITICO**: Si timestamp SCSS > CSS, ejecutar recompilacion:
```bash
cd web/themes/custom/ecosistema_jaraba_theme && npm run build && cd -
```

## Check 2: SCSS Orphans

Verificar que no hay parciales SCSS sin importar en main.scss.

```bash
node web/themes/custom/ecosistema_jaraba_theme/scripts/check-scss-orphans.js
```

**CRITICO**: Parciales huerfanos causan estilos no aplicados. Cada parcial DEBE
tener `@use` en el entry point correspondiente.

## Check 3: Colores Hardcoded en SCSS

Detectar violaciones de CSS-VAR-ALL-COLORS-001.

```bash
echo "=== Colores hex hardcoded (violacion CSS-VAR-ALL-COLORS-001) ==="
# Buscar hex no dentro de var() fallback
grep -rn '#[0-9a-fA-F]\{3,8\}\b' \
  web/themes/custom/ecosistema_jaraba_theme/scss/ \
  web/modules/custom/*/scss/ \
  --include='*.scss' | \
  grep -v 'var(--ej-' | \
  grep -v '^\s*//' | \
  grep -v '^\s*\*' | \
  grep -v '^\s*\$' | \
  grep -v 'sass:color' | \
  head -20
```

Cada hex encontrado DEBE convertirse a `var(--ej-nombre, #hex)`.

## Check 4: URLs Hardcoded en JS

Detectar violaciones de ROUTE-LANGPREFIX-001.

```bash
echo "=== URLs hardcoded en JS (violacion ROUTE-LANGPREFIX-001) ==="
grep -rn "fetch\s*(\s*['\"]/" \
  web/modules/custom/ \
  web/themes/custom/ \
  --include='*.js' | \
  grep -v 'drupalSettings' | \
  grep -v 'node_modules' | \
  grep -v '/session/token' | \
  head -20
```

**CRITICO**: El sitio usa prefix `/es/`. URLs hardcoded causan 404 o pierden POST body
en redirects. TODAS las URLs DEBEN venir de `drupalSettings`.

## Check 5: Rutas Accesibles

Verificar que las rutas del modulo responden correctamente.

```bash
echo "=== Verificacion de rutas ==="
# Listar rutas del modulo
lando drush route:list --path="/ruta" 2>/dev/null || echo "Verificar manualmente"
```

Para cada ruta del modulo, verificar que:
- Existe en el .routing.yml
- Tiene requirements adecuados (_permission, _entity_access, etc.)
- El controller/form referenciado existe

## Check 6: data-* Selectores JS ↔ HTML

Verificar que los selectores data-* usados en JS matchean los del HTML.

```bash
echo "=== data-* en JS ==="
grep -rn "data-[a-z]" web/modules/custom/$ARGUMENTS/ --include='*.js' | \
  grep -oP "data-[a-z-]+" | sort -u

echo "=== data-* en Twig ==="
grep -rn "data-[a-z]" web/modules/custom/$ARGUMENTS/ --include='*.html.twig' | \
  grep -oP "data-[a-z-]+" | sort -u
```

**WARNING**: Cada `data-*` en JS DEBE tener su correspondiente en HTML.
Selectores huerfanos causan JS silenciosamente inactivo.

## Check 7: Traducciones (I18N)

Verificar textos sin wrapper de traduccion.

```bash
echo "=== Textos sin traduccion en Twig ==="
# Buscar texto visible sin {% trans %}
grep -rn '>\s*[A-ZÁÉÍÓÚ][a-záéíóúñ ]\{5,\}' \
  web/modules/custom/$ARGUMENTS/templates/ \
  web/themes/custom/ecosistema_jaraba_theme/templates/ \
  --include='*.html.twig' | \
  grep -v 'trans' | \
  grep -v '{#' | \
  head -10

echo "=== Textos sin Drupal.t() en JS ==="
grep -rn "textContent\s*=\s*['\"]" \
  web/modules/custom/$ARGUMENTS/ \
  --include='*.js' | \
  grep -v 'Drupal.t' | \
  head -10
```

## Check 8: Body Classes

Verificar que las body classes se inyectan via hook_preprocess_html().

```bash
echo "=== Body classes en templates (INCORRECTO) ==="
grep -rn 'attributes.addClass' \
  web/themes/custom/ecosistema_jaraba_theme/templates/ \
  --include='page--*.html.twig' | \
  head -5

echo "=== Body classes en preprocess (CORRECTO) ==="
grep -n 'body.*class\|addClass' \
  web/themes/custom/ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme | \
  grep 'preprocess_html' -A 20 | \
  head -10
```

**Regla**: `attributes.addClass()` en template NO funciona para body.
DEBE ser `hook_preprocess_html()`.

## Check 9: Iconos

Verificar uso correcto de iconos (ICON-COLOR-001, ICON-CONVENTION-001).

```bash
echo "=== Iconos con colores no estandar ==="
grep -rn 'jaraba_icon' \
  web/modules/custom/$ARGUMENTS/templates/ \
  --include='*.html.twig' | \
  grep -v 'azul-corporativo\|naranja-impulso\|verde-innovacion\|white\|neutral' | \
  head -5

echo "=== SVG con currentColor (no valido en canvas) ==="
grep -rn 'currentColor' \
  web/modules/custom/$ARGUMENTS/ \
  --include='*.php' | \
  grep -i 'canvas\|grapesjs\|page_builder' | \
  head -5
```

## Check 10: Slide-Panel Forms

Si hay forms de frontend, verificar patron slide-panel.

```bash
echo "=== Forms con render() en vez de renderPlain() ==="
grep -rn '\$this->formBuilder.*render(' \
  web/modules/custom/$ARGUMENTS/ \
  --include='*Controller.php' | \
  grep -v 'renderPlain\|renderForm' | \
  head -5

echo "=== Forms sin #action explicito ==="
grep -rn 'isSlidePanelRequest\|isXmlHttpRequest' \
  web/modules/custom/$ARGUMENTS/ \
  --include='*Controller.php' | \
  head -5
```

**Regla SLIDE-PANEL-RENDER-001**: Slide-panel DEBE usar `renderPlain()`.
Form DEBE tener `$form['#action'] = $request->getRequestUri()`.

## Check 11: drupalSettings via Preprocess

Verificar que drupalSettings se inyecta correctamente en zero-region pages.

```bash
echo "=== drupalSettings en controller (INCORRECTO en zero-region) ==="
grep -rn "drupalSettings\|#attached.*drupalSettings" \
  web/modules/custom/$ARGUMENTS/ \
  --include='*Controller.php' | \
  head -5

echo "=== drupalSettings en preprocess (CORRECTO) ==="
grep -rn "drupalSettings" \
  web/modules/custom/$ARGUMENTS/*.module \
  2>/dev/null | head -5
```

**Regla ZERO-REGION-003**: `#attached` del controller NO se procesa en zero-region.
drupalSettings DEBE ir en `hook_preprocess_page()`.

## Check 12: Entity Integrity

Si se creo o modifico una entidad, verificar completitud.

```bash
echo "=== Entities sin AccessControlHandler ==="
grep -rn '@ContentEntityType' web/modules/custom/$ARGUMENTS/ --include='*.php' -l | \
  while read f; do
    if ! grep -q '"access"' "$f"; then
      echo "FALTA ACCESS HANDLER: $f"
    fi
  done

echo "=== Entities sin Views data ==="
grep -rn '@ContentEntityType' web/modules/custom/$ARGUMENTS/ --include='*.php' -l | \
  while read f; do
    if ! grep -q 'views_data' "$f"; then
      echo "FALTA VIEWS DATA: $f"
    fi
  done

echo "=== Forms sin PremiumEntityFormBase ==="
grep -rn 'extends ContentEntityForm' \
  web/modules/custom/$ARGUMENTS/ \
  --include='*Form.php' | head -5
```

## Resumen del Reporte

```
# Verificacion RUNTIME-VERIFY-001

| # | Check | Estado | Detalles |
|---|-------|--------|----------|
| 1 | CSS compilado | OK/FAIL | Timestamps |
| 2 | SCSS orphans | OK/FAIL | Parciales huerfanos |
| 3 | Colores hardcoded | OK/WARN | N violaciones |
| 4 | URLs hardcoded JS | OK/FAIL | N violaciones |
| 5 | Rutas accesibles | OK/FAIL | Rutas verificadas |
| 6 | data-* selectores | OK/WARN | Selectores huerfanos |
| 7 | Traducciones | OK/WARN | Textos sin trans |
| 8 | Body classes | OK/FAIL | Metodo correcto |
| 9 | Iconos | OK/WARN | Colores estandar |
| 10 | Slide-panel | OK/FAIL | renderPlain pattern |
| 11 | drupalSettings | OK/WARN | Preprocess vs controller |
| 12 | Entity integrity | OK/FAIL | Access + Views + Form |
```
