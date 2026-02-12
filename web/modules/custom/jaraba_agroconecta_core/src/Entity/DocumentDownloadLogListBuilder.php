<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Lista de registros de descarga (audit log) en admin.
 */
class DocumentDownloadLogListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['document_id'] = $this->t('Documento');
        $header['relationship_id'] = $this->t('Partner');
        $header['ip_address'] = $this->t('IP');
        $header['downloaded_at'] = $this->t('Fecha descarga');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_agroconecta_core\Entity\DocumentDownloadLog $entity */
        $row['document_id'] = $entity->getDocumentId() ?? '-';
        $row['relationship_id'] = $entity->getRelationshipId() ?? '-';
        $row['ip_address'] = $entity->get('ip_address')->value ?? '-';
        $row['downloaded_at'] = $entity->get('downloaded_at')->value
            ? date('Y-m-d H:i', (int) $entity->get('downloaded_at')->value)
            : '-';
        return $row + parent::buildRow($entity);
    }

}
