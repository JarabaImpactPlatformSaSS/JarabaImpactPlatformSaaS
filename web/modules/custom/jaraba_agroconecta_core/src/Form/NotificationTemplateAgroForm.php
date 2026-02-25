<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar plantillas de notificación AgroConecta.
 */
class NotificationTemplateAgroForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identification' => [
        'label' => $this->t('Identificación'),
        'icon' => ['category' => 'ui', 'name' => 'bell'],
        'fields' => ['name', 'type', 'channel', 'language'],
      ],
      'content' => [
        'label' => $this->t('Contenido de la plantilla'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'fields' => ['subject', 'body', 'body_html', 'tokens'],
      ],
      'config' => [
        'label' => $this->t('Configuración'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'fields' => ['is_active'],
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
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    // Validar canal válido.
    $valid_channels = ['email', 'push', 'sms', 'in_app'];
    $channel = $form_state->getValue(['channel', 0, 'value']);
    if ($channel && !in_array($channel, $valid_channels)) {
      $form_state->setErrorByName('channel', $this->t('Canal no válido. Canales permitidos: @channels.', [
        '@channels' => implode(', ', $valid_channels),
      ]));
    }
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
