<?php

declare(strict_types=1);

namespace Drupal\jaraba_diagnostic;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * ListBuilder para la entidad BusinessDiagnostic.
 *
 * Proporciona listado con filtros en /admin/content/diagnostics.
 * Implementa Patrón 19 (ListBuilders Filtrables) según Standard 28.6.
 */
class BusinessDiagnosticListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['business_name'] = $this->t('Negocio');
        $header['sector'] = $this->t('Sector');
        $header['score'] = $this->t('Puntuación');
        $header['maturity'] = $this->t('Nivel');
        $header['status'] = $this->t('Estado');
        $header['created'] = $this->t('Fecha');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_diagnostic\Entity\BusinessDiagnosticInterface $entity */

        // Nombre del negocio con enlace
        $row['business_name'] = Link::createFromRoute(
            $entity->getBusinessName(),
            'entity.business_diagnostic.canonical',
            ['business_diagnostic' => $entity->id()]
        );

        // Sector con badge
        $sectorLabels = [
            'comercio' => 'Comercio',
            'servicios' => 'Servicios',
            'agro' => 'Agro',
            'hosteleria' => 'Hostelería',
            'industria' => 'Industria',
            'tech' => 'Tech',
            'otros' => 'Otros',
        ];
        $sector = $entity->get('business_sector')->value ?? '';
        $row['sector'] = $sectorLabels[$sector] ?? $sector;

        // Puntuación con color
        $score = $entity->getOverallScore();
        $scoreClass = match (TRUE) {
            $score >= 80 => 'score-excellent',
            $score >= 60 => 'score-good',
            $score >= 40 => 'score-average',
            $score >= 20 => 'score-poor',
            default => 'score-critical',
        };
        $row['score'] = [
            'data' => [
                '#markup' => '<span class="diagnostic-score ' . $scoreClass . '">' . number_format($score, 1) . '</span>',
            ],
        ];

        // Nivel de madurez con badge
        $maturityLabels = [
            'analogico' => ['label' => 'Analógico', 'class' => 'badge--danger'],
            'basico' => ['label' => 'Básico', 'class' => 'badge--warning'],
            'conectado' => ['label' => 'Conectado', 'class' => 'badge--info'],
            'digitalizado' => ['label' => 'Digitalizado', 'class' => 'badge--success'],
            'inteligente' => ['label' => 'Inteligente', 'class' => 'badge--primary'],
        ];
        $maturity = $entity->getMaturityLevel();
        $maturityData = $maturityLabels[$maturity] ?? ['label' => $maturity, 'class' => ''];
        $row['maturity'] = [
            'data' => [
                '#markup' => '<span class="badge ' . $maturityData['class'] . '">' . $maturityData['label'] . '</span>',
            ],
        ];

        // Estado
        $statusLabels = [
            'in_progress' => ['label' => 'En Progreso', 'class' => 'badge--warning'],
            'completed' => ['label' => 'Completado', 'class' => 'badge--success'],
            'archived' => ['label' => 'Archivado', 'class' => 'badge--secondary'],
        ];
        $status = $entity->get('status')->value ?? 'in_progress';
        $statusData = $statusLabels[$status] ?? ['label' => $status, 'class' => ''];
        $row['status'] = [
            'data' => [
                '#markup' => '<span class="badge ' . $statusData['class'] . '">' . $statusData['label'] . '</span>',
            ],
        ];

        // Fecha de creación
        $created = $entity->get('created')->value;
        $row['created'] = $created ? \Drupal::service('date.formatter')->format($created, 'short') : '-';

        return $row + parent::buildRow($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function render(): array
    {
        $build = parent::render();

        // Añadir librerías de estilos
        $build['#attached']['library'][] = 'jaraba_diagnostic/admin';

        return $build;
    }

}
