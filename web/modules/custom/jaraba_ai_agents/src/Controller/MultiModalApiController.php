<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_ai_agents\Service\MultiModalBridgeService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * API Controller for MultiModal capabilities check (F11).
 */
class MultiModalApiController extends ControllerBase {

  public function __construct(
    protected MultiModalBridgeService $bridge,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_ai_agents.multimodal_bridge'),
    );
  }

  /**
   * GET /api/v1/ai/multimodal/capabilities
   */
  public function getCapabilities(): JsonResponse {
    return new JsonResponse([
      'success' => TRUE,
      'data' => [
        'input' => $this->bridge->getInputCapabilities(),
        'output' => $this->bridge->getOutputCapabilities(),
      ],
    ]);
  }

}
