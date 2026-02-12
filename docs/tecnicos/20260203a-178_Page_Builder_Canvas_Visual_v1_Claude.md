



178
ESPECIFICACIÓN TÉCNICA

Jaraba Canvas
Page Builder Visual de Clase Mundial

GrapesJS + Drupal 11 | Canvas Visual | Drag & Drop | Live Preview | Multi-Tenant

Versión:	1.0.0
Fecha:	3 de Febrero de 2026
Estado:	Especificación Técnica Definitiva
Horas Estimadas:	100-130 horas
Inversión:	€8.000-10.400 (@€80/h)
Dependencias:	Docs 160, 162, 163, 100, 176


Ecosistema Jaraba | EDI Google Antigravity
Plataforma de Ecosistemas Digitales S.L. © 2026
 
1. Resumen Ejecutivo
Este documento especifica la evolución del Constructor de Páginas actual del Ecosistema Jaraba hacia una experiencia visual de clase mundial denominada Jaraba Canvas. El sistema actual cuenta con +70 templates y renderizado funcional pero adolece de una experiencia de edición visual: no existe drag-and-drop, el preview solo funciona post-guardado, y el reordenamiento de bloques se realiza mediante formularios Drupal estándar.
La estrategia propuesta integra GrapesJS (framework open-source con 22.000+ estrellas en GitHub, 8+ años de madurez) como motor visual embebido dentro de la arquitectura Drupal 11 existente, reutilizando al 100% las entidades page_template, page_content y block_template ya especificadas en los documentos 160 y 162. Esta aproximación permite alcanzar paridad con Webflow/Elementor en experiencia de edición con una inversión de 100-130 horas, frente a las 400+ horas que requeriría un desarrollo completamente custom.
1.1 Decisión Arquitectónica Clave
Opción	Horas	Riesgo	Resultado
A) Custom (SortableJS + Alpine.js)	300-400h	Alto	Funcional pero limitado
B) GrapesJS Open Source (recomendada)	100-130h	Bajo	Clase mundial
C) GrapesJS Studio SDK (Enterprise)	60-80h	Medio (vendor)	Premium + soporte

Se recomienda la Opción B: GrapesJS Open Source integrado nativamente en Drupal 11. El framework es MIT-licensed, se instala como dependencia npm, y genera HTML/CSS estándar que se persiste en las entidades existentes (content_data JSON del page_content). No introduce vendor lock-in y permite migrar a Studio SDK en el futuro si se requieren features premium como colaboración en tiempo real.
1.2 Ventajas Competitivas de GrapesJS para Jaraba
•	Diseñado para CMS: creado específicamente para integrarse con sistemas de gestión de contenido, no como SaaS standalone
•	White-label nativo: UI completamente personalizable sin branding externo, alineado con la filosofía de marca Jaraba
•	Plugin ecosystem maduro: +50 plugins oficiales y comunitarios para formularios, imágenes, bloques predefinidos, exportación
•	Sin iframes de terceros: el editor se ejecuta directamente en el DOM, permitiendo inyección de Design Tokens y CSS Custom Properties del tenant
•	Datos propios: todo el HTML/CSS/JSON generado se almacena en la BD del ecosistema, zero dependencia externa
•	Compatible con Drupal: ya existe módulo contrib (drupal/grapesjs_editor) como referencia de integración
 
2. Investigación de Mercado y Benchmark
2.1 Constructores de Páginas Clase Mundial (2025-2026)
Plataforma	Modelo	UX Core	Precio/mes	Relevancia
Webflow	SaaS cerrado	Canvas visual full, CSS real-time	$29-212	Benchmark UX de referencia
Elementor	WP Plugin	Sidebar + canvas, widgets drag	$12-25	Patrón sidebar + preview
Wix	SaaS cerrado	Canvas WYSIWYG, AI website gen	$17-159	Accesibilidad para no-técnicos
Framer	SaaS + code	Motion design, React components	$15-35	Animaciones y micro-interactions
Squarespace	SaaS cerrado	Bloques + sections, templates	$16-65	Elegancia y simplicidad
Divi (WP)	WP Plugin	Visual builder, A/B testing	$89/año	2000+ layouts, efectos visuales
Breakdance	WP Plugin	Next-gen WP builder, rápido	$149/life	Performance + clean output
2.2 Frameworks Embebibles para SaaS (Build vs Buy)
Framework	Licencia	Stars GH	Embebible	Ideal Para
GrapesJS	MIT (free)	22.000+	Sí (npm)	CMS/SaaS con editor visual
Puck (React)	Apache 2.0	6.000+	Sí (React)	Apps React con page building
Craft.js	MIT	7.000+	Sí (React)	Editores React custom
Unlayer	Comercial	N/A	Sí (SDK)	Email + landing builders
Storyblok	Comercial	N/A	Via iframe	CMS headless + visual editor

GrapesJS destaca como la única opción que combina licencia MIT, madurez de 8+ años, diseño específico para CMS, y la capacidad de funcionar sin iframes ni dependencias de terceros. Su arquitectura de bloques personalizables encaja perfectamente con los 67 bloques ya especificados en el documento 162 (45 base + 12 Aceternity + 10 Magic UI).
2.3 Por Qué NO Desarrollar un Canvas Custom
El plan de trabajo original (documento adjunto) proponía SortableJS + Alpine.js + iframe postMessage. Este enfoque presenta riesgos críticos:
•	Reinventar la rueda: un editor visual completo requiere gestión de undo/redo stack, drag-and-drop con snap guides, responsive breakpoints, style manager, layer manager, y cientos de edge cases que GrapesJS ya resuelve
•	Mantenimiento exponencial: cada nuevo bloque requiere adaptador custom para drag, edit inline, preview, y persistencia
•	Deuda técnica: la comunicación parent-iframe vía postMessage es frágil y difícil de debuggear en producción
•	ROI negativo: 300-400h de inversión para un resultado inferior al que GrapesJS ofrece en 100-130h
 
3. Arquitectura Técnica: Jaraba Canvas
3.1 Diagrama de Arquitectura
┌───────────────────────────────────────────────────────────────┐
│              JARABA CANVAS EDITOR (Drupal Admin UI)             │
├───────────────────────────────────────────────────────────────┤
│                                                               │
│   ┌────────────────────────────────────────────────────────┐   │
│   │                  GrapesJS Editor Instance                │   │
│   │                                                          │   │
│   │  ┌───────────┐  ┌──────────────┐  ┌────────────────┐  │   │
│   │  │ Block     │  │ Canvas (WYSI │  │ Style Manager  │  │   │
│   │  │ Manager   │  │ WYG Preview) │  │ (CSS Props)    │  │   │
│   │  │ 67 bloques│  │              │  │ Design Tokens  │  │   │
│   │  └───────────┘  └──────────────┘  └────────────────┘  │   │
│   │                                                          │   │
│   └────────────────────────────────────────────────────────┘   │
│                        │  REST API  │                          │
├───────────────────────┴────────────┴─────────────────────────┤
│  DRUPAL 11 BACKEND                                             │
│  ┌───────────────┐ ┌───────────────┐ ┌───────────────┐      │
│  │ page_template │ │ page_content  │ │ block_template│      │
│  │ (schema JSON) │ │ (user data)   │ │ (67 bloques)  │      │
│  └───────────────┘ └───────────────┘ └───────────────┘      │
│  Group Module (Multi-Tenant) + Design Tokens (Cascada)         │
└───────────────────────────────────────────────────────────────┘
3.2 Flujo de Datos
El flujo completo desde edición hasta renderizado en frontend público:
•	1. Carga del editor: Drupal Controller carga la ruta /admin/page-builder/{id}/canvas. Inyecta GrapesJS con los bloques del tenant (filtrados por plan) y el content_data existente del page_content.
•	2. Edición visual: El usuario arrastra bloques desde el Block Manager al canvas WYSIWYG. GrapesJS gestiona internamente el DOM virtual, undo/redo, y responsive breakpoints.
•	3. Persistencia: Al guardar (manual o auto-save), GrapesJS exporta HTML + CSS + componentes JSON. El Controller los almacena en page_content.content_data via REST API con debounce de 3s.
•	4. Renderizado público: El frontend público (Zero Region Policy) lee content_data del page_content y renderiza via Twig templates + CSS Custom Properties del tenant. Sin GrapesJS en frontend.
3.3 Frontend Limpio: Zero Region Policy + GrapesJS
La integración de GrapesJS es EXCLUSIVAMENTE en el admin UI (/admin/page-builder/*). El frontend público mantiene la Zero Region Policy ya implementada:
Capa	Admin (Editor)	Frontend (Público)
HTML	GrapesJS canvas DOM	Twig SSR (page--frontend.html.twig)
CSS	GrapesJS Style Manager	CSS Custom Properties + compilado SCSS
JS	grapesjs.min.js + plugins	CERO JavaScript de GrapesJS
Regiones Drupal	Admin theme estándar	CERO regiones, CERO bloques Drupal
Design Tokens	Style Manager lee tokens	hook_preprocess_html() inyecta :root
Multi-Tenant	Bloques filtrados por plan	Contenido aislado por Group Module
 
4. Integración con Entidades Existentes
Jaraba Canvas NO crea nuevas entidades. Reutiliza al 100% la arquitectura de datos de los documentos 160/162 añadiendo un campo grapesjs_data al page_content y un adaptador que transforma los 67 block_template en bloques GrapesJS.
4.1 Extensión del page_content
Campo	Tipo	Descripción	Ejemplo
grapesjs_html	LONGTEXT	HTML generado por GrapesJS	<section class="jaraba-hero">...
grapesjs_css	LONGTEXT	CSS scoped generado	.jaraba-hero { background: ... }
grapesjs_components	JSON	Estructura interna GrapesJS (para re-edición)	[{type: 'jaraba-hero', ...}]
grapesjs_styles	JSON	Estilos GrapesJS internos	[{selectors: [...], style: {...}}]
editor_mode	VARCHAR(16)	canvas | legacy (fallback formularios)	canvas
4.2 Adaptador block_template → GrapesJS Block
Cada uno de los 67 bloques definidos en el documento 162 se registra automáticamente como bloque GrapesJS mediante un adaptador PHP + JS:
// jaraba_page_builder/js/grapesjs-jaraba-blocks.js
// Adaptador que registra bloques Jaraba en GrapesJS

export default (editor, opts = {}) => {
  const bm = editor.BlockManager;
  const blocks = opts.jarabaBlocks || [];

  blocks.forEach(block => {
    bm.add(`jaraba-${block.machine_name}`, {
      label: block.label,
      category: block.category,
      media: `<img src="${block.thumbnail}" />`,
      content: {
        type: `jaraba-${block.machine_name}`,
        // HTML base del template Twig renderizado con datos default
        content: block.default_html,
        style: block.default_css,
      },
      attributes: { class: 'gjs-block-jaraba' }
    });
  });
};
4.3 Categorías de Bloques en el Block Manager
Categoría	Bloques	Plan Mínimo	Icono
Hero Sections	8 variantes (fullscreen, split, video, gradient...)	Starter	🎨
Features & Benefits	7 variantes (grid, tabs, alternating, comparison...)	Starter	⭐
Content	6 variantes (text, image+text, accordion, timeline...)	Starter	📝
CTA & Conversion	5 variantes (banner, floating, inline, exit-intent...)	Starter	📢
Social Proof	5 variantes (testimonials, logos, reviews, stats...)	Starter	👍
Pricing	4 variantes (table, cards, toggle, comparison...)	Professional	💰
Forms & Contact	5 variantes (contact, newsletter, multi-step...)	Professional	📧
Media	5 variantes (gallery, video, carousel, lightbox...)	Professional	🎬
Aceternity UI	12 componentes premium (Spotlight, 3D Card, Reveal...)	Enterprise	✨
Magic UI	10 componentes premium (Bento, Marquee, Beam...)	Enterprise	🪄
 
5. Implementación GrapesJS en Drupal 11
5.1 Estructura de Archivos
modules/custom/jaraba_page_builder/
├── js/
│   ├── grapesjs-init.js              # Inicialización del editor
│   ├── grapesjs-jaraba-blocks.js      # Adaptador 67 bloques
│   ├── grapesjs-jaraba-storage.js     # Persistencia REST API
│   ├── grapesjs-jaraba-panels.js      # Paneles custom (save, preview)
│   ├── grapesjs-jaraba-tokens.js      # Inyección Design Tokens tenant
│   └── grapesjs-jaraba-ai.js          # Asistente IA para contenido
├── css/
│   ├── grapesjs-jaraba-theme.css      # Skin del editor (colores Jaraba)
│   └── grapesjs-canvas-overrides.css  # Override estilos del canvas
├── src/
│   ├── Controller/
│   │   └── CanvasEditorController.php # Ruta /admin/page-builder/{id}/canvas
│   ├── Service/
│   │   ├── GrapesJSBlockAdapter.php   # Transforma block_template → GrapesJS
│   │   └── GrapesJSStorageService.php # Save/Load content_data
│   └── Plugin/
│       └── rest/resource/
│           └── CanvasDataResource.php     # Endpoint REST para auto-save
└── templates/
    └── jaraba-canvas-editor.html.twig # Template del editor
5.2 Inicialización del Editor (Código Clave)
// grapesjs-init.js - Inicialización configurada para Jaraba Canvas

import grapesjs from 'grapesjs';
import jarabaBlocksPlugin from './grapesjs-jaraba-blocks';
import jarabaStoragePlugin from './grapesjs-jaraba-storage';
import jarabaPanelsPlugin from './grapesjs-jaraba-panels';
import jarabaTokensPlugin from './grapesjs-jaraba-tokens';

const editor = grapesjs.init({
  container: '#jaraba-canvas-editor',
  height: '100vh',
  width: 'auto',
  fromElement: false,

  // Almacenamiento via REST API Drupal
  storageManager: {
    type: 'jaraba-rest',
    autosave: true,
    autoload: true,
    stepsBeforeSave: 3,  // Auto-save cada 3 cambios
  },

  // Canvas con estilos del tenant
  canvas: {
    styles: [
      drupalSettings.jarabaCanvas.tenantCssUrl,
      drupalSettings.jarabaCanvas.designTokensUrl,
    ],
  },

  // Device Manager (responsive)
  deviceManager: {
    devices: [
      { name: 'Desktop', width: '' },
      { name: 'Tablet', width: '768px', widthMedia: '1024px' },
      { name: 'Móvil', width: '375px', widthMedia: '480px' },
    ]
  },

  // Plugins Jaraba
  plugins: [
    jarabaBlocksPlugin,
    jarabaStoragePlugin,
    jarabaPanelsPlugin,
    jarabaTokensPlugin,
  ],
  pluginsOpts: {
    [jarabaBlocksPlugin]: {
      jarabaBlocks: drupalSettings.jarabaCanvas.blocks,
      tenantPlan: drupalSettings.jarabaCanvas.tenantPlan,
    },
    [jarabaStoragePlugin]: {
      pageId: drupalSettings.jarabaCanvas.pageId,
      csrfToken: drupalSettings.jarabaCanvas.csrfToken,
      apiBase: '/api/v1/pages',
    },
  },
});
5.3 Storage Plugin (Persistencia REST)
// grapesjs-jaraba-storage.js
export default (editor, opts) => {
  editor.StorageManager.add('jaraba-rest', {
    async load() {
      const res = await fetch(
        `${opts.apiBase}/${opts.pageId}/canvas-data`,
        { headers: { 'X-CSRF-Token': opts.csrfToken } }
      );
      return await res.json();
    },
    async store(data) {
      await fetch(
        `${opts.apiBase}/${opts.pageId}/canvas-data`,
        {
          method: 'PATCH',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': opts.csrfToken,
          },
          body: JSON.stringify({
            grapesjs_html: editor.getHtml(),
            grapesjs_css: editor.getCss(),
            grapesjs_components: JSON.stringify(editor.getComponents()),
            grapesjs_styles: JSON.stringify(editor.getStyle()),
          }),
        }
      );
    },
  });
};
 
6. Integración Multi-Tenant y Design Tokens
La integración de GrapesJS con el sistema multi-tenant de 5 capas (Doc 100) es crítica para que cada tenant vea y edite su sitio con sus propios colores, tipografías y componentes.
6.1 Inyección de Design Tokens en el Canvas
El canvas de GrapesJS carga los CSS Custom Properties del tenant activo mediante la propiedad canvas.styles, que inyecta una URL dinámica generada por Drupal con todas las variables :root del tenant. Esto garantiza que el WYSIWYG muestra exactamente lo que verá el visitante final.
// CanvasEditorController.php
public function canvasEditor(string $page_id): array {
  $tenant = $this->groupContext->getCurrentGroup();
  $plan = $tenant->get('field_plan')->value;
  $tokens = $this->designTokens->getCascadedTokens($tenant);

  // Filtrar bloques según plan del tenant
  $blocks = $this->blockAdapter->getBlocksForPlan($plan);

  return [
    '#theme' => 'jaraba_canvas_editor',
    '#attached' => [
      'library' => ['jaraba_page_builder/canvas-editor'],
      'drupalSettings' => [
        'jarabaCanvas' => [
          'pageId' => $page_id,
          'blocks' => $blocks,
          'tenantPlan' => $plan,
          'tenantCssUrl' => '/api/v1/tenant/css/' . $tenant->id(),
          'designTokensUrl' => '/api/v1/tenant/tokens/' . $tenant->id(),
          'csrfToken' => $this->csrfGenerator->get('rest'),
        ],
      ],
    ],
  ];
}
6.2 Feature Flags por Plan
Feature	Starter	Professional	Enterprise
Editor visual (canvas)	✓ (básico)	✓ (completo)	✓ (completo)
Bloques base disponibles	15 bloques	35 bloques	45 (todos)
Bloques Aceternity/Magic UI	—	10 bloques	22 (todos)
Responsive breakpoints	Desktop only	Desktop + tablet	Desktop + tablet + móvil
Style Manager (CSS custom)	—	Básico	Completo
Auto-save	✔	✔	✔
Undo/Redo	✔ (10 pasos)	✔ (50 pasos)	✔ (ilimitado)
Exportar HTML/CSS	—	✔	✔
AI Content Assistant	—	✔ (básico)	✔ (completo)
Custom CSS injection	—	—	✔
 
7. UX Premium: Experiencia de Edición
7.1 Layout del Editor
El editor sigue el patrón probado de Elementor/Webflow con tres zonas principales:
•	Sidebar izquierda (320px): Block Manager con categorías colapsables, búsqueda en tiempo real, thumbnails de preview, e indicadores de plan (candado en bloques premium).
•	Canvas central (fluid): WYSIWYG con el contenido real del tenant, responsive breakpoint toggle, zoom control, y guías de alineamiento.
•	Panel derecho (280px, contextual): Aparece al seleccionar un bloque. Muestra: propiedades del componente (textos, imágenes, CTAs), Style Manager (espaciado, colores, tipografía), y Layer Manager (orden de capas).
7.2 Interacciones Clave
Interacción	Comportamiento	Feedback Visual
Drag bloque al canvas	Ghost semitransparente sigue cursor	Drop zone azul entre bloques existentes
Click en bloque	Selección con borde azul pulsante	Toolbar flotante: mover, duplicar, eliminar
Doble-click en texto	Edición inline directa	Cursor de texto, toolbar de formato
Hover sobre bloque	Outline sutil gris	Nombre del bloque en tooltip
Resize responsive	Canvas se adapta al breakpoint	Indicador de dispositivo activo
Ctrl+Z / Ctrl+Y	Undo/Redo inmediato	Toast notification del cambio
Ctrl+S	Guardado manual instantáneo	Indicador verde de guardado exitoso
Arrastrar entre bloques	Reordenamiento con animación	Placeholder azul en posición destino
7.3 Onboarding del Editor
Primera vez que un tenant accede al canvas editor:
•	Paso 1 - Selección de plantilla: Galería visual con templates pre-construidos filtrados por vertical (ya implementado en Doc 162). El usuario elige y la plantilla se carga en el canvas.
•	Paso 2 - Tour guiado: Overlay con 4 tooltips: sidebar de bloques, canvas central, panel de propiedades, barra de acciones (guardar/preview/publicar).
•	Paso 3 - Primera edición: Prompt para cambiar el título del hero con edición inline. Al completar, confetti animation + mensaje de felicitación.
 
8. Renderizado Público (Frontend Clean)
El frontend público NUNCA carga GrapesJS. El HTML/CSS generado por el editor se renderiza mediante un pipeline de procesamiento que garantiza output limpio, performante y SEO-ready.
8.1 Pipeline de Renderizado
// PageContentRenderService.php
public function render(PageContent $page): array {
  if ($page->get('editor_mode')->value === 'canvas') {
    // Modo Canvas: renderizar HTML de GrapesJS
    $html = $page->get('grapesjs_html')->value;
    $css  = $page->get('grapesjs_css')->value;

    // Sanitizar HTML (XSS prevention)
    $html = $this->htmlPurifier->purify($html);

    // Inyectar Design Tokens del tenant
    $tokens = $this->designTokens->getCascadedTokens($page->getTenant());

    return [
      '#theme' => 'page_content_canvas',
      '#html' => $html,
      '#css' => $css,
      '#tokens' => $tokens,
      '#cache' => [
        'tags' => ['page_content:' . $page->id()],
        'contexts' => ['url', 'tenant'],
      ],
    ];
  }

  // Modo Legacy: renderizar via Twig templates (fallback)
  return $this->legacyRenderer->render($page);
}
8.2 Template Público Zero Region
{# page-content-canvas.html.twig #}
{# Zero Region Policy: solo el contenido del canvas #}

<style>
  :root {
    {% for token, value in tokens %}
      --{{ token }}: {{ value }};
    {% endfor %}
  }
  {{ css|raw }}
</style>

<main class="jaraba-canvas-output" role="main">
  {{ html|raw }}
</main>
8.3 Optimizaciones de Rendimiento
•	Critical CSS inline: los estilos del canvas se inyectan en <style> inline para evitar request adicional y mejorar LCP
•	HTML sanitizado: HTMLPurifier elimina scripts, event handlers, y atributos peligrosos antes del renderizado
•	Cache por tenant: cada página se cachea con tags específicos del page_content y contexto de tenant
•	Lazy loading: imágenes generadas por el canvas incluyen loading="lazy" y dimensiones explícitas
•	Preconnect: el pre-renderizado añade preconnect para CDN de fuentes y assets del tenant
 
9. Roadmap de Implementación
Sprint	Semanas	Entregables	Horas	Deps
Sprint 1	S1-S2	Integración GrapesJS base. Canvas Controller. Storage REST. 15 bloques básicos registrados.	25-30h	Doc 162 Sprint 1
Sprint 2	S3-S4	Adaptador completo 67 bloques. Feature flags por plan. Design Tokens en canvas.	25-35h	Sprint 1 + Doc 100
Sprint 3	S5-S6	Bloques Aceternity/Magic UI con GrapesJS traits. Style Manager custom. Responsive.	20-25h	Sprint 2 + Doc 163
Sprint 4	S7-S8	Pipeline de renderizado público. Sanitización HTML. Cache multi-tenant. SEO.	15-20h	Sprint 3
Sprint 5	S9-S10	AI Content Assistant. Onboarding tour. Polish UX. Testing E2E. Documentación.	15-20h	Sprint 4 + Doc 128
9.1 Criterios de Aceptación Clave
•	Sprint 1 – Editor GrapesJS carga en /admin/page-builder/{id}/canvas. Drag-and-drop funcional con 15 bloques. Auto-save vía REST API. Undo/Redo funcional (10+ pasos).
•	Sprint 2 – Los 67 bloques aparecen en Block Manager categorizados. Bloques premium bloqueados según plan. Canvas muestra colores/fuentes del tenant activo.
•	Sprint 3 – Bloques Aceternity con efectos funcionales en canvas. Preview responsive en 3 breakpoints. Style Manager permite editar spacing, colores, y tipografía.
•	Sprint 4 – Páginas editadas con canvas renderizan correctamente en frontend público. Zero JavaScript de GrapesJS en output. LCP < 2.5s. HTML sanitizado sin XSS.
•	Sprint 5 – AI Assistant sugiere contenido para bloques vacíos. Tour de onboarding funcional. Tests E2E cubren flujo completo create → edit → publish.
 
10. Análisis Financiero
10.1 Inversión por Sprint
Componente	Horas	Costo (@€80/h)	Prioridad
Sprint 1: GrapesJS Core + Storage	25-30h	€2.000-2.400	P0
Sprint 2: 67 Bloques + Tokens	25-35h	€2.000-2.800	P0
Sprint 3: Premium + Style Manager	20-25h	€1.600-2.000	P1
Sprint 4: Renderizado Público	15-20h	€1.200-1.600	P0
Sprint 5: AI + Polish + Testing	15-20h	€1.200-1.600	P1
TOTAL	100-130h	€8.000-10.400	
10.2 Comparativa de Inversión
Enfoque	Horas	Costo	Resultado UX (1-10)
Custom (SortableJS + iframe)	300-400h	€24.000-32.000	6/10
GrapesJS Open Source (esta propuesta)	100-130h	€8.000-10.400	9/10
GrapesJS Studio SDK (Enterprise)	60-80h	€4.800-6.400 + licencia	9.5/10
Elementor White Label (WP)	N/A	€49/mes × tenants	8/10 (no nativo)
10.3 ROI Proyectado
•	Ahorro vs desarrollo custom: €16.000-22.000 en inversión inicial con resultado superior.
•	Ahorro vs licencias Elementor: €49/mes × 50 tenants = €29.400/año evitados en licencias externas.
•	Ahorro vs Webflow: €29/mes × 50 tenants = €17.400/año evitados.
•	Diferenciación: Constructor visual nativo integrado con las 5 verticales del ecosistema Jaraba.
•	Payback: 2-3 meses con 30+ tenants activos (considerando el ahorro en licencias de terceros).
•	Valor de plataforma: Un page builder visual nativo es el feature más valorado en encuestas de satisfacción SaaS, incrementando retención un 35-40%.
 
11. Dependencias Técnicas
11.1 Paquetes NPM
// package.json - Dependencias GrapesJS
{
  "dependencies": {
    "grapesjs": "^0.21.0",
    "grapesjs-preset-webpage": "^1.0.0",
    "grapesjs-blocks-basic": "^1.0.0",
    "grapesjs-plugin-forms": "^2.0.0",
    "grapesjs-style-bg": "^2.0.0",
    "grapesjs-tui-image-editor": "^1.0.0",
    "grapesjs-plugin-export": "^1.0.0"
  }
}
11.2 Dependencias de Documentos
Doc	Título	Relación
160	Page Builder SaaS v1	Entidades base, permisos, flujo de usuario
162	Page Builder Sistema Completo	67 bloques, templates, JSON schemas
163	Bloques Premium (Anexo)	Templates Twig Aceternity/Magic UI
100	Frontend Architecture Multi-Tenant	Design Tokens, cascada 5 capas
176	Site Structure Manager	Jerarquía páginas, URLs, sitemap
167	Analytics Page Builder	Tracking de bloques y conversiones
128	AI Content Hub	Asistente IA para generación de contenido
05	Core Theming jaraba_theme	SCSS, variables, componentes base
11.3 Riesgos y Mitigaciones
Riesgo	Probabilidad	Impacto	Mitigación
GrapesJS output HTML no compatible con Twig templates existentes	Media	Alto	Adaptador bidireccional: Twig → GrapesJS component y viceversa. Modo legacy como fallback.
Performance del editor con 67+ bloques	Baja	Medio	Lazy loading de bloques por categoría. Virtualización del Block Manager.
Conflicto CSS entre admin theme y canvas	Media	Bajo	Canvas GrapesJS usa iframe interno que aísla estilos del admin.
Actualizaciones de GrapesJS rompen integración	Baja	Medio	Pin de versión en package.json. Lockfile. Tests E2E en CI/CD.
Complejidad de bloques premium (Aceternity) en canvas	Alta	Medio	Registrar como componentes GrapesJS con traits específicos. Preview simplificado en editor.


─── Fin del Documento ───

Este documento contiene toda la información técnica necesaria para que el equipo EDI Google Antigravity
implemente el Jaraba Canvas Visual Page Builder del Ecosistema Jaraba.

Versión 1.0 | 3 de Febrero de 2026 | CONFIDENCIAL
Plataforma de Ecosistemas Digitales S.L. © 2026
