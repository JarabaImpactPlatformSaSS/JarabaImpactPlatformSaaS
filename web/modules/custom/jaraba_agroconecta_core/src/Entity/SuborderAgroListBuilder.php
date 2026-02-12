<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Proporciona la lista de sub-pedidos agro para administración.
 */
class SuborderAgroListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['suborder_number'] = $this->t('Nº Sub-pedido');
        $header['state'] = $this->t('Estado');
        $header['subtotal'] = $this->t('Subtotal');
        $header['payout_state'] = $this->t('Payout');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_agroconecta_core\Entity\SuborderAgro $entity */
        $row['suborder_number'] = $entity->get('suborder_number')->value ?? '-';
        $row['state'] = $entity->get('state')->value ?? '-';
        $row['subtotal'] = number_format((float) ($entity->get('subtotal')->value ?? 0), 2, ',', '.') . ' €';
        $row['payout_state'] = $entity->get('payout_state')->value ?? 'pending';
        return $row + parent::buildRow($entity);
    }

}
