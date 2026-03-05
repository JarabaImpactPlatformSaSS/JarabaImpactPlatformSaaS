#!/usr/bin/env php
<?php
/**
 * @file populate-jarabaimpact-ptbr.php
 *
 * Traduce todas las páginas de jarabaimpact.com (tenant 6) a PT-BR
 * usando diccionario completo de textos extraídos del ES.
 *
 * USO:
 *   lando drush scr scripts/i18n/populate-jarabaimpact-ptbr.php
 */

declare(strict_types=1);

// Diccionario exhaustivo ES → PT-BR para jarabaimpact.com.
// Ordenado de frases largas a cortas para que el reemplazo sea correcto.
$dict = [
  // =========================================================================
  // PAGE 56 — Homepage hero
  // =========================================================================
  'PLATAFORMA SAAS DE IMPACTO' => 'PLATAFORMA SAAS DE IMPACTO',
  'Infraestructura Digital para la Transformación Económica' => 'Infraestrutura Digital para a Transformação Econômica',
  'Plataforma multi-tenant que sincroniza empleo, emprendimiento y comercio local con tecnología de impacto. +30 años de experiencia, +100M€ en fondos gestionados.' => 'Plataforma multi-tenant que sincroniza emprego, empreendedorismo e comércio local com tecnologia de impacto. +30 anos de experiência, +100M€ em fundos gerenciados.',
  'Solicita una Demo' => 'Solicite uma Demo',
  'Descarga el Libro Blanco' => 'Baixe o Livro Branco',

  // =========================================================================
  // PAGE 66 — Plataforma
  // =========================================================================
  'La Plataforma' => 'A Plataforma',
  'El Modelo que Conecta Ecosistemas' => 'O Modelo que Conecta Ecossistemas',
  'Infraestructura SaaS multi-tenant diseñada para escalar el impacto social y económico en territorios' => 'Infraestrutura SaaS multi-tenant projetada para escalar o impacto social e econômico em territórios',
  'Triple Motor Económico' => 'Triplo Motor Econômico',
  'Tres fuentes de ingresos complementarias que garantizan sostenibilidad financiera mientras maximizan' => 'Três fontes de receita complementares que garantem sustentabilidade financeira enquanto maximizam',
  'Motor Institucional' => 'Motor Institucional',
  'Fondos públicos y programas subvencionados.' => 'Fundos públicos e programas subsidiados.',
  'Colaboración directa con la Junta de Andalucía, SEPE, universidades y entidades locales. Programas c' => 'Colaboração direta com a Junta da Andaluzia, SEPE, universidades e entidades locais. Programas c',
  'Motor Privado' => 'Motor Privado',
  'Productos digitales y servicios SaaS.' => 'Produtos digitais e serviços SaaS.',
  'Cinco verticales especializadas (AgroConecta, ComercioConecta, ServiciosConecta, Empleabilidad, Empr' => 'Cinco verticais especializadas (AgroConecta, ComércioConecta, ServiçosConecta, Empregabilidade, Empr',
  'Motor de Licencias' => 'Motor de Licenças',
  'Certificación del Método Jaraba.' => 'Certificação do Método Jaraba.',
  'Red de consultores certificados en 3 niveles, franquicias territoriales con royalties, y un marketpl' => 'Rede de consultores certificados em 3 níveis, franquias territoriais com royalties, e um marketpl',
  'Arquitectura Tecnológica' => 'Arquitetura Tecnológica',
  'Construida sobre estándares enterprise con foco en escalabilidad, seguridad y rendimiento.' => 'Construída sobre padrões enterprise com foco em escalabilidade, segurança e desempenho.',
  'Drupal 11 Multi-Tenant' => 'Drupal 11 Multi-Tenant',
  'CMS enterprise con arquitectura multi-tenant basada en Group Module. Cada tenant opera en aislamient' => 'CMS enterprise com arquitetura multi-tenant baseada em Group Module. Cada tenant opera em isolament',
  'AI-Native' => 'AI-Native',
  'Inteligencia artificial integrada en el core: generación de contenido, matching inteligente de emple' => 'Inteligência artificial integrada no core: geração de conteúdo, matching inteligente de empre',
  'API-First + SSR' => 'API-First + SSR',
  'APIs RESTful para integraciones, Server-Side Rendering para SEO/GEO óptimo, y webhooks para automati' => 'APIs RESTful para integrações, Server-Side Rendering para SEO/GEO ótimo, e webhooks para automati',
  '¿Quieres Ver la Plataforma en Acción?' => 'Quer Ver a Plataforma em Ação?',
  'Agenda una demo personalizada y descubre cómo la infraestructura Jaraba puede transformar tu territo' => 'Agende uma demo personalizada e descubra como a infraestrutura Jaraba pode transformar seu territ',

  // =========================================================================
  // PAGE 67 — Verticales SaaS
  // =========================================================================
  'Verticales SaaS de Impacto' => 'Verticais SaaS de Impacto',
  'AgroConecta' => 'AgroConecta',
  'Gestión agrícola inteligente. Producción, comercialización y trazabilidad para el sector agropecuari' => 'Gestão agrícola inteligente. Produção, comercialização e rastreabilidade para o setor agropecuári',
  'ComercioConecta' => 'ComércioConecta',
  'Digitalización del comercio local. Marketplace, fidelización y pasarelas de pago integradas.' => 'Digitalização do comércio local. Marketplace, fidelização e gateways de pagamento integrados.',
  'EmpleoConecta' => 'EmpregoConecta',
  'Ecosistema de empleo y formación. Matching inteligente, competencias y rutas de inserción laboral.' => 'Ecossistema de emprego e formação. Matching inteligente, competências e rotas de inserção laboral.',
  'TurismoConecta' => 'TurismoConecta',
  'Dinamización turística territorial. Experiencias, rutas, reservas y promoción del destino.' => 'Dinamização turística territorial. Experiências, rotas, reservas e promoção do destino.',
  'EmprendimientoConecta' => 'EmpreendedorismoConecta',
  'Aceleración y acompañamiento emprendedor. Incubación, mentoring y acceso a financiación.' => 'Aceleração e acompanhamento empreendedor. Incubação, mentoria e acesso a financiamento.',
  'ServiciosConecta' => 'ServiçosConecta',
  'Gestión de servicios profesionales. Directorio, agenda, facturación y CRM para autónomos y pymes.' => 'Gestão de serviços profissionais. Diretório, agenda, faturamento e CRM para autônomos e PMEs.',
  'Soporte Especializado' => 'Suporte Especializado',
  'Equipo de expertos a tu disposición para garantizar el éxito de tu transformación digital.' => 'Equipe de especialistas à sua disposição para garantir o sucesso da sua transformação digital.',
  'Personalizable' => 'Personalizável',
  'Módulos adaptables a las necesidades específicas de tu territorio u organización.' => 'Módulos adaptáveis às necessidades específicas do seu território ou organização.',
  'Eficiencia de Costes' => 'Eficiência de Custos',
  'Optimiza tu inversión con soluciones escalables que crecen con tu proyecto.' => 'Otimize seu investimento com soluções escaláveis que crescem com seu projeto.',

  // =========================================================================
  // PAGE 68 — Impacto
  // =========================================================================
  'Nuestro Impacto' => 'Nosso Impacto',
  'Resultados Medibles, Historias Reales' => 'Resultados Mensuráveis, Histórias Reais',
  'Más de tres décadas construyendo ecosistemas digitales que transforman vidas y territorios. Estos so' => 'Mais de três décadas construindo ecossistemas digitais que transformam vidas e territórios. Estes so',
  'Años liderando transformación digital' => 'Anos liderando transformação digital',
  'Fondos europeos gestionados' => 'Fundos europeus gerenciados',
  'Beneficiarios directos' => 'Beneficiários diretos',
  'Tasa de éxito en proyectos' => 'Taxa de sucesso em projetos',
  'Casos de Éxito' => 'Casos de Sucesso',
  'Personas y organizaciones reales que han transformado su realidad con el apoyo del ecosistema Jaraba' => 'Pessoas e organizações reais que transformaram sua realidade com o apoio do ecossistema Jaraba',
  'Emprendimiento' => 'Empreendedorismo',
  'Marcela Calabia' => 'Marcela Calabia',
  'El reto:' => 'O desafio:',
  'Reinventarse profesionalmente sin experiencia como autónoma.' => 'Reinventar-se profissionalmente sem experiência como autônoma.',
  'La solución:' => 'A solução:',
  'Programa Andalucía +ei con el Método Jaraba "sin humo".' => 'Programa Andaluzia +ei com o Método Jaraba "sem enrolação".',
  'El resultado:' => 'O resultado:',
  'Creó su propia web, dominó el marketing digital y lanzó su actividad como coach de comunicación estr' => 'Criou seu próprio site, dominou o marketing digital e lançou sua atividade como coach de comunicação estr',
  'Pymes' => 'PMEs',
  'Ángel Martínez — Camino Viejo' => 'Ángel Martínez — Camino Viejo',
  'Salir del estrés corporativo y emprender en el mundo rural.' => 'Sair do estresse corporativo e empreender no mundo rural.',
  'Lean finance y acompañamiento para minimizar inversión inicial.' => 'Lean finance e acompanhamento para minimizar investimento inicial.',
  'Creó Camino Viejo, experiencias comunitarias en la Sierra Norte de Sevilla, con un modelo sostenible' => 'Criou Camino Viejo, experiências comunitárias na Sierra Norte de Sevilha, com um modelo sustentável',
  'Empleabilidad' => 'Empregabilidade',
  'Luis Miguel Criado' => 'Luis Miguel Criado',
  'Parálisis administrativa como barrera principal para emprender.' => 'Paralisia administrativa como barreira principal para empreender.',
  'Desmitificación de trámites y apoyo paso a paso.' => 'Desmistificação de trâmites e apoio passo a passo.',
  'Se dio de alta como autónomo, obtuvo financiación de la Junta y gestiona sus cuotas de forma indepen' => 'Registrou-se como autônomo, obteve financiamento da Junta e gerencia suas cotas de forma indepen',
  'Únete al Ecosistema de Impacto' => 'Junte-se ao Ecossistema de Impacto',
  'Forma parte de una comunidad que genera resultados reales para personas y territorios.' => 'Faça parte de uma comunidade que gera resultados reais para pessoas e territórios.',
  'Contactar' => 'Entrar em contato',

  // =========================================================================
  // PAGE 69 — Programas Institucionales
  // =========================================================================
  'Programas' => 'Programas',
  'Programas Institucionales de Impacto' => 'Programas Institucionais de Impacto',
  'Colaboramos con instituciones públicas y privadas para implementar programas de transformación digit' => 'Colaboramos com instituições públicas e privadas para implementar programas de transformação digit',
  'Activo' => 'Ativo',
  'Andalucía +ei' => 'Andaluzia +ei',
  'Programa de orientación profesional e inserción laboral para personas desempleadas en Andalucía. Inc' => 'Programa de orientação profissional e inserção laboral para pessoas desempregadas na Andaluzia. Inc',
  'Colaboradores:' => 'Colaboradores:',
  'Junta de Andalucía, SAE, SEPE' => 'Junta da Andaluzia, SAE, SEPE',
  'Kit Digital' => 'Kit Digital',
  'Digitalización acelerada de PYMEs con fondos NextGen EU. Implementación de soluciones digitales inte' => 'Digitalização acelerada de PMEs com fundos NextGen EU. Implementação de soluções digitais inte',
  'Financiación:' => 'Financiamento:',
  'Fondos Next Generation EU' => 'Fundos Next Generation EU',
  'En preparación' => 'Em preparação',
  'PIIL — Proyectos Integrales de Inserción Laboral' => 'PIIL — Projetos Integrais de Inserção Laboral',
  'Programa integral que combina formación, orientación y acompañamiento para la inserción laboral de c' => 'Programa integral que combina formação, orientação e acompanhamento para a inserção laboral de c',
  'Piloto' => 'Piloto',
  'Autodigitales' => 'Autodigitais',
  'Formación digital práctica para autónomos rurales. Programa piloto que combina sesiones presenciales' => 'Formação digital prática para autônomos rurais. Programa piloto que combina sessões presenciais',
  '¿Tu Institución Busca Colaborar?' => 'Sua Instituição Busca Colaborar?',
  'Diseñamos programas a medida para entidades públicas, universidades, cámaras de comercio y organizac' => 'Desenhamos programas sob medida para entidades públicas, universidades, câmaras de comércio e organizaç',
  'Contactar para Colaborar' => 'Entrar em contato para Colaborar',

  // =========================================================================
  // PAGE 70 — Centro de Recursos
  // =========================================================================
  'CENTRO DE RECURSOS' => 'CENTRO DE RECURSOS',
  'Documentos y Materiales' => 'Documentos e Materiais',
  'Accede a la documentación técnica y estratégica del ecosistema Jaraba Impact. and success stories' => 'Acesse a documentação técnica e estratégica do ecossistema Jaraba Impact e casos de sucesso',
  'Todos' => 'Todos',
  'Whitepapers' => 'Whitepapers',
  'Fichas' => 'Fichas',
  'Prensa' => 'Imprensa',
  'E-commerce Redesign' => 'E-commerce Redesign',
  'TechCorp Inc.' => 'TechCorp Inc.',
  'UX Design' => 'UX Design',
  'Development' => 'Development',
  'Brand Identity' => 'Brand Identity',
  'StartupX' => 'StartupX',
  'Logo Design' => 'Logo Design',
  'Prensa Banking App' => 'Imprensa Banking App',
  'FinanceFirst' => 'FinanceFirst',
  'UI/UX' => 'UI/UX',

  // =========================================================================
  // PAGE 71 — Contacto
  // =========================================================================
  'Hablemos de tu Proyecto' => 'Vamos Falar do seu Projeto',
  'Estamos aquí para ayudarte a impulsar la transformación digital de tu organización o tu carrera prof' => 'Estamos aqui para ajudá-lo a impulsionar a transformação digital da sua organização ou sua carreira prof',
  'Email Institucional' => 'Email Institucional',
  'Teléfono / WhatsApp' => 'Telefone / WhatsApp',
  'Sede Central' => 'Sede Central',
  'Sevilla, Andalucía, España' => 'Sevilha, Andaluzia, Espanha',
  'Síguenos' => 'Siga-nos',
  'LinkedIn' => 'LinkedIn',
  'YouTube' => 'YouTube',
  'WhatsApp' => 'WhatsApp',
  'Formulario de Contacto' => 'Formulário de Contato',
  'Completa el formulario y nos pondremos en contacto contigo en menos de 24 horas laborables.' => 'Preencha o formulário e entraremos em contato em menos de 24 horas úteis.',
  'Para enviar tu consulta, escríbenos directamente a' => 'Para enviar sua consulta, escreva diretamente para',
  'o llámanos al' => 'ou ligue para',

  // =========================================================================
  // PAGE 73 — Inicio (landing principal)
  // =========================================================================
  'Ecosistema Digital de Impacto' => 'Ecossistema Digital de Impacto',
  'Infraestructura Digital para la Transformación que Importa' => 'Infraestrutura Digital para a Transformação que Importa',
  'Plataforma SaaS multi-tenant que sincroniza empleo, emprendimiento y comercio local con tecnología d' => 'Plataforma SaaS multi-tenant que sincroniza emprego, empreendedorismo e comércio local com tecnologia d',
  'Conoce la Plataforma' => 'Conheça a Plataforma',
  'Modelo de Negocio' => 'Modelo de Negócio',
  'Un modelo híbrido que sincroniza fondos públicos, productos SaaS y certificación profesional para ge' => 'Um modelo híbrido que sincroniza fundos públicos, produtos SaaS e certificação profissional para ge',
  'Fondos públicos y programas subvencionados. Andalucía +ei, PIIL, NextGen EU. Colaboración directa co' => 'Fundos públicos e programas subsidiados. Andaluzia +ei, PIIL, NextGen EU. Colaboração direta co',
  'Productos digitales y servicios SaaS. Cinco verticales operativas que generan ingresos recurrentes: ' => 'Produtos digitais e serviços SaaS. Cinco verticais operacionais que geram receita recorrente: ',
  'Certificación del Método Jaraba para consultores. Franquicias territoriales, royalties y una red cre' => 'Certificação do Método Jaraba para consultores. Franquias territoriais, royalties e uma rede cre',
  'Verticales SaaS' => 'Verticais SaaS',
  'Soluciones para Cada Sector' => 'Soluções para Cada Setor',
  'Cinco verticales especializadas que cubren el ecosistema completo de transformación digital territor' => 'Cinco verticais especializadas que cobrem o ecossistema completo de transformação digital territor',
  'Del campo a tu mesa. Trazabilidad blockchain para productores locales.' => 'Do campo à sua mesa. Rastreabilidade blockchain para produtores locais.',
  'Impulsa tu comercio local. QR dinámicos y ofertas flash geolocalizadas.' => 'Impulsione seu comércio local. QR dinâmicos e ofertas flash geolocalizadas.',
  'Profesionales de confianza. Agenda, firma digital y buzón seguro.' => 'Profissionais de confiança. Agenda, assinatura digital e caixa postal segura.',
  'Conectamos talento con oportunidades. LMS + Job Board integrado.' => 'Conectamos talento com oportunidades. LMS + Job Board integrado.',
  'De la idea al negocio. Diagnóstico, mentoring y financiación.' => 'Da ideia ao negócio. Diagnóstico, mentoria e financiamento.',
  'Impacto en Cifras' => 'Impacto em Números',
  'Resultados que Hablan por Sí Solos' => 'Resultados que Falam por Si Mesmos',
  'Años de experiencia en transformación digital' => 'Anos de experiência em transformação digital',
  'Fondos europeos gestionados' => 'Fundos europeus gerenciados',
  'Beneficiarios formados y acompañados' => 'Beneficiários formados e acompanhados',
  'Verticales SaaS operativas' => 'Verticais SaaS operacionais',
  'Historias Reales' => 'Histórias Reais',
  'Historias de Transformación y Resultados' => 'Histórias de Transformação e Resultados',
  'Personas reales que han transformado su vida profesional con el ecosistema Jaraba.' => 'Pessoas reais que transformaram sua vida profissional com o ecossistema Jaraba.',
  'De la Incertidumbre a Crear mi Propio Camino' => 'Da Incerteza a Criar meu Próprio Caminho',
  'Marcela Calabia, escritora y coach de comunicación estratégica, lanzó su proyecto como autónoma sin ' => 'Marcela Calabia, escritora e coach de comunicação estratégica, lançou seu projeto como autônoma sem ',
  'Leer su Historia' => 'Ler sua História',
  'Del Estrés Corporativo al Éxito Rural' => 'Do Estresse Corporativo ao Sucesso Rural',
  'Ángel Martínez dejó el mundo corporativo para crear Camino Viejo, experiencias comunitarias en la Si' => 'Ángel Martínez deixou o mundo corporativo para criar Camino Viejo, experiências comunitárias na Si',
  'De la Parálisis Administrativa a la Acción' => 'Da Paralisia Administrativa à Ação',
  'Luis Miguel Criado, terapeuta manual, superó la barrera administrativa para darse de alta como autón' => 'Luis Miguel Criado, terapeuta manual, superou a barreira administrativa para se registrar como autôn',
  'Impulsa tu Próximo Gran Proyecto' => 'Impulsione seu Próximo Grande Projeto',
  'Ya seas consultor buscando certificación o una entidad que necesita soluciones de transformación dig' => 'Seja você um consultor buscando certificação ou uma entidade que precisa de soluções de transformação dig',
  'Certificación para Consultores' => 'Certificação para Consultores',
  'Soluciones para Entidades' => 'Soluções para Entidades',

  // =========================================================================
  // PAGE 74 — Certificación de Consultores
  // =========================================================================
  'Programa Oficial Jaraba Impact' => 'Programa Oficial Jaraba Impact',
  'Lidera la Transformación Digital con Impacto Real' => 'Lidere a Transformação Digital com Impacto Real',
  'Obtén el reconocimiento y la metodología para multiplicar el valor que aportas a tus clientes y acel' => 'Obtenha o reconhecimento e a metodologia para multiplicar o valor que você entrega aos seus clientes e acel',
  'Solicitar Información' => 'Solicitar Informação',
  '¿Para Quién es Esta Certificación?' => 'Para Quem é Esta Certificação?',
  'Consultores Independientes' => 'Consultores Independentes',
  'Quieres estandarizar tu metodología, aumentar tu credibilidad y escalar tu negocio de consultoría co' => 'Quer padronizar sua metodologia, aumentar sua credibilidade e escalar seu negócio de consultoria co',
  'Consultores Senior / Managers' => 'Consultores Seniores / Gerentes',
  'Buscas liderar proyectos de transformación con mayor impacto y diferenciarte dentro de tu organizaci' => 'Busca liderar projetos de transformação com maior impacto e se diferenciar dentro da sua organizaçã',
  'Directivos y Líderes de Proyecto' => 'Diretores e Líderes de Projeto',
  'Necesitas un marco estratégico para implementar cambios efectivos y gestionar la complejidad en tu e' => 'Precisa de um framework estratégico para implementar mudanças efetivas e gerenciar a complexidade em su',
  'Programa de Certificación' => 'Programa de Certificação',
  'La Senda del Consultor de Impacto' => 'O Caminho do Consultor de Impacto',
  'Tres niveles progresivos que te llevan desde los fundamentos hasta el liderazgo en transformación de' => 'Três níveis progressivos que levam desde os fundamentos até a liderança em transformação de',
  'Nivel Asociado' => 'Nível Associado',
  'Domina los Fundamentos' => 'Domine os Fundamentos',
  'En Ecosistemas Digitales' => 'Em Ecossistemas Digitais',
  'Requisitos' => 'Requisitos',
  'Completar curso formativo base' => 'Completar curso formativo base',
  'Superar examen de conocimientos' => 'Aprovação em exame de conhecimentos',
  'Adhesión al código ético del ecosistema' => 'Adesão ao código ético do ecossistema',
  'Beneficios' => 'Benefícios',
  'Certificación oficial de Nivel Asociado' => 'Certificação oficial de Nível Associado',
  'Perfil básico en el marketplace' => 'Perfil básico no marketplace',
  'Acceso a comunidad de consultores' => 'Acesso à comunidade de consultores',
  'Nivel Certificado' => 'Nível Certificado',
  'Aplica la Estrategia' => 'Aplique a Estratégia',
  'En Estrategia Digital' => 'Em Estratégia Digital',
  'Certificación de Nivel Asociado' => 'Certificação de Nível Associado',
  'Presentación de 1 caso de éxito auditado' => 'Apresentação de 1 caso de sucesso auditado',
  'Formación avanzada en estrategia y gestión' => 'Formação avançada em estratégia e gestão',
  'Sello "Consultor Certificado"' => 'Selo "Consultor Certificado"',
  'Perfil destacado y verificado en marketplace' => 'Perfil destacado e verificado no marketplace',
  'Acceso a leads pre-cualificados' => 'Acesso a leads pré-qualificados',
  'Nivel Master' => 'Nível Master',
  'Lidera la Transformación' => 'Lidere a Transformação',
  'En Transformación de Ecosistemas' => 'Em Transformação de Ecossistemas',
  'Certificación de Nivel Certificado' => 'Certificação de Nível Certificado',
  'Mínimo 3 años de experiencia demostrable' => 'Mínimo 3 anos de experiência demonstrável',
  'Contribución: artículo, webinar o caso de estudio' => 'Contribuição: artigo, webinar ou estudo de caso',
  'Entrevista con el comité directivo' => 'Entrevista com o comitê diretivo',
  'Máximo reconocimiento en el ecosistema' => 'Máximo reconhecimento no ecossistema',
  'Participación en proyectos estratégicos' => 'Participação em projetos estratégicos',
  'Oportunidades de co-autoría y ponencias' => 'Oportunidades de co-autoria e palestras',
  '¿Qué Obtendrás con la Certificación?' => 'O Que Você Obterá com a Certificação?',
  'Reconocimiento Oficial' => 'Reconhecimento Oficial',
  'Conviértete en consultor certificado con el aval de Jaraba Impact y una metodología reconocida.' => 'Torne-se um consultor certificado com o aval da Jaraba Impact e uma metodologia reconhecida.',
  'Metodología Probada' => 'Metodologia Comprovada',
  'Aplica un framework estratégico validado con más de 30 años de resultados medibles.' => 'Aplique um framework estratégico validado com mais de 30 anos de resultados mensuráveis.',
  'Diferenciación en el Mercado' => 'Diferenciação no Mercado',
  'Destaca de la competencia y atrae clientes de mayor valor con credenciales verificables.' => 'Destaque-se da concorrência e atraia clientes de maior valor com credenciais verificáveis.',
  'Red de Contactos Exclusiva' => 'Rede de Contatos Exclusiva',
  'Accede a una comunidad de profesionales de alto nivel en transformación digital.' => 'Acesse uma comunidade de profissionais de alto nível em transformação digital.',
  'Mayor Rentabilidad' => 'Maior Rentabilidade',
  'Posiciónate para proyectos de mayor envergadura y honorarios profesionales superiores.' => 'Posicione-se para projetos de maior envergadura e honorários profissionais superiores.',
  'Confianza y Solidez' => 'Confiança e Solidez',
  'Presenta propuestas con el respaldo de una metodología y un ecosistema de éxito demostrado.' => 'Apresente propostas com o respaldo de uma metodologia e um ecossistema de sucesso comprovado.',
  '¿Listo para Generar un Impacto Real?' => 'Pronto para Gerar um Impacto Real?',
  'Conviértete en un consultor de referencia en el mercado digital. Solicita información sobre el progr' => 'Torne-se um consultor de referência no mercado digital. Solicite informação sobre o progr',

  // =========================================================================
  // PAGE 75 — Aviso Legal
  // =========================================================================
  'Aviso Legal' => 'Aviso Legal',
  '1. Datos Identificativos' => '1. Dados de Identificação',
  'En cumplimiento del artículo 10 de la Ley 34/2002, de 11 de julio, de Servicios de la Sociedad de la' => 'Em cumprimento do artigo 10 da Lei 34/2002, de 11 de julho, de Serviços da Sociedade da',
  'Razón Social:' => 'Razão Social:',
  'Plataforma de Ecosistemas Digitales S.L.' => 'Plataforma de Ecosistemas Digitales S.L.',
  'Domicilio Social:' => 'Sede Social:',
  'Email:' => 'Email:',
  'Sitio Web:' => 'Site Web:',
  '2. Objeto' => '2. Objeto',
  'El presente sitio web tiene como objeto facilitar información sobre los servicios de consultoría, ce' => 'O presente site tem como objeto facilitar informação sobre os serviços de consultoria, ce',
  '3. Propiedad Intelectual' => '3. Propriedade Intelectual',
  'Todos los contenidos del sitio web, incluyendo textos, imágenes, logotipos, iconos, software y demás' => 'Todos os conteúdos do site, incluindo textos, imagens, logotipos, ícones, software e demais',
  '4. Responsabilidad' => '4. Responsabilidade',
  'Jaraba Impact no se hace responsable de los daños o perjuicios que pudieran derivarse del acceso o u' => 'Jaraba Impact não se responsabiliza pelos danos ou prejuízos que possam derivar do acesso ou u',
  '5. Legislación Aplicable' => '5. Legislação Aplicável',
  'El presente aviso legal se rige por la legislación española. Para cualquier controversia que pudiera' => 'O presente aviso legal rege-se pela legislação espanhola. Para qualquer controvérsia que possa',

  // =========================================================================
  // PAGE 76 — Política de Privacidad
  // =========================================================================
  'Política de Privacidad' => 'Política de Privacidade',
  'Última actualización:' => 'Última atualização:',
  'Febrero 2026' => 'Fevereiro 2026',
  '1. Responsable del Tratamiento' => '1. Responsável pelo Tratamento',
  'Plataforma de Ecosistemas Digitales S.L. es responsable del tratamiento de los datos personales reco' => 'Plataforma de Ecosistemas Digitales S.L. é responsável pelo tratamento dos dados pessoais reco',
  '2. Datos Recogidos' => '2. Dados Coletados',
  'Recogemos únicamente los datos que nos facilitas voluntariamente a través de formularios de contacto' => 'Coletamos unicamente os dados que você nos fornece voluntariamente através de formulários de contato',
  '3. Finalidad' => '3. Finalidade',
  'Los datos se utilizan exclusivamente para responder a tus consultas, gestionar solicitudes de inform' => 'Os dados são utilizados exclusivamente para responder às suas consultas, gerenciar solicitações de inform',
  '4. Derechos' => '4. Direitos',
  'Puedes ejercer tus derechos de acceso, rectificación, supresión, portabilidad, limitación y oposició' => 'Você pode exercer seus direitos de acesso, retificação, exclusão, portabilidade, limitação e oposiçã',
  '5. Conservación' => '5. Conservação',
  'Los datos se conservan durante el tiempo necesario para cumplir con la finalidad para la que fueron ' => 'Os dados são conservados durante o tempo necessário para cumprir a finalidade para a qual foram ',

  // =========================================================================
  // PAGE 77 — Política de Cookies
  // =========================================================================
  'Política de Cookies' => 'Política de Cookies',
  '1. ¿Qué son las Cookies?' => '1. O que são Cookies?',
  'Las cookies son pequeños archivos de texto que se almacenan en tu dispositivo cuando visitas un siti' => 'Cookies são pequenos arquivos de texto armazenados no seu dispositivo quando você visita um siti',
  '2. Cookies Utilizadas' => '2. Cookies Utilizados',
  'Este sitio web utiliza exclusivamente cookies técnicas necesarias para el funcionamiento del sitio. ' => 'Este site utiliza exclusivamente cookies técnicos necessários para o funcionamento do site. ',
  '3. Gestión de Cookies' => '3. Gestão de Cookies',
  'Puedes configurar tu navegador para rechazar cookies o para que te avise cuando se envíen. Ten en cu' => 'Você pode configurar seu navegador para rejeitar cookies ou para avisá-lo quando forem enviados. Tenha em cu',
  '4. Contacto' => '4. Contato',
  'Para cualquier consulta relacionada con nuestra política de cookies, contacta con nosotros en' => 'Para qualquer consulta relacionada com nossa política de cookies, entre em contato conosco em',
];

// Títulos de páginas ES → PT-BR.
$titleDict = [
  'Jaraba Impact - Infraestructura Digital para la Transformación Económica' => 'Jaraba Impact - Infraestrutura Digital para a Transformação Econômica',
  'Plataforma' => 'Plataforma',
  'Verticales SaaS - Soluciones para Cada Sector' => 'Verticais SaaS - Soluções para Cada Setor',
  'Impacto' => 'Impacto',
  'Programas Institucionales' => 'Programas Institucionais',
  'Centro de Recursos Jaraba Impact' => 'Centro de Recursos Jaraba Impact',
  'Contacto' => 'Contato',
  'Inicio' => 'Início',
  'Certificación de Consultores' => 'Certificação de Consultores',
  'Aviso Legal' => 'Aviso Legal',
  'Política de Privacidad' => 'Política de Privacidade',
  'Política de Cookies' => 'Política de Cookies',
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
      foreach ($dict as $es => $ptbr) {
        if (mb_strlen($es) > 20 && str_starts_with($trimmed, mb_substr($es, 0, 80))) {
          // Replace the matched prefix.
          return '>' . str_replace(mb_substr($es, 0, mb_strlen($trimmed)), $ptbr, $originalText) . '<';
        }
      }

      // Substring replacements.
      $translated = $trimmed;
      foreach ($dict as $es => $ptbr) {
        if (str_contains($translated, $es)) {
          $translated = str_replace($es, $ptbr, $translated);
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

echo "Tenant 6 — jarabaimpact.com → PT-BR\n";
echo str_repeat('─', 60) . "\n";
echo count($pages) . " paginas\n\n";

$updated = 0;
$errors = 0;

foreach ($pages as $page) {
  $pageTitle = $page->get('title')->value ?? '';
  $pageId = $page->id();
  echo "Page #$pageId \"$pageTitle\": ";

  if (!$page->hasTranslation('pt-br')) {
    echo "sin traduccion pt-br (skip)\n";
    continue;
  }

  $es = $page->getUntranslated();
  $esHtml = $es->get('rendered_html')->value ?? '';
  $esCanvas = $es->get('canvas_data')->value ?? '';

  try {
    $translation = $page->getTranslation('pt-br');

    // Title.
    $trTitle = $titleDict[$pageTitle] ?? $pageTitle;
    $translation->set('title', $trTitle);

    // Meta fields.
    foreach (['meta_title', 'meta_description'] as $metaField) {
      if ($page->hasField($metaField)) {
        $esVal = $es->get($metaField)->value ?? '';
        if (!empty($esVal)) {
          $trVal = $esVal;
          foreach ($dict as $esStr => $ptbrStr) {
            if (str_contains($trVal, $esStr)) {
              $trVal = str_replace($esStr, $ptbrStr, $trVal);
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
