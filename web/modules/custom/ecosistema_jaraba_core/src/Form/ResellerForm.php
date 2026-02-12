<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de creacion/edicion de Reseller.
 *
 * PROPOSITO:
 * Permite a administradores crear y editar resellers/partners
 * con su configuracion comercial y datos de contrato.
 *
 * LOGICA:
 * - Organiza campos en 4 fieldsets: informacion general,
 *   configuracion comercial, tenants y contrato
 * - Valida que el JSON de territorio sea valido si se proporciona
 * - Redirige a la coleccion tras guardar
 */
class ResellerForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    // Grupo 1: Informacion General.
    $form['general_info'] = [
      '#type' => 'details',
      '#title' => $this->t('Informacion General'),
      '#open' => TRUE,
      '#weight' => -20,
    ];
    $form['name']['#group'] = 'general_info';
    $form['company_name']['#group'] = 'general_info';
    $form['contact_email']['#group'] = 'general_info';

    // Grupo 2: Configuracion Comercial.
    $form['commercial_config'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuracion Comercial'),
      '#open' => TRUE,
      '#weight' => -10,
    ];
    $form['commission_rate']['#group'] = 'commercial_config';
    $form['revenue_share_model']['#group'] = 'commercial_config';
    $form['territory']['#group'] = 'commercial_config';

    // Grupo 3: Tenants.
    $form['tenants_group'] = [
      '#type' => 'details',
      '#title' => $this->t('Tenants'),
      '#open' => TRUE,
      '#weight' => 0,
    ];
    $form['managed_tenant_ids']['#group'] = 'tenants_group';

    // Grupo 4: Contrato.
    $form['contract_group'] = [
      '#type' => 'details',
      '#title' => $this->t('Contrato'),
      '#open' => TRUE,
      '#weight' => 10,
    ];
    $form['contract_start']['#group'] = 'contract_group';
    $form['contract_end']['#group'] = 'contract_group';
    $form['status_reseller']['#group'] = 'contract_group';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    // Validar JSON de territorio si se proporciona.
    $territoryRaw = $form_state->getValue('territory')[0]['value'] ?? '';
    if (!empty($territoryRaw)) {
      $decoded = json_decode($territoryRaw, TRUE);
      if (!is_array($decoded)) {
        $form_state->setErrorByName('territory', $this->t('El territorio debe ser un JSON valido.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $entity = $this->entity;
    $message = $result === SAVED_NEW
      ? $this->t('Reseller %label creado.', ['%label' => $entity->label()])
      : $this->t('Reseller %label actualizado.', ['%label' => $entity->label()]);

    $this->messenger()->addStatus($message);
    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $result;
  }

}
