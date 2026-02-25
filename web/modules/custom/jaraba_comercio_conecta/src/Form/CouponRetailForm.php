<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing retail coupons.
 */
class CouponRetailForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'info_cupon' => [
        'label' => $this->t('Informacion del Cupon'),
        'icon' => ['category' => 'commerce', 'name' => 'tag'],
        'description' => $this->t('Datos principales del cupon de descuento.'),
        'fields' => ['code', 'description', 'discount_type', 'discount_value', 'min_order_amount', 'merchant_id'],
      ],
      'limites' => [
        'label' => $this->t('Limites de Uso'),
        'icon' => ['category' => 'ui', 'name' => 'lock'],
        'description' => $this->t('Control de usos del cupon.'),
        'fields' => ['max_uses', 'max_uses_per_user', 'current_uses'],
      ],
      'vigencia' => [
        'label' => $this->t('Vigencia'),
        'icon' => ['category' => 'ui', 'name' => 'calendar'],
        'description' => $this->t('Periodo de validez del cupon.'),
        'fields' => ['valid_from', 'valid_until', 'status'],
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
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    // Computed field: current_uses is read-only.
    if (isset($form['premium_section_limites']['current_uses'])) {
      $form['premium_section_limites']['current_uses']['#disabled'] = TRUE;
    }
    elseif (isset($form['current_uses'])) {
      $form['current_uses']['#disabled'] = TRUE;
    }

    return $form;
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
