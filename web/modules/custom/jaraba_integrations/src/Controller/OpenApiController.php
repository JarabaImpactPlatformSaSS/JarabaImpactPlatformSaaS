<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_integrations\Service\OpenApiSpecService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller para servir la especificacion OpenAPI 3.0.
 */
class OpenApiController extends ControllerBase {

  protected OpenApiSpecService $openApiSpec;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    // CONTROLLER-READONLY-001: No readonly on inherited properties.
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->openApiSpec = $container->get('jaraba_integrations.openapi_spec');
    return $instance;
  }

  /**
   * Devuelve la spec OpenAPI 3.0.3 como JSON.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response con cache de 1 hora.
   */
  public function getSpec(): JsonResponse {
    $spec = $this->openApiSpec->generateSpec();

    $response = new JsonResponse($spec);
    $response->setMaxAge(3600);
    $response->headers->set('Access-Control-Allow-Origin', '*');

    return $response;
  }

}
