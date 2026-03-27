<?php

/**
 * Restaura el perfil enriquecido de Remedios Estévez en /equipo (page 87).
 *
 * Datos reales de su CV (Licenciada en Economía UNED, MBA, 15 titulaciones,
 * 3 posiciones profesionales con timeline, 8 badges).
 */

declare(strict_types=1);

$storage = \Drupal::entityTypeManager()->getStorage('page_content');
$page = $storage->load(87);

if (!$page) {
  echo "ERROR: Page 87 not found\n";
  return;
}

$canvas = json_decode($page->get('canvas_data')->value, FALSE);
$html = $canvas->html ?? '';

// Find the Remedios section to replace.
$startMarker = '<!-- PERFIL COFUNDADORA 2';
$endMarker = '</section>';

$start = strpos($html, $startMarker);
if ($start === FALSE) {
  echo "ERROR: Cannot find Remedios section start marker\n";
  return;
}

$endSearch = strpos($html, $endMarker, strpos($html, 'remedios-estevez'));
if ($endSearch === FALSE) {
  echo "ERROR: Cannot find Remedios section end marker\n";
  return;
}
$end = $endSearch + strlen($endMarker);

$oldSection = substr($html, $start, $end - $start);
echo "Old section length: " . strlen($oldSection) . "\n";

// New enriched section with real CV data.
$newSection = <<<'HTML'
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
            <span class="ped-founder__badge">Emprendimiento</span>
            <span class="ped-founder__badge">Empleabilidad</span>
            <span class="ped-founder__badge">+70 cursos de formación</span>
          </div>
          <p class="ped-founder__bio">Remedios Estévez es Licenciada en Economía por la UNED (especialidad Análisis Económico, 2002), con dos Másteres en Dirección de Instituciones Sociales y en Servicios Sociales Europeos (IEEE, más de 1.300 horas), Certificado de Habilitación Docente en FP (SSCE0110, 2024) y formación específica en Docencia para el Empleo por la Universidad Nebrija (350 horas, 14 créditos ECTS).</p>
          <p class="ped-founder__bio">Cuenta con más de 20 años de experiencia en gestión de políticas activas de empleo, emprendimiento y servicios públicos locales, habiendo participado en el diseño, coordinación y ejecución de programas cofinanciados por el Fondo Social Europeo y administraciones autonómicas y provinciales.</p>
          <p class="ped-founder__bio">Su formación complementaria supera las 5.000 horas en más de 70 cursos especializados en áreas de empleo, economía social, formación y gestión pública.</p>
          <p class="ped-founder__bio">Aporta una visión técnica rigurosa combinada con un conocimiento profundo de la realidad del empleo y el emprendimiento en el ámbito local y rural.</p>
          <blockquote class="ped-founder__quote">
            <p>&laquo;Cada persona tiene un potencial profesional único; mi labor es ayudarle a descubrirlo y ponerlo en valor.&raquo;</p>
          </blockquote>
        </div>
      </div>

      <!-- Trayectoria profesional -->
      <div class="ped-founder__timeline" style="margin-top: 2.5rem;">
        <h3 style="font-size: 1.25rem; color: var(--ej-azul-corporativo, #233D63); margin-bottom: 1.5rem;">Trayectoria profesional</h3>
        <div style="border-left: 3px solid var(--ej-naranja-impulso, #FF8C42); padding-left: 1.5rem;">
          <div style="margin-bottom: 1.5rem;">
            <div style="font-weight: 700; color: var(--ej-azul-corporativo, #233D63);">Gerente — Sociedad Municipal de Desarrollo de Monturque S.L.</div>
            <div style="font-size: 0.9rem; color: #6B7280;">2005 – actualidad &middot; Monturque, Córdoba</div>
            <p style="margin-top: 0.5rem; color: #374151;">Dirección integral de empresa pública municipal. Diseño y ejecución de programas de empleo, emprendimiento y formación para el empleo. Gestión de subvenciones de la Junta de Andalucía, FSE+ y Diputación Provincial.</p>
          </div>
          <div style="margin-bottom: 1.5rem;">
            <div style="font-weight: 700; color: var(--ej-azul-corporativo, #233D63);">Contable — Ayuntamiento de Santaella</div>
            <div style="font-size: 0.9rem; color: #6B7280;">2000 – 2005 &middot; Santaella, Córdoba</div>
            <p style="margin-top: 0.5rem; color: #374151;">Contabilidad pública municipal y gestión administrativa.</p>
          </div>
          <div style="margin-bottom: 1.5rem;">
            <div style="font-weight: 700; color: var(--ej-azul-corporativo, #233D63);">Asesora Fiscal, Laboral y Contable — E.J. & J.E. Consultores Reunidos</div>
            <div style="font-size: 0.9rem; color: #6B7280;">1993 – 2000 &middot; Córdoba</div>
            <p style="margin-top: 0.5rem; color: #374151;">Asesoramiento fiscal, laboral y contable a empresas y autónomos.</p>
          </div>
        </div>
      </div>

      <!-- Formación académica -->
      <div class="ped-founder__education" style="margin-top: 2.5rem;">
        <h3 style="font-size: 1.25rem; color: var(--ej-azul-corporativo, #233D63); margin-bottom: 1.5rem;">Formación académica</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem;">
          <div style="background: var(--ej-bg-subtle, #F8FAFC); border-radius: 0.75rem; padding: 1rem; border-left: 3px solid var(--ej-azul-corporativo, #233D63);">
            <div style="font-weight: 600;">Licenciada en Economía</div>
            <div style="font-size: 0.85rem; color: #6B7280;">UNED · 2002 · Esp. Análisis Económico</div>
          </div>
          <div style="background: var(--ej-bg-subtle, #F8FAFC); border-radius: 0.75rem; padding: 1rem; border-left: 3px solid var(--ej-azul-corporativo, #233D63);">
            <div style="font-weight: 600;">Máster MBA — Dirección Instituciones Sociales</div>
            <div style="font-size: 0.85rem; color: #6B7280;">IEEE · 2012 · 600 horas</div>
          </div>
          <div style="background: var(--ej-bg-subtle, #F8FAFC); border-radius: 0.75rem; padding: 1rem; border-left: 3px solid var(--ej-azul-corporativo, #233D63);">
            <div style="font-weight: 600;">Máster Europeo MBA — Servicios Sociales</div>
            <div style="font-size: 0.85rem; color: #6B7280;">IEEE · 2015 · 700 horas</div>
          </div>
          <div style="background: var(--ej-bg-subtle, #F8FAFC); border-radius: 0.75rem; padding: 1rem; border-left: 3px solid var(--ej-naranja-impulso, #FF8C42);">
            <div style="font-weight: 600;">Certificado Profesional — Docencia FP (SSCE0110)</div>
            <div style="font-size: 0.85rem; color: #6B7280;">2024 · Habilitación docente</div>
          </div>
          <div style="background: var(--ej-bg-subtle, #F8FAFC); border-radius: 0.75rem; padding: 1rem; border-left: 3px solid var(--ej-naranja-impulso, #FF8C42);">
            <div style="font-weight: 600;">Docencia de la Formación para el Empleo</div>
            <div style="font-size: 0.85rem; color: #6B7280;">Univ. Nebrija · 2023 · 350h, 14 créditos ECTS</div>
          </div>
          <div style="background: var(--ej-bg-subtle, #F8FAFC); border-radius: 0.75rem; padding: 1rem; border-left: 3px solid var(--ej-verde-innovacion, #00A9A5);">
            <div style="font-weight: 600;">Esp. Régimen Jurídico AAPP (I y II)</div>
            <div style="font-size: 0.85rem; color: #6B7280;">Univ. Miguel de Cervantes · 2022 · 500h total</div>
          </div>
          <div style="background: var(--ej-bg-subtle, #F8FAFC); border-radius: 0.75rem; padding: 1rem; border-left: 3px solid var(--ej-verde-innovacion, #00A9A5);">
            <div style="font-weight: 600;">Técnico Superior — Inteligencia Emocional Empresarial</div>
            <div style="font-size: 0.85rem; color: #6B7280;">IEEE · 2012 · 220 horas</div>
          </div>
          <div style="background: var(--ej-bg-subtle, #F8FAFC); border-radius: 0.75rem; padding: 1rem; border-left: 3px solid var(--ej-verde-innovacion, #00A9A5);">
            <div style="font-weight: 600;">Formadora Ocupacional</div>
            <div style="font-size: 0.85rem; color: #6B7280;">Fund. Tripartita / FSE · 2009 · 380 horas</div>
          </div>
          <div style="background: var(--ej-bg-subtle, #F8FAFC); border-radius: 0.75rem; padding: 1rem; border-left: 3px solid var(--ej-verde-innovacion, #00A9A5);">
            <div style="font-weight: 600;">Técnico Superior — Atención a Personas Dependientes</div>
            <div style="font-size: 0.85rem; color: #6B7280;">2010 · 300 horas</div>
          </div>
        </div>
      </div>

      <div class="ped-founder__social" style="margin-top: 2rem;">
        <a href="https://www.linkedin.com/in/remedios-est%C3%A9vez-palomino/" target="_blank" rel="noopener noreferrer" class="ped-founder__social-link" aria-label="Perfil de LinkedIn de Remedios Estévez Palomino">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
          LinkedIn
        </a>
      </div>
    </div>
  </section>
HTML;

$html = substr($html, 0, $start) . $newSection . substr($html, $end);
$canvas->html = $html;

$newCanvasJson = json_encode($canvas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if (json_decode($newCanvasJson) === NULL) {
  echo "ERROR: Invalid JSON after replacement\n";
  return;
}

$page->set('canvas_data', $newCanvasJson);
$page->save();

echo "SUCCESS: Remedios profile enriched with real CV data.\n";
echo "New canvas length: " . strlen($newCanvasJson) . "\n";
echo "Has UNED: " . (str_contains($newCanvasJson, 'UNED') ? 'YES' : 'NO') . "\n";
echo "Has Monturque: " . (str_contains($newCanvasJson, 'Monturque') ? 'YES' : 'NO') . "\n";
echo "Has timeline: " . (str_contains($newCanvasJson, 'Trayectoria profesional') ? 'YES' : 'NO') . "\n";
echo "Has 9 credentials: " . substr_count($newCanvasJson, 'ped-founder__education') . " education section(s)\n";
