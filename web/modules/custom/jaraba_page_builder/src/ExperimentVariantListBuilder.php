<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Proporciona una lista de entidades ExperimentVariant.
 *
 * ESPECIFICACIÓN: Doc 168 - Platform_AB_Testing_Pages_v1
 *
 * Esta clase genera la tabla de listado de variantes de experimento.
 * Se accede desde la vista de detalle de cada experimento.
 *
 * @package Drupal\jaraba_page_builder
 */
class ExperimentVariantListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     *
     * Define las columnas de la tabla de variantes.
     * Muestra nombre, si es control, peso de tráfico y métricas.
     */
    public function buildHeader(): array
    {
        $header['name'] = $this->t('Nombre');
        $header['is_control'] = $this->t('Control');
        $header['traffic'] = $this->t('Tráfico');
        $header['visitors'] = $this->t('Visitantes');
        $header['conversions'] = $this->t('Conversiones');
        $header['rate'] = $this->t('Tasa');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     *
     * Construye cada fila con los datos de la variante.
     * Incluye métricas de rendimiento calculadas.
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_page_builder\Entity\ExperimentVariant $entity */
        $row['name'] = $entity->getName();

        // Indicador visual de variante de control.
        $row['is_control'] = $entity->isControl()
            ? $this->t('✓ Sí')
            : $this->t('No');

        // Peso de tráfico con formato de porcentaje.
        $row['traffic'] = $entity->getTrafficWeight() . '%';

        // Métricas de visitantes y conversiones.
        $row['visitors'] = number_format($entity->getVisitors());
        $row['conversions'] = number_format($entity->getConversions());

        // Tasa de conversión formateada.
        $rate = $entity->getConversionRate();
        $row['rate'] = number_format($rate, 2) . '%';

        return $row + parent::buildRow($entity);
    }

    /**
     * {@inheritdoc}
     *
     * Mensaje cuando no hay variantes creadas.
     */
    public function render(): array
    {
        $build = parent::render();
        $build['table']['#empty'] = $this->t('Este experimento no tiene variantes todavía.');
        return $build;
    }

}
