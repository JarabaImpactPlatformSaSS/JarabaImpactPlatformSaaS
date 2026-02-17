<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Listado de conversaciones multi-agente.
 *
 * ESTRUCTURA:
 *   Extiende EntityListBuilder con columnas: ID, User, Current Agent,
 *   Status (color badges), Handoffs, Started At. Operaciones estandar.
 *
 * LOGICA:
 *   El usuario se carga via entity_reference (user_id).
 *   El agente actual se carga via entity_reference (current_agent_id).
 *   Los estados se muestran con badges de color:
 *     active=blue, completed=green, escalated=orange, timeout=red.
 *   AUDIT-CONS-005: tenant_id como entity_reference a group.
 */
class AgentConversationListBuilder extends EntityListBuilder {

  /**
   * Almacen de la entidad User.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $userStorage;

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
    $instance->userStorage = $container->get('entity_type.manager')
      ->getStorage('user');
    $instance->agentStorage = $container->get('entity_type.manager')
      ->getStorage('autonomous_agent');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['user'] = $this->t('Usuario');
    $header['current_agent'] = $this->t('Agente actual');
    $header['status'] = $this->t('Estado');
    $header['handoffs'] = $this->t('Handoffs');
    $header['started_at'] = $this->t('Inicio');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    // Etiquetas traducibles para estados de conversacion.
    $status_labels = [
      'active' => $this->t('Activa'),
      'completed' => $this->t('Completada'),
      'escalated' => $this->t('Escalada'),
      'timeout' => $this->t('Expirada'),
    ];

    // Colores CSS para badges de estado.
    $status_colors = [
      'active' => '#2196F3',
      'completed' => '#4CAF50',
      'escalated' => '#FF9800',
      'timeout' => '#F44336',
    ];

    // Cargar nombre del usuario via referencia de entidad.
    $user_name = '';
    $user_id = $entity->get('user_id')->target_id ?? NULL;
    if ($user_id) {
      $user = $this->userStorage->load($user_id);
      if ($user) {
        $user_name = $user->getDisplayName();
      }
    }

    // Cargar nombre del agente actual via referencia de entidad.
    $agent_name = '';
    $agent_id = $entity->get('current_agent_id')->target_id ?? NULL;
    if ($agent_id) {
      $agent = $this->agentStorage->load($agent_id);
      if ($agent) {
        $agent_name = $agent->get('name')->value ?? (string) $agent->id();
      }
    }

    $status = $entity->get('status')->value ?? '';
    $handoff_count = (int) ($entity->get('handoff_count')->value ?? 0);
    $started = $entity->get('started_at')->value ?? '';
    $color = $status_colors[$status] ?? '#999';

    $row['id'] = $entity->id();
    $row['user'] = $user_name;
    $row['current_agent'] = $agent_name;
    $row['status'] = [
      'data' => [
        '#markup' => '<span style="background-color:' . htmlspecialchars($color, ENT_QUOTES, 'UTF-8')
          . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:0.85em;">'
          . ($status_labels[$status] ?? $status)
          . '</span>',
      ],
    ];
    $row['handoffs'] = $handoff_count;
    $row['started_at'] = $started ? date('d/m/Y H:i', strtotime($started)) : '';

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('No hay conversaciones registradas.');
    return $build;
  }

}
