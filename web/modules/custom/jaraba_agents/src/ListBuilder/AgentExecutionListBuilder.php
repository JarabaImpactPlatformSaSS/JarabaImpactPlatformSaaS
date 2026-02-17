<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Listado de ejecuciones de agentes (APPEND-ONLY).
 *
 * Estructura: Extiende EntityListBuilder con operacion solo lectura.
 * Logica: Las ejecuciones son inmutables; no se permite editar ni eliminar.
 *   Solo se muestra la operacion 'view'.
 *   El nombre del agente se carga via referencia de entidad.
 */
class AgentExecutionListBuilder extends EntityListBuilder {

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
    $header['trigger_type'] = $this->t('Trigger');
    $header['status'] = $this->t('Estado');
    $header['tokens_used'] = $this->t('Tokens');
    $header['cost_estimate'] = $this->t('Coste');
    $header['started_at'] = $this->t('Inicio');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    // Etiquetas traducibles para estados de ejecucion.
    $status_labels = [
      'running' => $this->t('En ejecucion'),
      'completed' => $this->t('Completada'),
      'failed' => $this->t('Fallida'),
      'paused' => $this->t('Pausada'),
      'cancelled' => $this->t('Cancelada'),
    ];

    // Etiquetas traducibles para tipos de trigger.
    $trigger_labels = [
      'manual' => $this->t('Manual'),
      'scheduled' => $this->t('Programado'),
      'event' => $this->t('Evento'),
      'webhook' => $this->t('Webhook'),
      'approval' => $this->t('Aprobacion'),
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

    $status = $entity->get('status')->value ?? '';
    $trigger_type = $entity->get('trigger_type')->value ?? '';
    $tokens = (int) ($entity->get('tokens_used')->value ?? 0);
    $cost = (float) ($entity->get('cost_estimate')->value ?? 0);
    $started = $entity->get('started_at')->value ?? '';

    $row['id'] = $entity->id();
    $row['agent'] = $agent_name;
    $row['trigger_type'] = $trigger_labels[$trigger_type] ?? $trigger_type;
    $row['status'] = [
      'data' => [
        '#markup' => '<span class="execution-status execution-status--' . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . '">'
          . ($status_labels[$status] ?? $status)
          . '</span>',
      ],
    ];
    $row['tokens_used'] = number_format($tokens);
    $row['cost_estimate'] = number_format($cost, 4) . ' €';
    $row['started_at'] = $started ? date('d/m/Y H:i', (int) $started) : '';

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   *
   * Solo operacion 'view' — append-only, sin editar ni eliminar.
   */
  protected function getDefaultOperations(EntityInterface $entity): array {
    $operations = parent::getDefaultOperations($entity);

    // Eliminar operaciones de edicion y borrado (append-only).
    unset($operations['edit'], $operations['delete']);

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('No hay ejecuciones registradas.');
    return $build;
  }

}
