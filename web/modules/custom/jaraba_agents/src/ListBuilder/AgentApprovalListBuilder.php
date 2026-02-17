<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Listado de aprobaciones de agentes en la interfaz de administracion.
 *
 * Estructura: Extiende EntityListBuilder para tabla admin.
 * Logica: Muestra aprobaciones con badges de color por nivel de riesgo.
 *   Solo operaciones 'view' y 'edit' (sin delete).
 *   El nombre del agente se carga via referencia de entidad.
 */
class AgentApprovalListBuilder extends EntityListBuilder {

  /**
   * Almacen de la entidad AutonomousAgent.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $agentStorage;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): static {
    $instance = parent::createInstance($container, $entity_type);
    $instance->agentStorage = $container->get('entity_type.manager')
      ->getStorage('autonomous_agent');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['agent'] = $this->t('Agente');
    $header['risk_assessment'] = $this->t('Riesgo');
    $header['status'] = $this->t('Estado');
    $header['expires_at'] = $this->t('Expira');
    $header['created'] = $this->t('Creado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    // Etiquetas traducibles para estados de aprobacion.
    $status_labels = [
      'pending' => $this->t('Pendiente'),
      'approved' => $this->t('Aprobada'),
      'rejected' => $this->t('Rechazada'),
      'expired' => $this->t('Expirada'),
      'auto_approved' => $this->t('Auto-aprobada'),
    ];

    // Mapa de niveles de riesgo a clases CSS de color.
    $risk_colors = [
      'low' => 'success',
      'medium' => 'warning',
      'high' => 'danger',
    ];

    // Etiquetas traducibles para niveles de riesgo.
    $risk_labels = [
      'low' => $this->t('Bajo'),
      'medium' => $this->t('Medio'),
      'high' => $this->t('Alto'),
    ];

    // Cargar nombre del agente via referencia de entidad.
    $agent_name = '';
    $agent_id = $entity->get('agent_id')->target_id ?? NULL;
    if ($agent_id) {
      $agent = $this->agentStorage->load($agent_id);
      if ($agent) {
        $agent_name = $agent->get('name')->value ?? (string) $agent->id();
      }
    }

    $risk = $entity->get('risk_assessment')->value ?? 'medium';
    $status = $entity->get('status')->value ?? '';
    $expires = $entity->get('expires_at')->value ?? '';
    $created = $entity->get('created')->value ?? '';
    $risk_color = $risk_colors[$risk] ?? 'warning';

    $row['id'] = $entity->id();
    $row['agent'] = $agent_name;
    $row['risk_assessment'] = [
      'data' => [
        '#markup' => '<span class="badge badge--' . htmlspecialchars($risk_color, ENT_QUOTES, 'UTF-8') . '">'
          . ($risk_labels[$risk] ?? $risk)
          . '</span>',
      ],
    ];
    $row['status'] = $status_labels[$status] ?? $status;
    $row['expires_at'] = $expires ? date('d/m/Y H:i', (int) $expires) : $this->t('Sin expiracion');
    $row['created'] = $created ? date('d/m/Y H:i', (int) $created) : '';

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   *
   * Solo operaciones 'view' y 'edit' — no se permite eliminar aprobaciones.
   */
  protected function getDefaultOperations(EntityInterface $entity): array {
    $operations = parent::getDefaultOperations($entity);

    // Eliminar operacion de borrado.
    unset($operations['delete']);

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('No hay aprobaciones registradas.');
    return $build;
  }

}
