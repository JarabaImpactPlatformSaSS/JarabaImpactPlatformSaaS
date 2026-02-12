<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración para ProducerProfile.
 *
 * Permite el acceso a Field UI para la entidad.
 */
class ProducerProfileSettingsForm extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'producer_profile_settings_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form['producer_profile_settings'] = [
            '#markup' => $this->t('Utiliza las pestañas superiores para administrar los campos de la entidad Perfil de Productor.'),
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
