<?php

declare(strict_types=1);

namespace Drupal\jaraba_interactive\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración para InteractiveContent settings.
 *
 * Requerido para el patrón 4-YAML: ancla las tabs de Field UI.
 */
class InteractiveContentSettingsForm extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'interactive_content_settings_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form['description'] = [
            '#type' => 'markup',
            '#markup' => '<p>' . $this->t('Utiliza las pestañas de arriba para administrar los campos y la visualización de Contenido Interactivo.') . '</p>',
        ];

        $form['info'] = [
            '#type' => 'details',
            '#title' => $this->t('Información del Sistema'),
            '#open' => TRUE,
        ];

        $form['info']['types'] = [
            '#type' => 'markup',
            '#markup' => '<p><strong>' . $this->t('Tipos de contenido soportados:') . '</strong></p>
        <ul>
          <li>' . $this->t('Question Set - Cuestionarios y evaluaciones') . '</li>
          <li>' . $this->t('Interactive Video - Videos con checkpoints') . '</li>
          <li>' . $this->t('Course Presentation - Presentaciones con diapositivas') . '</li>
          <li>' . $this->t('Branching Scenario - Escenarios ramificados') . '</li>
          <li>' . $this->t('Drag and Drop - Ejercicios de arrastrar y soltar') . '</li>
          <li>' . $this->t('Essay - Respuestas extensas con IA') . '</li>
        </ul>',
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
