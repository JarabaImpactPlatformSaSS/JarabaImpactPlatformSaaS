<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\jaraba_credentials\Service\CredentialIssuer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formulario para crear/editar IssuedCredential.
 */
class IssuedCredentialForm extends ContentEntityForm
{

    /**
     * Servicio de emisión de credenciales.
     */
    protected CredentialIssuer $issuer;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        $instance = parent::create($container);
        $instance->issuer = $container->get('jaraba_credentials.issuer');
        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $result = parent::save($form, $form_state);

        // Auto-firmar si no tiene firma
        /** @var \Drupal\jaraba_credentials\Entity\IssuedCredential $credential */
        $credential = $this->entity;
        $signature = $credential->get('signature')->value ?? '';

        if (empty($signature)) {
            try {
                $this->issuer->signExistingCredential($credential);
                $this->messenger()->addStatus($this->t('Credencial #@id firmada criptográficamente.', [
                    '@id' => $credential->id(),
                ]));
            } catch (\Exception $e) {
                $this->messenger()->addWarning($this->t('No se pudo firmar la credencial: @error', [
                    '@error' => $e->getMessage(),
                ]));
            }
        }

        $messageArgs = ['@id' => $this->entity->id()];
        $message = $result === SAVED_NEW
            ? $this->t('Credencial #@id emitida correctamente.', $messageArgs)
            : $this->t('Credencial #@id actualizada.', $messageArgs);

        $this->messenger()->addStatus($message);

        $form_state->setRedirectUrl($this->entity->toUrl('collection'));

        return $result;
    }

}
