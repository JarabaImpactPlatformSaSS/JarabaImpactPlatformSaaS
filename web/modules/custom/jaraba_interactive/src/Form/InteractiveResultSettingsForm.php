<?php

declare(strict_types=1);

namespace Drupal\jaraba_interactive\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración para InteractiveResult settings.
 *
 * Requerido para el patrón 4-YAML: ancla las tabs de Field UI.
 */
class InteractiveResultSettingsForm extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'interactive_result_settings_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form['description'] = [
            '#type' => 'markup',
            '#markup' => '<p>' . $this->t('Utiliza las pestañas de arriba para administrar los campos y la visualización de Resultados Interactivos.') . '</p>',
        ];

        $form['info'] = [
            '#type' => 'details',
            '#title' => $this->t('Información'),
            '#open' => TRUE,
        ];

        $form['info']['details'] = [
            '#type' => 'markup',
            '#markup' => '<p>' . $this->t('Los resultados se generan automáticamente cuando los usuarios completan contenido interactivo. Incluyen puntuación, tiempo empleado, y respuestas detalladas.') . '</p>',
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        // Este formulario es solo informativo para anclar Field UI.
    }

}
