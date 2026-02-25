<?php

declare(strict_types=1);

namespace Drupal\jaraba_multiregion\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario de creacion/edicion de tipos de cambio.
 */
class CurrencyRateForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'exchange_rate' => [
        'label' => $this->t('Exchange Rate'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'description' => $this->t('Source and destination currencies with the exchange rate.'),
        'fields' => ['from_currency', 'to_currency', 'rate'],
      ],
      'metadata' => [
        'label' => $this->t('Metadata'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Data source and fetch timestamp.'),
        'fields' => ['source', 'fetched_at'],
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
