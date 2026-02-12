
ECOSISTEMA JARABA
Industry Style Presets
Especificación Técnica Premium para Implementación
Documento Preparado para EDI Google Antigravity
Versión 1.0 | Enero 2026
 
Índice de Contenidos
1. Resumen Ejecutivo
2. Arquitectura del Sistema de Presets
3. Catálogo Completo de Presets Premium
   3.1 AgroConecta (5 presets)
   3.2 ComercioConecta (5 presets)
   3.3 ServiciosConecta (6 presets)
4. Especificaciones Técnicas de Design Tokens
5. Sistema de Componentes UI
6. Microinteracciones y Animaciones CSS
7. Agente IA de Personalización
8. Flujo de Onboarding Inteligente
9. Implementación Frontend
10. APIs y Endpoints
11. Roadmap de Implementación
 
1. Resumen Ejecutivo
Este documento proporciona las especificaciones técnicas completas para implementar el sistema de Industry Style Presets del Ecosistema Jaraba, diseñado para posicionar el SaaS en el segmento premium del mercado.
1.1 Objetivo
Transformar la experiencia de onboarding de "configurar desde cero" a "seleccionar y personalizar", proporcionando a cada tenant un punto de partida experto que transmita profesionalismo y calidad premium contextualizada a su sector.
1.2 Tendencias de Diseño 2025-2026 Incorporadas
•	AI-driven personalization: Personalización adaptativa mediante agentes IA
•	Glassmorphism: Efectos de cristal esmerilado para capas premium
•	High-contrast serifs: Tipografías con carácter y autoridad
•	Variable fonts: Flexibilidad tipográfica con un solo archivo
•	Meaningful microinteractions: Animaciones con propósito, no decorativas
•	Dark mode first: Diseño optimizado para modo oscuro
•	Motion design: Transiciones que guían y dan feedback
1.3 Diferenciación Competitiva
Mientras competidores como Wix, Squarespace o Shopify ofrecen templates genéricos, Jaraba proporciona presets contextualizados por sector específico con IA integrada para personalización continua.
 
2. Arquitectura del Sistema de Presets
2.1 Taxonomía de Presets
Nivel	Identificador	Descripción
Vertical	agroconecta, comercio, servicios	Plataforma SaaS principal
Sector	gourmet, legal, tech, barrio...	Categoría de negocio dentro del vertical
Mood	luxury, professional, friendly...	Tono emocional y personalidad visual
2.2 Estructura JSON de Preset
{
  "id": "agro_gourmet",
  "name": "Gourmet Artesanal",
  "vertical": "agroconecta",
  "mood": ["luxury", "craft", "premium", "artisanal"],
  "tokens": { /* Design Tokens */ },
  "components": { /* Component Variants */ },
  "animations": { /* Microinteraction Config */ },
  "ai_config": { /* AI Agent Parameters */ },
  "content_starters": { /* Copywriting Guidelines */ }
}
 
3. Catálogo Completo de Presets Premium
3.1 AgroConecta - 5 Presets
3.1.1 agro_gourmet - Gourmet Artesanal
Target: Bodegas, queserías DOP, productores de AOVE premium, jamones ibéricos, conservas artesanas.
Mood: Luxury, craft, exclusividad, tradición reinventada.
Token	Valor	Justificación
--color-primary	#1A1A2E	Casi negro: elegancia, sofisticación
--color-secondary	#C9A227	Oro: lujo, calidad artesanal
--color-accent	#722F37	Burdeos: vino, tradición, calidez
--color-surface	#FAFAF8	Crema: calidez, papel artesano
--color-surface-dark	#121218	Negro profundo para dark mode
--font-headings	Playfair Display	Serif editorial clásico
--font-body	Lora	Serif elegante para lectura
--radius-md	2px	Mínimo, refinado
--shadow-md	0 4px 20px rgba(0,0,0,0.08)	Sutil, imperceptible
Componentes seleccionados:
•	header--transparent: Fotografía como protagonista
•	hero--fullscreen: Impacto visual máximo con viñedo/bodega
•	card--product-minimal: Producto protagonista, precio discreto
•	footer--elegant: Contacto premium, sin ruido
3.1.2 agro_organic - Natural & Ecológico
Target: Granjas ecológicas, cestas km0, huertos urbanos, productos bio certificados.
Mood: Natural, verde, sostenibilidad, salud.
Token	Valor	Justificación
--color-primary	#2D5016	Verde bosque: naturaleza, orgánico
--color-secondary	#8B4513	Tierra: autenticidad, raíces
--color-accent	#F4A460	Naranja suave: cosecha, vitalidad
--color-surface	#F5F5DC	Beige: papel reciclado, natural
--font-headings	Bitter	Slab serif cálido y accesible
--font-body	Source Sans Pro	Sans-serif humanista legible
--radius-md	8px	Suave, orgánico
 
3.1.3 agro_traditional - Tradición Rural
Target: Fincas generacionales, productos con historia, denominaciones de origen.
Mood: Auténtico, histórico, familiar, arraigado.
Token	Valor	Justificación
--color-primary	#5D4E37	Marrón roble: madera, tradición
--color-secondary	#A67B5B	Terracota: cerámica, artesanía
--color-accent	#D4A574	Trigo: cosecha, abundancia
--font-headings	Libre Baskerville	Serif clásico con historia
--font-body	Merriweather	Serif cálido para narrativa
--radius-md	4px	Sutil, respetuoso
3.1.4 agro_cooperative - Cooperativa & Impacto
Target: Cooperativas agrarias, comercio justo, proyectos de impacto social.
Mood: Comunidad, transparencia, compromiso social.
Token	Valor	Justificación
--color-primary	#1565C0	Azul confianza: cooperación
--color-secondary	#43A047	Verde crecimiento: sostenibilidad
--color-accent	#FF8F00	Naranja: energía, optimismo
--font-headings	Nunito	Sans-serif amigable y abierto
--font-body	Open Sans	Neutro, accesible, claro
--radius-md	12px	Redondeado, inclusivo
3.1.5 agro_modern - AgTech & Innovación
Target: Agricultura de precisión, IoT agrícola, startups agtech, hidroponía.
Mood: Tecnología, innovación, futuro, eficiencia.
Token	Valor	Justificación
--color-primary	#00BFA5	Teal tech: innovación, frescura
--color-secondary	#1A237E	Azul profundo: expertise, datos
--color-accent	#76FF03	Verde neón: tecnología, futuro
--font-headings	Inter	Sans-serif moderno, técnico
--font-body	IBM Plex Sans	Claridad técnica, legibilidad
--radius-md	6px	Preciso, técnico
 
3.2 ComercioConecta - 5 Presets
3.2.1 comercio_boutique - Elegante & Exclusivo
Target: Moda, joyería, decoración de autor, galerías.
Mood: Elegancia, exclusividad, curación, distinción.
Token	Valor	Justificación
--color-primary	#0D0D0D	Negro absoluto: alta moda
--color-secondary	#B8860B	Oro oscuro: lujo discreto
--color-accent	#E8E8E8	Plata: modernidad, elegancia fría
--color-surface	#FFFFFF	Blanco puro: galería, espacio
--font-headings	Cormorant Garamond	Serif elegante, editorial moda
--font-body	Montserrat	Geométrica, limpia, moderna
--radius-md	0px	Angular, arquitectónico
3.2.2 comercio_barrio - Cercano & Familiar
Target: Tiendas de barrio, ultramarinos, ferreterías, papelerías locales.
Mood: Proximidad, confianza, trato personal, comunidad.
Token	Valor	Justificación
--color-primary	#D97706	Naranja cálido: cercanía, energía
--color-secondary	#1E40AF	Azul confianza: tradición local
--color-accent	#059669	Verde: frescura, disponibilidad
--color-surface	#FFF7ED	Naranja muy claro: calidez
--font-headings	Poppins	Geométrica amigable
--font-body	Nunito Sans	Redondeada, accesible
--radius-md	16px	Muy redondeado, amigable
 
3.2.3 comercio_gastro - Foodie & Gourmet
Target: Delicatessen, panaderías artesanas, cafés especiales, tiendas gourmet.
Mood: Sabor, artesanía, experiencia sensorial.
Token	Valor	Justificación
--color-primary	#78350F	Marrón café: calidez, aroma
--color-secondary	#C2410C	Naranja tostado: horneado
--color-accent	#FCD34D	Amarillo mantequilla: delicia
--font-headings	DM Serif Display	Serif con personalidad foodie
--font-body	DM Sans	Companion limpio, legible
3.2.4 comercio_tech - Digital & Gaming
Target: Electrónica, informática, gaming, gadgets.
Mood: Moderno, digital, innovación, energía.
Token	Valor	Justificación
--color-primary	#6366F1	Indigo: tech, futuro
--color-secondary	#0EA5E9	Cyan: digital, pantallas
--color-accent	#A855F7	Púrpura: gaming, creatividad
--color-surface	#0F172A	Slate oscuro: modo oscuro nativo
--font-headings	Space Grotesk	Geométrica futurista
--font-body	Inter	UI optimizada, técnica
3.2.5 comercio_wellness - Zen & Natural
Target: Herbolarios, parafarmacias, yoga shops, cosmética natural.
Mood: Bienestar, calma, naturaleza, salud.
Token	Valor	Justificación
--color-primary	#047857	Verde esmeralda: salud, naturaleza
--color-secondary	#6B7280	Gris cálido: calma, equilibrio
--color-accent	#FDE68A	Amarillo suave: luz, energía positiva
--font-headings	Josefin Sans	Elegante, zen, respiro
--font-body	Lato	Humanista, cálido, claro
 
3.3 ServiciosConecta - 6 Presets
3.3.1 servicios_legal - Profesional & Serio
Target: Abogados, notarías, gestorías, asesorías fiscales.
Mood: Confianza, seriedad, competencia, tradición profesional.
Token	Valor	Justificación
--color-primary	#1E3A5F	Azul marino: confianza, seriedad
--color-secondary	#8B7355	Marrón cuero: tradición legal
--color-accent	#B8860B	Oro oscuro: autoridad, éxito
--font-headings	Libre Baskerville	Serif clásico, formal
--font-body	Source Sans Pro	Legibilidad, modernidad equilibrada
--radius-md	4px	Mínimo, serio
3.3.2 servicios_salud - Limpio & Cuidado
Target: Clínicas, fisioterapeutas, psicólogos, dentistas.
Mood: Limpieza, cuidado, profesionalidad médica.
Token	Valor	Justificación
--color-primary	#0891B2	Cyan médico: limpieza, ciencia
--color-secondary	#10B981	Verde salud: bienestar, esperanza
--color-accent	#F59E0B	Naranja cálido: humanidad
--font-headings	Poppins	Moderna, accesible, clara
--font-body	Inter	Máxima legibilidad
 
3.3.3 servicios_creative - Artístico & Bold
Target: Estudios de diseño, arquitectos, fotógrafos, creativos.
Mood: Creatividad, boldness, expresión artística.
Token	Valor	Justificación
--color-primary	#0F0F0F	Negro puro: galería, minimalismo
--color-secondary	#FF3366	Magenta: creatividad, impacto
--color-accent	#00D9FF	Cyan brillante: digital, moderno
--font-headings	Syne	Experimental, bold, único
--font-body	Work Sans	Limpia, versátil
3.3.4 servicios_consulting - Corporativo & Experto
Target: Consultoras de negocio, coaches, formadores B2B.
Mood: Profesionalidad, expertise, resultados.
Token	Valor	Justificación
--color-primary	#1F2937	Gris carbón: seriedad, solidez
--color-secondary	#3B82F6	Azul corporativo: confianza
--color-accent	#10B981	Verde éxito: resultados, crecimiento
--font-headings	Plus Jakarta Sans	Moderna, profesional, tech
--font-body	Inter	UI-optimizada, neutra
3.3.5 servicios_tech - Digital & Innovador
Target: Agencias digitales, desarrollo software, IT services.
Mood: Tecnología, innovación, eficiencia digital.
Token	Valor	Justificación
--color-primary	#7C3AED	Violeta: innovación, creatividad tech
--color-secondary	#06B6D4	Cyan: digital, futuro
--color-accent	#F472B6	Rosa: accesibilidad, humanidad
--font-headings	JetBrains Mono	Monospace: código, tech
--font-body	IBM Plex Sans	Tech-friendly, legible
3.3.6 servicios_education - Didáctico & Accesible
Target: Academias, tutores, formadores, centros de idiomas.
Mood: Aprendizaje, accesibilidad, claridad, motivación.
Token	Valor	Justificación
--color-primary	#2563EB	Azul brillante: conocimiento, claridad
--color-secondary	#F59E0B	Naranja: energía, motivación
--color-accent	#10B981	Verde: progreso, logro
--font-headings	Nunito	Amigable, accesible, abierta
--font-body	Roboto	Universal, clara, neutral
 
4. Especificaciones Técnicas de Design Tokens
4.1 Estructura Completa de Tokens CSS
Cada preset define las siguientes variables CSS que se inyectan en :root o en el scope del tenant:
:root {
  /* === COLORES === */
  --color-primary: #1A1A2E;
  --color-primary-hover: #2A2A3E;
  --color-primary-active: #0A0A1E;
  --color-primary-rgb: 26, 26, 46;

  --color-secondary: #C9A227;
  --color-secondary-hover: #D9B237;
  --color-secondary-rgb: 201, 162, 39;

  --color-accent: #722F37;
  --color-accent-rgb: 114, 47, 55;

  --color-surface: #FAFAF8;
  --color-surface-elevated: #FFFFFF;
  --color-surface-dark: #121218;

  --color-text-primary: #1A1A2E;
  --color-text-secondary: #64748B;
  --color-text-muted: #94A3B8;
  --color-text-inverse: #FFFFFF;

  /* === TIPOGRAFÍA === */
  --font-family-headings: 'Playfair Display', serif;
  --font-family-body: 'Lora', serif;
  --font-family-mono: 'JetBrains Mono', monospace;

  --font-size-xs: 0.75rem;    /* 12px */
  --font-size-sm: 0.875rem;   /* 14px */
  --font-size-base: 1rem;     /* 16px */
  --font-size-lg: 1.125rem;   /* 18px */
  --font-size-xl: 1.25rem;    /* 20px */
  --font-size-2xl: 1.5rem;    /* 24px */
  --font-size-3xl: 1.875rem;  /* 30px */
  --font-size-4xl: 2.25rem;   /* 36px */
  --font-size-5xl: 3rem;      /* 48px */

  --font-weight-normal: 400;
  --font-weight-medium: 500;
  --font-weight-semibold: 600;
  --font-weight-bold: 700;

  --line-height-tight: 1.25;
  --line-height-normal: 1.5;
  --line-height-relaxed: 1.75;

  /* === ESPACIADO === */
  --spacing-xs: 0.25rem;   /* 4px */
  --spacing-sm: 0.5rem;    /* 8px */
  --spacing-md: 1rem;      /* 16px */
  --spacing-lg: 1.5rem;    /* 24px */
  --spacing-xl: 2rem;      /* 32px */
  --spacing-2xl: 3rem;     /* 48px */
  --spacing-3xl: 4rem;     /* 64px */

  /* === BORDES === */
  --radius-none: 0;
  --radius-sm: 2px;
  --radius-md: 4px;
  --radius-lg: 8px;
  --radius-xl: 16px;
  --radius-full: 9999px;

  /* === SOMBRAS === */
  --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
  --shadow-md: 0 4px 20px rgba(0, 0, 0, 0.08);
  --shadow-lg: 0 10px 40px rgba(0, 0, 0, 0.12);
  --shadow-xl: 0 20px 60px rgba(0, 0, 0, 0.15);

  /* === GLASSMORPHISM === */
  --glass-bg: rgba(255, 255, 255, 0.25);
  --glass-bg-dark: rgba(0, 0, 0, 0.25);
  --glass-blur: blur(12px);
  --glass-border: 1px solid rgba(255, 255, 255, 0.18);

  /* === TRANSICIONES === */
  --transition-fast: 150ms ease;
  --transition-normal: 250ms ease;
  --transition-slow: 350ms ease;
  --transition-spring: 300ms cubic-bezier(0.34, 1.56, 0.64, 1);
}
 
5. Sistema de Componentes UI
5.1 Variantes de Header
Variante	Uso	Presets que lo usan
header--transparent	Fotografía como protagonista	agro_gourmet, comercio_boutique
header--classic	Navegación profesional clara	servicios_legal, servicios_consulting
header--sticky	Siempre visible, e-commerce	comercio_barrio, comercio_gastro
header--minimal	Solo logo, navegación oculta	servicios_creative
header--glass	Glassmorphism, premium tech	agro_modern, comercio_tech, servicios_tech
5.2 Variantes de Hero
Variante	Descripción	Presets
hero--fullscreen	100vh, imagen inmersiva	agro_gourmet, comercio_boutique
hero--split	50/50 texto + imagen	servicios_legal, servicios_salud
hero--centered	Texto centrado, imagen fondo	comercio_barrio, servicios_education
hero--gradient	Gradiente animado, tech	agro_modern, comercio_tech
hero--video	Video background loop	servicios_creative
5.3 Variantes de Cards
Variante	Características	Presets
card--product-minimal	Imagen grande, info mínima, precio discreto	agro_gourmet, comercio_boutique
card--product-detailed	Info completa, especificaciones visibles	comercio_tech, agro_modern
card--product-friendly	Bordes redondeados, badges coloridos	comercio_barrio, agro_organic
card--profile-formal	Foto rectangular, credenciales visibles	servicios_legal, servicios_consulting
card--profile-casual	Foto circular, bio breve, accesible	servicios_education, servicios_salud
card--service-glass	Glassmorphism, iconos animados	servicios_tech, servicios_creative
 
6. Microinteracciones y Animaciones CSS
6.1 Filosofía de Animación
Las microinteracciones siguen el principio "meaningful motion": cada animación debe proporcionar feedback, guiar al usuario o reforzar la jerarquía visual. Nunca son decorativas sin propósito.
6.2 Animaciones Base (Todos los Presets)
6.2.1 Hover de Botón
.btn-primary {
  transition: transform var(--transition-fast),
              box-shadow var(--transition-fast),
              background-color var(--transition-fast);
}
.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
}
.btn-primary:active {
  transform: translateY(0);
}
6.2.2 Hover de Card
.card {
  transition: transform var(--transition-normal),
              box-shadow var(--transition-normal);
}
.card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-xl);
}
6.2.3 Scroll Reveal
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
.scroll-reveal {
  animation: fadeInUp 0.6s var(--transition-spring) both;
}
6.3 Animaciones Premium por Contexto
6.3.1 Glow Effect (Tech Presets)
.glow-effect {
  position: relative;
}
.glow-effect::before {
  content: '';
  position: absolute;
  inset: -2px;
  background: linear-gradient(
    45deg,
    var(--color-primary),
    var(--color-accent),
    var(--color-secondary)
  );
  border-radius: inherit;
  z-index: -1;
  opacity: 0;
  filter: blur(8px);
  transition: opacity var(--transition-normal);
}
.glow-effect:hover::before {
  opacity: 0.7;
}
6.3.2 Glassmorphism Card
.card-glass {
  background: var(--glass-bg);
  backdrop-filter: var(--glass-blur);
  -webkit-backdrop-filter: var(--glass-blur);
  border: var(--glass-border);
  transition: background var(--transition-normal),
              backdrop-filter var(--transition-normal);
}
.card-glass:hover {
  background: rgba(255, 255, 255, 0.35);
  backdrop-filter: blur(16px);
}
6.3.3 Elegant Fade (Luxury Presets)
.elegant-reveal {
  opacity: 0;
  transform: translateY(10px);
  transition: opacity 0.8s ease, transform 0.8s ease;
}
.elegant-reveal.visible {
  opacity: 1;
  transform: translateY(0);
}
 
7. Agente IA de Personalización
7.1 Arquitectura del Agente
El sistema incorpora un agente IA conversacional que asiste al usuario durante el onboarding y permite refinamiento continuo del diseño.
7.1.1 Capacidades del Agente
•	Análisis de sector: Identifica automáticamente el tipo de negocio
•	Recomendación de preset: Sugiere el preset más apropiado
•	Refinamiento conversacional: Ajusta tokens según feedback
•	Generación de contenido: Crea textos iniciales adaptados al tono
•	Sugerencias de fotografía: Guía sobre estilo visual óptimo
7.1.2 Flujo de Interacción
// Ejemplo de prompt del agente
const styleAgentPrompt = `
Eres un consultor de branding digital experto.
El usuario tiene un negocio de: {business_type}
Preset seleccionado: {preset_id}

Tu rol es:
1. Validar si el preset es apropiado
2. Sugerir ajustes específicos de color/tipografía
3. Proporcionar copywriting inicial adaptado al tono
4. Recomendar estilo fotográfico

Mantén un tono profesional pero accesible.
Usa vocabulario del sector del usuario.
`;
7.2 API del Agente de Estilo
POST /api/v1/style-agent/analyze
Request:
{
  "business_description": "Bodega familiar...",
  "target_audience": "Consumidores gourmet",
  "current_preset": "agro_gourmet"
}

Response:
{
  "preset_fit_score": 0.92,
  "suggested_adjustments": {
    "--color-accent": "#8B0000",
    "reasoning": "Rojo más intenso para vinos tintos"
  },
  "content_suggestions": {
    "headline": "Tradición en cada copa",
    "tagline": "Vinos de la Sierra desde 1920"
  },
  "photography_guide": {
    "style": "Luz natural, fondos neutros",
    "subjects": ["Viñedos", "Barricas", "Catas"]
  }
}
7.3 Integración con Onboarding
El agente se activa en tres momentos clave:
1.	Selección de preset: Valida elección y sugiere alternativas
2.	Personalización de colores: Analiza coherencia y contraste
3.	Creación de contenido: Genera textos iniciales adaptados
 
8. Flujo de Onboarding Inteligente
8.1 Pasos del Onboarding
Paso 1: Selección de Vertical
El usuario indica el tipo de plataforma: AgroConecta, ComercioConecta o ServiciosConecta.
Paso 2: Detección de Sector
El sistema presenta los presets disponibles para el vertical seleccionado. Cada preset muestra:
•	Thumbnail de preview generado
•	Nombre descriptivo
•	Mood tags
•	Ejemplos de negocios target
Paso 3: Aplicación Automática
Al seleccionar un preset, el sistema aplica automáticamente:
•	Todos los design tokens
•	Variantes de componentes seleccionadas
•	Configuración de microinteracciones
•	Content starters y guías de copywriting
Paso 4: Personalización Guiada
El usuario puede ajustar sobre la base del preset:
•	Logo upload (sistema extrae colores automáticamente)
•	Ajuste de color primario (agente valida coherencia)
•	Selección de tipografía alternativa (dentro del mood)
8.2 Métricas de Onboarding
Métrica	Target	Medición
Time to First Preview	< 45 segundos	Desde registro hasta preview visual
Preset Selection Rate	> 80%	Usuarios que seleccionan un preset
Customization Depth	2-4 ajustes promedio	Cambios sobre preset base
Onboarding Completion	> 70%	Usuarios que completan setup
 
9. Implementación Frontend
9.1 Estructura de Archivos
/frontend
├── /src
│   ├── /styles
│   │   ├── /tokens
│   │   │   ├── base.css           # Tokens base
│   │   │   └── /presets
│   │   │       ├── agro_gourmet.css
│   │   │       ├── agro_organic.css
│   │   │       ├── comercio_boutique.css
│   │   │       └── ... (16 archivos)
│   │   ├── /components
│   │   │   ├── header.css
│   │   │   ├── hero.css
│   │   │   ├── cards.css
│   │   │   └── footer.css
│   │   └── /animations
│   │       ├── base.css
│   │       ├── premium.css
│   │       └── glassmorphism.css
│   ├── /components
│   │   ├── PresetPicker.jsx
│   │   ├── StyleAgent.jsx
│   │   └── ThemeProvider.jsx
│   └── /lib
│       ├── presetLoader.js
│       └── tokenInjector.js
└── /public
    └── /previews
        └── ... (thumbnails generados)
9.2 ThemeProvider React
// ThemeProvider.jsx
import { createContext, useContext, useEffect, useState } from 'react';
import { loadPreset, injectTokens } from '../lib/presetLoader';

const ThemeContext = createContext(null);

export function ThemeProvider({ children, tenantId }) {
  const [preset, setPreset] = useState(null);
  const [customTokens, setCustomTokens] = useState({});

  useEffect(() => {
    async function loadTenantTheme() {
      const config = await fetch(`/api/v1/tenants/${tenantId}/theme`);
      const { preset_id, custom_tokens } = await config.json();
      const presetData = await loadPreset(preset_id);
      setPreset(presetData);
      setCustomTokens(custom_tokens);
      injectTokens({ ...presetData.tokens, ...custom_tokens });
    }
    loadTenantTheme();
  }, [tenantId]);

  return (
    <ThemeContext.Provider value={{ preset, customTokens, setCustomTokens }}>
      {children}
    </ThemeContext.Provider>
  );
}
 
10. APIs y Endpoints
10.1 Endpoints de Presets
Método	Endpoint	Descripción
GET	/api/v1/presets	Lista todos los presets disponibles
GET	/api/v1/presets/{vertical}	Lista presets de un vertical
GET	/api/v1/presets/{id}	Detalle completo de un preset
GET	/api/v1/presets/{id}/preview	Thumbnail generado del preset
POST	/api/v1/tenants/{id}/theme	Aplica preset a tenant
PATCH	/api/v1/tenants/{id}/theme/tokens	Actualiza tokens personalizados
10.2 Endpoints del Agente IA
Método	Endpoint	Descripción
POST	/api/v1/style-agent/analyze	Analiza negocio y recomienda preset
POST	/api/v1/style-agent/refine	Sugiere ajustes sobre preset actual
POST	/api/v1/style-agent/content	Genera contenido adaptado al tono
POST	/api/v1/style-agent/validate-colors	Valida coherencia de paleta personalizada
 
11. Roadmap de Implementación
Sprint	Timeline	Entregables	Horas Est.
Sprint 1	Semana 1-2	Estructura JSON de presets. 5 presets piloto (1 por vertical). Base CSS tokens.	40-50h
Sprint 2	Semana 3-4	Sistema de carga de presets. ThemeProvider React. Inyección dinámica de tokens.	35-45h
Sprint 3	Semana 5-6	Variantes de componentes (header, hero, cards). Sistema de animaciones base.	40-50h
Sprint 4	Semana 7-8	Generador de thumbnails de preview. UI de selección de preset en onboarding.	30-40h
Sprint 5	Semana 9-10	11 presets adicionales (total 16). Animaciones premium (glassmorphism, glow).	45-55h
Sprint 6	Semana 11-12	Integración del Agente IA. APIs de style-agent. Endpoints de personalización.	50-60h
Sprint 7	Semana 13-14	Testing con usuarios reales. Ajustes finos. Documentación. QA. Go-live.	35-45h

Inversión total estimada: 275-345 horas
Timeline total: 14 semanas (3.5 meses)
11.1 Entregables por Fase
Fase 1 (Sprints 1-2): Fundamentos
•	Arquitectura de design tokens completa
•	5 presets MVP funcionales
•	Sistema de carga dinámica
Fase 2 (Sprints 3-4): Componentes
•	Biblioteca de variantes de componentes
•	Sistema de animaciones
•	UI de selección en onboarding
Fase 3 (Sprints 5-6): Premium
•	Catálogo completo de 16 presets
•	Efectos premium (glassmorphism, glow)
•	Agente IA de personalización
Fase 4 (Sprint 7): Producción
•	Testing y optimización
•	Documentación completa
•	Despliegue en producción
 
Anexo: Checklist de Implementación
•	□ Estructura de archivos CSS/JSON creada
•	□ Tokens base definidos en base.css
•	□ 16 archivos de preset generados
•	□ ThemeProvider React implementado
•	□ Sistema de inyección de tokens funcional
•	□ Variantes de componentes codificadas
•	□ Animaciones base y premium implementadas
•	□ Generador de thumbnails funcional
•	□ UI de PresetPicker en onboarding
•	□ APIs de presets documentadas y funcionando
•	□ Agente IA integrado con endpoints
•	□ Tests de accesibilidad WCAG 2.1 AA
•	□ Tests de rendimiento (LCP < 2.5s)
•	□ Documentación para desarrolladores
•	□ Guía de uso para administradores
— Fin del Documento —
Ecosistema Jaraba | Industry Style Presets v1.0
