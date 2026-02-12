



178
ESPECIFICACIÓN TÉCNICA V2

Jaraba Canvas
Full Page Visual Editor
Header + Navegación + Contenido + Footer — Editable en Canvas

GrapesJS + Drupal 11 | Canvas Página Completa | Parciales Editables | Multi-Tenant

Versión:	2.0.0 (Full Page Canvas)
Fecha:	3 de Febrero de 2026
Estado:	Especificación Técnica Definitiva
Horas Estimadas:	120-155 horas
Inversión:	€9.600-12.400 (@€80/h)
Dependencias:	Docs 100, 160, 162, 163, 176, 177
Sustituye:	178_v1 (Canvas solo cuerpo)


Ecosistema Jaraba | EDI Google Antigravity
Plataforma de Ecosistemas Digitales S.L. © 2026
 
1. Resumen Ejecutivo
Este documento define la arquitectura del Jaraba Canvas v2: un editor visual de página completa que renderiza y permite editar en un único canvas todos los parciales que componen la experiencia del visitante final: top bar, header con navegación, cuerpo de contenido con bloques arrastrables, y footer multicolumna.
La diferencia crítica respecto a la v1 es que el canvas ya no es solo un editor de "cuerpo de página". El tenant ve y edita exactamente lo que verá su visitante: su header con el logo y menú configurado, su variante de encabezado (classic, transparent, centered, mega, minimal), el contenido drag-and-drop, y su footer con las columnas y enlaces configurados. Todo respetando los Design Tokens activos del tenant.
1.1 Concepto: Full Page Canvas
┌───────────────────────────────────────────────────────────────┐
│          JARABA CANVAS v2 — FULL PAGE EDITOR                 │
├───────────────────────────────────────────────────────────────┤
│ SIDEBAR  │  CANVAS (PÁGINA COMPLETA como la ve el visitante) │
│ (320px)  │                                                   │
│          │  ┌───────────────────────────────────────────┐  │
│ Bloques  │  │ ┌─────────────────────────────────────────┐ │  │
│ ─────── │  │ │ TOP BAR  [Oferta: -20% este mes]   └─┘ │  │
│ Parciales│  │ └─────────────────────────────────────────┘ │  │
│ ─────── │  │ ┌─────────────────────────────────────────┐ │  │
│ Header   │  │ │ HEADER █ Logo  [Menú1] [Menú2] [CTA] │ │  │
│ Footer   │  │ └─────────────────────────────────────────┘ │  │
│ Top Bar  │  │                                           │  │
│ ─────── │  │ ┌─────────────────────────────────────────┐ │  │
│ Contenido│  │ │ HERO SECTION (drag-and-drop)        │ │  │
│ ─────── │  │ └─────────────────────────────────────────┘ │  │
│ Hero     │  │ ─── DROP ZONE (barra azul) ──────────────── │  │
│ Features │  │ ┌─────────────────────────────────────────┐ │  │
│ Stats    │  │ │ FEATURES GRID (drag-and-drop)       │ │  │
│ CTA      │  │ └─────────────────────────────────────────┘ │  │
│ …        │  │                                           │  │
│          │  │ ┌─────────────────────────────────────────┐ │  │
│          │  │ │ FOOTER [Col1] [Col2] [Col3] [Social] │ │  │
│          │  │ │ © 2026 Mi Empresa | Aviso Legal      │ │  │
│          │  │ └─────────────────────────────────────────┘ │  │
│          │  └───────────────────────────────────────────┘  │
├──────────┴────────────────────────────────────────────────────┤
│ [💾 Guardar] [👁 Preview] [🚀 Publicar] [↩ Undo] [📱💻 Responsive]  │
└───────────────────────────────────────────────────────────────┘
1.2 Diferencia v1 vs v2
Aspecto	v1 (Canvas Body)	v2 (Full Page Canvas)
¿Qué ve el tenant en el canvas?	Solo bloques de contenido	Página completa: header + menú + body + footer
¿Header editable en canvas?	No (se configura en panel separado)	Sí, click en header abre panel contextual
¿Menú de navegación visible?	No	Sí, con items reales del tenant
¿Footer editable en canvas?	No	Sí, columnas y enlaces editables
¿Top Bar visible?	No	Sí, si está activada
Contexto visual para el tenant	Parcial — solo el body	Total — exactamente lo que ve el visitante
Dependencia Doc 177	Ninguna	Integra Header Builder + Footer Builder
Complejidad adicional	—	+20-25h (parciales como componentes GrapesJS)
 
2. Arquitectura de Zonas del Canvas
El canvas se divide en tres tipos de zonas con comportamientos de edición diferenciados. Esto es fundamental: el header y footer NO son bloques arrastrables, son parciales estructurales que se editan in-situ con paneles contextuales.
2.1 Tipos de Zona
Zona	Tipo	Comportamiento en Canvas	Cómo se Edita
Top Bar	Parcial fijo	Visible si está activada. Posición fija arriba.	Click → panel lateral: texto, colores, toggle on/off
Header + Nav	Parcial fijo	Renderiza la variante activa con logo y menú real del tenant.	Click → panel lateral: cambiar variante, editar menú, logo, CTA, sticky, colores
Content Body	Zona drag-and-drop	Zona libre donde se arrastran bloques. Zona principal de edición.	Drag-and-drop desde sidebar. Click en bloque → editar propiedades. Reordenar arrastrando.
Footer	Parcial fijo	Renderiza la variante activa con columnas, menús y social links.	Click → panel lateral: cambiar variante, editar columnas, menús, newsletter, copyright
2.2 Principio: Parciales NO Son Bloques
Es crítico entender la diferencia arquitectónica:
•	Bloques de contenido (67 bloques del Doc 162): se arrastran al body, se reordenan, se duplican, se eliminan. Son instancias de block_template dentro de page_content. Cada página tiene su propia composición de bloques.
•	Parciales estructurales (header, footer, top bar): son compartidos entre TODAS las páginas del tenant. Se configuran una vez y se heredan globalmente. Se gestionan via las entidades site_header_config, site_footer_config y site_menu del Doc 177.
En GrapesJS esto se implementa registrando header y footer como componentes NO arrastrables (draggable: false, droppable: false, removable: false) que se renderizan en posiciones fijas del canvas y que al hacer click abren un panel de configuración contextual en lugar del Style Manager estándar.
2.3 Mapa de Datos por Zona
Zona	Entidad Fuente (Doc 177)	Campos Clave	Alcance
Top Bar	site_header_config	topbar_enabled, topbar_text, topbar_bg_color, topbar_text_color	Global tenant
Header	site_header_config	header_type (standard|centered|minimal|mega|transparent), logo_id, sticky, height, bg_color, cta_text, cta_url	Global tenant
Navegación	site_menu + site_menu_item	machine_name, items[].title, items[].url, items[].icon, items[].children[]	Global tenant
Content Body	page_content (Doc 162)	grapesjs_html, grapesjs_css, grapesjs_components (por página)	Por página
Footer	site_footer_config	footer_type (simple|columns|mega|minimal|cta), columns_config, show_social, show_newsletter, copyright	Global tenant
 
3. Componentes GrapesJS para Parciales
Cada parcial se registra en GrapesJS como un componente custom con restricciones específicas que impiden que sea arrastrado, eliminado o movido de posición, pero que permite edición contextual.
3.1 Componente: jaraba-header
// grapesjs-jaraba-partials.js
export default (editor, opts) => {
  const { headerConfig, menuItems, footerConfig } = opts;

  // ── HEADER COMPONENT ──
  editor.Components.addType('jaraba-header', {
    model: {
      defaults: {
        tagName: 'header',
        draggable: false,    // NO se puede arrastrar
        droppable: false,    // NO se pueden soltar bloques dentro
        removable: false,    // NO se puede eliminar
        copyable: false,     // NO se puede duplicar
        movable: false,      // NO se puede mover de posición
        selectable: true,    // SÍ se puede seleccionar (para editar)
        hoverable: true,     // SÍ muestra overlay al hacer hover

        // Traits = propiedades editables en panel lateral
        traits: [
          {
            type: 'select',
            label: 'Tipo de Header',
            name: 'header-type',
            options: [
              { value: 'standard', name: 'Estándar (logo izq + menú der)' },
              { value: 'centered', name: 'Centrado (logo centro)' },
              { value: 'minimal', name: 'Mínimo (solo hamburger)' },
              { value: 'mega', name: 'Mega Menú (desplegables)' },
              { value: 'transparent', name: 'Transparente (sobre hero)' },
            ],
          },
          { type: 'checkbox', label: 'Sticky al scroll', name: 'sticky' },
          { type: 'text', label: 'Texto del botón CTA', name: 'cta-text' },
          { type: 'text', label: 'URL del CTA', name: 'cta-url' },
          { type: 'checkbox', label: 'Mostrar buscador', name: 'show-search' },
          { type: 'checkbox', label: 'Mostrar Top Bar', name: 'topbar-enabled' },
          { type: 'text', label: 'Texto Top Bar', name: 'topbar-text' },
        ],
      },

      // Al cambiar un trait, re-renderizar el header via API
      init() {
        this.on('change:attributes', this.handleAttrChange);
      },
      handleAttrChange() {
        const type = this.getAttributes()['header-type'];
        // Fetch el HTML actualizado del header desde Drupal
        fetch(`/api/v1/site/header/preview?type=${type}`)
          .then(r => r.text())
          .then(html => {
            this.components(html);  // Re-renderizar en canvas
          });
      },
    },
  });
3.2 Componente: jaraba-footer
  // ── FOOTER COMPONENT ──
  editor.Components.addType('jaraba-footer', {
    model: {
      defaults: {
        tagName: 'footer',
        draggable: false,
        droppable: false,
        removable: false,
        copyable: false,
        movable: false,
        selectable: true,
        hoverable: true,

        traits: [
          {
            type: 'select',
            label: 'Tipo de Footer',
            name: 'footer-type',
            options: [
              { value: 'simple', name: 'Simple (logo + copyright)' },
              { value: 'columns', name: 'Columnas (3-4 menús)' },
              { value: 'mega', name: 'Mega (cols + newsletter)' },
              { value: 'minimal', name: 'Mínimo (solo legal)' },
              { value: 'cta', name: 'Con CTA destacado' },
            ],
          },
          { type: 'checkbox', label: 'Mostrar redes sociales', name: 'show-social' },
          { type: 'checkbox', label: 'Mostrar newsletter', name: 'show-newsletter' },
          { type: 'text', label: 'Texto copyright', name: 'copyright' },
        ],
      },
      init() {
        this.on('change:attributes', this.handleAttrChange);
      },
      handleAttrChange() {
        const type = this.getAttributes()['footer-type'];
        fetch(`/api/v1/site/footer/preview?type=${type}`)
          .then(r => r.text())
          .then(html => this.components(html));
      },
    },
  });
3.3 Componente: jaraba-content-zone (Zona de Bloques)
  // ── CONTENT ZONE (drag-and-drop de bloques) ──
  editor.Components.addType('jaraba-content-zone', {
    model: {
      defaults: {
        tagName: 'main',
        draggable: false,    // La zona en sí no se mueve
        droppable: true,     // SÍ acepta bloques soltados
        removable: false,    // No se puede eliminar la zona
        attributes: {
          'data-gjs-type': 'jaraba-content-zone',
          'class': 'jaraba-canvas-content',
          'role': 'main',
        },
      },
    },
  });
};
 
4. Inicialización del Canvas Completo
Al cargar el editor, el Controller de Drupal pre-renderiza el HTML de los parciales del tenant y los inyecta como estructura base del canvas. El resultado es que GrapesJS se inicializa con la página completa ya montada.
4.1 Controller PHP: Carga Completa
// CanvasEditorController.php
public function canvasEditor(string $page_id): array {
  $tenant = $this->groupContext->getCurrentGroup();
  $plan = $tenant->get('field_plan')->value;

  // 1. Cargar configuración de parciales (Doc 177)
  $headerConfig = $this->headerService->getConfig($tenant->id());
  $footerConfig = $this->footerService->getConfig($tenant->id());
  $menuItems = $this->menuService->getMenuTree(
    $headerConfig['main_menu_id']
  );

  // 2. Pre-renderizar parciales con Twig
  $headerHtml = $this->twigRenderer->render(
    '@jaraba_site_builder/header--' . $headerConfig['header_type'] . '.html.twig',
    ['config' => $headerConfig, 'menu' => $menuItems]
  );
  $footerHtml = $this->twigRenderer->render(
    '@jaraba_site_builder/footer--' . $footerConfig['footer_type'] . '.html.twig',
    ['config' => $footerConfig]
  );

  // 3. Cargar contenido del body (page_content)
  $page = $this->pageContentStorage->load($page_id);
  $bodyComponents = $page->get('grapesjs_components')->value;

  // 4. Cargar bloques filtrados por plan
  $blocks = $this->blockAdapter->getBlocksForPlan($plan);
  $tokens = $this->designTokens->getCascadedTokens($tenant);

  return [
    '#theme' => 'jaraba_canvas_editor',
    '#attached' => [
      'library' => ['jaraba_page_builder/canvas-editor'],
      'drupalSettings' => [
        'jarabaCanvas' => [
          'pageId' => $page_id,
          'tenantPlan' => $plan,
          'blocks' => $blocks,
          'headerHtml' => $headerHtml,
          'headerConfig' => $headerConfig,
          'footerHtml' => $footerHtml,
          'footerConfig' => $footerConfig,
          'menuItems' => $menuItems,
          'bodyComponents' => $bodyComponents,
          'tenantCssUrl' => '/api/v1/tenant/css/' . $tenant->id(),
          'designTokensUrl' => '/api/v1/tenant/tokens/' . $tenant->id(),
          'csrfToken' => $this->csrfGenerator->get('rest'),
        ],
      ],
    ],
  ];
}
4.2 Inicialización GrapesJS con Página Completa
// grapesjs-init.js - Full Page Canvas
const settings = drupalSettings.jarabaCanvas;

const editor = grapesjs.init({
  container: '#jaraba-canvas-editor',
  height: '100vh',
  fromElement: false,

  // ESTRUCTURA BASE: página completa pre-renderizada
  components: [
    {
      type: 'jaraba-header',
      content: settings.headerHtml,
      attributes: {
        'header-type': settings.headerConfig.header_type,
        'sticky': settings.headerConfig.sticky,
        'cta-text': settings.headerConfig.cta_text,
        'topbar-enabled': settings.headerConfig.topbar_enabled,
      },
    },
    {
      type: 'jaraba-content-zone',
      components: settings.bodyComponents
        ? JSON.parse(settings.bodyComponents)
        : [],  // Página nueva: zona vacía lista para bloques
    },
    {
      type: 'jaraba-footer',
      content: settings.footerHtml,
      attributes: {
        'footer-type': settings.footerConfig.footer_type,
        'show-social': settings.footerConfig.show_social,
        'copyright': settings.footerConfig.copyright,
      },
    },
  ],

  // Canvas carga CSS del tenant para WYSIWYG fiel
  canvas: {
    styles: [
      settings.tenantCssUrl,
      settings.designTokensUrl,
    ],
  },

  // ... resto de config (storage, devices, plugins)
});
 
5. UX de Edición por Zona
5.1 Edición del Header
Cuando el tenant hace click en el header dentro del canvas:
•	1. Selección visual: el header se resalta con borde naranja pulsante y un badge "⚙ Configurar Encabezado" aparece arriba-derecha.
•	2. Panel contextual: el panel derecho cambia a "Configuración del Encabezado" con las opciones del Doc 177 (Header Builder):
•	  Selector visual de variante con miniaturas (standard, centered, minimal, mega, transparent)
•	  Upload/cambio de logo (con preview instantáneo en canvas)
•	  Toggle Sticky al scroll
•	  Botón CTA: texto, URL, estilo (primary/secondary/outline)
•	  Mostrar/ocultar buscador, idioma, teléfono
•	  Top Bar: toggle on/off, texto promocional, colores
•	  Colores: fondo, texto, altura desktop/mobile
•	3. Preview instantáneo: al cambiar cualquier opción, el header se re-renderiza en el canvas vía fetch al endpoint /api/v1/site/header/preview que devuelve el HTML Twig renderizado con la nueva configuración.
•	4. Persistencia: los cambios del header se guardan en site_header_config (Doc 177), NO en page_content. Son globales para todo el tenant.
5.2 Edición del Menú de Navegación
El menú es un sub-componente dentro del header. Cuando el tenant hace click específicamente en la zona de navegación:
•	Panel de Menú: se abre un panel especializado con la lista de items del menú principal. Cada item muestra: título editable, URL/página destino, icono, y sub-items si tiene.
•	Añadir item: botón "+" al final de la lista. Abre formulario inline: título, URL (con autocomplete de páginas del tenant), tipo (link, dropdown, mega_column).
•	Reordenar: drag-and-drop dentro de la lista del panel (SortableJS ya integrado en el Menu Builder del Doc 177).
•	Submenús: arrastrar un item debajo de otro lo convierte en hijo (indentación visual). Soporta hasta 3 niveles.
•	Preview en vivo: cada cambio en el menú re-renderiza el header en el canvas con los items actualizados.
5.3 Edición del Footer
Cuando el tenant hace click en el footer:
•	Panel de Footer: muestra la configuración del Footer Builder (Doc 177):
•	  Selector visual de variante (simple, columns, mega, minimal, cta)
•	  Configuración de columnas (1-5): cada columna con título + menú de enlaces
•	  Toggle newsletter: título, placeholder, estilo del botón
•	  Social links: activar/desactivar, selección de redes (Facebook, Instagram, LinkedIn...)
•	  Texto de copyright editable
•	  Colores: fondo, texto, acentos
•	Persistencia: los cambios se guardan en site_footer_config (Doc 177). Son globales para todo el tenant.
5.4 Edición del Content Body (Bloques)
El content body funciona exactamente como se especificó en la v1: zona libre de drag-and-drop con los 67 bloques arrastrables desde el sidebar. La única diferencia es que ahora el tenant ve el contexto completo (header arriba, footer abajo) mientras edita, lo que elimina la disonancia entre edición y resultado final.
 
6. Sidebar: Categorías Reorganizadas
Con el Full Page Canvas, el sidebar se reorganiza para distinguir claramente entre parciales globales y bloques de contenido:
6.1 Estructura del Sidebar
Sección	Contenido	Comportamiento
🏠 Parciales del Sitio	Accesos directos para editar: Header, Menú, Footer, Top Bar	Click → selecciona el parcial en canvas + abre su panel. NO son arrastrables.
🎨 Bloques de Contenido	67 bloques organizados por categoría: Hero, Features, CTA, Stats, Pricing, Media...	Arrastrables al content body. Drop zones entre bloques existentes.
📄 Plantillas de Página	55 plantillas pre-construidas con bloques pre-configurados	Click → reemplaza los bloques del body (con confirmación). Header/Footer NO se tocan.
⚙ Configuración	Metadatos de la página: título, URL, SEO, og:tags	Formulario con auto-save
6.2 Sección Parciales: Accesos Directos
Los accesos directos de parciales son tarjetas NO arrastrables que al hacer click seleccionan el parcial correspondiente en el canvas y abren su panel de configuración:
┌──────────────────────────────┐
│  🏠 PARCIALES DEL SITIO      │
├──────────────────────────────┤
│  ┌──────────────────────────┐  │
│  │ ▔▔ Top Bar           → │  │
│  │    ○ Activada            │  │
│  └──────────────────────────┘  │
│  ┌──────────────────────────┐  │
│  │ ██ Encabezado        → │  │
│  │    standard (5 opciones) │  │
│  └──────────────────────────┘  │
│  ┌──────────────────────────┐  │
│  │ ☰ Navegación        → │  │
│  │    4 items + 2 subitems │  │
│  └──────────────────────────┘  │
│  ┌──────────────────────────┐  │
│  │ ▄▄ Pie de Página    → │  │
│  │    columns (5 tipos)    │  │
│  └──────────────────────────┘  │
└──────────────────────────────┘
 
7. Persistencia Dual: Parciales vs Contenido
El Full Page Canvas requiere un sistema de persistencia dual, porque los parciales y el contenido del body viven en entidades diferentes con alcances diferentes.
7.1 Flujo de Guardado
Acción del Tenant	Qué se Guarda	Dónde	Alcance	Endpoint
Cambia variante de header	header_type, sticky, cta_text...	site_header_config	Global (todas las págs)	PUT /api/v1/site/header
Edita items del menú	title, url, weight, children	site_menu + site_menu_item	Global	PUT /api/v1/site/menu/{id}
Cambia variante de footer	footer_type, columns_config...	site_footer_config	Global	PUT /api/v1/site/footer
Arrastra/edita bloque en body	grapesjs_html, grapesjs_css, components	page_content	Solo esta página	PATCH /api/v1/pages/{id}/canvas
Edita metadatos (título, URL)	title, path_alias, seo_data	page_content	Solo esta página	PATCH /api/v1/pages/{id}
7.2 Aviso de Cambios Globales
Cuando el tenant modifica un parcial (header, menú, footer), el editor muestra un toast informativo:
┌─────────────────────────────────────────────────┐
│ ⚠  Los cambios en el encabezado se aplicarán    │
│    a TODAS las páginas de tu sitio.             │
│    [Entendido]  [Deshacer]                       │
└─────────────────────────────────────────────────┘
Esto educa al tenant sobre la diferencia entre contenido de página (local) y estructura del sitio (global), sin requerir que entienda la arquitectura subyacente.
7.3 Storage Plugin Actualizado
// grapesjs-jaraba-storage.js (v2 con persistencia dual)
export default (editor, opts) => {
  editor.StorageManager.add('jaraba-rest', {
    async store(data) {
      // 1. Extraer SOLO componentes del content-zone
      const contentZone = editor.getWrapper()
        .find('[data-gjs-type=jaraba-content-zone]')[0];
      
      // 2. Guardar body (por página)
      await fetch(`${opts.apiBase}/${opts.pageId}/canvas-data`, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': opts.csrfToken,
        },
        body: JSON.stringify({
          grapesjs_html: contentZone.getInnerHTML(),
          grapesjs_css: editor.getCss({ component: contentZone }),
          grapesjs_components: JSON.stringify(
            contentZone.get('components').toJSON()
          ),
        }),
      });

      // NOTA: Los parciales (header, footer, menú) se guardan
      // instantáneamente al cambiar, via su propio endpoint
      // (no en el store general del editor)
    },
  });
};
 
8. Renderizado Público (Frontend Zero Region)
El frontend público ensambla los parciales y el contenido del body en una única página limpia, sin regiones ni bloques de Drupal:
8.1 Template de Renderizado
{# page--frontend--canvas.html.twig #}
{# Zero Region Policy: ensamblaje completo sin regiones Drupal #}
<!DOCTYPE html>
<html lang="{{ language }}" data-tenant="{{ tenant.id }}">
<head>
  <style>
    :root {
      {% for token, value in design_tokens %}
        --{{ token }}: {{ value }};
      {% endfor %}
    }
    {{ canvas_css|raw }}
  </style>
</head>
<body class="jaraba-frontend jaraba-{{ vertical }}">

  {# ── TOP BAR (si está activada) ── #}
  {% if header_config.topbar_enabled %}
    {% include '@jaraba_site_builder/partials/topbar.html.twig'
       with { config: header_config } %}
  {% endif %}

  {# ── HEADER (variante del tenant) ── #}
  {% include '@jaraba_site_builder/header--'
     ~ header_config.header_type ~ '.html.twig'
     with { config: header_config, menu: menu_items } %}

  {# ── CONTENT BODY (bloques del canvas) ── #}
  <main class="jaraba-canvas-output" role="main">
    {{ canvas_html|raw }}
  </main>

  {# ── FOOTER (variante del tenant) ── #}
  {% include '@jaraba_site_builder/footer--'
     ~ footer_config.footer_type ~ '.html.twig'
     with { config: footer_config } %}

</body>
</html>
8.2 Garantías del Frontend Limpio
Garantía	Implementación	Verificación
CERO regiones Drupal	page--frontend--canvas.html.twig no hereda page.html.twig estándar	Inspección DOM: sin div.region-*
CERO bloques Drupal	No se usa block.module ni block_content en frontend	Inspección DOM: sin div.block-*
CERO JavaScript de GrapesJS	grapesjs.min.js solo se carga en /admin/*	Network tab: sin grapesjs en frontend
Header/Footer via Twig puro	Parciales renderizados server-side con datos de BD	View source: HTML estático, sin JS dinámico
CSS scoped al canvas	Estilos del canvas en <style> inline + Design Tokens	Sin conflictos con CSS externo
SEO compliant	HTML semántico: header, main, footer, nav	Lighthouse audit > 90
 
9. Roadmap de Implementación
Sprint	Sem	Entregables	Horas	Deps
Sprint 1	S1-S2	GrapesJS core. Storage REST. 15 bloques básicos. Canvas con estructura header+body+footer (HTML estático inicial).	25-30h	Doc 162 Spr1
Sprint 2	S3-S4	Adaptador 67 bloques. Feature flags por plan. Design Tokens en canvas. Parciales del tenant pre-renderizados.	25-35h	Spr1 + Doc 100
Sprint 3	S5-S6	Header como componente GrapesJS editable. Traits: variante, logo, CTA, sticky. Preview via API. Edición de menú en panel contextual.	25-30h	Spr2 + Doc 177
Sprint 4	S7-S8	Footer como componente editable. Traits: variante, columnas, newsletter, social. Bloques premium Aceternity/Magic UI.	20-25h	Spr3 + Doc 163
Sprint 5	S9-S10	Pipeline renderizado público (Zero Region). Template page--frontend--canvas. Sanitización HTML. Cache multi-tenant.	15-20h	Spr4
Sprint 6	S11-S12	AI Content Assistant. Onboarding tour. Polish UX. Testing E2E. Documentación usuario.	15-20h	Spr5 + Doc 128
9.1 Criterios de Aceptación Clave
•	Sprint 1: El canvas carga con estructura header+body+footer visible. Drag-and-drop de 15 bloques funcional en la zona body. Auto-save REST funcional.
•	Sprint 2: 67 bloques categorizados en sidebar. Bloques premium bloqueados según plan. Canvas muestra colores y tipografía del tenant activo.
•	Sprint 3: Click en header abre panel con selector de variante. Al cambiar variante, el header se re-renderiza en canvas con el HTML real. Edición de menú drag-and-drop funcional.
•	Sprint 4: Click en footer abre panel con sus opciones. Bloques Aceternity UI con efectos visibles en canvas. Responsive preview en 3 breakpoints.
•	Sprint 5: Página pública renderiza header + body + footer sin JavaScript de GrapesJS. Zero regiones Drupal. LCP < 2.5s. HTML sanitizado.
•	Sprint 6: AI sugiere contenido para bloques vacíos. Tour de onboarding funcional. Tests E2E cubren flujo create → edit header → add blocks → publish.
 
10. Análisis Financiero
10.1 Inversión por Sprint
Componente	Horas	Costo (@€80/h)	Prio
Sprint 1: GrapesJS Core + Storage + Estructura	25-30h	€2.000-2.400	P0
Sprint 2: 67 Bloques + Tokens + Parciales	25-35h	€2.000-2.800	P0
Sprint 3: Header Editable + Menú	25-30h	€2.000-2.400	P0
Sprint 4: Footer Editable + Premium Blocks	20-25h	€1.600-2.000	P1
Sprint 5: Renderizado Público Zero Region	15-20h	€1.200-1.600	P0
Sprint 6: AI + Polish + Testing E2E	15-20h	€1.200-1.600	P1
TOTAL	120-155h	€9.600-12.400	
10.2 Incremento vs v1
Concepto	v1 (Body Only)	v2 (Full Page)	Diferencia
Horas totales	100-130h	120-155h	+20-25h
Costo total	€8.000-10.400	€9.600-12.400	+€1.600-2.000
Componentes GrapesJS parciales	0 (no existían)	3 (header, footer, content-zone)	+3 componentes
Endpoints API adicionales	1 (canvas-data)	4 (+header/preview, +footer/preview, +menu)	+3 endpoints
Satisfacción UX estimada	7/10 (falta contexto)	9.5/10 (página completa)	+2.5 puntos

El incremento de +20-25h (solo un 20% más) produce un salto cualitativo enorme en la experiencia del tenant. La diferencia entre ver "solo bloques" y ver "tu página completa tal como la verán tus clientes" es la diferencia entre un editor funcional y un editor de clase mundial.
10.3 ROI
•	Ahorro vs licencias Elementor: €49/mes × 50 tenants = €29.400/año evitados.
•	Ahorro vs Webflow: €29/mes × 50 tenants = €17.400/año.
•	Valor diferencial: Ningún competidor SaaS ofrece editor visual nativo con header/footer/menú editables + 5 verticales + Design Tokens por tenant.
•	Payback: 2-3 meses con 30+ tenants activos.
 
11. Dependencias Técnicas y Riesgos
11.1 Dependencias de Documentos
Doc	Título	Relación con v2
177	Global Navigation System	CRÍTICA: Header Builder, Menu Builder, Footer Builder. Sin este doc no hay parciales editables.
100	Frontend Architecture Multi-Tenant	Design Tokens en cascada. Variantes de componentes. Visual Picker.
162	Page Builder Sistema Completo	67 bloques, templates, JSON schemas. Base de todo el sistema.
163	Bloques Premium (Anexo)	Templates Twig Aceternity/Magic UI.
176	Site Structure Manager	Jerarquía de páginas, URLs, sitemap.
05	Core Theming jaraba_theme	SCSS, variables CSS, estructura del tema.
128	AI Content Hub	Asistente IA para generación de contenido en bloques.
11.2 Riesgos Específicos de v2
Riesgo	Prob.	Impacto	Mitigación
Latencia al re-renderizar header/footer via API al cambiar variante	Media	Medio	Pre-cachear las 5 variantes de header y 5 de footer en el Controller al cargar. Servir desde cache, no re-renderizar cada vez.
Conflicto CSS entre estilos del header/footer y bloques del canvas	Baja	Bajo	Scoping CSS: parciales usan clases .jaraba-header-* y .jaraba-footer-*. Bloques del canvas usan .jaraba-block-*.
Tenant confunde cambios globales (header) con locales (bloques)	Alta	Medio	Toast informativo claro al editar parciales. Badge visual en panel indicando "Cambio global en todas las páginas".
Complejidad del menú editor dentro de GrapesJS traits	Media	Alto	No usar traits para el menú. Abrir modal dedicado con el Menu Builder ya especificado en Doc 177.
Undo/redo cruzado entre cambios de parciales y bloques	Media	Alto	Stacks de undo/redo separados: uno para parciales (global) y otro para bloques (local). El undo del editor solo afecta bloques.


─── Fin del Documento ───

Jaraba Canvas v2 — Full Page Visual Editor
Header + Navegación + Contenido + Footer: todo editable en un único canvas.

Versión 2.0 | 3 de Febrero de 2026 | CONFIDENCIAL
Plataforma de Ecosistemas Digitales S.L. © 2026
