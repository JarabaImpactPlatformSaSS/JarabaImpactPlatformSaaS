---
description: Verificación completa en navegador después de cada implementación
---

# Verificación en Navegador (Obligatorio)

Después de **cualquier cambio de código o configuración**, SIEMPRE ejecutar verificación visual en navegador.

## Pasos Obligatorios

### 1. Cache Rebuild
```bash
// turbo
docker exec jarabasaas_appserver_1 drush cr
```

### 2. Verificación Visual
Usar `browser_subagent` para:
- Navegar a la(s) página(s) afectadas
- Hard refresh (Ctrl+F5) para evitar caché del navegador
- Capturar screenshot de evidencia
- Verificar que NO hay errores 500/404
- Confirmar que los cambios son visibles

### 3. Checklist de Verificación

Para cambios de **UI/UX**:
- [ ] Elementos visibles donde deben estar
- [ ] Estilos aplicados correctamente
- [ ] Responsive (si aplica)
- [ ] Interacciones funcionan (clicks, hovers)

Para cambios de **bloques/regiones**:
- [ ] Bloque aparece en la región correcta
- [ ] Visible en TODAS las páginas si es global
- [ ] Cache contexts correctos

Para cambios de **rutas/controladores**:
- [ ] Ruta accesible sin errores
- [ ] Contenido correcto renderizado
- [ ] Permisos funcionan

Para verificación de **diseño premium**:
- [ ] Header glassmórfico (backdrop-filter: blur)
- [ ] Footer premium presente
- [ ] Estilos consistentes con homepage
- [ ] Verificar via JavaScript: `getComputedStyle(header).backdropFilter === "blur(20px)"`

Para verificación de **bloques premium con JS**:
- [ ] Efectos animated_beam: líneas SVG conectan hub con nodos
- [ ] Efectos gradient_cards: bordes de 4px con gradiente visible y animado
- [ ] Efectos text_gradient: texto con gradiente animado (clase is-animated)
- [ ] Glassmorphism: backdrop-filter blur visible sobre fondo texturizado
- [ ] Verificar en consola: `Object.keys(Drupal.behaviors)` incluye behaviors esperados
- [ ] Si falla: usar Hard Refresh (Ctrl+Shift+R) para limpiar cache de JS

### 4. Documentación
Incluir screenshots en walkthrough.md como evidencia.

## Ejemplo de Verificación

```
browser_subagent:
  Task: "Verificar cambios en /courses:
    1. Navegar a https://jaraba-saas.lndo.site/courses
    2. Hard refresh
    3. Verificar [elemento específico]
    4. Captura screenshot
    RETURN: Estado de verificación"
```

// turbo-all
