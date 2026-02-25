<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para AupViolation.
 *
 * En producción, las violaciones AUP se detectan automáticamente
 * vía AupEnforcerService. Este formulario permite la gestión manual.
 */
class AupViolationForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'tenant' => [
        'label' => $this->t('Tenant'),
        'icon' => ['category' => 'business', 'name' => 'briefcase'],
        'description' => $this->t('Tenant this violation belongs to.'),
        'fields' => ['tenant_id'],
      ],
      'classification' => [
        'label' => $this->t('Classification'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'description' => $this->t('Violation type and severity level.'),
        'fields' => ['violation_type', 'severity'],
      ],
      'details' => [
        'label' => $this->t('Details'),
        'icon' => ['category' => 'ui', 'name' => 'document'],
        'description' => $this->t('Detailed description of the detected violation.'),
        'fields' => ['description'],
      ],
      'action' => [
        'label' => $this->t('Action & Timeline'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Action taken and detection/resolution timestamps.'),
        'fields' => ['action_taken', 'detected_at', 'resolved_at'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'shield'];
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
