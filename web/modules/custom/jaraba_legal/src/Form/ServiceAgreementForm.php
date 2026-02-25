<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para ServiceAgreement.
 *
 * En producción, los acuerdos se gestionan vía TosManagerService.
 * Este formulario permite la creación y edición manual desde admin.
 */
class ServiceAgreementForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identification' => [
        'label' => $this->t('Identification'),
        'icon' => ['category' => 'ui', 'name' => 'scale'],
        'description' => $this->t('Agreement title, type, and version.'),
        'fields' => ['tenant_id', 'title', 'agreement_type', 'version'],
      ],
      'content' => [
        'label' => $this->t('Content'),
        'icon' => ['category' => 'ui', 'name' => 'document'],
        'description' => $this->t('Full HTML content of the agreement.'),
        'fields' => ['content_html'],
      ],
      'publication' => [
        'label' => $this->t('Publication'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Publication date, active status, and acceptance settings.'),
        'fields' => ['published_at', 'is_active', 'requires_acceptance', 'effective_date'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'scale'];
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
