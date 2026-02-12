<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar suscripciones webhook.
 *
 * PROPÓSITO:
 * Genera automáticamente el secret HMAC al crear.
 */
class WebhookSubscriptionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);
    $entity = $this->getEntity();

    // Auto-generar secret para nuevas suscripciones.
    if ($entity->isNew()) {
      if (isset($form['secret']['widget'][0]['value'])) {
        $form['secret']['widget'][0]['value']['#default_value'] = 'whsec_' . bin2hex(random_bytes(24));
        $form['secret']['widget'][0]['value']['#description'] = $this->t('Generado automáticamente. Úselo para verificar firmas HMAC-SHA256.');
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->getEntity();
    $is_new = $entity->isNew();
    $result = parent::save($form, $form_state);

    $label = $entity->label();
    if ($is_new) {
      $this->messenger()->addStatus($this->t('Webhook %label creado.', ['%label' => $label]));
    }
    else {
      $this->messenger()->addStatus($this->t('Webhook %label actualizado.', ['%label' => $label]));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
