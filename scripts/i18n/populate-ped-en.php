#!/usr/bin/env php
<?php
/**
 * @file populate-ped-en.php
 *
 * Traduce todas las paginas de plataformadeecosistemas.es (tenant 7) a EN
 * usando diccionario completo de textos extraidos del ES.
 *
 * USO:
 *   lando drush scr scripts/i18n/populate-ped-en.php
 */

declare(strict_types=1);

// Diccionario exhaustivo ES -> EN para plataformadeecosistemas.es.
// Ordenado de frases largas a cortas para que el reemplazo sea correcto.
$dict = [
  // =========================================================================
  // PAGE 78 — Inicio
  // =========================================================================
  'Plataforma de Ecosistemas Digitales S.L. opera el Ecosistema Jaraba: tecnologia de impacto al servicio de personas, pymes y municipios' => 'Plataforma de Ecosistemas Digitales S.L. operates the Jaraba Ecosystem: impact technology at the service of people, SMEs and municipalities',
  'Infraestructura Digital para el Desarrollo Rural y la Transformacion de Territorios' => 'Digital Infrastructure for Rural Development and Territorial Transformation',
  'Modelo de sostenibilidad basado en tres fuentes de ingresos complementarias' => 'Sustainability model based on three complementary revenue streams',
  'Fondos publicos, programas subvencionados y acuerdos con administraciones' => 'Public funds, subsidized programs and government agreements',
  'Entidades y organismos que colaboran con el Ecosistema Jaraba' => 'Entities and organizations that collaborate with the Jaraba Ecosystem',
  'Certificacion Metodo Jaraba, franquicias territoriales y formacion' => 'Jaraba Method certification, territorial franchises and training',
  'Productos digitales, servicios SaaS y soluciones para pymes' => 'Digital products, SaaS services and SME solutions',
  'Transparencia financiera y metricas de impacto' => 'Financial transparency and impact metrics',
  'Colaboracion, programas y alianzas estrategicas' => 'Collaboration, programs and strategic alliances',
  'Notas de prensa, datos y recursos para medios' => 'Press releases, data and media resources',
  'Oportunidades profesionales y colaboracion' => 'Professional opportunities and collaboration',
  'anos de experiencia en transformacion digital' => 'years of experience in digital transformation',
  'euros gestionados en fondos europeos' => 'euros managed in European funds',
  'verticales SaaS operativas' => 'operational SaaS verticals',
  'Conoce nuestro impacto' => 'Discover our impact',
  'Ver arquitectura completa' => 'View full architecture',
  'Contacto institucional' => 'Institutional contact',
  'Triple Motor Economico' => 'Triple Economic Engine',
  'Partners Institucionales' => 'Institutional Partners',
  'Soy Institucion Publica' => 'I\'m a Public Institution',
  'Quiero Trabajar Aqui' => 'I Want to Work Here',
  'beneficiarios directos' => 'direct beneficiaries',
  'Acceso por perfil' => 'Access by profile',
  'Motor Institucional' => 'Institutional Engine',
  'Motor de Licencias' => 'Licensing Engine',
  'Junta de Andalucia' => 'Junta de Andaluc\u00eda',
  'Motor de Mercado' => 'Market Engine',
  'Cifras Clave' => 'Key Figures',
  'Universidades' => 'Universities',
  'Ayuntamientos' => 'Town Halls',
  'Soy Inversor' => 'I\'m an Investor',
  'Soy Prensa' => 'I\'m Press',
  '+50.000' => '50,000+',
  'FUNDAE' => 'FUNDAE',
  '+100M' => '100M+',
  'SEPE' => 'SEPE',
  '+30' => '30+',

  // =========================================================================
  // PAGE 79 — Contacto
  // =========================================================================
  'Conecta con Plataforma de Ecosistemas Digitales S.L. para informacion institucional, prensa, partners, inversores o empleo' => 'Connect with Plataforma de Ecosistemas Digitales S.L. for institutional information, press, partners, investors or careers',
  'Contacto Institucional' => 'Institutional Contact',
  'Canales especializados' => 'Specialized channels',
  'Partners e instituciones' => 'Partners and institutions',
  'Informacion general' => 'General information',
  'Soporte tecnico' => 'Technical support',
  'Tipo de consulta' => 'Inquiry type',
  'Prensa y medios' => 'Press and media',
  '29002 M\u00e1laga' => '29002 Malaga',
  'Direccion' => 'Address',
  'Inversores' => 'Investors',
  'Respuesta' => 'Response',
  'Contacto' => 'Contact',
  'Telefono' => 'Phone',
  '48 horas' => '48 hours',
  '24 horas' => '24 hours',
  '72 horas' => '72 hours',
  '1 semana' => '1 week',
  'Empleo' => 'Careers',
  'Online' => 'Online',
  'Email' => 'Email',

  // =========================================================================
  // PAGE 80 — Aviso Legal
  // =========================================================================
  'En cumplimiento de la Ley 34/2002, de 11 de julio, de Servicios de la Sociedad de la Informacion y Comercio Electronico (LSSI-CE), se informa de los siguientes datos:' => 'In compliance with Law 34/2002, of July 11, on Information Society and Electronic Commerce Services (LSSI-CE), the following data is provided:',
  'Todos los contenidos de este sitio web, incluyendo textos, imagenes, logotipos, iconos, software y cualquier otro material, son propiedad de Plataforma de Ecosistemas Digitales S.L. o de sus licenciantes, y estan protegidos por la legislacion nacional e internacional de propiedad intelectual e industrial.' => 'All content on this website, including texts, images, logos, icons, software and any other material, is the property of Plataforma de Ecosistemas Digitales S.L. or its licensors, and is protected by national and international intellectual and industrial property law.',
  'Plataforma de Ecosistemas Digitales S.L. no se hace responsable de los danos o perjuicios que pudieran derivarse del acceso o uso del sitio web, incluyendo danos informaticos o la introduccion de virus.' => 'Plataforma de Ecosistemas Digitales S.L. is not responsible for any damages that may arise from access or use of the website, including computer damage or the introduction of viruses.',
  'El acceso y uso de este sitio web atribuye la condicion de usuario e implica la aceptacion plena de estas condiciones. El usuario se compromete a hacer un uso adecuado de los contenidos y servicios ofrecidos.' => 'Access and use of this website grants user status and implies full acceptance of these terms. The user agrees to make appropriate use of the content and services offered.',
  'Las presentes condiciones se rigen por la legislacion espanola. Para cualquier controversia, las partes se someten a los Juzgados y Tribunales de Granada.' => 'These terms are governed by Spanish law. For any dispute, the parties submit to the Courts of Granada.',
  'Inscrita en el Registro Mercantil de M\u00e1laga' => 'Registered at the Malaga Trade Registry',
  'Calle H\u00e9roe de Sostoa 12, 29002 M\u00e1laga' => 'Calle H\u00e9roe de Sostoa 12, 29002 Malaga',
  'Plataforma de Ecosistemas Digitales S.L.' => 'Plataforma de Ecosistemas Digitales S.L.',
  '4. Limitacion de responsabilidad' => '4. Limitation of liability',
  '1. Datos identificativos' => '1. Identification data',
  '3. Propiedad intelectual' => '3. Intellectual property',
  '2. Condiciones de uso' => '2. Terms of use',
  'Registro Mercantil' => 'Trade Registry',
  '5. Jurisdiccion' => '5. Jurisdiction',
  'Aviso Legal' => 'Legal Notice',
  'B93750271' => 'B93750271',
  'Domicilio' => 'Address',
  'Titular' => 'Owner',
  'CIF' => 'Tax ID',

  // =========================================================================
  // PAGE 81 — Politica de Privacidad
  // =========================================================================
  'Los datos personales recogidos seran tratados con las siguientes finalidades: gestion de consultas e informacion, gestion de relaciones institucionales, comunicaciones comerciales (con consentimiento previo), y cumplimiento de obligaciones legales.' => 'Personal data collected will be processed for the following purposes: management of inquiries and information, management of institutional relations, commercial communications (with prior consent), and compliance with legal obligations.',
  'El tratamiento de datos se realiza en base a: consentimiento del interesado (art. 6.1.a RGPD), ejecucion de un contrato (art. 6.1.b), cumplimiento de una obligacion legal (art. 6.1.c), e interes legitimo del responsable (art. 6.1.f).' => 'Data processing is carried out based on: consent of the data subject (Art. 6.1.a GDPR), performance of a contract (Art. 6.1.b), compliance with a legal obligation (Art. 6.1.c), and legitimate interest of the controller (Art. 6.1.f).',
  'Puede ejercer sus derechos de acceso, rectificacion, cancelacion, oposicion, portabilidad, olvido y limitacion del tratamiento enviando un escrito a privacidad@plataformadeecosistemas.es o a la direccion postal indicada, acompanando copia de su documento de identidad.' => 'You may exercise your rights of access, rectification, cancellation, objection, portability, erasure and restriction of processing by writing to privacidad@plataformadeecosistemas.es or to the postal address indicated, attaching a copy of your identity document.',
  'Los datos personales se conservaran mientras se mantenga la relacion o durante los anos necesarios para cumplir con las obligaciones legales.' => 'Personal data will be retained for as long as the relationship is maintained or for the years necessary to comply with legal obligations.',
  '2. Finalidades del tratamiento' => '2. Purposes of processing',
  '4. Derechos de los interesados' => '4. Data subject rights',
  '1. Responsable del tratamiento' => '1. Data controller',
  'Politica de Privacidad' => 'Privacy Policy',
  '5. Conservacion de datos' => '5. Data retention',
  '3. Base juridica' => '3. Legal basis',
  'Direccion:' => 'Address:',
  'Identidad:' => 'Identity:',
  'Email:' => 'Email:',
  'CIF:' => 'Tax ID:',

  // =========================================================================
  // PAGE 82 — Politica de Cookies
  // =========================================================================
  'Las cookies son pequenos archivos de texto que se almacenan en su dispositivo al visitar un sitio web. Se utilizan para mejorar la experiencia de navegacion y ofrecer servicios personalizados.' => 'Cookies are small text files stored on your device when you visit a website. They are used to improve the browsing experience and offer personalized services.',
  'Puede configurar su navegador para aceptar, rechazar o eliminar cookies. Tenga en cuenta que desactivar las cookies tecnicas puede afectar al funcionamiento del sitio web.' => 'You can configure your browser to accept, reject or delete cookies. Please note that disabling technical cookies may affect website functionality.',
  'Esta politica de cookies puede ser actualizada para adaptarla a cambios normativos o tecnologicos. Le recomendamos revisarla periodicamente.' => 'This cookie policy may be updated to adapt to regulatory or technological changes. We recommend reviewing it periodically.',
  'Recordar configuracion del usuario' => 'Remember user settings',
  'Estadisticas de uso anonimas' => 'Anonymous usage statistics',
  'Funcionamiento basico del sitio' => 'Basic site functionality',
  '4. Actualizacion de la politica' => '4. Policy updates',
  '2. Tipos de cookies utilizadas' => '2. Types of cookies used',
  '1. Que son las cookies' => '1. What are cookies',
  '3. Gestion de cookies' => '3. Cookie management',
  'Politica de Cookies' => 'Cookie Policy',
  'Preferencias' => 'Preferences',
  'Analiticas' => 'Analytical',
  'Finalidad' => 'Purpose',
  'Duracion' => 'Duration',
  'Tecnicas' => 'Technical',
  'Sesion' => 'Session',
  '2 anos' => '2 years',
  '1 ano' => '1 year',
  'Tipo' => 'Type',

  // =========================================================================
  // PAGE 83 — Empresa
  // =========================================================================
  'Plataforma de Ecosistemas Digitales S.L. (PED) es la empresa que opera el Ecosistema Jaraba, una infraestructura digital de impacto destinada a la transformacion economica y social de personas, pymes y municipios, con especial foco en el mundo rural y territorios con brecha digital.' => 'Plataforma de Ecosistemas Digitales S.L. (PED) is the company that operates the Jaraba Ecosystem, a digital impact infrastructure aimed at the economic and social transformation of people, SMEs and municipalities, with special focus on rural areas and territories with a digital divide.',
  'Co-fundada por Jos\u00e9 Jaraba Mu\u00f1oz (Pepe Jaraba), PED integra mas de tres decadas de experiencia en desarrollo rural, formacion profesional, transformacion digital y gestion de fondos europeos en una plataforma SaaS multi-vertical.' => 'Co-founded by Jos\u00e9 Jaraba Mu\u00f1oz (Pepe Jaraba), PED integrates over three decades of experience in rural development, professional training, digital transformation and European fund management into a multi-vertical SaaS platform.',
  'Democratizar el acceso a la transformacion digital para personas, pymes y territorios rurales, proporcionando herramientas tecnologicas accesibles y programas de capacitacion adaptados a cada contexto.' => 'Democratize access to digital transformation for people, SMEs and rural territories, providing accessible technological tools and training programs adapted to each context.',
  'Ser la infraestructura digital de referencia para el desarrollo rural sostenible en el mundo hispanohablante, conectando instituciones, empresas y personas en un ecosistema de impacto medible.' => 'To be the leading digital infrastructure for sustainable rural development in the Spanish-speaking world, connecting institutions, businesses and people in a measurable impact ecosystem.',
  'Tecnologia al servicio de todos, adaptada a contextos rurales y de baja alfabetizacion digital.' => 'Technology at the service of everyone, adapted to rural contexts and low digital literacy.',
  'El valor se genera en red. Consultores, instituciones y beneficiarios forman un ecosistema vivo.' => 'Value is generated through networks. Consultants, institutions and beneficiaries form a living ecosystem.',
  'Transparencia radical. Datos verificables, metricas reales, resultados medibles.' => 'Radical transparency. Verifiable data, real metrics, measurable results.',
  'Desarrollo y explotacion de plataformas digitales para la transformacion economica territorial' => 'Development and operation of digital platforms for territorial economic transformation',
  'Mas de 30 anos construyendo infraestructura digital para personas, pymes y territorios' => 'Over 30 years building digital infrastructure for people, SMEs and territories',
  'Cada accion tiene un indicador. Si no se mide, no existe.' => 'Every action has an indicator. If it\'s not measured, it doesn\'t exist.',
  'Mision, Vision y Valores' => 'Mission, Vision and Values',
  'Jos\u00e9 Jaraba Mu\u00f1oz' => 'Jos\u00e9 Jaraba Mu\u00f1oz',
  'Representante Legal' => 'Legal Representative',
  'Impacto Medible' => 'Measurable Impact',
  'Datos Societarios' => 'Corporate Data',
  'Domicilio Social' => 'Registered Office',
  'Conoce al equipo' => 'Meet the team',
  'Sobre Nosotros' => 'About Us',
  'Quienes Somos' => 'Who We Are',
  'Objeto Social' => 'Corporate Purpose',
  'Razon Social' => 'Company Name',
  'Accesibilidad' => 'Accessibility',
  'Sin Humo' => 'No Fluff',
  'Comunidad' => 'Community',
  'Mision' => 'Mission',
  'Vision' => 'Vision',

  // =========================================================================
  // PAGE 84 — Ecosistema
  // =========================================================================
  'El Ecosistema Jaraba opera bajo un modelo de Triple Motor Economico que garantiza la sostenibilidad a largo plazo combinando fuentes de ingresos publicas, privadas y de propiedad intelectual.' => 'The Jaraba Ecosystem operates under a Triple Economic Engine model that ensures long-term sustainability by combining public, private and intellectual property revenue streams.',
  'Triple Motor Economico y 5 verticales SaaS para la transformacion digital de territorios' => 'Triple Economic Engine and 5 SaaS verticals for territorial digital transformation',
  'Plataforma de servicios profesionales para pymes rurales: consultoria, marketing, contabilidad y legal.' => 'Professional services platform for rural SMEs: consulting, marketing, accounting and legal.',
  'Marketplace agroalimentario, trazabilidad, certificacion de calidad y comercio directo productor-consumidor.' => 'Agri-food marketplace, traceability, quality certification and direct producer-consumer trade.',
  'Formacion, orientacion laboral y conexion con empresas. Programas SEPE, FUNDAE y formacion propia.' => 'Training, career guidance and business connections. SEPE, FUNDAE and proprietary training programs.',
  'Digitalizacion del comercio local, tiendas online, pagos y logistica de ultima milla.' => 'Local commerce digitalization, online stores, payments and last-mile logistics.',
  'Incubacion, aceleracion y financiacion para nuevos negocios rurales y digitales.' => 'Incubation, acceleration and funding for new rural and digital businesses.',
  'Fondos publicos, programas subvencionados, convenios con administraciones y fondos europeos.' => 'Public funds, subsidized programs, government agreements and European funds.',
  'Certificacion Metodo Jaraba, franquicias territoriales, formacion de consultores.' => 'Jaraba Method certification, territorial franchises, consultant training.',
  'Suscripciones SaaS, servicios de consultoria digital, marketplace y soluciones B2B.' => 'SaaS subscriptions, digital consulting services, marketplace and B2B solutions.',
  'Modelo de Sostenibilidad' => 'Sustainability Model',
  'El Ecosistema Jaraba' => 'The Jaraba Ecosystem',
  'Motor Institucional (30%)' => 'Institutional Engine (30%)',
  'Motor de Licencias (30%)' => 'Licensing Engine (30%)',
  'Motor de Mercado (40%)' => 'Market Engine (40%)',
  'Las 5 Verticales' => 'The 5 Verticals',
  'ServiciosConecta' => 'ServiciosConecta',
  'ComercioConecta' => 'ComercioConecta',
  'Empleabilidad' => 'Employability',
  'Emprendimiento' => 'Entrepreneurship',
  'AgroConecta' => 'AgroConecta',

  // =========================================================================
  // PAGE 85 — Impacto
  // =========================================================================
  'Nuestras acciones contribuyen directamente a los Objetivos de Desarrollo Sostenible de Naciones Unidas:' => 'Our actions directly contribute to the United Nations Sustainable Development Goals:',
  'Metricas verificables de impacto: personas formadas, empleos generados, negocios digitalizados' => 'Verifiable impact metrics: people trained, jobs generated, businesses digitalized',
  'Educacion de calidad: formacion profesional accesible' => 'Quality education: accessible professional training',
  'Trabajo decente y crecimiento economico' => 'Decent work and economic growth',
  'Industria, innovacion e infraestructura' => 'Industry, innovation and infrastructure',
  'Reduccion de desigualdades territoriales' => 'Reduction of territorial inequalities',
  'Ciudades y comunidades sostenibles' => 'Sustainable cities and communities',
  'Alineacion con los ODS' => 'Alignment with the SDGs',
  'Ver transparencia completa' => 'View full transparency',
  'municipios impactados' => 'municipalities impacted',
  'pymes digitalizadas' => 'SMEs digitalized',
  'empleos facilitados' => 'jobs facilitated',
  'personas formadas' => 'people trained',
  'Impacto Social' => 'Social Impact',
  '+15.000' => '15,000+',
  '+3.200' => '3,200+',
  'ODS 4' => 'SDG 4',
  'ODS 8' => 'SDG 8',
  'ODS 9' => 'SDG 9',
  'ODS 10' => 'SDG 10',
  'ODS 11' => 'SDG 11',
  '+800' => '800+',
  '+120' => '120+',

  // =========================================================================
  // PAGE 86 — Partners
  // =========================================================================
  'Red de partners institucionales y tecnologicos del Ecosistema Jaraba' => 'Network of institutional and technology partners of the Jaraba Ecosystem',
  'Fundacion Estatal para la Formacion en el Empleo' => 'National Foundation for Employment Training',
  'Colaboracion en programas de empleo y formacion' => 'Collaboration in employment and training programs',
  'Servicio Publico de Empleo Estatal' => 'National Public Employment Service',
  'Quieres ser partner?' => 'Want to become a partner?',
  'Partners Tecnologicos' => 'Technology Partners',
  'Partners y Alianzas' => 'Partners and Alliances',
  'Google Cloud' => 'Google Cloud',
  'Cloudflare' => 'Cloudflare',
  'Stripe' => 'Stripe',
  'IONOS' => 'IONOS',

  // =========================================================================
  // PAGE 87 — Equipo Directivo
  // =========================================================================
  'Con mas de 30 anos de experiencia en desarrollo rural, formacion profesional y transformacion digital, Pepe Jaraba ha liderado proyectos de impacto en mas de 120 municipios de Andalucia. Su trayectoria incluye la gestion de mas de 100 millones de euros en fondos europeos y la creacion de programas formativos que han beneficiado a mas de 50.000 personas.' => 'With over 30 years of experience in rural development, professional training and digital transformation, Pepe Jaraba has led impact projects in over 120 municipalities across Andalusia. His track record includes managing over 100 million euros in European funds and creating training programs that have benefited over 50,000 people.',
  'Experto en metodologias de intervencion territorial, ha desarrollado el Metodo Jaraba, un marco sistematico para la transformacion economica de territorios rurales que combina formacion, emprendimiento, digitalizacion y conexion con mercados.' => 'An expert in territorial intervention methodologies, he has developed the Jaraba Method, a systematic framework for the economic transformation of rural territories that combines training, entrepreneurship, digitalization and market connections.',
  'Las personas que lideran la transformacion digital del mundo rural' => 'The people leading the digital transformation of rural communities',
  'Arquitectura SaaS y desarrollo' => 'SaaS architecture and development',
  'Gestion de programas y verticales' => 'Program and vertical management',
  'Medicion y reporting ESG' => 'ESG measurement and reporting',
  'Director Tecnologia' => 'Technology Director',
  'Director Operaciones' => 'Operations Director',
  'Equipo Directivo' => 'Executive Team',
  'Directora Impacto' => 'Impact Director',
  'Quieres unirte?' => 'Want to join us?',
  'Fundador y CEO' => 'Founder and CEO',

  // =========================================================================
  // PAGE 88 — Transparencia
  // =========================================================================
  'Plataforma de Ecosistemas Digitales S.L. es una sociedad limitada constituida conforme a la legislacion espanola, con domicilio social en M\u00e1laga. La empresa esta inscrita en el Registro Mercantil de M\u00e1laga y opera bajo la supervision de los organos reguladores correspondientes.' => 'Plataforma de Ecosistemas Digitales S.L. is a limited liability company incorporated under Spanish law, with registered office in Malaga. The company is registered at the Malaga Trade Registry and operates under the supervision of the corresponding regulatory bodies.',
  'Informacion societaria, financiera y de compliance para inversores y administraciones' => 'Corporate, financial and compliance information for investors and administrations',
  'Cumplimiento RGPD y LOPDGDD. Delegado de Proteccion de Datos designado.' => 'GDPR and LOPDGDD compliance. Data Protection Officer appointed.',
  'Politicas de integridad, anticorrupcion y conflicto de intereses.' => 'Integrity, anti-corruption and conflict of interest policies.',
  'Infraestructura en UE, cifrado SSL/TLS, backups automaticos.' => 'EU infrastructure, SSL/TLS encryption, automatic backups.',
  'Mecanismo confidencial conforme a la Ley 2/2023.' => 'Confidential mechanism in accordance with Law 2/2023.',
  'Certificaciones, franquicias, formacion' => 'Certifications, franchises, training',
  'Fondos publicos, subvenciones, convenios' => 'Public funds, grants, agreements',
  'Transparencia Corporativa' => 'Corporate Transparency',
  'Informacion Societaria' => 'Corporate Information',
  'SaaS, servicios, marketplace' => 'SaaS, services, marketplace',
  'Proteccion de Datos' => 'Data Protection',
  'Canal de Denuncias' => 'Whistleblowing Channel',
  'Modelo de Ingresos' => 'Revenue Model',
  'Codigo Etico' => 'Code of Ethics',
  'Descripcion' => 'Description',
  'Porcentaje' => 'Percentage',
  'Compliance' => 'Compliance',
  'Seguridad' => 'Security',
  'Fuente' => 'Source',

  // =========================================================================
  // PAGE 89 — Certificaciones
  // =========================================================================
  'Proteccion de datos conforme al Reglamento General de Proteccion de Datos europeo.' => 'Data protection in compliance with the European General Data Protection Regulation.',
  'Homologaciones y certificaciones de calidad que avalan nuestra actividad' => 'Quality certifications and accreditations that endorse our activity',
  'Homologacion para impartir certificados de profesionalidad y formacion para el empleo.' => 'Accreditation to deliver professional certificates and employment training.',
  'Compromiso de accesibilidad web conforme a las pautas internacionales.' => 'Web accessibility commitment in accordance with international guidelines.',
  'Acreditacion para gestionar formacion bonificada para empresas.' => 'Accreditation to manage subsidized training for companies.',
  'Certificacion para consultores' => 'Certification for consultants',
  'Accesibilidad WCAG 2.1 AA' => 'WCAG 2.1 AA Accessibility',
  'Certificaciones Obtenidas' => 'Certifications Obtained',
  'Centro SEPE Homologado' => 'SEPE Accredited Center',
  'Cumplimiento RGPD' => 'GDPR Compliance',
  'Entidad FUNDAE' => 'FUNDAE Entity',
  'Certificaciones' => 'Certifications',

  // =========================================================================
  // PAGE 90 — Prensa
  // =========================================================================
  'Disponemos de un kit de prensa completo con logotipos, fotografias corporativas, biografias del equipo directivo, ficha tecnica de la empresa y guia de marca. Para solicitar acceso al press kit, contacte con el departamento de comunicacion.' => 'We have a complete press kit with logos, corporate photos, executive team biographies, company fact sheet and brand guidelines. To request access to the press kit, contact the communications department.',
  'Las notas de prensa y comunicados oficiales se publicaran en esta seccion. Para recibir alertas de prensa, contacte con prensa@plataformadeecosistemas.es' => 'Press releases and official communications will be published in this section. To receive press alerts, contact prensa@plataformadeecosistemas.es',
  'Recursos de prensa, notas oficiales y contacto para medios de comunicacion' => 'Press resources, official releases and media contact',
  'Tecnologia / Impacto Social' => 'Technology / Social Impact',
  'Tiempo de respuesta: 24 horas' => 'Response time: 24 hours',
  'Contacto de Prensa' => 'Press Contact',
  'Notas de Prensa' => 'Press Releases',
  'Sala de Prensa' => 'Press Room',
  'M\u00e1laga, Espa\u00f1a' => 'Malaga, Spain',
  'Datos Rapidos' => 'Quick Facts',
  'Press Kit' => 'Press Kit',
  'Fundacion:' => 'Founded:',
  'Fundador:' => 'Founder:',
  'Sector:' => 'Sector:',
  'Sede:' => 'Headquarters:',
  '2020' => '2020',
];

// Titulos de paginas ES -> EN.
$titleDict = [
  'Inicio' => 'Home',
  'Contacto' => 'Contact',
  'Aviso Legal' => 'Legal Notice',
  'Politica de Privacidad' => 'Privacy Policy',
  'Politica de Cookies' => 'Cookie Policy',
  'Empresa' => 'Company',
  'Ecosistema' => 'Ecosystem',
  'Impacto' => 'Impact',
  'Partners' => 'Partners',
  'Equipo Directivo' => 'Executive Team',
  'Transparencia' => 'Transparency',
  'Certificaciones' => 'Certifications',
  'Prensa' => 'Press',
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
$pages = $storage->loadByProperties(['tenant_id' => 7]);

echo "Tenant 7 — plataformadeecosistemas.es → EN\n";
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
