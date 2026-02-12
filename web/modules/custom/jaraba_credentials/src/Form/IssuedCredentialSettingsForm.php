<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración para IssuedCredential.
 *
 * Permite el acceso a Field UI para la entidad.
 */
class IssuedCredentialSettingsForm extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'issued_credential_settings_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form['issued_credential_settings'] = [
            '#markup' => $this->t('Utiliza las pestañas superiores para administrar los campos de la entidad Credencial Emitida.'),
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        // No hay configuración adicional que guardar.
    }

}
