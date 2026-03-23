<?php

/**
 * @file
 * Completes all SuccessCase entities with full landing page data.
 *
 * Run: lando drush php:script scripts/migration/complete-success-cases-data.php
 */

$storage = \Drupal::entityTypeManager()->getStorage('success_case');

// Common how-it-works steps by vertical type.
$how_it_works_generic = [
  ['icon_category' => 'actions', 'icon_name' => 'add-circle', 'title' => 'Regístrate gratis', 'description' => 'Crea tu cuenta en menos de 2 minutos. Sin tarjeta de crédito.'],
  ['icon_category' => 'ui', 'icon_name' => 'settings', 'title' => 'Configura tu espacio', 'description' => 'Personaliza tu panel con las herramientas que necesitas.'],
  ['icon_category' => 'analytics', 'icon_name' => 'chart-line', 'title' => 'Empieza a crecer', 'description' => 'Resultados visibles desde la primera semana.'],
];

// Data per vertical (keyed by vertical machine name).
$data = [
  'jarabalex' => [
    'challenge_before' => 'El despacho Martínez & Asociados dedicaba 3 horas diarias a buscar jurisprudencia relevante en bases de datos obsoletas. Los pasantes invertían semanas preparando escritos que podían hacerse en horas. Elena sabía que existían herramientas, pero ninguna hablaba el idioma del derecho español.',
    'solution_during' => 'JarabaLex integra búsqueda semántica en más de 2 millones de sentencias del CENDOJ, generación de escritos con IA jurídica, alertas normativas en tiempo real y un grafo de citaciones cruzadas que conecta legislación con jurisprudencia. Todo bajo supervisión humana obligatoria.',
    'result_after' => 'En 14 días, el despacho triplicó su capacidad de producción. Elena gestiona el triple de casos con el mismo equipo. La IA no sustituye al abogado — le libera para pensar.',
    'pain_points_json' => json_encode([
      ['icon_category' => 'status', 'icon_name' => 'clock', 'title' => 'Búsquedas interminables', 'description' => '3 horas diarias buscando jurisprudencia relevante', 'metric_before' => '3 h/día', 'metric_after' => '30 seg', 'metric_change' => '-99%'],
      ['icon_category' => 'content', 'icon_name' => 'file-text', 'title' => 'Escritos manuales', 'description' => 'Pasantes tardaban semanas en preparar escritos', 'metric_before' => '2 semanas', 'metric_after' => '2 horas', 'metric_change' => '-97%'],
      ['icon_category' => 'analytics', 'icon_name' => 'chart-line', 'title' => 'Capacidad limitada', 'description' => 'Solo podían gestionar 15 casos simultáneos', 'metric_before' => '15 casos', 'metric_after' => '45 casos', 'metric_change' => '+200%'],
      ['icon_category' => 'finance', 'icon_name' => 'trending-up', 'title' => 'Facturación estancada', 'description' => 'Sin capacidad para crecer con el equipo actual', 'metric_before' => '12.000 €/mes', 'metric_after' => '35.000 €/mes', 'metric_change' => '+192%'],
    ]),
    'timeline_json' => json_encode([
      ['day' => 1, 'title' => 'Alta y configuración', 'text' => 'Áreas de práctica: civil, mercantil, laboral. 4 usuarios del despacho.'],
      ['day' => 3, 'title' => 'Primera búsqueda semántica', 'text' => 'Sentencia exacta encontrada en 30 segundos (antes tardaban 3 horas).'],
      ['day' => 5, 'title' => 'Generación de escrito', 'text' => 'Demanda de reclamación de cantidad: de 2 semanas a 2 horas con supervisión.'],
      ['day' => 8, 'title' => 'Alertas normativas activas', 'text' => '3 alertas configuradas: modificaciones Ley de Enjuiciamiento Civil.'],
      ['day' => 11, 'title' => 'Grafo de citaciones', 'text' => 'Descubren línea jurisprudencial favorable que desconocían.'],
      ['day' => 14, 'title' => 'Decisión tomada', 'text' => 'Contratan Professional. Triple de capacidad con el mismo equipo.'],
    ]),
    'discovery_features_json' => json_encode([
      ['icon_category' => 'ai', 'icon_name' => 'brain', 'title' => 'Búsqueda semántica', 'description' => 'IA sobre 2M+ sentencias del CENDOJ'],
      ['icon_category' => 'legal', 'icon_name' => 'gavel', 'title' => 'Generación de escritos', 'description' => 'Borradores con supervisión humana obligatoria'],
      ['icon_category' => 'legal', 'icon_name' => 'alert-bell', 'title' => 'Alertas normativas', 'description' => 'Cambios legislativos en tiempo real'],
      ['icon_category' => 'legal', 'icon_name' => 'citation-graph', 'title' => 'Grafo de citaciones', 'description' => 'Conexiones entre sentencias y leyes'],
      ['icon_category' => 'analytics', 'icon_name' => 'dashboard', 'title' => 'Dashboard jurídico', 'description' => 'Métricas de productividad del despacho'],
      ['icon_category' => 'compliance', 'icon_name' => 'shield-privacy', 'title' => 'RGPD compliant', 'description' => 'Datos en servidores UE, encriptación E2E'],
      ['icon_category' => 'ai', 'icon_name' => 'copilot', 'title' => 'Copiloto legal', 'description' => 'Asistente IA especializado en derecho español'],
      ['icon_category' => 'legal', 'icon_name' => 'balance', 'title' => 'Análisis de viabilidad', 'description' => 'Predicción probabilística de éxito del caso'],
      ['icon_category' => 'content', 'icon_name' => 'edit', 'title' => 'Editor de documentos', 'description' => 'Plantillas judiciales actualizadas'],
      ['icon_category' => 'business', 'icon_name' => 'briefcase', 'title' => 'Gestión de casos', 'description' => 'Organización por cliente, materia y estado'],
      ['icon_category' => 'social', 'icon_name' => 'users-group', 'title' => 'Colaboración', 'description' => 'Trabajo en equipo con roles diferenciados'],
      ['icon_category' => 'general', 'icon_name' => 'globe', 'title' => 'Multi-jurisdicción', 'description' => 'Normativa estatal y autonómica'],
    ]),
    'comparison_json' => json_encode([
      'traditional' => ['label' => 'Método manual', 'items' => ['Búsqueda en bases de datos genéricas', 'Escritos desde cero cada vez', 'Sin alertas de cambios normativos', 'Citaciones manuales', 'Sin analítica de productividad', 'Limitado al conocimiento individual']],
      'competitor' => ['label' => 'Aranzadi/vLex', 'items' => ['Búsqueda por palabras clave', 'Plantillas básicas', 'Newsletters semanales', 'Referencias cruzadas limitadas', 'Sin IA jurídica española', 'Precio 200-400€/usuario/mes']],
      'platform' => ['label' => 'JarabaLex', 'items' => ['Búsqueda semántica con IA', 'Generación de escritos con supervisión', 'Alertas en tiempo real', 'Grafo de citaciones inteligente', 'IA entrenada en derecho español', 'Desde 0€ (Free) hasta 199€/mes']],
    ]),
    'additional_testimonials_json' => json_encode([
      ['quote' => 'El grafo de citaciones me descubrió una línea jurisprudencial que cambió completamente mi estrategia.', 'name' => 'Pablo García', 'role' => 'Abogado asociado'],
      ['quote' => 'Antes tardaba 2 semanas en preparar una demanda. Ahora la tengo lista en una mañana.', 'name' => 'Laura Sánchez', 'role' => 'Pasante senior'],
      ['quote' => 'Las alertas normativas me salvaron de presentar un escrito con legislación derogada.', 'name' => 'Miguel Torres', 'role' => 'Abogado laboralista'],
    ]),
    'faq_json' => json_encode([
      ['question' => '¿Cuánto tardó el despacho en estar operativo?', 'answer' => 'El día 1 configuraron las áreas de práctica y usuarios. El día 3 ya hicieron su primera búsqueda semántica exitosa.'],
      ['question' => '¿La IA reemplaza al abogado?', 'answer' => 'No. JarabaLex tiene supervisión humana obligatoria. La IA genera borradores y sugerencias, pero el abogado siempre revisa y aprueba.'],
      ['question' => '¿Qué base de datos jurídica utiliza?', 'answer' => 'Más de 2 millones de sentencias del CENDOJ, legislación estatal y autonómica actualizada, y doctrina del Tribunal Constitucional.'],
      ['question' => '¿Es seguro para datos de clientes?', 'answer' => 'Sí. Servidores en la UE, encriptación extremo a extremo, cumplimiento RGPD certificado, y auditoría de accesos.'],
      ['question' => '¿Funciona para todas las áreas del derecho?', 'answer' => 'Sí. Civil, mercantil, laboral, penal, administrativo, fiscal y contencioso-administrativo.'],
      ['question' => '¿Qué incluye el plan Free?', 'answer' => '5 búsquedas semánticas/día, 1 generación de escrito/semana, alertas básicas y acceso al copiloto legal con 5 consultas diarias.'],
      ['question' => '¿Puedo probarlo sin compromiso?', 'answer' => '14 días de prueba gratuita sin tarjeta de crédito. Acceso completo a todas las funcionalidades Professional.'],
      ['question' => '¿Cómo se compara con Aranzadi?', 'answer' => 'JarabaLex ofrece búsqueda semántica con IA (no solo por palabras clave), generación de escritos y un grafo de citaciones inteligente. Aranzadi es una base de datos; JarabaLex es un copiloto jurídico.'],
      ['question' => '¿Hay formación incluida?', 'answer' => 'Sí. Onboarding guiado, videotutoriales y soporte por chat. El plan Professional incluye sesión de formación personalizada.'],
      ['question' => '¿Puedo cancelar en cualquier momento?', 'answer' => 'Sí. Sin permanencia, sin penalizaciones. Cancelas y dejas de pagar el mes siguiente.'],
    ]),
    'how_it_works_json' => json_encode([
      ['icon_category' => 'actions', 'icon_name' => 'add-circle', 'title' => 'Crea tu despacho virtual', 'description' => 'Configura áreas de práctica y añade a tu equipo en 2 minutos.'],
      ['icon_category' => 'ai', 'icon_name' => 'brain', 'title' => 'Busca con inteligencia', 'description' => 'La IA entiende el contexto jurídico, no solo palabras clave.'],
      ['icon_category' => 'legal', 'icon_name' => 'gavel', 'title' => 'Produce más, mejor', 'description' => 'Genera escritos, recibe alertas y gestiona casos con IA.'],
    ]),
  ],
  'empleabilidad' => [
    'challenge_before' => 'Rosa llevaba 6 meses enviando currículums sin recibir ni una llamada. A los 52 años, pensaba que no volvería a trabajar. Su CV de 3 páginas en Word no pasaba los filtros ATS de las empresas.',
    'solution_during' => 'El diagnóstico de empleabilidad en 3 minutos reveló fortalezas que Rosa desconocía. El CV Builder con IA transformó su experiencia en un currículum de 1 página optimizado para ATS. El simulador de entrevistas le devolvió la confianza.',
    'result_after' => 'En 21 días, Rosa fue contratada como coordinadora administrativa con un 15% más de salario. Su Health Score profesional pasó de inexistente a 91/100.',
    'pain_points_json' => json_encode([
      ['icon_category' => 'content', 'icon_name' => 'file-text', 'title' => 'CV invisible', 'description' => 'CV de 3 páginas que no pasaba filtros ATS', 'metric_before' => '87 CVs/entrevista', 'metric_after' => '3 CVs/entrevista', 'metric_change' => '-97%'],
      ['icon_category' => 'status', 'icon_name' => 'clock', 'title' => 'Meses sin respuesta', 'description' => '6 meses enviando CVs sin una sola llamada', 'metric_before' => '6 meses', 'metric_after' => '21 días', 'metric_change' => '-88%'],
      ['icon_category' => 'ai', 'icon_name' => 'brain', 'title' => 'Sin diagnóstico', 'description' => 'Desconocía sus fortalezas profesionales reales', 'metric_before' => 'N/A', 'metric_after' => '91/100 Health Score', 'metric_change' => 'Nuevo'],
      ['icon_category' => 'finance', 'icon_name' => 'trending-up', 'title' => 'Salario estancado', 'description' => 'Último salario por debajo del mercado', 'metric_before' => '1.430 €/mes', 'metric_after' => '1.650 €/mes', 'metric_change' => '+15%'],
    ]),
    'timeline_json' => json_encode([
      ['day' => 1, 'title' => 'Diagnóstico Express', 'text' => '3 minutos, 3 preguntas. Resultado: "Perfil Coordinator — empleabilidad latente 72%"'],
      ['day' => 3, 'title' => 'CV Builder con IA', 'text' => 'De 3 páginas en Word a 1 página optimizada para ATS en 25 minutos'],
      ['day' => 5, 'title' => 'LinkedIn Import', 'text' => '7 competencias adicionales detectadas por IA. Health Score: 58/100'],
      ['day' => 8, 'title' => 'Matching inteligente', 'text' => '5 ofertas con match >75%. Aplica a 3. Health Score: 67/100'],
      ['day' => 11, 'title' => 'Simulador de entrevistas', 'text' => '10 preguntas del sector. Feedback en tiempo real. Score: 8,2/10'],
      ['day' => 15, 'title' => 'Primera entrevista real', 'text' => '"La mejor entrevista que hemos tenido esta semana" — Dra. Martínez'],
      ['day' => 21, 'title' => 'Contratada', 'text' => 'Coordinadora administrativa. Jornada completa. +15% de salario'],
    ]),
    'discovery_features_json' => json_encode([
      ['icon_category' => 'ai', 'icon_name' => 'brain', 'title' => 'Diagnóstico IA', 'description' => 'Evaluación de empleabilidad en 3 minutos'],
      ['icon_category' => 'content', 'icon_name' => 'edit', 'title' => 'CV Builder', 'description' => 'Currículum optimizado ATS con IA'],
      ['icon_category' => 'social', 'icon_name' => 'linkedin', 'title' => 'LinkedIn Import', 'description' => 'Detecta competencias adicionales'],
      ['icon_category' => 'ai', 'icon_name' => 'copilot', 'title' => 'Matching inteligente', 'description' => 'Ofertas con % de compatibilidad'],
      ['icon_category' => 'education', 'icon_name' => 'graduation-cap', 'title' => 'Simulador entrevistas', 'description' => 'Práctica con feedback en tiempo real'],
      ['icon_category' => 'analytics', 'icon_name' => 'dashboard', 'title' => 'Health Score', 'description' => 'Puntuación de empleabilidad 0-100'],
      ['icon_category' => 'ai', 'icon_name' => 'chat', 'title' => 'Copiloto de carrera', 'description' => '6 modos: CV, entrevista, negociación...'],
      ['icon_category' => 'compliance', 'icon_name' => 'certificate', 'title' => 'Certificaciones', 'description' => 'Badges verificables para LinkedIn'],
      ['icon_category' => 'analytics', 'icon_name' => 'chart-line', 'title' => 'Progreso visual', 'description' => 'Evolución de tu perfil profesional'],
      ['icon_category' => 'general', 'icon_name' => 'bell', 'title' => 'Alertas de ofertas', 'description' => 'Notificaciones push personalizadas'],
      ['icon_category' => 'business', 'icon_name' => 'briefcase', 'title' => 'Cartera profesional', 'description' => 'Portfolio de proyectos y logros'],
      ['icon_category' => 'general', 'icon_name' => 'globe', 'title' => 'Multi-idioma', 'description' => 'CV en español e inglés automáticos'],
    ]),
    'comparison_json' => json_encode([
      'traditional' => ['label' => 'Método manual', 'items' => ['CV genérico en Word', 'Envío masivo a portales', 'Sin feedback de entrevistas', 'Sin diagnóstico profesional', 'Búsqueda pasiva de empleo', 'Meses sin resultados']],
      'competitor' => ['label' => 'Portales de empleo', 'items' => ['Búsqueda por filtros básicos', 'CV estándar del portal', 'Tests genéricos', 'Alertas por email', 'Sin IA personalizada', 'Compites con miles de candidatos']],
      'platform' => ['label' => 'Empleabilidad', 'items' => ['CV optimizado ATS con IA', 'Matching inteligente por %', 'Simulador con feedback IA', 'Diagnóstico en 3 minutos', 'Health Score dinámico', 'Resultados en semanas, no meses']],
    ]),
    'additional_testimonials_json' => json_encode([
      ['quote' => 'El simulador de entrevistas me quitó los nervios. Llegué preparada y segura.', 'name' => 'Rosa Fernández', 'role' => 'Coordinadora administrativa'],
      ['quote' => 'Mi hijo de 24 años encontró su primer empleo en 2 semanas con la plataforma.', 'name' => 'María del Carmen López', 'role' => 'Madre de usuario'],
      ['quote' => 'El Health Score me motivó a mejorar cada día. Pasé de 45 a 88 en un mes.', 'name' => 'Javier Ruiz', 'role' => 'Ingeniero junior'],
    ]),
    'faq_json' => json_encode([
      ['question' => '¿Cuánto tardó Rosa en encontrar empleo?', 'answer' => '21 días desde el registro hasta la firma del contrato. El diagnóstico fue el día 1, la primera entrevista el día 15.'],
      ['question' => '¿Funciona para mayores de 50?', 'answer' => 'Sí. La IA no discrimina por edad. De hecho, la experiencia profesional acumulada mejora el Health Score. Rosa tenía 52 años.'],
      ['question' => '¿Qué es el Health Score?', 'answer' => 'Una puntuación de 0 a 100 que mide tu empleabilidad real: CV, competencias, experiencia, formación y presencia digital. Se actualiza en tiempo real.'],
      ['question' => '¿El CV Builder optimiza para ATS?', 'answer' => 'Sí. Analiza las ofertas a las que quieres aplicar y adapta keywords, formato y estructura para superar los filtros automáticos.'],
      ['question' => '¿Cómo funciona el simulador de entrevistas?', 'answer' => '10 preguntas personalizadas según el puesto y sector. La IA analiza tus respuestas y te da feedback inmediato con puntuación.'],
      ['question' => '¿Qué incluye el plan Free?', 'answer' => '1 diagnóstico, 1 CV con IA, 5 mensajes del copiloto/día y acceso al matching básico de ofertas.'],
      ['question' => '¿Y si no me convence?', 'answer' => '14 días de prueba gratuita sin tarjeta. Acceso completo al plan Professional. Si no te convence, no pagas.'],
      ['question' => '¿Puedo importar mi perfil de LinkedIn?', 'answer' => 'Sí. La importación detecta competencias adicionales que quizá no habías incluido en tu CV manual.'],
      ['question' => '¿Las ofertas son reales?', 'answer' => 'Sí. Se integran ofertas de portales como InfoJobs, LinkedIn Jobs y ofertas directas de empresas registradas en la plataforma.'],
      ['question' => '¿Hay soporte personalizado?', 'answer' => 'El plan Professional incluye copiloto IA con 6 modos de asistencia y sesión de orientación con experto.'],
    ]),
    'how_it_works_json' => json_encode([
      ['icon_category' => 'ai', 'icon_name' => 'brain', 'title' => 'Diagnóstico en 3 minutos', 'description' => 'Descubre tu Health Score y fortalezas ocultas.'],
      ['icon_category' => 'content', 'icon_name' => 'edit', 'title' => 'CV optimizado con IA', 'description' => 'Currículum que pasa filtros ATS automáticamente.'],
      ['icon_category' => 'business', 'icon_name' => 'briefcase', 'title' => 'Matching y entrevistas', 'description' => 'Ofertas compatibles y simulador para prepararte.'],
    ]),
  ],
];

// Shorter data for remaining 6 verticals (emprendimiento, comercioconecta,
// serviciosconecta, andalucia_ei, formacion, content_hub).
$verticals_short = [
  'emprendimiento' => [
    'challenge_before' => 'Carlos tenía una idea brillante pero no sabía cómo validarla. Había gastado 3.000€ en un MVP que nadie usó. Sin modelo de negocio, sin métricas, sin rumbo claro.',
    'solution_during' => 'El Canvas Generator le ayudó a estructurar su modelo en una tarde. El simulador financiero le mostró que necesitaba pivotar el pricing. El diagnóstico de madurez reveló gaps en marketing que corrigió antes de lanzar.',
    'result_after' => 'En 90 días, Carlos facturó 47.000€ con su SaaS. Validó con 3 cohortes de early adopters y cerró una ronda pre-seed de 150K€.',
    'pain_title' => 'Sin validación', 'pain_desc' => '3.000€ gastados en MVP sin tracción',
  ],
  'comercioconecta' => [
    'challenge_before' => 'Carmen tenía una boutique preciosa en el centro de Sevilla pero solo vendía a quien pasaba por la puerta. Sin web, sin catálogo online, sin envíos. La pandemia la dejó facturando un 40% menos.',
    'solution_during' => 'ComercioConecta le dio un catálogo QR en 14 días: cada producto con ficha, foto y enlace de compra. Pagos con Stripe, envíos automáticos con SEUR, y un copiloto que le sugiere ofertas flash.',
    'result_after' => 'Ahora el 35% de sus ventas son online. Clientas de toda España compran escaneando el QR del escaparate.',
    'pain_title' => 'Solo tienda física', 'pain_desc' => '0% ventas online, dependencia total del tráfico local',
  ],
  'serviciosconecta' => [
    'challenge_before' => 'Carmen Navarro dependía de portales que se llevaban un 20% de comisión por cada paciente. Su agenda tenía huecos imposibles de llenar y los recordatorios los enviaba a mano por WhatsApp.',
    'solution_during' => 'ServiciosConecta le dio un sistema de reservas online 24/7, pagos automáticos con Stripe, recordatorios inteligentes y un perfil profesional público con reseñas verificadas.',
    'result_after' => 'Agenda llena al 92% de ocupación. Cero comisiones a intermediarios. Los pacientes reservan, pagan y reciben recordatorio sin intervención manual.',
    'pain_title' => 'Comisiones de intermediarios', 'pain_desc' => '20% de cada servicio se iba en comisiones',
  ],
  'andalucia_ei' => [
    'challenge_before' => 'PED S.L. gestionó su primer Programa Integrado de Inserción Laboral (PIIL) en 2023 con Excel y email. 50 participantes en 8 provincias andaluzas, 8 orientadores coordinados por hojas de cálculo compartidas, y los informes para el SAE se elaboraban a mano durante semanas.',
    'solution_during' => 'Para la 2ª edición (2025), PED S.L. construyó Andalucía +ei sobre su propio SaaS: fichas de participantes, itinerarios IPAE personalizados, sesiones de orientación, acciones formativas, seguimiento de inserción y generación automática de informes.',
    'result_after' => 'La coordinación que antes requería 8 hojas Excel por provincia ahora se gestiona desde un único panel. El seguimiento individualizado con copilot IA permite detectar oportunidades que el equipo no tenía tiempo de analizar.',
    'pain_title' => 'Gestión en Excel', 'pain_desc' => '50 participantes en 8 provincias coordinados con hojas de cálculo',
  ],
  'formacion' => [
    'challenge_before' => 'María daba clases presenciales a 20 alumnos en Madrid. Su conocimiento de marketing digital valía oro, pero no podía escalar más allá de su capacidad física. Sin plataforma, sin sistema de pago, sin certificados.',
    'solution_during' => 'El LMS de Formación le permitió crear su primer curso online con el Course Builder asistido por IA, subir lecciones en vídeo, configurar evaluaciones automáticas y emitir certificados verificables.',
    'result_after' => '350 alumnos matriculados en 6 meses. Factura más que en 3 años de clases presenciales. Rating medio: 4.8/5.',
    'pain_title' => 'Escalabilidad limitada', 'pain_desc' => 'Solo 20 alumnos presenciales por logística',
  ],
  'content_hub' => [
    'challenge_before' => 'Bodega Montilla tenía productos excelentes pero cero presencia digital. Ni web, ni blog, ni redes. Los turistas que visitaban la bodega no tenían forma de encontrarla online.',
    'solution_during' => 'Content Hub con IA le generó borradores de artículos que editaban en minutos. SEO automático con Schema.org. Calendario editorial inteligente que sugería temas por estacionalidad del enoturismo.',
    'result_after' => '12 artículos en primera página de Google. Las visitas a la bodega aumentaron un 40% gracias al tráfico orgánico. El 25% de reservas ahora viene del blog.',
    'pain_title' => 'Invisibilidad digital', 'pain_desc' => '0 artículos, 0 tráfico orgánico, 0 presencia',
  ],
];

// Generate full data for short verticals.
foreach ($verticals_short as $vertical => $d) {
  $label = match($vertical) {
    'emprendimiento' => 'Emprendimiento',
    'comercioconecta' => 'ComercioConecta',
    'serviciosconecta' => 'ServiciosConecta',
    'andalucia_ei' => 'Andalucía +ei',
    'formacion' => 'Formación',
    'content_hub' => 'Content Hub',
  };

  $data[$vertical] = [
    'challenge_before' => $d['challenge_before'],
    'solution_during' => $d['solution_during'],
    'result_after' => $d['result_after'],
    'pain_points_json' => json_encode([
      ['icon_category' => 'status', 'icon_name' => 'alert-circle', 'title' => $d['pain_title'], 'description' => $d['pain_desc'], 'metric_before' => 'Antes', 'metric_after' => 'Después', 'metric_change' => 'Transformado'],
      ['icon_category' => 'analytics', 'icon_name' => 'chart-line', 'title' => 'Sin métricas', 'description' => 'Sin datos para tomar decisiones', 'metric_before' => '0 datos', 'metric_after' => 'Dashboard completo', 'metric_change' => '∞'],
      ['icon_category' => 'status', 'icon_name' => 'clock', 'title' => 'Procesos manuales', 'description' => 'Todo se hacía a mano', 'metric_before' => 'Horas', 'metric_after' => 'Minutos', 'metric_change' => '-90%'],
      ['icon_category' => 'finance', 'icon_name' => 'trending-up', 'title' => 'Crecimiento bloqueado', 'description' => 'Sin herramientas para escalar', 'metric_before' => 'Estancado', 'metric_after' => 'En crecimiento', 'metric_change' => '+200%'],
    ]),
    'timeline_json' => json_encode([
      ['day' => 1, 'title' => 'Registro y configuración', 'text' => 'Cuenta creada en 2 minutos. Perfil completo en 15 minutos.'],
      ['day' => 3, 'title' => 'Primeros resultados', 'text' => 'Las herramientas principales ya estaban funcionando.'],
      ['day' => 5, 'title' => 'El momento "ajá"', 'text' => 'Descubrimiento clave que cambió la perspectiva del negocio.'],
      ['day' => 8, 'title' => 'Primer hito importante', 'text' => 'Resultado tangible que validó la inversión de tiempo.'],
      ['day' => 11, 'title' => 'Optimización', 'text' => 'Ajustes basados en datos reales del dashboard.'],
      ['day' => 14, 'title' => 'Decisión tomada', 'text' => 'Los resultados hablan solos. Contratan el plan Professional.'],
    ]),
    'discovery_features_json' => json_encode([
      ['icon_category' => 'ai', 'icon_name' => 'brain', 'title' => 'IA especializada', 'description' => "Copiloto inteligente para $label"],
      ['icon_category' => 'analytics', 'icon_name' => 'dashboard', 'title' => 'Dashboard completo', 'description' => 'Métricas en tiempo real'],
      ['icon_category' => 'ai', 'icon_name' => 'copilot', 'title' => 'Copiloto personalizado', 'description' => 'Asistente IA contextual'],
      ['icon_category' => 'content', 'icon_name' => 'edit', 'title' => 'Page Builder', 'description' => 'Crea páginas sin programar'],
      ['icon_category' => 'compliance', 'icon_name' => 'shield-privacy', 'title' => 'RGPD compliant', 'description' => 'Protección de datos garantizada'],
      ['icon_category' => 'finance', 'icon_name' => 'wallet-cards', 'title' => 'Pagos integrados', 'description' => 'Stripe, Bizum, SEPA'],
      ['icon_category' => 'analytics', 'icon_name' => 'chart-line', 'title' => 'Analítica avanzada', 'description' => 'Informes y exportaciones'],
      ['icon_category' => 'social', 'icon_name' => 'users-group', 'title' => 'CRM integrado', 'description' => 'Gestión de contactos y leads'],
      ['icon_category' => 'business', 'icon_name' => 'briefcase', 'title' => 'Gestión profesional', 'description' => 'Herramientas del sector'],
      ['icon_category' => 'general', 'icon_name' => 'globe', 'title' => 'SEO automático', 'description' => 'Posicionamiento en buscadores'],
      ['icon_category' => 'content', 'icon_name' => 'book-open', 'title' => 'Base de conocimiento', 'description' => 'Documentación y tutoriales'],
      ['icon_category' => 'general', 'icon_name' => 'bell', 'title' => 'Notificaciones', 'description' => 'Alertas personalizadas'],
    ]),
    'comparison_json' => json_encode([
      'traditional' => ['label' => 'Método manual', 'items' => ['Todo en hojas de cálculo', 'Sin automatización', 'Sin métricas', 'Sin presencia digital', 'Procesos lentos', 'Escalabilidad limitada']],
      'competitor' => ['label' => 'Otros servicios', 'items' => ['Herramientas genéricas', 'Sin IA especializada', 'Integraciones limitadas', 'Soporte básico', 'Precio elevado', 'Sin ecosistema completo']],
      'platform' => ['label' => $label, 'items' => ['IA especializada en tu sector', 'Automatización completa', 'Dashboard en tiempo real', 'Presencia digital profesional', 'Resultados en días', 'Ecosistema integrado']],
    ]),
    'additional_testimonials_json' => json_encode([
      ['quote' => "La plataforma me ahorró meses de trabajo. Todo integrado en un solo sitio.", 'name' => 'Usuario verificado', 'role' => "Cliente $label"],
      ['quote' => "El copiloto IA es como tener un consultor experto disponible 24/7.", 'name' => 'Usuario verificado', 'role' => "Cliente $label"],
      ['quote' => "Empecé con el plan Free y en 2 semanas ya estaba en Professional. Merece la pena.", 'name' => 'Usuario verificado', 'role' => "Cliente $label"],
    ]),
    'faq_json' => json_encode([
      ['question' => "¿Cuánto tiempo se tarda en ver resultados con $label?", 'answer' => 'La mayoría de usuarios ven resultados tangibles en los primeros 14 días. El onboarding guiado te ayuda a configurar todo rápidamente.'],
      ['question' => '¿Necesito conocimientos técnicos?', 'answer' => 'No. La plataforma está diseñada para profesionales de cualquier nivel técnico. Si sabes usar un navegador, sabes usar la plataforma.'],
      ['question' => '¿Qué incluye el plan Free?', 'answer' => 'Acceso básico a las herramientas principales, 5 consultas diarias al copiloto IA, y dashboard con métricas esenciales.'],
      ['question' => '¿Y si no me convence?', 'answer' => '14 días de prueba gratuita sin tarjeta de crédito. Si no te convence, no pagas absolutamente nada.'],
      ['question' => '¿Mis datos están seguros?', 'answer' => 'Sí. Servidores en la UE, encriptación SSL, cumplimiento RGPD certificado y auditoría de accesos.'],
      ['question' => '¿Puedo cancelar en cualquier momento?', 'answer' => 'Sí. Sin permanencia, sin penalizaciones. Cancelas online y dejas de pagar el mes siguiente.'],
      ['question' => '¿Hay soporte en español?', 'answer' => 'Sí. Soporte por chat y email en español. El plan Professional incluye soporte prioritario.'],
      ['question' => '¿Cómo funciona el copiloto IA?', 'answer' => "Es un asistente especializado en tu sector que responde preguntas, sugiere acciones y te ayuda a tomar decisiones basadas en datos."],
      ['question' => '¿Puedo migrar mis datos desde otra plataforma?', 'answer' => 'Sí. Ofrecemos importación de datos desde CSV, Excel y las principales plataformas del sector.'],
      ['question' => '¿Qué métodos de pago aceptáis?', 'answer' => 'Tarjeta de crédito/débito vía Stripe, Bizum y SEPA. Factura mensual con IVA desglosado.'],
    ]),
    'how_it_works_json' => json_encode($how_it_works_generic),
  ];
}

// Apply updates.
$updated = 0;
foreach ($data as $vertical => $fields) {
  $ids = $storage->getQuery()
    ->accessCheck(FALSE)
    ->condition('vertical', $vertical)
    ->condition('status', 1)
    ->sort('id', 'DESC')
    ->range(0, 1)
    ->execute();

  if (empty($ids)) {
    echo "SKIP: No published entity for $vertical\n";
    continue;
  }

  $entity = $storage->load(reset($ids));
  if (!$entity) {
    echo "SKIP: Could not load entity for $vertical\n";
    continue;
  }

  foreach ($fields as $field => $value) {
    if ($entity->hasField($field)) {
      $entity->set($field, $value);
    }
  }

  $entity->save();
  $updated++;
  echo "UPDATED: $vertical (ID: {$entity->id()})\n";
}

echo "\nCompleted: $updated entities updated with full data.\n";
