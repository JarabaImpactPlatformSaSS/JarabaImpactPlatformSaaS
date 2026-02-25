<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Premium form for creating/editing SecurityPolicy entities.
 */
class SecurityPolicyForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identification' => [
        'label' => $this->t('Identificación'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'description' => $this->t('Nombre, tenant y alcance.'),
        'fields' => ['name', 'tenant_id', 'scope'],
      ],
      'policy' => [
        'label' => $this->t('Configuración de Política'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Tipo, reglas y enforcement.'),
        'fields' => ['policy_type', 'rules', 'enforcement'],
      ],
      'status' => [
        'label' => $this->t('Estado'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'description' => $this->t('Activación de la política.'),
        'fields' => ['active'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'shield'];
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
