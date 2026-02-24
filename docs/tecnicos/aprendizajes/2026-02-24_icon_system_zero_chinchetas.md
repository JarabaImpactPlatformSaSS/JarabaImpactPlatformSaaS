# Aprendizaje #117 — Icon System: Zero Chinchetas

**Fecha:** 2026-02-24
**Modulos afectados:** ecosistema_jaraba_core, jaraba_interactive, jaraba_i18n, jaraba_facturae, jaraba_resources, jaraba_page_builder
**Reglas:** ICON-CONVENTION-001 (P0), ICON-DUOTONE-001 (P1), ICON-COLOR-001 (P1)
**Regla de oro:** #32

---

## Contexto

La funcion Twig `jaraba_icon()` renderiza iconos SVG inline desde `ecosistema_jaraba_core/images/icons/{category}/{name}[-variant].svg`. Cuando el SVG no existe, muestra un emoji fallback via `getFallbackEmoji()`. El fallback por defecto es la chincheta 📌 — un indicador visual de que falta un icono.

## Problema

1. **Convenciones de llamada rotas:** 4 modulos usaban formatos incorrectos de `jaraba_icon()`:
   - **Path-style:** `jaraba_icon('ui/arrow-left', 'outline')` — 17 llamadas en jaraba_interactive
   - **Args invertidos:** `jaraba_icon('star', 'micro')` — 9 llamadas en jaraba_i18n
   - **Args invertidos:** `jaraba_icon('invoice', 'fiscal')` — 8 llamadas en jaraba_facturae
   - **4 args posicionales:** `jaraba_icon('download', 'outline', 'white', '20')` — 13 llamadas en jaraba_resources

2. **SVGs faltantes:** ~170 pares category/name referenciados en templates no tenian SVG correspondiente en el filesystem, causando fallback a emoji 📌.

3. **Symlinks rotos/circulares:** `ui/save.svg` apuntaba a si mismo, `bookmark.svg` en raiz era circular, `general/alert-duotone.svg` apuntaba a target inexistente.

## Solucion

### Firma correcta
```twig
{{ jaraba_icon('category', 'name', { variant: 'duotone', color: 'azul-corporativo', size: '24px' }) }}
```

Nunca:
- `jaraba_icon('ui/arrow-left', 'outline')` (path-style)
- `jaraba_icon('star', 'micro')` (args invertidos)
- `jaraba_icon('download', 'outline', 'white', '20')` (posicional)

### Bridge categories
Directorios de symlinks que mapean categorias usadas en templates a iconos existentes en categorias primarias:

| Bridge | Primaria | Ejemplo |
|--------|----------|---------|
| achievement/ | actions/ | trophy, medal, target |
| finance/ | fiscal/ | wallet, credit-card, coins |
| general/ | ui/ | settings, info, alert-triangle |
| legal/ | ui/ | scale, shield, file-text |
| navigation/ | ui/ | home, menu, compass |
| status/ | ui/ | check-circle, clock, alert-circle |
| tools/ | ui/ | wrench, code, terminal |
| media/ | ui/ | play-circle, image, camera |
| users/ | ui/ | user, group, id-card |

Cada bridge tiene symlinks outline + duotone: `icon.svg → ../primary/icon.svg` y `icon-duotone.svg → ../primary/icon-duotone.svg`.

### Protocolo de auditoria
1. Extraer todos los pares unicos: `grep -oP "jaraba_icon\('([^']+)',\s*'([^']+)'" | sort -u`
2. Para cada par, verificar existencia de outline y duotone en filesystem
3. Crear symlinks faltantes en bridge category → categoria primaria
4. Re-verificar: `find images/icons/ -type l ! -exec test -e {} \; -print` (symlinks rotos)
5. Verificar circulares: `readlink -f` debe apuntar a archivo real
6. Verificar convenciones: grep por patrones rotos (path-style, 4 args, etc.)

### Duotone-first policy
Todo icono en templates premium usa `variant: 'duotone'`. El variante duotone agrega capas de fondo con `opacity: 0.2` + `fill: currentColor`, creando profundidad visual coherente con glassmorphism.

### Colores Jaraba
Solo colores de la paleta: `azul-corporativo` (#233D63), `naranja-impulso` (#FF8C42), `verde-innovacion` (#00A9A5), `white`, `neutral`.

## Resultado

- **305 pares unicos** verificados en todo el codebase
- **0 chinchetas** restantes
- **32 llamadas** con convencion rota corregidas en 4 modulos
- **~170 SVGs/symlinks** creados
- **3 symlinks** reparados (2 circulares, 1 roto)
- **177 templates** Page Builder verificados
- **4 commits** pushed

## Leccion clave

La chincheta 📌 es un canario en la mina de carbon del sistema de iconos. Su aparicion indica siempre uno de dos problemas: (1) convencion de llamada incorrecta, o (2) SVG faltante en el filesystem. La solucion sistematica es verificar **todos** los pares en el codebase contra el filesystem, no solo los visibles en una pagina concreta.
