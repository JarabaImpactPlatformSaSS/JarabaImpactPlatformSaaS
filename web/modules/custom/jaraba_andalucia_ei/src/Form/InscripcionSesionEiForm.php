<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Form;

use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Form for InscripcionSesionEi entity.
 *
 * PREMIUM-FORMS-PATTERN-001: Extends PremiumEntityFormBase.
 */
class InscripcionSesionEiForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'inscripcion' => [
        'label' => $this->t('Inscripción'),
        'icon' => ['category' => 'ui', 'name' => 'user-check'],
        'description' => $this->t('Datos de la inscripción del participante a la sesión.'),
        'fields' => [
          'sesion_id',
          'participante_id',
          'estado',
          'fecha_inscripcion',
        ],
      ],
      'asistencia' => [
        'label' => $this->t('Asistencia'),
        'icon' => ['category' => 'ui', 'name' => 'check-circle'],
        'description' => $this->t('Control de asistencia y horas computadas.'),
        'fields' => [
          'fecha_asistencia',
          'asistencia_verificada',
          'horas_computadas',
        ],
      ],
      'registro' => [
        'label' => $this->t('Registro'),
        'icon' => ['category' => 'ui', 'name' => 'clipboard'],
        'description' => $this->t('Vinculación con actuación STO y motivo de cancelación.'),
        'fields' => [
          'actuacion_sto_id',
          'motivo_cancelacion',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'user-check'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getCharacterLimits(): array {
    return [
      'motivo_cancelacion' => 512,
    ];
  }

}
