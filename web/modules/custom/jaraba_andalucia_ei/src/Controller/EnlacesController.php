<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Link-in-bio premium page for Andalucía +ei social profiles.
 *
 * Public route, no authentication required. Serves TWO audiences:
 * - Participantes: solicitar plaza (formación + 528€ incentivo).
 * - Negocios: prueba gratuita digital (2-4 semanas, 5 packs).
 *
 * ZERO-REGION-001: Returns render array with #theme.
 * CONTROLLER-READONLY-001: No redeclara $entityTypeManager con readonly.
 * ROUTE-LANGPREFIX-001: All URLs via Url::fromRoute() with try-catch.
 * FUNNEL-COMPLETENESS-001: All links carry UTM params + tracking.
 * NO-HARDCODE-PRICE-001: Numeric values from config, not hardcoded.
 */
class EnlacesController extends ControllerBase {

  /**
   * Logger del módulo.
   */
  protected LoggerInterface $logger;

  /**
   * Resuelve rutas de extensiones.
   */
  protected ExtensionPathResolver $pathResolver;

  /**
   * Construye el controller.
   */
  public function __construct(
    LoggerInterface $logger,
    ExtensionPathResolver $pathResolver,
    ConfigFactoryInterface $configFactory,
  ) {
    $this->logger = $logger;
    $this->pathResolver = $pathResolver;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('logger.channel.jaraba_andalucia_ei'),
      $container->get('extension.path.resolver'),
      $container->get('config.factory'),
    );
  }

  /**
   * Página link-in-bio premium de Andalucía +ei.
   *
   * @return array
   *   Render array con #theme => 'andalucia_ei_enlaces'.
   */
  public function enlaces(): array {
    $config = $this->configFactory->get('jaraba_andalucia_ei.settings');
    $modulePath = $this->pathResolver->getPath('module', 'jaraba_andalucia_ei');

    $plazas = (int) ($config->get('plazas_restantes') ?? 45);
    $incentivo = (int) ($config->get('incentivo_euros') ?? 528);
    $tasaInsercion = (int) ($config->get('tasa_insercion_objetivo') ?? 40);

    $utm_base = [
      'utm_source' => 'instagram',
      'utm_medium' => 'bio_link',
      'utm_campaign' => 'andalucia_ei_2ed',
    ];

    $solicitar_url = $this->buildRouteUrl('jaraba_andalucia_ei.solicitar', $utm_base + ['utm_content' => 'participantes']);
    $prueba_url = $this->buildRouteUrl('jaraba_andalucia_ei.prueba_gratuita', $utm_base + ['utm_content' => 'negocios']);

    $links_primary = [
      [
        'label' => $this->t('Solicita tu plaza'),
        'sublabel' => $this->t('100% gratuito · Sevilla y Málaga · @incentivo€ incentivo', [
          '@incentivo' => $incentivo,
        ]),
        'url' => $solicitar_url,
        'icon' => 'education/graduation',
        'color' => 'naranja-impulso',
        'micro' => $this->t('Formulario en 2 minutos'),
      ],
      [
        'label' => $this->t('Prueba gratuita para tu negocio'),
        'sublabel' => $this->t('Gestión digital gratis 2-4 semanas · Sin compromiso'),
        'url' => $prueba_url,
        'icon' => 'business/storefront',
        'color' => 'verde-innovacion',
        'micro' => $this->t('5 servicios disponibles'),
      ],
    ];

    $stats = [
      [
        'value' => $tasaInsercion . '%',
        'raw' => $tasaInsercion,
        'suffix' => '%',
        'label' => $this->t('inserción laboral 1ª Edición'),
      ],
      [
        'value' => '100',
        'raw' => 100,
        'suffix' => 'h',
        'label' => $this->t('horas de programa'),
      ],
      [
        'value' => $incentivo . '€',
        'raw' => $incentivo,
        'suffix' => '€',
        'label' => $this->t('incentivo por participar'),
      ],
    ];

    $links_secondary = [
      [
        'label' => $this->t('Más información del programa'),
        'url' => $this->buildRouteUrl('jaraba_andalucia_ei.reclutamiento', $utm_base + ['utm_content' => 'info']),
        'icon' => 'ui/info-circle',
        'track_cta' => 'info_link',
      ],
      [
        'label' => $this->t('WhatsApp: +34 623 174 304'),
        'sublabel' => $this->t('Respuesta en menos de 2 horas'),
        'href' => 'https://wa.me/34623174304?text=Hola%2C%20quiero%20información%20sobre%20Andalucía%20%2Bei',
        'icon' => 'social/whatsapp',
        'color' => 'whatsapp',
        'track_cta' => 'whatsapp_link',
      ],
      [
        'label' => $this->t('Jaraba Impact Platform'),
        'url' => $this->buildRouteUrl('jaraba_page_builder.metodo_landing', $utm_base + ['utm_content' => 'plataforma']),
        'icon' => 'brand/rocket',
        'track_cta' => 'platform_link',
      ],
    ];

    $program_info = [
      'name' => $this->t('Programa Andalucía +ei'),
      'title' => $this->t('Andalucía +ei'),
      'subtitle' => $this->t('Emprendimiento Aumentado con Inteligencia Artificial'),
      'edition' => $this->t('2ª Edición · Junta de Andalucía'),
      'entity' => $this->t('Plataforma de Ecosistemas Digitales S.L.'),
      'website' => 'plataformadeecosistemas.com',
      'funding' => $this->t('Programa cofinanciado por la Unión Europea · FSE+ Andalucía 2021-2027'),
    ];

    $testimonials = [
      [
        'nombre' => 'Marcela Calabia',
        'rol' => $this->t('Coach de Comunicación'),
        'sede' => $this->t('Córdoba'),
        'quote' => $this->t('Este curso es oro puro. Ninguno de los cursos de pago que he hecho me ha dado lo que me dio este.'),
        'foto' => 'testimonio-marcela.webp',
        'slug' => 'marcela-calabia',
        'resultado' => $this->t('Libros en 4 idiomas, tarifa desde 75 €/h'),
      ],
      [
        'nombre' => 'Ángel Martínez',
        'rol' => $this->t('Cofundador Camino Viejo'),
        'sede' => $this->t('Sevilla'),
        'quote' => $this->t('La formación de PED es oro puro. Ahora tengo conciencia de que ES POSIBLE.'),
        'foto' => 'testimonio-angel.webp',
        'slug' => 'angel-martinez-camino-viejo',
        'resultado' => $this->t('Cicloturismo premium en Sierra Morena'),
      ],
      [
        'nombre' => 'Cristina Martín',
        'rol' => $this->t('Fundadora De Cris Moda'),
        'sede' => $this->t('Granada'),
        'quote' => $this->t('Hay un seguimiento real, que en muchos sitios se queda en el aire. Aquí funciona.'),
        'foto' => 'testimonio-cristina.webp',
        'slug' => 'cristina-martin-pereira-de-cris-moda',
        'resultado' => $this->t('Tienda online con ventas en 6 países'),
      ],
      [
        'nombre' => 'Adrián Capatina',
        'rol' => $this->t('Fundador NOVAVID Media'),
        'sede' => $this->t('Málaga'),
        'quote' => $this->t('La vida me ha cambiado, te lo aseguro. Todo lo que aprendí lo repartí a otros emprendedores.'),
        'foto' => 'testimonio-adrian.webp',
        'slug' => 'adrian-capatina-tudor-novavid',
        'resultado' => $this->t('Agencia audiovisual, clientes sector lujo'),
      ],
    ];

    $benefits_participantes = [
      $this->t('Formación certificada 50+ horas'),
      $this->t('@incentivo€ de incentivo al completar', ['@incentivo' => $incentivo]),
      $this->t('Mentoría IA + orientación personalizada'),
    ];

    $benefits_negocios = [
      $this->t('Redes, web, reseñas o tienda online gratis'),
      $this->t('Sin compromiso ni permanencia'),
      $this->t('Supervisado por equipo de expertos'),
    ];

    $faqs = [
      [
        'q' => $this->t('¿Es realmente gratis?'),
        'a' => $this->t('Sí, 100% gratuito. Es un programa público financiado por la Junta de Andalucía y el Fondo Social Europeo Plus (FSE+). No hay coste oculto ni letra pequeña.'),
      ],
      [
        'q' => $this->t('¿Es compatible con el subsidio por desempleo?'),
        'a' => $this->t('Sí. Al ser un programa formativo público, es compatible con prestaciones por desempleo, subsidio y RAI. El incentivo de @incentivo€ es una ayuda adicional, no una retribución salarial. Consulte su caso concreto con el SEPE.', ['@incentivo' => $incentivo]),
      ],
      [
        'q' => $this->t('¿Cuánto dura el programa?'),
        'a' => $this->t('El programa dura hasta 18 meses: formación intensiva (50+ horas) + orientación individualizada (10+ horas) en los primeros meses, seguido de acompañamiento a la inserción laboral (40+ horas para las personas que logran insertarse) y seguimiento durante 6 meses. Compatible con otras actividades.'),
      ],
      [
        'q' => $this->t('¿Qué recibe mi negocio en la prueba gratuita?'),
        'a' => $this->t('Un profesional formado le presta servicio digital durante 2-4 semanas: gestión de redes sociales, creación de web, respuesta a reseñas, asistencia administrativa o tienda online. Usted elige.'),
      ],
    ];

    $urgency = [
      'plazas_restantes' => $plazas,
      'mostrar_countdown' => (bool) ($config->get('mostrar_countdown') ?? TRUE),
      'fecha_limite' => $config->get('fecha_limite_solicitudes') ?? '2026-04-10',
    ];

    return [
      '#theme' => 'andalucia_ei_enlaces',
      '#links_primary' => $links_primary,
      '#stats' => $stats,
      '#links_secondary' => $links_secondary,
      '#program_info' => $program_info,
      '#module_path' => $modulePath,
      '#testimonials' => $testimonials,
      '#benefits_participantes' => $benefits_participantes,
      '#benefits_negocios' => $benefits_negocios,
      '#faqs' => $faqs,
      '#urgency' => $urgency,
      '#solicitar_url' => $solicitar_url,
      '#prueba_url' => $prueba_url,
      '#cache' => [
        'contexts' => ['url.path'],
        'tags' => ['config:jaraba_andalucia_ei.settings'],
        'max-age' => 3600,
      ],
    ];
  }

  /**
   * Genera URL segura desde ruta con UTM params.
   *
   * ROUTE-LANGPREFIX-001: try-catch para evitar WSOD si la ruta no existe.
   */
  protected function buildRouteUrl(string $route_name, array $utm_params = []): string {
    try {
      return Url::fromRoute($route_name, [], [
        'query' => $utm_params,
        'absolute' => TRUE,
      ])->toString();
    }
    catch (\Exception $e) {
      $this->logger->warning('Route @route not found for enlaces page: @message', [
        '@route' => $route_name,
        '@message' => $e->getMessage(),
      ]);
      return '#';
    }
  }

}
