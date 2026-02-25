<?php

declare(strict_types=1);

namespace Drupal\jaraba_dr\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for DR Incident entities.
 */
class DrIncidentForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'incident' => [
        'label' => $this->t('Incident'),
        'icon' => ['category' => 'ui', 'name' => 'alert'],
        'fields' => ['title', 'severity', 'description', 'affected_services', 'impact'],
      ],
      'resolution' => [
        'label' => $this->t('Resolution'),
        'icon' => ['category' => 'ui', 'name' => 'check'],
        'fields' => ['root_cause', 'resolution', 'started_at', 'resolved_at'],
      ],
      'followup' => [
        'label' => $this->t('Follow-up'),
        'icon' => ['category' => 'ui', 'name' => 'document'],
        'fields' => ['communication_log', 'postmortem_url', 'assigned_to'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'fields' => ['status'],
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
