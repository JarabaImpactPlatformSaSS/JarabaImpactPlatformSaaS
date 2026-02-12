<?php

declare(strict_types=1);

namespace Drupal\jaraba_sepe_teleformacion;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for SepeCentro entities.
 *
 * Muestra la lista de centros SEPE en /admin/content/sepe-centros
 * con columnas para CIF, Razón Social, Código SEPE, Estado y operaciones.
 */
class SepeCentroListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['cif'] = $this->t('CIF/NIF');
        $header['razon_social'] = $this->t('Razón Social');
        $header['codigo_sepe'] = $this->t('Código SEPE');
        $header['tipo_registro'] = $this->t('Tipo');
        $header['is_active'] = $this->t('Estado');

        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_sepe_teleformacion\Entity\SepeCentro $entity */

        $row['cif'] = $entity->get('cif')->value;
        $row['razon_social'] = $entity->label();
        $row['codigo_sepe'] = $entity->get('codigo_sepe')->value ?: '-';

        // Mapear tipo de registro.
        $tipo = $entity->get('tipo_registro')->value;
        $row['tipo_registro'] = match ($tipo) {
            'inscripcion' => $this->t('Inscripción'),
            'acreditacion' => $this->t('Acreditación'),
            default => $tipo,
        };

        // Indicador de estado activo/inactivo.
        $is_active = $entity->get('is_active')->value;
        $row['is_active'] = [
            'data' => [
                '#markup' => $is_active
                    ? '<span class="badge badge--success">' . $this->t('Activo') . '</span>'
                    : '<span class="badge badge--secondary">' . $this->t('Inactivo') . '</span>',
            ],
        ];

        return $row + parent::buildRow($entity);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOperations(EntityInterface $entity): array
    {
        $operations = parent::getDefaultOperations($entity);

        // Añadir operación para establecer como centro activo.
        if ($entity->access('update')) {
            $operations['set_active'] = [
                'title' => $this->t('Establecer como activo'),
                'weight' => 10,
                'url' => $entity->toUrl('edit-form'),
            ];
        }

        return $operations;
    }

}
