<?php

/**
 * FINAL FIX: Remedios Estévez profile on /equipo — matching Pepe's format exactly.
 *
 * Structure: Hero section → Quote section → Timeline section → Credentials section
 * Icons: SVG inline duotone (graduation cap for degrees, checkmark for certs)
 * Data: Real CV (UNED 2002, MBA IEEE, Monturque 2005+, etc.)
 */

declare(strict_types=1);

$storage = \Drupal::entityTypeManager()->getStorage('page_content');
$page = $storage->load(87);
$canvas = json_decode($page->get('canvas_data')->value, FALSE);
$html = $canvas->html ?? '';

// SVG icons matching Pepe's exact style.
$svgDegree = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#C49B30" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 1.657 2.686 3 6 3s6-1.343 6-3v-5"/></svg>';
$svgCert = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#C49B30" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg>';

// Find start/end of all Remedios content.
$remStart = strpos($html, '<!-- PERFIL COFUNDADORA 2');
$areasStart = strpos($html, 'Areas clave del equipo');
$lastSectionEnd = strrpos(substr($html, 0, $areasStart), '</section>') + strlen('</section>');

$beforeRemedios = substr($html, 0, $remStart);
$afterRemedios = substr($html, $lastSectionEnd);

// New Remedios content — 4 sections matching Pepe's format EXACTLY.
$newRemedios = <<<HEREDOC
<!-- PERFIL COFUNDADORA 2 — Remedios Estévez Palomino                 -->
  <!-- ================================================================ -->
  <section class="ped-section" id="remedios-estevez">
    <div class="ped-section__narrow">
      <div class="ped-founder__hero">
        <div class="ped-founder__photo-wrapper">
          <img src="/themes/custom/ecosistema_jaraba_theme/images/equipo-remedios-estevez.webp"
               srcset="/themes/custom/ecosistema_jaraba_theme/images/equipo-remedios-estevez.webp 2x"
               alt="Remedios Estévez Palomino — Cofundadora y COO de Plataforma de Ecosistemas Digitales"
               class="ped-founder__photo"
               width="280" height="280"
               loading="lazy" />
        </div>
        <div>
          <div class="ped-founder__name">Remedios Estévez Palomino</div>
          <div class="ped-founder__title">Cofundadora y COO</div>
          <div class="ped-founder__subtitle">Licenciada en Economía (UNED) &middot; MBA Servicios Sociales &middot; +20 años en gestión pública</div>
          <div class="ped-founder__badges">
            <span class="ped-founder__badge">Economía (UNED)</span>
            <span class="ped-founder__badge">MBA Servicios Sociales</span>
            <span class="ped-founder__badge">Formadora habilitada FP</span>
            <span class="ped-founder__badge">Desarrollo Económico Local</span>
            <span class="ped-founder__badge">Gestión de Subvenciones</span>
          </div>
          <p class="ped-founder__bio">Remedios Estévez Palomino es economista y formadora con más de 20 años de experiencia en gestión de políticas activas de empleo, emprendimiento y servicios públicos locales. Licenciada en Economía por la UNED (especialidad Análisis Económico, 2002), con dos Másteres en Dirección de Instituciones Sociales y en Servicios Sociales Europeos (IEEE, más de 1.300 horas), Certificado de Habilitación Docente en FP (SSCE0110, 2024) y formación específica en Docencia para el Empleo por la Universidad Nebrija (350 horas, 14 créditos ECTS).</p>
          <p class="ped-founder__bio">A lo largo de su trayectoria ha desarrollado una sólida experiencia en el diseño, coordinación y ejecución de proyectos con financiación pública y privada, acumulando un conocimiento profundo del mundo de los negocios, del tejido emprendedor y de la realidad cotidiana de los autónomos.</p>
          <p class="ped-founder__bio">Es autora de <em>Equilibrio Autónomo</em> (Más ingresos &middot; Menos estrés &middot; Más vida), guía práctica para autónomos que combina su formación económica rigurosa con su experiencia directa en acompañamiento a personas emprendedoras.</p>
          <p class="ped-founder__bio">Su formación complementaria supera las 5.000 horas en más de 70 cursos especializados en empleo, economía social, formación y gestión pública. Aporta una visión técnica sólida con capacidad para trasladarla de forma clara y práctica a quienes trabajan por su cuenta.</p>
          <div class="ped-founder__social">
            <a href="https://www.linkedin.com/in/remedios-est%C3%A9vez-palomino/" target="_blank" rel="noopener noreferrer" class="ped-founder__social-link" aria-label="Perfil de LinkedIn de Remedios Estévez Palomino">
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
        <p class="ped-founder__quote-text">Cada persona tiene un potencial profesional único; mi labor es ayudarle a descubrirlo y ponerlo en valor. En 20 años de trabajo en el terreno he aprendido que la empleabilidad no se improvisa: se construye con rigor, cercanía y programas que de verdad conectan con las necesidades del territorio.</p>
        <div class="ped-founder__quote-attr">&mdash; Remedios Estévez Palomino, Cofundadora y COO</div>
      </div>
    </div>
  </section>

  <!-- Trayectoria profesional Remedios -->
  <section class="ped-section">
    <div class="ped-section__narrow">
      <h2>Trayectoria profesional &mdash; Remedios Estévez</h2>
      <div class="ped-founder__timeline">
        <div class="ped-founder__timeline-item">
          <div class="ped-founder__timeline-date">2020 &ndash; Presente</div>
          <div class="ped-founder__timeline-title">Cofundadora y COO</div>
          <div class="ped-founder__timeline-org">Plataforma de Ecosistemas Digitales S.L. (PED) &mdash; Andalucía</div>
          <p class="ped-founder__timeline-desc">Cofundación y dirección operativa de empresa especializada en empleabilidad y emprendimiento digital. Diseño de procesos y metodologías de empleabilidad. Gestión de equipos multidisciplinares. Supervisión de programas de empleo e impacto social. Puesta en marcha y coordinación del vertical Andalucía +ei.</p>
        </div>
        <div class="ped-founder__timeline-item">
          <div class="ped-founder__timeline-date">2005 &ndash; Presente</div>
          <div class="ped-founder__timeline-title">Especialista en gestión pública y en la gestión directa de servicios públicos</div>
          <div class="ped-founder__timeline-org">Sector público municipal &mdash; Córdoba</div>
          <p class="ped-founder__timeline-desc">Dirección y gestión en entidad pública local. Gestión y justificación de subvenciones cofinanciadas por el FSE+ y administraciones autonómicas y provinciales. Dirección de equipos y coordinación con entidades públicas y privadas.</p>
        </div>
        <div class="ped-founder__timeline-item ped-founder__timeline-item--book">
          <p class="ped-founder__timeline-desc">📖 Autora: <em>Equilibrio Autónomo &mdash; Más ingresos &middot; Menos estrés &middot; Más vida. El plan para conseguirlo.</em> ISBN: 979-13-991329-0-8 (2025)</p>
        </div>
        <div class="ped-founder__timeline-item">
          <div class="ped-founder__timeline-date">2000 &ndash; 2005</div>
          <div class="ped-founder__timeline-title">Colaboración en área contable</div>
          <div class="ped-founder__timeline-org">Varias empresas e instituciones &mdash; Córdoba</div>
          <p class="ped-founder__timeline-desc">Apoyo contable y administrativo en distintos contextos. Contabilidad pública municipal, gestión presupuestaria y administrativa en administración local. Contabilidad y gestión administrativa en empresas del sector privado.</p>
        </div>
        <div class="ped-founder__timeline-item">
          <div class="ped-founder__timeline-date">1993 &ndash; 2000</div>
          <div class="ped-founder__timeline-title">Asesora Fiscal, Laboral y Contable</div>
          <div class="ped-founder__timeline-org">E.J. &amp; J.E. Consultores Reunidos &mdash; Córdoba</div>
          <p class="ped-founder__timeline-desc">Asesoramiento integral a empresas y autónomos. Gestión fiscal, laboral y contable para pymes y autónomos. Primera experiencia profesional en el sector privado.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Formación académica Remedios -->
  <section class="ped-section ped-section--alt">
    <div class="ped-section__container">
      <h2>Formación académica y certificaciones &mdash; Remedios Estévez</h2>
      <div class="ped-founder__credentials-grid">
        <div class="ped-founder__credential">
          <div class="ped-founder__credential-icon">
            {$svgDegree}
          </div>
          <div>
            <div class="ped-founder__credential-title">Licenciada en Economía</div>
            <div class="ped-founder__credential-detail">UNED &mdash; 2002 &middot; Especialidad Análisis Económico</div>
          </div>
        </div>
        <div class="ped-founder__credential">
          <div class="ped-founder__credential-icon">
            {$svgDegree}
          </div>
          <div>
            <div class="ped-founder__credential-title">Máster MBA &mdash; Dirección Instituciones Sociales</div>
            <div class="ped-founder__credential-detail">IEEE &mdash; 2012 &middot; 600 horas &middot; Atención a la Dependencia</div>
          </div>
        </div>
        <div class="ped-founder__credential">
          <div class="ped-founder__credential-icon">
            {$svgDegree}
          </div>
          <div>
            <div class="ped-founder__credential-title">Máster Europeo MBA &mdash; Servicios Sociales</div>
            <div class="ped-founder__credential-detail">IEEE &mdash; 2015 &middot; 700 horas</div>
          </div>
        </div>
        <div class="ped-founder__credential">
          <div class="ped-founder__credential-icon">
            {$svgCert}
          </div>
          <div>
            <div class="ped-founder__credential-title">Certificado Profesional &mdash; Docencia FP (SSCE0110)</div>
            <div class="ped-founder__credential-detail">2024 &middot; Habilitación para docencia en Formación Profesional</div>
          </div>
        </div>
        <div class="ped-founder__credential">
          <div class="ped-founder__credential-icon">
            {$svgCert}
          </div>
          <div>
            <div class="ped-founder__credential-title">Docencia de la Formación para el Empleo</div>
            <div class="ped-founder__credential-detail">Universidad Nebrija &mdash; 2023 &middot; 350 horas, 14 créditos ECTS</div>
          </div>
        </div>
        <div class="ped-founder__credential">
          <div class="ped-founder__credential-icon">
            {$svgCert}
          </div>
          <div>
            <div class="ped-founder__credential-title">Esp. Régimen Jurídico AAPP (I y II)</div>
            <div class="ped-founder__credential-detail">Universidad Miguel de Cervantes &mdash; 2022 &middot; 500 horas total</div>
          </div>
        </div>
        <div class="ped-founder__credential">
          <div class="ped-founder__credential-icon">
            {$svgCert}
          </div>
          <div>
            <div class="ped-founder__credential-title">Técnico Superior &mdash; Inteligencia Emocional Empresarial</div>
            <div class="ped-founder__credential-detail">IEEE &mdash; 2012 &middot; 220 horas</div>
          </div>
        </div>
        <div class="ped-founder__credential">
          <div class="ped-founder__credential-icon">
            {$svgCert}
          </div>
          <div>
            <div class="ped-founder__credential-title">Formadora Ocupacional</div>
            <div class="ped-founder__credential-detail">Fundación Tripartita / FSE &mdash; 2009 &middot; 380 horas</div>
          </div>
        </div>
        <div class="ped-founder__credential">
          <div class="ped-founder__credential-icon">
            {$svgCert}
          </div>
          <div>
            <div class="ped-founder__credential-title">Técnico Superior &mdash; Atención a Personas Dependientes</div>
            <div class="ped-founder__credential-detail">2010 &middot; 300 horas</div>
          </div>
        </div>
      </div>
      <p style="margin-top: 1.5rem; text-align: center; color: #6B7280; font-size: 0.9rem;">+70 cursos de formación complementaria &middot; Más de 5.000 horas de formación continua</p>
    </div>
  </section>

  <!-- ================================================================ -->
HEREDOC;

$html = $beforeRemedios . $newRemedios . "\n" . $afterRemedios;
$canvas->html = $html;

$newJson = json_encode($canvas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if (json_decode($newJson) === NULL) {
  echo "ERROR: Invalid JSON\n";
  return;
}

$page->set('canvas_data', $newJson);
$page->save();

echo "SUCCESS: Remedios profile rebuilt matching Pepe's exact format.\n";
echo "Canvas length: " . strlen($newJson) . "\n";
echo "Emojis: " . preg_match_all('/[\x{1F600}-\x{1F9FF}]/u', $html) . "\n";
echo "SVG icons: " . substr_count($html, '<svg') . "\n";
echo "Sections: " . substr_count($html, '<section') . "\n";
