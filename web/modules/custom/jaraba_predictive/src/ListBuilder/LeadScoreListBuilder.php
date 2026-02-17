<?php

declare(strict_types=1);

namespace Drupal\jaraba_predictive\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de puntuaciones de leads en la interfaz de administracion.
 *
 * Estructura: Extiende EntityListBuilder para tabla admin.
 * Logica: Muestra columnas clave del lead score con badge de cualificacion.
 *   Soporta operaciones CRUD completas.
 */
class LeadScoreListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['tenant'] = $this->t('Organizacion');
    $header['user'] = $this->t('Usuario');
    $header['total_score'] = $this->t('Score');
    $header['qualification'] = $this->t('Cualificacion');
    $header['model_version'] = $this->t('Modelo');
    $header['calculated_at'] = $this->t('Calculado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    // Etiquetas traducibles para cualificacion de leads.
    $qualification_labels = [
      'cold' => $this->t('Frio'),
      'warm' => $this->t('Templado'),
      'hot' => $this->t('Caliente'),
      'sales_ready' => $this->t('Listo para venta'),
    ];

    // Clases CSS para badges de cualificacion.
    $qualification_classes = [
      'cold' => 'badge--inactive',
      'warm' => 'badge--warning',
      'hot' => 'badge--danger',
      'sales_ready' => 'badge--success',
    ];

    $qualification = $entity->get('qualification')->value ?? '';
    $total_score = (int) ($entity->get('total_score')->value ?? 0);
    $calculated_at = $entity->get('calculated_at')->value ?? '';

    // Cargar nombre de la organizacion via referencia de entidad.
    $tenant_name = '';
    $tenant_id = $entity->get('tenant_id')->target_id ?? NULL;
    if ($tenant_id) {
      $tenant = \Drupal::entityTypeManager()->getStorage('group')->load($tenant_id);
      if ($tenant) {
        $tenant_name = $tenant->label() ?? (string) $tenant->id();
      }
    }

    // Cargar nombre del usuario via referencia de entidad.
    $user_name = '';
    $user_id = $entity->get('user_id')->target_id ?? NULL;
    if ($user_id) {
      $user = \Drupal::entityTypeManager()->getStorage('user')->load($user_id);
      if ($user) {
        $user_name = $user->getDisplayName() ?? (string) $user->id();
      }
    }

    $row['id'] = $entity->id();
    $row['tenant'] = $tenant_name;
    $row['user'] = $user_name;
    $row['total_score'] = $total_score;
    $row['qualification'] = [
      'data' => [
        '#markup' => '<span class="badge ' . ($qualification_classes[$qualification] ?? '') . '">'
          . ($qualification_labels[$qualification] ?? $qualification)
          . '</span>',
      ],
    ];
    $row['model_version'] = $entity->get('model_version')->value ?? '';
    $row['calculated_at'] = $calculated_at ?: '';

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('No hay puntuaciones de leads registradas.');
    return $build;
  }

}
