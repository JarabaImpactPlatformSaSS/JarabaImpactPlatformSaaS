<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * ListBuilder para NotificationPreferenceAgro.
 *
 * Muestra: usuario, tipo de notificación, canales habilitados, fecha.
 */
class NotificationPreferenceAgroListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['user'] = $this->t('Usuario');
        $header['notification_type'] = $this->t('Tipo');
        $header['email'] = $this->t('Email');
        $header['push'] = $this->t('Push');
        $header['sms'] = $this->t('SMS');
        $header['in_app'] = $this->t('In-App');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_agroconecta_core\Entity\NotificationPreferenceAgro $entity */
        $row['user'] = $entity->getOwner() ? $entity->getOwner()->getDisplayName() : $this->t('—');
        $row['notification_type'] = $entity->get('notification_type')->value;
        $row['email'] = $entity->isChannelEnabled('email') ? '✅' : '❌';
        $row['push'] = $entity->isChannelEnabled('push') ? '✅' : '❌';
        $row['sms'] = $entity->isChannelEnabled('sms') ? '✅' : '❌';
        $row['in_app'] = $entity->isChannelEnabled('in_app') ? '✅' : '❌';
        return $row + parent::buildRow($entity);
    }

}
