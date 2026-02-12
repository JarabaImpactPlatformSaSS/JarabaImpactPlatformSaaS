# Aprendizajes: Mejoras Front-End Premium Landing

**Fecha:** 2026-01-24  
**Contexto:** Implementación de Quick Wins para homepage Jaraba SaaS  
**Tiempo invertido:** ~4 horas

---

## 📚 Lecciones Aprendidas

### 1. CSS Selectors en Drupal
**Problema:** Estilos CSS no aplicaban al FAB copiloto.  
**Causa:** Selectores BEM (`&__button`) no coincidían con clases HTML simples (`.agent-fab-trigger`).  
**Solución:** Usar selectores exactos que coincidan con el HTML generado.

### 2. Menú Móvil z-index
**Problema:** Botón "Empezar gratis" tapaba el botón de cerrar menú.  
**Causa:** El toggle no tenía z-index suficiente cuando `.is-active`.  
**Solución:** Toggle con `z-index: 1002`, panel con `z-index: 1001`.

### 3. Animaciones CSS en SVG
**Patrón exitoso:**
- `stroke-dasharray` + `stroke-dashoffset` para animación de dibujo
- `@keyframes` separados por tipo: float, pulse, blink
- `animation-delay` negativo para orbs desfasados

---

## 🔧 Patrones Reutilizables

### Feature Cards con Badges
```scss
.feature-card__badge {
  display: inline-block;
  padding: 0.375rem 0.75rem;
  background: rgba(color, 0.1);
  border-radius: 20px;
  font-size: 0.75rem;
}
```

### Partículas Flotantes
```scss
&__particles::before,
&__particles::after {
  content: "";
  position: absolute;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(color, 0.12) 0%, transparent 70%);
  animation: particleFloat 20s ease-in-out infinite;
}
```

---

## 📋 Checklist para Próximas Mejoras Frontend

- [ ] Verificar selectores CSS coinciden con HTML
- [ ] Probar en viewport móvil antes de commit
- [ ] Usar z-index consistente en overlays
- [ ] Añadir `prefers-reduced-motion` para animaciones
- [ ] Compilar SCSS después de cada cambio

---

## 🔗 Archivos Modificados

| Archivo | Cambios |
|---------|---------|
| `page--front.html.twig` | Hero, features mejoradas, FAB copiloto |
| `_landing-page.scss` | Estilos menú móvil, features, FAB |
| `_hero-landing.scss` | Partículas, gradient pulse |
| `scroll-animations.js` | Behaviors: counter, mobileMenu, copilot |

---

## 🎯 Plantillas Twig Limpias (Sin Regiones)

### Problema
Las páginas de Drupal por defecto incluyen sidebar, header y footer de regiones. Para landings y homepages profesionales necesitamos control total del layout.

### Solución: Plantilla page--front.html.twig

```twig
{#
 * page--front.html.twig - Homepage profesional sin regiones.
 * NO renderiza: page.sidebar, page.header, page.footer de Drupal.
 #}
{{ attach_library('ecosistema_jaraba_theme/homepage') }}

<div class="homepage-wrapper">
  {# Hero renderizado desde bloque custom #}
  {{ drupal_block('hero_landing_block') }}
  
  {# Solo el contenido, sin regiones #}
  <main>{{ page.content }}</main>
  
  {# Footer custom inline o partial #}
  {% include '@ecosistema_jaraba_theme/partials/footer-homepage.html.twig' %}
</div>
```

### Activación por Ruta Dinámica

```php
// En ecosistema_jaraba_theme.theme
function ecosistema_jaraba_theme_theme_suggestions_page_alter(&$suggestions, $variables) {
  $route = \Drupal::routeMatch()->getRouteName();
  
  // Rutas de landing usan plantilla limpia
  if (str_starts_with($route, 'ecosistema_jaraba_core.landing')) {
    $suggestions[] = 'page__clean';
  }
}
```

### Lecciones Clave

| Aspecto | Recomendación |
|---------|---------------|
| **Naming** | `page--RUTA.html.twig` o `page--node--TYPE.html.twig` |
| **Libraries** | Usar `attach_library()` para CSS/JS específicos |
| **Bloques** | Renderizar con `drupal_block('plugin_id')` |
| **Partials** | Organizar en `/templates/partials/` |
| **Cache** | Siempre `drush cr` después de crear plantilla |

### Cuándo Usar

✅ **Usar plantilla limpia:**
- Homepage de SaaS
- Landings de campaña
- Páginas de producto
- Portales de login/registro

❌ **No usar (mantener regiones):**
- Páginas administrativas
- Dashboards con sidebar
- Páginas de contenido editorial
