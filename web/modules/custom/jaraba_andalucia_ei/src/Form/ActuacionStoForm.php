<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Form;

use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Form for ActuacionSto entity.
 *
 * PREMIUM-FORMS-PATTERN-001: Extends PremiumEntityFormBase.
 */
class ActuacionStoForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'actuacion' => [
        'label' => $this->t('Actuación'),
        'icon' => ['category' => 'ui', 'name' => 'clipboard-check'],
        'description' => $this->t('Datos principales de la actuación del itinerario.'),
        'fields' => [
          'participante_id',
          'tipo_actuacion',
          'fecha',
          'hora_inicio',
          'hora_fin',
          'lugar',
          'orientador_id',
        ],
      ],
      'contenido' => [
        'label' => $this->t('Contenido'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('Descripción y resultado de la actuación.'),
        'fields' => [
          'contenido',
          'resultado',
        ],
      ],
      'firma' => [
        'label' => $this->t('Firma y Recibo'),
        'icon' => ['category' => 'ui', 'name' => 'signature'],
        'description' => $this->t('Estado de firma y recibo de servicio.'),
        'fields' => [
          'firmado_participante',
          'firmado_orientador',
          'recibo_servicio_id',
        ],
      ],
      'vobo' => [
        'label' => $this->t('VoBo SAE'),
        'icon' => ['category' => 'ui', 'name' => 'shield-check'],
        'description' => $this->t('Visto Bueno del SAE (solo para formación).'),
        'fields' => [
          'vobo_sae_status',
          'vobo_sae_fecha',
          'vobo_sae_documento_id',
        ],
      ],
      'grupal' => [
        'label' => $this->t('Grupal'),
        'icon' => ['category' => 'ui', 'name' => 'users'],
        'description' => $this->t('Datos para actuaciones grupales.'),
        'fields' => [
          'grupo_participantes_ids',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'clipboard-check'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getCharacterLimits(): array {
    return [
      'contenido' => 512,
    ];
  }

}
