# Aprendizaje #123 — Elevacion Empleabilidad + Andalucia EI Plan Maestro + Meta-Site Tenant-Aware Rendering

**Fecha:** 2026-02-25
**Modulos afectados:** `jaraba_candidate`, `jaraba_andalucia_ei`, `jaraba_crm`, `jaraba_site_builder`, `jaraba_geo`, `jaraba_page_builder`, `ecosistema_jaraba_theme`
**Contexto:** Sprint de elevacion multi-vertical: perfil de candidato con formularios premium y CRUD slide-panel, Andalucia EI con portal completo de participante en 8 fases, formularios CRM premium, y meta-sitios con rendering tenant-aware
**Impacto:** 90+ ficheros modificados/creados, 4 patrones nuevos, 4 reglas de oro (#38-#41)
**Commits:** `5375471c`, `139feaa6`, `8408396b`, `478817e7`, `7e59257a`, `9fb14601`, `bac160b3`
**Reglas de oro:** #38, #39, #40, #41

---

## 1. Empleabilidad — Profile Elevation

### Problema
Los formularios de entidad usaban `ContentEntityForm` generico de Drupal — sin secciones, sin labels en espanol, sin UX premium. El campo `photo` era `entity_reference` con widget `file_generic` (roto). Los campos de fecha eran `timestamp` (int) en lugar de `datetime` (con date picker).

### Solucion
- **CandidateProfileForm** premium con 6 secciones (personal, profesional, ubicacion, preferencias, online, privacidad), constantes `SECTIONS`, `HIDDEN_FIELDS`, `FIELD_LABELS`.
- **ProfileSectionForm** generico para CRUD de education, experience, language via slide-panel.
- **ProfileSectionFormController**: rutas genericas `/my-profile/section/{entity_type_id}/add|edit|delete`.
- **Campo photo**: migrado con `update_10004` (backup → uninstall → install image → restore refs).
- **Campos date**: reinstalados con `update_10005` (tablas vacias, safe to reinstall).
- **CV Builder**: soporte slide-panel AJAX + fallback PNG sobre SVG para previews.
- **ProfileCompletionService**: refactorizado para usar `hasRelatedEntities()` con entity queries en lugar de fields del profile.

### Patron: isSlidePanelRequest()
```php
protected function isSlidePanelRequest(Request $request): bool {
    return $request->isXmlHttpRequest() && !$request->query->has('_wrapper_format');
}
```
Drupal modal envia `_wrapper_format=drupal_modal`. Slide-panel custom no. Solo retornar bare HTML para slide-panel.

---

## 2. Andalucia EI — Plan Maestro 8 Fases

### Alcance
71 ficheros, +6644/-531 lineas. 7 servicios nuevos, 1 entidad nueva, 11 bloques Page Builder, 2 landing pages, 1 portal de participante.

### Fases
1. **P0/P1 fixes**: AiMentorshipTracker metodos publicos, StoExportService syncParticipante, tenant isolation en access handlers, DI corrections.
2. **11 bloques PB verticales**: hero, features, content, gallery, faq, testimonials, social_proof, stats, pricing, cta, map. Config YAMLs + templates Twig + update hook import.
3. **Landing conversion** `/andalucia-ei/programa`: Zero Region Policy, Open Graph + Schema.org JSON-LD, body classes.
4. **Portal participante** `/andalucia-ei/mi-participacion`: health score gauge, timeline expandida con sub-steps, training progress, quick actions, achievements/badges, PDF progress report.
5. **ExpedienteDocumento** entity: 19 categorias documentales, vault cifrado, revision IA, firmas digitales, STO compliance, CRUD + Field UI.
6. **Mensajeria integration**: CONTEXT_ANDALUCIA_EI en SecureConversation, MensajeriaIntegrationService, widget.
7. **AI automation**: CopilotContextProvider, AdaptiveDifficultyEngine, 4 nudge rules proactivos.
8. **SEO/marketing**: sitemap entries, lead magnet guide `/andalucia-ei/guia-participante`.

---

## 3. CRM Premium Forms

5 formularios (Company, Contact, Opportunity, Activity, PipelineStage) migrados de `ContentEntityForm` a `PremiumEntityFormBase` con glass-card UI, section navigation pills, duotone icons. Campos computados (`engagement_score`, `bant_score`) marcados `#disabled`.

---

## 4. Meta-Site Tenant-Aware Rendering

### Problema
Los meta-sitios (ej. pepejaraba.jarabaimpact.com) mostraban el branding global de Jaraba en lugar del branding del tenant. Schema.org, title, header, footer, navegacion — todo apuntaba a Jaraba Impact Platform.

### Solucion
- **MetaSiteResolverService** (`jaraba_site_builder.meta_site_resolver`): resuelve PageContent → meta-site context (SiteConfig + SitePageTree + tenant info).
- **Schema.org** (`jaraba_geo`): Organization name/description/logo desde SiteConfig cuando la pagina es de un meta-sitio.
- **Title tag** (`preprocess_html`): override con `meta_title_suffix` de SiteConfig. Body class `meta-site meta-site-tenant-{id}`.
- **Header/Footer/Nav** (`preprocess_page`): site_name, navigation_items, header layout/CTA, footer copyright/layout, logo — todo desde SiteConfig.
- **Fix critico**: SitePageTree status filter `'published'` (string) → `1` (int). Los campos boolean de entidad almacenan `0`/`1`, no strings.

---

## 5. Patrones Derivados

### PREMIUM-FORM-001 — Formularios premium con secciones
Constantes `SECTIONS` (agrupaciones), `HIDDEN_FIELDS` (internos), `FIELD_LABELS` (traducciones). Form class en entity annotation.

### SLIDE-PANEL-001 — Slide-panel vs Drupal modal
`isSlidePanelRequest()` = XHR sin `_wrapper_format`. Solo bare HTML para slide-panel.

### META-SITE-RENDER-001 — Rendering tenant-aware
`MetaSiteResolverService::resolveFromPageContent()` como punto unico. Override title, Schema.org, header/footer/nav en hooks de preprocess.

### Migracion de field types
Backup → uninstall old → install new desde `baseFieldDefinitions()` → restore. `uninstallEntityType()` + `installEntityType()` para entidades vacias.

---

## 6. Referencias

- **Directrices:** v71.0.0
- **Arquitectura:** v72.0.0 (3 ASCII boxes: Empleabilidad Elevation, Andalucia EI Plan Maestro, Meta-Site Rendering)
- **Flujo:** v26.0.0 (4 patrones + reglas de oro #38-#41)
- **Indice:** v95.0.0 (bloque HAL-01 a HAL-04)
