<?php

/**
 * Fixes Remedios Estévez profile on /equipo (page 87).
 *
 * - Removes old duplicate sections (quote, timeline, credentials)
 * - Restructures to match Pepe Jaraba's format (separate sections)
 * - Uses correct data from real CV
 */

declare(strict_types=1);

$storage = \Drupal::entityTypeManager()->getStorage('page_content');
$page = $storage->load(87);
$canvas = json_decode($page->get('canvas_data')->value, FALSE);
$html = $canvas->html ?? '';

// ── STEP 1: Replace the entire Remedios section + old duplicates ──
// Find where Remedios content starts (the comment marker).
$remStart = strpos($html, '<!-- PERFIL COFUNDADORA 2');
// Find where the last old Remedios section ends (before "Areas clave").
$areasStart = strpos($html, 'Areas clave del equipo');
$lastOldSectionEnd = strrpos(substr($html, 0, $areasStart), '</section>') + strlen('</section>');

$beforeRemedios = substr($html, 0, $remStart);
$afterRemedios = substr($html, $lastOldSectionEnd);

echo "Before Remedios: " . strlen($beforeRemedios) . " chars\n";
echo "After Remedios (areas clave + CTA): " . strlen($afterRemedios) . " chars\n";

// ── STEP 2: New Remedios content matching Pepe's format ──
$newRemedios = <<<'HTML'
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
            <span class="ped-founder__badge">Licenciada en Economía</span>
            <span class="ped-founder__badge">MBA Servicios Sociales</span>
            <span class="ped-founder__badge">Formadora habilitada FP</span>
            <span class="ped-founder__badge">Desarrollo Económico Local</span>
            <span class="ped-founder__badge">Gestión de Subvenciones</span>
          </div>
          <p class="ped-founder__bio">Remedios Estévez es Licenciada en Economía por la UNED (especialidad Análisis Económico, 2002), con dos Másteres en Dirección de Instituciones Sociales y en Servicios Sociales Europeos (IEEE, más de 1.300 horas), Certificado de Habilitación Docente en FP (SSCE0110, 2024) y formación específica en Docencia para el Empleo por la Universidad Nebrija (350 horas, 14 créditos ECTS).</p>
          <p class="ped-founder__bio">Cuenta con más de 20 años de experiencia en gestión de políticas activas de empleo, emprendimiento y servicios públicos locales, habiendo participado en el diseño, coordinación y ejecución de programas cofinanciados por el Fondo Social Europeo y administraciones autonómicas y provinciales.</p>
          <p class="ped-founder__bio">Su formación complementaria supera las 5.000 horas en más de 70 cursos especializados en áreas de empleo, economía social, formación y gestión pública.</p>
          <p class="ped-founder__bio">Aporta una visión técnica rigurosa combinada con un conocimiento profundo de la realidad del empleo y el emprendimiento en el ámbito local y rural.</p>
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

  <!-- ================================================================ -->
  <!-- CITA — Remedios Estévez Palomino                                 -->
  <!-- ================================================================ -->
  <section class="ped-section ped-section--alt">
    <div class="ped-section__narrow" style="text-align: center;">
      <div class="ped-founder__quote">
        <p>&laquo;Cada persona tiene un potencial profesional único; mi labor es ayudarle a descubrirlo y ponerlo en valor.&raquo;</p>
      </div>
      <div class="ped-founder__quote-attr">&mdash; Remedios Estévez Palomino, Cofundadora y COO</div>
    </div>
  </section>

  <!-- ================================================================ -->
  <!-- TRAYECTORIA — Remedios Estévez Palomino                          -->
  <!-- ================================================================ -->
  <section class="ped-section">
    <div class="ped-section__narrow">
      <h2>Trayectoria profesional &mdash; Remedios Estévez</h2>
      <div class="ped-founder__timeline">
        <div class="ped-founder__timeline-item">
          <div class="ped-founder__timeline-date">2020 &ndash; Presente</div>
          <div class="ped-founder__timeline-title">Cofundadora y COO</div>
          <div class="ped-founder__timeline-org">Plataforma de Ecosistemas Digitales S.L. (PED)</div>
          <div class="ped-founder__timeline-desc">Cofundación y dirección operativa. Diseño de procesos, gestión de equipos multidisciplinares y supervisión de programas de empleo e impacto social.</div>
        </div>
        <div class="ped-founder__timeline-item">
          <div class="ped-founder__timeline-date">2005 &ndash; Presente</div>
          <div class="ped-founder__timeline-title">Gerente</div>
          <div class="ped-founder__timeline-org">Sociedad Municipal de Desarrollo de Monturque S.L. &mdash; Monturque, Córdoba</div>
          <div class="ped-founder__timeline-desc">Dirección integral de empresa pública municipal. Diseño y ejecución de programas de empleo, emprendimiento y formación profesional. Gestión de subvenciones de la Junta de Andalucía, FSE+ y Diputación Provincial.</div>
        </div>
        <div class="ped-founder__timeline-item">
          <div class="ped-founder__timeline-date">2000 &ndash; 2005</div>
          <div class="ped-founder__timeline-title">Contable</div>
          <div class="ped-founder__timeline-org">Ayuntamiento de Santaella &mdash; Santaella, Córdoba</div>
          <div class="ped-founder__timeline-desc">Contabilidad pública municipal y gestión administrativa.</div>
        </div>
        <div class="ped-founder__timeline-item">
          <div class="ped-founder__timeline-date">1993 &ndash; 2000</div>
          <div class="ped-founder__timeline-title">Asesora Fiscal, Laboral y Contable</div>
          <div class="ped-founder__timeline-org">E.J. &amp; J.E. Consultores Reunidos &mdash; Córdoba</div>
          <div class="ped-founder__timeline-desc">Asesoramiento integral a empresas y autónomos en materia fiscal, laboral y contable.</div>
        </div>
      </div>
    </div>
  </section>

  <!-- ================================================================ -->
  <!-- FORMACIÓN — Remedios Estévez Palomino                            -->
  <!-- ================================================================ -->
  <section class="ped-section ped-section--alt">
    <div class="ped-section__narrow">
      <h2>Formación académica &mdash; Remedios Estévez</h2>
      <div class="ped-founder__credentials-grid">
        <div class="ped-founder__credential">
          <div class="ped-founder__credential-icon">🎓</div>
          <div class="ped-founder__credential-title">Licenciada en Economía</div>
          <div class="ped-founder__credential-detail">UNED &mdash; 2002 &middot; Especialidad Análisis Económico</div>
        </div>
        <div class="ped-founder__credential">
          <div class="ped-founder__credential-icon">🎓</div>
          <div class="ped-founder__credential-title">Máster MBA &mdash; Dirección Instituciones Sociales</div>
          <div class="ped-founder__credential-detail">IEEE &mdash; 2012 &middot; 600 horas</div>
        </div>
        <div class="ped-founder__credential">
          <div class="ped-founder__credential-icon">🎓</div>
          <div class="ped-founder__credential-title">Máster Europeo MBA &mdash; Servicios Sociales</div>
          <div class="ped-founder__credential-detail">IEEE &mdash; 2015 &middot; 700 horas</div>
        </div>
        <div class="ped-founder__credential">
          <div class="ped-founder__credential-icon">📋</div>
          <div class="ped-founder__credential-title">Certificado Profesional &mdash; Docencia FP (SSCE0110)</div>
          <div class="ped-founder__credential-detail">2024 &middot; Habilitación para docencia en Formación Profesional</div>
        </div>
        <div class="ped-founder__credential">
          <div class="ped-founder__credential-icon">📋</div>
          <div class="ped-founder__credential-title">Docencia de la Formación para el Empleo</div>
          <div class="ped-founder__credential-detail">Universidad Nebrija &mdash; 2023 &middot; 350 horas, 14 créditos ECTS</div>
        </div>
        <div class="ped-founder__credential">
          <div class="ped-founder__credential-icon">⚖️</div>
          <div class="ped-founder__credential-title">Esp. Régimen Jurídico AAPP (I y II)</div>
          <div class="ped-founder__credential-detail">Universidad Miguel de Cervantes &mdash; 2022 &middot; 500 horas total</div>
        </div>
        <div class="ped-founder__credential">
          <div class="ped-founder__credential-icon">🧠</div>
          <div class="ped-founder__credential-title">Técnico Superior &mdash; Inteligencia Emocional Empresarial</div>
          <div class="ped-founder__credential-detail">IEEE &mdash; 2012 &middot; 220 horas</div>
        </div>
        <div class="ped-founder__credential">
          <div class="ped-founder__credential-icon">👥</div>
          <div class="ped-founder__credential-title">Formadora Ocupacional</div>
          <div class="ped-founder__credential-detail">Fundación Tripartita / FSE &mdash; 2009 &middot; 380 horas</div>
        </div>
        <div class="ped-founder__credential">
          <div class="ped-founder__credential-icon">🤝</div>
          <div class="ped-founder__credential-title">Técnico Superior &mdash; Atención a Personas Dependientes</div>
          <div class="ped-founder__credential-detail">2010 &middot; 300 horas</div>
        </div>
      </div>
      <p style="margin-top: 1.5rem; text-align: center; color: #6B7280; font-size: 0.9rem;">+70 cursos de formación complementaria &middot; Más de 5.000 horas de formación continua</p>
    </div>
  </section>

HTML;

$html = $beforeRemedios . $newRemedios . "\n" . $afterRemedios;
$canvas->html = $html;

$newCanvasJson = json_encode($canvas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if (json_decode($newCanvasJson) === NULL) {
  echo "ERROR: Invalid JSON\n";
  return;
}

$page->set('canvas_data', $newCanvasJson);
$page->save();

echo "SUCCESS: Remedios profile restructured to match Pepe's format.\n";
echo "Canvas length: " . strlen($newCanvasJson) . "\n";

// Verify structure.
preg_match_all('/<section[^>]*>/', $html, $sections);
echo "Total sections: " . count($sections[0]) . "\n";
echo "Has duplicate timeline: " . (substr_count($html, 'Trayectoria profesional') > 1 ? 'NO — single' : 'CLEAN') . "\n";
echo "Has duplicate credentials: " . (substr_count($html, 'Formacion academica') + substr_count($html, 'Formación académica')) . " education section(s)\n";
