<?php

/**
 * @file
 * Elevación de las páginas del meta-sitio PED a nivel clase mundial.
 *
 * Actualiza el canvas_data de las 12 páginas secundarias del meta-sitio
 * plataformadeecosistemas.es (tenant/group 7) con:
 * - Clases CSS .ped-* en lugar de estilos inline
 * - Copy comercial/marketing optimizado para conversión
 * - CTAs estratégicos por página
 * - Estructura AIDA aplicada a cada contenido
 *
 * Uso: lando drush php:script scripts/maintenance/elevate_ped_pages.php
 *
 * @see _ped-metasite.scss para las clases CSS disponibles
 */

declare(strict_types=1);

$storage = \Drupal::entityTypeManager()->getStorage('page_content');

// ═══════════════════════════════════════════════════════════════════════
// CONTENIDO POR PÁGINA — Canvas data con clases CSS PED
// ═══════════════════════════════════════════════════════════════════════

$pages = [];

// ─────────────────────────────────────────────────────────────────────
// PAGE 79: CONTACTO (/contacto)
// Objetivo: Conversión — múltiples canales, urgencia, social proof
// ─────────────────────────────────────────────────────────────────────
$pages[79] = <<<'HTML'
<div class="ped-page">
  <section class="ped-page-header">
    <span class="ped-page-header__eyebrow">Tiempo de respuesta: menos de 24 horas</span>
    <h1>Hablemos de tu proyecto</h1>
    <p class="ped-page-header__subtitle">Tanto si eres institucion publica, inversor, medio de comunicacion o profesional — queremos escucharte. Sin formularios eternos, sin bots.</p>
  </section>

  <section class="ped-section">
    <div class="ped-section__container">
      <div class="ped-grid ped-grid--3">
        <div class="ped-contact-card">
          <div class="ped-contact-card__icon">
            <svg width="40" height="40" viewBox="0 0 40 40" fill="none"><rect x="4" y="8" width="32" height="24" rx="3" stroke="#233D63" stroke-width="2"/><path d="M4 12l16 10 16-10" stroke="#233D63" stroke-width="2"/></svg>
          </div>
          <div class="ped-contact-card__title">Email corporativo</div>
          <a href="mailto:info@plataformadeecosistemas.es" class="ped-contact-card__detail">info@plataformadeecosistemas.es</a>
          <div class="ped-contact-card__meta">Para consultas generales e institucionales</div>
        </div>

        <div class="ped-contact-card">
          <div class="ped-contact-card__icon">
            <svg width="40" height="40" viewBox="0 0 40 40" fill="none"><rect x="12" y="4" width="16" height="32" rx="3" stroke="#233D63" stroke-width="2"/><circle cx="20" cy="30" r="2" fill="#233D63"/></svg>
          </div>
          <div class="ped-contact-card__title">WhatsApp</div>
          <a href="https://wa.me/' . (theme_get_setting('whatsapp_number', 'ecosistema_jaraba_theme') ?: '') . '" class="ped-contact-card__detail" target="_blank" rel="noopener">Escribir por WhatsApp</a>
          <div class="ped-contact-card__meta">Disponible 24/7 con IA</div>
        </div>

        <div class="ped-contact-card">
          <div class="ped-contact-card__icon">
            <svg width="40" height="40" viewBox="0 0 40 40" fill="none"><path d="M20 4C13 4 8 9 8 15c0 8 12 21 12 21s12-13 12-21c0-6-5-11-12-11z" stroke="#233D63" stroke-width="2"/><circle cx="20" cy="15" r="4" stroke="#233D63" stroke-width="2"/></svg>
          </div>
          <div class="ped-contact-card__title">Oficinas</div>
          <span class="ped-contact-card__detail">Calle Heroe de Sostoa 12</span>
          <div class="ped-contact-card__meta">29002 Malaga, Espana</div>
        </div>
      </div>
    </div>
  </section>

  <section class="ped-section ped-section--alt">
    <div class="ped-section__container">
      <h2>Canales especializados</h2>
      <div class="ped-grid ped-grid--2">
        <div class="ped-card ped-card--bordered-top">
          <div class="ped-card__title">Instituciones publicas y B2G</div>
          <p class="ped-card__text">Para programas de empleo, fondos europeos, convenios y colaboraciones con administraciones.</p>
          <a href="mailto:instituciones@plataformadeecosistemas.es" class="ped-contact-card__detail">instituciones@plataformadeecosistemas.es</a>
        </div>
        <div class="ped-card ped-card--bordered-top">
          <div class="ped-card__title">Inversores y partnerships</div>
          <p class="ped-card__text">Para oportunidades de inversion, alianzas estrategicas y certificacion del Metodo Jaraba.</p>
          <a href="mailto:inversores@plataformadeecosistemas.es" class="ped-contact-card__detail">inversores@plataformadeecosistemas.es</a>
        </div>
        <div class="ped-card ped-card--bordered-top">
          <div class="ped-card__title">Prensa y medios</div>
          <p class="ped-card__text">Notas de prensa, entrevistas, datos de impacto y recursos graficos.</p>
          <a href="mailto:prensa@plataformadeecosistemas.es" class="ped-contact-card__detail">prensa@plataformadeecosistemas.es</a>
        </div>
        <div class="ped-card ped-card--bordered-top">
          <div class="ped-card__title">Trabaja con nosotros</div>
          <p class="ped-card__text">Buscamos ingenieros, consultores y especialistas en impacto. Envia tu CV.</p>
          <a href="mailto:talento@plataformadeecosistemas.es" class="ped-contact-card__detail">talento@plataformadeecosistemas.es</a>
        </div>
      </div>
    </div>
  </section>

  <section class="ped-cta-saas">
    <div class="ped-cta-saas__container">
      <h2>Prefiere una demo en vivo?</h2>
      <p>Le mostramos la plataforma funcionando con datos reales en 30 minutos. Sin compromiso.</p>
      <div class="ped-cta-saas__actions">
        <a href="/contacto" class="btn-gold">Solicitar demo</a>
      </div>
    </div>
  </section>
</div>
HTML;

// ─────────────────────────────────────────────────────────────────────
// PAGE 83: EMPRESA (/sobre-nosotros)
// Objetivo: Credibilidad y autoridad — historia, misión, valores
// ─────────────────────────────────────────────────────────────────────
$pages[83] = <<<'HTML'
<div class="ped-page">
  <section class="ped-page-header">
    <span class="ped-page-header__eyebrow">Fundada en 2020</span>
    <h1>Infraestructura digital para personas, pymes y territorios</h1>
    <p class="ped-page-header__subtitle">Plataforma de Ecosistemas Digitales S.L. opera el Ecosistema Jaraba: una empresa fundada en 2020 que recoge 30 anos de experiencia de sus fundadores en transformacion territorial, convertidos en tecnologia SaaS de impacto.</p>
  </section>

  <section class="ped-section">
    <div class="ped-section__container">
      <div class="ped-grid ped-grid--4">
        <div class="ped-kpi">
          <div class="ped-kpi__value">+30</div>
          <div class="ped-kpi__label">anos de experiencia</div>
        </div>
        <div class="ped-kpi">
          <div class="ped-kpi__value ped-kpi__value--green">+100M€</div>
          <div class="ped-kpi__label">en fondos gestionados</div>
        </div>
        <div class="ped-kpi">
          <div class="ped-kpi__value ped-kpi__value--gold">+50.000</div>
          <div class="ped-kpi__label">beneficiarios directos</div>
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
      <p>En 1994, Jose Jaraba empezo a trabajar en programas de empleo y formacion en municipios rurales de Andalucia. Lo que comenzo como intervencion social directa — talleres, orientacion, itinerarios personalizados — se convirtio en tres decadas de experiencia en transformacion territorial.</p>
      <p>En 2020, toda esa experiencia se cristalizo en la fundacion de Plataforma de Ecosistemas Digitales S.L., una empresa tecnologica que traslada el Metodo Jaraba — un marco probado de intervencion territorial — a una plataforma SaaS multi-vertical con inteligencia artificial.</p>
      <p><strong>El resultado:</strong> una infraestructura digital que ha gestionado mas de 100 millones de euros en fondos europeos, ha formado a mas de 50.000 personas y opera hoy 10 verticales especializados con 11 agentes de IA.</p>
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
          <p class="ped-card__text">Ser la infraestructura digital de referencia para el desarrollo local sostenible en el mundo hispanohablante, conectando instituciones, empresas y personas en un ecosistema de impacto medible.</p>
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

  <!-- Nuestro fundador — sección resumen con enlace a /equipo -->
  <section class="ped-section">
    <div class="ped-section__narrow">
      <h2>Nuestro fundador</h2>
      <div class="ped-founder__hero">
        <div class="ped-founder__photo-wrapper">
          <img src="/themes/custom/ecosistema_jaraba_theme/images/pepe-jaraba-fundador.png"
               alt="Jose Jaraba Munoz — Cofundador y CEO de PED"
               class="ped-founder__photo"
               width="280" height="280"
               loading="lazy" />
        </div>
        <div>
          <div class="ped-founder__name">Jose Jaraba Munoz</div>
          <div class="ped-founder__title">Cofundador y CEO</div>
          <div class="ped-founder__subtitle">Jurista · Experto en Desarrollo Local y Territorial · +30 anos de experiencia</div>
          <p class="ped-founder__bio">Licenciado en Derecho (UCO), tres Masters, doctorando y certificado EOQ Quality System Manager. Ha dirigido grupos de desarrollo local que han gestionado +100M€ en fondos europeos, creado programas para +50.000 beneficiarios y transformado +120 municipios rurales. En 2020 fundo PED para convertir esa experiencia en tecnologia SaaS de impacto.</p>
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

// ─────────────────────────────────────────────────────────────────────
// PAGE 84: ECOSISTEMA (/ecosistema)
// Objetivo: Explicar la arquitectura de producto como ventaja competitiva
// ─────────────────────────────────────────────────────────────────────
$pages[84] = <<<'HTML'
<div class="ped-page">
  <section class="ped-page-header">
    <span class="ped-page-header__eyebrow">Arquitectura SaaS multi-vertical</span>
    <h1>El Ecosistema Jaraba</h1>
    <p class="ped-page-header__subtitle">10 verticales especializados, 11 agentes IA, firma digital y copiloto integrado. Una sola plataforma para profesionales, empresas e instituciones.</p>
  </section>

  <section class="ped-section">
    <div class="ped-section__container">
      <h2>Modelo de Triple Motor Economico</h2>
      <p>La sostenibilidad del ecosistema se apoya en tres fuentes de ingresos complementarias, eliminando la dependencia de cualquier actor unico.</p>
      <div class="ped-grid ped-grid--3">
        <div class="ped-card ped-card--bordered-top">
          <div class="ped-card__subtitle">30% de los ingresos</div>
          <div class="ped-card__title">Motor Institucional</div>
          <p class="ped-card__text">Fondos publicos, programas subvencionados, convenios con administraciones y fondos europeos (FSE, FEDER, Next Generation). Gestion directa de +100M€ acumulados.</p>
        </div>
        <div class="ped-card ped-card--bordered-top">
          <div class="ped-card__subtitle">40% de los ingresos</div>
          <div class="ped-card__title">Motor de Mercado</div>
          <p class="ped-card__text">Suscripciones SaaS, verticales especializados, servicios profesionales. Modelo de ingresos recurrentes con churn por debajo de la media del sector.</p>
        </div>
        <div class="ped-card ped-card--bordered-top">
          <div class="ped-card__subtitle">30% de los ingresos</div>
          <div class="ped-card__title">Motor de Licencias</div>
          <p class="ped-card__text">Certificacion del Metodo Jaraba, formacion de formadores, franquicia digital del modelo a otros territorios. Escalabilidad sin coste marginal.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="ped-section ped-section--alt">
    <div class="ped-section__container">
      <h2>10 verticales especializados</h2>
      <p>Cada vertical es un producto SaaS completo con su propio copiloto IA, flujos de trabajo y modulo de monetizacion. Comparten infraestructura pero operan de forma independiente.</p>
      <div class="ped-grid ped-grid--2">
        <div class="ped-card">
          <div class="ped-card__title">Empleabilidad</div>
          <p class="ped-card__text">Diagnostico de competencias, CV con IA, matching inteligente, preparacion de entrevistas y seguimiento de itinerarios.</p>
        </div>
        <div class="ped-card">
          <div class="ped-card__title">Emprendimiento</div>
          <p class="ped-card__text">Lean Canvas asistido, MVP builder, mentoria digital, aceleracion y conexion con inversores.</p>
        </div>
        <div class="ped-card">
          <div class="ped-card__title">JarabaLex</div>
          <p class="ped-card__text">Inteligencia legal IA: busqueda semantica en CENDOJ/BOE/EUR-Lex, gestion de expedientes, facturacion y LexNET integrado.</p>
        </div>
        <div class="ped-card">
          <div class="ped-card__title">AgroConecta</div>
          <p class="ped-card__text">Tienda online para productores rurales en 10 minutos. Venta directa sin intermediarios, logistica integrada.</p>
        </div>
        <div class="ped-card">
          <div class="ped-card__title">ComercioConecta</div>
          <p class="ped-card__text">Ofertas flash, click&collect, pedidos online. Digitalizacion del comercio local con integracion TPV.</p>
        </div>
        <div class="ped-card">
          <div class="ped-card__title">ServiciosConecta</div>
          <p class="ped-card__text">Agenda, videollamadas, cobro automatico y firma digital para profesionales independientes.</p>
        </div>
        <div class="ped-card">
          <div class="ped-card__title">Formacion</div>
          <p class="ped-card__text">LMS con rutas de aprendizaje IA, certificacion de competencias y conexion con el mercado laboral.</p>
        </div>
        <div class="ped-card">
          <div class="ped-card__title">Andalucia +e+i</div>
          <p class="ped-card__text">Programa especifico de empleo y emprendimiento para el territorio andaluz con metodologia probada.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="ped-section">
    <div class="ped-section__container">
      <h2>Stack tecnologico</h2>
      <div class="ped-grid ped-grid--4">
        <div class="ped-card ped-card--centered">
          <div class="ped-card__title">Drupal 11</div>
          <p class="ped-card__text">80+ modulos custom, multi-tenancy nativo</p>
        </div>
        <div class="ped-card ped-card--centered">
          <div class="ped-card__title">11 Agentes IA</div>
          <p class="ped-card__text">Claude + Gemini, tool use, RAG semantico</p>
        </div>
        <div class="ped-card ped-card--centered">
          <div class="ped-card__title">Stripe Connect</div>
          <p class="ped-card__text">Pagos multi-tenant, destination charges</p>
        </div>
        <div class="ped-card ped-card--centered">
          <div class="ped-card__title">RGPD by Design</div>
          <p class="ped-card__text">Cifrado, PII detection, audit trail</p>
        </div>
      </div>
    </div>
  </section>

  <section class="ped-cta-saas">
    <div class="ped-cta-saas__container">
      <h2>Quiere ver la arquitectura en accion?</h2>
      <p>Le mostramos los 10 verticales funcionando con datos reales.</p>
      <div class="ped-cta-saas__actions">
        <a href="/contacto" class="btn-gold">Solicitar demo tecnica</a>
      </div>
    </div>
  </section>
</div>
HTML;

// ─────────────────────────────────────────────────────────────────────
// PAGE 85: IMPACTO (/impacto)
// Objetivo: Social proof máximo — métricas verificables
// ─────────────────────────────────────────────────────────────────────
$pages[85] = <<<'HTML'
<div class="ped-page">
  <section class="ped-page-header">
    <span class="ped-page-header__eyebrow">Resultados verificables, no promesas</span>
    <h1>Nuestro impacto</h1>
    <p class="ped-page-header__subtitle">30 anos de datos reales. Personas formadas, empleos generados, negocios digitalizados, territorios transformados.</p>
  </section>

  <section class="ped-section">
    <div class="ped-section__container">
      <div class="ped-grid ped-grid--4">
        <div class="ped-kpi">
          <div class="ped-kpi__value">+50.000</div>
          <div class="ped-kpi__label">personas formadas</div>
        </div>
        <div class="ped-kpi">
          <div class="ped-kpi__value ped-kpi__value--green">+3.200</div>
          <div class="ped-kpi__label">empleos facilitados</div>
        </div>
        <div class="ped-kpi">
          <div class="ped-kpi__value ped-kpi__value--orange">+800</div>
          <div class="ped-kpi__label">pymes digitalizadas</div>
        </div>
        <div class="ped-kpi">
          <div class="ped-kpi__value ped-kpi__value--gold">120+</div>
          <div class="ped-kpi__label">municipios impactados</div>
        </div>
      </div>
    </div>
  </section>

  <section class="ped-section ped-section--alt">
    <div class="ped-section__container">
      <h2>Impacto por dimension</h2>
      <div class="ped-grid ped-grid--3">
        <div class="ped-card ped-card--bordered-left">
          <div class="ped-card__title">Personas</div>
          <p class="ped-card__text">Mas de 50.000 personas han participado en programas de formacion, orientacion laboral e itinerarios de empleabilidad gestionados con nuestra metodologia y tecnologia.</p>
        </div>
        <div class="ped-card ped-card--bordered-left">
          <div class="ped-card__title">Empresas</div>
          <p class="ped-card__text">+800 pymes han digitalizado su actividad a traves de nuestros verticales: tiendas online, agendas digitales, gestion documental y facturacion electronica.</p>
        </div>
        <div class="ped-card ped-card--bordered-left">
          <div class="ped-card__title">Territorios</div>
          <p class="ped-card__text">Intervencion directa en +120 municipios de Andalucia, con especial foco en zonas rurales con brecha digital y riesgo de despoblacion.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="ped-section">
    <div class="ped-section__container">
      <h2>Hitos de impacto</h2>
      <div class="ped-grid ped-grid--2">
        <div class="ped-card">
          <div class="ped-card__subtitle">2024</div>
          <div class="ped-card__title">Lanzamiento de la plataforma SaaS</div>
          <p class="ped-card__text">10 verticales, 11 agentes IA, multi-tenancy nativo. Toda la experiencia de 30 anos convertida en producto digital escalable.</p>
        </div>
        <div class="ped-card">
          <div class="ped-card__subtitle">2023</div>
          <div class="ped-card__title">Programa Andalucia +e+i</div>
          <p class="ped-card__text">3 emprendedores lanzados con exito, 100% de participantes recomendarian el programa. Caso Camino Viejo: negocio rural nacido del programa.</p>
        </div>
        <div class="ped-card">
          <div class="ped-card__subtitle">2010-2023</div>
          <div class="ped-card__title">+100M€ en fondos europeos</div>
          <p class="ped-card__text">Gestion directa de programas FSE, FEDER y Next Generation con 100% de justificacion aprobada por las autoridades de gestion.</p>
        </div>
        <div class="ped-card">
          <div class="ped-card__subtitle">1994-2010</div>
          <div class="ped-card__title">Origen: intervencion territorial</div>
          <p class="ped-card__text">Talleres, orientacion laboral, formacion profesional. Las raices del Metodo Jaraba nacen de la experiencia directa con personas y municipios.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="ped-cta-saas">
    <div class="ped-cta-saas__container">
      <h2>Quiere generar impacto medible en su territorio?</h2>
      <p>Le contamos como la plataforma puede potenciar sus programas de desarrollo local.</p>
      <div class="ped-cta-saas__actions">
        <a href="/contacto" class="btn-gold">Hablar con un consultor</a>
      </div>
    </div>
  </section>
</div>
HTML;

// ─────────────────────────────────────────────────────────────────────
// PAGE 86: PARTNERS (/partners)
// Objetivo: Credibilidad institucional
// ─────────────────────────────────────────────────────────────────────
$pages[86] = <<<'HTML'
<div class="ped-page">
  <section class="ped-page-header">
    <span class="ped-page-header__eyebrow">Red de aliados estrategicos</span>
    <h1>Partners y Alianzas</h1>
    <p class="ped-page-header__subtitle">Instituciones publicas, entidades formativas y partners tecnologicos que confian en el Ecosistema Jaraba.</p>
  </section>

  <section class="ped-section">
    <div class="ped-section__container">
      <h2>Partners institucionales</h2>
      <p>Administraciones publicas con las que hemos gestionado programas de empleo, formacion y desarrollo territorial.</p>
      <div class="ped-grid ped-grid--3">
        <div class="ped-card ped-card--centered">
          <div class="ped-card__title">Union Europea</div>
          <p class="ped-card__text">Financiacion de programas de empleo y desarrollo territorial a traves de fondos estructurales FSE y FEDER.</p>
        </div>
        <div class="ped-card ped-card--centered">
          <div class="ped-card__title">Estado Espanol</div>
          <p class="ped-card__text">Programas nacionales de empleo, formacion profesional y transformacion digital territorial.</p>
        </div>
        <div class="ped-card ped-card--centered">
          <div class="ped-card__title">Junta de Andalucia</div>
          <p class="ped-card__text">Programas de empleo y formacion profesional. Convenios de gestion de fondos europeos FSE.</p>
        </div>
        <div class="ped-card ped-card--centered">
          <div class="ped-card__title">Diputacion de Malaga</div>
          <p class="ped-card__text">Programas de desarrollo economico provincial y apoyo a municipios rurales en transformacion digital.</p>
        </div>
        <div class="ped-card ped-card--centered">
          <div class="ped-card__title">Diputacion de Caceres</div>
          <p class="ped-card__text">Colaboracion en programas de empleo y emprendimiento para territorios de la Espana vaciada.</p>
        </div>
        <div class="ped-card ped-card--centered">
          <div class="ped-card__title">Ayto. Alhaurin el Grande</div>
          <p class="ped-card__text">Sede historica de programas de empleo y formacion. +15 anos de colaboracion continuada.</p>
        </div>
        <div class="ped-card ped-card--centered">
          <div class="ped-card__title">Ayto. Alcala de Guadaira</div>
          <p class="ped-card__text">Programas de empleo y emprendimiento para el desarrollo economico local.</p>
        </div>
        <div class="ped-card ped-card--centered">
          <div class="ped-card__title">Ayto. Almendralejo</div>
          <p class="ped-card__text">Programas de empleo y formacion en municipios de Extremadura.</p>
        </div>
        <div class="ped-card ped-card--centered">
          <div class="ped-card__title">Ayto. Ponferrada</div>
          <p class="ped-card__text">Programas de empleo y transformacion digital en la comarca del Bierzo, Leon.</p>
        </div>
        <div class="ped-card ped-card--centered">
          <div class="ped-card__title">Ayto. Puente Genil</div>
          <p class="ped-card__text">Programas de empleo, emprendimiento y formacion profesional en Cordoba.</p>
        </div>
        <div class="ped-card ped-card--centered">
          <div class="ped-card__title">Universidad de Cordoba</div>
          <p class="ped-card__text">Colaboracion en investigacion, transferencia de conocimiento y programas academicos.</p>
        </div>
        <div class="ped-card ped-card--centered">
          <div class="ped-card__title">Fundacion MADECA</div>
          <p class="ped-card__text">Colaboracion en programas de desarrollo territorial y estrategia provincial de Malaga.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="ped-section ped-section--alt">
    <div class="ped-section__container">
      <h2>Por que ser partner?</h2>
      <div class="ped-grid ped-grid--3">
        <div class="ped-card ped-card--gold-top">
          <div class="ped-card__title">Tecnologia validada</div>
          <p class="ped-card__text">Plataforma SaaS con +80 modulos custom, 11 agentes IA y 30 anos de iteracion sobre necesidades reales del territorio.</p>
        </div>
        <div class="ped-card ped-card--gold-top">
          <div class="ped-card__title">Metodologia certificable</div>
          <p class="ped-card__text">El Metodo Jaraba es replicable. Puede licenciarse para aplicar en su territorio con soporte tecnico completo.</p>
        </div>
        <div class="ped-card ped-card--gold-top">
          <div class="ped-card__title">Impacto verificable</div>
          <p class="ped-card__text">Dashboard de impacto en tiempo real. Metricas automatizadas para justificacion de fondos y reporting.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="ped-cta-saas">
    <div class="ped-cta-saas__container">
      <h2>Quiere ser partner del ecosistema?</h2>
      <p>Hablemos sobre como integrar su institucion, empresa o territorio en nuestra red.</p>
      <div class="ped-cta-saas__actions">
        <a href="/contacto" class="btn-gold">Iniciar conversacion</a>
      </div>
    </div>
  </section>
</div>
HTML;

// ─────────────────────────────────────────────────────────────────────
// PAGE 87: EQUIPO (/equipo)
// ─────────────────────────────────────────────────────────────────────
$pages[87] = <<<'HTML'
<div class="ped-page">
  <section class="ped-page-header">
    <span class="ped-page-header__eyebrow">Las personas detras del ecosistema</span>
    <h1>Equipo Directivo</h1>
    <p class="ped-page-header__subtitle">Liderazgo con 30 anos de experiencia real en desarrollo territorial, fondos europeos, derecho comunitario y tecnologia de impacto.</p>
  </section>

  <!-- ═══════════════════════════════════════════════════════════════ -->
  <!-- PERFIL FUNDADOR — Jose "Pepe" Jaraba Munoz                    -->
  <!-- ═══════════════════════════════════════════════════════════════ -->
  <section class="ped-section">
    <div class="ped-section__narrow">
      <div class="ped-founder__hero">
        <div class="ped-founder__photo-wrapper">
          <img src="/themes/custom/ecosistema_jaraba_theme/images/pepe-jaraba-fundador.png"
               alt="Jose Jaraba Munoz — Fundador y CEO de Plataforma de Ecosistemas Digitales"
               class="ped-founder__photo"
               width="280" height="280"
               loading="eager" />
        </div>
        <div>
          <div class="ped-founder__name">Jose Jaraba Munoz</div>
          <div class="ped-founder__title">Cofundador y CEO</div>
          <div class="ped-founder__subtitle">Jurista · Experto en Desarrollo Local y Territorial · Doctorando UCO</div>
          <p class="ped-founder__bio">Licenciado en Derecho por la Universidad de Cordoba, con tres Masters (Gestion de Turismo Ambiental por la UPM, Gestion del Desarrollo Rural por la UCO y MBA en proceso), doctorando en la UCO y certificado EOQ Quality System Manager e ITIL Foundation. Pepe Jaraba combina una formacion juridica de alto nivel con mas de 30 anos de experiencia practica en la interseccion entre politicas publicas, fondos europeos, desarrollo territorial y tecnologia.</p>
          <p class="ped-founder__bio">Su vision: convertir tres decadas de intervencion directa en +120 municipios en una infraestructura digital SaaS que democratice el acceso al desarrollo economico local, disponible para cualquier territorio del mundo hispanohablante.</p>
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

  <!-- Cita del fundador -->
  <section class="ped-section ped-section--alt">
    <div class="ped-section__narrow">
      <div class="ped-founder__quote">
        <p class="ped-founder__quote-text">La tecnologia solo tiene sentido si resuelve problemas reales de personas reales. Llevamos 30 anos en el terreno — en municipios rurales, en programas de empleo, en despachos de abogados. Todo lo que construimos nace de esa experiencia, no de una pizarra en Silicon Valley.</p>
        <div class="ped-founder__quote-attr">— Jose Jaraba Munoz, Cofundador y CEO</div>
      </div>
    </div>
  </section>

  <!-- Trayectoria profesional -->
  <section class="ped-section">
    <div class="ped-section__narrow">
      <h2>Trayectoria profesional</h2>
      <div class="ped-founder__timeline">
        <div class="ped-founder__timeline-item">
          <div class="ped-founder__timeline-date">2019 – Presente</div>
          <div class="ped-founder__timeline-title">Cofundador y CEO</div>
          <div class="ped-founder__timeline-org">Plataforma de Ecosistemas Digitales S.L. (PED)</div>
          <p class="ped-founder__timeline-desc">Fundacion y direccion de la empresa que opera el Ecosistema Jaraba: plataforma SaaS multi-vertical con 10 verticales, 11 agentes IA, integracion con fondos europeos y presencia en +120 municipios. Arquitectura de producto, estrategia B2G e inversion.</p>
        </div>
        <div class="ped-founder__timeline-item">
          <div class="ped-founder__timeline-date">2015 – 2019</div>
          <div class="ped-founder__timeline-title">Director Gerente</div>
          <div class="ped-founder__timeline-org">SODEPO S.L. — Sociedad para el Desarrollo Economico de Puente Genil (Ayuntamiento)</div>
          <p class="ped-founder__timeline-desc">Direccion de la sociedad municipal de desarrollo economico. Gestion de programas de empleo, formacion profesional, emprendimiento y atraccion de inversiones para el municipio.</p>
        </div>
        <div class="ped-founder__timeline-item">
          <div class="ped-founder__timeline-date">2014</div>
          <div class="ped-founder__timeline-title">Experto independiente</div>
          <div class="ped-founder__timeline-org">Fundacion MADECA — Diputacion de Malaga</div>
          <p class="ped-founder__timeline-desc">Consultoria especializada en planificacion estrategica territorial y fondos europeos para la provincia de Malaga.</p>
        </div>
        <div class="ped-founder__timeline-item">
          <div class="ped-founder__timeline-date">2011 – 2014</div>
          <div class="ped-founder__timeline-title">Profesor externo universitario</div>
          <div class="ped-founder__timeline-org">Universidad de Cordoba y Universidad de Jaen</div>
          <p class="ped-founder__timeline-desc">Docencia en programas de Desarrollo Rural Territorial. Transferencia de conocimiento practico sobre intervencion en territorios rurales y gestion de fondos europeos.</p>
        </div>
        <div class="ped-founder__timeline-item">
          <div class="ped-founder__timeline-date">2001 – 2015</div>
          <div class="ped-founder__timeline-title">Director Gerente y fundador</div>
          <div class="ped-founder__timeline-org">Ingenova Consulting S.L.</div>
          <p class="ped-founder__timeline-desc">Consultora especializada en innovacion TIC para el medio rural. Diseno e implementacion de estrategias de transformacion digital territorial, incluyendo telecentros, formacion digital y conectividad rural.</p>
        </div>
        <div class="ped-founder__timeline-item">
          <div class="ped-founder__timeline-date">1997 – 2015</div>
          <div class="ped-founder__timeline-title">Director Gerente</div>
          <div class="ped-founder__timeline-org">Asociacion Grupo de Desarrollo Rural Campina Sur Cordobesa</div>
          <p class="ped-founder__timeline-desc">Direccion durante 18 anos del principal grupo de desarrollo local de la provincia de Cordoba. Gestion directa de +100 millones de euros en politicas europeas (LEADER, PRODER, FSE, FEDER). Coordinacion de programas de empleo, formacion y emprendimiento con impacto en +50.000 beneficiarios directos.</p>
        </div>
        <div class="ped-founder__timeline-item">
          <div class="ped-founder__timeline-date">1992 – 1995</div>
          <div class="ped-founder__timeline-title">Director de Consultoria</div>
          <div class="ped-founder__timeline-org">EJ&amp;JE Consultores Reunidos</div>
          <p class="ped-founder__timeline-desc">Consultoria juridica y de desarrollo territorial en Sevilla. Primeras experiencias en contratacion publica y asesoria legal.</p>
        </div>
        <div class="ped-founder__timeline-item">
          <div class="ped-founder__timeline-date">1989 – 1997</div>
          <div class="ped-founder__timeline-title">Abogado y consultor</div>
          <div class="ped-founder__timeline-org">Ejercicio libre — Sevilla y Cordoba</div>
          <p class="ped-founder__timeline-desc">Inicio de la carrera profesional como jurista especializado en derecho comunitario, contratacion publica y asesoramiento a instituciones locales.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Formacion academica y certificaciones -->
  <section class="ped-section ped-section--alt">
    <div class="ped-section__container">
      <h2>Formacion academica y certificaciones</h2>
      <div class="ped-founder__credentials-grid">
        <div class="ped-founder__credential">
          <div class="ped-founder__credential-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#C49B30" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 1.657 2.686 3 6 3s6-1.343 6-3v-5"/></svg>
          </div>
          <div>
            <div class="ped-founder__credential-title">Doctorado en curso</div>
            <div class="ped-founder__credential-detail">Universidad de Cordoba — Desarrollo Rural Territorial</div>
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
            <div class="ped-founder__credential-detail">Universidad de Cordoba (2007–2009)</div>
          </div>
        </div>
        <div class="ped-founder__credential">
          <div class="ped-founder__credential-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#C49B30" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 1.657 2.686 3 6 3s6-1.343 6-3v-5"/></svg>
          </div>
          <div>
            <div class="ped-founder__credential-title">Master en Gestion del Desarrollo Rural</div>
            <div class="ped-founder__credential-detail">Universidad de Cordoba (2001–2003)</div>
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

  <!-- Areas clave del equipo -->
  <section class="ped-section">
    <div class="ped-section__container">
      <h2>Areas clave del equipo</h2>
      <p>Ademas del liderazgo fundador, PED cuenta con un equipo multidisciplinar que cubre todas las dimensiones del ecosistema.</p>
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

// ─────────────────────────────────────────────────────────────────────
// PAGE 88: TRANSPARENCIA (/transparencia)
// ─────────────────────────────────────────────────────────────────────
$pages[88] = <<<'HTML'
<div class="ped-page">
  <section class="ped-page-header">
    <span class="ped-page-header__eyebrow">Filosofia Sin Humo</span>
    <h1>Transparencia Corporativa</h1>
    <p class="ped-page-header__subtitle">Informacion societaria, modelo financiero y governance para inversores, administraciones y stakeholders.</p>
  </section>

  <section class="ped-section">
    <div class="ped-section__container">
      <h2>Informacion societaria</h2>
      <table class="ped-table">
        <tbody>
          <tr><td><strong>Razon social</strong></td><td>Plataforma de Ecosistemas Digitales S.L.</td></tr>
          <tr><td><strong>CIF</strong></td><td>B93750271</td></tr>
          <tr><td><strong>Constitucion</strong></td><td>2020</td></tr>
          <tr><td><strong>Domicilio</strong></td><td>Calle Heroe de Sostoa 12, 29002 Malaga</td></tr>
          <tr><td><strong>Registro Mercantil</strong></td><td>Malaga</td></tr>
          <tr><td><strong>Actividad</strong></td><td>Desarrollo y explotacion de plataformas digitales SaaS</td></tr>
        </tbody>
      </table>
    </div>
  </section>

  <section class="ped-section ped-section--alt">
    <div class="ped-section__container">
      <h2>Modelo de ingresos</h2>
      <p>Triple Motor Economico que diversifica riesgo y maximiza sostenibilidad.</p>
      <table class="ped-table">
        <thead>
          <tr><th>Fuente</th><th>Peso</th><th>Descripcion</th></tr>
        </thead>
        <tbody>
          <tr><td>SaaS recurrente</td><td>40%</td><td>Suscripciones mensuales por vertical y por tenant</td></tr>
          <tr><td>Fondos publicos</td><td>30%</td><td>Programas FSE, FEDER, Next Generation, convenios</td></tr>
          <tr><td>Licencias y formacion</td><td>30%</td><td>Metodo Jaraba, certificaciones, formacion de formadores</td></tr>
        </tbody>
      </table>
    </div>
  </section>

  <section class="ped-section">
    <div class="ped-section__container">
      <h2>Governance y compliance</h2>
      <div class="ped-grid ped-grid--3">
        <div class="ped-card ped-card--bordered-left">
          <div class="ped-card__title">RGPD nativo</div>
          <p class="ped-card__text">Privacy by design en toda la plataforma. DPO designado, registros de actividad de tratamiento, evaluaciones de impacto y procedimientos de ejercicio de derechos implementados.</p>
        </div>
        <div class="ped-card ped-card--bordered-left">
          <div class="ped-card__title">Preparados para EU AI Act</div>
          <p class="ped-card__text">Audit trail implementado para los 11 agentes IA. Trazabilidad de decisiones, guardrails PII bidireccionales y limites eticos configurados. Pendiente de certificacion formal cuando el reglamento entre en plena aplicacion.</p>
        </div>
        <div class="ped-card ped-card--bordered-left">
          <div class="ped-card__title">Seguridad</div>
          <p class="ped-card__text">Cifrado en transito y reposo, HMAC en webhooks, CSP headers, CSRF protection, multi-tenant isolation.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="ped-cta-saas">
    <div class="ped-cta-saas__container">
      <h2>Inversor o administracion?</h2>
      <p>Solicite acceso al data room con informacion financiera detallada.</p>
      <div class="ped-cta-saas__actions">
        <a href="/contacto" class="btn-gold">Solicitar acceso</a>
      </div>
    </div>
  </section>
</div>
HTML;

// ─────────────────────────────────────────────────────────────────────
// PAGE 89: CERTIFICACIONES (/certificaciones)
// ─────────────────────────────────────────────────────────────────────
$pages[89] = <<<'HTML'
<div class="ped-page">
  <section class="ped-page-header">
    <span class="ped-page-header__eyebrow">Calidad certificada</span>
    <h1>Certificaciones y Acreditaciones</h1>
    <p class="ped-page-header__subtitle">Homologaciones oficiales que avalan nuestra actividad formativa, tecnologica y de gestion de fondos.</p>
  </section>

  <section class="ped-section">
    <div class="ped-section__container">
      <h2>Certificaciones obtenidas</h2>
      <div class="ped-grid ped-grid--2">
        <div class="ped-cert">
          <div class="ped-cert__icon">
            <svg width="48" height="48" viewBox="0 0 48 48" fill="none"><circle cx="24" cy="20" r="14" stroke="#C5A55A" stroke-width="2"/><path d="M18 32l6 12 6-12" stroke="#C5A55A" stroke-width="2"/><path d="M17 18l3 3 7-7" stroke="#233D63" stroke-width="2.5" stroke-linecap="round"/></svg>
          </div>
          <div class="ped-cert__info">
            <div class="ped-cert__name">Centro SEPE Homologado</div>
            <div class="ped-cert__desc">Homologacion para impartir certificados de profesionalidad y formacion para el empleo. Numero de homologacion pendiente de asignacion.</div>
          </div>
        </div>
        <div class="ped-cert">
          <div class="ped-cert__icon">
            <svg width="48" height="48" viewBox="0 0 48 48" fill="none"><circle cx="24" cy="20" r="14" stroke="#C5A55A" stroke-width="2"/><path d="M18 32l6 12 6-12" stroke="#C5A55A" stroke-width="2"/><path d="M17 18l3 3 7-7" stroke="#233D63" stroke-width="2.5" stroke-linecap="round"/></svg>
          </div>
          <div class="ped-cert__info">
            <div class="ped-cert__name">Entidad FUNDAE</div>
            <div class="ped-cert__desc">Acreditacion para gestionar formacion bonificada para empresas. Codigo de registro: 99000259.</div>
          </div>
        </div>
        <div class="ped-cert">
          <div class="ped-cert__icon">
            <svg width="48" height="48" viewBox="0 0 48 48" fill="none"><rect x="8" y="6" width="32" height="36" rx="3" stroke="#233D63" stroke-width="2"/><path d="M16 16h16M16 22h16M16 28h10" stroke="#233D63" stroke-width="2" stroke-linecap="round"/><circle cx="34" cy="34" r="8" fill="#C5A55A" opacity="0.2" stroke="#C5A55A" stroke-width="2"/><path d="M31 34l2 2 4-4" stroke="#C5A55A" stroke-width="2" stroke-linecap="round"/></svg>
          </div>
          <div class="ped-cert__info">
            <div class="ped-cert__name">Cumplimiento RGPD (autodeclarado)</div>
            <div class="ped-cert__desc">Privacy by design implementado. DPO designado, registros de tratamiento, evaluaciones de impacto y procedimientos de derechos ARCO operativos. Pendiente de auditoria externa.</div>
          </div>
        </div>
        <div class="ped-cert">
          <div class="ped-cert__icon">
            <svg width="48" height="48" viewBox="0 0 48 48" fill="none"><path d="M24 4L8 12v12c0 11 6.8 20 16 24 9.2-4 16-13 16-24V12L24 4z" stroke="#233D63" stroke-width="2"/><path d="M24 4L8 12v12c0 11 6.8 20 16 24 9.2-4 16-13 16-24V12L24 4z" fill="#233D63" opacity="0.1"/><path d="M18 24l4 4 8-8" stroke="#C5A55A" stroke-width="2.5" stroke-linecap="round"/></svg>
          </div>
          <div class="ped-cert__info">
            <div class="ped-cert__name">Preparados para EU AI Act (autodeclarado)</div>
            <div class="ped-cert__desc">Audit trail implementado, trazabilidad de decisiones IA y guardrails PII bidireccionales. Compromiso de certificacion formal cuando el reglamento entre en plena aplicacion (agosto 2026).</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="ped-section ped-section--alt">
    <div class="ped-section__container">
      <h2>Estandares tecnicos</h2>
      <div class="ped-grid ped-grid--3">
        <div class="ped-card ped-card--centered">
          <div class="ped-card__title">Cifrado AES-256</div>
          <p class="ped-card__text">Datos en reposo y en transito cifrados con estandar bancario.</p>
        </div>
        <div class="ped-card ped-card--centered">
          <div class="ped-card__title">Multi-tenant isolation</div>
          <p class="ped-card__text">Aislamiento logico por tenant con access control handlers en cada entidad.</p>
        </div>
        <div class="ped-card ped-card--centered">
          <div class="ped-card__title">OWASP Top 10</div>
          <p class="ped-card__text">Proteccion contra XSS, SQL injection, CSRF, command injection y mas.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="ped-cta-saas">
    <div class="ped-cta-saas__container">
      <h2>Necesita verificar nuestras acreditaciones?</h2>
      <p>Le proporcionamos documentacion oficial de todas nuestras certificaciones.</p>
      <div class="ped-cta-saas__actions">
        <a href="/contacto" class="btn-gold">Solicitar documentacion</a>
      </div>
    </div>
  </section>
</div>
HTML;

// ─────────────────────────────────────────────────────────────────────
// PAGE 90: PRENSA (/prensa)
// ─────────────────────────────────────────────────────────────────────
$pages[90] = <<<'HTML'
<div class="ped-page">
  <section class="ped-page-header">
    <span class="ped-page-header__eyebrow">Recursos para medios</span>
    <h1>Sala de Prensa</h1>
    <p class="ped-page-header__subtitle">Notas de prensa, datos clave, recursos graficos y contacto directo con el departamento de comunicacion.</p>
  </section>

  <section class="ped-section">
    <div class="ped-section__container">
      <h2>Datos clave para medios</h2>
      <div class="ped-grid ped-grid--4">
        <div class="ped-kpi">
          <div class="ped-kpi__value">+30</div>
          <div class="ped-kpi__label">anos de trayectoria</div>
        </div>
        <div class="ped-kpi">
          <div class="ped-kpi__value ped-kpi__value--green">+100M€</div>
          <div class="ped-kpi__label">fondos gestionados</div>
        </div>
        <div class="ped-kpi">
          <div class="ped-kpi__value ped-kpi__value--gold">+50.000</div>
          <div class="ped-kpi__label">personas beneficiarias</div>
        </div>
        <div class="ped-kpi">
          <div class="ped-kpi__value ped-kpi__value--orange">10</div>
          <div class="ped-kpi__label">verticales SaaS</div>
        </div>
      </div>
    </div>
  </section>

  <section class="ped-section ped-section--alt">
    <div class="ped-section__container">
      <h2>Press Kit</h2>
      <p>Kit de prensa completo con logotipos, fotografias corporativas, biografias del equipo directivo, ficha tecnica y guia de marca.</p>
      <div class="ped-grid ped-grid--3">
        <div class="ped-card ped-card--centered">
          <div class="ped-card__title">Logotipos</div>
          <p class="ped-card__text">Versiones en color, monocromo y negativo. Formatos PNG, SVG y EPS.</p>
        </div>
        <div class="ped-card ped-card--centered">
          <div class="ped-card__title">Fotografias</div>
          <p class="ped-card__text">Fotos corporativas del equipo, oficinas e impacto territorial.</p>
        </div>
        <div class="ped-card ped-card--centered">
          <div class="ped-card__title">Ficha tecnica</div>
          <p class="ped-card__text">Datos societarios, cifras clave, modelo de negocio y stack tecnologico.</p>
        </div>
      </div>
      <div style="text-align: center; margin-top: 2rem;">
        <a href="mailto:prensa@plataformadeecosistemas.es" class="ped-cta-inline ped-cta-inline--primary">Solicitar Press Kit</a>
      </div>
    </div>
  </section>

  <section class="ped-section">
    <div class="ped-section__container">
      <h2>Contacto de prensa</h2>
      <div class="ped-grid ped-grid--2">
        <div class="ped-contact-card">
          <div class="ped-contact-card__title">Departamento de Comunicacion</div>
          <a href="mailto:prensa@plataformadeecosistemas.es" class="ped-contact-card__detail">prensa@plataformadeecosistemas.es</a>
          <div class="ped-contact-card__meta">Tiempo de respuesta: menos de 24 horas</div>
        </div>
        <div class="ped-contact-card">
          <div class="ped-contact-card__title">Entrevistas y declaraciones</div>
          <span class="ped-contact-card__detail">Pepe Jaraba, CEO y fundador</span>
          <div class="ped-contact-card__meta">Disponible para entrevistas previa cita</div>
        </div>
      </div>
    </div>
  </section>
</div>
HTML;

// ─────────────────────────────────────────────────────────────────────
// PÁGINAS LEGALES (80, 81, 82) — Solo mejora de estilos, no de contenido
// El contenido legal se mantiene pero con clases CSS PED
// ─────────────────────────────────────────────────────────────────────

// PAGE 80: AVISO LEGAL — LSSI-CE Art. 10 compliant
$pages[80] = <<<'HTML'
<div class="ped-page">
  <section class="ped-page-header">
    <h1>Aviso Legal</h1>
    <p class="ped-page-header__subtitle">Ultima actualizacion: marzo 2026</p>
  </section>
  <section class="ped-section">
    <div class="ped-section__narrow">
      <h2>1. Datos identificativos (Art. 10 LSSI-CE)</h2>
      <p>En cumplimiento del articulo 10 de la Ley 34/2002, de 11 de julio, de Servicios de la Sociedad de la Informacion y Comercio Electronico (LSSI-CE), se informa de los siguientes datos del titular del sitio web:</p>
      <table class="ped-table">
        <tbody>
          <tr><td><strong>Razon social</strong></td><td>Plataforma de Ecosistemas Digitales S.L.</td></tr>
          <tr><td><strong>Nombre comercial</strong></td><td>Ecosistema Jaraba / PED</td></tr>
          <tr><td><strong>CIF</strong></td><td>B93750271</td></tr>
          <tr><td><strong>Domicilio social</strong></td><td>Calle Heroe de Sostoa 12, 29002 Malaga, Espana</td></tr>
          <tr><td><strong>Telefono</strong></td><td>' . (theme_get_setting('contact_phone', 'ecosistema_jaraba_theme') ?: '') . '</td></tr>
          <tr><td><strong>Email de contacto</strong></td><td>' . (\Drupal::config('system.site')->get('mail') ?: '') . '</td></tr>
          <tr><td><strong>Email legal</strong></td><td>' . (\Drupal::config('system.site')->get('mail') ?: '') . '</td></tr>
          <tr><td><strong>Registro Mercantil</strong></td><td>Inscrita en el Registro Mercantil de Malaga, Tomo 5947, Libro 4854, Folio 100, Inscripcion 1.ª, Hoja MA-156134</td></tr>
          <tr><td><strong>Actividad</strong></td><td>Desarrollo y explotacion de plataformas digitales SaaS (CNAE 6201)</td></tr>
        </tbody>
      </table>
      <p><em>Datos actualizados a marzo de 2026.</em></p>

      <h2>2. Objeto y ambito de aplicacion</h2>
      <p>El presente aviso legal regula el uso y acceso al sitio web plataformadeecosistemas.es y todos los subdominios asociados (en adelante, "el sitio web"), asi como a la plataforma SaaS Ecosistema Jaraba, propiedad de Plataforma de Ecosistemas Digitales S.L. (en adelante, "PED").</p>
      <p>La navegacion, registro o uso de los servicios del sitio web atribuye la condicion de usuario e implica la aceptacion plena y sin reservas de las disposiciones incluidas en este aviso legal, la <a href="/politica-privacidad">politica de privacidad</a> y la <a href="/politica-cookies">politica de cookies</a>, en la version publicada en el momento de acceso.</p>

      <h2>3. Propiedad intelectual e industrial</h2>
      <p>Todos los contenidos del sitio web — incluyendo textos, codigo fuente, disenos, logotipos, marcas, graficos, iconografia, imagenes, bases de datos, software y cualquier otro material — son propiedad de PED o de sus licenciantes y estan protegidos por la legislacion espanola e internacional de propiedad intelectual e industrial (Real Decreto Legislativo 1/1996, Ley 17/2001).</p>
      <p>Queda prohibida su reproduccion, distribucion, comunicacion publica o transformacion, total o parcial, sin autorizacion expresa y por escrito de PED. El uso del sitio web no otorga al usuario ningun derecho de propiedad sobre sus contenidos.</p>
      <p>Las marcas "Ecosistema Jaraba", "JarabaLex", "Metodo Jaraba" y "PED" son marcas registradas o en proceso de registro de Plataforma de Ecosistemas Digitales S.L.</p>

      <h2>4. Condiciones de uso</h2>
      <p>El usuario se compromete a:</p>
      <ul style="color: var(--ej-text-body, #4b5563); line-height: 1.8;">
        <li>Hacer un uso adecuado de los contenidos y servicios, conforme a la ley, la moral y el orden publico.</li>
        <li>No utilizar los servicios con fines ilicitos, lesivos de derechos de terceros o que puedan perjudicar la reputacion de PED.</li>
        <li>No introducir virus, malware o cualquier otro elemento que pueda danar o alterar los sistemas informaticos.</li>
        <li>No intentar acceder a areas restringidas del sitio web sin autorizacion.</li>
        <li>Respetar la multi-tenancy de la plataforma, no accediendo a datos de otros tenants.</li>
      </ul>

      <h2>5. Limitacion de responsabilidad</h2>
      <p>PED no se responsabiliza de:</p>
      <ul style="color: var(--ej-text-body, #4b5563); line-height: 1.8;">
        <li>Danos derivados del uso inadecuado del sitio web por parte del usuario.</li>
        <li>Fallos tecnicos, interrupciones del servicio o errores en las comunicaciones electronicas.</li>
        <li>Contenido de sitios web de terceros enlazados desde el sitio web.</li>
        <li>La no disponibilidad temporal del servicio por mantenimiento o causas de fuerza mayor.</li>
      </ul>
      <p>PED se compromete a adoptar las medidas tecnicas razonables para mantener la disponibilidad y seguridad del servicio.</p>

      <h2>6. Proteccion de datos</h2>
      <p>El tratamiento de datos personales se rige por nuestra <a href="/politica-privacidad">Politica de Privacidad</a>, conforme al Reglamento (UE) 2016/679 (RGPD) y la Ley Organica 3/2018 (LOPDGDD).</p>

      <h2>7. Legislacion aplicable y jurisdiccion</h2>
      <p>Las presentes condiciones se rigen por la legislacion espanola. Para cualquier controversia derivada del acceso o uso del sitio web, las partes se someten expresamente a los Juzgados y Tribunales de la ciudad de Malaga, con renuncia expresa a cualquier otro fuero que pudiera corresponderles, salvo que la legislacion aplicable imponga un fuero distinto (consumidores y usuarios).</p>

      <h2>8. Modificaciones</h2>
      <p>PED se reserva el derecho de modificar el presente aviso legal en cualquier momento, siendo la version vigente la publicada en esta URL. Se recomienda revisar periodicamente esta pagina.</p>
    </div>
  </section>
</div>
HTML;

// PAGE 81: POLITICA DE PRIVACIDAD — RGPD Art. 13/14 + LOPDGDD compliant
$pages[81] = <<<'HTML'
<div class="ped-page">
  <section class="ped-page-header">
    <h1>Politica de Privacidad</h1>
    <p class="ped-page-header__subtitle">Ultima actualizacion: marzo 2026</p>
  </section>
  <section class="ped-section">
    <div class="ped-section__narrow">

      <h2>1. Responsable del tratamiento</h2>
      <table class="ped-table">
        <tbody>
          <tr><td><strong>Identidad</strong></td><td>Plataforma de Ecosistemas Digitales S.L.</td></tr>
          <tr><td><strong>CIF</strong></td><td>B93750271</td></tr>
          <tr><td><strong>Direccion</strong></td><td>Calle Heroe de Sostoa 12, 29002 Malaga</td></tr>
          <tr><td><strong>Email privacidad</strong></td><td>' . (\Drupal::config('system.site')->get('mail') ?: '') . '</td></tr>
          <tr><td><strong>Delegado de Proteccion de Datos (DPO)</strong></td><td>Jose Jaraba Munoz — Email: ' . (\Drupal::config('system.site')->get('mail') ?: '') . '</td></tr>
        </tbody>
      </table>

      <h2>2. Finalidades del tratamiento y base juridica</h2>
      <p>Tratamos sus datos personales para las siguientes finalidades, cada una con su base juridica conforme al RGPD:</p>
      <table class="ped-table">
        <thead>
          <tr><th>Finalidad</th><th>Base juridica (Art. 6 RGPD)</th><th>Plazo de conservacion</th></tr>
        </thead>
        <tbody>
          <tr><td>Gestion de consultas via formulario de contacto</td><td>Consentimiento (art. 6.1.a)</td><td>1 ano desde la ultima comunicacion</td></tr>
          <tr><td>Prestacion de servicios SaaS contratados</td><td>Ejecucion de contrato (art. 6.1.b)</td><td>Duracion del contrato + 5 anos (obligacion mercantil)</td></tr>
          <tr><td>Facturacion y cumplimiento fiscal</td><td>Obligacion legal (art. 6.1.c)</td><td>5 anos (Ley General Tributaria)</td></tr>
          <tr><td>Comunicaciones comerciales / newsletter</td><td>Consentimiento expreso (art. 6.1.a)</td><td>Hasta revocacion del consentimiento</td></tr>
          <tr><td>Gestion de programas de empleo y formacion (B2G)</td><td>Interes publico (art. 6.1.e) / Obligacion legal</td><td>Segun normativa del programa (FSE: 10 anos)</td></tr>
          <tr><td>Analitica web anonimizada</td><td>Interes legitimo (art. 6.1.f)</td><td>26 meses (datos agregados)</td></tr>
          <tr><td>Prevencion de fraude y seguridad</td><td>Interes legitimo (art. 6.1.f)</td><td>6 meses (logs de acceso)</td></tr>
        </tbody>
      </table>

      <h2>3. Decisiones automatizadas e inteligencia artificial</h2>
      <p>La plataforma utiliza sistemas de inteligencia artificial (IA) que pueden procesar datos personales para las siguientes funciones:</p>
      <ul style="color: var(--ej-text-body, #4b5563); line-height: 1.8;">
        <li><strong>Copiloto IA:</strong> Asistente conversacional que puede acceder al contexto del usuario para proporcionar respuestas personalizadas. No toma decisiones vinculantes.</li>
        <li><strong>Matching de empleo:</strong> Algoritmo que compara competencias del candidato con ofertas disponibles. El resultado es orientativo; la decision final la toma el usuario o el tecnico.</li>
        <li><strong>Analisis de documentos legales (JarabaLex):</strong> Busqueda semantica en bases de datos juridicas. No constituye asesoramiento legal.</li>
      </ul>
      <p>Conforme al articulo 22 del RGPD, <strong>ninguna decision con efectos juridicos o significativos se basa unicamente en el tratamiento automatizado</strong>. Siempre existe supervision humana. Puede solicitar intervencion humana, expresar su punto de vista y impugnar la decision dirigiendose a privacidad@plataformadeecosistemas.es.</p>
      <p>Todos los agentes IA cuentan con audit trail completo (trazabilidad de decisiones) conforme al Reglamento Europeo de IA (EU AI Act).</p>

      <h2>4. Destinatarios y transferencias internacionales</h2>
      <p>Sus datos pueden ser comunicados a los siguientes destinatarios:</p>
      <table class="ped-table">
        <thead>
          <tr><th>Destinatario</th><th>Finalidad</th><th>Ubicacion</th><th>Garantias</th></tr>
        </thead>
        <tbody>
          <tr><td>IONOS SE (hosting)</td><td>Almacenamiento y procesamiento</td><td>Alemania (UE)</td><td>Dentro del EEE</td></tr>
          <tr><td>Stripe Inc. (pagos)</td><td>Procesamiento de pagos</td><td>EE.UU.</td><td>EU-US Data Privacy Framework (DPF)</td></tr>
          <tr><td>Anthropic PBC (IA)</td><td>Procesamiento IA (Claude API)</td><td>EE.UU.</td><td>Clausulas contractuales tipo (SCCs) + DPF</td></tr>
          <tr><td>Google LLC (analitica)</td><td>Google Analytics 4 (anonimizado)</td><td>EE.UU.</td><td>EU-US Data Privacy Framework (DPF)</td></tr>
        </tbody>
      </table>
      <p>No se realizan transferencias a paises sin nivel de proteccion adecuado sin las garantias del Capitulo V del RGPD. Puede solicitar copia de las clausulas contractuales tipo en privacidad@plataformadeecosistemas.es.</p>

      <h2>5. Derechos del interesado</h2>
      <p>Conforme a los articulos 15 a 22 del RGPD y la LOPDGDD, usted tiene derecho a:</p>
      <table class="ped-table">
        <thead>
          <tr><th>Derecho</th><th>Descripcion</th></tr>
        </thead>
        <tbody>
          <tr><td><strong>Acceso</strong> (Art. 15)</td><td>Obtener confirmacion de si tratamos sus datos y acceder a ellos.</td></tr>
          <tr><td><strong>Rectificacion</strong> (Art. 16)</td><td>Corregir datos inexactos o completar datos incompletos.</td></tr>
          <tr><td><strong>Supresion</strong> (Art. 17)</td><td>Solicitar la eliminacion de sus datos ("derecho al olvido").</td></tr>
          <tr><td><strong>Limitacion</strong> (Art. 18)</td><td>Solicitar la limitacion del tratamiento en determinadas circunstancias.</td></tr>
          <tr><td><strong>Portabilidad</strong> (Art. 20)</td><td>Recibir sus datos en formato estructurado y transmitirlos a otro responsable.</td></tr>
          <tr><td><strong>Oposicion</strong> (Art. 21)</td><td>Oponerse al tratamiento basado en interes legitimo o fines de marketing.</td></tr>
          <tr><td><strong>Retirar consentimiento</strong> (Art. 7.3)</td><td>Retirar el consentimiento en cualquier momento, sin que afecte a la licitud del tratamiento previo.</td></tr>
          <tr><td><strong>No ser objeto de decisiones automatizadas</strong> (Art. 22)</td><td>Solicitar intervencion humana en decisiones basadas en tratamiento automatizado.</td></tr>
        </tbody>
      </table>
      <p>Para ejercer cualquier derecho, envie un email a <strong>privacidad@plataformadeecosistemas.es</strong> indicando el derecho que desea ejercer y adjuntando copia de su DNI/NIE. Plazo de respuesta: 30 dias.</p>
      <p>Si considera que sus derechos no han sido debidamente atendidos, puede presentar reclamacion ante la Agencia Espanola de Proteccion de Datos (<a href="https://www.aepd.es" target="_blank" rel="noopener">www.aepd.es</a>), C/ Jorge Juan 6, 28001 Madrid.</p>

      <h2>6. Medidas de seguridad</h2>
      <p>Aplicamos medidas tecnicas y organizativas conforme al articulo 32 del RGPD:</p>
      <ul style="color: var(--ej-text-body, #4b5563); line-height: 1.8;">
        <li>Cifrado AES-256 en reposo y TLS 1.3 en transito.</li>
        <li>Control de accesos basado en roles con aislamiento multi-tenant.</li>
        <li>Logs de auditoria inmutables y monitorizacion continua.</li>
        <li>Backups cifrados con retencion de 30 dias.</li>
        <li>Evaluaciones periodicas de vulnerabilidades (OWASP Top 10).</li>
        <li>Deteccion automatica de PII en flujos de IA (bidireccional).</li>
        <li>Plan de respuesta ante brechas con notificacion a la AEPD en 72 horas.</li>
      </ul>

      <h2>7. Cookies</h2>
      <p>La informacion sobre las cookies utilizadas en este sitio web esta detallada en nuestra <a href="/politica-cookies">Politica de Cookies</a>. Puede configurar sus preferencias en cualquier momento desde el boton "Cookie settings" en el pie de pagina.</p>

      <h2>8. Modificaciones</h2>
      <p>PED se reserva el derecho de modificar esta politica para adaptarla a novedades legislativas o jurisprudenciales. Cualquier cambio sera publicado en esta pagina con la fecha de actualizacion.</p>
    </div>
  </section>
</div>
HTML;

// PAGE 82: POLITICA DE COOKIES — Guia AEPD 2023 + LSSI-CE Art. 22.2 compliant
$pages[82] = <<<'HTML'
<div class="ped-page">
  <section class="ped-page-header">
    <h1>Politica de Cookies</h1>
    <p class="ped-page-header__subtitle">Ultima actualizacion: marzo 2026</p>
  </section>
  <section class="ped-section">
    <div class="ped-section__narrow">

      <h2>1. Que son las cookies</h2>
      <p>Las cookies son pequenos archivos de texto que los sitios web almacenan en su dispositivo (ordenador, tablet o movil) al visitarlos. Permiten que el sitio recuerde sus acciones y preferencias durante un periodo de tiempo, de modo que no tenga que volver a configurarlos cada vez que visite el sitio o navegue entre paginas.</p>
      <p>Este sitio web utiliza tambien <strong>localStorage</strong> del navegador para almacenar preferencias de forma local, con una funcion equivalente a las cookies.</p>

      <h2>2. Cookies estrictamente necesarias</h2>
      <p>Estas cookies son esenciales para el funcionamiento del sitio web. No requieren consentimiento (exencion del Art. 22.2 LSSI-CE).</p>
      <table class="ped-table">
        <thead>
          <tr><th>Nombre</th><th>Proveedor</th><th>Finalidad</th><th>Tipo</th><th>Duracion</th></tr>
        </thead>
        <tbody>
          <tr><td>SSESS* / SESS*</td><td>Propia (Drupal)</td><td>Sesion de usuario autenticado. Necesaria para login y CSRF.</td><td>Cookie HTTP</td><td>Sesion / 23 dias</td></tr>
          <tr><td>jaraba_cookie_consent</td><td>Propia</td><td>Almacena las preferencias de cookies del usuario.</td><td>localStorage</td><td>365 dias</td></tr>
        </tbody>
      </table>

      <h2>3. Cookies funcionales</h2>
      <p>Mejoran la experiencia de uso. Se activan con su consentimiento.</p>
      <table class="ped-table">
        <thead>
          <tr><th>Nombre</th><th>Proveedor</th><th>Finalidad</th><th>Tipo</th><th>Duracion</th></tr>
        </thead>
        <tbody>
          <tr><td>Drupal.visitor.*</td><td>Propia (Drupal)</td><td>Preferencias de idioma, tema visual y audiencia seleccionada.</td><td>Cookie HTTP</td><td>100 dias</td></tr>
          <tr><td>jaraba_visitor_id</td><td>Propia</td><td>Identificador anonimo de visitante para personalizacion.</td><td>localStorage</td><td>365 dias</td></tr>
          <tr><td>admin_sidebar_collapsed</td><td>Propia</td><td>Estado del menu lateral (solo administradores).</td><td>localStorage</td><td>Indefinida</td></tr>
        </tbody>
      </table>

      <h2>4. Cookies analiticas</h2>
      <p>Recogen informacion anonima sobre el uso del sitio web. <strong>Solo se activan tras consentimiento explicito.</strong> Se utiliza Google Analytics 4 con Consent Mode v2 (denegado por defecto) y anonimizacion de IP.</p>
      <table class="ped-table">
        <thead>
          <tr><th>Nombre</th><th>Proveedor</th><th>Finalidad</th><th>Tipo</th><th>Duracion</th></tr>
        </thead>
        <tbody>
          <tr><td>_ga</td><td>Google (tercero)</td><td>Distinguir usuarios unicos para estadisticas de visitas.</td><td>Cookie HTTP</td><td>2 anos</td></tr>
          <tr><td>_ga_*</td><td>Google (tercero)</td><td>Mantener estado de sesion en GA4.</td><td>Cookie HTTP</td><td>2 anos</td></tr>
          <tr><td>_gid</td><td>Google (tercero)</td><td>Distinguir usuarios durante 24 horas.</td><td>Cookie HTTP</td><td>24 horas</td></tr>
        </tbody>
      </table>
      <p>Politica de privacidad de Google: <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">policies.google.com/privacy</a></p>

      <h2>5. Cookies de marketing / terceros</h2>
      <p><strong>Solo se activan tras consentimiento explicito.</strong> Actualmente no se utilizan cookies de marketing de terceros (Facebook Pixel, LinkedIn Insight, etc.) salvo que se indique en futuras actualizaciones de esta politica.</p>

      <h2>6. Cookies de proveedores de pago</h2>
      <table class="ped-table">
        <thead>
          <tr><th>Nombre</th><th>Proveedor</th><th>Finalidad</th><th>Tipo</th><th>Duracion</th></tr>
        </thead>
        <tbody>
          <tr><td>__stripe_mid</td><td>Stripe (tercero)</td><td>Prevencion de fraude en procesamiento de pagos.</td><td>Cookie HTTP</td><td>1 ano</td></tr>
          <tr><td>__stripe_sid</td><td>Stripe (tercero)</td><td>Sesion de pago para el checkout.</td><td>Cookie HTTP</td><td>30 minutos</td></tr>
        </tbody>
      </table>
      <p>Estas cookies son necesarias para el procesamiento seguro de pagos y se establecen unicamente cuando el usuario inicia un proceso de pago. Politica de privacidad de Stripe: <a href="https://stripe.com/es/privacy" target="_blank" rel="noopener">stripe.com/es/privacy</a></p>

      <h2>7. Gestion de preferencias</h2>
      <p>Puede gestionar sus preferencias de cookies en cualquier momento mediante:</p>
      <ul style="color: var(--ej-text-body, #4b5563); line-height: 1.8;">
        <li><strong>Panel de cookies de este sitio:</strong> Haga clic en el boton "Cookie settings" en el pie de pagina para reabrir el banner de configuracion.</li>
        <li><strong>Configuracion del navegador:</strong>
          <ul>
            <li>Chrome: <code>chrome://settings/cookies</code></li>
            <li>Firefox: <code>about:preferences#privacy</code></li>
            <li>Safari: Preferencias &gt; Privacidad</li>
            <li>Edge: <code>edge://settings/privacy</code></li>
          </ul>
        </li>
      </ul>
      <p>La desactivacion de cookies esenciales puede impedir el correcto funcionamiento del sitio web. La desactivacion de cookies analiticas no afecta a la navegacion.</p>

      <h2>8. Consentimiento</h2>
      <p>Al acceder al sitio web por primera vez, se muestra un banner de consentimiento de cookies que le permite:</p>
      <ul style="color: var(--ej-text-body, #4b5563); line-height: 1.8;">
        <li><strong>Aceptar todas</strong> las cookies.</li>
        <li><strong>Rechazar</strong> las cookies no esenciales.</li>
        <li><strong>Configurar</strong> sus preferencias por categoria.</li>
      </ul>
      <p>Conforme al articulo 7.3 del RGPD, puede retirar su consentimiento en cualquier momento con la misma facilidad con que lo otorgo, usando el boton "Cookie settings" del pie de pagina.</p>

      <h2>9. Actualizacion</h2>
      <p>Esta politica puede actualizarse para reflejar cambios en las cookies utilizadas o en la legislacion aplicable. La fecha de la ultima actualizacion se indica al inicio de esta pagina.</p>
    </div>
  </section>
</div>
HTML;

// ═══════════════════════════════════════════════════════════════════════
// EJECUCIÓN — Actualizar canvas_data de cada página
// ═══════════════════════════════════════════════════════════════════════

$updated = 0;
$errors = [];

foreach ($pages as $id => $html) {
  try {
    $entity = $storage->load($id);
    if (!$entity) {
      $errors[] = "Page $id not found";
      continue;
    }

    $canvas_data = json_encode([
      'html' => $html,
      'css' => '',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // Update ALL translations to avoid language negotiation serving legacy content.
    // Clear rendered_html (pre-rendered cache has priority over canvas_data in ViewBuilder).
    $entity->set('canvas_data', $canvas_data);
    $entity->set('rendered_html', '');
    foreach ($entity->getTranslationLanguages() as $langcode => $language) {
      if ($langcode !== $entity->language()->getId()) {
        $translation = $entity->getTranslation($langcode);
        $translation->set('canvas_data', $canvas_data);
        $translation->set('rendered_html', '');
      }
    }
    $entity->save();
    $updated++;

    $title = $entity->get('title')->value;
    echo "OK: Page $id ($title) — " . strlen($html) . " bytes\n";
  }
  catch (\Throwable $e) {
    $errors[] = "Page $id: " . $e->getMessage();
    echo "ERROR: Page $id — " . $e->getMessage() . "\n";
  }
}

echo "\n=== RESUMEN ===\n";
echo "Actualizadas: $updated / " . count($pages) . "\n";
if (!empty($errors)) {
  echo "Errores: " . count($errors) . "\n";
  foreach ($errors as $err) {
    echo "  - $err\n";
  }
}
