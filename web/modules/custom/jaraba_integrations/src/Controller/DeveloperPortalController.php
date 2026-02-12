<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_integrations\Service\AppApprovalService;
use Drupal\jaraba_integrations\Service\ConnectorSdkService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller para el portal de desarrolladores de conectores.
 *
 * PROPOSITO:
 * Proporciona una interfaz para que desarrolladores externos:
 * - Creen y gestionen sus conectores
 * - Descarguen el SDK y documentacion
 * - Envien conectores a revision
 * - Vean el estado de sus envios
 *
 * Ruta: /integraciones/developer
 */
class DeveloperPortalController extends ControllerBase {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   El gestor de tipos de entidad.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   El usuario actual.
   * @param \Drupal\jaraba_integrations\Service\AppApprovalService $appApproval
   *   Servicio de aprobacion de apps.
   * @param \Drupal\jaraba_integrations\Service\ConnectorSdkService $connectorSdk
   *   Servicio de SDK para conectores.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    AccountProxyInterface $currentUser,
    protected AppApprovalService $appApproval,
    protected ConnectorSdkService $connectorSdk,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('jaraba_integrations.app_approval'),
      $container->get('jaraba_integrations.connector_sdk'),
    );
  }

  /**
   * Renderiza el dashboard del portal de desarrolladores.
   *
   * @return array
   *   Render array para el portal.
   */
  public function dashboard(): array {
    $myConnectors = $this->getMyConnectors();
    $sdkInfo = [
      'version' => ConnectorSdkService::SDK_VERSION,
      'docs_url' => '/integraciones/developer/docs',
    ];

    return [
      '#theme' => 'jaraba_integrations_developer_portal',
      '#connectors' => $myConnectors,
      '#sdk_info' => $sdkInfo,
      '#pending_review' => $this->getPendingReviewCount(),
      '#attached' => [
        'library' => [
          'jaraba_integrations/developer-portal',
        ],
      ],
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Renderiza la documentacion del SDK.
   *
   * @return array
   *   Render array con la documentacion.
   */
  public function documentation(): array {
    return [
      '#theme' => 'jaraba_integrations_developer_docs',
      '#sdk_version' => ConnectorSdkService::SDK_VERSION,
      '#api_endpoints' => $this->getApiEndpoints(),
      '#attached' => [
        'library' => [
          'jaraba_integrations/developer-portal',
        ],
      ],
      '#cache' => [
        'max-age' => 3600,
      ],
    ];
  }

  /**
   * Obtiene los conectores del desarrollador actual.
   */
  protected function getMyConnectors(): array {
    $connectors = [];
    try {
      $storage = $this->entityTypeManager->getStorage('connector');
      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('developer_uid', (int) $this->currentUser->id())
        ->sort('changed', 'DESC')
        ->execute();

      if (!empty($ids)) {
        foreach ($storage->loadMultiple($ids) as $connector) {
          $connectors[] = [
            'id' => $connector->id(),
            'name' => $connector->label(),
            'status' => $connector->get('approval_status')->value ?? 'draft',
            'version' => $connector->get('version')->value ?? '1.0.0',
            'installs' => (int) ($connector->get('install_count')->value ?? 0),
            'created' => $connector->get('created')->value ?? 0,
          ];
        }
      }
    }
    catch (\Exception $e) {
      // Devolver lista vacia.
    }
    return $connectors;
  }

  /**
   * Obtiene la cantidad de conectores pendientes de revision.
   */
  protected function getPendingReviewCount(): int {
    try {
      $storage = $this->entityTypeManager->getStorage('connector');
      return (int) $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('developer_uid', (int) $this->currentUser->id())
        ->condition('approval_status', [AppApprovalService::STATUS_SUBMITTED, AppApprovalService::STATUS_IN_REVIEW], 'IN')
        ->count()
        ->execute();
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Listado de endpoints de la API para documentacion.
   */
  protected function getApiEndpoints(): array {
    return [
      [
        'method' => 'GET',
        'path' => '/api/v1/integrations/connectors',
        'description' => $this->t('Listar conectores disponibles'),
      ],
      [
        'method' => 'GET',
        'path' => '/api/v1/integrations/connectors/{id}',
        'description' => $this->t('Obtener detalle de un conector'),
      ],
      [
        'method' => 'POST',
        'path' => '/api/v1/integrations/connectors/{id}/install',
        'description' => $this->t('Instalar un conector'),
      ],
      [
        'method' => 'POST',
        'path' => '/api/v1/integrations/connectors/{id}/uninstall',
        'description' => $this->t('Desinstalar un conector'),
      ],
      [
        'method' => 'GET',
        'path' => '/api/v1/integrations/connectors/{id}/health',
        'description' => $this->t('Verificar estado de un conector'),
      ],
      [
        'method' => 'POST',
        'path' => '/oauth/authorize',
        'description' => $this->t('Iniciar flujo OAuth2'),
      ],
      [
        'method' => 'POST',
        'path' => '/oauth/token',
        'description' => $this->t('Obtener token de acceso'),
      ],
    ];
  }

}
