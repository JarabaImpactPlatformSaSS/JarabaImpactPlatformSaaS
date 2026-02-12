<?php

declare(strict_types=1);

namespace Drupal\jaraba_skills;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para entidades AiSkill.
 */
class AiSkillListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['name'] = $this->t('Nombre');
        $header['skill_type'] = $this->t('Tipo');
        $header['priority'] = $this->t('Prioridad');
        $header['is_active'] = $this->t('Activo');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_skills\Entity\AiSkill $entity */
        $skillTypes = [
            'core' => '🌐 Core',
            'vertical' => '📊 Vertical',
            'agent' => '🤖 Agent',
            'tenant' => '🏢 Tenant',
        ];

        $row['name'] = $entity->label();
        $row['skill_type'] = $skillTypes[$entity->getSkillType()] ?? $entity->getSkillType();
        $row['priority'] = $entity->get('priority')->value ?? 0;
        $row['is_active'] = $entity->isActive() ? $this->t('Sí') : $this->t('No');

        return $row + parent::buildRow($entity);
    }

}
