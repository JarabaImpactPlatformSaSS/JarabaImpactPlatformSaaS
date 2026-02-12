<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para la entidad SiteMenuItem.
 */
class SiteMenuItemListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['title'] = $this->t('Título');
    $header['menu'] = $this->t('Menú');
    $header['type'] = $this->t('Tipo');
    $header['weight'] = $this->t('Peso');
    $header['enabled'] = $this->t('Estado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_site_builder\Entity\SiteMenuItem $entity */
    $depth = (int) ($entity->get('depth')->value ?? 0);
    $prefix = str_repeat('— ', $depth);

    $row['title'] = $prefix . ($entity->get('title')->value ?? '-');

    $menu = $entity->getMenu();
    $row['menu'] = $menu ? $menu->label() : '-';

    $row['type'] = $entity->getItemType();
    $row['weight'] = $entity->get('weight')->value ?? 0;

    $enabled = $entity->isEnabled();
    $row['enabled'] = $enabled ? $this->t('Activo') : $this->t('Inactivo');

    return $row + parent::buildRow($entity);
  }

}
