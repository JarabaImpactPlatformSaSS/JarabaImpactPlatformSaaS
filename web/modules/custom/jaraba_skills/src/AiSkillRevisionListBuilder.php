<?php

declare(strict_types=1);

namespace Drupal\jaraba_skills;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para revisiones de skills.
 */
class AiSkillRevisionListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        return [
            'revision_number' => $this->t('Revisión'),
            'name' => $this->t('Nombre'),
            'changed_by' => $this->t('Modificado por'),
            'change_summary' => $this->t('Cambios'),
            'created' => $this->t('Fecha'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_skills\Entity\AiSkillRevision $entity */
        $user = $entity->get('changed_by')->entity;

        return [
            'revision_number' => '#' . $entity->getRevisionNumber(),
            'name' => $entity->getName(),
            'changed_by' => $user ? $user->getDisplayName() : $this->t('Sistema'),
            'change_summary' => $entity->getChangeSummary() ?: $this->t('Sin descripción'),
            'created' => \Drupal::service('date.formatter')->format($entity->getCreatedTime(), 'short'),
        ];
    }

}
