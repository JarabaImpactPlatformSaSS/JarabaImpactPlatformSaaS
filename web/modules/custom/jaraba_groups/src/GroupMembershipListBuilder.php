<?php

declare(strict_types=1);

namespace Drupal\jaraba_groups;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a listing of Group Membership entities.
 */
class GroupMembershipListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['id'] = $this->t('ID');
        $header['group'] = $this->t('Grupo');
        $header['user'] = $this->t('Usuario');
        $header['role'] = $this->t('Rol');
        $header['status'] = $this->t('Estado');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_groups\Entity\GroupMembership $entity */
        $row['id'] = $entity->id();
        $row['group'] = $entity->getGroup()?->getName() ?? '-';
        $row['user'] = $entity->getOwner()?->getDisplayName() ?? '-';
        $row['role'] = $entity->getRole();
        $row['status'] = $entity->getStatus();
        return $row + parent::buildRow($entity);
    }

}
