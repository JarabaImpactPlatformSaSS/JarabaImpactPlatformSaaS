
AI CONTENT HUB
Especificación de Frontend y UX
Complemento al Documento Técnico Principal (128_v2)

JARABA IMPACT PLATFORM
Diseño UI/UX - Componentes - Templates Twig - React Components

Campo	Valor	Notas
Versión:	1.0	Especificación Frontend completa
Fecha:	Enero 2026	
Estado:	Ready for Development	Sin Humo
Código:	128b_Platform_AI_Content_Hub_Frontend	
Dependencias:	128_v2, 100_Frontend_Architecture, 105_Theming	
 
1. Resumen Ejecutivo
Este documento especifica la arquitectura frontend completa del AI Content Hub, incluyendo el diseño del blog homepage (muro de publicaciones), páginas de artículos, componentes React/Twig, y todas las interacciones UX modernas basadas en las mejores prácticas de 2025-2026.
1.1 Stack Frontend
Capa	Tecnología	Propósito
Templates	Twig (Drupal 11)	Server-side rendering para SEO/GEO
Componentes Interactivos	React 19 + TypeScript	UI dinámica (editor, widgets)
Estilos	Tailwind CSS v4 + CSS Custom Properties	Theming multi-tenant
Animaciones	Framer Motion	Micro-interacciones
Estado	Zustand	Estado global ligero
Data Fetching	TanStack Query (React Query)	Cache y sincronización
Icons	Lucide React	Iconografía consistente
1.2 Principios de Diseño 2025
•	Card-based UI: Contenido organizado en tarjetas modulares y escaneables
•	Progressive Disclosure: Información revelada gradualmente según necesidad
•	Mobile-first Responsive: Diseño adaptativo desde 320px hasta 2560px
•	Micro-interacciones: Feedback visual sutil para cada acción del usuario
•	Accesibilidad WCAG 2.1 AA: Contraste, navegación por teclado, screen readers
•	Performance: LCP < 2.5s, FID < 100ms, CLS < 0.1
•	GEO-optimized: Contenido estructurado para AI crawlers y búsqueda semántica
 
2. Blog Homepage - Muro de Publicaciones
El muro de publicaciones es la vista principal del blog, diseñada para maximizar el engagement y facilitar el descubrimiento de contenido relevante.
2.1 Estructura de Layout
Layout responsivo con tres variantes principales:
2.1.1 Desktop (≥1280px)
┌─────────────────────────────────────────────────────────────┐
│                    HEADER + NAV                            │
├─────────────────────────────────────────────────────────────┤
│  HERO SECTION: Artículo Destacado (full-width card)        │
├─────────────────────────────────────────────────────────────┤
│                                                            │
│  ┌──────────┬─────────────────────────────────────────┐   │
│  │ SIDEBAR  │         CONTENT FEED                    │   │
│  │          │  ┌─────────┐ ┌─────────┐ ┌─────────┐   │   │
│  │ Categorías│  │ Card 1  │ │ Card 2  │ │ Card 3  │   │   │
│  │          │  └─────────┘ └─────────┘ └─────────┘   │   │
│  │ Newsletter│  ┌─────────┐ ┌─────────┐ ┌─────────┐   │   │
│  │          │  │ Card 4  │ │ Card 5  │ │ Card 6  │   │   │
│  │ Trending │  └─────────┘ └─────────┘ └─────────┘   │   │
│  │          │                                         │   │
│  │          │  [Load More / Infinite Scroll]          │   │
│  └──────────┴─────────────────────────────────────────┘   │
│                                                            │
├─────────────────────────────────────────────────────────────┤
│                    FOOTER                                  │
└─────────────────────────────────────────────────────────────┘
2.1.2 Tablet (768px - 1279px)
┌─────────────────────────────────────┐
│          HEADER + NAV              │
├─────────────────────────────────────┤
│  HERO: Artículo Destacado          │
├─────────────────────────────────────┤
│  [Category Pills - Horizontal]     │
├─────────────────────────────────────┤
│  ┌─────────┐ ┌─────────┐           │
│  │ Card 1  │ │ Card 2  │           │
│  └─────────┘ └─────────┘           │
│  ┌─────────┐ ┌─────────┐           │
│  │ Card 3  │ │ Card 4  │           │
│  └─────────┘ └─────────┘           │
│                                     │
│  [Newsletter CTA - Inline]         │
│                                     │
│  [Load More Button]                │
├─────────────────────────────────────┤
│            FOOTER                  │
└─────────────────────────────────────┘
2.1.3 Mobile (< 768px)
┌─────────────────────┐
│   HEADER + BURGER   │
├─────────────────────┤
│  HERO (compact)     │
├─────────────────────┤
│  [Category Pills]   │
├─────────────────────┤
│  ┌─────────────┐    │
│  │   Card 1    │    │
│  │  (stacked)  │    │
│  └─────────────┘    │
│  ┌─────────────┐    │
│  │   Card 2    │    │
│  └─────────────┘    │
│                     │
│  [Newsletter CTA]   │
│                     │
│  [Infinite Scroll]  │
├─────────────────────┤
│      FOOTER         │
└─────────────────────┘
 
2.2 Article Card Component
El componente principal del feed es la tarjeta de artículo, diseñada para máxima escaneabilidad.
2.2.1 Anatomía del Card
Elemento	Posición	Contenido	Interacción
Featured Image	Top (aspect-ratio 16:9)	Imagen optimizada WebP	Hover: scale 1.05 + overlay
Category Badge	Overlay top-left	Nombre categoría + color	Click → filtro categoría
Reading Time	Overlay top-right	X min · icono reloj	Informativo
Title	Below image	Max 2 líneas, ellipsis	Click → artículo
Excerpt	Below title	Max 3 líneas, 120 chars	Truncado con ...
Author Avatar	Footer left	32px circular + nombre	Click → perfil autor
Publish Date	Footer right	Fecha relativa o absoluta	Tooltip fecha exacta
Bookmark Icon	Footer far-right	Icono guardar	Toggle saved state
2.2.2 Card Variants
Variante	Uso	Diferencias
card-featured	Hero section	Full-width, imagen grande, excerpt completo
card-standard	Grid principal	3 columnas desktop, imagen 16:9
card-horizontal	Sidebar trending	Imagen left, contenido right, compacto
card-minimal	Related articles	Sin imagen, solo título + meta
card-newsletter	CTA inline	Icono email, título, input + botón
2.2.3 Twig Template: card-standard
{# templates/components/article-card.html.twig #}
{% set card_classes = [
  "article-card",
  "article-card--" ~ variant|default("standard"),
  is_featured ? "article-card--featured" : "",
]|join(" ")|trim %}

<article class="{{ card_classes }}" data-article-id="{{ article.uuid }}">
  <a href="{{ article.url }}" class="article-card__link">
    <div class="article-card__image-wrapper">
      <img
        src="{{ article.featured_image.url }}"
        alt="{{ article.featured_image.alt }}"
        loading="lazy"
        class="article-card__image"
      />
      <span class="article-card__category" style="--cat-color: {{ article.category.color }}">
        {{ article.category.name }}
      </span>
      <span class="article-card__reading-time">
        <svg class="icon">...</svg> {{ article.reading_time }} min
      </span>
    </div>
    <div class="article-card__content">
      <h3 class="article-card__title">{{ article.title }}</h3>
      <p class="article-card__excerpt">{{ article.excerpt|truncate(120) }}</p>
    </div>
  </a>
  <footer class="article-card__footer">
    <div class="article-card__author">
      <img src="{{ article.author.avatar }}" alt="" class="article-card__avatar" />
      <span>{{ article.author.name }}</span>
    </div>
    <time datetime="{{ article.publish_date|date("c") }}">
      {{ article.publish_date|time_diff }}
    </time>
    <button class="article-card__bookmark" aria-label="Guardar artículo">
      <svg class="icon">...</svg>
    </button>
  </footer>
</article>
 
2.3 Grid Layout Options
El feed soporta múltiples layouts configurables por tenant.
2.3.1 Masonry Grid (Recomendado)
Layout estilo Pinterest donde las cards se acomodan según su altura natural, maximizando el uso del espacio.
•	Librería: CSS Grid + JavaScript (Masonry.js o CSS-only con grid-auto-rows)
•	Columnas: 3 (desktop) → 2 (tablet) → 1 (mobile)
•	Gap: 24px (desktop) → 16px (mobile)
•	Mejor para: Blogs con imágenes de diferentes aspectos, contenido visual
2.3.2 Uniform Grid
Todas las cards tienen la misma altura fija, creando un layout más estructurado.
•	Implementación: CSS Grid con grid-template-rows fijo
•	Cards truncadas para uniformidad
•	Mejor para: Blogs corporativos, contenido formal
2.3.3 List View
Cards horizontales en una sola columna, similar a Reddit o Hacker News.
•	Thumbnail pequeño a la izquierda
•	Título + excerpt a la derecha
•	Mejor para: Contenido text-heavy, usuarios que prefieren escanear títulos
2.4 Infinite Scroll vs Pagination
Opción	Pros	Contras	Uso Recomendado
Infinite Scroll	UX fluido, más engagement, discovery	SEO limitado, no bookmarkable	Homepage, feeds casuales
Load More Button	Control del usuario, menos ansiedad	Requiere acción, más clicks	Default recomendado
Pagination Numérica	SEO friendly, bookmarkable	UX interrumpida, más formal	Archivos, búsquedas
Híbrido	Mejor SEO + UX fluido	Complejidad técnica	Implementar si recursos
Implementación Híbrida Recomendada:
•	Primera carga: 12 artículos server-rendered (SEO)
•	Scroll: Load More button que carga 6 más via AJAX
•	Después de 3 loads: Infinite scroll automático
•	URL actualizada con ?page=X para bookmarking
 
3. Página de Artículo
La página individual de artículo es donde ocurre el consumo real de contenido. Diseñada para máxima legibilidad y engagement.
3.1 Layout Desktop
┌─────────────────────────────────────────────────────────────────┐
│                    HEADER + NAV                                │
├─────────────────────────────────────────────────────────────────┤
│  ████████████████████ READING PROGRESS BAR ████████████████████│
├─────────────────────────────────────────────────────────────────┤
│                                                                │
│  Breadcrumb: Home > Categoría > Artículo                       │
│                                                                │
│  ┌────────────────────────────────────────────────────────┐    │
│  │                 HERO IMAGE (16:9)                      │    │
│  └────────────────────────────────────────────────────────┘    │
│                                                                │
│  ┌──────────┬────────────────────────────────┬───────────┐    │
│  │ SOCIAL   │      ARTICLE CONTENT           │   TABLE   │    │
│  │ SHARE    │                                │    OF     │    │
│  │ (sticky) │  <h1>Título del Artículo</h1>  │ CONTENTS  │    │
│  │          │                                │  (sticky) │    │
│  │ 🔗 📘 🐦 │  Author · Date · Reading Time  │           │    │
│  │          │                                │  • Intro  │    │
│  │          │  Answer Capsule (highlighted)  │  • Punto 1│    │
│  │          │                                │  • Punto 2│    │
│  │          │  <p>Contenido...</p>           │  • Concl. │    │
│  │          │  <h2>Sección 1</h2>            │           │    │
│  │          │  <p>...</p>                    │           │    │
│  │          │                                │           │    │
│  │          │  [CTA Newsletter mid-article]  │           │    │
│  │          │                                │           │    │
│  │          │  <h2>Sección 2</h2>            │           │    │
│  │          │  <p>...</p>                    │           │    │
│  │          │                                │           │    │
│  └──────────┴────────────────────────────────┴───────────┘    │
│                                                                │
│  ┌────────────────────────────────────────────────────────┐    │
│  │              AUTHOR BIO CARD                           │    │
│  └────────────────────────────────────────────────────────┘    │
│                                                                │
│  ┌────────────────────────────────────────────────────────┐    │
│  │          RELATED ARTICLES (3-4 cards)                  │    │
│  └────────────────────────────────────────────────────────┘    │
│                                                                │
│  ┌────────────────────────────────────────────────────────┐    │
│  │              NEWSLETTER CTA FULL-WIDTH                 │    │
│  └────────────────────────────────────────────────────────┘    │
│                                                                │
├─────────────────────────────────────────────────────────────────┤
│                         FOOTER                                 │
└─────────────────────────────────────────────────────────────────┘
 
3.2 Reading Progress Bar
Barra de progreso fija en la parte superior que indica el avance de lectura.
3.2.1 Especificación Técnica
Propiedad	Valor	Notas
Posición	fixed, top: 0 (o debajo del header sticky)	z-index: 100
Altura	4px (desktop) / 3px (mobile)	Sutil pero visible
Color	var(--color-primary)	Usa color de marca del tenant
Animación	width transition 100ms ease-out	Smooth update
Cálculo	(scrollY / (docHeight - viewportHeight)) * 100	Porcentaje real
Trigger	Intersection Observer en article-content	Solo activa en contenido
3.2.2 React Component
// components/ReadingProgressBar.tsx
export function ReadingProgressBar() {
  const [progress, setProgress] = useState(0);
  const articleRef = useRef<HTMLElement>(null);

  useEffect(() => {
    const article = articleRef.current || document.querySelector(".article-content");
    if (!article) return;

    const updateProgress = () => {
      const rect = article.getBoundingClientRect();
      const articleTop = window.scrollY + rect.top;
      const articleHeight = rect.height;
      const viewportHeight = window.innerHeight;
      const scrolled = window.scrollY - articleTop + viewportHeight * 0.3;
      const total = articleHeight - viewportHeight * 0.7;
      const percent = Math.min(100, Math.max(0, (scrolled / total) * 100));
      setProgress(percent);
    };

    window.addEventListener("scroll", updateProgress, { passive: true });
    return () => window.removeEventListener("scroll", updateProgress);
  }, []);

  return (
    <div className="reading-progress" role="progressbar" aria-valuenow={progress}>
      <div className="reading-progress__bar" style={{ width: `${progress}%` }} />
    </div>
  );
}
3.3 Sticky Table of Contents
Navegación contextual que sigue al usuario y resalta la sección actual.
3.3.1 Comportamiento
•	Aparece cuando el usuario scrollea pasado el hero image
•	Se fija a la derecha del contenido (desktop) o colapsable (mobile)
•	Resalta el heading actual basado en Intersection Observer
•	Click en item hace smooth scroll a la sección
•	Muestra solo H2 y H3 para evitar clutter
3.3.2 React Component
// components/TableOfContents.tsx
interface TOCItem { id: string; text: string; level: number; }

export function TableOfContents({ headings }: { headings: TOCItem[] }) {
  const [activeId, setActiveId] = useState("");

  useEffect(() => {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) setActiveId(entry.target.id);
        });
      },
      { rootMargin: "-20% 0px -80% 0px" }
    );

    headings.forEach(({ id }) => {
      const el = document.getElementById(id);
      if (el) observer.observe(el);
    });

    return () => observer.disconnect();
  }, [headings]);

  return (
    <nav className="toc" aria-label="Tabla de contenidos">
      <h4 className="toc__title">En este artículo</h4>
      <ul className="toc__list">
        {headings.map(({ id, text, level }) => (
          <li key={id} className={`toc__item toc__item--h${level}`}>
            <a
              href={`#${id}`}
              className={activeId === id ? "toc__link--active" : "toc__link"}
              onClick={(e) => {
                e.preventDefault();
                document.getElementById(id)?.scrollIntoView({ behavior: "smooth" });
              }}
            >
              {text}
            </a>
          </li>
        ))}
      </ul>
    </nav>
  );
}
 
3.4 Social Share Sidebar
Barra lateral sticky con botones para compartir en redes sociales.
3.4.1 Botones Incluidos
Red	Icono	URL Pattern	Tracking
LinkedIn	linkedin	https://linkedin.com/shareArticle?url={url}&title={title}	utm_source=linkedin
Twitter/X	twitter	https://twitter.com/intent/tweet?url={url}&text={title}	utm_source=twitter
Facebook	facebook	https://facebook.com/sharer/sharer.php?u={url}	utm_source=facebook
WhatsApp	whatsapp	https://wa.me/?text={title}%20{url}	utm_source=whatsapp
Copy Link	link	navigator.clipboard.writeText(url)	N/A
Email	mail	mailto:?subject={title}&body={url}	utm_source=email
3.4.2 Comportamiento
•	Desktop: Sticky vertical a la izquierda del contenido
•	Mobile: Fixed bottom bar con iconos horizontales
•	Aparece después de 300px de scroll
•	Desaparece cuando llega a Related Articles
•	Copy Link muestra toast 'Enlace copiado!'
3.5 Answer Capsule Display
El Answer Capsule es un bloque destacado al inicio del artículo, optimizado para ser extraído por AI.
3.5.1 Diseño Visual
•	Background: var(--color-primary-light) con 10% opacity
•	Border-left: 4px solid var(--color-primary)
•	Padding: 20px
•	Font-size: 1.1em
•	Icono: Lightbulb o Quote antes del texto
•	Máximo 200 caracteres
3.5.2 Markup Semántico
<aside class="answer-capsule" role="note" aria-label="Resumen clave">
  <svg class="answer-capsule__icon" aria-hidden="true">...</svg>
  <p class="answer-capsule__text">
    {{ article.answer_capsule }}
  </p>
</aside>
 
3.6 Author Bio Card
Card al final del artículo presentando al autor.
3.6.1 Contenido
Elemento	Especificación	Acción
Avatar	80px circular, lazy-load	Click → perfil autor
Nombre	H4, font-weight 600	Click → perfil autor
Bio	Max 160 chars, 2 líneas	Expandible si más
Redes Sociales	Iconos LinkedIn, Twitter, Web	Open in new tab
CTA	Ver todos los artículos de {nombre}	Link a /author/{slug}
3.7 Related Articles Widget
Sección de contenido relacionado basada en el motor de recomendaciones.
3.7.1 Configuración
•	Cantidad: 3-4 artículos (3 desktop, 4 tablet con 2x2)
•	Layout: Grid horizontal de card-minimal
•	Fuente: API /recommendations con fallback a misma categoría
•	Ordenación: score DESC
•	Título sección: 'También te puede interesar' o 'Artículos relacionados'
3.7.2 Twig Template
{# templates/components/related-articles.html.twig #}
{% if related_articles|length > 0 %}
<section class="related-articles" aria-labelledby="related-heading">
  <h2 id="related-heading" class="related-articles__title">
    También te puede interesar
  </h2>
  <div class="related-articles__grid">
    {% for article in related_articles|slice(0, 4) %}
      {% include "components/article-card.html.twig" with {
        article: article,
        variant: "minimal"
      } %}
    {% endfor %}
  </div>
</section>
{% endif %}
 
4. Sidebar Components
4.1 Category Filter Widget
Widget para filtrar artículos por categoría.
4.1.1 Variantes
Variante	Uso	Comportamiento
Pills horizontales	Debajo del hero (mobile/tablet)	Scroll horizontal, filter on click
Lista vertical	Sidebar (desktop)	Expandible, muestra count
Dropdown	Mobile cuando muchas categorías	Select nativo + custom styling
4.1.2 React Component
// components/CategoryFilter.tsx
interface Category { id: string; name: string; slug: string; count: number; color: string; }

export function CategoryFilter({ 
  categories, 
  activeSlug, 
  onSelect 
}: { 
  categories: Category[];
  activeSlug: string | null;
  onSelect: (slug: string | null) => void;
}) {
  return (
    <nav className="category-filter" aria-label="Filtrar por categoría">
      <button
        className={`category-filter__pill ${!activeSlug ? "--active" : ""}`}
        onClick={() => onSelect(null)}
      >
        Todos
      </button>
      {categories.map((cat) => (
        <button
          key={cat.id}
          className={`category-filter__pill ${activeSlug === cat.slug ? "--active" : ""}`}
          style={{ "--cat-color": cat.color } as React.CSSProperties}
          onClick={() => onSelect(cat.slug)}
        >
          {cat.name}
          <span className="category-filter__count">({cat.count})</span>
        </button>
      ))}
    </nav>
  );
}
4.2 Newsletter Subscription Widget
CTA para captar suscriptores de newsletter.
4.2.1 Ubicaciones
•	Sidebar (sticky) - Versión compacta
•	Mid-article inline - Versión expandida con propuesta de valor
•	Footer full-width - Versión completa con campo nombre
•	Exit-intent popup - Con descuento/lead magnet
4.2.2 Estados del Formulario
Estado	UI	Mensaje
idle	Input + botón activo	Placeholder: 'tu@email.com'
loading	Botón con spinner, input disabled	Procesando...
success	Checkmark verde, animación confetti	¡Gracias! Revisa tu email.
error	Borde rojo, icono warning	Error específico del backend
already_subscribed	Info icon	Ya estás suscrito.
4.2.3 Validación
•	Email: Regex + DNS check backend
•	Honeypot field oculto para spam
•	Rate limit: 3 intentos por IP/minuto
•	Double opt-in: Siempre requerido
 
4.3 Trending Articles Widget
Lista de artículos más populares de los últimos 7 días.
4.3.1 Diseño
•	5 artículos máximo
•	Número grande a la izquierda (01, 02, 03...)
•	Título a la derecha (max 2 líneas)
•	Sin imagen para compactar
•	Hover: underline en título
4.3.2 Cálculo de Trending
// Fórmula de trending score
trending_score = (views_last_7d * 0.4) + 
                 (unique_visitors * 0.3) + 
                 (avg_time_on_page / 60 * 0.2) +
                 (social_shares * 0.1)

// Decay factor para contenido antiguo
final_score = trending_score * Math.pow(0.95, days_since_publish)
4.4 Search Widget
Búsqueda de artículos con autocompletado.
4.4.1 Comportamiento
•	Input con icono de búsqueda
•	Debounce 300ms antes de buscar
•	Dropdown de sugerencias (max 5)
•	Keyboard navigation (up/down/enter)
•	Highlight de match en sugerencias
•	Búsqueda semántica via Qdrant
4.4.2 React Component
// components/SearchWidget.tsx
export function SearchWidget() {
  const [query, setQuery] = useState("");
  const [suggestions, setSuggestions] = useState<Article[]>([]);
  const debouncedQuery = useDebounce(query, 300);

  useEffect(() => {
    if (debouncedQuery.length < 2) {
      setSuggestions([]);
      return;
    }
    fetch(`/api/v1/content/search?q=${encodeURIComponent(debouncedQuery)}`)
      .then(res => res.json())
      .then(data => setSuggestions(data.results.slice(0, 5)));
  }, [debouncedQuery]);

  return (
    <div className="search-widget" role="search">
      <input
        type="search"
        placeholder="Buscar artículos..."
        value={query}
        onChange={(e) => setQuery(e.target.value)}
        aria-label="Buscar artículos"
      />
      {suggestions.length > 0 && (
        <ul className="search-widget__suggestions">
          {suggestions.map((article) => (
            <li key={article.uuid}>
              <a href={article.url}>{article.title}</a>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
 
5. Sistema Responsivo
5.1 Breakpoints
Nombre	Min Width	Max Width	Uso Típico
xs	0	479px	Móviles pequeños
sm	480px	767px	Móviles grandes
md	768px	1023px	Tablets portrait
lg	1024px	1279px	Tablets landscape, laptops
xl	1280px	1535px	Desktop estándar
2xl	1536px	∞	Pantallas grandes
5.2 Grid Configuration
/* CSS Custom Properties para grid */
:root {
  --grid-columns: 1;
  --grid-gap: 16px;
  --content-max-width: 100%;
  --sidebar-width: 0;
}

@media (min-width: 768px) {
  :root {
    --grid-columns: 2;
    --grid-gap: 20px;
    --content-max-width: 720px;
  }
}

@media (min-width: 1024px) {
  :root {
    --grid-columns: 3;
    --grid-gap: 24px;
    --content-max-width: 960px;
    --sidebar-width: 280px;
  }
}

@media (min-width: 1280px) {
  :root {
    --content-max-width: 1140px;
    --sidebar-width: 320px;
  }
}
5.3 Typography Scale
Elemento	Mobile	Tablet	Desktop
H1 (article title)	28px / 1.2	36px / 1.2	48px / 1.1
H2 (section)	22px / 1.3	26px / 1.3	32px / 1.25
H3 (subsection)	18px / 1.4	20px / 1.4	24px / 1.35
Body	16px / 1.6	17px / 1.65	18px / 1.7
Caption	13px / 1.4	14px / 1.4	14px / 1.4
Card title	16px / 1.3	18px / 1.3	20px / 1.3
Card excerpt	14px / 1.5	14px / 1.5	15px / 1.5
 
6. Micro-interacciones y Animaciones
6.1 Hover States
Elemento	Efecto	Duración	Easing
Card image	scale(1.05) + overlay opacity	300ms	ease-out
Card	box-shadow elevación + translateY(-4px)	200ms	ease-out
Button primary	background-color darken 10%	150ms	ease
Link	color + underline slide-in	200ms	ease
Icon button	background-color + scale(1.1)	150ms	ease
Category pill	background-color + border-color	150ms	ease
6.2 Loading States
Contexto	Animación	Implementación
Card skeleton	Shimmer gradient animado	CSS animation + pseudo-element
Button loading	Spinner circular reemplaza texto	SVG rotate infinite
Image lazy-load	Blur → focus transition	CSS filter + opacity
Page transition	Fade out → fade in	Framer Motion AnimatePresence
Infinite scroll	3 skeleton cards	Intersection Observer trigger
6.3 Success/Error Feedback
Acción	Feedback Visual	Feedback Adicional
Newsletter subscribe	Checkmark animation + confetti	Toast 'Revisa tu email'
Copy link	Icon → checkmark	Toast 'Copiado!'
Bookmark article	Heart fill animation	Toast 'Guardado'
Form error	Shake animation + red border	Error message inline
Rate limit	Disabled state + countdown	Toast con tiempo restante
6.4 Scroll-based Animations
•	Reading progress bar: Update continuo con scroll
•	TOC active state: Highlight section actual
•	Social share: Fade in/out basado en posición
•	Back to top: Aparece después de 500px scroll
•	Lazy images: Fade in cuando entran en viewport
 
7. Accesibilidad (WCAG 2.1 AA)
7.1 Requisitos Obligatorios
Criterio	Requisito	Implementación
1.1.1 Non-text Content	Alt text en todas las imágenes	alt obligatorio, decorativas alt=''
1.3.1 Info & Relationships	Estructura semántica	article, nav, main, aside, header
1.4.3 Contrast	Ratio mínimo 4.5:1 para texto	Verificar con Colour Contrast Checker
1.4.4 Resize Text	Funcional hasta 200% zoom	rem/em units, no fixed heights
2.1.1 Keyboard	Todo accesible por teclado	tabindex, focus states visibles
2.4.1 Bypass Blocks	Skip links	<a href='#main' class='skip-link'>
2.4.4 Link Purpose	Texto de enlace descriptivo	No 'click aquí', contexto claro
2.4.7 Focus Visible	Indicador de focus visible	outline: 2px solid, offset 2px
3.1.1 Language	lang attribute en html	<html lang='es'>
4.1.2 Name, Role, Value	ARIA labels donde necesario	aria-label, aria-labelledby
7.2 Landmarks ARIA
<body>
  <a href="#main" class="skip-link">Saltar al contenido</a>
  
  <header role="banner">
    <nav role="navigation" aria-label="Principal">...</nav>
  </header>
  
  <main id="main" role="main">
    <article>
      <header><!-- article header --></header>
      <div class="article-content">...</div>
      <footer><!-- article footer --></footer>
    </article>
  </main>
  
  <aside role="complementary" aria-label="Barra lateral">
    <nav aria-label="Tabla de contenidos">...</nav>
    <section aria-label="Newsletter">...</section>
  </aside>
  
  <footer role="contentinfo">...</footer>
</body>
7.3 Focus Management
•	Focus trap en modales y dropdowns
•	Restore focus al cerrar modal
•	Smooth scroll con reduced-motion respect
•	Focus visible con outline consistente
•	Skip links al inicio del documento
 
8. Performance Optimization
8.1 Core Web Vitals Targets
Métrica	Target	Actual Objetivo	Estrategia
LCP	< 2.5s	< 1.8s	Image optimization, critical CSS
FID	< 100ms	< 50ms	Code splitting, defer non-critical JS
CLS	< 0.1	< 0.05	Aspect ratios, skeleton loaders
TTFB	< 600ms	< 400ms	Server caching, CDN
TTI	< 3.8s	< 2.5s	Lazy loading, progressive enhancement
8.2 Image Optimization
Técnica	Implementación	Ahorro Estimado
WebP format	Picture element con fallback	25-35% vs JPEG
Lazy loading	loading='lazy' + Intersection Observer	Initial payload -50%
Responsive srcset	Multiple sizes por breakpoint	Mobile bandwidth -40%
Aspect ratio CSS	aspect-ratio: 16/9 para reservar espacio	CLS = 0
Blur placeholder	LQIP 20px base64 inline	Perceived performance ++
CDN with transforms	IONOS CDN o Cloudflare	Cache + edge delivery
8.3 JavaScript Strategy
•	Critical JS inline en head (reading progress init)
•	Main bundle deferred
•	React components lazy-loaded via dynamic import
•	Third-party scripts (analytics) con async
•	Service worker para offline reading (PWA)
8.4 CSS Strategy
•	Critical CSS inline para above-the-fold
•	Rest of CSS loaded async
•	Tailwind purge para eliminar unused classes
•	CSS custom properties para theming (no rebuilds)
•	No CSS-in-JS en runtime (solo build time)
 
9. Theming Multi-Tenant
9.1 CSS Custom Properties por Tenant
/* Base theme (loaded always) */
:root {
  /* Colors */
  --color-primary: #F37021;
  --color-primary-light: #FFF5F0;
  --color-primary-dark: #D45A10;
  --color-secondary: #00B4AA;
  --color-text: #1A1A2E;
  --color-text-muted: #6B7280;
  --color-background: #FFFFFF;
  --color-surface: #F9FAFB;
  --color-border: #E5E7EB;
  
  /* Typography */
  --font-family-heading: "Inter", system-ui, sans-serif;
  --font-family-body: "Inter", system-ui, sans-serif;
  
  /* Spacing */
  --spacing-xs: 4px;
  --spacing-sm: 8px;
  --spacing-md: 16px;
  --spacing-lg: 24px;
  --spacing-xl: 32px;
  --spacing-2xl: 48px;
  
  /* Border radius */
  --radius-sm: 4px;
  --radius-md: 8px;
  --radius-lg: 12px;
  --radius-full: 9999px;
  
  /* Shadows */
  --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
  --shadow-md: 0 4px 6px rgba(0,0,0,0.07);
  --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
}
9.2 Tenant Overrides
/* Tenant: AgroConecta */
[data-tenant="agroconecta"] {
  --color-primary: #4CAF50;
  --color-primary-light: #E8F5E9;
  --color-primary-dark: #388E3C;
  --color-secondary: #8BC34A;
}

/* Tenant: ComercioConecta */
[data-tenant="comercioconecta"] {
  --color-primary: #FF5722;
  --color-primary-light: #FBE9E7;
  --color-primary-dark: #E64A19;
  --color-secondary: #FF9800;
}

/* Tenant: ServiciosConecta */
[data-tenant="serviciosconecta"] {
  --color-primary: #2196F3;
  --color-primary-light: #E3F2FD;
  --color-primary-dark: #1976D2;
  --color-secondary: #03A9F4;
}
9.3 Dark Mode Support
@media (prefers-color-scheme: dark) {
  :root {
    --color-text: #F9FAFB;
    --color-text-muted: #9CA3AF;
    --color-background: #111827;
    --color-surface: #1F2937;
    --color-border: #374151;
  }
}

/* Manual toggle override */
[data-theme="dark"] {
  --color-text: #F9FAFB;
  --color-text-muted: #9CA3AF;
  --color-background: #111827;
  --color-surface: #1F2937;
  --color-border: #374151;
}
 
10. Roadmap de Implementación Frontend
10.1 Sprint 2B: Blog Homepage (Semanas 3-4)
Horas estimadas: 40-50h adicionales al Sprint 2 backend
•	Template Twig: blog-homepage.html.twig
•	Component: ArticleCard (4 variantes)
•	Component: CategoryFilter (pills + sidebar)
•	Layout: CSS Grid responsivo con masonry option
•	Infinite scroll / Load more implementation
•	Skeleton loaders
Entregable: Homepage del blog funcional y responsiva
10.2 Sprint 3B: Article Page (Semanas 5-6)
Horas estimadas: 50-60h adicionales al Sprint 3 backend
•	Template Twig: article-full.html.twig
•	Component: ReadingProgressBar
•	Component: TableOfContents (sticky)
•	Component: SocialShare (sidebar + mobile)
•	Component: AuthorBioCard
•	Component: RelatedArticles
•	Answer Capsule styling
•	Typography y spacing refinado
Entregable: Páginas de artículo con todos los componentes UX
10.3 Sprint 4B: Widgets y Newsletter UI (Semanas 7-8)
Horas estimadas: 30-40h adicionales al Sprint 4 backend
•	Component: NewsletterWidget (3 variantes)
•	Component: TrendingArticles
•	Component: SearchWidget con autocomplete
•	Form validation y feedback states
•	Toast notifications system
•	Exit-intent popup
Entregable: Todos los widgets sidebar funcionales
10.4 Sprint 6B: Polish y Performance (Semanas 11-12)
Horas estimadas: 30-40h adicionales al Sprint 6 backend
•	Micro-interacciones y animaciones
•	Accessibility audit y fixes
•	Performance optimization (images, JS, CSS)
•	Multi-tenant theming implementation
•	Dark mode support
•	Cross-browser testing
•	Mobile testing en dispositivos reales
Entregable: Frontend pulido, accesible y performante
10.5 Resumen de Inversión Frontend
Sprint	Semanas	Horas Frontend	Costo (€80/h)
Sprint 2B: Homepage	3-4	40-50h	€3,200-4,000
Sprint 3B: Article Page	5-6	50-60h	€4,000-4,800
Sprint 4B: Widgets	7-8	30-40h	€2,400-3,200
Sprint 6B: Polish	11-12	30-40h	€2,400-3,200
TOTAL FRONTEND	-	150-190h	€12,000-15,200
Inversión Total (Backend + Frontend): 440-540h, €35,200-43,200

--- Fin del Documento ---

Jaraba Impact Platform | 128b_AI_Content_Hub_Frontend_v1 | Enero 2026
