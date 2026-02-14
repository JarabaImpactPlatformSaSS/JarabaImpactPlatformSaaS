# Navegacion Contextual por Avatar (AvatarNavigationService)

**Fecha:** 2026-02-13
**Autor:** IA Asistente
**Version:** 1.0.0
**Sesion:** Implementacion del sistema de navegacion contextual por avatar — Spec f-103 Fase 1

---

## 1. Contexto

Las 324+ rutas frontend del ecosistema SaaS operaban como islas aisladas. El header mostraba solo 5 enlaces estaticos de marketing. Un usuario autenticado solo veia "Mi cuenta" y "Cerrar sesion". Los building blocks existian (AvatarDetectionService, CopilotContextService, EmployabilityMenuService) pero no estaban conectados para navegacion visible.

## 2. Situacion → Aprendizaje → Regla

### Aprendizaje 1: Generalizacion de servicios de menu por vertical

**Situacion:** `EmployabilityMenuService` funcionaba perfectamente para 1 vertical (Empleabilidad) con 4 roles (candidate, employer, student, anonymous). Sin embargo, el ecosistema tiene 7+ verticales y 10 avatares.

**Aprendizaje:** Generalizar un servicio existente probado es mas eficiente y seguro que crear uno desde cero. `AvatarNavigationService` replica el patron exacto de `EmployabilityMenuService` (misma estructura de items, mismos campos: id, label, url, icon_category, icon_name, weight) y le anade `active` state.

**Regla NAV-001:** Para servicios de navegacion multi-vertical, usar un servicio centralizado con mapeo de avatares a items, NO un servicio por vertical. Las URLs se resuelven con try/catch para modulos opcionales.

### Aprendizaje 2: Propagacion DRY via _header.html.twig

**Situacion:** Habia ~33 page templates que incluyen `_header.html.twig`. Insertar la avatar-nav en cada una requeriria modificar 33 ficheros.

**Aprendizaje:** Insertar el include de `_avatar-nav.html.twig` dentro de `_header.html.twig` (al final, despues del mobile menu overlay) permite propagacion automatica a TODAS las page templates sin tocarlas. Solo las que usan `only` keyword necesitan pasar la variable explicitamente.

**Regla NAV-002:** Los componentes globales de navegacion deben incluirse dentro de `_header.html.twig` (no en cada page template) para propagacion DRY. Guard con `{% if avatar_nav is defined and avatar_nav %}`.

### Aprendizaje 3: Scope leak en includes con `only`

**Situacion:** 7 de las ~33 page templates usan `} only %}` al incluir el header. Esto bloquea TODAS las variables del scope padre que no esten listadas explicitamente en el bloque `with { ... }`.

**Aprendizaje:** Cuando se anade una nueva variable global (como `avatar_nav`) que debe llegar al header, TODAS las page templates que usen `only` deben actualizarse para pasar `'avatar_nav': avatar_nav|default(null)`. Las templates SIN `only` la heredan automaticamente.

**Regla NAV-003:** Al crear variables globales en `preprocess_page()` que se usen en partials del header, buscar `} only %}` en todas las `page--*.html.twig` y anadir la variable al bloque `with`.

### Aprendizaje 4: Resolucion segura de URLs para modulos opcionales

**Situacion:** Los items de navegacion apuntan a rutas de modulos que pueden no estar instalados (ej: `jaraba_paths.catalog`, `jaraba_mentoring.mentor_catalog`). Llamar a `Url::fromRoute()` con una ruta inexistente lanza RouteNotFoundException.

**Aprendizaje:** Envolver cada `Url::fromRoute()` en try/catch y omitir silenciosamente los items cuya ruta no existe. Esto hace que la navegacion sea resiliente a modulos opcionales — si un vertical no esta instalado, sus items simplemente no aparecen.

**Regla NAV-004:** En servicios que resuelven URLs de multiples modulos, SIEMPRE usar try/catch por item. Nunca fallar toda la navegacion por un modulo faltante.

### Aprendizaje 5: Bottom nav mobile requiere padding defensivo

**Situacion:** La bottom nav fija en mobile (`position: fixed; bottom: 0`) tapa el contenido inferior de la pagina.

**Aprendizaje:** Usar body class `.has-avatar-nav` (inyectada en `hook_preprocess_html()`) para aplicar `padding-bottom: 60px` solo en viewports mobile. Esto evita que la nav tape el ultimo elemento de la pagina. `env(safe-area-inset-bottom)` para iOS notch.

**Regla NAV-005:** Componentes `position: fixed` en mobile DEBEN tener una body class correspondiente que aplique padding defensivo al body, y usar `env(safe-area-inset-bottom)` para iOS.

## 3. Reglas Nuevas

| Regla | Descripcion |
|-------|-------------|
| NAV-001 | Servicio centralizado de navegacion multi-avatar, NO uno por vertical. URLs con try/catch |
| NAV-002 | Componentes globales de nav dentro de _header.html.twig para propagacion DRY |
| NAV-003 | Variables globales usadas en header requieren actualizar templates con `only` |
| NAV-004 | Url::fromRoute() en bucle SIEMPRE con try/catch por item |
| NAV-005 | Fixed mobile components requieren body class + padding defensivo + safe-area-inset |

## 4. Ficheros Creados/Modificados

| Fichero | Accion |
|---------|--------|
| `ecosistema_jaraba_core/src/Service/AvatarNavigationService.php` | Creado |
| `ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml` | Modificado |
| `ecosistema_jaraba_theme/templates/partials/_avatar-nav.html.twig` | Creado |
| `ecosistema_jaraba_theme/scss/components/_avatar-nav.scss` | Creado |
| `ecosistema_jaraba_theme/scss/main.scss` | Modificado |
| `ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme` | Modificado (preprocess_page, preprocess_html, form_alter) |
| `ecosistema_jaraba_theme/templates/partials/_header.html.twig` | Modificado |
| 7 page templates con `only` | Modificado (avatar_nav pasado al header) |
| `ecosistema_jaraba_theme/css/main.css` | Compilado |

## 5. Spec Cubierta

| Spec | Estado |
|------|--------|
| f-103 UX Journey Avatars — Context Engine | Parcial (Capa 1 de 3) |
| f-103 UX Journey Avatars — Presentation Engine | Parcial (sin AI Decision Engine) |
| f-103 UX Journey Avatars — 19 Avatares | 10 cubiertos |
| f-177 Global Nav System — Mobile Menu | Bottom nav fija implementada |
| f-177 Global Nav System — User Menu | Nav contextual sustituye menu estatico |
