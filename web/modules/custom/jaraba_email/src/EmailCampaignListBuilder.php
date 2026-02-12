<?php

declare(strict_types=1);

namespace Drupal\jaraba_email;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for Email Campaign entities.
 */
class EmailCampaignListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['name'] = $this->t('Campaign');
        $header['type'] = $this->t('Type');
        $header['status'] = $this->t('Status');
        $header['sent'] = $this->t('Sent');
        $header['opens'] = $this->t('Opens');
        $header['clicks'] = $this->t('Clicks');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        $row['name'] = $entity->toLink($entity->label());
        $row['type'] = $entity->get('type')->value ?? 'regular';
        $row['status'] = $entity->get('status')->value ?? 'draft';
        $row['sent'] = $entity->get('total_sent')->value ?? 0;
        $row['opens'] = sprintf(
            '%d (%.1f%%)',
            $entity->get('unique_opens')->value ?? 0,
            $entity->getOpenRate()
        );
        $row['clicks'] = sprintf(
            '%d (%.1f%%)',
            $entity->get('unique_clicks')->value ?? 0,
            $entity->getClickRate()
        );
        return $row + parent::buildRow($entity);
    }

}
