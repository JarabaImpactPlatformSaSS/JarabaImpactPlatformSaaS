<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para IssuerProfile.
 */
class IssuerProfileListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['name'] = $this->t('Nombre');
        $header['url'] = $this->t('URL');
        $header['is_default'] = $this->t('Por defecto');
        $header['has_keys'] = $this->t('Claves');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_credentials\Entity\IssuerProfile $entity */
        $row['name'] = $entity->get('name')->value;
        $row['url'] = $entity->get('url')->value ?? '-';
        $row['is_default'] = $entity->get('is_default')->value ? $this->t('Sí') : $this->t('No');
        $row['has_keys'] = $entity->hasKeys() ? $this->t('Configuradas') : $this->t('Sin claves');
        return $row + parent::buildRow($entity);
    }

}
