<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar ProductAgro.
 */
class ProductAgroForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'basic' => [
        'label' => $this->t('Información básica'),
        'icon' => ['category' => 'verticals', 'name' => 'agro'],
        'fields' => ['name', 'sku', 'description_short', 'description', 'image'],
      ],
      'catalog' => [
        'label' => $this->t('Catálogo'),
        'icon' => ['category' => 'commerce', 'name' => 'tag'],
        'fields' => ['category', 'origin', 'unit', 'producer_id'],
      ],
      'pricing' => [
        'label' => $this->t('Precio y stock'),
        'icon' => ['category' => 'commerce', 'name' => 'cart'],
        'fields' => ['price', 'currency_code', 'stock'],
      ],
      'config' => [
        'label' => $this->t('Configuración'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'fields' => ['tenant_id', 'status'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'verticals', 'name' => 'agro'];
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
