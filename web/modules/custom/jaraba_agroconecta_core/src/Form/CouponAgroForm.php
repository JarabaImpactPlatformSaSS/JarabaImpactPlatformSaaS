<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario de creación/edición de CouponAgro.
 */
class CouponAgroForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'coupon' => [
        'label' => $this->t('Cupón'),
        'icon' => ['category' => 'commerce', 'name' => 'tag'],
        'fields' => ['code', 'promotion_id'],
      ],
      'limits' => [
        'label' => $this->t('Límites'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'fields' => ['max_uses', 'max_uses_per_user', 'minimum_order'],
      ],
      'validity' => [
        'label' => $this->t('Vigencia'),
        'icon' => ['category' => 'ui', 'name' => 'calendar'],
        'fields' => ['start_date', 'end_date'],
      ],
      'config' => [
        'label' => $this->t('Configuración'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'fields' => ['tenant_id', 'is_active'],
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

    // current_uses is a computed/read-only field.
    if (isset($form['premium_section_other']['current_uses'])) {
      $form['premium_section_other']['current_uses']['#disabled'] = TRUE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    /** @var \Drupal\jaraba_agroconecta_core\Entity\CouponAgro $entity */
    $entity = $this->getEntity();

    // Normalizar código a mayúsculas.
    $code = strtoupper(trim($entity->get('code')->value ?? ''));
    $entity->set('code', $code);

    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
