<?php

declare(strict_types=1);

namespace Drupal\jaraba_foc\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating financial transactions.
 *
 * Transactions are immutable once created. Editing and deletion are blocked
 * by the AccessHandler to preserve ledger integrity.
 */
class FinancialTransactionForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'monetary' => [
        'label' => $this->t('Monetary Data'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'description' => $this->t('Amount and currency.'),
        'fields' => ['amount', 'currency', 'description'],
      ],
      'classification' => [
        'label' => $this->t('Classification'),
        'icon' => ['category' => 'ui', 'name' => 'tag'],
        'description' => $this->t('Transaction type and recurrence.'),
        'fields' => ['transaction_type', 'is_recurring'],
      ],
      'relations' => [
        'label' => $this->t('Relations'),
        'icon' => ['category' => 'ui', 'name' => 'link'],
        'description' => $this->t('Related tenant, vertical, and campaign.'),
        'fields' => ['related_tenant', 'related_vertical', 'related_campaign'],
      ],
      'traceability' => [
        'label' => $this->t('Traceability'),
        'icon' => ['category' => 'analytics', 'name' => 'search'],
        'description' => $this->t('Source system and external references.'),
        'fields' => ['source_system', 'external_id', 'metadata'],
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
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    // Immutability notice.
    $form['immutability_notice'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['messages', 'messages--warning']],
      '#weight' => -1001,
      'message' => [
        '#markup' => '<strong>' . $this->t('Immutability Notice') . ':</strong> '
          . $this->t('Financial transactions are immutable. Once created, they cannot be edited or deleted. For corrections, register a compensating entry.'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $form_state->setRedirect('jaraba_foc.transactions');
    return $result;
  }

}
