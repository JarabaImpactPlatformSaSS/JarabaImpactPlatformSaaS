<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar preferencias de notificación AgroConecta.
 */
class NotificationPreferenceAgroForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'type' => [
        'label' => $this->t('Tipo de notificación'),
        'icon' => ['category' => 'ui', 'name' => 'bell'],
        'fields' => ['notification_type'],
      ],
      'channels' => [
        'label' => $this->t('Canales de notificación'),
        'icon' => ['category' => 'ui', 'name' => 'bell'],
        'description' => $this->t('Seleccione los canales por los que desea recibir este tipo de notificación.'),
        'fields' => ['channel_email', 'channel_push', 'channel_sms', 'channel_in_app'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'bell'];
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
