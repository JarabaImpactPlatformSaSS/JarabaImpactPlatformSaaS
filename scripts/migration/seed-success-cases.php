<?php

/**
 * @file
 * Seed script: Migrates hardcoded case study data to SuccessCase entities.
 *
 * SUCCESS-CASES-001: All case study data must come from SuccessCase entity.
 * Run: lando drush php:script scripts/migration/seed-success-cases.php
 *
 * This script creates 14 SuccessCase entities:
 * - 9 pre-launch placeholders (one per commercial vertical, SaaS showcase)
 * - 5 real cases from Andalucía +ei 1ª Edición:
 *   Luis Miguel Criado (empleabilidad), Marcela Calabia (emprendimiento),
 *   Ángel Martínez (emprendimiento), Adrián Capatina Tudor (emprendimiento),
 *   Cristina Martín Pereira (emprendimiento), Maia Tolomeo (empleabilidad)
 * - PED S.L. dogfooding case (andalucia_ei)
 * LEGACY-CONTROLLER-CLEANUP-001: All hardcoded CaseStudyControllers removed.
 */

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

// Bootstrap Drupal if not already bootstrapped.
if (!class_exists('Drupal') || !\Drupal::hasContainer()) {
  $autoloader = require_once 'autoload.php';
  $kernel = new DrupalKernel('prod', $autoloader);
  $request = Request::createFromGlobals();
  $kernel->boot();
  $kernel->preHandle($request);
}

$storage = \Drupal::entityTypeManager()->getStorage('success_case');

// Count existing entities for info.
$existing = $storage->getQuery()
  ->accessCheck(FALSE)
  ->count()
  ->execute();
echo "Found $existing existing success cases. Checking for new/missing entries...\n";

$theme_path = '/' . \Drupal::service('extension.list.theme')
  ->getPath('ecosistema_jaraba_theme');

$cases = [
  [
    'name' => 'Cooperativa Sierra de Cazorla',
    'slug' => 'cooperativa-sierra-cazorla',
    'vertical' => 'agroconecta',
    'headline' => 'De vender aceite a granel a 2€/litro a facturar 30.000€/mes vendiendo directo a 17€/litro',
    'subtitle' => 'Cómo una cooperativa centenaria de Jaén digitalizó su cadena de valor en 14 días',
    'protagonist_name' => 'Antonio Morales',
    'protagonist_role' => 'Presidente',
    'protagonist_company' => 'Cooperativa Sierra de Cazorla, Jaén',
    'sector' => 'Agroalimentario / Aceite ecológico',
    'location' => 'Cazorla, Jaén',
    'cta_urgency_text' => 'Solo 5 plazas esta semana para cooperativas',
    'challenge_before' => 'Llevamos 60 años vendiendo nuestro aceite a granel a Italia a 2 euros el litro para que intermediarios lo vendan a 15. Sin trazabilidad para el consumidor final, sin control sobre la marca, sin margen.',
    'solution_during' => 'AgroConecta nos dio un marketplace propio con QR de trazabilidad inmutable: del olivo a la botella. En 14 días teníamos productos publicados, pedidos de Barcelona y un copiloto IA que nos informa sobre ayudas PAC.',
    'result_after' => 'Ahora vendemos directamente a 17€/litro. El cliente puede ver desde su móvil que este aceite viene de nuestros olivos. Margen multiplicado por 4.',
    'quote_long' => 'Llevamos 60 años vendiendo nuestro aceite a granel a 2 euros el litro para que otros lo vendan a 15. Ahora lo vendemos nosotros directamente a 17 euros el litro. Y el cliente puede ver desde su móvil que este aceite viene de nuestros olivos.',
    'quote_short' => 'Ahora vendemos directo a 17€/litro. El cliente ve desde su móvil que el aceite viene de nuestros olivos.',
    'rating' => 5,
    'metrics_json' => json_encode([
      ['label' => 'Registro partidas', 'before' => '45 min', 'after' => '5 min', 'change' => '-89%'],
      ['label' => 'Trazabilidad cliente', 'before' => '1 día', 'after' => '0 min (QR)', 'change' => '-100%'],
      ['label' => 'Margen por botella', 'before' => '2,10 €', 'after' => '8,50 €', 'change' => '+305%'],
      ['label' => 'Ventas directas/mes', 'before' => '0 €', 'after' => '18.750 €', 'change' => 'Nuevo'],
      ['label' => 'Clientes directos', 'before' => '0', 'after' => '47', 'change' => '+47'],
    ]),
    'pain_points_json' => json_encode([
      ['icon_category' => 'commerce', 'icon_name' => 'cart', 'title' => 'Sin venta directa', 'description' => 'Solo podían vender a granel a intermediarios', 'metric_before' => '0 €/mes directo', 'metric_after' => '18.750 €/mes', 'metric_change' => '+18.750€'],
      ['icon_category' => 'analytics', 'icon_name' => 'chart-line', 'title' => 'Margen mínimo', 'description' => '2€/litro a granel sin marca propia', 'metric_before' => '2,10 €/botella', 'metric_after' => '8,50 €/botella', 'metric_change' => '+305%'],
      ['icon_category' => 'compliance', 'icon_name' => 'clipboard', 'title' => 'Sin trazabilidad', 'description' => 'El consumidor no podía verificar el origen', 'metric_before' => '1 día manual', 'metric_after' => 'QR instantáneo', 'metric_change' => '-100%'],
      ['icon_category' => 'status', 'icon_name' => 'clock', 'title' => 'Registro lento', 'description' => 'Partidas registradas en papel', 'metric_before' => '45 min', 'metric_after' => '5 min', 'metric_change' => '-89%'],
    ]),
    'timeline_json' => json_encode([
      ['day' => 1, 'title' => 'Perfil de la cooperativa', 'text' => '35 socios, 1.200 hectáreas, certificación ecológica'],
      ['day' => 3, 'title' => 'Primer producto', 'text' => 'AOVE Picual Ecológico en el marketplace público'],
      ['day' => 5, 'title' => 'El QR que lo cambió todo', 'text' => 'Trazabilidad inmutable: del olivo a la botella'],
      ['day' => 8, 'title' => 'Primer pedido Barcelona', 'text' => '500 botellas con envío MRW automático'],
      ['day' => 10, 'title' => 'Copiloto y ayudas PAC', 'text' => 'IA informa sobre ayudas agroambientales vigentes'],
      ['day' => 14, 'title' => 'Decisión tomada', 'text' => 'Contratan Professional. Margen × 4.'],
    ]),
    'discovery_features_json' => json_encode([
      ['icon_category' => 'commerce', 'icon_name' => 'catalog', 'title' => 'Marketplace propio', 'description' => 'Tienda online con marca propia'],
      ['icon_category' => 'commerce', 'icon_name' => 'barcode', 'title' => 'QR trazabilidad', 'description' => 'Del olivo a la mesa, verificable'],
      ['icon_category' => 'ai', 'icon_name' => 'brain', 'title' => 'Copiloto IA agrario', 'description' => 'Alertas PAC, clima, mercado'],
      ['icon_category' => 'commerce', 'icon_name' => 'delivery-truck', 'title' => 'Logística integrada', 'description' => 'MRW, SEUR automáticos'],
      ['icon_category' => 'analytics', 'icon_name' => 'dashboard', 'title' => 'Dashboard productor', 'description' => 'Ventas, stock, pedidos en tiempo real'],
      ['icon_category' => 'compliance', 'icon_name' => 'certificate', 'title' => 'Certificación ECO', 'description' => 'Sello ecológico verificable'],
      ['icon_category' => 'finance', 'icon_name' => 'wallet-cards', 'title' => 'Pagos Stripe', 'description' => 'Cobros seguros, automáticos'],
      ['icon_category' => 'analytics', 'icon_name' => 'chart-bar', 'title' => 'Analítica de ventas', 'description' => 'Métricas por producto y canal'],
      ['icon_category' => 'business', 'icon_name' => 'briefcase', 'title' => 'Gestión cooperativa', 'description' => 'Multi-socio, lotes, partidas'],
      ['icon_category' => 'social', 'icon_name' => 'users-group', 'title' => 'CRM clientes', 'description' => 'Historial, segmentación, retention'],
      ['icon_category' => 'content', 'icon_name' => 'edit', 'title' => 'Page Builder', 'description' => 'Página propia sin programar'],
      ['icon_category' => 'general', 'icon_name' => 'globe', 'title' => 'SEO automático', 'description' => 'Schema.org, meta tags, sitemap'],
    ]),
    'faq_json' => json_encode([
      ['question' => '¿Cuánto tardó la cooperativa en estar operativa?', 'answer' => 'Antonio y su equipo tuvieron su primer producto publicado en el marketplace el día 3. El primer pedido llegó el día 8 desde Barcelona.'],
      ['question' => '¿Necesito conocimientos técnicos?', 'answer' => 'No. La plataforma está diseñada para productores agrarios. Si sabes usar un móvil, sabes usar AgroConecta.'],
      ['question' => '¿Qué pasa con la trazabilidad?', 'answer' => 'Cada producto tiene un QR único que el consumidor puede escanear para ver el origen completo: parcela, fecha de cosecha, proceso de elaboración.'],
      ['question' => '¿Puedo vender fuera de España?', 'answer' => 'Sí. El marketplace soporta envíos internacionales con MRW y SEUR. Los pagos se procesan en cualquier moneda con Stripe.'],
      ['question' => '¿Qué incluye el plan Free?', 'answer' => '1 producto publicado, QR básico, dashboard de ventas y 5 mensajes diarios al copiloto IA.'],
      ['question' => '¿Y si no me convence?', 'answer' => '14 días de prueba gratuita sin tarjeta de crédito. Si no te convence, no pagas nada.'],
      ['question' => '¿Cómo funciona el copiloto IA?', 'answer' => 'Es un asistente especializado en agroalimentación que te informa sobre ayudas PAC, precios de mercado, normativa y tendencias del sector.'],
      ['question' => '¿Puedo gestionar varios socios?', 'answer' => 'Sí. AgroConecta tiene gestión multi-socio con roles diferenciados: presidente, secretario, socios productores.'],
      ['question' => '¿Qué métodos de pago aceptáis?', 'answer' => 'Tarjeta de crédito/débito vía Stripe. Sin compromiso de permanencia, puedes cancelar en cualquier momento.'],
      ['question' => '¿Es compatible con certificaciones ecológicas?', 'answer' => 'Sí. El sistema integra sellos de certificación ecológica, DOP y otros distintivos de calidad verificables por el consumidor.'],
    ]),
    'schema_date_published' => '2026-03-20',
    'meta_description' => 'Caso de éxito: Cómo la Cooperativa Sierra de Cazorla de Jaén pasó de vender aceite a granel a 2€/l a facturar 30.000€/mes vendiendo directo con AgroConecta.',
    'status' => TRUE,
    'featured' => TRUE,
    'weight' => 0,
  ],
  // The remaining 8 verticals follow the same pattern.
  // For brevity, creating minimal stubs that can be enriched from admin UI.
  [
    'name' => 'Elena Martínez',
    'slug' => 'despacho-martinez',
    'vertical' => 'jarabalex',
    'headline' => 'De perder 3 horas buscando jurisprudencia a encontrar la sentencia exacta en 30 segundos',
    'subtitle' => 'Cómo un despacho de abogados de Madrid triplicó su capacidad con IA jurídica',
    'protagonist_name' => 'Elena Martínez',
    'protagonist_role' => 'Socia directora',
    'protagonist_company' => 'Martínez & Asociados, Madrid',
    'sector' => 'Legal / Derecho civil y mercantil',
    'location' => 'Madrid',
    'cta_urgency_text' => 'Plazas limitadas esta semana',
    'quote_long' => 'En 14 días hemos ganado lo que tardábamos 3 meses en producir. La IA jurídica no sustituye al abogado — le libera para pensar.',
    'quote_short' => 'La IA jurídica no sustituye al abogado — le libera para pensar.',
    'rating' => 5,
    'schema_date_published' => '2026-03-20',
    'meta_description' => 'Caso de éxito: Despacho Martínez & Asociados triplicó su capacidad con JarabaLex, la plataforma de inteligencia legal con IA.',
    'status' => TRUE,
    'featured' => TRUE,
    'weight' => 1,
  ],
  // REAL CASE: Luis Miguel Criado — Andalucía +ei 1ª Edición (PIIL CV 2023-2024).
  // Source: "Caso de Éxito Luis Miguel Criado_ El Poder de Empezar.docx"
  // Photos: 2x high-res JPG (Jun 2024) + 3x WhatsApp (Oct 2025).
  [
    'name' => 'Luis Miguel Criado',
    'slug' => 'luis-miguel-criado',
    'vertical' => 'empleabilidad',
    'headline' => 'De la parálisis administrativa a terapeuta autónomo: el poder de dar el primer paso',
    'subtitle' => 'Cómo Luis Miguel superó el muro invisible de la burocracia y consiguió darse de alta como autónomo con ayuda pública de la Junta de Andalucía',
    'protagonist_name' => 'Luis Miguel Criado',
    'protagonist_role' => 'Quiromasajista — Autónomo',
    'protagonist_company' => 'Andalucía',
    'sector' => 'Salud y bienestar / Terapias manuales',
    'location' => 'Andalucía',
    'cta_urgency_text' => 'Plazas limitadas para el programa Andalucía +ei',
    'challenge_before' => 'Luis Miguel es quiromasajista vocacional con un perfil introvertido. Tenía la habilidad y la demanda de mercado, pero se encontraba paralizado ante el "muro invisible" de la burocracia: darse de alta como autónomo, gestión de cuotas, solicitud de ayudas, lenguaje fiscal. El reto no era falta de talento sino la ansiedad que generaba el proceso administrativo.',
    'solution_during' => 'El programa Andalucía +ei, con la metodología "Sin Humo" de PED, le proporcionó herramientas y guías prácticas paso a paso que desmitificaron el proceso. Le dio claridad para empezar a cotizar como autónomo, le enseñó a conseguir la ayuda de la Junta de Andalucía por su cuenta y le dio autonomía para gestionar sus propias cuotas sin depender de una gestoría.',
    'result_after' => 'Luis Miguel pasó de la parálisis a tener un negocio real en funcionamiento como autónomo. Obtuvo la ayuda pública de la Junta de Andalucía, gestiona sus cuotas de forma independiente y genera ingresos propios. Un "éxito fundacional": no mide facturación sino barreras derribadas y coraje de empezar.',
    'quote_long' => 'Aprendí a gestionar por mí mismo las cuotas de autónomo sin depender de una gestoría. Eso es un ahorro y un control fundamental cuando empiezas. El programa me dio las herramientas para dar los pasos más difíciles: empezar a cotizar y conseguir mis primeras ayudas.',
    'quote_short' => 'El programa me dio las herramientas para dar los pasos más difíciles: empezar a cotizar y conseguir mis primeras ayudas.',
    'rating' => 5,
    'metrics_json' => json_encode([
      ['label' => 'Alta como autónomo', 'before' => 'Paralizado', 'after' => 'Completada', 'change' => 'Operativo'],
      ['label' => 'Ayuda pública Junta', 'before' => 'No solicitada', 'after' => 'Obtenida', 'change' => 'Aprobada'],
      ['label' => 'Dependencia gestoría', 'before' => 'Total', 'after' => 'Eliminada', 'change' => 'Autónomo'],
      ['label' => 'Gestión cuotas', 'before' => 'Desconocida', 'after' => 'Autogestionada', 'change' => 'Ahorro mensual'],
    ]),
    'program_name' => 'Andalucía +ei',
    'program_funder' => 'Junta de Andalucía — Consejería de Empleo',
    'program_year' => '2023-2024',
    'schema_date_published' => '2024-06-04',
    'meta_description' => 'Caso de éxito real: Luis Miguel Criado, quiromasajista, superó la parálisis administrativa y se estableció como autónomo con ayuda pública gracias al programa Andalucía +ei.',
    'status' => TRUE,
    'featured' => TRUE,
    'weight' => 2,
  ],
  // PRE-LAUNCH PLACEHOLDER: Carlos Etxebarría — fictional emprendimiento case.
  // Represents what the SaaS vertical will offer once launched.
  [
    'name' => 'Carlos Etxebarría',
    'slug' => 'carlos-etxebarria-bilbao',
    'vertical' => 'emprendimiento',
    'headline' => 'De una idea en una servilleta a 47.000€ de facturación en 90 días',
    'subtitle' => 'Cómo Carlos validó su startup en Bilbao con herramientas de emprendimiento con IA',
    'protagonist_name' => 'Carlos Etxebarría',
    'protagonist_role' => 'Fundador',
    'protagonist_company' => 'Bilbao',
    'sector' => 'Tecnología / SaaS',
    'location' => 'Bilbao',
    'quote_short' => 'El Canvas Generator y el simulador financiero me ahorraron 6 meses de ensayo y error.',
    'rating' => 5,
    'schema_date_published' => '2026-03-20',
    'status' => TRUE,
    'featured' => TRUE,
    'weight' => 3,
  ],
  [
    'name' => 'Boutique La Mariposa',
    'slug' => 'boutique-la-mariposa',
    'vertical' => 'comercioconecta',
    'headline' => 'De una tienda local a vender online en 14 días con catálogo QR y envíos automáticos',
    'subtitle' => 'Cómo una boutique de Sevilla triplicó sus ventas con ComercioConecta',
    'protagonist_name' => 'Carmen Ruiz',
    'protagonist_role' => 'Propietaria',
    'protagonist_company' => 'Boutique La Mariposa, Sevilla',
    'sector' => 'Retail / Moda',
    'location' => 'Sevilla',
    'quote_short' => 'Ahora vendo online sin intermediarios. Mis clientas escanean el QR y compran directo.',
    'rating' => 5,
    'schema_date_published' => '2026-03-20',
    'status' => TRUE,
    'featured' => TRUE,
    'weight' => 4,
  ],
  // PRE-LAUNCH PLACEHOLDER: Carmen Navarro — fictional serviciosconecta case.
  // Represents what the SaaS vertical will offer once launched.
  [
    'name' => 'Carmen Navarro',
    'slug' => 'carmen-navarro-madrid',
    'vertical' => 'serviciosconecta',
    'headline' => 'De depender de portales a llenar la agenda con reservas directas en 14 días',
    'subtitle' => 'Cómo una fisioterapeuta de Madrid automatizó su negocio con ServiciosConecta',
    'protagonist_name' => 'Carmen Navarro',
    'protagonist_role' => 'Fisioterapeuta',
    'protagonist_company' => 'Madrid',
    'sector' => 'Salud / Fisioterapia',
    'location' => 'Madrid',
    'quote_short' => 'Mi agenda se llena sola. Los pacientes reservan, pagan y reciben recordatorio sin que yo haga nada.',
    'rating' => 5,
    'schema_date_published' => '2026-03-20',
    'status' => TRUE,
    'featured' => TRUE,
    'weight' => 5,
  ],
  // REAL CASE: PED S.L. — Dogfooding. PIIL CV 2023-2024 real data.
  // Source: internal data, 50 participants, 46% insertion rate, 8 provinces.
  [
    'name' => 'Plataforma de Ecosistemas Digitales S.L.',
    'slug' => 'plataforma-ecosistemas-digitales',
    'vertical' => 'andalucia_ei',
    'headline' => 'De gestionar 50 itinerarios de inserción laboral en Excel a un ecosistema digital con IA',
    'subtitle' => 'Cómo PED S.L. construyó Andalucía +ei para resolver el problema que vivió en primera persona con el PIIL CV 2023',
    'protagonist_name' => 'José Jaraba',
    'protagonist_role' => 'Director del programa Andalucía +ei',
    'protagonist_company' => 'Plataforma de Ecosistemas Digitales S.L.',
    'sector' => 'Inserción laboral / Emprendimiento inclusivo',
    'location' => 'Málaga',
    'cta_urgency_text' => 'La 2ª edición del programa ya está en marcha',
    'challenge_before' => 'En 2023, PED S.L. gestionó su primer Programa Integrado de Inserción Laboral (PIIL) financiado por la Junta de Andalucía. 50 participantes en 8 provincias andaluzas, 8 orientadores coordinados por hojas de cálculo compartidas en Google Drive. Los informes para el SAE se elaboraban a mano durante semanas. Cada orientador tenía su propio Excel con formatos diferentes.',
    'solution_during' => 'Para la 2ª edición (2025), PED S.L. construyó Andalucía +ei sobre su propio SaaS: fichas de participantes con 12 campos estructurados, itinerarios IPAE personalizados, sesiones de orientación registradas, acciones formativas con asistencia dual (presencial/online), seguimiento de inserción laboral y generación automática de informes FSE+. 21 entidades de datos, 4 roles de programa, copiloto IA con 6 fases adaptativas.',
    'result_after' => 'La 1ª edición logró una tasa de inserción del 46% (23 de 50 participantes insertados laboralmente). La coordinación que antes requería 8 hojas Excel por provincia ahora se gestiona desde un único panel. El seguimiento individualizado con copiloto IA permite detectar oportunidades de empleo que el equipo humano no tenía tiempo de analizar.',
    'quote_short' => 'Construimos Andalucía +ei porque nosotros mismos sufrimos el problema. La primera edición la gestionamos con Excel.',
    'rating' => 5,
    'metrics_json' => json_encode([
      ['label' => 'Participantes programa', 'before' => '0', 'after' => '50', 'change' => '50 personas'],
      ['label' => 'Tasa de inserción', 'before' => 'N/A', 'after' => '46%', 'change' => '23 insertados'],
      ['label' => 'Provincias cubiertas', 'before' => '0', 'after' => '8', 'change' => 'Toda Andalucía'],
      ['label' => 'Orientadores coordinados', 'before' => 'Excel', 'after' => 'Panel único', 'change' => '-8 hojas'],
      ['label' => 'Tiempo informes SAE', 'before' => 'Semanas', 'after' => 'Automático', 'change' => '-95%'],
    ]),
    'program_name' => 'Andalucía +ei — PIIL CV',
    'program_funder' => 'Junta de Andalucía — Consejería de Empleo, Empresa y Trabajo Autónomo',
    'program_year' => '2023-2024',
    'schema_date_published' => '2024-01-15',
    'meta_description' => 'Caso de éxito real: PED S.L. construyó Andalucía +ei tras gestionar 50 itinerarios de inserción laboral con Excel. 46% tasa de inserción en la 1ª edición del PIIL.',
    'status' => TRUE,
    'featured' => TRUE,
    'weight' => 6,
  ],
  [
    'name' => 'María López',
    'slug' => 'maria-lopez-madrid',
    'vertical' => 'formacion',
    'headline' => 'De dar clases presenciales a 20 alumnos a tener una academia online con 350 matriculados',
    'subtitle' => 'Cómo María creó su academia de marketing digital con el LMS de Formación',
    'protagonist_name' => 'María López',
    'protagonist_role' => 'Instructora de marketing digital',
    'protagonist_company' => 'Madrid',
    'sector' => 'Educación / Marketing digital',
    'location' => 'Madrid',
    'quote_short' => 'En 14 días tenía mi primer curso online publicado. Ahora facturo más que en 3 años de clases presenciales.',
    'rating' => 5,
    'schema_date_published' => '2026-03-20',
    'status' => TRUE,
    'featured' => TRUE,
    'weight' => 7,
  ],
  [
    'name' => 'Bodega Montilla',
    'slug' => 'bodega-montilla-cordoba',
    'vertical' => 'content_hub',
    'headline' => 'De no tener presencia online a posicionar 12 artículos en primera página de Google',
    'subtitle' => 'Cómo una bodega de Córdoba construyó su autoridad digital con Content Hub',
    'protagonist_name' => 'Bodega Montilla',
    'protagonist_role' => 'Directora de comunicación',
    'protagonist_company' => 'Bodega Montilla, Córdoba',
    'sector' => 'Enoturismo / Vinos',
    'location' => 'Montilla, Córdoba',
    'quote_short' => 'El Content Hub con IA nos genera borradores que editamos en minutos. 12 artículos en primera página.',
    'rating' => 5,
    'schema_date_published' => '2026-03-20',
    'status' => TRUE,
    'featured' => TRUE,
    'weight' => 8,
  ],
  // =========================================================================
  // ADDITIONAL REAL CASES from Andalucía +ei 1ª Edición.
  // These are real participants with verifiable businesses/outcomes.
  // They coexist alongside pre-launch placeholders for SaaS verticals.
  // =========================================================================
  // REAL CASE: Marcela Calabia — Andalucía +ei 1ª Edición.
  // Source: "00. Caso Marcela Calabia.docx", transcripción reunión 01/10/2025.
  // Photos: 1x high-res JPG (Jun 2024), book covers, LinkedIn banner, logo.
  // Video: WhatsApp video 18/09/2025 (47 MB).
  [
    'name' => 'Marcela Calabia',
    'slug' => 'marcela-calabia',
    'vertical' => 'emprendimiento',
    'headline' => 'De no saber por dónde empezar a coach premium con libros publicados en 4 idiomas',
    'subtitle' => 'Cómo Marcela estructuró su marca personal como coach de comunicación y resiliencia con el programa Andalucía +ei',
    'protagonist_name' => 'Marcela Calabia',
    'protagonist_role' => 'Coach de Comunicación Estratégica y Resiliencia',
    'protagonist_company' => 'Andalucía',
    'sector' => 'Coaching ejecutivo / Comunicación / Servicios lingüísticos premium',
    'location' => 'Andalucía',
    'cta_urgency_text' => 'Plazas limitadas para emprendedores en Andalucía +ei',
    'challenge_before' => 'Marcela venía de "otro mundo laboral" con más de 25 años de experiencia formando ejecutivos y más de 28.000 horas de formación. Necesitaba reinventarse como autónoma pero no sabía por dónde empezar. Tenía un perfil abandonado en Preply sin reservas, un LinkedIn sin optimizar, y webs muertas. Se definía como de la "era analógica".',
    'solution_during' => 'El programa le proporcionó un acompañamiento integral: definición de nicho premium (español para ejecutivos extranjeros), reconstrucción completa de su perfil en Preply, optimización de LinkedIn, modelo de negocio con dos productos (programa de 1 mes y programa insignia de 3 meses), estructura de precios premium (mínimo 75€/hora) y estrategia de contenido digital reutilizando sus vídeos de YouTube.',
    'result_after' => 'Marcela pasó de no tener reservas a tener un modelo de negocio completo con proyección de 48.000-80.000€/año. Publicó su libro "Sin culpa. Con coraje" en 4 idiomas (español, italiano, francés, bilingüe) usando IA para las traducciones. Lanzó un segundo libro "Comunicar con Confianza". Domina redes sociales profesionales, crea sus propias landing pages y tiene 2 emprendimientos en marcha.',
    'quote_long' => 'Ninguno de los cursos de pago que he hecho me ha dado lo que me dio este curso gratuito. Las herramientas que da, yo no las encontré en ningún otro lado. No sabía por dónde empezar. Este curso me dio no solo las herramientas para hacer mi propia web, sino la parte humana que no encontré ni en cursos de pago.',
    'quote_short' => 'Ninguno de los cursos de pago que he hecho me ha dado lo que me dio este programa gratuito.',
    'rating' => 5,
    'metrics_json' => json_encode([
      ['label' => 'Experiencia formativa', 'before' => 'Dispersa', 'after' => 'Focalizada premium', 'change' => '+28.000h acreditadas'],
      ['label' => 'Libros publicados', 'before' => '0', 'after' => '2 libros, 4 idiomas', 'change' => '+2 en Amazon'],
      ['label' => 'Presencia digital', 'before' => 'Inexistente', 'after' => 'LinkedIn + Preply + YouTube', 'change' => 'Operativa'],
      ['label' => 'Proyección anual', 'before' => '0 €', 'after' => '48.000-80.000 €', 'change' => 'Viable'],
      ['label' => 'Precio/hora', 'before' => 'Sin definir', 'after' => 'Mín. 75 €/h', 'change' => 'Premium'],
    ]),
    'program_name' => 'Andalucía +ei',
    'program_funder' => 'Junta de Andalucía — Consejería de Empleo',
    'program_year' => '2023-2024',
    'schema_date_published' => '2025-09-18',
    'meta_description' => 'Caso de éxito real: Marcela Calabia, coach de comunicación, pasó de no tener presencia digital a publicar 2 libros en 4 idiomas y estructurar un negocio premium con Andalucía +ei.',
    'status' => TRUE,
    'featured' => TRUE,
    'weight' => 10,
  ],
  // REAL CASE: Ángel Martínez / Camino Viejo — Andalucía +ei 1ª Edición.
  // Source: "Caso de Éxito Ángel Martínez_ Emprendimiento.docx", transcripción 07/10/2025.
  // Photos: 2x high-res JPG (Jun 2024) + 8x WhatsApp activities (Oct 2025).
  // Video: Reunión 351 MB (highlight) + 2 GB (completo).
  // Web: caminoviejo.es | YouTube: gk8MGO8ldLE
  [
    'name' => 'Ángel Martínez',
    'slug' => 'angel-martinez-camino-viejo',
    'vertical' => 'emprendimiento',
    'headline' => 'De ejecutivo agotado a emprendedor rural: Camino Viejo, cicloturismo premium en Sierra Morena',
    'subtitle' => 'Cómo Ángel dejó una gran empresa para fundar un negocio de gastrobiking en Cazalla de la Sierra con el apoyo del programa Andalucía +ei',
    'protagonist_name' => 'Ángel Martínez',
    'protagonist_role' => 'Cofundador de Camino Viejo',
    'protagonist_company' => 'Camino Viejo — Gastrobiking, Cazalla de la Sierra',
    'sector' => 'Cicloturismo / Gastronomía rural / Experiencias',
    'location' => 'Cazalla de la Sierra, Sevilla',
    'cta_urgency_text' => 'Descubre cómo emprender en el mundo rural con Andalucía +ei',
    'challenge_before' => 'Ángel venía de una gran empresa con un trabajo de responsabilidad y bien remunerado, pero el ritmo era incompatible con la conciliación familiar y muy estresante. Quería hacer algo que le gustase y le permitiera quedarse en su pueblo, Cazalla de la Sierra, intentando dar un servicio que no había en la zona.',
    'solution_during' => 'A través de la formación PED del programa Andalucía +ei, aprendió principios lean: emprender minimizando la inversión inicial para ser más libre de cambiar planes y minimizar riesgos. Se le desmitificó la burocracia administrativa. Aprendió a analizar constantemente y si hay que cambiar, cambiar.',
    'result_after' => 'Fundó "Camino Viejo" en Cazalla de la Sierra: venta y alquiler de bicicletas, taller mecánico, gastrobiking y rutas guiadas por el Parque Natural Sierra Morena. Obtuvo 18.000€ en subvenciones de la Junta de Andalucía. Negocio rentable tras año y medio. Asistió a la 1ª Feria Nacional de Cicloturismo en Zaragoza. Pivotando estratégicamente de retail a cicloturismo experiencial de alto valor.',
    'quote_long' => 'La formación de PED es oro puro. Si se tiene una buena idea, con trabajo se puede llevar adelante; con trabajo y aprendiendo de las buenas experiencias de otros y no cometiendo sus errores. Para mí, lo más valioso que he aprendido es que hay que emprender minimizando la inversión inicial, así somos más libres para cambiar de planes sobre la marcha.',
    'quote_short' => 'La formación de PED es oro puro. Ahora tengo conciencia de que ES POSIBLE.',
    'rating' => 5,
    'metrics_json' => json_encode([
      ['label' => 'Subvención Junta', 'before' => '0 €', 'after' => '18.000 €', 'change' => 'Aprobada'],
      ['label' => 'Tiempo operando', 'before' => '0', 'after' => '1,5 años', 'change' => 'Rentable'],
      ['label' => 'Servicios ofrecidos', 'before' => '0', 'after' => '6 líneas', 'change' => 'Diversificado'],
      ['label' => 'Ferias asistidas', 'before' => '0', 'after' => '1ª Feria Nacional Cicloturismo', 'change' => 'Zaragoza 2025'],
      ['label' => 'Posición Google local', 'before' => 'Inexistente', 'after' => '1º alquiler bicis', 'change' => 'Top 1'],
    ]),
    'program_name' => 'Andalucía +ei',
    'program_funder' => 'Junta de Andalucía — Consejería de Empleo',
    'program_year' => '2023-2024',
    'website' => 'https://www.caminoviejo.es/',
    // NOTE: YouTube gk8MGO8ldLE is Camino Viejo promo, NOT program testimonial.
    // Program interview video: 20251007-Reunión Camino Viejo-resaltar.mp4 (46 MB).
    // video_url left empty until testimonial clip is uploaded to /sites/default/files/.
    'video_url' => '',
    'schema_date_published' => '2025-10-07',
    'meta_description' => 'Caso de éxito real: Ángel Martínez fundó Camino Viejo, cicloturismo premium en Sierra Morena, con 18.000€ de subvención y negocio rentable gracias al programa Andalucía +ei.',
    'status' => TRUE,
    'featured' => TRUE,
    'weight' => 11,
  ],
  // REAL CASE: Adrián Capatina Tudor / NOVAVID — Andalucía +ei 1ª Edición.
  // Source: Transcripción reunión 10/10/2025, Nota IA, Preparación caso NOVAVID.
  // Photos: 2x high-res JPG (Apr 2024) + 1x WhatsApp (Oct 2025).
  // Video: Reunión 802 MB (completa) + transcripción.
  // Web: novavid.media | Instagram: @novavidmedia | Google Business: Novavid
  [
    'name' => 'Adrián Capatina Tudor',
    'slug' => 'adrian-capatina-tudor-novavid',
    'vertical' => 'emprendimiento',
    'headline' => 'De inmigrante sin idioma a agencia audiovisual con clientes del sector inmobiliario de lujo',
    'subtitle' => 'Cómo Adrián fundó NOVAVID Media Network en Estepona y transformó su talento autodidacta en un negocio estructurado con el programa Andalucía +ei',
    'protagonist_name' => 'Adrián Capatina Tudor',
    'protagonist_role' => 'Fundador y Director Creativo de NOVAVID',
    'protagonist_company' => 'NOVAVID Media Network, Estepona',
    'sector' => 'Producción audiovisual / Videografía / Diseño 3D / Marketing digital',
    'location' => 'Estepona, Málaga',
    'cta_urgency_text' => 'Descubre cómo emprender en el sector creativo con Andalucía +ei',
    'challenge_before' => 'Adrián llegó a España desde Rumanía con 16 años sin conocer el idioma. Se formó de manera autodidacta en informática, videografía y diseño 3D, acumulando clientes del sector inmobiliario de lujo que valoraban personalmente su trabajo. Pero le faltaba estructura empresarial: no sabía calcular beneficios reales, no tenía un modelo de precios formal y operaba por instinto sin visión estratégica de negocio.',
    'solution_during' => 'El programa Andalucía +ei le proporcionó la base empresarial que le faltaba: cálculo real de beneficios, estructura de precios por servicio, comprensión del sistema fiscal, networking profesional y mentalidad estratégica. Aprendió a diferenciar facturación de beneficio y a planificar el crecimiento con datos, no con intuición.',
    'result_after' => 'Fundó NOVAVID Media Network, una agencia de producción audiovisual y marketing digital en Estepona especializada en el sector inmobiliario de lujo. Opera con clientes corporativos recurrentes, una media de 5 proyectos mensuales con contratos formales y sistema de pago 50/50. Pivotó estratégicamente de videografía generalista a diseño 3D de alto valor. Los proyectos le entran solos por Google y boca a boca. Tan impactante fue la formación que reparte los conocimientos aprendidos a otros emprendedores de su entorno.',
    'quote_long' => 'La vida me ha cambiado, te lo aseguro. Yo todo lo que he aprendido de ti, todo lo que nos enseñaste, lo repartí a otros porque siempre te encuentras amigos o gente nueva. Cualquier persona que te das cuenta que necesita, le explico: mira, a mí me han explicado de esa manera, me lo han dicho así.',
    'quote_short' => 'La vida me ha cambiado, te lo aseguro. Todo lo que aprendí lo reparto a otros emprendedores.',
    'rating' => 5,
    'metrics_json' => json_encode([
      ['label' => 'Clientes mensuales', 'before' => 'Esporádicos', 'after' => '5 de media', 'change' => 'Recurrentes'],
      ['label' => 'Modelo de cobro', 'before' => 'Informal', 'after' => 'Contrato 50/50', 'change' => 'Profesionalizado'],
      ['label' => 'Pivote estratégico', 'before' => 'Videografía general', 'after' => 'Diseño 3D alto valor', 'change' => 'Mayor rentabilidad'],
      ['label' => 'Captación', 'before' => 'Boca a boca', 'after' => 'Google + reseñas', 'change' => 'Proyectos entran solos'],
      ['label' => 'Objetivo', 'before' => 'Sin plan', 'after' => '10.000 €/mes', 'change' => 'En 1 año'],
    ]),
    'program_name' => 'Andalucía +ei',
    'program_funder' => 'Junta de Andalucía — Consejería de Empleo',
    'program_year' => '2023-2024',
    'website' => 'https://novavid.media/',
    'video_url' => '',
    'schema_date_published' => '2025-10-10',
    'meta_description' => 'Caso de éxito real: Adrián Capatina Tudor, de inmigrante rumano a fundador de NOVAVID Media Network, agencia audiovisual de lujo en Estepona, gracias al programa Andalucía +ei.',
    'status' => TRUE,
    'featured' => TRUE,
    'weight' => 12,
  ],
  // REAL CASE: Cristina Martín Pereira / De Cris Moda — Andalucía +ei 1ª Edición.
  // Source: Caso de Éxito completo, Transcripción 13/10/2025, Análisis estratégico.
  // Photos: 1x high-res JPG (May 2024).
  // Video: Reunión 298 MB (completa) + transcripción.
  [
    'name' => 'Cristina Martín Pereira',
    'slug' => 'cristina-martin-pereira-de-cris-moda',
    'vertical' => 'emprendimiento',
    'headline' => 'De camarera a emprendedora digital: 600-800€/mes con solo 4 horas de trabajo a la semana',
    'subtitle' => 'Cómo Cristina fundó De Cris Moda y vende moda femenina en 6 países desde su casa en Huelva gracias al programa Andalucía +ei',
    'protagonist_name' => 'Cristina Martín Pereira',
    'protagonist_role' => 'Fundadora de De Cris Moda',
    'protagonist_company' => 'De Cris Moda, Huelva',
    'sector' => 'Comercio social / Moda femenina online',
    'location' => 'Huelva',
    'cta_urgency_text' => 'Descubre cómo emprender en comercio digital con Andalucía +ei',
    'challenge_before' => 'Cristina llevaba muchos años como camarera en hostelería. El trabajo había dejado de motivarla. Siempre quiso tener algo propio pero no sabía qué ni cómo: no encontraba a qué dedicarse. No sabía hacer un proyecto de empresa, en qué epígrafe darse de alta, ni por dónde empezar con la burocracia.',
    'solution_during' => 'El programa Andalucía +ei le provocó un "shock de ecosistema": descubrió que sus competidores no eran tiendas locales sino Shein y Amazon. Aprendió a calcular beneficios reales, a estructurar una propuesta de valor diferenciada (packs de conveniencia para madres ocupadas), y a usar TikTok como motor de ventas. Las dinámicas de grupo le dieron el empujón psicológico: "la chispa que te falta, ese empujoncillo del miedo".',
    'result_after' => 'Fundó "De Cris Moda", un negocio de comercio social 100% online que genera 600-800€/mes con solo 4 horas semanales de trabajo activo (directos los viernes). Se mudó de vivienda por falta de espacio para el stock. Vende internacionalmente en 6 países (Honduras, Francia, Italia, Holanda, Portugal, Bélgica) vía Vinted y Wallapop. Se dio de alta como autónoma ella sola gracias a lo aprendido. 8-9 meses operando.',
    'quote_long' => 'Estoy de mudanza porque la tienda ya no cabía en casa. Yo empecé en casa y no paro de crecer. Solamente trabajo en directo los viernes, entre 2 y 4 horas. Los reels de diario ya los tengo programados, se suben solos. El rendimiento para lo que le dedico real está bastante bien.',
    'quote_short' => 'La tienda ya no cabía en casa. Solo trabajo 4 horas a la semana y no paro de crecer.',
    'rating' => 5,
    'metrics_json' => json_encode([
      ['label' => 'Ingresos mensuales', 'before' => '0 €', 'after' => '600-800 €', 'change' => 'Con 4h/semana'],
      ['label' => 'Trabajo activo', 'before' => 'Hostelería tiempo completo', 'after' => '4 horas/semana', 'change' => 'Arbitraje de tiempo'],
      ['label' => 'Venta internacional', 'before' => '0 países', 'after' => '6 países', 'change' => 'Honduras a Bélgica'],
      ['label' => 'Espacio físico', 'before' => 'Habitación', 'after' => 'Mudanza + caseta stock', 'change' => 'Crecimiento'],
      ['label' => 'Autonomía burocrática', 'before' => 'No sabía por dónde empezar', 'after' => 'Alta autónoma sola', 'change' => 'Empoderada'],
    ]),
    'program_name' => 'Andalucía +ei',
    'program_funder' => 'Junta de Andalucía — Consejería de Empleo',
    'program_year' => '2023-2024',
    'video_url' => '',
    'schema_date_published' => '2025-10-13',
    'meta_description' => 'Caso de éxito real: Cristina Martín Pereira, de camarera a emprendedora digital con De Cris Moda. 600-800€/mes con 4h/semana vendiendo en 6 países gracias a Andalucía +ei.',
    'status' => TRUE,
    'featured' => TRUE,
    'weight' => 13,
  ],
  // REAL CASE: Maia Tolomeo — Andalucía +ei 1ª Edición.
  // Source: Video-testimonio propio (2024-03-26, 1:55 min).
  // Photos: 2x high-res JPG (Feb 2024, 3.7 MB + 3.4 MB).
  [
    'name' => 'Maia Tolomeo',
    'slug' => 'maia-tolomeo',
    'vertical' => 'empleabilidad',
    'headline' => 'De desempleada a empleada en comercio local: cuando el acompañamiento marca la diferencia',
    'subtitle' => 'Cómo Maia pasó del desempleo a encontrar su lugar profesional en el comercio de proximidad gracias al programa Andalucía +ei',
    'protagonist_name' => 'Maia Tolomeo',
    'protagonist_role' => 'Empleada de comercio local',
    'protagonist_company' => 'Andalucía',
    'sector' => 'Comercio local / Retail de proximidad',
    'location' => 'Andalucía',
    'cta_urgency_text' => 'Plazas limitadas para el programa Andalucía +ei',
    'challenge_before' => 'Maia se encontraba en situación de desempleo, sin una estrategia clara para reincorporarse al mercado laboral. La búsqueda de empleo sin orientación profesional se prolongaba sin resultados, generando frustración y pérdida de confianza en sus posibilidades.',
    'solution_during' => 'A través del programa Andalucía +ei, recibió orientación laboral personalizada que le permitió identificar sus fortalezas, prepararse para procesos de selección y conectar con oportunidades reales en su entorno. El acompañamiento incluyó definición de perfil profesional, preparación para entrevistas y conexión con el tejido empresarial local. El intercambio de ideas con los compañeros y las clases por videollamada fueron muy prácticos.',
    'result_after' => 'Maia consiguió empleo como dependienta en un comercio local de su zona. Pasó del desempleo a la actividad laboral, recuperando su autonomía económica y su confianza profesional. Aprendió conceptos imprescindibles para emprendimiento y a no limitarse con ideas preconcebidas.',
    'quote_long' => 'El curso ha sido muy bueno, muy completo, se lo recomiendo a todos. Hemos aprendido un montón de conceptos imprescindibles a la hora de montar un emprendimiento. Lo que me llevo como mayor importancia del curso es el haber conocido a Pepe, que es una persona súper inteligente, culta y con un montón de experiencia. Me enseñó a abrir la mente y no limitarme. Han surgido un montón de ideas que no me las había planteado nunca. Nos enseñó a no limitarnos, básicamente.',
    'quote_short' => 'El curso ha sido muy bueno, muy completo. Me enseñó a abrir la mente y no limitarme. Han surgido ideas que no me había planteado nunca.',
    'rating' => 5,
    'metrics_json' => json_encode([
      ['label' => 'Situación laboral', 'before' => 'Desempleada', 'after' => 'Empleada', 'change' => 'Inserción'],
      ['label' => 'Sector', 'before' => 'Sin definir', 'after' => 'Comercio local', 'change' => 'Definido'],
      ['label' => 'Autonomía económica', 'before' => 'Dependiente', 'after' => 'Independiente', 'change' => 'Recuperada'],
    ]),
    'program_name' => 'Andalucía +ei',
    'program_funder' => 'Junta de Andalucía — Consejería de Empleo',
    'program_year' => '2023-2024',
    'video_url' => '/sites/default/files/success-cases/videos/testimonio-maia-tolomeo.mp4',
    'schema_date_published' => '2024-03-26',
    'meta_description' => 'Caso de éxito real: Maia Tolomeo, participante del programa Andalucía +ei, pasó del desempleo a empleada en comercio local con acompañamiento profesional personalizado.',
    'status' => TRUE,
    'featured' => FALSE,
    'weight' => 14,
  ],
];

$created = 0;
foreach ($cases as $data) {
  // Check if already exists.
  $existing = $storage->getQuery()
    ->accessCheck(FALSE)
    ->condition('slug', $data['slug'])
    ->condition('vertical', $data['vertical'])
    ->count()
    ->execute();

  if ((int) $existing > 0) {
    echo "Skipping {$data['vertical']}/{$data['slug']} — already exists.\n";
    continue;
  }

  $entity = $storage->create($data);
  $entity->save();
  $created++;
  echo "Created: {$data['vertical']}/{$data['slug']} (id: {$entity->id()})\n";
}

echo "\nSeed complete: $created new SuccessCase entities created.\n";
