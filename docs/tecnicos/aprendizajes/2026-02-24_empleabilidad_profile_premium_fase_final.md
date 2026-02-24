# Aprendizaje #118 — Empleabilidad /my-profile Premium: Fase Final

**Fecha:** 2026-02-24
**Modulo:** jaraba_candidate
**Tema:** Completar la implementacion premium de /my-profile — CandidateEducation entity, XSS fix, controller cleanup

---

## Contexto

Una sesion anterior implemento el 90% del diseno premium de `/my-profile`:
- Template premium `candidate-profile-view.html.twig` con 7 secciones glassmorphism, `jaraba_icon()` duotone, stagger animations, timeline de experiencia, skill pills, completion ring SVG.
- Empty state premium `my-profile-empty.html.twig`.
- SCSS `_profile-view.scss` (920 lineas con design tokens `--ej-*`, BEM `cp-*`, responsive).
- CSS compilado `css/profile-view.css` (13,987 bytes minificado).
- Controller `ProfileController.php` con carga resiliente de skills, experiencia, educacion, foto, links, completion %.
- Todos los 15 pares `jaraba_icon()` verificados.

**Lo que faltaba:**
1. La entidad `CandidateEducation` NO existia — la seccion "Educacion" del perfil nunca renderizaba datos reales.
2. Vulnerabilidad XSS: `{{ profile.summary|raw }}` violaba TWIG-XSS-001.
3. HTML hardcodeado en fallback de `editProfile()` (lineas 409-413).
4. Faltaban permiso admin, rutas de entidad y Field UI settings tab para educacion.

---

## Problemas y Soluciones

### 1. CandidateEducation Entity (NUEVA)

**Problema:** El `ProfileController.php` ya cargaba `candidate_education` entities resilientemente (try/catch), pero la entidad no existia. La seccion "Educacion" del template siempre mostraba 0 registros — sin error pero sin datos.

**Solucion:** Crear `CandidateEducation` ContentEntity siguiendo el patron exacto de `CandidateExperience` y `CandidateSkill`:
- Campos: `user_id` (entity_reference → user), `institution`, `degree`, `field_of_study`, `start_date`, `end_date`, `created`, `changed`.
- Interface con getters tipados (getInstitution(), getDegree(), getFieldOfStudy(), getStartDate(), getEndDate()).
- `AdminHtmlRouteProvider` para auto-generar rutas CRUD admin.
- `field_ui_base_route` apuntando a `entity.candidate_education.settings`.
- Links: collection, canonical, add-form, edit-form, delete-form.
- Admin permission: `administer candidate educations`.
- Update hook `10002` para instalar el schema de la nueva entidad.
- SettingsForm + ruta + default settings tab en links.task.yml (FIELD-UI-SETTINGS-TAB-001).
- Collection tab "Educacion" en `/admin/content` (weight: 26).

**Patron aplicado:** El mismo patron de entidad que `CandidateExperience` (sin rutas) pero mejorado con `AdminHtmlRouteProvider` y `field_ui_base_route` como `CandidateSkill`, siguiendo las directrices actuales FIELD-UI-SETTINGS-TAB-001.

### 2. XSS Fix en Template (TWIG-XSS-001)

**Problema:** Linea 102 de `candidate-profile-view.html.twig` usaba `{{ profile.summary|raw }}`, lo cual permite inyeccion de scripts si el resumen contiene HTML malicioso.

**Solucion:** Reemplazar por `{{ profile.summary|safe_html }}`. El filtro custom `safe_html` (definido en `JarabaTwigExtension.php`) permite tags HTML seguros (div, span, p, h1-h6, ul, ol, li, a, img, table, strong, em) pero elimina `<script>`, `<iframe>`, event handlers (onclick, onerror) y otros vectores XSS.

**Verificacion:** Test con `<b>Test</b><script>alert(1)</script>` → output: `<b>Test</b>alert(1)`. Script eliminado, bold preservado.

### 3. Controller Fallback Cleanup

**Problema:** En `ProfileController::editProfile()`, cuando la creacion del perfil falla, el fallback usaba `#markup` con HTML hardcodeado (inline styles, links hardcoded, texto sin i18n). Viola principios de separacion template/logica y no reutiliza el empty state premium.

**Solucion:** Reemplazar por render array con `#theme => 'my_profile_empty'` y `library => ['jaraba_candidate/profile_view']`. Reutiliza el template premium existente con glassmorphism, benefit cards y CTA.

---

## Ficheros Creados

| Fichero | Tipo |
|---------|------|
| `src/Entity/CandidateEducation.php` | ContentEntity con AdminHtmlRouteProvider |
| `src/Entity/CandidateEducationInterface.php` | Interface con getters tipados |
| `src/Form/CandidateEducationSettingsForm.php` | Settings form para Field UI |

## Ficheros Modificados

| Fichero | Cambio |
|---------|--------|
| `templates/candidate-profile-view.html.twig` | `\|raw` → `\|safe_html` (linea 102) |
| `src/Controller/ProfileController.php` | HTML hardcodeado → `#theme => 'my_profile_empty'` |
| `jaraba_candidate.permissions.yml` | +`administer candidate educations` (restrict access) |
| `jaraba_candidate.install` | +`update_10002()` — instala schema CandidateEducation |
| `jaraba_candidate.links.task.yml` | +collection tab "Educacion" + settings tab Field UI |
| `jaraba_candidate.routing.yml` | +`entity.candidate_education.settings` ruta |

---

## Verificacion

- Entity registrada: `candidate_education` con base_table, admin_permission, 6 rutas auto-generadas.
- Tabla creada via `update_10002` (0 rows, queryable).
- 6 rutas operativas: collection, canonical, add, edit, delete, settings.
- Permiso registrado con `restrict access: true`.
- Theme hooks `candidate_profile_view` y `my_profile_empty` activos.
- Filtro `safe_html` verificado: elimina `<script>`, preserva `<b>`.

---

## Reglas Aplicadas (No Nuevas)

- **TWIG-XSS-001** (P0): `|safe_html` en contenido de usuario, nunca `|raw`.
- **FIELD-UI-SETTINGS-TAB-001** (P0): Default settings tab obligatorio para entidades con `field_ui_base_route`.
- **Regla de Oro #7**: Documentar siempre — toda sesion genera actualizacion documental.
- **Regla de Oro #29**: Field UI settings tab obligatorio.

---

## Resultado

El perfil `/my-profile` esta ahora "clase mundial":
- 7 secciones premium funcionando con datos reales (hero, about, experiencia, educacion, skills, links, CTA).
- La seccion "Educacion" puede ahora renderizar registros de la entidad `candidate_education`.
- XSS eliminado del template de perfil.
- El fallback del controller reutiliza el empty state premium.
- Administracion completa via `/admin/content/candidate-educations` con Field UI habilitado.
