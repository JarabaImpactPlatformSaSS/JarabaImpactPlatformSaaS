<?php

declare(strict_types=1);

namespace Drupal\jaraba_whitelabel\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for creating/editing WhitelabelEmailTemplate entities.
 */
class WhitelabelEmailTemplateForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['template_info'] = [
      '#type' => 'details',
      '#title' => $this->t('Template Information'),
      '#open' => TRUE,
      '#weight' => -20,
    ];
    $form['template_key']['#group'] = 'template_info';
    $form['subject']['#group'] = 'template_info';
    $form['tenant_id']['#group'] = 'template_info';
    $form['template_status']['#group'] = 'template_info';

    $form['content_group'] = [
      '#type' => 'details',
      '#title' => $this->t('Content'),
      '#open' => TRUE,
      '#weight' => -10,
    ];
    $form['body_html']['#group'] = 'content_group';
    $form['body_text']['#group'] = 'content_group';

    $form['available_tokens'] = [
      '#type' => 'details',
      '#title' => $this->t('Available Variables'),
      '#open' => FALSE,
      '#weight' => 0,
      '#description' => $this->t('You can use the following tokens in subject and body: {{ user_name }}, {{ company_name }}, {{ site_url }}, {{ current_date }}, {{ reset_link }}, {{ invoice_number }}, {{ invoice_total }}.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->entity;

    $message = $result === SAVED_NEW
      ? $this->t('Email template %label created.', ['%label' => $entity->label()])
      : $this->t('Email template %label updated.', ['%label' => $entity->label()]);

    $this->messenger()->addStatus($message);
    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $result;
  }

}
