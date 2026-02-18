<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para solicitudes Andalucía +ei.
 */
class SolicitudEiListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['nombre'] = $this->t('Nombre');
        $header['email'] = $this->t('Email');
        $header['provincia'] = $this->t('Provincia');
        $header['colectivo_inferido'] = $this->t('Colectivo');
        $header['ai_triage'] = $this->t('Triaje IA');
        $header['estado'] = $this->t('Estado');
        $header['created'] = $this->t('Fecha');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_andalucia_ei\Entity\SolicitudEiInterface $entity */
        $provincias = [
            'almeria' => 'Almería',
            'cadiz' => 'Cádiz',
            'cordoba' => 'Córdoba',
            'granada' => 'Granada',
            'huelva' => 'Huelva',
            'jaen' => 'Jaén',
            'malaga' => 'Málaga',
            'sevilla' => 'Sevilla',
        ];

        $colectivos = [
            'larga_duracion' => '🟠 Larga duración',
            'mayores_45' => '🟡 Mayores 45',
            'migrantes' => '🌍 Migrantes',
            'perceptores_prestaciones' => '🔵 Perceptores',
            'otros' => '⚪ Otros',
        ];

        $estados = [
            'pendiente' => '⏳ Pendiente',
            'contactado' => '📞 Contactado',
            'admitido' => '✅ Admitido',
            'rechazado' => '❌ Rechazado',
            'lista_espera' => '📋 Lista espera',
        ];

        $provincia = $entity->get('provincia')->value;
        $colectivo = $entity->getColectivoInferido();
        $estado = $entity->getEstado();

        $row['nombre'] = $entity->getNombre();
        $row['email'] = $entity->getEmail();
        $row['provincia'] = $provincias[$provincia] ?? $provincia;
        $row['colectivo_inferido'] = $colectivos[$colectivo] ?? $colectivo;

        // Triaje IA: mostrar score con badge de color.
        $aiScore = $entity->get('ai_score')->value;
        $aiRec = $entity->get('ai_recomendacion')->value ?? '';
        if ($aiScore !== NULL) {
            $recEmojis = ['admitir' => '🟢', 'revisar' => '🟡', 'rechazar' => '🔴'];
            $emoji = $recEmojis[$aiRec] ?? '⚪';
            $row['ai_triage'] = $emoji . ' ' . $aiScore . '/100';
        } else {
            $row['ai_triage'] = '—';
        }

        $row['estado'] = $estados[$estado] ?? $estado;
        $row['created'] = \Drupal::service('date.formatter')->format(
            (int) $entity->get('created')->value,
            'short'
        );

        return $row + parent::buildRow($entity);
    }

}
