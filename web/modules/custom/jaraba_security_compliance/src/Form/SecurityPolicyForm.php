<?php

declare(strict_types=1);

namespace Drupal\jaraba_security_compliance\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de creación/edición de SecurityPolicy.
 *
 * PROPOSITO:
 * Permite a administradores crear y editar políticas de seguridad
 * con versionado, fechas de vigencia y contenido completo.
 *
 * LOGICA:
 * - Grupo 1: Identificación (nombre, tipo, tenant)
 * - Grupo 2: Contenido y Versión (contenido, versión, fechas)
 * - Grupo 3: Estado (estado de la política)
 */
class SecurityPolicyForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    // Grupo 1: Identificación.
    $form['identification'] = [
      '#type' => 'details',
      '#title' => $this->t('Identificación'),
      '#open' => TRUE,
      '#weight' => -30,
    ];
    $form['name']['#group'] = 'identification';
    $form['policy_type']['#group'] = 'identification';
    $form['tenant_id']['#group'] = 'identification';

    // Grupo 2: Contenido y Versión.
    $form['content_version'] = [
      '#type' => 'details',
      '#title' => $this->t('Contenido y Versión'),
      '#open' => TRUE,
      '#weight' => -20,
    ];
    $form['content']['#group'] = 'content_version';
    $form['version']['#group'] = 'content_version';
    $form['effective_date']['#group'] = 'content_version';
    $form['review_date']['#group'] = 'content_version';

    // Grupo 3: Estado.
    $form['status_group'] = [
      '#type' => 'details',
      '#title' => $this->t('Estado'),
      '#open' => TRUE,
      '#weight' => -10,
    ];
    $form['policy_status']['#group'] = 'status_group';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $entity = $this->entity;
    $message = $result === SAVED_NEW
      ? $this->t('Política de seguridad %label creada.', ['%label' => $entity->label()])
      : $this->t('Política de seguridad %label actualizada.', ['%label' => $entity->label()]);

    $this->messenger()->addStatus($message);
    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $result;
  }

}
