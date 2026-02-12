# Tareas Pendientes: Jaraba Canvas Editor

**Fecha**: 5 de Febrero de 2026  
**Responsable**: Equipo Técnico  
**Prioridad**: ALTA

---

## 📋 Resumen de Estado Actual

El Canvas Editor v3 está parcialmente implementado con GrapesJS. Durante la sesión del 4 de Febrero se implementaron componentes `jaraba-navigation` y `jaraba-button` con el patrón correcto de GrapesJS (changeProp + listeners), pero requieren verificación completa.

---

## ✅ Tareas Completadas (4 Feb)

- [x] Componente `jaraba-navigation` con traits configurables (texto/URL por enlace)
- [x] Componente `jaraba-button` con traits: texto, URL, estilo, target
- [x] Bloques button-primary y button-secondary usan componente jaraba-button
- [x] Investigación documentación oficial GrapesJS (Traits, Components, lifecycle hooks)

---

## 🔴 Tareas Pendientes para Mañana

### 1. Configuración de Bloques de Navegación (ALTA)

> **Estado**: Implementado pero no verificado completamente

**Subtareas:**
- [ ] Verificar que los traits de navegación aparecen en el panel derecho
- [ ] Probar cambio de "Número de enlaces" y confirmar que se actualizan traits
- [ ] Probar edición de texto/URL de enlaces y confirmar update en canvas
- [ ] Depurar si no funciona: revisar listeners `change:link${i}_text`

**Archivos involucrados:**
- `web/modules/custom/jaraba_page_builder/js/grapesjs-jaraba-blocks.js` (líneas 162-260)

---

### 2. Panel SEO Auditor (ALTA)

> **Referencia**: Sección 6.1 de la Arquitectura Maestra

**Objetivo:** Crear panel de auditoría SEO en tiempo real que valide:
- H1 único en la página
- Jerarquía correcta de headings (H1 → H2 → H3...)
- Meta description length (150-160 chars)
- Alt text en imágenes

**Subtareas:**
- [ ] Crear archivo `grapesjs-jaraba-seo.js`
- [ ] Implementar panel custom en sidebar derecho
- [ ] Añadir validaciones:
  - [ ] `checkH1Unique()` - Solo un H1 por página
  - [ ] `checkHeadingHierarchy()` - No saltar niveles
  - [ ] `checkImagesAlt()` - Todas las imágenes con alt
- [ ] Mostrar indicadores visuales (✅ / ⚠️ / ❌)

**Referencia de implementación:**
```javascript
editor.on('update', () => {
  const issues = seoAuditor.run(editor.getHtml());
  seoPanel.render(issues);
});
```

---

### 3. Revisión Estado de Implementación vs Arquitectura Maestra

> **Documento**: `docs/tecnicos/20260204b-Canvas_Editor_v3_Arquitectura_Maestra.md`

**Checklist de validación:**

#### Sprint 1-2 (Core GrapesJS)
- [x] GrapesJS carga en `/page/{id}/editor?mode=canvas`
- [x] Toggle UI funcional entre modos
- [x] Storage REST persiste cambios

#### Sprint 3-4 (Adaptador Bloques)
- [x] Bloques básicos (H1-H4, párrafo, botones) visibles en Block Manager
- [ ] **67 bloques completos** - Solo hay ~12 básicos implementados
- [ ] Thumbnails SVG para todos los bloques
- [ ] Feature flags para planes (starter/professional/enterprise)

#### Sprint 5-6 (Parciales Editables)
- [ ] **Componente jaraba-header** con traits de variante
- [ ] **Componente jaraba-footer** con traits de variante
- [ ] Hot-swap de variantes header/footer
- [ ] Toast de advertencia para cambios globales

#### Integraciones Pendientes
- [ ] AI Content Assistant (`grapesjs-jaraba-ai.js`)
- [ ] Menu Editor modal (integración Doc 177)
- [ ] Onboarding tour (`canvas-onboarding.js`)

---

## 📁 Archivos a Crear/Modificar

| Archivo | Acción | Prioridad |
|---------|--------|-----------|
| `js/grapesjs-jaraba-seo.js` | [NEW] Panel SEO Auditor | ALTA |
| `js/grapesjs-jaraba-partials.js` | [NEW] Componentes header/footer | MEDIA |
| `js/grapesjs-jaraba-ai.js` | [NEW] Integración AI | BAJA |
| `css/grapesjs-overrides.css` | [MODIFY] Estilos panel SEO | MEDIA |

---

## 🧪 Comandos de Verificación

```bash
# Limpiar caché
wsl -d Ubuntu -- bash -c "cd ~/PED/JarabaImpactPlatformSaaS && lando drush cr"

# Abrir Canvas Editor
# URL: https://jaraba-saas.lndo.site/es/page/17/editor?mode=canvas

# Console check para traits
editor.getSelected().get('traits').models.map(t => t.get('name'))
```

---

## 📊 Métricas de Éxito

| Métrica | Target | Actual |
|---------|--------|--------|
| Bloques configurables | 67 | ~12 |
| Parciales con traits | 2 | 0 |
| SEO Auditor | ✅ | ❌ |
| Tests Cypress | 6 | 0 |

---

*Documento generado: 4 de Febrero de 2026*
