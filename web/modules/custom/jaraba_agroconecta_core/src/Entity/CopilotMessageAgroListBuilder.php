<?php
declare(strict_types=1);
namespace Drupal\jaraba_agroconecta_core\Entity;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class CopilotMessageAgroListBuilder extends EntityListBuilder
{
    public function buildHeader(): array
    {
        $header['role'] = $this->t('Rol');
        $header['content'] = $this->t('Contenido');
        $header['model'] = $this->t('Modelo');
        $header['tokens'] = $this->t('Tokens');
        $header['latency'] = $this->t('Latencia');
        return $header + parent::buildHeader();
    }

    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_agroconecta_core\Entity\CopilotMessageAgro $entity */
        $row['role'] = $entity->getRole();
        $content = $entity->getContent();
        $row['content'] = mb_strlen($content) > 80 ? mb_substr($content, 0, 80) . '…' : $content;
        $row['model'] = $entity->get('model_used')->value ?? '—';
        $row['tokens'] = ($entity->getTokensInput() + $entity->getTokensOutput());
        $row['latency'] = ($entity->get('latency_ms')->value ?? 0) . 'ms';
        return $row + parent::buildRow($entity);
    }
}
