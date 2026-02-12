<?php

declare(strict_types=1);

namespace Drupal\jaraba_whitelabel\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for creating/editing CustomDomain entities.
 */
class CustomDomainForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['domain_info'] = [
      '#type' => 'details',
      '#title' => $this->t('Domain Information'),
      '#open' => TRUE,
      '#weight' => -20,
    ];
    $form['domain']['#group'] = 'domain_info';
    $form['tenant_id']['#group'] = 'domain_info';

    $form['verification'] = [
      '#type' => 'details',
      '#title' => $this->t('Verification & SSL'),
      '#open' => TRUE,
      '#weight' => -10,
    ];
    $form['ssl_status']['#group'] = 'verification';
    $form['dns_verified']['#group'] = 'verification';
    $form['dns_verification_token']['#group'] = 'verification';
    $form['domain_status']['#group'] = 'verification';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $domain = $form_state->getValue('domain')[0]['value'] ?? '';
    if (!empty($domain) && !preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,}$/', $domain)) {
      $form_state->setErrorByName('domain', $this->t('Please enter a valid domain name (e.g. app.example.com).'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->entity;

    $message = $result === SAVED_NEW
      ? $this->t('Custom domain %label created.', ['%label' => $entity->label()])
      : $this->t('Custom domain %label updated.', ['%label' => $entity->label()]);

    $this->messenger()->addStatus($message);
    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $result;
  }

}
