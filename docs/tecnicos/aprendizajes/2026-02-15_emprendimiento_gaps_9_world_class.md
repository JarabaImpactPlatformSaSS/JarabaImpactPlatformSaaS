# Aprendizaje #79: Cierre 9 Gaps Emprendimiento — De 90% a Clase Mundial

**Fecha:** 2026-02-15
**Sesion:** Auditoria + Cierre Gaps Vertical Emprendimiento
**Modulos:** ecosistema_jaraba_core, jaraba_copilot_v2, jaraba_journey, jaraba_email, jaraba_funding, jaraba_onboarding, jaraba_self_discovery
**Archivos afectados:** 33 (12 nuevos config YAML + 6 nuevos MJML + 1 nuevo servicio PHP + 1 nuevo YAML config design token + 13 modificados)
**Reglas nuevas:** Ninguna (compliance con reglas existentes)

---

## Contexto

Auditoria senior multi-disciplina del vertical Emprendimiento revelo que la arquitectura estaba al ~90% (103 archivos en copilot_v2, 24 API endpoints, 23 servicios, 5 entidades, 3 paginas frontend) pero con 10 gaps que impedian entrega clase mundial. Se implementaron 9 de 10 gaps (G10 diferido por requerir framework de experimentacion). Los gaps cubrieron: design tokens, freemium gating, email lifecycle, cross-sell automation, re-engagement, AI upgrade nudges, funding intelligence enrichment, cross-vertical flows, y onboarding personalizado.

---

## Lecciones Aprendidas

### 1. Un solo design token incorrecto rompe la coherencia visual de todo un vertical

**Situacion:** El archivo `ecosistema_jaraba_core.design_token_config.vertical_emprendimiento.yml` usaba `Inter` como `family-body` cuando la directriz SCSS-FONT-001 exige `Outfit`. Cada tenant emprendimiento renderizaba con fuente incorrecta.

**Aprendizaje:** Los design tokens de vertical se aplican en cascada via CSS custom properties a todos los componentes del tenant. Un solo valor incorrecto en la configuracion YAML se propaga a toda la UI. La verificacion post-edicion debe incluir compilacion SCSS para confirmar que el CSS resultante contiene la fuente correcta.

**Regla aplicada:** SCSS-FONT-001 — `font-family` siempre usa `'Outfit'` como primaria.

### 2. FreemiumVerticalLimit como ConfigEntity escala a N features sin codigo nuevo

**Situacion:** Solo 2 features estaban gateadas por freemium (bmc_drafts, calculadora_uses). Se necesitaba gatear 4 features mas (hypotheses_active, experiments_monthly, copilot_sessions_daily, mentoring_sessions_monthly) en 3 planes (free, starter, profesional).

**Aprendizaje:** El patron FreemiumVerticalLimit ConfigEntity permite agregar nuevos limites simplemente creando archivos YAML sin tocar codigo PHP. Cada YAML define vertical, plan, feature_key, limit_value, upgrade_message y expected_conversion. El servicio existente `freemium_vertical_limit_service` ya sabe evaluar cualquier feature key contra el plan del tenant. Se crearon 12 configs (4 features x 3 planes) en minutos.

**Regla aplicada:** F2 / Doc 183 — Freemium model via ConfigEntity.

### 3. Los templates MJML de email deben existir por vertical para lifecycle communications

**Situacion:** Las verticales empleabilidad (5 templates) y marketplace (6 templates) tenian lifecycle emails completos, pero emprendimiento tenia 0. Esto significaba que eventos clave (bienvenida, diagnostico completado, canvas milestone, mentor matched) no generaban comunicacion alguna.

**Aprendizaje:** Cada vertical necesita su propio conjunto de templates MJML que cubran los momentos clave del journey. Los templates heredan de `base.mjml` y usan design tokens via CSS custom properties con hex fallbacks. El TemplateLoaderService ya resuelve paths por directorio, asi que solo se necesita crear el directorio `emprendimiento/` con los archivos MJML y registrarlos en el loader.

**Regla aplicada:** MJML-001 — Templates extienden base.mjml. I18N-001 — Strings via `{{ t() }}`.

### 4. Cross-sell rules en journey definition sin execution engine son datos muertos

**Situacion:** `EmprendimientoJourneyDefinition::EMPRENDEDOR_JOURNEY['cross_sell']` definia 4 reglas (diagnostic_completed → Curso modelo de negocio, before_mvp → Kit de validacion, etc.) pero no existia codigo que escuchara los eventos de transicion y presentara las ofertas.

**Aprendizaje:** Las definiciones declarativas de cross-sell necesitan un servicio de ejecucion que: (1) escuche eventos de transicion del JourneyEngine, (2) matchee contra las reglas del journey definition, (3) ejecute la accion (notificacion in-app + email). Se creo `EmprendimientoCrossSellService` siguiendo el patron existente de `CrossSellEngine` en AgroConecta, adaptado para emprendimiento con sus ofertas especificas y usando KernelEvents::TERMINATE para no bloquear la respuesta HTTP.

**Regla aplicada:** MILESTONE-001 — Eventos asincrono via KernelEvents::TERMINATE.

### 5. AI upgrade nudges requieren inyeccion contextual en el system prompt, no mensajes hardcodeados

**Situacion:** El `UpgradeTriggerService` detectaba cuando un usuario alcanzaba un limite, pero el copilot no tenia contexto para sugerir upgrades de forma natural durante la conversacion.

**Aprendizaje:** La forma correcta de integrar upgrade nudges con un copilot es inyectar contexto en el system prompt (no hardcodear mensajes). Se anadio `getUpgradeContext()` a UpgradeTriggerService que retorna un `prompt_snippet` con informacion del plan actual, features near limit (>80% uso), y beneficios especificos del upgrade segun el modo copilot activo. CopilotOrchestratorService lo inyecta en `buildSystemPrompt()` con try/catch no-blocking.

**Regla aplicada:** PROVIDER-001 — Inyeccion via system prompt.

### 6. Enrichment cross-modulo via optional DI mejora matching sin hard-coupling

**Situacion:** `jaraba_funding` matcheaba subvenciones sin considerar datos del BMC canvas del emprendedor, perdiendo precision en las recomendaciones de sector y segmento.

**Aprendizaje:** El patron `\Drupal::hasService()` + try/catch permite consumir datos de modulos opcionales (jaraba_business_tools) sin crear dependencia dura. `FundingMatchingEngine::getCanvasContext()` extrae sector, revenue_streams, customer_segments y business_stage del ultimo canvas del usuario, enriqueciendo el matching sin romper si el modulo no esta instalado.

**Regla aplicada:** DRUPAL11-001 — Optional DI pattern.

### 7. Cross-vertical flows bidireccionales multiplican el valor del ecosistema

**Situacion:** No existia flujo automatizado entre empleabilidad y emprendimiento. Emprendedores cuyo venture fallaba perdian engagement, y jobseekers con perfil emprendedor no descubrian el programa.

**Aprendizaje:** Se implemento en tres capas: (1) `RiasecService::evaluateEntrepreneurPotential()` detecta RIASEC Enterprising >= 7, (2) `EmprendimientoJourneyDefinition::evaluateEmpleabilidadFallback()` detecta all_hypotheses_killed + at_risk, (3) `AvatarNavigationService::getCrossVerticalItems()` inyecta items de navegacion condicionales. El patron es replicable para cualquier par de verticales.

**Regla aplicada:** Doc 103 — Journey definitions cross-vertical.

---

## Resumen de Cambios

| Archivo | Cambio |
|---------|--------|
| `config/sync/ecosistema_jaraba_core.design_token_config.vertical_emprendimiento.yml` | **FIX** — Inter → Outfit (SCSS-FONT-001) |
| 12 archivos `config/install/ecosistema_jaraba_core.freemium_vertical_limit.emprendimiento_*.yml` | **NUEVOS** — 4 features x 3 planes freemium |
| 6 archivos `jaraba_email/templates/mjml/emprendimiento/*.mjml` | **NUEVOS** — Lifecycle emails (welcome, diagnostic, canvas, experiment, mentor, weekly) |
| `jaraba_email/src/Service/TemplateLoaderService.php` | **MODIFICADO** — Registro emprendimiento templates |
| `jaraba_journey/src/Service/EmprendimientoCrossSellService.php` | **NUEVO** — Motor cross-sell emprendimiento |
| `jaraba_journey/jaraba_journey.services.yml` | **MODIFICADO** — Registro servicio cross-sell |
| `jaraba_journey/src/Service/JourneyEngineService.php` | **MODIFICADO** — Integra cross-sell en transiciones |
| `jaraba_copilot_v2/jaraba_copilot_v2.module` | **MODIFICADO** — hook_cron re-engagement |
| `jaraba_journey/src/Service/JourneyTriggerService.php` | **MODIFICADO** — evaluateEntrepreneurTriggers() |
| `ecosistema_jaraba_core/src/Service/UpgradeTriggerService.php` | **MODIFICADO** — getUpgradeContext() + helpers |
| `jaraba_copilot_v2/src/Service/CopilotOrchestratorService.php` | **MODIFICADO** — Upgrade context en system prompt |
| `jaraba_funding/src/Service/Intelligence/FundingMatchingEngine.php` | **MODIFICADO** — Canvas enrichment |
| `jaraba_self_discovery/src/Service/RiasecService.php` | **MODIFICADO** — evaluateEntrepreneurPotential() |
| `jaraba_journey/src/JourneyDefinition/EmprendimientoJourneyDefinition.php` | **MODIFICADO** — Empleabilidad fallback + onramp |
| `ecosistema_jaraba_core/src/Service/AvatarNavigationService.php` | **MODIFICADO** — Cross-vertical nav items |
| `jaraba_onboarding/templates/onboarding-wizard-step-welcome.html.twig` | **MODIFICADO** — Idea + sector emprendimiento |
| `jaraba_onboarding/templates/onboarding-wizard-step-content.html.twig` | **MODIFICADO** — BMC CTA card emprendimiento |

---

## Resultado

| Metrica | Antes | Despues |
|---------|-------|---------|
| Features gateadas freemium | 2 | 6 |
| Configs freemium emprendimiento | 6 | 18 |
| Email templates emprendimiento | 0 | 6 |
| Email templates totales MJML | 24 | 30 |
| Cross-sell rules con execution | 0 / 4 | 4 / 4 |
| Re-engagement cron activo | No | Si (weekly) |
| Copilot upgrade nudges | No | Si (contextual) |
| Funding canvas enrichment | No | Si (sector + revenue + segments) |
| Cross-vertical flow bidireccional | No | Si (RIASEC + fallback) |
| Onboarding emprendimiento personalizado | No | Si (idea + sector + BMC CTA) |
| Design token font correcto | Inter | Outfit |

---

## Verificacion

- [x] G1: Design token Inter → Outfit en YAML
- [x] G2: 12 nuevos configs freemium (4 features x 3 planes)
- [x] G3: 6 MJML templates emprendimiento + TemplateLoaderService registro
- [x] G4: EmprendimientoCrossSellService + wiring en JourneyEngine
- [x] G5: hook_cron re-engagement + evaluateEntrepreneurTriggers()
- [x] G6: getUpgradeContext() + buildSystemPrompt() inyeccion
- [x] G7: getCanvasContext() + runEnrichedMatchingForSubscription()
- [x] G8: evaluateEntrepreneurPotential() + empleabilidad fallback + cross-vertical nav
- [x] G9: Onboarding wizard steps emprendimiento (idea, sector, BMC CTA)
- [ ] G10: A/B Testing (diferido — requiere framework experimentacion)
