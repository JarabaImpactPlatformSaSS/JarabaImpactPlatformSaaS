<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_copilot_v2\Service\BmcValidationService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * API controller para validacion BMC y pivot log.
 */
class BmcApiController extends ControllerBase {

  protected BmcValidationService $bmcValidation;

  /**
   * Constructor.
   */
  public function __construct(BmcValidationService $bmcValidation) {
    $this->bmcValidation = $bmcValidation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_copilot_v2.bmc_validation'),
    );
  }

  /**
   * GET /api/v1/bmc/validation/{userId} - Estado de validacion del BMC.
   */
  public function validation(string $userId): JsonResponse {
    try {
      $state = $this->bmcValidation->getValidationState((int) $userId);

      return new JsonResponse([
        'success' => TRUE,
        'data' => $state,
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * GET /api/v1/bmc/pivot-log/{userId} - Historial de pivots.
   */
  public function pivotLog(string $userId): JsonResponse {
    try {
      $pivots = $this->bmcValidation->getPivotLog((int) $userId);

      return new JsonResponse([
        'success' => TRUE,
        'data' => $pivots,
        'count' => count($pivots),
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => $e->getMessage(),
      ], 500);
    }
  }

}
