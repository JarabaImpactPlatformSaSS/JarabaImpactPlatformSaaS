<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para CredentialTemplate.
 */
class CredentialTemplateListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['name'] = $this->t('Nombre');
        $header['machine_name'] = $this->t('ID');
        $header['credential_type'] = $this->t('Tipo');
        $header['level'] = $this->t('Nivel');
        $header['is_active'] = $this->t('Estado');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_credentials\Entity\CredentialTemplate $entity */
        $row['name'] = $entity->get('name')->value;
        $row['machine_name'] = $entity->get('machine_name')->value;
        $row['credential_type'] = $entity->get('credential_type')->value ?? '-';
        $row['level'] = $entity->get('level')->value ?? '-';
        $row['is_active'] = $entity->get('is_active')->value
            ? $this->t('Activo')
            : $this->t('Inactivo');
        return $row + parent::buildRow($entity);
    }

}
