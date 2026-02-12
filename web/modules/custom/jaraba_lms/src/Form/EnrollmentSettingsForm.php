<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for Enrollment entity - provides Field UI integration.
 */
class EnrollmentSettingsForm extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'lms_enrollment_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form['#prefix'] = '<div class="entity-settings-intro">';
        $form['#suffix'] = '</div>';

        $form['title'] = [
            '#markup' => '<h2>' . $this->t('Configuración de Matrículas') . '</h2>',
        ];

        $form['description'] = [
            '#markup' => '<p>' . $this->t('Utiliza las pestañas superiores para gestionar los campos y la presentación de las Matrículas (inscripciones a cursos).') . '</p>',
        ];

        $form['actions_list'] = [
            '#theme' => 'item_list',
            '#title' => $this->t('Acciones disponibles'),
            '#items' => [
                $this->t('<strong>Administrar campos</strong>: Añadir campos como fecha límite, notas del instructor, etc.'),
                $this->t('<strong>Administrar visualización del formulario</strong>: Configurar el formulario de inscripción.'),
                $this->t('<strong>Gestionar presentación</strong>: Configurar cómo se visualizan las matrículas.'),
            ],
        ];

        $form['help'] = [
            '#type' => 'details',
            '#title' => $this->t('Ayuda'),
            '#open' => FALSE,
            'content' => [
                '#markup' => '<p>' . $this->t('Las matrículas vinculan usuarios a cursos, registran progreso y fechas de inscripción/completitud.') . '</p>',
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
