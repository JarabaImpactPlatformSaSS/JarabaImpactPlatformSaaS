
AI CONTENT HUB
Editor Dashboard, AI Assistant UI,
Newsletter Builder, Analytics & Tests
Documento Complementario Final (128c)

JARABA IMPACT PLATFORM

Campo	Valor	Notas
Versión:	1.0	Cierra gaps identificados
Fecha:	Enero 2026	
Estado:	Ready for Development	Sin Humo
Código:	128c_Platform_AI_Content_Hub_Editor	
Dependencias:	128_v2 (Backend), 128b (Frontend)	
 
Índice de Contenidos
1. Editor Dashboard - Interfaz de Creación de Contenido
2. AI Writing Assistant UI - Componentes de Generación IA
3. Newsletter Campaign Builder - Constructor de Campañas
4. Email Templates (MJML) - Diseños de Newsletter
5. Analytics Dashboard - Métricas y KPIs
6. Test Specification - Unit, Integration, E2E
7. OpenAPI Specification - Documentación API
8. Roadmap de Implementación Adicional
 
1. Editor Dashboard
Interfaz donde los autores crean, editan y gestionan contenido con asistencia de IA integrada.
1.1 Layout del Editor
┌─────────────────────────────────────────────────────────────────────┐
│  HEADER: Logo | Dashboard | Artículos | Newsletter | [User Menu]   │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │ TOOLBAR: [Guardar] [Preview] [Programar] [Publicar] [···]   │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                     │
│  ┌────────────┬────────────────────────────────┬───────────────┐   │
│  │            │                                │               │   │
│  │   AI       │      EDITOR PRINCIPAL          │   METADATA    │   │
│  │   PANEL    │                                │   PANEL       │   │
│  │            │  ┌─────────────────────────┐   │               │   │
│  │  [Outline] │  │ Título del artículo    │   │  Categoría ▼  │   │
│  │  [Generar] │  └─────────────────────────┘   │  Tags [+]     │   │
│  │  [Expandir]│                                │  Autor ▼      │   │
│  │  [Titular] │  ┌─────────────────────────┐   │               │   │
│  │  [Resumen] │  │ Featured Image         │   │  ──────────── │   │
│  │  [SEO]     │  │ [Upload / Generate]    │   │  SEO Score    │   │
│  │  [Traducir]│  └─────────────────────────┘   │  ████████░░   │   │
│  │            │                                │  72/100       │   │
│  │  ────────  │  ┌─────────────────────────┐   │               │   │
│  │            │  │                         │   │  ──────────── │   │
│  │  AI Chat   │  │   WYSIWYG EDITOR        │   │  Publish      │   │
│  │  ────────  │  │   (CKEditor 5)          │   │  ○ Borrador   │   │
│  │  [Ask AI]  │  │                         │   │  ○ Revisión   │   │
│  │            │  │   Lorem ipsum dolor...  │   │  ○ Programado │   │
│  │            │  │                         │   │  ● Publicado  │   │
│  │            │  │                         │   │               │   │
│  │            │  └─────────────────────────┘   │  [Fecha] 📅   │   │
│  │            │                                │               │   │
│  └────────────┴────────────────────────────────┴───────────────┘   │
│                                                                     │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │ STATUS BAR: Guardado hace 2 min | 1,250 palabras | 6 min   │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
1.2 Panel AI Assistant (Sidebar Izquierdo)
Panel lateral con todas las herramientas de generación IA.
1.2.1 Acciones Disponibles
Acción	Icono	Input Requerido	Output
Generar Outline	📋	Topic + Keywords	Estructura H2/H3 sugerida
Generar Artículo	✨	Outline aprobado o Topic	Artículo completo
Expandir Sección	📝	Selección de texto o H2	Párrafos expandidos
Generar Titulares	💡	Topic o contenido actual	5 variantes de título
Crear Resumen	📄	Contenido del artículo	Excerpt + Answer Capsule
Optimizar SEO	🔍	Contenido actual	Sugerencias + score
Traducir	🌐	Contenido + idioma destino	Contenido traducido
Reescribir	🔄	Selección de texto	Texto mejorado
1.2.2 AI Chat Interface
Interfaz conversacional para consultas específicas al asistente.
•	Input: Campo de texto con placeholder 'Pregunta al asistente...'
•	Historial: Últimas 10 interacciones de la sesión
•	Contexto: Siempre incluye el contenido actual del artículo
•	Ejemplos de queries: '¿Cómo puedo mejorar la introducción?', 'Dame 3 ejemplos para esta sección'
 
1.3 Editor Principal (CKEditor 5)
Editor WYSIWYG con plugins custom para integración IA.
1.3.1 Toolbar Configuration
// ckeditor-config.js
export const editorConfig = {
  toolbar: {
    items: [
      "heading", "|",
      "bold", "italic", "underline", "strikethrough", "|",
      "link", "uploadImage", "blockQuote", "codeBlock", "|",
      "bulletedList", "numberedList", "todoList", "|",
      "outdent", "indent", "|",
      "insertTable", "mediaEmbed", "|",
      "aiAssistant", "|",  // Custom plugin
      "undo", "redo", "|",
      "findAndReplace", "selectAll"
    ]
  },
  heading: {
    options: [
      { model: "paragraph", title: "Paragraph", class: "ck-heading_paragraph" },
      { model: "heading2", view: "h2", title: "Heading 2", class: "ck-heading_heading2" },
      { model: "heading3", view: "h3", title: "Heading 3", class: "ck-heading_heading3" },
      { model: "heading4", view: "h4", title: "Heading 4", class: "ck-heading_heading4" }
    ]
  },
  image: {
    toolbar: ["imageTextAlternative", "toggleImageCaption", "|", "imageStyle:inline", "imageStyle:block"],
    upload: { types: ["jpeg", "png", "gif", "webp"] }
  }
};
1.3.2 Custom AI Plugin
// plugins/AIAssistantPlugin.ts
import { Plugin } from "@ckeditor/ckeditor5-core";
import { ButtonView } from "@ckeditor/ckeditor5-ui";

export class AIAssistantPlugin extends Plugin {
  init() {
    const editor = this.editor;

    editor.ui.componentFactory.add("aiAssistant", (locale) => {
      const button = new ButtonView(locale);
      button.set({
        label: "AI Assistant",
        icon: sparklesIcon,
        tooltip: true,
        withText: false
      });

      button.on("execute", () => {
        const selection = editor.model.document.selection;
        const selectedText = this.getSelectedText(selection);
        this.openAIModal(selectedText);
      });

      return button;
    });
  }

  openAIModal(selectedText: string) {
    // Dispatch event to React component
    window.dispatchEvent(new CustomEvent("openAIAssistant", {
      detail: { selectedText, context: this.getArticleContext() }
    }));
  }
}
 
1.4 Metadata Panel (Sidebar Derecho)
Panel de configuración del artículo.
1.4.1 Campos del Panel
Campo	Tipo	Validación	Ayuda
Categoría	Select (required)	Debe seleccionar una	Categoría principal del artículo
Tags	Multi-select + create	Max 10 tags	Etiquetas para organización
Autor	Select (default: current)	Usuario con rol author+	Autor mostrado públicamente
Featured Image	Image upload	Min 1200x630px	Imagen para OG y cards
Excerpt	Textarea (500 chars)	Required si published	Resumen para listados
Answer Capsule	Textarea (200 chars)	Auto-generado o manual	Para GEO optimization
SEO Title	Input (70 chars)	Warn > 60 chars	Título para buscadores
SEO Description	Textarea (160 chars)	Warn > 155 chars	Meta description
URL Slug	Input (auto-generated)	Unique per tenant	URL amigable
Status	Radio buttons	Required	Estado de publicación
Publish Date	Datetime picker	If scheduled	Fecha de publicación
1.4.2 SEO Score Widget
Análisis en tiempo real de optimización SEO.
// components/SEOScoreWidget.tsx
interface SEOCheck {
  id: string;
  label: string;
  status: "pass" | "warn" | "fail";
  message: string;
}

const seoChecks: SEOCheck[] = [
  { id: "title_length", label: "Longitud del título", ... },
  { id: "meta_description", label: "Meta description", ... },
  { id: "keyword_density", label: "Densidad de keyword", ... },
  { id: "headings_structure", label: "Estructura de headings", ... },
  { id: "internal_links", label: "Enlaces internos", ... },
  { id: "image_alt", label: "Alt text en imágenes", ... },
  { id: "readability", label: "Legibilidad", ... },
  { id: "answer_capsule", label: "Answer Capsule", ... },
];

export function SEOScoreWidget({ article }: { article: Article }) {
  const checks = useMemo(() => analyzeArticle(article), [article]);
  const score = calculateScore(checks);

  return (
    <div className="seo-widget">
      <div className="seo-widget__score">
        <CircularProgress value={score} max={100} />
        <span>{score}/100</span>
      </div>
      <ul className="seo-widget__checks">
        {checks.map(check => (
          <li key={check.id} className={`seo-check--${check.status}`}>
            <StatusIcon status={check.status} />
            <span>{check.label}</span>
            <Tooltip content={check.message} />
          </li>
        ))}
      </ul>
    </div>
  );
}
 
1.5 Article List View
Vista de listado de artículos para gestión.
1.5.1 Layout
┌─────────────────────────────────────────────────────────────────────┐
│  HEADER: Artículos                          [+ Nuevo Artículo]     │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │ [Search 🔍] | Status ▼ | Categoría ▼ | Autor ▼ | Fecha ▼   │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                     │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │ □  Título               Categoría  Autor    Fecha   Status │   │
│  ├─────────────────────────────────────────────────────────────┤   │
│  │ □  Cómo preparar CV...  Empleo     P.Jaraba 15 Ene ●Publi  │   │
│  │ □  Tendencias IA 2026   Tech       M.López  14 Ene ○Draft  │   │
│  │ □  Guía Kit Digital...  Negocio    P.Jaraba 13 Ene ◐Sched  │   │
│  │ □  Agricultura 4.0...   Agro       A.García 12 Ene ●Publi  │   │
│  │ □  ...                  ...        ...      ...    ...     │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                     │
│  ◄ 1 2 3 ... 15 ►                      Mostrando 1-20 de 298      │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
1.5.2 Bulk Actions
Acción	Permisos	Confirmación
Cambiar status	edit any content	No
Cambiar categoría	edit any content	No
Cambiar autor	admin	Sí
Eliminar	delete content	Sí, con lista de títulos
Exportar CSV	view content	No
 
2. AI Writing Assistant UI
Componentes detallados de la interfaz de generación IA.
2.1 Modal de Generación
Modal principal que aparece al usar cualquier acción de IA.
2.1.1 Layout del Modal
┌─────────────────────────────────────────────────────────────┐
│  ✨ AI Writing Assistant                              [X]  │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Acción: [Generar Outline ▼]                               │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ Tema o descripción:                                 │   │
│  │ ┌─────────────────────────────────────────────────┐ │   │
│  │ │ Cómo preparar un CV para el sector tech en      │ │   │
│  │ │ España, enfocado en desarrolladores junior      │ │   │
│  │ └─────────────────────────────────────────────────┘ │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  Keywords objetivo (separadas por coma):                   │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ CV tech, curriculum desarrollador, empleo IT        │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  ┌──────────────────┐  ┌──────────────────┐                │
│  │ Tipo: Guide    ▼ │  │ Palabras: 1500 ▼ │                │
│  └──────────────────┘  └──────────────────┘                │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │  ▸ Opciones avanzadas                               │   │
│  │    Tono: [Professional ▼]                           │   │
│  │    Audiencia: [Juniors ▼]                           │   │
│  │    □ Incluir estadísticas                           │   │
│  │    □ Incluir FAQs                                   │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │                                                     │   │
│  │              [🔄 RESULTADO]                         │   │
│  │                                                     │   │
│  │  ## Outline Generado                                │   │
│  │                                                     │   │
│  │  1. Introducción                                    │   │
│  │     - Por qué importa tu CV                         │   │
│  │     - El mercado tech en España                     │   │
│  │                                                     │   │
│  │  2. Estructura del CV Tech                          │   │
│  │     - Header y datos de contacto                    │   │
│  │     - Perfil profesional                            │   │
│  │     ...                                             │   │
│  │                                                     │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│         [Regenerar 🔄]  [Insertar ✓]  [Cancelar]           │
│                                                             │
└─────────────────────────────────────────────────────────────┘
 
2.2 Estados del Modal
Estado	UI	Acciones Disponibles	Feedback
idle	Formulario vacío	Completar campos, Generar	Placeholder hints
loading	Spinner + skeleton	Cancelar	Texto: 'Generando...'
streaming	Texto apareciendo progresivamente	Cancelar, Pausar	Tokens/s indicator
success	Resultado completo	Regenerar, Insertar, Editar	Checkmark verde
error	Mensaje de error	Reintentar, Cerrar	Error específico
rate_limited	Countdown timer	Esperar, Cerrar	Tiempo restante
2.3 Inline AI Suggestions
Sugerencias contextuales que aparecen mientras el usuario escribe.
2.3.1 Trigger Conditions
•	Usuario termina un párrafo (. + Enter)
•	Usuario escribe '##' para nuevo heading
•	Usuario selecciona texto y hace right-click
•	Contenido tiene < 300 palabras después de 2 min
•	SEO score baja de 60
2.3.2 Tooltip de Sugerencia
┌────────────────────────────────────────────┐
│ 💡 Sugerencia AI                          │
├────────────────────────────────────────────┤
│ Este párrafo podría beneficiarse de un    │
│ ejemplo concreto. ¿Quieres que sugiera    │
│ uno?                                       │
│                                            │
│     [Sí, sugerir]  [Ignorar]  [No más]   │
└────────────────────────────────────────────┘
2.4 Context Menu AI
Menú contextual al seleccionar texto.
┌─────────────────────────┐
│ ✨ Mejorar redacción    │
│ 📝 Expandir             │
│ 📄 Resumir              │
│ 🔄 Reescribir           │
│ 🌐 Traducir         ▸   │
│ ───────────────────     │
│ ✂️ Cortar               │
│ 📋 Copiar               │
│ 📝 Pegar                │
└─────────────────────────┘
2.5 React Components
2.5.1 AIGenerationModal
// components/ai/AIGenerationModal.tsx
interface AIGenerationModalProps {
  isOpen: boolean;
  onClose: () => void;
  onInsert: (content: string) => void;
  initialAction?: AIAction;
  selectedText?: string;
  articleContext: ArticleContext;
}

type AIAction = "outline" | "article" | "expand" | "headline" | "summary" | "seo" | "translate";

export function AIGenerationModal({
  isOpen, onClose, onInsert, initialAction, selectedText, articleContext
}: AIGenerationModalProps) {
  const [action, setAction] = useState<AIAction>(initialAction || "outline");
  const [topic, setTopic] = useState("");
  const [keywords, setKeywords] = useState<string[]>([]);
  const [contentType, setContentType] = useState<ContentType>("guide");
  const [wordCount, setWordCount] = useState(1500);
  const [result, setResult] = useState<string | null>(null);

  const { mutate: generate, isLoading, error } = useMutation({
    mutationFn: (params: GenerateParams) => 
      fetch(`/api/v1/content/generate/${action}`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(params)
      }).then(res => res.json()),
    onSuccess: (data) => setResult(data.content)
  });

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="ai-modal">
        <DialogHeader>
          <DialogTitle>✨ AI Writing Assistant</DialogTitle>
        </DialogHeader>
        
        <div className="ai-modal__form">
          <ActionSelect value={action} onChange={setAction} />
          <TopicInput value={topic} onChange={setTopic} />
          <KeywordsInput value={keywords} onChange={setKeywords} />
          <AdvancedOptions contentType={contentType} wordCount={wordCount} ... />
        </div>
        
        {isLoading && <LoadingState />}
        {error && <ErrorState error={error} onRetry={() => generate(...)} />}
        {result && <ResultPreview content={result} />}
        
        <DialogFooter>
          <Button variant="outline" onClick={onClose}>Cancelar</Button>
          {result && <Button onClick={() => generate(...)}>Regenerar</Button>}
          {result && <Button onClick={() => onInsert(result)}>Insertar</Button>}
          {!result && <Button onClick={() => generate(...)}>Generar</Button>}
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
 
3. Newsletter Campaign Builder
Constructor visual de campañas de newsletter con drag-and-drop.
3.1 Layout del Builder
┌─────────────────────────────────────────────────────────────────────┐
│  HEADER: Nueva Campaña                    [Guardar] [Programar]   │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  ┌──────────────────┬──────────────────────────────────────────┐   │
│  │                  │                                          │   │
│  │   BLOCKS         │         EMAIL PREVIEW                    │   │
│  │   PANEL          │                                          │   │
│  │                  │  ┌──────────────────────────────────┐   │   │
│  │  ┌────────────┐  │  │  Subject: Lo mejor de la semana │   │   │
│  │  │ 📰 Hero    │  │  │  Preheader: Descubre los 5...   │   │   │
│  │  └────────────┘  │  └──────────────────────────────────┘   │   │
│  │  ┌────────────┐  │                                          │   │
│  │  │ 📄 Article │  │  ┌──────────────────────────────────┐   │   │
│  │  └────────────┘  │  │ ┌────────────────────────────┐   │   │   │
│  │  ┌────────────┐  │  │ │      HERO ARTICLE          │   │   │   │
│  │  │ 📋 List    │  │  │ │ [Imagen destacada]         │   │   │   │
│  │  └────────────┘  │  │ │ Título del artículo        │   │   │   │
│  │  ┌────────────┐  │  │ │ Excerpt breve...           │   │   │   │
│  │  │ 🔗 CTA     │  │  │ │ [Leer más →]               │   │   │   │
│  │  └────────────┘  │  │ └────────────────────────────┘   │   │   │
│  │  ┌────────────┐  │  │                                  │   │   │
│  │  │ ➖ Divider │  │  │ ┌──────────┐ ┌──────────┐       │   │   │
│  │  └────────────┘  │  │ │ Art 2    │ │ Art 3    │       │   │   │
│  │  ┌────────────┐  │  │ └──────────┘ └──────────┘       │   │   │
│  │  │ 📝 Text    │  │  │                                  │   │   │
│  │  └────────────┘  │  │ ┌────────────────────────────┐   │   │   │
│  │  ┌────────────┐  │  │ │ [Ver todos los artículos]  │   │   │   │
│  │  │ 🖼️ Image  │  │  │ └────────────────────────────┘   │   │   │
│  │  └────────────┘  │  │                                  │   │   │
│  │  ┌────────────┐  │  │ ──────────────────────────────   │   │   │
│  │  │ 🔲 Button  │  │  │ Footer: Unsub | Preferences     │   │   │
│  │  └────────────┘  │  │                                  │   │   │
│  │                  │  └──────────────────────────────────┘   │   │
│  │  ──────────────  │                                          │   │
│  │                  │  [📱 Mobile] [💻 Desktop] [📧 Send Test]│   │
│  │  SETTINGS        │                                          │   │
│  │                  │                                          │   │
│  │  Tipo: Digest ▼  │                                          │   │
│  │  Segmento: All ▼ │                                          │   │
│  │                  │                                          │   │
│  └──────────────────┴──────────────────────────────────────────┘   │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
 
3.2 Block Types
Block	Propiedades	Contenido	Personalización
Hero Article	article_uuid, custom_headline	Imagen + título + excerpt + CTA	Headline override, CTA text
Article Card	article_uuid	Thumbnail + título + excerpt	Show/hide image
Article List	article_uuids[], max_items	Lista de artículos compacta	Order, max items
CTA Button	text, url, style	Botón con enlace	Color, size, alignment
Divider	style, spacing	Línea separadora	Style, color, margin
Text Block	content (HTML)	Texto libre	Full WYSIWYG
Image	image_url, alt, link	Imagen sola	Width, alignment, link
Social Icons	networks[], style	Iconos de redes sociales	Networks, icon style
Spacer	height	Espacio vacío	Height in px
3.3 Drag-and-Drop Implementation
// components/newsletter/CampaignBuilder.tsx
import { DndContext, DragOverlay, closestCenter } from "@dnd-kit/core";
import { SortableContext, verticalListSortingStrategy } from "@dnd-kit/sortable";

interface ContentBlock {
  id: string;
  type: BlockType;
  props: Record<string, any>;
}

export function CampaignBuilder({ campaign }: { campaign: Campaign }) {
  const [blocks, setBlocks] = useState<ContentBlock[]>(campaign.content_blocks);
  const [activeId, setActiveId] = useState<string | null>(null);

  const handleDragEnd = (event: DragEndEvent) => {
    const { active, over } = event;
    if (over && active.id !== over.id) {
      setBlocks((items) => {
        const oldIndex = items.findIndex((i) => i.id === active.id);
        const newIndex = items.findIndex((i) => i.id === over.id);
        return arrayMove(items, oldIndex, newIndex);
      });
    }
  };

  return (
    <div className="campaign-builder">
      <BlocksPalette onAddBlock={(type) => addBlock(type)} />
      
      <DndContext
        collisionDetection={closestCenter}
        onDragStart={({ active }) => setActiveId(active.id)}
        onDragEnd={handleDragEnd}
      >
        <SortableContext items={blocks} strategy={verticalListSortingStrategy}>
          <div className="campaign-builder__canvas">
            {blocks.map((block) => (
              <SortableBlock
                key={block.id}
                block={block}
                onEdit={(props) => updateBlock(block.id, props)}
                onDelete={() => deleteBlock(block.id)}
              />
            ))}
          </div>
        </SortableContext>
        <DragOverlay>
          {activeId ? <BlockPreview id={activeId} /> : null}
        </DragOverlay>
      </DndContext>
      
      <EmailPreview blocks={blocks} template={campaign.template_id} />
    </div>
  );
}
3.4 Article Selector Modal
Modal para seleccionar artículos a incluir en la campaña.
┌─────────────────────────────────────────────────────────────┐
│  Seleccionar Artículos                                [X]  │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  [Search 🔍____________] | Categoría ▼ | Fecha ▼ | Top ▼   │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ ☑ Cómo preparar CV tech    Empleo   15 Ene  ⭐ 89   │   │
│  │ ☑ Tendencias IA 2026       Tech     14 Ene  ⭐ 76   │   │
│  │ □ Guía Kit Digital         Negocio  13 Ene  ⭐ 72   │   │
│  │ □ Agricultura 4.0          Agro     12 Ene  ⭐ 68   │   │
│  │ □ ...                                                │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  Seleccionados: 2                    [Cancelar] [Añadir]   │
│                                                             │
└─────────────────────────────────────────────────────────────┘
 
3.5 Campaign Settings Panel
Campo	Tipo	Descripción
Campaign Type	Select	digest | announcement | engagement | reengagement
Subject Line	Input (100 chars)	Asunto del email
Preheader	Input (150 chars)	Texto preview
Template	Select	Template base a usar
Segment	Select	Segmento de audiencia o 'All'
Schedule	Datetime picker	Fecha/hora de envío
A/B Test Subject	Toggle + input	Variante B del subject
3.6 Campaign List View
Columna	Contenido	Ordenable	Filtrable
Subject	Asunto truncado	Sí	Search
Type	Badge de tipo	Sí	Select
Status	Badge draft/scheduled/sent	Sí	Select
Scheduled	Fecha programada	Sí	Date range
Sent	Fecha de envío	Sí	Date range
Recipients	Número de destinatarios	Sí	No
Open Rate	% aperturas	Sí	Range
Click Rate	% clicks	Sí	Range
Actions	Edit/Duplicate/Delete	No	No
 
4. Email Templates (MJML)
Diseños HTML responsive para los diferentes tipos de newsletter, creados con MJML.
4.1 Template Base
<!-- templates/email/base.mjml -->
<mjml>
  <mj-head>
    <mj-title>{{ subject }}</mj-title>
    <mj-preview>{{ preheader }}</mj-preview>
    <mj-attributes>
      <mj-all font-family="Arial, sans-serif" />
      <mj-text font-size="16px" line-height="1.6" color="#333333" />
      <mj-button background-color="{{ tenant.primary_color }}" border-radius="4px" />
    </mj-attributes>
    <mj-style inline="inline">
      .headline { font-size: 24px; font-weight: bold; }
      .article-title { color: #333; text-decoration: none; }
      .article-title:hover { color: {{ tenant.primary_color }}; }
    </mj-style>
  </mj-head>
  <mj-body background-color="#f4f4f4">
    <!-- Header -->
    <mj-section background-color="{{ tenant.primary_color }}" padding="20px">
      <mj-column>
        <mj-image src="{{ tenant.logo_url }}" alt="{{ tenant.name }}" width="150px" />
      </mj-column>
    </mj-section>
    
    <!-- Content Blocks -->
    {% for block in content_blocks %}
      {% include "email/blocks/" ~ block.type ~ ".mjml" with block.props %}
    {% endfor %}
    
    <!-- Footer -->
    <mj-section background-color="#333333" padding="30px">
      <mj-column>
        <mj-social font-size="12px" icon-size="24px" mode="horizontal">
          <mj-social-element name="linkedin" href="{{ tenant.social.linkedin }}" />
          <mj-social-element name="twitter" href="{{ tenant.social.twitter }}" />
        </mj-social>
        <mj-text color="#ffffff" font-size="12px" align="center">
          {{ tenant.address }}
        </mj-text>
        <mj-text color="#ffffff" font-size="12px" align="center">
          <a href="{{ unsubscribe_url }}" style="color: #ffffff;">Darse de baja</a> | 
          <a href="{{ preferences_url }}" style="color: #ffffff;">Preferencias</a>
        </mj-text>
      </mj-column>
    </mj-section>
  </mj-body>
</mjml>
 
4.2 Block Templates
4.2.1 Hero Article Block
<!-- templates/email/blocks/hero.mjml -->
<mj-section background-color="#ffffff" padding="0">
  <mj-column>
    <mj-image src="{{ article.featured_image.url }}" alt="{{ article.title }}" fluid-on-mobile="true" />
  </mj-column>
</mj-section>
<mj-section background-color="#ffffff" padding="20px 30px">
  <mj-column>
    <mj-text css-class="headline">
      <a href="{{ article.url }}?{{ utm }}" class="article-title">
        {{ custom_headline ?? article.title }}
      </a>
    </mj-text>
    <mj-text>{{ article.excerpt }}</mj-text>
    <mj-button href="{{ article.url }}?{{ utm }}">
      {{ cta_text ?? "Leer artículo →" }}
    </mj-button>
  </mj-column>
</mj-section>
4.2.2 Article Card Block
<!-- templates/email/blocks/article_card.mjml -->
<mj-section background-color="#ffffff" padding="15px 30px">
  <mj-column width="30%">
    {% if show_image %}
    <mj-image src="{{ article.featured_image.thumbnail }}" alt="" width="120px" />
    {% endif %}
  </mj-column>
  <mj-column width="70%">
    <mj-text font-size="14px" color="#666666">{{ article.category.name }}</mj-text>
    <mj-text font-size="18px" font-weight="bold">
      <a href="{{ article.url }}?{{ utm }}" class="article-title">{{ article.title }}</a>
    </mj-text>
    <mj-text font-size="14px">{{ article.excerpt|truncate(100) }}</mj-text>
  </mj-column>
</mj-section>
4.2.3 Article List Block
<!-- templates/email/blocks/article_list.mjml -->
<mj-section background-color="#ffffff" padding="20px 30px">
  <mj-column>
    <mj-text font-size="20px" font-weight="bold" padding-bottom="15px">
      {{ list_title ?? "Más artículos" }}
    </mj-text>
    {% for article in articles|slice(0, max_items ?? 5) %}
    <mj-text padding="10px 0" border-bottom="1px solid #eeeeee">
      <a href="{{ article.url }}?{{ utm }}" class="article-title">
        {{ article.title }}
      </a>
      <br />
      <span style="font-size: 12px; color: #999;">
        {{ article.publish_date|date("d M") }} · {{ article.reading_time }} min
      </span>
    </mj-text>
    {% endfor %}
  </mj-column>
</mj-section>
4.2.4 CTA Button Block
<!-- templates/email/blocks/cta.mjml -->
<mj-section background-color="#ffffff" padding="20px 30px">
  <mj-column>
    <mj-button 
      href="{{ url }}?{{ utm }}" 
      background-color="{{ bg_color ?? tenant.primary_color }}"
      color="{{ text_color ?? '#ffffff' }}"
      font-size="{{ font_size ?? '16px' }}"
      border-radius="4px"
      padding="12px 24px"
    >
      {{ text }}
    </mj-button>
  </mj-column>
</mj-section>
 
4.3 Pre-built Campaign Templates
4.3.1 Weekly Digest Template
Sección	Block Type	Contenido
Header	Hero Image	Logo + banner semanal
Intro	Text	Saludo + resumen de la semana
Featured	Hero Article	Artículo más popular
Divider	Divider	Línea separadora
More Articles	Article List (4)	Siguientes 4 por engagement
CTA	CTA Button	Ver todos los artículos
Footer	Footer	Social + legal
4.3.2 New Article Announcement
Sección	Block Type	Contenido
Header	Header	Logo
Hero	Hero Article	Artículo nuevo completo
Related	Article Cards (2)	Artículos relacionados
CTA	CTA Button	Ver en el blog
Footer	Footer	Social + legal
4.3.3 Re-engagement Template
Sección	Block Type	Contenido
Header	Header	Logo
Message	Text	Te echamos de menos + propuesta de valor
Best Of	Article List (5)	Top 5 artículos históricos
CTA	CTA Button	Volver al blog
Footer	Footer	Social + opción unsub destacada
4.4 Email Build Pipeline
// services/EmailBuilder.ts
import mjml2html from "mjml";

export class EmailBuilder {
  async buildCampaign(campaign: Campaign, tenant: Tenant): Promise<string> {
    // 1. Load base template
    const baseTemplate = await this.loadTemplate("base.mjml");
    
    // 2. Render blocks
    const renderedBlocks = await Promise.all(
      campaign.content_blocks.map(block => this.renderBlock(block, tenant))
    );
    
    // 3. Compile MJML to HTML
    const mjmlContent = this.injectBlocks(baseTemplate, renderedBlocks, {
      subject: campaign.subject,
      preheader: campaign.preheader,
      tenant,
      unsubscribe_url: this.getUnsubscribeUrl(campaign),
      preferences_url: this.getPreferencesUrl(campaign)
    });
    
    const { html, errors } = mjml2html(mjmlContent, {
      validationLevel: "soft",
      minify: true
    });
    
    if (errors.length > 0) {
      console.warn("MJML warnings:", errors);
    }
    
    // 4. Inline CSS for email clients
    return this.inlineCSS(html);
  }
}
 
5. Analytics Dashboard
Dashboard de métricas de contenido, engagement y conversión.
5.1 Layout Principal
┌─────────────────────────────────────────────────────────────────────┐
│  Content Analytics            [Last 7 days ▼] [Export CSV]        │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  ┌───────────┐ ┌───────────┐ ┌───────────┐ ┌───────────┐           │
│  │ Total     │ │ Unique    │ │ Avg Time  │ │ Bounce    │           │
│  │ Views     │ │ Visitors  │ │ on Page   │ │ Rate      │           │
│  │           │ │           │ │           │ │           │           │
│  │  45,231   │ │  12,847   │ │  4:32     │ │  42.3%    │           │
│  │  ↑ 12.5%  │ │  ↑ 8.3%   │ │  ↑ 0:45   │ │  ↓ 3.2%   │           │
│  └───────────┘ └───────────┘ └───────────┘ └───────────┘           │
│                                                                     │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │                 TRAFFIC OVER TIME                           │   │
│  │                                                             │   │
│  │      ╭──╮                                                   │   │
│  │   ╭──╯  ╰──╮    ╭─╮                                         │   │
│  │  ─╯        ╰────╯ ╰──╮  ╭──────╮                            │   │
│  │                      ╰──╯      ╰───                          │   │
│  │  Mon  Tue  Wed  Thu  Fri  Sat  Sun                          │   │
│  │                                                             │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                     │
│  ┌──────────────────────────────┬──────────────────────────────┐   │
│  │     TOP ARTICLES             │     TOP CATEGORIES           │   │
│  │                              │                              │   │
│  │  1. Cómo preparar CV    4.2K │  ████████████░░ Empleo  45% │   │
│  │  2. Tendencias IA       3.8K │  ████████░░░░░░ Tech    32% │   │
│  │  3. Kit Digital         2.1K │  █████░░░░░░░░░ Agro    15% │   │
│  │  4. Agricultura 4.0     1.9K │  ███░░░░░░░░░░░ Otros    8% │   │
│  │  5. Comercio local      1.7K │                              │   │
│  │                              │                              │   │
│  └──────────────────────────────┴──────────────────────────────┘   │
│                                                                     │
│  ┌──────────────────────────────┬──────────────────────────────┐   │
│  │     NEWSLETTER STATS         │     CONTENT GAP ANALYSIS     │   │
│  │                              │                              │   │
│  │  Subscribers:    2,847       │  Trending topics sin cover:  │   │
│  │  Avg Open Rate:  34.2%       │  • Inteligencia artificial   │   │
│  │  Avg Click Rate: 12.8%       │  • Sostenibilidad            │   │
│  │  Unsubscribe:    0.3%        │  • Trabajo remoto            │   │
│  │                              │                              │   │
│  └──────────────────────────────┴──────────────────────────────┘   │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
 
5.2 KPIs y Métricas
5.2.1 Content Metrics
Métrica	Cálculo	Target	Frecuencia Update
Total Page Views	SUM(views) en período	↑ 10% MoM	Real-time
Unique Visitors	COUNT(DISTINCT session_id)	↑ 8% MoM	Real-time
Avg Time on Page	AVG(time_on_page)	> 3 min	Hourly
Bounce Rate	Single page sessions / Total	< 50%	Hourly
Pages per Session	Views / Sessions	> 2.5	Hourly
Scroll Depth	AVG(max_scroll_percent)	> 70%	Hourly
Social Shares	SUM(shares) por red	↑ 15% MoM	Daily
Comments	COUNT(comments)	↑ 20% MoM	Daily
5.2.2 Newsletter Metrics
Métrica	Cálculo	Target	Benchmark
Subscriber Growth	(New - Unsubs) / Total	> 2% MoM	1.5% industry avg
Open Rate	Opens / Delivered	> 25%	21% industry avg
Click Rate	Clicks / Delivered	> 3%	2.5% industry avg
Click-to-Open	Clicks / Opens	> 12%	10% industry avg
Unsubscribe Rate	Unsubs / Delivered	< 0.5%	0.3% industry avg
Bounce Rate	Bounced / Sent	< 2%	1% industry avg
Spam Complaints	Complaints / Delivered	< 0.1%	0.02% industry avg
List Health Score	Engaged / Total	> 60%	Custom metric
5.2.3 AI Generation Metrics
Métrica	Cálculo	Target	Uso
Generations/Day	COUNT(generations)	Monitor	Capacity planning
Avg Latency	AVG(latency_ms)	< 5000ms	Performance
Success Rate	Success / Total	> 95%	Reliability
User Rating	AVG(user_rating)	> 4.0	Quality
Acceptance Rate	Inserted / Generated	> 60%	Usefulness
Token Usage	SUM(tokens)	Budget	Cost control
Cost/Article	API cost / articles	< €0.50	ROI
 
5.3 Dashboard Components
5.3.1 KPI Card Component
// components/analytics/KPICard.tsx
interface KPICardProps {
  title: string;
  value: number | string;
  change: number;  // Percentage change
  changeLabel: string;  // e.g., "vs last period"
  format?: "number" | "percent" | "duration" | "currency";
  icon?: React.ReactNode;
}

export function KPICard({ title, value, change, changeLabel, format, icon }: KPICardProps) {
  const formattedValue = formatValue(value, format);
  const isPositive = change >= 0;
  const changeColor = isPositive ? "text-green-600" : "text-red-600";
  const changeIcon = isPositive ? <TrendingUp /> : <TrendingDown />;

  return (
    <div className="kpi-card">
      <div className="kpi-card__header">
        {icon && <span className="kpi-card__icon">{icon}</span>}
        <span className="kpi-card__title">{title}</span>
      </div>
      <div className="kpi-card__value">{formattedValue}</div>
      <div className={`kpi-card__change ${changeColor}`}>
        {changeIcon}
        <span>{Math.abs(change).toFixed(1)}%</span>
        <span className="kpi-card__label">{changeLabel}</span>
      </div>
    </div>
  );
}
5.3.2 Traffic Chart Component
// components/analytics/TrafficChart.tsx
import { LineChart, Line, XAxis, YAxis, Tooltip, ResponsiveContainer } from "recharts";

interface TrafficData {
  date: string;
  views: number;
  visitors: number;
}

export function TrafficChart({ data, period }: { data: TrafficData[], period: string }) {
  return (
    <div className="traffic-chart">
      <h3 className="traffic-chart__title">Traffic Over Time</h3>
      <ResponsiveContainer width="100%" height={300}>
        <LineChart data={data}>
          <XAxis dataKey="date" tickFormatter={formatDate} />
          <YAxis />
          <Tooltip formatter={formatTooltip} />
          <Line 
            type="monotone" 
            dataKey="views" 
            stroke="var(--color-primary)" 
            strokeWidth={2}
            dot={false}
          />
          <Line 
            type="monotone" 
            dataKey="visitors" 
            stroke="var(--color-secondary)" 
            strokeWidth={2}
            dot={false}
          />
        </LineChart>
      </ResponsiveContainer>
    </div>
  );
}
5.4 Content Gap Analysis
Identificación automática de temas trending sin cobertura.
•	Fuente: Google Trends API + Search Console queries
•	Comparación: Queries con clicks vs contenido existente
•	Output: Lista de topics sugeridos con volumen estimado
•	Acción: Botón 'Crear artículo sobre este tema' → abre editor con topic pre-poblado
 
6. Test Specification
Especificación de tests unitarios, integración y E2E para el módulo Content Hub.
6.1 Unit Tests
6.1.1 Entity Tests
// tests/Unit/Entity/ContentArticleTest.php
class ContentArticleTest extends UnitTestCase {

  public function testArticleCreation() {
    $article = ContentArticle::create([
      "title" => "Test Article",
      "slug" => "test-article",
      "body" => "<p>Test content</p>",
      "tenant_id" => 1,
      "author_id" => 1,
    ]);
    
    $this->assertEquals("Test Article", $article->getTitle());
    $this->assertEquals("draft", $article->getStatus());
  }

  public function testSlugGeneration() {
    $article = ContentArticle::create(["title" => "Cómo Crear un CV"]);
    $this->assertEquals("como-crear-un-cv", $article->getSlug());
  }

  public function testReadingTimeCalculation() {
    $article = ContentArticle::create([
      "body" => str_repeat("word ", 1000)  // 1000 words
    ]);
    $this->assertEquals(5, $article->getReadingTime());  // 200 wpm
  }

  public function testStatusTransitions() {
    $article = ContentArticle::create(["status" => "draft"]);
    
    $article->setStatus("review");
    $this->assertEquals("review", $article->getStatus());
    
    $this->expectException(InvalidStatusTransitionException::class);
    $article->setStatus("archived");  // Can't go from review to archived
  }
}
6.1.2 Service Tests
// tests/Unit/Service/AIGenerationServiceTest.php
class AIGenerationServiceTest extends UnitTestCase {

  private $claudeClient;
  private $service;

  protected function setUp(): void {
    $this->claudeClient = $this->createMock(ClaudeApiClient::class);
    $this->service = new AIGenerationService($this->claudeClient);
  }

  public function testGenerateOutline() {
    $this->claudeClient->expects($this->once())
      ->method("generate")
      ->willReturn(["content" => "## Section 1\n## Section 2"]);
    
    $result = $this->service->generateOutline("Test topic", ["keyword1"]);
    
    $this->assertIsArray($result);
    $this->assertArrayHasKey("content", $result);
  }

  public function testRateLimiting() {
    // Simulate 11 requests (limit is 10/min)
    for ($i = 0; $i < 10; $i++) {
      $this->service->generateOutline("Topic $i", []);
    }
    
    $this->expectException(RateLimitExceededException::class);
    $this->service->generateOutline("Topic 11", []);
  }
}
 
6.2 Integration Tests
6.2.1 API Tests
// tests/Functional/Api/ArticleApiTest.php
class ArticleApiTest extends BrowserTestBase {

  protected static $modules = ["jaraba_content_hub"];

  public function testListArticles() {
    $this->createArticle(["status" => "published"]);
    $this->createArticle(["status" => "published"]);
    $this->createArticle(["status" => "draft"]);
    
    $response = $this->request("GET", "/api/v1/content/articles");
    
    $this->assertEquals(200, $response->getStatusCode());
    $data = json_decode($response->getContent(), true);
    $this->assertCount(2, $data["data"]);  // Only published
  }

  public function testCreateArticle() {
    $this->drupalLogin($this->createUser(["create content_article"]));
    
    $response = $this->request("POST", "/api/v1/content/articles", [
      "json" => [
        "title" => "New Article",
        "body" => "<p>Content</p>",
        "category_id" => 1
      ]
    ]);
    
    $this->assertEquals(201, $response->getStatusCode());
    $data = json_decode($response->getContent(), true);
    $this->assertEquals("New Article", $data["data"]["title"]);
  }

  public function testUnauthorizedAccess() {
    $response = $this->request("POST", "/api/v1/content/articles", [
      "json" => ["title" => "Test"]
    ]);
    
    $this->assertEquals(401, $response->getStatusCode());
  }
}
6.2.2 ECA Flow Tests
// tests/Functional/Eca/PublishFlowTest.php
class PublishFlowTest extends BrowserTestBase {

  public function testArticlePublishTriggersIndexing() {
    $article = $this->createArticle(["status" => "draft"]);
    
    // Mock Qdrant client
    $qdrantMock = $this->createMock(QdrantClient::class);
    $qdrantMock->expects($this->once())
      ->method("upsert")
      ->with($this->callback(fn($points) => 
        $points[0]["payload"]["article_uuid"] === $article->uuid()
      ));
    
    $this->container->set("jaraba_content_hub.qdrant_client", $qdrantMock);
    
    // Trigger publish
    $article->setStatus("published")->save();
    
    // Verify sitemap updated
    $this->assertSitemapContains($article->toUrl()->toString());
  }

  public function testWeeklyDigestGeneration() {
    // Create 5 published articles
    for ($i = 0; $i < 5; $i++) {
      $this->createArticle([
        "status" => "published",
        "publish_date" => strtotime("-" . $i . " days")
      ]);
    }
    
    // Run cron (Monday 7am)
    $this->setCronTime("Monday 07:00");
    $this->cronRun();
    
    // Verify campaign created
    $campaigns = NewsletterCampaign::loadMultiple();
    $this->assertCount(1, $campaigns);
    $this->assertEquals("digest", reset($campaigns)->getType());
  }
}
 
6.3 E2E Tests (Cypress)
6.3.1 Editor Flow Test
// cypress/e2e/editor.cy.ts
describe("Article Editor", () => {
  beforeEach(() => {
    cy.login("author@example.com", "password");
    cy.visit("/admin/content/articles/new");
  });

  it("creates and publishes an article", () => {
    // Fill title
    cy.get("[data-testid=article-title]").type("Test Article Title");
    
    // Fill body in CKEditor
    cy.get(".ck-editor__editable").type("This is the article content.");
    
    // Select category
    cy.get("[data-testid=category-select]").click();
    cy.contains("Empleabilidad").click();
    
    // Save draft
    cy.get("[data-testid=save-button]").click();
    cy.contains("Guardado").should("be.visible");
    
    // Publish
    cy.get("[data-testid=publish-button]").click();
    cy.get("[data-testid=confirm-publish]").click();
    cy.contains("Publicado").should("be.visible");
    
    // Verify public page
    cy.visit("/blog/test-article-title");
    cy.contains("Test Article Title").should("be.visible");
  });

  it("uses AI assistant to generate outline", () => {
    cy.get("[data-testid=ai-panel]").click();
    cy.get("[data-testid=ai-action-outline]").click();
    
    cy.get("[data-testid=ai-topic-input]").type("Cómo preparar un CV tech");
    cy.get("[data-testid=ai-keywords-input]").type("CV, tech, empleo");
    cy.get("[data-testid=ai-generate-button]").click();
    
    // Wait for generation
    cy.get("[data-testid=ai-result]", { timeout: 30000 }).should("be.visible");
    cy.get("[data-testid=ai-result]").should("contain", "##");
    
    // Insert into editor
    cy.get("[data-testid=ai-insert-button]").click();
    cy.get(".ck-editor__editable").should("contain", "##");
  });
});
6.3.2 Newsletter Builder Test
// cypress/e2e/newsletter-builder.cy.ts
describe("Newsletter Campaign Builder", () => {
  beforeEach(() => {
    cy.login("editor@example.com", "password");
    cy.visit("/admin/newsletter/campaigns/new");
  });

  it("creates a weekly digest campaign", () => {
    // Set subject
    cy.get("[data-testid=subject-input]").type("Lo mejor de la semana");
    cy.get("[data-testid=preheader-input]").type("Descubre los artículos más leídos");
    
    // Add hero block
    cy.get("[data-testid=block-hero]").drag("[data-testid=canvas]");
    cy.get("[data-testid=select-article-button]").click();
    cy.get("[data-testid=article-list] li").first().click();
    cy.get("[data-testid=confirm-selection]").click();
    
    // Add article list
    cy.get("[data-testid=block-article-list]").drag("[data-testid=canvas]");
    cy.get("[data-testid=select-articles-button]").click();
    cy.get("[data-testid=article-list] li").eq(1).click();
    cy.get("[data-testid=article-list] li").eq(2).click();
    cy.get("[data-testid=confirm-selection]").click();
    
    // Preview
    cy.get("[data-testid=preview-desktop]").click();
    cy.get("[data-testid=email-preview]").should("be.visible");
    
    // Schedule
    cy.get("[data-testid=schedule-button]").click();
    cy.get("[data-testid=datetime-picker]").type("2026-01-20T09:00");
    cy.get("[data-testid=confirm-schedule]").click();
    
    cy.contains("Campaña programada").should("be.visible");
  });
});
6.4 Test Coverage Requirements
Área	Target Coverage	Críticos
Entities	> 90%	Status transitions, validations
Services	> 85%	AI generation, rate limiting
APIs	> 95%	All endpoints, auth, errors
ECA Flows	> 80%	Publish, digest, recommendations
React Components	> 75%	Editor, modals, forms
E2E Flows	100% critical paths	Create, publish, newsletter
 
7. OpenAPI Specification
Documentación formal de la API REST en formato OpenAPI 3.0.
7.1 Spec Overview
openapi: 3.0.3
info:
  title: Jaraba Content Hub API
  description: API para gestión de contenido del AI Content Hub
  version: 1.0.0
  contact:
    name: Jaraba Impact Platform
    email: api@jaraba.es

servers:
  - url: https://{tenant}.jaraba.es/api/v1
    variables:
      tenant:
        default: demo
        description: Tenant identifier

security:
  - bearerAuth: []

tags:
  - name: Articles
    description: Gestión de artículos del blog
  - name: Categories
    description: Categorías de contenido
  - name: AI Generation
    description: Generación de contenido con IA
  - name: Newsletter
    description: Gestión de campañas y suscriptores
  - name: Analytics
    description: Métricas y estadísticas
7.2 Articles Endpoints
paths:
  /content/articles:
    get:
      tags: [Articles]
      summary: Lista artículos
      parameters:
        - name: status
          in: query
          schema:
            type: string
            enum: [draft, review, scheduled, published, archived]
        - name: category
          in: query
          schema:
            type: string
            format: uuid
        - name: page
          in: query
          schema:
            type: integer
            default: 1
        - name: per_page
          in: query
          schema:
            type: integer
            default: 10
            maximum: 50
      responses:
        200:
          description: Lista de artículos
          content:
            application/json:
              schema:
                $ref: "#/components/schemas/ArticleListResponse"
    post:
      tags: [Articles]
      summary: Crea un artículo
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: "#/components/schemas/ArticleCreateRequest"
      responses:
        201:
          description: Artículo creado
          content:
            application/json:
              schema:
                $ref: "#/components/schemas/ArticleResponse"
        400:
          $ref: "#/components/responses/ValidationError"
        401:
          $ref: "#/components/responses/Unauthorized"
7.3 AI Generation Endpoints
  /content/generate/article:
    post:
      tags: [AI Generation]
      summary: Genera un artículo completo con IA
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [topic]
              properties:
                topic:
                  type: string
                  maxLength: 500
                  example: "Cómo preparar un CV para el sector tech"
                keywords:
                  type: array
                  items:
                    type: string
                  maxItems: 10
                content_type:
                  type: string
                  enum: [guide, tutorial, listicle, comparison, news]
                  default: guide
                word_count:
                  type: integer
                  minimum: 500
                  maximum: 5000
                  default: 1500
                tone:
                  type: string
                  enum: [professional, casual, academic, inspirational]
                  default: professional
      responses:
        200:
          description: Artículo generado
          content:
            application/json:
              schema:
                $ref: "#/components/schemas/AIGenerationResponse"
        429:
          description: Rate limit exceeded
          headers:
            X-RateLimit-Reset:
              schema:
                type: integer
              description: Unix timestamp when limit resets
7.4 Schemas
components:
  schemas:
    Article:
      type: object
      properties:
        uuid:
          type: string
          format: uuid
        title:
          type: string
        slug:
          type: string
        excerpt:
          type: string
        body:
          type: string
        answer_capsule:
          type: string
          maxLength: 200
        featured_image:
          $ref: "#/components/schemas/Image"
        category:
          $ref: "#/components/schemas/Category"
        author:
          $ref: "#/components/schemas/Author"
        reading_time:
          type: integer
        status:
          type: string
          enum: [draft, review, scheduled, published, archived]
        publish_date:
          type: string
          format: date-time
        seo_title:
          type: string
        seo_description:
          type: string
        created:
          type: string
          format: date-time
        changed:
          type: string
          format: date-time

    AIGenerationResponse:
      type: object
      properties:
        status:
          type: string
          enum: [success, error]
        generation_id:
          type: string
        article:
          type: object
          properties:
            title:
              type: string
            slug:
              type: string
            excerpt:
              type: string
            answer_capsule:
              type: string
            body:
              type: string
            seo_title:
              type: string
            seo_description:
              type: string
        metadata:
          type: object
          properties:
            model:
              type: string
            input_tokens:
              type: integer
            output_tokens:
              type: integer
            latency_ms:
              type: integer
 
8. Roadmap de Implementación Adicional
8.1 Sprints Adicionales
Sprint	Semanas	Entregables	Horas
Sprint 3C: Editor UI	5-6	Editor dashboard, AI panel, metadata panel	50-60h
Sprint 3D: AI Assistant UI	5-6	Modal generación, inline suggestions, context menu	40-50h
Sprint 4C: Newsletter Builder	7-8	Drag-drop builder, blocks, preview	50-60h
Sprint 4D: Email Templates	7-8	MJML templates, build pipeline	30-40h
Sprint 6C: Analytics Dashboard	11-12	KPIs, charts, content gap analysis	40-50h
Sprint 6D: Tests & Docs	11-12	Unit, integration, E2E, OpenAPI	40-50h
8.2 Resumen de Inversión Total
Componente	Horas	Costo (€80/h)
Backend (128_v2)	290-350h	€23,200-28,000
Frontend Público (128b)	150-190h	€12,000-15,200
Editor & AI UI (128c)	90-110h	€7,200-8,800
Newsletter Builder (128c)	80-100h	€6,400-8,000
Analytics & Tests (128c)	80-100h	€6,400-8,000
TOTAL CONTENT HUB	690-850h	€55,200-68,000
8.3 Priorización Recomendada
Fase 1 - MVP (Semanas 1-8):
•	Backend completo (entidades, APIs, ECA básico)
•	Frontend público (homepage, article page, widgets)
•	Editor básico sin AI assistant
•	Newsletter envío manual
Inversión Fase 1: ~400h, €32,000

Fase 2 - AI Enhancement (Semanas 9-12):
•	AI Writing Assistant completo
•	Newsletter campaign builder
•	Email templates
•	Analytics básico
Inversión Fase 2: ~250h, €20,000

Fase 3 - Polish (Post-launch):
•	Analytics avanzado con content gap
•	Tests completos
•	OpenAPI documentation
•	Optimizaciones de performance
Inversión Fase 3: ~100h, €8,000

--- Fin del Documento ---

Jaraba Impact Platform | 128c_AI_Content_Hub_Editor_v1 | Enero 2026
