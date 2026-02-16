# Elevacion JarabaLex a Vertical Independiente v1

**Fecha:** 2026-02-16
**Estado:** Completado
**Tipo:** Decision Estrategica + Implementacion
**Modulo:** `jaraba_legal_intelligence`
**Package anterior:** Jaraba ServiciosConecta
**Package nuevo:** JarabaLex

---

## 1. Decision Estrategica

### 1.1 Justificacion

`jaraba_legal_intelligence` (137 archivos, 22,703 lineas) estaba clasificado como sub-feature de ServiciosConecta, pero:

1. **No tiene dependencia** en `jaraba_servicios_conecta` — solo depende de `ecosistema_jaraba_core`
2. **Compite con Aranzadi/La Ley** en el mercado de bases de datos juridicas (TAM ~300M EUR)
3. **El TAM como vertical independiente** es 10-50x mayor que como feature de ServiciosConecta
4. **La arquitectura ya soporta la elevacion** con cambios minimos: config entities + theme + metadata + docs

### 1.2 Impacto

- Sin cambios funcionales ni breaking changes
- Sin dependencias nuevas
- Solo metadata, config seed data y documentacion

---

## 2. Inventario de Cambios

| Categoria | Nuevos | Modificados | Total |
|-----------|--------|-------------|-------|
| Config install YMLs | 16 | 0 | 16 |
| Theme templates | 1 | 0 | 1 |
| SCSS | 0 | 2 | 2 |
| PHP | 0 | 1 | 1 |
| Module metadata | 0 | 1 | 1 |
| Config (comentario) | 0 | 1 | 1 |
| Documentacion | 1 | 6 | 7 |
| **Total** | **18** | **11** | **29** |

---

## 3. Archivos Nuevos (18)

### 3.1 Vertical Seed + Features (4)

| Archivo | Descripcion |
|---------|-------------|
| `ecosistema_jaraba_core.vertical.jarabalex.yml` | Seed vertical: machine_name jarabalex, 3 features, 1 AI agent |
| `ecosistema_jaraba_core.feature.legal_search.yml` | Busqueda juridica semantica, category: legal, weight: 20 |
| `ecosistema_jaraba_core.feature.legal_alerts.yml` | Alertas juridicas inteligentes, weight: 21 |
| `ecosistema_jaraba_core.feature.legal_citations.yml` | Citaciones cruzadas, weight: 22 |

### 3.2 SaaS Plans (3)

| Plan | Precio Mensual | Precio Anual | Features |
|------|---------------|-------------|----------|
| Starter | 49 EUR | 490 EUR | legal_search, soporte_email |
| Pro | 99 EUR | 990 EUR | + legal_alerts, legal_citations, soporte_chat |
| Enterprise | 199 EUR | 1990 EUR | + api_access, soporte_dedicado |

### 3.3 FreemiumVerticalLimit (9)

| Plan | searches_per_month | max_alerts | max_bookmarks | Weights |
|------|--------------------|------------|---------------|---------|
| free | 10 | 1 | 10 | 300-302 |
| starter | 50 | 5 | 100 | 303-305 |
| profesional | -1 (ilimitado) | -1 | -1 | 306-308 |

Solo plan `free` tiene `upgrade_message` y `expected_conversion > 0`.

### 3.4 Theme (1)

- `page--legal.html.twig` — Dashboard Legal Intelligence Hub (zero-region, Copilot FAB legal_copilot)

### 3.5 Documentacion (1)

- Este documento

---

## 4. Archivos Modificados (11)

### 4.1 Metadata

- `jaraba_legal_intelligence.info.yml` — package: 'Jaraba ServiciosConecta' → 'JarabaLex'

### 4.2 SCSS

- `_variables-legal.scss` — Bloque `:root {}` con CSS custom properties `--ej-legal-*`
- `_vertical-landing.scss` — Modifier `.vertical-landing--jarabalex`

### 4.3 PHP

- `FeatureAccessService.php` — 3 entradas FEATURE_ADDON_MAP: legal_search, legal_alerts, legal_citations

### 4.4 Config

- `jaraba_legal_intelligence.settings.yml` — Comentario legacy sobre `limits:` (ahora FreemiumVerticalLimit)

### 4.5 Documentacion (6)

- `00_DOCUMENTO_MAESTRO_ARQUITECTURA.md` v34.0.0 — Estado modulo, tabla 12.3, titulo 12.6
- `00_DIRECTRICES_PROYECTO.md` v34.0.0 — Seccion 1.4, nueva seccion 2.10, billing 6 productos
- `00_INDICE_GENERAL.md` v50.0.0 — Blockquote elevacion JarabaLex
- `178_ServiciosConecta_Legal_Intelligence_Hub_v1.md` — Metadata: "Vertical JarabaLex (antes ServiciosConecta)"
- `178A_Legal_Intelligence_Hub_EU_Sources_v1.md` — Metadata actualizada
- `178B_Legal_Intelligence_Hub_Implementation_v1.md` — Metadata actualizada

---

## 5. Futuras Fases

| Fase | Descripcion | Prioridad |
|------|-------------|-----------|
| F1 | Implementacion completa modulo (10 fases, 530-685h) | P0 |
| F2 | Stripe Price IDs para los 3 plans | P1 (pre-launch) |
| F3 | Landing page publica /legal con vertical-landing--jarabalex | P1 |
| F4 | Email sequences SEQ_LEX_001-005 | P2 |
| F5 | Cross-vertical bridges (fiscal, emprendimiento) | P2 |
| F6 | Health Score + Journey Progression | P3 |

---

## 6. Verificacion

- `php -l` en FeatureAccessService.php: OK
- YAML validation en 16 config YMLs: OK
- `git diff --stat`: 18 nuevos + 11 modificados
