<?php

declare(strict_types=1);

namespace Drupal\jaraba_paths;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * ListBuilder para DigitalizationPath.
 */
class DigitalizationPathListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['title'] = $this->t('Itinerario');
        $header['sector'] = $this->t('Sector');
        $header['weeks'] = $this->t('Semanas');
        $header['status'] = $this->t('Estado');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_paths\Entity\DigitalizationPathInterface $entity */
        $row['title'] = Link::createFromRoute(
            $entity->getTitle(),
            'entity.digitalization_path.canonical',
            ['digitalization_path' => $entity->id()]
        );

        $sectorLabels = [
            'comercio' => 'Comercio',
            'servicios' => 'Servicios',
            'agro' => 'Agro',
            'hosteleria' => 'Hostelería',
            'industria' => 'Industria',
            'tech' => 'Tech',
            'general' => 'General',
        ];
        $sector = $entity->getTargetSector();
        $row['sector'] = $sectorLabels[$sector] ?? $sector;

        $row['weeks'] = $entity->getEstimatedWeeks() . ' sem.';

        $row['status'] = $entity->isPublished() ? '✓ Publicado' : 'Borrador';

        return $row + parent::buildRow($entity);
    }

}
