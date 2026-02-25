<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing marketplace connectors.
 */
class ConnectorForm extends PremiumEntityFormBase {

  protected function getSectionDefinitions(): array {
    return [
      'basic_info' => [
        'label' => $this->t('Basic Information'),
        'icon' => ['category' => 'ui', 'name' => 'link'],
        'description' => $this->t('Connector identity and metadata.'),
        'fields' => ['name', 'machine_name', 'description', 'category', 'icon', 'logo_url', 'provider', 'version'],
      ],
      'api' => [
        'label' => $this->t('API & Auth'),
        'icon' => ['category' => 'ui', 'name' => 'lock'],
        'description' => $this->t('Authentication and API configuration.'),
        'fields' => ['auth_type', 'api_base_url', 'config_schema', 'docs_url'],
      ],
      'publishing' => [
        'label' => $this->t('Publishing'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'description' => $this->t('Publication status and plan requirements.'),
        'fields' => ['publish_status', 'required_plans', 'supported_events', 'install_count'],
      ],
    ];
  }

  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'link'];
  }

  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
    return $result;
  }

}
