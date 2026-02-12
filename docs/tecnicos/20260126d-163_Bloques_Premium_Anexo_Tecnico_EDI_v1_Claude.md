163
ANEXO TÉCNICO
Bloques Premium: Código de Implementación
Templates Twig + JavaScript Adapters + CSS Design Tokens
Ecosistema Jaraba | EDI Google Antigravity
Versión:	1.0.0
Fecha:	26 de Enero de 2026
Autor:	Equipo Técnico Jaraba
 
Índice de Contenidos
1. Templates Twig - Bloques Base Nativos
   1.1. Hero Fullscreen
   1.2. Features Grid
   1.3. Stats Counter
2. Templates Twig - Aceternity UI
   2.1. Spotlight Effect
   2.2. 3D Card Effect
3. Templates Twig - Magic UI
   3.1. Bento Grid
   3.2. Marquee
4. JavaScript Adapters
   4.1. Aceternity Adapter
   4.2. Magic UI Adapter
5. CSS Design Tokens
   5.1. Sistema de Variables CSS
   5.2. Estilos Aceternity
   5.3. Estilos Magic UI
 
1. Templates Twig - Bloques Base Nativos
Esta sección contiene los templates Twig para los bloques base del constructor de páginas. Cada template incluye soporte completo para AOS animations, design tokens, y configuración dinámica desde el Form Builder.
1.1. Hero Fullscreen
Bloque hero de pantalla completa con imagen de fondo, overlay configurable, CTAs primario y secundario, badges, y logos de confianza.
 hero-fullscreen.html.twig
{# hero-fullscreen.html.twig #}
{# Bloque Hero Fullscreen con imagen de fondo, overlay y CTAs #}
 
{% set block_id = 'hero-' ~ block.id %}
{% set bg_style = block.background_image ? 'background-image: url(' ~ file_url(block.background_image) ~ ');' : '' %}
 
<section 
  id="{{ block_id }}"
  class="jaraba-hero jaraba-hero--fullscreen"
  style="{{ bg_style }}"
  data-aos="fade-up"
  data-aos-duration="800"
>
  {# Overlay con opacidad configurable #}
  <div 
    class="jaraba-hero__overlay"
    style="--overlay-opacity: {{ block.overlay_opacity|default(0.5) }};"
  ></div>
  
  <div class="jaraba-hero__container container">
    <div class="jaraba-hero__content jaraba-hero__content--{{ block.text_alignment|default('center') }}">
      
      {# Badge opcional #}
      {% if block.badge_text %}
        <span class="jaraba-hero__badge" data-aos="fade-down" data-aos-delay="100">
          {{ block.badge_text }}
        </span>
      {% endif %}
      
      {# Headline principal #}
      <h1 class="jaraba-hero__title" data-aos="fade-up" data-aos-delay="200">
        {{ block.headline }}
      </h1>
      
      {# Subtitulo #}
      {% if block.subheadline %}
        <p class="jaraba-hero__subtitle" data-aos="fade-up" data-aos-delay="300">
          {{ block.subheadline }}
        </p>
      {% endif %}
      
      {# CTAs #}
      {% if block.cta_primary_text or block.cta_secondary_text %}
        <div class="jaraba-hero__ctas" data-aos="fade-up" data-aos-delay="400">
          {% if block.cta_primary_text %}
            <a 
              href="{{ block.cta_primary_url }}" 
              class="jaraba-btn jaraba-btn--primary jaraba-btn--lg"
            >
              {{ block.cta_primary_text }}
              {% if block.cta_primary_icon %}
                <svg class="jaraba-btn__icon">
                  <use xlink:href="#icon-{{ block.cta_primary_icon }}"></use>
                </svg>
              {% endif %}
            </a>
          {% endif %}
          
          {% if block.cta_secondary_text %}
            <a 
              href="{{ block.cta_secondary_url }}" 
              class="jaraba-btn jaraba-btn--outline jaraba-btn--lg"
            >
              {{ block.cta_secondary_text }}
            </a>
          {% endif %}
        </div>
      {% endif %}
      
      {# Trust Logos #}
      {% if block.trust_logos %}
        <div class="jaraba-hero__trust" data-aos="fade-up" data-aos-delay="500">
          <span class="jaraba-hero__trust-label">{{ block.trust_label|default('Confían en nosotros:') }}</span>
          <div class="jaraba-hero__trust-logos">
            {% for logo in block.trust_logos %}
              <img 
                src="{{ file_url(logo.image) }}" 
                alt="{{ logo.alt }}"
                class="jaraba-hero__trust-logo"
                loading="lazy"
              />
            {% endfor %}
          </div>
        </div>
      {% endif %}
      
    </div>
  </div>
  
  {# Scroll indicator #}
  {% if block.show_scroll_indicator %}
    <div class="jaraba-hero__scroll-indicator">
      <span class="jaraba-hero__scroll-text">Scroll</span>
      <div class="jaraba-hero__scroll-arrow"></div>
    </div>
  {% endif %}
</section>

1.2. Features Grid
Grid de características responsive con iconos, descripciones, bullet points y enlaces opcionales. Soporta 2, 3 o 4 columnas.
 features-grid.html.twig
{# features-grid.html.twig #}
{# Grid de características con iconos y hover effects #}
 
{% set block_id = 'features-' ~ block.id %}
{% set columns = block.columns|default(3) %}
 
<section 
  id="{{ block_id }}"
  class="jaraba-features jaraba-features--grid"
>
  <div class="container">
    {# Header de sección #}
    {% if block.section_title or block.section_subtitle %}
      <header class="jaraba-features__header" data-aos="fade-up">
        {% if block.section_badge %}
          <span class="jaraba-section-badge">{{ block.section_badge }}</span>
        {% endif %}
        
        {% if block.section_title %}
          <h2 class="jaraba-features__title">{{ block.section_title }}</h2>
        {% endif %}
        
        {% if block.section_subtitle %}
          <p class="jaraba-features__subtitle">{{ block.section_subtitle }}</p>
        {% endif %}
      </header>
    {% endif %}
    
    {# Grid de features #}
    <div class="jaraba-features__grid jaraba-features__grid--cols-{{ columns }}">
      {% for feature in block.features %}
        <article 
          class="jaraba-feature-card"
          data-aos="fade-up"
          data-aos-delay="{{ loop.index0 * 100 }}"
        >
          {# Icono #}
          {% if feature.icon %}
            <div class="jaraba-feature-card__icon-wrapper">
              <div class="jaraba-feature-card__icon jaraba-feature-card__icon--{{ feature.icon_style|default('default') }}">
                {% if feature.icon_type == 'svg' %}
                  {{ feature.icon_svg|raw }}
                {% elseif feature.icon_type == 'lucide' %}
                  <svg class="lucide-icon">
                    <use xlink:href="#lucide-{{ feature.icon }}"></use>
                  </svg>
                {% else %}
                  <i class="icon-{{ feature.icon }}"></i>
                {% endif %}
              </div>
            </div>
          {% endif %}
          
          {# Contenido #}
          <div class="jaraba-feature-card__content">
            <h3 class="jaraba-feature-card__title">{{ feature.title }}</h3>
            
            {% if feature.description %}
              <p class="jaraba-feature-card__description">{{ feature.description }}</p>
            {% endif %}
            
            {# Lista de puntos #}
            {% if feature.bullet_points %}
              <ul class="jaraba-feature-card__list">
                {% for point in feature.bullet_points %}
                  <li>{{ point }}</li>
                {% endfor %}
              </ul>
            {% endif %}
            
            {# Link opcional #}
            {% if feature.link_url %}
              <a href="{{ feature.link_url }}" class="jaraba-feature-card__link">
                {{ feature.link_text|default('Saber más') }}
                <svg class="jaraba-feature-card__link-arrow">
                  <use xlink:href="#icon-arrow-right"></use>
                </svg>
              </a>
            {% endif %}
          </div>
        </article>
      {% endfor %}
    </div>
    
    {# CTA de sección #}
    {% if block.section_cta_text %}
      <div class="jaraba-features__cta" data-aos="fade-up">
        <a href="{{ block.section_cta_url }}" class="jaraba-btn jaraba-btn--primary">
          {{ block.section_cta_text }}
        </a>
      </div>
    {% endif %}
  </div>
</section>

1.3. Stats Counter
Estadísticas con contador animado que se activa al hacer scroll. Soporta formatos de número, porcentaje, moneda y decimal.
 stats-counter.html.twig
{# stats-counter.html.twig #}
{# Estadísticas con contador animado on scroll #}
 
{% set block_id = 'stats-' ~ block.id %}
 
<section 
  id="{{ block_id }}"
  class="jaraba-stats jaraba-stats--{{ block.layout|default('inline') }}"
  style="--stats-bg: {{ block.background_color|default('var(--color-surface-elevated)') }};"
>
  <div class="container">
    {% if block.section_title %}
      <header class="jaraba-stats__header" data-aos="fade-up">
        <h2 class="jaraba-stats__title">{{ block.section_title }}</h2>
        {% if block.section_subtitle %}
          <p class="jaraba-stats__subtitle">{{ block.section_subtitle }}</p>
        {% endif %}
      </header>
    {% endif %}
    
    <div class="jaraba-stats__grid">
      {% for stat in block.stats %}
        <div 
          class="jaraba-stat-item"
          data-aos="zoom-in"
          data-aos-delay="{{ loop.index0 * 150 }}"
        >
          {# Número con contador animado #}
          <div class="jaraba-stat-item__value">
            {% if stat.prefix %}
              <span class="jaraba-stat-item__prefix">{{ stat.prefix }}</span>
            {% endif %}
            
            <span 
              class="jaraba-stat-item__number js-counter"
              data-target="{{ stat.value }}"
              data-duration="{{ stat.animation_duration|default(2000) }}"
              data-format="{{ stat.format|default('number') }}"
            >
              0
            </span>
            
            {% if stat.suffix %}
              <span class="jaraba-stat-item__suffix">{{ stat.suffix }}</span>
            {% endif %}
          </div>
          
          {# Label #}
          <div class="jaraba-stat-item__label">{{ stat.label }}</div>
          
          {# Descripción opcional #}
          {% if stat.description %}
            <p class="jaraba-stat-item__description">{{ stat.description }}</p>
          {% endif %}
        </div>
      {% endfor %}
    </div>
  </div>
</section>
 
{# JavaScript para contador animado #}
{{ attach_library('jaraba_page_builder/stats-counter') }}

 
2. Templates Twig - Aceternity UI
Templates para los componentes premium de Aceternity UI. Estos componentes utilizan efectos avanzados como cursor-following spotlight, 3D tilt effects, y animaciones Framer Motion.
2.1. Spotlight Effect
Efecto spotlight que sigue el cursor del usuario. Crea un foco de luz suave que realza el contenido bajo el mouse.
 aceternity/spotlight-effect.html.twig
{# aceternity/spotlight-effect.html.twig #}
{# Efecto Spotlight que sigue el cursor - Aceternity UI #}
 
{% set block_id = 'spotlight-' ~ block.id %}
 
<section 
  id="{{ block_id }}"
  class="jaraba-aceternity-spotlight"
  data-spotlight-config='{{ {
    "color": block.spotlight_color|default("rgba(120, 119, 198, 0.3)"),
    "size": block.spotlight_size|default(400),
    "blur": block.spotlight_blur|default(100)
  }|json_encode }}'
>
  {# Canvas para efecto spotlight #}
  <div class="jaraba-spotlight__canvas" data-spotlight-canvas></div>
  
  {# Contenido principal #}
  <div class="jaraba-spotlight__content container">
    <div class="jaraba-spotlight__inner">
      
      {% if block.badge %}
        <span class="jaraba-spotlight__badge">{{ block.badge }}</span>
      {% endif %}
      
      <h2 class="jaraba-spotlight__title">
        {{ block.title }}
      </h2>
      
      {% if block.description %}
        <p class="jaraba-spotlight__description">
          {{ block.description }}
        </p>
      {% endif %}
      
      {% if block.cta_text %}
        <a href="{{ block.cta_url }}" class="jaraba-btn jaraba-btn--glow">
          {{ block.cta_text }}
        </a>
      {% endif %}
      
    </div>
  </div>
  
  {# Gradiente de fondo base #}
  <div class="jaraba-spotlight__bg-gradient"></div>
</section>
 
<style>
  #{{ block_id }} {
    --spotlight-primary: {{ block.spotlight_color|default("rgba(120, 119, 198, 0.3)") }};
    --spotlight-size: {{ block.spotlight_size|default(400) }}px;
    --spotlight-blur: {{ block.spotlight_blur|default(100) }}px;
  }
</style>
 
{{ attach_library('jaraba_page_builder/aceternity-spotlight') }}

2.2. 3D Card Effect
Tarjetas con efecto 3D tilt al hover. Incluye capas con diferentes profundidades (translateZ) y efecto glare opcional.
 aceternity/3d-card-effect.html.twig
{# aceternity/3d-card-effect.html.twig #}
{# Tarjeta 3D con efecto tilt al hover - Aceternity UI #}
 
{% set block_id = 'card3d-' ~ block.id %}
 
<section id="{{ block_id }}" class="jaraba-aceternity-3dcard-section">
  <div class="container">
    
    {% if block.section_title %}
      <header class="jaraba-section-header" data-aos="fade-up">
        <h2>{{ block.section_title }}</h2>
        {% if block.section_subtitle %}
          <p>{{ block.section_subtitle }}</p>
        {% endif %}
      </header>
    {% endif %}
    
    <div class="jaraba-3dcard-grid">
      {% for card in block.cards %}
        <div 
          class="jaraba-3dcard-container"
          data-aos="fade-up"
          data-aos-delay="{{ loop.index0 * 100 }}"
        >
          <div 
            class="jaraba-3dcard"
            data-tilt
            data-tilt-max="{{ card.tilt_max|default(15) }}"
            data-tilt-speed="{{ card.tilt_speed|default(400) }}"
            data-tilt-perspective="{{ card.tilt_perspective|default(1000) }}"
            data-tilt-glare="{{ card.enable_glare|default(true) ? 'true' : 'false' }}"
            data-tilt-max-glare="{{ card.max_glare|default(0.3) }}"
          >
            {# Capa de fondo con profundidad #}
            <div class="jaraba-3dcard__bg" style="transform: translateZ(-50px);"></div>
            
            {# Contenido principal #}
            <div class="jaraba-3dcard__content" style="transform: translateZ(50px);">
              
              {% if card.image %}
                <div class="jaraba-3dcard__image-wrapper" style="transform: translateZ(75px);">
                  <img 
                    src="{{ file_url(card.image) }}" 
                    alt="{{ card.image_alt|default(card.title) }}"
                    class="jaraba-3dcard__image"
                    loading="lazy"
                  />
                </div>
              {% endif %}
              
              <div class="jaraba-3dcard__text" style="transform: translateZ(60px);">
                {% if card.icon %}
                  <div class="jaraba-3dcard__icon">
                    <svg><use xlink:href="#icon-{{ card.icon }}"></use></svg>
                  </div>
                {% endif %}
                
                <h3 class="jaraba-3dcard__title">{{ card.title }}</h3>
                
                {% if card.description %}
                  <p class="jaraba-3dcard__description">{{ card.description }}</p>
                {% endif %}
                
                {% if card.cta_text %}
                  <a 
                    href="{{ card.cta_url }}" 
                    class="jaraba-3dcard__cta"
                    style="transform: translateZ(80px);"
                  >
                    {{ card.cta_text }}
                    <span class="jaraba-3dcard__cta-arrow">→</span>
                  </a>
                {% endif %}
              </div>
            </div>
            
            {# Efecto glow en hover #}
            <div class="jaraba-3dcard__glow"></div>
          </div>
        </div>
      {% endfor %}
    </div>
    
  </div>
</section>
 
{{ attach_library('jaraba_page_builder/aceternity-3dcard') }}

 
3. Templates Twig - Magic UI
Templates para los componentes de Magic UI. Incluyen micro-interacciones, efectos shine, y animaciones CSS puras para mejor rendimiento.
3.1. Bento Grid
Grid asimétrico estilo Apple/Bento con diferentes tamaños de items (large, medium, wide, tall). Incluye efecto shine al hover.
 magicui/bento-grid.html.twig
{# magicui/bento-grid.html.twig #}
{# Bento Grid asimétrico estilo Apple - Magic UI #}
 
{% set block_id = 'bento-' ~ block.id %}
 
<section id="{{ block_id }}" class="jaraba-magicui-bento">
  <div class="container">
    
    {% if block.section_title %}
      <header class="jaraba-section-header" data-aos="fade-up">
        {% if block.section_badge %}
          <span class="jaraba-section-badge">{{ block.section_badge }}</span>
        {% endif %}
        <h2>{{ block.section_title }}</h2>
        {% if block.section_subtitle %}
          <p>{{ block.section_subtitle }}</p>
        {% endif %}
      </header>
    {% endif %}
    
    <div class="jaraba-bento-grid">
      {% for item in block.items %}
        {% set size_class = item.size|default('medium') %}
        
        <article 
          class="jaraba-bento-item jaraba-bento-item--{{ size_class }}"
          data-aos="fade-up"
          data-aos-delay="{{ loop.index0 * 50 }}"
          style="
            --bento-bg: {{ item.background_color|default('var(--color-surface-card)') }};
            --bento-accent: {{ item.accent_color|default('var(--color-primary)') }};
          "
        >
          {# Efecto shine en hover #}
          <div class="jaraba-bento-item__shine"></div>
          
          {# Contenido #}
          <div class="jaraba-bento-item__content">
            
            {% if item.icon %}
              <div class="jaraba-bento-item__icon">
                <svg><use xlink:href="#icon-{{ item.icon }}"></use></svg>
              </div>
            {% endif %}
            
            {% if item.image %}
              <div class="jaraba-bento-item__image">
                <img 
                  src="{{ file_url(item.image) }}" 
                  alt="{{ item.image_alt|default(item.title) }}"
                  loading="lazy"
                />
              </div>
            {% endif %}
            
            <div class="jaraba-bento-item__text">
              <h3 class="jaraba-bento-item__title">{{ item.title }}</h3>
              
              {% if item.description %}
                <p class="jaraba-bento-item__description">{{ item.description }}</p>
              {% endif %}
              
              {% if item.stats %}
                <div class="jaraba-bento-item__stats">
                  {% for stat in item.stats %}
                    <div class="jaraba-bento-stat">
                      <span class="jaraba-bento-stat__value">{{ stat.value }}</span>
                      <span class="jaraba-bento-stat__label">{{ stat.label }}</span>
                    </div>
                  {% endfor %}
                </div>
              {% endif %}
              
              {% if item.cta_text %}
                <a href="{{ item.cta_url }}" class="jaraba-bento-item__cta">
                  {{ item.cta_text }}
                  <svg class="jaraba-bento-item__cta-icon"><use xlink:href="#icon-arrow-right"></use></svg>
                </a>
              {% endif %}
            </div>
          </div>
          
          {# Gradiente decorativo #}
          {% if item.show_gradient %}
            <div class="jaraba-bento-item__gradient"></div>
          {% endif %}
        </article>
      {% endfor %}
    </div>
    
  </div>
</section>
 
{{ attach_library('jaraba_page_builder/magicui-bento') }}

3.2. Marquee
Carrusel infinito con scroll horizontal seamless. Soporta logos, testimonios y cards. Pausa automática en hover.
 magicui/marquee.html.twig
{# magicui/marquee.html.twig #}
{# Marquee infinito con pausa en hover - Magic UI #}
 
{% set block_id = 'marquee-' ~ block.id %}
{% set direction = block.direction|default('left') %}
{% set speed = block.speed|default(30) %}
 
<section 
  id="{{ block_id }}" 
  class="jaraba-magicui-marquee"
  style="--marquee-speed: {{ speed }}s; --marquee-direction: {{ direction == 'right' ? 'reverse' : 'normal' }};"
>
  
  {% if block.section_title %}
    <header class="jaraba-section-header container" data-aos="fade-up">
      <h2>{{ block.section_title }}</h2>
    </header>
  {% endif %}
  
  <div 
    class="jaraba-marquee-wrapper"
    data-marquee
    data-pause-on-hover="{{ block.pause_on_hover|default(true) ? 'true' : 'false' }}"
  >
    {# Track principal #}
    <div class="jaraba-marquee-track">
      {# Contenido original #}
      <div class="jaraba-marquee-content">
        {% for item in block.items %}
          <div class="jaraba-marquee-item">
            {% if item.type == 'logo' %}
              <img 
                src="{{ file_url(item.image) }}" 
                alt="{{ item.alt }}"
                class="jaraba-marquee-logo"
              />
            {% elseif item.type == 'testimonial' %}
              <blockquote class="jaraba-marquee-testimonial">
                <p>"{{ item.quote }}"</p>
                <footer>
                  {% if item.avatar %}
                    <img src="{{ file_url(item.avatar) }}" alt="{{ item.author }}" class="jaraba-marquee-avatar" />
                  {% endif %}
                  <cite>
                    <span class="jaraba-marquee-author">{{ item.author }}</span>
                    {% if item.role %}
                      <span class="jaraba-marquee-role">{{ item.role }}</span>
                    {% endif %}
                  </cite>
                </footer>
              </blockquote>
            {% elseif item.type == 'card' %}
              <div class="jaraba-marquee-card">
                {% if item.icon %}
                  <div class="jaraba-marquee-card__icon">
                    <svg><use xlink:href="#icon-{{ item.icon }}"></use></svg>
                  </div>
                {% endif %}
                <h4>{{ item.title }}</h4>
                {% if item.description %}
                  <p>{{ item.description }}</p>
                {% endif %}
              </div>
            {% endif %}
          </div>
        {% endfor %}
      </div>
      
      {# Contenido duplicado para loop infinito seamless #}
      <div class="jaraba-marquee-content" aria-hidden="true">
        {% for item in block.items %}
          <div class="jaraba-marquee-item">
            {% if item.type == 'logo' %}
              <img 
                src="{{ file_url(item.image) }}" 
                alt="{{ item.alt }}"
                class="jaraba-marquee-logo"
              />
            {% elseif item.type == 'testimonial' %}
              <blockquote class="jaraba-marquee-testimonial">
                <p>"{{ item.quote }}"</p>
                <footer>
                  {% if item.avatar %}
                    <img src="{{ file_url(item.avatar) }}" alt="{{ item.author }}" class="jaraba-marquee-avatar" />
                  {% endif %}
                  <cite>
                    <span class="jaraba-marquee-author">{{ item.author }}</span>
                    {% if item.role %}
                      <span class="jaraba-marquee-role">{{ item.role }}</span>
                    {% endif %}
                  </cite>
                </footer>
              </blockquote>
            {% elseif item.type == 'card' %}
              <div class="jaraba-marquee-card">
                {% if item.icon %}
                  <div class="jaraba-marquee-card__icon">
                    <svg><use xlink:href="#icon-{{ item.icon }}"></use></svg>
                  </div>
                {% endif %}
                <h4>{{ item.title }}</h4>
                {% if item.description %}
                  <p>{{ item.description }}</p>
                {% endif %}
              </div>
            {% endif %}
          </div>
        {% endfor %}
      </div>
    </div>
    
    {# Máscaras de fade en los bordes #}
    <div class="jaraba-marquee-mask jaraba-marquee-mask--left"></div>
    <div class="jaraba-marquee-mask jaraba-marquee-mask--right"></div>
  </div>
</section>
 
{{ attach_library('jaraba_page_builder/magicui-marquee') }}

 
4. JavaScript Adapters
Adaptadores JavaScript que integran las librerías de componentes UI con el sistema Drupal behaviors. Utilizan el patrón once() para evitar múltiples inicializaciones y soportan AJAX/BigPipe.
4.1. Aceternity Adapter
Adapter completo para todos los componentes Aceternity UI incluyendo Spotlight, 3D Card, Text Reveal e Infinite Cards.
 js/aceternity-adapter.js
/**
 * aceternity-adapter.js
 * Adaptador JavaScript para componentes Aceternity UI en Drupal
 * 
 * @package jaraba_page_builder
 * @version 1.0.0
 */
 
(function (Drupal, once) {
  'use strict';
 
  /**
   * Namespace para componentes Aceternity
   */
  Drupal.aceternityUI = Drupal.aceternityUI || {};
 
  // ============================================================
  // SPOTLIGHT EFFECT
  // ============================================================
  
  Drupal.aceternityUI.initSpotlight = function (container) {
    const canvas = container.querySelector('[data-spotlight-canvas]');
    if (!canvas) return;
 
    const config = JSON.parse(container.dataset.spotlightConfig || '{}');
    const ctx = canvas.getContext ? null : null; // Canvas 2D alternativo
    
    let mouseX = 0;
    let mouseY = 0;
    let currentX = 0;
    let currentY = 0;
    
    // Configuración por defecto
    const settings = {
      color: config.color || 'rgba(120, 119, 198, 0.3)',
      size: config.size || 400,
      blur: config.blur || 100,
      easing: 0.08
    };
    
    // Crear elemento de spotlight con CSS
    const spotlight = document.createElement('div');
    spotlight.className = 'jaraba-spotlight__beam';
    spotlight.style.cssText = `
      position: absolute;
      width: ${settings.size}px;
      height: ${settings.size}px;
      border-radius: 50%;
      background: radial-gradient(circle, ${settings.color} 0%, transparent 70%);
      filter: blur(${settings.blur}px);
      pointer-events: none;
      transform: translate(-50%, -50%);
      opacity: 0;
      transition: opacity 0.3s ease;
    `;
    canvas.appendChild(spotlight);
    
    // Event listeners
    container.addEventListener('mouseenter', () => {
      spotlight.style.opacity = '1';
    });
    
    container.addEventListener('mouseleave', () => {
      spotlight.style.opacity = '0';
    });
    
    container.addEventListener('mousemove', (e) => {
      const rect = container.getBoundingClientRect();
      mouseX = e.clientX - rect.left;
      mouseY = e.clientY - rect.top;
    });
    
    // Animation loop con easing suave
    function animate() {
      currentX += (mouseX - currentX) * settings.easing;
      currentY += (mouseY - currentY) * settings.easing;
      
      spotlight.style.left = currentX + 'px';
      spotlight.style.top = currentY + 'px';
      
      requestAnimationFrame(animate);
    }
    
    animate();
  };
 
  // ============================================================
  // 3D CARD EFFECT
  // ============================================================
  
  Drupal.aceternityUI.init3DCard = function (card) {
    // Usar VanillaTilt si está disponible, sino implementación propia
    if (typeof VanillaTilt !== 'undefined') {
      VanillaTilt.init(card);
      return;
    }
    
    // Implementación propia de tilt
    const config = {
      max: parseFloat(card.dataset.tiltMax) || 15,
      speed: parseFloat(card.dataset.tiltSpeed) || 400,
      perspective: parseFloat(card.dataset.tiltPerspective) || 1000,
      glare: card.dataset.tiltGlare === 'true',
      maxGlare: parseFloat(card.dataset.tiltMaxGlare) || 0.3
    };
    
    let glareElement = null;
    
    if (config.glare) {
      glareElement = document.createElement('div');
      glareElement.className = 'jaraba-3dcard__glare';
      glareElement.style.cssText = `
        position: absolute;
        inset: 0;
        border-radius: inherit;
        background: linear-gradient(
          135deg,
          rgba(255,255,255,${config.maxGlare}) 0%,
          transparent 50%
        );
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease;
      `;
      card.appendChild(glareElement);
    }
    
    card.style.transformStyle = 'preserve-3d';
    card.style.perspective = config.perspective + 'px';
    card.style.transition = `transform ${config.speed}ms ease-out`;
    
    card.addEventListener('mouseenter', () => {
      card.style.transition = 'none';
      if (glareElement) glareElement.style.opacity = '1';
    });
    
    card.addEventListener('mousemove', (e) => {
      const rect = card.getBoundingClientRect();
      const centerX = rect.left + rect.width / 2;
      const centerY = rect.top + rect.height / 2;
      
      const mouseX = e.clientX - centerX;
      const mouseY = e.clientY - centerY;
      
      const rotateX = (mouseY / (rect.height / 2)) * -config.max;
      const rotateY = (mouseX / (rect.width / 2)) * config.max;
      
      card.style.transform = `
        rotateX(${rotateX}deg) 
        rotateY(${rotateY}deg)
        scale3d(1.02, 1.02, 1.02)
      `;
      
      if (glareElement) {
        const glareAngle = Math.atan2(mouseY, mouseX) * (180 / Math.PI);
        glareElement.style.background = `
          linear-gradient(
            ${glareAngle + 135}deg,
            rgba(255,255,255,${config.maxGlare}) 0%,
            transparent 50%
          )
        `;
      }
    });
    
    card.addEventListener('mouseleave', () => {
      card.style.transition = `transform ${config.speed}ms ease-out`;
      card.style.transform = 'rotateX(0) rotateY(0) scale3d(1, 1, 1)';
      if (glareElement) glareElement.style.opacity = '0';
    });
  };
 
  // ============================================================
  // TEXT REVEAL EFFECT
  // ============================================================
  
  Drupal.aceternityUI.initTextReveal = function (container) {
    const text = container.querySelector('[data-reveal-text]');
    if (!text) return;
    
    const words = text.textContent.trim().split(' ');
    text.innerHTML = '';
    
    words.forEach((word, index) => {
      const span = document.createElement('span');
      span.className = 'jaraba-text-reveal__word';
      span.textContent = word + ' ';
      span.style.cssText = `
        display: inline-block;
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.5s ease;
        transition-delay: ${index * 0.05}s;
      `;
      text.appendChild(span);
    });
    
    // Intersection Observer para trigger
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const words = entry.target.querySelectorAll('.jaraba-text-reveal__word');
          words.forEach(word => {
            word.style.opacity = '1';
            word.style.transform = 'translateY(0)';
          });
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.2 });
    
    observer.observe(container);
  };
 
  // ============================================================
  // INFINITE MOVING CARDS
  // ============================================================
  
  Drupal.aceternityUI.initInfiniteCards = function (container) {
    const track = container.querySelector('[data-infinite-track]');
    if (!track) return;
    
    const content = track.innerHTML;
    track.innerHTML = content + content; // Duplicar para loop seamless
    
    const pauseOnHover = container.dataset.pauseOnHover === 'true';
    
    if (pauseOnHover) {
      container.addEventListener('mouseenter', () => {
        track.style.animationPlayState = 'paused';
      });
      
      container.addEventListener('mouseleave', () => {
        track.style.animationPlayState = 'running';
      });
    }
  };
 
  // ============================================================
  // DRUPAL BEHAVIORS
  // ============================================================
  
  Drupal.behaviors.aceternitySpotlight = {
    attach: function (context) {
      once('aceternity-spotlight', '.jaraba-aceternity-spotlight', context)
        .forEach(Drupal.aceternityUI.initSpotlight);
    }
  };
  
  Drupal.behaviors.aceternity3DCard = {
    attach: function (context) {
      once('aceternity-3dcard', '.jaraba-3dcard[data-tilt]', context)
        .forEach(Drupal.aceternityUI.init3DCard);
    }
  };
  
  Drupal.behaviors.aceternityTextReveal = {
    attach: function (context) {
      once('aceternity-text-reveal', '.jaraba-text-reveal', context)
        .forEach(Drupal.aceternityUI.initTextReveal);
    }
  };
  
  Drupal.behaviors.aceternityInfiniteCards = {
    attach: function (context) {
      once('aceternity-infinite', '.jaraba-infinite-cards', context)
        .forEach(Drupal.aceternityUI.initInfiniteCards);
    }
  };
 
})(Drupal, once);

4.2. Magic UI Adapter
Adapter para componentes Magic UI: Bento Shine, Marquee, Animated Beam, Orbiting Circles, Blur Fade, Number Ticker y Typing Animation.
 js/magicui-adapter.js
/**
 * magicui-adapter.js
 * Adaptador JavaScript para componentes Magic UI en Drupal
 * 
 * @package jaraba_page_builder
 * @version 1.0.0
 */
 
(function (Drupal, once) {
  'use strict';
 
  /**
   * Namespace para componentes Magic UI
   */
  Drupal.magicUI = Drupal.magicUI || {};
 
  // ============================================================
  // BENTO GRID - Shine Effect
  // ============================================================
  
  Drupal.magicUI.initBentoShine = function (item) {
    const shine = item.querySelector('.jaraba-bento-item__shine');
    if (!shine) return;
    
    item.addEventListener('mousemove', (e) => {
      const rect = item.getBoundingClientRect();
      const x = ((e.clientX - rect.left) / rect.width) * 100;
      const y = ((e.clientY - rect.top) / rect.height) * 100;
      
      shine.style.background = `
        radial-gradient(
          circle at ${x}% ${y}%,
          rgba(255,255,255,0.15) 0%,
          transparent 50%
        )
      `;
      shine.style.opacity = '1';
    });
    
    item.addEventListener('mouseleave', () => {
      shine.style.opacity = '0';
    });
  };
 
  // ============================================================
  // MARQUEE - Infinite Scroll
  // ============================================================
  
  Drupal.magicUI.initMarquee = function (wrapper) {
    const track = wrapper.querySelector('.jaraba-marquee-track');
    if (!track) return;
    
    const pauseOnHover = wrapper.dataset.pauseOnHover === 'true';
    
    // Calcular duración basada en contenido
    const content = track.querySelector('.jaraba-marquee-content');
    if (content) {
      const contentWidth = content.offsetWidth;
      const speed = parseFloat(getComputedStyle(wrapper).getPropertyValue('--marquee-speed')) || 30;
      
      // Ajustar animación CSS
      track.style.setProperty('--marquee-width', contentWidth + 'px');
    }
    
    if (pauseOnHover) {
      wrapper.addEventListener('mouseenter', () => {
        track.style.animationPlayState = 'paused';
      });
      
      wrapper.addEventListener('mouseleave', () => {
        track.style.animationPlayState = 'running';
      });
    }
  };
 
  // ============================================================
  // ANIMATED BEAM
  // ============================================================
  
  Drupal.magicUI.initAnimatedBeam = function (container) {
    const beams = container.querySelectorAll('[data-beam]');
    
    beams.forEach(beam => {
      const fromEl = container.querySelector(beam.dataset.beamFrom);
      const toEl = container.querySelector(beam.dataset.beamTo);
      
      if (!fromEl || !toEl) return;
      
      const updateBeam = () => {
        const containerRect = container.getBoundingClientRect();
        const fromRect = fromEl.getBoundingClientRect();
        const toRect = toEl.getBoundingClientRect();
        
        const fromX = fromRect.left + fromRect.width / 2 - containerRect.left;
        const fromY = fromRect.top + fromRect.height / 2 - containerRect.top;
        const toX = toRect.left + toRect.width / 2 - containerRect.left;
        const toY = toRect.top + toRect.height / 2 - containerRect.top;
        
        // Calcular puntos de control para curva Bezier
        const midY = (fromY + toY) / 2;
        const controlX1 = fromX;
        const controlY1 = midY;
        const controlX2 = toX;
        const controlY2 = midY;
        
        beam.setAttribute('d', `
          M ${fromX} ${fromY}
          C ${controlX1} ${controlY1}, ${controlX2} ${controlY2}, ${toX} ${toY}
        `);
      };
      
      updateBeam();
      
      // Observar cambios de tamaño
      const resizeObserver = new ResizeObserver(updateBeam);
      resizeObserver.observe(container);
    });
  };
 
  // ============================================================
  // ORBITING CIRCLES
  // ============================================================
  
  Drupal.magicUI.initOrbitingCircles = function (container) {
    const orbits = container.querySelectorAll('[data-orbit]');
    
    orbits.forEach(orbit => {
      const radius = parseFloat(orbit.dataset.orbitRadius) || 100;
      const duration = parseFloat(orbit.dataset.orbitDuration) || 10;
      const delay = parseFloat(orbit.dataset.orbitDelay) || 0;
      const reverse = orbit.dataset.orbitReverse === 'true';
      
      orbit.style.cssText = `
        position: absolute;
        top: 50%;
        left: 50%;
        animation: jaraba-orbit ${duration}s linear ${delay}s infinite ${reverse ? 'reverse' : 'normal'};
        --orbit-radius: ${radius}px;
      `;
    });
  };
 
  // ============================================================
  // BLUR FADE
  // ============================================================
  
  Drupal.magicUI.initBlurFade = function (element) {
    const delay = parseFloat(element.dataset.blurDelay) || 0;
    const duration = parseFloat(element.dataset.blurDuration) || 0.5;
    
    element.style.cssText = `
      opacity: 0;
      filter: blur(10px);
      transform: translateY(20px);
      transition: all ${duration}s ease ${delay}s;
    `;
    
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.opacity = '1';
          entry.target.style.filter = 'blur(0)';
          entry.target.style.transform = 'translateY(0)';
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1 });
    
    observer.observe(element);
  };
 
  // ============================================================
  // NUMBER TICKER
  // ============================================================
  
  Drupal.magicUI.initNumberTicker = function (element) {
    const target = parseFloat(element.dataset.target);
    const duration = parseFloat(element.dataset.duration) || 2000;
    const format = element.dataset.format || 'number';
    
    let startTime = null;
    let hasStarted = false;
    
    const formatNumber = (num) => {
      if (format === 'currency') {
        return new Intl.NumberFormat('es-ES', {
          style: 'currency',
          currency: 'EUR',
          minimumFractionDigits: 0
        }).format(num);
      }
      if (format === 'percent') {
        return num.toFixed(0) + '%';
      }
      if (format === 'decimal') {
        return num.toFixed(1);
      }
      return new Intl.NumberFormat('es-ES').format(Math.round(num));
    };
    
    const animate = (timestamp) => {
      if (!startTime) startTime = timestamp;
      const progress = Math.min((timestamp - startTime) / duration, 1);
      
      // Easing function (ease-out-expo)
      const eased = progress === 1 ? 1 : 1 - Math.pow(2, -10 * progress);
      const current = target * eased;
      
      element.textContent = formatNumber(current);
      
      if (progress < 1) {
        requestAnimationFrame(animate);
      }
    };
    
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting && !hasStarted) {
          hasStarted = true;
          requestAnimationFrame(animate);
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.5 });
    
    observer.observe(element);
  };
 
  // ============================================================
  // TYPING ANIMATION
  // ============================================================
  
  Drupal.magicUI.initTyping = function (element) {
    const texts = JSON.parse(element.dataset.typingTexts || '[]');
    const speed = parseFloat(element.dataset.typingSpeed) || 100;
    const pause = parseFloat(element.dataset.typingPause) || 2000;
    const loop = element.dataset.typingLoop !== 'false';
    
    if (!texts.length) return;
    
    let textIndex = 0;
    let charIndex = 0;
    let isDeleting = false;
    
    const type = () => {
      const currentText = texts[textIndex];
      
      if (isDeleting) {
        charIndex--;
        element.textContent = currentText.substring(0, charIndex);
        
        if (charIndex === 0) {
          isDeleting = false;
          textIndex = (textIndex + 1) % texts.length;
          setTimeout(type, speed);
        } else {
          setTimeout(type, speed / 2);
        }
      } else {
        charIndex++;
        element.textContent = currentText.substring(0, charIndex);
        
        if (charIndex === currentText.length) {
          if (loop || textIndex < texts.length - 1) {
            isDeleting = true;
            setTimeout(type, pause);
          }
        } else {
          setTimeout(type, speed);
        }
      }
    };
    
    // Crear cursor
    const cursor = document.createElement('span');
    cursor.className = 'jaraba-typing-cursor';
    cursor.textContent = '|';
    cursor.style.cssText = 'animation: jaraba-blink 1s infinite;';
    element.parentNode.insertBefore(cursor, element.nextSibling);
    
    type();
  };
 
  // ============================================================
  // DRUPAL BEHAVIORS
  // ============================================================
  
  Drupal.behaviors.magicUIBento = {
    attach: function (context) {
      once('magicui-bento', '.jaraba-bento-item', context)
        .forEach(Drupal.magicUI.initBentoShine);
    }
  };
  
  Drupal.behaviors.magicUIMarquee = {
    attach: function (context) {
      once('magicui-marquee', '[data-marquee]', context)
        .forEach(Drupal.magicUI.initMarquee);
    }
  };
  
  Drupal.behaviors.magicUIBeam = {
    attach: function (context) {
      once('magicui-beam', '.jaraba-animated-beam', context)
        .forEach(Drupal.magicUI.initAnimatedBeam);
    }
  };
  
  Drupal.behaviors.magicUIOrbit = {
    attach: function (context) {
      once('magicui-orbit', '.jaraba-orbiting-circles', context)
        .forEach(Drupal.magicUI.initOrbitingCircles);
    }
  };
  
  Drupal.behaviors.magicUIBlurFade = {
    attach: function (context) {
      once('magicui-blur', '[data-blur-fade]', context)
        .forEach(Drupal.magicUI.initBlurFade);
    }
  };
  
  Drupal.behaviors.magicUITicker = {
    attach: function (context) {
      once('magicui-ticker', '.js-counter', context)
        .forEach(Drupal.magicUI.initNumberTicker);
    }
  };
  
  Drupal.behaviors.magicUITyping = {
    attach: function (context) {
      once('magicui-typing', '[data-typing-texts]', context)
        .forEach(Drupal.magicUI.initTyping);
    }
  };
 
})(Drupal, once);

 
5. CSS Design Tokens
Sistema completo de Design Tokens con más de 80 variables CSS que cubren colores, tipografía, espaciado, sombras, bordes y animaciones. Incluye soporte para dark mode y cascada multi-tenant.
5.1. Sistema de Variables CSS
Tokens base que definen el sistema de diseño completo de la plataforma.
 scss/_design-tokens.scss
/**
 * _design-tokens.scss
 * Sistema de Design Tokens para Jaraba Page Builder
 * 
 * Cascada de prioridad:
 * 1. Tenant (máxima prioridad)
 * 2. Plan
 * 3. Vertical
 * 4. Plataforma (valores base)
 */
 
:root {
  /* ============================================================
     COLORES - BRAND
     ============================================================ */
  --color-primary: #3B82F6;
  --color-primary-light: #60A5FA;
  --color-primary-dark: #2563EB;
  --color-primary-contrast: #FFFFFF;
  
  --color-secondary: #8B5CF6;
  --color-secondary-light: #A78BFA;
  --color-secondary-dark: #7C3AED;
  --color-secondary-contrast: #FFFFFF;
  
  --color-accent: #10B981;
  --color-accent-light: #34D399;
  --color-accent-dark: #059669;
  --color-accent-contrast: #FFFFFF;
  
  /* ============================================================
     COLORES - SEMÁNTICOS
     ============================================================ */
  --color-success: #22C55E;
  --color-success-light: #4ADE80;
  --color-success-dark: #16A34A;
  
  --color-warning: #F59E0B;
  --color-warning-light: #FBBF24;
  --color-warning-dark: #D97706;
  
  --color-error: #EF4444;
  --color-error-light: #F87171;
  --color-error-dark: #DC2626;
  
  --color-info: #3B82F6;
  --color-info-light: #60A5FA;
  --color-info-dark: #2563EB;
  
  /* ============================================================
     COLORES - SUPERFICIES
     ============================================================ */
  --color-surface-bg: #FFFFFF;
  --color-surface-elevated: #F8FAFC;
  --color-surface-card: #FFFFFF;
  --color-surface-overlay: rgba(0, 0, 0, 0.5);
  
  /* ============================================================
     COLORES - TEXTO
     ============================================================ */
  --color-text-primary: #1E293B;
  --color-text-secondary: #64748B;
  --color-text-muted: #94A3B8;
  --color-text-inverse: #FFFFFF;
  --color-text-link: var(--color-primary);
  --color-text-link-hover: var(--color-primary-dark);
  
  /* ============================================================
     COLORES - BORDES
     ============================================================ */
  --color-border: #E2E8F0;
  --color-border-light: #F1F5F9;
  --color-border-dark: #CBD5E1;
  --color-border-focus: var(--color-primary);
  
  /* ============================================================
     TIPOGRAFÍA - FAMILIAS
     ============================================================ */
  --font-family-base: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  --font-family-headings: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  --font-family-mono: 'JetBrains Mono', 'Fira Code', Consolas, monospace;
  
  /* ============================================================
     TIPOGRAFÍA - TAMAÑOS
     ============================================================ */
  --font-size-xs: 0.75rem;    /* 12px */
  --font-size-sm: 0.875rem;   /* 14px */
  --font-size-base: 1rem;     /* 16px */
  --font-size-lg: 1.125rem;   /* 18px */
  --font-size-xl: 1.25rem;    /* 20px */
  --font-size-2xl: 1.5rem;    /* 24px */
  --font-size-3xl: 1.875rem;  /* 30px */
  --font-size-4xl: 2.25rem;   /* 36px */
  --font-size-5xl: 3rem;      /* 48px */
  --font-size-6xl: 3.75rem;   /* 60px */
  
  /* ============================================================
     TIPOGRAFÍA - PESOS
     ============================================================ */
  --font-weight-light: 300;
  --font-weight-normal: 400;
  --font-weight-medium: 500;
  --font-weight-semibold: 600;
  --font-weight-bold: 700;
  --font-weight-extrabold: 800;
  
  /* ============================================================
     TIPOGRAFÍA - LINE HEIGHT
     ============================================================ */
  --line-height-tight: 1.25;
  --line-height-snug: 1.375;
  --line-height-normal: 1.5;
  --line-height-relaxed: 1.625;
  --line-height-loose: 2;
  
  /* ============================================================
     TIPOGRAFÍA - LETTER SPACING
     ============================================================ */
  --letter-spacing-tighter: -0.05em;
  --letter-spacing-tight: -0.025em;
  --letter-spacing-normal: 0;
  --letter-spacing-wide: 0.025em;
  --letter-spacing-wider: 0.05em;
  --letter-spacing-widest: 0.1em;
  
  /* ============================================================
     ESPACIADO
     ============================================================ */
  --space-0: 0;
  --space-1: 0.25rem;   /* 4px */
  --space-2: 0.5rem;    /* 8px */
  --space-3: 0.75rem;   /* 12px */
  --space-4: 1rem;      /* 16px */
  --space-5: 1.25rem;   /* 20px */
  --space-6: 1.5rem;    /* 24px */
  --space-8: 2rem;      /* 32px */
  --space-10: 2.5rem;   /* 40px */
  --space-12: 3rem;     /* 48px */
  --space-16: 4rem;     /* 64px */
  --space-20: 5rem;     /* 80px */
  --space-24: 6rem;     /* 96px */
  
  /* ============================================================
     BORDER RADIUS
     ============================================================ */
  --radius-none: 0;
  --radius-sm: 0.125rem;  /* 2px */
  --radius-default: 0.25rem;  /* 4px */
  --radius-md: 0.375rem;  /* 6px */
  --radius-lg: 0.5rem;    /* 8px */
  --radius-xl: 0.75rem;   /* 12px */
  --radius-2xl: 1rem;     /* 16px */
  --radius-3xl: 1.5rem;   /* 24px */
  --radius-full: 9999px;
  
  /* ============================================================
     SOMBRAS
     ============================================================ */
  --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  --shadow-default: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
  --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
  --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
  --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
  --shadow-2xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  --shadow-inner: inset 0 2px 4px 0 rgba(0, 0, 0, 0.05);
  --shadow-none: none;
  
  /* ============================================================
     TRANSICIONES
     ============================================================ */
  --transition-fast: 150ms;
  --transition-normal: 250ms;
  --transition-slow: 350ms;
  --transition-slower: 500ms;
  
  /* ============================================================
     EASING
     ============================================================ */
  --ease-linear: linear;
  --ease-in: cubic-bezier(0.4, 0, 1, 1);
  --ease-out: cubic-bezier(0, 0, 0.2, 1);
  --ease-in-out: cubic-bezier(0.4, 0, 0.2, 1);
  --ease-bounce: cubic-bezier(0.68, -0.55, 0.265, 1.55);
  --ease-elastic: cubic-bezier(0.68, -0.6, 0.32, 1.6);
  
  /* ============================================================
     Z-INDEX
     ============================================================ */
  --z-dropdown: 1000;
  --z-sticky: 1020;
  --z-fixed: 1030;
  --z-modal-backdrop: 1040;
  --z-modal: 1050;
  --z-popover: 1060;
  --z-tooltip: 1070;
  --z-toast: 1080;
  
  /* ============================================================
     BREAKPOINTS (para referencia en media queries)
     ============================================================ */
  --breakpoint-sm: 640px;
  --breakpoint-md: 768px;
  --breakpoint-lg: 1024px;
  --breakpoint-xl: 1280px;
  --breakpoint-2xl: 1536px;
  
  /* ============================================================
     CONTAINER
     ============================================================ */
  --container-sm: 640px;
  --container-md: 768px;
  --container-lg: 1024px;
  --container-xl: 1280px;
  --container-2xl: 1400px;
  --container-padding: var(--space-4);
  
  /* ============================================================
     ASPECT RATIOS
     ============================================================ */
  --aspect-square: 1 / 1;
  --aspect-video: 16 / 9;
  --aspect-portrait: 3 / 4;
  --aspect-landscape: 4 / 3;
  --aspect-wide: 21 / 9;
}
 
/* ============================================================
   DARK MODE
   ============================================================ */
[data-theme="dark"],
.dark {
  --color-surface-bg: #0F172A;
  --color-surface-elevated: #1E293B;
  --color-surface-card: #1E293B;
  --color-surface-overlay: rgba(0, 0, 0, 0.7);
  
  --color-text-primary: #F1F5F9;
  --color-text-secondary: #94A3B8;
  --color-text-muted: #64748B;
  --color-text-inverse: #1E293B;
  
  --color-border: #334155;
  --color-border-light: #1E293B;
  --color-border-dark: #475569;
  
  --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.3);
  --shadow-default: 0 1px 3px 0 rgba(0, 0, 0, 0.4), 0 1px 2px -1px rgba(0, 0, 0, 0.3);
  --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.4), 0 2px 4px -2px rgba(0, 0, 0, 0.3);
  --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.4), 0 4px 6px -4px rgba(0, 0, 0, 0.3);
  --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.4), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
  --shadow-2xl: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
}
 
/* ============================================================
   ANIMACIONES KEYFRAMES
   ============================================================ */
@keyframes jaraba-fade-in {
  from { opacity: 0; }
  to { opacity: 1; }
}
 
@keyframes jaraba-fade-up {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
 
@keyframes jaraba-scale-in {
  from {
    opacity: 0;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}
 
@keyframes jaraba-slide-in-right {
  from {
    opacity: 0;
    transform: translateX(20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}
 
@keyframes jaraba-orbit {
  from {
    transform: rotate(0deg) translateX(var(--orbit-radius)) rotate(0deg);
  }
  to {
    transform: rotate(360deg) translateX(var(--orbit-radius)) rotate(-360deg);
  }
}
 
@keyframes jaraba-marquee {
  from {
    transform: translateX(0);
  }
  to {
    transform: translateX(-50%);
  }
}
 
@keyframes jaraba-blink {
  0%, 50% { opacity: 1; }
  51%, 100% { opacity: 0; }
}
 
@keyframes jaraba-pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}
 
@keyframes jaraba-bounce {
  0%, 100% {
    transform: translateY(-25%);
    animation-timing-function: cubic-bezier(0.8, 0, 1, 1);
  }
  50% {
    transform: translateY(0);
    animation-timing-function: cubic-bezier(0, 0, 0.2, 1);
  }
}
 
@keyframes jaraba-spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
 
@keyframes jaraba-ping {
  75%, 100% {
    transform: scale(2);
    opacity: 0;
  }
}

5.2. Estilos Aceternity
Estilos específicos para los componentes Aceternity UI.
 scss/_aceternity-components.scss
/**
 * _aceternity-components.scss
 * Estilos para componentes Aceternity UI
 */
 
/* ============================================================
   SPOTLIGHT EFFECT
   ============================================================ */
.jaraba-aceternity-spotlight {
  position: relative;
  min-height: 60vh;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  background: var(--color-surface-bg);
}
 
.jaraba-spotlight__canvas {
  position: absolute;
  inset: 0;
  pointer-events: none;
  z-index: 1;
}
 
.jaraba-spotlight__content {
  position: relative;
  z-index: 2;
  text-align: center;
  max-width: 800px;
}
 
.jaraba-spotlight__badge {
  display: inline-block;
  padding: var(--space-2) var(--space-4);
  background: rgba(var(--color-primary-rgb), 0.1);
  color: var(--color-primary);
  border-radius: var(--radius-full);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  margin-bottom: var(--space-6);
}
 
.jaraba-spotlight__title {
  font-size: var(--font-size-5xl);
  font-weight: var(--font-weight-bold);
  line-height: var(--line-height-tight);
  margin-bottom: var(--space-6);
  background: linear-gradient(
    to right,
    var(--color-text-primary),
    var(--color-primary)
  );
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
 
.jaraba-spotlight__description {
  font-size: var(--font-size-xl);
  color: var(--color-text-secondary);
  margin-bottom: var(--space-8);
}
 
.jaraba-spotlight__bg-gradient {
  position: absolute;
  inset: 0;
  background: radial-gradient(
    ellipse at center,
    rgba(var(--color-primary-rgb), 0.15) 0%,
    transparent 70%
  );
  z-index: 0;
}
 
/* ============================================================
   3D CARD EFFECT
   ============================================================ */
.jaraba-3dcard-container {
  perspective: 1000px;
}
 
.jaraba-3dcard {
  position: relative;
  transform-style: preserve-3d;
  transition: transform 0.4s ease;
  border-radius: var(--radius-xl);
  overflow: visible;
}
 
.jaraba-3dcard__bg {
  position: absolute;
  inset: -5px;
  background: linear-gradient(
    135deg,
    var(--color-primary) 0%,
    var(--color-secondary) 100%
  );
  border-radius: var(--radius-xl);
  opacity: 0;
  transition: opacity 0.3s ease;
}
 
.jaraba-3dcard:hover .jaraba-3dcard__bg {
  opacity: 0.1;
}
 
.jaraba-3dcard__content {
  position: relative;
  background: var(--color-surface-card);
  border-radius: var(--radius-xl);
  padding: var(--space-6);
  border: 1px solid var(--color-border);
}
 
.jaraba-3dcard__image-wrapper {
  border-radius: var(--radius-lg);
  overflow: hidden;
  margin-bottom: var(--space-4);
}
 
.jaraba-3dcard__image {
  width: 100%;
  height: auto;
  display: block;
  transition: transform 0.4s ease;
}
 
.jaraba-3dcard:hover .jaraba-3dcard__image {
  transform: scale(1.05);
}
 
.jaraba-3dcard__icon {
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
  border-radius: var(--radius-lg);
  margin-bottom: var(--space-4);
}
 
.jaraba-3dcard__icon svg {
  width: 24px;
  height: 24px;
  color: white;
}
 
.jaraba-3dcard__title {
  font-size: var(--font-size-xl);
  font-weight: var(--font-weight-semibold);
  margin-bottom: var(--space-2);
}
 
.jaraba-3dcard__description {
  color: var(--color-text-secondary);
  margin-bottom: var(--space-4);
}
 
.jaraba-3dcard__cta {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
  color: var(--color-primary);
  font-weight: var(--font-weight-medium);
  transition: gap 0.3s ease;
}
 
.jaraba-3dcard__cta:hover {
  gap: var(--space-3);
}
 
.jaraba-3dcard__glow {
  position: absolute;
  inset: 0;
  border-radius: var(--radius-xl);
  background: radial-gradient(
    circle at var(--mouse-x, 50%) var(--mouse-y, 50%),
    rgba(var(--color-primary-rgb), 0.15) 0%,
    transparent 50%
  );
  opacity: 0;
  transition: opacity 0.3s ease;
  pointer-events: none;
}
 
.jaraba-3dcard:hover .jaraba-3dcard__glow {
  opacity: 1;
}

5.3. Estilos Magic UI
Estilos específicos para los componentes Magic UI.
 scss/_magicui-components.scss
/**
 * _magicui-components.scss
 * Estilos para componentes Magic UI
 */
 
/* ============================================================
   BENTO GRID
   ============================================================ */
.jaraba-bento-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  grid-auto-rows: minmax(200px, auto);
  gap: var(--space-4);
}
 
@media (max-width: 1024px) {
  .jaraba-bento-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
 
@media (max-width: 640px) {
  .jaraba-bento-grid {
    grid-template-columns: 1fr;
  }
}
 
.jaraba-bento-item {
  position: relative;
  background: var(--bento-bg, var(--color-surface-card));
  border-radius: var(--radius-2xl);
  padding: var(--space-6);
  border: 1px solid var(--color-border);
  overflow: hidden;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
 
.jaraba-bento-item:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-xl);
}
 
.jaraba-bento-item--large {
  grid-column: span 2;
  grid-row: span 2;
}
 
.jaraba-bento-item--medium {
  grid-column: span 2;
  grid-row: span 1;
}
 
.jaraba-bento-item--wide {
  grid-column: span 2;
  grid-row: span 1;
}
 
.jaraba-bento-item--tall {
  grid-column: span 1;
  grid-row: span 2;
}
 
.jaraba-bento-item__shine {
  position: absolute;
  inset: 0;
  opacity: 0;
  transition: opacity 0.3s ease;
  pointer-events: none;
}
 
.jaraba-bento-item__content {
  position: relative;
  z-index: 1;
  height: 100%;
  display: flex;
  flex-direction: column;
}
 
.jaraba-bento-item__icon {
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--bento-accent, var(--color-primary));
  border-radius: var(--radius-lg);
  margin-bottom: var(--space-4);
}
 
.jaraba-bento-item__icon svg {
  width: 24px;
  height: 24px;
  color: white;
}
 
.jaraba-bento-item__image {
  border-radius: var(--radius-lg);
  overflow: hidden;
  margin-bottom: var(--space-4);
}
 
.jaraba-bento-item__image img {
  width: 100%;
  height: auto;
  display: block;
}
 
.jaraba-bento-item__title {
  font-size: var(--font-size-xl);
  font-weight: var(--font-weight-semibold);
  margin-bottom: var(--space-2);
}
 
.jaraba-bento-item__description {
  color: var(--color-text-secondary);
  flex-grow: 1;
}
 
.jaraba-bento-item__stats {
  display: flex;
  gap: var(--space-6);
  margin-top: var(--space-4);
}
 
.jaraba-bento-stat__value {
  display: block;
  font-size: var(--font-size-2xl);
  font-weight: var(--font-weight-bold);
  color: var(--bento-accent, var(--color-primary));
}
 
.jaraba-bento-stat__label {
  font-size: var(--font-size-sm);
  color: var(--color-text-muted);
}
 
.jaraba-bento-item__cta {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
  color: var(--bento-accent, var(--color-primary));
  font-weight: var(--font-weight-medium);
  margin-top: var(--space-4);
  transition: gap 0.3s ease;
}
 
.jaraba-bento-item__cta:hover {
  gap: var(--space-3);
}
 
.jaraba-bento-item__gradient {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 50%;
  background: linear-gradient(
    to top,
    var(--bento-accent, var(--color-primary)),
    transparent
  );
  opacity: 0.05;
  pointer-events: none;
}
 
/* ============================================================
   MARQUEE
   ============================================================ */
.jaraba-magicui-marquee {
  position: relative;
  overflow: hidden;
  padding: var(--space-12) 0;
}
 
.jaraba-marquee-wrapper {
  position: relative;
  overflow: hidden;
}
 
.jaraba-marquee-track {
  display: flex;
  width: max-content;
  animation: jaraba-marquee var(--marquee-speed, 30s) linear infinite;
  animation-direction: var(--marquee-direction, normal);
}
 
.jaraba-marquee-content {
  display: flex;
  align-items: center;
  gap: var(--space-8);
  padding: 0 var(--space-4);
}
 
.jaraba-marquee-item {
  flex-shrink: 0;
}
 
.jaraba-marquee-logo {
  height: 40px;
  width: auto;
  filter: grayscale(100%);
  opacity: 0.6;
  transition: all 0.3s ease;
}
 
.jaraba-marquee-logo:hover {
  filter: grayscale(0%);
  opacity: 1;
}
 
.jaraba-marquee-testimonial {
  background: var(--color-surface-card);
  border-radius: var(--radius-xl);
  padding: var(--space-6);
  max-width: 400px;
  border: 1px solid var(--color-border);
}
 
.jaraba-marquee-testimonial p {
  font-style: italic;
  margin-bottom: var(--space-4);
}
 
.jaraba-marquee-testimonial footer {
  display: flex;
  align-items: center;
  gap: var(--space-3);
}
 
.jaraba-marquee-avatar {
  width: 40px;
  height: 40px;
  border-radius: var(--radius-full);
}
 
.jaraba-marquee-author {
  display: block;
  font-weight: var(--font-weight-semibold);
}
 
.jaraba-marquee-role {
  display: block;
  font-size: var(--font-size-sm);
  color: var(--color-text-muted);
}
 
.jaraba-marquee-card {
  background: var(--color-surface-card);
  border-radius: var(--radius-xl);
  padding: var(--space-6);
  min-width: 280px;
  border: 1px solid var(--color-border);
}
 
.jaraba-marquee-mask {
  position: absolute;
  top: 0;
  bottom: 0;
  width: 200px;
  z-index: 2;
  pointer-events: none;
}
 
.jaraba-marquee-mask--left {
  left: 0;
  background: linear-gradient(to right, var(--color-surface-bg), transparent);
}
 
.jaraba-marquee-mask--right {
  right: 0;
  background: linear-gradient(to left, var(--color-surface-bg), transparent);
}
 
/* Pause on hover */
.jaraba-marquee-wrapper:hover .jaraba-marquee-track {
  animation-play-state: paused;
}
 
/* ============================================================
   TYPING CURSOR
   ============================================================ */
.jaraba-typing-cursor {
  display: inline-block;
  margin-left: 2px;
  color: var(--color-primary);
  font-weight: var(--font-weight-normal);
}

 
Anexo: Estructura de Archivos del Módulo
Estructura completa del directorio de templates y assets:
 Estructura de Archivos
jaraba_page_builder/
├── templates/
│   ├── blocks/
│   │   ├── hero/
│   │   │   ├── hero-fullscreen.html.twig
│   │   │   ├── hero-split.html.twig
│   │   │   ├── hero-video.html.twig
│   │   │   └── hero-gradient.html.twig
│   │   ├── features/
│   │   │   ├── features-grid.html.twig
│   │   │   ├── features-tabs.html.twig
│   │   │   └── features-alternating.html.twig
│   │   ├── stats/
│   │   │   ├── stats-counter.html.twig
│   │   │   └── stats-cards.html.twig
│   │   ├── testimonials/
│   │   │   ├── testimonials-slider.html.twig
│   │   │   └── testimonials-grid.html.twig
│   │   ├── pricing/
│   │   │   ├── pricing-table.html.twig
│   │   │   └── pricing-cards.html.twig
│   │   ├── cta/
│   │   │   ├── cta-banner.html.twig
│   │   │   └── cta-floating.html.twig
│   │   ├── aceternity/
│   │   │   ├── spotlight-effect.html.twig
│   │   │   ├── 3d-card-effect.html.twig
│   │   │   ├── text-reveal.html.twig
│   │   │   ├── infinite-cards.html.twig
│   │   │   ├── meteors-background.html.twig
│   │   │   ├── aurora-background.html.twig
│   │   │   ├── tracing-beam.html.twig
│   │   │   ├── hover-border-gradient.html.twig
│   │   │   ├── floating-navbar.html.twig
│   │   │   ├── glowing-stars.html.twig
│   │   │   ├── lamp-effect.html.twig
│   │   │   └── sparkles.html.twig
│   │   └── magicui/
│   │       ├── bento-grid.html.twig
│   │       ├── animated-beam.html.twig
│   │       ├── orbiting-circles.html.twig
│   │       ├── dock-menu.html.twig
│   │       ├── marquee.html.twig
│   │       ├── particles-background.html.twig
│   │       ├── blur-fade.html.twig
│   │       ├── typing-animation.html.twig
│   │       ├── number-ticker.html.twig
│   │       └── shine-border.html.twig
├── js/
│   ├── aceternity-adapter.js
│   ├── magicui-adapter.js
│   ├── stats-counter.js
│   ├── form-builder.js
│   └── preview-manager.js
├── css/
│   ├── scss/
│   │   ├── _design-tokens.scss
│   │   ├── _base.scss
│   │   ├── _typography.scss
│   │   ├── _buttons.scss
│   │   ├── _forms.scss
│   │   ├── _aceternity-components.scss
│   │   ├── _magicui-components.scss
│   │   ├── _blocks-base.scss
│   │   └── main.scss
│   └── dist/
│       ├── page-builder.css
│       └── page-builder.min.css
└── jaraba_page_builder.libraries.yml

Configuración de Libraries
 jaraba_page_builder.libraries.yml
# jaraba_page_builder.libraries.yml
 
global:
  version: 1.0.0
  css:
    theme:
      css/dist/page-builder.min.css: {}
  dependencies:
    - core/drupal
    - core/once
 
aceternity-spotlight:
  version: 1.0.0
  js:
    js/aceternity-adapter.js: {}
  dependencies:
    - jaraba_page_builder/global
    - core/drupal.ajax
 
aceternity-3dcard:
  version: 1.0.0
  js:
    js/aceternity-adapter.js: {}
  dependencies:
    - jaraba_page_builder/global
 
magicui-bento:
  version: 1.0.0
  js:
    js/magicui-adapter.js: {}
  dependencies:
    - jaraba_page_builder/global
 
magicui-marquee:
  version: 1.0.0
  css:
    component:
      css/dist/marquee.css: {}
  js:
    js/magicui-adapter.js: {}
  dependencies:
    - jaraba_page_builder/global
 
stats-counter:
  version: 1.0.0
  js:
    js/stats-counter.js: {}
  dependencies:
    - jaraba_page_builder/global
 
form-builder:
  version: 1.0.0
  js:
    js/form-builder.js: {}
  css:
    component:
      css/dist/form-builder.css: {}
  dependencies:
    - core/drupal
    - core/jquery
    - core/drupal.ajax
    - jaraba_page_builder/global


Documento Complementario
Este anexo técnico complementa el documento 162_Page_Builder_Sistema_Completo_EDI_v1.docx que contiene las especificaciones de arquitectura, esquemas de base de datos, APIs REST, sistema de permisos RBAC y roadmap de implementación.

Fin del documento.
