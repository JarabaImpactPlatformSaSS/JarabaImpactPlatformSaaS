<?php

declare(strict_types=1);

namespace Drupal\jaraba_ab_testing\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar exposiciones de experimento.
 */
class ExperimentExposureForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'reference' => [
        'label' => $this->t('Experiment Reference'),
        'icon' => ['category' => 'business', 'name' => 'briefcase'],
        'description' => $this->t('Parent experiment assignment.'),
        'fields' => ['experiment_id'],
      ],
      'visitor' => [
        'label' => $this->t('Variant & Visitor'),
        'icon' => ['category' => 'users', 'name' => 'user'],
        'description' => $this->t('Variant key, visitor ID and authenticated user.'),
        'fields' => ['variant_id', 'visitor_id', 'user_id'],
      ],
      'context' => [
        'label' => $this->t('Visitor Context'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Device type, browser and country information.'),
        'fields' => ['device_type', 'browser', 'country'],
      ],
      'utm' => [
        'label' => $this->t('UTM Parameters'),
        'icon' => ['category' => 'social', 'name' => 'share'],
        'description' => $this->t('Traffic source and campaign tracking.'),
        'fields' => ['utm_source', 'utm_campaign'],
      ],
      'conversion' => [
        'label' => $this->t('Exposure & Conversion'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Exposure timestamp, conversion flag and value.'),
        'fields' => ['exposed_at', 'converted', 'conversion_value'],
      ],
      'tenant' => [
        'label' => $this->t('Tenant'),
        'icon' => ['category' => 'business', 'name' => 'briefcase'],
        'description' => $this->t('Multi-tenant assignment.'),
        'fields' => ['tenant_id'],
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
