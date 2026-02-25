<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar QrCodeAgro.
 */
class QrCodeAgroForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'qr' => [
        'label' => $this->t('Código QR'),
        'icon' => ['category' => 'ui', 'name' => 'link'],
        'fields' => ['label', 'qr_type', 'destination_url'],
      ],
      'target' => [
        'label' => $this->t('Destino'),
        'icon' => ['category' => 'ui', 'name' => 'link'],
        'fields' => ['target_entity_type', 'target_entity_id'],
      ],
      'utm' => [
        'label' => $this->t('UTM / Tracking'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'fields' => ['utm_source', 'utm_medium', 'utm_campaign'],
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
    return ['category' => 'ui', 'name' => 'link'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    /** @var \Drupal\jaraba_agroconecta_core\Entity\QrCodeAgro $entity */
    $entity = $this->getEntity();

    // Generar short_code si es nuevo.
    if ($entity->isNew() && empty($entity->get('short_code')->value)) {
      $entity->set('short_code', substr(bin2hex(random_bytes(6)), 0, 12));
    }

    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
