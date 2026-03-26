<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Link-in-bio page for Andalucía +ei Instagram/social profiles.
 *
 * Public route, no authentication required.
 *
 * ZERO-REGION-001: Returns render array with #theme.
 * CONTROLLER-READONLY-001: No redeclara $entityTypeManager con readonly.
 * ROUTE-LANGPREFIX-001: All URLs via Url::fromRoute() with try-catch.
 * FUNNEL-COMPLETENESS-001: All links carry UTM params.
 */
class EnlacesController extends ControllerBase {

  /**
   * Logger del módulo.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Construye el controller.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del módulo.
   */
  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('logger.channel.jaraba_andalucia_ei'),
    );
  }

  /**
   * Página link-in-bio de Andalucía +ei.
   *
   * @return array
   *   Render array con #theme => 'andalucia_ei_enlaces'.
   */
  public function enlaces(): array {
    $utm_base = [
      'utm_source' => 'instagram',
      'utm_medium' => 'bio_link',
      'utm_campaign' => 'andalucia_ei_2ed',
    ];

    $links_primary = [
      [
        'label' => $this->t('Solicita tu plaza (participantes)'),
        'sublabel' => $this->t('Gratuito · 45 plazas · Sevilla y Málaga · 528€ incentivo'),
        'url' => $this->buildRouteUrl('jaraba_andalucia_ei.solicitar', $utm_base + ['utm_content' => 'participantes']),
        'icon' => 'education/graduation',
        'color' => 'naranja-impulso',
      ],
      [
        'label' => $this->t('Prueba gratuita para tu negocio'),
        'sublabel' => $this->t('Gestión digital gratis 2-4 semanas · Sin compromiso'),
        'url' => $this->buildRouteUrl('jaraba_andalucia_ei.prueba_gratuita', $utm_base + ['utm_content' => 'negocios']),
        'icon' => 'business/storefront',
        'color' => 'verde-innovacion',
      ],
    ];

    $stats = [
      ['value' => '46%', 'label' => $this->t('inserción laboral 1ª Edición')],
      ['value' => '45', 'label' => $this->t('plazas disponibles')],
      ['value' => '528€', 'label' => $this->t('incentivo por participar')],
    ];

    $links_secondary = [
      [
        'label' => $this->t('Más información del programa'),
        'url' => $this->buildRouteUrl('jaraba_andalucia_ei.reclutamiento', $utm_base + ['utm_content' => 'info']),
        'icon' => 'ui/info-circle',
      ],
      [
        'label' => $this->t('WhatsApp: +34 623 174 304'),
        'sublabel' => $this->t('Respuesta en menos de 2 horas'),
        'href' => 'https://wa.me/34623174304?text=Hola%2C%20quiero%20información%20sobre%20Andalucía%20%2Bei',
        'icon' => 'social/whatsapp',
        'color' => 'whatsapp',
      ],
      [
        'label' => $this->t('Jaraba Impact Platform'),
        'url' => $this->buildRouteUrl('jaraba_page_builder.metodo_landing', $utm_base + ['utm_content' => 'plataforma']),
        'icon' => 'brand/rocket',
      ],
    ];

    $program_info = [
      'name' => $this->t('Programa Andalucía +ei'),
      'edition' => $this->t('2ª Edición'),
      'entity' => $this->t('Plataforma de Ecosistemas Digitales S.L.'),
      'website' => 'plataformadeecosistemas.com',
      'funding' => $this->t('Programa cofinanciado por la Unión Europea · FSE+ Andalucía 2021-2027'),
    ];

    return [
      '#theme' => 'andalucia_ei_enlaces',
      '#links_primary' => $links_primary,
      '#stats' => $stats,
      '#links_secondary' => $links_secondary,
      '#program_info' => $program_info,
      '#cache' => [
        'contexts' => ['url.path'],
        'max-age' => 3600,
      ],
    ];
  }

  /**
   * Genera URL segura desde ruta con UTM params.
   *
   * ROUTE-LANGPREFIX-001: try-catch para evitar WSOD si la ruta no existe.
   *
   * @param string $route_name
   *   Nombre de la ruta Drupal.
   * @param array $utm_params
   *   Parámetros UTM a añadir como query string.
   *
   * @return string
   *   URL absoluta o '#' si la ruta no existe.
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
