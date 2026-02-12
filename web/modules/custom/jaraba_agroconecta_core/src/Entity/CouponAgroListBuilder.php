<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para entidades CouponAgro.
 *
 * Muestra: Código, Promoción asociada, Usos, Vigencia, Estado.
 */
class CouponAgroListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['code'] = $this->t('Código');
        $header['promotion'] = $this->t('Promoción');
        $header['uses'] = $this->t('Usos');
        $header['dates'] = $this->t('Vigencia');
        $header['status'] = $this->t('Estado');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_agroconecta_core\Entity\CouponAgro $entity */
        $row['code'] = $entity->getCode();

        $promo = $entity->getPromotion();
        $row['promotion'] = $promo ? $promo->label() . ' (' . $promo->getFormattedDiscount() . ')' : '—';

        $max = (int) $entity->get('max_uses')->value;
        $current = (int) $entity->get('current_uses')->value;
        $row['uses'] = $current . '/' . ($max ?: '∞');

        $start = $entity->get('start_date')->value ?? '—';
        $end = $entity->get('end_date')->value ?? '∞';
        $row['dates'] = $start . ' → ' . $end;

        $active = $entity->isActive() && $entity->isWithinDateRange() && $entity->hasUsesRemaining();
        $row['status'] = $active ? $this->t('✅ Activo') : $this->t('⏸ Inactivo');

        return $row + parent::buildRow($entity);
    }

}
