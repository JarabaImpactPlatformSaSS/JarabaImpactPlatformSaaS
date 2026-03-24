<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Form;

use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Form for AsistenciaDetalladaEi entity.
 *
 * PREMIUM-FORMS-PATTERN-001: Extends PremiumEntityFormBase.
 */
class AsistenciaDetalladaEiForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'sesion' => [
        'label' => $this->t('Sesión'),
        'icon' => ['category' => 'education', 'name' => 'calendar-clock'],
        'description' => $this->t('Datos de la sesión y participante.'),
        'fields' => [
          'participante_id',
          'sesion_id',
          'modulo',
          'fecha',
          'modalidad',
          'horas',
        ],
      ],
      'registro' => [
        'label' => $this->t('Registro'),
        'icon' => ['category' => 'actions', 'name' => 'check-circle'],
        'description' => $this->t('Control de asistencia y evidencia.'),
        'fields' => [
          'asistio',
          'evidencia',
          'registrado_por',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'education', 'name' => 'clipboard'];
  }

}
