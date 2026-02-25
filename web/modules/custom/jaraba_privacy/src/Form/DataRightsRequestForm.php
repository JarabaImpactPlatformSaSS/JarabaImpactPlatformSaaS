<?php

declare(strict_types=1);

namespace Drupal\jaraba_privacy\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para DataRightsRequest (ARCO-POL).
 *
 * Permite crear y gestionar solicitudes de ejercicio de derechos
 * del interesado segun RGPD Art. 15-22.
 */
class DataRightsRequestForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'requester' => [
        'label' => $this->t('Requester'),
        'icon' => ['category' => 'users', 'name' => 'user'],
        'description' => $this->t('Requester identity and contact.'),
        'fields' => ['tenant_id', 'requester_email', 'requester_name'],
      ],
      'request' => [
        'label' => $this->t('Request'),
        'icon' => ['category' => 'ui', 'name' => 'document'],
        'description' => $this->t('Right type and description.'),
        'fields' => ['right_type', 'description'],
      ],
      'verification' => [
        'label' => $this->t('Verification'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'description' => $this->t('Identity verification status and method.'),
        'fields' => ['identity_verified', 'verification_method'],
      ],
      'resolution' => [
        'label' => $this->t('Resolution'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Status, response, and responsible handler.'),
        'fields' => ['status', 'response', 'handler_id'],
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
