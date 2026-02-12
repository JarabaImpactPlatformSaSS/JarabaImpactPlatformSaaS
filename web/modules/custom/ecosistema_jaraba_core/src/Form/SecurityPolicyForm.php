<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de creación/edición de SecurityPolicy.
 *
 * PROPÓSITO:
 * Permite a administradores crear y editar políticas de seguridad
 * a nivel global o por tenant.
 *
 * LÓGICA:
 * - Organiza campos en 3 grupos: identificación, configuración, estado
 * - Muestra mensaje de confirmación al guardar
 * - Redirige a la colección tras guardar
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
      '#weight' => -20,
    ];
    $form['name']['#group'] = 'identification';
    $form['tenant_id']['#group'] = 'identification';
    $form['scope']['#group'] = 'identification';

    // Grupo 2: Configuración de política.
    $form['policy_config'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuración de Política'),
      '#open' => TRUE,
      '#weight' => -10,
    ];
    $form['policy_type']['#group'] = 'policy_config';
    $form['rules']['#group'] = 'policy_config';
    $form['enforcement']['#group'] = 'policy_config';

    // Grupo 3: Estado.
    $form['status_group'] = [
      '#type' => 'details',
      '#title' => $this->t('Estado'),
      '#open' => TRUE,
      '#weight' => 0,
    ];
    $form['active']['#group'] = 'status_group';

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
