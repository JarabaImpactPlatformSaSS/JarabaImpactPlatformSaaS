<?php

/**
 * @file
 * Actualiza el canvas_data de /metodo en pepejaraba.com al Método Jaraba v2.
 *
 * 8 secciones: hero, problema, solución, 3 capas, 4 competencias,
 * CID 90 días, 3 caminos, evidencia + CTA.
 *
 * Uso: lando drush php:script scripts/maintenance/update-metodo-pepejaraba-v2.php
 *
 * CONTENT-SEED-PIPELINE-001: UUID-anchored, idempotente.
 */

$uuid = 'ba196e7c-78b7-48e9-9c34-a221f77dd044';

$pageStorage = \Drupal::entityTypeManager()->getStorage('page_content');
$pages = $pageStorage->loadByProperties(['uuid' => $uuid]);

if (empty($pages)) {
  echo "ERROR: Page not found with UUID $uuid\n";
  return;
}

$page = reset($pages);
echo "Found page: " . $page->label() . " (ID: " . $page->id() . ")\n";

// Construir HTML de las 8 secciones con clases CSS del design system pepejaraba.
$html = <<<'HTML'
<!-- Método Jaraba v2 — 8 secciones -->

<!-- SECCIÓN 1: HERO -->
<section class="pj-section pj-hero" style="background:var(--pj-dark,#233D63);color:#fff;text-align:center;padding:80px 20px;">
  <div class="pj-container">
    <span style="display:inline-block;background:rgba(255,140,66,0.15);color:#FF8C42;border:1px solid rgba(255,140,66,0.3);padding:6px 16px;border-radius:20px;font-size:13px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;margin-bottom:24px;">Método Jaraba™</span>
    <h1 style="font-size:clamp(2rem,5vw,3.25rem);font-weight:800;line-height:1.2;margin:0 auto 20px;max-width:800px;">Aprende a supervisar agentes de IA.<br>Y que eso se convierta en tu profesión.</h1>
    <p style="font-size:1.15rem;opacity:0.9;max-width:680px;margin:0 auto 32px;line-height:1.7;">Un sistema de capacitación en 90 días que te enseña a generar impacto económico real dirigiendo inteligencia artificial. Sin humo. Sin tecnicismos. Con resultados medibles.</p>
    <div style="display:inline-flex;flex-direction:column;align-items:center;gap:4px;background:rgba(255,255,255,0.08);padding:24px;border-radius:12px;margin-bottom:32px;">
      <span style="font-size:3rem;font-weight:800;color:#FF8C42;">46<small style="font-size:1.5rem;">%</small></span>
      <span style="font-weight:600;">de inserción laboral</span>
      <span style="font-size:0.8rem;opacity:0.7;">Programa Andalucía +ei, 1ª Edición</span>
    </div>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
      <a href="#solucion" style="display:inline-flex;align-items:center;gap:8px;background:#FF8C42;color:#fff;padding:12px 24px;border-radius:8px;font-weight:600;text-decoration:none;" data-track-cta="pj_metodo_hero_scroll">Ver cómo funciona ↓</a>
      <a href="https://plataformadeecosistemas.com/es/user/register?utm_source=pepejaraba&utm_medium=metodo&utm_content=hero" style="display:inline-flex;align-items:center;gap:8px;border:1.5px solid rgba(255,255,255,0.3);color:#fff;padding:12px 24px;border-radius:8px;font-weight:600;text-decoration:none;" data-track-cta="pj_metodo_hero_register" target="_blank" rel="noopener">Empezar gratis →</a>
    </div>
  </div>
</section>

<!-- SECCIÓN 2: EL PROBLEMA -->
<section class="pj-section" style="padding:80px 20px;">
  <div class="pj-container" style="max-width:1120px;margin:0 auto;">
    <h2 style="font-size:2rem;font-weight:700;text-align:center;color:var(--pj-dark,#233D63);margin-bottom:16px;">Vi un puente roto y decidí construirlo.</h2>
    <p style="font-size:1.1rem;text-align:center;max-width:720px;margin:0 auto 40px;color:#4a5568;line-height:1.7;">Durante 30 años gestioné más de 100 millones de euros en fondos europeos. Diseñé planes estratégicos para provincias enteras. Y vi cómo esos recursos no llegaban a quien más los necesitaba.</p>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px;">
      <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:24px;">
        <blockquote style="font-style:italic;color:#233D63;margin:0 0 8px;">&laquo;Me dicen que use IA, pero no sé por dónde empezar&raquo;</blockquote>
        <p style="color:#4a5568;margin:0;">Hay miles de herramientas. Ninguna te enseña a pensar.</p>
      </div>
      <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:24px;">
        <blockquote style="font-style:italic;color:#233D63;margin:0 0 8px;">&laquo;Hice un curso y sigo sin saber cómo cobrar por esto&raquo;</blockquote>
        <p style="color:#4a5568;margin:0;">La formación tradicional enseña teoría. El método enseña a facturar.</p>
      </div>
      <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:24px;">
        <blockquote style="font-style:italic;color:#233D63;margin:0 0 8px;">&laquo;Mi negocio es invisible en internet&raquo;</blockquote>
        <p style="color:#4a5568;margin:0;">No necesitas un experto. Necesitas aprender a dirigir uno (de IA).</p>
      </div>
    </div>
  </div>
</section>

<!-- SECCIÓN 3: LA SOLUCIÓN -->
<section id="solucion" class="pj-section" style="background:var(--pj-light,#F7FAFC);padding:80px 20px;">
  <div class="pj-container" style="max-width:1120px;margin:0 auto;">
    <h2 style="font-size:2rem;font-weight:700;text-align:center;color:var(--pj-dark,#233D63);margin-bottom:16px;">No aprendes a hacer las cosas. Aprendes a dirigir a quien las hace.</h2>
    <p style="text-align:center;max-width:720px;margin:0 auto 40px;color:#4a5568;line-height:1.7;">En la formación tradicional, primero te explican la teoría, luego practicas, y meses después (si llegas) lo aplicas. El Método Jaraba lo invierte:</p>
    <ol style="list-style:none;padding:0;max-width:640px;margin:0 auto 40px;">
      <li style="display:flex;gap:16px;margin-bottom:24px;align-items:flex-start;"><span style="flex-shrink:0;width:40px;height:40px;border-radius:50%;background:#FF8C42;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;">1</span><div><strong style="display:block;color:#233D63;">Haces la tarea con un agente de IA</strong><span style="color:#4a5568;">Desde el día 1. Sin esperar a que alguien te dé permiso.</span></div></li>
      <li style="display:flex;gap:16px;margin-bottom:24px;align-items:flex-start;"><span style="flex-shrink:0;width:40px;height:40px;border-radius:50%;background:#FF8C42;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;">2</span><div><strong style="display:block;color:#233D63;">Supervisas el resultado</strong><span style="color:#4a5568;">¿Está bien? ¿Falta algo? ¿Suena a ti?</span></div></li>
      <li style="display:flex;gap:16px;margin-bottom:24px;align-items:flex-start;"><span style="flex-shrink:0;width:40px;height:40px;border-radius:50%;background:#FF8C42;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;">3</span><div><strong style="display:block;color:#233D63;">Entiendes el concepto</strong><span style="color:#4a5568;">Sin que nadie te dé clase. Aprendes haciendo.</span></div></li>
    </ol>
    <div style="text-align:center;padding:32px;background:rgba(35,61,99,0.05);border-radius:12px;max-width:640px;margin:0 auto;">
      <h3 style="color:#233D63;margin-bottom:8px;">Tú eres el director de obra.</h3>
      <p style="color:#4a5568;margin:0;">La IA es tu equipo especializado. Tú decides qué se construye y cómo. Ellos ejecutan bajo tu supervisión.</p>
    </div>
  </div>
</section>

<!-- SECCIÓN 4: LAS 3 CAPAS -->
<section class="pj-section" style="padding:80px 20px;">
  <div class="pj-container" style="max-width:1120px;margin:0 auto;">
    <h2 style="font-size:2rem;font-weight:700;text-align:center;color:var(--pj-dark,#233D63);margin-bottom:40px;">Tres capas. Una competencia profesional completa.</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:24px;">
      <div style="background:#fff;border:1px solid #e5e7eb;border-top:3px solid #FF8C42;border-radius:12px;padding:24px;">
        <h3 style="color:#233D63;margin-bottom:4px;">Criterio</h3>
        <span style="display:block;font-size:0.875rem;font-weight:600;color:#FF8C42;margin-bottom:12px;">¿Para qué?</span>
        <p style="color:#4a5568;margin:0;line-height:1.6;">Saber lo que quieres. Entender tu mercado. Tomar decisiones. Esto no lo sustituye ninguna IA.</p>
      </div>
      <div style="background:#fff;border:1px solid #e5e7eb;border-top:3px solid #233D63;border-radius:12px;padding:24px;">
        <h3 style="color:#233D63;margin-bottom:4px;">Supervisión IA</h3>
        <span style="display:block;font-size:0.875rem;font-weight:600;color:#233D63;margin-bottom:12px;">¿Cómo con IA?</span>
        <p style="color:#4a5568;margin:0;line-height:1.6;">Pedir. Evaluar. Iterar. Integrar. Las 4 competencias que convierten a cualquier persona en director/a de un equipo de agentes de IA.</p>
      </div>
      <div style="background:#fff;border:1px solid #e5e7eb;border-top:3px solid #00A9A5;border-radius:12px;padding:24px;">
        <h3 style="color:#233D63;margin-bottom:4px;">Posicionamiento</h3>
        <span style="display:block;font-size:0.875rem;font-weight:600;color:#00A9A5;margin-bottom:12px;">¿Cómo cobro?</span>
        <p style="color:#4a5568;margin:0;line-height:1.6;">Propuesta de valor. Presencia digital. Embudo de captación. Porque de nada sirve saber si no facturas.</p>
      </div>
    </div>
  </div>
</section>

<!-- SECCIÓN 5: LAS 4 COMPETENCIAS -->
<section class="pj-section" style="background:var(--pj-light,#F7FAFC);padding:80px 20px;">
  <div class="pj-container" style="max-width:1120px;margin:0 auto;">
    <h2 style="font-size:2rem;font-weight:700;text-align:center;color:var(--pj-dark,#233D63);margin-bottom:40px;">4 competencias que se entrenan, se miden y se certifican.</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;">
      <div style="display:flex;gap:16px;padding:20px;background:#fff;border:1px solid #e5e7eb;border-radius:10px;">
        <div><h3 style="color:#233D63;margin:0 0 4px;font-size:1.05rem;">Pedir</h3><p style="color:#1a1d29;margin:0 0 8px;font-size:0.94rem;">Formular instrucciones claras al agente IA.</p><p style="color:#6b7280;margin:0;font-size:0.875rem;font-style:italic;">«Calcula el punto de equilibrio de una cafetería con estos datos...»</p></div>
      </div>
      <div style="display:flex;gap:16px;padding:20px;background:#fff;border:1px solid #e5e7eb;border-radius:10px;">
        <div><h3 style="color:#233D63;margin:0 0 4px;font-size:1.05rem;">Evaluar</h3><p style="color:#1a1d29;margin:0 0 8px;font-size:0.94rem;">Determinar si el resultado es correcto y útil.</p><p style="color:#6b7280;margin:0;font-size:0.875rem;font-style:italic;">«La IA dice que la tarifa plana es 60 €. ¿Sigue siendo así en 2026?»</p></div>
      </div>
      <div style="display:flex;gap:16px;padding:20px;background:#fff;border:1px solid #e5e7eb;border-radius:10px;">
        <div><h3 style="color:#233D63;margin:0 0 4px;font-size:1.05rem;">Iterar</h3><p style="color:#1a1d29;margin:0 0 8px;font-size:0.94rem;">Ajustar las instrucciones para mejorar el output.</p><p style="color:#6b7280;margin:0;font-size:0.875rem;font-style:italic;">«Suena demasiado formal. Reescríbelo como si hablaras con un vecino.»</p></div>
      </div>
      <div style="display:flex;gap:16px;padding:20px;background:#fff;border:1px solid #e5e7eb;border-radius:10px;">
        <div><h3 style="color:#233D63;margin:0 0 4px;font-size:1.05rem;">Integrar</h3><p style="color:#1a1d29;margin:0 0 8px;font-size:0.94rem;">Combinar outputs de varios agentes en un resultado final.</p><p style="color:#6b7280;margin:0;font-size:0.875rem;font-style:italic;">«Une el plan financiero, el Lean Canvas y el pitch en un solo documento.»</p></div>
      </div>
    </div>
  </div>
</section>

<!-- SECCIÓN 6: CID 90 DÍAS -->
<section class="pj-section" style="padding:80px 20px;">
  <div class="pj-container" style="max-width:640px;margin:0 auto;">
    <h2 style="font-size:2rem;font-weight:700;text-align:center;color:var(--pj-dark,#233D63);margin-bottom:40px;">3 fases. 90 días. Resultados que puedes medir.</h2>
    <div style="position:relative;padding-left:48px;">
      <div style="position:absolute;left:20px;top:24px;bottom:24px;width:2px;background:#e5e7eb;"></div>
      <div style="margin-bottom:32px;position:relative;">
        <div style="position:absolute;left:-28px;width:42px;height:42px;border-radius:50%;background:#FF8C42;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;z-index:1;">1</div>
        <span style="display:block;font-size:0.8rem;font-weight:600;color:#FF8C42;text-transform:uppercase;letter-spacing:0.05em;">Días 1-30</span>
        <h3 style="color:#233D63;margin:4px 0 8px;">Criterio y primeras tareas con IA</h3>
        <p style="color:#4a5568;margin:0;"><strong style="color:#233D63;">Entregable:</strong> Diagnóstico + hipótesis + primeras tareas productivas.</p>
      </div>
      <div style="margin-bottom:32px;position:relative;">
        <div style="position:absolute;left:-28px;width:42px;height:42px;border-radius:50%;background:#233D63;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;z-index:1;">2</div>
        <span style="display:block;font-size:0.8rem;font-weight:600;color:#233D63;text-transform:uppercase;letter-spacing:0.05em;">Días 31-60</span>
        <h3 style="color:#233D63;margin:4px 0 8px;">Supervisión y construcción</h3>
        <p style="color:#4a5568;margin:0;"><strong style="color:#233D63;">Entregable:</strong> Portfolio con 5+ outputs profesionales reales.</p>
      </div>
      <div style="position:relative;">
        <div style="position:absolute;left:-28px;width:42px;height:42px;border-radius:50%;background:#00A9A5;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;z-index:1;">3</div>
        <span style="display:block;font-size:0.8rem;font-weight:600;color:#00A9A5;text-transform:uppercase;letter-spacing:0.05em;">Días 61-90</span>
        <h3 style="color:#233D63;margin:4px 0 8px;">Posicionamiento e impacto</h3>
        <p style="color:#4a5568;margin:0;"><strong style="color:#233D63;">Entregable:</strong> Presencia digital + proyecto piloto + primer ingreso.</p>
      </div>
    </div>
  </div>
</section>

<!-- SECCIÓN 7: 3 CAMINOS -->
<section class="pj-section" style="padding:80px 20px;">
  <div class="pj-container" style="max-width:1120px;margin:0 auto;">
    <h2 style="font-size:2rem;font-weight:700;text-align:center;color:var(--pj-dark,#233D63);margin-bottom:40px;">Un método. Tres aplicaciones. Tu elección.</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px;">
      <div style="background:#fff;border:1px solid #e5e7eb;border-top:3px solid #00A9A5;border-radius:12px;padding:24px;text-align:center;">
        <h3 style="color:#233D63;margin-bottom:4px;">Busco trabajo</h3>
        <p style="color:#4a5568;margin:8px 0 20px;line-height:1.6;">CV con IA, entrevistas simuladas, perfil digital profesional.</p>
        <a href="https://plataformadeecosistemas.com/es/empleabilidad?utm_source=pepejaraba&utm_medium=metodo&utm_content=empleabilidad" style="display:inline-block;background:#FF8C42;color:#fff;padding:8px 20px;border-radius:8px;font-weight:600;text-decoration:none;font-size:0.9rem;" target="_blank" rel="noopener" data-track-cta="pj_metodo_camino_empleabilidad">Impulsar mi carrera →</a>
      </div>
      <div style="background:#fff;border:1px solid #e5e7eb;border-top:3px solid #FF8C42;border-radius:12px;padding:24px;text-align:center;">
        <h3 style="color:#233D63;margin-bottom:4px;">Quiero emprender</h3>
        <p style="color:#4a5568;margin:8px 0 20px;line-height:1.6;">Lean Canvas con IA, packs de servicios, primeros clientes, facturación.</p>
        <a href="https://plataformadeecosistemas.com/es/emprendimiento?utm_source=pepejaraba&utm_medium=metodo&utm_content=emprendimiento" style="display:inline-block;background:#FF8C42;color:#fff;padding:8px 20px;border-radius:8px;font-weight:600;text-decoration:none;font-size:0.9rem;" target="_blank" rel="noopener" data-track-cta="pj_metodo_camino_emprendimiento">Lanzar mi negocio →</a>
      </div>
      <div style="background:#fff;border:1px solid #e5e7eb;border-top:3px solid #233D63;border-radius:12px;padding:24px;text-align:center;">
        <h3 style="color:#233D63;margin-bottom:4px;">Tengo negocio</h3>
        <p style="color:#4a5568;margin:8px 0 20px;line-height:1.6;">Web profesional, redes sociales, reseñas, embudo de captación digital.</p>
        <a href="https://plataformadeecosistemas.com/es/comercioconecta?utm_source=pepejaraba&utm_medium=metodo&utm_content=digitalizacion" style="display:inline-block;background:#FF8C42;color:#fff;padding:8px 20px;border-radius:8px;font-weight:600;text-decoration:none;font-size:0.9rem;" target="_blank" rel="noopener" data-track-cta="pj_metodo_camino_digitalizacion">Digitalizar mi negocio →</a>
      </div>
    </div>
  </div>
</section>

<!-- SECCIÓN 8: EVIDENCIA + CTA FINAL -->
<section class="pj-section" style="background:var(--pj-dark,#233D63);color:#fff;text-align:center;padding:80px 20px;">
  <div class="pj-container" style="max-width:600px;margin:0 auto;">
    <div style="margin-bottom:24px;">
      <span style="font-size:clamp(3rem,8vw,5rem);font-weight:800;color:#FF8C42;">46<small style="font-size:0.5em;">%</small></span>
      <span style="display:block;font-size:1.25rem;font-weight:600;">inserción laboral</span>
      <span style="display:block;font-size:0.875rem;opacity:0.7;">Programa Andalucía +ei, 1ª Edición. Colectivos vulnerables.</span>
    </div>
    <p style="font-size:1.25rem;font-style:italic;opacity:0.9;margin:24px auto 40px;line-height:1.6;">Si funciona con el colectivo más difícil, funciona contigo.</p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
      <a href="https://plataformadeecosistemas.com/es/user/register?utm_source=pepejaraba&utm_medium=metodo&utm_content=cta_final" style="display:inline-flex;align-items:center;gap:8px;background:#FF8C42;color:#fff;padding:16px 32px;border-radius:8px;font-weight:600;font-size:1rem;text-decoration:none;" target="_blank" rel="noopener" data-track-cta="pj_metodo_evidencia_register">Empieza gratis →</a>
      <a href="https://plataformadeecosistemas.com/es/planes?utm_source=pepejaraba&utm_medium=metodo&utm_content=planes" style="display:inline-flex;align-items:center;gap:8px;border:1.5px solid rgba(255,255,255,0.3);color:#fff;padding:16px 32px;border-radius:8px;font-weight:600;text-decoration:none;" target="_blank" rel="noopener" data-track-cta="pj_metodo_evidencia_planes">Ver planes y precios</a>
    </div>
  </div>
</section>
HTML;

// Construir canvas_data JSON.
$canvasData = json_encode([
  'html' => $html,
  'css' => '',
  'components' => [],
  'styles' => [],
  'updated_at' => date('c'),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// Actualizar entity.
$page->set('canvas_data', $canvasData);
$page->set('rendered_html', $html);
$page->set('meta_title', 'Método Jaraba: Supervisión de Agentes IA en 90 Días | Pepe Jaraba');
$page->set('meta_description', 'Sistema de capacitación profesional para generar impacto económico supervisando agentes IA. 46% de inserción laboral. 3 capas, 4 competencias, 90 días.');
$page->save();

echo "✓ Page updated successfully.\n";
echo "  Title: " . $page->label() . "\n";
echo "  Canvas data: " . strlen($canvasData) . " bytes\n";
echo "  Meta title: " . $page->get('meta_title')->value . "\n";
