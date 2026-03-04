<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\NavigationFilterService;
use Drupal\ecosistema_jaraba_core\Service\VerticalBrandSeoService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller para gestion de VerticalBrand.
 *
 * Ruta admin para listar/editar configs de marca por vertical.
 * Ruta API para consumo JS frontend.
 */
class VerticalBrandController extends ControllerBase {

  protected NavigationFilterService $navigationFilter;

  protected VerticalBrandSeoService $verticalBrandSeo;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    // CONTROLLER-READONLY-001: No readonly en propiedades heredadas.
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->navigationFilter = $container->get('ecosistema_jaraba_core.navigation_filter');
    $instance->verticalBrandSeo = $container->get('ecosistema_jaraba_core.vertical_brand_seo');
    return $instance;
  }

  /**
   * API: Devuelve configuracion de marca de un vertical.
   *
   * @param string $vertical
   *   Vertical canonico.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Datos de marca en JSON.
   */
  public function getVerticalBrand(string $vertical): JsonResponse {
    try {
      $storage = $this->entityTypeManager()->getStorage('vertical_brand');
      $entities = $storage->loadByProperties(['vertical' => $vertical]);

      if ($entities === []) {
        return new JsonResponse(['error' => 'Vertical brand not found'], 404);
      }

      /** @var \Drupal\ecosistema_jaraba_core\Entity\VerticalBrandConfig $brand */
      $brand = reset($entities);

      $data = [
        'vertical' => $brand->getVertical(),
        'public_name' => $brand->getPublicName(),
        'tagline' => $brand->getTagline(),
        'description' => $brand->getDescription(),
        'icon' => [
          'category' => $brand->getIconCategory(),
          'name' => $brand->getIconName(),
        ],
        'colors' => [
          'primary' => $brand->getPrimaryColor(),
          'secondary' => $brand->getSecondaryColor(),
        ],
        'seo' => $this->verticalBrandSeo->getSeoMetaTags($vertical),
        'navigation' => $this->navigationFilter->filterMenuItems([], $vertical),
        'revelation_level' => $brand->getRevelationLevel(),
        'enabled' => $brand->isEnabled(),
      ];

      $response = new JsonResponse($data);
      $response->setMaxAge(300);
      return $response;
    }
    catch (\Throwable) {
      return new JsonResponse(['error' => 'Internal error'], 500);
    }
  }

}
