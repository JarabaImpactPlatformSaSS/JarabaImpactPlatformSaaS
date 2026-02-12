<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for CandidateSkill entities.
 */
class CandidateSkillListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['user'] = $this->t('User');
        $header['skill'] = $this->t('Skill');
        $header['level'] = $this->t('Level');
        $header['years'] = $this->t('Years');
        $header['verified'] = $this->t('Verified');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_candidate\Entity\CandidateSkillInterface $entity */
        $row['user'] = $entity->getOwner()?->getDisplayName() ?? '-';

        // Get skill term label.
        $skill_id = $entity->getSkillId();
        if ($skill_id) {
            $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($skill_id);
            $row['skill'] = $term ? $term->label() : "Term #$skill_id";
        } else {
            $row['skill'] = '-';
        }

        $row['level'] = ucfirst($entity->getLevel());
        $row['years'] = $entity->getYearsExperience();
        $row['verified'] = $entity->isVerified() ? $this->t('Yes') : $this->t('No');

        return $row + parent::buildRow($entity);
    }

}
