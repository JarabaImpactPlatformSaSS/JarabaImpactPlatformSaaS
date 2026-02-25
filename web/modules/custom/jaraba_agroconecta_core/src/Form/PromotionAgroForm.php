<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario de creación/edición de PromotionAgro.
 */
class PromotionAgroForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'basic' => [
        'label' => $this->t('Información básica'),
        'icon' => ['category' => 'commerce', 'name' => 'tag'],
        'fields' => ['name', 'description'],
      ],
      'discount' => [
        'label' => $this->t('Descuento'),
        'icon' => ['category' => 'commerce', 'name' => 'cart'],
        'fields' => ['discount_type', 'discount_value', 'minimum_order', 'max_discount'],
      ],
      'validity' => [
        'label' => $this->t('Vigencia'),
        'icon' => ['category' => 'ui', 'name' => 'calendar'],
        'fields' => ['start_date', 'end_date'],
      ],
      'limits' => [
        'label' => $this->t('Límites de uso'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'fields' => ['max_uses', 'max_uses_per_user'],
      ],
      'targeting' => [
        'label' => $this->t('Segmentación'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'fields' => ['target_categories', 'target_products', 'bxgy_config'],
      ],
      'config' => [
        'label' => $this->t('Configuración'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'fields' => ['stackable', 'priority', 'is_active', 'tenant_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'commerce', 'name' => 'tag'];
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
