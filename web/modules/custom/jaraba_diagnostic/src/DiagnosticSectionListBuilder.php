<?php

declare(strict_types=1);

namespace Drupal\jaraba_diagnostic;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * ListBuilder para DiagnosticSection.
 */
class DiagnosticSectionListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['name'] = $this->t('Sección');
        $header['machine_name'] = $this->t('ID');
        $header['weight'] = $this->t('Peso');
        $header['order'] = $this->t('Orden');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_diagnostic\Entity\DiagnosticSection $entity */
        $row['name'] = $entity->label();
        $row['machine_name'] = $entity->getMachineName();
        $row['weight'] = number_format($entity->getWeight() * 100, 0) . '%';
        $row['order'] = $entity->get('order')->value ?? 0;
        return $row + parent::buildRow($entity);
    }

}
