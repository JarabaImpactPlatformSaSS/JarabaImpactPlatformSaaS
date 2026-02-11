<?php

declare(strict_types=1);

namespace Drupal\jaraba_self_discovery\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración para Rueda de la Vida.
 *
 * PROPÓSITO:
 * Proporciona la ruta base para Field UI.
 * Permite configurar aspectos del módulo.
 */
class LifeWheelSettingsForm extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'life_wheel_settings_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form['info'] = [
            '#type' => 'markup',
            '#markup' => '<p>' . $this->t('Configuración de la entidad Rueda de la Vida. Usa las pestañas para gestionar campos y displays.') . '</p>',
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        // No hay configuración que guardar por ahora.
        $this->messenger()->addStatus($this->t('Configuración guardada.'));
    }

}
