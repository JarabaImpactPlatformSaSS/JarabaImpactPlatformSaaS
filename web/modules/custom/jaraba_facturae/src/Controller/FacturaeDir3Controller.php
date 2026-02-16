<?php

declare(strict_types=1);

namespace Drupal\jaraba_facturae\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Trait\ApiResponseTrait;
use Drupal\jaraba_facturae\Service\FacturaeDIR3Service;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * REST API controller for DIR3 directory search.
 *
 * Provides autocomplete search, unit lookup, and validation
 * for DIR3 organizational codes used in B2G invoicing.
 *
 * Spec: Doc 180, Seccion 4.4.
 * Plan: FASE 7, entregable F7-4.
 */
class FacturaeDir3Controller extends ControllerBase {

  use ApiResponseTrait;

  public function __construct(
    protected readonly FacturaeDIR3Service $dir3Service,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_facturae.dir3_service'),
    );
  }

  /**
   * GET /api/v1/facturae/dir3/search?q={query}&type={type}.
   */
  public function search(Request $request): JsonResponse {
    $query = $request->query->get('q', '');
    $type = $request->query->get('type', 'all');

    if (strlen($query) < 3) {
      return $this->apiError('Query must be at least 3 characters.', 'BAD_REQUEST', 400);
    }

    $units = $this->dir3Service->search($query, $type);

    $data = array_map(fn($unit) => $unit->toArray(), $units);

    return $this->apiSuccess($data);
  }

  /**
   * GET /api/v1/facturae/dir3/{code}.
   */
  public function getUnit(string $code): JsonResponse {
    $unit = $this->dir3Service->getUnit($code);

    if ($unit === NULL) {
      return $this->apiError("DIR3 unit '$code' not found.", 'NOT_FOUND', 404);
    }

    return $this->apiSuccess($unit->toArray());
  }

  /**
   * GET /api/v1/facturae/dir3/{code}/validate.
   */
  public function validate(string $code, Request $request): JsonResponse {
    $type = $request->query->get('type', 'all');

    $isValid = $this->dir3Service->validateCentre($code, $type);

    return $this->apiSuccess([
      'code' => $code,
      'type' => $type,
      'valid' => $isValid,
    ]);
  }

}
