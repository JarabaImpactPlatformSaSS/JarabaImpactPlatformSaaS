<?php

declare(strict_types=1);

namespace Drupal\jaraba_training;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * ListBuilder para la entidad TrainingProduct.
 */
class TrainingProductListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header = [
            'title' => $this->t('Producto'),
            'type' => $this->t('Tipo'),
            'level' => $this->t('Peldaño'),
            'price' => $this->t('Precio'),
            'billing' => $this->t('Facturación'),
            'status' => $this->t('Estado'),
        ];
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_training\Entity\TrainingProductInterface $entity */
        $row = [
            'title' => $entity->getTitle(),
            'type' => $entity->getProductType(),
            'level' => $entity->getLadderLevel(),
            'price' => $entity->isFree() ? $this->t('Gratis') : '€' . number_format($entity->getPrice(), 2),
            'billing' => $entity->getBillingType(),
            'status' => $entity->get('status')->value ? $this->t('Publicado') : $this->t('Borrador'),
        ];
        return $row + parent::buildRow($entity);
    }

}
