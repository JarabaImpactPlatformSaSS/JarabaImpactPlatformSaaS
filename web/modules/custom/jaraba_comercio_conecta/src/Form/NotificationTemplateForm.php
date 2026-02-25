<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing notification templates.
 */
class NotificationTemplateForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'plantilla' => [
        'label' => $this->t('Plantilla de Notificacion'),
        'icon' => ['category' => 'ui', 'name' => 'bell'],
        'description' => $this->t('Configuracion de la plantilla de notificacion.'),
        'fields' => ['name', 'machine_name', 'channel', 'subject_template', 'body_template', 'is_active'],
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
