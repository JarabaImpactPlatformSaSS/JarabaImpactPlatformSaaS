<?php

namespace Drupal\ai_provider_google_vertex\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\Service\AiProviderFormHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Google Vertex Provider API access.
 */
class VertexConfigForm extends ConfigFormBase {

  /**
   * Config settings.
   */
  const CONFIG_NAME = 'ai_provider_google_vertex.settings';

  /**
   * Constructs a new Google Vertex Provider Config object.
   */
  final public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typed_config_manager,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AiProviderPluginManager $aiProviderManager,
    protected AiProviderFormHelper $formHelper,
  ) {
    parent::__construct($config_factory, $typed_config_manager);
  }

  /**
   * {@inheritdoc}
   */
  final public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('entity_type.manager'),
      $container->get('ai.provider'),
      $container->get('ai.form_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_vertex_ai_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::CONFIG_NAME,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::CONFIG_NAME);

    $form['general_credential_file'] = [
      '#type' => 'key_select',
      '#title' => $this->t('General Google Credentials'),
      '#description' => $this->t('<br>Read more at <a href="https://cloud.google.com/docs/authentication/application-default-credentials">https://cloud.google.com/docs/authentication/application-default-credentials</a>.'),
      '#default_value' => $config->get('general_credential_file'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
    ];

    $provider = $this->aiProviderManager->createInstance('google_vertex');
    $form['models'] = $this->formHelper->getModelsTable($form, $form_state, $provider);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config(static::CONFIG_NAME)
      ->set('general_credential_file', $form_state->getValue('general_credential_file'))
      ->save();
  }

}
