<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Proporciona la lista de pedidos agro para administración.
 */
class OrderAgroListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['order_number'] = $this->t('Nº Pedido');
        $header['state'] = $this->t('Estado');
        $header['total'] = $this->t('Total');
        $header['payment_state'] = $this->t('Pago');
        $header['created'] = $this->t('Fecha');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_agroconecta_core\Entity\OrderAgro $entity */
        $row['order_number'] = $entity->get('order_number')->value ?? '-';
        $row['state'] = $entity->getStateLabel();
        $row['total'] = $entity->getFormattedTotal();
        $row['payment_state'] = $entity->get('payment_state')->value ?? 'pending';
        $row['created'] = date('d/m/Y H:i', $entity->get('created')->value);
        return $row + parent::buildRow($entity);
    }

}
