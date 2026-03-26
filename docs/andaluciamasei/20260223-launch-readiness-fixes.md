# Aprendizaje #110: Andalucia +ei 2a Edicion — Correccion de 8 Incidencias de Lanzamiento

**Fecha:** 2026-02-23
**Contexto:** Testing de recorrido candidato en browser revelo 8 incidencias bloqueantes para el lanzamiento de la 2a edicion del programa Andalucia +ei
**Impacto:** Vertical Andalucia +ei + Plataforma global (paginas legales, footer, features badge)

---

## 1. Problema

Al simular el recorrido completo de un candidato que solicita plaza en el programa Andalucia +ei (navegacion → formulario → envio → paginas legales), se detectaron 8 incidencias:

| # | Severidad | Incidencia |
|---|-----------|------------|
| C1 | Critica | El formulario de solicitud **tragaba silenciosamente** todos los errores de validacion y mensajes de exito |
| C2 | Critica | Emojis Unicode en produccion (violacion regla P4-EMOJI-002) en vez de `jaraba_icon()` |
| C3 | Critica | Enlace de privacidad en el formulario apuntaba a `/privacidad` (404) |
| A1 | Alta | Paginas legales (`/privacy`, `/terms`, `/cookies`) retornaban 404 |
| A2 | Alta | Paginas informativas (`/about`, `/contact`) retornaban 404 |
| A3 | Alta | Links del footer usaban URLs en ingles no funcionales |
| M1 | Media | Badge de features decia "4 verticales" en vez de "6 verticales" |
| M2 | Media | Imagenes placeholder grises en secciones features/social-proof |

---

## 2. Diagnostico

### C1 — Mensajes de formulario invisibles

**Causa raiz:** El theme hook `solicitud_ei_page` solo declaraba la variable `form` en `hook_theme()`. No existia variable `messages` ni preprocess hook que inyectara `['#type' => 'status_messages']`. El template no incluia `{{ messages }}`.

**Flujo roto:**
1. `SolicitudEiPublicForm::validateForm()` llama a `$form_state->setErrorByName()` → errores se almacenan en la sesion
2. `SolicitudEiPublicForm::submitForm()` llama a `$this->messenger()->addStatus()` → mensajes en sesion
3. `SolicitudEiController::solicitar()` renderiza `#theme => 'solicitud_ei_page'` con solo `#form`
4. El template `solicitud-ei-page.html.twig` renderiza `{{ form }}` pero **nunca** renderiza los mensajes
5. Resultado: los mensajes de la sesion se pierden silenciosamente en la siguiente peticion

**SCSS ya preparado:** `_solicitud-form.scss` ya tenia estilos para `.messages--error` y `.messages--status` (lineas 364-398), pero nunca se usaban.

### C2 — Emojis en plantilla de produccion

6 posiciones con emojis Unicode directos en el template Twig, violando la regla de arquitectura que exige `jaraba_icon()` para todos los iconos SVG.

### A1-A3 — Paginas y links no existentes

Las URLs del footer y del formulario apuntaban a rutas que nunca se crearon. Los defaults del footer estaban en ingles (`/privacy`, `/about`, `/contact`) pero las rutas no existian en ningun idioma.

---

## 3. Solucion Implementada

### Fase 1: Critica — Mensajes y Compliance de Iconos

**3.1 Inyeccion de mensajes (C1)** — 2 ficheros modificados:

```php
// jaraba_andalucia_ei.module — hook_theme() actualizado
'solicitud_ei_page' => [
  'variables' => [
    'form' => NULL,
    'messages' => NULL,  // NUEVO
  ],
],

// Nuevo preprocess hook
function jaraba_andalucia_ei_preprocess_solicitud_ei_page(array &$variables): void {
  $variables['messages'] = ['#type' => 'status_messages'];
}
```

```twig
{# solicitud-ei-page.html.twig — antes de {{ form }} #}
{{ messages }}
{{ form }}
```

**3.2 Emojis → jaraba_icon() (C2)** — 6 reemplazos en el template:
- `🚀` → `jaraba_icon('verticals', 'rocket', { variant: 'duotone', color: 'naranja-impulso', size: '20px' })`
- `🤖` → `jaraba_icon('ai', 'copilot', ...)`
- `📚` → `jaraba_icon('ui', 'graduation', ...)`
- `👥` → `jaraba_icon('ui', 'users', ...)`
- `🎯` → `jaraba_icon('ui', 'compass', ...)`
- `💬` → `jaraba_icon('ui', 'chat', ...)`

**3.3 URL de privacidad (C3):**
```php
// SolicitudEiPublicForm.php linea 250
// ANTES: <a href="/privacidad" target="_blank">
// DESPUES: <a href="/politica-privacidad" target="_blank">
```

### Fase 2: Alta — Paginas Legales e Informativas

**3.4 Rutas nuevas** — 5 rutas anadidas a `ecosistema_jaraba_core.routing.yml`:
- `/politica-privacidad` → `LegalPagesController::privacy`
- `/terminos-uso` → `LegalPagesController::terms`
- `/politica-cookies` → `LegalPagesController::cookies`
- `/sobre-nosotros` → `InfoPagesController::about`
- `/contacto` → `InfoPagesController::contact`

**3.5 Controladores actualizados** — `LegalPagesController` e `InfoPagesController` leen contenido de `theme_get_setting()` para ser configurables desde la UI de Drupal sin tocar codigo.

**3.6 Templates nuevos** — 3 templates zero-region con `{% trans %}`:
- `legal-page.html.twig` — reutilizable para las 3 paginas legales
- `info-page-about.html.twig` — pagina sobre nosotros
- `info-page-contact.html.twig` — pagina de contacto

**3.7 Theme hooks** — 3 hooks nuevos en `ecosistema_jaraba_core.module` (`hook_theme()`):
- `legal_page` con variables: `page_type`, `title`, `content`, `last_updated`
- `info_page_about` con variables: `content`
- `info_page_contact` con variables: `content`, `contact_email`, `contact_phone`, `contact_address`

**3.8 Footer actualizado** — URLs por defecto en `_footer.html.twig`:
- Col2: `/about` → `/sobre-nosotros`, `/contact` → `/contacto`
- Col3: `/privacy` → `/politica-privacidad`, `/terms` → `/terminos-uso`, `/cookies` → `/politica-cookies`

### Fase 3: Media — Contenido y Configuracion

**3.9 Badge corregido** — `_features.html.twig`:
- Badge: `'4 verticales'` → `'6 verticales'`
- Descripcion actualizada con los 6 verticales reales

**3.10 Theme settings** — TAB 14 "Paginas Legales" anadido al formulario de configuracion del tema con campos para contenido de las 6 paginas (privacidad, terminos, cookies, sobre nosotros, contacto).

---

## 4. Reglas Derivadas

### FORM-MSG-001: Mensajes en templates de formulario custom
Cuando un modulo define un `#theme` custom para renderizar un formulario, el theme hook DEBE declarar una variable `messages` y el preprocess hook DEBE inyectar `['#type' => 'status_messages']`. El template DEBE renderizar `{{ messages }}` ANTES del `{{ form }}`.

**Patron obligatorio:**
```php
// hook_theme()
'mi_pagina_formulario' => [
  'variables' => ['form' => NULL, 'messages' => NULL],
],
// preprocess
function mimodulo_preprocess_mi_pagina_formulario(&$variables) {
  $variables['messages'] = ['#type' => 'status_messages'];
}
```

### LEGAL-ROUTE-001: URLs canonicas de paginas legales
Las paginas legales DEBEN usar URLs en espanol, SEO-friendly y sin ambiguedad: `/politica-privacidad`, `/terminos-uso`, `/politica-cookies`. Nunca URLs en ingles (`/privacy`) ni abreviadas (`/privacidad`). Todos los enlaces del formulario, footer y sitemap DEBEN apuntar a la URL canonica.

### LEGAL-CONFIG-001: Contenido legal configurable desde UI
El contenido de las paginas legales DEBE ser editable desde Theme Settings (Apariencia > Configuracion del tema) sin necesidad de tocar codigo. Los controladores leen de `theme_get_setting()` y los templates muestran un placeholder informativo cuando no hay contenido configurado.

---

## 5. Ficheros Modificados (12 ficheros)

| Fichero | Accion | Fase |
|---------|--------|------|
| `jaraba_andalucia_ei/jaraba_andalucia_ei.module` | Preprocess hook + variable messages | 1 |
| `jaraba_andalucia_ei/templates/solicitud-ei-page.html.twig` | `{{ messages }}` + 6 emojis → `jaraba_icon()` | 1 |
| `jaraba_andalucia_ei/src/Form/SolicitudEiPublicForm.php` | URL `/privacidad` → `/politica-privacidad` | 1 |
| `ecosistema_jaraba_core/ecosistema_jaraba_core.routing.yml` | 5 rutas nuevas | 2 |
| `ecosistema_jaraba_core/src/Controller/LegalPagesController.php` | Controlador con `theme_get_setting()` | 2 |
| `ecosistema_jaraba_core/src/Controller/InfoPagesController.php` | Controlador con `theme_get_setting()` | 2 |
| `ecosistema_jaraba_core/ecosistema_jaraba_core.module` | 3 theme hooks nuevos | 2 |
| `ecosistema_jaraba_core/templates/legal-page.html.twig` | Template zero-region reutilizable | 2 |
| `ecosistema_jaraba_core/templates/info-page-about.html.twig` | Template sobre nosotros | 2 |
| `ecosistema_jaraba_core/templates/info-page-contact.html.twig` | Template contacto | 2 |
| `ecosistema_jaraba_theme/templates/partials/_footer.html.twig` | URLs default actualizadas | 2 |
| `ecosistema_jaraba_theme/templates/partials/_features.html.twig` | Badge "6 verticales" | 3 |
| `ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme` | TAB 14 + footer defaults | 3 |

---

## 6. Verificacion en Browser

| Test | Resultado |
|------|-----------|
| Formulario vacio con bypass HTML5 → errores visibles en rojo | PASS |
| Honeypot time gate muestra "espera un momento" | PASS |
| 6 iconos SVG en la pagina del formulario (no emojis) | PASS |
| Enlace privacidad en formulario → `/politica-privacidad` | PASS |
| `/politica-privacidad` → 200 OK con template limpio | PASS |
| `/terminos-uso` → 200 OK | PASS |
| `/politica-cookies` → 200 OK | PASS |
| `/sobre-nosotros` → 200 OK con descripcion plataforma | PASS |
| `/contacto` → 200 OK | PASS |
| Footer: 10 links con URLs correctas en espanol | PASS |
| Badge homepage: "6 verticales" | PASS |

---

## 7. Leccion Clave

**Los templates de formulario custom en Drupal NO heredan automaticamente los mensajes de estado.** Cuando un modulo define su propio theme hook para envolver un formulario (patron zero-region), los `status_messages` de Drupal no se renderizan a menos que se inyecten explicitamente. Este es un error silencioso que pasa desapercibido porque:

1. La validacion HTML5 del browser intercepta muchos errores antes de llegar al servidor
2. Los mensajes de exito se almacenan en la sesion y se pierden en la redireccion si el template de destino tampoco los renderiza
3. No hay error visible en los logs de Drupal

**Regla FORM-MSG-001** asegura que todo template custom de formulario inyecte y renderice los mensajes del sistema.
