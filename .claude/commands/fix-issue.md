---
description: >
  Resolucion autonoma de issues de GitHub. Analiza el issue, implementa el fix,
  genera tests, ejecuta verificacion RUNTIME-VERIFY-001, y crea un PR.
  Uso: /fix-issue <ISSUE_NUMBER>
argument-hint: "[issue_number]"
allowed-tools: Read, Grep, Glob, Bash(git *), Bash(gh *), Bash(./vendor/bin/phpunit *), Bash(lando *), Bash(npm *), Bash(node *), Edit, Write
---

# /fix-issue — Resolucion Autonoma de Issues

Resuelve el issue de GitHub numero $ARGUMENTS siguiendo el flujo de trabajo completo
del proyecto Jaraba Impact Platform.

## Paso 1: Analizar el Issue

```bash
gh issue view $ARGUMENTS --json title,body,labels,assignees,comments
```

Lee el issue completo, incluyendo comentarios. Identifica:
- Tipo: bug, feature, improvement, refactor
- Modulo(s) afectado(s)
- Archivos probablemente involucrados
- Directrices relevantes del CLAUDE.md

## Paso 2: Crear Branch

```bash
git checkout -b fix/$ARGUMENTS
```

Nombre de branch: `fix/{issue_number}` para bugs, `feat/{issue_number}` para features.

## Paso 3: Investigar el Codigo

Antes de modificar nada:
1. Lee los archivos involucrados completos
2. Busca patrones similares en el codebase con Grep/Glob
3. Verifica las directrices relevantes en CLAUDE.md
4. Identifica si hay tests existentes que cubran el area

## Paso 4: Implementar el Fix

Reglas obligatorias:
- **Fix minimo**: Solo el cambio necesario. NO refactorizar codigo circundante
- **PREMIUM-FORMS-PATTERN-001**: Si tocas un entity form, DEBE extender PremiumEntityFormBase
- **CSS-VAR-ALL-COLORS-001**: Si tocas SCSS, TODOS los colores via var(--ej-*, fallback)
- **ROUTE-LANGPREFIX-001**: Si tocas JS, URLs via drupalSettings, NUNCA hardcoded
- **I18N**: Si tocas Twig, textos con {% trans %}. Si tocas JS, Drupal.t(). Si tocas PHP, $this->t()
- **TENANT-001**: Si tocas queries, DEBEN filtrar por tenant_id
- **SECRET-MGMT-001**: Si tocas configuracion, secretos via getenv(), NUNCA hardcoded

## Paso 5: Generar Tests

Si el issue es un bug:
1. Escribir test que REPRODUCE el bug (debe fallar sin el fix)
2. Verificar que el test PASA con el fix aplicado
3. Seguir reglas de testing del proyecto:
   - phpunit.xml en raiz (NO web/core/phpunit.xml.dist)
   - MOCK-DYNPROP-001: No dynamic properties en mocks (PHP 8.4)
   - KERNEL-TEST-DEPS-001: Listar TODOS los modulos en $modules
   - TEST-CACHE-001: Entity mocks con getCacheContexts/Tags/MaxAge

Si el issue es una feature:
1. Escribir tests que cubran la nueva funcionalidad
2. Incluir test de tenant isolation si la entidad tiene tenant_id

Ejecutar tests:
```bash
./vendor/bin/phpunit --filter=NombreTest
```

## Paso 6: Verificar RUNTIME-VERIFY-001

Si se modificaron archivos SCSS:
```bash
cd web/themes/custom/ecosistema_jaraba_theme && npm run build && cd -
```

Verificar:
1. CSS compilado (timestamp CSS > SCSS)
2. SCSS orphans: `node web/themes/custom/ecosistema_jaraba_theme/scripts/check-scss-orphans.js`
3. Rutas responden: `lando drush router:match /ruta`
4. No hay URLs hardcoded en JS: `grep -rn "fetch.*'/es/" --include='*.js'`
5. data-* selectores matchean entre JS y HTML
6. Body classes via hook_preprocess_html() (no en template)

## Paso 7: Commit y PR

```bash
git add <archivos_especificos>
git commit -m "$(cat <<'EOF'
fix(modulo): descripcion concisa del fix

Resolves #$ARGUMENTS

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>
EOF
)"

git push -u origin fix/$ARGUMENTS

gh pr create --title "fix(modulo): descripcion" --body "$(cat <<'EOF'
## Summary
- Descripcion del problema
- Que se cambio y por que

## Test plan
- [ ] Tests unitarios pasan
- [ ] Verificacion RUNTIME-VERIFY-001
- [ ] Revisado contra directrices del proyecto

Resolves #$ARGUMENTS

Generated with [Claude Code](https://claude.com/claude-code)
EOF
)"
```

## Paso 8: Docs (si aplica)

Si el fix revela un patron nuevo o una leccion aprendida:
- Crear aprendizaje en `docs/tecnicos/aprendizajes/`
- Si es master doc, commit SEPARADO del codigo (COMMIT-SCOPE-001)
- Actualizar CLAUDE.md si se descubre una nueva regla
