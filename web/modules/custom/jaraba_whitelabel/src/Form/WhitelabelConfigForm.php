<?php

declare(strict_types=1);

namespace Drupal\jaraba_whitelabel\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for creating/editing WhitelabelConfig entities.
 */
class WhitelabelConfigForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['identity'] = [
      '#type' => 'details',
      '#title' => $this->t('Identity'),
      '#open' => TRUE,
      '#weight' => -20,
    ];
    $form['config_key']['#group'] = 'identity';
    $form['tenant_id']['#group'] = 'identity';
    $form['company_name']['#group'] = 'identity';

    $form['branding'] = [
      '#type' => 'details',
      '#title' => $this->t('Branding'),
      '#open' => TRUE,
      '#weight' => -10,
    ];
    $form['logo_url']['#group'] = 'branding';
    $form['favicon_url']['#group'] = 'branding';
    $form['primary_color']['#group'] = 'branding';
    $form['secondary_color']['#group'] = 'branding';

    $form['customisation'] = [
      '#type' => 'details',
      '#title' => $this->t('Customisation'),
      '#open' => TRUE,
      '#weight' => 0,
    ];
    $form['custom_css']['#group'] = 'customisation';
    $form['custom_footer_html']['#group'] = 'customisation';
    $form['hide_powered_by']['#group'] = 'customisation';
    $form['config_status']['#group'] = 'customisation';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    // Validate hex colour format.
    foreach (['primary_color', 'secondary_color'] as $field) {
      $value = $form_state->getValue($field)[0]['value'] ?? '';
      if (!empty($value) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
        $form_state->setErrorByName($field, $this->t('Colour must be a valid hex code (e.g. #FF8C42).'));
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
      ? $this->t('Whitelabel config %label created.', ['%label' => $entity->label()])
      : $this->t('Whitelabel config %label updated.', ['%label' => $entity->label()]);

    $this->messenger()->addStatus($message);
    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $result;
  }

}
