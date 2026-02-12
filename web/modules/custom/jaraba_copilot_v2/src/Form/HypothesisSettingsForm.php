<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form de configuración para la entidad Hypothesis.
 *
 * Este formulario sirve como base para Field UI.
 */
class HypothesisSettingsForm extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'hypothesis_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form['info'] = [
            '#markup' => $this->t('<p>Configuración de la entidad Hipótesis. Usa las pestañas superiores para gestionar campos y formularios.</p>'),
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        // No action needed.
    }

}
