<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Lista de documentos de producto en admin.
 */
class ProductDocumentListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['title'] = $this->t('Título');
        $header['document_type'] = $this->t('Tipo');
        $header['min_access_level'] = $this->t('Nivel mínimo');
        $header['version'] = $this->t('Versión');
        $header['download_count'] = $this->t('Descargas');
        $header['is_active'] = $this->t('Activo');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_agroconecta_core\Entity\ProductDocument $entity */
        $row['title'] = $entity->getTitle();
        $row['document_type'] = $entity->getDocumentType();
        $row['min_access_level'] = $entity->getMinAccessLevel();
        $row['version'] = $entity->getVersion();
        $row['download_count'] = $entity->getDownloadCount();
        $row['is_active'] = $entity->isActive() ? $this->t('Sí') : $this->t('No');
        return $row + parent::buildRow($entity);
    }

}
