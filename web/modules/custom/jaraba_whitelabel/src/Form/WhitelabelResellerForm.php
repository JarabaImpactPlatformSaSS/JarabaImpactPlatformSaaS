<?php

declare(strict_types=1);

namespace Drupal\jaraba_whitelabel\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for creating/editing WhitelabelReseller entities.
 */
class WhitelabelResellerForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['general_info'] = [
      '#type' => 'details',
      '#title' => $this->t('General Information'),
      '#open' => TRUE,
      '#weight' => -20,
    ];
    $form['name']['#group'] = 'general_info';
    $form['company_name']['#group'] = 'general_info';
    $form['contact_email']['#group'] = 'general_info';

    $form['commercial_config'] = [
      '#type' => 'details',
      '#title' => $this->t('Commercial Configuration'),
      '#open' => TRUE,
      '#weight' => -10,
    ];
    $form['commission_rate']['#group'] = 'commercial_config';
    $form['revenue_share_model']['#group'] = 'commercial_config';
    $form['territory']['#group'] = 'commercial_config';

    $form['tenants_group'] = [
      '#type' => 'details',
      '#title' => $this->t('Tenants'),
      '#open' => TRUE,
      '#weight' => 0,
    ];
    $form['managed_tenant_ids']['#group'] = 'tenants_group';

    $form['contract_group'] = [
      '#type' => 'details',
      '#title' => $this->t('Contract'),
      '#open' => TRUE,
      '#weight' => 10,
    ];
    $form['contract_start']['#group'] = 'contract_group';
    $form['contract_end']['#group'] = 'contract_group';
    $form['reseller_status']['#group'] = 'contract_group';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $territoryRaw = $form_state->getValue('territory')[0]['value'] ?? '';
    if (!empty($territoryRaw)) {
      $decoded = json_decode($territoryRaw, TRUE);
      if (!is_array($decoded)) {
        $form_state->setErrorByName('territory', $this->t('Territory must be valid JSON.'));
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
      ? $this->t('Reseller %label created.', ['%label' => $entity->label()])
      : $this->t('Reseller %label updated.', ['%label' => $entity->label()]);

    $this->messenger()->addStatus($message);
    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $result;
  }

}
