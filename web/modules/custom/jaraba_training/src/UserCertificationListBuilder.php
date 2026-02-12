<?php

declare(strict_types=1);

namespace Drupal\jaraba_training;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * ListBuilder para la entidad UserCertification.
 */
class UserCertificationListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header = [
            'user' => $this->t('Usuario'),
            'program' => $this->t('Programa'),
            'status' => $this->t('Estado'),
            'date' => $this->t('Fecha Certificación'),
            'certificate' => $this->t('Nº Certificado'),
        ];
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        $user = $entity->get('user_id')->entity;
        $program = $entity->get('program_id')->entity;

        $row = [
            'user' => $user ? $user->getDisplayName() : $this->t('(desconocido)'),
            'program' => $program ? $program->get('title')->value : $this->t('(sin programa)'),
            'status' => $entity->get('certification_status')->value ?? '',
            'date' => $entity->get('certification_date')->value ?? '-',
            'certificate' => $entity->get('certificate_number')->value ?? '-',
        ];
        return $row + parent::buildRow($entity);
    }

}
