<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar AgroCertification.
 */
class AgroCertificationForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'certification' => [
        'label' => $this->t('Certificación'),
        'icon' => ['category' => 'ui', 'name' => 'award'],
        'fields' => ['name', 'certification_type', 'certifier', 'certificate_number', 'description'],
      ],
      'validity' => [
        'label' => $this->t('Vigencia'),
        'icon' => ['category' => 'ui', 'name' => 'calendar'],
        'fields' => ['issue_date', 'expiry_date'],
      ],
      'association' => [
        'label' => $this->t('Asociación'),
        'icon' => ['category' => 'verticals', 'name' => 'agro'],
        'fields' => ['producer_id', 'document'],
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
    return ['category' => 'ui', 'name' => 'award'];
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
