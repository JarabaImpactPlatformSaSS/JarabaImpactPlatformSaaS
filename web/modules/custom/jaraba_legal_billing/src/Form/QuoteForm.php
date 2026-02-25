<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_billing\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario de creacion/edicion de Presupuestos.
 */
class QuoteForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'references' => [
        'label' => $this->t('References'),
        'icon' => ['category' => 'business', 'name' => 'briefcase'],
        'description' => $this->t('Tenant, professional and source inquiry.'),
        'fields' => ['tenant_id', 'provider_id', 'inquiry_id'],
      ],
      'identification' => [
        'label' => $this->t('Identification'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'description' => $this->t('Quote title and basic identification.'),
        'fields' => ['title'],
      ],
      'client' => [
        'label' => $this->t('Client Data'),
        'icon' => ['category' => 'users', 'name' => 'user'],
        'description' => $this->t('Client contact details.'),
        'fields' => ['client_name', 'client_email', 'client_phone', 'client_company', 'client_nif'],
      ],
      'content' => [
        'label' => $this->t('Content'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('Introductory text, payment terms and notes.'),
        'fields' => ['introduction', 'payment_terms', 'notes'],
      ],
      'amounts' => [
        'label' => $this->t('Amounts'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'description' => $this->t('Subtotal, discounts, taxes and total.'),
        'fields' => ['discount_percent', 'tax_rate'],
      ],
      'lifecycle' => [
        'label' => $this->t('Lifecycle'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Quote status and validity dates.'),
        'fields' => ['status', 'valid_until'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'fiscal', 'name' => 'coins'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
    return $result;
  }

}
