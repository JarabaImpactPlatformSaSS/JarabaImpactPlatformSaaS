<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Form;

use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Form for SesionProgramadaEi entity.
 *
 * PREMIUM-FORMS-PATTERN-001: Extends PremiumEntityFormBase.
 */
class SesionProgramadaEiForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'datos_principales' => [
        'label' => $this->t('Datos Principales'),
        'icon' => ['category' => 'ui', 'name' => 'calendar'],
        'description' => $this->t('Título, tipo de sesión y fase del programa.'),
        'fields' => [
          'titulo',
          'descripcion',
          'tipo_sesion',
          'fase_programa',
          'accion_formativa_id',
        ],
      ],
      'horario' => [
        'label' => $this->t('Horario'),
        'icon' => ['category' => 'ui', 'name' => 'clock'],
        'description' => $this->t('Fecha, hora y lugar de la sesión.'),
        'fields' => [
          'fecha',
          'hora_inicio',
          'hora_fin',
          'modalidad',
          'lugar_descripcion',
          'lugar_url',
        ],
      ],
      'facilitador' => [
        'label' => $this->t('Facilitador/a'),
        'icon' => ['category' => 'users', 'name' => 'user-graduate'],
        'description' => $this->t('Profesional que facilita la sesión.'),
        'fields' => [
          'facilitador_id',
          'facilitador_nombre',
        ],
      ],
      'capacidad' => [
        'label' => $this->t('Capacidad y Estado'),
        'icon' => ['category' => 'ui', 'name' => 'users'],
        'description' => $this->t('Plazas disponibles y estado de la sesión.'),
        'fields' => [
          'max_plazas',
          'plazas_ocupadas',
          'estado',
        ],
      ],
      'recurrencia' => [
        'label' => $this->t('Recurrencia'),
        'icon' => ['category' => 'ui', 'name' => 'refresh'],
        'description' => $this->t('Configuración de sesiones recurrentes.'),
        'fields' => [
          'es_recurrente',
          'recurrencia_patron',
          'sesion_padre_id',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'calendar'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getCharacterLimits(): array {
    return [
      'titulo' => 255,
    ];
  }

}
