# Aprendizaje: Arquitectura Theming SaaS - Federated Design Tokens

**Fecha:** 2026-02-05
**Contexto:** Refactorización de estructura SCSS del módulo `jaraba_page_builder`
**Impacto:** Alto - Define patrón arquitectónico para todo el proyecto

---

## 📋 Resumen Ejecutivo

Se diseñó e implementó el patrón **"Federated Design Tokens"** para la arquitectura SCSS del SaaS, estableciendo `ecosistema_jaraba_core` como **Single Source of Truth (SSOT)** para todas las variables de diseño.

---

## 🔍 Problema Identificado

| Aspecto | Situación Detectada |
|---------|---------------------|
| **57 archivos SCSS** | Fragmentados en 6+ módulos |
| **Variables duplicadas** | Cada módulo redefinía `$ej-*` |
| **Sin package.json** | En módulos satélite (compilación manual) |
| **Build descentralizado** | Comandos `npx` individuales sin estándar |

---

## ✅ Solución Implementada

### Patrón Federated Design Tokens

```
ecosistema_jaraba_core (SSOT)
    └── scss/_variables.scss    ← Fallbacks SCSS
    └── scss/_injectable.scss   ← CSS Custom Properties (:root)
            ↓
    Módulos Satélite (SOLO consumen)
        └── var(--ej-*, #fallback)  ← NO definen $ej-*
```

### Regla de Oro

> **Los módulos satélite NO DEBEN definir variables SCSS.**
> Solo consumen CSS Custom Properties con fallbacks inline.

```scss
// ✅ CORRECTO
.component { color: var(--ej-color-corporate, #233D63); }

// ❌ INCORRECTO
$ej-color-corporate: #233D63;  // NUNCA en módulos
```

---

## 📁 Documentación Generada

| Documento | Ubicación |
|-----------|-----------|
| **Maestro Arquitectura Theming** | `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md` |
| **KI Standards (actualizado)** | `knowledge/standards/theming_architecture_master.md` |
| **Índice General (v9.1)** | `docs/00_INDICE_GENERAL.md` |

---

## 🛠️ Package.json Estándar

Todo módulo con SCSS ahora debe incluir:

```json
{
    "name": "jaraba-[module-name]",
    "version": "1.0.0",
    "scripts": {
        "build": "sass scss/main.scss:css/[output].css --style=compressed",
        "watch": "sass --watch scss:css --style=compressed"
    },
    "devDependencies": { "sass": "^1.71.0" }
}
```

---

## 📊 Roadmap de Consolidación

| Fase | Módulos | Estado |
|------|---------|--------|
| 1 | `jaraba_page_builder` | 🔄 En progreso |
| 2 | `jaraba_i18n`, `jaraba_interactive` | ⏳ Pendiente |
| 3 | `jaraba_site_builder`, `jaraba_foc` | ⏳ Pendiente |
| 4 | Módulos restantes (5) | ⏳ Pendiente |

---

## 💡 Lecciones Clave

1. **SSOT es crítico**: La duplicación de variables genera inconsistencia visual
2. **CSS vars > SCSS vars para runtime**: Permite branding por tenant sin recompilar
3. **package.json obligatorio**: Estandariza compilación y facilita CI/CD
4. **Documentar primero**: El patrón documentado previene futuras desviaciones

---

## 🔗 Referencias

- [Workflow SCSS](../../.agent/workflows/scss-estilos.md)
- [Branding & Theming KI](../../.gemini/knowledge/.../branding_and_theming.md)
- [Standards Overview](../../.gemini/knowledge/.../standards_overview.md)
