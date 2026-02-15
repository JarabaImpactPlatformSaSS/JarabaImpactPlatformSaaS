# Plan de Elevacion a Clase Mundial: Vertical EMPRENDIMIENTO

**Fecha:** 2026-02-15
**Version:** 1.0.0
**Estado:** En implementacion
**Vertical:** Emprendimiento

## Resumen

Plan de 6 fases para elevar el vertical Emprendimiento a clase mundial:

1. **FASE 1**: Parent Template + Copilot FAB + Preprocess Hook (CRITICA)
2. **FASE 2**: Body Classes unificadas (ALTA)
3. **FASE 3**: Correccion SCSS + package.json (ALTA)
4. **FASE 4**: EmprendimientoFeatureGateService (CRITICA)
5. **FASE 5**: i18n en JourneyDefinition (ALTA)
6. **FASE 6**: A/B Testing Framework - G10 (MEDIA)

## Archivos afectados

### Fase 1 (5 archivos)
- NUEVO: `web/themes/custom/ecosistema_jaraba_theme/templates/page--emprendimiento.html.twig`
- MODIFICADO: `web/themes/custom/ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme`
- ELIMINADO: `page--emprendimiento--bmc.html.twig`
- ELIMINADO: `page--emprendimiento--hipotesis.html.twig`
- ELIMINADO: `page--emprendimiento--experimentos-gestion.html.twig`

### Fase 2 (1 archivo)
- MODIFICADO: `ecosistema_jaraba_theme.theme` (hook_preprocess_html body classes)

### Fase 3 (4 archivos)
- MODIFICADO: `jaraba_copilot_v2/scss/_copilot-chat-widget.scss`
- MODIFICADO: `jaraba_copilot_v2/scss/_hypothesis-manager.scss`
- NUEVO: `jaraba_copilot_v2/package.json`

### Fase 4 (3 archivos)
- NUEVO: `ecosistema_jaraba_core/src/Service/EmprendimientoFeatureGateService.php`
- MODIFICADO: `ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml`
- MODIFICADO: `ecosistema_jaraba_core/ecosistema_jaraba_core.install`

### Fase 5 (1+ archivos)
- MODIFICADO: `jaraba_journey/src/JourneyDefinition/EmprendimientoJourneyDefinition.php`
- MODIFICADO: Llamadores (JourneyEngineService, EmprendimientoCrossSellService)

### Fase 6 (2 archivos)
- NUEVO: `ecosistema_jaraba_core/src/Service/EmprendimientoExperimentService.php`
- MODIFICADO: `ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml`

## Directrices aplicadas
- Nuclear #14: Frontend Limpio (Zero Region Policy)
- Nuclear #11: Full-width layout
- P4-AI-001: Copilot FAB en toda ruta autenticada
- P4-COLOR-001: color-mix() en lugar de rgba()
- SCSS-PKG-001: package.json por modulo
- I18N-001: Todas las cadenas traducibles
- F2/Doc 183: FreemiumVerticalLimit enforcement
- DRUPAL11-001: Dependency Injection correcta
- MILESTONE-001: Tracking append-only
