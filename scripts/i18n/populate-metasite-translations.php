#!/usr/bin/env php
<?php
/**
 * @file populate-metasite-translations.php
 *
 * Copia canvas_data y rendered_html del idioma ES a las traducciones EN/PT-BR,
 * reemplazando los textos extraidos con traducciones manuales.
 *
 * Este script resuelve el problema de traducciones creadas como estructura
 * HTML vacia (tags sin texto) porque el agente IA no estaba disponible.
 *
 * USO:
 *   lando drush scr scripts/i18n/populate-metasite-translations.php
 */

declare(strict_types=1);

// =========================================================================
// CONFIGURACION
// =========================================================================

$tenants = [
  5 => ['name' => 'pepejaraba.com', 'languages' => ['en']],
  6 => ['name' => 'jarabaimpact.com', 'languages' => ['en', 'pt-br']],
  7 => ['name' => 'plataformadeecosistemas.es', 'languages' => ['en']],
];

// Diccionario ES->EN de textos comunes en los meta-sitios.
$dictEN = [
  // --- COMUNES ---
  'Inicio' => 'Home',
  'Contacto' => 'Contact',
  'Aviso Legal' => 'Legal Notice',
  'Politica de Privacidad' => 'Privacy Policy',
  'Política de Privacidad' => 'Privacy Policy',
  'Politica de Cookies' => 'Cookie Policy',
  'Política de Cookies' => 'Cookie Policy',
  'Empresa' => 'Company',
  'Ecosistema' => 'Ecosystem',
  'Impacto' => 'Impact',
  'Partners' => 'Partners',
  'Equipo Directivo' => 'Leadership Team',
  'Transparencia' => 'Transparency',
  'Certificaciones' => 'Certifications',
  'Prensa' => 'Press',
  'Blog' => 'Blog',
  'Manifiesto' => 'Manifesto',
  'Plataforma' => 'Platform',
  'Recursos' => 'Resources',
  'Legal' => 'Legal',
  'Contenido' => 'Content',
  'Leer mas' => 'Read more',
  'Leer más' => 'Read more',
  'Ver mas' => 'See more',
  'Ver más' => 'See more',
  'Saber mas' => 'Learn more',
  'Saber más' => 'Learn more',
  'Contactar' => 'Get in touch',
  'Enviar' => 'Send',
  'Nombre' => 'Name',
  'Email' => 'Email',
  'Mensaje' => 'Message',
  'Telefono' => 'Phone',
  'Teléfono' => 'Phone',
  'Direccion' => 'Address',
  'Dirección' => 'Address',

  // --- PEPEJARABA (tenant 5) ---
  'Pepe Jaraba' => 'Pepe Jaraba',
  'Transformacion digital para todos, sin rodeos' => 'Digital transformation for everyone, no fluff',
  'Transformación digital para todos, sin rodeos' => 'Digital transformation for everyone, no fluff',
  'Acceder al Ecosistema' => 'Access the Ecosystem',
  'Emprendedor digital, consultor de transformacion e impulsor de ecosistemas de impacto' => 'Digital entrepreneur, transformation consultant and impact ecosystem builder',
  'El ecosistema que construyo nace de una conviccion simple' => 'The ecosystem I build comes from a simple conviction',
  'la tecnologia debe estar al servicio de las personas y los territorios, no al reves' => 'technology should serve people and territories, not the other way around',
  'Mas de 30 anos construyendo puentes entre la tecnologia y el impacto real' => 'Over 30 years building bridges between technology and real impact',
  'Más de 30 años construyendo puentes entre la tecnología y el impacto real' => 'Over 30 years building bridges between technology and real impact',
  'Sin Humo' => 'No Fluff',
  'Mi filosofia' => 'My philosophy',
  'Mi filosofía' => 'My philosophy',
  'Metodo Jaraba' => 'Jaraba Method',
  'Método Jaraba' => 'Jaraba Method',
  'El metodo que aplico en cada proyecto' => 'The method I apply to every project',
  'Trayectoria' => 'Track Record',
  'Proyectos' => 'Projects',
  'Ecosistemas' => 'Ecosystems',
  'Consulta gratuita' => 'Free consultation',
  'Mi compromiso es claro' => 'My commitment is clear',
  'Resultados medibles, sin letra pequena' => 'Measurable results, no fine print',

  // --- JARABAIMPACT (tenant 6) ---
  'Jaraba Impact' => 'Jaraba Impact',
  'Infraestructura Digital para la Transformacion que Importa' => 'Digital Infrastructure for Transformation that Matters',
  'Infraestructura Digital para la Transformación que Importa' => 'Digital Infrastructure for Transformation that Matters',
  'Solicita una Demo' => 'Request a Demo',
  'Verticales SaaS' => 'SaaS Verticals',
  'Verticales SaaS - Soluciones para Cada Sector' => 'SaaS Verticals - Solutions for Every Sector',
  'Soluciones para Cada Sector' => 'Solutions for Every Sector',
  'Programas Institucionales' => 'Institutional Programs',
  'Centro de Recursos' => 'Resource Center',
  'Centro de Recursos Jaraba Impact' => 'Jaraba Impact Resource Center',
  'Certificacion de Consultores' => 'Consultant Certification',
  'Certificación de Consultores' => 'Consultant Certification',
  'Tecnologia de impacto para territorios que necesitan resultados' => 'Impact technology for territories that need results',
  'Tecnología de impacto para territorios que necesitan resultados' => 'Impact technology for territories that need results',
  'Una plataforma, multiples verticales, un solo objetivo: transformacion real' => 'One platform, multiple verticals, one goal: real transformation',
  'Empleabilidad' => 'Employability',
  'Emprendimiento' => 'Entrepreneurship',
  'Comercio' => 'Commerce',
  'Agroalimentario' => 'Agri-food',
  'Servicios' => 'Services',
  'Formacion' => 'Training',
  'Formación' => 'Training',
  'Nuestro impacto en numeros' => 'Our impact in numbers',
  'Nuestro impacto en números' => 'Our impact in numbers',
  'beneficiarios directos' => 'direct beneficiaries',
  'municipios conectados' => 'connected municipalities',
  'euros gestionados' => 'euros managed',
  'anos de experiencia' => 'years of experience',
  'años de experiencia' => 'years of experience',
  'verticales operativas' => 'operational verticals',
  'Modelo de sostenibilidad' => 'Sustainability model',
  'Motor Institucional' => 'Institutional Engine',
  'Motor de Mercado' => 'Market Engine',
  'Motor Social' => 'Social Engine',

  // --- PED (tenant 7) ---
  'Plataforma de Ecosistemas Digitales' => 'Digital Ecosystems Platform',
  'Infraestructura Digital para el Desarrollo Local' => 'Digital Infrastructure for Local Development',
  'Infraestructura Digital para el Desarrollo Local y la Transformacion de Territorios' => 'Digital Infrastructure for Local Development and Territory Transformation',
  'Infraestructura Digital para el Desarrollo Local y la Transformación de Territorios' => 'Digital Infrastructure for Local Development and Territory Transformation',
  'Plataforma de Ecosistemas Digitales S.L. opera el Ecosistema Jaraba: tecnologia de impacto al servicio de personas, pymes y municipios' => 'Plataforma de Ecosistemas Digitales S.L. operates the Jaraba Ecosystem: impact technology at the service of people, SMEs and municipalities',
  'Conoce nuestro impacto' => 'Discover our impact',
  'Contacto institucional' => 'Institutional contact',
  'Cifras Clave' => 'Key Figures',
  'anos de experiencia en transformacion digital' => 'years of experience in digital transformation',
  'años de experiencia en transformación digital' => 'years of experience in digital transformation',
  'euros gestionados en fondos europeos' => 'euros managed in European funds',
  'verticales SaaS operativas' => 'operational SaaS verticals',
  'beneficiarios directos' => 'direct beneficiaries',
  'Triple Motor Economico' => 'Triple Economic Engine',
  'Triple Motor Económico' => 'Triple Economic Engine',
  'Modelo de sostenibilidad basado en tres fuentes de ingresos complementarias' => 'Sustainability model based on three complementary revenue streams',
  'Motor Institucional' => 'Institutional Engine',
  'Fondos publicos, programas subvencionados y acuerdos con administraciones' => 'Public funds, subsidized programs and government agreements',
  'Fondos públicos, programas subvencionados y acuerdos con administraciones' => 'Public funds, subsidized programs and government agreements',
  'Motor de Mercado' => 'Market Engine',
  'Productos digitales, servicios SaaS y soluciones para pymes' => 'Digital products, SaaS services and solutions for SMEs',
  'Motor Social' => 'Social Engine',
  'Proyectos de impacto social, programas formativos y desarrollo comunitario' => 'Social impact projects, training programs and community development',
  'Ecosistema Jaraba' => 'Jaraba Ecosystem',
  'Verticales de Impacto' => 'Impact Verticals',
  'Cada vertical resuelve una necesidad especifica del territorio' => 'Each vertical addresses a specific territory need',
  'Cada vertical resuelve una necesidad específica del territorio' => 'Each vertical addresses a specific territory need',
  'Empleabilidad Conecta' => 'Connected Employability',
  'Conectamos talento local con oportunidades de empleo real' => 'We connect local talent with real employment opportunities',
  'Emprendimiento Conecta' => 'Connected Entrepreneurship',
  'Acompanamiento integral para emprendedores del territorio' => 'Comprehensive support for territorial entrepreneurs',
  'Acompañamiento integral para emprendedores del territorio' => 'Comprehensive support for territorial entrepreneurs',
  'Comercio Conecta' => 'Connected Commerce',
  'Digitalizacion y visibilidad para el comercio local' => 'Digitalization and visibility for local commerce',
  'Digitalización y visibilidad para el comercio local' => 'Digitalization and visibility for local commerce',
  'Agro Conecta' => 'Agro Connect',
  'Marketplace y herramientas para productores agroalimentarios' => 'Marketplace and tools for agri-food producers',
  'Servicios Conecta' => 'Connected Services',
  'Directorio y gestion para profesionales de servicios' => 'Directory and management for service professionals',
  'Directorio y gestión para profesionales de servicios' => 'Directory and management for service professionals',
  'JarabaLex' => 'JarabaLex',
  'Asistencia legal inteligente con IA para ciudadanos' => 'AI-powered smart legal assistance for citizens',
  'Formacion Digital' => 'Digital Training',
  'Formación Digital' => 'Digital Training',
  'Plataforma de aprendizaje adaptativo y certificaciones' => 'Adaptive learning platform and certifications',
  'Andalucia Emprende e Innova' => 'Andalusia Innovates',
  'Andalucía Emprende e Innova' => 'Andalusia Innovates',
  'Ecosistema de innovacion para la comunidad andaluza' => 'Innovation ecosystem for the Andalusian community',
  'Ecosistema de innovación para la comunidad andaluza' => 'Innovation ecosystem for the Andalusian community',
  'Gobierno Corporativo' => 'Corporate Governance',
  'Estructura transparente y comprometida con el impacto' => 'Transparent structure committed to impact',
  'Equipo directivo' => 'Leadership team',
  'Junta de Asesores' => 'Advisory Board',
  'Consejo de Administracion' => 'Board of Directors',
  'Consejo de Administración' => 'Board of Directors',
  'Nuestros valores' => 'Our values',
  'Transparencia radical' => 'Radical transparency',
  'Compromiso territorial' => 'Territorial commitment',
  'Tecnologia con proposito' => 'Technology with purpose',
  'Tecnología con propósito' => 'Technology with purpose',
  'Innovacion pragmatica' => 'Pragmatic innovation',
  'Innovación pragmática' => 'Pragmatic innovation',
  'Impacto medible' => 'Measurable impact',
  'Alianzas Estrategicas' => 'Strategic Alliances',
  'Alianzas Estratégicas' => 'Strategic Alliances',
  'Colaboramos con organizaciones que comparten nuestra vision' => 'We collaborate with organizations that share our vision',
  'Colaboramos con organizaciones que comparten nuestra visión' => 'We collaborate with organizations that share our vision',
  'Partners tecnologicos' => 'Technology partners',
  'Partners tecnológicos' => 'Technology partners',
  'Instituciones publicas' => 'Public institutions',
  'Instituciones públicas' => 'Public institutions',
  'Organizaciones sociales' => 'Social organizations',
  'Universidades' => 'Universities',
  'Datos de la empresa' => 'Company data',
  'Razon social' => 'Legal name',
  'Razón social' => 'Legal name',
  'Plataforma de Ecosistemas Digitales S.L.' => 'Plataforma de Ecosistemas Digitales S.L.',
  'Domicilio social' => 'Registered office',
  'Objeto social' => 'Corporate purpose',
  'Desarrollo y explotacion de plataformas digitales de impacto social y economico' => 'Development and operation of digital platforms for social and economic impact',
  'Desarrollo y explotación de plataformas digitales de impacto social y económico' => 'Development and operation of digital platforms for social and economic impact',
  'Informes anuales' => 'Annual reports',
  'Memorias de impacto' => 'Impact reports',
  'Cuentas anuales' => 'Annual accounts',
  'Codigo etico' => 'Code of ethics',
  'Código ético' => 'Code of ethics',
  'Politica de compliance' => 'Compliance policy',
  'Política de compliance' => 'Compliance policy',
  'Certificaciones y Reconocimientos' => 'Certifications and Recognition',
  'Comprometidos con la excelencia y la mejora continua' => 'Committed to excellence and continuous improvement',
  'Sala de Prensa' => 'Press Room',
  'Notas de prensa, recursos mediaticos y contacto para medios' => 'Press releases, media resources and media contact',
  'Notas de prensa, recursos mediáticos y contacto para medios' => 'Press releases, media resources and media contact',
  'Contacto para medios' => 'Media contact',
  'Descargar kit de prensa' => 'Download press kit',
  'Notas de prensa recientes' => 'Recent press releases',
  'Formulario de contacto' => 'Contact form',
  'Escríbenos' => 'Write to us',
  'Escribenos' => 'Write to us',
  'Te responderemos en menos de 24 horas' => 'We will respond within 24 hours',
  'Informacion de contacto' => 'Contact information',
  'Información de contacto' => 'Contact information',
  'Todos los derechos reservados' => 'All rights reserved',
  'Hecho con' => 'Made with',
  'en Andalucia' => 'in Andalusia',
  'en Andalucía' => 'in Andalusia',
  'Quienes somos' => 'About us',
  'Quiénes somos' => 'About us',
  'Que hacemos' => 'What we do',
  'Qué hacemos' => 'What we do',
  'Nuestra mision' => 'Our mission',
  'Nuestra misión' => 'Our mission',
  'Nuestra vision' => 'Our vision',
  'Nuestra visión' => 'Our vision',
  'Sede central' => 'Headquarters',
  'Malaga, Espana' => 'Malaga, Spain',
  'Málaga, España' => 'Malaga, Spain',
];

// Diccionario ES->PT-BR (solo para tenant 6).
$dictPTBR = [
  'Jaraba Impact' => 'Jaraba Impact',
  'Infraestructura Digital para la Transformacion que Importa' => 'Infraestrutura Digital para a Transformação que Importa',
  'Infraestructura Digital para la Transformación que Importa' => 'Infraestrutura Digital para a Transformação que Importa',
  'Solicita una Demo' => 'Solicite uma Demo',
  'Verticales SaaS' => 'Verticais SaaS',
  'Verticales SaaS - Soluciones para Cada Sector' => 'Verticais SaaS - Soluções para Cada Setor',
  'Soluciones para Cada Sector' => 'Soluções para Cada Setor',
  'Programas Institucionales' => 'Programas Institucionais',
  'Centro de Recursos' => 'Centro de Recursos',
  'Centro de Recursos Jaraba Impact' => 'Centro de Recursos Jaraba Impact',
  'Certificacion de Consultores' => 'Certificação de Consultores',
  'Certificación de Consultores' => 'Certificação de Consultores',
  'Tecnologia de impacto para territorios que necesitan resultados' => 'Tecnologia de impacto para territórios que precisam de resultados',
  'Tecnología de impacto para territorios que necesitan resultados' => 'Tecnologia de impacto para territórios que precisam de resultados',
  'Una plataforma, multiples verticales, un solo objetivo: transformacion real' => 'Uma plataforma, múltiplas verticais, um único objetivo: transformação real',
  'Empleabilidad' => 'Empregabilidade',
  'Emprendimiento' => 'Empreendedorismo',
  'Comercio' => 'Comércio',
  'Agroalimentario' => 'Agroalimentar',
  'Servicios' => 'Serviços',
  'Formacion' => 'Formação',
  'Formación' => 'Formação',
  'Nuestro impacto en numeros' => 'Nosso impacto em números',
  'Nuestro impacto en números' => 'Nosso impacto em números',
  'beneficiarios directos' => 'beneficiários diretos',
  'municipios conectados' => 'municípios conectados',
  'euros gestionados' => 'euros gerenciados',
  'anos de experiencia' => 'anos de experiência',
  'años de experiencia' => 'anos de experiência',
  'verticales operativas' => 'verticais operacionais',
  'Modelo de sostenibilidad' => 'Modelo de sustentabilidade',
  'Motor Institucional' => 'Motor Institucional',
  'Motor de Mercado' => 'Motor de Mercado',
  'Motor Social' => 'Motor Social',
  'Fondos publicos, programas subvencionados y acuerdos con administraciones' => 'Fundos públicos, programas subsidiados e acordos com administrações',
  'Fondos públicos, programas subvencionados y acuerdos con administraciones' => 'Fundos públicos, programas subsidiados e acordos com administrações',
  'Productos digitales, servicios SaaS y soluciones para pymes' => 'Produtos digitais, serviços SaaS e soluções para PMEs',
  'Proyectos de impacto social, programas formativos y desarrollo comunitario' => 'Projetos de impacto social, programas de formação e desenvolvimento comunitário',
  'Contacto' => 'Contato',
  'Inicio' => 'Início',
  'Plataforma' => 'Plataforma',
  'Impacto' => 'Impacto',
  'Recursos' => 'Recursos',
  'Legal' => 'Legal',
  'Aviso Legal' => 'Aviso Legal',
  'Politica de Privacidad' => 'Política de Privacidade',
  'Política de Privacidad' => 'Política de Privacidade',
  'Politica de Cookies' => 'Política de Cookies',
  'Política de Cookies' => 'Política de Cookies',
  'Leer mas' => 'Leia mais',
  'Leer más' => 'Leia mais',
  'Ver mas' => 'Veja mais',
  'Ver más' => 'Veja mais',
  'Saber mas' => 'Saiba mais',
  'Saber más' => 'Saiba mais',
  'Enviar' => 'Enviar',
  'Nombre' => 'Nome',
  'Mensaje' => 'Mensagem',
  'Todos los derechos reservados' => 'Todos os direitos reservados',
  'Formulario de contacto' => 'Formulário de contato',
  'Te responderemos en menos de 24 horas' => 'Responderemos em menos de 24 horas',
  'Escribenos' => 'Escreva para nós',
  'Escríbenos' => 'Escreva para nós',
  'Informacion de contacto' => 'Informações de contato',
  'Información de contacto' => 'Informações de contato',
  'Empresa' => 'Empresa',
  'Ecosistema' => 'Ecossistema',
  'Blog' => 'Blog',
  'Hecho con' => 'Feito com',
  'en Andalucia' => 'na Andaluzia',
  'en Andalucía' => 'na Andaluzia',
  'Quienes somos' => 'Quem somos',
  'Quiénes somos' => 'Quem somos',
  'Que hacemos' => 'O que fazemos',
  'Qué hacemos' => 'O que fazemos',
  'Nuestra mision' => 'Nossa missão',
  'Nuestra misión' => 'Nossa missão',
  'Sede central' => 'Sede',
  'Malaga, Espana' => 'Málaga, Espanha',
  'Málaga, España' => 'Málaga, Espanha',
  'Contactar' => 'Entrar em contato',
  'Email' => 'Email',
  'Telefono' => 'Telefone',
  'Teléfono' => 'Telefone',
  'Direccion' => 'Endereço',
  'Dirección' => 'Endereço',
];

// Title translations.
$titleEN = [
  'Inicio' => 'Home',
  'Contacto' => 'Contact',
  'Aviso Legal' => 'Legal Notice',
  'Politica de Privacidad' => 'Privacy Policy',
  'Política de Privacidad' => 'Privacy Policy',
  'Politica de Cookies' => 'Cookie Policy',
  'Política de Cookies' => 'Cookie Policy',
  'Empresa' => 'Company',
  'Ecosistema' => 'Ecosystem',
  'Impacto' => 'Impact',
  'Partners' => 'Partners',
  'Equipo Directivo' => 'Leadership Team',
  'Transparencia' => 'Transparency',
  'Certificaciones' => 'Certifications',
  'Prensa' => 'Press',
  'Blog' => 'Blog',
  'Manifiesto' => 'Manifesto',
  'Método Jaraba' => 'Jaraba Method',
  'Metodo Jaraba' => 'Jaraba Method',
  'Plataforma' => 'Platform',
  'Verticales SaaS - Soluciones para Cada Sector' => 'SaaS Verticals - Solutions for Every Sector',
  'Programas Institucionales' => 'Institutional Programs',
  'Centro de Recursos Jaraba Impact' => 'Jaraba Impact Resource Center',
  'Certificación de Consultores' => 'Consultant Certification',
  'Certificacion de Consultores' => 'Consultant Certification',
  'Jaraba Impact - Infraestructura Digital para la Transformación Económica' => 'Jaraba Impact - Digital Infrastructure for Economic Transformation',
];

$titlePTBR = [
  'Inicio' => 'Início',
  'Contacto' => 'Contato',
  'Aviso Legal' => 'Aviso Legal',
  'Politica de Privacidad' => 'Política de Privacidade',
  'Política de Privacidad' => 'Política de Privacidade',
  'Politica de Cookies' => 'Política de Cookies',
  'Política de Cookies' => 'Política de Cookies',
  'Plataforma' => 'Plataforma',
  'Verticales SaaS - Soluciones para Cada Sector' => 'Verticais SaaS - Soluções para Cada Setor',
  'Impacto' => 'Impacto',
  'Programas Institucionales' => 'Programas Institucionais',
  'Centro de Recursos Jaraba Impact' => 'Centro de Recursos Jaraba Impact',
  'Certificación de Consultores' => 'Certificação de Consultores',
  'Certificacion de Consultores' => 'Certificação de Consultores',
  'Jaraba Impact - Infraestructura Digital para la Transformación Económica' => 'Jaraba Impact - Infraestrutura Digital para a Transformação Econômica',
];

// =========================================================================
// FUNCIONES
// =========================================================================

/**
 * Traduce un HTML reemplazando textos usando un diccionario.
 *
 * Extrae nodos de texto del HTML y los busca en el diccionario.
 * Los textos que no se encuentran se dejan en español.
 */
function translateHtmlWithDict(string $html, array $dict): string {
  if (empty(trim($html))) {
    return $html;
  }

  // Sort dict by length descending to match longer phrases first.
  uksort($dict, fn($a, $b) => mb_strlen($b) - mb_strlen($a));

  // Replace text nodes between HTML tags.
  $result = preg_replace_callback(
    '/>((?:(?!<).)+)</us',
    function ($matches) use ($dict) {
      $originalText = $matches[1];
      $trimmed = trim($originalText);
      if (empty($trimmed) || mb_strlen($trimmed) < 2) {
        return $matches[0];
      }

      // Try exact match first.
      if (isset($dict[$trimmed])) {
        // Preserve leading/trailing whitespace from original.
        $leading = '';
        $trailing = '';
        if (preg_match('/^(\s+)/', $originalText, $m)) {
          $leading = $m[1];
        }
        if (preg_match('/(\s+)$/', $originalText, $m)) {
          $trailing = $m[1];
        }
        return '>' . $leading . $dict[$trimmed] . $trailing . '<';
      }

      // Try substring replacements for composite text.
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

/**
 * Traduce canvas_data JSON de GrapesJS.
 */
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

/**
 * Traduce componentes GrapesJS recursivamente.
 */
function translateComponentsWithDict(array $components, array $dict): array {
  foreach ($components as &$comp) {
    if (!empty($comp['content']) && is_string($comp['content'])) {
      $text = strip_tags($comp['content']);
      $trimmed = trim($text);
      if (isset($dict[$trimmed])) {
        // If content has HTML, replace the text inside.
        if ($comp['content'] !== $text) {
          $comp['content'] = str_replace($trimmed, $dict[$trimmed], $comp['content']);
        } else {
          $comp['content'] = $dict[$trimmed];
        }
      }
    }
    if (!empty($comp['components']) && is_array($comp['components'])) {
      $comp['components'] = translateComponentsWithDict($comp['components'], $dict);
    }
    if (!empty($comp['attributes']) && is_array($comp['attributes'])) {
      foreach (['alt', 'title', 'placeholder', 'aria-label'] as $attr) {
        if (!empty($comp['attributes'][$attr]) && isset($dict[trim($comp['attributes'][$attr])])) {
          $comp['attributes'][$attr] = $dict[trim($comp['attributes'][$attr])];
        }
      }
    }
  }
  return $components;
}

// =========================================================================
// MAIN
// =========================================================================

$entityTypeManager = \Drupal::entityTypeManager();
$storage = $entityTypeManager->getStorage('page_content');
$totalUpdated = 0;
$totalSkipped = 0;
$totalErrors = 0;

foreach ($tenants as $tenantId => $config) {
  echo "\n" . str_repeat('─', 60) . "\n";
  echo "Tenant $tenantId: {$config['name']}\n";
  echo str_repeat('─', 60) . "\n";

  $pages = $storage->loadByProperties(['tenant_id' => $tenantId]);
  if (empty($pages)) {
    echo "  Sin paginas\n";
    continue;
  }

  echo "  " . count($pages) . " paginas\n";

  foreach ($pages as $page) {
    $pageTitle = $page->get('title')->value ?? 'Sin titulo';
    $pageId = $page->id();
    $esHtml = $page->getUntranslated()->get('rendered_html')->value ?? '';
    $esCanvas = $page->getUntranslated()->get('canvas_data')->value ?? '';

    foreach ($config['languages'] as $targetLang) {
      echo "  Page #$pageId \"$pageTitle\" -> $targetLang: ";

      if (!$page->hasTranslation($targetLang)) {
        echo "sin traduccion (skip)\n";
        $totalSkipped++;
        continue;
      }

      $dict = ($targetLang === 'pt-br') ? $dictPTBR : $dictEN;
      $titleDict = ($targetLang === 'pt-br') ? $titlePTBR : $titleEN;

      try {
        $translation = $page->getTranslation($targetLang);

        // Traducir titulo.
        $esTitle = $page->getUntranslated()->get('title')->value ?? '';
        $trTitle = $titleDict[$esTitle] ?? $dictEN[$esTitle] ?? $esTitle;
        $translation->set('title', $trTitle);

        // Traducir meta_title y meta_description.
        foreach (['meta_title', 'meta_description'] as $metaField) {
          if ($page->hasField($metaField)) {
            $esVal = $page->getUntranslated()->get($metaField)->value ?? '';
            if (!empty($esVal)) {
              $trVal = $esVal;
              foreach ($dict as $es => $tr) {
                if (str_contains($trVal, $es)) {
                  $trVal = str_replace($es, $tr, $trVal);
                }
              }
              $translation->set($metaField, $trVal);
            }
          }
        }

        // Traducir rendered_html: copiar ES y reemplazar textos.
        if (!empty($esHtml)) {
          $trHtml = translateHtmlWithDict($esHtml, $dict);
          $translation->set('rendered_html', $trHtml);
        }

        // Traducir canvas_data: copiar ES y reemplazar textos.
        if (!empty($esCanvas)) {
          $trCanvas = translateCanvasWithDict($esCanvas, $dict);
          $translation->set('canvas_data', $trCanvas);
        }

        // Guardar con syncing para evitar loop del hook.
        $page->setSyncing(TRUE);
        $page->save();
        $page->setSyncing(FALSE);

        echo "OK\n";
        $totalUpdated++;
      }
      catch (\Throwable $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        $totalErrors++;
      }
    }
  }
}

// =========================================================================
// RESUMEN
// =========================================================================
echo "\n" . str_repeat('=', 60) . "\n";
echo "RESUMEN\n";
echo str_repeat('=', 60) . "\n";
echo "  Actualizadas: $totalUpdated\n";
echo "  Saltadas:     $totalSkipped\n";
echo "  Errores:      $totalErrors\n";
echo str_repeat('=', 60) . "\n";
