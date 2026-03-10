#!/usr/bin/env php
<?php

/**
 * @file
 * Update /sobre-nosotros (ID 83) and /equipo (ID 87) pages.
 *
 * Makes co-founder info consistent:
 * - Both pages now feature Jose Jaraba Munoz AND Remedios Estevez Palomino
 * - Bios consistent with reclutamiento landing world-class level
 * - Professional photos (webp) for both founders
 *
 * Usage: lando drush scr scripts/maintenance/update-cofounders-pages.php
 */

declare(strict_types=1);

$storage = \Drupal::entityTypeManager()->getStorage('page_content');

// ============================================================================
// 1. UPDATE /sobre-nosotros (Entity 83)
// ============================================================================

$sobre_nosotros_html = <<<'HTML'
<div class="ped-page">
  <section class="ped-page-header">
    <span class="ped-page-header__eyebrow">Fundada en 2020</span>
    <h1>Infraestructura digital para personas, pymes y territorios</h1>
    <p class="ped-page-header__subtitle">Plataforma de Ecosistemas Digitales S.L. opera el Ecosistema Jaraba: una empresa fundada en 2020 que recoge mas de 50 anos de experiencia combinada de sus cofundadores en transformacion territorial, convertidos en tecnologia SaaS de impacto.</p>
  </section>

  <section class="ped-section">
    <div class="ped-section__container">
      <div class="ped-grid ped-grid--4">
        <div class="ped-kpi">
          <div class="ped-kpi__value">+50</div>
          <div class="ped-kpi__label">anos de experiencia combinada</div>
        </div>
        <div class="ped-kpi">
          <div class="ped-kpi__value ped-kpi__value--green">+100M&euro;</div>
          <div class="ped-kpi__label">en fondos gestionados</div>
        </div>
        <div class="ped-kpi">
          <div class="ped-kpi__value ped-kpi__value--gold">+500</div>
          <div class="ped-kpi__label">proyectos acompanados</div>
        </div>
        <div class="ped-kpi">
          <div class="ped-kpi__value ped-kpi__value--orange">10</div>
          <div class="ped-kpi__label">verticales SaaS</div>
        </div>
      </div>
    </div>
  </section>

  <section class="ped-section ped-section--alt">
    <div class="ped-section__narrow">
      <h2>La historia</h2>
      <p>En 1994, Jose Jaraba empezo a trabajar en programas de empleo y formacion en municipios rurales de Andalucia. En paralelo, Remedios Estevez desarrollaba su carrera en gestion de servicios publicos, desarrollo economico local y formacion profesional. Lo que comenzo como intervencion social directa — talleres, orientacion, itinerarios personalizados — se convirtio en mas de cinco decadas combinadas de experiencia en transformacion territorial.</p>
      <p>En 2020, ambos cofundaron Plataforma de Ecosistemas Digitales S.L., una empresa tecnologica que traslada el Metodo Jaraba — un marco probado de intervencion territorial — a una plataforma SaaS multi-vertical con inteligencia artificial.</p>
      <p><strong>El resultado:</strong> una infraestructura digital respaldada por mas de 100 millones de euros en fondos europeos gestionados, mas de 500 proyectos de emprendimiento y empleo acompanados, y 10 verticales especializados con 11 agentes de IA.</p>
    </div>
  </section>

  <section class="ped-section">
    <div class="ped-section__container">
      <h2>Mision, Vision y Valores</h2>
      <div class="ped-grid ped-grid--3">
        <div class="ped-card ped-card--gold-top">
          <div class="ped-card__title">Mision</div>
          <p class="ped-card__text">Democratizar el acceso a la transformacion digital para personas, pymes y territorios rurales, proporcionando herramientas tecnologicas accesibles y programas de capacitacion adaptados a cada contexto.</p>
        </div>
        <div class="ped-card ped-card--gold-top">
          <div class="ped-card__title">Vision</div>
          <p class="ped-card__text">Ser la infraestructura digital de referencia para el desarrollo rural sostenible en el mundo hispanohablante, conectando instituciones, empresas y personas en un ecosistema de impacto medible.</p>
        </div>
        <div class="ped-card ped-card--gold-top">
          <div class="ped-card__title">Filosofia: Sin Humo</div>
          <p class="ped-card__text">Transparencia radical. Datos verificables, metricas reales, resultados medibles. Cada funcionalidad existe porque resuelve un problema real validado en campo.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="ped-section ped-section--alt">
    <div class="ped-section__container">
      <h2>Valores que nos definen</h2>
      <div class="ped-grid ped-grid--4">
        <div class="ped-card ped-card--centered">
          <div class="ped-card__title">Impacto medible</div>
          <p class="ped-card__text">Cada accion tiene un indicador. Si no se mide, no existe.</p>
        </div>
        <div class="ped-card ped-card--centered">
          <div class="ped-card__title">Accesibilidad</div>
          <p class="ped-card__text">Tecnologia al servicio de todos, adaptada a contextos rurales.</p>
        </div>
        <div class="ped-card ped-card--centered">
          <div class="ped-card__title">Comunidad</div>
          <p class="ped-card__text">El valor se genera en red. Cada actor del ecosistema fortalece al conjunto.</p>
        </div>
        <div class="ped-card ped-card--centered">
          <div class="ped-card__title">Innovacion responsable</div>
          <p class="ped-card__text">IA con limites eticos, datos privados, RGPD desde el diseno.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Cofundadores — seccion resumen con enlace a /equipo -->
  <section class="ped-section">
    <div class="ped-section__container">
      <h2>Nuestros cofundadores</h2>
      <p class="ped-section__intro">Mas de 50 anos de experiencia combinada en transformacion territorial, fondos europeos, desarrollo economico local y tecnologia de impacto.</p>

      <div class="ped-grid ped-grid--2">
        <!-- Jose Jaraba -->
        <div class="ped-card ped-card--founder">
          <div class="ped-founder__photo-wrapper">
            <img src="/themes/custom/ecosistema_jaraba_theme/images/equipo-pepe-jaraba.webp"
                 srcset="/themes/custom/ecosistema_jaraba_theme/images/equipo-pepe-jaraba.webp 2x"
                 alt="Jose Jaraba Munoz — Cofundador y CEO de PED"
                 class="ped-founder__photo"
                 width="280" height="280"
                 loading="lazy" />
          </div>
          <div class="ped-founder__name">Jose Jaraba Munoz</div>
          <div class="ped-founder__title">Cofundador y CEO</div>
          <div class="ped-founder__subtitle">Jurista &middot; Experto en Desarrollo Rural Territorial &middot; +30 anos</div>
          <p class="ped-founder__bio">Licenciado en Derecho por la UCO, tres Masters, doctorando y certificado EOQ Quality System Manager. Ha gestionado +100M&euro; en fondos europeos y acompanado +500 proyectos de emprendimiento y empleo en +120 municipios rurales.</p>
          <div class="ped-founder__social">
            <a href="/equipo" class="ped-founder__social-link">
              <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
              Ver perfil completo
            </a>
            <a href="https://www.linkedin.com/in/pepejaraba/" target="_blank" rel="noopener noreferrer" class="ped-founder__social-link" aria-label="LinkedIn de Pepe Jaraba">
              <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
              LinkedIn
            </a>
          </div>
        </div>

        <!-- Remedios Estevez -->
        <div class="ped-card ped-card--founder">
          <div class="ped-founder__photo-wrapper">
            <img src="/themes/custom/ecosistema_jaraba_theme/images/equipo-remedios-estevez.webp"
                 srcset="/themes/custom/ecosistema_jaraba_theme/images/equipo-remedios-estevez.webp 2x"
                 alt="Remedios Estevez Palomino — Cofundadora y COO de PED"
                 class="ped-founder__photo"
                 width="280" height="280"
                 loading="lazy" />
          </div>
          <div class="ped-founder__name">Remedios Estevez Palomino</div>
          <div class="ped-founder__title">Cofundadora y COO</div>
          <div class="ped-founder__subtitle">Economista &middot; Experta en Desarrollo Economico Local &middot; +20 anos</div>
          <p class="ped-founder__bio">Licenciada en Economia con mas de 20 anos de experiencia en gestion de servicios publicos, desarrollo economico local y formacion profesional. Gerencia una empresa municipal que promueve el desarrollo economico local a traves del acompanamiento al emprendimiento y la mejora de la empleabilidad.</p>
          <div class="ped-founder__social">
            <a href="/equipo#remedios-estevez" class="ped-founder__social-link">
              <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
              Ver perfil completo
            </a>
            <a href="https://www.linkedin.com/in/remedios-estevez/" target="_blank" rel="noopener noreferrer" class="ped-founder__social-link" aria-label="LinkedIn de Remedios Estevez">
              <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
              LinkedIn
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="ped-cta-saas">
    <div class="ped-cta-saas__container">
      <h2>Quiere conocer la plataforma?</h2>
      <p>Le mostramos como funciona el Ecosistema Jaraba con una demo personalizada.</p>
      <div class="ped-cta-saas__actions">
        <a href="/contacto" class="btn-gold">Solicitar demo</a>
      </div>
    </div>
  </section>
</div>
HTML;

$entity83 = $storage->load(83);
if ($entity83) {
  $canvas_data = json_encode(['html' => $sobre_nosotros_html, 'css' => ''], JSON_UNESCAPED_UNICODE);
  $entity83->set('canvas_data', $canvas_data);
  $entity83->save();
  echo "Updated /sobre-nosotros (ID 83) with both co-founders.\n";
}
else {
  echo "ERROR: Entity 83 not found.\n";
}

// ============================================================================
// 2. UPDATE /equipo (Entity 87)
// ============================================================================

$equipo_html = <<<'HTML'
<div class="ped-page">
  <section class="ped-page-header">
    <span class="ped-page-header__eyebrow">Las personas detras del ecosistema</span>
    <h1>Equipo Directivo</h1>
    <p class="ped-page-header__subtitle">Mas de 50 anos de experiencia combinada en desarrollo territorial, fondos europeos, gestion publica, derecho comunitario y tecnologia de impacto.</p>
  </section>

  <!-- ================================================================ -->
  <!-- IMPACT BANNER — Cifras conjuntas de los cofundadores              -->
  <!-- ================================================================ -->
  <section class="ped-section ped-section--impact">
    <div class="ped-section__container">
      <div class="ped-grid ped-grid--3">
        <div class="ped-kpi ped-kpi--highlight">
          <div class="ped-kpi__value">+50</div>
          <div class="ped-kpi__label">anos de experiencia combinada</div>
        </div>
        <div class="ped-kpi ped-kpi--highlight">
          <div class="ped-kpi__value ped-kpi__value--green">+500</div>
          <div class="ped-kpi__label">proyectos acompanados</div>
        </div>
        <div class="ped-kpi ped-kpi--highlight">
          <div class="ped-kpi__value ped-kpi__value--gold">+100M&euro;</div>
          <div class="ped-kpi__label">en fondos europeos gestionados</div>
        </div>
      </div>
    </div>
  </section>

  <!-- ================================================================ -->
  <!-- PERFIL COFUNDADOR 1 — Jose "Pepe" Jaraba Munoz                   -->
  <!-- ================================================================ -->
  <section class="ped-section" id="pepe-jaraba">
    <div class="ped-section__narrow">
      <div class="ped-founder__hero">
        <div class="ped-founder__photo-wrapper">
          <img src="/themes/custom/ecosistema_jaraba_theme/images/equipo-pepe-jaraba.webp"
               srcset="/themes/custom/ecosistema_jaraba_theme/images/equipo-pepe-jaraba.webp 2x"
               alt="Jose Jaraba Munoz — Cofundador y CEO de Plataforma de Ecosistemas Digitales"
               class="ped-founder__photo"
               width="280" height="280"
               loading="eager" />
        </div>
        <div>
          <div class="ped-founder__name">Jose Jaraba Munoz</div>
          <div class="ped-founder__title">Cofundador y CEO</div>
          <div class="ped-founder__subtitle">Jurista &middot; Experto en Desarrollo Rural Territorial &middot; Doctorando UCO</div>
          <div class="ped-founder__badges">
            <span class="ped-founder__badge">Derecho Comunitario</span>
            <span class="ped-founder__badge">Fondos Europeos</span>
            <span class="ped-founder__badge">LEADER/PRODER/FSE/FEDER</span>
            <span class="ped-founder__badge">EOQ Quality Manager</span>
            <span class="ped-founder__badge">ITIL Foundation</span>
          </div>
          <p class="ped-founder__bio">Licenciado en Derecho por la Universidad de Cordoba, con tres Masters (Gestion de Turismo Ambiental por la UPM, Gestion del Desarrollo Rural por la UCO y MBA en proceso), doctorando en la UCO y certificado EOQ Quality System Manager e ITIL Foundation. Pepe Jaraba combina una formacion juridica de alto nivel con mas de 30 anos de experiencia practica en la interseccion entre politicas publicas, fondos europeos, desarrollo territorial y tecnologia.</p>
          <p class="ped-founder__bio">Ha dirigido grupos de desarrollo rural que han gestionado mas de 100 millones de euros en fondos europeos (LEADER, PRODER, FSE, FEDER), ha acompanado mas de 500 proyectos de emprendimiento y empleo, y ha transformado mas de 120 municipios rurales. En 2020 cofundo PED para convertir esa experiencia en tecnologia SaaS de impacto.</p>
          <div class="ped-founder__social">
            <a href="https://www.linkedin.com/in/pepejaraba/" target="_blank" rel="noopener noreferrer" class="ped-founder__social-link" aria-label="Perfil de LinkedIn de Pepe Jaraba">
              <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
              LinkedIn
            </a>
            <a href="https://pepejaraba.com" target="_blank" rel="noopener noreferrer" class="ped-founder__social-link" aria-label="Meta-sitio personal de Pepe Jaraba">
              <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
              pepejaraba.com
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Cita de Pepe -->
  <section class="ped-section ped-section--alt">
    <div class="ped-section__narrow">
      <div class="ped-founder__quote">
        <p class="ped-founder__quote-text">La tecnologia solo tiene sentido si resuelve problemas reales de personas reales. Llevamos 30 anos en el terreno — en municipios rurales, en programas de empleo, en despachos de abogados. Todo lo que construimos nace de esa experiencia, no de una pizarra en Silicon Valley.</p>
        <div class="ped-founder__quote-attr">&mdash; Jose Jaraba Munoz, Cofundador y CEO</div>
      </div>
    </div>
  </section>

  <!-- Trayectoria profesional Pepe -->
  <section class="ped-section">
    <div class="ped-section__narrow">
      <h2>Trayectoria profesional &mdash; Jose Jaraba</h2>
      <div class="ped-founder__timeline">
        <div class="ped-founder__timeline-item">
          <div class="ped-founder__timeline-date">2019 &ndash; Presente</div>
          <div class="ped-founder__timeline-title">Cofundador y CEO</div>
          <div class="ped-founder__timeline-org">Plataforma de Ecosistemas Digitales S.L. (PED)</div>
          <p class="ped-founder__timeline-desc">Fundacion y direccion de la empresa que opera el Ecosistema Jaraba: plataforma SaaS multi-vertical con 10 verticales, 11 agentes IA, integracion con fondos europeos y presencia en +120 municipios. Arquitectura de producto, estrategia B2G e inversion.</p>
        </div>
        <div class="ped-founder__timeline-item">
          <div class="ped-founder__timeline-date">2015 &ndash; 2019</div>
          <div class="ped-founder__timeline-title">Director Gerente</div>
          <div class="ped-founder__timeline-org">SODEPO S.L. &mdash; Sociedad para el Desarrollo Economico de Puente Genil (Ayuntamiento)</div>
          <p class="ped-founder__timeline-desc">Direccion de la sociedad municipal de desarrollo economico. Gestion de programas de empleo, formacion profesional, emprendimiento y atraccion de inversiones para el municipio.</p>
        </div>
        <div class="ped-founder__timeline-item">
          <div class="ped-founder__timeline-date">2014</div>
          <div class="ped-founder__timeline-title">Experto independiente</div>
          <div class="ped-founder__timeline-org">Fundacion MADECA &mdash; Diputacion de Malaga</div>
          <p class="ped-founder__timeline-desc">Consultoria especializada en planificacion estrategica territorial y fondos europeos para la provincia de Malaga.</p>
        </div>
        <div class="ped-founder__timeline-item">
          <div class="ped-founder__timeline-date">2011 &ndash; 2014</div>
          <div class="ped-founder__timeline-title">Profesor externo universitario</div>
          <div class="ped-founder__timeline-org">Universidad de Cordoba y Universidad de Jaen</div>
          <p class="ped-founder__timeline-desc">Docencia en programas de Desarrollo Rural Territorial. Transferencia de conocimiento practico sobre intervencion en territorios rurales y gestion de fondos europeos.</p>
        </div>
        <div class="ped-founder__timeline-item">
          <div class="ped-founder__timeline-date">2001 &ndash; 2015</div>
          <div class="ped-founder__timeline-title">Director Gerente y fundador</div>
          <div class="ped-founder__timeline-org">Ingenova Consulting S.L.</div>
          <p class="ped-founder__timeline-desc">Consultora especializada en innovacion TIC para el medio rural. Diseno e implementacion de estrategias de transformacion digital territorial, incluyendo telecentros, formacion digital y conectividad rural.</p>
        </div>
        <div class="ped-founder__timeline-item">
          <div class="ped-founder__timeline-date">1997 &ndash; 2015</div>
          <div class="ped-founder__timeline-title">Director Gerente</div>
          <div class="ped-founder__timeline-org">Asociacion Grupo de Desarrollo Rural Campina Sur Cordobesa</div>
          <p class="ped-founder__timeline-desc">Direccion durante 18 anos del principal grupo de desarrollo rural de la provincia de Cordoba. Gestion directa de +100 millones de euros en politicas europeas (LEADER, PRODER, FSE, FEDER). Coordinacion de programas de empleo, formacion y emprendimiento con impacto en +50.000 beneficiarios directos.</p>
        </div>
        <div class="ped-founder__timeline-item">
          <div class="ped-founder__timeline-date">1992 &ndash; 1995</div>
          <div class="ped-founder__timeline-title">Director de Consultoria</div>
          <div class="ped-founder__timeline-org">EJ&amp;JE Consultores Reunidos</div>
          <p class="ped-founder__timeline-desc">Consultoria juridica y de desarrollo territorial en Sevilla. Primeras experiencias en contratacion publica y asesoria legal.</p>
        </div>
        <div class="ped-founder__timeline-item">
          <div class="ped-founder__timeline-date">1989 &ndash; 1997</div>
          <div class="ped-founder__timeline-title">Abogado y consultor</div>
          <div class="ped-founder__timeline-org">Ejercicio libre &mdash; Sevilla y Cordoba</div>
          <p class="ped-founder__timeline-desc">Inicio de la carrera profesional como jurista especializado en derecho comunitario, contratacion publica y asesoramiento a instituciones locales.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Formacion academica Pepe -->
  <section class="ped-section ped-section--alt">
    <div class="ped-section__container">
      <h2>Formacion academica y certificaciones &mdash; Jose Jaraba</h2>
      <div class="ped-founder__credentials-grid">
        <div class="ped-founder__credential">
          <div class="ped-founder__credential-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#C49B30" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 1.657 2.686 3 6 3s6-1.343 6-3v-5"/></svg>
          </div>
          <div>
            <div class="ped-founder__credential-title">Doctorado en curso</div>
            <div class="ped-founder__credential-detail">Universidad de Cordoba &mdash; Desarrollo Rural Territorial</div>
          </div>
        </div>
        <div class="ped-founder__credential">
          <div class="ped-founder__credential-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#C49B30" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 1.657 2.686 3 6 3s6-1.343 6-3v-5"/></svg>
          </div>
          <div>
            <div class="ped-founder__credential-title">MBA (en finalizacion)</div>
            <div class="ped-founder__credential-detail">Universidad Politecnica de Madrid</div>
          </div>
        </div>
        <div class="ped-founder__credential">
          <div class="ped-founder__credential-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#C49B30" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 1.657 2.686 3 6 3s6-1.343 6-3v-5"/></svg>
          </div>
          <div>
            <div class="ped-founder__credential-title">Master Cientifico en Desarrollo Rural Territorial</div>
            <div class="ped-founder__credential-detail">Universidad de Cordoba (2007&ndash;2009)</div>
          </div>
        </div>
        <div class="ped-founder__credential">
          <div class="ped-founder__credential-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#C49B30" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 1.657 2.686 3 6 3s6-1.343 6-3v-5"/></svg>
          </div>
          <div>
            <div class="ped-founder__credential-title">Master en Gestion del Desarrollo Rural</div>
            <div class="ped-founder__credential-detail">Universidad de Cordoba (2001&ndash;2003)</div>
          </div>
        </div>
        <div class="ped-founder__credential">
          <div class="ped-founder__credential-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#C49B30" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 1.657 2.686 3 6 3s6-1.343 6-3v-5"/></svg>
          </div>
          <div>
            <div class="ped-founder__credential-title">Master Gestion de Turismo Ambiental</div>
            <div class="ped-founder__credential-detail">Universidad Politecnica de Madrid (1996)</div>
          </div>
        </div>
        <div class="ped-founder__credential">
          <div class="ped-founder__credential-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#C49B30" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 1.657 2.686 3 6 3s6-1.343 6-3v-5"/></svg>
          </div>
          <div>
            <div class="ped-founder__credential-title">Licenciado en Derecho</div>
            <div class="ped-founder__credential-detail">Universidad de Cordoba (1989)</div>
          </div>
        </div>
        <div class="ped-founder__credential">
          <div class="ped-founder__credential-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#C49B30" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg>
          </div>
          <div>
            <div class="ped-founder__credential-title">EOQ Quality System Manager</div>
            <div class="ped-founder__credential-detail">European Organization for Quality</div>
          </div>
        </div>
        <div class="ped-founder__credential">
          <div class="ped-founder__credential-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#C49B30" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg>
          </div>
          <div>
            <div class="ped-founder__credential-title">ITIL Foundation</div>
            <div class="ped-founder__credential-detail">Gestion de servicios TI</div>
          </div>
        </div>
        <div class="ped-founder__credential">
          <div class="ped-founder__credential-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#C49B30" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg>
          </div>
          <div>
            <div class="ped-founder__credential-title">Experto Europeo en Gestion de Calidad AAPP</div>
            <div class="ped-founder__credential-detail">Administraciones Publicas</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ================================================================ -->
  <!-- PERFIL COFUNDADORA 2 — Remedios Estevez Palomino                 -->
  <!-- ================================================================ -->
  <section class="ped-section" id="remedios-estevez">
    <div class="ped-section__narrow">
      <div class="ped-founder__hero">
        <div class="ped-founder__photo-wrapper">
          <img src="/themes/custom/ecosistema_jaraba_theme/images/equipo-remedios-estevez.webp"
               srcset="/themes/custom/ecosistema_jaraba_theme/images/equipo-remedios-estevez.webp 2x"
               alt="Remedios Estevez Palomino — Cofundadora y COO de Plataforma de Ecosistemas Digitales"
               class="ped-founder__photo"
               width="280" height="280"
               loading="lazy" />
        </div>
        <div>
          <div class="ped-founder__name">Remedios Estevez Palomino</div>
          <div class="ped-founder__title">Cofundadora y COO</div>
          <div class="ped-founder__subtitle">Economista &middot; Experta en Desarrollo Economico Local &middot; +20 anos</div>
          <div class="ped-founder__badges">
            <span class="ped-founder__badge">Desarrollo Economico Local</span>
            <span class="ped-founder__badge">Gestion de Servicios Publicos</span>
            <span class="ped-founder__badge">Formacion Profesional</span>
            <span class="ped-founder__badge">Emprendimiento</span>
            <span class="ped-founder__badge">Empleabilidad</span>
          </div>
          <p class="ped-founder__bio">Licenciada en Economia con mas de 20 anos de experiencia en gestion de servicios publicos, desarrollo economico local y formacion profesional. Especializada en el diseno y ejecucion de programas de empleo, emprendimiento y capacitacion que conectan el sector publico con las necesidades reales del tejido productivo local.</p>
          <p class="ped-founder__bio">Actualmente gerencia una empresa municipal dedicada a promover el desarrollo economico local a traves del acompanamiento al emprendimiento y la mejora de la empleabilidad. Su experiencia directa en la gestion publica y el trabajo con administraciones locales aporta a PED una vision operativa y pragmatica que complementa la vision estrategica del ecosistema.</p>
          <div class="ped-founder__social">
            <a href="https://www.linkedin.com/in/remedios-estevez/" target="_blank" rel="noopener noreferrer" class="ped-founder__social-link" aria-label="Perfil de LinkedIn de Remedios Estevez">
              <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
              LinkedIn
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Cita de Remedios -->
  <section class="ped-section ped-section--alt">
    <div class="ped-section__narrow">
      <div class="ped-founder__quote">
        <p class="ped-founder__quote-text">El verdadero desarrollo economico local se construye desde dentro, acompanando a las personas y a las pymes en su dia a dia. La tecnologia es la herramienta, pero el impacto lo generan las personas que la usan.</p>
        <div class="ped-founder__quote-attr">&mdash; Remedios Estevez Palomino, Cofundadora y COO</div>
      </div>
    </div>
  </section>

  <!-- Trayectoria profesional Remedios -->
  <section class="ped-section">
    <div class="ped-section__narrow">
      <h2>Trayectoria profesional &mdash; Remedios Estevez</h2>
      <div class="ped-founder__timeline">
        <div class="ped-founder__timeline-item">
          <div class="ped-founder__timeline-date">2020 &ndash; Presente</div>
          <div class="ped-founder__timeline-title">Cofundadora y COO</div>
          <div class="ped-founder__timeline-org">Plataforma de Ecosistemas Digitales S.L. (PED)</div>
          <p class="ped-founder__timeline-desc">Cofundacion y direccion de operaciones del Ecosistema Jaraba. Responsable de la definicion de procesos operativos, gestion de equipos y relaciones con administraciones publicas locales.</p>
        </div>
        <div class="ped-founder__timeline-item">
          <div class="ped-founder__timeline-date">2005 &ndash; Presente</div>
          <div class="ped-founder__timeline-title">Gerente</div>
          <div class="ped-founder__timeline-org">Empresa Municipal de Desarrollo Economico Local</div>
          <p class="ped-founder__timeline-desc">Direccion y gestion integral de una empresa publica municipal dedicada a promover el desarrollo economico local. Diseno y ejecucion de programas de empleo, emprendimiento, formacion profesional y capacitacion digital para el tejido productivo local.</p>
        </div>
        <div class="ped-founder__timeline-item">
          <div class="ped-founder__timeline-date">2000 &ndash; 2005</div>
          <div class="ped-founder__timeline-title">Tecnica de desarrollo economico</div>
          <div class="ped-founder__timeline-org">Administracion Publica Local</div>
          <p class="ped-founder__timeline-desc">Gestion de programas de empleo y formacion profesional. Coordinacion con entidades locales, diseno de itinerarios de insercion laboral y acompanamiento a emprendedores.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Formacion academica Remedios -->
  <section class="ped-section ped-section--alt">
    <div class="ped-section__container">
      <h2>Formacion academica &mdash; Remedios Estevez</h2>
      <div class="ped-founder__credentials-grid">
        <div class="ped-founder__credential">
          <div class="ped-founder__credential-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#C49B30" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 1.657 2.686 3 6 3s6-1.343 6-3v-5"/></svg>
          </div>
          <div>
            <div class="ped-founder__credential-title">Licenciada en Economia</div>
            <div class="ped-founder__credential-detail">Especialidad en Economia Aplicada</div>
          </div>
        </div>
        <div class="ped-founder__credential">
          <div class="ped-founder__credential-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#C49B30" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg>
          </div>
          <div>
            <div class="ped-founder__credential-title">Formacion en Gestion de Servicios Publicos</div>
            <div class="ped-founder__credential-detail">Administracion y gestion publica local</div>
          </div>
        </div>
        <div class="ped-founder__credential">
          <div class="ped-founder__credential-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#C49B30" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg>
          </div>
          <div>
            <div class="ped-founder__credential-title">Desarrollo Economico Local</div>
            <div class="ped-founder__credential-detail">Programas de empleo, emprendimiento y formacion profesional</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Areas clave del equipo -->
  <section class="ped-section">
    <div class="ped-section__container">
      <h2>Areas clave del equipo</h2>
      <p>Ademas del liderazgo de los cofundadores, PED cuenta con un equipo multidisciplinar que cubre todas las dimensiones del ecosistema.</p>
      <div class="ped-grid ped-grid--4">
        <div class="ped-card ped-card--centered">
          <div class="ped-card__title">Tecnologia</div>
          <p class="ped-card__text">Ingenieria de software, IA generativa, arquitectura SaaS multi-tenant y DevOps.</p>
        </div>
        <div class="ped-card ped-card--centered">
          <div class="ped-card__title">Impacto Social</div>
          <p class="ped-card__text">Consultoria territorial, programas de empleo, formacion profesional y fondos europeos.</p>
        </div>
        <div class="ped-card ped-card--centered">
          <div class="ped-card__title">Legal y Compliance</div>
          <p class="ped-card__text">Derecho comunitario, RGPD, contratacion publica y cumplimiento normativo.</p>
        </div>
        <div class="ped-card ped-card--centered">
          <div class="ped-card__title">Comercial</div>
          <p class="ped-card__text">Partnerships institucionales, B2G, expansion territorial y relaciones con inversores.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="ped-cta-saas">
    <div class="ped-cta-saas__container">
      <h2>Quiere unirse al equipo?</h2>
      <p>Buscamos ingenieros, consultores y especialistas en impacto. Trabaje en proyectos que transforman territorios.</p>
      <div class="ped-cta-saas__actions">
        <a href="mailto:talento@plataformadeecosistemas.es" class="btn-gold">Enviar candidatura</a>
        <a href="/contacto" class="btn-ghost">Contactar</a>
      </div>
    </div>
  </section>
</div>
HTML;

$entity87 = $storage->load(87);
if ($entity87) {
  $canvas_data = json_encode(['html' => $equipo_html, 'css' => ''], JSON_UNESCAPED_UNICODE);
  $entity87->set('canvas_data', $canvas_data);
  $entity87->save();
  echo "Updated /equipo (ID 87) with both co-founders.\n";
}
else {
  echo "ERROR: Entity 87 not found.\n";
}

echo "\nDone. Clear cache with: drush cr\n";
