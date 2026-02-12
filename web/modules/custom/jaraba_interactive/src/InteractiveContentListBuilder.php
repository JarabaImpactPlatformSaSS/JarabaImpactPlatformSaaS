<?php

declare(strict_types=1);

namespace Drupal\jaraba_interactive;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Define el listado de contenidos interactivos.
 */
class InteractiveContentListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['id'] = $this->t('ID');
        $header['title'] = $this->t('Título');
        $header['content_type'] = $this->t('Tipo');
        $header['status'] = $this->t('Estado');
        $header['difficulty'] = $this->t('Dificultad');
        $header['author'] = $this->t('Autor');
        $header['changed'] = $this->t('Modificado');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_interactive\Entity\InteractiveContent $entity */
        $row['id'] = $entity->id();
        $row['title'] = Link::createFromRoute(
            $entity->label(),
            'entity.interactive_content.canonical',
            ['interactive_content' => $entity->id()]
        );
        $row['content_type'] = $this->getContentTypeLabel($entity->getContentType());
        $row['status'] = $this->getStatusBadge($entity->get('status')->value ?? 'draft');
        $row['difficulty'] = $entity->get('difficulty')->value ?? '-';
        $row['author'] = $entity->getOwner()?->getDisplayName() ?? $this->t('Anónimo');
        $row['changed'] = \Drupal::service('date.formatter')->format(
            $entity->get('changed')->value,
            'short'
        );
        return $row + parent::buildRow($entity);
    }

    /**
     * Obtiene el label del tipo de contenido.
     */
    protected function getContentTypeLabel(string $type): string
    {
        $labels = [
            'question_set' => (string) $this->t('Cuestionario'),
            'interactive_video' => (string) $this->t('Video Interactivo'),
            'course_presentation' => (string) $this->t('Presentación'),
            'branching_scenario' => (string) $this->t('Escenario Ramificado'),
            'drag_and_drop' => (string) $this->t('Arrastrar y Soltar'),
            'essay' => (string) $this->t('Ensayo'),
        ];
        return $labels[$type] ?? $type;
    }

    /**
     * Obtiene el badge de estado.
     */
    protected function getStatusBadge(string $status): array
    {
        $classes = [
            'draft' => 'badge--warning',
            'published' => 'badge--success',
            'archived' => 'badge--secondary',
        ];
        $labels = [
            'draft' => (string) $this->t('Borrador'),
            'published' => (string) $this->t('Publicado'),
            'archived' => (string) $this->t('Archivado'),
        ];

        return [
            '#type' => 'markup',
            '#markup' => '<span class="badge ' . ($classes[$status] ?? '') . '">' . ($labels[$status] ?? $status) . '</span>',
        ];
    }

}
