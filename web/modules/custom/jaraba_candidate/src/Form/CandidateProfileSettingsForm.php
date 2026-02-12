<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Settings form for Candidate Profile entity - provides Field UI integration.
 */
class CandidateProfileSettingsForm extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'candidate_profile_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form['#prefix'] = '<div class="entity-settings-intro">';
        $form['#suffix'] = '</div>';

        $form['title'] = [
            '#markup' => '<h2>' . $this->t('Configuración de Perfiles de Candidato') . '</h2>',
        ];

        $form['description'] = [
            '#markup' => '<p>' . $this->t('Utiliza las pestañas superiores para gestionar los campos y la presentación de las entidades Perfil de Candidato.') . '</p>',
        ];

        $form['actions_list'] = [
            '#theme' => 'item_list',
            '#title' => $this->t('Acciones disponibles'),
            '#items' => [
                $this->t('<strong>Administrar campos</strong>: Añadir, editar o eliminar campos personalizados del perfil.'),
                $this->t('<strong>Administrar visualización del formulario</strong>: Configurar cómo se muestran los campos en el formulario de edición.'),
                $this->t('<strong>Gestionar presentación</strong>: Configurar cómo se visualizan los perfiles públicamente.'),
            ],
        ];

        $form['help'] = [
            '#type' => 'details',
            '#title' => $this->t('Ayuda'),
            '#open' => FALSE,
            'content' => [
                '#markup' => '<p>' . $this->t('Los campos base del perfil (nombre, email, experiencia, etc.) están definidos en código. Puedes añadir campos adicionales desde "Administrar campos" para extender la información capturada.') . '</p>',
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
