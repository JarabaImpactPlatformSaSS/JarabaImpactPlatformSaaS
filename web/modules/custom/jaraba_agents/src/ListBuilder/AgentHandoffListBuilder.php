<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Listado de handoffs de agentes (APPEND-ONLY).
 *
 * ESTRUCTURA:
 *   Extiende EntityListBuilder con columnas: ID, Conversation ID,
 *   From Agent, To Agent, Confidence, Handoff At.
 *   Append-only: no se permite editar ni eliminar.
 *
 * LOGICA:
 *   Los agentes se cargan via entity_reference (from_agent_id, to_agent_id).
 *   Las operaciones de edicion y borrado se eliminan (ENTITY-APPEND-001).
 *   AUDIT-CONS-005: tenant_id como entity_reference a group.
 */
class AgentHandoffListBuilder extends EntityListBuilder {

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
    $header['conversation_id'] = $this->t('Conversacion');
    $header['from_agent'] = $this->t('Agente origen');
    $header['to_agent'] = $this->t('Agente destino');
    $header['confidence'] = $this->t('Confianza');
    $header['handoff_at'] = $this->t('Fecha del handoff');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    // Cargar nombre del agente origen via referencia de entidad.
    $from_agent_name = '';
    $from_agent_id = $entity->get('from_agent_id')->target_id ?? NULL;
    if ($from_agent_id) {
      $from_agent = $this->agentStorage->load($from_agent_id);
      if ($from_agent) {
        $from_agent_name = $from_agent->get('name')->value ?? (string) $from_agent->id();
      }
    }

    // Cargar nombre del agente destino via referencia de entidad.
    $to_agent_name = '';
    $to_agent_id = $entity->get('to_agent_id')->target_id ?? NULL;
    if ($to_agent_id) {
      $to_agent = $this->agentStorage->load($to_agent_id);
      if ($to_agent) {
        $to_agent_name = $to_agent->get('name')->value ?? (string) $to_agent->id();
      }
    }

    $conversation_id = $entity->get('conversation_id')->target_id ?? '';
    $confidence = (float) ($entity->get('confidence')->value ?? 0);
    $handoff_at = $entity->get('handoff_at')->value ?? '';

    $row['id'] = $entity->id();
    $row['conversation_id'] = $conversation_id;
    $row['from_agent'] = $from_agent_name;
    $row['to_agent'] = $to_agent_name;
    $row['confidence'] = number_format($confidence, 2);
    $row['handoff_at'] = $handoff_at ? date('d/m/Y H:i', strtotime($handoff_at)) : '';

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
    $build['table']['#empty'] = $this->t('No hay handoffs registrados.');
    return $build;
  }

}
