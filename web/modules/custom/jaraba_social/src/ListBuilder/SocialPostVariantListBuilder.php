<?php

declare(strict_types=1);

namespace Drupal\jaraba_social\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado administrativo de variantes de posts sociales.
 *
 * PROPOSITO:
 * Renderiza la tabla de variantes en /admin/content/social-post-variants.
 *
 * LOGICA:
 * Muestra: nombre de variante, post asociado, si es ganadora
 * y la tasa de engagement con badge de color.
 */
class SocialPostVariantListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['variant_name'] = $this->t('Variante');
    $header['post_id'] = $this->t('Post Social');
    $header['is_winner'] = $this->t('Ganadora');
    $header['engagement_rate'] = $this->t('Engagement Rate');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_social\Entity\SocialPostVariant $entity */

    // Badge de ganadora con colores.
    $isWinner = (bool) $entity->get('is_winner')->value;
    $winnerColor = $isWinner ? '#43A047' : '#6C757D';
    $winnerLabel = $isWinner ? $this->t('Si') : $this->t('No');

    // Obtener el post asociado.
    $postLabel = '-';
    $postEntity = $entity->get('post_id')->entity;
    if ($postEntity) {
      $postLabel = $postEntity->label() ?? $postEntity->id();
    }

    $row['variant_name'] = $entity->label();
    $row['post_id'] = $postLabel;
    $row['is_winner'] = [
      'data' => [
        '#markup' => '<span style="background:' . $winnerColor . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:0.85em;">' . $winnerLabel . '</span>',
      ],
    ];
    $row['engagement_rate'] = number_format((float) ($entity->get('engagement_rate')->value ?? 0), 4, ',', '.') . '%';

    return $row + parent::buildRow($entity);
  }

}
