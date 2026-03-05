<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_comercio_conecta\Service\ProductImportService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * P1-06: Bulk CSV import controller for ComercioConecta merchants.
 *
 * Ruta: /mi-comercio/importar
 * Permite subir un CSV con productos y procesarlos en batch.
 *
 * CONTROLLER-READONLY-001: entityTypeManager asignado manualmente.
 * TENANT-001: Productos se crean bajo el tenant del merchant.
 * CSRF-API-001: _csrf_request_header_token en ruta de proceso.
 */
class MerchantImportController extends ControllerBase {

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    protected readonly AccountProxyInterface $account,
    protected readonly ProductImportService $importService,
  ) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('jaraba_comercio_conecta.product_import'),
    );
  }

  /**
   * Página de importación CSV.
   */
  public function importPage(): array {
    // Obtener perfil del merchant actual.
    $merchants = $this->entityTypeManager->getStorage('merchant_profile')
      ->loadByProperties(['uid' => $this->account->id()]);
    $merchant = reset($merchants);

    if (!$merchant) {
      return [
        '#markup' => $this->t('No tienes un perfil de comerciante asociado.'),
      ];
    }

    return [
      '#theme' => 'comercio_merchant_import',
      '#merchant' => $merchant,
      '#max_file_size' => '5MB',
      '#accepted_formats' => 'CSV',
      '#sample_url' => '/modules/custom/jaraba_comercio_conecta/assets/sample-import.csv',
      '#attached' => [
        'library' => [
          'jaraba_comercio_conecta/merchant-import',
        ],
        'drupalSettings' => [
          'comercioImport' => [
            'processUrl' => '/api/v1/comercio/import/process',
            'maxRows' => 500,
          ],
        ],
      ],
    ];
  }

  /**
   * API: Procesa el CSV subido.
   */
  public function processImport(Request $request): JsonResponse {
    $file = $request->files->get('csv_file');
    if (!$file || !$file->isValid()) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => (string) $this->t('No se ha subido un archivo válido.'),
      ], 400);
    }

    // Verificar extensión.
    $extension = strtolower($file->getClientOriginalExtension());
    if ($extension !== 'csv') {
      return new JsonResponse([
        'success' => FALSE,
        'error' => (string) $this->t('Solo se aceptan archivos CSV.'),
      ], 400);
    }

    // Obtener merchant del usuario actual.
    $merchants = $this->entityTypeManager->getStorage('merchant_profile')
      ->loadByProperties(['uid' => $this->account->id()]);
    $merchant = reset($merchants);

    if (!$merchant) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => (string) $this->t('No tienes un perfil de comerciante.'),
      ], 403);
    }

    try {
      $result = $this->importService->processFile(
        $file->getRealPath(),
        (int) $merchant->id(),
        (int) ($merchant->get('tenant_id')->target_id ?? 0),
      );

      return new JsonResponse([
        'success' => TRUE,
        'imported' => $result['imported'],
        'skipped' => $result['skipped'],
        'errors' => $result['errors'],
        'message' => (string) $this->t('@count productos importados correctamente.', [
          '@count' => $result['imported'],
        ]),
      ]);
    }
    catch (\Throwable $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => (string) $this->t('Error al procesar el archivo: @error', [
          '@error' => $e->getMessage(),
        ]),
      ], 500);
    }
  }

}
