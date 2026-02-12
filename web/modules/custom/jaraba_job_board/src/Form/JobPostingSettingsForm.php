<?php

declare(strict_types=1);

namespace Drupal\jaraba_job_board\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for Job Posting entity - provides Field UI integration.
 */
class JobPostingSettingsForm extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'job_posting_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form['#prefix'] = '<div class="entity-settings-intro">';
        $form['#suffix'] = '</div>';

        $form['title'] = [
            '#markup' => '<h2>' . $this->t('Configuración de Ofertas de Empleo') . '</h2>',
        ];

        $form['description'] = [
            '#markup' => '<p>' . $this->t('Utiliza las pestañas superiores para gestionar los campos y la presentación de las Ofertas de Empleo.') . '</p>',
        ];

        $form['actions_list'] = [
            '#theme' => 'item_list',
            '#title' => $this->t('Acciones disponibles'),
            '#items' => [
                $this->t('<strong>Administrar campos</strong>: Añadir campos como beneficios, requisitos específicos, etc.'),
                $this->t('<strong>Administrar visualización del formulario</strong>: Configurar el formulario de publicación de ofertas.'),
                $this->t('<strong>Gestionar presentación</strong>: Configurar cómo se visualizan las ofertas en el portal.'),
            ],
        ];

        $form['help'] = [
            '#type' => 'details',
            '#title' => $this->t('Ayuda'),
            '#open' => FALSE,
            'content' => [
                '#markup' => '<p>' . $this->t('Las ofertas de empleo incluyen campos base para título, descripción, ubicación, salario y requisitos. Los campos adicionales mejoran el matching con candidatos.') . '</p>',
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
