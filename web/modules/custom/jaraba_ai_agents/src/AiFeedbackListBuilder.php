<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for AI Feedback entities.
 *
 * Displays feedback records in the admin UI with response_id, rating,
 * user, and created date columns.
 *
 * FIX-034: AI Feedback entity and endpoint.
 */
class AiFeedbackListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['response_id'] = $this->t('Response ID');
        $header['rating'] = $this->t('Rating');
        $header['user'] = $this->t('User');
        $header['created'] = $this->t('Date');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_ai_agents\Entity\AiFeedback $entity */
        $row['response_id'] = $entity->getResponseId();
        $row['rating'] = (string) $entity->getRating() . '/5';

        // Resolve user label safely.
        $user = $entity->get('user_id')->entity;
        $row['user'] = $user ? $user->getDisplayName() : $this->t('Unknown');

        $row['created'] = date('Y-m-d H:i', (int) $entity->get('created')->value);
        return $row + parent::buildRow($entity);
    }

}
