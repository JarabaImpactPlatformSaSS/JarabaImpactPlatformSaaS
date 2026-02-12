<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para entidades PromotionAgro.
 *
 * Muestra: Nombre, Tipo, Valor, Vigencia, Usos, Estado.
 */
class PromotionAgroListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['name'] = $this->t('Nombre');
        $header['type'] = $this->t('Tipo');
        $header['value'] = $this->t('Valor');
        $header['dates'] = $this->t('Vigencia');
        $header['uses'] = $this->t('Usos');
        $header['status'] = $this->t('Estado');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_agroconecta_core\Entity\PromotionAgro $entity */
        $row['name'] = $entity->label();
        $row['type'] = $entity->getDiscountTypeLabel();
        $row['value'] = $entity->getFormattedDiscount();

        // Vigencia.
        $start = $entity->get('start_date')->value ?? '—';
        $end = $entity->get('end_date')->value ?? '∞';
        $row['dates'] = $start . ' → ' . $end;

        // Usos.
        $max = (int) $entity->get('max_uses')->value;
        $current = (int) $entity->get('current_uses')->value;
        $row['uses'] = $current . '/' . ($max ?: '∞');

        // Estado.
        $active = $entity->isActive() && $entity->isWithinDateRange() && $entity->hasUsesRemaining();
        $row['status'] = $active ? $this->t('✅ Activa') : $this->t('⏸ Inactiva');

        return $row + parent::buildRow($entity);
    }

}
