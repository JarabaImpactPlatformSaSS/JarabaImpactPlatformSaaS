# Perfil Candidato — Marca Profesional con IA Nativa

**Fecha:** 2026-02-25
**Estado:** EN PROGRESO
**Rama:** main
**Ultimo commit:** `a491c4e3` (andalucia_ei vertical + public profile gating)

---

## 1. Contexto y Objetivo

Dividir el formulario monolitico `CandidateProfileForm` (6 secciones glass-card) en **formularios especializados** que se cargan via slide-panel desde el checklist de completitud del perfil:

| Seccion checklist | Ruta nueva | Form class | Estado |
|---|---|---|---|
| Datos Personales | `/my-profile/personal` | `PersonalInfoForm` | FUNCIONAL |
| Marca Profesional | `/my-profile/brand` | `ProfessionalBrandForm` | FUNCIONAL (IA pendiente config) |
| Privacidad | `/my-profile/privacy` | `PrivacySettingsForm` | FUNCIONAL |

La ruta antigua `/my-profile/edit` se mantiene (backward compat) con el formulario monolitico `CandidateProfileForm`.

---

## 2. Archivos Creados

| Archivo | Descripcion |
|---|---|
| `jaraba_candidate/src/Form/PersonalInfoForm.php` | 5 campos: first_name, last_name, email, phone, photo. Extiende PremiumEntityFormBase. |
| `jaraba_candidate/src/Form/ProfessionalBrandForm.php` | 3 glass-cards (headline, summary, nivel). Botones IA inline. Extiende PremiumEntityFormBase. |
| `jaraba_candidate/src/Form/ProfileSectionForm.php` | Form generico para experience/education/language. Extiende PremiumEntityFormBase. |
| `jaraba_candidate/src/Form/PrivacySettingsForm.php` | 3 campos: is_public, show_photo, show_contact. Extiende PremiumEntityFormBase. |
| `jaraba_candidate/js/brand-professional.js` | JS para 3 flujos IA: generate_headline, optimize_summary, generate_summary. Comunica con copilot via fetch POST. |

## 3. Archivos Modificados

| Archivo | Cambio |
|---|---|
| `jaraba_candidate/src/Entity/CandidateProfile.php` | Anadidas form operations: `personal_info`, `professional_brand`, `privacy` |
| `jaraba_candidate/jaraba_candidate.routing.yml` | 3 rutas nuevas: `my_profile.personal`, `my_profile.brand`, `my_profile.privacy` (ruta privacy cambiada de `_form` a `_controller`). |
| `jaraba_candidate/jaraba_candidate.libraries.yml` | Libreria `brand_professional` (JS + deps) |
| `jaraba_candidate/src/Controller/ProfileController.php` | `renderFormOperation()` con `renderPlain()` + `$form['#action']` explicito. Metodos `personalInfoSection()`, `professionalBrandSection()`, `privacySection()`, helper `loadOrCreateProfile()`. |
| `jaraba_candidate/src/Controller/ProfileSectionFormController.php` | `renderPlain()` + `$form['#action']` explicito en `add()` y `edit()`. |
| `jaraba_candidate/src/Service/ProfileCompletionService.php` | `headline` anadido a campos de `professional_summary`. Label actualizado a "Marca Profesional". |
| `jaraba_candidate/jaraba_candidate.install` | `update_10006()` para instalar tabla `candidate_language`. |
| `jaraba_candidate/js/skills-manager.js` | DOM update inline en vez de `window.location.reload()` al anadir skill. |
| `jaraba_candidate/templates/candidate-profile-view.html.twig` | Boton "Completar perfil" cambiado de modal a slide-panel apuntando a `/my-profile/personal`. |
| `jaraba_candidate/templates/my-profile-skills.html.twig` | CSS `.btn-add-skill--added`. |
| `ecosistema_jaraba_core/src/Form/PremiumEntityFormBase.php` | `getFormTitle()` y `save()` con fallback null-safe para entidades sin label key. Afecta 20 entidades. |
| `ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme` | `section_routes` y `section_labels` actualizados con 9 secciones (incl. privacy). Libreria `brand_professional` adjuntada en `preprocess_page__user()` (L3151). Array `$always_completed` para secciones con defaults. |
| `ecosistema_jaraba_theme/templates/partials/_profile-completeness.html.twig` | Secciones completadas siguen siendo clicables (eliminado gate `not section.completed`). |

---

## 4. Bugs Resueltos (Commits)

### 4.1 Tabla candidate_language no existia
- **Sintoma:** Idiomas no se grababan.
- **Causa:** Faltaba `update_10006()` para `installEntityType('candidate_language')`.
- **Fix:** `jaraba_candidate.install` con update hook.

### 4.2 Skills UX: reload + link deshabilitado
- **Sintoma:** Anadir skill recargaba la pagina y deshabilitaba el enlace.
- **Causa:** `skills-manager.js` hacia `window.location.reload()`. Template condicionaba enlace a `not section.completed`.
- **Fix:** DOM update inline + eliminacion del gate.

### 4.3 ProfileSectionForm::save() crash — toUrl('collection')
- **Sintoma:** Experiencia, formacion e idiomas no se grababan (en realidad si, pero la respuesta crasheaba).
- **Causa:** `$entity->toUrl('collection')` lanzaba `UndefinedLinkTemplateException` porque las rutas admin collection no existen para section entities.
- **Fix:** `$form_state->setRedirect('jaraba_candidate.my_profile')`.

### 4.4 setCached(TRUE) crash en GET
- **Sintoma:** Formularios no cargaban. `LogicException: Form state caching on GET requests is not allowed.`
- **Causa:** `$form_state->setCached(TRUE)` lanza excepcion en requests GET/HEAD por diseno de Drupal.
- **Fix:** Eliminado `setCached(TRUE)` de los 3 formularios. No es necesario: Drupal reconstruye entity forms en POST automaticamente.

### 4.5 PremiumEntityFormBase — NULL label crash (SISTEMICO)
- **Sintoma:** `TypeError: Html::escape(): Argument #1 must be string, null given` al guardar.
- **Causa:** 20 entidades sin `"label"` en `entity_keys`. `$entity->label()` retorna NULL. `getFormTitle()` y `save()` no tenian fallback.
- **Fix:** Fallback null-coalescing: `$entity->label() ?? singularLabel ?? id()` en ambos metodos.
- **Entidades afectadas:** candidate_profile, candidate_skill, addon_subscription, candidate_experience, candidate_education, candidate_language, y 14 mas (ver audit en sesion).

### 4.6 Copilot URL sin prefijo de idioma
- **Sintoma:** Boton "Generar con IA" daba error de conexion.
- **Causa:** URL hardcodeada `/api/v1/copilot/employability/chat` sin prefijo `/es/`. El fetch POST llegaba a 404 o redirect que perdia el body.
- **Fix:** `Url::fromRoute('jaraba_candidate.copilot.chat')` que incluye automaticamente prefijo de idioma y CSRF token.

---

## 5. Estado Actual y Pendientes

### FUNCIONAL
- [x] Ruta `/my-profile/personal` carga PersonalInfoForm en slide-panel
- [x] Ruta `/my-profile/brand` carga ProfessionalBrandForm en slide-panel
- [x] Ruta `/my-profile/privacy` carga PrivacySettingsForm en slide-panel
- [x] Ruta `/my-profile/edit` mantiene formulario monolitico (backward compat)
- [x] ProfileSectionForm graba experience, education, language sin crash
- [x] Checklist con labels y rutas nuevas (9 secciones incl. Privacidad)
- [x] Skills: DOM update inline sin reload
- [x] PremiumEntityFormBase null-safe para 20 entidades
- [x] 6 enlaces migrados de modal a slide-panel (commit `1d752a16`)

### PENDIENTE — IA Copilot
- [ ] **API key de OpenAI no configurada** en el entorno local. Config: `ai_provider_openai.settings` → `api_key` tiene placeholder de 10 chars (necesita key real `sk-...`). Ruta admin: `/admin/config/ai/settings` o `/admin/config/ai/providers/openai`.
- [ ] Una vez configurada la key, verificar los 3 flujos IA:
  - `generate_headline` → 3 opciones clicables
  - `optimize_summary` → panel comparativo original vs optimizado
  - `generate_summary` → textarea relleno + enlace "Recuperar anterior"
- [x] CSS para componentes IA — `css/brand-professional.css` (commit `1d752a16`). Usa `var(--ej-*)` tokens per arquitectura theming.

### PENDIENTE — Icono Habilidades
- [ ] El usuario indico "No me gusta el icono que has usado para Habilidades" — pendiente preguntar cual prefiere.

### VERIFICACION — Formularios via slide-panel POST
Analisis tecnico (sin cache, post-commit `03b80268`):
- **Formularios texto/select/checkbox**: Funcionan sin cache. `FormData` envia todos los valores, entity form se reconstruye en POST desde la entidad, `save()` ejecuta correctamente.
- **ProfessionalBrandForm**: Solo campos texto y select — funciona.
- **PrivacySettingsForm**: Solo checkboxes — funciona.
- **ProfileSectionForm**: Solo campos texto/date/boolean — funciona.
- **PersonalInfoForm** (photo field): El widget `managed_file` usa AJAX para preview. En contexto slide-panel, el AJAX de preview puede no funcionar, pero el upload via `FormData` en submit si deberia funcionar. **Verificar en navegador**.
- Fallback si falla: `if ($this->getRequest()->isMethod('POST')) { $form_state->setCached(TRUE); }` en `buildForm()`.

---

## 6. Patron Slide-Panel — Referencia Tecnica

### Flujo GET (cargar form)
1. Click en checklist item con `data-slide-panel-url`
2. `slide-panel.js` hace fetch GET a la URL
3. Controller detecta `isSlidePanelRequest()` (XHR sin `_wrapper_format`)
4. Renderiza form con `renderPlain()` + `$form['#action']` explicito
5. Devuelve HTML bare (sin page chrome)
6. slide-panel inyecta HTML + `Drupal.attachBehaviors()`

### Flujo POST (guardar form)
1. slide-panel intercepta submit, envia `FormData` via fetch POST
2. Anade el submit button al FormData (critico para triggering element)
3. Si `form.action` es placeholder BigPipe, usa `originalUrl` como fallback
4. Controller llama `getForm($entity, $operation)` que trigger FormBuilder
5. FormBuilder reconstruye el form (sin cache) y procesa los valores
6. `save()` ejecuta, `setRedirect()` fija la redireccion
7. Response 200 HTML → slide-panel cierra + `refreshCurrentPage()`

### Clave: renderPlain() vs render()
- `render()` deja placeholders BigPipe sin resolver en contexto AJAX
- `renderPlain()` resuelve todo y es seguro para responses HTML parciales
- `$form['#action']` debe setearse explicitamente al `$request->getRequestUri()`

---

## 7. Arquitectura de Formularios Premium

```
EntityForm (Drupal core)
  └── ContentEntityForm (Drupal core)
        └── PremiumEntityFormBase (ecosistema_jaraba_core)
              ├── CandidateProfileForm (monolitico, 6 secciones)
              ├── PersonalInfoForm (5 campos contacto)
              ├── ProfessionalBrandForm (3 cards + IA)
              ├── PrivacySettingsForm (3 campos visibilidad)
              └── ProfileSectionForm (experience/education/language)
```

PremiumEntityFormBase provee:
- Glass-cards con nav pills
- Seccion "Other" para campos no asignados
- `getFormTitle()`, `getFormSubtitle()`, `getFormIcon()` (overrideable)
- `getSectionDefinitions()` (abstract) — define secciones, iconos, campos
- `save()` con mensaje generico null-safe

---

### 4.7 PrivacySettingsForm no existia (CRITICO)
- **Sintoma:** Ruta `/my-profile/privacy` causaba error fatal (clase no encontrada).
- **Causa:** `jaraba_candidate.routing.yml` referenciaba `PrivacySettingsForm` con `_form:` pero la clase no existia. Ademas, usar `_form:` no es compatible con el patron entity form + slide-panel.
- **Fix:** Creada `PrivacySettingsForm.php` extendiendo `PremiumEntityFormBase` con 3 campos (is_public, show_photo, show_contact). Ruta cambiada de `_form:` a `_controller:` con nuevo metodo `ProfileController::privacySection()`. Form operation `privacy` registrada en `CandidateProfile.php`. Seccion anadida al checklist del theme con `$always_completed` array para secciones con defaults.

---

## 8. Commits de esta sesion (en orden)

1. `f1810b2d` — feat: update_10006 + skills UX + brand-professional.js + completeness fix
2. `464b3963` — fix: renderPlain + form action + ProfileSectionForm redirect + completar perfil slide-panel
3. `03b80268` — fix: remove setCached(TRUE) that crashes on GET
4. `c72a1a28` — fix: null-safe label in PremiumEntityFormBase (20 entidades)
5. `9ea8b424` — fix: Url::fromRoute for copilot URL with language prefix
6. `1d752a16` — feat: migrate modal links to slide-panel + AI component CSS
7. `a491c4e3` — feat: andalucia_ei vertical + domain-based tenant resolution + public profile gating
8. *(pendiente commit)* — fix: crear PrivacySettingsForm + privacy en checklist
