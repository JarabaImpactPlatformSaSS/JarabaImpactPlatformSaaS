<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

/**
 * List builder para IssuedCredential.
 */
class IssuedCredentialListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['recipient'] = $this->t('Receptor');
        $header['template'] = $this->t('Template');
        $header['status'] = $this->t('Estado');
        $header['issued_on'] = $this->t('Emitida');
        $header['verify'] = $this->t('Verificar');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_credentials\Entity\IssuedCredential $entity */
        $row['recipient'] = $entity->get('recipient_name')->value ?? $entity->get('recipient_email')->value ?? '-';

        $template = $entity->getTemplate();
        $row['template'] = $template ? $template->get('name')->value : '-';

        $status = $entity->get('status')->value ?? '';
        $row['status'] = match ($status) {
            'active' => $this->t('Activa'),
            'revoked' => $this->t('Revocada'),
            'expired' => $this->t('Expirada'),
            'suspended' => $this->t('Suspendida'),
            default => $status,
        };

        $row['issued_on'] = $entity->get('issued_on')->value ?? '-';

        // Enlace de verificación pública
        $uuid = $entity->uuid();
        $verifyUrl = Url::fromRoute('jaraba_credentials.verify', ['uuid' => $uuid]);
        $row['verify'] = [
            'data' => [
                '#type' => 'link',
                '#title' => $this->t('Verificar'),
                '#url' => $verifyUrl,
                '#attributes' => [
                    'target' => '_blank',
                    'class' => ['button', 'button--small'],
                ],
            ],
        ];

        return $row + parent::buildRow($entity);
    }

}

