<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para la entidad ProductDocument.
 */
class ProductDocumentForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'document' => [
        'label' => $this->t('Documento'),
        'icon' => ['category' => 'ui', 'name' => 'document'],
        'fields' => ['title', 'document_type', 'file_id', 'version', 'language_code'],
      ],
      'association' => [
        'label' => $this->t('Asociación'),
        'icon' => ['category' => 'verticals', 'name' => 'agro'],
        'fields' => ['producer_id', 'product_id'],
      ],
      'access' => [
        'label' => $this->t('Control de acceso'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'fields' => ['min_access_level', 'allowed_partner_types', 'is_auto_generated'],
      ],
      'validity' => [
        'label' => $this->t('Vigencia'),
        'icon' => ['category' => 'ui', 'name' => 'calendar'],
        'fields' => ['valid_from', 'valid_until'],
      ],
      'config' => [
        'label' => $this->t('Configuración'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'fields' => ['is_active', 'tenant_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'document'];
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
