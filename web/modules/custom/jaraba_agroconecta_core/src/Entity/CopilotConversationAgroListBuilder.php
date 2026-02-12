<?php
declare(strict_types=1);
namespace Drupal\jaraba_agroconecta_core\Entity;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class CopilotConversationAgroListBuilder extends EntityListBuilder
{
    public function buildHeader(): array
    {
        $header['title'] = $this->t('Título');
        $header['intent'] = $this->t('Intent');
        $header['messages'] = $this->t('Msgs');
        $header['tokens'] = $this->t('Tokens');
        return $header + parent::buildHeader();
    }

    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_agroconecta_core\Entity\CopilotConversationAgro $entity */
        $row['title'] = $entity->getTitle();
        $row['intent'] = $entity->getIntent();
        $row['messages'] = $entity->getMessageCount();
        $totalTokens = (int) ($entity->get('total_tokens_input')->value ?? 0) + (int) ($entity->get('total_tokens_output')->value ?? 0);
        $row['tokens'] = number_format($totalTokens);
        return $row + parent::buildRow($entity);
    }
}
