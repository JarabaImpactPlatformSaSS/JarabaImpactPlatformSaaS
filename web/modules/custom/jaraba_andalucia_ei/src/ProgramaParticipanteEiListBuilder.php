<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * ListBuilder para la entidad ProgramaParticipanteEi.
 *
 * Define las columnas de la tabla en /admin/content/andalucia-ei.
 */
class ProgramaParticipanteEiListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['dni_nie'] = $this->t('DNI/NIE');
        $header['colectivo'] = $this->t('Colectivo');
        $header['provincia'] = $this->t('Provincia');
        $header['fase'] = $this->t('Fase');
        $header['horas_orientacion'] = $this->t('Horas Orientación');
        $header['horas_ia'] = $this->t('Horas IA');
        $header['sto_status'] = $this->t('STO');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_andalucia_ei\Entity\ProgramaParticipanteEiInterface $entity */

        $colectivoLabels = [
            'jovenes' => $this->t('Jóvenes'),
            'mayores_45' => $this->t('+45 años'),
            'larga_duracion' => $this->t('Larga duración'),
        ];

        $faseLabels = [
            'atencion' => $this->t('Atención'),
            'insercion' => $this->t('Inserción'),
            'baja' => $this->t('Baja'),
        ];

        $stoLabels = [
            'pending' => $this->t('Pendiente'),
            'synced' => $this->t('Sincronizado'),
            'error' => $this->t('Error'),
        ];

        $row['dni_nie'] = $entity->getDniNie();
        $row['colectivo'] = $colectivoLabels[$entity->getColectivo()] ?? $entity->getColectivo();
        $row['provincia'] = $entity->get('provincia_participacion')->value ?? '';
        $row['fase'] = $faseLabels[$entity->getFaseActual()] ?? $entity->getFaseActual();
        $row['horas_orientacion'] = number_format($entity->getTotalHorasOrientacion(), 1) . 'h';
        $row['horas_ia'] = number_format($entity->getHorasMentoriaIa(), 1) . 'h';
        $row['sto_status'] = $stoLabels[$entity->get('sto_sync_status')->value ?? 'pending'];

        return $row + parent::buildRow($entity);
    }

}
