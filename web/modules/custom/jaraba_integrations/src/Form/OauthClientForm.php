<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar clientes OAuth2.
 *
 * PROPÓSITO:
 * Genera automáticamente client_id y client_secret al crear.
 * Organizado en grupos: información y credenciales.
 */
class OauthClientForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);
    $entity = $this->getEntity();

    // Auto-generar client_id y client_secret para nuevas entidades.
    if ($entity->isNew()) {
      if (isset($form['client_id']['widget'][0]['value'])) {
        $form['client_id']['widget'][0]['value']['#default_value'] = 'jaraba_' . bin2hex(random_bytes(16));
        $form['client_id']['widget'][0]['value']['#description'] = $this->t('Generado automáticamente. Puede personalizarse.');
      }
      if (isset($form['client_secret']['widget'][0]['value'])) {
        $form['client_secret']['widget'][0]['value']['#default_value'] = bin2hex(random_bytes(32));
        $form['client_secret']['widget'][0]['value']['#description'] = $this->t('Generado automáticamente. Copie y guarde de forma segura.');
      }
    }
    else {
      // Ocultar secret en edición.
      if (isset($form['client_secret'])) {
        $form['client_secret']['#access'] = FALSE;
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
      $this->messenger()->addStatus($this->t('Cliente OAuth %label creado. Client ID: @id', [
        '%label' => $label,
        '@id' => $entity->getClientId(),
      ]));
    }
    else {
      $this->messenger()->addStatus($this->t('Cliente OAuth %label actualizado.', ['%label' => $label]));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
