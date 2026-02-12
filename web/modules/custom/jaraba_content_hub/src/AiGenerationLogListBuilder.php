<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for AI Generation Log entities.
 */
class AiGenerationLogListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['id'] = $this->t('ID');
        $header['agent'] = $this->t('Agent');
        $header['action'] = $this->t('Action');
        $header['success'] = $this->t('Success');
        $header['tier'] = $this->t('Tier');
        $header['tokens'] = $this->t('Tokens');
        $header['user'] = $this->t('User');
        $header['created'] = $this->t('Created');

        return $header;
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_content_hub\Entity\AiGenerationLog $entity */
        $row['id'] = $entity->id();
        $row['agent'] = $entity->getAgentId();
        $row['action'] = $entity->getAction();
        $row['success'] = $entity->isSuccessful() ? '✓' : '✗';
        $row['tier'] = $entity->get('tier')->value ?? '-';
        $row['tokens'] = $entity->getTokensUsed();
        $row['user'] = $entity->getOwner() ? $entity->getOwner()->getDisplayName() : '-';
        $row['created'] = date('Y-m-d H:i', $entity->get('created')->value);

        return $row;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityIds(): array
    {
        $query = $this->getStorage()->getQuery()
            ->accessCheck(TRUE)
            ->sort('created', 'DESC')
            ->pager(50);

        return $query->execute();
    }

}
