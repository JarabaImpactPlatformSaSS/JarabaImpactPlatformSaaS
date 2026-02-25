<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario de creacion/edicion de publicaciones programadas.
 *
 * P1-05: Permite al usuario programar la publicacion o despublicacion
 * de una pagina del Page Builder en una fecha y hora especifica.
 */
class ScheduledPublishForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'scheduling' => [
        'label' => $this->t('Scheduling'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Page, action, and scheduled date.'),
        'fields' => ['label', 'page_content_id', 'action', 'scheduled_at'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Schedule status and notes.'),
        'fields' => ['schedule_status', 'notes'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'actions', 'name' => 'calendar'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    // Only show status to administrators.
    if (isset($form['premium_section_status']['schedule_status'])) {
      if (!$this->currentUser()->hasPermission('administer page builder')) {
        $form['premium_section_status']['schedule_status']['#access'] = FALSE;
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    // Validate that the scheduled date is in the future.
    $scheduledAt = $form_state->getValue('scheduled_at');
    if (!empty($scheduledAt[0]['value'])) {
      $scheduledTimestamp = strtotime($scheduledAt[0]['value']);
      $now = \Drupal::time()->getRequestTime();
      if ($scheduledTimestamp && $scheduledTimestamp <= $now) {
        $form_state->setErrorByName(
          'scheduled_at',
          $this->t('La fecha programada debe ser en el futuro.')
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->entity;

    // Assign tenant from current user if not defined.
    if ($entity->get('tenant_id')->isEmpty()) {
      if (\Drupal::hasService('jaraba_page_builder.tenant_resolver')) {
        $tenantResolver = \Drupal::service('jaraba_page_builder.tenant_resolver');
        $tenant = $tenantResolver->getCurrentTenant();
        if ($tenant) {
          $entity->set('tenant_id', $tenant->id());
        }
      }
    }

    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
