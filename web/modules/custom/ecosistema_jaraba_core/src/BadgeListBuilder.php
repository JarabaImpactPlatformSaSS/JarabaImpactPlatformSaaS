<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * ListBuilder para la entidad Badge (Insignia).
 *
 * Muestra una tabla con las insignias configuradas y sus propiedades
 * principales para facilitar la gestión desde el panel de administración.
 */
class BadgeListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['name'] = $this->t('Nombre');
        $header['icon'] = $this->t('Icono');
        $header['category'] = $this->t('Categoría');
        $header['criteria_type'] = $this->t('Tipo de Criterio');
        $header['points'] = $this->t('Puntos');
        $header['active'] = $this->t('Estado');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\ecosistema_jaraba_core\Entity\Badge $entity */
        $row['name'] = $entity->getName();
        $row['icon'] = $entity->getIcon();

        // Mapeo de categorías a etiquetas legibles.
        $categoryLabels = [
            'onboarding' => $this->t('Onboarding'),
            'engagement' => $this->t('Engagement'),
            'achievement' => $this->t('Logro'),
            'milestone' => $this->t('Hito'),
            'community' => $this->t('Comunidad'),
        ];
        $row['category'] = $categoryLabels[$entity->getCategory()] ?? $entity->getCategory();

        // Mapeo de tipos de criterio a etiquetas legibles.
        $criteriaLabels = [
            'event_count' => $this->t('Conteo de eventos'),
            'first_action' => $this->t('Primera acción'),
            'streak' => $this->t('Racha consecutiva'),
            'manual' => $this->t('Manual'),
        ];
        $row['criteria_type'] = $criteriaLabels[$entity->getCriteriaType()] ?? $entity->getCriteriaType();

        $row['points'] = $entity->getPoints();
        $row['active'] = $entity->isActive() ? $this->t('Activa') : $this->t('Inactiva');

        return $row + parent::buildRow($entity);
    }

}
