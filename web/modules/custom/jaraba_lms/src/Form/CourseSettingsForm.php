<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for Course entity - provides Field UI integration.
 */
class CourseSettingsForm extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'lms_course_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form['#prefix'] = '<div class="entity-settings-intro">';
        $form['#suffix'] = '</div>';

        $form['title'] = [
            '#markup' => '<h2>' . $this->t('Configuración de Cursos') . '</h2>',
        ];

        $form['description'] = [
            '#markup' => '<p>' . $this->t('Utiliza las pestañas superiores para gestionar los campos y la presentación de las entidades Curso.') . '</p>',
        ];

        $form['actions_list'] = [
            '#theme' => 'item_list',
            '#title' => $this->t('Acciones disponibles'),
            '#items' => [
                $this->t('<strong>Administrar campos</strong>: Añadir campos personalizados como requisitos, nivel de dificultad, etc.'),
                $this->t('<strong>Administrar visualización del formulario</strong>: Configurar el formulario de creación/edición de cursos.'),
                $this->t('<strong>Gestionar presentación</strong>: Configurar cómo se visualizan los cursos en el catálogo.'),
            ],
        ];

        $form['help'] = [
            '#type' => 'details',
            '#title' => $this->t('Ayuda'),
            '#open' => FALSE,
            'content' => [
                '#markup' => '<p>' . $this->t('Los cursos incluyen campos base para título, descripción, lecciones y estado. Puedes añadir campos adicionales desde "Administrar campos".') . '</p>',
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
