<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Form;

use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Form for IndicadorFsePlus entity.
 *
 * PREMIUM-FORMS-PATTERN-001: Extends PremiumEntityFormBase.
 */
class IndicadorFsePlusForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'datos_recogida' => [
        'label' => $this->t('Datos de Recogida'),
        'icon' => ['category' => 'ui', 'name' => 'calendar-check'],
        'description' => $this->t('Participante, momento y fecha de recogida de indicadores FSE+.'),
        'fields' => [
          'participante_id',
          'momento_recogida',
          'fecha_recogida',
        ],
      ],
      'sociodemograficos' => [
        'label' => $this->t('Indicadores Sociodemográficos'),
        'icon' => ['category' => 'ui', 'name' => 'users'],
        'description' => $this->t('Indicadores de entrada: situación laboral, educación, discapacidad, origen y vivienda.'),
        'fields' => [
          'situacion_laboral',
          'nivel_educativo_isced',
          'discapacidad',
          'discapacidad_tipo',
          'discapacidad_grado',
          'pais_origen',
          'nacionalidad',
          'hogar_unipersonal',
          'hijos_a_cargo',
          'zona_residencia',
          'situacion_sin_hogar',
          'comunidad_marginada',
        ],
      ],
      'resultados' => [
        'label' => $this->t('Indicadores de Resultado'),
        'icon' => ['category' => 'ui', 'name' => 'chart-bar'],
        'description' => $this->t('Indicadores de salida y seguimiento: empleo, cualificación, inclusión.'),
        'fields' => [
          'situacion_laboral_resultado',
          'tipo_contrato_resultado',
          'cualificacion_obtenida',
          'tipo_cualificacion',
          'mejora_situacion',
          'inclusion_social',
        ],
      ],
      'sistema' => [
        'label' => $this->t('Sistema'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Estado de completitud y observaciones.'),
        'fields' => [
          'completado',
          'notas',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'chart-bar'];
  }

}
