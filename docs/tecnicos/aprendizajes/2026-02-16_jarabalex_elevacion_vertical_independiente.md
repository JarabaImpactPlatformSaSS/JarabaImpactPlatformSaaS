# Aprendizajes: Elevacion JarabaLex a Vertical Independiente

| Campo | Valor      |
|-------|------------|
| Fecha | 2026-02-16 |

---

## Patron Principal

La sesion elevo `jaraba_legal_intelligence` de sub-feature de ServiciosConecta a vertical independiente JarabaLex. El modulo (137 archivos, 22,703 lineas) no tenia dependencia real en `jaraba_servicios_conecta` — solo en `ecosistema_jaraba_core`. La elevacion se logro con cambios minimos (metadata + config seed data + theme + billing + docs), sin tocar funcionalidad ni crear dependencias nuevas, validando que la arquitectura de config entities del ecosistema soporta esta operacion de forma limpia.

---

## Aprendizajes Clave

### 1. La arquitectura de config entities permite elevar modulos a verticales sin cambios funcionales

**Situacion:** `jaraba_legal_intelligence` estaba clasificado como feature de ServiciosConecta (package: 'Jaraba ServiciosConecta') pero no depende de ese modulo. Compite con Aranzadi/La Ley en un mercado de ~300M EUR, con TAM 10-50x mayor como vertical independiente.

**Aprendizaje:** La arquitectura de config entities (Vertical, Feature, SaasPlan, FreemiumVerticalLimit) permite elevar un modulo a vertical completo con solo: (1) cambiar el package en .info.yml, (2) crear seed data YAML, (3) agregar template de tema, (4) registrar en billing. Zero cambios funcionales, zero dependencias nuevas, zero breaking changes.

**Regla:** VERTICAL-ELEV-001: Para elevar un modulo a vertical independiente, verificar PRIMERO que no tiene dependencias en el vertical padre — si solo depende de `ecosistema_jaraba_core`, la elevacion es una operacion de metadata + config seed data.

### 2. El patron FreemiumVerticalLimit escala linealmente con cada vertical nuevo

**Situacion:** Se necesitaban limites freemium para 3 planes (free, starter, profesional) y 3 feature_keys (searches_per_month, max_alerts, max_bookmarks) = 9 archivos YAML.

**Aprendizaje:** El patron `{vertical}_{plan}_{feature_key}.yml` es predecible y mecanico. Solo los planes `free` necesitan `upgrade_message` y `expected_conversion > 0`. Los planes de pago usan `upgrade_message: ''` y `expected_conversion: 0`. Los ilimitados usan `limit_value: -1`. El rango de weights se asigna por vertical (300-308 para JarabaLex).

**Regla:** VERTICAL-ELEV-002: Al crear FreemiumVerticalLimit para un nuevo vertical, asignar un rango de weights dedicado (ej. 300-308) y usar la convencion `upgrade_message` solo en plan free — planes de pago heredan acceso sin mensajes.

### 3. CSS custom properties como :root permiten theme isolation sin conflictos

**Situacion:** El modulo legal ya tenia variables SCSS `$legal-*` pero no las exponia como CSS custom properties, impidiendo su uso en templates Twig y en el modifier del vertical-landing.

**Aprendizaje:** Anadir un bloque `:root { --ej-legal-*: #{$legal-*}; }` al final de `_variables-legal.scss` (siguiendo el patron exacto de `_fiscal-variables.scss`) expone los tokens como CSS custom properties consumibles desde cualquier contexto (Twig, otros SCSS, JS). Los derivados usan `color-mix()` (NUNCA `rgba()`) para mantener consistencia con la directriz del proyecto.

**Regla:** VERTICAL-ELEV-003: Todo modulo elevado a vertical DEBE exponer sus colores como CSS custom properties `--ej-{vertical}-*` en un bloque `:root {}` — seguir el patron de `_fiscal-variables.scss` lineas 105-136.

### 4. El page template zero-region es un patron reutilizable con copiar-y-adaptar

**Situacion:** Se necesitaba un template `page--legal.html.twig` identico en estructura al existente `page--fiscal.html.twig`.

**Aprendizaje:** Los page templates zero-region siguen un patron estricto: (1) `attach_library` del modulo, (2) `attributes.addClass()` con body classes semanticas, (3) partials reutilizados (_header, _footer, _copilot-fab), (4) `clean_messages` y `clean_content` como variables inyectadas via preprocess, (5) FAB copilot con `agent_context` y `fab_color` especificos del vertical. La diferenciacion esta en los nombres de clase CSS, la library adjunta, y los parametros del copilot.

**Regla:** VERTICAL-ELEV-004: Nuevos page templates de vertical DEBEN seguir el patron zero-region de `page--fiscal.html.twig` — copiar, reemplazar 'fiscal' por el vertical, y ajustar library + copilot context. NO inventar patrones nuevos.

### 5. FEATURE_ADDON_MAP centraliza el registro de features para billing

**Situacion:** Se necesitaba que el sistema de billing reconociera las 3 features de JarabaLex (legal_search, legal_alerts, legal_citations).

**Aprendizaje:** `FeatureAccessService::FEATURE_ADDON_MAP` es el unico punto donde se registra la relacion feature → modulo. Anadir 3 lineas al const array fue suficiente. El `getAvailableAddons()` separado lista los add-ons con labels para la UI — si no se necesita add-on independiente (es parte del plan base), solo se necesita el MAP.

**Regla:** VERTICAL-ELEV-005: Al elevar un vertical, registrar sus features en `FEATURE_ADDON_MAP` de `FeatureAccessService.php` — mapear cada feature_key al codigo del modulo add-on.

---

## Metricas de la Sesion

| Metrica | Valor |
|---------|-------|
| Archivos nuevos | 18 (16 config YAML + 1 Twig + 1 doc) |
| Archivos modificados | 11 (1 PHP + 2 SCSS + 1 YAML + 1 info.yml + 6 docs) |
| Config entities nuevos | 16 (1 vertical + 3 features + 3 plans + 9 limits) |
| Tests afectados | 0 (exit code 0 en 1,858 Unit tests) |
| PHP lint | OK |
| YAML validation | 16/16 OK |
| Docs maestros actualizados | 3 (Arquitectura v34, Directrices v34, Indice v50) |
| Docs tecnicos actualizados | 3 (178, 178A, 178B metadata) |
| Reglas nuevas | VERTICAL-ELEV-001 a 005 |

---

## Patrones Reutilizables

| Patron | Origen | Reutilizado en |
|--------|--------|----------------|
| Config entity seed vertical | ecosistema_jaraba_core.vertical.agroconecta.yml | ecosistema_jaraba_core.vertical.jarabalex.yml |
| FreemiumVerticalLimit 3×3 | agroconecta_free_products pattern | 9 jarabalex_*.yml |
| SaaS plan config | saas_plan.basico.yml | 3 saas_plan.jarabalex_*.yml |
| Page template zero-region | page--fiscal.html.twig | page--legal.html.twig |
| CSS custom properties :root | _fiscal-variables.scss L105-136 | _variables-legal.scss |
| Vertical landing modifier | .vertical-landing--instituciones | .vertical-landing--jarabalex |
| FEATURE_ADDON_MAP entry | credentials_advanced → credentials | legal_search → jaraba_legal_intelligence |
