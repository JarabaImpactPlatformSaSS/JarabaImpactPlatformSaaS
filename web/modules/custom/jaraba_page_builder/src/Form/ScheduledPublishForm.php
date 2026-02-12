<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de creacion/edicion de publicaciones programadas.
 *
 * P1-05: Permite al usuario programar la publicacion o despublicacion
 * de una pagina del Page Builder en una fecha y hora especifica.
 */
class ScheduledPublishForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['scheduling'] = [
      '#type' => 'details',
      '#title' => $this->t('Programacion'),
      '#open' => TRUE,
      '#weight' => -5,
    ];

    // Mover campos relevantes al grupo de programacion.
    if (isset($form['page_content_id'])) {
      $form['page_content_id']['#group'] = 'scheduling';
    }
    if (isset($form['action'])) {
      $form['action']['#group'] = 'scheduling';
    }
    if (isset($form['scheduled_at'])) {
      $form['scheduled_at']['#group'] = 'scheduling';
    }

    // Solo mostrar estado a administradores.
    if (isset($form['schedule_status'])) {
      if (!$this->currentUser()->hasPermission('administer page builder')) {
        $form['schedule_status']['#access'] = FALSE;
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    // Validar que la fecha programada sea en el futuro.
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

    // Asignar tenant del usuario actual si no esta definido.
    if ($entity->get('tenant_id')->isEmpty()) {
      if (\Drupal::hasService('jaraba_page_builder.tenant_resolver')) {
        $tenantResolver = \Drupal::service('jaraba_page_builder.tenant_resolver');
        $tenant = $tenantResolver->getCurrentTenant();
        if ($tenant) {
          $entity->set('tenant_id', $tenant->id());
        }
      }
    }

    $status = parent::save($form, $form_state);

    if ($status === SAVED_NEW) {
      $this->messenger()->addStatus($this->t(
        'Publicacion programada "%label" creada correctamente.',
        ['%label' => $entity->label()]
      ));
    }
    else {
      $this->messenger()->addStatus($this->t(
        'Publicacion programada "%label" actualizada.',
        ['%label' => $entity->label()]
      ));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $status;
  }

}
