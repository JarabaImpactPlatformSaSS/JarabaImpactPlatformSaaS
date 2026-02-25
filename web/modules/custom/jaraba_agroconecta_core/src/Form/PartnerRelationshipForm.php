<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para la entidad PartnerRelationship.
 */
class PartnerRelationshipForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'partner' => [
        'label' => $this->t('Partner'),
        'icon' => ['category' => 'business', 'name' => 'building'],
        'fields' => ['partner_name', 'partner_email', 'partner_type', 'producer_id'],
      ],
      'access' => [
        'label' => $this->t('Acceso'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'fields' => ['access_level', 'allowed_products', 'allowed_categories'],
      ],
      'config' => [
        'label' => $this->t('Configuración'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'fields' => ['status', 'notes', 'tenant_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'business', 'name' => 'building'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    /** @var \Drupal\jaraba_agroconecta_core\Entity\PartnerRelationship $entity */
    $entity = $this->getEntity();

    // Generar access_token securizado si es nueva relación.
    if ($entity->isNew() && empty($entity->get('access_token')->value)) {
      $entity->set('access_token', bin2hex(random_bytes(32)));
    }

    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
