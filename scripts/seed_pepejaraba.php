<?php

declare(strict_types=1);

/**
 * @file
 * Script de aprovisionamiento para el tenant pepejaraba.com.
 *
 * Ejecutar con: lando drush php:script scripts/seed_pepejaraba.php
 *
 * DIMENSIONES:
 * - Estructura: Vertical > SaasPlan > Tenant > PageContent + SiteMenu/Items
 * - Lógica: Creación idempotente, validación de existencia antes de crear
 * - Sintaxis: Uso de entity storage API, references por ID, JSON en campos long
 */

use Drupal\ecosistema_jaraba_core\Entity\Vertical;
use Drupal\ecosistema_jaraba_core\Entity\SaasPlan;
use Drupal\ecosistema_jaraba_core\Entity\Tenant;
use Drupal\jaraba_page_builder\Entity\PageContent;
use Drupal\jaraba_site_builder\Entity\SiteMenu;
use Drupal\jaraba_site_builder\Entity\SiteMenuItem;

// Resumen de entidades creadas.
$summary = [
  'vertical' => NULL,
  'plan' => NULL,
  'tenant' => NULL,
  'pages' => [],
  'menu' => NULL,
  'menu_items' => [],
];

try {
  echo "\n===========================================\n";
  echo "Aprovisionamiento de pepejaraba.com\n";
  echo "===========================================\n\n";

  // ============================================================================
  // 1. VERTICAL: Marca Personal
  // ============================================================================
  echo "[1/7] Verificando Vertical 'Marca Personal'...\n";

  $verticalStorage = \Drupal::entityTypeManager()->getStorage('vertical');
  $existingVerticals = $verticalStorage->loadByProperties(['name' => 'Marca Personal']);

  if (!empty($existingVerticals)) {
    $vertical = reset($existingVerticals);
    echo "  ✓ Vertical ya existe (ID: {$vertical->id()})\n";
  }
  else {
    $vertical = Vertical::create([
      'name' => 'Marca Personal',
      'slug' => 'marca_personal',
      'description' => 'Vertical para sitios web de marca personal y portafolios profesionales.',
      'status' => 1,
    ]);
    $vertical->save();
    echo "  ✓ Vertical creado (ID: {$vertical->id()})\n";
  }
  $summary['vertical'] = $vertical->id();

  // ============================================================================
  // 2. SAAS PLAN: Personal Brand Premium
  // ============================================================================
  echo "\n[2/7] Verificando SaaS Plan 'Personal Brand Premium'...\n";

  $planStorage = \Drupal::entityTypeManager()->getStorage('saas_plan');
  $existingPlans = $planStorage->loadByProperties(['name' => 'Personal Brand Premium']);

  if (!empty($existingPlans)) {
    $plan = reset($existingPlans);
    echo "  ✓ Plan ya existe (ID: {$plan->id()})\n";
  }
  else {
    $plan = SaasPlan::create([
      'name' => 'Personal Brand Premium',
      'vertical' => $vertical->id(),
      'price' => 0.00,
      'billing_cycle' => 'monthly',
      'max_users' => 5,
      'max_pages' => 50,
      'max_storage_gb' => 10,
      'features' => json_encode([
        'custom_domain',
        'advanced_seo',
        'analytics',
        'email_integration',
        'multiblock_pages',
      ]),
      'status' => 1,
    ]);
    $plan->save();
    echo "  ✓ Plan creado (ID: {$plan->id()})\n";
  }
  $summary['plan'] = $plan->id();

  // ============================================================================
  // 3. TENANT: Pepe Jaraba - Marca Personal
  // ============================================================================
  echo "\n[3/7] Creando Tenant 'Pepe Jaraba - Marca Personal'...\n";

  $tenantStorage = \Drupal::entityTypeManager()->getStorage('tenant');

  // Verificar si ya existe un tenant con este dominio.
  $existingTenants = $tenantStorage->loadByProperties(['domain' => 'pepejaraba.com']);
  if (!empty($existingTenants)) {
    $tenant = reset($existingTenants);
    echo "  ⚠ Tenant ya existe para pepejaraba.com (ID: {$tenant->id()})\n";
    echo "    Si deseas recrearlo, elimínalo manualmente primero.\n";
  }
  else {
    $tenant = Tenant::create([
      'name' => 'Pepe Jaraba - Marca Personal',
      'vertical' => $vertical->id(),
      'subscription_plan' => $plan->id(),
      'domain' => 'pepejaraba.com',
      'subscription_status' => 'active',
      'admin_user' => 1,
      'theme_overrides' => json_encode([
        'color_primary' => '#FF8C42',
        'color_secondary' => '#00A9A5',
        'color_corporate' => '#233D63',
        'font_family_headings' => 'Montserrat',
        'font_family_body' => 'Roboto',
      ]),
      'status' => 1,
    ]);
    $tenant->save();
    echo "  ✓ Tenant creado (ID: {$tenant->id()})\n";

    // Obtener el Group ID creado automáticamente por Tenant::postSave().
    $groupId = $tenant->get('group_id')->target_id;
    if ($groupId) {
      echo "    → Group asociado (ID: {$groupId})\n";
    }
    else {
      echo "    ⚠ No se creó Group automáticamente. Verifica módulo group.\n";
    }
  }
  $summary['tenant'] = $tenant->id();

  // Obtener Group ID para usar en páginas.
  $tenantGroupId = $tenant->get('group_id')->target_id;
  if (!$tenantGroupId) {
    throw new \Exception('No se pudo obtener el Group ID del tenant. Verifica la configuración del módulo group.');
  }

  // ============================================================================
  // 4. PAGECONTENT: 7 páginas con secciones multiblock
  // ============================================================================
  echo "\n[4/7] Creando páginas (PageContent)...\n";

  $pageStorage = \Drupal::entityTypeManager()->getStorage('page_content');
  $uuidService = \Drupal::service('uuid');

  // ---------------------------------------------------------------------------
  // Página 1: Homepage
  // ---------------------------------------------------------------------------
  $existingHome = $pageStorage->loadByProperties([
    'tenant_id' => $tenantGroupId,
    'path_alias' => '/',
  ]);

  if (empty($existingHome)) {
    $sectionsHome = [
      [
        'uuid' => $uuidService->generate(),
        'template_id' => 'parallax_hero',
        'weight' => 0,
        'content' => [
          'eyebrow' => 'Pepe Jaraba',
          'title' => '¿Desempleado, emprendedor o con un pequeño negocio? Te ayudo a dar el salto digital.',
          'subtitle' => 'Transformación digital sin rodeos. Sin humo. Sin jerga. Sin letra pequeña.',
          'cta_primary_text' => 'Diagnóstico Gratuito',
          'cta_primary_url' => '/contacto',
          'cta_secondary_text' => 'Descubre el Ecosistema',
          'cta_secondary_url' => '/ecosistema',
          'scroll_text' => 'Conoce más',
        ],
      ],
      [
        'uuid' => $uuidService->generate(),
        'template_id' => 'logo_cloud',
        'weight' => 1,
        'content' => [
          'title' => 'Confianza de instituciones y empresas',
          'logos' => [
            ['name' => 'Junta de Andalucía', 'alt' => 'Junta de Andalucía'],
            ['name' => 'Diputación de Córdoba', 'alt' => 'Diputación de Córdoba'],
            ['name' => 'Ayuntamiento de Santaella', 'alt' => 'Ayuntamiento de Santaella'],
            ['name' => 'Universidad de Córdoba', 'alt' => 'Universidad de Córdoba'],
            ['name' => 'CADE', 'alt' => 'CADE Andalucía Emprende'],
          ],
        ],
      ],
      [
        'uuid' => $uuidService->generate(),
        'template_id' => 'feature_cards_three',
        'weight' => 2,
        'content' => [
          'title' => '¿Cuál es tu situación?',
          'subtitle' => 'Tres caminos, un ecosistema',
          'cards' => [
            [
              'title' => 'Tengo un Negocio que no Tiene Tiempo',
              'description' => 'PYMEs, autónomos y comercios que necesitan digitalizarse sin complicaciones.',
              'icon' => 'business/store',
              'cta_text' => 'AgroConecta, ComercioConecta, ServiciosConecta',
              'cta_url' => '/ecosistema',
            ],
            [
              'title' => 'Tengo una Idea que Necesita un Impulso',
              'description' => 'Emprendedores que buscan validar y lanzar su proyecto digital.',
              'icon' => 'business/rocket',
              'cta_text' => 'Emprendimiento',
              'cta_url' => '/ecosistema',
            ],
            [
              'title' => 'Soy un Talento que se Siente Invisible',
              'description' => 'Profesionales en búsqueda activa que quieren destacar con competencias digitales.',
              'icon' => 'education/graduation-cap',
              'cta_text' => 'Empleabilidad',
              'cta_url' => '/ecosistema',
            ],
          ],
        ],
      ],
      [
        'uuid' => $uuidService->generate(),
        'template_id' => 'icon_grid',
        'weight' => 3,
        'content' => [
          'title' => 'El Ecosistema Jaraba',
          'subtitle' => '5 verticales diseñadas para impulsar tu transformación digital',
          'items' => [
            ['title' => 'AgroConecta', 'description' => 'Marketplace agroalimentario con trazabilidad', 'icon' => 'agro/leaf'],
            ['title' => 'ComercioConecta', 'description' => 'Digitalización del comercio local', 'icon' => 'business/store'],
            ['title' => 'ServiciosConecta', 'description' => 'Profesionales y servicios digitalizados', 'icon' => 'business/briefcase'],
            ['title' => 'Emprendimiento', 'description' => 'De la idea al negocio digital', 'icon' => 'business/rocket'],
            ['title' => 'Empleabilidad', 'description' => 'Competencias digitales para el empleo', 'icon' => 'education/graduation-cap'],
          ],
        ],
      ],
      [
        'uuid' => $uuidService->generate(),
        'template_id' => 'testimonial_carousel',
        'weight' => 4,
        'content' => [
          'title' => 'Lo que dicen quienes ya han dado el salto',
          'testimonials' => [
            ['name' => 'María G.', 'role' => 'Cooperativa Oleícola', 'text' => 'Gracias al ecosistema, ahora vendemos aceite online a toda España.', 'rating' => 5],
            ['name' => 'Antonio L.', 'role' => 'Emprendedor', 'text' => 'Validé mi idea en una semana. Sin inversión inicial.', 'rating' => 5],
            ['name' => 'Carmen R.', 'role' => 'Profesional en transición', 'text' => 'A los 48 años, me reinventé digitalmente. Hoy tengo un trabajo que me apasiona.', 'rating' => 5],
          ],
        ],
      ],
      [
        'uuid' => $uuidService->generate(),
        'template_id' => 'newsletter_cta',
        'weight' => 5,
        'content' => [
          'title' => 'Kit de Impulso Digital Gratuito',
          'subtitle' => 'Descarga tu guía práctica con los primeros pasos para dar el salto digital.',
          'cta_text' => 'Descargar Gratis',
          'placeholder' => 'Tu email',
        ],
      ],
      [
        'uuid' => $uuidService->generate(),
        'template_id' => 'footer_four_columns',
        'weight' => 6,
        'content' => [
          'columns' => [
            ['title' => 'Pepe Jaraba', 'links' => [['text' => 'Sobre Mí', 'url' => '/sobre'], ['text' => 'Servicios', 'url' => '/servicios'], ['text' => 'Blog', 'url' => '/blog']]],
            ['title' => 'Ecosistema', 'links' => [['text' => 'AgroConecta', 'url' => '/ecosistema'], ['text' => 'ComercioConecta', 'url' => '/ecosistema'], ['text' => 'ServiciosConecta', 'url' => '/ecosistema']]],
            ['title' => 'Recursos', 'links' => [['text' => 'Kit de Impulso', 'url' => '/recursos'], ['text' => 'Blog', 'url' => '/blog'], ['text' => 'Contacto', 'url' => '/contacto']]],
            ['title' => 'Legal', 'links' => [['text' => 'Política de Privacidad', 'url' => '/privacidad'], ['text' => 'Aviso Legal', 'url' => '/aviso-legal'], ['text' => 'Cookies', 'url' => '/cookies']]],
          ],
          'copyright' => '© 2026 Pepe Jaraba. Ecosistema Jaraba Impact Platform.',
        ],
      ],
    ];

    $pageHome = PageContent::create([
      'title' => 'Inicio',
      'tenant_id' => $tenantGroupId,
      'template_id' => 'multiblock',
      'path_alias' => '/',
      'meta_title' => 'Pepe Jaraba | Transformación Digital Sin Rodeos',
      'meta_description' => 'Te ayudo a dar el salto digital. Sin humo, sin jerga, sin letra pequeña. AgroConecta, Emprendimiento, Empleabilidad.',
      'layout_mode' => 'multiblock',
      'sections' => json_encode($sectionsHome),
      'status' => 1,
      'uid' => 1,
    ]);
    $pageHome->save();
    echo "  ✓ Homepage creada (ID: {$pageHome->id()})\n";
    $summary['pages'][] = ['title' => 'Inicio', 'id' => $pageHome->id()];
  }
  else {
    $pageHome = reset($existingHome);
    echo "  ⏭  Homepage ya existe (ID: {$pageHome->id()})\n";
    $summary['pages'][] = ['title' => 'Inicio', 'id' => $pageHome->id()];
  }

  // ---------------------------------------------------------------------------
  // Página 2: Sobre Mí
  // ---------------------------------------------------------------------------
  $existingSobre = $pageStorage->loadByProperties([
    'tenant_id' => $tenantGroupId,
    'path_alias' => '/sobre',
  ]);

  if (empty($existingSobre)) {
    $sectionsSobre = [
      [
        'uuid' => $uuidService->generate(),
        'template_id' => 'hero_split_text_image',
        'weight' => 0,
        'content' => [
          'title' => 'Sobre Mí',
          'subtitle' => 'De Santaella al mundo digital',
          'text' => 'Soy Pepe Jaraba, consultor de transformación digital con sede en Santaella, Córdoba. Mi misión: democratizar la tecnología para que cualquier persona, negocio o comunidad pueda dar el salto digital. Sin humo, sin jerga, sin letra pequeña.',
        ],
      ],
      [
        'uuid' => $uuidService->generate(),
        'template_id' => 'timeline',
        'weight' => 1,
        'content' => [
          'title' => 'Mi Trayectoria',
          'items' => [
            ['year' => '2003', 'title' => 'Primeros pasos en tecnología', 'description' => 'Inicio en el sector IT con foco en soluciones para PYMEs.'],
            ['year' => '2010', 'title' => 'Consultoría digital', 'description' => 'Especialización en transformación digital para comercios y cooperativas.'],
            ['year' => '2018', 'title' => 'Formación e impacto social', 'description' => 'Colaboraciones con Junta de Andalucía y programas de empleabilidad.'],
            ['year' => '2024', 'title' => 'Ecosistema Jaraba', 'description' => 'Lanzamiento de la Plataforma de Ecosistemas con 5 verticales.'],
          ],
        ],
      ],
      [
        'uuid' => $uuidService->generate(),
        'template_id' => 'feature_cards_three',
        'weight' => 2,
        'content' => [
          'title' => 'Valores',
          'cards' => [
            ['title' => 'Sin Humo', 'description' => 'Comunicación directa, honesta y sin adornos. Lo que ves es lo que hay.', 'icon' => 'ui/check-circle'],
            ['title' => 'Impacto Real', 'description' => 'Cada proyecto debe generar valor medible para personas y comunidades.', 'icon' => 'business/target'],
            ['title' => 'Tecnología Accesible', 'description' => 'La transformación digital no es un lujo. Es un derecho.', 'icon' => 'ui/globe'],
          ],
        ],
      ],
    ];

    $pageSobre = PageContent::create([
      'title' => 'Sobre Mí',
      'tenant_id' => $tenantGroupId,
      'template_id' => 'multiblock',
      'path_alias' => '/sobre',
      'meta_title' => 'Sobre Pepe Jaraba | Transformación Digital Sin Rodeos',
      'meta_description' => 'Conozca a Pepe Jaraba: consultor de transformación digital en Santaella, Córdoba. Más de 20 años conectando personas, tecnología e impacto social.',
      'layout_mode' => 'multiblock',
      'sections' => json_encode($sectionsSobre),
      'status' => 1,
      'uid' => 1,
    ]);
    $pageSobre->save();
    echo "  ✓ Página 'Sobre Mí' creada (ID: {$pageSobre->id()})\n";
    $summary['pages'][] = ['title' => 'Sobre Mí', 'id' => $pageSobre->id()];
  }
  else {
    $pageSobre = reset($existingSobre);
    echo "  ⏭  Página 'Sobre Mí' ya existe (ID: {$pageSobre->id()})\n";
    $summary['pages'][] = ['title' => 'Sobre Mí', 'id' => $pageSobre->id()];
  }

  // ---------------------------------------------------------------------------
  // Página 3: Servicios
  // ---------------------------------------------------------------------------
  $existingServicios = $pageStorage->loadByProperties([
    'tenant_id' => $tenantGroupId,
    'path_alias' => '/servicios',
  ]);

  if (empty($existingServicios)) {
    $sectionsServicios = [
      [
        'uuid' => $uuidService->generate(),
        'template_id' => 'hero_centered',
        'weight' => 0,
        'content' => [
          'title' => 'Servicios',
          'subtitle' => 'La escalera de valor: elige tu punto de entrada',
        ],
      ],
      [
        'uuid' => $uuidService->generate(),
        'template_id' => 'pricing_table',
        'weight' => 1,
        'content' => [
          'title' => 'Planes y Servicios',
          'plans' => [
            ['name' => 'Diagnóstico Digital', 'price' => 'Gratis', 'features' => ['Evaluación de presencia digital', 'Informe de oportunidades', 'Hoja de ruta personalizada'], 'cta_text' => 'Solicitar', 'cta_url' => '/contacto', 'highlighted' => FALSE],
            ['name' => 'Mentoría Express', 'price' => '97€', 'period' => 'sesión', 'features' => ['Sesión 1:1 de 60 minutos', 'Plan de acción concreto', 'Seguimiento por email 7 días'], 'cta_text' => 'Reservar', 'cta_url' => '/contacto', 'highlighted' => FALSE],
            ['name' => 'Programa Impulso', 'price' => '297€', 'period' => 'mes', 'features' => ['4 sesiones mensuales', 'Acceso a herramientas', 'Soporte WhatsApp', 'Dashboard de progreso'], 'cta_text' => 'Empezar', 'cta_url' => '/contacto', 'highlighted' => TRUE],
            ['name' => 'Consultoría Premium', 'price' => '497€', 'period' => 'mes', 'features' => ['Dedicación preferente', 'Implementación guiada', 'Revisión semanal', 'Acceso a todo el ecosistema'], 'cta_text' => 'Contactar', 'cta_url' => '/contacto', 'highlighted' => FALSE],
          ],
        ],
      ],
    ];

    $pageServicios = PageContent::create([
      'title' => 'Servicios',
      'tenant_id' => $tenantGroupId,
      'template_id' => 'multiblock',
      'path_alias' => '/servicios',
      'meta_title' => 'Servicios de Transformación Digital | Pepe Jaraba',
      'meta_description' => 'Desde diagnóstico gratuito hasta consultoría premium. Descubre cómo puedo ayudarte a dar el salto digital.',
      'layout_mode' => 'multiblock',
      'sections' => json_encode($sectionsServicios),
      'status' => 1,
      'uid' => 1,
    ]);
    $pageServicios->save();
    echo "  ✓ Página 'Servicios' creada (ID: {$pageServicios->id()})\n";
    $summary['pages'][] = ['title' => 'Servicios', 'id' => $pageServicios->id()];
  }
  else {
    $pageServicios = reset($existingServicios);
    echo "  ⏭  Página 'Servicios' ya existe (ID: {$pageServicios->id()})\n";
    $summary['pages'][] = ['title' => 'Servicios', 'id' => $pageServicios->id()];
  }

  // ---------------------------------------------------------------------------
  // Página 4: Ecosistema
  // ---------------------------------------------------------------------------
  $existingEcosistema = $pageStorage->loadByProperties([
    'tenant_id' => $tenantGroupId,
    'path_alias' => '/ecosistema',
  ]);

  if (empty($existingEcosistema)) {
    $sectionsEcosistema = [
      [
        'uuid' => $uuidService->generate(),
        'template_id' => 'hero_centered',
        'weight' => 0,
        'content' => [
          'title' => 'El Ecosistema Jaraba',
          'subtitle' => '5 verticales interconectadas para cubrir todas las necesidades de la transformación digital',
        ],
      ],
      [
        'uuid' => $uuidService->generate(),
        'template_id' => 'bento_grid',
        'weight' => 1,
        'content' => [
          'title' => 'Verticales',
          'items' => [
            ['title' => 'AgroConecta', 'description' => 'Marketplace agroalimentario con trazabilidad blockchain, logística integrada y conexión directa productor-consumidor. Desde la cooperativa al consumidor final.', 'icon' => 'agro/leaf', 'size' => 'large', 'cta_text' => 'Explorar', 'cta_url' => '#'],
            ['title' => 'ComercioConecta', 'description' => 'Digitalización integral del comercio local: presencia online, gestión de inventario, fidelización y delivery.', 'icon' => 'business/store', 'size' => 'medium'],
            ['title' => 'ServiciosConecta', 'description' => 'Plataforma para profesionales: agenda online, facturación, CRM y presencia digital.', 'icon' => 'business/briefcase', 'size' => 'medium'],
            ['title' => 'Emprendimiento', 'description' => 'Validación de ideas, MVP, mentoring y conexión con inversores. De 0 a negocio.', 'icon' => 'business/rocket', 'size' => 'medium'],
            ['title' => 'Empleabilidad', 'description' => 'Competencias digitales, certificaciones, matching laboral y marca personal profesional.', 'icon' => 'education/graduation-cap', 'size' => 'medium'],
          ],
        ],
      ],
    ];

    $pageEcosistema = PageContent::create([
      'title' => 'Ecosistema',
      'tenant_id' => $tenantGroupId,
      'template_id' => 'multiblock',
      'path_alias' => '/ecosistema',
      'meta_title' => 'Ecosistema Jaraba | 5 Verticales de Transformación Digital',
      'meta_description' => 'AgroConecta, ComercioConecta, ServiciosConecta, Emprendimiento y Empleabilidad. Un ecosistema completo para el salto digital.',
      'layout_mode' => 'multiblock',
      'sections' => json_encode($sectionsEcosistema),
      'status' => 1,
      'uid' => 1,
    ]);
    $pageEcosistema->save();
    echo "  ✓ Página 'Ecosistema' creada (ID: {$pageEcosistema->id()})\n";
    $summary['pages'][] = ['title' => 'Ecosistema', 'id' => $pageEcosistema->id()];
  }
  else {
    $pageEcosistema = reset($existingEcosistema);
    echo "  ⏭  Página 'Ecosistema' ya existe (ID: {$pageEcosistema->id()})\n";
    $summary['pages'][] = ['title' => 'Ecosistema', 'id' => $pageEcosistema->id()];
  }

  // ---------------------------------------------------------------------------
  // Página 5: Blog
  // ---------------------------------------------------------------------------
  $existingBlog = $pageStorage->loadByProperties([
    'tenant_id' => $tenantGroupId,
    'path_alias' => '/blog',
  ]);

  if (empty($existingBlog)) {
    $sectionsBlog = [
      [
        'uuid' => $uuidService->generate(),
        'template_id' => 'hero_centered',
        'weight' => 0,
        'content' => [
          'title' => 'Blog',
          'subtitle' => 'Ideas, guías y casos prácticos para tu transformación digital',
        ],
      ],
      [
        'uuid' => $uuidService->generate(),
        'template_id' => 'text_block',
        'weight' => 1,
        'content' => [
          'content' => '<p>Los artículos del blog se cargan dinámicamente desde el sistema de contenidos. Esta página sirve como contenedor de la sección Blog.</p>',
        ],
      ],
    ];

    $pageBlog = PageContent::create([
      'title' => 'Blog',
      'tenant_id' => $tenantGroupId,
      'template_id' => 'multiblock',
      'path_alias' => '/blog',
      'meta_title' => 'Blog | Pepe Jaraba - Transformación Digital',
      'meta_description' => 'Artículos prácticos sobre empleabilidad, emprendimiento, comercio local y transformación digital.',
      'layout_mode' => 'multiblock',
      'sections' => json_encode($sectionsBlog),
      'status' => 1,
      'uid' => 1,
    ]);
    $pageBlog->save();
    echo "  ✓ Página 'Blog' creada (ID: {$pageBlog->id()})\n";
    $summary['pages'][] = ['title' => 'Blog', 'id' => $pageBlog->id()];
  }
  else {
    $pageBlog = reset($existingBlog);
    echo "  ⏭  Página 'Blog' ya existe (ID: {$pageBlog->id()})\n";
    $summary['pages'][] = ['title' => 'Blog', 'id' => $pageBlog->id()];
  }

  // ---------------------------------------------------------------------------
  // Página 6: Recursos
  // ---------------------------------------------------------------------------
  $existingRecursos = $pageStorage->loadByProperties([
    'tenant_id' => $tenantGroupId,
    'path_alias' => '/recursos',
  ]);

  if (empty($existingRecursos)) {
    $sectionsRecursos = [
      [
        'uuid' => $uuidService->generate(),
        'template_id' => 'hero_centered',
        'weight' => 0,
        'content' => [
          'title' => 'Recursos',
          'subtitle' => 'Herramientas gratuitas para empezar tu transformación',
        ],
      ],
      [
        'uuid' => $uuidService->generate(),
        'template_id' => 'feature_cards_three',
        'weight' => 1,
        'content' => [
          'title' => 'Descargables',
          'cards' => [
            ['title' => 'Kit de Impulso Digital', 'description' => 'Tu guía paso a paso para los primeros 30 días de transformación digital.', 'icon' => 'ui/download', 'cta_text' => 'Descargar', 'cta_url' => '#'],
            ['title' => 'Checklist de Presencia Online', 'description' => '20 puntos esenciales para evaluar y mejorar tu visibilidad digital.', 'icon' => 'ui/check-square', 'cta_text' => 'Descargar', 'cta_url' => '#'],
            ['title' => 'Plantilla de Plan Digital', 'description' => 'Plantilla editable para crear tu hoja de ruta de transformación.', 'icon' => 'ui/file-text', 'cta_text' => 'Descargar', 'cta_url' => '#'],
          ],
        ],
      ],
    ];

    $pageRecursos = PageContent::create([
      'title' => 'Recursos',
      'tenant_id' => $tenantGroupId,
      'template_id' => 'multiblock',
      'path_alias' => '/recursos',
      'meta_title' => 'Recursos Gratuitos | Pepe Jaraba',
      'meta_description' => 'Descarga guías, plantillas y herramientas gratuitas para tu transformación digital.',
      'layout_mode' => 'multiblock',
      'sections' => json_encode($sectionsRecursos),
      'status' => 1,
      'uid' => 1,
    ]);
    $pageRecursos->save();
    echo "  ✓ Página 'Recursos' creada (ID: {$pageRecursos->id()})\n";
    $summary['pages'][] = ['title' => 'Recursos', 'id' => $pageRecursos->id()];
  }
  else {
    $pageRecursos = reset($existingRecursos);
    echo "  ⏭  Página 'Recursos' ya existe (ID: {$pageRecursos->id()})\n";
    $summary['pages'][] = ['title' => 'Recursos', 'id' => $pageRecursos->id()];
  }

  // ---------------------------------------------------------------------------
  // Página 7: Contacto
  // ---------------------------------------------------------------------------
  $existingContacto = $pageStorage->loadByProperties([
    'tenant_id' => $tenantGroupId,
    'path_alias' => '/contacto',
  ]);

  if (empty($existingContacto)) {
    $sectionsContacto = [
      [
        'uuid' => $uuidService->generate(),
        'template_id' => 'hero_centered',
        'weight' => 0,
        'content' => [
          'title' => 'Contacto',
          'subtitle' => '¿Listo para dar el salto? Reserva tu diagnóstico gratuito',
        ],
      ],
      [
        'uuid' => $uuidService->generate(),
        'template_id' => 'contact_form',
        'weight' => 1,
        'content' => [
          'title' => 'Escríbeme',
          'fields' => ['name', 'email', 'phone', 'message'],
          'cta_text' => 'Enviar Mensaje',
        ],
      ],
      [
        'uuid' => $uuidService->generate(),
        'template_id' => 'text_block',
        'weight' => 2,
        'content' => [
          'content' => '<div class="contact-info"><h3>También puedes encontrarme en:</h3><p><strong>Ubicación:</strong> Santaella, Córdoba, España</p><p><strong>Email:</strong> hola@pepejaraba.com</p><p><strong>LinkedIn:</strong> linkedin.com/in/pepejaraba</p></div>',
        ],
      ],
    ];

    $pageContacto = PageContent::create([
      'title' => 'Contacto',
      'tenant_id' => $tenantGroupId,
      'template_id' => 'multiblock',
      'path_alias' => '/contacto',
      'meta_title' => 'Contacto | Pepe Jaraba - Consulta Gratuita',
      'meta_description' => 'Reserva tu diagnóstico digital gratuito. Pepe Jaraba, consultor de transformación digital en Santaella, Córdoba.',
      'layout_mode' => 'multiblock',
      'sections' => json_encode($sectionsContacto),
      'status' => 1,
      'uid' => 1,
    ]);
    $pageContacto->save();
    echo "  ✓ Página 'Contacto' creada (ID: {$pageContacto->id()})\n";
    $summary['pages'][] = ['title' => 'Contacto', 'id' => $pageContacto->id()];
  }
  else {
    $pageContacto = reset($existingContacto);
    echo "  ⏭  Página 'Contacto' ya existe (ID: {$pageContacto->id()})\n";
    $summary['pages'][] = ['title' => 'Contacto', 'id' => $pageContacto->id()];
  }

  // ============================================================================
  // 5. SITEMENU: Menú Principal
  // ============================================================================
  echo "\n[5/7] Creando SiteMenu 'Menú Principal'...\n";

  $menuStorage = \Drupal::entityTypeManager()->getStorage('site_menu');
  $existingMenus = $menuStorage->loadByProperties([
    'tenant_id' => $tenantGroupId,
    'machine_name' => 'main',
  ]);

  if (!empty($existingMenus)) {
    $menu = reset($existingMenus);
    echo "  ⏭  SiteMenu 'main' ya existe (ID: {$menu->id()})\n";
  }
  else {
    $menu = SiteMenu::create([
      'tenant_id' => $tenantGroupId,
      'machine_name' => 'main',
      'label' => 'Menú Principal',
    ]);
    $menu->save();
    echo "  ✓ SiteMenu creado (ID: {$menu->id()})\n";
  }
  $summary['menu'] = $menu->id();

  // ============================================================================
  // 6. SITEMENUITEM: 6 items del menú principal
  // ============================================================================
  echo "\n[6/7] Creando items del menú...\n";

  $menuItemStorage = \Drupal::entityTypeManager()->getStorage('site_menu_item');

  // Array de items a crear.
  $menuItemsData = [
    ['title' => 'Sobre Mí', 'page' => $pageSobre, 'weight' => 0, 'highlight' => 0],
    ['title' => 'Servicios', 'page' => $pageServicios, 'weight' => 1, 'highlight' => 0],
    ['title' => 'Ecosistema', 'page' => $pageEcosistema, 'weight' => 2, 'highlight' => 0],
    ['title' => 'Blog', 'page' => $pageBlog, 'weight' => 3, 'highlight' => 0],
    ['title' => 'Recursos', 'page' => $pageRecursos, 'weight' => 4, 'highlight' => 0],
    ['title' => 'Contacto', 'page' => $pageContacto, 'weight' => 5, 'highlight' => 1],
  ];

  foreach ($menuItemsData as $itemData) {
    $existingItems = $menuItemStorage->loadByProperties([
      'menu_id' => $menu->id(),
      'title' => $itemData['title'],
    ]);

    if (!empty($existingItems)) {
      $item = reset($existingItems);
      echo "  ⏭  Item '{$itemData['title']}' ya existe (ID: {$item->id()})\n";
      $summary['menu_items'][] = ['title' => $itemData['title'], 'id' => $item->id()];
    }
    else {
      $item = SiteMenuItem::create([
        'menu_id' => $menu->id(),
        'title' => $itemData['title'],
        'page_id' => $itemData['page']->id(),
        'item_type' => 'page',
        'weight' => $itemData['weight'],
        'depth' => 0,
        'is_enabled' => 1,
        'highlight' => $itemData['highlight'],
      ]);
      $item->save();
      echo "  ✓ Item '{$itemData['title']}' creado (ID: {$item->id()})\n";
      $summary['menu_items'][] = ['title' => $itemData['title'], 'id' => $item->id()];
    }
  }

  // ============================================================================
  // 7. RESUMEN FINAL
  // ============================================================================
  echo "\n[7/7] Resumen de entidades creadas:\n";
  echo "========================================\n";
  echo "Vertical ID:      {$summary['vertical']}\n";
  echo "SaaS Plan ID:     {$summary['plan']}\n";
  echo "Tenant ID:        {$summary['tenant']}\n";
  echo "Tenant Group ID:  {$tenantGroupId}\n";
  echo "\nPáginas creadas:\n";
  foreach ($summary['pages'] as $page) {
    echo "  - {$page['title']} (ID: {$page['id']})\n";
  }
  echo "\nSiteMenu ID:      {$summary['menu']}\n";
  echo "Items de menú:    " . count($summary['menu_items']) . "\n";
  foreach ($summary['menu_items'] as $menuItem) {
    echo "  - {$menuItem['title']} (ID: {$menuItem['id']})\n";
  }

  echo "\n===========================================\n";
  echo "Aprovisionamiento completado exitosamente.\n";
  echo "===========================================\n\n";
}
catch (\Exception $e) {
  echo "\n❌ ERROR durante el aprovisionamiento:\n";
  echo "Mensaje: {$e->getMessage()}\n";
  echo "Archivo: {$e->getFile()}:{$e->getLine()}\n\n";

  \Drupal::logger('pepejaraba_seed')->error(
    'Error en seed_pepejaraba.php: @message',
    ['@message' => $e->getMessage()]
  );

  echo "Consulta los logs de Drupal para más detalles.\n\n";
}
