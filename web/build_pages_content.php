<?php

/**
 * Build all Pepe Jaraba pages content with Canvas HTML + CSS.
 * Run: lando drush scr web/build_pages_content.php
 */

$etm = \Drupal::entityTypeManager();
$page_storage = $etm->getStorage('page_content');

// ═══════════════════════════════════════════════════════════════
// SHARED CSS (reused across all pages)
// ═══════════════════════════════════════════════════════════════
$sharedCSS = <<<'CSS'
* {
  --pj-primary: #FF8C42;
  --pj-secondary: #00A9A5;
  --pj-dark: #233D63;
  --pj-text: #2D3748;
  --pj-muted: #718096;
  --pj-light: #F7FAFC;
  --pj-white: #FFFFFF;
  --pj-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
  --pj-shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
  --pj-radius: 12px;
  box-sizing: border-box;
}
body { font-family: 'Roboto', sans-serif; color: var(--pj-text); line-height: 1.7; margin: 0; }
.pj-container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }
.pj-text-center { text-align: center; }
.pj-section { padding: 80px 0; }
.pj-section__title { font-family: 'Montserrat', sans-serif; font-size: 36px; font-weight: 700; color: var(--pj-dark); text-align: center; margin-bottom: 16px; }
.pj-section__subtitle { font-size: 18px; color: var(--pj-muted); text-align: center; max-width: 700px; margin: 0 auto 48px; }
.pj-grid { display: grid; gap: 32px; }
.pj-grid--2 { grid-template-columns: repeat(2, 1fr); }
.pj-grid--3 { grid-template-columns: repeat(3, 1fr); }
@media (max-width: 768px) { .pj-grid--2, .pj-grid--3 { grid-template-columns: 1fr; } }
.pj-btn { display: inline-block; padding: 14px 32px; border-radius: 9999px; font-family: 'Montserrat', sans-serif; font-weight: 600; font-size: 16px; text-decoration: none; transition: all 0.3s ease; border: 2px solid transparent; }
.pj-btn--primary { background: var(--pj-primary); color: var(--pj-white); border-color: var(--pj-primary); }
.pj-btn--primary:hover { background: #E67A33; transform: translateY(-2px); box-shadow: var(--pj-shadow-lg); }
.pj-btn--secondary { background: var(--pj-secondary); color: var(--pj-white); }
.pj-btn--outline { background: transparent; color: var(--pj-dark); border-color: var(--pj-dark); }
.pj-btn--lg { padding: 16px 40px; font-size: 18px; }
.pj-card { background: var(--pj-white); border-radius: var(--pj-radius); padding: 40px 32px; box-shadow: var(--pj-shadow); transition: all 0.3s ease; }
.pj-card:hover { transform: translateY(-4px); box-shadow: var(--pj-shadow-lg); }
.pj-card__icon { font-size: 48px; margin-bottom: 16px; }
.pj-card__title { font-family: 'Montserrat', sans-serif; font-size: 20px; font-weight: 700; color: var(--pj-dark); margin: 0 0 12px; }
.pj-card__text { font-size: 15px; color: var(--pj-muted); line-height: 1.7; margin: 0; }

.pj-hero-inner { position: relative; min-height: 50vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, var(--pj-dark) 0%, #1a2f4e 100%); }
.pj-hero-inner__content { position: relative; z-index: 2; text-align: center; max-width: 800px; padding: 60px 24px; }
.pj-hero-inner__title { font-family: 'Montserrat', sans-serif; font-size: 48px; font-weight: 800; color: var(--pj-white); line-height: 1.2; margin: 0 0 16px; }
.pj-hero-inner__subtitle { font-size: 18px; color: rgba(255,255,255,0.8); margin: 0; }

.pj-content { padding: 60px 0; }
.pj-content h2 { font-family: 'Montserrat', sans-serif; font-size: 28px; font-weight: 700; color: var(--pj-dark); margin: 48px 0 16px; }
.pj-content h3 { font-family: 'Montserrat', sans-serif; font-size: 22px; font-weight: 600; color: var(--pj-dark); margin: 32px 0 12px; }
.pj-content p { font-size: 17px; line-height: 1.8; color: var(--pj-text); margin: 0 0 20px; max-width: 800px; }
.pj-content blockquote { border-left: 4px solid var(--pj-primary); padding: 20px 32px; margin: 32px 0; background: var(--pj-light); border-radius: 0 var(--pj-radius) var(--pj-radius) 0; font-style: italic; font-size: 18px; color: var(--pj-dark); }

.pj-principle { display: flex; gap: 20px; align-items: flex-start; margin: 24px 0; padding: 24px; background: var(--pj-white); border-radius: var(--pj-radius); box-shadow: var(--pj-shadow); }
.pj-principle__number { font-family: 'Montserrat', sans-serif; font-size: 24px; font-weight: 800; color: var(--pj-primary); min-width: 40px; }
.pj-principle__title { font-family: 'Montserrat', sans-serif; font-size: 18px; font-weight: 700; color: var(--pj-dark); margin: 0 0 8px; }
.pj-principle__text { font-size: 15px; color: var(--pj-muted); margin: 0; }

.pj-phase { text-align: center; padding: 40px 32px; background: var(--pj-white); border-radius: var(--pj-radius); box-shadow: var(--pj-shadow); border-top: 4px solid var(--pj-primary); }
.pj-phase__number { font-family: 'Montserrat', sans-serif; font-size: 14px; font-weight: 700; color: var(--pj-primary); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 8px; }
.pj-phase__title { font-family: 'Montserrat', sans-serif; font-size: 22px; font-weight: 700; color: var(--pj-dark); margin: 0 0 16px; }
.pj-phase__desc { font-size: 15px; color: var(--pj-muted); line-height: 1.7; }
.pj-phase__deliverable { margin-top: 16px; padding: 12px 16px; background: var(--pj-light); border-radius: 8px; font-size: 13px; font-weight: 600; color: var(--pj-secondary); }

.pj-cta-block { background: linear-gradient(135deg, var(--pj-dark) 0%, #1a2f4e 100%); padding: 80px 0; text-align: center; }
.pj-cta-block__title { font-family: 'Montserrat', sans-serif; font-size: 36px; font-weight: 800; color: var(--pj-white); margin: 0 0 16px; }
.pj-cta-block__text { font-size: 18px; color: rgba(255,255,255,0.8); max-width: 600px; margin: 0 auto 32px; }

.pj-contact-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 48px; }
@media (max-width: 768px) { .pj-contact-grid { grid-template-columns: 1fr; } }
.pj-contact-info { padding: 40px; }
.pj-contact-info__item { display: flex; gap: 16px; align-items: flex-start; margin-bottom: 24px; }
.pj-contact-info__icon { font-size: 24px; min-width: 32px; }
.pj-contact-info__label { font-weight: 700; color: var(--pj-dark); font-size: 14px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
.pj-contact-info__value { color: var(--pj-text); font-size: 16px; }
.pj-contact-info__value a { color: var(--pj-primary); text-decoration: none; }

.pj-social-links { display: flex; gap: 16px; margin-top: 32px; }
.pj-social-link { display: flex; align-items: center; justify-content: center; width: 48px; height: 48px; border-radius: 50%; background: var(--pj-light); color: var(--pj-dark); text-decoration: none; font-size: 20px; transition: all 0.3s ease; }
.pj-social-link:hover { background: var(--pj-primary); color: var(--pj-white); }

.pj-legal { padding: 60px 0; }
.pj-legal h1 { font-family: 'Montserrat', sans-serif; font-size: 36px; font-weight: 700; color: var(--pj-dark); margin-bottom: 32px; }
.pj-legal h2 { font-family: 'Montserrat', sans-serif; font-size: 22px; font-weight: 600; color: var(--pj-dark); margin: 32px 0 12px; }
.pj-legal p { font-size: 16px; line-height: 1.8; color: var(--pj-text); margin-bottom: 16px; }
.pj-legal ul { padding-left: 24px; margin-bottom: 16px; }
.pj-legal li { font-size: 16px; line-height: 1.8; color: var(--pj-text); margin-bottom: 8px; }

.pj-empty-state { text-align: center; padding: 80px 24px; }
.pj-empty-state__icon { font-size: 64px; margin-bottom: 16px; }
.pj-empty-state__title { font-family: 'Montserrat', sans-serif; font-size: 24px; font-weight: 700; color: var(--pj-dark); margin-bottom: 8px; }
.pj-empty-state__text { font-size: 16px; color: var(--pj-muted); }

.pj-filter-bar { display: flex; gap: 12px; justify-content: center; margin-bottom: 48px; flex-wrap: wrap; }
.pj-filter { padding: 8px 20px; border-radius: 9999px; border: 2px solid var(--pj-border); background: var(--pj-white); color: var(--pj-muted); font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s; text-decoration: none; }
.pj-filter--active, .pj-filter:hover { background: var(--pj-primary); color: var(--pj-white); border-color: var(--pj-primary); }
CSS;

// ═══════════════════════════════════════════════════════════════
// PAGE CONTENT DEFINITIONS
// ═══════════════════════════════════════════════════════════════

$pages_content = [];

// ─── MANIFIESTO (ID 58) ───
$pages_content[58] = [
  'html' => <<<'HTML'
<section class="pj-hero-inner">
  <div class="pj-hero-inner__content">
    <h1 class="pj-hero-inner__title">Mi Historia es Simple: Vi un Puente Roto y Decidí Construirlo</h1>
    <p class="pj-hero-inner__subtitle">Más de 30 años construyendo ecosistemas digitales que conectan personas con oportunidades reales.</p>
  </div>
</section>

<section class="pj-content">
  <div class="pj-container">
    <h2>El Origen</h2>
    <p>Llevo más de 30 años trabajando en proyectos de desarrollo territorial y transformación digital. He gestionado más de 100 millones de euros en fondos europeos, diseñé planes estratégicos provinciales, puse en marcha la primera red WiFi rural de España y apoyé la transformación del sector ecológico en Andalucía.</p>
    <p>Suena impresionante en un currículum, pero lo que realmente me marcó fue lo que vi por el camino.</p>

    <h2>La Frustración: El Puente Roto</h2>
    <blockquote>Vi un puente roto entre los recursos masivos que existen y las personas que los necesitan.</blockquote>
    <p>Los fondos europeos existen. Las herramientas digitales existen. Los programas de apoyo existen. Pero entre todo eso y el tendero de barrio, el artesano, la profesional de más de 45 años que busca reinventarse... hay un abismo de burocracia, complejidad y tecnicismos que los convierte en inaccesibles.</p>
    <p>Mientras los informes hablaban de "ecosistemas de innovación" y "transformación digital 4.0", la realidad era mucho más simple: la gente necesita herramientas que entienda, pasos que pueda seguir y resultados que pueda medir.</p>

    <h2>La Misión: El Ecosistema Sin Humo</h2>
    <p>En 2019 fundé la Plataforma de Ecosistemas Digitales con una misión clara: hacer que la transformación digital sea práctica, accesible y real. No más PowerPoints prometiendo el futuro. No más herramientas que nadie sabe usar. No más métricas de vanidad.</p>

    <h2>Mi Compromiso Contigo: Los Principios Sin Humo</h2>
    <div class="pj-principle">
      <div class="pj-principle__number">01</div>
      <div>
        <div class="pj-principle__title">Realidad antes que Teoría</div>
        <p class="pj-principle__text">Solo comparto métodos probados en negocios reales. Si no ha funcionado en la práctica, no lo recomiendo.</p>
      </div>
    </div>
    <div class="pj-principle">
      <div class="pj-principle__number">02</div>
      <div>
        <div class="pj-principle__title">Simplicidad antes que Complejidad</div>
        <p class="pj-principle__text">La mejor herramienta es la que entiendes y usas. No necesitas la más cara ni la más sofisticada.</p>
      </div>
    </div>
    <div class="pj-principle">
      <div class="pj-principle__number">03</div>
      <div>
        <div class="pj-principle__title">Progreso antes que Perfección</div>
        <p class="pj-principle__text">Una acción pequeña hoy vale más que un plan perfecto para mañana. Empezamos con lo mínimo viable.</p>
      </div>
    </div>
    <div class="pj-principle">
      <div class="pj-principle__number">04</div>
      <div>
        <div class="pj-principle__title">Personas antes que Métricas de Vanidad</div>
        <p class="pj-principle__text">El éxito se mide en ventas reales, entrevistas conseguidas y negocios funcionando. No en likes.</p>
      </div>
    </div>
    <div class="pj-principle">
      <div class="pj-principle__number">05</div>
      <div>
        <div class="pj-principle__title">Transparencia Radical</div>
        <p class="pj-principle__text">Comparto mis éxitos y mis fracasos. No hay atajos mágicos. Hay trabajo, método y constancia.</p>
      </div>
    </div>
  </div>
</section>

<section class="pj-cta-block">
  <div class="pj-container">
    <h2 class="pj-cta-block__title">Únete a la Comunidad del Ecosistema</h2>
    <p class="pj-cta-block__text">Miles de profesionales, emprendedores y pymes ya están transformando su futuro digital. Sin humo.</p>
    <a href="https://plataformadeecosistemas.com" class="pj-btn pj-btn--primary pj-btn--lg" target="_blank">Acceder al Ecosistema →</a>
  </div>
</section>
HTML,
];

// ─── MÉTODO JARABA (ID 59) ───
$pages_content[59] = [
  'html' => <<<'HTML'
<section class="pj-hero-inner">
  <div class="pj-hero-inner__content">
    <h1 class="pj-hero-inner__title">El Método Jaraba: El Ciclo de Impacto Digital (CID)</h1>
    <p class="pj-hero-inner__subtitle">Un sistema de 3 fases diseñado para conseguir resultados reales. Sin humo, sin atajos, sin complejidad innecesaria.</p>
  </div>
</section>

<section class="pj-section">
  <div class="pj-container">
    <h2 class="pj-section__title">3 Fases, 90 Días, Resultados Medibles</h2>
    <p class="pj-section__subtitle">Cada ciclo te lleva del diagnóstico a la acción y de la acción a resultados que puedes medir.</p>
    <div class="pj-grid pj-grid--3">
      <div class="pj-phase">
        <div class="pj-phase__number">Fase 1 — Días 1-30</div>
        <h3 class="pj-phase__title">Diagnóstico y Hoja de Ruta</h3>
        <p class="pj-phase__desc">Analizamos tu punto de partida, definimos un objetivo claro a 90 días y creamos tu plan de acción personalizado. Sin adornos, directo al grano.</p>
        <div class="pj-phase__deliverable">Entregable: Plan de Impulso Digital</div>
      </div>
      <div class="pj-phase">
        <div class="pj-phase__number">Fase 2 — Días 31-60</div>
        <h3 class="pj-phase__title">Implementación y Acción</h3>
        <p class="pj-phase__desc">Construimos tus activos digitales mínimos, ejecutamos el plan paso a paso y conseguimos tu primera victoria medible. Acción sobre planificación.</p>
        <div class="pj-phase__deliverable">Entregable: Activos Digitales + Primera Victoria</div>
      </div>
      <div class="pj-phase">
        <div class="pj-phase__number">Fase 3 — Días 61-90</div>
        <h3 class="pj-phase__title">Optimización y Escalado</h3>
        <p class="pj-phase__desc">Analizamos datos reales, aprendemos qué funcionó e introducimos automatizaciones simples para que tu crecimiento sea sostenible.</p>
        <div class="pj-phase__deliverable">Entregable: Informe de Resultados + Plan de Siguiente Ciclo</div>
      </div>
    </div>
  </div>
</section>

<section class="pj-section" style="background: var(--pj-light);">
  <div class="pj-container">
    <h2 class="pj-section__title">Los 4 Principios No Negociables</h2>
    <div class="pj-grid pj-grid--2">
      <div class="pj-card">
        <h3 class="pj-card__title">Acción Mínima Viable</h3>
        <p class="pj-card__text">La acción más pequeña que produce el mayor resultado. No buscamos la perfección, buscamos el progreso. Un paso bien dado vale más que cien pasos planeados.</p>
      </div>
      <div class="pj-card">
        <h3 class="pj-card__title">Tecnología Humana</h3>
        <p class="pj-card__text">Las herramientas deben simplificar tu vida, no complicarla. Si no la entiendes, no es la herramienta correcta. Elegimos tecnología que sirve a las personas.</p>
      </div>
      <div class="pj-card">
        <h3 class="pj-card__title">Medición Real</h3>
        <p class="pj-card__text">Solo importan las métricas que se traducen en impacto tangible. Ventas, clientes, entrevistas, ahorros de tiempo. No likes ni seguidores vacíos.</p>
      </div>
      <div class="pj-card">
        <h3 class="pj-card__title">Proceso Cíclico</h3>
        <p class="pj-card__text">Cada ciclo de 90 días es un paso adelante. Diagnosticar, actuar, medir, optimizar. Y vuelta a empezar. Mejora continua sin agotamiento.</p>
      </div>
    </div>
  </div>
</section>

<section class="pj-cta-block">
  <div class="pj-container">
    <h2 class="pj-cta-block__title">Ve el Método en Acción</h2>
    <p class="pj-cta-block__text">Descubre cómo pymes, emprendedores y profesionales han aplicado el Ciclo de Impacto Digital para transformar su realidad.</p>
    <a href="/casos-de-exito" class="pj-btn pj-btn--primary pj-btn--lg">Ver Casos de Éxito →</a>
  </div>
</section>
HTML,
];

// ─── CASOS DE ÉXITO (ID 60) ───
$pages_content[60] = [
  'html' => <<<'HTML'
<section class="pj-hero-inner">
  <div class="pj-hero-inner__content">
    <h1 class="pj-hero-inner__title">Resultados Reales, Historias Reales</h1>
    <p class="pj-hero-inner__subtitle">No hablamos de teorías. Aquí encontrarás impacto tangible sin adornos ni métricas de vanidad.</p>
  </div>
</section>

<section class="pj-section">
  <div class="pj-container">
    <div class="pj-filter-bar">
      <a href="#" class="pj-filter pj-filter--active">Todos</a>
      <a href="#" class="pj-filter">Pymes</a>
      <a href="#" class="pj-filter">Emprendimiento</a>
      <a href="#" class="pj-filter">Empleabilidad</a>
    </div>
    <div class="pj-empty-state">
      <div class="pj-empty-state__icon">📋</div>
      <h3 class="pj-empty-state__title">Próximamente</h3>
      <p class="pj-empty-state__text">Estamos documentando los primeros casos de éxito del Ciclo de Impacto Digital. Pronto podrás leer historias reales de transformación.</p>
    </div>
  </div>
</section>

<section class="pj-cta-block">
  <div class="pj-container">
    <h2 class="pj-cta-block__title">¿Quieres ser el próximo caso de éxito?</h2>
    <p class="pj-cta-block__text">Empieza tu Ciclo de Impacto Digital hoy. 90 días para resultados medibles.</p>
    <a href="/contacto" class="pj-btn pj-btn--primary pj-btn--lg">Empezar ahora →</a>
  </div>
</section>
HTML,
];

// ─── BLOG (ID 61) ───
$pages_content[61] = [
  'html' => <<<'HTML'
<section class="pj-hero-inner">
  <div class="pj-hero-inner__content">
    <h1 class="pj-hero-inner__title">Blog: Ideas y Estrategias Sin Humo</h1>
    <p class="pj-hero-inner__subtitle">Estrategias prácticas de transformación digital para impulsar tu proyecto. Sin tecnicismos, sin humo.</p>
  </div>
</section>

<section class="pj-section">
  <div class="pj-container">
    <div class="pj-filter-bar">
      <a href="#" class="pj-filter pj-filter--active">Todos</a>
      <a href="#" class="pj-filter">Casos de éxito</a>
      <a href="#" class="pj-filter">Guías prácticas</a>
      <a href="#" class="pj-filter">Marca personal</a>
      <a href="#" class="pj-filter">Tendencias</a>
    </div>
    <div class="pj-empty-state">
      <div class="pj-empty-state__icon">✍️</div>
      <h3 class="pj-empty-state__title">Pronto nuevos artículos</h3>
      <p class="pj-empty-state__text">Estamos preparando contenido de alto valor sobre transformación digital práctica. Mientras tanto, descarga tu Kit de Impulso Gratuito para empezar a actuar hoy.</p>
      <div style="margin-top: 24px;">
        <a href="#" class="pj-btn pj-btn--primary">Descargar Kit de Impulso →</a>
      </div>
    </div>
  </div>
</section>
HTML,
];

// ─── CONTACTO (ID 62) ───
$pages_content[62] = [
  'html' => <<<'HTML'
<section class="pj-hero-inner">
  <div class="pj-hero-inner__content">
    <h1 class="pj-hero-inner__title">Hablemos. Sin Humo</h1>
    <p class="pj-hero-inner__subtitle">Elige el canal que mejor te funcione. Respuesta en menos de 48 horas.</p>
  </div>
</section>

<section class="pj-section">
  <div class="pj-container">
    <div class="pj-contact-grid">
      <div class="pj-contact-info">
        <h2 style="font-family: 'Montserrat', sans-serif; font-size: 28px; font-weight: 700; color: var(--pj-dark); margin: 0 0 32px;">Canales de Contacto</h2>

        <div class="pj-contact-info__item">
          <div class="pj-contact-info__icon">📧</div>
          <div>
            <div class="pj-contact-info__label">Email</div>
            <div class="pj-contact-info__value"><a href="mailto:info@pepejaraba.com">info@pepejaraba.com</a></div>
          </div>
        </div>

        <div class="pj-contact-info__item">
          <div class="pj-contact-info__icon">📱</div>
          <div>
            <div class="pj-contact-info__label">WhatsApp</div>
            <div class="pj-contact-info__value"><a href="https://wa.me/34623174304">+34 623 174 304</a></div>
          </div>
        </div>

        <div class="pj-contact-info__item">
          <div class="pj-contact-info__icon">📍</div>
          <div>
            <div class="pj-contact-info__label">Base de Operaciones</div>
            <div class="pj-contact-info__value">Calle Héroe de Sostoa 12<br>29002 Málaga, España</div>
          </div>
        </div>

        <h3 style="font-family: 'Montserrat', sans-serif; font-weight: 700; color: var(--pj-dark); margin-top: 32px;">Conecta en Redes</h3>
        <div class="pj-social-links">
          <a href="https://www.linkedin.com/in/pepejaraba/" class="pj-social-link" target="_blank" title="LinkedIn">in</a>
          <a href="https://www.facebook.com/PepeJaraba" class="pj-social-link" target="_blank" title="Facebook">f</a>
          <a href="https://www.instagram.com/pepejaraba_/" class="pj-social-link" target="_blank" title="Instagram">ig</a>
          <a href="https://www.youtube.com/@PepeJaraba" class="pj-social-link" target="_blank" title="YouTube">yt</a>
        </div>
      </div>

      <div>
        <div class="pj-card" style="padding: 48px 40px;">
          <h3 style="font-family: 'Montserrat', sans-serif; font-size: 24px; font-weight: 700; color: var(--pj-dark); margin: 0 0 24px;">Envía tu Consulta</h3>
          <form>
            <div style="margin-bottom: 20px;">
              <label style="display: block; font-weight: 600; color: var(--pj-dark); margin-bottom: 6px; font-size: 14px;">Nombre completo</label>
              <input type="text" placeholder="Tu nombre" style="width: 100%; padding: 12px 16px; border: 2px solid #E2E8F0; border-radius: 8px; font-size: 16px; transition: border-color 0.2s;" />
            </div>
            <div style="margin-bottom: 20px;">
              <label style="display: block; font-weight: 600; color: var(--pj-dark); margin-bottom: 6px; font-size: 14px;">Email</label>
              <input type="email" placeholder="tu@email.com" style="width: 100%; padding: 12px 16px; border: 2px solid #E2E8F0; border-radius: 8px; font-size: 16px;" />
            </div>
            <div style="margin-bottom: 20px;">
              <label style="display: block; font-weight: 600; color: var(--pj-dark); margin-bottom: 6px; font-size: 14px;">¿Cómo puedo ayudarte?</label>
              <select style="width: 100%; padding: 12px 16px; border: 2px solid #E2E8F0; border-radius: 8px; font-size: 16px; background: white;">
                <option>Consulta general</option>
                <option>Quiero impulsar mi negocio</option>
                <option>Quiero emprender</option>
                <option>Busco empleo / marca personal</option>
                <option>Conferencias y charlas</option>
              </select>
            </div>
            <div style="margin-bottom: 24px;">
              <label style="display: block; font-weight: 600; color: var(--pj-dark); margin-bottom: 6px; font-size: 14px;">Mensaje</label>
              <textarea rows="4" placeholder="Cuéntame en qué puedo ayudarte..." style="width: 100%; padding: 12px 16px; border: 2px solid #E2E8F0; border-radius: 8px; font-size: 16px; resize: vertical;"></textarea>
            </div>
            <button type="submit" class="pj-btn pj-btn--primary" style="width: 100%; text-align: center; cursor: pointer;">Enviar consulta →</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
HTML,
];

// ─── AVISO LEGAL (ID 63) ───
$pages_content[63] = [
  'html' => <<<'HTML'
<div class="pj-legal">
  <div class="pj-container">
    <h1>Aviso Legal</h1>
    <p><strong>Última actualización:</strong> Febrero 2026</p>

    <h2>Datos Identificativos</h2>
    <p>En cumplimiento del artículo 10 de la Ley 34/2002, de 11 de julio, de Servicios de la Sociedad de la Información y Comercio Electrónico, se informa:</p>
    <ul>
      <li><strong>Titular:</strong> Plataforma de Ecosistemas Digitales S.L.</li>
      <li><strong>NIF:</strong> B93750271</li>
      <li><strong>Domicilio:</strong> Calle Héroe de Sostoa 12, 29002 Málaga</li>
      <li><strong>Email:</strong> info@pepejaraba.com</li>
      <li><strong>Registro Mercantil:</strong> Inscrita en el Registro Mercantil de Málaga</li>
    </ul>

    <h2>Objeto</h2>
    <p>El presente sitio web tiene como finalidad proporcionar información sobre los servicios de consultoría en transformación digital ofrecidos por Pepe Jaraba a través de Plataforma de Ecosistemas Digitales S.L.</p>

    <h2>Propiedad Intelectual</h2>
    <p>Todos los contenidos del sitio web, incluyendo textos, imágenes, diseño gráfico, código fuente, logos y demás elementos, son propiedad de Plataforma de Ecosistemas Digitales S.L. o de sus legítimos titulares y están protegidos por las leyes de propiedad intelectual e industrial.</p>

    <h2>Responsabilidad</h2>
    <p>Plataforma de Ecosistemas Digitales S.L. no se hace responsable del mal uso que se realice de los contenidos de su página web, siendo exclusiva responsabilidad de la persona que accede a ellos o los utiliza.</p>

    <h2>Ley Aplicable y Jurisdicción</h2>
    <p>Las presentes condiciones se rigen por la legislación española. Para cualquier controversia que pudiera derivarse del acceso o uso de este sitio web, las partes se someten a los Juzgados y Tribunales de Málaga.</p>
  </div>
</div>
HTML,
];

// ─── POLÍTICA DE PRIVACIDAD (ID 64) ───
$pages_content[64] = [
  'html' => <<<'HTML'
<div class="pj-legal">
  <div class="pj-container">
    <h1>Política de Privacidad</h1>
    <p><strong>Última actualización:</strong> Febrero 2026</p>

    <h2>Responsable del Tratamiento</h2>
    <ul>
      <li><strong>Identidad:</strong> Plataforma de Ecosistemas Digitales S.L.</li>
      <li><strong>NIF:</strong> B93750271</li>
      <li><strong>Dirección:</strong> Calle Héroe de Sostoa 12, 29002 Málaga</li>
      <li><strong>Email:</strong> info@pepejaraba.com</li>
    </ul>

    <h2>Finalidad del Tratamiento</h2>
    <p>Los datos personales recabados a través de los formularios de contacto serán tratados con las siguientes finalidades:</p>
    <ul>
      <li>Gestionar las consultas realizadas a través del formulario de contacto.</li>
      <li>Enviar comunicaciones comerciales sobre nuestros servicios, solo si ha dado su consentimiento expreso.</li>
      <li>Mejorar la experiencia de navegación del usuario.</li>
    </ul>

    <h2>Base Legal</h2>
    <p>El tratamiento de sus datos se basa en el consentimiento del interesado (art. 6.1.a RGPD) y en el interés legítimo del responsable (art. 6.1.f RGPD).</p>

    <h2>Derechos del Usuario</h2>
    <p>Puede ejercer sus derechos de acceso, rectificación, supresión, limitación, portabilidad y oposición escribiendo a info@pepejaraba.com, adjuntando copia de su DNI.</p>

    <h2>Conservación de Datos</h2>
    <p>Los datos personales se conservarán mientras exista una relación comercial o el interesado no solicite su supresión, y en todo caso conforme a los plazos legales aplicables.</p>

    <h2>Destinatarios</h2>
    <p>No se cederán datos a terceros salvo obligación legal.</p>
  </div>
</div>
HTML,
];

// ─── POLÍTICA DE COOKIES (ID 65) ───
$pages_content[65] = [
  'html' => <<<'HTML'
<div class="pj-legal">
  <div class="pj-container">
    <h1>Política de Cookies</h1>
    <p><strong>Última actualización:</strong> Febrero 2026</p>

    <h2>¿Qué son las Cookies?</h2>
    <p>Las cookies son pequeños archivos de texto que se almacenan en su dispositivo cuando visita un sitio web. Se utilizan para mejorar la experiencia de navegación y para recopilar información estadística.</p>

    <h2>Tipos de Cookies que Utilizamos</h2>

    <h3>Cookies Técnicas (Necesarias)</h3>
    <p>Son esenciales para el funcionamiento del sitio web. Permiten la navegación y el uso de funcionalidades básicas. No requieren consentimiento.</p>

    <h3>Cookies Analíticas</h3>
    <p>Nos permiten analizar el comportamiento de los usuarios de forma agregada para mejorar el sitio web. Utilizamos Google Analytics 4 con anonimización de IP.</p>

    <h3>Cookies de Preferencias</h3>
    <p>Almacenan las preferencias del usuario (idioma, región) para personalizar la experiencia.</p>

    <h2>Gestión de Cookies</h2>
    <p>Puede configurar su navegador para rechazar cookies o para que le avise cuando un sitio web intenta colocar una cookie. Tenga en cuenta que rechazar las cookies técnicas puede afectar al funcionamiento del sitio.</p>

    <h2>Actualización de esta Política</h2>
    <p>Esta política de cookies puede ser actualizada periódicamente. Le recomendamos revisarla de forma regular.</p>
  </div>
</div>
HTML,
];

// ═══════════════════════════════════════════════════════════════
// APPLY CONTENT TO ALL PAGES
// ═══════════════════════════════════════════════════════════════

foreach ($pages_content as $page_id => $content) {
  $page = $page_storage->load($page_id);
  if (!$page) {
    echo "ERROR: Page {$page_id} not found\n";
    continue;
  }

  $canvas_data = json_encode([
    'html' => $content['html'],
    'css' => $sharedCSS,
    'components' => [],
    'styles' => [],
  ]);

  $page->set('canvas_data', $canvas_data);
  $page->set('rendered_html', $content['html']);
  $page->set('layout_mode', 'canvas');
  $page->save();

  echo "✓ {$page->label()} (ID: {$page_id}) - content saved\n";
}

echo "\n=== ALL PAGES CONTENT BUILT SUCCESSFULLY ===\n";
echo "Pages updated: " . count($pages_content) . "\n";
echo "\nYou can now open each page in the Canvas Editor:\n";
foreach ($pages_content as $page_id => $content) {
  $page = $page_storage->load($page_id);
  echo "  {$page->label()}: /page/{$page_id}/editor\n";
}
