<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * ListBuilder para NotificationTemplateAgro.
 *
 * Muestra: nombre, tipo, canal, idioma, activa, fecha modificación.
 */
class NotificationTemplateAgroListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['name'] = $this->t('Nombre');
        $header['type'] = $this->t('Tipo');
        $header['channel'] = $this->t('Canal');
        $header['language'] = $this->t('Idioma');
        $header['is_active'] = $this->t('Activa');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_agroconecta_core\Entity\NotificationTemplateAgro $entity */
        $row['name'] = $entity->get('name')->value;
        $row['type'] = $entity->get('type')->value;
        $row['channel'] = $entity->getChannelLabel();
        $row['language'] = $entity->get('language')->value;
        $row['is_active'] = $entity->isActive() ? '✅' : '❌';
        return $row + parent::buildRow($entity);
    }

}
