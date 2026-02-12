<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Proporciona una lista de entidades PageExperiment.
 *
 * ESPECIFICACIÓN: Doc 168 - Platform_AB_Testing_Pages_v1
 *
 * Esta clase genera la tabla de listado de experimentos A/B
 * en /admin/content/experiments.
 *
 * @package Drupal\jaraba_page_builder
 */
class PageExperimentListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     *
     * Define las columnas de la tabla de listado.
     * Cada elemento del array se convierte en una columna.
     */
    public function buildHeader(): array
    {
        $header['id'] = $this->t('ID');
        $header['name'] = $this->t('Nombre');
        $header['status'] = $this->t('Estado');
        $header['page'] = $this->t('Página');
        $header['created'] = $this->t('Creado');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     *
     * Construye cada fila de la tabla con los datos del experimento.
     * Los métodos del experimento (getName, getStatus, etc.) están
     * definidos en la entidad PageExperiment.
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_page_builder\Entity\PageExperiment $entity */
        $row['id'] = $entity->id();
        $row['name'] = $entity->toLink($entity->getName())->toString();

        // Traducción del estado para la UI.
        $statuses = [
            'draft' => $this->t('Borrador'),
            'running' => $this->t('En ejecución'),
            'paused' => $this->t('Pausado'),
            'completed' => $this->t('Completado'),
        ];
        $status = $entity->getStatus();
        $row['status'] = $statuses[$status] ?? $status;

        // Referencia a la página del experimento.
        $pageId = $entity->getPageId();
        $row['page'] = $pageId ? $this->t('Página @id', ['@id' => $pageId]) : '-';

        // Fecha de creación formateada.
        $created = $entity->get('created')->value;
        $row['created'] = $created ? date('d/m/Y H:i', (int) $created) : '-';

        return $row + parent::buildRow($entity);
    }

    /**
     * {@inheritdoc}
     *
     * Texto a mostrar cuando no hay experimentos.
     */
    public function render(): array
    {
        $build = parent::render();
        $build['table']['#empty'] = $this->t('No hay experimentos A/B creados todavía.');
        return $build;
    }

}
