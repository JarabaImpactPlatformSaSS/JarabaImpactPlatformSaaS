<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Form;

use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Form for AccionFormativaEi entity.
 *
 * PREMIUM-FORMS-PATTERN-001: Extends PremiumEntityFormBase.
 */
class AccionFormativaEiForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'datos_principales' => [
        'label' => $this->t('Datos Principales'),
        'icon' => ['category' => 'education', 'name' => 'book-open'],
        'description' => $this->t('Título, tipo y categoría de la acción formativa.'),
        'fields' => [
          'titulo',
          'descripcion',
          'tipo_formacion',
          'categoria',
          'modalidad',
          'carril',
        ],
      ],
      'duracion' => [
        'label' => $this->t('Duración'),
        'icon' => ['category' => 'ui', 'name' => 'clock'],
        'description' => $this->t('Horas previstas y ejecutadas.'),
        'fields' => [
          'horas_previstas',
          'horas_ejecutadas',
          'numero_sesiones',
          'orden',
        ],
      ],
      'formador' => [
        'label' => $this->t('Formador/a'),
        'icon' => ['category' => 'users', 'name' => 'user-graduate'],
        'description' => $this->t('Profesional que impartirá la formación.'),
        'fields' => [
          'formador_id',
          'formador_nombre',
        ],
      ],
      'vobo_sae' => [
        'label' => $this->t('VoBo SAE'),
        'icon' => ['category' => 'ui', 'name' => 'shield-check'],
        'description' => $this->t('Estado del Visto Bueno del Servicio Andaluz de Empleo.'),
        'fields' => [
          'estado',
          'vobo_codigo',
          'vobo_fecha_envio',
          'vobo_fecha_respuesta',
          'vobo_motivo_rechazo',
          'vobo_documento_id',
        ],
      ],
      'integraciones' => [
        'label' => $this->t('Integraciones'),
        'icon' => ['category' => 'ui', 'name' => 'link'],
        'description' => $this->t('Vínculos con otros módulos del ecosistema.'),
        'fields' => [
          'course_id',
          'interactive_content_id',
        ],
      ],
      'notas' => [
        'label' => $this->t('Notas Internas'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('Notas internas solo visibles para coordinadores.'),
        'fields' => [
          'notas_internas',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'education', 'name' => 'book-open'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getCharacterLimits(): array {
    return [
      'titulo' => 255,
      'notas_internas' => 2000,
    ];
  }

}
