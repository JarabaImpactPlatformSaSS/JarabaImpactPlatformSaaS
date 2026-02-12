<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;
use Drupal\jaraba_integrations\Entity\Connector;
use Drupal\jaraba_integrations\Entity\ConnectorInstallation;

/**
 * Servicio de instalación y desinstalación de conectores por tenant.
 *
 * PROPÓSITO:
 * Gestiona el ciclo de vida completo de un conector dentro de un tenant:
 * instalación, configuración, actualización y desinstalación.
 *
 * FLUJO:
 * 1. install(): Crea ConnectorInstallation con estado pending_config.
 * 2. configure(): Actualiza credenciales y activa.
 * 3. uninstall(): Elimina la instalación y limpia tokens.
 *
 * AISLAMIENTO:
 * Todas las operaciones filtran por tenant_id del usuario actual.
 */
class ConnectorInstallerService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConnectorRegistryService $connectorRegistry,
    protected AccountProxyInterface $currentUser,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Instala un conector para un tenant.
   *
   * @param \Drupal\jaraba_integrations\Entity\Connector $connector
   *   El conector a instalar.
   * @param string $tenant_id
   *   ID del grupo/tenant.
   *
   * @return \Drupal\jaraba_integrations\Entity\ConnectorInstallation|null
   *   La instalación creada, o NULL si ya existe.
   */
  public function install(Connector $connector, string $tenant_id): ?ConnectorInstallation {
    // Verificar que no esté ya instalado.
    $existing = $this->getInstallation($connector, $tenant_id);
    if ($existing) {
      $this->logger->warning('Conector @name ya instalado para tenant @tid', [
        '@name' => $connector->getName(),
        '@tid' => $tenant_id,
      ]);
      return NULL;
    }

    $storage = $this->entityTypeManager->getStorage('connector_installation');
    /** @var \Drupal\jaraba_integrations\Entity\ConnectorInstallation $installation */
    $installation = $storage->create([
      'connector_id' => $connector->id(),
      'tenant_id' => $tenant_id,
      'status' => ConnectorInstallation::STATUS_PENDING_CONFIG,
      'installed_by' => $this->currentUser->id(),
    ]);
    $installation->save();

    // Actualizar conteo de instalaciones del conector.
    $this->updateInstallCount($connector);

    $this->logger->notice('Conector @name instalado para tenant @tid por @user', [
      '@name' => $connector->getName(),
      '@tid' => $tenant_id,
      '@user' => $this->currentUser->getDisplayName(),
    ]);

    return $installation;
  }

  /**
   * Configura una instalación existente.
   *
   * @param \Drupal\jaraba_integrations\Entity\ConnectorInstallation $installation
   *   La instalación a configurar.
   * @param array $config
   *   Configuración (credenciales, opciones).
   *
   * @return \Drupal\jaraba_integrations\Entity\ConnectorInstallation
   *   La instalación actualizada.
   */
  public function configure(ConnectorInstallation $installation, array $config): ConnectorInstallation {
    $installation->setConfiguration($config);
    $installation->set('status', ConnectorInstallation::STATUS_ACTIVE);
    $installation->save();

    $connector = $installation->getConnector();
    $this->logger->notice('Conector @name configurado para tenant @tid', [
      '@name' => $connector ? $connector->getName() : 'unknown',
      '@tid' => $installation->get('tenant_id')->target_id,
    ]);

    return $installation;
  }

  /**
   * Desinstala un conector para un tenant.
   *
   * @param \Drupal\jaraba_integrations\Entity\Connector $connector
   *   El conector a desinstalar.
   * @param string $tenant_id
   *   ID del grupo/tenant.
   *
   * @return bool
   *   TRUE si se desinstaló correctamente.
   */
  public function uninstall(Connector $connector, string $tenant_id): bool {
    $installation = $this->getInstallation($connector, $tenant_id);
    if (!$installation) {
      return FALSE;
    }

    $installation->delete();

    // Actualizar conteo.
    $this->updateInstallCount($connector);

    $this->logger->notice('Conector @name desinstalado para tenant @tid', [
      '@name' => $connector->getName(),
      '@tid' => $tenant_id,
    ]);

    return TRUE;
  }

  /**
   * Obtiene la instalación de un conector para un tenant.
   *
   * @param \Drupal\jaraba_integrations\Entity\Connector $connector
   *   El conector.
   * @param string $tenant_id
   *   ID del grupo/tenant.
   *
   * @return \Drupal\jaraba_integrations\Entity\ConnectorInstallation|null
   *   La instalación o NULL si no existe.
   */
  public function getInstallation(Connector $connector, string $tenant_id): ?ConnectorInstallation {
    $storage = $this->entityTypeManager->getStorage('connector_installation');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('connector_id', $connector->id())
      ->condition('tenant_id', $tenant_id)
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return NULL;
    }

    $installation = $storage->load(reset($ids));
    return $installation instanceof ConnectorInstallation ? $installation : NULL;
  }

  /**
   * Obtiene todas las instalaciones de un tenant.
   *
   * @param string $tenant_id
   *   ID del grupo/tenant.
   *
   * @return \Drupal\jaraba_integrations\Entity\ConnectorInstallation[]
   *   Array de instalaciones.
   */
  public function getTenantInstallations(string $tenant_id): array {
    $storage = $this->entityTypeManager->getStorage('connector_installation');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('tenant_id', $tenant_id)
      ->sort('created', 'DESC')
      ->execute();

    return $ids ? $storage->loadMultiple($ids) : [];
  }

  /**
   * Actualiza el conteo de instalaciones activas de un conector.
   */
  protected function updateInstallCount(Connector $connector): void {
    $storage = $this->entityTypeManager->getStorage('connector_installation');
    $count = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('connector_id', $connector->id())
      ->condition('status', ConnectorInstallation::STATUS_ACTIVE)
      ->count()
      ->execute();

    $connector->set('install_count', $count);
    $connector->save();
  }

}
