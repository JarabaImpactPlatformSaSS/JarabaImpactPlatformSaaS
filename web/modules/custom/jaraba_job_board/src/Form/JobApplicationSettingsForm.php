<?php

declare(strict_types=1);

namespace Drupal\jaraba_job_board\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for Job Application entity - provides Field UI integration.
 */
class JobApplicationSettingsForm extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'job_application_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form['#prefix'] = '<div class="entity-settings-intro">';
        $form['#suffix'] = '</div>';

        $form['title'] = [
            '#markup' => '<h2>' . $this->t('Configuración de Candidaturas') . '</h2>',
        ];

        $form['description'] = [
            '#markup' => '<p>' . $this->t('Utiliza las pestañas superiores para gestionar los campos y la presentación de las Candidaturas (solicitudes de empleo).') . '</p>',
        ];

        $form['actions_list'] = [
            '#theme' => 'item_list',
            '#title' => $this->t('Acciones disponibles'),
            '#items' => [
                $this->t('<strong>Administrar campos</strong>: Añadir campos como preguntas de filtrado, documentos adicionales, etc.'),
                $this->t('<strong>Administrar visualización del formulario</strong>: Configurar el formulario de solicitud.'),
                $this->t('<strong>Gestionar presentación</strong>: Configurar cómo se visualizan las candidaturas para empleadores.'),
            ],
        ];

        $form['help'] = [
            '#type' => 'details',
            '#title' => $this->t('Ayuda'),
            '#open' => FALSE,
            'content' => [
                '#markup' => '<p>' . $this->t('Las candidaturas registran las solicitudes de empleo con estado de pipeline (ATS), carta de presentación, CV y puntuación de matching.') . '</p>',
            ],
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        // Nothing to submit.
    }

}
