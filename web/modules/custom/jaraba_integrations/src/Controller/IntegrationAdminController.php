<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controlador administrativo para el marketplace de integraciones.
 *
 * PROPÓSITO:
 * Panel de administración en /admin/structure/integrations con
 * vista global de conectores, instalaciones y estadísticas.
 */
class IntegrationAdminController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static();
  }

  /**
   * Dashboard administrativo (/admin/structure/integrations).
   */
  public function dashboard(): array {
    $entity_list = $this->entityTypeManager()->getListBuilder('connector');
    return $entity_list->render();
  }

  /**
   * Lista de instalaciones (/admin/structure/integrations/installations).
   */
  public function installations(): array {
    $entity_list = $this->entityTypeManager()->getListBuilder('connector_installation');
    return $entity_list->render();
  }

}
