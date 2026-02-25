<?php

declare(strict_types=1);

namespace Drupal\jaraba_privacy\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para PrivacyPolicy.
 *
 * Permite crear y editar politicas de privacidad con contenido HTML
 * parametrizable por vertical y tenant.
 */
class PrivacyPolicyForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identity' => [
        'label' => $this->t('Identity'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'description' => $this->t('Tenant, vertical, and version.'),
        'fields' => ['tenant_id', 'vertical', 'version'],
      ],
      'content' => [
        'label' => $this->t('Content'),
        'icon' => ['category' => 'ui', 'name' => 'document'],
        'description' => $this->t('Policy HTML content.'),
        'fields' => ['content_html'],
      ],
      'publication' => [
        'label' => $this->t('Publication'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Active status and DPO contact.'),
        'fields' => ['is_active', 'dpo_contact'],
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
