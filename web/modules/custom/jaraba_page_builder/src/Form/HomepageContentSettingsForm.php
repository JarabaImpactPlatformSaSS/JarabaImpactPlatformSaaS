<?php

namespace Drupal\jaraba_page_builder\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración para HomepageContent.
 *
 * Este formulario es requerido por field_ui_base_route para
 * habilitar la pestaña "Administrar campos" en Field UI.
 */
class HomepageContentSettingsForm extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'homepage_content_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form['info'] = [
            '#markup' => '<p>' . $this->t('Usa las pestañas "Administrar campos" y "Administrar presentación" para configurar los campos de la entidad Contenido Homepage.') . '</p>',
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
