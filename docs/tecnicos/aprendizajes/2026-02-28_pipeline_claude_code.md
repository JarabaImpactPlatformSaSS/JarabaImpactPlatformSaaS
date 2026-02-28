# Aprendizaje #154 — Pipeline de Desarrollo Claude Code — 5 Capas DX

**Fecha:** 2026-02-28
**Contexto:** Implementacion completa de la pipeline de desarrollo asistido por IA para Claude Code, con 14 ficheros, 5 capas funcionales y 30 tests de verificacion funcional.
**Documentos referencia:**
- Directrices v104.0.0: 3 reglas HOOK-PRETOOLUSE-001, HOOK-POSTLINT-001, CLAUDE-SUBAGENT-001
- Arquitectura v93.0.0: seccion 10.6 Developer Experience Pipeline
- Plan: `docs/implementacion/2026-02-28_Plan_Implementacion_Claude_Code_Pipeline_v1.md`
- Estado SaaS: `docs/tecnicos/2026-02-28_Estado_SaaS_Claude_Chat_v1.md`

---

## Problema

El desarrollo asistido por Claude Code carecia de guardrails especificos para el proyecto. Sin hooks de proteccion, Claude podia modificar archivos criticos (composer.lock, settings.secrets.php). Sin lint automatico, podia introducir violaciones de directrices (hex hardcoded, extends ContentEntityForm, URLs hardcoded en JS). Sin subagentes especializados, la revision de codigo y tests eran genericos. Sin slash commands, los workflows repetitivos (crear entidad, verificar runtime, auditar WCAG) requerian instrucciones manuales cada vez.

## Solucion

Pipeline de 5 capas:

### Capa 1: Proteccion (PreToolUse hook)
- `pre-tool-use.sh` lee JSON stdin via python3
- Extrae `tool_input.file_path` directamente (no navegar dentro de `content`)
- 7 archivos bloqueados (exit 2 + JSON deny en stdout)
- Master docs y CI workflows con `permissionDecision:ask`

### Capa 2: Lint (PostToolUse hook)
- `post-edit-lint.sh` valida por extension: PHP, SCSS, JS, Twig
- PHP: syntax check + ContentEntityForm + eval/exec/shell_exec
- SCSS: hex sin var(--ej-*) + @import + rgba(#hex)
- JS: fetch con URL hardcoded + innerHTML sin checkPlain
- Twig: |raw + textos sin trans

### Capa 3: Subagentes
- reviewer: 4 niveles severidad, 20+ directrices del proyecto
- tester: 7 reglas PHP 8.4, templates Unit+Kernel
- security-auditor: 10 categorias de escaneo

### Capa 4: Slash Commands
- /fix-issue: workflow 8 pasos desde issue hasta PR
- /create-entity: scaffold 9 archivos (Entity+Form+Access+List+Settings+routing+menu+task+preprocess)
- /verify-runtime: 12 checks RUNTIME-VERIFY-001
- /audit-wcag: 10 categorias WCAG 2.1 AA adaptadas al proyecto

### Capa 5: Integraciones
- MCP Stripe via .mcp.json
- CLAUDE.md (236 LOC) con identidad y reglas del proyecto
- Estado del SaaS (793 LOC) para Claude Chat Project Knowledge

## Bugs Encontrados y Corregidos

### Bug 1: Python extraction AttributeError (CRITICO)
**Causa raiz:** `ti.get('content', {}).get('file_path', '')` falla cuando Write tool envia `content` como string (no dict). `.get()` no existe en strings.
**Sintoma:** Archivos protegidos editados via Write no se bloqueaban. El `2>/dev/null || echo ""` silenciaba el error.
**Fix:** Simplificar a `ti.get('file_path', '')` — file_path siempre es clave directa de tool_input en Edit y Write.
**Leccion:** NUNCA asumir la estructura del campo `content` en tool_input. Solo usar claves de primer nivel documentadas.

### Bug 2: JSON deny en stderr (MEDIO)
**Causa raiz:** La respuesta JSON deny iba a stderr (`>&2`). Claude Code parsea stdout para `permissionDecisionReason`.
**Sintoma:** Claude Code bloqueaba la operacion (exit 2 funciona) pero no mostraba el motivo estructurado.
**Fix:** Mover echo deny a stdout (sin redirect).
**Leccion:** Claude Code hooks: stdout = respuesta formal parseada, stderr = texto de error crudo. Exit code = decision binaria.

### Bug 3: grep single-line false positives (MENOR)
**Causa raiz:** `grep -rn '<img ' | grep -v 'alt='` no detecta `alt=` en la linea siguiente al `<img`.
**Sintoma:** 4 false positives en templates con `<img>` multilinea.
**Fix:** `grep -rPzol '<img[^>]*(?<!alt=)[^>]*>'` con lookbehind negativo multilinea.
**Leccion:** HTML templates Drupal frecuentemente usan multilinea. Checks WCAG deben contemplar esto.

## Verificacion Funcional

30 tests ejecutados:
- 5 pre-tool-use (deny×3 + ask×1 + allow×1 con exit codes verificados)
- 14 post-edit-lint (PHP×4 + SCSS×4 + JS×3 + Twig×3)
- 5 verify-runtime (checks contra jaraba_support real)
- 5 audit-wcag (checks contra templates reales)
- 1 MCP Stripe (npx @stripe/mcp disponible)

## Reglas Derivadas

1. **HOOK-PRETOOLUSE-001**: Extraer file_path de tool_input.file_path directo. JSON deny en stdout. Exit 2 para bloqueo.
2. **HOOK-POSTLINT-001**: Lint por extension < 5s. Errores a stdout con exit 2. Patrones del proyecto, no genericos.
3. **CLAUDE-SUBAGENT-001**: Subagentes con directrices REALES del proyecto, no genericas. Context:fork para aislamiento.

## Regla de Oro #91

> Hooks Claude Code: file_path directo de tool_input (NUNCA navegar en content), deny JSON en stdout (no stderr), lint < 5s por archivo con patrones especificos del proyecto, subagentes que referencian directrices reales (PREMIUM-FORMS, CSS-VAR-ALL-COLORS, TENANT-ISOLATION, etc.).
