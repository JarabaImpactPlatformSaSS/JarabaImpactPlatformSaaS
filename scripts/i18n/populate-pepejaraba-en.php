#!/usr/bin/env php
<?php
/**
 * @file populate-pepejaraba-en.php
 *
 * Traduce todas las paginas de pepejaraba.com (tenant 5) a EN
 * usando diccionario completo de textos extraidos del ES.
 *
 * USO:
 *   lando drush scr scripts/i18n/populate-pepejaraba-en.php
 */

declare(strict_types=1);

// Diccionario exhaustivo ES -> EN para pepejaraba.com.
// Ordenado de frases largas a cortas para que el reemplazo sea correcto.
$dict = [
  // =========================================================================
  // PAGE 57 — Inicio
  // =========================================================================
  'Ves cómo tu competencia vende online mientras tú sigues atado al día a día. Has invertido en herramientas que no entiendes y en promesas que no se cumplen. Necesitas resultados, no más reuniones.' => 'You watch your competitors sell online while you\'re stuck in the day-to-day. You\'ve invested in tools you don\'t understand and promises that aren\'t kept. You need results, not more meetings.',
  'Tienes una gran idea pero te falta capital, conocimiento técnico y una hoja de ruta clara. Cada paso parece un laberinto de opciones confusas. Necesitas un camino directo al primer resultado.' => 'You have a great idea but lack capital, technical knowledge and a clear roadmap. Every step feels like a maze of confusing options. You need a direct path to your first result.',
  'El mercado cambia y tus candidaturas no reciben respuesta. La IA parece una amenaza en lugar de una aliada. Necesitas visibilidad digital y estrategia de marca personal que funcione.' => 'The market changes and your applications get no response. AI seems like a threat instead of an ally. You need digital visibility and a personal branding strategy that works.',
  'Más de 30 años gestionando más de 100 millones en fondos europeos me enseñaron algo: la transformación digital real no necesita humo, necesita puentes.' => 'Over 30 years managing more than 100 million in European funds taught me something: real digital transformation doesn\'t need hype, it needs bridges.',
  'El Ciclo de Impacto Digital (CID): 3 fases, 90 días, resultados medibles.' => 'The Digital Impact Cycle (DIC): 3 phases, 90 days, measurable results.',
  'Analizamos tu punto de partida, definimos un objetivo a 90 días y creamos tu Plan de Impulso Digital personalizado.' => 'We analyze your starting point, define a 90-day goal and create your personalized Digital Boost Plan.',
  'Construimos tus activos digitales mínimos, ejecutamos el plan y conseguimos tu primera victoria medible.' => 'We build your minimum digital assets, execute the plan and achieve your first measurable win.',
  'Analizamos datos, aprendemos qué funcionó e introducimos automatizaciones simples para crecer de forma sostenible.' => 'We analyze data, learn what worked and introduce simple automations to grow sustainably.',
  'Incluye: Checklist de diagnóstico digital, Plantilla de plan de 90 días, Guía de primeros pasos sin tecnicismos y Acceso a la comunidad del ecosistema.' => 'Includes: Digital diagnostic checklist, 90-day plan template, Jargon-free getting started guide and Ecosystem community access.',
  'María tenía una tienda de productos ecológicos que dependía del tráfico peatonal. En 90 días, implementamos su estrategia digital.' => 'María had an organic products shop that depended on foot traffic. In 90 days, we implemented her digital strategy.',
  'No necesitas inversión para dar el primer paso digital. Estas herramientas te permiten empezar hoy mismo, sin coste.' => 'You don\'t need investment to take the first digital step. These tools let you start today, at no cost.',
  'Tu perfil digital es tu carta de presentación 24/7. Estas tres claves marcan la diferencia entre ser visible o ser invisible.' => 'Your digital profile is your 24/7 business card. These three keys make the difference between being visible or invisible.',
  'Te ayudo a digitalizar tu negocio, lanzar tu idea o impulsar tu carrera profesional.' => 'I help you digitize your business, launch your idea or boost your professional career.',
  'Una plataforma integral para cada fase de tu camino digital.' => 'A comprehensive platform for every stage of your digital journey.',
  'Encuentra empleo o talento con matching inteligente potenciado por IA.' => 'Find jobs or talent with AI-powered smart matching.',
  'Valida tu idea y lanza tu negocio con metodología probada y mentoría.' => 'Validate your idea and launch your business with proven methodology and mentoring.',
  'Digitaliza tu negocio local y vende online de forma sencilla y efectiva.' => 'Digitize your local business and sell online simply and effectively.',
  'Cómo una Tienda de Barrio en Málaga Aumentó su Facturación un 30%' => 'How a Neighborhood Shop in Malaga Increased Revenue by 30%',
  '5 Herramientas Gratuitas para Lanzar tu Negocio Online esta Semana' => '5 Free Tools to Launch Your Online Business This Week',
  '3 Claves para que tu Perfil de LinkedIn Destaque en la Era de la IA' => '3 Keys to Making Your LinkedIn Profile Stand Out in the AI Era',
  'Sin humo, sin tecnicismos. Solo resultados reales.' => 'No fluff, no jargon. Just real results.',
  'No estás solo. Estos son los tres grandes retos que veo cada día.' => 'You\'re not alone. These are the three big challenges I see every day.',
  'Desde el Blog: Ideas y Estrategias Sin Humo' => 'From the Blog: No-Fluff Ideas and Strategies',
  'Sin spam. Sin compromisos. Solo recursos útiles.' => 'No spam. No commitments. Just useful resources.',
  'CONSULTOR EN TRANSFORMACIÓN DIGITAL' => 'DIGITAL TRANSFORMATION CONSULTANT',
  'Descarga tu Kit de Impulso Gratuito →' => 'Download your Free Starter Kit →',
  'Tu Kit de Impulso Digital Gratuito' => 'Your Free Digital Boost Kit',
  'Para el Negocio que no Tiene Tiempo' => 'For the Business with No Time',
  'Para la Idea que Necesita un Impulso' => 'For the Idea that Needs a Push',
  'Para el Talento que se Siente Invisible' => 'For the Talent that Feels Invisible',
  'Hay un camino más sencillo y honesto' => 'There\'s a simpler and more honest way',
  'Descubre el Método Jaraba →' => 'Discover the Jaraba Method →',
  'Ver todos los artículos en el Blog →' => 'See all articles on the Blog →',
  'Leer la historia completa →' => 'Read the full story →',
  'Descubre las herramientas →' => 'Discover the tools →',
  'Conoce el Método completo →' => 'Learn the full Method →',
  'Descargar Kit Gratuito →' => 'Download Free Kit →',
  'Transformación digital' => 'Digital transformation',
  'para todos, sin rodeos' => 'for everyone, no fluff',
  'El Ecosistema Sin Humo' => 'The No-Fluff Ecosystem',
  'Diagnóstico y Hoja de Ruta' => 'Diagnosis and Roadmap',
  'Implementación y Acción' => 'Implementation and Action',
  'Optimización y Escalado' => 'Optimization and Scaling',
  'Optimizar mi perfil →' => 'Optimize my profile →',
  'Acceder al Ecosistema →' => 'Access the Ecosystem →',
  'Lee mi Manifiesto →' => 'Read my Manifesto →',
  'Verticales del ecosistema' => 'Ecosystem verticals',
  'En fondos gestionados' => 'In managed funds',
  'Años de experiencia' => 'Years of experience',
  'Días para resultados' => 'Days to results',
  '¿Te Suena Familiar?' => 'Sound Familiar?',
  'El Método Jaraba' => 'The Jaraba Method',
  'Marca personal' => 'Personal branding',
  'Guías prácticas' => 'Practical guides',
  'Casos de éxito' => 'Success stories',
  'Emprendimiento' => 'Entrepreneurship',
  'Empleo' => 'Employment',
  'Comercio' => 'Commerce',
  '100M€' => '100M€',
  '30+' => '30+',

  // =========================================================================
  // PAGE 58 — Manifiesto
  // =========================================================================
  'Llevo más de 30 años trabajando en proyectos de desarrollo territorial y transformación digital. He gestionado más de 100 millones de euros en fondos europeos, diseñé planes estratégicos provinciales, puse en marcha la primera red WiFi rural de España y apoyé la transformación del sector ecológico en Andalucía.' => 'I\'ve spent over 30 years working on territorial development and digital transformation projects. I\'ve managed over 100 million euros in European funds, designed provincial strategic plans, launched the first rural WiFi network in Spain and supported the transformation of the organic sector in Andalusia.',
  'Los fondos europeos existen. Las herramientas digitales existen. Los programas de apoyo existen. Pero entre todo eso y el tendero de barrio, el artesano, la profesional de más de 45 años que busca reinventarse... hay un abismo de burocracia, complejidad y tecnicismos que los convierte en inaccesibles.' => 'European funds exist. Digital tools exist. Support programs exist. But between all that and the neighborhood shopkeeper, the artisan, the 45+ professional seeking reinvention... there\'s an abyss of bureaucracy, complexity and jargon that makes them inaccessible.',
  'Mientras los informes hablaban de "ecosistemas de innovación" y "transformación digital 4.0", la realidad era mucho más simple: la gente necesita herramientas que entienda, pasos que pueda seguir y resultados que pueda medir.' => 'While reports talked about "innovation ecosystems" and "digital transformation 4.0", the reality was much simpler: people need tools they understand, steps they can follow and results they can measure.',
  'En 2019 fundé la Plataforma de Ecosistemas Digitales con una misión clara: hacer que la transformación digital sea práctica, accesible y real. No más PowerPoints prometiendo el futuro. No más herramientas que nadie sabe usar. No más métricas de vanidad.' => 'In 2019 I founded the Digital Ecosystems Platform with a clear mission: make digital transformation practical, accessible and real. No more PowerPoints promising the future. No more tools nobody knows how to use. No more vanity metrics.',
  'Miles de profesionales, emprendedores y pymes ya están transformando su futuro digital. Sin humo.' => 'Thousands of professionals, entrepreneurs and SMEs are already transforming their digital future. No fluff.',
  'Más de 30 años construyendo ecosistemas digitales que conectan personas con oportunidades reales.' => 'Over 30 years building digital ecosystems that connect people with real opportunities.',
  'Mi Historia es Simple: Vi un Puente Roto y Decidí Construirlo' => 'My Story is Simple: I Saw a Broken Bridge and Decided to Build It',
  'Solo comparto métodos probados en negocios reales. Si no ha funcionado en la práctica, no lo recomiendo.' => 'I only share methods proven in real businesses. If it hasn\'t worked in practice, I don\'t recommend it.',
  'La mejor herramienta es la que entiendes y usas. No necesitas la más cara ni la más sofisticada.' => 'The best tool is the one you understand and use. You don\'t need the most expensive or the most sophisticated.',
  'Una acción pequeña hoy vale más que un plan perfecto para mañana. Empezamos con lo mínimo viable.' => 'A small action today is worth more than a perfect plan for tomorrow. We start with the minimum viable.',
  'El éxito se mide en ventas reales, entrevistas conseguidas y negocios funcionando. No en likes.' => 'Success is measured in real sales, interviews landed and businesses running. Not in likes.',
  'Comparto mis éxitos y mis fracasos. No hay atajos mágicos. Hay trabajo, método y constancia.' => 'I share my successes and my failures. There are no magic shortcuts. There is work, method and consistency.',
  'Suena impresionante en un currículum, pero lo que realmente me marcó fue lo que vi por el camino.' => 'It sounds impressive on a resume, but what really marked me was what I saw along the way.',
  'Vi un puente roto entre los recursos masivos que existen y las personas que los necesitan.' => 'I saw a broken bridge between the massive resources that exist and the people who need them.',
  'Mi Compromiso Contigo: Los Principios Sin Humo' => 'My Commitment to You: The No-Fluff Principles',
  'Personas antes que Métricas de Vanidad' => 'People over Vanity Metrics',
  'La Misión: El Ecosistema Sin Humo' => 'The Mission: The No-Fluff Ecosystem',
  'La Frustración: El Puente Roto' => 'The Frustration: The Broken Bridge',
  'Únete a la Comunidad del Ecosistema' => 'Join the Ecosystem Community',
  'Simplicidad antes que Complejidad' => 'Simplicity over Complexity',
  'Progreso antes que Perfección' => 'Progress over Perfection',
  'Realidad antes que Teoría' => 'Reality over Theory',
  'Transparencia Radical' => 'Radical Transparency',
  'Acceder al Ecosistema →' => 'Access the Ecosystem →',
  'El Origen' => 'The Origin',

  // =========================================================================
  // PAGE 59 — Metodo Jaraba
  // =========================================================================
  'Un sistema de 3 fases diseñado para conseguir resultados reales. Sin humo, sin atajos, sin complejidad innecesaria.' => 'A 3-phase system designed to achieve real results. No fluff, no shortcuts, no unnecessary complexity.',
  'Cada ciclo te lleva del diagnóstico a la acción y de la acción a resultados que puedes medir.' => 'Each cycle takes you from diagnosis to action and from action to results you can measure.',
  'Analizamos tu punto de partida, definimos un objetivo claro a 90 días y creamos tu plan de acción personalizado. Sin adornos, directo al grano.' => 'We analyze your starting point, define a clear 90-day goal and create your personalized action plan. No frills, straight to the point.',
  'Construimos tus activos digitales mínimos, ejecutamos el plan paso a paso y conseguimos tu primera victoria medible. Acción sobre planificación.' => 'We build your minimum digital assets, execute the plan step by step and achieve your first measurable win. Action over planning.',
  'Analizamos datos reales, aprendemos qué funcionó e introducimos automatizaciones simples para que tu crecimiento sea sostenible.' => 'We analyze real data, learn what worked and introduce simple automations so your growth is sustainable.',
  'La acción más pequeña que produce el mayor resultado. No buscamos la perfección, buscamos el progreso. Un paso bien dado vale más que cien pasos planeados.' => 'The smallest action that produces the biggest result. We don\'t seek perfection, we seek progress. One well-taken step is worth more than a hundred planned ones.',
  'Las herramientas deben simplificar tu vida, no complicarla. Si no la entiendes, no es la herramienta correcta. Elegimos tecnología que sirve a las personas.' => 'Tools should simplify your life, not complicate it. If you don\'t understand it, it\'s not the right tool. We choose technology that serves people.',
  'Solo importan las métricas que se traducen en impacto tangible. Ventas, clientes, entrevistas, ahorros de tiempo. No likes ni seguidores vacíos.' => 'Only metrics that translate into tangible impact matter. Sales, clients, interviews, time savings. Not likes or empty followers.',
  'Cada ciclo de 90 días es un paso adelante. Diagnosticar, actuar, medir, optimizar. Y vuelta a empezar. Mejora continua sin agotamiento.' => 'Every 90-day cycle is a step forward. Diagnose, act, measure, optimize. And start again. Continuous improvement without burnout.',
  'Descubre cómo pymes, emprendedores y profesionales han aplicado el Ciclo de Impacto Digital para transformar su realidad.' => 'Discover how SMEs, entrepreneurs and professionals have applied the Digital Impact Cycle to transform their reality.',
  'El Método Jaraba: El Ciclo de Impacto Digital (CID)' => 'The Jaraba Method: The Digital Impact Cycle (DIC)',
  'Entregable: Informe de Resultados + Plan de Siguiente Ciclo' => 'Deliverable: Results Report + Next Cycle Plan',
  'Entregable: Activos Digitales + Primera Victoria' => 'Deliverable: Digital Assets + First Win',
  '3 Fases, 90 Días, Resultados Medibles' => '3 Phases, 90 Days, Measurable Results',
  'Los 4 Principios No Negociables' => 'The 4 Non-Negotiable Principles',
  'Entregable: Plan de Impulso Digital' => 'Deliverable: Digital Boost Plan',
  'Ve el Método en Acción' => 'See the Method in Action',
  'Acción Mínima Viable' => 'Minimum Viable Action',
  'Ver Casos de Éxito →' => 'See Success Stories →',
  'Diagnóstico y Hoja de Ruta' => 'Diagnosis and Roadmap',
  'Implementación y Acción' => 'Implementation and Action',
  'Optimización y Escalado' => 'Optimization and Scaling',
  'Fase 1 — Días 1-30' => 'Phase 1 — Days 1-30',
  'Fase 2 — Días 31-60' => 'Phase 2 — Days 31-60',
  'Fase 3 — Días 61-90' => 'Phase 3 — Days 61-90',
  'Tecnología Humana' => 'Human Technology',
  'Proceso Cíclico' => 'Cyclical Process',
  'Medición Real' => 'Real Measurement',

  // =========================================================================
  // PAGE 61 — Blog
  // =========================================================================
  'Estamos preparando contenido de alto valor sobre transformación digital práctica. Mientras tanto, descarga tu Kit de Impulso Gratuito para empezar a actuar hoy.' => 'We\'re preparing high-value content on practical digital transformation. Meanwhile, download your Free Boost Kit to start taking action today.',
  'Estrategias prácticas de transformación digital para impulsar tu proyecto. Sin tecnicismos, sin humo.' => 'Practical digital transformation strategies to boost your project. No jargon, no fluff.',
  'Blog: Ideas y Estrategias Sin Humo' => 'Blog: No-Fluff Ideas and Strategies',
  'Descargar Kit de Impulso →' => 'Download Boost Kit →',
  'Pronto nuevos artículos' => 'New articles coming soon',
  'Casos de éxito' => 'Success stories',
  'Guías prácticas' => 'Practical guides',
  'Marca personal' => 'Personal branding',
  'Tendencias' => 'Trends',
  'Todos' => 'All',

  // =========================================================================
  // PAGE 62 — Contacto
  // =========================================================================
  'Elige el canal que mejor te funcione. Respuesta en menos de 48 horas.' => 'Choose the channel that works best for you. Response within 48 hours.',
  'Quiero impulsar mi negocio' => 'I want to boost my business',
  'Busco empleo / marca personal' => 'Looking for a job / personal branding',
  'Conferencias y charlas' => 'Conferences and talks',
  '¿Cómo puedo ayudarte?' => 'How can I help you?',
  'Quiero emprender' => 'I want to start a business',
  'Hablemos. Sin Humo' => 'Let\'s Talk. No Fluff',
  'Conecta en Redes' => 'Connect on Social Media',
  'Base de Operaciones' => 'Base of Operations',
  'Canales de Contacto' => 'Contact Channels',
  '29002 Málaga, España' => '29002 Malaga, Spain',
  'Calle Héroe de Sostoa 12' => 'Calle Héroe de Sostoa 12',
  'Envía tu Consulta' => 'Send Your Inquiry',
  'Enviar consulta →' => 'Send inquiry →',
  'Nombre completo' => 'Full name',
  'Consulta general' => 'General inquiry',
  'WhatsApp' => 'WhatsApp',
  'Mensaje' => 'Message',
  'Email' => 'Email',

  // =========================================================================
  // PAGE 63 — Aviso Legal
  // =========================================================================
  'En cumplimiento del artículo 10 de la Ley 34/2002, de 11 de julio, de Servicios de la Sociedad de la Información y Comercio Electrónico, se informa:' => 'In compliance with Article 10 of Law 34/2002, of July 11, on Information Society and Electronic Commerce Services, the following is reported:',
  'El presente sitio web tiene como finalidad proporcionar información sobre los servicios de consultoría en transformación digital ofrecidos por Pepe Jaraba a través de Plataforma de Ecosistemas Digitales S.L.' => 'This website aims to provide information about digital transformation consulting services offered by Pepe Jaraba through Plataforma de Ecosistemas Digitales S.L.',
  'Todos los contenidos del sitio web, incluyendo textos, imágenes, diseño gráfico, código fuente, logos y demás elementos, son propiedad de Plataforma de Ecosistemas Digitales S.L. o de sus legítimos titulares y están protegidos por las leyes de propiedad intelectual e industrial.' => 'All website content, including texts, images, graphic design, source code, logos and other elements, are the property of Plataforma de Ecosistemas Digitales S.L. or their legitimate owners and are protected by intellectual and industrial property laws.',
  'Plataforma de Ecosistemas Digitales S.L. no se hace responsable del mal uso que se realice de los contenidos de su página web, siendo exclusiva responsabilidad de la persona que accede a ellos o los utiliza.' => 'Plataforma de Ecosistemas Digitales S.L. is not responsible for the misuse of its website content, being the sole responsibility of the person who accesses or uses it.',
  'Las presentes condiciones se rigen por la legislación española. Para cualquier controversia que pudiera derivarse del acceso o uso de este sitio web, las partes se someten a los Juzgados y Tribunales de Málaga.' => 'These conditions are governed by Spanish law. For any dispute that may arise from the access or use of this website, the parties submit to the Courts of Malaga.',
  'Inscrita en el Registro Mercantil de Málaga' => 'Registered at the Malaga Trade Registry',
  'Calle Héroe de Sostoa 12, 29002 Málaga' => 'Calle Héroe de Sostoa 12, 29002 Malaga',
  'Plataforma de Ecosistemas Digitales S.L.' => 'Plataforma de Ecosistemas Digitales S.L.',
  'Ley Aplicable y Jurisdicción' => 'Applicable Law and Jurisdiction',
  'Propiedad Intelectual' => 'Intellectual Property',
  'Datos Identificativos' => 'Identification Data',
  'Última actualización:' => 'Last updated:',
  'Registro Mercantil:' => 'Trade Registry:',
  'Responsabilidad' => 'Liability',
  'Febrero 2026' => 'February 2026',
  'Aviso Legal' => 'Legal Notice',
  'Domicilio:' => 'Address:',
  'Titular:' => 'Owner:',
  'B93750271' => 'B93750271',
  'Objeto' => 'Purpose',
  'Email:' => 'Email:',
  'NIF:' => 'Tax ID:',

  // =========================================================================
  // PAGE 64 — Politica de Privacidad
  // =========================================================================
  'Los datos personales recabados a través de los formularios de contacto serán tratados con las siguientes finalidades:' => 'Personal data collected through contact forms will be processed for the following purposes:',
  'El tratamiento de sus datos se basa en el consentimiento del interesado (art. 6.1.a RGPD) y en el interés legítimo del responsable (art. 6.1.f RGPD).' => 'The processing of your data is based on the consent of the data subject (Art. 6.1.a GDPR) and on the legitimate interest of the controller (Art. 6.1.f GDPR).',
  'Puede ejercer sus derechos de acceso, rectificación, supresión, limitación, portabilidad y oposición escribiendo a info@pepejaraba.com, adjuntando copia de su DNI.' => 'You may exercise your rights of access, rectification, erasure, restriction, portability and objection by writing to info@pepejaraba.com, attaching a copy of your ID.',
  'Los datos personales se conservarán mientras exista una relación comercial o el interesado no solicite su supresión, y en todo caso conforme a los plazos legales aplicables.' => 'Personal data will be retained as long as a business relationship exists or the data subject does not request its erasure, and in any case in accordance with applicable legal deadlines.',
  'Enviar comunicaciones comerciales sobre nuestros servicios, solo si ha dado su consentimiento expreso.' => 'Send commercial communications about our services, only if you have given your express consent.',
  'Gestionar las consultas realizadas a través del formulario de contacto.' => 'Manage inquiries made through the contact form.',
  'Mejorar la experiencia de navegación del usuario.' => 'Improve the user\'s browsing experience.',
  'No se cederán datos a terceros salvo obligación legal.' => 'Data will not be shared with third parties except by legal obligation.',
  'Plataforma de Ecosistemas Digitales S.L.' => 'Plataforma de Ecosistemas Digitales S.L.',
  'Calle Héroe de Sostoa 12, 29002 Málaga' => 'Calle Héroe de Sostoa 12, 29002 Malaga',
  'Responsable del Tratamiento' => 'Data Controller',
  'Finalidad del Tratamiento' => 'Purpose of Processing',
  'Política de Privacidad' => 'Privacy Policy',
  'Derechos del Usuario' => 'User Rights',
  'Conservación de Datos' => 'Data Retention',
  'Última actualización:' => 'Last updated:',
  'Destinatarios' => 'Recipients',
  'Febrero 2026' => 'February 2026',
  'Identidad:' => 'Identity:',
  'Dirección:' => 'Address:',
  'Base Legal' => 'Legal Basis',
  'NIF:' => 'Tax ID:',

  // =========================================================================
  // PAGE 65 — Politica de Cookies
  // =========================================================================
  'Las cookies son pequeños archivos de texto que se almacenan en su dispositivo cuando visita un sitio web. Se utilizan para mejorar la experiencia de navegación y para recopilar información estadística.' => 'Cookies are small text files stored on your device when you visit a website. They are used to improve the browsing experience and to collect statistical information.',
  'Son esenciales para el funcionamiento del sitio web. Permiten la navegación y el uso de funcionalidades básicas. No requieren consentimiento.' => 'They are essential for the website to function. They enable navigation and use of basic features. They do not require consent.',
  'Nos permiten analizar el comportamiento de los usuarios de forma agregada para mejorar el sitio web. Utilizamos Google Analytics 4 con anonimización de IP.' => 'They allow us to analyze user behavior in aggregate to improve the website. We use Google Analytics 4 with IP anonymization.',
  'Almacenan las preferencias del usuario (idioma, región) para personalizar la experiencia.' => 'They store user preferences (language, region) to personalize the experience.',
  'Puede configurar su navegador para rechazar cookies o para que le avise cuando un sitio web intenta colocar una cookie. Tenga en cuenta que rechazar las cookies técnicas puede afectar al funcionamiento del sitio.' => 'You can configure your browser to reject cookies or to alert you when a website tries to place a cookie. Please note that rejecting technical cookies may affect how the site functions.',
  'Esta política de cookies puede ser actualizada periódicamente. Le recomendamos revisarla de forma regular.' => 'This cookie policy may be updated periodically. We recommend reviewing it regularly.',
  'Tipos de Cookies que Utilizamos' => 'Types of Cookies We Use',
  'Cookies Técnicas (Necesarias)' => 'Technical Cookies (Necessary)',
  'Actualización de esta Política' => 'Updates to this Policy',
  '¿Qué son las Cookies?' => 'What are Cookies?',
  'Cookies de Preferencias' => 'Preference Cookies',
  'Gestión de Cookies' => 'Cookie Management',
  'Política de Cookies' => 'Cookie Policy',
  'Cookies Analíticas' => 'Analytical Cookies',
  'Última actualización:' => 'Last updated:',
  'Febrero 2026' => 'February 2026',
];

// Titulos de paginas ES -> EN.
$titleDict = [
  'Inicio' => 'Home',
  'Manifiesto' => 'Manifesto',
  'Método Jaraba' => 'Jaraba Method',
  'Blog' => 'Blog',
  'Contacto' => 'Contact',
  'Aviso Legal' => 'Legal Notice',
  'Política de Privacidad' => 'Privacy Policy',
  'Política de Cookies' => 'Cookie Policy',
];

// =========================================================================
// FUNCIONES
// =========================================================================

function translateHtmlWithDict(string $html, array $dict): string {
  if (empty(trim($html))) {
    return $html;
  }
  // Sort by key length descending.
  uksort($dict, fn($a, $b) => mb_strlen($b) - mb_strlen($a));

  $result = preg_replace_callback(
    '/>((?:(?!<).)+)</us',
    function ($matches) use ($dict) {
      $originalText = $matches[1];
      $trimmed = trim($originalText);
      if (empty($trimmed) || mb_strlen($trimmed) < 2) {
        return $matches[0];
      }

      // Exact match.
      if (isset($dict[$trimmed])) {
        $leading = '';
        $trailing = '';
        if (preg_match('/^(\s+)/', $originalText, $m)) $leading = $m[1];
        if (preg_match('/(\s+)$/', $originalText, $m)) $trailing = $m[1];
        return '>' . $leading . $dict[$trimmed] . $trailing . '<';
      }

      // Truncated match: try matching beginning of text (regex extracted
      // up to 100 chars, but original might be longer).
      foreach ($dict as $es => $en) {
        if (mb_strlen($es) > 20 && str_starts_with($trimmed, mb_substr($es, 0, 80))) {
          // Replace the matched prefix.
          return '>' . str_replace(mb_substr($es, 0, mb_strlen($trimmed)), $en, $originalText) . '<';
        }
      }

      // Substring replacements.
      $translated = $trimmed;
      foreach ($dict as $es => $en) {
        if (str_contains($translated, $es)) {
          $translated = str_replace($es, $en, $translated);
        }
      }
      if ($translated !== $trimmed) {
        return '>' . $translated . '<';
      }

      return $matches[0];
    },
    $html
  );

  return $result ?? $html;
}

function translateCanvasWithDict(string $canvasJson, array $dict): string {
  $data = json_decode($canvasJson, TRUE);
  if (!is_array($data)) {
    return $canvasJson;
  }
  foreach (['html', 'gjs-html'] as $key) {
    if (!empty($data[$key]) && is_string($data[$key])) {
      $data[$key] = translateHtmlWithDict($data[$key], $dict);
    }
  }
  if (!empty($data['components']) && is_array($data['components'])) {
    $data['components'] = translateComponentsWithDict($data['components'], $dict);
  }
  return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function translateComponentsWithDict(array $components, array $dict): array {
  uksort($dict, fn($a, $b) => mb_strlen($b) - mb_strlen($a));
  foreach ($components as &$comp) {
    if (!empty($comp['content']) && is_string($comp['content'])) {
      $text = strip_tags($comp['content']);
      $trimmed = trim($text);
      if (isset($dict[$trimmed])) {
        $comp['content'] = ($comp['content'] !== $text)
          ? str_replace($trimmed, $dict[$trimmed], $comp['content'])
          : $dict[$trimmed];
      }
    }
    if (!empty($comp['components']) && is_array($comp['components'])) {
      $comp['components'] = translateComponentsWithDict($comp['components'], $dict);
    }
  }
  return $components;
}

// =========================================================================
// MAIN
// =========================================================================

$storage = \Drupal::entityTypeManager()->getStorage('page_content');
$pages = $storage->loadByProperties(['tenant_id' => 5]);

echo "Tenant 5 — pepejaraba.com → EN\n";
echo str_repeat('─', 60) . "\n";
echo count($pages) . " paginas\n\n";

$updated = 0;
$errors = 0;

foreach ($pages as $page) {
  $pageTitle = $page->get('title')->value ?? '';
  $pageId = $page->id();
  echo "Page #$pageId \"$pageTitle\": ";

  if (!$page->hasTranslation('en')) {
    echo "sin traduccion en (skip)\n";
    continue;
  }

  $es = $page->getUntranslated();
  $esHtml = $es->get('rendered_html')->value ?? '';
  $esCanvas = $es->get('canvas_data')->value ?? '';

  try {
    $translation = $page->getTranslation('en');

    // Title.
    $trTitle = $titleDict[$pageTitle] ?? $pageTitle;
    $translation->set('title', $trTitle);

    // Meta fields.
    foreach (['meta_title', 'meta_description'] as $metaField) {
      if ($page->hasField($metaField)) {
        $esVal = $es->get($metaField)->value ?? '';
        if (!empty($esVal)) {
          $trVal = $esVal;
          // Sort by length descending for meta field replacement too.
          $sortedDict = $dict;
          uksort($sortedDict, fn($a, $b) => mb_strlen($b) - mb_strlen($a));
          foreach ($sortedDict as $esStr => $enStr) {
            if (str_contains($trVal, $esStr)) {
              $trVal = str_replace($esStr, $enStr, $trVal);
            }
          }
          $translation->set($metaField, $trVal);
        }
      }
    }

    // rendered_html: copy from ES and translate.
    if (!empty($esHtml)) {
      $translation->set('rendered_html', translateHtmlWithDict($esHtml, $dict));
    }

    // canvas_data: copy from ES and translate.
    if (!empty($esCanvas) && $esCanvas !== '{"components":[],"styles":[],"html":"","css":""}') {
      $translation->set('canvas_data', translateCanvasWithDict($esCanvas, $dict));
    }

    $page->setSyncing(TRUE);
    $page->save();
    $page->setSyncing(FALSE);

    echo "OK\n";
    $updated++;
  }
  catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    $errors++;
  }
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "Actualizadas: $updated | Errores: $errors\n";
