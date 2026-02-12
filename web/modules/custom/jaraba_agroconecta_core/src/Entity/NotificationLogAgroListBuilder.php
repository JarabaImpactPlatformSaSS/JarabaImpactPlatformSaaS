<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * ListBuilder para NotificationLogAgro.
 *
 * Muestra: tipo, canal, destinatario, asunto, estado, fecha.
 * Entidad de solo lectura — sin botones de edición.
 */
class NotificationLogAgroListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['type'] = $this->t('Tipo');
        $header['channel'] = $this->t('Canal');
        $header['recipient'] = $this->t('Destinatario');
        $header['subject'] = $this->t('Asunto');
        $header['status'] = $this->t('Estado');
        $header['opened'] = $this->t('Abierto');
        $header['created'] = $this->t('Enviado');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_agroconecta_core\Entity\NotificationLogAgro $entity */
        $row['type'] = $entity->get('type')->value;
        $row['channel'] = $entity->get('channel')->value;
        $row['recipient'] = $entity->get('recipient_email')->value ?: $this->t('User #@id', ['@id' => $entity->get('recipient_id')->value]);
        $row['subject'] = $entity->get('subject')->value;
        $row['status'] = $entity->getStatusLabel();
        $row['opened'] = $entity->wasOpened() ? '✅' : '—';
        $row['created'] = \Drupal::service('date.formatter')->format($entity->get('created')->value, 'short');
        return $row + parent::buildRow($entity);
    }

}
