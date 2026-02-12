<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración para PageExperiment.
 *
 * PROPÓSITO:
 * Este formulario es requerido por field_ui_base_route para
 * habilitar las pestañas de Field UI (Administrar campos, etc.)
 * en la ruta /admin/structure/experiment.
 *
 * ESPECIFICACIÓN: Entity Navigation Standards - Double Navigation Pattern
 */
class PageExperimentSettingsForm extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'page_experiment_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form['info'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['entity-settings-info']],
        ];

        $form['info']['description'] = [
            '#markup' => '<h3>' . $this->t('Configuración de Experimentos A/B') . '</h3>' .
                '<p>' . $this->t('Configura los campos y la presentación de la entidad Experimento A/B.') . '</p>' .
                '<p>' . $this->t('Usa las pestañas superiores para gestionar campos y formularios.') . '</p>',
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        // No hay configuración que guardar en este formulario básico.
    }

}
