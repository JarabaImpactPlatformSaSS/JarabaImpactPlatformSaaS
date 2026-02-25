<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario de creacion/edicion de Informes Personalizados.
 */
class CustomReportForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'general' => [
        'label' => $this->t('General Configuration'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Report name, tenant and type.'),
        'fields' => ['name', 'tenant_id', 'report_type'],
      ],
      'data' => [
        'label' => $this->t('Data'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Metrics, filters and date range.'),
        'fields' => ['metrics', 'filters', 'date_range'],
      ],
      'scheduling' => [
        'label' => $this->t('Scheduling'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Automatic delivery schedule and recipients.'),
        'fields' => ['schedule', 'recipients'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'analytics', 'name' => 'chart'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
    return $result;
  }

}
