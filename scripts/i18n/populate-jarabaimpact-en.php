#!/usr/bin/env php
<?php
/**
 * @file populate-jarabaimpact-en.php
 *
 * Traduce todas las paginas de jarabaimpact.com (tenant 6) a EN
 * usando diccionario completo de textos extraidos del ES.
 *
 * USO:
 *   lando drush scr scripts/i18n/populate-jarabaimpact-en.php
 */

declare(strict_types=1);

// Diccionario exhaustivo ES -> EN para jarabaimpact.com.
// Ordenado de frases largas a cortas para que el reemplazo sea correcto.
$dict = [
  // =========================================================================
  // PAGE 56 — Jaraba Impact - Infraestructura Digital
  // =========================================================================
  'Plataforma multi-tenant que sincroniza empleo, emprendimiento y comercio local con tecnología de impacto. +30 años de experiencia, +100M€ en fondos gestionados.' => 'Multi-tenant platform that synchronizes employment, entrepreneurship and local commerce with impact technology. 30+ years of experience, 100M€+ in managed funds.',
  'Infraestructura Digital para la Transformación Económica' => 'Digital Infrastructure for Economic Transformation',
  'PLATAFORMA SAAS DE IMPACTO' => 'IMPACT SAAS PLATFORM',
  'Descarga el Libro Blanco' => 'Download the White Paper',
  'Solicita una Demo' => 'Request a Demo',

  // =========================================================================
  // PAGE 66 — Plataforma
  // =========================================================================
  'Colaboración directa con la Junta de Andalucía, SEPE, universidades y entidades locales. Programas como Andalucía +ei, PIIL y NextGen EU financian la adopción masiva de la plataforma.' => 'Direct collaboration with the Junta de Andalucía, SEPE, universities and local entities. Programs such as Andalucía +ei, PIIL and NextGen EU fund mass adoption of the platform.',
  'Cinco verticales especializadas (AgroConecta, ComercioConecta, ServiciosConecta, Empleabilidad, Emprendimiento) que generan ingresos recurrentes por suscripción y transacción.' => 'Five specialized verticals (AgroConecta, ComercioConecta, ServiciosConecta, Employability, Entrepreneurship) generating recurring revenue through subscription and transaction.',
  'Red de consultores certificados en 3 niveles, franquicias territoriales con royalties, y un marketplace de expertos verificados que multiplica el alcance del ecosistema.' => 'Network of 3-level certified consultants, territorial franchises with royalties, and a verified expert marketplace that multiplies the ecosystem reach.',
  'CMS enterprise con arquitectura multi-tenant basada en Group Module. Cada tenant opera en aislamiento completo con su propio dominio, datos y configuración.' => 'Enterprise CMS with multi-tenant architecture based on Group Module. Each tenant operates in complete isolation with its own domain, data and configuration.',
  'Inteligencia artificial integrada en el core: generación de contenido, matching inteligente de empleo, diagnóstico empresarial automatizado y asistentes conversacionales.' => 'Artificial intelligence integrated at the core: content generation, smart job matching, automated business diagnostics and conversational assistants.',
  'APIs RESTful para integraciones, Server-Side Rendering para SEO/GEO óptimo, y webhooks para automatizaciones con terceros.' => 'RESTful APIs for integrations, Server-Side Rendering for optimal SEO/GEO, and webhooks for third-party automations.',
  'Infraestructura SaaS multi-tenant diseñada para escalar el impacto social y económico en territorios rurales y urbanos.' => 'Multi-tenant SaaS infrastructure designed to scale social and economic impact in rural and urban territories.',
  'Tres fuentes de ingresos complementarias que garantizan sostenibilidad financiera mientras maximizan el impacto social.' => 'Three complementary revenue streams that ensure financial sustainability while maximizing social impact.',
  'Agenda una demo personalizada y descubre cómo la infraestructura Jaraba puede transformar tu territorio.' => 'Schedule a personalized demo and discover how Jaraba infrastructure can transform your territory.',
  'Construida sobre estándares enterprise con foco en escalabilidad, seguridad y rendimiento.' => 'Built on enterprise standards with a focus on scalability, security and performance.',
  '¿Quieres Ver la Plataforma en Acción?' => 'Want to See the Platform in Action?',
  'El Modelo que Conecta Ecosistemas' => 'The Model that Connects Ecosystems',
  'Productos digitales y servicios SaaS.' => 'Digital products and SaaS services.',
  'Fondos públicos y programas subvencionados.' => 'Public funds and subsidized programs.',
  'Certificación del Método Jaraba.' => 'Jaraba Method Certification.',
  'Arquitectura Tecnológica' => 'Technology Architecture',
  'Triple Motor Económico' => 'Triple Economic Engine',
  'Drupal 11 Multi-Tenant' => 'Drupal 11 Multi-Tenant',
  'Motor Institucional' => 'Institutional Engine',
  'Motor de Licencias' => 'Licensing Engine',
  'La Plataforma' => 'The Platform',
  'API-First + SSR' => 'API-First + SSR',
  'Motor Privado' => 'Private Engine',
  'AI-Native' => 'AI-Native',

  // =========================================================================
  // PAGE 67 — Verticales SaaS
  // =========================================================================
  'Gestión agrícola inteligente. Producción, comercialización y trazabilidad para el sector agropecuario.' => 'Smart agricultural management. Production, marketing and traceability for the agricultural sector.',
  'Digitalización del comercio local. Marketplace, fidelización y pasarelas de pago integradas.' => 'Local commerce digitalization. Marketplace, loyalty programs and integrated payment gateways.',
  'Ecosistema de empleo y formación. Matching inteligente, competencias y rutas de inserción laboral.' => 'Employment and training ecosystem. Smart matching, competencies and career insertion paths.',
  'Dinamización turística territorial. Experiencias, rutas, reservas y promoción del destino.' => 'Territorial tourism promotion. Experiences, routes, bookings and destination marketing.',
  'Aceleración y acompañamiento emprendedor. Incubación, mentoring y acceso a financiación.' => 'Entrepreneurial acceleration and support. Incubation, mentoring and access to funding.',
  'Gestión de servicios profesionales. Directorio, agenda, facturación y CRM para autónomos y pymes.' => 'Professional services management. Directory, scheduling, billing and CRM for freelancers and SMEs.',
  'Equipo de expertos a tu disposición para garantizar el éxito de tu transformación digital.' => 'Expert team at your disposal to ensure the success of your digital transformation.',
  'Módulos adaptables a las necesidades específicas de tu territorio u organización.' => 'Modules adaptable to the specific needs of your territory or organization.',
  'Optimiza tu inversión con soluciones escalables que crecen con tu proyecto.' => 'Optimize your investment with scalable solutions that grow with your project.',
  'Verticales SaaS de Impacto' => 'Impact SaaS Verticals',
  'EmprendimientoConecta' => 'EmprendimientoConecta',
  'Soporte Especializado' => 'Specialized Support',
  'Eficiencia de Costes' => 'Cost Efficiency',
  'ServiciosConecta' => 'ServiciosConecta',
  'ComercioConecta' => 'ComercioConecta',
  'TurismoConecta' => 'TurismoConecta',
  'EmpleoConecta' => 'EmpleoConecta',
  'Personalizable' => 'Customizable',
  'AgroConecta' => 'AgroConecta',

  // =========================================================================
  // PAGE 68 — Impacto
  // =========================================================================
  'Más de tres décadas construyendo ecosistemas digitales que transforman vidas y territorios. Estos son nuestros números.' => 'Over three decades building digital ecosystems that transform lives and territories. These are our numbers.',
  'Personas y organizaciones reales que han transformado su realidad con el apoyo del ecosistema Jaraba.' => 'Real people and organizations who have transformed their reality with the support of the Jaraba ecosystem.',
  'Creó su propia web, dominó el marketing digital y lanzó su actividad como coach de comunicación estratégica.' => 'She built her own website, mastered digital marketing and launched her business as a strategic communication coach.',
  'Creó Camino Viejo, experiencias comunitarias en la Sierra Norte de Sevilla, con un modelo sostenible.' => 'He created Camino Viejo, community experiences in the Sierra Norte de Sevilla, with a sustainable model.',
  'Se dio de alta como autónomo, obtuvo financiación de la Junta y gestiona sus cuotas de forma independiente.' => 'He registered as a freelancer, obtained funding from the Junta and independently manages his contributions.',
  'Forma parte de una comunidad que genera resultados reales para personas y territorios.' => 'Be part of a community that generates real results for people and territories.',
  'Reinventarse profesionalmente sin experiencia como autónoma.' => 'Reinventing herself professionally without freelance experience.',
  'Programa Andalucía +ei con el Método Jaraba "sin humo".' => 'Andalucía +ei program with the "no-fluff" Jaraba Method.',
  'Lean finance y acompañamiento para minimizar inversión inicial.' => 'Lean finance and mentoring to minimize initial investment.',
  'Salir del estrés corporativo y emprender en el mundo rural.' => 'Escaping corporate stress and starting a rural business.',
  'Parálisis administrativa como barrera principal para emprender.' => 'Administrative paralysis as the main barrier to entrepreneurship.',
  'Desmitificación de trámites y apoyo paso a paso.' => 'Demystification of procedures and step-by-step support.',
  'Resultados Medibles, Historias Reales' => 'Measurable Results, Real Stories',
  'Años liderando transformación digital' => 'Years leading digital transformation',
  'Únete al Ecosistema de Impacto' => 'Join the Impact Ecosystem',
  'Ángel Martínez — Camino Viejo' => 'Ángel Martínez — Camino Viejo',
  'Fondos europeos gestionados' => 'European funds managed',
  'Tasa de éxito en proyectos' => 'Project success rate',
  'Beneficiarios directos' => 'Direct beneficiaries',
  'Nuestro Impacto' => 'Our Impact',
  'Casos de Éxito' => 'Success Stories',
  'Luis Miguel Criado' => 'Luis Miguel Criado',
  'Marcela Calabia' => 'Marcela Calabia',
  'Emprendimiento' => 'Entrepreneurship',
  'Empleabilidad' => 'Employability',
  'El resultado:' => 'The result:',
  'La solución:' => 'The solution:',
  'El reto:' => 'The challenge:',
  'Contactar' => 'Contact',
  '+50.000' => '50,000+',
  '+100M€' => '100M€+',
  'Pymes' => 'SMEs',
  '+30' => '30+',
  '98%' => '98%',

  // =========================================================================
  // PAGE 69 — Programas Institucionales
  // =========================================================================
  'Colaboramos con instituciones públicas y privadas para implementar programas de transformación digital que generan resultados medibles.' => 'We collaborate with public and private institutions to implement digital transformation programs that generate measurable results.',
  'Programa de orientación profesional e inserción laboral para personas desempleadas en Andalucía. Incluye itinerarios personalizados de empleo, emprendimiento y competencias digitales.' => 'Professional orientation and job placement program for unemployed people in Andalusia. Includes personalized itineraries for employment, entrepreneurship and digital skills.',
  'Digitalización acelerada de PYMEs con fondos NextGen EU. Implementación de soluciones digitales integrales: web, e-commerce, gestión, facturación electrónica y presencia en internet.' => 'Accelerated SME digitalization with NextGen EU funds. Implementation of comprehensive digital solutions: web, e-commerce, management, electronic billing and internet presence.',
  'Programa integral que combina formación, orientación y acompañamiento para la inserción laboral de colectivos vulnerables en zonas rurales con alta desestacionalización.' => 'Comprehensive program that combines training, orientation and support for job placement of vulnerable groups in rural areas with high seasonal variation.',
  'Formación digital práctica para autónomos rurales. Programa piloto que combina sesiones presenciales y online para dotar de competencias digitales avanzadas a profesionales independientes.' => 'Practical digital training for rural freelancers. Pilot program that combines in-person and online sessions to provide advanced digital skills to independent professionals.',
  'Diseñamos programas a medida para entidades públicas, universidades, cámaras de comercio y organizaciones del tercer sector.' => 'We design custom programs for public entities, universities, chambers of commerce and third-sector organizations.',
  'PIIL — Proyectos Integrales de Inserción Laboral' => 'PIIL — Comprehensive Job Placement Projects',
  'Programas Institucionales de Impacto' => 'Institutional Impact Programs',
  '¿Tu Institución Busca Colaborar?' => 'Is Your Institution Looking to Collaborate?',
  'Junta de Andalucía, SAE, SEPE' => 'Junta de Andalucía, SAE, SEPE',
  'Fondos Next Generation EU' => 'Next Generation EU Funds',
  'Contactar para Colaborar' => 'Contact to Collaborate',
  'Andalucía +ei' => 'Andalucía +ei',
  'En preparación' => 'In preparation',
  'Colaboradores:' => 'Partners:',
  'Autodigitales' => 'Autodigitales',
  'Financiación:' => 'Funding:',
  'Kit Digital' => 'Kit Digital',
  'Programas' => 'Programs',
  'Piloto' => 'Pilot',
  'Activo' => 'Active',

  // =========================================================================
  // PAGE 70 — Centro de Recursos
  // =========================================================================
  'Accede a la documentación técnica y estratégica del ecosistema Jaraba Impact. and success stories' => 'Access technical and strategic documentation of the Jaraba Impact ecosystem and success stories.',
  'Prensa Banking App' => 'Press Banking App',
  'Documentos y Materiales' => 'Documents and Materials',
  'CENTRO DE RECURSOS' => 'RESOURCE CENTER',
  'E-commerce Redesign' => 'E-commerce Redesign',
  'Brand Identity' => 'Brand Identity',
  'TechCorp Inc.' => 'TechCorp Inc.',
  'FinanceFirst' => 'FinanceFirst',
  'Logo Design' => 'Logo Design',
  'Development' => 'Development',
  'Whitepapers' => 'Whitepapers',
  'UX Design' => 'UX Design',
  'StartupX' => 'StartupX',
  'Fichas' => 'Fact Sheets',
  'Prensa' => 'Press',
  'UI/UX' => 'UI/UX',
  'Todos' => 'All',

  // =========================================================================
  // PAGE 71 — Contacto
  // =========================================================================
  'Estamos aquí para ayudarte a impulsar la transformación digital de tu organización o tu carrera profesional.' => 'We are here to help you drive the digital transformation of your organization or your professional career.',
  'Completa el formulario y nos pondremos en contacto contigo en menos de 24 horas laborables.' => 'Complete the form and we will contact you within 24 business hours.',
  'Para enviar tu consulta, escríbenos directamente a' => 'To send your inquiry, write directly to',
  'Hablemos de tu Proyecto' => 'Let\'s Talk About Your Project',
  'Sevilla, Andalucía, España' => 'Seville, Andalusia, Spain',
  'contacto@jarabaimpact.com' => 'contacto@jarabaimpact.com',
  'Teléfono / WhatsApp' => 'Phone / WhatsApp',
  'Formulario de Contacto' => 'Contact Form',
  'Email Institucional' => 'Institutional Email',
  '+34 623 174 304' => '+34 623 174 304',
  'o llámanos al' => 'or call us at',
  'Sede Central' => 'Headquarters',
  'Síguenos' => 'Follow Us',
  'LinkedIn' => 'LinkedIn',
  'WhatsApp' => 'WhatsApp',
  'YouTube' => 'YouTube',

  // =========================================================================
  // PAGE 73 — Inicio (Home)
  // =========================================================================
  'Plataforma SaaS multi-tenant que sincroniza empleo, emprendimiento y comercio local con tecnología de impacto. Programas avanzados y certificación oficial para consultores y entidades que lideran el cambio.' => 'Multi-tenant SaaS platform that synchronizes employment, entrepreneurship and local commerce with impact technology. Advanced programs and official certification for consultants and entities leading the change.',
  'Un modelo híbrido que sincroniza fondos públicos, productos SaaS y certificación profesional para generar impacto sostenible.' => 'A hybrid model that synchronizes public funds, SaaS products and professional certification to generate sustainable impact.',
  'Fondos públicos y programas subvencionados. Andalucía +ei, PIIL, NextGen EU. Colaboración directa con Junta de Andalucía, SEPE y universidades.' => 'Public funds and subsidized programs. Andalucía +ei, PIIL, NextGen EU. Direct collaboration with Junta de Andalucía, SEPE and universities.',
  'Productos digitales y servicios SaaS. Cinco verticales operativas que generan ingresos recurrentes: AgroConecta, ComercioConecta, ServiciosConecta y más.' => 'Digital products and SaaS services. Five operational verticals generating recurring revenue: AgroConecta, ComercioConecta, ServiciosConecta and more.',
  'Certificación del Método Jaraba para consultores. Franquicias territoriales, royalties y una red creciente de profesionales acreditados.' => 'Jaraba Method certification for consultants. Territorial franchises, royalties and a growing network of accredited professionals.',
  'Marcela Calabia, escritora y coach de comunicación estratégica, lanzó su proyecto como autónoma sin experiencia previa gracias al programa Andalucía +ei.' => 'Marcela Calabia, writer and strategic communication coach, launched her freelance project without prior experience thanks to the Andalucía +ei program.',
  'Ángel Martínez dejó el mundo corporativo para crear Camino Viejo, experiencias comunitarias en la Sierra Norte de Sevilla.' => 'Ángel Martínez left the corporate world to create Camino Viejo, community experiences in the Sierra Norte de Sevilla.',
  'Luis Miguel Criado, terapeuta manual, superó la barrera administrativa para darse de alta como autónomo y obtener financiación pública.' => 'Luis Miguel Criado, manual therapist, overcame the administrative barrier to register as a freelancer and obtain public funding.',
  'Ya seas consultor buscando certificación o una entidad que necesita soluciones de transformación digital, tenemos la infraestructura para ti.' => 'Whether you are a consultant seeking certification or an entity that needs digital transformation solutions, we have the infrastructure for you.',
  'Cinco verticales especializadas que cubren el ecosistema completo de transformación digital territorial.' => 'Five specialized verticals covering the complete territorial digital transformation ecosystem.',
  'Personas reales que han transformado su vida profesional con el ecosistema Jaraba.' => 'Real people who have transformed their professional lives with the Jaraba ecosystem.',
  'Infraestructura Digital para la Transformación que Importa' => 'Digital Infrastructure for the Transformation that Matters',
  'Del campo a tu mesa. Trazabilidad blockchain para productores locales.' => 'From field to table. Blockchain traceability for local producers.',
  'Impulsa tu comercio local. QR dinámicos y ofertas flash geolocalizadas.' => 'Boost your local commerce. Dynamic QR codes and geolocated flash deals.',
  'Profesionales de confianza. Agenda, firma digital y buzón seguro.' => 'Trusted professionals. Scheduling, digital signature and secure inbox.',
  'Conectamos talento con oportunidades. LMS + Job Board integrado.' => 'We connect talent with opportunities. Integrated LMS + Job Board.',
  'De la idea al negocio. Diagnóstico, mentoring y financiación.' => 'From idea to business. Diagnostics, mentoring and funding.',
  'De la Incertidumbre a Crear mi Propio Camino' => 'From Uncertainty to Creating My Own Path',
  'Del Estrés Corporativo al Éxito Rural' => 'From Corporate Stress to Rural Success',
  'De la Parálisis Administrativa a la Acción' => 'From Administrative Paralysis to Action',
  'Historias de Transformación y Resultados' => 'Stories of Transformation and Results',
  'Años de experiencia en transformación digital' => 'Years of experience in digital transformation',
  'Impulsa tu Próximo Gran Proyecto' => 'Drive Your Next Big Project',
  'Beneficiarios formados y acompañados' => 'Beneficiaries trained and supported',
  'Certificación para Consultores' => 'Certification for Consultants',
  'Ecosistema Digital de Impacto' => 'Digital Impact Ecosystem',
  'Resultados que Hablan por Sí Solos' => 'Results that Speak for Themselves',
  'Soluciones para Cada Sector' => 'Solutions for Every Sector',
  'Verticales SaaS operativas' => 'Operational SaaS verticals',
  'Soluciones para Entidades' => 'Solutions for Entities',
  'Conoce la Plataforma' => 'Explore the Platform',
  'Leer su Historia' => 'Read Their Story',
  'Impacto en Cifras' => 'Impact in Numbers',
  'Historias Reales' => 'Real Stories',
  'Verticales SaaS' => 'SaaS Verticals',
  'Modelo de Negocio' => 'Business Model',

  // =========================================================================
  // PAGE 74 — Certificación de Consultores
  // =========================================================================
  'Obtén el reconocimiento y la metodología para multiplicar el valor que aportas a tus clientes y acelerar tu carrera como consultor certificado.' => 'Get the recognition and methodology to multiply the value you bring to your clients and accelerate your career as a certified consultant.',
  'Quieres estandarizar tu metodología, aumentar tu credibilidad y escalar tu negocio de consultoría con un sistema probado.' => 'You want to standardize your methodology, increase your credibility and scale your consulting business with a proven system.',
  'Buscas liderar proyectos de transformación con mayor impacto y diferenciarte dentro de tu organización o en tu carrera.' => 'You want to lead transformation projects with greater impact and differentiate yourself within your organization or career.',
  'Necesitas un marco estratégico para implementar cambios efectivos y gestionar la complejidad en tu equipo o empresa.' => 'You need a strategic framework to implement effective changes and manage complexity in your team or company.',
  'Tres niveles progresivos que te llevan desde los fundamentos hasta el liderazgo en transformación de ecosistemas digitales.' => 'Three progressive levels that take you from the fundamentals to leadership in digital ecosystem transformation.',
  'Lidera la Transformación Digital con Impacto Real' => 'Lead Digital Transformation with Real Impact',
  'Contribución: artículo, webinar o caso de estudio' => 'Contribution: article, webinar or case study',
  'Mínimo 3 años de experiencia demostrable' => 'Minimum 3 years of demonstrable experience',
  'Formación avanzada en estrategia y gestión' => 'Advanced training in strategy and management',
  'Presentación de 1 caso de éxito auditado' => 'Presentation of 1 audited success story',
  'Perfil destacado y verificado en marketplace' => 'Featured and verified marketplace profile',
  'Adhesión al código ético del ecosistema' => 'Adherence to the ecosystem code of ethics',
  'La Senda del Consultor de Impacto' => 'The Path of the Impact Consultant',
  '¿Para Quién es Esta Certificación?' => 'Who Is This Certification For?',
  'En Transformación de Ecosistemas' => 'In Ecosystem Transformation',
  'Certificación oficial de Nivel Asociado' => 'Official Associate Level Certification',
  'Certificación de Nivel Certificado' => 'Certified Level Certification',
  'Acceso a leads pre-cualificados' => 'Access to pre-qualified leads',
  'Certificación de Nivel Asociado' => 'Associate Level Certification',
  'Acceso a comunidad de consultores' => 'Access to consultant community',
  'Directivos y Líderes de Proyecto' => 'Executives and Project Leaders',
  'Consultores Senior / Managers' => 'Senior Consultants / Managers',
  'Programa Oficial Jaraba Impact' => 'Official Jaraba Impact Program',
  'Perfil básico en el marketplace' => 'Basic profile on the marketplace',
  'Superar examen de conocimientos' => 'Pass knowledge exam',
  'Completar curso formativo base' => 'Complete base training course',
  'Consultores Independientes' => 'Independent Consultants',
  'Programa de Certificación' => 'Certification Program',
  'Sello "Consultor Certificado"' => '"Certified Consultant" badge',
  'Lidera la Transformación' => 'Lead the Transformation',
  'En Ecosistemas Digitales' => 'In Digital Ecosystems',
  'Domina los Fundamentos' => 'Master the Fundamentals',
  'Solicitar Información' => 'Request Information',
  'En Estrategia Digital' => 'In Digital Strategy',
  'Aplica la Estrategia' => 'Apply the Strategy',
  'Nivel Certificado' => 'Certified Level',
  'Nivel Asociado' => 'Associate Level',
  'Nivel Master' => 'Master Level',
  'Requisitos' => 'Requirements',
  'Beneficios' => 'Benefits',

  // =========================================================================
  // PAGE 75 — Soluciones para Entidades
  // =========================================================================
  'Infraestructura Digital a Medida' => 'Custom Digital Infrastructure',
  'Soluciones para Entidades' => 'Solutions for Entities',

  // =========================================================================
  // PAGE 75 (legal) — Aviso Legal
  // =========================================================================
  'El presente sitio web tiene como objeto facilitar información sobre los servicios de consultoría, certificación profesional y transformación digital que ofrece Jaraba Impact, marca comercial de Plataforma de Ecosistemas Digitales S.L.' => 'This website aims to provide information about the consulting, professional certification and digital transformation services offered by Jaraba Impact, a brand of Plataforma de Ecosistemas Digitales S.L.',
  'Todos los contenidos del sitio web, incluyendo textos, imágenes, logotipos, iconos, software y demás material, están protegidos por las leyes de propiedad intelectual e industrial. Queda prohibida su reproducción, distribución o transformación sin autorización expresa.' => 'All website content, including texts, images, logos, icons, software and other materials, are protected by intellectual and industrial property laws. Reproduction, distribution or transformation without express authorization is prohibited.',
  'Jaraba Impact no se hace responsable de los daños o perjuicios que pudieran derivarse del acceso o uso de este sitio web. Nos reservamos el derecho de modificar, suspender o eliminar cualquier contenido o servicio sin previo aviso.' => 'Jaraba Impact is not responsible for any damages that may arise from accessing or using this website. We reserve the right to modify, suspend or remove any content or service without prior notice.',
  'El presente aviso legal se rige por la legislación española. Para cualquier controversia que pudiera derivarse del acceso o uso de este sitio web, las partes se someten a la jurisdicción de los Juzgados y Tribunales de Sevilla.' => 'This legal notice is governed by Spanish law. For any dispute arising from accessing or using this website, the parties submit to the jurisdiction of the Courts of Seville.',
  'En cumplimiento del artículo 10 de la Ley 34/2002, de 11 de julio, de Servicios de la Sociedad de la Información y del Comercio Electrónico, se informa que el titular de este sitio web es:' => 'In compliance with Article 10 of Law 34/2002, of July 11, on Information Society and Electronic Commerce Services, we inform that the owner of this website is:',
  'Plataforma de Ecosistemas Digitales S.L.' => 'Plataforma de Ecosistemas Digitales S.L.',
  '3. Propiedad Intelectual' => '3. Intellectual Property',
  '5. Legislación Aplicable' => '5. Applicable Legislation',
  '1. Datos Identificativos' => '1. Identification Data',
  '4. Responsabilidad' => '4. Liability',
  'Domicilio Social:' => 'Registered Office:',
  'Razón Social:' => 'Company Name:',
  'Aviso Legal' => 'Legal Notice',
  'Sitio Web:' => 'Website:',
  '2. Objeto' => '2. Purpose',
  'Email:' => 'Email:',

  // =========================================================================
  // PAGE 76 — Política de Privacidad
  // =========================================================================
  'Los datos se utilizan exclusivamente para responder a tus consultas, gestionar solicitudes de información sobre programas de certificación y soluciones institucionales, y mantener comunicaciones comerciales si has dado tu consentimiento expreso.' => 'Data is used exclusively to respond to your inquiries, manage information requests about certification programs and institutional solutions, and maintain commercial communications if you have given your express consent.',
  'Plataforma de Ecosistemas Digitales S.L. es responsable del tratamiento de los datos personales recogidos a través de este sitio web, en cumplimiento del Reglamento General de Protección de Datos (RGPD) y la Ley Orgánica 3/2018 de Protección de Datos Personales.' => 'Plataforma de Ecosistemas Digitales S.L. is responsible for the processing of personal data collected through this website, in compliance with the General Data Protection Regulation (GDPR) and Organic Law 3/2018 on Personal Data Protection.',
  'Recogemos únicamente los datos que nos facilitas voluntariamente a través de formularios de contacto: nombre, email, organización y mensaje. No recogemos datos sensibles ni realizamos perfilado automatizado.' => 'We collect only the data you voluntarily provide through contact forms: name, email, organization and message. We do not collect sensitive data nor perform automated profiling.',
  'Los datos se conservan durante el tiempo necesario para cumplir con la finalidad para la que fueron recogidos y para determinar posibles responsabilidades derivadas de dicha finalidad.' => 'Data is retained for the time necessary to fulfill the purpose for which it was collected and to determine possible liabilities arising from said purpose.',
  'Puedes ejercer tus derechos de acceso, rectificación, supresión, portabilidad, limitación y oposición escribiendo a' => 'You may exercise your rights of access, rectification, erasure, portability, restriction and objection by writing to',
  '1. Responsable del Tratamiento' => '1. Data Controller',
  'Política de Privacidad' => 'Privacy Policy',
  'Última actualización:' => 'Last updated:',
  '2. Datos Recogidos' => '2. Data Collected',
  '5. Conservación' => '5. Data Retention',
  'Febrero 2026' => 'February 2026',
  '3. Finalidad' => '3. Purpose',
  '4. Derechos' => '4. Rights',

  // =========================================================================
  // PAGE 77 — Política de Cookies
  // =========================================================================
  'Las cookies son pequeños archivos de texto que se almacenan en tu dispositivo cuando visitas un sitio web. Permiten que el sitio recuerde tus preferencias y mejore tu experiencia de navegación.' => 'Cookies are small text files stored on your device when you visit a website. They allow the site to remember your preferences and improve your browsing experience.',
  'Este sitio web utiliza exclusivamente cookies técnicas necesarias para el funcionamiento del sitio. No utilizamos cookies de seguimiento publicitario ni compartimos datos con terceros a través de cookies.' => 'This website uses exclusively technical cookies necessary for the site\'s operation. We do not use advertising tracking cookies nor share data with third parties through cookies.',
  'Puedes configurar tu navegador para rechazar cookies o para que te avise cuando se envíen. Ten en cuenta que si desactivas las cookies, algunas funcionalidades del sitio podrían verse afectadas.' => 'You can configure your browser to reject cookies or to alert you when they are sent. Please note that if you disable cookies, some site features may be affected.',
  'Para cualquier consulta relacionada con nuestra política de cookies, contacta con nosotros en' => 'For any inquiries related to our cookie policy, contact us at',
  '1. ¿Qué son las Cookies?' => '1. What Are Cookies?',
  'Política de Cookies' => 'Cookie Policy',
  '2. Cookies Utilizadas' => '2. Cookies Used',
  '3. Gestión de Cookies' => '3. Cookie Management',
  '4. Contacto' => '4. Contact',
];

// Titulos de paginas ES -> EN.
$titleDict = [
  'Jaraba Impact - Infraestructura Digital para la Transformación Económica' => 'Jaraba Impact - Digital Infrastructure for Economic Transformation',
  'Verticales SaaS - Soluciones para Cada Sector' => 'SaaS Verticals - Solutions for Every Sector',
  'Centro de Recursos Jaraba Impact' => 'Jaraba Impact Resource Center',
  'Certificación de Consultores' => 'Consultant Certification',
  'Programas Institucionales' => 'Institutional Programs',
  'Política de Privacidad' => 'Privacy Policy',
  'Política de Cookies' => 'Cookie Policy',
  'Aviso Legal' => 'Legal Notice',
  'Plataforma' => 'Platform',
  'Contacto' => 'Contact',
  'Impacto' => 'Impact',
  'Inicio' => 'Home',
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
$pages = $storage->loadByProperties(['tenant_id' => 6]);

echo "Tenant 6 — jarabaimpact.com → EN\n";
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
