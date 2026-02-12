<?php

declare(strict_types=1);

namespace Drupal\jaraba_self_discovery\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuracion para StrengthAssessment.
 *
 * Proporciona la ruta base para Field UI.
 */
class StrengthAssessmentSettingsForm extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'strength_assessment_settings_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form['info'] = [
            '#type' => 'markup',
            '#markup' => '<p>' . $this->t('Configuracion de la entidad Evaluacion de Fortalezas. Usa las pestanas para gestionar campos y displays.') . '</p>',
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        $this->messenger()->addStatus($this->t('Configuracion guardada.'));
    }

}
