<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\jaraba_credentials\Service\CryptographyService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formulario para crear/editar IssuerProfile.
 */
class IssuerProfileForm extends ContentEntityForm
{

    /**
     * Servicio de criptografía.
     */
    protected CryptographyService $cryptography;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        $instance = parent::create($container);
        $instance->cryptography = $container->get('jaraba_credentials.cryptography');
        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form = parent::buildForm($form, $form_state);

        // Añadir botón para generar claves
        if ($this->entity->isNew() || !$this->entity->hasKeys()) {
            $form['generate_keys'] = [
                '#type' => 'checkbox',
                '#title' => $this->t('Generar claves Ed25519 automáticamente'),
                '#description' => $this->t('Genera un par de claves Ed25519 para firmar credenciales. Las claves existentes serán reemplazadas.'),
                '#default_value' => $this->entity->isNew(),
                '#weight' => 100,
            ];
        }

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        // Generar claves si se solicitó
        if ($form_state->getValue('generate_keys')) {
            $this->generateAndSetKeys();
        }

        $result = parent::save($form, $form_state);

        $messageArgs = ['%label' => $this->entity->label()];
        $message = $result === SAVED_NEW
            ? $this->t('Perfil de emisor %label creado.', $messageArgs)
            : $this->t('Perfil de emisor %label actualizado.', $messageArgs);

        $this->messenger()->addStatus($message);

        $form_state->setRedirectUrl($this->entity->toUrl('collection'));

        return $result;
    }

    /**
     * Genera y asigna claves Ed25519 a la entidad.
     */
    protected function generateAndSetKeys(): void
    {
        try {
            $keys = $this->cryptography->generateKeyPair();

            // Almacenar clave pública
            $this->entity->set('public_key', $keys['public']);

            // Encriptar y almacenar clave privada
            $encryptedPrivate = $this->cryptography->encryptPrivateKey($keys['private']);
            $this->entity->set('private_key_encrypted', $encryptedPrivate);

            $this->messenger()->addStatus($this->t('Claves Ed25519 generadas correctamente.'));
        } catch (\Exception $e) {
            $this->messenger()->addError($this->t('Error generando claves: @message', [
                '@message' => $e->getMessage(),
            ]));
        }
    }

}
