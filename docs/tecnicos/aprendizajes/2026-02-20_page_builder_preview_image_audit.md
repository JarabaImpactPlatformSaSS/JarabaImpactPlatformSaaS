# Aprendizaje #103: Page Builder Preview Image Audit & Generation

**Fecha:** 2026-02-20
**Contexto:** Auditoría de 4 escenarios del Page Builder (Biblioteca de Plantillas, Canvas Editor, Canvas Insertion, Página Pública)
**Impacto:** 66 imágenes de preview generadas y desplegadas para 6 verticales

---

## Problema

Los 55 bloques de 5 verticales (AgroConecta, ComercioConecta, Empleabilidad, Emprendimiento, ServiciosConecta) mostraban **imágenes rotas** en la Biblioteca de Plantillas. Adicionalmente, los 11 bloques de JarabaLex carecían de imágenes de preview.

## Causa Raíz

1. **Config YMLs referencian PNGs inexistentes**: Los archivos YAML en `config/install/` (ej: `jaraba_page_builder.template.agroconecta_hero.yml`) incluyen `preview_image: '/modules/custom/jaraba_page_builder/images/previews/agroconecta-hero.png'`, pero los archivos PNG **nunca fueron creados**.

2. **Fallback auto-detección también falla**: `PageTemplate::getPreviewImage()` intenta auto-detectar convirtiendo el ID `agroconecta_hero` → `agroconecta-hero.png` y buscando en `images/previews/`, pero tampoco encuentra nada.

3. **JarabaLex es GrapesJS-only**: Los bloques legales se definen directamente en `grapesjs-jaraba-legal-blocks.js`, no como config entities `PageTemplate`. No participan del pipeline `getPreviewImage()`.

## Solución Aplicada

- Generadas **66 imágenes premium** estilo 3D glassmorphism con paletas únicas por vertical:
  | Vertical | Bloques | Paleta |
  |----------|:-------:|--------|
  | AgroConecta | 11 | Verde-dorado |
  | ComercioConecta | 11 | Naranja-ámbar |
  | Empleabilidad | 11 | Azul-teal |
  | Emprendimiento | 11 | Púrpura-violeta |
  | ServiciosConecta | 11 | Teal-cyan |
  | JarabaLex | 11 | Navy-dorado (#1E3A5F + #C8A96E) |

- Desplegadas en `web/modules/custom/jaraba_page_builder/images/previews/`

## Hallazgos Adicionales (Canvas Editor — Escenario 2)

- **219 bloques** registrados en el BlockManager
- **31 categorías** activas (incluyendo JarabaLex como categoría `legal`)
- **Bloques duplicados**: Separador (2x), Tabla de Precios (3x), Hero con Video (2x), Cuenta Regresiva (2x)

## Regla Derivada

**PB-PREVIEW-002**: Todo vertical que se añada al Page Builder DEBE generar sus imágenes de preview PNG en `images/previews/` ANTES de desplegar a producción. Convención de nombres: `{vertical}-{tipo}.png` (ej: `agroconecta-hero.png`). Paleta de colores consistente por vertical usando design tokens `--ej-{vertical}-*`.

**PB-DUP-001**: No DEBEN existir bloques con el mismo label en el BlockManager GrapesJS. Antes de registrar un bloque, verificar `blockManager.get(id)` para evitar duplicados entre bloques estáticos y dinámicos (API Template Registry).

## Archivos Clave

- `src/Entity/PageTemplate.php` → `getPreviewImage()` (auto-detección por convención)
- `src/Service/TemplateRegistryService.php` → Pipeline de preview images
- `js/grapesjs-jaraba-blocks.js` → 208+ bloques estáticos
- `js/grapesjs-jaraba-legal-blocks.js` → 11 bloques JarabaLex
- `config/install/jaraba_page_builder.template.*.yml` → Config entities con `preview_image`
