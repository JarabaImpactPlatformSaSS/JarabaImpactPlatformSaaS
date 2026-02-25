<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing OAuth2 clients.
 */
class OauthClientForm extends PremiumEntityFormBase {

  protected function getSectionDefinitions(): array {
    return [
      'client' => [
        'label' => $this->t('Client'),
        'icon' => ['category' => 'ui', 'name' => 'lock'],
        'description' => $this->t('OAuth2 client credentials and configuration.'),
        'fields' => ['name', 'client_id', 'client_secret', 'redirect_uri', 'scopes'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'description' => $this->t('Activation and tenant settings.'),
        'fields' => ['is_active', 'tenant_id'],
      ],
    ];
  }

  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'lock'];
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);
    $entity = $this->getEntity();
    if ($entity->isNew()) {
      if (isset($form['premium_section_client']['client_id'])) {
        $form['premium_section_client']['client_id']['widget'][0]['value']['#default_value'] = bin2hex(random_bytes(16));
      }
      if (isset($form['premium_section_client']['client_secret'])) {
        $form['premium_section_client']['client_secret']['widget'][0]['value']['#default_value'] = bin2hex(random_bytes(32));
      }
    }
    return $form;
  }

  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
    return $result;
  }

}
