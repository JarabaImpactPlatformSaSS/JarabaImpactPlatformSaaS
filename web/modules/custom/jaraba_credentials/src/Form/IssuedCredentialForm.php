<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;
use Drupal\jaraba_credentials\Service\CredentialIssuer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formulario para crear/editar IssuedCredential.
 */
class IssuedCredentialForm extends PremiumEntityFormBase {

  /**
   * Servicio de emision de credenciales.
   */
  protected CredentialIssuer $issuer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->issuer = $container->get('jaraba_credentials.issuer');
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
        'description' => $this->t('Credential URI, template and status.'),
        'fields' => ['credential_id_uri', 'template_id', 'status'],
      ],
      'recipient' => [
        'label' => $this->t('Recipient'),
        'icon' => ['category' => 'users', 'name' => 'user'],
        'description' => $this->t('Recipient user, email and name.'),
        'fields' => ['recipient_id', 'recipient_email', 'recipient_name'],
      ],
      'dates' => [
        'label' => $this->t('Dates'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Issuance and expiration dates.'),
        'fields' => ['issued_on', 'expires_on'],
      ],
      'evidence' => [
        'label' => $this->t('Evidence'),
        'icon' => ['category' => 'education', 'name' => 'course'],
        'description' => $this->t('Evidence of the achievement in JSON format.'),
        'fields' => ['evidence'],
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
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    // Auto-sign if no signature exists.
    /** @var \Drupal\jaraba_credentials\Entity\IssuedCredential $credential */
    $credential = $this->entity;
    $signature = $credential->get('signature')->value ?? '';

    if (empty($signature)) {
      try {
        $this->issuer->signExistingCredential($credential);
        $this->messenger()->addStatus($this->t('Credencial #@id firmada criptograficamente.', [
          '@id' => $credential->id(),
        ]));
      }
      catch (\Exception $e) {
        $this->messenger()->addWarning($this->t('No se pudo firmar la credencial: @error', [
          '@error' => $e->getMessage(),
        ]));
      }
    }

    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
    return $result;
  }

}
