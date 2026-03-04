<?php

declare(strict_types=1);

namespace Drupal\jaraba_pilot_manager\Form;

use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para la entidad Pilot Program.
 *
 * PREMIUM-FORMS-PATTERN-001: Extiende PremiumEntityFormBase.
 */
class PilotProgramForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'basic' => [
        'label' => $this->t('Informacion Basica'),
        'icon' => ['category' => 'ui', 'name' => 'info'],
        'description' => $this->t('Datos principales del programa piloto.'),
        'fields' => ['name', 'vertical', 'description', 'status'],
      ],
      'timeline' => [
        'label' => $this->t('Cronologia'),
        'icon' => ['category' => 'ui', 'name' => 'calendar'],
        'description' => $this->t('Fechas y duracion del piloto.'),
        'fields' => ['start_date', 'end_date', 'max_tenants', 'target_plan'],
      ],
      'evaluation' => [
        'label' => $this->t('Evaluacion'),
        'icon' => ['category' => 'analytics', 'name' => 'chart-bar'],
        'description' => $this->t('Criterios y metricas.'),
        'fields' => ['success_criteria', 'conversion_rate', 'avg_nps', 'total_enrolled', 'total_converted'],
      ],
      'management' => [
        'label' => $this->t('Gestion'),
        'icon' => ['category' => 'ui', 'name' => 'users'],
        'description' => $this->t('Asignacion y notas.'),
        'fields' => ['assigned_csm', 'tenant_id', 'notes'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'business', 'name' => 'target'];
  }

}
