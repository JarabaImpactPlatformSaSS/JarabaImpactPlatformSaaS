<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para OffboardingRequest.
 *
 * Permite crear y gestionar solicitudes de baja de tenants.
 * El workflow completo es gestionado por OffboardingManagerService.
 */
class OffboardingRequestForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'tenant_info' => [
        'label' => $this->t('Tenant Information'),
        'icon' => ['category' => 'business', 'name' => 'briefcase'],
        'description' => $this->t('Tenant reference and name for the offboarding request.'),
        'fields' => ['tenant_id', 'tenant_name', 'requested_by'],
      ],
      'reason' => [
        'label' => $this->t('Reason'),
        'icon' => ['category' => 'ui', 'name' => 'document'],
        'description' => $this->t('Reason and details for the offboarding request.'),
        'fields' => ['reason', 'reason_details'],
      ],
      'status' => [
        'label' => $this->t('Status & Timeline'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Current status and grace period end date.'),
        'fields' => ['status', 'grace_period_end'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'document'];
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
