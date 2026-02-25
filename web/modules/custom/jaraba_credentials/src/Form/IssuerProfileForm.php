<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;
use Drupal\jaraba_credentials\Service\CryptographyService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formulario para crear/editar IssuerProfile.
 */
class IssuerProfileForm extends PremiumEntityFormBase {

  /**
   * Servicio de criptografia.
   */
  protected CryptographyService $cryptography;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->cryptography = $container->get('jaraba_credentials.cryptography');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'general' => [
        'label' => $this->t('General'),
        'icon' => ['category' => 'ui', 'name' => 'award'],
        'description' => $this->t('Issuer name, URL and contact information.'),
        'fields' => ['name', 'url', 'email', 'description'],
      ],
      'visual' => [
        'label' => $this->t('Visual'),
        'icon' => ['category' => 'media', 'name' => 'image'],
        'description' => $this->t('Issuer logo.'),
        'fields' => ['image'],
      ],
      'cryptography' => [
        'label' => $this->t('Cryptography'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Ed25519 public key and JSON URL for verification.'),
        'fields' => ['public_key', 'issuer_json_url'],
      ],
      'settings' => [
        'label' => $this->t('Settings'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Default issuer flag.'),
        'fields' => ['is_default'],
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
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    // Add checkbox to generate keys.
    if ($this->entity->isNew() || !$this->entity->hasKeys()) {
      $form['generate_keys'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Generar claves Ed25519 automaticamente'),
        '#description' => $this->t('Genera un par de claves Ed25519 para firmar credenciales. Las claves existentes seran reemplazadas.'),
        '#default_value' => $this->entity->isNew(),
        '#weight' => 100,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    // Generate keys if requested.
    if ($form_state->getValue('generate_keys')) {
      $this->generateAndSetKeys();
    }

    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
    return $result;
  }

  /**
   * Genera y asigna claves Ed25519 a la entidad.
   */
  protected function generateAndSetKeys(): void {
    try {
      $keys = $this->cryptography->generateKeyPair();

      // Store public key.
      $this->entity->set('public_key', $keys['public']);

      // Encrypt and store private key.
      $encryptedPrivate = $this->cryptography->encryptPrivateKey($keys['private']);
      $this->entity->set('private_key_encrypted', $encryptedPrivate);

      $this->messenger()->addStatus($this->t('Claves Ed25519 generadas correctamente.'));
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Error generando claves: @message', [
        '@message' => $e->getMessage(),
      ]));
    }
  }

}
